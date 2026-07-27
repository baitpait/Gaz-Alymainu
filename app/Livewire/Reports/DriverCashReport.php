<?php

namespace App\Livewire\Reports;

use App\Models\CashHandover;
use App\Models\Collection as CollectionModel;
use App\Models\DriverExpense;
use App\Models\Sale;
use App\Models\User;
use App\Services\CashBoxService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * تقرير صندوق السائق وتسوية الكاش:
 * لكل سائق ضمن الفترة: مبيعات نقدية + تحصيل نقدي − مسحوب = صافي الفترة.
 * مع عرض الرصيد الحالي الكلي (غير المُسلَّم) والشيكات.
 */
class DriverCashReport extends Component
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
    private function buildRows(CashBoxService $cashBox): array
    {
        $drivers = User::where('role', 'driver')->orderBy('full_name')->get(['id', 'full_name']);
        $rows = [];

        foreach ($drivers as $d) {
            $cashSales = (float) Sale::where('driver_user_id', $d->id)
                ->where('payment_type', 'cash')
                ->whereDate('sale_date', '>=', $this->dateFrom)
                ->whereDate('sale_date', '<=', $this->dateTo)
                ->sum('total_amount');

            $creditSales = (float) Sale::where('driver_user_id', $d->id)
                ->where('payment_type', 'credit')
                ->whereDate('sale_date', '>=', $this->dateFrom)
                ->whereDate('sale_date', '<=', $this->dateTo)
                ->sum('total_amount');

            $cashCollections = (float) CollectionModel::where('driver_user_id', $d->id)
                ->where('method', 'cash')
                ->whereDate('collection_date', '>=', $this->dateFrom)
                ->whereDate('collection_date', '<=', $this->dateTo)
                ->sum('amount');

            $chequeCollections = (float) CollectionModel::where('driver_user_id', $d->id)
                ->where('method', 'cheque')
                ->whereDate('collection_date', '>=', $this->dateFrom)
                ->whereDate('collection_date', '<=', $this->dateTo)
                ->sum('amount');

            $withdrawn = (float) CashHandover::where('driver_user_id', $d->id)
                ->where('method', 'cash')
                ->whereDate('handover_date', '>=', $this->dateFrom)
                ->whereDate('handover_date', '<=', $this->dateTo)
                ->sum('amount');

            $expenses = (float) DriverExpense::where('driver_user_id', $d->id)
                ->whereDate('expense_date', '>=', $this->dateFrom)
                ->whereDate('expense_date', '<=', $this->dateTo)
                ->sum('amount');

            $rows[] = [
                'driver' => $d->full_name,
                'cash_sales' => $cashSales,
                'credit_sales' => $creditSales,
                'cash_collections' => $cashCollections,
                'cheque_collections' => $chequeCollections,
                'expenses' => $expenses,
                'withdrawn' => $withdrawn,
                'net' => round($cashSales + $cashCollections - $expenses - $withdrawn, 2),
                'balance' => $cashBox->balance($d->id),
            ];
        }

        return $rows;
    }

    public function render(CashBoxService $cashBox)
    {
        abort_unless(Gate::allows('view-period-reports'), 403);

        $rows = $this->buildRows($cashBox);

        $totals = [
            'cash_sales' => array_sum(array_column($rows, 'cash_sales')),
            'credit_sales' => array_sum(array_column($rows, 'credit_sales')),
            'cash_collections' => array_sum(array_column($rows, 'cash_collections')),
            'cheque_collections' => array_sum(array_column($rows, 'cheque_collections')),
            'expenses' => array_sum(array_column($rows, 'expenses')),
            'withdrawn' => array_sum(array_column($rows, 'withdrawn')),
            'net' => array_sum(array_column($rows, 'net')),
            'balance' => array_sum(array_column($rows, 'balance')),
        ];

        return view('livewire.reports.gas.driver-cash-report', [
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }
}
