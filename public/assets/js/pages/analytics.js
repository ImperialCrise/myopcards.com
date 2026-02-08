function valueTimeline() {
    return {
        chart: null, days: 90,
        async load() {
            var res = await fetch('/api/analytics/value-history?days=' + this.days);
            var data = await res.json();
            var ctx = document.getElementById('analyticsValueChart').getContext('2d');
            if (this.chart) this.chart.destroy();
            if (data.length === 0) { ctx.font = '14px Inter'; ctx.fillStyle = '#9ca3af'; ctx.textAlign = 'center'; ctx.fillText('No snapshot data yet.', ctx.canvas.width/2, ctx.canvas.height/2); return; }
            this.chart = new Chart(ctx, {
                type: 'line',
                data: { labels: data.map(function(d){ return d.snapshot_date; }), datasets: [
                    { label: 'USD', data: data.map(function(d){ return parseFloat(d.total_value_usd); }), borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.05)', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true },
                    { label: 'EUR', data: data.map(function(d){ return parseFloat(d.total_value_eur || 0); }), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.05)', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true }
                ] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#6b7280', font: { size: 11 } } } }, scales: { x: { ticks: { color: '#9ca3af', maxTicksLimit: 8 }, grid: { display: false } }, y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(0,0,0,0.06)' } } } }
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    var res = await fetch('/api/analytics/distribution');
    var data = await res.json();
    var palette = ['#ef4444','#3b82f6','#22c55e','#a855f7','#eab308','#06b6d4','#f97316','#ec4899','#6366f1','#14b8a6','#f43f5e','#8b5cf6'];
    var chartOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#6b7280', boxWidth: 12, padding: 6, font: { size: 10 } } } } };

    if (data.colors && data.colors.length > 0) {
        new Chart(document.getElementById('analyticsColorChart'), { type: 'doughnut', data: { labels: data.colors.map(function(c){ return c.label; }), datasets: [{ data: data.colors.map(function(c){ return parseInt(c.value); }), backgroundColor: palette, borderWidth: 0 }] }, options: chartOpts });
    }
    if (data.rarities && data.rarities.length > 0) {
        new Chart(document.getElementById('analyticsRarityChart'), { type: 'bar', data: { labels: data.rarities.map(function(r){ return r.label; }), datasets: [{ data: data.rarities.map(function(r){ return parseInt(r.value); }), backgroundColor: palette, borderWidth: 0, borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(0,0,0,0.06)' } }, y: { ticks: { color: '#6b7280' }, grid: { display: false } } } } });
    }
    if (data.types && data.types.length > 0) {
        new Chart(document.getElementById('analyticsTypeChart'), { type: 'pie', data: { labels: data.types.map(function(t){ return t.label; }), datasets: [{ data: data.types.map(function(t){ return parseInt(t.value); }), backgroundColor: palette, borderWidth: 0 }] }, options: chartOpts });
    }
    if (data.sets && data.sets.length > 0) {
        new Chart(document.getElementById('analyticsSetChart'), { type: 'bar', data: { labels: data.sets.map(function(s){ return s.label; }), datasets: [{ data: data.sets.map(function(s){ return parseInt(s.value); }), backgroundColor: '#374151', borderWidth: 0, borderRadius: 3 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9ca3af', maxRotation: 45 }, grid: { display: false } }, y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(0,0,0,0.06)' } } } } });
    }
});
