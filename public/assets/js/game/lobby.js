(function () {
  function getSocketUrl() {
    var proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    return proto + '//' + window.location.host;
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

        matchPopup: false,
        matchData: null,
        matchAccepted: false,
        matchCountdown: 30,
        matchTimer: null,
        matchBothReady: false,

        init: function () {
          var self = this;
          if (typeof io === 'undefined') return;
          try {
            self.socket = io(getSocketUrl(), { path: '/socket.io/', withCredentials: true });

            self.socket.on('connected', function () {});

            self.socket.on('gameStart', function (data) {
              if (data && data.gameId) window.location.href = '/play/game/' + data.gameId;
            });

            self.socket.on('customRoomCreated', function (data) {
              self.queueing = false;
              self.roomCode = (data && data.code) ? data.code : '';
              self.message = '';
            });

            self.socket.on('matchReady', function (data) {
              self.matchPopup = true;
              self.matchAccepted = false;
              self.matchBothReady = false;
              self.matchData = data || {};
              self.matchCountdown = 30;
              self.queueing = false;
              self.message = '';

              if (self.matchTimer) clearInterval(self.matchTimer);
              self.matchTimer = setInterval(function () {
                self.matchCountdown--;
                if (self.matchCountdown <= 0) {
                  clearInterval(self.matchTimer);
                  self.matchTimer = null;
                  if (!self.matchAccepted) {
                    self.declineMatch();
                  }
                }
              }, 1000);
            });

            self.socket.on('matchAccepted', function (data) {
              if (data && data.who === 'opponent') {
                self.matchBothReady = self.matchAccepted;
              } else if (data && data.who === 'both') {
                self.matchBothReady = true;
              }
            });

            self.socket.on('matchDeclined', function () {
              self.matchPopup = false;
              self.matchAccepted = false;
              self.matchData = null;
              if (self.matchTimer) { clearInterval(self.matchTimer); self.matchTimer = null; }
              self.message = 'Match declined. Searching again...';
              self.messageType = 'info';
            });

            self.socket.on('error', function (err) {
              self.queueing = false;
              self.message = err && err.message ? err.message : 'Connection error';
              self.messageType = 'error';
            });

            self.socket.on('connect_error', function () {
              self.queueing = false;
              self.message = 'Game server unreachable.';
              self.messageType = 'error';
            });
          } catch (e) {
            self.queueing = false;
            self.message = 'Could not connect to game server.';
            self.messageType = 'error';
          }
        },

        findMatch: function (mode) {
          if (this.queueing || !this.selectedDeckId) return;
          this.queueing = true;
          this.queueMode = mode;
          this.message = 'Searching for ' + mode + ' match...';
          this.messageType = '';
          if (this.socket) this.socket.emit('findMatch', {
            userId: this.userId,
            username: this.username,
            deckId: parseInt(this.selectedDeckId, 10),
            mode: mode
          });
        },

        cancelSearch: function () {
          if (this.socket) this.socket.emit('cancelQueue', { userId: this.userId, mode: this.queueMode });
          this.queueing = false;
          this.queueMode = '';
          this.message = '';
        },

        acceptMatch: function () {
          this.matchAccepted = true;
          if (this.socket && this.matchData) {
            this.socket.emit('acceptMatch', { matchId: this.matchData.matchId });
          }
        },

        declineMatch: function () {
          if (this.socket && this.matchData) {
            this.socket.emit('declineMatch', { matchId: this.matchData.matchId });
          }
          this.matchPopup = false;
          this.matchAccepted = false;
          this.matchData = null;
          if (this.matchTimer) { clearInterval(this.matchTimer); this.matchTimer = null; }
        },

        vsBot: function (difficulty) {
          if (this.queueing || !this.selectedDeckId) return;
          if (!this.socket || !this.socket.connected) {
            this.message = 'Game server unreachable.';
            this.messageType = 'error';
            return;
          }
          this.queueing = true;
          this.message = 'Starting vs Bot (' + difficulty + ')...';
          this.messageType = '';
          this.socket.emit('vsBot', {
            userId: this.userId,
            username: this.username,
            deckId: parseInt(this.selectedDeckId, 10),
            difficulty: difficulty
          });
        },

        createCustom: function () {
          if (this.queueing || !this.selectedDeckId) return;
          if (this.socket) this.socket.emit('createCustom', {
            userId: this.userId,
            username: this.username,
            deckId: parseInt(this.selectedDeckId, 10)
          });
        },

        joinCustom: function () {
          var code = (this.joinCode || '').trim();
          if (!code || !this.selectedDeckId) return;
          this.queueing = true;
          this.queueMode = 'custom';
          this.message = 'Joining room...';
          this.messageType = '';
          if (this.socket) this.socket.emit('joinCustom', {
            userId: this.userId,
            username: this.username,
            deckId: parseInt(this.selectedDeckId, 10),
            code: code
          });
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
