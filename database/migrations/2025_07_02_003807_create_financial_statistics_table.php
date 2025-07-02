<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->string('category');
            $table->enum('transaction_type', ['income', 'expense']);
            $table->decimal('total_amount', 15, 2);
            $table->integer('transaction_count');
            $table->timestamps();

            $table->index(['user_id', 'year', 'month', 'category', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_statistics');
    }
};
