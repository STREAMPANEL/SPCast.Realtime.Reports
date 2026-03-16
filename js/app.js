// js/app.js

document.addEventListener("DOMContentLoaded", function () {
    // 1. Countdown Logic
    let countdown = 45;
    const countdownElement = document.getElementById('countdown');

    if (countdownElement) {
        function updateCountdown() {
            countdown--;
            if (countdown <= 0) {
                location.reload();
            } else {
                countdownElement.innerText = countdown;
            }
        }
        setInterval(updateCountdown, 1000);
    }
});

// 2. FAQ Links Generator
function createFaqLinks(data, category) {
    return Object.keys(data).map(label => {
        return {
            label: label,
            url: `https://www.spcast.eu/faq/statistik/user${category}/${encodeURIComponent(label.toLowerCase())}/`
        };
    });
}

// 3. Chart Generator
function createBarChart(ctx, labels, data, datasetLabel, backgroundColor, borderColor, xAxisLabel) {
    if (!ctx) return null;
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.map(labelObj => labelObj.label),
            datasets: [{
                label: datasetLabel,
                data: data,
                backgroundColor: backgroundColor,
                borderColor: borderColor,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    if (labels[index] && labels[index].url) {
                        window.open(labels[index].url, '_blank', 'noopener,noreferrer');
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    onClick: (e, legendItem, legend) => {
                        const index = legendItem.datasetIndex;
                        const chart = legend.chart;
                        const meta = chart.getDatasetMeta(index);

                        meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
                        chart.update();
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.raw + ' Nutzer';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: xAxisLabel || datasetLabel
                    },
                    ticks: {
                        autoSkip: true
                    }
                },
                y: {
                    type: 'logarithmic',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Anzahl der Nutzer'
                    },
                    ticks: {
                        callback: function (value) {
                            if (Number.isInteger(Math.log10(value))) {
                                return value;
                            }
                        }
                    }
                }
            }
        }
    });
}
