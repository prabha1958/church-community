<?php

namespace App\Mail;

use App\Models\ChurchRegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChurchRegistrationRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ChurchRegistrationRequest $registrationRequest;

    public function __construct(
        ChurchRegistrationRequest $registrationRequest
    ) {
        $this->registrationRequest = $registrationRequest;
    }

    public function build(): static
    {
        return $this
            ->subject(
                'New Church Registration Request - '
                    . $this->registrationRequest->church_name
            )
            ->view('emails.church-registration-request');
    }
}
