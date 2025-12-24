<?php

namespace App\Helpers;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\ApnsConfig;

class PushNotificationHelper
{
    protected Messaging $messaging;

    public function __construct()
    {
        $this->messaging = app('firebase.messaging');
    }

    /**
     * Send notification to single FCM token
     */
    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($this->flattenData($data))
                ->withAndroidConfig([
                    'priority' => 'high',
                ])
                ->withDefaultSounds()
                ->withApnsConfig(
                    ApnsConfig::new()
                        ->withImmediatePriority()

                        ->withBadge($this->getBadgeCount($data))

                );

            $this->messaging->send($message);

            Log::info('FCM sent to token', [
                'token' => $this->maskToken($token),
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('FCM Token Send Error', [
                'token' => $this->maskToken($token),
                'error' => $e->getMessage(),
            ]);

            $this->handleTokenError($token, $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function flattenData($data)
    {
        if (is_string($data) && $this->isJson($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data)) {
            return [];
        }

        $flat = [];

        foreach ($data as $key => $value) {
            if ($value instanceof \Illuminate\Database\Eloquent\Model || is_object($value)) {
                $flat[$key] = json_encode($value->toArray(), JSON_UNESCAPED_UNICODE);
            } elseif (is_array($value)) {
                $flat[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $flat[$key] = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $flat[$key] = '';
            } else {
                $flat[$key] = (string) $value;
            }
        }

        return $flat;
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Send to multiple tokens (batch)
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
    {
        $messages = [];
        foreach ($tokens as $token) {
            $messages[] = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($this->flattenData($data));
        }

        try {
            $report = $this->messaging->sendAll($messages);

            Log::info('Batch FCM sent', [
                'success' => $report->successes()->count(),
                'failed' => $report->failures()->count(),
            ]);

            return $report;
        } catch (\Throwable $e) {
            Log::error('FCM Batch Send Error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send notification to topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($this->flattenData($data));

            $this->messaging->send($message);

            Log::info("FCM sent to topic {$topic}");

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('FCM Topic Error', ['topic' => $topic, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Subscribe token to topic
     */
    public function subscribeToTopic(string $token, string $topic)
    {
        try {
            $this->messaging->subscribeToTopic($topic, $token);
            Log::info("Subscribed {$this->maskToken($token)} to {$topic}");
            return true;
        } catch (\Throwable $e) {
            Log::error('Subscribe Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Unsubscribe token from topic
     */
    public function unsubscribeFromTopic(string $token, string $topic)
    {
        try {
            $this->messaging->unsubscribeFromTopic($topic, $token);
            Log::info("Unsubscribed {$this->maskToken($token)} from {$topic}");
            return true;
        } catch (\Throwable $e) {
            Log::error('Unsubscribe Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send silent notification (data only)
     */
    public function sendSilentNotification(string $token, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withData($this->flattenData(array_merge($data, ['silent' => true])));

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            Log::error('Silent Notification Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Validate FCM token (try silent message)
     */
    public function validateToken(string $token)
    {
        return $this->sendSilentNotification($token, ['validation' => true]);
    }

    /**
     * Handle invalid tokens
     */
    protected function handleTokenError($token, $error)
    {
        if (
            str_contains($error, 'registration-token-not-registered') ||
            str_contains($error, 'invalid-argument')
        ) {
            Log::warning('Invalid Token', ['token' => $this->maskToken($token)]);
            event(new \App\Events\InvalidFcmToken($token));
        }
    }

    /**
     * Mock statistics
     */
    public function getDeliveryStatistics($timeRange = '24h')
    {
        return [
            'sent' => 0,
            'delivered' => 0,
            'failed' => 0,
            'range' => $timeRange,
        ];
    }

    /**
     * Test configuration
     */
    public function testConfiguration()
    {
        try {
            // Create a dummy invalid token
            $invalidToken = 'invalid-test-token-123';

            $message = CloudMessage::withTarget('token', $invalidToken)
                ->withNotification(
                    Notification::create('Test', 'Testing FCM Config')
                );

            // Try sending → Firebase will respond with an error (this is expected)
            $this->messaging->send($message);

            // If somehow success (should not)
            return [
                'success' => true,
                'message' => 'FCM configuration appears valid (unexpected success)'
            ];
        } catch (\Throwable $e) {

            $msg = $e->getMessage();

            // Firebase returns 400/404 with proper credentials
            if (
                str_contains($msg, 'registration-token-not-registered') ||
                str_contains($msg, 'invalid-argument') ||
                str_contains($msg, 'Requested entity was not found') ||
                str_contains($msg, 'Invalid registration token')
            ) {
                return [
                    'success' => true,
                    'message' => 'FCM configuration is valid (Firebase responded correctly).'
                ];
            }

            // If error is credential-based
            if (
                str_contains($msg, 'authentication') ||
                str_contains($msg, 'permission') ||
                str_contains($msg, 'Request had insufficient authentication')
            ) {
                return [
                    'success' => false,
                    'error' => 'Firebase credentials incorrect: ' . $msg,
                ];
            }

            return [
                'success' => false,
                'error' => 'Unexpected error: ' . $msg,
            ];
        }
    }

    /**
     * Badge count helper
     */
    protected function getBadgeCount(array $data)
    {
        return $data['badge'] ?? 1;
    }

    /**
     * Mask token in logs
     */
    protected function maskToken($token)
    {
        return substr($token, 0, 4) . '...' . substr($token, -4);
    }
}
