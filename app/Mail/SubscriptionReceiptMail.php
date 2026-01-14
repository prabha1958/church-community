<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;

class SubscriptionReceiptMail extends Mailable
{
    public Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function build()
    {
        $payment = $this->payment->load(['member', 'subscription']);
        $months = $payment->raw['months'] ?? [];

        $pdf = Pdf::loadView('pdf.subscription-receipt', [
            'receipt_no' => $payment->id,
            'date' => $payment->created_at->format('d-m-Y'),
            'member' => $payment->member,
            'subscription' => $payment->subscription,
            'months' => $months,
            'amount' => $payment->amount,
            'payment_mode' => $payment->payment_mode,
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_payment_id' => $payment->razorpay_payment_id,
            'financial_year' => $payment->subscription->financial_year,
        ]);

        return $this->subject('Subscription Payment Receipt')
            ->view('emails.subscription-receipt')   // ✅ EMAIL VIEW
            ->with([
                'member' => $payment->member,
                'amount' => $payment->amount,
                'months' => $months,
                'financial_year' => $payment->subscription->financial_year,
                'payment_mode' => $payment->payment_mode,
                'reference_no' => $payment->reference_no,
            ])
            ->attachData(
                $pdf->output(),
                'subscription-receipt-' . $payment->id . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
