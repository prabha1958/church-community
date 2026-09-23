<?php

namespace App\Mail;

use App\Models\Church;
use App\Models\PlatformUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SetupAdminWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PlatformUser $user,
        public Church $church,
        public string $temporaryPassword,
    ) {
        $this->afterCommit();
    }

    public function build(): self
    {
        return $this
            ->subject('Your Church Community Setup Administrator Account')
            ->view('emails.setup-admin-welcome');
    }
}
