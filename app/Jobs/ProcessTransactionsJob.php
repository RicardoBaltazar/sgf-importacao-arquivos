<?php

namespace App\Jobs;

use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactions;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $transactions, int $userId)
    {
        $this->transactions = $transactions;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(TransactionService $transactionService): void
    {
        Log::info('Iniciando processamento de ' . count($this->transactions) . ' transações para o usuário ' . $this->userId);

        try {
            $results = $transactionService->storeTransactions($this->transactions, $this->userId);
            Log::info('Processamento concluído com sucesso. ' . count($results) . ' transações armazenadas.');

            // Aqui seria o local para adicionar o envio de e-mail
            // Mail::to($user)->send(new TransactionsProcessedMail($results));

        } catch (\Exception $e) {
            Log::error('Erro ao processar transações: ' . $e->getMessage());
            throw $e;
        }
    }
}
