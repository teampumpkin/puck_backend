<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationHelper
{
    protected $fcmUrl;
    protected $fcmServerKey;

    public function __construct()
    {
        $this->fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        $this->fcmServerKey = config('services.fcm.server_key');

        // Validate configuration
        if (empty($this->fcmServerKey)) {
            Log::warning('FCM server key is not configured. Push notifications will not work.');
        }
    }

    /**
     * Send notification to specific device token
     */
    public function sendToToken($token, $title, $body, $data = [])
    {
        // Validate inputs
        if (empty($token) || empty($this->fcmServerKey)) {
            Log::error('FCM token or server key is missing', [
                'token' => $token,
                'has_server_key' => !empty($this->fcmServerKey)
            ]);
            return false;
        }

        try {
            $payload = [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => $this->getBadgeCount($data),
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'priority' => 'high',
                    'timestamp' => now()->toISOString(),
                ]),
                'priority' => 'high',
                'content_available' => true,
                'mutable_content' => true,
            ];

            // Add icon to notification if provided
            if (isset($data['icon'])) {
                $payload['notification']['icon'] = $data['icon'];
            }

            // Add image to notification if provided
            if (isset($data['image_url'])) {
                $payload['notification']['image'] = $data['image_url'];
            }

            // Add Android specific config
            $payload['android'] = [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => 'high_importance_channel',
                    'icon' => $data['icon'] ?? 'ic_notification',
                    'color' => $data['icon_color'] ?? '#FF0000',
                ]
            ];

            // Add iOS specific config
            $payload['apns'] = [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => $this->getBadgeCount($data),
                        'content-available' => 1,
                    ]
                ],
                'fcm_options' => [
                    'image' => $data['image_url'] ?? null,
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30) // 30 second timeout
                ->retry(3, 100) // Retry 3 times with 100ms delay
                ->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('FCM notification sent successfully', [
                    'token' => $this->maskToken($token),
                    'message_id' => $responseData['message_id'] ?? null,
                    'success' => $responseData['success'] ?? 0,
                    'failure' => $responseData['failure'] ?? 0,
                    'data' => $data
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['message_id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                $errorResponse = $response->body();
                Log::error('FCM notification failed', [
                    'token' => $this->maskToken($token),
                    'status_code' => $response->status(),
                    'response' => $errorResponse,
                    'payload' => $payload
                ]);

                // Handle specific FCM errors
                $this->handleFcmError($errorResponse, $token);

                return [
                    'success' => false,
                    'error' => $errorResponse,
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('FCM notification exception: ' . $e->getMessage(), [
                'token' => $this->maskToken($token),
                'exception' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to multiple devices
     */
    public function sendToTokens(array $tokens, $title, $body, $data = [])
    {
        if (empty($tokens)) {
            Log::warning('No tokens provided for batch notification');
            return [];
        }

        // FCM allows max 100 devices per multicast
        if (count($tokens) > 100) {
            return $this->sendLargeBatch($tokens, $title, $body, $data);
        }

        try {
            $payload = [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => $this->getBadgeCount($data),
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'priority' => 'high',
                    'timestamp' => now()->toISOString(),
                ]),
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('FCM multicast notification sent', [
                    'token_count' => count($tokens),
                    'success' => $responseData['success'] ?? 0,
                    'failure' => $responseData['failure'] ?? 0,
                    'results' => $responseData['results'] ?? []
                ]);

                // Process results to map tokens to their outcomes
                $results = [];
                if (isset($responseData['results'])) {
                    foreach ($responseData['results'] as $index => $result) {
                        $token = $tokens[$index] ?? 'unknown';
                        $results[$token] = [
                            'success' => isset($result['message_id']),
                            'message_id' => $result['message_id'] ?? null,
                            'error' => $result['error'] ?? null,
                        ];

                        // Handle token errors
                        if (isset($result['error'])) {
                            $this->handleTokenError($token, $result['error']);
                        }
                    }
                }

                return $results;
            } else {
                Log::error('FCM multicast notification failed', [
                    'token_count' => count($tokens),
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);

                return array_fill_keys($tokens, ['success' => false, 'error' => 'HTTP Error']);
            }
        } catch (\Exception $e) {
            Log::error('FCM multicast notification exception: ' . $e->getMessage(), [
                'token_count' => count($tokens),
                'exception' => $e->getTraceAsString()
            ]);

            return array_fill_keys($tokens, ['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send to large batch of tokens (more than 100)
     */
    protected function sendLargeBatch(array $tokens, $title, $body, $data = [])
    {
        $chunks = array_chunk($tokens, 100); // Split into chunks of 100
        $allResults = [];

        Log::info('Sending large batch notification', [
            'total_tokens' => count($tokens),
            'chunks' => count($chunks)
        ]);

        foreach ($chunks as $chunkIndex => $chunkTokens) {
            Log::info('Processing chunk', [
                'chunk_index' => $chunkIndex + 1,
                'chunk_size' => count($chunkTokens)
            ]);

            $chunkResults = $this->sendToTokens($chunkTokens, $title, $body, $data);
            $allResults = array_merge($allResults, $chunkResults);

            // Small delay between chunks to avoid rate limiting
            if ($chunkIndex < count($chunks) - 1) {
                usleep(100000); // 100ms delay
            }
        }

        return $allResults;
    }

    /**
     * Send notification to topic
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        if (empty($topic) || empty($this->fcmServerKey)) {
            Log::error('FCM topic or server key is missing');
            return false;
        }

        try {
            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'priority' => 'high',
                    'timestamp' => now()->toISOString(),
                ]),
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('FCM topic notification sent successfully', [
                    'topic' => $topic,
                    'message_id' => $responseData['message_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['message_id'] ?? null,
                ];
            } else {
                Log::error('FCM topic notification failed', [
                    'topic' => $topic,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('FCM topic notification exception: ' . $e->getMessage(), [
                'topic' => $topic,
                'exception' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Subscribe device token to topic
     */
    public function subscribeToTopic($token, $topic)
    {
        return $this->manageTopicSubscription($token, $topic, 'subscribe');
    }

    /**
     * Unsubscribe device token from topic
     */
    public function unsubscribeFromTopic($token, $topic)
    {
        return $this->manageTopicSubscription($token, $topic, 'unsubscribe');
    }

    /**
     * Manage topic subscription
     */
    protected function manageTopicSubscription($token, $topic, $operation)
    {
        if (empty($token) || empty($topic) || empty($this->fcmServerKey)) {
            Log::error('Token, topic or server key missing for topic operation');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post('https://iid.googleapis.com/iid/v1:batch' . ucfirst($operation), [
                    'to' => '/topics/' . $topic,
                    'registration_tokens' => [$token],
                ]);

            if ($response->successful()) {
                Log::info("FCM topic {$operation} successful", [
                    'token' => $this->maskToken($token),
                    'topic' => $topic
                ]);
                return true;
            } else {
                Log::error("FCM topic {$operation} failed", [
                    'token' => $this->maskToken($token),
                    'topic' => $topic,
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("FCM topic {$operation} exception: " . $e->getMessage(), [
                'token' => $this->maskToken($token),
                'topic' => $topic
            ]);
            return false;
        }
    }

    /**
     * Send silent notification (data-only)
     */
    public function sendSilentNotification($token, $data = [])
    {
        if (empty($token) || empty($this->fcmServerKey)) {
            return false;
        }

        try {
            $payload = [
                'to' => $token,
                'data' => array_merge($data, [
                    'priority' => 'high',
                    'content_available' => true,
                    'timestamp' => now()->toISOString(),
                ]),
                'priority' => 'high',
                'content_available' => true,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                Log::info('FCM silent notification sent successfully', [
                    'token' => $this->maskToken($token),
                    'data' => $data
                ]);
                return true;
            } else {
                Log::error('FCM silent notification failed', [
                    'token' => $this->maskToken($token),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM silent notification exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate FCM token
     */
    public function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        // Send a test silent notification to validate token
        $result = $this->sendSilentNotification($token, ['type' => 'validation']);

        return $result === true;
    }

    /**
     * Handle FCM errors and take appropriate actions
     */
    protected function handleFcmError($errorResponse, $token)
    {
        $error = json_decode($errorResponse, true);
        $errorMessage = $error['error']['message'] ?? $errorResponse;

        // Handle common FCM errors
        if (
            str_contains($errorMessage, 'NotRegistered') ||
            str_contains($errorMessage, 'InvalidRegistration')
        ) {
            Log::warning('FCM token is invalid or not registered', [
                'token' => $this->maskToken($token),
                'error' => $errorMessage
            ]);

            // Here you can trigger an event to remove the invalid token from your database
            event(new \App\Events\InvalidFcmToken($token));
        } elseif (str_contains($errorMessage, 'MismatchSenderId')) {
            Log::error('FCM sender ID mismatch - check FCM configuration', [
                'token' => $this->maskToken($token)
            ]);
        } elseif (str_contains($errorMessage, 'MessageRateExceeded')) {
            Log::warning('FCM message rate exceeded', [
                'token' => $this->maskToken($token)
            ]);
        } elseif (str_contains($errorMessage, 'DeviceMessageRateExceeded')) {
            Log::warning('FCM device message rate exceeded', [
                'token' => $this->maskToken($token)
            ]);
        } elseif (str_contains($errorMessage, 'Unavailable')) {
            Log::warning('FCM service temporarily unavailable', [
                'token' => $this->maskToken($token)
            ]);
        }
    }

    /**
     * Handle token-specific errors from multicast
     */
    protected function handleTokenError($token, $error)
    {
        if (
            str_contains($error, 'NotRegistered') ||
            str_contains($error, 'InvalidRegistration')
        ) {

            Log::warning('Invalid FCM token in multicast', [
                'token' => $this->maskToken($token),
                'error' => $error
            ]);

            // Trigger event to handle invalid token
            event(new \App\Events\InvalidFcmToken($token));
        }
    }

    /**
     * Get badge count from data or calculate
     */
    protected function getBadgeCount($data)
    {
        return $data['badge'] ?? 1; // Default to 1 if not specified
    }

    /**
     * Mask token for logging (privacy)
     */
    protected function maskToken($token)
    {
        if (strlen($token) <= 8) {
            return '***';
        }

        return substr($token, 0, 4) . '...' . substr($token, -4);
    }

    /**
     * Get delivery statistics (mock - you might want to implement proper tracking)
     */
    public function getDeliveryStatistics($timeRange = '24h')
    {
        // This is a mock implementation
        // In a real scenario, you might want to track deliveries in database
        return [
            'sent' => 0,
            'delivered' => 0,
            'failed' => 0,
            'time_range' => $timeRange
        ];
    }

    /**
     * Test FCM configuration
     */
    public function testConfiguration()
    {
        if (empty($this->fcmServerKey)) {
            return [
                'success' => false,
                'error' => 'FCM server key not configured'
            ];
        }

        // Try to send a test notification to a non-existent token to check configuration
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->post($this->fcmUrl, [
                    'to' => 'invalid_token',
                    'notification' => ['title' => 'Test', 'body' => 'Test'],
                ]);

            // Even with invalid token, if we get a proper FCM response, configuration is correct
            if ($response->status() === 200) {
                return [
                    'success' => true,
                    'message' => 'FCM configuration is valid'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid FCM configuration',
                    'status_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
