function toggleDark() {
    const on = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', on);
    document.getElementById('dm-moon').classList.toggle('hidden', on);
    document.getElementById('dm-sun').classList.toggle('hidden', !on);
    document.getElementById('tc-meta').content = on ? '#06080d' : '#ffffff';
}
(function(){
    const on = document.documentElement.classList.contains('dark');
    document.getElementById('dm-moon').classList.toggle('hidden', on);
    document.getElementById('dm-sun').classList.toggle('hidden', !on);
    if(on) document.getElementById('tc-meta').content = '#06080d';
})();

function cleanSubmit(form) {
    const action = form.getAttribute('action') || window.location.pathname;
    const params = new URLSearchParams();
    for (const el of form.elements) { if (!el.name) continue; if (el.value && !(el.name === 'sort' && el.value === 'set')) params.set(el.name, el.value); }
    const qs = params.toString();
    window.location.href = action + (qs ? '?' + qs : '');
}

function showToast(message, type) {
    type = type || 'success';
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-gray-800' };
    toast.className = (colors[type] || colors.info) + ' px-5 py-3 rounded-xl shadow-2xl toast-enter text-sm font-medium flex items-center gap-2';
    toast.style.color = '#fff';
    toast.innerHTML = '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + (type === 'success' ? 'M5 13l4 4L19 7' : type === 'error' ? 'M6 18L18 6M6 6l12 12' : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z') + '"></path></svg>' + message;
    container.appendChild(toast);
    setTimeout(function(){ toast.classList.add('toast-exit'); setTimeout(function(){ toast.remove(); }, 300); }, 3000);
}

async function apiPost(url, data) {
    const formData = new FormData();
    Object.entries(data).forEach(function(entry) { formData.append(entry[0], String(entry[1])); });
    const res = await fetch(url, { method: 'POST', body: formData });
    return res.json();
}

function globalSearch() {
    return {
        query: '', open: false, loading: false, activeIdx: -1,
        results: { cards: [], users: [], sets: [] },
        async search() {
            if (this.query.length < 2) { this.open = false; this.results = { cards: [], users: [], sets: [] }; return; }
            this.loading = true; this.open = true;
            try { const res = await fetch('/api/search?q=' + encodeURIComponent(this.query)); this.results = await res.json(); } catch(e) { this.results = { cards: [], users: [], sets: [] }; }
            this.loading = false; this.activeIdx = -1;
        },
        close() { this.open = false; },
        moveDown() { this.activeIdx = Math.min(this.activeIdx + 1, this.results.cards.length - 1); },
        moveUp() { this.activeIdx = Math.max(this.activeIdx - 1, 0); },
        go() { if (this.activeIdx >= 0 && this.results.cards[this.activeIdx]) window.location = '/cards/' + this.results.cards[this.activeIdx].card_set_id; }
    }
}

async function setLanguage(lang) { await apiPost('/settings/language', { lang: lang }); location.reload(); }

function notifBell() {
    return {
        open: false,
        items: window.__NOTIF_ITEMS || [],
        toggle() { this.open = !this.open; },
        async accept(req) {
            var res = await apiPost('/friends/accept', { user_id: req.user_id });
            if (res.success) {
                showToast(req.username + ' is now your friend');
                this.items = this.items.filter(function(r){ return r.id !== req.id; });
                this.syncBadge();
            }
        },
        async decline(req) {
            var res = await apiPost('/friends/decline', { user_id: req.user_id });
            if (res.success) {
                showToast('Request declined');
                this.items = this.items.filter(function(r){ return r.id !== req.id; });
                this.syncBadge();
            }
        },
        syncBadge() {
            var c = this.items.length;
            var dot = document.getElementById('nav-notif-dot');
            var badge = document.getElementById('nav-notif-count');
            if (dot) dot.style.display = c > 0 ? '' : 'none';
            if (badge) { badge.textContent = c; badge.style.display = c > 0 ? '' : 'none'; }
            if (typeof updateNavBadge === 'function') updateNavBadge(c);
        }
    }
}

function updateNavBadge(count) {
    var badge = document.getElementById('nav-notif-count');
    var dot = document.getElementById('nav-notif-dot');
    if (badge) { badge.textContent = count; badge.style.display = count > 0 ? '' : 'none'; }
    if (dot) { dot.style.display = count > 0 ? '' : 'none'; }
}
