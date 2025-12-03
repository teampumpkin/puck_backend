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

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Str;

class DeleteAccountController extends Controller
{
    // Show the account deletion form
    public function showDeleteForm()
    {
        return view('account.delete');
    }

    // Handle the account deletion request
    public function deleteAccount(Request $request)
    {
        // Ensure the user is authenticated
        $user = Auth::guard('v4api')->user();

        // If no user is found, throw a 404 or custom error
        if (!$user) {
            Log::error('User not found during account deletion attempt.', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['user' => 'User not found.']);
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

            // Logout the user
            Auth::logout();

            // Redirect to home or a different page after deletion
            return redirect('/')->with('success', 'Your account has been deleted successfully.');
        } catch (ModelNotFoundException $e) {
            // Handle model not found exception (e.g., user does not exist)
            DB::rollBack(); // Rollback transaction
            Log::error('Model not found during account deletion attempt.', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['error' => 'Account deletion failed. Please try again later.']);
        } catch (QueryException $e) {
            // Handle database query exception (e.g., issues with deleting records)
            DB::rollBack(); // Rollback transaction
            Log::error('Database error occurred during account deletion.', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['error' => 'A database error occurred. Please try again later.']);
        } catch (ValidationException $e) {
            // Handle validation exception (e.g., invalid password input)
            Log::warning('Validation error during account deletion attempt.', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors($e->errors());
        } catch (Exception $e) {
            // Handle any other general exception
            DB::rollBack(); // Rollback transaction
            Log::critical('Unexpected error during account deletion.', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again later.']);
        }
    }
}
