/* Messages — full social messaging JS */
/* GIF calls go through the PHP proxy /api/gif/* (keeps API key server-side) */

function conversationPage(convId, currentUserId) {
    return {
        convId,
        currentUserId,
        messages: [],
        messageBody: '',
        sending: false,
        error: '',
        lastId: 0,
        pollTimer: null,
        typingTimer: null,
        typingUsers: [],
        editingId: null,
        editBody: '',
        hoveredMsgId: null,
        // GIF picker
        gifOpen: false,
        gifQuery: '',
        gifResults: [],
        gifLoading: false,
        gifPage: 1,
        gifHasMore: false,
        gifLoadingMore: false,
        _gifObserver: null,
        // Image attachment (pending, not yet sent)
        pendingAttachment: null,   // { file, previewUrl, type }
        uploading: false,
        // Lightbox
        lightboxUrl: null,
        uploadDragOver: false,
        // Exit warning
        exitWarning: { show: false, url: '' },

        init() {
            window.__msgPage = this;
            this.scrollBottom();
            this.startPolling();
        },

        startPolling() {
            this.pollTimer = setInterval(() => this.poll(), 2000);
        },
        stopPolling() { clearInterval(this.pollTimer); },

        async poll() {
            try {
                const r = await fetch(`/api/messages/${this.convId}/poll?after=${this.lastId}`);
                if (!r.ok) return;
                const data = await r.json();
                if (!data.success) return;
                if (data.messages?.length) {
                    for (const m of data.messages) {
                        const existing = this.messages.find(x => x.id === m.id);
                        if (existing) {
                            existing.body = m.body;
                            existing.edited_at = m.edited_at;
                            existing.is_deleted = m.is_deleted;
                            continue;
                        }
                        const optIdx = this.messages.findIndex(x => x._optimistic && x.body === m.body && m.sender_id === this.currentUserId);
                        if (optIdx !== -1) {
                            this.messages[optIdx] = m;
                        } else {
                            this.messages.push(m);
                        }
                        if (m.id > this.lastId) this.lastId = m.id;
                    }
                    this.$nextTick(() => this.scrollBottom());
                }
                this.typingUsers = data.typing ?? [];
            } catch (e) { /* silent */ }
        },

        onKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.send();
                return;
            }
            this.signalTyping();
        },

        signalTyping() {
            clearTimeout(this.typingTimer);
            this.typingTimer = setTimeout(async () => {
                const fd = new FormData();
                fd.append('csrf_token', this.csrf());
                fetch(`/api/messages/${this.convId}/typing`, { method: 'POST', body: fd }).catch(() => {});
            }, 400);
        },

        // ── SEND ────────────────────────────────────────────────
        async send() {
            const body = this.messageBody.trim();
            const hasAttachment = !!this.pendingAttachment;
            if (!body && !hasAttachment) return;
            if (this.sending || this.uploading) return;
            this.sending = true;
            this.error = '';

            // If there's a pending attachment, upload first then send
            if (hasAttachment) {
                await this._sendWithAttachment(body);
                this.sending = false;
                return;
            }

            // Text-only optimistic send
            const optimistic = {
                id: 'opt-' + Date.now(), _optimistic: true,
                sender_id: this.currentUserId, body, type: 'text',
                media_url: null, edited_at: null, is_deleted: false,
                created_at: new Date().toISOString().replace('T', ' ').slice(0, 19),
            };
            this.messages.push(optimistic);
            this.messageBody = '';
            this.$nextTick(() => this.scrollBottom());

            try {
                const fd = new FormData();
                fd.append('conversation_id', this.convId);
                fd.append('body', body);
                fd.append('csrf_token', this.csrf());
                const r = await fetch('/api/messages/send', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.success) {
                    optimistic.id = data.message_id;
                    optimistic._optimistic = false;
                    if (data.message_id > this.lastId) this.lastId = data.message_id;
                } else {
                    this.messages = this.messages.filter(m => m !== optimistic);
                    this.messageBody = body;
                    this.error = data.message || 'Failed to send';
                }
            } catch (e) {
                this.messages = this.messages.filter(m => m !== optimistic);
                this.messageBody = body;
                this.error = 'Network error';
            }
            this.sending = false;
        },

        async _sendWithAttachment(caption) {
            this.uploading = true;
            const attachment = this.pendingAttachment;
            try {
                // Upload the file
                const fd = new FormData();
                fd.append('conversation_id', this.convId);
                fd.append('file', attachment.file);
                fd.append('csrf_token', this.csrf());
                const upResp = await fetch('/api/messages/upload', { method: 'POST', body: fd });
                const upData = await upResp.json();
                if (!upData.success) {
                    this.error = upData.message || 'Upload failed';
                    this.uploading = false;
                    this.sending = false;
                    return;
                }
                // Clear pending attachment
                URL.revokeObjectURL(attachment.previewUrl);
                this.pendingAttachment = null;
                this.messageBody = '';
                // Send the media message
                const fd2 = new FormData();
                fd2.append('conversation_id', this.convId);
                fd2.append('body', caption || '');
                fd2.append('type', attachment.type);
                fd2.append('media_url', upData.url);
                fd2.append('csrf_token', this.csrf());
                const r = await fetch('/api/messages/send', { method: 'POST', body: fd2 });
                const data = await r.json();
                if (data.success) {
                    this.messages.push({
                        id: data.message_id, sender_id: this.currentUserId,
                        body: caption || '', type: attachment.type, media_url: upData.url,
                        edited_at: null, is_deleted: false,
                        created_at: new Date().toISOString().replace('T', ' ').slice(0, 19),
                    });
                    if (data.message_id > this.lastId) this.lastId = data.message_id;
                    this.$nextTick(() => this.scrollBottom());
                } else {
                    this.error = data.message || 'Failed to send';
                }
            } catch (e) {
                this.error = 'Upload failed: network error';
            }
            this.uploading = false;
            this.sending = false;
        },

        async sendMedia(url, type) {
            const fd = new FormData();
            fd.append('conversation_id', this.convId);
            fd.append('body', '');
            fd.append('type', type);
            fd.append('media_url', url);
            fd.append('csrf_token', this.csrf());
            try {
                const r = await fetch('/api/messages/send', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.success) {
                    this.messages.push({
                        id: data.message_id, sender_id: this.currentUserId,
                        body: '', type, media_url: url,
                        edited_at: null, is_deleted: false,
                        created_at: new Date().toISOString().replace('T', ' ').slice(0, 19),
                    });
                    if (data.message_id > this.lastId) this.lastId = data.message_id;
                    this.$nextTick(() => this.scrollBottom());
                } else {
                    this.error = data.message || 'Failed to send';
                }
            } catch (e) { this.error = 'Network error'; }
        },

        // ── EDIT ────────────────────────────────────────────────
        startEdit(msg) {
            this.editingId = msg.id;
            this.editBody = msg.body;
            this.$nextTick(() => {
                const el = document.getElementById('edit-input-' + msg.id);
                if (el) { el.focus(); el.select(); }
                if (window.lucide) lucide.createIcons();
            });
        },
        cancelEdit() { this.editingId = null; this.editBody = ''; },
        async submitEdit() {
            if (!this.editBody.trim() || !this.editingId) return;
            const fd = new FormData();
            fd.append('message_id', this.editingId);
            fd.append('body', this.editBody.trim());
            fd.append('csrf_token', this.csrf());
            const r = await fetch('/api/messages/edit', { method: 'POST', body: fd });
            const data = await r.json();
            if (data.success) {
                const msg = this.messages.find(m => m.id === this.editingId);
                if (msg) { msg.body = this.editBody.trim(); msg.edited_at = new Date().toISOString(); }
                this.cancelEdit();
            } else { this.error = 'Could not edit message'; }
        },

        async deleteMsg(msgId) {
            if (!confirm('Delete this message?')) return;
            const fd = new FormData();
            fd.append('message_id', msgId);
            fd.append('csrf_token', this.csrf());
            const r = await fetch('/api/messages/delete', { method: 'POST', body: fd });
            const data = await r.json();
            if (data.success) {
                const msg = this.messages.find(m => m.id === msgId);
                if (msg) msg.is_deleted = true;
            }
        },

        // ── FILE ATTACHMENT (preview, don't send yet) ────────────
        setAttachment(file) {
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) { this.error = 'File too large (max 5MB)'; return; }
            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowed.includes(file.type)) { this.error = 'Only jpg/png/gif/webp allowed'; return; }
            this.error = '';
            // Revoke any previous object URL
            if (this.pendingAttachment) URL.revokeObjectURL(this.pendingAttachment.previewUrl);
            const previewUrl = URL.createObjectURL(file);
            const type = file.type === 'image/gif' ? 'gif' : 'image';
            this.pendingAttachment = { file, previewUrl, type };
        },
        clearAttachment() {
            if (this.pendingAttachment) URL.revokeObjectURL(this.pendingAttachment.previewUrl);
            this.pendingAttachment = null;
        },
        onFilePick(e) {
            this.setAttachment(e.target.files[0]);
            e.target.value = '';
        },
        onDrop(e) {
            this.uploadDragOver = false;
            this.setAttachment(e.dataTransfer?.files?.[0]);
        },

        // ── GIF (Klipy via PHP proxy — key stays server-side) ───
        async openGifPicker() {
            this.gifOpen = true;
            // Load default "one piece" results silently (field stays empty)
            if (!this.gifResults.length && !this.gifLoading) {
                await this._fetchGifs('one piece', 1, false);
            }
            this.$nextTick(() => this._initGifScrollObserver());
        },

        closeGifPicker() {
            this.gifOpen = false;
            this._destroyGifScrollObserver();
        },

        // Called by the search input (user-typed query)
        async searchGifs(q) {
            this.gifQuery = q;
            this.gifPage = 1;
            this.gifResults = [];
            this.gifHasMore = false;
            // When field is empty, show one piece again
            const term = q.trim() || 'one piece';
            await this._fetchGifs(term, 1, false);
        },

        // Internal fetch — appends if append=true
        async _fetchGifs(term, page, append) {
            if (append) {
                this.gifLoadingMore = true;
            } else {
                this.gifLoading = true;
            }
            try {
                const url = `/api/gif/search?q=${encodeURIComponent(term)}&page=${page}`;
                const r = await fetch(url);
                if (!r.ok) throw new Error('proxy ' + r.status);
                const data = await r.json();
                const parsed = this._parseKlipyResults(data);
                if (append) {
                    this.gifResults = [...this.gifResults, ...parsed];
                } else {
                    this.gifResults = parsed;
                }
                this.gifHasMore = data?.data?.has_next === true;
                this.gifPage = page;
            } catch (e) {
                if (!append) this.gifResults = [];
            }
            this.gifLoading = false;
            this.gifLoadingMore = false;
        },

        async _loadMoreGifs() {
            if (this.gifLoadingMore || !this.gifHasMore) return;
            const term = (this.gifQuery.trim() || 'one piece');
            await this._fetchGifs(term, this.gifPage + 1, true);
        },

        // IntersectionObserver on a sentinel div at the bottom of the GIF grid
        _initGifScrollObserver() {
            this._destroyGifScrollObserver();
            const sentinel = document.getElementById('gif-scroll-sentinel');
            if (!sentinel) return;
            this._gifObserver = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) this._loadMoreGifs();
            }, { threshold: 0.1 });
            this._gifObserver.observe(sentinel);
        },
        _destroyGifScrollObserver() {
            if (this._gifObserver) { this._gifObserver.disconnect(); this._gifObserver = null; }
        },

        // Klipy response: { data: { data: [...], has_next: bool } }
        _parseKlipyResults(data) {
            const items = data?.data?.data ?? data?.results ?? [];
            return items.map(item => ({
                url:     item.file?.md?.gif?.url ?? item.file?.hd?.gif?.url ?? '',
                preview: item.file?.xs?.gif?.url ?? item.file?.sm?.gif?.url ?? item.file?.md?.gif?.url ?? '',
            })).filter(x => x.url);
        },

        async pickGif(url) {
            this.closeGifPicker();
            this.gifResults = [];
            this.gifQuery = '';
            await this.sendMedia(url, 'gif');
        },

        // ── LIGHTBOX ─────────────────────────────────────────────
        openLightbox(url) { this.lightboxUrl = url; },
        closeLightbox() { this.lightboxUrl = null; },

        // ── HELPERS ───────────────────────────────────────────────
        scrollBottom() {
            const c = this.$refs.msgContainer;
            if (c) c.scrollTop = c.scrollHeight;
        },
        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },
        formatTime(ts) {
            if (!ts) return '';
            const d = new Date((ts + '').replace(' ', 'T'));
            if (isNaN(d)) return '';
            const now = new Date();
            const diff = (now - d) / 1000;
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (d.toDateString() === now.toDateString())
                return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' +
                   d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },
        avatarInitial(u) { return (u || '?').charAt(0).toUpperCase(); },

        // Render text with clickable links.
        // Internal links (myopcards.com or relative) open directly; external links show warning.
        renderText(text) {
            if (!text) return '';
            const escaped = text
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            return escaped.replace(/(https?:\/\/[^\s<>"'&]+)/g, (url) => {
                const display = url.length > 55 ? url.slice(0, 52) + '…' : url;
                const isInternal = /^https?:\/\/(www\.)?myopcards\.com(\/|$)/i.test(url);
                if (isInternal) {
                    // Safe internal link — open directly
                    const safeUrl = url.replace(/"/g, '&quot;');
                    return `<a href="${safeUrl}" class="text-blue-400 underline underline-offset-2 hover:text-blue-300 break-all">${display}</a>`;
                }
                // External link — show exit warning
                const safeUrl = url.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                return `<a href="#" onclick="event.preventDefault();window.__msgPage&&window.__msgPage.showExitWarning('${safeUrl}')" class="text-blue-400 underline underline-offset-2 hover:text-blue-300 break-all">${display}</a>`;
            });
        },
        showExitWarning(url) { this.exitWarning = { show: true, url }; },
    };
}
