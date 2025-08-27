<?php

namespace App\Services;

use Illuminate\Support\LazyCollection;

class FileProcessorService
{
    public function processFileFromPath(string $filePath): LazyCollection
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if (strtolower($extension) === 'csv') {
            return $this->processCSV($filePath);
        } else {
            return $this->processExcel($filePath);
        }
    }

    private function processCSV(string $filePath): LazyCollection
    {
        return LazyCollection::make(function () use ($filePath) {
            $handle = fopen($filePath, 'r');
            
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
                $transaction = [];
                foreach ($headers as $index => $header) {
                    $transaction[$header] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                yield $transaction;
            }
            
            fclose($handle);
        });
    }

    private function processExcel(string $filePath): LazyCollection
    {
        return LazyCollection::make(function () use ($filePath) {
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
                
                $transaction = [];
                foreach ($headers as $index => $header) {
                    $transaction[$header] = isset($values[$index]) ? trim($values[$index]) : '';
                }
                
                yield $transaction;
                $rowIterator->next();
            }
        });
    }
}
