<?php
declare(strict_types=1);

namespace App;

use DateTimeImmutable;
use DateTimeZone;

final class SeoHelper
{
    public const BASE_URL = 'https://live.spcast.eu';
    public const BRAND_URL = 'https://www.spcast.eu/';
    public const BRAND_ORGANIZATION_ID = 'https://www.spcast.eu/#organization';
    public const BRAND_WEBSITE_ID = 'https://www.spcast.eu/#/schema/WebSite';
    public const LOGO_URL = 'https://live.spcast.eu/img/favicons/favicon-512x512.png';
    public const OG_IMAGE_URL = 'https://live.spcast.eu/img/og-preview.png';
    public const OG_IMAGE_WIDTH = 1200;
    public const OG_IMAGE_HEIGHT = 630;
    public const DEFAULT_LANG = 'de';

    public static function buildCanonicalUrl(string $baseCanonicalUrl, string $currentLang = self::DEFAULT_LANG): string
    {
        $parsed = parse_url(trim($baseCanonicalUrl));
        $scheme = is_array($parsed) && isset($parsed['scheme']) ? strtolower($parsed['scheme']) : 'https';
        $host = is_array($parsed) && isset($parsed['host']) ? strtolower($parsed['host']) : 'live.spcast.eu';
        $path = is_array($parsed) && isset($parsed['path']) && $parsed['path'] !== '' ? $parsed['path'] : '/';

        $cleanBase = $scheme . '://' . $host . $path;
        $normalizedLang = strtolower(trim($currentLang));

        if ($normalizedLang === '' || $normalizedLang === self::DEFAULT_LANG) {
            return $cleanBase;
        }

        if (preg_match('/^[a-z]{2}$/', $normalizedLang) !== 1) {
            return $cleanBase;
        }

        return $cleanBase . '?lang=' . rawurlencode($normalizedLang);
    }

    public static function localizeRelativeUrl(string $relativeUrl, string $currentLang = self::DEFAULT_LANG): string
    {
        $normalizedLang = strtolower(trim($currentLang));
        if ($normalizedLang === '' || $normalizedLang === self::DEFAULT_LANG || preg_match('/^[a-z]{2}$/', $normalizedLang) !== 1) {
            return $relativeUrl;
        }

        $separator = str_contains($relativeUrl, '?') ? '&' : '?';

        return $relativeUrl . $separator . 'lang=' . rawurlencode($normalizedLang);
    }

    /**
     * @param array<string, string> $supportedLangs
     */
    public static function buildHreflangLinks(string $baseCanonicalUrl, array $supportedLangs): string
    {
        $links = '';

        foreach ($supportedLangs as $lang => $locale) {
            $langCode = strtolower(trim((string) $lang));
            if (preg_match('/^[a-z]{2}$/', $langCode) !== 1) {
                continue;
            }

            $href = self::buildCanonicalUrl($baseCanonicalUrl, $langCode);
            $links .= '<link rel="alternate" hreflang="'
                . htmlspecialchars($langCode, ENT_QUOTES, 'UTF-8')
                . '" href="'
                . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
                . '" />' . "\n";
        }

        $defaultHref = self::buildCanonicalUrl($baseCanonicalUrl, self::DEFAULT_LANG);
        $links .= '<link rel="alternate" hreflang="x-default" href="'
            . htmlspecialchars($defaultHref, ENT_QUOTES, 'UTF-8')
            . '" />' . "\n";

        return $links;
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public static function resolveRobotsDirective(array $queryParams): string
    {
        if (array_key_exists('stat', $queryParams) || array_key_exists('period', $queryParams)) {
            return 'noindex, follow';
        }

        return 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
    }

    /**
     * @param list<string> $cacheFilePaths
     */
    public static function resolveLastModifiedIso(array $cacheFilePaths, ?DateTimeImmutable $now = null): string
    {
        $tz = new DateTimeZone('Europe/Berlin');
        $latestMtime = 0;

        foreach ($cacheFilePaths as $path) {
            if ($path !== '' && is_file($path)) {
                $mtime = filemtime($path);
                if (is_int($mtime) && $mtime > $latestMtime) {
                    $latestMtime = $mtime;
                }
            }
        }

        if ($latestMtime > 0) {
            return (new DateTimeImmutable('@' . $latestMtime))
                ->setTimezone($tz)
                ->format(DATE_ATOM);
        }

        $reference = $now !== null ? $now->setTimezone($tz) : new DateTimeImmutable('now', $tz);

        return $reference->format(DATE_ATOM);
    }

    public static function buildTemporalCoverage(?DateTimeImmutable $now = null, int $daysBack = 30): string
    {
        $tz = new DateTimeZone('Europe/Berlin');
        $end = $now !== null ? $now->setTimezone($tz) : new DateTimeImmutable('now', $tz);
        $safeDays = max(1, $daysBack);
        $start = $end->modify("-{$safeDays} days");

        return $start->format('Y-m-d') . '/' . $end->format('Y-m-d');
    }

    public static function ensureDatasetDescription(string $primaryDescription, string $fallbackExtension = ''): string
    {
        $desc = trim(preg_replace('/\s+/u', ' ', $primaryDescription) ?? $primaryDescription);
        $ext = trim(preg_replace('/\s+/u', ' ', $fallbackExtension) ?? $fallbackExtension);

        if (mb_strlen($desc, 'UTF-8') < 50 && $ext !== '' && $ext !== $desc) {
            $desc = trim($desc . ' ' . $ext);
        }

        if (mb_strlen($desc, 'UTF-8') < 50) {
            $desc = trim($desc . ' Anonymized realtime and historical streaming network telemetry provided by SPCast Live.');
        }

        return $desc;
    }

    /**
     * @return list<string>
     */
    public static function parseKeywords(string $rawKeywords): array
    {
        $parts = explode(',', $rawKeywords);
        $cleaned = [];

        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '' && !in_array($trimmed, $cleaned, true)) {
                $cleaned[] = $trimmed;
            }
        }

