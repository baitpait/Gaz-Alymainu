<?php

namespace App\Http\Controllers;

use App\Services\ArabicPdfRenderer;
use App\Services\ClientReceivablesAgingFilters;
use App\Services\ClientReceivablesAgingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientReceivablesAgingController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(): View
    {
        $this->authorize('view-client-receivables-aging');

        return view('reports.client-receivables-aging');
    }

    public function pdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-client-receivables-aging-csv');

        $filters = ClientReceivablesAgingFilters::fromRequest($request);
        $svc = new ClientReceivablesAgingService;
        $rows = $svc->rows($filters);
        $summary = $svc->summary($filters);
        $filterLabels = $filters->describeActive();

        $companyName = config('app.company_display_name', 'Gaz Al-Yamani');

        $html = view('pdf.client-receivables-aging', [
            'rows' => $rows,
            'summary' => $summary,
            'filterLabels' => $filterLabels,
            'companyName' => $companyName,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $filename = 'client-receivables-aging-'.now()->format('Ymd-His').'.pdf';

        return $pdfRenderer->stream(
            $html,
            $filename,
            'inline',
            'أعمار ذمم العملاء',
            $companyName,
        );
    }
}
