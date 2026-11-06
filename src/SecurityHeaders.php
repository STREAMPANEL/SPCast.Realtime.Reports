<?php
declare(strict_types=1);

namespace App;

final class SecurityHeaders
{
    private const PRIVACY_BANNER_ONCLICK_HASH = 'sha256-S+8bKS/36q+tiFXa9vEJVpkVx2Z9SLV+Ok5IOy7nhBA=';

    public static function sendHtml(): string
    {
        if (headers_sent()) {
            throw new \RuntimeException('Cannot send security headers after response output.');
        }

        $nonce = base64_encode(random_bytes(16));

        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: private, no-store, max-age=0');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), focus-without-user-activation=()');
        header('Content-Security-Policy: ' . self::contentSecurityPolicy($nonce));

        return $nonce;
    }

    public static function contentSecurityPolicy(string $nonce): string
    {
        if (preg_match('/^[A-Za-z0-9+\/=]+$/D', $nonce) !== 1) {
            throw new \InvalidArgumentException('Invalid CSP nonce.');
        }

        $onclickHash = self::PRIVACY_BANNER_ONCLICK_HASH;

        return implode('; ', [
            "default-src 'self'",
            "script-src 'nonce-{$nonce}' 'strict-dynamic' 'unsafe-hashes' '{$onclickHash}' 'report-sample' 'self' https://pw.spcast.eu https://assets.streampanel.net",
            "style-src 'self' 'unsafe-inline' https://assets.streampanel.net https://support.datadachs.eu",
            "img-src 'self' data: https://pw.spcast.eu https://pw.streampanel.net https://assets.streampanel.net https://support.datadachs.eu https://*.google-analytics.com https://*.googletagmanager.com",
            "font-src 'self' data: https://assets.streampanel.net https://support.datadachs.eu",
            "connect-src 'self' https://pw.spcast.eu https://pw.streampanel.net https://assets.streampanel.net https://support.datadachs.eu https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com",
            "frame-src 'self' https://support.datadachs.eu",
            "object-src 'none'",
            "base-uri 'none'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            'upgrade-insecure-requests',
        ]);
    }
}
