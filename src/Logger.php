<?php
declare(strict_types=1);

namespace App;

class Logger
{
    public function __construct(
        private readonly string $logFile
    ) {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function error(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $sanitized = str_replace(["\r", "\n"], ' ', $message);
        $formattedMessage = "[$timestamp] ERROR: $sanitized" . PHP_EOL;
        error_log($formattedMessage, 3, $this->logFile);
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
