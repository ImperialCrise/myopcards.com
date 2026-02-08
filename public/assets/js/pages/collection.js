function collectionPage() {
    var data = window.__PAGE_DATA || {};
    return {
        shareOpen: false,
        shareUrl: data.shareUrl || '',
        shareLoading: false,
        copied: false,

        shareCollection() {
            this.shareOpen = !this.shareOpen;
        },

        async generateShare() {
            this.shareLoading = true;
            try {
                var res = await apiPost('/collection/share', {});
                if (res.success) {
                    this.shareUrl = res.url;
                    showToast('Share link created');
                }
            } catch(e) {}
            this.shareLoading = false;
        },

        async revokeShare() {
            await apiPost('/collection/share/revoke', {});
            this.shareUrl = '';
            showToast('Share link revoked', 'info');
        },

        copyShare() {
            navigator.clipboard.writeText(this.shareUrl);
            this.copied = true;
            setTimeout(function(){ this.copied = false; }.bind(this), 2000);
        }
    }
}
