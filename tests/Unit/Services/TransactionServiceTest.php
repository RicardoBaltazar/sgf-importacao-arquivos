<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Log::shouldReceive('error')->andReturn(null);
    }

    public function testStoreTransactionsChunkSuccess(): void
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
            ]
        ];

        $this->transactionService->storeTransactionsChunk($transactions, $userId);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userId,
            'description' => 'Teste de transação',
            'amount' => 100.00,
            'transaction_type' => 'expense'
        ]);
    }

    public function testStoreTransactionsChunkWithInvalidData(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $transactions = [
            [
                'data_transacao' => 'data_invalida',
                'descricao' => null,
                'categoria' => '',
                'valor' => 50.00,
                'tipo_transacao' => 'receita'
            ]
        ];

        $this->transactionService->storeTransactionsChunk($transactions, $userId);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userId,
            'description' => '',
            'amount' => 50.00,
            'transaction_type' => 'income'
        ]);
    }
}
