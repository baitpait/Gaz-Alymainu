<?php

namespace App\Services\Documents;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\SupplierStatementService;

/**
 * Shared view data for purchase order print and PDF.
 */
class PurchaseOrderDocumentService
{
    public function __construct(
        private ArabicAmountInWords $amountInWords,
        private SupplierStatementService $statementService,
    ) {}

    /** @return array<string, mixed> */
    public function viewData(PurchaseOrder $purchaseOrder): array
    {
        $purchaseOrder->load(['lines', 'supplier']);

        $supplier = $purchaseOrder->supplier;
        $currencyCode = $purchaseOrder->currency_code ?? 'ILS';

        return [
            'purchaseOrder' => $purchaseOrder,
            'supplier' => $supplier,
            'amountInWords' => $this->amountInWords->format((float) $purchaseOrder->total_amount, $currencyCode),
            'supplierBalanceDue' => $this->supplierBalanceDue($supplier, $currencyCode, $purchaseOrder),
            'companyName' => config('app.company_display_name', config('app.name', 'غاز اليمين')),
            'logoPath' => public_path('branding/logo.png'),
            'pdfUrl' => route('purchase-orders.pdf', $purchaseOrder),
        ];
    }

    private function supplierBalanceDue(?Supplier $supplier, string $currencyCode, PurchaseOrder $purchaseOrder): ?float
    {
        if ($supplier === null) {
            return null;
        }

        $statement = $this->statementService->forSupplier($supplier);
        $balance = (float) ($statement[$currencyCode]['balance'] ?? 0);

        if ($balance <= 0.00001) {
            return null;
        }

        $priorBalance = $balance;
        if ($purchaseOrder->status === 'issued' && $purchaseOrder->currency_code === $currencyCode) {
            $priorBalance = $balance - (float) $purchaseOrder->total_amount;
        }

        if ($priorBalance <= 0.00001) {
            return null;
        }

        return round($balance, 2);
    }
}
