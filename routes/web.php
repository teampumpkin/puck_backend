<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuardianController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V4\V4AuthController;


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

Route::get("send-otp",[V4AuthController::class , 'sendOtp']);

// Route::get("no-cache/verify-account/{token}", [AuthController::class, 'verifyAccount'])->name('verify');
// Route::get('no-cache/accept/{token}', [GuardianController::class, 'acceptRequest'])->name('acceptGuardian');
// Route::get('no-cache/{token}', [GuardianController::class, 'rejectRequest'])->name('rejectGuardian');
