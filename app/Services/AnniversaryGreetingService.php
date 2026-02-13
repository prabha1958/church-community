<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AnniversaryWishMail;

class AnniversaryGreetingService
{
    protected function log(string $type, string $message, string $level = 'info'): void
    {
        DB::table('system_run_logs')->insert([
            'type' => $type,
            'message' => $message,
            'level' => $level,
            'created_at' => now(),
        ]);
    }

    public function run(Carbon $date, callable $log = null): void
    {

        try {

            $today = Carbon::today();

            $month = $date->month;
            $day   = $date->day;

            $this->log('anniversary', "🎉 Checking anniversaries for {$date->toFormattedDateString()}");



            $members = Member::query()
                ->where('gender', 'male')
                ->whereNotNull('wedding_date')
                ->whereMonth('wedding_date', $today->month)
                ->whereDay('wedding_date', $today->day)
                ->get();

            $this->log('anniversary', "💍 Found {$members->count()} member(s)");

            foreach ($members as $member) {

                $alreadySent = DB::table('anniversary_greetings')
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

                $messageText = $this->buildMessage($member);

                $emailSent = false;
                $whatsappSent = false;

                // 📧 Email
                if ($member->email) {
                    try {
                        Mail::to($member->email)->send(new AnniversaryWishMail($member));
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
                DB::table('anniversary_greetings')->insert([
                    'member_id' => $member->id,
                    'wedding_date' => $member->wedding_date,
                    'sent_on' => $today->toDateString(),
                    'channel' => $emailSent ? 'email' : 'failed',
                    'message' => $messageText,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 📬 Message inbox entry
                Message::create([
                    'member_id' => $member->id,
                    'title' => 'Happy Wedding Anniversary 🎉',
                    'body' => $messageText,
                    'message_type' => 'anniversary',
                    'image_path' => $member->getRawOriginal('couple_pic'),
                    'is_published' => 1,
                    'published_at' => now(),
                ]);
            }

            DB::table('system_runs')->updateOrInsert(
                ['type' => 'anniversary'],
                ['last_run_at' => now(), 'status' => 'success']
            );


            DB::table('system_runs')->updateOrInsert(
                ['type' => 'anniversary'],
                [
                    'last_run_at' => now(),
                    'status' => 'success',
                    'updated_at' => now(),
                ]
            );

            $this->log('anniversary', "✅ Anniversary greetings completed", 'success');
        } catch (\Throwable $e) {

            DB::table('system_runs')->updateOrInsert(
                ['type' => 'anniversary'],
                [
                    'last_run_at' => now(),
                    'status' => 'failed',
                    'updated_at' => now(),
                ]
            );

            $this->log('anniversary', "❌ ERROR: " . $e->getMessage(), 'error');

            Log::error('Anniversary cron failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function buildMessage(Member $member): string
    {
        $name = $member->first_name ?: 'Friend';
        $spouse = $member->spouse_name ?: 'your beloved spouse';

        return <<<MSG
            🎉 Happy Wedding Anniversary, {$name}! 🎉

            May God bless your union with {$spouse} with love, peace, and togetherness.

            — CSI Centenary Wesley Church, Ramkote
            MSG;
    }
}
