<?php
declare(strict_types=1);

global $supported_langs, $current_lang, $locale_code, $translations;

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isSecureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');

    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $isSecureCookie,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]);
}

$supported_langs = [
    'en' => 'en_US', 'de' => 'de_DE', 'fr' => 'fr_FR', 'es' => 'es_ES', 'it' => 'it_IT',
    'pt' => 'pt_PT', 'nl' => 'nl_NL', 'pl' => 'pl_PL', 'sv' => 'sv_SE', 'da' => 'da_DK',
    'fi' => 'fi_FI', 'cs' => 'cs_CZ', 'sk' => 'sk_SK', 'hu' => 'hu_HU', 'ro' => 'ro_RO',
    'bg' => 'bg_BG', 'el' => 'el_GR', 'hr' => 'hr_HR', 'sl' => 'sl_SI', 'et' => 'et_EE',
    'lv' => 'lv_LV', 'lt' => 'lt_LT', 'ga' => 'ga_IE', 'mt' => 'mt_MT',
];

if (isset($_GET['lang']) && is_string($_GET['lang']) && array_key_exists($_GET['lang'], $supported_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? 'de';
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
if (!is_string($current_lang) || !array_key_exists($current_lang, $supported_langs)) {
    $current_lang = 'de';
}
$locale_code = $supported_langs[$current_lang];

$translations = [];

$po_file = __DIR__ . "/../locale/{$locale_code}/messages.po";
if (file_exists($po_file)) {
    $po_content = file_get_contents($po_file);
    if (is_string($po_content)) {
        $lines = explode("\n", $po_content);
        $msgid = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
                $msgid = stripcslashes($matches[1]);
            } elseif (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches) && $msgid !== '') {
                $msgstr = stripcslashes($matches[1]);
                if ($msgstr !== '') {
                    $translations[$msgid] = $msgstr;
                }
                $msgid = '';
            }
        }
    }
}

if (!function_exists('__')) {
    function __(string $text): string
    {
        global $translations;
        return $translations[$text] ?? $text;
    }
}

if (!function_exists('get_hreflang_links')) {
    function get_hreflang_links(): string
    {
        global $supported_langs;

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
        $protocol = $isHttps ? 'https://' : 'http://';

        $rawHost = trim((string) ($_SERVER['HTTP_HOST'] ?? 'live.spcast.eu'));
        $isValidHost = preg_match(
            '/^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*(?::[0-9]{1,5})?$/',
            $rawHost
        ) === 1;
        $host = $isValidHost ? $rawHost : 'live.spcast.eu';

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $parsedUrl = parse_url($requestUri);
        $path = is_array($parsedUrl) ? ($parsedUrl['path'] ?? '/') : '/';

        $baseQuery = [];
        if (is_array($parsedUrl) && isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $baseQuery);
        }

        $links = '';
        foreach ($supported_langs as $lang => $locale) {
            $query = $baseQuery;
            $query['lang'] = $lang;
            $newQuery = http_build_query($query);
            $url = $protocol . $host . $path . '?' . $newQuery;
            $links .= '<link rel="alternate" hreflang="' . htmlspecialchars((string) $lang, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        }

        $defaultQuery = $baseQuery;
        $defaultQuery['lang'] = 'de';
        $defaultUrl = $protocol . $host . $path . '?' . http_build_query($defaultQuery);
        $links .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($defaultUrl, ENT_QUOTES, 'UTF-8') . '" />' . "\n";

        return $links;
    }
}
