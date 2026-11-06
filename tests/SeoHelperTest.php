<?php
declare(strict_types=1);

namespace Tests;

use App\ReportType;
use App\SeoHelper;
use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;

class SeoHelperTest extends TestCase
{
    public function testBuildCanonicalUrlHandlesDefaultAndLocalizedLanguagesAndStripsQuery(): void
    {
        $this->assertSame(
            'https://live.spcast.eu/country.php',
            SeoHelper::buildCanonicalUrl('https://live.spcast.eu/country.php?stat=os&period=7days', 'de')
        );
        $this->assertSame(
            'https://live.spcast.eu/country.php?lang=en',
            SeoHelper::buildCanonicalUrl('https://live.spcast.eu/country.php', 'en')
        );
        $this->assertSame(
            'https://live.spcast.eu/?lang=fr',
            SeoHelper::buildCanonicalUrl('https://live.spcast.eu/', 'FR')
        );
        $this->assertSame(
            'https://live.spcast.eu/',
            SeoHelper::buildCanonicalUrl('https://live.spcast.eu/', 'invalid-lang')
        );
    }

    public function testLocalizeRelativeUrlAppendsNonDefaultLanguageParameter(): void
    {
        $this->assertSame('country.php', SeoHelper::localizeRelativeUrl('country.php', 'de'));
        $this->assertSame('country.php?lang=en', SeoHelper::localizeRelativeUrl('country.php', 'en'));
        $this->assertSame('./?stat=os&lang=fr', SeoHelper::localizeRelativeUrl('./?stat=os', 'fr'));
    }

    public function testBuildHreflangLinksAlignsSelfReferencingCanonicals(): void
    {
        $links = SeoHelper::buildHreflangLinks('https://live.spcast.eu/in.php', [
            'de' => 'de_DE',
            'en' => 'en_US',
            'es' => 'es_ES',
        ]);

        $this->assertStringContainsString(
            '<link rel="alternate" hreflang="de" href="https://live.spcast.eu/in.php" />',
            $links
        );
        $this->assertStringContainsString(
            '<link rel="alternate" hreflang="en" href="https://live.spcast.eu/in.php?lang=en" />',
            $links
        );
        $this->assertStringContainsString(
            '<link rel="alternate" hreflang="x-default" href="https://live.spcast.eu/in.php" />',
            $links
        );
        $this->assertStringNotContainsString('?lang=de', $links);
    }

    public function testResolveRobotsDirectiveBlocksFilterStatesAndAllowsCanonicalPages(): void
    {
        $this->assertSame(
            'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
            SeoHelper::resolveRobotsDirective([])
        );
        $this->assertSame(
            'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
            SeoHelper::resolveRobotsDirective(['lang' => 'en'])
        );
        $this->assertSame('noindex, follow', SeoHelper::resolveRobotsDirective(['stat' => 'country']));
        $this->assertSame('noindex, follow', SeoHelper::resolveRobotsDirective(['period' => '7days']));
    }

    public function testEnsureDatasetDescriptionEnforcesMinimumGoogleDatasetLength(): void
    {
        $short = SeoHelper::ensureDatasetDescription('Short summary.');
        $this->assertGreaterThanOrEqual(50, mb_strlen($short, 'UTF-8'));

        $combined = SeoHelper::ensureDatasetDescription(
            'Short summary.',
            'Extended anonymized radio streaming telemetry overview across all connected stations.'
        );
        $this->assertGreaterThanOrEqual(50, mb_strlen($combined, 'UTF-8'));
        $this->assertStringContainsString('Extended anonymized', $combined);
    }

    public function testBuildHomeSchemaGraphContainsValidDataCatalogWithElevenDatasets(): void
    {
        $now = new DateTimeImmutable('2026-11-06T12:00:00+01:00', new DateTimeZone('Europe/Berlin'));
        $graph = SeoHelper::buildHomeSchemaGraph(
            'https://live.spcast.eu/?lang=en',
            'en',
            'SPCast Realtime Statistics - SPCast Live',
            'Get realtime statistics on user activities in the SPCast network.',
            'Anonymized realtime statistics across all connected radio stations in the SPCast network.',
            'spcast live, realtime statistics, radio telemetry',
            static fn (string $s): string => $s,
            [],
            $now
        );

        $this->assertSame('https://schema.org', $graph['@context']);
        $this->assertIsArray($graph['@graph']);
        $this->assertCount(4, $graph['@graph']);

        $catalog = $graph['@graph'][3];
        $this->assertSame('DataCatalog', $catalog['@type']);
        $this->assertTrue($catalog['isAccessibleForFree']);
        $this->assertCount(11, $catalog['dataset']);
        $this->assertSame('https://live.spcast.eu/in.php?lang=en', $catalog['dataset'][0]['url']);
        $this->assertSame('2026-10-07/2026-11-06', $catalog['dataset'][0]['temporalCoverage']);
    }

