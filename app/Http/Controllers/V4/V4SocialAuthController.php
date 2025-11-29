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

class V4SocialAuthController extends Controller
{
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
            $googleUser = Socialite::driver('google')->userFromToken($request->access_token);

            $googleUser->first_name    = $request->first_name ?? ($googleUser->user['given_name'] ?? explode(' ', $googleUser->name ?? '')[0] ?? '');
            $googleUser->last_name     = $request->last_name ?? ($googleUser->user['family_name'] ?? implode(' ', array_slice(explode(' ', $googleUser->name ?? ''), 1)) ?? '');
            $googleUser->profile_photo = $request->profile_photo ?? $googleUser->avatar;
            $googleUser->role          = $request->role;

            return $this->findOrCreateUser($googleUser, 'google');
        } catch (Exception $e) {
            Log::error("Google OAuth Error: {$e->getMessage()}");
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
            return $this->response(false, 'Invalid Facebook Token', null, $e->getMessage(), 400);
        }
    }

    /**
     * Apple Login (iOS + Android)
     */
    public function handleAppleCallback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identity_token'     => 'required|string',
            'authorization_code' => 'required|string',
            'user_identifier'    => 'required|string',
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
            return $this->response(false, 'Validation failed', null, $e->errors(), 422);
        } catch (Exception $e) {
            Log::error("Apple Login Error: {$e->getMessage()}");
            return $this->response(false, 'Invalid Apple Token', null, $e->getMessage(), 400);
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
        $teamId     = config('services.apple.team_id');                        // Apple Team ID
        $clientId   = config('services.apple.client_id');                      // Service ID
        $privateKey = file_get_contents(config('services.apple.private_key')); // .p8 path
        $keyId      = config('services.apple.key_id');                         // Key ID

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
