<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    /**
     * Armazena as transações financeiras no banco de dados
     */
    public function storeTransactions(array $transactions): array
    {
        $results = [];
        $userId = Auth::id();

        foreach ($transactions as $transaction) {
            $results[] = $this->createTransaction($transaction, $userId);
        }

        return $results;
    }

    /**
     * Cria uma nova transação no banco de dados
     */
    private function createTransaction(array $data, int $userId): Transaction
    {
        $transactionDate = $this->convertExcelDate($data['data_transacao']);

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
     * Converte data no formato Excel para Carbon
     */
    private function convertExcelDate($excelDate): Carbon
    {
        // O Excel conta dias a partir de 1/1/1900
        return Carbon::createFromTimestamp(($excelDate - 25569) * 86400);
    }
}
