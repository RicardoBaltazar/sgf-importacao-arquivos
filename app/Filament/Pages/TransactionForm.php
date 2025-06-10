<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Support\Enums\ActionSize;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Route;

class TransactionForm extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Enviar Transações';
    protected static ?string $title = 'Envie suas transações';

    protected static string $view = 'filament.pages.transaction-form';

    public ?array $data = [];
    public bool $isShowingRequiredFields = false;
    public ?array $processedData = null;

    public function mount(): void
    {
        $this->form->fill();

        if (session()->has('processedData')) {
            $this->processedData = session('processedData');
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        FileUpload::make('arquivo')
                            ->label('Arquivo de Transações')
                            ->disk('public')
                            ->directory('uploads')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/xml', 'application/xml'])
                            ->helperText('Envie um arquivo CSV, Excel ou XML com suas transações')
                            ->required(),

                        Placeholder::make('campos_obrigatorios')
                            ->label('Campos Obrigatórios')
                            ->content('O arquivo deve conter os seguintes campos:')
                            ->extraAttributes(['class' => 'font-medium'])
                            ->visible($this->isShowingRequiredFields),

                        Placeholder::make('campo_data')
                            ->label('data_transacao')
                            ->content('Formato YYYY-MM-DD')
                            ->visible($this->isShowingRequiredFields),

                        Placeholder::make('campo_descricao')
                            ->label('descricao')
                            ->content('Texto descritivo da transação')
                            ->visible($this->isShowingRequiredFields),

                        Placeholder::make('campo_categoria')
                            ->label('categoria')
                            ->content('Classificação da transação (ex.: "Alimentação", "Transporte")')
                            ->visible($this->isShowingRequiredFields),

                        Placeholder::make('campo_valor')
                            ->label('valor')
                            ->content('Valor monetário no formato decimal (ex.: 1234.56)')
                            ->visible($this->isShowingRequiredFields),

                        Placeholder::make('campo_tipo')
                            ->label('tipo_transacao')
                            ->content('Especifica se a transação é uma receita ou despesa')
                            ->visible($this->isShowingRequiredFields),
                    ])
            ])
            ->statePath('data');
    }

    public function toggleRequiredFields(): void
    {
        $this->isShowingRequiredFields = !$this->isShowingRequiredFields;
    }

    public function enviar()
    {
        $data = $this->form->getState();

        if (empty($data['arquivo'])) {
            $this->processedData = [
                'error' => 'Nenhum arquivo foi enviado.'
            ];
            return;
        }

        $arquivoPath = $data['arquivo'];

        $controller = app(\App\Http\Controllers\TransactionController::class);
        $request = new \Illuminate\Http\Request(['arquivo' => $arquivoPath]);

        $response = $controller->process($request);

        $this->processedData = json_decode($response->getContent(), true);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label('Processar Arquivo')
                ->action(fn() => $this->enviar())
                ->submit('form')
                ->extraAttributes(['class' => 'mt-2'])
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mostrarCampos')
                ->label('Mostrar Campos Obrigatórios')
                ->action('toggleRequiredFields')
                ->size(ActionSize::Small)
                ->color('gray'),
        ];
    }

    public static function getRoutes(): \Closure
    {
        return function () {
            Route::get('enviar-transacoes', static::class)->name('enviar-transacoes');
        };
    }
}
