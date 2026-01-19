<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AlliancePayment;

class AllianceReceiptMail extends Mailable
{
    public AlliancePayment $payment;

    public function __construct(AlliancePayment $payment)
    {
        $this->payment = $payment;
    }

    public function build()
    {
        $payment = $this->payment->load(['member', 'alliance']);

        $pdf = Pdf::loadView('pdf.alliance-receipt', [
            'receipt_no' => $payment->id,
            'date'       => $payment->paid_at->format('d-m-Y'),
            'member'     => $payment->member,
            'alliance'   => $payment->alliance,
            'amount'     => $payment->amount,
            'payment_id' => $payment->payment_gateway_payment_id,
        ]);

        return $this->subject('Alliance Payment Receipt')
            ->view('emails.alliance-receipt')
            ->with(['member' => $payment->member])
            ->attachData(
                $pdf->output(),
                'alliance-receipt-' . $payment->id . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
