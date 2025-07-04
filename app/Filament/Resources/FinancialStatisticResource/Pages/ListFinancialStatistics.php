<?php

namespace App\Filament\Resources\FinancialStatisticResource\Pages;

use App\Filament\Resources\FinancialStatisticResource;
use Filament\Resources\Pages\ListRecords;

class ListFinancialStatistics extends ListRecords
{
    protected static string $resource = FinancialStatisticResource::class;

    protected static ?string $title = 'Relatórios Financeiros';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
