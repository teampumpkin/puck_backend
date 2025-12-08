<?php

namespace App\Http\Controllers\V4;

use App\Constants\OtpProvider;
use App\Constants\OtpType;
use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\V4Otp;
use App\Models\V4User;
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
    public function sendLoginOtp(Request $request)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|in:player,coach,scout,parent,team,academy,organizer,fan,adviser,evaluator,super-admin',
                'is_child' => ['sometimes', 'required_if:role,player', 'boolean'],
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email|string|regex:/^\+[1-9]\d{1,14}$/',
            ]);

            $identifier = $validated['email'] ?? $validated['phone'];
            $field = isset($validated['email']) ? 'email' : 'phone';

            if ($validated['role'] === 'player' && ($validated['is_child'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child players must use parent-supervised login'
                ], 403);
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
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $requestedAt = Carbon::now();
            $expireAt = $requestedAt->copy()->addMinutes(10);

            // Delete existing OTPs for the user
            V4Otp::where('user_id', $user->id)->delete();

            // Store in v4_otps table
            V4Otp::create([
                'user_id' => $user->id,
                'otp' => $otp,
                'type' => ($field === 'email') ? OtpType::EMAIL : OtpType::PHONE,
                'provider' => OtpProvider::TEST,
                'requested_at' => $requestedAt,
                'expire_at' => $expireAt,
            ]);

            //SendXOtpController::sendOtp($user->email, $otp);

            if ($user->email) {
                Log::info('Sending OTP to email: ' . $user->email);
                Mail::to($user->email)->send(new SendOtpMail($otp));
            } else {
                $TwilioSmsService = new TwilioSmsService();
                $message = "Your Puck Recruiter OTP is: $otp. It will expire in 10 minutes.";
                $TwilioSmsService->sendSms($user->phone, $message);
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
                'phone' => 'required_without:email|string|regex:/^[0-9]{10,15}$/',
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

            $token = JWTAuth::fromUser($user);

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
                'token' => $token,
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
            ]);

            $user = V4User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'super-admin',
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Admin registered successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
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

            $token = JWTAuth::fromUser($user);


            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
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

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' => $token,
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
}
;
