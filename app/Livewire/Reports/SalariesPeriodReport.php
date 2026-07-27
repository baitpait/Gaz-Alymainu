<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Services\Hr\SalaryPeriodReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalariesPeriodReport extends Component
{
    use HasPeriodReportFilters;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, array<string, float|int>> */
    public array $totals = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(): void
    {
        $this->mountPeriodReportFilters();
        $this->currencyOptions = (new SalaryPeriodReportService)->currencyOptions();
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
        $this->currency = '';
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $svc = new SalaryPeriodReportService;
        $filters = $this->buildPeriodFilters();
        $this->rows = $svc->rows($filters)->all();
        $this->totals = $svc->totalsByCurrency($filters);
    }

    public function pdfExportUrl(): string
    {
        return route('reports.salaries.pdf', $this->periodQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildPeriodFilters();
        $rows = (new SalaryPeriodReportService)->rows($filters);

        $filename = 'رواتب-الفترة-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['الموظف', 'القسم', 'الشهر', 'أيام العمل', 'أجرة اليوم', 'أساسي', 'مكافأة', 'خصم', 'صافي', 'عملة', 'تاريخ الدفع', 'الحالة']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['employee_name'],
                    $r['department'] ?? '',
                    $r['period_label'],
                    $r['worked_days'] ?? '',
                    $r['daily_rate'] !== null ? number_format($r['daily_rate'], 2, '.', '') : '',
                    number_format($r['base_amount'], 2, '.', ''),
                    number_format($r['bonus_amount'], 2, '.', ''),
                    number_format($r['deduction_amount'], 2, '.', ''),
                    number_format($r['net_amount'], 2, '.', ''),
                    $r['currency'],
                    $r['paid_at']?->format('Y-m-d') ?? '',
                    $r['status_label'],
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['الفترة', $filters->resolvedDateFrom()->format('Y-m-d'), $filters->resolvedDateTo()->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.salaries-period-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
