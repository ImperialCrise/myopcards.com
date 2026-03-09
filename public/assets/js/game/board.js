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
        donAttachMode: false,
        phase: '',
        gameStartTime: Date.now(),
        durationTick: 0,
        showTutorial: false,
        tutorialStep: 1,
        screenFlash: false,
        gameOverData: null,

        cardImageSrc: function (url) {
          if (url == null || typeof url !== 'string') return '/assets/img/card-back.png';
          if (url.indexOf('optcgapi.com') !== -1) return '/uploads/cards/' + url.split('/').pop();
          return url;
        },

        formatCardText: function (text) {
          if (!text) return '';
          var s = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
          s = s.replace(/\[(Rush|Blocker|Double Attack|Banish|On K\.O\.)\]/g, '<span class="kw-badge kw-red">$1</span>');
          s = s.replace(/\[(Trigger)\]/g, '<span class="kw-badge kw-yellow">$1</span>');
          s = s.replace(/\[(On Play|When Attacking)\]/g, '<span class="kw-badge kw-blue">$1</span>');
          s = s.replace(/\[(Activate: Main)\]/g, '<span class="kw-badge kw-green">$1</span>');
          s = s.replace(/\[(Counter)\]/g, '<span class="kw-badge kw-purple">$1</span>');
          s = s.replace(/\[(Once Per Turn)\]/g, '<span class="kw-badge kw-gray">$1</span>');
          s = s.replace(/\[(Your Turn|End of Your Turn|Opponent\'s Turn)\]/g, '<span class="kw-badge kw-gray">$1</span>');
          s = s.replace(/DON!!/g, '<span class="don-inline">DON!!</span>');
          s = s.replace(/(\+\d+000)/g, '<span class="pow-inline">$1</span>');
          s = s.replace(/\n/g, '<br>');
          return s;
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

        nextTutorial: function () { if (this.tutorialStep < 5) this.tutorialStep++; },
        closeTutorial: function () {
          this.showTutorial = false;
          try { localStorage.setItem('optcg_tutorial_done', '1'); } catch (e) { /* ignore */ }
        },
        reopenTutorial: function () { this.tutorialStep = 1; this.showTutorial = true; },

        notify: function (msg) {
          var self = this;
          self.notification = msg;
          setTimeout(function () { self.notification = ''; }, 3500);
        },

        showPreview: function (card, e) {
          this.hoveredCard = card;
          this._positionPreview(e);
        },
        hidePreview: function () { this.hoveredCard = null; },
        previewMove: function (e) {
          if (this.hoveredCard) this._positionPreview(e);
        },
        _positionPreview: function (e) {
          var cx = e ? e.clientX : 0;
          var cy = e ? e.clientY : 0;
          var pw = 420;
          var x = cx + 16;
          if (x + pw > window.innerWidth) x = cx - pw - 16;
          this.previewX = Math.max(4, x);
          var popup = document.querySelector('.card-preview-popup');
          var ph = popup ? popup.offsetHeight : 300;
          var y = cy + 16;
          if (y + ph > window.innerHeight) y = cy - ph - 8;
          this.previewY = Math.max(4, y);
        },

        isMyTurn: function () {
          return this.state && this.state.turn && this.state.turn.currentPlayerIndex === this.playerIndex && this.phase === 'main';
        },
        isFirstTurn: function () {
          return this.state && this.state.turn && this.state.turn.isFirstTurn;
        },
        turnCount: function () { return (this.state && this.state.turn) ? this.state.turn.turnCount : 1; },
        me: function () { return (this.state && this.state.board) ? this.state.board.me : null; },
        opp: function () { return (this.state && this.state.board) ? this.state.board.opponent : null; },
        myLeader: function () { var m = this.me(); return m ? m.leader : null; },
        myStage: function () { var m = this.me(); return m ? m.stage : null; },
        myChars: function () { var m = this.me(); return (m && m.characters) ? m.characters : []; },
        oppLeader: function () { var o = this.opp(); return o ? o.leader : null; },
        oppStage: function () { var o = this.opp(); return o ? o.stage : null; },
        oppChars: function () { var o = this.opp(); return (o && o.characters) ? o.characters : []; },
        handList: function () { var m = this.me(); return (m && Array.isArray(m.hand)) ? m.hand : []; },
        myLife: function () { var m = this.me(); return m ? m.lifeRemaining : 5; },
        myLostLife: function () {
          var m = this.me();
          var total = parseInt((m && m.lifeStartCount) || (m && m.leader && m.leader.life) || 5, 10);
          return Math.max(0, total - this.myLife());
        },
        oppLife: function () { var o = this.opp(); return o ? o.lifeRemaining : 5; },
        oppLostLife: function () {
          var o = this.opp();
          var total = parseInt((o && o.lifeStartCount) || (o && o.leader && o.leader.life) || 5, 10);
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
        myTotalDon: function () { var m = this.me(); return (m && m.donArea) ? m.donArea.length : 0; },
        oppActiveDon: function () {
          var o = this.opp();
          if (!o || !o.donArea) return 0;
          return o.donArea.filter(function (d) { return !d.rested; }).length;
        },
        oppTotalDon: function () { var o = this.opp(); return (o && o.donArea) ? o.donArea.length : 0; },

        cardPower: function (card) {
          if (!card) return '';
          var base = parseInt(card.card_power, 10) || 0;
          var don = (card.attachedDon || 0) * 1000;
          return base + don;
        },

        canPlayCard: function (card) {
          if (!this.isMyTurn()) return false;
          if (this.hasCombatPrompt()) return false;
          var cost = parseInt(card.card_cost, 10) || 0;
          return cost <= this.myActiveDon();
        },

        canAttackWith: function (type) {
          if (!this.isMyTurn()) return false;
          if (this.isFirstTurn()) return false;
          if (this.hasCombatPrompt()) return false;
          if (type === 'leader') {
            var l = this.myLeader();
            return l && !l.rested && !l.summonSick;
          }
          return false;
        },

        canAttackWithChar: function (idx) {
          if (!this.isMyTurn()) return false;
          if (this.isFirstTurn()) return false;
          if (this.hasCombatPrompt()) return false;
          var chars = this.myChars();
          var c = chars[idx];
          return c && !c.rested && !c.summonSick;
        },

        hasCombatPrompt: function () {
          return this.state && this.state.combat && this.state.combat.isDefender;
        },

        combatInfo: function () {
          return (this.state && this.state.combat) ? this.state.combat : null;
        },

        combatBlockers: function () {
          var c = this.combatInfo();
          return (c && c.availableBlockers) ? c.availableBlockers : [];
        },

        combatCounters: function () {
          var c = this.combatInfo();
          return (c && c.counterCards) ? c.counterCards : [];
        },

        onUseBlocker: function (charIndex) {
          this.socket.emit('useBlocker', { gameId: this.gameId, charIndex: charIndex });
        },

        onPlayCounter: function (handIndex) {
          this.socket.emit('playCounter', { gameId: this.gameId, handIndex: handIndex });
        },

        onFinishDefense: function () {
          this.socket.emit('finishDefense', { gameId: this.gameId });
        },

        onHandCardClick: function (idx) {
          if (!this.isMyTurn()) return;
          if (this.hasCombatPrompt()) return;
          if (this.attackMode) { this.cancelAttack(); return; }
          this.socket.emit('playCard', { gameId: this.gameId, handIndex: idx });
        },

        onMyLeaderClick: function () {
          if (this.donAttachMode) {
            this.socket.emit('attachDon', { gameId: this.gameId, targetType: 'leader', targetIndex: 0 });
            return;
          }
          if (!this.isMyTurn()) return;
          if (this.hasCombatPrompt()) return;
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
          this.notify('Select target: Leader or rested Character');
        },

        onMyCharClick: function (idx) {
          if (this.donAttachMode) {
            this.socket.emit('attachDon', { gameId: this.gameId, targetType: 'character', targetIndex: idx });
            return;
          }
          if (!this.isMyTurn()) return;
          if (this.hasCombatPrompt()) return;
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
          this.notify('Select target: Leader or rested Character');
        },

        onOppLeaderClick: function () {
          if (!this.attackMode || !this.attackSource) return;
          this.socket.emit('attack', {
            gameId: this.gameId,
            attackerType: this.attackSource.type,
            attackerIndex: this.attackSource.index,
            targetType: 'leader',
            targetIndex: 0
          });
          this.attackMode = false;
          this.attackSource = null;
        },

        onOppCharClick: function (idx) {
          if (!this.attackMode || !this.attackSource) return;
          this.socket.emit('attack', {
            gameId: this.gameId,
            attackerType: this.attackSource.type,
            attackerIndex: this.attackSource.index,
            targetType: 'character',
            targetIndex: idx
          });
          this.attackMode = false;
          this.attackSource = null;
        },

        cancelAttack: function () {
          this.attackMode = false;
          this.attackSource = null;
        },

        toggleDonAttach: function () {
          if (!this.isMyTurn()) return;
          this.donAttachMode = !this.donAttachMode;
          if (this.donAttachMode) {
            this.attackMode = false;
            this.attackSource = null;
            this.notify('Click your Leader or Character to attach DON!! (+1000 power)');
          }
        },

        gameDuration: function () {
          void this.durationTick;
          var s = Math.floor((Date.now() - this.gameStartTime) / 1000);
          var m = Math.floor(s / 60);
          s = s % 60;
          return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        gameLog: function () { return (this.state && this.state.log) ? this.state.log : []; },

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
            } else {
              self.spawnShield(defRect, layer);
              defEl.classList.add('anim-blocked');
              setTimeout(function () { defEl.classList.remove('anim-blocked'); }, 600);
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
            p.style.setProperty('--tx', Math.cos(angle) * dist + 'px');
            p.style.setProperty('--ty', Math.sin(angle) * dist + 'px');
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

        spawnShield: function (rect, layer) {
          var cx = rect.left + rect.width / 2;
          var cy = rect.top + rect.height / 2;

          var shield = document.createElement('div');
          shield.className = 'shield-effect';
          shield.style.left = cx + 'px';
          shield.style.top = cy + 'px';
          shield.innerHTML = '<svg width="80" height="90" viewBox="0 0 80 90"><path d="M40 5 L75 20 V55 C75 70 40 85 40 85 C40 85 5 70 5 55 V20 Z" fill="none" stroke="currentColor" stroke-width="3"/></svg>';
          layer.appendChild(shield);
          setTimeout(function () { if (shield.parentNode) shield.parentNode.removeChild(shield); }, 700);

          var txt = document.createElement('div');
          txt.className = 'shield-text';
          txt.style.left = cx + 'px';
          txt.style.top = (cy - 50) + 'px';
          txt.textContent = 'BLOCKED!';
          layer.appendChild(txt);
          setTimeout(function () { if (txt.parentNode) txt.parentNode.removeChild(txt); }, 900);

          for (var i = 0; i < 12; i++) {
            var spark = document.createElement('div');
            spark.className = 'shield-spark';
            var size = Math.random() * 6 + 3;
            spark.style.width = size + 'px';
            spark.style.height = size + 'px';
            spark.style.left = cx + 'px';
            spark.style.top = cy + 'px';
            var angle = (Math.PI * 2 * i) / 12 + (Math.random() - 0.5) * 0.5;
            var dist = 30 + Math.random() * 60;
            spark.style.setProperty('--tx', Math.cos(angle) * dist + 'px');
            spark.style.setProperty('--ty', Math.sin(angle) * dist + 'px');
            layer.appendChild(spark);
            (function (el) { setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 600); })(spark);
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
          if (this.hasCombatPrompt()) return;
          this.donAttachMode = false;
          this.attackMode = false;
          this.socket.emit('endTurn', { gameId: this.gameId });
        }
      };
    });
  });
})();
