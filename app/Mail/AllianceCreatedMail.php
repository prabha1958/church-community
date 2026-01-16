<?php

namespace App\Mail;

use App\Models\Alliance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AllianceCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Alliance $alliance;

    public function __construct(Alliance $alliance)
    {
        $this->alliance = $alliance;
    }

    public function build()
    {
        return $this
            ->subject('Alliance Profile Created – CSI Centenary Wesley Church')
            ->view('emails.alliance-created')
            ->with([
                'alliance' => $this->alliance,
                'member'   => $this->alliance->member,
            ]);
    }
}
