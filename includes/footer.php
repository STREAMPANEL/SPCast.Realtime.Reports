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
    'today' => __('Today'),
    'yesterday' => __('Yesterday'),
    'last7Days' => __('Last 7 Days'),
    'last30Days' => __('Last 30 Days'),
    'period' => __('Period'),
    'dailyAverage' => __('Daily Average'),
    'periodComparison' => __('Period Comparison'),
    'shareAcrossPeriods' => __('Share across Periods (%)'),
    'trendVs30d' => __('Trend vs. 30-Day Share'),
    'showEntryDetails' => __('Show cross-period details'),
    'techPortHttp' => __('Standard unencrypted HTTP streaming port'),
    'techPortHttps' => __('TLS-encrypted HTTPS streaming port'),
    'techPortIcecast' => __('Icecast / SHOUTcast default audio streaming port'),
    'techProtoTls' => __('Encrypted transport protocol (TLS)'),
    'techProtoPlain' => __('Unencrypted transport protocol'),
    'techIpv4' => __('Classic 32-bit network addressing (IPv4)'),
    'techIpv6' => __('Modern 128-bit network addressing (IPv6)'),
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

<dialog id="spEntryDetailDialog" class="sp-detail-dialog" closedby="any" aria-labelledby="spDetailDialogTitle">
    <div class="sp-detail-dialog-header">
        <div class="min-w-0">
            <div class="small text-uppercase fw-semibold mb-1" style="color: var(--sp-accent); letter-spacing: 0.04em;" id="spDetailCategoryBadge"></div>
            <h2 class="h5 fw-bold mb-0 text-break" id="spDetailDialogTitle"></h2>
        </div>
        <form method="dialog" class="flex-shrink-0">
            <button type="submit" value="close" class="sp-btn-ghost py-1 px-2" autofocus aria-label="<?php echo htmlspecialchars(__('Close'), ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(__('Close'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </form>
    </div>

    <div class="sp-detail-dialog-body">
        <div id="spDetailTechNote" class="sp-detail-tech-note d-none mb-3">
            <i class="bi bi-info-circle flex-shrink-0" aria-hidden="true"></i>
            <span id="spDetailTechNoteText"></span>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-sm-4">
                <div class="p-2 px-3 rounded-3 border h-100" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                    <div class="small" style="color: var(--sp-muted);"><?php echo htmlspecialchars(__('Rank') . ' (' . __('Today') . ')', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fw-bold fs-5" style="color: var(--sp-heading); font-variant-numeric: tabular-nums;" id="spDetailKpiRank">-</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-2 px-3 rounded-3 border h-100" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                    <div class="small" style="color: var(--sp-muted);"><?php echo htmlspecialchars(__('Share') . ' (' . __('Today') . ')', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fw-bold fs-5" style="color: var(--sp-heading); font-variant-numeric: tabular-nums;" id="spDetailKpiShare">-</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-2 px-3 rounded-3 border h-100" style="background: var(--sp-surface-elevated); border-color: var(--sp-border) !important;">
                    <div class="small" style="color: var(--sp-muted);"><?php echo htmlspecialchars(__('Trend vs. 30-Day Share'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="fw-bold fs-5" style="color: var(--sp-accent); font-variant-numeric: tabular-nums;" id="spDetailKpiTrend">-</div>
                </div>
            </div>
        </div>

        <div class="sp-detail-chart-box mb-3">
            <canvas id="spDetailModalChart"></canvas>
        </div>

        <div tabindex="0" role="region" aria-labelledby="spDetailTableCaption" class="sp-table-wrapper">
            <table class="sp-ranking-table sp-detail-table"
                   style="--label-2: '<?php echo htmlspecialchars(addcslashes(__('Rank'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>'; --label-3: '<?php echo htmlspecialchars(addcslashes(__('Count'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>'; --label-4: '<?php echo htmlspecialchars(addcslashes(__('Daily Average'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>'; --label-5: '<?php echo htmlspecialchars(addcslashes(__('Share'), "'\\"), ENT_QUOTES, 'UTF-8'); ?>';">
                <caption id="spDetailTableCaption"><?php echo htmlspecialchars(__('Period Comparison'), ENT_QUOTES, 'UTF-8'); ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?php echo htmlspecialchars(__('Period'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th scope="col" class="text-end"><?php echo htmlspecialchars(__('Rank'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th scope="col" class="text-end"><?php echo htmlspecialchars(__('Count'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th scope="col" class="text-end"><?php echo htmlspecialchars(__('Daily Average'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th scope="col"><?php echo htmlspecialchars(__('Share'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody id="spDetailPeriodBody"></tbody>
            </table>
        </div>
    </div>
</dialog>

<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" id="spcast-i18n" type="application/json"><?php echo json_encode($clientI18n, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" src="js/popper.min.js"></script>
<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" src="js/bootstrap.min.js"></script>
<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" src="js/chart.js"></script>
<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>" src="js/app.js"></script>
