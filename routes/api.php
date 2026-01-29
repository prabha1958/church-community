<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\AllianceController as AdminAllianceController;
use App\Http\Controllers\Admin\AlliancePaymentController as AdminAlliancePaymentController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\MessageAuthController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ChangeController;
use Illuminate\Database\Schema\IndexDefinition;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AllianceController;

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

Route::middleware('auth:sanctum')->get('/member', function (Request $request) {
    return $request->user();
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
    Route::post('alliances/{alliance}/payments/create-order', [AdminAlliancePaymentController::class, 'createOrder']);
    Route::post('alliances/{alliance}/payments/verify', [AdminAlliancePaymentController::class, 'verify']);
    Route::post('alliances/{alliance}/payments/offline', [AdminAlliancePaymentController::class, 'payOffline']);
});

Route::middleware('auth:sanctum')->get('alliances', [AllianceController::class, 'index']);
Route::middleware('auth:sanctum')->get('alliances/{alliance}', [AllianceController::class, 'show']);

// messages

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('/messages', [AdminMessageController::class, 'store']);
    Route::post('/messages/{message}/send', [AdminMessageController::class, 'publish']);
    Route::get('/messages', [AdminMessageController::class, 'index']);
    Route::put('/messages/{message}/update', [AdminMessageController::class, 'update']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/messages', [MessageAuthController::class, 'index']);
    Route::get('/messages/{message}', [MessageAuthController::class, 'show']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/subscriptions/{member}', [SubscriptionController::class, 'show']);
});

// msg app login

Route::post('/member/login', [MessageAuthController::class, 'login']);

Route::middleware('auth:sanctum')
    ->post('/device-token', [DeviceTokenController::class, 'store']);


// change requests


Route::middleware('auth:sanctum')->post('/changes/{member}', [ChangeController::class, 'store']);
Route::middleware('auth:sanctum')->get('/changes', [ChangeController::class, 'index']);
Route::middleware('auth:sanctum')->get('/changes/{id}', [ChangeController::class, 'show']);
Route::middleware('auth:sanctum')->patch('/changes/{id}', [ChangeController::class, 'update']);
