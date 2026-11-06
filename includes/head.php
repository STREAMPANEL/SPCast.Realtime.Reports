<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\SeoHelper;
?>
<meta name="color-scheme" content="light dark">
<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>">
(function () {
    try {
        var stored = localStorage.getItem("color-scheme");
        var valid = (stored === "light" || stored === "dark") ? stored : "light dark";
        var meta = document.querySelector('meta[name="color-scheme"]');
        if (meta) {
            meta.content = valid;
        }
        var resolved = valid === "light dark"
            ? (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light")
            : valid;
        document.documentElement.setAttribute("data-bs-theme", resolved);
        var fs = localStorage.getItem("spcast-font-size");
        if (fs === "small" || fs === "normal" || fs === "large") {
            document.documentElement.setAttribute("data-font-size", fs);
        }
    } catch (e) {}
})();
</script>

<link href="css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/bootstrap-icons.min.css">
<link rel="stylesheet" href="css/nav.min.css">
<link rel="stylesheet" href="css/spcast-theme.css">

<link rel="preload" href="img/favicons/favicon.svg" as="image" type="image/svg+xml">
<link rel="icon" type="image/svg+xml" href="img/favicons/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="img/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="img/favicons/favicon-16x16.png">
<link rel="apple-touch-icon" href="img/favicons/favicon-512x512.png">
<link rel="mask-icon" href="img/favicons/favicon.svg" color="#0b1020">
<link rel="icon" type="image/png" sizes="192x192" href="img/favicons/favicon-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="img/favicons/favicon-512x512.png">

<meta name="msapplication-TileColor" content="#0b1020">
<meta name="msapplication-TileImage" content="img/favicons/favicon-152x152.png">
<meta name="theme-color" content="#0b1020" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f3f6fa" media="(prefers-color-scheme: light)">

<?php
$ogTitle = $pageTitle ?? ($inoutConfig['pageTitle'] ?? ($reportConfig['pageTitle'] ?? 'SPCast Realtime Statistics - SPCast Live'));
$ogDesc = $pageDescription ?? ($inoutConfig['pageDescription'] ?? ($reportConfig['pageDescription'] ?? ''));
$ogUrl = $canonicalUrl ?? ($inoutConfig['canonicalUrl'] ?? ($reportConfig['canonicalUrl'] ?? 'https://live.spcast.eu/'));
$ogLocale = isset($locale_code) && is_string($locale_code) && $locale_code !== '' ? $locale_code : 'de_DE';
$robotsContent = isset($seoRobots) && is_string($seoRobots) && $seoRobots !== ''
    ? $seoRobots
    : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
?>
<meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars((string) $ogTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars((string) $ogDesc, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars((string) $ogUrl, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="SPCast Live">
<meta property="og:locale" content="<?php echo htmlspecialchars($ogLocale, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars(SeoHelper::OG_IMAGE_URL, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="<?php echo (int) SeoHelper::OG_IMAGE_WIDTH; ?>">
<meta property="og:image:height" content="<?php echo (int) SeoHelper::OG_IMAGE_HEIGHT; ?>">
<meta property="og:image:alt" content="<?php echo htmlspecialchars((string) $ogTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars((string) $ogTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars((string) $ogDesc, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars(SeoHelper::OG_IMAGE_URL, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:image:alt" content="<?php echo htmlspecialchars((string) $ogTitle, ENT_QUOTES, 'UTF-8'); ?>">

<?php
if (isset($baseCanonicalUrl) && is_string($baseCanonicalUrl) && isset($supported_langs) && is_array($supported_langs)) {
    echo SeoHelper::buildHreflangLinks($baseCanonicalUrl, $supported_langs);
} else {
    echo get_hreflang_links();
}
?>
<?php if (isset($schemaGraph) && is_array($schemaGraph)): ?>
<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" type="application/ld+json"><?php echo json_encode($schemaGraph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<?php endif; ?>

<!-- Matomo Tag Manager -->
<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>">
  var _mtm = window._mtm = window._mtm || [];
  _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
  (function() {
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src='https://pw.spcast.eu/js/container_KVNJAR2p_rewrite.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Tag Manager -->
