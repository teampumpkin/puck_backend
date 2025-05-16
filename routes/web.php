<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuardianController;
use Illuminate\Support\Facades\Route;

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


// Route::get("no-cache/verify-account/{token}", [AuthController::class, 'verifyAccount'])->name('verify');
// Route::get('no-cache/accept/{token}', [GuardianController::class, 'acceptRequest'])->name('acceptGuardian');
// Route::get('no-cache/{token}', [GuardianController::class, 'rejectRequest'])->name('rejectGuardian');
