<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class BirthdayWishMail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $church;
    public $presbyter;

    /**
     * Create a new message instance.
     */
    public function __construct($member, $church, $presbyter = null)
    {
        $this->member = $member;
        $this->church = $church;
        $this->presbyter = $presbyter;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $name = $this->member->family_name . ' ' . $this->member->first_name . ' ' . $this->member->last_name ?? ($this->member->name ?? 'Friend');

        return $this->subject("Happy Birthday, {$name}!")
            ->view('emails.birthday_wish') // create resources/views/emails/birthday_wish.blade.php
            ->with([
                'member' => $this->member,
                'church' => $this->church,
                'presbyter' => $this->presbyter,
                'name' => $name,
            ]);
    }
}
