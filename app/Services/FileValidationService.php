<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileValidationService
{
    protected $requiredAttributes = [
        'data_transacao',
        'descricao',
        'categoria',
        'valor',
        'tipo_transacao'
    ];

    protected $allowedExtensions = ['csv', 'xlsx', 'xls'];

    /**
     * Valida o arquivo antes do processamento
     */
    public function validate(string $filePath): array
    {
        if (!Storage::disk('public')->exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Arquivo não encontrado: ' . basename($filePath)
            ];
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), $this->allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Formato de arquivo não suportado. Use CSV ou Excel.'
            ];
        }

        $fullPath = Storage::disk('public')->path($filePath);

        if (filesize($fullPath) === 0) {
            return [
                'success' => false,
                'message' => 'O arquivo está vazio.'
            ];
        }

        if (strtolower($extension) === 'csv') {
            return $this->validateCSV($fullPath);
        } else {
            return $this->validateExcel($fullPath);
        }
    }

    /**
     * Valida arquivo CSV
     */
    private function validateCSV(string $filePath): array
    {
        if (($handle = fopen($filePath, "r")) === false) {
            return [
                'success' => false,
                'message' => 'Não foi possível abrir o arquivo CSV.'
            ];
        }

        // Detectar o delimitador
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiters = ["\t", ",", ";"];
        $delimiter = ","; // Padrão

        foreach ($delimiters as $d) {
            if (substr_count($firstLine, $d) > 0) {
                $delimiter = $d;
                break;
            }
        }

        // Ler cabeçalhos com o delimitador detectado
        $headers = fgetcsv($handle, 1000, $delimiter);
        if (!$headers) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'O arquivo CSV não contém cabeçalhos.'
            ];
        }

        // Debug
        \Illuminate\Support\Facades\Log::info('Delimitador detectado: ' . $delimiter);
        \Illuminate\Support\Facades\Log::info('Cabeçalhos encontrados:', $headers);

        $normalizedHeaders = array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);

        $normalizedRequired = array_map(function ($attr) {
            return trim(strtolower($attr));
        }, $this->requiredAttributes);

        $missingAttributes = [];
        foreach ($this->requiredAttributes as $index => $attr) {
            if (!in_array($normalizedRequired[$index], $normalizedHeaders)) {
                $missingAttributes[] = $attr;
            }
        }

        if (!empty($missingAttributes)) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'Atributos obrigatórios ausentes: ' . implode(', ', $missingAttributes)
            ];
        }

        $hasRows = false;
        $rowCount = 0;
        $emptyColumns = [];

        $requiredIndices = [];
        foreach ($normalizedRequired as $index => $required) {
            $headerIndex = array_search($required, $normalizedHeaders);
            if ($headerIndex !== false) {
                $requiredIndices[$this->requiredAttributes[$index]] = $headerIndex;
            }
        }

        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            $hasRows = true;
            $rowCount++;

            // Debug da primeira linha
            if ($rowCount === 1) {
                \Illuminate\Support\Facades\Log::info('Primeira linha de dados:', $row);
            }

            foreach ($this->requiredAttributes as $attr) {
                if (
                    isset($requiredIndices[$attr]) &&
                    (!isset($row[$requiredIndices[$attr]]) ||
                        trim((string)$row[$requiredIndices[$attr]]) === '')
                ) {
                    $emptyColumns[$attr] = true;
                }
            }
        }

        fclose($handle);

        if (!$hasRows) {
            return [
                'success' => false,
                'message' => 'O arquivo não contém dados além dos cabeçalhos.'
            ];
        }

        if (!empty($emptyColumns)) {
            return [
                'success' => false,
                'message' => 'Valores obrigatórios em branco nas colunas: ' . implode(', ', array_keys($emptyColumns))
            ];
        }

        return [
            'success' => true,
            'message' => 'Arquivo válido com ' . $rowCount . ' transações.'
        ];
    }


    /**
     * Valida arquivo Excel
     */
    private function validateExcel(string $filePath): array
    {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = [];
            $firstRow = $worksheet->getRowIterator()->current();
            $cellIterator = $firstRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $headers[] = $cell->getValue();
            }

            $missingAttributes = $this->checkMissingAttributes($headers);
            if (!empty($missingAttributes)) {
                return [
                    'success' => false,
                    'message' => 'Atributos obrigatórios ausentes: ' . implode(', ', $missingAttributes)
                ];
            }

            $rowCount = 0;
            $emptyColumns = [];
            $rowIterator = $worksheet->getRowIterator();
            $rowIterator->next();

            while ($rowIterator->valid()) {
                $rowCount++;
                $row = $rowIterator->current();
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $values = [];
                foreach ($cellIterator as $cell) {
                    $values[] = $cell->getValue();
                }

                foreach ($headers as $index => $header) {
                    if (
                        in_array($header, $this->requiredAttributes) &&
                        (!isset($values[$index]) || trim((string)$values[$index]) === '')
                    ) {
                        $emptyColumns[$header] = true;
                    }
                }

                $rowIterator->next();
            }

            if ($rowCount === 0) {
                return [
                    'success' => false,
                    'message' => 'O arquivo não contém dados além dos cabeçalhos.'
                ];
            }

            if (!empty($emptyColumns)) {
                return [
                    'success' => false,
                    'message' => 'Valores obrigatórios em branco nas colunas: ' . implode(', ', array_keys($emptyColumns))
                ];
            }

            return [
                'success' => true,
                'message' => 'Arquivo válido com ' . $rowCount . ' transações.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao validar arquivo Excel: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica atributos obrigatórios ausentes
     */
    private function checkMissingAttributes(array $headers): array
    {
        $missingAttributes = [];

        foreach ($this->requiredAttributes as $attr) {
            if (!in_array($attr, $headers)) {
                $missingAttributes[] = $attr;
            }
        }

        return $missingAttributes;
    }
}
