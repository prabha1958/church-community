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
use App\Http\Controllers\Admin\PastorController as AdminPastorController;
use App\Http\Controllers\Api\MemberSessionController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\PastorateComMemberController as AdminPastorateComMemberController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\PoorFeedingController as AdminPoorFeedingController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminSystemController;
use App\Http\Controllers\AdminGreetingController;
use App\Http\Controllers\PastorController;

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
    Route::patch('messages/{message}/show', [AdminMessageController::class, 'display']);
    Route::patch('messages/{message}/hide', [AdminMessageController::class, 'hide']);
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

//pastors

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/pastors', [AdminPastorController::class, 'index'])->name('pastors_list');
    Route::get('/pastors/{pastor}', [AdminPastorController::class, 'show'])->name('pastors_single');
    Route::post('/pastors', [AdminPastorController::class, 'store'])->name('pastors_store');
    Route::patch('/pastors/{pastor}', [AdminPastorController::class, 'update'])->name('pastor_update');
});


Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/pastors', [PastorController::class, 'index'])->name('pastors_list');
});


//session

Route::middleware('auth:sanctum')->get(
    '/member/session',
    [MemberSessionController::class, 'show']
);

// Announcments

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('announcements', [AdminAnnouncementController::class, 'index']);
    Route::get('announcements/{announcement}', [AdminAnnouncementController::class, 'show']);
    Route::post('announcements', [AdminAnnouncementController::class, 'store']);
    Route::patch('announcements/{announcement}', [AdminAnnouncementController::class, 'update']);
    Route::post('announcements/{announcement}/send', [AdminAnnouncementController::class, 'publish']);
});


// Pastorate Committee members

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('commembers', [AdminPastorateComMemberController::class, 'index']);
    Route::get('commembers/{commember}', [AdminPastorateComMemberController::class, 'show']);
    Route::post('commembers', [AdminPastorateComMemberController::class, 'store']);
    Route::patch('commembers/{commember}', [AdminPastorateComMemberController::class, 'update']);
    Route::post('commembers/{commember}/send', [AdminPastorateComMemberController::class, 'publish']);
});


// events

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('events', [AdminEventController::class, 'store'])->name('admin.events.store');
    Route::get('events', [AdminEventController::class, 'index'])->name('admin.events');
    Route::patch('events/{event}', [AdminEventController::class, 'update'])->name('admin.events.update');
    Route::patch('events/{event}/hide', [AdminEventController::class, 'hide']);
    Route::patch('events/{event}/show', [AdminEventController::class, 'display']);
    Route::delete('events/{event}', [AdminEventController::class, 'destroy'])->name('admin.events.destroy');

    // optional endpoint to remove a single photo
    Route::delete('events/{event}/photo', [AdminEventController::class, 'removePhoto'])->name('admin.events.photo.remove');
});

//Poor feeding

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::post('poor-feedings', [AdminPoorFeedingController::class, 'store']);
    Route::get('poor-feedings', [AdminPoorFeedingController::class, 'index']);
    Route::put('poor-feedings/{poorFeeding}', [AdminPoorFeedingController::class, 'update']);
    Route::patch('poor-feedings/{poorFeeding}', [AdminPoorFeedingController::class, 'update']);
    Route::delete('poor-feedings/{poorFeeding}', [AdminPoorFeedingController::class, 'destroy']);
    Route::patch('poor-feedings/{pfeeding}/hide', [AdminPoorFeedingController::class, 'hide']);
    Route::patch('poor-feedings/{pfeeding}/show', [AdminPoorFeedingController::class, 'display']);

    // optional remove one photo endpoint
    Route::delete('poor-feedings/{poorFeedings}/photo', [AdminPoorFeedingController::class, 'removePhoto']);
});


//dashborad

Route::middleware(['auth:sanctum', 'admin'])
    ->get('admin/dashboard', [AdminDashboardController::class, 'index']);

//SYSTEM RUNS

Route::post('admin/run/birthday', [AdminSystemController::class, 'runBirthday']);
Route::post('admin/run/anniversary', [AdminSystemController::class, 'runAnniversary']);


Route::post('admin/greetings/birthday/run', [AdminGreetingController::class, 'runBirthday']);
Route::get('admin/greetings/birthday/logs', [AdminGreetingController::class, 'logs']);


Route::post('admin/greetings/anniversay/run', [AdminGreetingController::class, 'runAnniversary']);
Route::get('admin/greetings/anniversary/logs', [AdminGreetingController::class, 'annlogs']);
