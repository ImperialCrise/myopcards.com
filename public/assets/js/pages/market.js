function priceMovers() {
    return {
        gainers: [], losers: [],
        async load() {
            try {
                var c = window.__CURRENCY || {};
                var qs = 'direction=up&days=7';
                if (c.source) qs += '&source=' + encodeURIComponent(c.source);
                if (c.edition) qs += '&edition=' + encodeURIComponent(c.edition);
                var qs2 = 'direction=down&days=7';
                if (c.source) qs2 += '&source=' + encodeURIComponent(c.source);
                if (c.edition) qs2 += '&edition=' + encodeURIComponent(c.edition);
                var results = await Promise.all([
                    fetch('/api/market/movers?' + qs),
                    fetch('/api/market/movers?' + qs2)
                ]);
                this.gainers = await results[0].json();
                this.losers = await results[1].json();
            } catch(e) {}
        }
    }
}
