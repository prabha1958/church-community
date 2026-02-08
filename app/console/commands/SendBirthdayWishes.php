<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Mail\BirthdayWishMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\BirthdayGreeting;
use App\Models\Message;
use Illuminate\Support\Facades\DB;


class SendBirthdayWishes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run with: php artisan send:birthday-wishes
     */
    protected $signature = 'send:birthday-wishes
                            {--whatsapp : send WhatsApp messages instead of SMS}
                            {--dry : dry run - do not actually send messages}
                            {--template= : custom message template (optional)}';

    protected $description = 'Send birthday wishes (email and optional WhatsApp) to members whose birthday is today';

    public function handle(): int
    {
        $today = Carbon::today();
        $month = $today->month;
        $day = $today->day;
        $year = $today->year;


        $this->info("Looking up members with birthday on {$today->toDateString()}");

        $members = Member::query()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $month)
            ->whereDay('date_of_birth', $day)
            ->get();

        $count = $members->count();
        $this->info("Found {$count} member(s).");

        if ($count === 0) {
            return 0;
        }





        $sendWhatsapp = $this->option('whatsapp');
        $dry = $this->option('dry');
        $templateOption = $this->option('template');

        foreach ($members as $member) {

            // 🔒 DUPLICATE CHECK (correct place)
            $alreadySent = BirthdayGreeting::where('member_id', $member->id)
                ->where('greeted_year', $year)
                ->exists();

            if ($alreadySent) {
                $this->warn("Birthday already sent to member {$member->id} for {$year}, skipping.");
                continue;
            }

            $toEmail = $member->email;
            $toMobile = $member->mobile_number ?? $member->mobile ?? null;

            $emailSent = false;
            $whatsAppSent = false;

            $this->line("Processing member ID {$member->id}");


            // 📧 EMAIL
            if ($toEmail && ! $dry) {
                try {
                    Mail::to($toEmail)->send(new BirthdayWishMail($member));
                    $emailSent = true;
                    $this->info("Email sent to {$toEmail}");
                } catch (\Throwable $e) {
                    Log::error('Birthday email failed', [
                        'member_id' => $member->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 📱 WHATSAPP
            if ($sendWhatsapp && $toMobile && ! $dry) {
                try {
                    $text = $this->buildWhatsappText($member, $templateOption);
                    $this->sendWhatsAppViaTwilio($toMobile, $text);
                    $whatsAppSent = true;
                    $this->info("WhatsApp sent to {$toMobile}");
                } catch (\Throwable $e) {
                    Log::error('Birthday WhatsApp failed', [
                        'member_id' => $member->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 🧾 DB RECORDS — ALWAYS CREATED ONCE
            try {
                BirthdayGreeting::create([
                    'member_id' => $member->id,
                    'greeted_on' => $today->toDateString(),
                    'greeted_year' => $year,
                    'email_sent' => $emailSent,
                    'whatsapp_sent' => $whatsAppSent,
                ]);

                $imagePath = $member->getRawOriginal('profile_photo');

                Message::create([
                    'member_id' => $member->id,
                    'image_path' => $imagePath,
                    'title' => 'Happy Birthday 🎉',
                    'body' => $this->buildWhatsappText($member, $templateOption),
                    'message_type' => 'birthday',
                    'is_published' => 1,
                    'published_at' => now(),
                ]);

                DB::table('system_runs')->updateOrInsert(
                    ['type' => 'birthday'],
                    [
                        'last_run_at' => now(),
                        'status' => 'success',
                        'updated_at' => now(),
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Failed to persist birthday greeting', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
            }
        }


        $this->info('Done.');
        return 0;
    }

    /**
     * Build WhatsApp message text (simple templating).
     */
    protected function buildWhatsappText($member, ?string $template = null): string
    {
        $name = $member->first_name ?? $member->name ?? 'Friend';
        $default = "Happy Birthday, {$name}! 🎉\nWarm wishes from CSI CENTENARY WESLEY CHURCH on your Birthday. God bless you.";
        if (! $template) return $default;

        // Simple replacements: {name}
        return str_replace('{name}', $name, $template);
    }

    /**
     * Send WhatsApp message using Twilio (SDK or HTTP fallback).
     *
     * Returns array with 'sid' when available.
     */
    protected function sendWhatsAppViaTwilio(string $toMobile, string $message): array
    {
        // Normalize to E.164 if possible (caller should provide a proper number)
        // Twilio requires 'whatsapp:+<E.164>'
        $to = $this->normalizeWhatsAppNumber($toMobile);

        $sid = config('services.twilio.sid') ?? env('TWILIO_SID');
        $token = config('services.twilio.token') ?? env('TWILIO_TOKEN');
        $from = config('services.twilio.whatsapp_from') ?? env('TWILIO_WHATSAPP_FROM');

        if (! $sid || ! $token || ! $from) {
            throw new \RuntimeException('Twilio WhatsApp credentials not configured (TWILIO_SID / TWILIO_TOKEN / TWILIO_WHATSAPP_FROM).');
        }

        // If Twilio SDK is available, use it
        if (class_exists(\Twilio\Rest\Client::class)) {
            $client = new \Twilio\Rest\Client($sid, $token);
            $messageResp = $client->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);
            return ['sid' => $messageResp->sid ?? null];
        }

        // Fallback HTTP call to Twilio API
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, [
                'From' => $from,
                'To'   => $to,
                'Body' => $message,
            ]);





        return ['sid' => $json['sid'] ?? null];
    }

    /**
     * Normalize a raw mobile number to Twilio WhatsApp format: 'whatsapp:+<E.164>'.
     * If number already starts with 'whatsapp:' keep; if already E.164 begin with '+', prefix with 'whatsapp:'.
     * This is a best-effort helper; prefer storing E.164 numbers in DB.
     */
    protected function normalizeWhatsAppNumber(string $raw): string
    {
        $raw = trim($raw);

        if (stripos($raw, 'whatsapp:') === 0) {
            return $raw;
        }

        // if number already has +, just prefix
        if (str_starts_with($raw, '+')) {
            return 'whatsapp:' . $raw;
        }

        // remove non-digit characters and prefix with + if you know country code (dangerous)
        $digits = preg_replace('/\D+/', '', $raw);

        // If digits already include country code (best-effort), prefix +.
        // WARNING: This may be incorrect for local numbers. Prefer E.164 storage.
        return 'whatsapp:+' . $digits;
    }
}
