<?php

namespace App\Mail;

use App\Models\Church;
use App\Models\ChurchRegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChurchAdminSetupLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChurchRegistrationRequest $registrationRequest,
        public Church $church,
        public string $setupUrl
    ) {}

    public function build()
    {
        return $this
            ->subject(
                'Your Church Community administrator setup'
            )
            ->view(
                'emails.church-admin-setup-link'
            );
    }
}
