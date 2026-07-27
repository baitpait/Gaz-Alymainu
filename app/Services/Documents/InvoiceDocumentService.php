<?php

namespace App\Services\Documents;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\ClientStatementService;

/**
 * Shared view data for invoice print and PDF (same business rules).
 */
class InvoiceDocumentService
{
    public function __construct(
        private ArabicAmountInWords $amountInWords,
        private ClientStatementService $statementService,
    ) {}

    /** @return array<string, mixed> */
    public function viewData(Invoice $invoice): array
    {
        $invoice->load(['lines', 'client']);

        $client = $invoice->client;
        $currencyCode = $invoice->currency_code ?? 'ILS';

        return [
            'invoice' => $invoice,
            'client' => $client,
            'amountInWords' => $this->amountInWords->format((float) $invoice->total_amount, $currencyCode),
            'clientBalanceDue' => $this->clientBalanceDue($client, $currencyCode, $invoice),
            'companyName' => config('app.company_display_name', config('app.name', 'غاز اليمين')),
            'logoPath' => public_path('branding/logo.png'),
            'pdfUrl' => route('invoices.pdf', $invoice),
        ];
    }

  /**
   * Show total amount due only when the client had prior balance in this currency.
   */
    private function clientBalanceDue(?Client $client, string $currencyCode, Invoice $invoice): ?float
    {
        if ($client === null) {
            return null;
        }

        $statement = $this->statementService->forClient($client);
        $balance = (float) ($statement[$currencyCode]['balance'] ?? 0);

        if ($balance <= 0.00001) {
            return null;
        }

        $priorBalance = $balance;
        if ($invoice->status === 'issued' && $invoice->currency_code === $currencyCode) {
            $priorBalance = $balance - (float) $invoice->total_amount;
        }

        if ($priorBalance <= 0.00001) {
            return null;
        }

        return round($balance, 2);
    }
}
