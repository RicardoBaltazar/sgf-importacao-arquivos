<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::match(['get', 'post'], '/processar-transacoes', [App\Http\Controllers\TransactionController::class, 'process'])
    ->name('transacoes.processar');
