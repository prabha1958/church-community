<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\AllianceController as AdminAllianceController;
use App\Http\Controllers\AlliancePaymentController;

//authentication

Route::post('otp/send', [OtpAuthController::class, 'send'])
    ->name('otp.send');

Route::post('otp/verify', [OtpAuthController::class, 'verify'])
    ->name('otp.verify');


//Admin routes

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('members', [AdminMemberController::class, 'index'])->name('members.index');
    Route::get('members/{member}', [AdminMemberController::class, 'show'])->name('members.show');
    Route::put('members/{member}', [AdminMemberController::class, 'update'])->name('members.update');
    Route::patch('members/{member}', [AdminMemberController::class, 'update']);
    Route::delete('members/{member}/deactivate', [AdminMemberController::class, 'deactivate'])->name('members.destroy');
    Route::post('members', [AdminMemberController::class, 'store'])->name('members.store');
    Route::patch('members/{member}/email', [AdminMemberController::class, 'updateEmail'])->name('members.updateEmail');
    Route::patch('members/{member}/mobile', [AdminMemberController::class, 'updateMobile'])->name('members.updateMobile');
});


//admin subscription routes

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('subscriptions/{member}/pay', [AdminSubscriptionController::class, 'payOnBehalf']);
    Route::post('subscriptions/{member}/pay-offline', [AdminSubscriptionController::class, 'payOffline']);
    Route::post('subscriptions/{member}/create', [AdminSubscriptionController::class, 'createSubscription']);
    Route::get('subscriptions/{member}/due', [AdminSubscriptionController::class, 'due']);
    Route::get('subscriptions', [AdminSubscriptionController::class, 'index']);
    Route::get('subscriptions/{member}', [AdminSubscriptionController::class, 'show']);
    Route::post('subscriptions/verify-payment', [AdminSubscriptionController::class, 'verifyPayment']);
});

//alliance routes

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('alliances', [AdminAllianceController::class, 'store']);
    Route::get('alliances', [AdminAllianceController::class, 'index']);
    Route::get('alliances/{alliance}', [AdminAllianceController::class, 'show']);
    Route::patch('alliances/{alliance}', [AdminAllianceController::class, 'update']);
    Route::post('alliances/{alliance}/payments/create-order', [AlliancePaymentController::class, 'createOrder']);
    Route::post('alliances/{alliance}/payments/verify', [AlliancePaymentController::class, 'verify']);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('alliances/{alliance}/payments/create-order', [AlliancePaymentController::class, 'createOrder']);
    Route::post('alliances/{alliance}/payments/verify', [AlliancePaymentController::class, 'verify']);
});
