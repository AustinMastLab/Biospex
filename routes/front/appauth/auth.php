<?php

/*
// Begin AuthController
Route::get('/login')->uses('LoginController@showLoginForm')->name('app.get.login');
Route::post('/login')->uses('LoginController@login')->name('app.post.login');
Route::get('/logout')->uses('LoginController@logout')->name('app.get.logout');

// Begin PasswordController
Route::get('password/reset', 'ForgotPasswordController@showLinkRequestForm')->name('app.password.request');
Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('app.password.email');
Route::get('password/reset/{token}', 'ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'ResetPasswordController@reset');

// Begin RegistrationController
Route::get('/register/{code?}')->uses('RegisterController@showRegistrationForm')->name('app.get.register');
Route::post('/register')->uses('RegisterController@register')->name('app.post.register');
Route::get('/resend')->uses('RegisterController@showResendActivationForm')->name('app.get.resend');
Route::post('/resend')->uses('RegisterController@postResendActivation')->name('app.post.resend');
Route::get('/users/{id}/activate/{code}')->uses('RegisterController@getActivate')->name('app.get.activate');
*/

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register/{invite?}', [RegisteredUserController::class, 'create'])
        ->name('app.get.register');

    Route::post('register/{invite?}', [RegisteredUserController::class, 'store'])
        ->name('app.post.register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('app.get.login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->name('app.post.login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('app.password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('app.password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.put.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('app.post.logout');
});
