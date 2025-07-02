<?php

namespace App\Jobs;

use App\Models\FinancialStatistic;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFinancialStatisticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;
    protected $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function handle(): void
    {
        Log::info("Iniciando processamento de estatísticas financeiras para usuário: {$this->userId}");
        $this->processUserStatistics($this->userId);
        Log::info("Processamento de estatísticas concluído para usuário: {$this->userId}");
    }

    private function processUserStatistics(int $userId): void
    {
        $query = Transaction::where('user_id', $userId)
            ->orderBy('transaction_date');

        Log::info('Processando estatísticas para o usuário: ' . $userId);

        $stats = [];

        foreach ($query->cursor() as $transaction) {
            $transactionDate = $transaction->transaction_date;
            if (!($transactionDate instanceof Carbon)) {
                $transactionDate = Carbon::parse($transactionDate);
            }

            $year = (int) $transactionDate->format('Y');
            $month = (int) $transactionDate->format('m');
            $category = $transaction->category;
            $type = $transaction->transaction_type;

            $key = "{$year}_{$month}_{$category}_{$type}";

            if (!isset($stats[$key])) {
                $stats[$key] = [
                    'user_id' => $userId,
                    'year' => $year,
                    'month' => $month,
                    'category' => $category,
                    'transaction_type' => $type,
                    'total_amount' => 0,
                    'transaction_count' => 0,
                ];
            }

            $stats[$key]['total_amount'] += $transaction->amount;
            $stats[$key]['transaction_count']++;

            if (count($stats) >= 1000) {
                $this->upsertStatsBatch($stats);
                $stats = [];
            }
        }

        if (!empty($stats)) {
            $this->upsertStatsBatch($stats);
        }
    }

    private function upsertStatsBatch(array $stats): void
    {
        foreach ($stats as $stat) {
            FinancialStatistic::updateOrCreate(
                [
                    'user_id' => $stat['user_id'],
                    'year' => $stat['year'],
                    'month' => $stat['month'],
                    'category' => $stat['category'],
                    'transaction_type' => $stat['transaction_type'],
                ],
                [
                    'total_amount' => $stat['total_amount'],
                    'transaction_count' => $stat['transaction_count'],
                ]
            );
        }
        Log::info('Estatísticas atualizadas com sucesso');
    }
}
