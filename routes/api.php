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
use App\Models\PastorateComMember;
use App\Http\Controllers\EventController;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use App\Models\Message;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Alliance;
use App\Models\AlliancePayment;
use App\Models\AnniversaryGreeting;
use App\Models\OtpCode;


// ============================================================
// 1. TENANT PUBLIC ROUTES
// ============================================================
//
// These routes identify the church but do not require a
// Sanctum bearer token.
//

Route::middleware('tenant')->group(function () {

    // OTP
    Route::post('otp/send', [
        OtpAuthController::class,
        'send'
    ])->name('otp.send');

    Route::post('otp/verify', [
        OtpAuthController::class,
        'verify'
    ])->name('otp.verify');


    // Member login
    Route::post('member/login', [
        MessageAuthController::class,
        'login'
    ]);
});


// ============================================================
// 2. TENANT AUTHENTICATED ROUTES
// ============================================================
//
// These routes require:
//     1. Church identification
//     2. Valid Sanctum bearer token
//
// They operate on the selected church database.
//

Route::middleware([
    'tenant',
    'auth:sanctum'
])->group(function () {


    // --------------------------------------------------------
    // MEMBER
    // --------------------------------------------------------

    // --------------------------------------------------------
    // MEMBER PROFILE
    // --------------------------------------------------------

    Route::get('/member/profile', [
        MemberController::class,
        'profile'
    ]);

    Route::put('/member/profile', [
        MemberController::class,
        'updateProfile'
    ]);

    Route::patch('/member/profile/email', [
        MemberController::class,
        'updateEmail'
    ]);

    Route::patch('/member/profile/mobile', [
        MemberController::class,
        'updateMobile'
    ]);

    // --------------------------------------------------------
    // MESSAGES
    // --------------------------------------------------------

    Route::get('/messages', [
        MessageAuthController::class,
        'index'
    ]);

    Route::get('/messages/{message}', [
        MessageAuthController::class,
        'show'
    ]);


    // --------------------------------------------------------
    // SUBSCRIPTIONS
    // --------------------------------------------------------

    Route::get('/subscriptions', [
        SubscriptionController::class,
        'show'
    ]);


    // --------------------------------------------------------
    // ALLIANCES
    // --------------------------------------------------------

    Route::get('alliances', [
        AllianceController::class,
        'index'
    ]);

    Route::get('alliances/{allianceId}', [
        AllianceController::class,
        'show'
    ]);


    // --------------------------------------------------------
    // DEVICE TOKEN
    // --------------------------------------------------------

    Route::post('/device-token', [
        DeviceTokenController::class,
        'store'
    ]);



    // --------------------------------------------------------
    // PASTORS
    // --------------------------------------------------------

    Route::get('/pastors', [
        PastorController::class,
        'index'
    ])->name('pastors_list_app');

    Route::get('/pastors/{pastor}', [
        PastorController::class,
        'show'
    ]);




    // --------------------------------------------------------
    // EVENTS
    // --------------------------------------------------------

    Route::get('events', [
        EventController::class,
        'index'
    ]);

    Route::get('events/{event}', [
        EventController::class,
        'show'
    ]);
});


// ============================================================
// 3. TENANT ADMIN ROUTES
// ============================================================
//
// These routes require:
//     1. Church identification
//     2. Sanctum authentication
//     3. Admin role
//
// All data belongs to the currently identified church.
//

