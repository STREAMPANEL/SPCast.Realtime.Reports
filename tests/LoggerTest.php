<?php
declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestCase.php';

use App\Logger;

class LoggerTest extends TestCase
{
    private string $testLogDir;
    private string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testLogDir = __DIR__ . '/temp_logs';
        $this->testLogFile = $this->testLogDir . '/test_error.log';
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        parent::tearDown();
    }

    private function cleanUp(): void
    {
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
        if (is_dir($this->testLogDir)) {
            rmdir($this->testLogDir);
        }
    }

    public function testConstructorCreatesDirectory(): void
    {
        $this->assertDirectoryDoesNotExist($this->testLogDir);

        new Logger($this->testLogFile);

        $this->assertDirectoryExists($this->testLogDir);
    }

    public function testErrorWritesToLogFileWithCorrectFormat(): void
    {
        $logger = new Logger($this->testLogFile);
        $logger->error('Test error message');

        $this->assertFileExists($this->testLogFile);
        $content = file_get_contents($this->testLogFile);

        $this->assertStringContainsString('ERROR: Test error message', $content);
        $this->assertMatchesRegularExpression('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] ERROR: Test error message' . preg_quote(PHP_EOL, '/') . '$/', $content);
    }

    public function testErrorSanitizesLineBreaksToPreventLogInjection(): void
    {
        $logger = new Logger($this->testLogFile);
        $logger->error("First line\r\nFake entry");

        $content = (string) file_get_contents($this->testLogFile);
        $lines = array_values(array_filter(explode(PHP_EOL, $content), static fn (string $l): bool => $l !== ''));

        $this->assertCount(1, $lines);
        $this->assertStringContainsString('First line  Fake entry', $lines[0]);
    }

    public function testErrorLogSizeStaysBounded(): void
    {
        $logger = new Logger($this->testLogFile, 180);
        for ($i = 0; $i < 20; $i++) {
            $logger->error('Message ' . $i . ' ' . str_repeat('x', 25));
        }

        $this->assertLessThanOrEqual(180, filesize($this->testLogFile));
        $this->assertStringContainsString('Message 19', (string) file_get_contents($this->testLogFile));
    }
}
