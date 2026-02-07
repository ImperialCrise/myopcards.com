const OPC = {
    palette: ['#ef4444','#3b82f6','#22c55e','#a855f7','#eab308','#06b6d4','#f97316','#ec4899','#6366f1','#14b8a6','#f43f5e','#8b5cf6'],
    chartDefaults: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#8ba4c0', font: { size: 11 }, boxWidth: 12, padding: 8 }
            }
        },
        scales: {
            x: { ticks: { color: '#4a6480' }, grid: { color: 'rgba(74,100,128,0.1)' } },
            y: { ticks: { color: '#4a6480' }, grid: { color: 'rgba(74,100,128,0.1)' } }
        }
    },

    lineChart(ctx, labels, datasets) {
        return new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: { ...this.chartDefaults, interaction: { intersect: false, mode: 'index' } }
        });
    },

    doughnutChart(ctx, labels, data) {
        return new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: this.palette.slice(0, data.length), borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#8ba4c0', boxWidth: 12, padding: 8, font: { size: 11 } } } } }
        });
    },

    barChart(ctx, labels, data, color) {
        return new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ data, backgroundColor: color || '#d4a853', borderWidth: 0, borderRadius: 3 }] },
            options: { ...this.chartDefaults, plugins: { legend: { display: false } } }
        });
    }
};
