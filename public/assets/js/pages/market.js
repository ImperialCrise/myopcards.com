function priceMovers() {
    return {
        gainers: [], losers: [],
        async load() {
            try {
                var results = await Promise.all([
                    fetch('/api/market/movers?direction=up&days=7'),
                    fetch('/api/market/movers?direction=down&days=7')
                ]);
                this.gainers = await results[0].json();
                this.losers = await results[1].json();
            } catch(e) {}
        }
    }
}
