<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

class SubscriptionReceiptService
{
    public function generate(Payment $payment): string
    {
        $payment->load(['member', 'subscription']);

        $pdf = Pdf::loadView('pdf.subscription-receipt', [
            'receipt_no' => $payment->id,
            'date' => $payment->created_at->format('d-m-Y'),
            'member' => $payment->member,
            'subscription' => $payment->subscription,
            'months' => $payment->raw['months'] ?? [],
            'amount' => $payment->amount,
            'financial_year' => $payment->subscription->financial_year,
        ]);

        $path = "receipts/subscriptions/subscription-receipt-{$payment->id}.pdf";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
