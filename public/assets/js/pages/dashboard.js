function dashValueChart() {
    return {
        async load() {
            var res = await fetch('/api/analytics/value-history?days=30');
            var data = await res.json();
            var ctx = document.getElementById('dashValueChart').getContext('2d');
            if (data.length === 0) { ctx.font = '13px Inter'; ctx.fillStyle = '#9ca3af'; ctx.textAlign = 'center'; ctx.fillText('No data yet', ctx.canvas.width/2, ctx.canvas.height/2); return; }
            new Chart(ctx, {
                type: 'line',
                data: { labels: data.map(function(d){ return d.snapshot_date; }), datasets: [{ label: 'Value (USD)', data: data.map(function(d){ return parseFloat(d.total_value_usd); }), borderColor: '#374151', backgroundColor: 'rgba(55,65,81,0.06)', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9ca3af', maxTicksLimit: 6 }, grid: { display: false } }, y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(0,0,0,0.06)' } } } }
            });
        }
    }
}

function dashColorChart() {
    return {
        async load() {
            var res = await fetch('/api/analytics/distribution');
            var data = await res.json();
            var ctx = document.getElementById('dashColorChart').getContext('2d');
            var colors = data.colors || [];
            if (colors.length === 0) { ctx.font = '13px Inter'; ctx.fillStyle = '#9ca3af'; ctx.textAlign = 'center'; ctx.fillText('No data yet', ctx.canvas.width/2, ctx.canvas.height/2); return; }
            var palette = ['#ef4444','#3b82f6','#22c55e','#a855f7','#eab308','#06b6d4','#f97316','#ec4899','#6366f1','#14b8a6'];
            new Chart(ctx, {
                type: 'doughnut',
                data: { labels: colors.map(function(c){ return c.label; }), datasets: [{ data: colors.map(function(c){ return parseInt(c.value); }), backgroundColor: palette.slice(0, colors.length), borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#6b7280', boxWidth: 12, padding: 8, font: { size: 11 } } } } }
            });
        }
    }
}
