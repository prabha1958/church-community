<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $contact;

    public function __construct(string $contact, string $code)
    {
        $this->contact = $contact;
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('Your login OTP')
            ->markdown('emails.otp')
            ->with([
                'code' => $this->code,
                'contact' => $this->contact,
            ]);
    }
}
