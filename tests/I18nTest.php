<?php
declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestCase.php';

class I18nTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testGetLangArray(): void
    {
        $_GET['lang'] = ['en'];

        require __DIR__ . '/../includes/i18n.php';

        $this->assertEquals('de', $_SESSION['lang'] ?? 'de');
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetLangString(): void
    {
        $_GET['lang'] = 'en';

        require __DIR__ . '/../includes/i18n.php';

        $this->assertEquals('en', $_SESSION['lang'] ?? 'de');
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetHreflangLinksHttp(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/path/to/page';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '80';

        require __DIR__ . '/../includes/i18n.php';
        global $supported_langs;
        $supported_langs = ['en' => 'en_US', 'de' => 'de_DE'];

        $links = get_hreflang_links();

        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="http://example.com/path/to/page?lang=en" />', $links);
        $this->assertStringContainsString('<link rel="alternate" hreflang="de" href="http://example.com/path/to/page?lang=de" />', $links);
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="http://example.com/path/to/page?lang=de" />', $links);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetHreflangLinksHttps(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/path/to/page';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = '443';

        require __DIR__ . '/../includes/i18n.php';
        global $supported_langs;
        $supported_langs = ['en' => 'en_US', 'de' => 'de_DE'];

        $links = get_hreflang_links();

        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="https://example.com/path/to/page?lang=en" />', $links);
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="https://example.com/path/to/page?lang=de" />', $links);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetHreflangLinksHttpsPort(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/path/to/page';
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '443';

        require __DIR__ . '/../includes/i18n.php';
        global $supported_langs;
        $supported_langs = ['en' => 'en_US', 'de' => 'de_DE'];

        $links = get_hreflang_links();

        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="https://example.com/path/to/page?lang=en" />', $links);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetHreflangLinksPreservesQueryParams(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/path/to/page?foo=bar&baz=qux';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '80';

        require __DIR__ . '/../includes/i18n.php';
        global $supported_langs;
        $supported_langs = ['en' => 'en_US', 'de' => 'de_DE'];

        $links = get_hreflang_links();

        $this->assertStringContainsString('href="http://example.com/path/to/page?foo=bar&amp;baz=qux&amp;lang=en"', $links);
        $this->assertStringContainsString('href="http://example.com/path/to/page?foo=bar&amp;baz=qux&amp;lang=de"', $links);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetHreflangLinksReplacesLangQueryParam(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/path/to/page?foo=bar&lang=fr';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_PORT'] = '80';

        require __DIR__ . '/../includes/i18n.php';
        global $supported_langs;
        $supported_langs = ['en' => 'en_US', 'de' => 'de_DE', 'fr' => 'fr_FR'];

        $links = get_hreflang_links();

        $this->assertStringContainsString('href="http://example.com/path/to/page?foo=bar&amp;lang=en"', $links);
        $this->assertStringContainsString('href="http://example.com/path/to/page?foo=bar&amp;lang=de"', $links);
        $this->assertStringNotContainsString('&amp;lang=fr&amp;lang=en', $links);
    }

    /**
     * @runInSeparateProcess
     */
    public function testEnglishReferencePoHasEmptyMsgstrAndFallsBackToMsgid(): void
    {
        $_GET['lang'] = 'en';
        require __DIR__ . '/../includes/i18n.php';

        $this->assertSame('Realtime Statistics', __('Realtime Statistics'));
        $this->assertSame('Interactive Statistics Hub', __('Interactive Statistics Hub'));

        $enPo = (string) file_get_contents(__DIR__ . '/../locale/en_US/messages.po');
        $lines = explode("\n", $enPo);
        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            if ($idx > 4 && str_starts_with($trimmed, 'msgstr ')) {
                $this->assertSame('msgstr ""', $trimmed);
            }
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testGermanLocaleTranslatesHubStrings(): void
    {
        $_GET['lang'] = 'de';
        require __DIR__ . '/../includes/i18n.php';

        $this->assertSame('Interaktiver Statistik-Hub', __('Interactive Statistics Hub'));
        $this->assertSame('Echtzeitstatistiken', __('Realtime Statistics'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetHreflangLinksRejectsMalformedHostHeader(): void
    {
        $_SERVER['HTTP_HOST'] = 'evil.example.com:80:99/injected';
        $_SERVER['REQUEST_URI'] = '/country.php';
        $_SERVER['HTTPS'] = 'on';

        require __DIR__ . '/../includes/i18n.php';
        global $supported_langs;
        $supported_langs = ['de' => 'de_DE'];

        $links = get_hreflang_links();

        $this->assertStringContainsString('href="https://live.spcast.eu/country.php?lang=de"', $links);
        $this->assertStringNotContainsString('evil.example.com', $links);
    }
}
