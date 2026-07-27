<?php

namespace App\Livewire\Reports;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تقرير مبيعات الغاز: تفصيل عمليات البيع (نقدي/على الحساب) حسب الفترة والسائق والصنف.
 */
class GasSalesReport extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $driverId = '';

    public string $productId = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-period-reports'), 403);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    private function baseQuery()
    {
        return Sale::query()
            ->with(['product', 'warehouse', 'driver'])
            ->whereDate('sale_date', '>=', $this->dateFrom)
            ->whereDate('sale_date', '<=', $this->dateTo)
            ->when($this->driverId, fn ($q) => $q->where('driver_user_id', $this->driverId))
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId));
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');
        $rows = $this->baseQuery()->latest('sold_at')->get();
        $filename = 'مبيعات-الغاز-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['التاريخ', 'الوقت', 'السائق', 'السيارة', 'الصنف', 'النوع', 'الكمية', 'السعر', 'الإجمالي']);
            foreach ($rows as $r) {
                fputcsv($h, [
                    $r->sale_date?->format('Y-m-d'),
                    $r->sold_at?->format('H:i'),
                    $r->driver?->full_name ?? '',
                    $r->warehouse?->name ?? '',
                    $r->product?->name ?? '',
                    $r->payment_type->label(),
                    number_format((float) $r->quantity, 2, '.', ''),
                    number_format((float) $r->unit_price, 2, '.', ''),
                    number_format((float) $r->total_amount, 2, '.', ''),
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        abort_unless(Gate::allows('view-period-reports'), 403);

        $rows = $this->baseQuery()->latest('sold_at')->latest('id')->get();

        $totals = [
            'cash' => (float) $rows->filter(fn ($r) => $r->payment_type->value === 'cash')->sum('total_amount'),
            'credit' => (float) $rows->filter(fn ($r) => $r->payment_type->value === 'credit')->sum('total_amount'),
            'qty' => (float) $rows->sum('quantity'),
            'count' => $rows->count(),
        ];
        $totals['grand'] = $totals['cash'] + $totals['credit'];

        return view('livewire.reports.gas.gas-sales-report', [
            'rows' => $rows,
            'totals' => $totals,
            'drivers' => User::where('role', 'driver')->orderBy('full_name')->get(['id', 'full_name']),
            'products' => Product::stockTracked()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
