(function () {
  function getSocketUrl() {
    const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const host = window.location.host;
    return proto + '//' + host;
  }

  document.addEventListener('alpine:init', function () {
    Alpine.data('lobbyPage', function (userId, username, decks) {
      return {
        userId: userId || 0,
        username: username || '',
        decks: Array.isArray(decks) ? decks : [],
        selectedDeckId: '',
        queueing: false,
        queueMode: '',
        joinCode: '',
        roomCode: '',
        message: '',
        messageType: '',
        socket: null,

        init: function () {
          if (typeof io === 'undefined') return;
          try {
            this.socket = io(getSocketUrl(), { path: '/socket.io/', withCredentials: true });
            this.socket.on('connected', function () {});
            this.socket.on('matchFound', function (data) {
              if (data && data.gameId) window.location.href = '/play/game/' + data.gameId;
            });
            this.socket.on('gameStart', function (data) {
              if (data && data.gameId) window.location.href = '/play/game/' + data.gameId;
            });
            this.socket.on('customRoomCreated', function (data) {
              this.message = 'Room created! Share this code: ' + (data && data.code ? data.code : '');
              this.messageType = 'info';
              this.roomCode = (data && data.code) ? data.code : '';
            }.bind(this));
            this.socket.on('error', function (err) {
              this.queueing = false;
              this.message = err && err.message ? err.message : 'Connection error';
              this.messageType = 'error';
            }.bind(this));
            this.socket.on('connect_error', function () {
              this.queueing = false;
              this.message = 'Game server unreachable. Is the game server running?';
              this.messageType = 'error';
            }.bind(this));
          } catch (e) {
            this.queueing = false;
            this.message = 'Could not connect to game server.';
            this.messageType = 'error';
          }
        },

        findMatch: function (mode) {
          if (this.queueing || !this.selectedDeckId) return;
          this.queueing = true;
          this.queueMode = mode;
          this.message = 'Searching for ' + mode + ' match...';
          this.messageType = '';
          if (this.socket) this.socket.emit('findMatch', { userId: this.userId, username: this.username, deckId: parseInt(this.selectedDeckId, 10), mode: mode });
        },

        vsBot: function (difficulty) {
          if (this.queueing || !this.selectedDeckId) return;
          if (!this.socket || !this.socket.connected) {
            this.message = 'Game server unreachable. Is the game server running?';
            this.messageType = 'error';
            return;
          }
          this.queueing = true;
          this.message = 'Starting vs Bot (' + difficulty + ')...';
          this.messageType = '';
          this.socket.emit('vsBot', { userId: this.userId, username: this.username, deckId: parseInt(this.selectedDeckId, 10), difficulty: difficulty });
        },

        createCustom: function () {
          if (this.queueing || !this.selectedDeckId) return;
          if (this.socket) this.socket.emit('createCustom', { userId: this.userId, username: this.username, deckId: parseInt(this.selectedDeckId, 10) });
          this.message = 'Creating room...';
        },

        joinCustom: function () {
          var code = (this.joinCode || '').trim();
          if (!code || !this.selectedDeckId) return;
          if (this.socket) this.socket.emit('joinCustom', { userId: this.userId, username: this.username, deckId: parseInt(this.selectedDeckId, 10), code: code });
          this.message = 'Joining...';
        },

        deckLeaderImage: function (deck) {
          var url = deck && deck.leader_image_url;
          if (url == null || typeof url !== 'string') return '/assets/img/card-back.png';
          if (url.indexOf('optcgapi.com') !== -1) return '/api/card-image?url=' + encodeURIComponent(url);
          return url;
        }
      };
    });
  });
})();
