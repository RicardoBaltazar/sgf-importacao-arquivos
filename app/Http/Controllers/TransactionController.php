<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTransactionsJob;
use App\Services\FileProcessorService;
use App\Services\FileValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    protected $fileProcessor;
    protected $fileValidator;

    public function __construct(
        FileProcessorService $fileProcessor,
        FileValidationService $fileValidator
    ) {
        $this->fileProcessor = $fileProcessor;
        $this->fileValidator = $fileValidator;
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

            $userId = Auth::id();
            ProcessTransactionsJob::dispatch($dados, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Suas transações estão sendo processadas',
                'count' => count($dados)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ]);
        }
    }
}
