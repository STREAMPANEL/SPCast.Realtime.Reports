<?php
declare(strict_types=1);

namespace Tests;

use App\SecurityHeaders;

class SecurityHeadersTest extends TestCase
{
    public function testPolicyAllowsRequiredResourcesAndBlocksUntrustedScripts(): void
    {
        $nonce = base64_encode(random_bytes(16));
        $policy = SecurityHeaders::contentSecurityPolicy($nonce);

        $this->assertStringContainsString("script-src 'nonce-{$nonce}' 'strict-dynamic'", $policy);
        $this->assertStringContainsString("'unsafe-hashes' 'sha256-S+8bKS/36q+tiFXa9vEJVpkVx2Z9SLV+Ok5IOy7nhBA='", $policy);
        $this->assertStringContainsString("'report-sample'", $policy);
        $this->assertStringContainsString('https://pw.spcast.eu', $policy);
        $this->assertStringContainsString('style-src \'self\' \'unsafe-inline\' https://assets.streampanel.net https://support.datadachs.eu', $policy);
        $this->assertStringContainsString('connect-src \'self\' https://pw.spcast.eu https://pw.streampanel.net', $policy);
        $this->assertStringContainsString('frame-src \'self\' https://support.datadachs.eu', $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringNotContainsString("script-src 'unsafe-inline'", $policy);
        $this->assertMatchesRegularExpression('/script-src[^;]+;/', $policy);
        $scriptDirective = '';
        foreach (explode('; ', $policy) as $directive) {
            if (str_starts_with($directive, 'script-src ')) {
                $scriptDirective = $directive;
                break;
            }
        }
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptDirective);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
    }

    public function testHeadIncludeLoadsSpcastMatomoContainerDomain(): void
    {
        $headContent = (string) file_get_contents(__DIR__ . '/../includes/head.php');

        $this->assertStringContainsString('https://pw.spcast.eu/js/container_KVNJAR2p_rewrite.js', $headContent);
        $this->assertStringNotContainsString('https://pw.streampanel.net/js/container_KVNJAR2p_rewrite.js', $headContent);
    }

    public function testNonceCannotInjectHeaderDirectives(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SecurityHeaders::contentSecurityPolicy("abc\r\nX-Test: injected");
    }
}
