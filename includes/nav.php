<?php
$navItems = [
    ['label' => 'Startseite', 'url' => 'https://live.spcast.eu/'],
    ['label' => 'Eingehend', 'url' => 'in.php'],
    ['label' => 'Ausgehend', 'url' => 'out.php'],
    ['label' => 'Land', 'url' => 'country.php'],
    ['label' => 'Bundesland', 'url' => 'region.php'],
    ['label' => 'Stadt', 'url' => 'city.php'],
    ['label' => 'Betriebssystem', 'url' => 'os.php'],
    ['label' => 'Browser', 'url' => 'browser.php'],
    ['label' => 'Mountpoint', 'url' => 'mount.php'],
    ['label' => 'Protokoll', 'url' => 'protocol.php'],
    ['label' => 'Port', 'url' => 'port.php'],
    ['label' => 'IP-Version', 'url' => 'ipversion.php'],
];
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light" aria-label="Hauptnavigation">
    <div class="container-fluid">
        <!-- Mobile menu button -->
        <button class="btn btn-primary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
            Menü
        </button>

        <!-- Desktop navigation -->
        <div class="collapse navbar-collapse justify-content-center d-none d-lg-flex" id="navbarNav">
            <ul class="navbar-nav">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Off-Canvas menu for mobile devices -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasNavLabel">Menü</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav flex-column">
            <?php foreach ($navItems as $item): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
