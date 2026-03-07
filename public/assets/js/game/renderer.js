(function () {
  function renderCardSlot(container, card, opts) {
    opts = opts || {};
    if (!container) return null;
    container.innerHTML = '';
    if (!card) return null;
    var el = document.createElement('img');
    el.className = 'game-card' + (card.rested ? ' rested' : '');
    el.src = (typeof window.getCardImageSrc === 'function' ? window.getCardImageSrc(card.card_image_url) : null) || card.card_image_url || '/assets/img/card-back.png';
    el.alt = card.card_name || '';
    if (opts.onClick) el.addEventListener('click', function () { opts.onClick(card); });
    container.appendChild(el);
    return el;
  }

  function renderZone(container, items, opts) {
    if (!container) return;
    container.innerHTML = '';
    (items || []).forEach(function (item) {
      renderCardSlot(container, item, opts);
    });
  }

  function renderDonArea(container, donList) {
    if (!container) return;
    container.innerHTML = '';
    (donList || []).forEach(function (d) {
      var el = document.createElement('div');
      el.className = 'game-card-slot don-chip';
      el.textContent = 'DON!!';
      el.title = d.rested ? 'Rested' : 'Active';
      if (d.rested) el.classList.add('rested');
      container.appendChild(el);
    });
  }

  window.GameRenderer = {
    renderCardSlot: renderCardSlot,
    renderZone: renderZone,
    renderDonArea: renderDonArea
  };
})();
