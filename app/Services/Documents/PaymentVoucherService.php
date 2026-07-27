<?php

namespace App\Services\Documents;

use App\Models\ClientPayment;
use App\Models\SupplierPayment;
use App\Services\Finance\PaymentMethod;

/**
 * Shared view data for client/supplier payment voucher print and PDF.
 */
class PaymentVoucherService
{
    public function __construct(private ArabicAmountInWords $amountInWords) {}

    /** @return array<string, mixed> */
    public function forClientPayment(ClientPayment $payment): array
    {
        $payment->load(['client', 'recordedBy']);

        return [
            'payment' => $payment,
            'party' => $payment->client,
            'partyName' => $payment->client?->displayName() ?? '—',
            'voucherTitle' => 'سند قبض',
            'voucherSubtitle' => 'Receipt Voucher',
            'partyLabel' => 'استلمنا من السيد / السادة',
            'amountInWords' => $this->amountInWords->format(
                (float) $payment->amount,
                $payment->currency_code ?? 'ILS'
            ),
            'methodLabel' => PaymentMethod::label($payment->method),
            'companyName' => config('app.company_display_name', config('app.name', 'غاز اليمين')),
            'logoPath' => public_path('branding/logo.png'),
            'pdfUrl' => route('payments.pdf', $payment),
        ];
    }

    /** @return array<string, mixed> */
    public function forSupplierPayment(SupplierPayment $payment): array
    {
        $payment->load(['supplier', 'recordedBy']);

        return [
            'payment' => $payment,
            'party' => $payment->supplier,
            'partyName' => $payment->supplier?->displayName() ?? '—',
            'voucherTitle' => 'سند صرف',
            'voucherSubtitle' => 'Payment Voucher',
            'partyLabel' => 'دفعنا إلى السيد / السادة',
            'amountInWords' => $this->amountInWords->format(
                (float) $payment->amount,
                $payment->currency_code ?? 'ILS'
            ),
            'methodLabel' => PaymentMethod::label($payment->method),
            'companyName' => config('app.company_display_name', config('app.name', 'غاز اليمين')),
            'logoPath' => public_path('branding/logo.png'),
            'pdfUrl' => route('supplier-payments.pdf', $payment),
        ];
    }
}