        return $cleaned;
    }

    /**
     * @param list<string> $cacheFilePaths
     * @return array<string, mixed>
     */
    public static function buildHomeSchemaGraph(
        string $canonicalUrl,
        string $currentLang,
        string $pageTitle,
        string $pageDescription,
        string $heroDescription,
        string $keywords,
        callable $translator,
        array $cacheFilePaths = [],
        ?DateTimeImmutable $now = null
    ): array {
        $lang = self::normalizeLanguageCode($currentLang);
        $dateModified = self::resolveLastModifiedIso($cacheFilePaths, $now);
        $temporalCoverage = self::buildTemporalCoverage($now, 30);
        $catalogDescription = self::ensureDatasetDescription($heroDescription, $pageDescription);

        $datasets = [];
        foreach (ReportType::cases() as $case) {
            $datasetUrl = self::buildCanonicalUrl(self::BASE_URL . '/' . $case->pageUrl(), $lang);
            $datasetTitle = (string) $translator($case->labelMsgId()) . ' - SPCast Live';
            $datasetDesc = self::ensureDatasetDescription(
                (string) $translator($case->descriptionMsgId()),
                $pageDescription
            );

            $datasets[] = [
                '@type' => 'Dataset',
                '@id' => $datasetUrl . '#dataset',
                'name' => $datasetTitle,
                'description' => $datasetDesc,
                'url' => $datasetUrl,
                'inLanguage' => $lang,
                'isAccessibleForFree' => true,
                'temporalCoverage' => $temporalCoverage,
                'creator' => ['@id' => self::BRAND_ORGANIZATION_ID],
                'distribution' => [
                    [
                        '@type' => 'DataDownload',
                        'encodingFormat' => 'application/json',
                        'contentUrl' => self::BASE_URL . '/api.php?action=report&type=' . $case->value,
                    ],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                self::buildOrganizationNode(),
                self::buildWebSiteNode($lang),
                [
                    '@type' => 'CollectionPage',
                    '@id' => $canonicalUrl . '#webpage',
                    'url' => $canonicalUrl,
                    'name' => $pageTitle,
                    'description' => $pageDescription,
                    'inLanguage' => $lang,
                    'dateModified' => $dateModified,
                    'isPartOf' => ['@id' => self::BASE_URL . '/#website'],
                    'about' => ['@id' => self::BASE_URL . '/#catalog'],
                    'primaryImageOfPage' => self::buildPrimaryImageNode($canonicalUrl),
                ],
                [
                    '@type' => 'DataCatalog',
                    '@id' => self::BASE_URL . '/#catalog',
                    'name' => $pageTitle,
                    'description' => $catalogDescription,
                    'url' => $canonicalUrl,
                    'inLanguage' => $lang,
                    'isAccessibleForFree' => true,
                    'dateModified' => $dateModified,
                    'keywords' => self::parseKeywords($keywords),
                    'publisher' => ['@id' => self::BRAND_ORGANIZATION_ID],
                    'dataset' => $datasets,
                ],
            ],
        ];
    }

    /**
     * @param list<string> $cacheFilePaths
     * @return array<string, mixed>
     */
    public static function buildReportSchemaGraph(
        ReportType $reportType,
        string $canonicalUrl,
        string $currentLang,
        string $pageTitle,
        string $headerTitle,
        string $pageDescription,
        string $headerDescription,
        string $keywords,
        string $measuredLabel,
        ?int $headlineMetricValue = null,
        array $cacheFilePaths = [],
        ?DateTimeImmutable $now = null
    ): array {
        $lang = self::normalizeLanguageCode($currentLang);
        $dateModified = self::resolveLastModifiedIso($cacheFilePaths, $now);
        $temporalCoverage = self::buildTemporalCoverage($now, 30);
        $homeCanonical = self::buildCanonicalUrl(self::BASE_URL . '/', $lang);

        $variableMeasured = [
            '@type' => 'PropertyValue',
            'name' => $measuredLabel,
        ];
        if ($headlineMetricValue !== null) {
            $variableMeasured['value'] = $headlineMetricValue;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                self::buildOrganizationNode(),
                self::buildWebSiteNode($lang),
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $canonicalUrl . '#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'SPCast Live',
                            'item' => $homeCanonical,
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => $headerTitle,
                            'item' => $canonicalUrl,
                        ],
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $canonicalUrl . '#webpage',
                    'url' => $canonicalUrl,
                    'name' => $pageTitle,
                    'description' => $pageDescription,
                    'inLanguage' => $lang,
                    'dateModified' => $dateModified,
                    'isPartOf' => ['@id' => self::BASE_URL . '/#website'],
                    'breadcrumb' => ['@id' => $canonicalUrl . '#breadcrumb'],
                    'mainEntity' => ['@id' => $canonicalUrl . '#dataset'],
                    'primaryImageOfPage' => self::buildPrimaryImageNode($canonicalUrl),
                ],
                [
                    '@type' => 'Dataset',
                    '@id' => $canonicalUrl . '#dataset',
                    'name' => $headerTitle . ' - SPCast Live',
                    'description' => self::ensureDatasetDescription($headerDescription, $pageDescription),
                    'url' => $canonicalUrl,
                    'inLanguage' => $lang,
                    'isAccessibleForFree' => true,
                    'temporalCoverage' => $temporalCoverage,
                    'dateModified' => $dateModified,
                    'keywords' => self::parseKeywords($keywords),
                    'creator' => ['@id' => self::BRAND_ORGANIZATION_ID],
                    'publisher' => ['@id' => self::BRAND_ORGANIZATION_ID],
                    'includedInDataCatalog' => ['@id' => self::BASE_URL . '/#catalog'],
                    'variableMeasured' => $variableMeasured,
                    'distribution' => [
                        [
                            '@type' => 'DataDownload',
                            'encodingFormat' => 'application/json',
                            'contentUrl' => self::BASE_URL . '/api.php?action=report&type=' . $reportType->value,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, int> $dataMap
     * @return list<array{rank: int, label: string, count: int}>
     */
    public static function normalizeMapEntries(array $dataMap, ?string $aggregateLabel = null): array
    {
        $sorted = [];
        foreach ($dataMap as $label => $count) {
            $sorted[(string) $label] = (int) $count;
        }
        $aggregateCount = null;
        if ($aggregateLabel !== null && array_key_exists($aggregateLabel, $sorted)) {
            $aggregateCount = $sorted[$aggregateLabel];
            unset($sorted[$aggregateLabel]);
        }
        arsort($sorted);

        $entries = [];
        $rank = 1;
        foreach ($sorted as $label => $count) {
            $entries[] = [
                'rank' => $rank++,
                'label' => $label,
                'count' => $count,
            ];
        }

        if ($aggregateCount !== null) {
            $entries[] = [
                'rank' => 0,
                'label' => $aggregateLabel,
                'count' => $aggregateCount,
            ];
        }

        return $entries;
    }

    /**
     * @param list<string> $labels
     * @param list<int> $values
     * @return list<array{rank: int, label: string, count: int}>
     */
    public static function normalizeSeriesEntries(array $labels, array $values): array
    {
        $entries = [];
        foreach ($labels as $idx => $label) {
            $entries[] = [
                'rank' => $idx + 1,
                'label' => (string) $label,
                'count' => (int) ($values[$idx] ?? 0),
            ];
        }

        return $entries;
    }

    /**
     * @param list<array{rank: int, label: string, count: int}> $entries
     */
    public static function renderRankingRowsHtml(
        array $entries,
        int $totalSum,
        string $emptyMessage
    ): string {
        if ($entries === []) {
            return '<tr><td colspan="3" class="text-center py-4" style="color: var(--sp-muted);">'
                . htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8')
                . '</td></tr>';
        }

        $html = '';
        foreach ($entries as $entry) {
            $rank = (int) $entry['rank'];
            $rankMarkup = $rank > 0 ? '#' . $rank : '&mdash;';
            $label = (string) $entry['label'];
            $count = (int) $entry['count'];

            $pct = $totalSum > 0 ? ($count / $totalSum) * 100.0 : 0.0;
            $clampedPct = min(100.0, max(0.0, $pct));
            $pctFormatted = number_format($pct, 1, '.', '') . '%';
            $widthFormatted = number_format($clampedPct, 1, '.', '') . '%';
            $countFormatted = number_format($count, 0, '.', ',');
            $labelMarkup = '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';

            $html .= '<tr>'
                . '<th scope="row"><span class="sp-badge-count me-2">' . $rankMarkup . '</span>' . $labelMarkup . '</th>'
                . '<td class="text-end fw-semibold" style="font-variant-numeric: tabular-nums;">' . $countFormatted . '</td>'
                . '<td><div class="sp-share-bar">'
                . '<div class="sp-share-track"><div class="sp-share-fill" style="width: ' . $widthFormatted . ';"></div></div>'
                . '<span class="small" style="min-width: 3.1rem; text-align: right; font-variant-numeric: tabular-nums;">' . $pctFormatted . '</span>'
                . '</div></td>'
                . '</tr>';
        }

        return $html;
    }

    public static function renderInterpolatedLink(
        string $translatedTemplate,
        string $href,
        string $linkLabel
    ): string {
        $safeLink = '<a href="'
            . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
            . '" target="_blank" rel="noopener" class="sp-inline-link">'
            . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8')
            . '</a>';

        if (str_contains($translatedTemplate, '%s')) {
            $parts = explode('%s', $translatedTemplate, 2);

            return htmlspecialchars($parts[0], ENT_QUOTES, 'UTF-8')
                . $safeLink
                . htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars($translatedTemplate, ENT_QUOTES, 'UTF-8') . ' ' . $safeLink;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildOrganizationNode(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => self::BRAND_ORGANIZATION_ID,
            'name' => 'SPCast',
            'alternateName' => 'SPCast.eu',
            'url' => self::BRAND_URL,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => self::LOGO_URL,
                'width' => 512,
                'height' => 512,
            ],
            'sameAs' => [
                self::BRAND_URL,
                'https://github.com/STREAMPANEL/SPCast.Realtime.Reports',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildWebSiteNode(string $lang): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::BASE_URL . '/#website',
            'url' => self::BASE_URL . '/',
            'name' => 'SPCast Live',
            'inLanguage' => $lang,
            'publisher' => ['@id' => self::BRAND_ORGANIZATION_ID],
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => self::BRAND_WEBSITE_ID,
                'url' => self::BRAND_URL,
                'name' => 'SPCast.eu',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildPrimaryImageNode(string $canonicalUrl): array
    {
        return [
            '@type' => 'ImageObject',
            '@id' => $canonicalUrl . '#primaryimage',
            'url' => self::OG_IMAGE_URL,
            'contentUrl' => self::OG_IMAGE_URL,
            'width' => self::OG_IMAGE_WIDTH,
            'height' => self::OG_IMAGE_HEIGHT,
        ];
    }

    private static function normalizeLanguageCode(string $lang): string
    {
        $normalized = strtolower(trim($lang));

        return preg_match('/^[a-z]{2}$/', $normalized) === 1 ? $normalized : self::DEFAULT_LANG;
    }
}
