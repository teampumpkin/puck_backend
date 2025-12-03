<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuardianController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V4\V4AuthController;
use App\Http\Controllers\V4\DeleteAccountController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route to show the account deletion form
Route::get('/account/delete', [DeleteAccountController::class, 'showDeleteForm'])->name('account.delete.form');

// Route to handle account deletion request
Route::post('/account/delete', [DeleteAccountController::class, 'deleteAccount'])->name('account.delete');

// Facebook Data Deletion Callback (excluded from CSRF verification)
// This endpoint is called by Facebook when a user requests data deletion
// Supports both GET (for verification) and POST (for actual deletion)
Route::match(['get', 'post'], '/facebook/data-deletion', [DeleteAccountController::class, 'handleFacebookDataDeletion'])->name('facebook.data.deletion');


Route::get("send-otp", [V4AuthController::class, 'sendOtp']);

// Route::get("no-cache/verify-account/{token}", [AuthController::class, 'verifyAccount'])->name('verify');
// Route::get('no-cache/accept/{token}', [GuardianController::class, 'acceptRequest'])->name('acceptGuardian');
// Route::get('no-cache/{token}', [GuardianController::class, 'rejectRequest'])->name('rejectGuardian');
