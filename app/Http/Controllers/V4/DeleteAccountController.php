<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class DeleteAccountController extends Controller
{
    /**
     * Get authenticated user from token (query parameter or Authorization header)
     */
    private function getAuthenticatedUser(Request $request)
    {
        try {
            // Try to get token from query parameter first (for web routes)
            $token = $request->input('token') ?? $request->query('token');
            
            // If no token in query, try Authorization header
            if (!$token && $request->hasHeader('Authorization')) {
                $authHeader = $request->header('Authorization');
                if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                    $token = $matches[1];
                }
            }

            if (!$token) {
                return null;
            }

            // Authenticate using JWT token
            $user = JWTAuth::setToken($token)->authenticate();
            
            return $user;
        } catch (JWTException $e) {
            Log::warning('JWT authentication failed during account deletion.', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('Error authenticating user for account deletion.', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return null;
        }
    }

    // Show the account deletion form
    public function showDeleteForm(Request $request)
    {
        // Try to authenticate user if token is provided (optional for viewing the form)
        // The actual deletion will require authentication
        $user = $this->getAuthenticatedUser($request);
        
        // Show the form regardless, but display a warning if not authenticated
        $data = [];
        if (!$user && $request->has('token')) {
            $data['errors'] = ['user' => 'Invalid or expired token. Please provide a valid authentication token.'];
        }

        return view('account.delete', $data);
    }

    // Handle the account deletion request
    public function deleteAccount(Request $request)
    {
        // Get authenticated user from token
        $user = $this->getAuthenticatedUser($request);

        // If no user is found, return error
        if (!$user) {
            Log::error('User not found during account deletion attempt.', [
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['user' => 'Authentication required. Please provide a valid token.']);
        }

        try {
            // Validate password input
            $request->validate([
                'password' => 'required|string|min:8',
            ]);

            // Check if the provided password matches the user's current password
            if (!Hash::check($request->password, $user->password)) {
                // Log the failed attempt and provide a user-friendly error message
                Log::warning('Incorrect password entered during account deletion attempt.', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]);
                return back()->withErrors(['password' => 'The password is incorrect.']);
            }

            // Start a database transaction to ensure integrity
            DB::beginTransaction();

            // Optional: If needed, handle the deletion of related records (e.g., notifications)
            // Notification::where('user_id', $user->id)->delete();

            // Perform the deletion
            $user->delete();

            // Commit the transaction
            DB::commit();

            // Log the successful account deletion
            Log::info('User account deleted successfully.', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            // Invalidate JWT token if it exists
            try {
                $token = $request->input('token') ?? $request->query('token');
                if ($token) {
                    JWTAuth::setToken($token)->invalidate();
                }
            } catch (Exception $e) {
                // Token invalidation is not critical, log and continue
                Log::warning('Failed to invalidate JWT token after account deletion.', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
            }

            // Redirect to home or a different page after deletion
            return redirect('/')->with('success', 'Your account has been deleted successfully.');
        } catch (ModelNotFoundException $e) {
            // Handle model not found exception (e.g., user does not exist)
            DB::rollBack(); // Rollback transaction
            Log::error('Model not found during account deletion attempt.', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['error' => 'Account deletion failed. Please try again later.']);
        } catch (QueryException $e) {
            // Handle database query exception (e.g., issues with deleting records)
            DB::rollBack(); // Rollback transaction
            Log::error('Database error occurred during account deletion.', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['error' => 'A database error occurred. Please try again later.']);
        } catch (ValidationException $e) {
            // Handle validation exception (e.g., invalid password input)
            Log::warning('Validation error during account deletion attempt.', [
                'errors' => $e->errors(),
                'user_id' => $user->id ?? null,
                'ip' => $request->ip(),
            ]);
            return back()->withErrors($e->errors());
        } catch (Exception $e) {
            // Handle any other general exception
            DB::rollBack(); // Rollback transaction
            Log::critical('Unexpected error during account deletion.', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again later.']);
        }
    }

    /**
     * Handle Facebook Data Deletion Callback
     * This endpoint is called by Facebook when a user requests data deletion
     * 
     * Facebook Requirements:
     * - Must accept POST requests with signed_request parameter
     * - Must verify the signed_request using app secret
     * - Must delete user data identified by Facebook user ID
     * - Must return JSON with confirmation_url
     * - May receive GET requests for verification
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function handleFacebookDataDeletion(Request $request): JsonResponse
    {
        try {
            // Handle GET request for Facebook verification
            if ($request->isMethod('GET')) {
                // Facebook may send a GET request to verify the endpoint exists
                return response()->json([
                    'url' => route('account.delete.form'),
                    'confirmation_code' => null,
                ], 200);
            }

            // Facebook sends signed_request in POST data
            $signedRequest = $request->input('signed_request');
            
            if (!$signedRequest) {
                Log::warning('Facebook data deletion callback received without signed_request.', [
                    'ip' => $request->ip(),
                    'request_data' => $request->all(),
                ]);
                
                return response()->json([
                    'url' => route('account.delete.form'),
                    'confirmation_code' => null,
                ], 200);
            }

            // Verify and decode the signed_request
            $data = $this->parseFacebookSignedRequest($signedRequest);
            
            if (!$data) {
                Log::error('Failed to verify Facebook signed_request.', [
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'url' => route('account.delete.form'),
                    'confirmation_code' => null,
                ], 200);
            }

            // Extract Facebook user ID from signed_request
            $facebookUserId = $data['user_id'] ?? null;
            
            if (!$facebookUserId) {
                Log::warning('Facebook signed_request missing user_id.', [
                    'ip' => $request->ip(),
                    'data' => $data,
                ]);
                
                return response()->json([
                    'url' => route('account.delete.form'),
                    'confirmation_code' => null,
                ], 200);
            }

            // Find user by Facebook provider_id
            $user = V4User::where('provider', 'facebook')
                ->where('provider_id', $facebookUserId)
                ->first();

            if (!$user) {
                // User not found - return success anyway (Facebook requirement)
                Log::info('Facebook data deletion requested for non-existent user.', [
                    'facebook_user_id' => $facebookUserId,
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'url' => route('account.delete.form'),
                    'confirmation_code' => 'USER_NOT_FOUND',
                ], 200);
            }

            // Generate a unique confirmation code
            $confirmationCode = Str::random(32);
            
            // Store confirmation code temporarily (you might want to use cache or database)
            // For now, we'll proceed with deletion immediately as per Facebook's requirements
            
            // Start database transaction
            DB::beginTransaction();

            try {
                // Delete user account
                $userId = $user->id;
                $user->delete();

                // Commit transaction
                DB::commit();

                // Log successful deletion
                Log::info('Facebook data deletion completed successfully.', [
                    'user_id' => $userId,
                    'facebook_user_id' => $facebookUserId,
                    'confirmation_code' => $confirmationCode,
                    'ip' => $request->ip(),
                ]);

                // Return response as per Facebook's requirements
                return response()->json([
                    'url' => route('account.delete.form'),
                    'confirmation_code' => $confirmationCode,
                ], 200);

            } catch (Exception $e) {
                DB::rollBack();
                
                Log::error('Error during Facebook data deletion.', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId ?? null,
                    'facebook_user_id' => $facebookUserId,
                    'ip' => $request->ip(),
                ]);

                // Still return success to Facebook (they expect 200 status)
                return response()->json([
                    'url' => route('account.delete.form'),
                    'confirmation_code' => 'DELETION_FAILED',
                ], 200);
            }

        } catch (Exception $e) {
            Log::critical('Unexpected error in Facebook data deletion callback.', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            // Always return 200 to Facebook
            return response()->json([
                'url' => route('account.delete.form'),
                'confirmation_code' => 'ERROR',
            ], 200);
        }
    }

    /**
     * Parse and verify Facebook signed_request
     * 
     * @param string $signedRequest
     * @return array|null
     */
    private function parseFacebookSignedRequest(string $signedRequest): ?array
    {
        try {
            // Split signed_request into signature and payload
            list($encodedSig, $payload) = explode('.', $signedRequest, 2);

            // Decode the payload
            $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

            if (!$data) {
                return null;
            }

            // Verify signature using app secret
            $appSecret = config('services.facebook.client_secret');
            if (!$appSecret) {
                Log::error('Facebook app secret not configured.');
                return null;
            }

            // Generate expected signature
            $expectedSig = hash_hmac('sha256', $payload, $appSecret, true);
            $expectedSigEncoded = strtr(base64_encode($expectedSig), '+/', '-_');

            // Verify signature matches
            if ($encodedSig !== $expectedSigEncoded) {
                Log::warning('Facebook signed_request signature verification failed.');
                return null;
            }

            return $data;

        } catch (Exception $e) {
            Log::error('Error parsing Facebook signed_request.', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
