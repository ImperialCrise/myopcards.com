const rooms = new Map();
function getRoom(roomId) { return rooms.get(roomId) || null; }
function setRoom(roomId, data) { rooms.set(roomId, data); }
function deleteRoom(roomId) { rooms.delete(roomId); }
module.exports = { getRoom, setRoom, deleteRoom };
