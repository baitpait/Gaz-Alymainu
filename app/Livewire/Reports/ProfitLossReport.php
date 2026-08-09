<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Services\Reports\ProfitLossReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossReport extends Component
{
    use HasPeriodReportFilters;

    public string $mode = ProfitLossReportService::MODE_ACCRUAL;

    /** @var array<string, array<string, mixed>> */
    public array $rows = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    /** قيمة أصول المخزون الحالية (خارج الربح) */
    public float $inventoryAssetTotal = 0;

    public function mount(string $mode = ProfitLossReportService::MODE_ACCRUAL): void
    {
        abort_unless(in_array($mode, [ProfitLossReportService::MODE_ACCRUAL, ProfitLossReportService::MODE_CASH], true), 404);

        $this->mode = $mode;
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
        $this->rows = $svc->byCurrency($this->buildPeriodFilters(), $this->mode);
        $this->inventoryAssetTotal = (float) ($svc->inventoryAssetSummary(
            $this->currency !== '' ? $this->currency : null
        )['total'] ?? 0);
    }

    public function pdfExportUrl(): string
    {
        $route = $this->mode === ProfitLossReportService::MODE_CASH
            ? 'reports.profit-loss-cash.pdf'
            : 'reports.profit-loss.pdf';

        return route($route, $this->periodQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildPeriodFilters();
        $rows = (new ProfitLossReportService)->byCurrency($filters, $this->mode);
        $isCash = $this->mode === ProfitLossReportService::MODE_CASH;
        $salesLabel = $isCash ? 'إيرادات نقدية' : 'مبيعات';
        $purchaseLabel = 'تكلفة المباع (COGS)';

        $filename = ($isCash ? 'ربح-خسارة-بدون-دين' : 'ربح-خسارة').'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $filters, $salesLabel, $purchaseLabel): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['العملة', $salesLabel, $purchaseLabel, 'مصروفات', 'رواتب', 'صافي الربح/الخسارة', 'أصل المخزون']);
            foreach ($rows as $cur => $row) {
                fputcsv($handle, [
                    $cur,
                    number_format($row['sales'], 2, '.', ''),
                    number_format($row['cogs'] ?? $row['purchases'], 2, '.', ''),
                    number_format($row['expenses'], 2, '.', ''),
                    number_format($row['salaries'], 2, '.', ''),
                    number_format($row['net_profit'], 2, '.', ''),
                    number_format($row['inventory_asset'] ?? 0, 2, '.', ''),
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['الفترة', $filters->resolvedDateFrom()->format('Y-m-d'), $filters->resolvedDateTo()->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.profit-loss-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
            'modeLabel' => ProfitLossReportService::modeLabel($this->mode),
            'isCash' => $this->mode === ProfitLossReportService::MODE_CASH,
        ]);
    }
}
