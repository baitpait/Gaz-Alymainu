<?php

namespace App\Livewire\Reports;

use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تقرير أرصدة المخزون: لقطة حالية للكمية لكل صنف في كل مخزن/سيارة.
 */
class StockBalancesReport extends Component
{
    public string $warehouseId = '';

    public bool $hideZero = true;

    public function mount(): void
    {
        abort_unless(Gate::allows('view-period-reports'), 403);
    }

    private function baseQuery()
    {
        return StockBalance::query()
            ->with(['warehouse', 'product'])
            ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId))
            ->when($this->hideZero, fn ($q) => $q->where('quantity', '>', 0));
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');
        $rows = $this->baseQuery()->get();
        $filename = 'ارصدة-المخزون-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['المخزن', 'النوع', 'الصنف', 'الكمية']);
            foreach ($rows as $r) {
                fputcsv($h, [
                    $r->warehouse?->name ?? '',
                    $r->warehouse?->type->label() ?? '',
                    $r->product?->name ?? '',
                    number_format((float) $r->quantity, 2, '.', ''),
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        abort_unless(Gate::allows('view-period-reports'), 403);

        $balances = $this->baseQuery()->get()
            ->filter(fn ($b) => $b->warehouse !== null && $b->product !== null)
            ->sortBy(fn ($b) => $b->product->name);

        // تجميع حسب المخزن
        $grouped = $balances->groupBy(fn ($b) => $b->warehouse->name);

        return view('livewire.reports.gas.stock-balances-report', [
            'grouped' => $grouped,
            'grandQty' => (float) $balances->sum('quantity'),
            'warehouses' => Warehouse::orderBy('type')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
