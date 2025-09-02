<?php

namespace Tests\Unit\Services;

use App\Services\FileValidationService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileValidationServiceTest extends TestCase
{
    protected $fileValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileValidationService = new FileValidationService();
        Storage::fake('public');
    }

    public function testValidateWithNonExistentFile(): void
    {
        $result = $this->fileValidationService->validate('arquivo_inexistente.csv');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Arquivo não encontrado', $result['message']);
    }

    public function testValidateWithUnsupportedFileFormat(): void
    {
        Storage::disk('public')->put('test.txt', 'Conteúdo de teste');

        $result = $this->fileValidationService->validate('test.txt');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Formato de arquivo não suportado', $result['message']);
    }

    public function testValidateWithEmptyFile(): void
    {
        Storage::disk('public')->put('empty.csv', '');

        $result = $this->fileValidationService->validate('empty.csv');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('O arquivo está vazio', $result['message']);
    }

    public function testValidateWithValidCSVFile(): void
    {
        $csvContent = "data_transacao,descricao,categoria,valor,tipo_transacao\n";
        $csvContent .= "2023-01-01,Compra,Alimentação,100.00,despesa\n";

        Storage::disk('public')->put('valid.csv', $csvContent);

        $result = $this->fileValidationService->validate('valid.csv');

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Arquivo válido', $result['message']);
    }
}
