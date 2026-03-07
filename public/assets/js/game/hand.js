(function () {
  function renderHand(cards, opts) {
    opts = opts || {};
    var container = opts.container;
    if (!container) return;
    container.innerHTML = "";
    (cards || []).forEach(function (c, i) {
      var el = document.createElement("img");
      el.className = "game-card";
      el.src = c.card_image_url || "/assets/img/card-back.png";
      el.alt = c.card_name || "";
      el.dataset.index = i;
      el.draggable = !!opts.draggable;
      if (opts.onClick) {
        el.addEventListener("click", function (e) {
          e.preventDefault();
          e.stopPropagation();
          opts.onClick(c, i);
        });
      }
      if (opts.draggable) {
        el.addEventListener("dragstart", function (e) {
          e.dataTransfer.setData("text/plain", String(i));
          e.dataTransfer.effectAllowed = "move";
          el.classList.add("dragging");
        });
        el.addEventListener("dragend", function () {
          el.classList.remove("dragging");
        });
      }
      container.appendChild(el);
    });
  }
  window.GameHand = { render: renderHand };
})();
