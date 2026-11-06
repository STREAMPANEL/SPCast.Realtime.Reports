<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\ReportType;

$navItems = [
    [
        'label' => __('Home'),
        'url' => './',
        'page' => 'index.php',
        'icon' => 'bi-activity',
    ],
];

foreach (ReportType::cases() as $reportCase) {
    $navItems[] = [
        'label' => __($reportCase->labelMsgId()),
        'url' => $reportCase->pageUrl(),
        'page' => $reportCase->pageUrl(),
        'icon' => $reportCase->iconClass(),
    ];
}

$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
?>
<header class="sp-navbar" role="banner">
    <nav class="container-fluid px-3 px-xl-4 d-flex align-items-center justify-content-between gap-3" aria-label="<?php echo htmlspecialchars(__('Main Navigation'), ENT_QUOTES, 'UTF-8'); ?>">
        <a class="sp-brand" href="./">
            <img src="img/favicons/favicon.svg" alt="SPCast" width="32" height="32" class="sp-brand-logo">
            <span>SPCast</span>
            <span class="sp-live-badge">
                <span class="sp-live-dot" aria-hidden="true"></span>
                <span>Live</span>
            </span>
        </a>

        <div class="d-none d-xxl-flex flex-grow-1 justify-content-center min-w-0">
            <ul class="sp-nav-list">
                <?php foreach ($navItems as $item):
                    $isActive = ($currentPage === $item['page']);
                ?>
                    <li>
                        <a class="sp-nav-link<?php echo $isActive ? ' active' : ''; ?>"
                           <?php echo $isActive ? 'aria-current="page"' : ''; ?>
                           href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="https://www.spcast.eu/" target="_blank" rel="noopener" class="sp-btn-ghost d-none d-sm-inline-flex">
                <span>spcast.eu</span>
                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
            </a>
            <button class="sp-nav-toggler d-xxl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav" aria-label="<?php echo htmlspecialchars(__('Menu'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-list fs-5" aria-hidden="true"></i>
            </button>
        </div>
    </nav>
</header>

<div class="offcanvas offcanvas-start sp-offcanvas" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title d-flex align-items-center gap-2 mb-0" id="offcanvasNavLabel">
            <img src="img/favicons/favicon.svg" alt="" width="26" height="26" class="sp-brand-logo" aria-hidden="true">
            <span><?php echo htmlspecialchars(__('Menu'), ENT_QUOTES, 'UTF-8'); ?></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php echo htmlspecialchars(__('Close'), ENT_QUOTES, 'UTF-8'); ?>"></button>
    </div>
    <div class="offcanvas-body">
        <ul>
            <?php foreach ($navItems as $item):
                $isActive = ($currentPage === $item['page']);
            ?>
                <li>
                    <a class="sp-nav-link<?php echo $isActive ? ' active' : ''; ?>"
                       <?php echo $isActive ? 'aria-current="page"' : ''; ?>
                       href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-4 pt-3 border-top" style="border-color: var(--sp-border) !important;">
            <a href="https://www.spcast.eu/" target="_blank" rel="noopener" class="sp-btn-ghost w-100 justify-content-center">
                <span>www.spcast.eu</span>
                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
