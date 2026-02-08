function priceChart() {
    var cardSetId = window.__PAGE_DATA ? window.__PAGE_DATA.cardSetId : '';
    return {
        chart: null, days: 90,
        async loadChart() {
            var res = await fetch('/api/cards/price-history/' + encodeURIComponent(cardSetId) + '?days=' + this.days);
            var data = await res.json();
            var ctx = document.getElementById('priceHistoryChart').getContext('2d');
            if (this.chart) this.chart.destroy();

            var series = [
                { key: 'tcgplayer',      label: 'TCGPlayer (USD)', color: '#22c55e' },
                { key: 'cardmarket_en',  label: 'EN (EUR)',        color: '#3b82f6' },
                { key: 'cardmarket_fr',  label: 'FR (EUR)',        color: '#818cf8' },
                { key: 'cardmarket_jp',  label: 'JP (EUR)',        color: '#ef4444' },
            ];

            var allDates = new Set();
            series.forEach(function(s){ (data[s.key] || []).forEach(function(p){ allDates.add(p.recorded_at); }); });
            allDates = Array.from(allDates).sort();

            if (allDates.length === 0) {
                ctx.font = '14px Inter'; ctx.fillStyle = '#9ca3af'; ctx.textAlign = 'center';
                ctx.fillText('No price history available yet', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            var datasets = series
                .filter(function(s){ return (data[s.key] || []).length > 0; })
                .map(function(s) {
                    var pts = (data[s.key] || []).map(function(p){ return { x: p.recorded_at, y: parseFloat(p.price) }; });
                    return { label: s.label, data: pts, borderColor: s.color, backgroundColor: s.color + '0d', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true };
                });

            this.chart = new Chart(ctx, {
                type: 'line',
                data: { labels: allDates, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: { legend: { labels: { color: '#6b7280', font: { size: 11 } } } },
                    scales: { x: { ticks: { color: '#9ca3af', maxTicksLimit: 8 }, grid: { color: 'rgba(0,0,0,0.06)' } }, y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(0,0,0,0.06)' } } }
                }
            });
        }
    }
}
