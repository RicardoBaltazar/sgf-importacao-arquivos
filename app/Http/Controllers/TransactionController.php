<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTransactionsJob;
use App\Services\FileValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    protected $fileValidator;

    public function __construct(FileValidationService $fileValidator)
    {
        $this->fileValidator = $fileValidator;
    }

    public function process(Request $request)
    {
        $arquivoPath = $request->input('arquivo');

        if (!$arquivoPath) {
            return response()->json(['error' => 'Nenhum arquivo foi enviado']);
        }

        if (!Storage::disk('public')->exists($arquivoPath)) {
            return response()->json(['error' => 'Arquivo não encontrado: ' . $arquivoPath]);
        }

        try {
            $fullPath = Storage::disk('public')->path($arquivoPath);
            
            $result = $this->fileValidator->validate($fullPath);
            
            if (!$result['success']) {
                return response()->json(['error' => $result['message']]);
            }

            ProcessTransactionsJob::dispatch($fullPath, Auth::id());

            return response()->json([
                'success' => true,
                'message' => $result['message'] . ' Processamento iniciado.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao processar arquivo: ' . $e->getMessage()]);
        }
    }
}
