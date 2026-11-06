<?php
declare(strict_types=1);
?>
<meta name="color-scheme" content="light dark">
<script>
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
?>
<meta property="og:title" content="<?php echo htmlspecialchars((string) $ogTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars((string) $ogDesc, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars((string) $ogUrl, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="SPCast Live">
<meta property="og:image" content="https://live.spcast.eu/img/favicons/favicon-512x512.png">

<?php echo get_hreflang_links(); ?>

<!-- Matomo Tag Manager -->
<script>
  var _mtm = window._mtm = window._mtm || [];
  _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
  (function() {
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src='https://pw.streampanel.net/js/container_KVNJAR2p_rewrite.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Tag Manager -->
