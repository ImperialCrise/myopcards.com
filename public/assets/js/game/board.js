(function () {
  document.addEventListener('alpine:init', function () {
    Alpine.data('gameBoard', function (gameId, userId) {
      return {
        gameId: gameId || 0,
        userId: userId || (window.__gameUserId || 0),
        playerIndex: 0,
        state: null,
        socket: null,
        hoveredCard: null,
        previewX: 0,
        previewY: 0,
        notification: '',
        lastAction: '',
        attackMode: false,
        attackSource: null,
        phase: '',
        gameStartTime: Date.now(),
        durationTick: 0,
        showTutorial: false,
        tutorialStep: 1,
        screenFlash: false,
        gameOverData: null,

        cardImageSrc: function (url) {
          if (url == null || typeof url !== 'string') return '/assets/img/card-back.png';
          if (url.indexOf('optcgapi.com') !== -1) return '/api/card-image?url=' + encodeURIComponent(url);
          return url;
        },

        init: function () {
          var self = this;
          if (typeof GameSocket !== 'undefined') {
            self.socket = GameSocket();
          } else if (window.__gameSocket) {
            self.socket = window.__gameSocket;
          }
          if (!self.socket) return;

          self.socket.on('gameState', function (data) {
            try {
              var plain = (data && typeof data === 'object') ? JSON.parse(JSON.stringify(data)) : data;
              self.state = plain;
              self.phase = (plain && plain.turn) ? plain.turn.phase : '';
              if (typeof plain.playerIndex === 'number') self.playerIndex = plain.playerIndex;

              if (plain && plain.log && plain.log.length > 0) {
                self.lastAction = plain.log[plain.log.length - 1].msg || '';
              }
            } catch (e) { /* ignore */ }
          });

          self.socket.on('actionResult', function (data) {
            if (data && !data.ok && data.reason) {
              self.notify(data.reason);
            }
          });

          self.socket.on('attackAnimation', function (data) {
            self.playAttackAnimation(data);
          });

          self.socket.on('botAttacks', function (attacks) {
            if (!attacks || !attacks.length) return;
            var delay = 0;
            for (var i = 0; i < attacks.length; i++) {
              (function (atk, d) {
                setTimeout(function () { self.playAttackAnimation(atk); }, d);
              })(attacks[i], delay);
              delay += 700;
            }
          });

          self.socket.on('gameOver', function (data) {
            try {
              self.gameOverData = (data && typeof data === 'object') ? JSON.parse(JSON.stringify(data)) : data;
            } catch (e) { /* ignore */ }
          });

          self.socket.emit('joinGame', { gameId: self.gameId, userId: self.userId });

          try {
            if (localStorage.getItem('optcg_tutorial_done') !== '1') {
              self.showTutorial = true;
              self.tutorialStep = 1;
            }
          } catch (e) { /* ignore */ }

          setInterval(function () { self.durationTick++; }, 1000);
        },

        nextTutorial: function () {
          if (this.tutorialStep < 5) this.tutorialStep++;
        },
        closeTutorial: function () {
          this.showTutorial = false;
          try { localStorage.setItem('optcg_tutorial_done', '1'); } catch (e) { /* ignore */ }
        },
        reopenTutorial: function () {
          this.tutorialStep = 1;
          this.showTutorial = true;
        },

        notify: function (msg) {
          var self = this;
          self.notification = msg;
          setTimeout(function () { self.notification = ''; }, 3000);
        },

        showPreview: function (card, e) {
          this.hoveredCard = card;
          this.previewX = Math.min((e ? e.clientX : 0) + 16, window.innerWidth - 320);
          this.previewY = Math.min((e ? e.clientY : 0) + 16, window.innerHeight - 240);
        },
        hidePreview: function () { this.hoveredCard = null; },
        previewMove: function (e) {
          if (this.hoveredCard) {
            this.previewX = Math.min(e.clientX + 16, window.innerWidth - 320);
            this.previewY = Math.min(e.clientY + 16, window.innerHeight - 240);
          }
        },

        isMyTurn: function () {
          return this.state && this.state.turn && this.state.turn.currentPlayerIndex === this.playerIndex && this.phase === 'main';
        },
        isFirstTurn: function () {
          return this.state && this.state.turn && this.state.turn.isFirstTurn;
        },
        turnCount: function () { return (this.state && this.state.turn) ? this.state.turn.turnCount : 1; },
        myUserId: function () { return (this.state && this.state.board && this.state.board.me) ? this.state.board.me.userId : 0; },

        me: function () { return (this.state && this.state.board) ? this.state.board.me : null; },
        opp: function () { return (this.state && this.state.board) ? this.state.board.opponent : null; },

        myLeader: function () { var m = this.me(); return m ? m.leader : null; },
        myStage: function () { var m = this.me(); return m ? m.stage : null; },
        myChars: function () { var m = this.me(); return (m && m.characters) ? m.characters : []; },
        oppLeader: function () { var o = this.opp(); return o ? o.leader : null; },
        oppStage: function () { var o = this.opp(); return o ? o.stage : null; },
        oppChars: function () { var o = this.opp(); return (o && o.characters) ? o.characters : []; },

        handList: function () {
          var m = this.me();
          return (m && Array.isArray(m.hand)) ? m.hand : [];
        },

        myLife: function () { var m = this.me(); return m ? m.lifeRemaining : 5; },
        myLostLife: function () {
          var m = this.me();
          var total = parseInt((m && m.leader && m.leader.life) || 5, 10);
          return Math.max(0, total - this.myLife());
        },
        oppLife: function () { var o = this.opp(); return o ? o.lifeRemaining : 5; },
        oppLostLife: function () {
          var o = this.opp();
          var total = parseInt((o && o.leader && o.leader.life) || 5, 10);
          return Math.max(0, total - this.oppLife());
        },
        oppHandCount: function () { var o = this.opp(); return o ? (o.handCount || 0) : 0; },
        oppDeckCount: function () { var o = this.opp(); return o ? (o.deckCount || 0) : 0; },
        myDeckCount: function () { var m = this.me(); return m ? (m.deckCount || 0) : 0; },
        myTrashCount: function () { var m = this.me(); return m ? (m.trashCount || 0) : 0; },
        myDonDeck: function () { var m = this.me(); return m ? (m.donDeck || 0) : 10; },

        myActiveDon: function () {
          var m = this.me();
          if (!m || !m.donArea) return 0;
          return m.donArea.filter(function (d) { return !d.rested; }).length;
        },
        myTotalDon: function () {
          var m = this.me();
          return (m && m.donArea) ? m.donArea.length : 0;
        },
        oppActiveDon: function () {
          var o = this.opp();
          if (!o || !o.donArea) return 0;
          return o.donArea.filter(function (d) { return !d.rested; }).length;
        },
        oppTotalDon: function () {
          var o = this.opp();
          return (o && o.donArea) ? o.donArea.length : 0;
        },

        /**
         * Rule 6-5-5-2: DON!! power boost only during owner's turn.
         * For display: my cards get +DON!! on my turn, opp cards don't.
         */
        cardPower: function (card, isOwnersTurn) {
          if (!card) return '';
          var base = parseInt(card.card_power, 10) || 0;
          var don = isOwnersTurn ? ((card.attachedDon || 0) * 1000) : 0;
          return base + don;
        },

        canPlayCard: function (card) {
          if (!this.isMyTurn()) return false;
          var cost = parseInt(card.card_cost, 10) || 0;
          return cost <= this.myActiveDon();
        },

        canAttackWith: function (type) {
          if (!this.isMyTurn()) return false;
          if (this.isFirstTurn()) return false;
          if (type === 'leader') {
            var l = this.myLeader();
            return l && !l.rested && !l.summonSick;
          }
          return false;
        },

        canAttackWithChar: function (idx) {
          if (!this.isMyTurn()) return false;
          if (this.isFirstTurn()) return false;
          var chars = this.myChars();
          var c = chars[idx];
          return c && !c.rested && !c.summonSick;
        },

        onHandCardClick: function (idx) {
          if (!this.isMyTurn()) return;
          this.socket.emit('playCard', { gameId: this.gameId, handIndex: idx });
        },

        onMyLeaderClick: function () {
          if (!this.isMyTurn()) return;
          if (this.isFirstTurn()) {
            this.notify('Cannot attack on your first turn');
            return;
          }
          var l = this.myLeader();
          if (!l || l.rested || l.summonSick) {
            this.notify('Leader is rested or cannot attack');
            return;
          }
          this.attackMode = true;
          this.attackSource = { type: 'leader', index: 0 };
          this.notify('Click opponent Leader or a rested Character');
        },

        onMyCharClick: function (idx) {
          if (!this.isMyTurn()) return;
          if (this.isFirstTurn()) {
            this.notify('Cannot attack on your first turn');
            return;
          }
          var c = this.myChars()[idx];
          if (!c) return;
          if (c.rested) { this.notify('This character is rested'); return; }
          if (c.summonSick) { this.notify('Just played — cannot attack this turn'); return; }
          this.attackMode = true;
          this.attackSource = { type: 'character', index: idx };
          this.notify('Click opponent Leader or a rested Character');
        },

        onOppLeaderClick: function () {
          if (!this.attackMode || !this.attackSource) return;
          var payload = {
            gameId: this.gameId,
            attackerType: this.attackSource.type,
            attackerIndex: this.attackSource.index,
            targetType: 'leader',
            targetIndex: 0
          };
          this.attackMode = false;
          this.attackSource = null;
          this.socket.emit('attack', payload);
        },

        onOppCharClick: function (idx) {
          if (!this.attackMode || !this.attackSource) return;
          var payload = {
            gameId: this.gameId,
            attackerType: this.attackSource.type,
            attackerIndex: this.attackSource.index,
            targetType: 'character',
            targetIndex: idx
          };
          this.attackMode = false;
          this.attackSource = null;
          this.socket.emit('attack', payload);
        },

        cancelAttack: function () {
          this.attackMode = false;
          this.attackSource = null;
        },

        gameDuration: function () {
          void this.durationTick;
          var s = Math.floor((Date.now() - this.gameStartTime) / 1000);
          var m = Math.floor(s / 60);
          s = s % 60;
          return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        gameLog: function () {
          return (this.state && this.state.log) ? this.state.log : [];
        },

        getCardElement: function (side, type, index) {
          if (side === 'my') {
            return type === 'leader' ? document.getElementById('my-leader') : document.getElementById('my-char-' + index);
          }
          return type === 'leader' ? document.getElementById('opp-leader') : document.getElementById('opp-char-' + index);
        },

        playAttackAnimation: function (data) {
          var self = this;
          var atkSide = data.side || 'my';
          var defSide = atkSide === 'my' ? 'opp' : 'my';
          var atkEl = self.getCardElement(atkSide, data.attackerType, data.attackerIndex || 0);
          var defEl = self.getCardElement(defSide, data.targetType, data.targetIndex || 0);

          if (!atkEl || !defEl) return;

          var atkRect = atkEl.getBoundingClientRect();
          var defRect = defEl.getBoundingClientRect();
          var layer = document.getElementById('atkAnimLayer');
          if (!layer) return;

          var ghost = document.createElement('div');
          ghost.className = 'atk-ghost';
          ghost.style.width = atkRect.width + 'px';
          ghost.style.height = atkRect.height + 'px';
          ghost.style.left = atkRect.left + 'px';
          ghost.style.top = atkRect.top + 'px';
          ghost.style.borderRadius = '12px';

          var img = atkEl.querySelector('img');
          if (img) {
            var ghostImg = document.createElement('img');
            ghostImg.src = img.src;
            ghostImg.style.width = '100%';
            ghostImg.style.height = '100%';
            ghostImg.style.objectFit = 'cover';
            ghostImg.style.borderRadius = '12px';
            ghost.appendChild(ghostImg);
          }

          layer.appendChild(ghost);

          var dx = (defRect.left + defRect.width / 2) - (atkRect.left + atkRect.width / 2);
          var dy = (defRect.top + defRect.height / 2) - (atkRect.top + atkRect.height / 2);

          requestAnimationFrame(function () {
            ghost.style.transform = 'translate(' + dx + 'px, ' + dy + 'px) scale(1.15)';
            ghost.style.transition = 'transform 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
          });

          setTimeout(function () {
            self.spawnImpact(defRect, layer, data.hit);
            if (data.hit) {
              self.shakeScreen();
              self.flashScreen();
              defEl.classList.add('anim-hit');
              setTimeout(function () { defEl.classList.remove('anim-hit'); }, 500);
            }
            ghost.style.transition = 'opacity 0.2s';
            ghost.style.opacity = '0';
            setTimeout(function () { if (ghost.parentNode) ghost.parentNode.removeChild(ghost); }, 250);
          }, 380);
        },

        spawnImpact: function (rect, layer, isHit) {
          var cx = rect.left + rect.width / 2;
          var cy = rect.top + rect.height / 2;
          var colors = isHit ? ['#ef4444', '#f97316', '#fbbf24', '#fff'] : ['#6b7280', '#9ca3af', '#fff'];
          var count = isHit ? 18 : 8;

          for (var i = 0; i < count; i++) {
            var p = document.createElement('div');
            p.className = 'atk-particle';
            var size = Math.random() * 8 + 4;
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            p.style.left = cx + 'px';
            p.style.top = cy + 'px';
            p.style.background = colors[Math.floor(Math.random() * colors.length)];
            var angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.6;
            var dist = 40 + Math.random() * 80;
            var tx = Math.cos(angle) * dist;
            var ty = Math.sin(angle) * dist;
            p.style.setProperty('--tx', tx + 'px');
            p.style.setProperty('--ty', ty + 'px');
            layer.appendChild(p);
            (function (el) { setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 600); })(p);
          }

          if (isHit) {
            var ring = document.createElement('div');
            ring.className = 'atk-ring';
            ring.style.left = cx + 'px';
            ring.style.top = cy + 'px';
            layer.appendChild(ring);
            setTimeout(function () { if (ring.parentNode) ring.parentNode.removeChild(ring); }, 500);
          }
        },

        shakeScreen: function () {
          var wrap = document.querySelector('.game-wrap');
          if (!wrap) return;
          wrap.classList.add('screen-shake');
          setTimeout(function () { wrap.classList.remove('screen-shake'); }, 400);
        },

        flashScreen: function () {
          this.screenFlash = true;
          var self = this;
          setTimeout(function () { self.screenFlash = false; }, 200);
        },

        endTurn: function () {
          if (!this.isMyTurn()) return;
          this.socket.emit('endTurn', { gameId: this.gameId });
        }
      };
    });
  });
})();
