<?php
declare(strict_types=1);

namespace App;

class Logger
{
    public function __construct(
        private readonly string $logFile,
        private readonly int $maxBytes = 5_000_000
    ) {
        if ($this->maxBytes < 128) {
            throw new \InvalidArgumentException('The maximum log size must be at least 128 bytes.');
        }
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function error(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', ' ', $message) ?? '';
        $sanitized = substr($sanitized, 0, min(4096, $this->maxBytes - 32));
        $formattedMessage = "[$timestamp] ERROR: $sanitized" . PHP_EOL;

        $handle = fopen($this->logFile, 'c+');
        if ($handle === false) {
            error_log($formattedMessage);
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                error_log($formattedMessage);
                return;
            }

            $size = fstat($handle)['size'] ?? 0;
            if ($size + strlen($formattedMessage) > $this->maxBytes) {
                ftruncate($handle, 0);
                rewind($handle);
            } else {
                fseek($handle, 0, SEEK_END);
            }

            if (fwrite($handle, $formattedMessage) !== strlen($formattedMessage)) {
                error_log($formattedMessage);
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public static function createDefault(): self
    {
        $logDir = __DIR__ . '/../logs';
        $logger = new self($logDir . '/error.log');
        $htaccessFile = $logDir . '/.htaccess';
        if (!file_exists($htaccessFile) && is_writable($logDir)) {
            $rules = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";
            file_put_contents($htaccessFile, $rules, LOCK_EX);
        }

        return $logger;
    }
}