Route::middleware([
    'tenant',
    'auth:sanctum',
    'admin'
])->prefix('admin')->name('admin.')->group(function () {


    // --------------------------------------------------------
    // MEMBERS
    // --------------------------------------------------------

    Route::get('members', [
        AdminMemberController::class,
        'index'
    ])->name('members.index');

    Route::get('members/{member}', [
        AdminMemberController::class,
        'show'
    ])->name('members.show')
        ->whereNumber('member');

    Route::patch('members/{member}', [
        AdminMemberController::class,
        'update'
    ])->whereNumber('member');

    Route::delete('members/{member}/deactivate', [
        AdminMemberController::class,
        'deactivate'
    ])->name('members.destroy');

    Route::post('members', [
        AdminMemberController::class,
        'store'
    ])->name('members.store');

    Route::patch('members/{member}/email', [
        AdminMemberController::class,
        'updateEmail'
    ])->name('members.updateEmail');

    Route::patch('members/{member}/mobile', [
        AdminMemberController::class,
        'updateMobile'
    ])->name('members.updateMobile');


    // --------------------------------------------------------
    // SUBSCRIPTIONS
    // --------------------------------------------------------

    Route::post('subscriptions/{member}/pay', [
        AdminSubscriptionController::class,
        'payOnBehalf'
    ]);

    Route::post('subscriptions/{member}/pay-offline', [
        AdminSubscriptionController::class,
        'payOffline'
    ]);

    Route::post('subscriptions/{member}/create', [
        AdminSubscriptionController::class,
        'createSubscription'
    ]);

    Route::get('subscriptions/{member}/due', [
        AdminSubscriptionController::class,
        'due'
    ]);

    Route::get('subscriptions/daily-report', [
        AdminSubscriptionController::class,
        'dailyReport'
    ]);

    Route::get('subscriptions', [
        AdminSubscriptionController::class,
        'index'
    ]);

    Route::get('subscriptions/{member}', [
        AdminSubscriptionController::class,
        'show'
    ]);

    Route::post('subscriptions/verify-payment', [
        AdminSubscriptionController::class,
        'verifyPayment'
    ]);


    // --------------------------------------------------------
    // ALLIANCES
    // --------------------------------------------------------

    Route::post('alliances', [
        AdminAllianceController::class,
        'store'
    ]);

    Route::get('alliances', [
        AdminAllianceController::class,
        'index'
    ]);

    Route::get('alliances/{alliance}', [
        AdminAllianceController::class,
        'show'
    ]);

    Route::patch('alliances/{alliance}', [
        AdminAllianceController::class,
        'update'
    ]);

    Route::patch('alliances/{allianceId}/toggle', [
        AdminAllianceController::class,
        'togglePublish'
    ]);

    Route::post('alliances/{alliance}/payments/create-order', [
        AdminAlliancePaymentController::class,
        'createOrder'
    ]);

    Route::post('alliances/{alliance}/payments/verify', [
        AdminAlliancePaymentController::class,
        'verify'
    ]);

    Route::post('alliances/{alliance}/payments/offline', [
        AdminAlliancePaymentController::class,
        'payOffline'
    ]);



    // --------------------------------------------------------
    // MESSAGES
    // --------------------------------------------------------

    Route::post('messages', [
        AdminMessageController::class,
        'store'
    ]);

    Route::post('messages/{message}/send', [
        AdminMessageController::class,
        'publish'
    ]);

    Route::get('messages', [
        AdminMessageController::class,
        'index'
    ]);

    Route::put('messages/{message}/update', [
        AdminMessageController::class,
        'update'
    ]);

    Route::patch('messages/{message}/show', [
        AdminMessageController::class,
        'display'
    ]);

    Route::patch('messages/{message}/hide', [
        AdminMessageController::class,
        'hide'
    ]);

    Route::get('messages/{message}', [
        AdminMessageController::class,
        'show'
    ]);


    // --------------------------------------------------------
    // PASTORS
    // --------------------------------------------------------

    Route::get('pastors', [
        AdminPastorController::class,
        'index'
    ])->name('pastors_list');

    Route::get('pastors/{pastor}', [
        AdminPastorController::class,
        'show'
    ])->name('pastors_single');

    Route::post('pastors', [
        AdminPastorController::class,
        'store'
    ])->name('pastors_store');

    Route::patch('pastors/{pastor}', [
        AdminPastorController::class,
        'update'
    ])->name('pastor_update');


    // --------------------------------------------------------
    // ANNOUNCEMENTS
    // --------------------------------------------------------

    Route::get('announcements', [
        AdminAnnouncementController::class,
        'index'
    ]);

    Route::get('announcements/{announcement}', [
        AdminAnnouncementController::class,
        'show'
    ]);

    Route::post('announcements', [
        AdminAnnouncementController::class,
        'store'
    ]);

    Route::patch('announcements/{announcement}', [
        AdminAnnouncementController::class,
        'update'
    ]);

    Route::post('announcements/{announcement}/send', [
        AdminAnnouncementController::class,
        'publish'
    ]);


    // --------------------------------------------------------
    // PASTORATE COMMITTEE MEMBERS
    // --------------------------------------------------------

    Route::get('commembers', [
        AdminPastorateComMemberController::class,
        'index'
    ]);

    Route::get('commembers/{commember}', [
        AdminPastorateComMemberController::class,
        'show'
    ]);

    Route::post('commembers', [
        AdminPastorateComMemberController::class,
        'store'
    ]);

    Route::patch('commembers/{commember}', [
        AdminPastorateComMemberController::class,
        'update'
    ]);

    Route::post('commembers/{commember}/send', [
        AdminPastorateComMemberController::class,
        'publish'
    ]);


    // --------------------------------------------------------
    // EVENTS
    // --------------------------------------------------------

    Route::post('events', [
        AdminEventController::class,
        'store'
    ])->name('events.store');

    Route::get('events', [
        AdminEventController::class,
        'index'
    ])->name('events');

    Route::get('events/{event}/show', [
        AdminEventController::class,
        'show'
    ])->name('eventshow');

    Route::patch('events/{event}', [
        AdminEventController::class,
        'update'
    ])->name('events.update');

    Route::patch('events/{event}/hide', [
        AdminEventController::class,
        'hide'
    ]);

    Route::patch('events/{event}/show', [
        AdminEventController::class,
        'display'
    ]);

    Route::delete('events/{event}', [
        AdminEventController::class,
        'destroy'
    ])->name('events.destroy');

    Route::delete('events/{event}/photo', [
        AdminEventController::class,
        'removePhoto'
    ])->name('events.photo.remove');


    // --------------------------------------------------------
    // POOR FEEDING
    // --------------------------------------------------------

    Route::post('poor-feedings', [
        AdminPoorFeedingController::class,
        'store'
    ]);

    Route::get('poor-feedings', [
        AdminPoorFeedingController::class,
        'index'
    ]);

    Route::put('poor-feedings/{poorFeeding}', [
        AdminPoorFeedingController::class,
        'update'
    ]);

    Route::patch('poor-feedings/{poorFeeding}', [
        AdminPoorFeedingController::class,
        'update'
    ]);

    Route::delete('poor-feedings/{poorFeeding}', [
        AdminPoorFeedingController::class,
        'destroy'
    ]);

    Route::patch('poor-feedings/{pfeeding}/hide', [
        AdminPoorFeedingController::class,
        'hide'
    ]);

    Route::patch('poor-feedings/{pfeeding}/show', [
        AdminPoorFeedingController::class,
        'display'
    ]);

    Route::delete('poor-feedings/{poorFeeding}/photo', [
        AdminPoorFeedingController::class,
        'removePhoto'
    ]);


    // --------------------------------------------------------
    // DASHBOARD
    // --------------------------------------------------------

    Route::get('dashboard', [
        AdminDashboardController::class,
        'index'
    ]);


    // --------------------------------------------------------
    // SYSTEM / GREETINGS
    // --------------------------------------------------------



    Route::post('greetings/birthday/run', [
        AdminGreetingController::class,
        'runBirthday'
    ]);

    Route::get('greetings/birthday/logs', [
        AdminGreetingController::class,
        'logs'
    ]);

    Route::post('greetings/anniversary/run', [
        AdminGreetingController::class,
        'runAnniversary'
    ]);

    Route::get('greetings/anniversary/logs', [
        AdminGreetingController::class,
        'annlogs'
    ]);
});


// ============================================================
// HEALTH CHECK
// ============================================================

Route::get('/ping', function () {
    return response()->json([
        'message' => 'API OK'
    ]);
});
