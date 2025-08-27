<?php

namespace App\Jobs;

use App\Mail\TransactionsProcessedMail;
use App\Models\User;
use App\Services\FileProcessorService;
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

    public $timeout = 7200;
    public $tries = 1;

    protected $filePath;
    protected $userId;
    protected $chunkSize = 1000;

    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    public function handle(FileProcessorService $fileProcessor, TransactionService $transactionService): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        Log::info("Iniciando processamento do arquivo para usuário {$this->userId}: {$this->filePath}");

        try {
            $lazyCollection = $fileProcessor->processFileFromPath($this->filePath);
            
            $chunk = [];
            $totalProcessed = 0;

            foreach ($lazyCollection as $transaction) {
                $chunk[] = $transaction;
                $totalProcessed++;

                if (count($chunk) >= $this->chunkSize) {
                    $transactionService->storeTransactionsChunk($chunk, $this->userId);
                    $chunk = [];
                    
                    if ($totalProcessed % 10000 === 0) {
                        Log::info("Processadas {$totalProcessed} transações");
                        gc_collect_cycles();
                    }
                }
            }

            if (!empty($chunk)) {
                $transactionService->storeTransactionsChunk($chunk, $this->userId);
            }

            Log::info("Processamento concluído: {$totalProcessed} transações");

            ProcessFinancialStatisticJob::dispatch($this->userId);

            $user = User::find($this->userId);
            if ($user) {
                Mail::to($user->email)->send(new TransactionsProcessedMail($totalProcessed));
                Log::info("E-mail enviado para {$user->email}");
            }

        } catch (\Exception $e) {
            Log::error("Erro no processamento: " . $e->getMessage());
            throw $e;
        }
    }
}
