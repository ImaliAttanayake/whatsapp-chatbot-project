@php
  $initialHumanHandoffChatCount = $contacts->filter(fn ($chatContact) => (bool) $chatContact->is_bot_paused)->count();
  $initialHumanHandoffUnreadMessageCount = $contacts->sum(
      fn ($chatContact) => (bool) $chatContact->is_bot_paused ? (int) $chatContact->unread_count : 0
  );
@endphp

<x-app-layout>
  <div class="max-w-7xl mx-auto p-3 sm:p-4 chat-shell-height min-h-0" x-data="chatApp({{ $contact->id }})" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-full min-h-0">
      {{-- Sidebar --}}
      <div class="lg:col-span-1 rounded-2xl bg-white/5 border border-white/10 overflow-hidden flex flex-col h-full min-h-0"
           x-ref="sidebar"
           :class="sidebarOpen ? '' : 'hidden lg:block'">
        <div class="p-4 border-b border-white/10">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-slt-accent"></span>
              <span class="font-semibold text-white">Chats</span>
            </div>
            <button class="lg:hidden px-3 py-2 rounded-xl border border-white/10 text-slt-muted hover:text-white" @click="sidebarOpen=false">Close</button>
          </div>
          <div class="mt-3 grid grid-cols-2 gap-2">
            <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2">
              <div class="text-lg font-semibold text-white" data-chat-stat="human_handoff_chat_count">{{ $initialHumanHandoffChatCount }}</div>
              <div class="text-[11px] text-slt-muted">Human chats</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2">
              <div class="text-lg font-semibold text-white" data-chat-stat="human_handoff_unread_message_count">{{ $initialHumanHandoffUnreadMessageCount }}</div>
              <div class="text-[11px] text-slt-muted">Unread msgs</div>
            </div>
          </div>
        </div>

        <!-- Sync Inbox Button -->
        <div class="p-3 border-b border-white/10">
          <form method="POST" action="{{ route('contacts.syncRecent') }}" class="w-full" @submit.prevent="manualSync()">
            @csrf
            <input type="hidden" name="limit" value="{{ (int) config('chat.sync_recent_limit', 40) }}" />
            <button type="submit"
              class="w-full px-4 py-2.5 rounded-xl border border-white/10 text-slt-muted hover:text-white hover:bg-white/5 flex items-center justify-center gap-2 text-sm transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Sync Inbox (Find New Chats)
            </button>
          </form>
        </div>

        <div id="chatList" x-ref="chatList" class="chat-list-scroll divide-y divide-white/5 flex-1 min-h-0 overflow-y-auto scrollbar-dark touch-pan-y">
          @include('chats.partials.list-items', [
            'contacts' => $contacts,
            'showPreview' => true,
            'showLock' => true,
            'showActive' => true,
            'activeContactId' => $contact->id,
          ])
        </div>
      </div>

      {{-- Chat panel --}}
      <div class="lg:col-span-2 rounded-2xl bg-white/5 border border-white/10 overflow-hidden flex flex-col h-full min-h-0">
        <!-- Chat Header -->
        <div class="p-4 border-b border-white/10 bg-white/5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
              <button class="lg:hidden px-3 py-2 rounded-xl border border-white/10 text-slt-muted hover:text-white" @click="sidebarOpen=true; $nextTick(() => setListHeight())">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <div class="w-10 h-10 rounded-full bg-slt-info flex items-center justify-center text-white font-semibold">
                {{ strtoupper(substr($contact->name ?? $contact->mobile, 0, 1)) }}
              </div>
              <div class="min-w-0">
                <div class="font-semibold text-white truncate">{{ $contact->name ?? $contact->mobile }}</div>
                <div class="flex items-center gap-1 text-xs text-slt-muted truncate">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  {{ $contact->mobile }}
                </div>
              </div>
            </div>

            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
              <!-- Save Contact Button -->
              <button @click="openSaveContact()" class="px-3 py-2 rounded-xl border border-slt-accent text-slt-accent hover:bg-slt-accent/10 text-sm flex items-center gap-2 whitespace-nowrap transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Save Contact
              </button>

              <!-- Take Chat (Lock) Button -->
              <button
                @click="acquireLock()"
                class="px-3 py-2 rounded-xl text-sm flex items-center gap-2 whitespace-nowrap transition-all"
                :class="lock.locked_by_me
                  ? 'bg-slt-accent text-white'
                  : (lock.locked && !lock.locked_by_me
                    ? 'bg-red-500/20 border border-red-500/50 text-red-400 cursor-not-allowed'
                    : 'border border-white/10 text-slt-muted hover:text-white hover:bg-white/5')"
                :disabled="lock.locked && !lock.locked_by_me"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span x-text="lock.locked_by_me ? 'Chat Locked by You' : (lock.locked ? 'Locked' : 'Take Chat')"></span>
              </button>

              <!-- Sync Button -->
              <button class="px-3 py-2 rounded-xl border border-white/10 text-slt-muted hover:text-white hover:bg-white/5 text-sm flex items-center gap-2 whitespace-nowrap transition-all" @click="manualSync()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync
              </button>

              <!-- Connection Status -->
              <div class="flex items-center gap-2 px-3 py-2 rounded-xl border border-white/10 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full" :class="online ? 'bg-slt-accent status-online' : 'bg-slt-muted'"></span>
                <span class="text-sm" :class="online ? 'text-slt-accent' : 'text-slt-muted'" x-text="online ? 'Connected' : 'Offline'"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 min-h-0 p-4 space-y-3 overflow-y-auto bg-slt-ink/50 scrollbar-dark" x-ref="scroll">
          <template x-for="(m, index) in messages" :key="m.id + '_' + m.uuid">
            <div>
              <template x-if="shouldShowDaySeparator(index)">
                <div class="flex items-center justify-center my-4">
                  <span class="px-3 py-1 rounded-full bg-white/10 text-xs text-slt-muted" x-text="formatDayLabel(m.sent_at)"></span>
                </div>
              </template>

              <div class="flex" :class="m.direction === 'out' ? 'justify-end' : 'justify-start'">
                <div class="max-w-[75%] rounded-2xl px-4 py-2"
                     :class="m.direction === 'out'
                          ? 'bg-slt-primary text-white'
                          : 'bg-white/10 text-white'">
                  <!-- Human Agent Request Badge -->
                  <template x-if="isHumanHandoffMessage(m)">
                    <div class="mb-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold animate-pulse"
                         :class="handoff.status === 'assigned_to_agent'
                           ? 'bg-emerald-500/20 border border-emerald-400/40 text-emerald-300'
                           : 'bg-amber-500/20 border border-amber-400/40 text-amber-300 shadow-[0_0_12px_rgba(245,158,11,0.3)]'">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <span x-text="handoff.status === 'assigned_to_agent' ? 'Assigned to Human Agent' : 'Human Agent Requested'"></span>
                    </div>
                  </template>
                  <div class="whitespace-pre-wrap" x-text="m.body"></div>
                  <div class="flex items-center justify-end gap-1 mt-1">
                    <span class="text-[11px] opacity-70" x-text="formatTime(m.sent_at, m.time_hint)"></span>
                    <template x-if="m.direction === 'out'">
                      <svg class="w-3 h-3 opacity-70" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                      </svg>
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div class="text-sm text-slt-muted text-center py-8" x-show="messages.length===0">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p>No messages yet</p>
            <p class="text-xs mt-1">Messages are loaded directly from the SLT API.</p>
          </div>
        </div>

        <!-- Message Input Area -->
        <div class="p-4 border-t border-white/10 bg-white/5">
          <!-- Locked by other admin warning -->
          <div x-show="lock.locked && !lock.locked_by_me" class="mb-3 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-start gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>This chat is locked by <strong x-text="lock.locked_by"></strong>. Wait for them to release it or the lock will expire in 30 minutes.</span>
          </div>

          <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="send()">
            <div class="flex-1 relative">
              <input
                x-model="draft"
                placeholder="Type a message..."
                :disabled="lock.locked && !lock.locked_by_me"
                class="w-full rounded-2xl bg-white/5 border-white/10 text-white placeholder-slt-muted pr-12 focus:border-slt-primary focus:ring-slt-primary disabled:opacity-50 disabled:cursor-not-allowed"
              />
            </div>
            <button
              type="submit"
              class="w-full sm:w-auto px-6 py-2 rounded-2xl bg-slt-accent text-white hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all"
              :disabled="sending || !draft.trim() || (lock.locked && !lock.locked_by_me)">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
              <span x-show="!sending">Send</span>
              <span x-show="sending">Sending...</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Save Contact Modal -->
  <dialog id="saveContactModal" class="rounded-2xl p-0 w-[calc(100%-1.5rem)] sm:w-full max-w-lg mx-3 sm:mx-auto backdrop:bg-black/50">
    <form method="POST" action="{{ route('contacts.update', $contact) }}" class="bg-slt-ink rounded-2xl overflow-hidden">
      @csrf
      @method('PUT')
      <div class="gradient-header p-4 text-white">
        <h3 class="font-semibold text-lg">Save Contact</h3>
        <p class="text-sm text-white/80 mt-0.5">Add a name for this number</p>
      </div>
      <div class="p-6 space-y-4">
        <div>
          <label class="text-sm text-slt-muted block mb-2">Name</label>
          <input name="name" value="{{ $contact->name }}" placeholder="John Doe"
            class="w-full rounded-xl bg-white/5 border-white/10 text-white placeholder-slt-muted focus:border-slt-primary focus:ring-slt-primary" />
        </div>
        <div>
          <label class="text-sm text-slt-muted block mb-2">Mobile Number</label>
          <input name="mobile" value="{{ $contact->mobile }}" readonly
            class="w-full rounded-xl bg-white/5 border-white/10 text-slt-muted" />
        </div>
      </div>
      <div class="p-4 border-t border-white/10 flex flex-col-reverse sm:flex-row justify-end gap-3">
        <button type="button" onclick="document.getElementById('saveContactModal').close()"
          class="w-full sm:w-auto text-center px-4 py-2 rounded-xl border border-white/10 text-slt-muted hover:text-white hover:bg-white/5 transition-all">
          Cancel
        </button>
        <button type="submit"
          class="w-full sm:w-auto text-center px-6 py-2 rounded-xl bg-slt-primary text-white hover:bg-slt-primary-600 transition-all">
          Save Contact
        </button>
      </div>
    </form>
  </dialog>

  <script>
    function chatApp(contactId) {
      return {
        sidebarOpen: false,
        online: true,
        sending: false,
        draft: '',
        messages: [],
        contactName: @js($contact->name ?? $contact->mobile),
        handoff: @js([
          'active' => (bool) $contact->is_bot_paused,
          'status' => $contact->human_handoff_status ?: 'open',
          'needs_human' => (bool) $contact->needs_human,
          'bot_paused' => (bool) $contact->is_bot_paused,
          'requested_at' => optional($contact->human_handoff_requested_at)->toIso8601String(),
          'message_preview' => $contact->human_handoff_message_preview,
          'message_key' => $contact->human_handoff_message_key,
          'assigned_to' => $contact->humanHandoffAssignedTo?->name,
          'assigned_to_id' => $contact->human_handoff_assigned_user_id,
          'assigned_to_me' => $contact->human_handoff_assigned_user_id
            ? (int) $contact->human_handoff_assigned_user_id === (int) auth()->id()
            : false,
          'unread_count' => (int) $contact->unread_count,
        ]),
        lock: { locked: false, locked_by: null, locked_by_me: false },
        lockNotice: '',
        pollMs: 5000,
        listPollMs: 30000,
        syncContactsEveryMs: 30000,
        humanHandoffBadgeTtlMs: 60 * 60 * 1000,
        syncContactsLimit: {{ (int) config('chat.sync_recent_limit', 40) }},
        lastContactSyncAt: 0,
        syncingContacts: false,
        listLoading: false,
        pauseListRefreshUntil: 0,
        pauseMessageAutoScrollUntil: 0,
        readMarking: false,
        timer: null,
        listTimer: null,
        lockTimer: null,
        handoffBadgeTimers: [],
        resizeHandler: null,
        lastLockedBy: null,
        releasing: false,
        listLimit: {{ (int) $listLimit }},
        isFirstMessageLoad: true,
        forceMessageScrollToBottom: false,
        lastInboundMessageKey: null,

        openSaveContact() {
          document.getElementById('saveContactModal').showModal();
        },

        showError(message, key = 'general') {
          const text = String(message || '').trim();
          if (!text) return;

          window.dispatchEvent(new CustomEvent('app:error', {
            detail: { key, message: text }
          }));
        },

        showNotice(message, key = 'sync-contacts-success') {
          const text = String(message || '').trim();
          if (!text) return;

          window.dispatchEvent(new CustomEvent('app:notify', {
            detail: { key, title: 'Sync Inbox', message: text }
          }));
        },

        clearError(key = 'general') {
          window.dispatchEvent(new CustomEvent('app:error:clear', {
            detail: { key }
          }));
        },

        latestInboundMessage(messages = []) {
          for (let index = messages.length - 1; index >= 0; index -= 1) {
            const message = messages[index];
            if (message?.direction === 'in' && String(message?.body || '').trim() !== '') {
              return message;
            }
          }
          return null;
        },

        inboundMessageKey(message) {
          if (!message) return null;
          return String(message.uuid || `${message.id || ''}|${message.sent_at || ''}|${message.body || ''}`) || null;
        },

        isHumanHandoffMessage(message) {
          if (!message || message.direction !== 'in') return false;
          const key = this.inboundMessageKey(message);
          return key === this.handoff.message_key;
        },

        maybeNotifyIncomingMessage(messages = []) {
          const latestInbound = this.latestInboundMessage(messages);
          const nextKey = this.inboundMessageKey(latestInbound);
          const previousKey = this.lastInboundMessageKey;
          this.lastInboundMessageKey = nextKey;

          if (!nextKey || !previousKey || nextKey === previousKey) {
            return;
          }

          if (document.visibilityState === 'visible' && document.hasFocus()) {
            return;
          }

          window.chatListNotifications?.notifyIncomingMessage(
            `chat:active:${contactId}:${nextKey}`,
            this.contactName,
            String(latestInbound?.body || 'New customer message')
          );
        },

        clearNeedsHumanBadgeTimers() {
          this.handoffBadgeTimers.forEach((timerId) => clearTimeout(timerId));
          this.handoffBadgeTimers = [];
        },

        hideNeedsHumanBadge(card) {
          card.querySelectorAll('.chat-needs-human-badge').forEach((badge) => {
            if (badge.classList.contains('hidden')) return;
            badge.classList.add('is-expiring');
            setTimeout(() => {
              badge.classList.add('hidden');
              badge.classList.remove('is-expiring');
            }, 220);
          });
        },

        scheduleNeedsHumanBadgeExpiry() {
          const listEl = this.$refs.chatList;
          if (!listEl) return;

          this.clearNeedsHumanBadgeTimers();
          listEl.querySelectorAll('[data-needs-human="1"][data-handoff-requested-at]').forEach((card) => {
            const requestedAt = Date.parse(card.dataset.handoffRequestedAt || '');
            if (!Number.isFinite(requestedAt)) return;

            const remainingMs = requestedAt + this.humanHandoffBadgeTtlMs - Date.now();
            if (remainingMs <= 0) {
              this.hideNeedsHumanBadge(card);
              return;
            }

            this.handoffBadgeTimers.push(setTimeout(() => {
              this.hideNeedsHumanBadge(card);
            }, remainingMs));
          });
        },

        applyLock(data = {}) {
          if (typeof data.locked === 'undefined' && typeof data.locked_by_me === 'undefined' && typeof data.locked_by === 'undefined') {
            return;
          }
          this.lock = {
            locked: !!data.locked,
            locked_by: data.locked_by || null,
            locked_by_me: !!data.locked_by_me,
          };

          if (data.handoff) {
            this.applyHandoff(data.handoff);
          }
        },

        applyHandoff(data = {}) {
          if (typeof data.active === 'undefined' && typeof data.assigned_to === 'undefined') {
            return;
          }

          this.handoff = {
            active: !!data.active,
            status: data.status || (data.active ? 'needs_human' : 'open'),
            needs_human: !!data.needs_human,
            bot_paused: !!data.bot_paused,
            requested_at: data.requested_at || null,
            message_preview: data.message_preview || null,
            message_key: data.message_key || null,
            assigned_to: data.assigned_to || null,
            assigned_to_id: data.assigned_to_id || null,
            assigned_to_me: !!data.assigned_to_me,
            unread_count: Number.parseInt(String(data.unread_count ?? '0'), 10) || 0,
          };
        },

        releaseLockKeepalive() {
          if (!this.lock.locked_by_me || this.releasing) return;
          this.releasing = true;
          try {
            if (navigator.sendBeacon) {
              const body = new URLSearchParams();
              body.set('_token', '{{ csrf_token() }}');
              navigator.sendBeacon(`/chats/${contactId}/lock/release`, body);
              this.releasing = false;
              return;
            }
            fetch(`/chats/${contactId}/lock/release`, {
              method: 'POST',
              keepalive: true,
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).catch(() => {});
          } catch (e) {}
          this.releasing = false;
        },

        async init() {
          await this.fetchLockStatus();
          await this.load();
          this.bindListInteractionHandlers();
          this.bindMessageInteractionHandlers();
          window.chatListNotifications?.syncFromList(this.$refs.chatList, {
            activeContactId: String(contactId),
            initial: true,
          });
          this.scheduleNeedsHumanBadgeExpiry();
          this.$nextTick(() => this.setListHeight());
          this.resizeHandler = () => this.setListHeight();
          window.addEventListener('resize', this.resizeHandler, { passive: true });
          window.addEventListener('orientationchange', this.resizeHandler, { passive: true });
          await this.refreshList();
          this.timer = setInterval(() => this.load(), this.pollMs);
          this.listTimer = setInterval(() => this.refreshList(), this.listPollMs);
          this.lockTimer = setInterval(() => this.refreshLockIfMine(), 15000);

          // Best-effort release when leaving page
          window.addEventListener('beforeunload', () => {
            clearInterval(this.timer);
            clearInterval(this.listTimer);
            clearInterval(this.lockTimer);
            this.clearNeedsHumanBadgeTimers();
            if (this.resizeHandler) {
              window.removeEventListener('resize', this.resizeHandler);
              window.removeEventListener('orientationchange', this.resizeHandler);
            }
            this.releaseLockKeepalive();
          });
        },

        async fetchLockStatus() {
          try {
            const res = await fetch(`/chats/${contactId}/lock`);
            let data = {};
            try {
              data = await res.json();
            } catch (e) {
              data = {};
            }

            if (!res.ok) {
              this.applyLock(data);
              this.showError(data.error || 'Failed to fetch chat lock status.', 'lock-status');
              return;
            }

            this.applyLock(data);
            this.clearError('lock-status');
          } catch (e) {
            this.showError('Failed to fetch chat lock status.', 'lock-status');
          }
        },

        async refreshLockIfMine() {
          // Only refresh if we own the lock
          if (this.lock.locked_by_me) {
            await this.acquireLock();
          } else {
            await this.fetchLockStatus();
          }
        },

        async acquireLock() {
          try {
            const res = await fetch(`/chats/${contactId}/lock/acquire`, {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            let data = {};
            try {
              data = await res.json();
            } catch (e) {
              data = {};
            }

            if (!res.ok) {
              this.applyLock(data);
              this.showError(data.error || 'Failed to lock this chat.', 'acquire-lock');
              return;
            }

            this.applyLock(data);
            this.applyHandoff(data.handoff || {});
            this.clearError('acquire-lock');
            await this.refreshList();
          } catch (e) {
            this.showError('Failed to lock this chat.', 'acquire-lock');
          }
        },

        async releaseLock() {
          if (this.releasing) return;
          this.releasing = true;
          try {
            const res = await fetch(`/chats/${contactId}/lock/release`, {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            let data = {};
            try {
              data = await res.json();
            } catch (e) {
              data = {};
            }
            if (!res.ok) {
              this.applyLock(data);
              this.showError(data.error || 'Failed to release chat lock.', 'release-lock');
              return;
            }
            this.applyLock(data);
            this.applyHandoff(data.handoff || {});
            this.clearError('release-lock');
          } catch (e) {
            this.showError('Failed to release chat lock.', 'release-lock');
          } finally {
            this.releasing = false;
          }
        },

        async manualSync() {
          this.lastContactSyncAt = 0;
          await this.syncRecentContactsIfDue(true);
          await this.load();
          await this.refreshList();
        },

        async updateHandoff(action) {
          const endpoint = {
            takeover: `/chats/${contactId}/handoff/takeover`,
            resume: `/chats/${contactId}/handoff/reset`,
            resolve: `/chats/${contactId}/handoff/resolve`,
          }[action];

          if (!endpoint) return;

          try {
            const res = await fetch(endpoint, {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            let data = {};
            try {
              data = await res.json();
            } catch (e) {
              data = {};
            }

            if (!res.ok) {
              this.showError(data.error || 'Failed to update handoff status.', 'handoff-action');
              return;
            }

            this.applyHandoff(data.handoff || {});
            this.clearError('handoff-action');
            await this.refreshList();
          } catch (e) {
            this.showError('Failed to update handoff status.', 'handoff-action');
          }
        },

        async syncRecentContactsIfDue(notifySuccess = false) {
          const now = Date.now();
          if (this.syncingContacts || (now - this.lastContactSyncAt) < this.syncContactsEveryMs) return;
          this.syncingContacts = true;
          try {
            const body = new URLSearchParams();
            body.set('limit', String(this.syncContactsLimit));
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 25000);
            const res = await fetch('/contacts/sync-recent', {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body,
              signal: controller.signal,
            });
            clearTimeout(timeoutId);
            let data = {};
            try {
              data = await res.json();
            } catch (e) {
              data = {};
            }
            if (!res.ok || data.success === false) {
              this.showError(data.message || 'Failed to sync recent contacts.', 'sync-contacts');
              return;
            }
            this.clearError('sync-contacts');
            if (notifySuccess) {
              this.showNotice(data.message || 'Sync Inbox completed.');
            }
          } catch (e) {
            this.showError(e?.name === 'AbortError' ? 'Timeout syncing recent contacts.' : (e?.message || 'Failed to sync recent contacts.'), 'sync-contacts');
          } finally {
            this.lastContactSyncAt = Date.now();
            this.syncingContacts = false;
          }
        },

        async refreshList() {
          if (this.listLoading) return;
          if (Date.now() < this.pauseListRefreshUntil) return;
          const listEl = this.$refs.chatList;
          if (!listEl) return;
          this.listLoading = true;
          try {
            await this.syncRecentContactsIfDue();
            const params = new URLSearchParams({
              active_contact_id: contactId,
              show_lock: '1',
              show_active: '1',
              show_preview: '1',
              limit: String(this.listLimit),
            });
            const res = await fetch(`/chats/list?${params.toString()}`);
            if (!res.ok) {
              this.showError('Failed to refresh chat list.', 'refresh-list');
              return;
            }
            const nextHtml = await res.text();
            if (Date.now() < this.pauseListRefreshUntil) {
              this.clearError('refresh-list');
              return;
            }
            if (nextHtml === listEl.innerHTML) {
              this.scheduleNeedsHumanBadgeExpiry();
              this.clearError('refresh-list');
              return;
            }
            const previousScrollTop = listEl.scrollTop;
            const wasNearBottom =
              (listEl.scrollHeight - (listEl.scrollTop + listEl.clientHeight)) < 24;
            listEl.innerHTML = nextHtml;
            this.$nextTick(() => {
              this.setListHeight();
              listEl.scrollTop = wasNearBottom ? listEl.scrollHeight : previousScrollTop;
              window.chatListNotifications?.syncFromList(listEl, {
                activeContactId: String(contactId),
              });
              this.scheduleNeedsHumanBadgeExpiry();
            });
            this.clearError('refresh-list');
          } catch (e) {
            this.showError('Failed to refresh chat list.', 'refresh-list');
          } finally {
            this.listLoading = false;
          }
        },

        async markRead() {
          if (this.readMarking) return;
          this.readMarking = true;
          try {
            await fetch(`/chats/${contactId}/read`, {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            this.handoff = {
              ...this.handoff,
              unread_count: 0,
            };
          } finally {
            this.readMarking = false;
          }
        },

        async load() {
          try {
            await this.fetchLockStatus();
            const scrollEl = this.$refs.scroll;
            const previousScrollTop = scrollEl ? scrollEl.scrollTop : 0;
            const wasNearBottom = this.isNearBottom(scrollEl, 64);

            const res = await fetch(`/chats/${contactId}/messages`);
            let data = {};
            try {
              data = await res.json();
            } catch (e) {
              data = {};
            }

            if (!res.ok || data.error) {
              this.online = false;
              this.showError(data.error || 'Unable to load messages right now.', 'load-messages');
              return;
            }
            this.messages = data.messages || [];
            this.applyHandoff(data.handoff || {});
            this.maybeNotifyIncomingMessage(this.messages);
            this.$nextTick(() => {
              const el = this.$refs.scroll;
              if (!el) return;
              const pauseAutoScroll = Date.now() < this.pauseMessageAutoScrollUntil;
              if (this.isFirstMessageLoad || this.forceMessageScrollToBottom || (!pauseAutoScroll && wasNearBottom)) {
                this.scrollToBottom();
              } else {
                el.scrollTop = previousScrollTop;
              }
              this.isFirstMessageLoad = false;
              this.forceMessageScrollToBottom = false;
            });
            this.online = true;
            this.clearError('load-messages');
            await this.markRead();
          } catch (e) {
            this.online = false;
            this.showError('Unable to load messages right now.', 'load-messages');
          }
        },

        async send() {
          if (this.lock.locked && !this.lock.locked_by_me) return;
          if (!this.draft.trim()) return;
          this.sending = true;
          try {
            const res = await fetch(`/chats/${contactId}/send`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({ message: this.draft })
            });

            let data = {};
            try {
              data = await res.json();
            } catch (e) {
              data = {};
            }

            if (!res.ok) {
              if (res.status === 423) {
                await this.fetchLockStatus();
              }
              this.showError(data.error || 'Send failed.', 'send-message');
              return;
            }

            this.draft = '';
            this.clearError('send-message');
            this.forceMessageScrollToBottom = true;
            await this.load();
            await this.refreshList();
          } catch (e) {
            this.showError('Failed to send message. Please try again.', 'send-message');
          } finally {
            this.sending = false;
          }
        },

        scrollToBottom() {
          const el = this.$refs.scroll;
          if (!el) return;
          el.scrollTop = el.scrollHeight;
        },

        bindListInteractionHandlers() {
          const listEl = this.$refs.chatList;
          if (!listEl) return;
          const pause = () => {
            this.pauseListRefreshUntil = Date.now() + 2500;
          };
          const forceListWheelScroll = (event) => {
            if (listEl.scrollHeight <= listEl.clientHeight) return;
            const before = listEl.scrollTop;
            listEl.scrollTop += event.deltaY;
            if (listEl.scrollTop !== before) {
              event.preventDefault();
            }
            pause();
          };
          listEl.addEventListener('pointerdown', pause, { passive: true });
          listEl.addEventListener('touchstart', pause, { passive: true });
          listEl.addEventListener('wheel', forceListWheelScroll, { passive: false });
          listEl.addEventListener('scroll', pause, { passive: true });
          listEl.addEventListener('click', (event) => {
            const link = event.target?.closest?.('a[href]');
            if (!link) return;
            this.releaseLockKeepalive();
          }, { passive: true });
        },

        setListHeight() {
          const sidebar = this.$refs.sidebar;
          const listEl = this.$refs.chatList;
          if (!sidebar || !listEl) return;
          if (sidebar.offsetParent === null) return;

          listEl.style.height = '';
          listEl.style.maxHeight = '';

          const sidebarRect = sidebar.getBoundingClientRect();
          const listRect = listEl.getBoundingClientRect();
          const available = Math.floor(sidebarRect.bottom - listRect.top);

          if (available > 120) {
            listEl.style.height = `${available}px`;
            listEl.style.maxHeight = `${available}px`;
          }
        },

        bindMessageInteractionHandlers() {
          const messageEl = this.$refs.scroll;
          if (!messageEl) return;
          const pause = () => {
            this.pauseMessageAutoScrollUntil = Date.now() + 2500;
          };
          messageEl.addEventListener('pointerdown', pause, { passive: true });
          messageEl.addEventListener('touchstart', pause, { passive: true });
          messageEl.addEventListener('wheel', pause, { passive: true });
          messageEl.addEventListener('scroll', pause, { passive: true });
        },

        isNearBottom(el, threshold = 48) {
          if (!el) return true;
          return (el.scrollHeight - (el.scrollTop + el.clientHeight)) <= threshold;
        },

        toDateKey(value) {
          if (!value) return null;
          const d = value instanceof Date ? value : new Date(value);
          if (Number.isNaN(d.getTime())) return null;
          return d.toLocaleDateString('en-CA', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            timeZone: 'Asia/Colombo',
          });
        },

        shouldShowDaySeparator(index) {
          if (index === 0) return true;
          const current = this.toDateKey(this.messages[index]?.sent_at);
          const previous = this.toDateKey(this.messages[index - 1]?.sent_at);
          if (current === null && previous === null) return false;
          return current !== previous;
        },

        formatDayLabel(iso) {
          const d = new Date(iso);
          if (Number.isNaN(d.getTime())) {
            return 'Unknown date';
          }

          const key = this.toDateKey(d);
          const todayKey = this.toDateKey(new Date());
          const yesterday = new Date();
          yesterday.setDate(yesterday.getDate() - 1);
          const yesterdayKey = this.toDateKey(yesterday);

          if (key === todayKey) return 'Today';
          if (key === yesterdayKey) return 'Yesterday';

          return d.toLocaleDateString('en-LK', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'Asia/Colombo',
          });
        },

        formatTime(iso, timeHint = '') {
          if (!iso) return timeHint || '';
          const d = new Date(iso);
          if (Number.isNaN(d.getTime())) return timeHint || '';
          return d.toLocaleTimeString('en-LK', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: 'Asia/Colombo'
          });
        }
      }
    }
  </script>
</x-app-layout>
