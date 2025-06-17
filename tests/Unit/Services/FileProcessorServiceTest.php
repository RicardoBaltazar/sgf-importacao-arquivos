<?php

namespace Tests\Unit\Services;

use App\Services\FileProcessorService;
use PHPUnit\Framework\TestCase;

class FileProcessorServiceTest extends TestCase
{
    protected $fileProcessor;
    protected $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileProcessor = new FileProcessorService();
    }

    protected function tearDown(): void
    {
        // Limpar arquivos temporários após cada teste
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testProcessFileFromPathWithCSVFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.csv';
        $this->tempFiles[] = $tempFile;

        $csvContent = "nome,idade,email\nJoão,30,joao@example.com";
        file_put_contents($tempFile, $csvContent);

        $result = $this->fileProcessor->processFileFromPath($tempFile);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('error', $result);
        $this->assertCount(1, $result);
        $this->assertEquals('João', $result[0]['nome']);
    }

    public function testProcessFileFromPathWithNonExistentFile(): void
    {
        $result = $this->fileProcessor->processFileFromPath('/caminho/inexistente/arquivo.csv');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Arquivo não encontrado', $result['error']);
    }

    public function testProcessFileFromPathWithUnsupportedFormat(): void
    {
        // Criar um arquivo temporário com formato não suportado
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        $this->tempFiles[] = $tempFile;

        file_put_contents($tempFile, 'Conteúdo de teste');

        $result = $this->fileProcessor->processFileFromPath($tempFile);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Formato de arquivo não suportado', $result['error']);
    }
}
