<?php
$currentUserId = \App\Core\Auth::id();
$otherAvatarUrl = $otherUser ? \App\Models\User::getAvatarUrl($otherUser) : '';
$isSystem = !empty($otherUser['is_system']);
$convIdJson = (int)$conversation['id'];
$currentUserIdJson = $currentUserId;
$formattedMessages = \App\Controllers\MessageController::getFormattedMessages($messages);
?>

<script>window.__CONV_MESSAGES = <?= json_encode($formattedMessages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>

<div
    class="flex flex-col"
    style="height:calc(100vh - 4rem);background:var(--d950,#07090e)"
    x-data="conversationPage(<?= $convIdJson ?>, <?= $currentUserIdJson ?>)"
    x-init="messages = window.__CONV_MESSAGES || []; lastId = messages.length ? messages[messages.length-1].id : 0; init();"
    @dragover.prevent="uploadDragOver = true"
    @dragleave.prevent="uploadDragOver = false"
    @drop.prevent="onDrop($event)"
    :class="uploadDragOver ? 'ring-2 ring-inset ring-blue-500/40' : ''"
>
<div class="flex flex-col flex-1 max-w-5xl w-full mx-auto overflow-hidden" style="height:100%">

    <!-- ── Header ─────────────────────────────────── -->
    <header class="flex items-center gap-3 px-4 py-3 flex-shrink-0 border-b" style="border-color:var(--nav-border);background:var(--d900,#0f1117)">
        <a href="/messages" class="p-2 rounded-lg text-dark-400 hover:text-white hover:bg-dark-700 transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <?php if ($isSystem): ?>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gold-500 to-amber-600 flex items-center justify-center flex-shrink-0 shadow">
            <svg width="16" height="16" viewBox="0 0 36 36" fill="none"><rect x="8" y="7" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.25" transform="rotate(-8 8 7)"/><rect x="12" y="8" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.9" transform="rotate(5 20 18)"/><text x="18" y="22" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="10" fill="#92400e" letter-spacing="-0.5">OP</text></svg>
        </div>
        <?php elseif ($otherAvatarUrl): ?>
        <img src="<?= htmlspecialchars($otherAvatarUrl) ?>" class="w-9 h-9 rounded-full object-cover flex-shrink-0" alt="">
        <?php else: ?>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center font-bold text-white text-sm flex-shrink-0">
            <?= strtoupper(substr($otherUser['username'] ?? '?', 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-white text-sm truncate">
                <?= htmlspecialchars($otherUser['username'] ?? 'User') ?>
                <?php if ($isSystem): ?><span class="ml-1 text-[9px] font-bold text-gold-400 bg-gold-500/10 px-1.5 py-0.5 rounded">SYSTEM</span><?php endif; ?>
            </p>
        </div>
        <?php if (!$isSystem): ?>
        <a href="/user/<?= htmlspecialchars($otherUser['username'] ?? '') ?>" class="p-2 text-dark-400 hover:text-white hover:bg-dark-700 rounded-lg transition">
            <i data-lucide="user" class="w-4 h-4"></i>
        </a>
        <?php endif; ?>
    </header>

    <!-- ── Messages area ──────────────────────────── -->
    <div x-ref="msgContainer" class="flex-1 overflow-y-auto px-4 py-4" style="overscroll-behavior:contain">
        <template x-for="(msg, idx) in messages" :key="msg.id ?? ('opt-'+idx)">
            <div class="mb-1">
                <!-- Day separator -->
                <template x-if="idx === 0 || new Date((msg.created_at||'').replace(' ','T')).toDateString() !== new Date((messages[idx-1]?.created_at||'').replace(' ','T')).toDateString()">
                    <div class="flex items-center gap-3 my-4">
                        <div class="flex-1 h-px" style="background:var(--nav-border)"></div>
                        <span class="text-[10px] text-dark-500" x-text="new Date((msg.created_at||'').replace(' ','T')).toLocaleDateString([],{month:'short',day:'numeric',year:'numeric'})"></span>
                        <div class="flex-1 h-px" style="background:var(--nav-border)"></div>
                    </div>
                </template>

                <div class="flex items-end gap-2"
                     :class="msg.sender_id === currentUserId ? 'justify-end' : 'justify-start'"
                     @mouseenter="hoveredMsgId = msg.id"
                     @mouseleave="hoveredMsgId = null">

                    <!-- Avatar (received) -->
                    <template x-if="msg.sender_id !== currentUserId">
                        <div class="w-7 h-7 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center text-white text-xs font-bold self-end mb-0.5"
                             :style="msg.sender_is_system?'background:linear-gradient(135deg,#f59e0b,#d97706)':'background:linear-gradient(135deg,#3b82f6,#06b6d4)'">
                            <template x-if="msg.sender_avatar && !msg.sender_is_system">
                                <img :src="msg.sender_avatar" class="w-full h-full object-cover" alt="">
                            </template>
                            <template x-if="msg.sender_is_system">
                                <svg width="13" height="13" viewBox="0 0 36 36" fill="none"><rect x="8" y="7" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.25" transform="rotate(-8 8 7)"/><rect x="12" y="8" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.9" transform="rotate(5 20 18)"/><text x="18" y="22" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="7" fill="#92400e">OP</text></svg>
                            </template>
                            <template x-if="!msg.sender_avatar && !msg.sender_is_system">
                                <span x-text="avatarInitial(msg.sender_username)"></span>
                            </template>
                        </div>
                    </template>

                    <!-- Bubble column -->
                    <div class="max-w-[70%] flex flex-col" :class="msg.sender_id===currentUserId?'items-end':'items-start'">

                        <!-- Deleted -->
                        <template x-if="msg.is_deleted">
                            <div class="px-3 py-2 rounded-2xl text-xs italic opacity-40 border border-dark-600 text-dark-400"
                                 :class="msg.sender_id===currentUserId?'rounded-br-sm':'rounded-bl-sm'">Message deleted</div>
                        </template>

                        <!-- Active message -->
                        <template x-if="!msg.is_deleted">
                            <div class="flex items-center gap-1.5" :class="msg.sender_id===currentUserId?'flex-row-reverse':'flex-row'">

                                <!-- Action buttons (hover, own messages) -->
                                <div x-show="hoveredMsgId===msg.id && msg.sender_id===currentUserId && editingId!==msg.id"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-90"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     class="flex items-center gap-0.5 bg-dark-800 border border-dark-600 rounded-lg p-0.5 shadow-lg flex-shrink-0">
                                    <template x-if="msg.type==='text'">
                                        <button @click="startEdit(msg)" title="Edit"
                                                class="p-1.5 text-dark-400 hover:text-blue-400 transition rounded">
                                            <!-- pencil inline SVG -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                        </button>
                                    </template>
                                    <button @click="deleteMsg(msg.id)" title="Delete"
                                            class="p-1.5 text-dark-400 hover:text-red-400 transition rounded">
                                        <!-- trash inline SVG -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                </div>

                                <!-- Bubble -->
                                <div>
                                    <!-- IMAGE / GIF -->
                                    <template x-if="msg.type==='image'||msg.type==='gif'">
                                        <div class="flex flex-col gap-1" :class="msg.sender_id===currentUserId?'items-end':'items-start'">
                                            <div class="cursor-pointer rounded-2xl overflow-hidden relative"
                                                 :class="msg.sender_id===currentUserId?'rounded-br-sm':'rounded-bl-sm'"
                                                 @click="openLightbox(msg.media_url)">
                                                <img :src="msg.media_url" class="max-w-[220px] max-h-[260px] object-cover block hover:opacity-90 transition" :alt="msg.type">
                                                <span x-show="msg.type==='gif'" class="absolute top-1 left-1 text-[9px] font-bold bg-black/60 text-white px-1.5 py-0.5 rounded-full">GIF</span>
                                            </div>
                                            <!-- Caption text under the image -->
                                            <template x-if="msg.body && msg.body.trim()">
                                                <div class="px-3 py-2 rounded-2xl text-sm leading-relaxed break-words max-w-[220px]"
                                                     :class="msg.sender_id===currentUserId?'bg-blue-600 text-white rounded-br-sm':'text-white rounded-bl-sm'"
                                                     :style="msg.sender_id!==currentUserId?'background:var(--d700,#1e2130)':''"
                                                     x-html="renderText(msg.body)"></div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- TEXT -->
                                    <template x-if="msg.type==='text'">
                                        <div>
                                            <!-- Edit mode -->
                                            <template x-if="editingId===msg.id">
                                                <div class="flex items-end gap-1.5">
                                                    <textarea :id="'edit-input-'+msg.id" x-model="editBody"
                                                        @keydown.enter.exact.prevent="submitEdit()"
                                                        @keydown.escape="cancelEdit()"
                                                        rows="2"
                                                        class="px-3 py-2 bg-dark-700 border border-blue-500 rounded-xl text-sm text-white resize-none focus:outline-none w-56"></textarea>
                                                    <!-- confirm (check) -->
                                                    <button @click="submitEdit()" title="Save"
                                                            class="flex-shrink-0 w-7 h-7 flex items-center justify-center bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                    </button>
                                                    <!-- cancel (x) -->
                                                    <button @click="cancelEdit()" title="Cancel"
                                                            class="flex-shrink-0 w-7 h-7 flex items-center justify-center bg-dark-600 hover:bg-dark-500 text-dark-300 rounded-lg transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <!-- Normal view -->
                                            <template x-if="editingId!==msg.id">
                                                <div class="px-3 py-2.5 rounded-2xl text-sm leading-relaxed break-words"
                                                     :class="msg.sender_id===currentUserId?'bg-blue-600 text-white rounded-br-sm':'text-white rounded-bl-sm'"
                                                     :style="msg.sender_id!==currentUserId?'background:var(--d700,#1e2130)':''"
                                                     x-html="renderText(msg.body)"></div>
                                            </template>
                                        </div>
                                    </template>
                                </div><!-- /bubble -->
                            </div>
                        </template>

                        <!-- Timestamp -->
                        <div class="flex items-center gap-1 mt-0.5 px-1"
                             :class="msg.sender_id===currentUserId?'justify-end':'justify-start'">
                            <span x-show="msg.edited_at" class="text-[9px] text-dark-500 italic">edited</span>
                            <span class="text-[10px] text-dark-500" x-text="formatTime(msg.created_at)"></span>
                        </div>
                    </div>

                    <!-- Right spacer for sent -->
                    <template x-if="msg.sender_id===currentUserId">
                        <div class="w-7 flex-shrink-0"></div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Typing indicator -->
        <div x-show="typingUsers.length>0" x-transition class="flex items-center gap-2 justify-start pl-9 mt-2 mb-1">
            <div class="px-3 py-2.5 rounded-2xl rounded-bl-sm flex items-center gap-1" style="background:var(--d700,#1e2130)">
                <span class="typing-dot"></span>
                <span class="typing-dot" style="animation-delay:.15s"></span>
                <span class="typing-dot" style="animation-delay:.3s"></span>
            </div>
            <span class="text-xs text-dark-500" x-text="typingUsers.join(', ') + ' is typing...'"></span>
        </div>
    </div>

    <!-- ── Error bar ───────────────────────────────── -->
    <div x-show="error" x-transition class="px-4 py-2 bg-red-500/10 border-t border-red-500/20 flex items-center justify-between flex-shrink-0">
        <span class="text-red-400 text-sm" x-text="error"></span>
        <button @click="error=''" class="text-red-400 hover:text-red-300 ml-2 flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <!-- ── GIF picker panel ────────────────────────── -->
    <div x-show="gifOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="flex-shrink-0 border-t" style="border-color:var(--nav-border);background:var(--d900,#0f1117)">
        <div class="p-3">
            <!-- Search bar -->
            <div class="flex items-center gap-2 mb-3">
                <div class="relative flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-dark-400 pointer-events-none"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" x-model="gifQuery"
                        @input.debounce.500ms="searchGifs(gifQuery)"
                        placeholder="Search GIFs…"
                        class="w-full pl-8 pr-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-500 focus:outline-none focus:border-blue-500 transition">
                </div>
                <button @click="closeGifPicker()" class="p-1.5 text-dark-400 hover:text-white rounded-lg hover:bg-dark-700 transition flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <!-- Initial loading spinner -->
            <div x-show="gifLoading" class="flex justify-center py-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin text-dark-400"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </div>

            <!-- GIF grid + infinite scroll sentinel -->
            <div x-show="!gifLoading" class="max-h-52 overflow-y-auto" id="gif-scroll-container">
                <div x-show="gifResults.length > 0" class="grid grid-cols-4 sm:grid-cols-6 gap-1.5">
                    <template x-for="gif in gifResults" :key="gif.url">
                        <button @click="pickGif(gif.url)" class="aspect-square rounded-lg overflow-hidden hover:opacity-80 transition bg-dark-800 cursor-pointer">
                            <img :src="gif.preview" class="w-full h-full object-cover" loading="lazy" alt="gif">
                        </button>
                    </template>
                </div>

                <!-- Scroll sentinel (IntersectionObserver target) -->
                <div id="gif-scroll-sentinel" class="h-4 flex items-center justify-center mt-1">
                    <svg x-show="gifLoadingMore" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin text-dark-500"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                </div>
            </div>

            <p x-show="!gifLoading && gifQuery.trim() && !gifResults.length" class="text-center py-3 text-dark-500 text-sm">No results for "<span x-text="gifQuery"></span>"</p>
            <p class="text-center mt-1 text-dark-600 text-[10px]">Powered by Klipy</p>
        </div>
    </div>

    <!-- ── Pending attachment preview ─────────────── -->
    <div x-show="pendingAttachment" x-transition class="flex-shrink-0 px-4 pt-3 pb-0 flex items-end gap-2" style="background:var(--d900,#0f1117)">
        <template x-if="pendingAttachment">
            <div class="relative inline-block">
                <img :src="pendingAttachment.previewUrl" class="h-20 w-auto max-w-[120px] rounded-xl object-cover border-2 border-blue-500/40 shadow-lg" alt="attachment preview">
                <span x-show="pendingAttachment.type==='gif'" class="absolute top-1 left-1 text-[8px] font-bold bg-black/70 text-white px-1 py-0.5 rounded-full">GIF</span>
                <button @click="clearAttachment()" title="Remove"
                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 hover:bg-red-400 rounded-full flex items-center justify-center text-white shadow transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- ── Input bar ───────────────────────────────── -->
    <div class="flex-shrink-0 px-4 py-3 border-t" style="border-color:var(--nav-border);background:var(--d900,#0f1117)">
        <div class="flex items-end gap-2">

            <!-- Left buttons: paperclip + GIF -->
            <div class="flex items-end gap-1 flex-shrink-0 self-end mb-0.5">
                <label class="w-9 h-9 flex items-center justify-center text-dark-400 hover:text-white hover:bg-dark-700 rounded-lg transition cursor-pointer flex-shrink-0" title="Attach image">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="onFilePick($event)">
                </label>
                <?php if (!$isSystem): ?>
                <button @click="gifOpen ? closeGifPicker() : openGifPicker()"
                        class="w-9 h-9 flex items-center justify-center rounded-lg transition text-xs font-bold border flex-shrink-0"
                        :class="gifOpen?'text-blue-300 bg-blue-500/15 border-blue-500/30':'text-dark-400 hover:text-white border-dark-600 hover:border-dark-500 hover:bg-dark-700'">
                    GIF
                </button>
                <?php endif; ?>
            </div>

            <!-- Textarea -->
            <div class="flex-1 min-w-0">
                <textarea
                    x-model="messageBody"
                    @keydown="onKeydown($event)"
                    rows="1"
                    placeholder="<?= htmlspecialchars(t('messages.type_placeholder')) ?>"
                    class="w-full px-4 py-2.5 bg-dark-800 border border-dark-600 rounded-2xl text-sm text-white placeholder-dark-500 focus:outline-none focus:border-blue-500/50 resize-none leading-relaxed transition block"
                    style="max-height:120px;overflow-y:auto;min-height:40px"
                    @input="$el.style.height='auto';$el.style.height=Math.min($el.scrollHeight,120)+'px'"
                ></textarea>
            </div>

            <!-- Send button -->
            <button
                @click="send()"
                :disabled="(!messageBody.trim() && !pendingAttachment) || sending || uploading"
                class="flex-shrink-0 w-9 h-9 self-end mb-0.5 rounded-xl flex items-center justify-center transition"
                :class="(messageBody.trim()||pendingAttachment)?'bg-blue-600 hover:bg-blue-500 text-white shadow':'bg-dark-700 text-dark-500 cursor-not-allowed'"
            >
                <!-- spinner -->
                <svg x-show="sending||uploading" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <!-- send icon -->
                <svg x-show="!sending && !uploading" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            </button>
        </div>
        <!-- Drag-over hint -->
        <p x-show="uploadDragOver" class="text-center text-sm text-blue-400 mt-2 font-medium">Drop image here to attach</p>
    </div>

</div><!-- /max-width wrapper -->

    <!-- ── Lightbox ────────────────────────────────── -->
    <div x-show="lightboxUrl" x-cloak x-transition @click="closeLightbox()"
         class="fixed inset-0 bg-black/90 z-[200] flex items-center justify-center cursor-zoom-out"
         style="backdrop-filter:blur(4px)">
        <img :src="lightboxUrl" class="max-w-[90vw] max-h-[90vh] object-contain rounded-xl shadow-2xl" @click.stop>
        <button @click="closeLightbox()" class="absolute top-4 right-4 w-10 h-10 bg-white/10 rounded-full flex items-center justify-center text-white hover:bg-white/20 transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>

    <!-- ── Exit warning modal ─────────────────────── -->
    <div x-show="exitWarning.show" x-cloak x-transition
         class="fixed inset-0 bg-black/70 z-[210] flex items-center justify-center p-4"
         style="backdrop-filter:blur(4px)"
         @click.self="exitWarning.show=false">
        <div class="bg-dark-800 border border-dark-600 rounded-2xl shadow-2xl max-w-sm w-full p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">Leaving MyOPCards</p>
                    <p class="text-dark-400 text-xs">You're about to visit an external site</p>
                </div>
            </div>
            <p class="text-dark-300 text-sm break-all font-mono bg-dark-900 rounded-lg px-3 py-2 text-xs mb-2" x-text="exitWarning.url"></p>
            <p class="text-dark-500 text-xs mb-5">External links may be dangerous. Only continue if you trust this source.</p>
            <div class="flex gap-3">
                <button @click="exitWarning.show=false" class="flex-1 px-4 py-2 bg-dark-700 text-dark-200 rounded-xl text-sm font-semibold hover:bg-dark-600 transition">Cancel</button>
                <a :href="exitWarning.url" target="_blank" rel="noopener noreferrer" @click="exitWarning.show=false"
                   class="flex-1 px-4 py-2 bg-amber-500 text-white rounded-xl text-sm font-semibold hover:bg-amber-400 transition text-center">
                    Continue
                </a>
            </div>
        </div>
    </div>

</div>

<style>
.typing-dot {
    display:inline-block; width:6px; height:6px;
    border-radius:50%; background:#6b7280;
    animation: typing-bounce 1s infinite;
}
@keyframes typing-bounce {
    0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-5px)}
}
</style>

<script src="/assets/js/pages/messages.js"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.lucide) lucide.createIcons(); });</script>
