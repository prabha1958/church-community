<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Services\WhatsAppSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\AnniversaryWishMail;
use App\Models\Message;
use Illuminate\Support\Facades\Mail;

class SendAnniversaryGreetings extends Command
{
    protected $signature = 'greetings:anniversary {--date=}';
    protected $description = 'Send WhatsApp wedding anniversary greetings to male members';

    protected WhatsAppSender $whatsapp;

    public function __construct(WhatsAppSender $whatsapp)
    {
        parent::__construct();
        $this->whatsapp = $whatsapp;
    }

    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $month = $date->format('m');
        $day   = $date->format('d');

        $this->info("🎉 Checking anniversaries for {$date->toFormattedDateString()}...");

        $members = Member::query()
            ->where('gender', 'male')
            ->whereNotNull('wedding_date')
            ->whereRaw('MONTH(wedding_date) = ?', [$month])
            ->whereRaw('DAY(wedding_date) = ?', [$day])
            ->get();

        if ($members->isEmpty()) {
            $this->info('No male members celebrating anniversary today.');
            return 0;
        }

        foreach ($members as $member) {
            $this->sendGreeting($member, $date);
        }

        $this->info('✅ All anniversary greetings processed.');
        return 0;
    }

    protected function sendGreeting(Member $member, Carbon $date)
    {
        $spouse = $member->spouse_name ?: 'your beloved spouse';
        $firstName = $member->first_name ?: $member->family_name ?: 'Friend';
        $phone = $member->mobile_number;
        $email = $member->email;

        $exists = DB::table('anniversary_greetings')
            ->where('member_id', $member->id)
            ->where('sent_on', $date->toDateString())
            ->exists();

        if ($exists) {
            $this->info("Already sent to {$firstName}. Skipping.");
            return;
        }

        $messageText = <<<MSG
🎉 *Happy Wedding Anniversary, {$firstName}!* 🎉

May God bless your union with {$spouse} with many more years of joy, love, and togetherness.

— CSI Centenary Wesley Church, Ramkote
MSG;

        $whatsappSent = false;
        $emailSent = false;

        // 📱 WhatsApp
        if ($phone) {
            try {
                $whatsappSent = $this->whatsapp->send($phone, $messageText);
            } catch (\Throwable $e) {
                Log::error('Anniversary WhatsApp failed', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 📧 Email
        if ($email) {
            try {
                Mail::to($email)->send(new AnniversaryWishMail($member));
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::error('Anniversary email failed', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 🧾 Record anniversary_greetings
        DB::table('anniversary_greetings')->insert([
            'member_id' => $member->id,
            'wedding_date' => $member->wedding_date,
            'sent_on' => $date->toDateString(),
            'channel' => $whatsappSent ? 'whatsapp' : ($emailSent ? 'email' : 'failed'),
            'message' => $messageText,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imagePath = $member->getRawOriginal('couple_pic');
        // 📬 Record in messages table (for app inbox + push)
        Message::create([
            'member_id' => $member->id,
            'image_path' => $imagePath,
            'title' => 'Happy Wedding Anniversary 🎉',
            'body' => $messageText,
            'message_type' => 'anniversary',
            'is_published' => 1,
            'published_at' => now(),
        ]);

        $this->info("✅ Anniversary processed for {$firstName}");
    }
}
