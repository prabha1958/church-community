<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BirthdayWishMail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $church;
    public $presbyter;
    public $name;

    /**
     * Create a new message instance.
     */
    public function __construct($member, $church, $presbyter = null)
    {
        $this->member = $member;
        $this->church = $church;
        $this->presbyter = $presbyter;

        /*
        |--------------------------------------------------------------------------
        | Member name
        |--------------------------------------------------------------------------
        */

        $name = trim(
            collect([
                $member->family_name ?? null,
                $member->first_name ?? null,
                $member->last_name ?? null,
            ])
                ->filter()
                ->implode(' ')
        );

        $this->name = $name ?: ($member->name ?? 'Friend');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                $church->email ?? 'noreply@yourchurch.org',
                $this->church->church_name
            ),
            subject: "Happy Birthday, {$this->name}!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.birthday_wish',
            with: [
                'member' => $this->member,
                'church' => $this->church,
                'presbyter' => $this->presbyter,
                'name' => $this->name,
            ],
        );
    }
}
