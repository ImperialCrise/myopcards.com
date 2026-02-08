function publicProfile() {
    var data = window.__PAGE_DATA || {};
    return {
        relation: data.relation || 'none',

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
