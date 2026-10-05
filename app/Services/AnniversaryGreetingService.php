<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AnniversaryWishMail;
use Illuminate\Support\Str;
use App\Models\Church;
use App\Models\Pastor;

class AnniversaryGreetingService
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

    public function run(Carbon $date, Church $church, callable $log = null): void
    {
        try {

            $today = Carbon::today();
            $churchId = $church->id;

            $this->log(
                'anniversary',
                "🎉 Checking anniversaries for {$date->toFormattedDateString()}"
            );

            $members = Member::query()
                ->whereNotNull('wedding_date')
                ->whereMonth('wedding_date', $today->month)
                ->whereDay('wedding_date', $today->day)
                ->get();

            $this->log(
                'anniversary',
                "💍 Found {$members->count()} member(s)"
            );


            $church = Church::on('platform')
                ->where('id', $churchId)
                ->firstOrFail();

            $presbyter = Pastor::where('order_no', 1)->first();



            foreach ($members as $member) {

                $alreadySent = DB::connection('tenant')
                    ->table('anniversary_greetings')
                    ->where('member_id', $member->id)
                    ->where('sent_on', $today->toDateString())
                    ->exists();

                if ($alreadySent) {
                    $this->log(
                        'anniversary',
                        "⏭ Skipping {$member->first_name} (already sent today)"
                    );

                    continue;
                }

                $messageText = $this->buildMessage($member, $church, $presbyter);

                $emailSent = false;
                $whatsappSent = false;

                $weddingYear = $member->wedding_date
                    ? Carbon::parse($member->wedding_date)->format('Y')
                    : 'the year of your wedding';

                $yearsMarried = $weddingYear
                    ? Carbon::parse($member->wedding_date)->diffInYears(Carbon::today())
                    : null;

                // 📧 Email
                if ($member->email) {
                    try {

                        Mail::to($member->email)
                            ->queue(new AnniversaryWishMail($member, $church, $presbyter, $yearsMarried));

                        $emailSent = true;

                        $this->log(
                            'anniversary',
                            "📧 Email sent to {$member->email}",
                            'success'
                        );
                    } catch (\Throwable $e) {

                        Log::error('Anniversary email failed', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage(),
                        ]);

                        $this->log(
                            'anniversary',
                            "❌ Email failed for {$member->email}",
                            'error'
                        );
                    }
                }

                // 🧾 DB: anniversary_greetings
                DB::connection('tenant')
                    ->table('anniversary_greetings')
                    ->insert([
                        'member_id' => $member->id,
                        'wedding_date' => $member->wedding_date,
                        'sent_on' => $today->toDateString(),
                        'channel' => $emailSent ? 'email' : 'failed',
                        'message' => $messageText,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                // 📬 Message inbox entry
                $message = Message::create([
                    'member_id' => $member->id,
                    'title' => 'Happy Wedding Anniversary 🎉',
                    'body' => $messageText,
                    'message_type' => 'anniversary',
                    'image_path' => $member->getRawOriginal('couple_pic'),
                    'is_published' => 1,
                    'published_at' => now(),
                ]);

                // 📱 Device tokens
                $tokens = DB::connection('tenant')
                    ->table('device_tokens')
                    ->where('member_id', $member->id)
                    ->pluck('token')
                    ->toArray();

                ExpoPushService::send(
                    $tokens,
                    $message->title,
                    Str::limit($message->body, 80),
                    [
                        'type' => 'message',
                        'id' => $message->id,
                    ]
                );
            }

            // ✅ Record successful run
            DB::connection('tenant')
                ->table('system_runs')
                ->updateOrInsert(
                    ['type' => 'anniversary'],
                    [
                        'last_run_at' => now(),
                        'status' => 'success',
                        'updated_at' => now(),
                    ]
                );

            $this->log(
                'anniversary',
                "✅ Anniversary greetings completed",
                'success'
            );
        } catch (\Throwable $e) {

            // ❌ Record failed run
            DB::connection('tenant')
                ->table('system_runs')
                ->updateOrInsert(
                    ['type' => 'anniversary'],
                    [
                        'last_run_at' => now(),
                        'status' => 'failed',
                        'updated_at' => now(),
                    ]
                );

            $this->log(
                'anniversary',
                "❌ ERROR: " . $e->getMessage(),
                'error'
            );

            Log::error('Anniversary cron failed', [
                'error' => $e->getMessage()
            ]);
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
🎉 Happy Wedding Anniversary, {$address} {$name}  🎉

On this beautiful occasion of {$yearsMarried} years of marriage with {$spouse}, I extend my warmest greetings and prayers to you both on behalf of MODERN GIDEON CHURCH.

We thank God for the years of love, companionship, faithfulness and togetherness that He has blessed you with. May the Lord continue to strengthen the bond you share and guide you as you walk together in His grace.

May your home continue to be filled with love, understanding, peace and the joy of the Lord. May God bless you with many more wonderful years together and make your family a testimony of His unfailing faithfulness.

🎊 Wishing you both a very Happy Wedding Anniversary! 🎊

God bless you and your family abundantly.

Yours in Christ,
{$presbyter->name}

MSG;
    }
}
