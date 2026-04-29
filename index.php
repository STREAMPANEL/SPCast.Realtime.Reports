<?php

declare(strict_types=1);

require_once 'includes/i18n.php'; ?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang ?? 'de'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $pageTitle = 'SPCast ' . __('Realtime Statistics') . ' - SPCast Live';
    $pageDescription = __('Get realtime statistics on user activities in the SPCast network. Insights into incoming and outgoing data, updated live.');
    $pageKeywords = __('spcast live, spcast echtzeitstatistiken, nutzerstatistik, netzwerkstatistiken, live daten, sendernutzung');
    $canonicalUrl = 'https://live.spcast.eu/';
    ?>
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords, ENT_QUOTES); ?>">
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>" />
    <?php require_once "includes/head.php"; ?>

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "SPCast Live",
      "url": "https://live.spcast.eu/",
      "description": "<?php echo htmlspecialchars($pageDescription, ENT_QUOTES); ?>",
      "publisher": {
        "@type": "Organization",
        "name": "SPCast"
      }
    }
    </script>
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container mt-5">
        <div class="text-center mb-4">
            <h1>SPCast <?php echo __('Realtime Statistics'); ?></h1>
            <p><?php echo __('Get anonymized realtime statistics on user activities in the SPCast network. Track general trends and analyze the user behavior of all radio stations in a centralized overview.'); ?></p>
        </div>
        
        <div class="row g-4">
            <?php
            $mainCards = [
                ['title' => __('Incoming'), 'text' => __('These are users who tuned in to a station in the network within the last 30 minutes.'), 'url' => 'in.php'],
                ['title' => __('Outgoing'), 'text' => __('These are users who tuned out of a station in the network within the last 30 minutes.'), 'url' => 'out.php']
            ];
            foreach ($mainCards as $card): ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="card-title"><?php echo $card['title']; ?></h2>
                            <p class="card-text"><?php echo $card['text']; ?></p>
                            <a href="<?php echo htmlspecialchars($card['url']); ?>" class="btn btn-primary"><?php echo __('View'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4 mt-2">
            <?php
            $statCards = [
                ['title' => __('Country'), 'text' => __('Shows user countries for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'country.php'],
                ['title' => __('Region'), 'text' => __('Shows user regions for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'region.php'],
                ['title' => __('City'), 'text' => __('Shows user cities for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'city.php'],
                ['title' => __('Operating System'), 'text' => __('Shows user operating systems for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'os.php'],
                ['title' => __('Browser'), 'text' => __('Shows used browsers for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'browser.php'],
                ['title' => __('Mountpoint'), 'text' => __('Shows user mountpoints for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'mount.php'],
                ['title' => __('Protocol'), 'text' => __('Shows user activity protocols for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'protocol.php'],
                ['title' => __('Port'), 'text' => __('Shows used ports for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'port.php'],
                ['title' => __('IP Version'), 'text' => __('Shows used IP versions for today, yesterday, the last 7, and the last 30 days.'), 'url' => 'ipversion.php']
            ];

            foreach ($statCards as $card): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="card-title"><?php echo $card['title']; ?></h2>
                            <p class="card-text"><?php echo $card['text']; ?></p>
                            <a href="<?php echo htmlspecialchars($card['url']); ?>" class="btn btn-primary"><?php echo __('View'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>