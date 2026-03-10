function publicProfile() {
    var data = window.__PAGE_DATA || {};
    return {
        relation: data.relation || 'none',
        userId: data.userId,
        username: data.username || '',
        reportModalOpen: false,
        reportReason: 'spam',
        reportDetails: '',
        reportError: '',

        openReportModal() {
            this.reportError = '';
            this.reportModalOpen = true;
            setTimeout(function(){ lucide.createIcons && lucide.createIcons(); }, 50);
        },
        async submitReport() {
            if (!this.userId) return;
            this.reportError = '';
            var res = await apiPost('/report/user', { user_id: this.userId, reason: this.reportReason, details: this.reportDetails });
            if (res.success) { showToast('Report submitted. Thank you.'); this.reportModalOpen = false; }
            else this.reportError = res.message || 'Failed to submit report';
        },
        async blockUser() {
            if (!confirm('Block ' + this.username + '? They will not be able to send you friend requests or messages.')) return;
            var res = await apiPost('/friends/block', { user_id: this.userId });
            if (res.success) { showToast('User blocked'); this.relation = 'none'; }
            else showToast(res.message || 'Could not block', 'error');
        },

        async addFriend() {
            var res = await apiPost('/friends/request', { user_id: data.userId });
            if (res.success) { showToast('Friend request sent'); this.relation = 'pending_sent'; }
            else showToast(res.message || 'Could not send request', 'error');
        },
        async acceptRequest() {
            var res = await apiPost('/friends/accept', { user_id: data.userId });
            if (res.success) { showToast('You are now friends with ' + data.username); this.relation = 'friend'; }
        },
        async declineRequest() {
            var res = await apiPost('/friends/decline', { user_id: data.userId });
            if (res.success) { showToast('Request declined'); this.relation = 'none'; }
        },
        async removeFriend() {
            if (!confirm('Remove ' + data.username + ' from your friends?')) return;
            var res = await apiPost('/friends/remove', { user_id: data.userId });
            if (res.success) { showToast('Friend removed'); this.relation = 'none'; }
        }
    }
}
