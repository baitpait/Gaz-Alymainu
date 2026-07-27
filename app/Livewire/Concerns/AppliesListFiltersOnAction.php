<?php

namespace App\Livewire\Concerns;

/**
 * Business Purpose: Apply list/report filters on button click instead of live wire:model updates.
 */
trait AppliesListFiltersOnAction
{
    public function applyListFilters(): void
    {
        $this->resetPage();
    }

    public function applyInvoiceFilters(): void
    {
        $this->resetPage();
    }

    public function applyPurchaseOrderFilters(): void
    {
        $this->resetPage();
    }

    public function applySaleFilters(): void
    {
        $this->resetPage();
    }

    public function applyReportFilters(): void
    {
        if (method_exists($this, 'loadRows')) {
            $this->loadRows();
        }
    }
}
