<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialStatisticResource\Pages;
use App\Models\FinancialStatistic;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinancialStatisticResource extends Resource
{
    protected static ?string $model = FinancialStatistic::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('Ano')
                    ->sortable(),
                Tables\Columns\TextColumn::make('month')
                    ->label('Mês')
                    ->formatStateUsing(fn(int $state): string => [
                        1 => 'Janeiro',
                        2 => 'Fevereiro',
                        3 => 'Março',
                        4 => 'Abril',
                        5 => 'Maio',
                        6 => 'Junho',
                        7 => 'Julho',
                        8 => 'Agosto',
                        9 => 'Setembro',
                        10 => 'Outubro',
                        11 => 'Novembro',
                        12 => 'Dezembro',
                    ][$state] ?? (string) $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn(string $state): string => [
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                    ][$state] ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Valor Total')
                    ->formatStateUsing(fn($state) => 'R$ ' . number_format($state, 2, ',', '.'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_count')
                    ->label('Quantidade')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Ano')
                    ->options(function () {
                        return FinancialStatistic::distinct()
                            ->pluck('year', 'year')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('month')
                    ->label('Mês')
                    ->options([
                        1 => 'Janeiro',
                        2 => 'Fevereiro',
                        3 => 'Março',
                        4 => 'Abril',
                        5 => 'Maio',
                        6 => 'Junho',
                        7 => 'Julho',
                        8 => 'Agosto',
                        9 => 'Setembro',
                        10 => 'Outubro',
                        11 => 'Novembro',
                        12 => 'Dezembro',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(function () {
                        return FinancialStatistic::distinct()
                            ->pluck('category', 'category')
                            ->toArray();
                    }),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Tipo')
                    ->options([
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                    ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('year', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('user_id', Auth::id());
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialStatistics::route('/'),
        ];
    }
}
