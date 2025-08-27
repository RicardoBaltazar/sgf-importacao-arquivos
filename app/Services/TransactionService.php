<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function storeTransactionsChunk(array $transactions, int $userId): void
    {
        Log::info("Armazenando chunk de " . count($transactions) . " transações");

        $data = [];
        foreach ($transactions as $transaction) {
            $data[] = [
                'user_id' => $userId,
                'transaction_date' => $this->parseDate($transaction['data_transacao']),
                'description' => $transaction['descricao'],
                'category' => $transaction['categoria'],
                'amount' => $transaction['valor'],
                'transaction_type' => $transaction['tipo_transacao'] === 'despesa' ? 'expense' : 'income',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('transactions')->insert($data);
        Log::info("Chunk armazenado com sucesso");
    }

    private function parseDate($dateValue): Carbon
    {
        if (is_string($dateValue) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateValue)) {
            return Carbon::createFromFormat('d/m/Y', $dateValue);
        }

        if (is_numeric($dateValue)) {
            return Carbon::createFromTimestamp(($dateValue - 25569) * 86400);
        }

        try {
            return Carbon::parse($dateValue);
        } catch (\Exception $e) {
            Log::error("Erro ao converter data: {$dateValue}");
            return Carbon::now();
        }
    }
}
