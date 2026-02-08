<?php

namespace App\Services;

use App\Models\Member;
use App\Models\BirthdayGreeting;
use App\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BirthdayGreetingService
{
    protected function log(string $type, string $message, string $level = 'info')
    {
        DB::table('system_run_logs')->insert([
            'type' => $type,
            'message' => $message,
            'level' => $level,
            'created_at' => now(),
        ]);
    }

    public function run(bool $sendWhatsapp = false): void
    {
        $today = Carbon::today();
        $year = $today->year;

        $this->log('birthday', "🔍 Verifying birthdays for {$today->toDateString()}");

        $members = Member::whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->get();

        $this->log('birthday', "🎂 Found {$members->count()} member(s)");

        foreach ($members as $member) {

            $alreadySent = BirthdayGreeting::where('member_id', $member->id)
                ->where('greeted_year', $year)
                ->exists();

            if ($alreadySent) {
                $this->log('birthday', "⏭ Skipping {$member->first_name} (already sent)");
                continue;
            }

            // Email
            if ($member->email) {
                Mail::to($member->email)->send(new \App\Mail\BirthdayWishMail($member));
                $this->log('birthday', "📧 Email sent to {$member->email}", 'success');
            }

            BirthdayGreeting::create([
                'member_id' => $member->id,
                'greeted_on' => $today,
                'greeted_year' => $year,
                'email_sent' => true,
                'whatsapp_sent' => false,
            ]);

            Message::create([
                'member_id' => $member->id,
                'title' => 'Happy Birthday 🎉',
                'body' => "Happy Birthday {$member->first_name}!",
                'message_type' => 'birthday',
                'image_path' => $member->profile_photo,
                'is_published' => 1,
            ]);
        }

        DB::table('system_runs')->updateOrInsert(
            ['type' => 'birthday'],
            ['last_run_at' => now(), 'status' => 'success']
        );

        $this->log('birthday', "✅ Birthday greetings completed", 'success');
    }
}
