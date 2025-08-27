<?php

namespace App\Jobs;

use App\Models\FinancialStatistic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFinancialStatisticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    protected $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $cacheKey = "financial_stats_processing_{$this->userId}";

        if (Cache::has($cacheKey)) {
            Log::info("Job de estatísticas já agendado para usuário {$this->userId}, cancelando duplicata");
            return;
        }

        Cache::put($cacheKey, true, 300);

        try {
            Log::info("Iniciando processamento de estatísticas financeiras para usuário: {$this->userId}");
            $this->processUserStatistics($this->userId);
            Log::info("Processamento de estatísticas concluído para usuário: {$this->userId}");
        } finally {
            Cache::forget($cacheKey);
        }
    }

    private function processUserStatistics(int $userId): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        
        Log::info('Processando estatísticas para o usuário: ' . $userId);

        FinancialStatistic::where('user_id', $userId)->delete();

        $query = DB::table('transactions')
            ->select([
                'user_id',
                DB::raw('EXTRACT(YEAR FROM transaction_date) as year'),
                DB::raw('EXTRACT(MONTH FROM transaction_date) as month'),
                'category',
                'transaction_type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as transaction_count')
            ])
            ->where('user_id', $userId)
            ->groupBy([
                'user_id',
                DB::raw('EXTRACT(YEAR FROM transaction_date)'),
                DB::raw('EXTRACT(MONTH FROM transaction_date)'),
                'category',
                'transaction_type'
            ]);

        $batchData = [];
        $processedCount = 0;

        foreach ($query->cursor() as $stat) {
            $batchData[] = [
                'user_id' => $stat->user_id,
                'year' => (int) $stat->year,
                'month' => (int) $stat->month,
                'category' => $stat->category,
                'transaction_type' => $stat->transaction_type,
                'total_amount' => $stat->total_amount,
                'transaction_count' => $stat->transaction_count,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $processedCount++;

            if (count($batchData) >= 1000) {
                FinancialStatistic::insert($batchData);
                $batchData = [];
                
                Log::info("Processadas {$processedCount} estatísticas");
                gc_collect_cycles();
            }
        }

        if (!empty($batchData)) {
            FinancialStatistic::insert($batchData);
        }

        Log::info("Estatísticas atualizadas com sucesso: {$processedCount} registros");
    }
}
