function friendsPage() {
    var data = window.__PAGE_DATA || {};
    return {
        searchQuery: '',
        searchResults: [],
        pendingRequests: data.pendingRequests || [],
        sentRequests: data.sentRequests || [],
        friendsList: data.friends || [],
        blockedList: data.blockedUsers || [],
        reportModalOpen: false,
        reportTargetId: null,
        reportTargetUsername: '',
        reportReason: 'spam',
        reportDetails: '',
        reportError: '',
        get friendCount() { return this.friendsList.length; },

        initReportModal() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && this.reportModalOpen) this.reportModalOpen = false;
            }.bind(this));
        },

        openReportModal(userId, username) {
            this.reportTargetId = userId;
            this.reportTargetUsername = username;
            this.reportReason = 'spam';
            this.reportDetails = '';
            this.reportError = '';
            this.reportModalOpen = true;
            setTimeout(function(){ lucide.createIcons && lucide.createIcons(); }, 50);
        },

        async submitReport() {
            if (!this.reportTargetId) return;
            this.reportError = '';
            var res = await apiPost('/report/user', {
                user_id: this.reportTargetId,
                reason: this.reportReason,
                details: this.reportDetails
            });
            if (res.success) {
                showToast('Report submitted. Thank you.');
                this.reportModalOpen = false;
            } else {
                this.reportError = res.message || 'Failed to submit report';
            }
        },

        async searchUsers() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            var res = await fetch('/api/users/search?q=' + encodeURIComponent(this.searchQuery));
            this.searchResults = await res.json();
        },

        async sendRequest(userId) {
            var res = await apiPost('/friends/request', { user_id: userId });
            if (res.success) {
                showToast('Friend request sent');
                this.searchResults = this.searchResults.filter(function(u){ return u.id !== userId; });
            } else {
                showToast(res.message || 'Could not send request', 'error');
            }
        },

        async acceptRequest(userId, reqId) {
            var res = await apiPost('/friends/accept', { user_id: userId });
            if (res.success) {
                showToast('Friend request accepted');
                var req = this.pendingRequests.find(function(r){ return r.id === reqId; });
                this.pendingRequests = this.pendingRequests.filter(function(r){ return r.id !== reqId; });
                if (req) this.friendsList.push({ id: req.user_id, username: req.username, avatar_url: req.avatar_url, card_count: 0 });
                updateNavBadge(this.pendingRequests.length);
            }
        },

        async declineRequest(userId, reqId) {
            var res = await apiPost('/friends/decline', { user_id: userId });
            if (res.success) {
                showToast('Request declined');
                this.pendingRequests = this.pendingRequests.filter(function(r){ return r.id !== reqId; });
                updateNavBadge(this.pendingRequests.length);
            }
        },

        async removeFriend(friendId, username) {
            if (!confirm('Remove ' + username + ' from your friends?')) return;
            var res = await apiPost('/friends/remove', { user_id: friendId });
            if (res.success) {
                showToast('Friend removed');
                this.friendsList = this.friendsList.filter(function(f){ return f.id !== friendId; });
            }
        },

        async blockUser(userId, username) {
            if (!confirm('Block ' + username + '? They will not be able to send you friend requests or messages.')) return;
            var res = await apiPost('/friends/block', { user_id: userId });
            if (res.success) {
                showToast('User blocked');
                var friend = this.friendsList.find(function(f){ return f.id === userId; });
                this.friendsList = this.friendsList.filter(function(f){ return f.id !== userId; });
                var avatar = friend ? friend.avatar_url : null;
                if (!avatar) {
                    var sr = this.searchResults.find(function(s){ return s.id === userId; });
                    avatar = sr ? sr.avatar_url : null;
                }
                this.blockedList.push({ id: userId, username: username, avatar_url: avatar });
            }
        },

        async unblockUser(userId) {
            var res = await apiPost('/friends/unblock', { user_id: userId });
            if (res.success) {
                showToast('User unblocked');
                this.blockedList = this.blockedList.filter(function(b){ return b.id !== userId; });
            }
        }
    }
}
