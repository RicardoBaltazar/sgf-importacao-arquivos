<?php

namespace Tests\Unit\Services;

use App\Services\FileProcessorService;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;

class FileProcessorServiceTest extends TestCase
{
    protected $fileProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileProcessor = new FileProcessorService();
    }

    public function testProcessFileFromPathReturnsLazyCollection(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.csv';
        
        $csvContent = "campo1\nvalor1";
        file_put_contents($tempFile, $csvContent);

        $result = $this->fileProcessor->processFileFromPath($tempFile);

        $this->assertInstanceOf(LazyCollection::class, $result);
        
        unlink($tempFile);
    }

    public function testProcessFileFromPathWithExcelExtension(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.xlsx';
        
        file_put_contents($tempFile, '');

        $result = $this->fileProcessor->processFileFromPath($tempFile);

        $this->assertInstanceOf(LazyCollection::class, $result);
        
        unlink($tempFile);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(FileProcessorService::class, $this->fileProcessor);
        $this->assertTrue(method_exists($this->fileProcessor, 'processFileFromPath'));
    }
}
