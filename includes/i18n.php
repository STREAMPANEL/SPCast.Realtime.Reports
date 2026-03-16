<?php

declare(strict_types=1);

session_start();

$supported_langs = [
    'en' => 'en_US', 'de' => 'de_DE', 'fr' => 'fr_FR', 'es' => 'es_ES', 'it' => 'it_IT', 
    'pt' => 'pt_PT', 'nl' => 'nl_NL', 'pl' => 'pl_PL', 'sv' => 'sv_SE', 'da' => 'da_DK',
    'fi' => 'fi_FI', 'cs' => 'cs_CZ', 'sk' => 'sk_SK', 'hu' => 'hu_HU', 'ro' => 'ro_RO',
    'bg' => 'bg_BG', 'el' => 'el_GR', 'hr' => 'hr_HR', 'sl' => 'sl_SI', 'et' => 'et_EE',
    'lv' => 'lv_LV', 'lt' => 'lt_LT', 'ga' => 'ga_IE', 'mt' => 'mt_MT'
];

if (isset($_GET['lang']) && is_string($_GET['lang']) && array_key_exists($_GET['lang'], $supported_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? 'en';
$locale_code = $supported_langs[$current_lang];

$translations = [];

// Parse PO file
$po_file = __DIR__ . "/../locale/{$locale_code}/messages.po";
if (file_exists($po_file)) {
    $po_content = file_get_contents($po_file);
    $lines = explode("\n", $po_content);
    $msgid = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
            $msgid = stripcslashes($matches[1]);
        }
        elseif (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches) && $msgid !== '') {
            $msgstr = stripcslashes($matches[1]);
            $translations[$msgid] = $msgstr;
            $msgid = '';
        }
    }
}

function __($text)
{
    global $translations;
    return $translations[$text] ?? $text;
}

function get_hreflang_links()
{
    global $supported_langs;
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $request_uri = $_SERVER['REQUEST_URI'];
    $parsed_url = parse_url($request_uri);
    $path = $parsed_url['path'] ?? '';

    $links = "";
    foreach ($supported_langs as $lang => $locale) {
        $query = [];
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query);
        }
        $query['lang'] = $lang;
        $new_query = http_build_query($query);
        $url = $protocol . $host . $path . '?' . $new_query;
        $links .= '<link rel="alternate" hreflang="' . $lang . '" href="' . htmlspecialchars($url) . '" />' . "\n";
    }
    // Default x-default language (English)
    $query = [];
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query);
    }
    unset($query['lang']); // No lang param for default maybe? Or just en
    $query['lang'] = 'en';
    $new_query = http_build_query($query);
    $url = $protocol . $host . $path . '?' . $new_query;
    $links .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($url) . '" />' . "\n";

    return $links;
}
