<?php

namespace App\Services;

use App\Models\Member;
use App\Models\BirthdayGreeting;
use App\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Church;
use App\Models\Pastor;

class BirthdayGreetingService
{
    protected function log(
        string $type,
        string $message,
        string $level = 'info'
    ): void {
        DB::connection('tenant')
            ->table('system_run_logs')
            ->insert([
                'type' => $type,
                'message' => $message,
                'level' => $level,
                'created_at' => now(),
            ]);
    }

    public function run(Church $church, bool $sendWhatsapp = false): void
    {

        try {

            $today = Carbon::today();
            $year = $today->year;
            $churchId = $church->id;

            $this->log(
                'birthday',
                "🔍 Verifying birthdays for {$today->toDateString()}"
            );

            $members = Member::whereMonth(
                'date_of_birth',
                $today->month
            )
                ->whereDay(
                    'date_of_birth',
                    $today->day
                )
                ->get();

            $this->log(
                'birthday',
                "🎂 Found {$members->count()} member(s)"
            );


            $church = Church::on('platform')
                ->where('id', $churchId)
                ->firstOrFail();

            $presbyter = Pastor::where('order_no', 1)->first();

            foreach ($members as $member) {

                try {

                    $this->log(
                        'birthday',
                        "🎂 Processing member #{$member->id}: {$member->first_name} {$member->last_name}"
                    );

                    /*
        |--------------------------------------------------------------------------
        | Check whether birthday was already processed this year
        |--------------------------------------------------------------------------
        */

                    $alreadySent = BirthdayGreeting::where(
                        'member_id',
                        $member->id
                    )
                        ->where(
                            'greeted_year',
                            $year
                        )
                        ->exists();

                    if ($alreadySent) {

                        $this->log(
                            'birthday',
                            "⏭ Skipping {$member->first_name} #{$member->id} - already sent for {$year}"
                        );

                        continue;
                    }

                    /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

                    if ($member->email) {

                        $this->log(
                            'birthday',
                            "📧 Sending birthday email to {$member->email} for member #{$member->id}"
                        );

                        Mail::to($member->email)
                            ->send(
                                new \App\Mail\BirthdayWishMail(
                                    $member,
                                    $church,
                                    $presbyter
                                )
                            );

                        $this->log(
                            'birthday',
                            "✅ Birthday email sent to {$member->email}",
                            'success'
                        );
                    } else {

                        $this->log(
                            'birthday',
                            "⚠️ No email address for member #{$member->id}",
                            'warning'
                        );
                    }

                    /*
        |--------------------------------------------------------------------------
        | Birthday Greeting Record
        |--------------------------------------------------------------------------
        */

                    BirthdayGreeting::create([
                        'member_id' => $member->id,
                        'greeted_on' => $today,
                        'greeted_year' => $year,
                        'email_sent' => !empty($member->email),
                        'whatsapp_sent' => false,
                    ]);

                    $this->log(
                        'birthday',
                        "📝 BirthdayGreeting created for member #{$member->id}",
                        'success'
                    );

                    /*
        |--------------------------------------------------------------------------
        | Message Inbox Entry
        |--------------------------------------------------------------------------
        */
                    $name = $member->family_name . ' ' . $member->first_name . ' ' . $member->last_name ?? ($member->name ?? 'Friend');

                    $messageText = $this->buildMessage($member, $church, $presbyter);

                    $message = Message::create([
                        'member_id' => $member->id,
                        'title' => 'Happy Birthday 🎉',
                        'body' => $messageText,
                        'message_type' => 'birthday',
                        'image_path' => $member->profile_photo,
                        'is_published' => 1,
                        'published_at' => now(),
                    ]);

                    $this->log(
                        'birthday',
                        "📨 Birthday inbox message created for member #{$member->id}, message #{$message->id}",
                        'success'
                    );

                    /*
        |--------------------------------------------------------------------------
        | Device Tokens
        |--------------------------------------------------------------------------
        */

                    $tokens = DB::connection('tenant')
                        ->table('device_tokens')
                        ->where('member_id', $member->id)
                        ->pluck('token')
                        ->toArray();

                    $this->log(
                        'birthday',
                        "📱 Found " . count($tokens) .
                            " device token(s) for member #{$member->id}"
                    );

                    /*
        |--------------------------------------------------------------------------
        | Push Notification
        |--------------------------------------------------------------------------
        */

                    if (!empty($tokens)) {

                        ExpoPushService::send(
                            $tokens,
                            $message->title,
                            $message->body,
                            [
                                'type' => 'birthday',
                                'message_id' => $message->id,
                            ]
                        );

                        $this->log(
                            'birthday',
                            "🔔 Push notification sent for member #{$member->id}",
                            'success'
                        );
                    } else {

                        $this->log(
                            'birthday',
                            "⚠️ No device tokens for member #{$member->id}",
                            'warning'
                        );
                    }
                } catch (\Throwable $e) {

                    $this->log(
                        'birthday',
                        "❌ FAILED member #{$member->id} {$member->first_name}: {$e->getMessage()}",
                        'error'
                    );

                    Log::error(
                        'Birthday greeting failed for member',
                        [
                            'church_id' => $church->id,
                            'church_code' => $church->church_code,
                            'member_id' => $member->id,
                            'member_name' => $member->first_name . ' ' . $member->last_name,
                            'email' => $member->email,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]
                    );

                    throw $e;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Record Successful Run
            |--------------------------------------------------------------------------
            */

            DB::connection('tenant')
                ->table('system_runs')
                ->updateOrInsert(
                    ['type' => 'birthday'],
                    [
                        'last_run_at' => now(),
                        'status' => 'success',
                        'updated_at' => now(),
                    ]
                );

            $this->log(
                'birthday',
                "✅ Birthday greetings completed",
                'success'
            );
        } catch (\Throwable $e) {

            DB::connection('tenant')
                ->table('system_runs')
                ->updateOrInsert(
                    ['type' => 'birthday'],
                    [
                        'last_run_at' => now(),
                        'status' => 'failed',
                        'updated_at' => now(),
                    ]
                );

            $this->log(
                'birthday',
                "❌ ERROR: " . $e->getMessage(),
                'error'
            );

            Log::error(
                'Birthday cron failed',
                [
                    'church_id' => $church->id ?? null,
                    'church_code' => $church->church_code ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            throw $e;
        }
    }

    protected function buildMessage($member, $church, $presbyter): string
    {

        $name = trim(
            $member->first_name . ' ' . $member->last_name
        );

        $name = $name ?: 'Friend';

        $spouse = $member->spouse_name ?: 'your beloved spouse';

        $address = $member->gender === 'male'
            ? 'Mr.'
            : 'Ms.';

        $weddingYear = $member->wedding_date
            ? Carbon::parse($member->wedding_date)->format('Y')
            : 'the year of your wedding';

        $yearsMarried = $weddingYear
            ? Carbon::parse($member->wedding_date)->diffInYears(Carbon::today())
            : null;
        $anniversaryLine = $yearsMarried !== null
            ? "💍 Celebrating {$yearsMarried} Years of Marriage "
            : '';

        return <<<MSG
🎉 Happy Birthday, {$address} {$name}  🎉

 Grace and peace to you in the name of or Lor d Jesus Christ.

 On this special day, I join the entire church family in celebrating the gift of your life.

 As you begin a new year, may the Lord renew your strength, crown your days with joy
, open new doors of favour, and keep you and your household in good health and in His perfect peace.

Yours in Christ,
{$presbyter->name}

MSG;
    }
}
