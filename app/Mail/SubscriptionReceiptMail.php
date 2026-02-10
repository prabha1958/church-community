<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;
use App\Models\Member;

class SubscriptionReceiptMail extends Mailable
{
    public Payment $payment;
    public $fy;
    public Member $member;
    public $receiptPath;




    public function __construct(Payment $payment, $fy, Member $member, $receiptPath)
    {
        $this->payment = $payment;
        $this->fy = $fy;
        $this->member = $member;
        $this->receiptPath = $receiptPath;
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
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_payment_id' => $payment->razorpay_payment_id,
            'financial_year' => $payment->subscription->financial_year,
        ]);

        return $this->subject('Subscription Payment Receipt')
            ->view('emails.subscription-receipt')
            ->with([
                'member' => $payment->member,
                'amount' => $payment->amount,
                'months' => $months,
                'receipt_no' => $payment->id,
                'payment' => $payment,
                'fy' => $this->fy,
            ])
            ->attach(
                storage_path("app/public/{$this->receiptPath}"),
                ['mime' => 'application/pdf']
            );
    }
}
