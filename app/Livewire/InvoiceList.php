<?php

namespace App\Livewire;

use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Livewire\Concerns\FiltersClientsForSelect;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoicePaymentAllocationService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceList extends Component
{
    use AppliesListFiltersOnAction;
    use FiltersClientsForSelect;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'inv_status')]
    public string $filterStatus = '';

    #[Url(as: 'inv_client')]
    public string $filterClientId = '';

    #[Url(as: 'inv_cur')]
    public string $filterCurrency = '';

    #[Url(as: 'inv_from')]
    public string $filterDateFrom = '';

    #[Url(as: 'inv_to')]
    public string $filterDateTo = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $confirmDeleteId = null;

    public ?int $viewingId = null;

    public string $client_id = '';

    public string $clientSearch = '';

    public string $legacy_invoice_no = '';

    public string $document_date = '';

    public string $due_date = '';

    public string $currency_code = 'ILS';

    public string $total_amount = '0';

    public string $discount_amount = '0';

    public string $status = 'draft';

    public string $notes = '';

    /** @var array<int, array{title:string, description:string, unit_price:string, quantity:string, line_total:string}> */
    public array $lines = [];

    public function clearInvoiceFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterClientId = '';
        $this->filterCurrency = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function hasActiveInvoiceFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->filterStatus !== ''
            || $this->filterClientId !== ''
            || $this->filterCurrency !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== '';
    }

    /* ── عند تغيير سعر أو كمية أي بند ── */
    public function updatedLines(mixed $value, ?string $key = null): void
    {
        if ($key === null || $key === '') {
            return;
        }

        $parts = explode('.', $key);
        if (count($parts) === 2 && in_array($parts[1], ['unit_price', 'quantity'])) {
            $i = (int) $parts[0];
            $price = (float) ($this->lines[$i]['unit_price'] ?? 0);
            $qty = (int) ($this->lines[$i]['quantity'] ?? 1);
            $this->lines[$i]['line_total'] = (string) round($price * $qty, 4);
            $this->recalcTotal();
        }
    }

    public function updatedDiscountAmount(): void
    {
        $this->recalcTotal();
    }

    private function recalcTotal(): void
    {
        $subtotal = collect($this->lines)->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
        $net = max(0, $subtotal - (float) ($this->discount_amount ?? 0));
        $this->total_amount = (string) round($net, 2);
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'title' => '',
            'description' => '',
            'unit_price' => '',
            'quantity' => '1',
            'line_total' => '0',
        ];
    }

    public function removeLine(int $index): void
    {
        array_splice($this->lines, $index, 1);
        $this->lines = array_values($this->lines);
        $this->recalcTotal();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->confirmDeleteId = null;
        $this->viewingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $inv = Invoice::with(['lines', 'client'])->findOrFail($id);
        $this->editingId = $id;
        $this->client_id = (string) $inv->client_id;
        $this->clientSearch = $inv->client?->displayName() ?? '';
        $this->legacy_invoice_no = $inv->legacy_invoice_no ?? '';
        $this->document_date = $inv->document_date?->format('Y-m-d') ?? '';
        $this->due_date = $inv->due_date?->format('Y-m-d') ?? '';
        $this->currency_code = $inv->currency_code ?? 'ILS';
        $this->total_amount = (string) $inv->total_amount;
        $this->discount_amount = (string) ($inv->discount_amount ?? 0);
        $this->status = $inv->status ?? 'draft';
        $this->notes = $inv->notes ?? '';
        $this->lines = $inv->lines->map(fn ($l) => [
            'title' => $l->title ?? '',
            'description' => $l->description ?? '',
            'unit_price' => (string) $l->unit_price,
            'quantity' => (string) $l->quantity,
            'line_total' => (string) $l->line_total,
        ])->toArray();
        $this->confirmDeleteId = null;
        $this->viewingId = null;
        $this->showModal = true;
    }

    public function openView(int $id): void
    {
        $this->showModal = false;
        $this->confirmDeleteId = null;
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    #[Computed]
    public function viewingRecord(): ?Invoice
    {
        return $this->viewingId ? Invoice::with(['client', 'lines'])->find($this->viewingId) : null;
    }

    public function save(): void
    {
        $rules = [
            'client_id' => 'required|exists:clients,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'currency_code' => 'required|string|size:3',
            'status' => 'required|in:draft,issued,cancelled',
            'lines' => 'array',
            'lines.*.title' => 'required_with:lines|string|max:500',
            'lines.*.quantity' => 'required_with:lines|integer|min:0',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
        ];

        $this->validate($rules, [
            'lines.*.title.required_with' => 'اسم البند مطلوب',
            'lines.*.quantity.required_with' => 'الكمية مطلوبة',
            'lines.*.unit_price.required_with' => 'السعر مطلوب',
        ], [
            'client_id' => 'العميل',
            'document_date' => 'تاريخ الفاتورة',
            'due_date' => 'تاريخ الاستحقاق',
            'currency_code' => 'العملة',
            'status' => 'الحالة',
        ]);

        // حساب الإجمالي من البنود تلقائياً إذا وُجدت
        if (! empty($this->lines)) {
            $subtotal = collect($this->lines)->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
            $this->total_amount = (string) max(0, round($subtotal - (float) ($this->discount_amount ?? 0), 2));
        }

        if (empty($this->total_amount) || (float) $this->total_amount < 0) {
            $this->addError('total_amount', 'المبلغ الإجمالي مطلوب');

            return;
        }

        $data = [
            'client_id' => $this->client_id,
            'legacy_invoice_no' => $this->legacy_invoice_no ?: null,
            'document_date' => $this->document_date,
            'due_date' => $this->due_date ?: null,
            'currency_code' => $this->currency_code,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount ?: 0,
            'status' => $this->status,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        if ($this->editingId) {
            $invoice = Invoice::findOrFail($this->editingId);
            $invoice->update($data);
        } else {
            $invoice = Invoice::create($data);
        }

        // حفظ البنود
        $invoice->lines()->delete();
        foreach ($this->lines as $i => $line) {
            if (trim($line['title'] ?? '') === '') {
                continue;
            }
            $invoice->lines()->create([
                'line_order' => $i + 1,
                'title' => $line['title'],
                'description' => $line['description'] ?: null,
                'unit_price' => (float) ($line['unit_price'] ?? 0),
                'quantity' => (int) ($line['quantity'] ?? 1),
                'line_total' => (float) ($line['line_total'] ?? 0),
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $this->editingId ? 'تم تحديث الفاتورة' : 'تم إضافة الفاتورة بنجاح');
    }

    public function confirmDelete(int $id): void
    {
        $this->showModal = false;
        $this->viewingId = null;
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmDeleteId) {
            Invoice::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف الفاتورة');
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'client_id', 'clientSearch', 'legacy_invoice_no', 'document_date', 'due_date', 'notes',
        ]);
        $this->currency_code = 'ILS';
        $this->status = 'draft';
        $this->discount_amount = '0';
        $this->total_amount = '0';
        $this->lines = [];
        $this->resetValidation();
    }

    public function render()
    {
        $invoiceCurrencies = Invoice::query()
            ->whereNotNull('currency_code')
            ->where('currency_code', '!=', '')
            ->distinct()
            ->orderBy('currency_code')
            ->pluck('currency_code')
            ->values()
            ->all();

        $filterCur = strtoupper(trim($this->filterCurrency));
        $currencyFilterActive = $filterCur !== '' && in_array($filterCur, $invoiceCurrencies, true);

        $query = Invoice::with('client')
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn ($q) => $q->where('legacy_invoice_no', 'like', $s)
                    ->orWhere('notes', 'like', $s)
                    ->orWhereHas('client', fn ($q) => $q->where('business_name', 'like', $s)
                        ->orWhere('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                    )
                );
            })
            ->when(in_array($this->filterStatus, ['draft', 'issued', 'void'], true), fn ($q) => $q->where('status', $this->filterStatus)
            )
            ->when(ctype_digit($this->filterClientId) && Client::whereKey((int) $this->filterClientId)->exists(), fn ($q) => $q->where('client_id', (int) $this->filterClientId)
            )
            ->when($currencyFilterActive, fn ($q) => $q->where('currency_code', $filterCur))
            ->when($this->filterDateFrom !== '', function ($q) {
                try {
                    $from = Carbon::parse($this->filterDateFrom)->startOfDay();
                    $q->whereDate('document_date', '>=', $from);
                } catch (\Throwable) {
                }
            })
            ->when($this->filterDateTo !== '', function ($q) {
                try {
                    $to = Carbon::parse($this->filterDateTo)->endOfDay();
                    $q->whereDate('document_date', '<=', $to);
                } catch (\Throwable) {
                }
            })
            ->latest('document_date');

        $rows = $this->paginateWithPerPage($query);

        $paymentStatuses = (new InvoicePaymentAllocationService)->forInvoices(
            collect($rows->items())
        );

        return view('livewire.invoice-list', [
            'rows' => $rows,
            'clients' => $this->clientsForSelect(),
            'invoiceCurrencies' => $invoiceCurrencies,
            'viewingRecord' => $this->viewingRecord,
            'paymentStatuses' => $paymentStatuses,
        ]);
    }
}
