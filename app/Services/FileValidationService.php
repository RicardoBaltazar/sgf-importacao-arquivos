<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

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
    protected $maxRecords = 250000;

    public function validate(string $filePath): array
    {
        if (file_exists($filePath)) {
            $fullPath = $filePath;
        } else {
            if (!Storage::disk('public')->exists($filePath)) {
                return [
                    'success' => false,
                    'message' => 'Arquivo não encontrado: ' . basename($filePath)
                ];
            }
            $fullPath = Storage::disk('public')->path($filePath);
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), $this->allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Formato de arquivo não suportado. Use CSV ou Excel.'
            ];
        }

        if (filesize($fullPath) === 0) {
            return [
                'success' => false,
                'message' => 'O arquivo está vazio.'
            ];
        }

        if (strtolower($extension) === 'csv') {
            return $this->validateCSVLazy($fullPath);
        } else {
            return $this->validateExcelLazy($fullPath);
        }
    }

    private function validateCSVLazy(string $filePath): array
    {
        set_time_limit(600);
        ini_set('memory_limit', '3G');

        if (($handle = fopen($filePath, "r")) === false) {
            return [
                'success' => false,
                'message' => 'Não foi possível abrir o arquivo CSV.'
            ];
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiters = ["\t", ",", ";"];
        $delimiter = ",";

        foreach ($delimiters as $d) {
            if (substr_count($firstLine, $d) > 0) {
                $delimiter = $d;
                break;
            }
        }

        $headers = fgetcsv($handle, 1000, $delimiter);
        if (!$headers) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'O arquivo CSV não contém cabeçalhos.'
            ];
        }

        \Illuminate\Support\Facades\Log::info('Delimitador detectado: ' . $delimiter);
        \Illuminate\Support\Facades\Log::info('Cabeçalhos encontrados:', $headers);

        $normalizedHeaders = array_map(fn($h) => trim(strtolower($h)), $headers);
        $normalizedRequired = array_map(fn($a) => trim(strtolower($a)), $this->requiredAttributes);

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

        $requiredIndices = [];
        foreach ($normalizedRequired as $index => $required) {
            $headerIndex = array_search($required, $normalizedHeaders);
            if ($headerIndex !== false) {
                $requiredIndices[$this->requiredAttributes[$index]] = $headerIndex;
            }
        }

        fclose($handle);

        $lazyCollection = LazyCollection::make(function () use ($filePath, $delimiter) {
            $handle = fopen($filePath, 'r');
            fgetcsv($handle, 1000, $delimiter);
            
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                yield $row;
            }
            
            fclose($handle);
        });

        $rowCount = 0;
        $emptyColumns = [];
        $hasRows = false;

        foreach ($lazyCollection as $row) {
            $hasRows = true;
            $rowCount++;

            if ($rowCount > $this->maxRecords) {
                return [
                    'success' => false,
                    'message' => "Arquivo excede o limite de {$this->maxRecords} registros. Encontrados: {$rowCount}"
                ];
            }

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

            if ($rowCount % 1000 === 0) {
                gc_collect_cycles();
            }
        }

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

    private function validateExcelLazy(string $filePath): array
    {
        set_time_limit(600);
        ini_set('memory_limit', '3G');
        
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            
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

            $lazyCollection = LazyCollection::make(function () use ($worksheet) {
                $rowIterator = $worksheet->getRowIterator();
                $rowIterator->next();
                
                while ($rowIterator->valid()) {
                    $row = $rowIterator->current();
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    $values = [];
                    foreach ($cellIterator as $cell) {
                        $values[] = $cell->getValue();
                    }
                    
                    yield $values;
                    $rowIterator->next();
                }
            });

            $rowCount = 0;
            $emptyColumns = [];

            foreach ($lazyCollection as $values) {
                $rowCount++;

                if ($rowCount > $this->maxRecords) {
                    return [
                        'success' => false,
                        'message' => "Arquivo excede o limite de {$this->maxRecords} registros. Encontrados: {$rowCount}"
                    ];
                }

                foreach ($headers as $index => $header) {
                    if (
                        in_array($header, $this->requiredAttributes) &&
                        (!isset($values[$index]) || trim((string)$values[$index]) === '')
                    ) {
                        $emptyColumns[$header] = true;
                    }
                }

                if ($rowCount % 1000 === 0) {
                    gc_collect_cycles();
                }
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
