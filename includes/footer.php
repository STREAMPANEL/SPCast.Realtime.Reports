<?php declare(strict_types=1); ?>
<!-- Bootstrap JS und Popper.js -->
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<!-- Footer -->
<footer class="bg-light text-center text-lg-start mt-5">
    <div class="container p-4">
        <ul class="d-flex flex-wrap justify-content-center align-items-center gap-3 list-unstyled mb-0">
            <li>
                <a href="https://www.spcast.eu/kontakt/impressum/" target="_blank" rel="noopener" class="text-dark text-decoration-none"><?php echo htmlspecialchars(__('Imprint')); ?></a>
            </li>
            <li class="d-none d-sm-block text-muted">|</li>
            <li>
                <a href="https://www.spcast.eu/kontakt/datenschutz/" target="_blank" rel="noopener" class="text-dark text-decoration-none"><?php echo htmlspecialchars(__('Privacy Policy')); ?></a>
            </li>
            <li class="d-none d-sm-block text-muted">|</li>
            <li>
                <a href="https://www.spcast.eu/kontakt/cookies/" target="_blank" rel="noopener" class="text-dark text-decoration-none"><?php echo htmlspecialchars(__('Cookies')); ?></a>
            </li>
            <li class="d-none d-sm-block text-muted">|</li>
            <li>
                <a href="https://github.com/STREAMPANEL/SPCast.Realtime.Reports" target="_blank" rel="noopener" class="text-dark text-decoration-none">Github</a>
            </li>
            <li>
                <form action="" method="GET" class="d-inline">
                    <?php

                    // Keep existing query parameters
                    $queryParams = $_GET;
                    unset($queryParams['lang']);
                    foreach ($queryParams as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                echo '<input type="hidden" name="' . htmlspecialchars($key . '[' . $k . ']', ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '">';
                            }
                        } else {
                            echo '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
                        }
                    }
                    ?>
                    <select name="lang" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto" aria-label="<?php echo htmlspecialchars(__('Language Selection')); ?>">
                        <?php

                        $lang_names    = [
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
                            echo '<option value="' . htmlspecialchars($k) . '" ' . $sel . '>' . htmlspecialchars($v) . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </li>
        </ul>
    </div>
    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
        &copy;<?php echo date("Y"); ?> <a href="https://www.spcast.eu" target="_blank" rel="noopener" class="text-dark text-decoration-none">SPCast</a> | <a href="https://www.spcast-playlist.de"
            target="_blank" rel="noopener" class="text-dark text-decoration-none">SPCast Playlist</a>
    </div>
</footer>

<!-- Chart.js -->
<script src="js/chart.js"></script>
<script src="js/app.js"></script>