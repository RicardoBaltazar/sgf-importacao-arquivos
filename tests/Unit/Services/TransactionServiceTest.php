<?php

namespace Tests\Unit\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = new TransactionService();

        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('debug')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
    }

    public function testStoreTransactionsSuccess(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $transactions = [
            [
                'data_transacao' => '2023-01-01',
                'descricao' => 'Teste de transação',
                'categoria' => 'Teste',
                'valor' => 100.00,
                'tipo_transacao' => 'despesa'
            ],
            [
                'data_transacao' => '2023-01-02',
                'descricao' => 'Outra transação',
                'categoria' => 'Teste',
                'valor' => 200.00,
                'tipo_transacao' => 'receita'
            ]
        ];

        $results = $this->transactionService->storeTransactions($transactions, $userId);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(Transaction::class, $results[0]);
        $this->assertInstanceOf(Transaction::class, $results[1]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userId,
            'description' => 'Teste de transação',
            'amount' => 100.00,
            'transaction_type' => 'expense'
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userId,
            'description' => 'Outra transação',
            'amount' => 200.00,
            'transaction_type' => 'income'
        ]);
    }

    public function testStoreTransactionsWithoutUserId(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        Auth::shouldReceive('id')->once()->andReturn($userId);

        $transactions = [
            [
                'data_transacao' => '2023-01-01',
                'descricao' => 'Transação sem userId',
                'categoria' => 'Teste',
                'valor' => 150.00,
                'tipo_transacao' => 'despesa'
            ]
        ];

        $results = $this->transactionService->storeTransactions($transactions);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(Transaction::class, $results[0]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userId,
            'description' => 'Transação sem userId',
            'amount' => 150.00
        ]);
    }
}
