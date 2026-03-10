function t(key, replace) {
    var s = (window.__LANG && window.__LANG[key]) || key;
    if (replace && typeof replace === 'object') for (var k in replace) s = String(s).split(k).join(replace[k]);
    return s;
}

function toggleDark() {}

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
    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) formData.append('csrf_token', token);
    Object.entries(data || {}).forEach(function(entry) { formData.append(entry[0], String(entry[1])); });
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
window.globalSearch = globalSearch;

async function setLanguage(lang) { await apiPost('/settings/language', { lang: lang }); location.reload(); }

async function setCurrency(cur) { await apiPost('/settings/currency', { currency: cur }); location.reload(); }

function getCardPrice(card) {
    var c = window.__CURRENCY || {};
    var col = c.column || 'market_price';
    var val = parseFloat(card[col] || 0);
    if (val <= 0) val = parseFloat(card.market_price || 0);
    return val;
}

function formatPrice(val) {
    if (!val || val <= 0) return '';
    var c = window.__CURRENCY || {};
    return (c.symbol || '$') + val.toFixed(2);
}

function formatCardPrice(card) {
    return formatPrice(getCardPrice(card));
}

function unifiedNotifications() {
    return {
        open: false,
        notifications: [],
        totalCount: 0,

        init() {
            this.loadNotifications();
            var self = this;
            document.addEventListener('refresh-notifications', function() { self.loadNotifications(); });
        },

        toggle() { 
            this.open = !this.open; 
            if (this.open) {
                lucide.createIcons();
            }
        },
        
        async loadNotifications() {
            try {
                const [countRes, recentRes] = await Promise.all([
                    fetch('/api/notifications/count'),
                    fetch('/api/notifications/recent')
                ]);
                const countData = await countRes.json();
                const recentData = await recentRes.json();
                this.notifications = recentData.notifications || [];
                this.totalCount = countData.count || 0;
            } catch (error) {
                console.error('Failed to load notifications:', error);
                this.notifications = [];
                this.totalCount = 0;
            }
        },
        
        async acceptFriend(notif) {
            var senderId = notif.data && notif.data.sender_id;
            if (!senderId) return;
            var res = await apiPost('/friends/accept', { user_id: senderId });
            if (res.success) {
                showToast((notif.data.sender_username || 'User') + ' is now your friend');
                await this._markNotifRead(notif.id);
                this.notifications = this.notifications.filter(function(n){ return n.id !== notif.id; });
                this.totalCount = Math.max(0, this.totalCount - 1);
            }
        },
        
        async declineFriend(notif) {
            var senderId = notif.data && notif.data.sender_id;
            if (!senderId) return;
            var res = await apiPost('/friends/decline', { user_id: senderId });
            if (res.success) {
                showToast('Request declined');
                await this._markNotifRead(notif.id);
                this.notifications = this.notifications.filter(function(n){ return n.id !== notif.id; });
                this.totalCount = Math.max(0, this.totalCount - 1);
            }
        },

        async _markNotifRead(notifId) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const body = 'notification_id=' + notifId + (csrfToken ? '&csrf_token=' + encodeURIComponent(csrfToken) : '');
                await fetch('/notifications/read', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body });
            } catch (e) {}
        },
        
        async markAsRead(notif) {
            if (notif.type === 'friend_request' && !notif.is_read) return;
            if (notif.url) {
                try {
                    if (!notif.is_read) {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const body = `notification_id=${notif.id}` + (csrfToken ? `&csrf_token=${encodeURIComponent(csrfToken)}` : '');
                        await fetch('/notifications/read', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: body
                        });
                    }
                    window.location.href = notif.url;
                } catch (error) {
                    console.error('Failed to mark notification as read:', error);
                }
            } else {
                window.location.href = '/notifications';
            }
        },

        notifIcon(type) {
            switch (type) {
                case 'forum_reply': return 'message-circle';
                case 'forum_like': return 'heart';
                case 'forum_mention': return 'at-sign';
                case 'friend_request': return 'user-plus';
                case 'friend_accepted': return 'user-check';
                case 'private_message': return 'mail';
                default: return 'bell';
            }
        }
    }
}
window.unifiedNotifications = unifiedNotifications;

var __PLACEHOLDER = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='250' height='350' viewBox='0 0 250 350'%3E%3Crect fill='%23e5e7eb' width='250' height='350' rx='12'/%3E%3Ctext x='125' y='165' text-anchor='middle' font-family='sans-serif' font-size='14' fill='%239ca3af'%3ENo Image%3C/text%3E%3Cpath d='M113 185 l12 15 8-6 17 21H100z' fill='%239ca3af' opacity='.4'/%3E%3Ccircle cx='150' cy='190' r='6' fill='%239ca3af' opacity='.4'/%3E%3C/svg%3E";
function cardImgErr(el) {
    var ext = el.dataset && el.dataset.extSrc;
    if (ext && el.src !== ext) { el.src = ext; el.onerror = null; return; }
    el.src = __PLACEHOLDER; el.onerror = null;
}

function cardImgSrc(url) {
    if (url == null || typeof url !== 'string') return __PLACEHOLDER;
    if (url.indexOf('optcgapi.com') !== -1) return '/uploads/cards/' + url.split('/').pop();
    return url;
}

function updateNavBadge(count) {
    var badge = document.getElementById('nav-notif-count');
    var dot = document.getElementById('nav-notif-dot');
    if (badge) { badge.textContent = count; badge.style.display = count > 0 ? '' : 'none'; }
    if (dot) { dot.style.display = count > 0 ? '' : 'none'; }
}

// Register Alpine components before Alpine initializes (required for CDN build)
document.addEventListener('alpine:init', function() {
    try {
        var gs = typeof window.globalSearch === 'function' ? window.globalSearch : function() {
            return { query: '', open: false, loading: false, activeIdx: -1, results: { cards: [], users: [], sets: [] }, close: function(){}, search: function(){}, moveDown: function(){}, moveUp: function(){}, go: function(){} };
        };
        var un = typeof window.unifiedNotifications === 'function' ? window.unifiedNotifications : function() {
            return { open: false, notifications: [], totalCount: 0, init: function(){}, toggle: function(){}, loadNotifications: function(){}, acceptFriend: function(){}, declineFriend: function(){}, markAsRead: function(){}, notifIcon: function(){ return 'bell'; } };
        };
        Alpine.data('globalSearch', gs);
        Alpine.data('unifiedNotifications', un);
    } catch (e) { console.error('[Alpine] Component registration failed:', e); }
});