    public function testBuildReportSchemaGraphContainsValidDatasetAndBreadcrumbList(): void
    {
        $now = new DateTimeImmutable('2026-11-06T12:00:00+01:00', new DateTimeZone('Europe/Berlin'));
        $graph = SeoHelper::buildReportSchemaGraph(
            ReportType::Country,
            'https://live.spcast.eu/country.php',
            'de',
            'Nutzerstatistik nach Land - SPCast Live',
            'Nutzerstatistik nach Land',
            'Analyse der Nutzeraktivitaeten nach Laendern im SPCast-Netzwerk.',
            'Analysieren Sie detailliert und anonymisiert, aus welchen Laendern Hoerer zugreifen.',
            'spcast live, laenderstatistik, echtzeitdaten',
            'Land (Heute)',
            320,
            [],
            $now
        );

        $this->assertCount(5, $graph['@graph']);

        $breadcrumb = $graph['@graph'][2];
        $this->assertSame('BreadcrumbList', $breadcrumb['@type']);
        $this->assertCount(2, $breadcrumb['itemListElement']);
        $this->assertSame('https://live.spcast.eu/', $breadcrumb['itemListElement'][0]['item']);
        $this->assertSame('https://live.spcast.eu/country.php', $breadcrumb['itemListElement'][1]['item']);

        $dataset = $graph['@graph'][4];
        $this->assertSame('Dataset', $dataset['@type']);
        $this->assertGreaterThanOrEqual(50, mb_strlen((string) $dataset['description'], 'UTF-8'));
        $this->assertSame(320, $dataset['variableMeasured']['value']);
        $this->assertSame(
            'https://live.spcast.eu/api.php?action=report&type=country',
            $dataset['distribution'][0]['contentUrl']
        );
    }

    public function testRenderRankingRowsHtmlEscapesMaliciousLabelsAndBuildsFaqLinks(): void
    {
        $entries = SeoHelper::normalizeMapEntries([
            'Germany' => 75,
            '<img src=x onerror=alert(1)>' => 25,
        ]);

        $html = SeoHelper::renderRankingRowsHtml($entries, 100, 'country', 'No data');

        $this->assertStringContainsString('#1', $html);
        $this->assertStringContainsString('https://www.spcast.eu/faq/statistik/usercountry/germany/', $html);
        $this->assertStringContainsString('75.0%', $html);
        $this->assertStringContainsString('25.0%', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStandaloneReportRendersServerSideTableRowsAndDatasetSchema(): void
    {
        $_GET['lang'] = 'en';
        $cacheDir = __DIR__ . '/../cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $periods = ['today', 'yesterday', '7days', '30days'];
        $createdFiles = [];
        foreach ($periods as $period) {
            $cacheFile = $cacheDir . 'analytics_country_cache_' . $period . '.json';
            file_put_contents($cacheFile, json_encode(['Germany' => 150, 'Austria' => 50]), LOCK_EX);
            $createdFiles[] = $cacheFile;
        }

        $previousCwd = getcwd();
        chdir(__DIR__ . '/..');

        ob_start();
        try {
            include __DIR__ . '/../country.php';
            $html = (string) ob_get_clean();
        } finally {
            if ($previousCwd !== false) {
                chdir($previousCwd);
            }
            foreach ($createdFiles as $cacheFile) {
                if (is_file($cacheFile)) {
                    unlink($cacheFile);
                }
            }
        }

        $this->assertStringContainsString('<link rel="canonical" href="https://live.spcast.eu/country.php?lang=en" />', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="en_US">', $html);
        $this->assertStringContainsString('<th scope="row"><span class="sp-badge-count me-2">#1</span>', $html);
        $this->assertStringContainsString('Germany</a>', $html);
        $this->assertStringContainsString('75.0%', $html);
        $this->assertStringContainsString('"@type":"Dataset"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
    }

    public function testSitemapXmlAndRobotsTxtAreValidAndConsistent(): void
    {
        $sitemapPath = __DIR__ . '/../sitemap.xml';
        $this->assertFileExists($sitemapPath);

        $dom = new DOMDocument();
        $loaded = $dom->load($sitemapPath);
        $this->assertTrue($loaded);

        $locs = $dom->getElementsByTagName('loc');
        $this->assertSame(12, $locs->length);

        $robots = (string) file_get_contents(__DIR__ . '/../robots.txt');
        $this->assertStringContainsString('Sitemap: https://live.spcast.eu/sitemap.xml', $robots);
        $this->assertStringNotContainsString('Disallow: .well-known/*', $robots);

        $htaccess = (string) file_get_contents(__DIR__ . '/../.htaccess');
        $this->assertStringContainsString('AddType application/x-httpd-alt-php85___lsphp', $htaccess);
        $this->assertStringContainsString('RewriteRule ^(in|out|country|region|city|os|browser|mount|protocol|port|ipversion)/?$ /$1.php [R=301,L,QSA]', $htaccess);
    }
}
