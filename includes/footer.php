<?php
declare(strict_types=1);

$clientI18n = [
    'users' => __('Users'),
    'count' => __('Count'),
    'share' => __('Share'),
    'rank' => __('Rank'),
    'value' => __('Entry'),
    'noData' => __('No statistics available for the selected period yet.'),
    'loading' => __('Loading statistics...'),
    'errorLoad' => __('Unable to load statistics at the moment.'),
    'numberOfUsers' => __('Number of users'),
    'paused' => __('Paused'),
    'resume' => __('Resume auto-refresh'),
    'pause' => __('Pause auto-refresh'),
    'systemCurrentlyDark' => __('System (currently dark)'),
    'systemCurrentlyLight' => __('System (currently light)'),
    'showingTop' => __('Top 15'),
    'showingAll' => __('All entries'),
    'totalEvents' => __('Total events'),
    'uniqueEntries' => __('Unique entries'),
    'topEntry' => __('Top entry'),
    'peakToday' => __('Peak Today'),
];
?>
<footer class="sp-footer">
    <div class="container py-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3 pb-3 border-bottom" style="border-color: var(--sp-border) !important;">
            <ul class="d-flex flex-wrap justify-content-center align-items-center gap-3 list-unstyled mb-0">
                <li>
                    <a href="https://www.spcast.eu/" target="_blank" rel="noopener" class="sp-footer-link">SPCast.eu</a>
                </li>
                <li class="d-none d-sm-inline" aria-hidden="true">|</li>
                <li>
                    <a href="https://www.spcast-playlist.de" target="_blank" rel="noopener" class="sp-footer-link">SPCast Playlist</a>
                </li>
                <li class="d-none d-sm-inline" aria-hidden="true">|</li>
                <li>
                    <a href="https://www.spcast.eu/kontakt/impressum/" target="_blank" rel="noopener" class="sp-footer-link"><?php echo htmlspecialchars(__('Imprint'), ENT_QUOTES, 'UTF-8'); ?></a>
                </li>
                <li class="d-none d-sm-inline" aria-hidden="true">|</li>
                <li>
                    <a href="https://www.spcast.eu/kontakt/datenschutz/" target="_blank" rel="noopener" class="sp-footer-link"><?php echo htmlspecialchars(__('Privacy Policy'), ENT_QUOTES, 'UTF-8'); ?></a>
                </li>
                <li class="d-none d-sm-inline" aria-hidden="true">|</li>
                <li>
                    <a href="https://www.spcast.eu/kontakt/cookies/" target="_blank" rel="noopener" class="sp-footer-link"><?php echo htmlspecialchars(__('Cookies'), ENT_QUOTES, 'UTF-8'); ?></a>
                </li>
                <li class="d-none d-sm-inline" aria-hidden="true">|</li>
                <li>
                    <a href="https://github.com/STREAMPANEL/SPCast.Realtime.Reports" target="_blank" rel="noopener" class="sp-footer-link">
                        <i class="bi bi-github me-1" aria-hidden="true"></i>GitHub
                    </a>
                </li>
            </ul>

            <form action="" method="GET" class="d-inline-flex align-items-center gap-2">
                <?php
                $allowedHiddenParams = ['stat', 'period'];
                foreach ($allowedHiddenParams as $paramKey) {
                    if (isset($_GET[$paramKey]) && is_string($_GET[$paramKey]) && preg_match('/^[a-zA-Z0-9_-]{1,32}$/', $_GET[$paramKey]) === 1) {
                        echo '<input type="hidden" name="' . htmlspecialchars($paramKey, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($_GET[$paramKey], ENT_QUOTES, 'UTF-8') . '">';
                    }
                }
                ?>
                <label for="spLangSelect" class="visually-hidden"><?php echo htmlspecialchars(__('Language Selection'), ENT_QUOTES, 'UTF-8'); ?></label>
                <select id="spLangSelect" name="lang" class="form-select form-select-sm sp-select w-auto" aria-label="<?php echo htmlspecialchars(__('Language Selection'), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php
                    $lang_names = [
                        'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español',
                        'it' => 'Italiano', 'pt' => 'Português', 'nl' => 'Nederlands', 'pl' => 'Polski',
                        'sv' => 'Svenska', 'da' => 'Dansk', 'fi' => 'Suomi', 'cs' => 'Čeština',
                        'sk' => 'Slovenčina', 'hu' => 'Magyar', 'ro' => 'Română', 'bg' => 'Български',
                        'el' => 'Ελληνικά', 'hr' => 'Hrvatski', 'sl' => 'Slovenščina', 'et' => 'Eesti',
                        'lv' => 'Latviešu', 'lt' => 'Lietuvių', 'ga' => 'Gaeilge', 'mt' => 'Malti',
                    ];
                    $selected_lang = $current_lang ?? 'de';
                    foreach ($lang_names as $k => $v) {
                        $sel = ($selected_lang === $k) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    ?>
                </select>
            </form>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-3">
            <div>
                <span>&copy; 2024 - <?php echo date('Y'); ?> <a href="https://www.spcast.eu/" target="_blank" rel="noopener" class="sp-footer-link fw-semibold">SPCast</a></span>
            </div>

            <div class="sp-a11y-console">
                <div class="sp-console-group" role="group" aria-label="<?php echo htmlspecialchars(__('Select color scheme'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="sp-console-btn" type="button" data-theme-choice="system" aria-label="<?php echo htmlspecialchars(__('System default'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('System default'), ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="true">
                        <i class="bi bi-circle-half" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline" id="spSystemThemeLabel"><?php echo htmlspecialchars(__('System'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                    <button class="sp-console-btn" type="button" data-theme-choice="light" aria-label="<?php echo htmlspecialchars(__('Light theme'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Light theme'), ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="false">
                        <i class="bi bi-sun" aria-hidden="true"></i>
                    </button>
                    <button class="sp-console-btn" type="button" data-theme-choice="dark" aria-label="<?php echo htmlspecialchars(__('Dark theme'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Dark theme'), ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="false">
                        <i class="bi bi-moon-stars" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="sp-console-group" role="group" aria-label="<?php echo htmlspecialchars(__('Adjust font size'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="sp-console-btn" type="button" data-font-size="small" aria-label="<?php echo htmlspecialchars(__('Decrease font size'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Decrease font size'), ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="false">
                        A<sup>-</sup>
                    </button>
                    <button class="sp-console-btn" type="button" data-font-size="normal" aria-label="<?php echo htmlspecialchars(__('Default font size'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Default font size'), ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="true">
                        A
                    </button>
                    <button class="sp-console-btn" type="button" data-font-size="large" aria-label="<?php echo htmlspecialchars(__('Increase font size'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Increase font size'), ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="false">
                        A<sup>+</sup>
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

<script id="spcast-i18n" type="application/json"><?php echo json_encode($clientI18n, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/chart.js"></script>
<script src="js/app.js"></script>
