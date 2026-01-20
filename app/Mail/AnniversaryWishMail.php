<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Member;

class AnniversaryWishMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Member $member) {}

    public function build()
    {
        return $this->subject('Happy Wedding Anniversary 🎉')
            ->view('emails.anniversary')
            ->with('member', $this->member);
    }
}
