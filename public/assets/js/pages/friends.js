function friendsPage() {
    var data = window.__PAGE_DATA || {};
    return {
        searchQuery: '',
        searchResults: [],
        pendingRequests: data.pendingRequests || [],
        sentRequests: data.sentRequests || [],
        friendsList: data.friends || [],
        get friendCount() { return this.friendsList.length; },

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
                if (req) this.friendsList.push({ id: req.user_id, username: req.username, avatar: req.avatar, card_count: 0 });
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
        }
    }
}
