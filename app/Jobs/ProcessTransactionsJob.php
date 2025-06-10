<?php

namespace App\Jobs;

use App\Mail\TransactionsProcessedMail;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            $count = count($results);
            Log::info('Processamento concluído com sucesso. ' . $count . ' transações armazenadas.');

            $user = User::find($this->userId);
            if ($user) {
                Mail::to($user->email)->send(new TransactionsProcessedMail($count));
                Log::info('E-mail de notificação enviado para ' . $user->email);
            } else {
                Log::warning('Usuário não encontrado para envio de e-mail: ' . $this->userId);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar transações: ' . $e->getMessage());
            throw $e;
        }
    }
}
