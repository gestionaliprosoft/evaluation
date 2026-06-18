<?php

namespace App\Filament\Tables\Actions\PurchaseOrder;

use App\Models\Purchase\PurchaseStockEntry;
use Filament\Tables\Actions\Action;

class ExportPdfEntryAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportPdfEntryAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Generate PDF'))
            ->color('success')
            ->icon('heroicon-s-document-arrow-down')
            ->url(fn (PurchaseStockEntry $record) => route('purchase-stock-entries-pdf', $record))
            ->openUrlInNewTab();
    }
}
