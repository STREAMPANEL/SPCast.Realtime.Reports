<?php declare(strict_types=1); ?>
<!-- Bootstrap CSS -->
<link href="css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap ICONS -->
<link rel="stylesheet" href="css/bootstrap-icons.min.css">
<!-- Navbar -->
<link rel="stylesheet" href="css/nav.min.css">

<!-- Preload main favicon (SVG) for faster rendering -->
<link rel="preload" href="img/favicons/favicon.svg" as="image" type="image/svg+xml">

<!-- Standard favicon (modern browsers prefer SVG) -->
<link rel="icon" type="image/svg+xml" href="img/favicons/favicon.svg">

<!-- PNG fallback for older browsers -->
<link rel="icon" type="image/png" sizes="32x32" href="img/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="img/favicons/favicon-16x16.png">

<!-- Apple touch icon (iOS requires PNG, SVG won't work) -->
<link rel="apple-touch-icon" href="img/favicons/favicon-512x512.png">

<!-- Safari pinned tab icon (SVG recommended) -->
<link rel="mask-icon" href="img/favicons/favicon.svg" color="#162536">

<!-- Android Chrome icons (must be PNG, SVG is not supported) -->
<link rel="icon" type="image/png" sizes="192x192" href="img/favicons/favicon-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="img/favicons/favicon-512x512.png">

<!-- Windows tiles (only supports PNG, no SVG) -->
<meta name="msapplication-TileColor" content="#162536">
<meta name="msapplication-TileImage" content="img/favicons/favicon-152x152.png">

<!-- Theme color for mobile browsers (affects address bar color on Android) -->
<meta name="theme-color" content="#162536">

<!-- Inline Style -->
<style>
    .info-icon {
        font-size: 0.8rem;
        position: relative;
        top: -20px;
        margin-left: -10px;
        cursor: pointer;
    }

    /* Tab styles */
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }

    .nav-tabs::-webkit-scrollbar {
        display: none;
    }

    .nav-tabs .nav-link {
        white-space: nowrap;
        font-weight: 500;
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.25rem;
        transition: color 0.2s, border-color 0.2s;
    }

    .nav-tabs .nav-link:hover {
        color: #495057;
        border-bottom-color: #adb5bd;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
        background: transparent;
    }

    .nav-tabs .nav-link .badge {
        font-size: 0.7rem;
        vertical-align: middle;
    }

    .tab-content {
        min-height: 300px;
    }

    @media (max-width: 575.98px) {
        .nav-tabs .nav-link {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }
    }
</style>


<?php
$ogTitle = $pageTitle ?? ($inoutConfig['pageTitle'] ?? ($reportConfig['pageTitle'] ?? 'SPCast Realtime Statistics - SPCast Live'));
$ogDesc = $pageDescription ?? ($inoutConfig['pageDescription'] ?? ($reportConfig['pageDescription'] ?? ''));
$ogUrl = $canonicalUrl ?? ($inoutConfig['canonicalUrl'] ?? ($reportConfig['canonicalUrl'] ?? 'https://live.spcast.eu/'));
?>
<!-- Open Graph Meta Tags -->
<meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($ogDesc, ENT_QUOTES); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($ogUrl, ENT_QUOTES); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="SPCast Live">
<meta property="og:image" content="img/favicons/favicon-512x512.png">

<?php echo get_hreflang_links(); ?>
