<?php

namespace App\Livewire;

use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Livewire\Concerns\FiltersSuppliersForSelect;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderPaymentAllocationService;
use App\Services\PurchaseOrderStockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class PurchaseOrderList extends Component
{
    use AppliesListFiltersOnAction;
    use FiltersSuppliersForSelect;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'po_supplier')]
    public string $filterSupplierId = '';

    #[Url(as: 'po_status')]
    public string $filterStatus = '';

    #[Url(as: 'po_cur')]
    public string $filterCurrency = '';

    #[Url(as: 'po_from')]
    public string $filterDateFrom = '';

    #[Url(as: 'po_to')]
    public string $filterDateTo = '';

    public ?int $confirmDeleteId = null;

    public ?int $viewingId = null;

    public function clearPurchaseOrderFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterSupplierId = '';
        $this->filterCurrency = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->supplierSearch = '';
        $this->resetPage();
    }

    public function hasActivePurchaseOrderFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->filterStatus !== ''
            || $this->filterSupplierId !== ''
            || trim($this->supplierSearch) !== ''
            || $this->filterCurrency !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== '';
    }

    public function openView(int $id): void
    {
        $this->confirmDeleteId = null;
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    #[Computed]
    public function viewingRecord(): ?PurchaseOrder
    {
        return $this->viewingId ? PurchaseOrder::with(['supplier', 'lines'])->find($this->viewingId) : null;
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('delete', PurchaseOrder::findOrFail($id));
        $this->viewingId = null;
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    /**
     * Business Purpose: حذف فاتورة المشتريات مع عكس أي مخزون سبق ترحيله منها.
     */
    public function delete(PurchaseOrderStockService $stockService): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $po = PurchaseOrder::with(['lines.product', 'receivingWarehouse'])->findOrFail($this->confirmDeleteId);
        Gate::authorize('delete', $po);

        try {
            DB::transaction(function () use ($po, $stockService) {
                $stockService->reversePostedInventory($po, auth()->id());
                $po->delete();
            });
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'تم حذف فاتورة المشتريات وإرجاع أثر المخزون');
    }

    public function render()
    {
        $poCurrencies = PurchaseOrder::query()
            ->whereNotNull('currency_code')
            ->where('currency_code', '!=', '')
            ->distinct()
            ->orderBy('currency_code')
            ->pluck('currency_code')
            ->values()
            ->all();

        $filterCur = strtoupper(trim($this->filterCurrency));
        $currencyFilterActive = $filterCur !== '' && in_array($filterCur, $poCurrencies, true);

        $query = PurchaseOrder::with('supplier')
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(fn ($q) => $q->where('legacy_po_no', 'like', $s)
                    ->orWhere('notes', 'like', $s)
                    ->orWhereHas('supplier', fn ($q) => $q->where('business_name', 'like', $s)
                        ->orWhere('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                        ->orWhere('phone_primary', 'like', $s)
                        ->orWhere('email', 'like', $s)
                    )
                );
            })
            ->when(in_array($this->filterStatus, ['draft', 'issued', 'void'], true), fn ($q) => $q->where('status', $this->filterStatus)
            )
            ->when(ctype_digit($this->filterSupplierId) && Supplier::whereKey((int) $this->filterSupplierId)->exists(), fn ($q) => $q->where('supplier_id', (int) $this->filterSupplierId)
            );

        $this->applySupplierSearchToPartyRelation($query);

        $query
            ->when($currencyFilterActive, fn ($q) => $q->where('currency_code', $filterCur))
            ->when($this->filterDateFrom !== '', function ($q) {
                try {
                    $from = Carbon::parse($this->filterDateFrom)->startOfDay();
                    $q->whereDate('document_date', '>=', $from);
                } catch (\Throwable) {
                }
            })
            ->when($this->filterDateTo !== '', function ($q) {
                try {
                    $to = Carbon::parse($this->filterDateTo)->endOfDay();
                    $q->whereDate('document_date', '<=', $to);
                } catch (\Throwable) {
                }
            })
            ->latest('document_date');

        $rows = $this->paginateWithPerPage($query);

        $paymentStatuses = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrders(
            collect($rows->items())
        );

        return view('livewire.purchase-order-list', [
            'rows' => $rows,
            'suppliers' => $this->suppliersForSelect(),
            'poCurrencies' => $poCurrencies,
            'viewingRecord' => $this->viewingRecord,
            'paymentStatuses' => $paymentStatuses,
        ]);
    }
}
