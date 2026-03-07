(function () {
  function gameSocket() {
    if (window.__gameSocket) {
      console.log('[GameSocket] returning existing __gameSocket');
      return window.__gameSocket;
    }
    console.log('[GameSocket] creating new socket');
    var url = (window.location.protocol === "https:" ? "wss:" : "ws:") + "//" + window.location.host;
    var socket = typeof io !== "undefined" ? io(url, { path: "/socket.io/", withCredentials: true }) : null;
    var wrap = {
      _raw: socket,
      on: function (ev, fn) { if (socket) socket.on(ev, fn); },
      emit: function (ev, data) { if (socket) socket.emit(ev, data); },
      off: function (ev) { if (socket) socket.off(ev); }
    };
    return wrap;
  }
  window.GameSocket = gameSocket;
})();
