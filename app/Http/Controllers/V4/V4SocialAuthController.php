<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4User;
use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Contracts\ErrorTrackerInterface;

class V4SocialAuthController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    public const JWK_URL = 'https://appleid.apple.com/auth/keys';
    /**
     * Standard JSON response helper.
     */
    private function response(bool $success, string $message, $data = null, $errors = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * Google Login
     */
    public function handleGoogleCallback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token'      => 'required|string',
            'role'          => 'required|string|in:player,coach,scout,parent,team,academy,organizer,fan,adviser,evaluator,super-admin',
            'first_name'    => 'nullable|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'email'         => 'nullable|email',
            'profile_photo' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->response(false, 'Validation error', null, $validator->errors(), 422);
        }

        try {
            $googleResponse = Http::get(
                'https://oauth2.googleapis.com/tokeninfo',
                ['id_token' => $request->id_token]
            );

            if (! $googleResponse->ok()) {
                throw new Exception('Invalid Google ID token');
            }

            $payload = $googleResponse->json();

            if (! in_array($payload['iss'], ['accounts.google.com', 'https://accounts.google.com'])) {
                throw new Exception('Invalid token issuer');
            }

            $googleUser = (object) [
                'id'            => $payload['sub'],
                'email'         => $request->email ?? $payload['email'] ?? null,
                'first_name'    => $request->first_name ?? $payload['given_name'] ?? null,
                'last_name'     => $request->last_name ?? $payload['family_name'] ?? null,
                'profile_photo' => $request->profile_photo ?? $payload['picture'] ?? null,
                'role'          => $request->role,
            ];

            return $this->findOrCreateUser($googleUser, 'google');
        } catch (Exception $e) {
            Log::error("Google OAuth Error: {$e->getMessage()}", ['' => $request->id_token]);
            
            $this->errorTracker->captureException($e, [
                'action' => 'google_oauth',
                'role' => $request->role ?? null,
                'email' => $request->email ?? null,
            ]);
            
            return $this->response(false, 'Invalid Google Token', null, $e->getMessage(), 400);
        }
    }

    /**
     * Facebook Login
     */
    public function handleFacebookCallback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'access_token'  => 'required|string',
            'role'          => 'required|string|in:player,coach,scout,parent,team,academy,organizer,fan,adviser,evaluator,super-admin',
            'first_name'    => 'nullable|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'email'         => 'nullable|email',
            'profile_photo' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->response(false, 'Validation error', null, $validator->errors(), 422);
        }

        try {
            $facebookUser = Socialite::driver('facebook')->userFromToken($request->access_token);

            $facebookUser->first_name    = $request->first_name ?? ($facebookUser->user['first_name'] ?? '');
            $facebookUser->last_name     = $request->last_name ?? ($facebookUser->user['last_name'] ?? '');
            $facebookUser->profile_photo = $request->profile_photo ?? $facebookUser->avatar;
            $facebookUser->role          = $request->role;

            return $this->findOrCreateUser($facebookUser, 'facebook');
        } catch (Exception $e) {
            Log::error("Facebook OAuth Error: {$e->getMessage()}");
            
            $this->errorTracker->captureException($e, [
                'action' => 'facebook_oauth',
                'role' => $request->role ?? null,
            ]);
            
            return $this->response(false, 'Invalid Facebook Token', null, $e->getMessage(), 400);
        }
    }

    /**
     * Apple Login (iOS + Android)
     */
    public function handleAppleCallback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identity_token'     => 'nullable|string',
            'authorization_code' => 'nullable|string',
            'user_identifier'    => 'nullable|string',
            'role'               => 'required|string|in:player,coach,scout,parent,team,academy,organizer,fan,adviser,evaluator,super-admin',
            'first_name'         => 'nullable|string|max:255',
            'last_name'          => 'nullable|string|max:255',
            'email'              => 'nullable|email',
            'profile_photo'      => 'nullable|string|max:255',
        ]);

        try {
            // Generate Apple client secret JWT
            $clientSecret = $this->generateAppleClientSecret();

            // Exchange authorization code for tokens
            $response = Http::asForm()->post('https://appleid.apple.com/auth/token', [
                'client_id'     => config('services.apple.client_id'),
                'client_secret' => $clientSecret,
                'code'          => $validated['authorization_code'],
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => config('services.apple.redirect'),
            ]);

            if ($response->failed()) {
                return $this->response(false, 'Apple token exchange failed', null, $response->json(), 400);
            }

            $data = $response->json();

            // Verify Apple identity token
            $appleKeys  = json_decode(file_get_contents(self::JWK_URL), true);
            $parsedKeys = JWK::parseKeySet($appleKeys);
            $decoded    = JWT::decode($data['id_token'], $parsedKeys);

            $email = $decoded->email ?? $validated['email'] ?? null;

            $appleUser = (object) [
                'id'            => $decoded->sub,
                'email'         => $email,
                'first_name'    => $validated['first_name'] ?? '',
                'last_name'     => $validated['last_name'] ?? '',
                'profile_photo' => $validated['profile_photo'] ?? null,
                'role'          => $validated['role'],
            ];

            Log::info("Apple Login Info:", [
                $decoded,
            ]);

            return $this->findOrCreateUser($appleUser, 'apple');
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'apple_oauth_validation',
                'role' => $validated['role'] ?? null,
            ]);
            
            return $this->response(false, 'Validation failed', null, $e->errors(), 422);
        } catch (Exception $e) {
            Log::error("Apple Login Error: {$e->getMessage()}");
            
            $this->errorTracker->captureException($e, [
                'action' => 'apple_oauth',
                'role' => $validated['role'] ?? null,
            ]);
            
            return $this->response(false, 'Invalid Apple Token', null, $e->getMessage(), 400);
        }
    }

    /**
     * Apple Sign-In Redirect Handler (for Android Web Authentication)
     * This handles the OAuth callback from Apple after user authentication
     * Simplified pass-through version - app handles token exchange
     */
    public function handleAppleRedirect(Request $request)
    {
        try {
            // Get Android configuration
            $androidPackageId = config('services.apple.android_package_id', env('APPLE_ANDROID_PACKAGE_ID', 'com.puck.recruiter'));
            $androidScheme    = config('services.apple.android_scheme') ?: 'signinwithapple';

            // Check if this is an Android request
            $isAndroid = $request->query('platform') === 'android'
                || strpos($request->query('state') ?? '', 'android') !== false
                || strpos($request->userAgent() ?? '', 'Android') !== false;

            // Log for debugging
            if ($request->has('error')) {
                Log::error("Apple OAuth Error", [
                    'error'             => $request->query('error'),
                    'error_description' => $request->query('error_description'),
                    'is_android'        => $isAndroid,
                ]);
            } else {
                Log::info("Apple Redirect Callback", [
                    'has_code'   => $request->has('code'),
                    'has_state'  => $request->has('state'),
                    'is_android' => $isAndroid,
                ]);
            }

            // Only pass through safe parameters from Apple
            $safeParams     = $request->only(['code', 'state', 'error', 'error_description']);
            $redirectParams = http_build_query(array_filter($safeParams));

            if ($isAndroid && $androidPackageId) {
                // For Android, redirect using intent URL format
                // Format: intent://callback?${PARAMETERS}#Intent;package=YOUR.PACKAGE.IDENTIFIER;scheme=SCHEME;end
                $redirect = "intent://callback?{$redirectParams}#Intent;package={$androidPackageId};scheme={$androidScheme};end";
                return redirect($redirect, 307);
            }

            // For web/frontend, redirect to frontend URL
            $frontendUrl = config('services.apple.frontend_redirect_url', env('APP_FRONTEND_URL', ''));
            if ($frontendUrl) {
                return redirect($frontendUrl . '?' . $redirectParams, 307);
            }

            // Fallback: redirect to Android if no frontend URL (shouldn't happen but safe fallback)
            if ($androidPackageId) {
                $redirect = "intent://callback?{$redirectParams}#Intent;package={$androidPackageId};scheme={$androidScheme};end";
                return redirect($redirect, 307);
            }

            // Last resort: redirect to a safe error page (or you can remove this)
            Log::error("Apple Redirect: No configuration found");
            return redirect('/?error=configuration_error', 307);
        } catch (Exception $e) {
            Log::error("Apple Redirect Error: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->errorTracker->captureException($e, [
                'action' => 'apple_redirect',
                'platform' => $request->query('platform') ?? 'unknown',
            ]);

            // Try to redirect with error
            $androidPackageId = config('services.apple.android_package_id', env('APPLE_ANDROID_PACKAGE_ID', 'com.puck.recruiter'));
            $androidScheme    = config('services.apple.android_scheme') ?: 'signinwithapple';
            $isAndroid        = strpos($request->userAgent() ?? '', 'Android') !== false;

            if ($isAndroid && $androidPackageId) {
                $params    = http_build_query(['error' => 'server_error', 'error_description' => $e->getMessage()]);
                $intentUrl = "intent://callback?{$params}#Intent;package={$androidPackageId};scheme={$androidScheme};end";
                return redirect($intentUrl, 307);
            }

            // Fallback redirect for web
            $frontendUrl = config('services.apple.frontend_redirect_url', env('APP_FRONTEND_URL', ''));
            if ($frontendUrl) {
                $params = http_build_query(['error' => 'server_error', 'error_description' => $e->getMessage()]);
                return redirect($frontendUrl . '?' . $params, 307);
            }

            // Last resort redirect
            return redirect('/?error=server_error', 307);
        }
    }

    /**
     * Create or authenticate user
     */
    private function findOrCreateUser($socialUser, string $provider): JsonResponse
    {
        $email = $socialUser->email ?? null;

        $user = V4User::where('provider', $provider)
            ->where('provider_id', $socialUser->id)
            ->first();

        if (! $user) {
            if ($email && V4User::where('email', $email)->exists()) {
                return $this->response(
                    false,
                    'Email already registered. Please sign in with your original method.',
                    null,
                    ['email' => ['This email is already registered with a different sign-in method.']],
                    409
                );
            }

            $user = V4User::create([
                'email'         => $email,
                'first_name'    => $socialUser->first_name ?? '',
                'last_name'     => $socialUser->last_name ?? '',
                'profile_photo' => $socialUser->profile_photo ?? null,
                'provider'      => $provider,
                'provider_id'   => $socialUser->id,
                'role'          => $socialUser->role ?? 'player',
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return $this->response(true, 'User authenticated successfully', [
            'token' => $token,
            'user'  => $user,
        ]);
    }

    /**
     * Generate Apple client secret
     */
    private function generateAppleClientSecret(): string
    {
        $teamId     = config('services.apple.team_id');          // Apple Team ID
        $clientId   = config('services.apple.client_id');        // Service ID
        $keyPath    = config('services.apple.private_key');      // .p8 path
        $privateKey = file_get_contents(storage_path($keyPath)); // Read from storage directory
        $keyId      = config('services.apple.key_id');           // Key ID

        $payload = [
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24),
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ];

        return JWT::encode($payload, $privateKey, 'ES256', $keyId);
    }
}
