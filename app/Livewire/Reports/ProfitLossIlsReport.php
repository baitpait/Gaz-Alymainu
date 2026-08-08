<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Services\Reports\ProfitLossReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossIlsReport extends Component
{
    use HasPeriodReportFilters;

    public string $basisMode = ProfitLossReportService::MODE_ACCRUAL;

    /** @var array<string, mixed> */
    public array $totals = [];

    /** @var array<string, array<string, mixed>> */
    public array $sourceRows = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(string $basis = ProfitLossReportService::MODE_ACCRUAL): void
    {
        abort_unless(in_array($basis, [ProfitLossReportService::MODE_ACCRUAL, ProfitLossReportService::MODE_CASH], true), 404);

        $this->basisMode = $basis;
        $this->mountPeriodReportFilters();
        if ($this->currency === '') {
            $this->currency = ProfitLossReportService::DEFAULT_CURRENCY;
        }
        $this->currencyOptions = (new ProfitLossReportService)->currencyOptions();
        $this->loadReport();
    }

    public function applyPeriodFilters(): void
    {
        $this->loadReport();
    }

    public function clearPeriodFilters(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->currency = ProfitLossReportService::DEFAULT_CURRENCY;
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $svc = new ProfitLossReportService;
        $filters = $this->buildPeriodFilters();
        $this->sourceRows = $svc->byCurrency($filters, $this->basisMode);

        try {
            $this->totals = $svc->consolidatedIls($filters, $this->basisMode);
        } catch (\Throwable) {
            $this->totals = [
                'sales' => 0.0,
                'purchases' => 0.0,
                'expenses' => 0.0,
                'salaries' => 0.0,
                'net_profit' => 0.0,
                'rates' => [],
                'rate_date' => $filters->resolvedDateTo()->format('Y-m-d'),
                'error' => 'تعذّر جلب أسعار الصرف — شغّل: php artisan boi:fetch-rates',
            ];
        }
    }

    public function pdfExportUrl(): string
    {
        return route('reports.profit-loss-ils.pdf', array_merge(
            $this->periodQueryParams(),
            ['basis' => $this->basisMode]
        ));
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $svc = new ProfitLossReportService;
        $filters = $this->buildPeriodFilters();
        $totals = $svc->consolidatedIls($filters, $this->basisMode);

        $filename = 'ربح-خسارة-شيكل-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($totals, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['البند', 'المبلغ ILS']);
            fputcsv($handle, ['إيرادات/مبيعات', number_format($totals['sales'], 2, '.', '')]);
            fputcsv($handle, ['مشتريات', number_format($totals['purchases'], 2, '.', '')]);
            fputcsv($handle, ['مصروفات', number_format($totals['expenses'], 2, '.', '')]);
            fputcsv($handle, ['رواتب', number_format($totals['salaries'], 2, '.', '')]);
            fputcsv($handle, ['صافي الربح/الخسارة', number_format($totals['net_profit'], 2, '.', '')]);
            fputcsv($handle, []);
            fputcsv($handle, ['تاريخ السعر', $totals['rate_date']]);
            foreach ($totals['rates'] as $cur => $rate) {
                fputcsv($handle, ["سعر {$cur}", number_format($rate, 6, '.', '')]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['الفترة', $filters->resolvedDateFrom()->format('Y-m-d'), $filters->resolvedDateTo()->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.profit-loss-ils-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
            'basisLabel' => ProfitLossReportService::modeLabel($this->basisMode),
        ]);
    }
}
