<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email? : O endereço de email para enviar o teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia um email de teste para verificar a configuração de email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: 'test@example.com';
        
        $this->info("Enviando email de teste para: {$email}");
        
        Mail::raw('Este é um email de teste do sistema SGF Importação de Arquivos.', function (Message $message) use ($email) {
            $message->to($email)
                ->subject('Email de Teste - SGF Importação de Arquivos');
        });
        
        $this->info('Email enviado com sucesso! Verifique o arquivo de log em storage/logs/laravel.log');
    }
}
