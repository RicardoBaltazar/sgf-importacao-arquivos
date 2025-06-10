<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Armazena as transações financeiras no banco de dados
     */
    public function storeTransactions(array $transactions, ?int $userId = null): array
    {
        $results = [];
        $userId = $userId ?: Auth::id();

        Log::info("Iniciando armazenamento de " . count($transactions) . " transações");

        foreach ($transactions as $index => $transaction) {
            Log::debug("Processando transação {$index}");
            $results[] = $this->createTransaction($transaction, $userId);
        }

        Log::info("Armazenamento concluído com sucesso");
        return $results;
    }

    /**
     * Cria uma nova transação no banco de dados
     */
    private function createTransaction(array $data, int $userId): Transaction
    {
        $transactionDate = $this->parseDate($data['data_transacao']);
        $transactionType = $data['tipo_transacao'] === 'despesa' ? 'expense' : 'income';

        return Transaction::create([
            'user_id' => $userId,
            'transaction_date' => $transactionDate,
            'description' => $data['descricao'],
            'category' => $data['categoria'],
            'amount' => $data['valor'],
            'transaction_type' => $transactionType,
        ]);
    }

    /**
     * Converte a data para o formato correto
     */
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
