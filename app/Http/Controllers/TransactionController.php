<?php

namespace App\Http\Controllers;

use App\Services\FileProcessorService;
use App\Services\FileValidationService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    protected $fileProcessor;
    protected $fileValidator;
    protected $transactionService;

    public function __construct(
        FileProcessorService $fileProcessor,
        FileValidationService $fileValidator,
        TransactionService $transactionService
    ) {
        $this->fileProcessor = $fileProcessor;
        $this->fileValidator = $fileValidator;
        $this->transactionService = $transactionService;
    }

    public function process(Request $request)
    {
        $arquivoPath = $request->input('arquivo');

        if (!$arquivoPath) {
            return response()->json([
                'error' => 'Nenhum arquivo foi enviado'
            ]);
        }

        if (!Storage::disk('public')->exists($arquivoPath)) {
            return response()->json([
                'error' => 'Arquivo não encontrado: ' . $arquivoPath
            ]);
        }

        try {
            $validationResult = $this->fileValidator->validate($arquivoPath);

            if (!$validationResult['success']) {
                return response()->json([
                    'error' => $validationResult['message']
                ]);
            }

            $fullPath = Storage::disk('public')->path($arquivoPath);
            $dados = $this->fileProcessor->processFileFromPath($fullPath);

            if (empty($dados) || isset($dados['error'])) {
                return response()->json([
                    'error' => isset($dados['error']) ? $dados['error'] : 'Nenhum dado encontrado no arquivo'
                ]);
            }

            $this->transactionService->storeTransactions($dados);

            return response()->json([
                'success' => true,
                'message' => 'Transações importadas com sucesso',
                'count' => count($dados)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ]);
        }
    }
}
