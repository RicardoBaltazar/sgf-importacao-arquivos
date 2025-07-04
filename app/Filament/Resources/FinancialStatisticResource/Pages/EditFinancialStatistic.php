<?php

namespace App\Filament\Resources\FinancialStatisticResource\Pages;

use App\Filament\Resources\FinancialStatisticResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialStatistic extends EditRecord
{
    protected static string $resource = FinancialStatisticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
