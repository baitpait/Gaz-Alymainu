<?php

namespace App\Livewire\Reports;

use App\Models\Collection as CollectionModel;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تقرير أداء السائقين: ملخص لكل سائق ضمن الفترة (كميات، مبيعات، تحصيلات، صافي نقدي).
 */
class DriverPerformanceReport extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-period-reports'), 403);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /** @return array<int, array<string, mixed>> */
    private function buildRows(): array
    {
        $drivers = User::where('role', 'driver')->orderBy('full_name')->get(['id', 'full_name']);
        $rows = [];

        foreach ($drivers as $d) {
            $salesQuery = Sale::where('driver_user_id', $d->id)
                ->whereDate('sale_date', '>=', $this->dateFrom)
                ->whereDate('sale_date', '<=', $this->dateTo);

            $qty = (float) (clone $salesQuery)->sum('quantity');
            $count = (clone $salesQuery)->count();
            $cashSales = (float) (clone $salesQuery)->where('payment_type', 'cash')->sum('total_amount');
            $creditSales = (float) (clone $salesQuery)->where('payment_type', 'credit')->sum('total_amount');

            $collections = (float) CollectionModel::where('driver_user_id', $d->id)
                ->whereDate('collection_date', '>=', $this->dateFrom)
                ->whereDate('collection_date', '<=', $this->dateTo)
                ->sum('amount');

            $rows[] = [
                'driver' => $d->full_name,
                'count' => $count,
                'qty' => $qty,
                'cash_sales' => $cashSales,
                'credit_sales' => $creditSales,
                'total_sales' => $cashSales + $creditSales,
                'collections' => $collections,
            ];
        }

        return $rows;
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');
        $rows = $this->buildRows();
        $filename = 'اداء-السائقين-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['السائق', 'عدد المبيعات', 'الكمية', 'نقدي', 'على الحساب', 'إجمالي المبيعات', 'التحصيلات']);
            foreach ($rows as $r) {
                fputcsv($h, [
                    $r['driver'],
                    $r['count'],
                    number_format($r['qty'], 2, '.', ''),
                    number_format($r['cash_sales'], 2, '.', ''),
                    number_format($r['credit_sales'], 2, '.', ''),
                    number_format($r['total_sales'], 2, '.', ''),
                    number_format($r['collections'], 2, '.', ''),
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        abort_unless(Gate::allows('view-period-reports'), 403);

        $rows = $this->buildRows();

        $totals = [
            'count' => array_sum(array_column($rows, 'count')),
            'qty' => array_sum(array_column($rows, 'qty')),
            'cash_sales' => array_sum(array_column($rows, 'cash_sales')),
            'credit_sales' => array_sum(array_column($rows, 'credit_sales')),
            'total_sales' => array_sum(array_column($rows, 'total_sales')),
            'collections' => array_sum(array_column($rows, 'collections')),
        ];

        return view('livewire.reports.gas.driver-performance-report', [
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }
}
