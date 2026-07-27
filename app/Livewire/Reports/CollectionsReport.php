<?php

namespace App\Livewire\Reports;

use App\Models\Collection as CollectionModel;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تقرير التحصيلات: نقدي/شيك حسب الفترة والسائق (بلا ذمم/زبون).
 */
class CollectionsReport extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $driverId = '';

    public string $method = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-period-reports'), 403);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    private function baseQuery()
    {
        return CollectionModel::query()
            ->with(['driver', 'warehouse'])
            ->whereDate('collection_date', '>=', $this->dateFrom)
            ->whereDate('collection_date', '<=', $this->dateTo)
            ->when($this->driverId, fn ($q) => $q->where('driver_user_id', $this->driverId))
            ->when($this->method, fn ($q) => $q->where('method', $this->method));
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');
        $rows = $this->baseQuery()->latest('collected_at')->get();
        $filename = 'التحصيلات-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($h, ['التاريخ', 'الوقت', 'السائق', 'الطريقة', 'المبلغ', 'رقم الشيك', 'ملاحظات']);
            foreach ($rows as $r) {
                fputcsv($h, [
                    $r->collection_date?->format('Y-m-d'),
                    $r->collected_at?->format('H:i'),
                    $r->driver?->full_name ?? '',
                    $r->method->label(),
                    number_format((float) $r->amount, 2, '.', ''),
                    $r->cheque_number ?? '',
                    $r->notes ?? '',
                ]);
            }
            fclose($h);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        abort_unless(Gate::allows('view-period-reports'), 403);

        $rows = $this->baseQuery()->latest('collected_at')->latest('id')->get();

        $totals = [
            'cash' => (float) $rows->filter(fn ($r) => $r->method->value === 'cash')->sum('amount'),
            'cheque' => (float) $rows->filter(fn ($r) => $r->method->value === 'cheque')->sum('amount'),
            'count' => $rows->count(),
        ];
        $totals['grand'] = $totals['cash'] + $totals['cheque'];

        return view('livewire.reports.gas.collections-report', [
            'rows' => $rows,
            'totals' => $totals,
            'drivers' => User::where('role', 'driver')->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }
}
