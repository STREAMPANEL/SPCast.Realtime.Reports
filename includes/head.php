<?php declare(strict_types=1); ?>
<!-- Bootstrap CSS -->
<link href="css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap ICONS -->
<link rel="stylesheet" href="css/bootstrap-icons.min.css">
<!-- Navbar -->
<link rel="stylesheet" href="css/nav.min.css">

<!-- Preconnect to speed up the connection to the external favicon domain -->
<link rel="preconnect" href="https://assets.streampanel.net" crossorigin>

<!-- DNS prefetch to resolve the domain name early and reduce lookup time -->
<link rel="dns-prefetch" href="https://assets.streampanel.net">

<!-- Preload main favicon (SVG) for faster rendering -->
<link rel="preload" href="https://assets.streampanel.net/favicons/sp/bw/favicon.svg" as="image" type="image/svg+xml">

<!-- Standard favicon (modern browsers prefer SVG) -->
<link rel="icon" type="image/svg+xml" href="https://assets.streampanel.net/favicons/sp/bw/favicon.svg">

<!-- PNG fallback for older browsers -->
<link rel="icon" type="image/png" sizes="32x32" href="https://assets.streampanel.net/favicons/sp/bw/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://assets.streampanel.net/favicons/sp/bw/favicon-16x16.png">

<!-- Apple touch icon (iOS requires PNG, SVG won't work) -->
<link rel="apple-touch-icon" href="https://assets.streampanel.net/favicons/sp/bw/favicon-512x512.png">

<!-- Safari pinned tab icon (SVG recommended) -->
<link rel="mask-icon" href="https://assets.streampanel.net/favicons/sp/bw/favicon.svg" color="#162536">

<!-- Android Chrome icons (must be PNG, SVG is not supported) -->
<link rel="icon" type="image/png" sizes="192x192" href="https://assets.streampanel.net/favicons/sp/bw/favicon-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="https://assets.streampanel.net/favicons/sp/bw/favicon-512x512.png">

<!-- Windows tiles (only supports PNG, no SVG) -->
<meta name="msapplication-TileColor" content="#162536">
<meta name="msapplication-TileImage" content="https://assets.streampanel.net/favicons/sp/bw/favicon-152x152.png">

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

<!-- Matomo Tag Manager -->
<script>
    var _mtm = window._mtm = window._mtm || [];
    _mtm.push({ 'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start' });
    (function () {
        var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
        g.async = true; g.src = 'https://pw.spcast.eu/js/container_KVNJAR2p_rewrite.js'; s.parentNode.insertBefore(g, s);
    })();
</script>
<!-- End Matomo Tag Manager -->
<?php



echo get_hreflang_links(); ?>
