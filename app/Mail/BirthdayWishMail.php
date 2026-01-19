<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BirthdayWishMail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;

    /**
     * Create a new message instance.
     */
    public function __construct($member)
    {
        $this->member = $member;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $name = $this->member->first_name ?? ($this->member->name ?? 'Friend');

        return $this->subject("Happy Birthday, {$name}!")
            ->view('emails.birthday_wish') // create resources/views/emails/birthday_wish.blade.php
            ->with([
                'member' => $this->member,
            ]);
    }
}
