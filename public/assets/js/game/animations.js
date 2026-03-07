(function () {
  function addOnce(el, className, duration) {
    if (!el) return;
    el.classList.add(className);
    duration = duration || 400;
    setTimeout(function () { el.classList.remove(className); }, duration);
  }

  function cardDraw(el) {
    addOnce(el, 'anim-draw', 350);
  }

  function cardPlay(el) {
    addOnce(el, 'anim-play', 300);
  }

  function cardAttack(el, dx, dy) {
    if (!el) return;
    el.style.setProperty('--ax', (dx || 20) + 'px');
    el.style.setProperty('--ay', (dy || -30) + 'px');
    addOnce(el, 'anim-attack', 500);
  }

  function screenDamage(container) {
    addOnce(container || document.querySelector('.game-board'), 'anim-damage', 300);
  }

  function cardKo(el) {
    if (!el) return;
    el.classList.add('anim-ko');
    setTimeout(function () {
      el.remove();
    }, 400);
  }

  function donAttach(el) {
    addOnce(el, 'anim-don-attach', 400);
  }

  function phaseTransition(el) {
    addOnce(el, 'anim-phase', 250);
  }

  window.GameAnimations = {
    cardDraw: cardDraw,
    cardPlay: cardPlay,
    cardAttack: cardAttack,
    screenDamage: screenDamage,
    cardKo: cardKo,
    donAttach: donAttach,
    phaseTransition: phaseTransition
  };
})();
