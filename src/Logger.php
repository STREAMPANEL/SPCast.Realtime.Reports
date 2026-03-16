<?php

declare(strict_types=1);

namespace App;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
        // Ensure directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function error(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] ERROR: $message" . PHP_EOL;
        error_log($formattedMessage, 3, $this->logFile);
    }

    public static function createDefault(): self
    {
        return new self(__DIR__ . '/../logs/error.log');
    }
}
