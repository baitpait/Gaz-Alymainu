<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\ArabicPdfRenderer;
use App\Services\SupplierReceivablesAgingFilters;
use App\Services\SupplierReceivablesAgingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierReceivablesAgingController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.supplier-receivables-aging');
    }

    public function pdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = SupplierReceivablesAgingFilters::fromRequest($request);
        $svc = new SupplierReceivablesAgingService;
        $rows = $svc->rows($filters);
        $summary = $svc->summary($filters);
        $filterLabels = $filters->describeActive();

        $companyName = config('app.company_display_name', 'Gaz Alymainu');

        $html = view('pdf.supplier-receivables-aging', [
            'rows' => $rows,
            'summary' => $summary,
            'filterLabels' => $filterLabels,
            'companyName' => $companyName,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $filename = 'supplier-receivables-aging-'.now()->format('Ymd-His').'.pdf';

        return $pdfRenderer->stream(
            $html,
            $filename,
            'inline',
            'أعمار ذمم الموردين',
            $companyName,
        );
    }
}
