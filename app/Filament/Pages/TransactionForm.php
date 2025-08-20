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
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class TransactionForm extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

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

    public function processarArquivo()
    {
        $data = $this->form->getState();

        if (empty($data['arquivo'])) {
            Notification::make()
                ->title('Erro')
                ->body('Nenhum arquivo foi enviado.')
                ->danger()
                ->send();
            return;
        }

        $arquivoPath = $data['arquivo'];

        $controller = app(\App\Http\Controllers\TransactionController::class);
        $request = new \Illuminate\Http\Request(['arquivo' => $arquivoPath]);

        $response = $controller->process($request);
        $result = json_decode($response->getContent(), true);

        if (isset($result['error'])) {
            Notification::make()
                ->title('Erro na validação')
                ->body($result['error'])
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('Arquivo enviado com sucesso!')
                ->body("Suas {$result['count']} transações estão sendo processadas em segundo plano.")
                ->success()
                ->send();
        }

        $this->processedData = $result;
    }

    public function enviarAction(): Action
    {
        return Action::make('enviar')
            ->label('Enviar Arquivo')
            ->requiresConfirmation()
            ->modalHeading('Confirmar envio do arquivo')
            ->modalDescription('Tem certeza que deseja processar este arquivo? As transações serão importadas para sua conta.')
            ->modalSubmitActionLabel('Sim')
            ->modalCancelActionLabel('Cancelar')
            ->action(function () {
                $this->form->validate();
                $this->processarArquivo();
            })
            ->color('primary');
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
