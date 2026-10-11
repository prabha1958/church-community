<?php

namespace App\Mail;

use App\Models\Church;
use App\Models\ChurchRegistrationRequest as ChurchRegistrationRequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChurchAdminSetupLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public ChurchRegistrationRequestModel $registrationRequest;
    public Church $church;
    public string $setupUrl;

    public function __construct(
        ChurchRegistrationRequestModel $registrationRequest,
        Church $church,
        string $setupUrl
    ) {
        $this->registrationRequest = $registrationRequest;
        $this->church = $church;
        $this->setupUrl = $setupUrl;
    }

    public function build(): static
    {
        return $this
            ->subject(
                'Set Up Your Church Administrator Account - '
                    . $this->church->church_name
            )
            ->view('emails.church-admin-setup-link')
            ->with([
                'adminName' => $this->registrationRequest->admin_name,
                'adminEmail' => $this->registrationRequest->admin_email,
                'churchName' => $this->church->church_name,
                'churchCode' => $this->church->church_code,
                'setupUrl' => $this->setupUrl,
                'expiresInHours' => 24,
            ]);
    }
}
