<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppSender;
use Illuminate\Support\Carbon;


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
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now();

        app(\App\Services\AnniversaryGreetingService::class)
            ->run($date, fn($msg) => $this->info($msg));

        return 0;
    }
}
