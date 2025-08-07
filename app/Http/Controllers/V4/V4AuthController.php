<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\V4User;
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
                'role' => 'required|string|in:player,coach,scout,parent,team,academy,organizer,fan,adviser',
                'is_child' => ['sometimes', 'required_if:role,player', 'boolean'],
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email|string|regex:/^[0-9]{10,15}$/',
            ]);

            $identifier = $validated['email'] ?? $validated['phone'];
            $field      = isset($validated['email']) ? 'email' : 'phone';

            if ($validated['role'] === 'player' && ($validated['is_child'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child players must use parent-supervised login'
                ], 403);
            }

            // Find or create user
            $user = V4User::firstOrCreate(
                [$field => $identifier],
                [
                    'role'      => $validated['role'],
                    'is_child'  => $validated['is_child'] ?? false,
                    // When phone is the identifier, email may be null
                    'email'     => $validated['email'] ?? null,
                    'phone'     => $validated['phone'] ?? null,
                ]
            );

            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'otp'         => $otp,
                'otp_expiry'  => now()->addMinutes(10),
            ]);

                //TODO: dispatch SMS job if $field === phone
                //if ($field === 'email') {
                // Mail::to($user->email)->send(new SendOtpMail($otp));
                //}

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'otp'     => $otp, // need to remove later
                $field    => $identifier,
                'role'    => $user->role,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (Exception $e) {
            Log::error('Send OTP error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function verifyLoginOtp(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email|string|regex:/^[0-9]{10,15}$/',
                'otp'   => 'required|string|size:6',
            ]);

            $field      = isset($validated['email']) ? 'email' : 'phone';
            $identifier = $validated[$field];

            $user = V4User::where($field, $identifier)->first();

            if (
                !$user ||
                $user->otp !== $validated['otp'] ||
                !$user->otp_expiry ||
                now()->gt($user->otp_expiry)
            ) {
                return response()->json(['message' => 'Invalid or expired OTP'], 401);
            }

            // Child players are not allowed via OTP
            if ($user->role === 'player' && $user->is_child) {
                return response()->json([
                    'message' => 'Please login using child credentials',
                ], 401);
            }

            $user->update([
                'otp'        => null,
                'otp_expiry' => null,
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'          => $user->id,
                    'role'        => $user->role,
                    'isOnboarded' => $user->is_onboarded,
                    'email'       => $user->email,
                    'phone'       => $user->phone,
                    'is_child'    => $user->is_child,
                ],
                'message' => 'OTP verification successful',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (Exception $e) {
            Log::error('OTP verification failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'OTP verification failed.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
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
};
