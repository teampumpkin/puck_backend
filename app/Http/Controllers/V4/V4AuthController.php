<?php

namespace App\Http\Controllers\V4;

use App\Constants\OtpProvider;
use App\Constants\OtpType;
use App\Contracts\ErrorTrackerInterface;
use App\Helpers\ChatUserSyncHelper;
use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\V4Otp;
use App\Models\V4User;
use App\Models\SuperAdminProfile;
use App\Services\TwilioSmsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

class V4AuthController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }
    public function sendLoginOtp(Request $request)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|in:player,coach,scout,parent,team,academy,organizer,fan,adviser,evaluator,super-admin',
                'is_child' => ['sometimes', 'required_if:role,player', 'boolean'],
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email|string|regex:/^\+[1-9]\d{7,14}$/',
                'country_code' => 'required_with:phone|string|regex:/^\+[1-9]\d{0,3}$/',
            ]);
            $identifier = $validated['email'] ?? $validated['phone'];
            $field = isset($validated['email']) ? 'email' : 'phone';

            if ($validated['role'] === 'player' && ($validated['is_child'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child players must use parent-supervised login'
                ], 403);
            }

            // Legacy records may store the phone without the country code prefix.
            // If the user logs in with the full number (e.g. +919318369648) but an
            // existing record holds only the local number (e.g. 9318369648), migrate
            // that record to the full number so we don't create a duplicate account.
            // If the number is already stored with the country code, do nothing.
            if ($field === 'phone' && !empty($validated['country_code'])) {
                $countryCode = $validated['country_code'];
                if (str_starts_with($identifier, $countryCode)) {
                    $localNumber = substr($identifier, strlen($countryCode));
                    if ($localNumber !== '') {
                        V4User::where('phone', $localNumber)
                            ->whereNull('deleted_at')
                            ->update(['phone' => $identifier]);
                    }
                }
            }

            $user = V4User::firstOrCreate(
                [$field => $identifier],
                [
                    'role' => $validated['role'],
                    'is_child' => $validated['is_child'] ?? false,
                    // When phone is the identifier, email may be null
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'provider' => $field,
                ]
            );

            // Generate OTP
            // Exception for specific test emails - all use fixed OTP
            $testEmails = [
                'mihir.pipermitwala+player@teampumpkin.com',
                'mihir.pipermitwala+coach@teampumpkin.com',
                'mihir.pipermitwala+scout@teampumpkin.com',
                'mihir.pipermitwala+parent@teampumpkin.com',
                'mihir.pipermitwala+team@teampumpkin.com',
                'mihir.pipermitwala+academy@teampumpkin.com',
                'mihir.pipermitwala+organizer@teampumpkin.com',
                'mihir.pipermitwala+fan@teampumpkin.com',
                'mihir.pipermitwala+advisor@teampumpkin.com',
                'mihir.pipermitwala+evaluator@teampumpkin.com',
                'player1@yopmail.com',
                'team1@yopmail.com',
                'coach1@yopmail.com',
                'coach1@tp.com',
                'scout1@yopmail.com',
                'organizer1@yopmail.com',
                'parent1@yopmail.com',
                'fan1@yopmail.com',
                'academy1@yopmail.com', // used by academy_*.yaml Maestro suite (academy Events tab)
                'advisor1@yopmail.com',
                'justpraveen55@gmail.com',
                'praveenpandiyan1704@gmail.com',
                'praveen.p@teampumpkin.com',
                'praveenjp1704@gmail.com',
                'evaluator1@yopmail.com',
                'play26@gmail.com',
                'player13@gmail.com',
                'scout13@gmail.com', // added for scout_*.yaml Maestro suite (scout Events tab)
                'team1@tp.com' // added for team_*.yaml Maestro suite (onboarded team, owns seeded events)
                // Add more test emails here as needed
            ];

            if (in_array($identifier, $testEmails)) {
                $otp = '123456';
            } else {
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            }
            $requestedAt = Carbon::now();
            $expireAt = $requestedAt->copy()->addMinutes(env('OTP_EXPIRY_TIME_MIN', 10));

            // Delete existing OTPs for the user
            V4Otp::where('user_id', $user->id)->delete();

            // Store in v4_otps table
            V4Otp::create([
                'user_id' => $user->id,
                'otp' => $otp,
                'type' => ($field === 'email') ? OtpType::EMAIL : OtpType::PHONE,
                'provider' => ($field === 'email') ? OtpProvider::SMTP : OtpProvider::Twilio,
                'requested_at' => $requestedAt,
                'expire_at' => $expireAt,
            ]);


            if ($field === 'email') {
                Mail::to($user->email)->send(new SendOtpMail($otp));
                //SendXOtpController::sendOtp($user->email, $otp);
            } else {
                $TwilioSmsService = new TwilioSmsService();
                $message = "Your Puck Recruiter OTP is: $otp. It will expire in " . env('OTP_EXPIRY_TIME_MIN', 10) . " minutes.";
                $TwilioSmsService->sendSms($identifier, $message);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'otp' => $otp, // need to remove later
                $field => $identifier,
                'role' => $user->role,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Send OTP error: ' . $e->getMessage());

            // Track error in Sentry with context
            $this->errorTracker->captureException($e, [
                'action' => 'send_login_otp',
                'role' => $validated['role'] ?? null,
                'field' => $field ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function verifyLoginOtp(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email|string|regex:/^\+[1-9]\d{7,14}$/',
                'otp' => 'required|string|size:6',
            ]);

            $field = isset($validated['email']) ? 'email' : 'phone';
            $identifier = $validated[$field];

            $user = V4User::where($field, $identifier)->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid or expired OTP'], 401);
            }

            // Child players are not allowed via OTP
            if ($user->role === 'player' && $user->is_child) {
                return response()->json([
                    'message' => 'Please login using child credentials',
                ], 401);
            }

            $otpRecord = V4Otp::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->orderByDesc('requested_at')
                ->first();

            if (
                !$otpRecord ||
                $otpRecord->otp !== $validated['otp'] ||
                !$otpRecord->expire_at ||
                Carbon::now()->gt(Carbon::parse($otpRecord->expire_at))
            ) {
                return response()->json(['message' => 'Invalid or expired OTP'], 401);
            }

            $otpRecord->delete();

            $accessToken = JWTAuth::fromUser($user);
            JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
            $refreshToken = JWTAuth::claims(['type' => 'refresh'])->fromUser($user);
            JWTAuth::factory()->setTTL(config('jwt.ttl'));

            // Ensure user exists in chat microservice (upsert) so /conversation/create
            // does not 404 with "Users not found".
            ChatUserSyncHelper::sync($user, $accessToken);

            $responseUser = [
                'id' => $user->id,
                'role' => $user->role,
                'is_onboarded' => $user->is_onboarded,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_child' => $user->is_child,
            ];

            if ($user->role === 'evaluator') {
                $user->load('evaluatorProfile');
                $responseUser['is_verified'] = $user->evaluatorProfile->is_verified ?? null;
            }

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => $responseUser,
                'message' => 'OTP verification successful',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('OTP verification failed: ' . $e->getMessage());

            // Track error in Sentry with context
            $this->errorTracker->captureException($e, [
                'action' => 'verify_login_otp',
                'field' => $field ?? null,
            ]);

            return response()->json([
                'message' => 'OTP verification failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function adminRegister(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:v4_users,email',
                'password' => 'required|string|min:8',
                'super_admin_id' => 'nullable|exists:v4_users,id',
            ]);

            $user = V4User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'super-admin',
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'super_admin_id' => $validated['super_admin_id'] ?? null,
            ]);

            SuperAdminProfile::create([
                'v4_user_id' => $user->id,
                'is_verified' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Admin registered successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'super_admin_id' => $user->super_admin_id,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Admin Register Error: ' . $e->getMessage());

            // Track error in Sentry with context
            $this->errorTracker->captureException($e, [
                'action' => 'admin_register',
                'email' => $validated['email'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register admin. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function adminLogin(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = V4User::where('email', $validated['email'])->first();

            if (!$user || $user->role !== 'super-admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials or unauthorized role.',
                ], 401);
            }

            if (!Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ], 401);
            }

            $accessToken = JWTAuth::fromUser($user);
            JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
            $refreshToken = JWTAuth::claims(['type' => 'refresh'])->fromUser($user);
            JWTAuth::factory()->setTTL(config('jwt.ttl'));

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Admin Login Error: ' . $e->getMessage());

            // Track error in Sentry with context
            $this->errorTracker->captureException($e, [
                'action' => 'admin_login',
                'email' => $validated['email'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function childLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = V4User::where('username', $request->username)
            ->where('is_child', true)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $accessToken = JWTAuth::fromUser($user);
        JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
        $refreshToken = JWTAuth::claims(['type' => 'refresh'])->fromUser($user);
        JWTAuth::factory()->setTTL(config('jwt.ttl'));

        ChatUserSyncHelper::sync($user, $accessToken);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
                'is_onboarded' => $user->is_onboarded,
                'is_child' => $user->is_child,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'message' => 'Login successful'
        ]);
    }

    public function refreshToken()
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return response()->json(['message' => 'Invalid token type'], 401);
            }

            $user = JWTAuth::parseToken()->authenticate();
            $newAccessToken = JWTAuth::fromUser($user);

            return response()->json([
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Token could not be refreshed'], 401);
        }
    }
};
