<?php

namespace App\Livewire\Reports;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تقرير حركات المخزون: شراء/تحميل/إرجاع/تحويل/بيع/تسوية ضمن الفترة.
 */
class StockMovementsReport extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $type = '';

    public string $warehouseId = '';

    public string $productId = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-period-reports'), 403);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    private function baseQuery()
    {
        return StockMovement::query()
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'driver'])
            ->whereDate('moved_at', '>=', $this->dateFrom)
            ->whereDate('moved_at', '<=', $this->dateTo)
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->when($this->warehouseId, fn ($q) => $q->where(function ($sub) {
                $sub->where('from_warehouse_id', $this->warehouseId)
                    ->orWhere('to_warehouse_id', $this->warehouseId);
            }));
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');
        $rows = $this->baseQuery()->latest('moved_at')->get();
        $filename = 'حركات-المخزون-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['التاريخ', 'النوع', 'من', 'إلى', 'الصنف', 'الكمية', 'السائق']);
            foreach ($rows as $r) {
                fputcsv($h, [
                    $r->moved_at?->format('Y-m-d H:i'),
                    $r->type->label(),
                    $r->fromWarehouse?->name ?? '',
                    $r->toWarehouse?->name ?? '',
                    $r->product?->name ?? '',
                    number_format((float) $r->quantity, 2, '.', ''),
                    $r->driver?->full_name ?? '',
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        abort_unless(Gate::allows('view-period-reports'), 403);

        $rows = $this->baseQuery()->latest('moved_at')->latest('id')->get();

        return view('livewire.reports.gas.stock-movements-report', [
            'rows' => $rows,
            'count' => $rows->count(),
            'typeOptions' => [
                StockMovementType::PurchaseIn->value => StockMovementType::PurchaseIn->label(),
                StockMovementType::Load->value => StockMovementType::Load->label(),
                StockMovementType::ReturnToWarehouse->value => StockMovementType::ReturnToWarehouse->label(),
                StockMovementType::Transfer->value => StockMovementType::Transfer->label(),
                StockMovementType::SaleOut->value => StockMovementType::SaleOut->label(),
                StockMovementType::Adjustment->value => StockMovementType::Adjustment->label(),
            ],
            'warehouses' => Warehouse::orderBy('type')->orderBy('name')->get(['id', 'name']),
            'products' => Product::stockTracked()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
