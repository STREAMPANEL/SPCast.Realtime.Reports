<?php

declare(strict_types=1);

$navItems = [
    ['label' => __('Home'), 'url' => 'https://live.spcast.eu/'],
    ['label' => __('Incoming'), 'url' => 'in.php'],
    ['label' => __('Outgoing'), 'url' => 'out.php'],
    ['label' => __('Country'), 'url' => 'country.php'],
    ['label' => __('Region'), 'url' => 'region.php'],
    ['label' => __('City'), 'url' => 'city.php'],
    ['label' => __('Operating System'), 'url' => 'os.php'],
    ['label' => __('Browser'), 'url' => 'browser.php'],
    ['label' => __('Mountpoint'), 'url' => 'mount.php'],
    ['label' => __('Protocol'), 'url' => 'protocol.php'],
    ['label' => __('Port'), 'url' => 'port.php'],
    ['label' => __('IP Version'), 'url' => 'ipversion.php'],
];

$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light" aria-label="<?php echo htmlspecialchars(__('Main Navigation')); ?>">
    <div class="container-fluid">
        <a class="navbar-brand d-lg-none fw-bold" href="https://live.spcast.eu/">SPCast Live</a>
        <!-- Mobile menu button -->
        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Desktop navigation -->
        <div class="collapse navbar-collapse justify-content-center d-none d-lg-flex" id="navbarNav">
            <ul class="navbar-nav">
                <?php foreach ($navItems as $item): 
                    $isActive = ($currentPage === 'index.php' && $item['url'] === 'https://live.spcast.eu/') || ($item['url'] === $currentPage);
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive ? 'active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?> href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Off-Canvas menu for mobile devices -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasNavLabel"><?php echo htmlspecialchars(__('Menu')); ?></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav flex-column">
            <?php foreach ($navItems as $item): 
                $isActive = ($currentPage === 'index.php' && $item['url'] === 'https://live.spcast.eu/') || ($item['url'] === $currentPage);
            ?>
                <li class="nav-item">
                    <a class="nav-link <?= $isActive ? 'active fw-bold' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?> href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
