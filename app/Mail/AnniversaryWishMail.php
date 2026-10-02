<?php

namespace App\Mail;

use App\Models\Church;
use App\Models\Member;
use App\Models\Pastor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnniversaryWishMail extends Mailable
{
    use Queueable, SerializesModels;

    public Member $member;
    public Church $church;
    public ?Pastor $presbyter;

    public string $name;
    public string $churchLogoUrl;
    public string $presbyterPhotoUrl;
    public int $yearsMarried;

    public function __construct(
        Member $member,
        Church $church,
        ?Pastor $presbyter = null,
        int $yearsMarried = 0
    ) {
        $this->member = $member;
        $this->church = $church;
        $this->presbyter = $presbyter;
        $this->yearsMarried = $yearsMarried;

        $this->name = trim(
            collect([
                $member->family_name ?? null,
                $member->first_name ?? null,
                $member->last_name ?? null,
            ])
                ->filter()
                ->implode(' ')
        );

        $this->name = $this->name ?: 'Friend';

        $this->churchLogoUrl = $church->logo
            ? asset('storage/' . $church->logo)
            : '';

        $this->presbyterPhotoUrl = '';

        if ($presbyter && !empty($presbyter->photo)) {
            $this->presbyterPhotoUrl =
                asset('storage/' . $presbyter->photo);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                $this->church->church_name
            ),

            replyTo: $this->church->email
                ? [
                    new Address(
                        $this->church->email,
                        $this->church->church_name
                    )
                ]
                : [],

            subject: "Happy Wedding Anniversary, {$this->name}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.anniversary',
            with: [
                'member' => $this->member,
                'church' => $this->church,
                'presbyter' => $this->presbyter,
                'name' => $this->name,
                'yearsMarried' => $this->yearsMarried,

            ],
        );
    }
}
