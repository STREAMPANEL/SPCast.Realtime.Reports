(function () {
    "use strict";

    var i18n = {
        users: "Users",
        count: "Count",
        share: "Share",
        rank: "Rank",
        value: "Entry",
        noData: "No statistics available for the selected period yet.",
        loading: "Loading statistics...",
        errorLoad: "Unable to load statistics at the moment.",
        numberOfUsers: "Number of users",
        paused: "Paused",
        resume: "Resume auto-refresh",
        pause: "Pause auto-refresh",
        systemCurrentlyDark: "System (currently dark)",
        systemCurrentlyLight: "System (currently light)",
        showingTop: "Top 15",
        showingAll: "All entries",
        totalEvents: "Total events",
        uniqueEntries: "Unique entries",
        topEntry: "Top entry",
        peakToday: "Peak Today"
    };

    var themeListeners = [];
    var refreshCallback = null;

    function loadI18n() {
        var el = document.getElementById("spcast-i18n");
        if (!el) return;
        try {
            var parsed = JSON.parse(el.textContent || "{}");
            if (parsed && typeof parsed === "object") {
                Object.keys(parsed).forEach(function (k) {
                    if (typeof parsed[k] === "string" && parsed[k] !== "") {
                        i18n[k] = parsed[k];
                    }
                });
            }
        } catch (e) {}
    }

    function getStoredScheme() {
        try {
            var v = localStorage.getItem("color-scheme");
            return (v === "light" || v === "dark") ? v : null;
        } catch (e) {
            return null;
        }
    }

    function getSystemScheme() {
        return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }

    function getResolvedTheme() {
        return getStoredScheme() || getSystemScheme();
    }

    function applyColorSchemeState() {
        var stored = getStoredScheme();
        var meta = document.querySelector('meta[name="color-scheme"]');
        if (meta) {
            meta.content = stored || "light dark";
        }

        var resolved = stored || getSystemScheme();
        document.documentElement.setAttribute("data-bs-theme", resolved);

        var choice = stored || "system";
        var buttons = document.querySelectorAll("[data-theme-choice]");
        buttons.forEach(function (btn) {
            var btnChoice = btn.getAttribute("data-theme-choice");
            btn.setAttribute("aria-pressed", btnChoice === choice ? "true" : "false");
            if (btnChoice === "system") {
                var sysText = getSystemScheme() === "dark" ? i18n.systemCurrentlyDark : i18n.systemCurrentlyLight;
                btn.setAttribute("title", sysText);
                btn.setAttribute("aria-label", sysText);
            }
        });

        themeListeners.forEach(function (fn) {
            try {
                fn(resolved);
            } catch (e) {}
        });
    }

    function applyFontSizeState(size) {
        var valid = (size === "small" || size === "normal" || size === "large") ? size : "normal";
        document.documentElement.setAttribute("data-font-size", valid);
        var buttons = document.querySelectorAll("[data-font-size]");
        buttons.forEach(function (btn) {
            btn.setAttribute("aria-pressed", btn.getAttribute("data-font-size") === valid ? "true" : "false");
        });
    }

    function initThemeAndA11y() {
        applyColorSchemeState();

        var initialFs = "normal";
        try {
            initialFs = localStorage.getItem("spcast-font-size") || "normal";
        } catch (e) {}
        applyFontSizeState(initialFs);

        document.querySelectorAll("[data-theme-choice]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var targetChoice = btn.getAttribute("data-theme-choice");
                try {
                    if (targetChoice === "light" || targetChoice === "dark") {
                        localStorage.setItem("color-scheme", targetChoice);
                    } else {
                        localStorage.removeItem("color-scheme");
                    }
                } catch (e) {}
                applyColorSchemeState();
            });
        });

        document.querySelectorAll("[data-font-size]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var fs = btn.getAttribute("data-font-size") || "normal";
                try {
                    if (fs === "normal") {
                        localStorage.removeItem("spcast-font-size");
                    } else {
                        localStorage.setItem("spcast-font-size", fs);
                    }
                } catch (e) {}
                applyFontSizeState(fs);
            });
        });

        var langSelect = document.getElementById("spLangSelect");
        if (langSelect && langSelect.form) {
            langSelect.addEventListener("change", function () {
                langSelect.form.submit();
            });
        }

        if (window.matchMedia) {
            var mql = window.matchMedia("(prefers-color-scheme: dark)");
            if (mql.addEventListener) {
                mql.addEventListener("change", function () {
                    applyColorSchemeState();
                });
            }
        }

        window.addEventListener("storage", function (ev) {
            if (ev.key === "color-scheme" || ev.key === null) {
                applyColorSchemeState();
            }
            if (ev.key === "spcast-font-size" || ev.key === null) {
                applyFontSizeState(ev.newValue || "normal");
            }
        });
    }

    function getThemePalette() {
        var isDark = getResolvedTheme() === "dark";
        if (isDark) {
            return {
                accent: "#49f2c2",
                secondary: "#6aa7ff",
                barTop: "rgba(73, 242, 194, 0.82)",
                barBottom: "rgba(106, 167, 255, 0.25)",
                lineFillTop: "rgba(73, 242, 194, 0.28)",
                lineFillBottom: "rgba(73, 242, 194, 0.02)",
                grid: "rgba(255, 255, 255, 0.08)",
                text: "#cbd5e1",
                muted: "#9eacc1",
                tooltipBg: "#0a0f1d",
                tooltipTitle: "#ffffff",
                tooltipBorder: "rgba(73, 242, 194, 0.45)"
            };
        }
        return {
            accent: "#00775b",
            secondary: "#0b6bcb",
            barTop: "rgba(0, 119, 91, 0.82)",
            barBottom: "rgba(11, 107, 203, 0.25)",
            lineFillTop: "rgba(0, 119, 91, 0.22)",
            lineFillBottom: "rgba(0, 119, 91, 0.02)",
            grid: "rgba(15, 23, 42, 0.08)",
            text: "#1e293b",
            muted: "#475569",
            tooltipBg: "#ffffff",
            tooltipTitle: "#0b1020",
            tooltipBorder: "#00775b"
        };
    }

    function createFaqLinks(data, category) {
        return Object.keys(data || {}).map(function (label) {
            return {
                label: label,
                url: category
                    ? "https://www.spcast.eu/faq/statistik/user" + encodeURIComponent(category) + "/" + encodeURIComponent(label.toLowerCase()) + "/"
                    : null
            };
        });
    }

    function createBarChart(ctx, labels, data, datasetLabel, backgroundColor, borderColor, xAxisLabel, scaleType) {
        if (!ctx) return null;
        var palette = getThemePalette();
        var canvas = ctx.canvas;
        var grad = ctx.createLinearGradient(0, 0, 0, canvas.height || 340);
        grad.addColorStop(0, backgroundColor || palette.barTop);
        grad.addColorStop(1, palette.barBottom);

        var hasPositive = Array.isArray(data) && data.some(function (v) { return Number(v) > 0; });
        var effectiveScale = (scaleType === "logarithmic" && hasPositive) ? "logarithmic" : "linear";

        return new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels.map(function (item) { return typeof item === "string" ? item : item.label; }),
                datasets: [{
                    label: datasetLabel,
                    data: data,
                    backgroundColor: grad,
                    borderColor: borderColor || palette.accent,
                    borderWidth: 1.5,
                    borderRadius: 5,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: function (event, elements) {
                    if (elements.length > 0) {
                        var idx = elements[0].index;
                        var entry = labels[idx];
                        if (entry && typeof entry === "object" && entry.url) {
                            window.open(entry.url, "_blank", "noopener,noreferrer");
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: { color: palette.text }
                    },
                    tooltip: {
                        backgroundColor: palette.tooltipBg,
                        titleColor: palette.tooltipTitle,
                        bodyColor: palette.text,
                        borderColor: palette.tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function (context) {
                                return Number(context.raw || 0).toLocaleString() + " " + i18n.users;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: Boolean(xAxisLabel || datasetLabel),
                            text: xAxisLabel || datasetLabel,
                            color: palette.muted
                        },
                        ticks: {
                            color: palette.muted,
                            autoSkip: true,
                            maxRotation: 40
                        },
                        grid: { color: palette.grid }
                    },
                    y: effectiveScale === "logarithmic" ? {
                        type: "logarithmic",
                        title: {
                            display: true,
                            text: i18n.numberOfUsers,
                            color: palette.muted
                        },
                        ticks: {
                            color: palette.muted,
                            callback: function (value) {
                                var logVal = Math.log10(Number(value));
                                if (Number.isInteger(logVal)) {
                                    return Number(value).toLocaleString();
                                }
                                return "";
                            }
                        },
                        grid: { color: palette.grid }
                    } : {
                        type: "linear",
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: i18n.numberOfUsers,
                            color: palette.muted
                        },
                        ticks: {
                            color: palette.muted,
                            precision: 0
                        },
                        grid: { color: palette.grid }
                    }
                }
            }
        });
    }

    function createSpLineChart(ctx, labels, data, datasetLabel, xAxisLabel, yAxisLabel) {
        if (!ctx) return null;
        var palette = getThemePalette();
        var canvas = ctx.canvas;
        var grad = ctx.createLinearGradient(0, 0, 0, canvas.height || 340);
        grad.addColorStop(0, palette.lineFillTop);
        grad.addColorStop(1, palette.lineFillBottom);

        return new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel,
                    data: data,
                    backgroundColor: grad,
                    borderColor: palette.accent,
                    pointBackgroundColor: palette.accent,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.28,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: { color: palette.text }
                    },
                    tooltip: {
                        backgroundColor: palette.tooltipBg,
                        titleColor: palette.tooltipTitle,
                        bodyColor: palette.text,
                        borderColor: palette.tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function (context) {
                                return Number(context.raw || 0).toLocaleString() + " " + i18n.users;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: Boolean(xAxisLabel),
                            text: xAxisLabel,
                            color: palette.muted
                        },
                        ticks: { color: palette.muted, autoSkip: true },
                        grid: { color: palette.grid }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: Boolean(yAxisLabel),
                            text: yAxisLabel,
                            color: palette.muted
                        },
                        ticks: { color: palette.muted, precision: 0 },
                        grid: { color: palette.grid }
                    }
                }
            }
        });
    }

    // Keep global helpers available for legacy callers
    window.createFaqLinks = createFaqLinks;
    window.createBarChart = createBarChart;

    function renderRankingRows(tbody, entries, totalSum, faqCategory, filterQuery) {
        if (!tbody) return;
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }

        var normalizedQuery = (filterQuery || "").trim().toLowerCase();
        var filtered = entries.filter(function (entry) {
            return normalizedQuery === "" || entry.label.toLowerCase().indexOf(normalizedQuery) !== -1;
        });

        if (filtered.length === 0) {
            var emptyRow = document.createElement("tr");
            var emptyCell = document.createElement("td");
            emptyCell.colSpan = 3;
            emptyCell.className = "text-center py-4";
            emptyCell.style.color = "var(--sp-muted)";
            emptyCell.textContent = i18n.noData;
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
            return;
        }

        filtered.forEach(function (entry) {
            var tr = document.createElement("tr");

            var th = document.createElement("th");
            th.scope = "row";

            var rankBadge = document.createElement("span");
            rankBadge.className = "sp-badge-count me-2";
            rankBadge.textContent = "#" + entry.rank;
            th.appendChild(rankBadge);

            if (faqCategory) {
                var link = document.createElement("a");
                link.href = "https://www.spcast.eu/faq/statistik/user" + encodeURIComponent(faqCategory) + "/" + encodeURIComponent(entry.label.toLowerCase()) + "/";
                link.target = "_blank";
                link.rel = "noopener noreferrer";
                link.className = "text-decoration-none";
                link.textContent = entry.label;
                th.appendChild(link);
            } else {
                var span = document.createElement("span");
                span.textContent = entry.label;
                th.appendChild(span);
            }

            var tdCount = document.createElement("td");
            tdCount.className = "text-end fw-semibold";
            tdCount.style.fontVariantNumeric = "tabular-nums";
            tdCount.textContent = Number(entry.count).toLocaleString();

            var pct = totalSum > 0 ? (entry.count / totalSum) * 100 : 0;
            var tdShare = document.createElement("td");
            var shareWrap = document.createElement("div");
            shareWrap.className = "sp-share-bar";

            var track = document.createElement("div");
            track.className = "sp-share-track";
            var fill = document.createElement("div");
            fill.className = "sp-share-fill";
            fill.style.width = Math.min(100, Math.max(0, pct)).toFixed(1) + "%";
            track.appendChild(fill);

            var pctLabel = document.createElement("span");
            pctLabel.className = "small";
            pctLabel.style.minWidth = "3.1rem";
            pctLabel.style.textAlign = "right";
            pctLabel.style.fontVariantNumeric = "tabular-nums";
            pctLabel.textContent = pct.toFixed(1) + "%";

            shareWrap.appendChild(track);
            shareWrap.appendChild(pctLabel);
            tdShare.appendChild(shareWrap);

            tr.appendChild(th);
            tr.appendChild(tdCount);
            tr.appendChild(tdShare);
            tbody.appendChild(tr);
        });
    }

    function mapObjectToEntries(dataMap) {
        var keys = Object.keys(dataMap || {});
        var list = keys.map(function (k) {
            return { label: k, count: Number(dataMap[k] || 0) };
        });
        list.sort(function (a, b) { return b.count - a.count; });
        list.forEach(function (item, idx) {
            item.rank = idx + 1;
        });
        return list;
    }

    function initCountdownController() {
        var countdownEl = document.getElementById("countdown");
        if (!countdownEl) return;

        var pauseBtn = document.getElementById("spPauseRefreshBtn");
        var manualBtn = document.getElementById("spManualRefreshBtn");
        var unitEl = document.getElementById("countdownUnit");

        var remaining = 45;
        var isPaused = false;
        var isRefreshing = false;

        function triggerRefresh() {
            if (isRefreshing) return;
            if (typeof refreshCallback === "function") {
                isRefreshing = true;
                Promise.resolve(refreshCallback()).finally(function () {
                    isRefreshing = false;
                    remaining = 45;
                    if (!isPaused) {
                        countdownEl.textContent = String(remaining);
                    }
                });
            } else {
                window.location.reload();
            }
        }

        if (pauseBtn) {
            pauseBtn.addEventListener("click", function () {
                isPaused = !isPaused;
                pauseBtn.setAttribute("aria-pressed", isPaused ? "true" : "false");
                var label = isPaused ? i18n.resume : i18n.pause;
                pauseBtn.setAttribute("aria-label", label);
                pauseBtn.setAttribute("title", label);

                var icon = pauseBtn.querySelector("i");
                if (icon) {
                    icon.className = isPaused ? "bi bi-play-fill" : "bi bi-pause-fill";
                }
                if (isPaused) {
                    countdownEl.textContent = i18n.paused;
                    if (unitEl) unitEl.classList.add("d-none");
                } else {
                    countdownEl.textContent = String(remaining);
                    if (unitEl) unitEl.classList.remove("d-none");
                }
            });
        }

        if (manualBtn) {
            manualBtn.addEventListener("click", function () {
                remaining = 45;
                if (!isPaused) {
                    countdownEl.textContent = String(remaining);
                }
                triggerRefresh();
            });
        }

        setInterval(function () {
            if (isPaused || isRefreshing) return;
            remaining -= 1;
            if (remaining <= 0) {
                triggerRefresh();
            } else {
                countdownEl.textContent = String(remaining);
            }
        }, 1000);
    }

    function initHomepageHub() {
        var hubEl = document.getElementById("spStatsHub");
        if (!hubEl) return;

        var activeType = hubEl.getAttribute("data-initial-type") || "country";
        var activePeriod = hubEl.getAttribute("data-initial-period") || "today";
        var scaleMode = "logarithmic";
        var limitCount = 15;
        var filterQuery = "";

        var reportCache = {};
        var chartInstance = null;
        var currentController = null;

        var detailLink = document.getElementById("hubDetailLink");
        var descEl = document.getElementById("hubCategoryDesc");
        var periodGroup = document.getElementById("hubPeriodGroup");
        var dimControls = document.getElementById("hubDimensionControls");
        var searchInput = document.getElementById("hubTableSearch");
        var loadingOverlay = document.getElementById("hubLoadingOverlay");
        var emptyBox = document.getElementById("hubChartEmpty");
        var emptyMessage = document.getElementById("hubEmptyMessage");
        var canvas = document.getElementById("hubMainChart");
        var rankingBody = document.getElementById("hubRankingBody");
        var col1Header = document.getElementById("hubCol1Header");
        var col2Header = document.getElementById("hubCol2Header");

        var stat1Label = document.getElementById("hubStat1Label");
        var stat1Val = document.getElementById("hubStat1Value");
        var stat2Label = document.getElementById("hubStat2Label");
        var stat2Val = document.getElementById("hubStat2Value");
        var stat3Label = document.getElementById("hubStat3Label");
        var stat3Val = document.getElementById("hubStat3Value");

        function updatePulseUI(pulse) {
            if (!pulse) return;
            var inEl = document.getElementById("pulseInbound");
            var outEl = document.getElementById("pulseOutbound");
            var netEl = document.getElementById("pulseNetFlow");
            if (inEl) inEl.textContent = Number(pulse.inbound || 0).toLocaleString();
            if (outEl) outEl.textContent = Number(pulse.outbound || 0).toLocaleString();
            if (netEl) {
                var net = Number(pulse.netFlow || 0);
                netEl.textContent = (net > 0 ? "+" : "") + net.toLocaleString();
            }
        }

        function fetchPulse() {
            return fetch("api.php?action=pulse", { headers: { Accept: "application/json" } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.ok && res.pulse) {
                        updatePulseUI(res.pulse);
                    }
                })
                .catch(function () {});
        }

        function syncUrlState() {
            if (!window.history || !window.history.replaceState) return;
            var url = new URL(window.location.href);
            url.searchParams.set("stat", activeType);
            url.searchParams.set("period", activePeriod);
            window.history.replaceState({}, "", url.toString());
        }

        function updateControlStates(scaleOverride) {
            if (scaleOverride) {
                scaleMode = scaleOverride;
            }
            hubEl.querySelectorAll("[data-scale-mode]").forEach(function (btn) {
                btn.setAttribute("aria-pressed", btn.getAttribute("data-scale-mode") === scaleMode ? "true" : "false");
            });
            hubEl.querySelectorAll("[data-limit-mode]").forEach(function (btn) {
                btn.setAttribute("aria-pressed", Number(btn.getAttribute("data-limit-mode")) === limitCount ? "true" : "false");
            });
        }

        function rebuildPeriodButtons(payload) {
            if (!periodGroup) return;
            while (periodGroup.firstChild) {
                periodGroup.removeChild(periodGroup.firstChild);
            }

            var periodDefs = [];
            if (payload.kind === "inout") {
                Object.keys(payload.periods || {}).forEach(function (k) {
                    periodDefs.push({ id: k, label: payload.periods[k].label || k });
                });
            } else {
                var labelsMap = {
                    today: periodGroup.getAttribute("data-lbl-today") || "Today",
                    yesterday: periodGroup.getAttribute("data-lbl-yesterday") || "Yesterday",
                    "7days": periodGroup.getAttribute("data-lbl-7days") || "7d",
                    "30days": periodGroup.getAttribute("data-lbl-30days") || "30d"
                };
                ["today", "yesterday", "7days", "30days"].forEach(function (k) {
                    periodDefs.push({ id: k, label: labelsMap[k] });
                });
            }

            if (!periodDefs.some(function (d) { return d.id === activePeriod; })) {
                activePeriod = periodDefs[0] ? periodDefs[0].id : "today";
            }

            periodDefs.forEach(function (def) {
                var btn = document.createElement("button");
                btn.type = "button";
                btn.className = "sp-btn-ghost" + (def.id === activePeriod ? " active" : "");
                btn.setAttribute("data-hub-period", def.id);
                btn.textContent = def.label;
                btn.addEventListener("click", function () {
                    activePeriod = def.id;
                    syncUrlState();
                    renderActivePayload(payload);
                });
                periodGroup.appendChild(btn);
            });
        }

        // Store server-rendered period labels before rebuilding buttons
        if (periodGroup) {
            var initialBtns = periodGroup.querySelectorAll("[data-hub-period]");
            initialBtns.forEach(function (b) {
                periodGroup.setAttribute("data-lbl-" + b.getAttribute("data-hub-period"), b.textContent.trim());
            });
        }

        function renderActivePayload(payload) {
            if (!payload || !canvas) return;

            if (detailLink && typeof payload.pageUrl === "string" && /^[a-z0-9_-]+\.php$/i.test(payload.pageUrl)) {
                detailLink.href = payload.pageUrl;
            }
            if (descEl && payload.description) {
                descEl.textContent = payload.description;
            }

            rebuildPeriodButtons(payload);

            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            if (payload.kind === "inout") {
                if (dimControls) {
                    dimControls.querySelectorAll("[data-scale-mode], [data-limit-mode]").forEach(function (b) {
                        b.parentElement.classList.add("d-none");
                    });
                }

                var pData = payload.periods[activePeriod] || payload.periods.realtime;
                var values = (pData && pData.values) ? pData.values : [];
                var labels = (pData && pData.labels) ? pData.labels : [];
                var sum = values.reduce(function (acc, v) { return acc + Number(v || 0); }, 0);
                var peak = values.reduce(function (acc, v) { return Math.max(acc, Number(v || 0)); }, 0);

                if (stat1Label) stat1Label.textContent = payload.title + " (30m)";
                if (stat1Val) stat1Val.textContent = Number(payload.activeUsers || 0).toLocaleString();
                if (stat2Label) stat2Label.textContent = pData.label;
                if (stat2Val) stat2Val.textContent = sum.toLocaleString();
                if (stat3Label) stat3Label.textContent = i18n.peakToday;
                if (stat3Val) stat3Val.textContent = peak.toLocaleString();
                if (col1Header) col1Header.textContent = pData.xAxisLabel || i18n.value;
                if (col2Header) col2Header.textContent = i18n.users;

                emptyBox.classList.add("d-none");
                canvas.classList.remove("d-none");
                chartInstance = createSpLineChart(
                    canvas.getContext("2d"),
                    labels,
                    values,
                    pData.datasetLabel,
                    pData.xAxisLabel,
                    pData.yAxisLabel
                );

                var seriesEntries = labels.map(function (lbl, idx) {
                    return {
                        rank: idx + 1,
                        label: String(lbl),
                        count: Number(values[idx] || 0)
                    };
                });
                renderRankingRows(rankingBody, seriesEntries, sum, null, filterQuery);
                return;
            }

            if (dimControls) {
                dimControls.querySelectorAll("[data-scale-mode], [data-limit-mode]").forEach(function (b) {
                    b.parentElement.classList.remove("d-none");
                });
            }

            var mapData = (payload.periods && payload.periods[activePeriod]) ? payload.periods[activePeriod] : {};
            var entries = mapObjectToEntries(mapData);
            var totalEvents = entries.reduce(function (acc, item) { return acc + item.count; }, 0);

            if (stat1Label) stat1Label.textContent = i18n.totalEvents;
            if (stat1Val) stat1Val.textContent = totalEvents.toLocaleString();
            if (stat2Label) stat2Label.textContent = i18n.uniqueEntries;
            if (stat2Val) stat2Val.textContent = entries.length.toLocaleString();
            if (stat3Label) stat3Label.textContent = i18n.topEntry;
            if (stat3Val) {
                stat3Val.textContent = entries.length > 0
                    ? entries[0].label + " (" + entries[0].count.toLocaleString() + ")"
                    : "-";
            }
            if (col1Header) col1Header.textContent = payload.axisLabel || i18n.value;
            if (col2Header) col2Header.textContent = i18n.count;

            renderRankingRows(rankingBody, entries, totalEvents, payload.faqLinkType, filterQuery);

            if (entries.length === 0) {
                canvas.classList.add("d-none");
                emptyMessage.textContent = i18n.noData;
                emptyBox.classList.remove("d-none");
                return;
            }

            emptyBox.classList.add("d-none");
            canvas.classList.remove("d-none");

            var sliced = limitCount > 0 ? entries.slice(0, limitCount) : entries;
            var slicedMap = {};
            sliced.forEach(function (item) { slicedMap[item.label] = item.count; });
            var faqLabels = createFaqLinks(slicedMap, payload.faqLinkType);
            var counts = sliced.map(function (item) { return item.count; });

            chartInstance = createBarChart(
                canvas.getContext("2d"),
                faqLabels,
                counts,
                payload.axisLabel || payload.title,
                null,
                null,
                payload.axisLabel || payload.title,
                scaleMode
            );
        }

        function loadCategory(type, forceNetwork) {
            activeType = type;

            hubEl.querySelectorAll("[data-hub-type]").forEach(function (btn) {
                var isMatch = btn.getAttribute("data-hub-type") === type;
                btn.classList.toggle("active", isMatch);
                btn.setAttribute("aria-selected", isMatch ? "true" : "false");
            });

            if (!forceNetwork && reportCache[type]) {
                updateControlStates(reportCache[type].defaultScale || "logarithmic");
                syncUrlState();
                renderActivePayload(reportCache[type]);
                return Promise.resolve();
            }

            if (currentController) {
                currentController.abort();
            }
            currentController = new AbortController();

            if (loadingOverlay) loadingOverlay.classList.remove("d-none");

            return fetch("api.php?action=report&type=" + encodeURIComponent(type), {
                headers: { Accept: "application/json" },
                signal: currentController.signal
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (loadingOverlay) loadingOverlay.classList.add("d-none");
                    if (!res || !res.ok) {
                        throw new Error((res && res.error) ? res.error : i18n.errorLoad);
                    }
                    var isFirstLoadForType = !reportCache[type];
                    reportCache[type] = res;
                    if (isFirstLoadForType) {
                        updateControlStates(res.defaultScale || "logarithmic");
                    }
                    syncUrlState();
                    renderActivePayload(res);
                })
                .catch(function (err) {
                    if (err && err.name === "AbortError") return;
                    if (loadingOverlay) loadingOverlay.classList.add("d-none");
                    if (canvas) canvas.classList.add("d-none");
                    if (emptyMessage) emptyMessage.textContent = i18n.errorLoad;
                    if (emptyBox) emptyBox.classList.remove("d-none");
                });
        }

        hubEl.querySelectorAll("[data-hub-type]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var nextType = btn.getAttribute("data-hub-type");
                if (!nextType) return;
                if (nextType === "in" || nextType === "out") {
                    if (["realtime", "minutes", "weekly", "monthly"].indexOf(activePeriod) === -1) {
                        activePeriod = "realtime";
                    }
                } else if (["today", "yesterday", "7days", "30days"].indexOf(activePeriod) === -1) {
                    activePeriod = "today";
                }
                loadCategory(nextType, false);
            });
        });

        hubEl.querySelectorAll("[data-scale-mode]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                scaleMode = btn.getAttribute("data-scale-mode") || "linear";
                updateControlStates();
                if (reportCache[activeType]) {
                    renderActivePayload(reportCache[activeType]);
                }
            });
        });

        hubEl.querySelectorAll("[data-limit-mode]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                limitCount = Number(btn.getAttribute("data-limit-mode") || 0);
                updateControlStates();
                if (reportCache[activeType]) {
                    renderActivePayload(reportCache[activeType]);
                }
            });
        });

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                filterQuery = searchInput.value || "";
                if (reportCache[activeType]) {
                    renderActivePayload(reportCache[activeType]);
                }
            });
        }

        themeListeners.push(function () {
            if (reportCache[activeType]) {
                renderActivePayload(reportCache[activeType]);
            }
        });

        refreshCallback = function () {
            reportCache = {};
            return Promise.all([fetchPulse(), loadCategory(activeType, true)]);
        };

        fetchPulse();
        loadCategory(activeType, false);
    }

    function initStandaloneReportPage() {
        var container = document.getElementById("spStandaloneReport");
        var payloadEl = document.getElementById("spcast-report-payload");
        if (!container || !payloadEl) return;

        var payload = null;
        try {
            payload = JSON.parse(payloadEl.textContent || "{}");
        } catch (e) {
            return;
        }

        var activePeriod = "today";
        var scaleMode = payload.defaultScale || "logarithmic";
        var limitCount = 15;
        var filterQuery = "";
        var chartInstance = null;

        var titleEl = document.getElementById("standaloneChartTitle");
        var canvas = document.getElementById("standaloneReportChart");
        var emptyBox = document.getElementById("standaloneChartEmpty");
        var tbody = document.getElementById("standaloneRankingBody");
        var searchInput = document.getElementById("standaloneTableSearch");

        var statTotal = document.getElementById("reportStatTotal");
        var statUnique = document.getElementById("reportStatUnique");
        var statTop = document.getElementById("reportStatTop");

        function renderCurrent() {
            if (!canvas) return;
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            if (titleEl && payload.chartTitles && payload.chartTitles[activePeriod]) {
                titleEl.textContent = payload.chartTitles[activePeriod];
            }

            var mapData = (payload.periods && payload.periods[activePeriod]) ? payload.periods[activePeriod] : {};
            var entries = mapObjectToEntries(mapData);
            var totalEvents = entries.reduce(function (acc, item) { return acc + item.count; }, 0);

            if (statTotal) statTotal.textContent = totalEvents.toLocaleString();
            if (statUnique) statUnique.textContent = entries.length.toLocaleString();
            if (statTop) {
                statTop.textContent = entries.length > 0
                    ? entries[0].label + " (" + entries[0].count.toLocaleString() + ")"
                    : "-";
            }

            Object.keys(payload.periods || {}).forEach(function (pKey) {
                var badge = container.querySelector('[data-period-badge="' + pKey + '"]');
                if (badge) {
                    badge.textContent = String(Object.keys(payload.periods[pKey] || {}).length);
                }
            });

            renderRankingRows(tbody, entries, totalEvents, payload.faqLinkType, filterQuery);

            if (entries.length === 0) {
                canvas.classList.add("d-none");
                if (emptyBox) emptyBox.classList.remove("d-none");
                return;
            }

            if (emptyBox) emptyBox.classList.add("d-none");
            canvas.classList.remove("d-none");

            var sliced = limitCount > 0 ? entries.slice(0, limitCount) : entries;
            var slicedMap = {};
            sliced.forEach(function (item) { slicedMap[item.label] = item.count; });
            var faqLabels = createFaqLinks(slicedMap, payload.faqLinkType);
            var counts = sliced.map(function (item) { return item.count; });

            chartInstance = createBarChart(
                canvas.getContext("2d"),
                faqLabels,
                counts,
                payload.axisLabel,
                null,
                null,
                payload.axisLabel,
                scaleMode
            );
        }

        container.querySelectorAll("[data-report-period]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                activePeriod = btn.getAttribute("data-report-period") || "today";
                container.querySelectorAll("[data-report-period]").forEach(function (b) {
                    var active = b === btn;
                    b.classList.toggle("active", active);
                    b.setAttribute("aria-selected", active ? "true" : "false");
                });
                renderCurrent();
            });
        });

        container.querySelectorAll("[data-scale-mode]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                scaleMode = btn.getAttribute("data-scale-mode") || "linear";
                container.querySelectorAll("[data-scale-mode]").forEach(function (b) {
                    b.setAttribute("aria-pressed", b === btn ? "true" : "false");
                });
                renderCurrent();
            });
        });

        container.querySelectorAll("[data-limit-mode]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                limitCount = Number(btn.getAttribute("data-limit-mode") || 0);
                container.querySelectorAll("[data-limit-mode]").forEach(function (b) {
                    b.setAttribute("aria-pressed", b === btn ? "true" : "false");
                });
                renderCurrent();
            });
        });

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                filterQuery = searchInput.value || "";
                renderCurrent();
            });
        }

        themeListeners.push(renderCurrent);

        refreshCallback = function () {
            return fetch("api.php?action=report&type=" + encodeURIComponent(payload.type), {
                headers: { Accept: "application/json" }
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.ok && res.periods) {
                        payload.periods = res.periods;
                        renderCurrent();
                    }
                })
                .catch(function () {});
        };

        renderCurrent();
    }

    function initStandaloneInOutPage() {
        var container = document.getElementById("spStandaloneInOut");
        var payloadEl = document.getElementById("spcast-inout-payload");
        if (!container || !payloadEl) return;

        var payload = null;
        try {
            payload = JSON.parse(payloadEl.textContent || "{}");
        } catch (e) {
            return;
        }

        var activePeriod = "realtime";
        var chartInstance = null;
        var canvas = document.getElementById("standaloneInOutChart");
        var tbody = document.getElementById("standaloneInOutBody");
        var timeHeader = document.getElementById("inoutTimeColHeader");

        function renderCurrent() {
            if (!canvas || !payload.periods) return;
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            var activeUsersEl = document.getElementById("activeUsers");
            var peakEl = document.getElementById("inoutPeakToday");
            var weeklyEl = document.getElementById("inoutWeeklyTotal");

            if (activeUsersEl) {
                activeUsersEl.textContent = Number(payload.activeUsers || 0).toLocaleString();
            }
            if (peakEl && payload.periods.realtime && Array.isArray(payload.periods.realtime.values)) {
                var peak = payload.periods.realtime.values.reduce(function (m, v) { return Math.max(m, Number(v || 0)); }, 0);
                peakEl.textContent = peak.toLocaleString();
            }
            if (weeklyEl && payload.periods.weekly && Array.isArray(payload.periods.weekly.values)) {
                var wSum = payload.periods.weekly.values.reduce(function (s, v) { return s + Number(v || 0); }, 0);
                weeklyEl.textContent = wSum.toLocaleString();
            }

            var pData = payload.periods[activePeriod] || payload.periods.realtime;
            if (!pData) return;

            if (timeHeader && pData.xAxisLabel) {
                timeHeader.textContent = pData.xAxisLabel;
            }

            chartInstance = createSpLineChart(
                canvas.getContext("2d"),
                pData.labels || [],
                pData.values || [],
                pData.datasetLabel || "",
                pData.xAxisLabel || "",
                pData.yAxisLabel || ""
            );

            var sum = (pData.values || []).reduce(function (acc, v) { return acc + Number(v || 0); }, 0);
            var rows = (pData.labels || []).map(function (lbl, idx) {
                return {
                    rank: idx + 1,
                    label: String(lbl),
                    count: Number(pData.values[idx] || 0)
                };
            });
            renderRankingRows(tbody, rows, sum, null, "");
        }

        container.querySelectorAll("[data-inout-period]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                activePeriod = btn.getAttribute("data-inout-period") || "realtime";
                container.querySelectorAll("[data-inout-period]").forEach(function (b) {
                    var active = b === btn;
                    b.classList.toggle("active", active);
                    b.setAttribute("aria-selected", active ? "true" : "false");
                });
                renderCurrent();
            });
        });

        themeListeners.push(renderCurrent);

        refreshCallback = function () {
            return fetch("api.php?action=report&type=" + encodeURIComponent(payload.type), {
                headers: { Accept: "application/json" }
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.ok && res.periods) {
                        payload.activeUsers = res.activeUsers;
                        payload.periods = res.periods;
                        renderCurrent();
                    }
                })
                .catch(function () {});
        };

        renderCurrent();
    }

    document.addEventListener("DOMContentLoaded", function () {
        loadI18n();
        initThemeAndA11y();
        initHomepageHub();
        initStandaloneReportPage();
        initStandaloneInOutPage();
        initCountdownController();
    });
})();
