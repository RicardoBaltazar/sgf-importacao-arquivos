<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileProcessorService
{
    /**
     * Processa o arquivo a partir de um caminho físico
     */
    public function processFileFromPath(string $physicalPath): array
    {
        if (!file_exists($physicalPath)) {
            return ['error' => 'Arquivo não encontrado: ' . $physicalPath];
        }

        $extension = pathinfo($physicalPath, PATHINFO_EXTENSION);

        if (strtolower($extension) === 'csv') {
            return $this->processCSV($physicalPath);
        } elseif (in_array(strtolower($extension), ['xlsx', 'xls'])) {
            return $this->processExcel($physicalPath);
        } elseif (strtolower($extension) === 'xml') {
            return $this->processXML($physicalPath);
        }

        return ['error' => 'Formato de arquivo não suportado'];
    }

    /**
     * Processa arquivo CSV
     */
    private function processCSV(string $filePath): array
    {
        $data = [];

        try {
            $content = file_get_contents($filePath);
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
                file_put_contents($tempFile, $content);
                $filePath = $tempFile;
            }

            $firstLine = '';
            if (($handle = fopen($filePath, "r")) !== false) {
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

                while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                    $item = [];

                    foreach ($headers as $index => $field) {
                        if (isset($row[$index])) {
                            $item[$field] = $row[$index];
                        }
                    }

                    $data[] = $item;
                }

                fclose($handle);

                if (isset($tempFile)) {
                    @unlink($tempFile);
                }
            }
        } catch (\Exception $e) {
            return ['error' => 'Erro ao processar arquivo CSV: ' . $e->getMessage()];
        }

        return $data;
    }


    /**
     * Processa arquivo Excel
     */
    private function processExcel(string $filePath): array
    {
        $data = [];

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = [];
            $firstRow = true;

            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $values = [];
                foreach ($cellIterator as $cell) {
                    $values[] = $cell->getValue();
                }

                if ($firstRow) {
                    $headers = $values;
                    $firstRow = false;
                } else {
                    $item = [];
                    foreach ($headers as $index => $field) {
                        if (isset($values[$index])) {
                            $item[$field] = $values[$index];
                        }
                    }
                    $data[] = $item;
                }
            }
        } catch (\Exception $e) {
            return ['error' => 'Erro ao processar arquivo Excel: ' . $e->getMessage()];
        }

        return $data;
    }

    /**
     * Processa arquivo XML
     */
    private function processXML(string $filePath): array
    {
        $data = [];

        try {
            $xml = simplexml_load_file($filePath);

            // Assumindo que o XML tem uma estrutura com elementos repetidos
            // Por exemplo: <transacoes><transacao>...</transacao><transacao>...</transacao></transacoes>
            foreach ($xml->children() as $item) {
                $transaction = [];

                foreach ($item as $key => $value) {
                    $transaction[(string)$key] = (string)$value;
                }

                $data[] = $transaction;
            }
        } catch (\Exception $e) {
            return ['error' => 'Erro ao processar arquivo XML: ' . $e->getMessage()];
        }

        return $data;
    }
}
