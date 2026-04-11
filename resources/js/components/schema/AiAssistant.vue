<template>
  <div :class="['ai-assistant-root', isDark ? 'ai-theme-dark' : 'ai-theme-light']">
    <!-- Floating action button -->
    <div class="ai-fab-container" aria-hidden="false">
      <div class="ai-fab-glow"></div>
      <v-btn
        class="ai-fab"
        rounded
        elevation="0"
        aria-label="Open assistant"
        @click="toggleAssistant"
      >
        <div class="ai-fab-inner">
          <v-icon size="26" color="white">{{ isOpen ? 'mdi-close' : 'mdi-robot-happy-outline' }}</v-icon>
        </div>
      </v-btn>
    </div>

    <!-- Chat panel -->
    <transition name="ai-panel-slide">
      <div
        v-if="isOpen"
        class="ai-chat-panel"
        role="dialog"
        aria-modal="true"
        :aria-label="$t('ai_assistant')"
      >
        <!-- Header -->
        <header class="ai-chat-header">
          <div class="ai-header-left">
            <div class="ai-avatar-ring">
              <div class="ai-avatar">
                <v-icon size="20" color="white">mdi-robot-happy-outline</v-icon>
              </div>
            </div>
            <div class="ai-header-info">
              <div class="ai-header-title">{{ $t('ai_assistant') }}</div>
              <div class="ai-header-status">
                <span class="ai-status-dot"></span>
                {{ $t('online') }}
              </div>
            </div>
          </div>
          <div class="ai-header-actions">
            <v-btn
              icon
              size="x-small"
              variant="text"
              @click="clearAssistantMessage"
              :title="$t('clear_messages')"
              class="ai-header-btn"
            >
              <v-icon size="18" color="rgba(255,255,255,0.85)">mdi-broom</v-icon>
            </v-btn>
            <v-btn
              icon
              size="x-small"
              variant="text"
              @click="closeAssistant"
              :title="$t('minimize_chat')"
              class="ai-header-btn"
            >
              <v-icon size="18" color="rgba(255,255,255,0.85)">mdi-chevron-down</v-icon>
            </v-btn>
          </div>
        </header>

        <!-- Messages body -->
        <div class="ai-chat-body" ref="chatBody">
          <div class="ai-chat-messages" ref="messagesContainer" aria-live="polite" @scroll="onScroll">
            <!-- Infinite scroll loader -->
            <div v-if="ai_prompt.loading_history" class="ai-load-more">
              <div class="ai-load-more-spinner">
                <v-progress-circular indeterminate size="18" width="2" color="#2196F3"></v-progress-circular>
                <span class="ai-load-more-text">Loading history...</span>
              </div>
            </div>
            <div v-else-if="ai_prompt.has_more" class="ai-load-more">
              <button class="ai-load-more-btn" @click="loadMore">
                <v-icon size="14" class="me-1">mdi-history</v-icon>
                Load older messages
              </button>
            </div>
            <div
              v-for="(m, i) in messages"
              :key="m.id || i"
              :class="[
                'ai-msg-row',
                m.role === 'agent' ? 'ai-msg-agent' : 'ai-msg-user'
              ]"
              @click="handleBubbleClick($event)"
            >
              <!-- Agent avatar -->
              <div v-if="m.role === 'agent' && !m.typing" class="ai-msg-avatar">
                <v-icon size="14" color="white">mdi-robot-happy-outline</v-icon>
              </div>

              <div
                :class="[
                  'ai-msg-bubble',
                  m.role === 'agent' ? 'ai-bubble-agent' : 'ai-bubble-user',
                  m.typing ? 'ai-bubble-typing' : '',
                  isChartMessage(m) ? 'ai-bubble-chart' : ''
                ]"
              >
                <!-- Typing indicator -->
                <template v-if="m.typing">
                  <div class="ai-typing-indicator" aria-hidden="true">
                    <span class="ai-typing-dot"></span>
                    <span class="ai-typing-dot"></span>
                    <span class="ai-typing-dot"></span>
                  </div>
                </template>

                <!-- Chart content -->
                <template v-else-if="isChartMessage(m)">
                  <div class="ai-chart-container">
                    <div class="ai-chart-header">
                      <v-icon size="16" color="#2196F3">mdi-chart-box-outline</v-icon>
                      <span class="ai-chart-label">Chart View</span>
                    </div>
                    <div class="ai-chart-content" v-html="m.text"></div>
                  </div>
                </template>

                <!-- Normal message -->
                <template v-else>
                  <div class="ai-msg-text" v-html="m.text"></div>
                </template>

                <!-- Timestamp + inline actions -->
                <div
                  v-if="!m.typing"
                  :class="['ai-msg-footer', m.role === 'agent' ? 'ai-footer-agent' : 'ai-footer-user']"
                  @mouseenter="hoveredMsgId = m.id"
                  @mouseleave="hoveredMsgId = null"
                >
                  <span class="ai-msg-time">{{ formatTime(m.ts) }}</span>
                  <span class="ai-msg-actions-inline">
                    <!-- Like (visible on hover OR when active) -->
                    <button
                      v-if="m.role === 'agent' && (hoveredMsgId === m.id || m.reaction === 'like')"
                      class="ai-action-btn"
                      :class="{ 'ai-action-active': m.reaction === 'like' }"
                      @click.stop="reactMessage(m.id, 'like')"
                      :title="$t('helpful')"
                    >
                      <v-icon size="13">{{ m.reaction === 'like' ? 'mdi-thumb-up' : 'mdi-thumb-up-outline' }}</v-icon>
                    </button>
                    <!-- Dislike (visible on hover OR when active) -->
                    <button
                      v-if="m.role === 'agent' && (hoveredMsgId === m.id || m.reaction === 'dislike')"
                      class="ai-action-btn"
                      :class="{ 'ai-action-active ai-action-dislike': m.reaction === 'dislike' }"
                      @click.stop="reactMessage(m.id, 'dislike')"
                      :title="$t('not_helpful')"
                    >
                      <v-icon size="13">{{ m.reaction === 'dislike' ? 'mdi-thumb-down' : 'mdi-thumb-down-outline' }}</v-icon>
                    </button>
                    <!-- Delete (hover only) -->
                    <button
                      v-if="hoveredMsgId === m.id"
                      class="ai-action-btn ai-action-delete"
                      @click.stop="deleteMessage(m.id)"
                      :title="$t('delete')"
                    >
                      <v-icon size="13">mdi-delete-outline</v-icon>
                    </button>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Input area -->
        <div class="ai-chat-input-area">
          <div class="ai-input-wrapper">
            <input
              v-model="input"
              :placeholder="$t('type_a_message')"
              class="ai-chat-input"
              @keyup.enter="send"
              :disabled="loading"
            />
            <button
              class="ai-send-btn"
              @click="send"
              :disabled="loading || !input.trim()"
              :class="{ 'ai-send-active': input.trim() && !loading }"
            >
              <v-icon v-if="loading" size="18" class="ai-spin">mdi-loading</v-icon>
              <v-icon v-else size="18">mdi-send-variant</v-icon>
            </button>
          </div>
          <div class="ai-input-hint">
            <v-icon size="13" class="ai-hint-icon">mdi-keyboard-return</v-icon>
            <span>{{ $t('press_enter_to_send') || 'Press Enter to send' }}</span>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { useAIPrompt } from '@/stores/AIPrompt';
import { useMain } from '@/stores/Main';

export default {
  data() {
    return {
      isOpen: false,
      loading: false,
      input: '',
      sending: false,
      typingPlaceholder: null,
      scrollRestorationPending: false,
      hoveredMsgId: null,
    };
  },
  computed: {
    ai_prompt: () => useAIPrompt(),
    main: () => useMain(),
    isDark() {
      return this.main.is_dark;
    },
    messages() {
      const base = Array.isArray(this.ai_prompt.assistant_messages)
        ? this.ai_prompt.assistant_messages.slice()
        : [];
      if (this.typingPlaceholder) {
        base.push(this.typingPlaceholder);
      }
      return base;
    },
  },
  methods: {
    toggleAssistant() {
      this.isOpen = !this.isOpen;
      if (this.isOpen) {
        this.loadHistoryIfNeeded();
        this.$nextTick(() => this.scrollToBottom());
      }
    },
    closeAssistant() {
      this.isOpen = false;
    },
    async clearAssistantMessage() {
      await this.ai_prompt.clearHistory();
      this.initChat();
    },
    initChat() {
      if (this.ai_prompt.assistant_messages.length === 0) {
        let msg = { id: 'welcome', role: 'agent', text: this.$t("ai_assistant_msg"), ts: Date.now() };
        this.ai_prompt.add_assistant_messages(msg);
      }
    },
    async loadHistoryIfNeeded() {
      if (!this.ai_prompt.history_loaded) {
        await this.ai_prompt.loadChatHistory();
        this.initChat();
        this.$nextTick(() => this.scrollToBottom());
      }
    },
    async loadMore() {
      const container = this.$refs.messagesContainer;
      const prevHeight = container ? container.scrollHeight : 0;
      await this.ai_prompt.loadMoreHistory();
      // Restore scroll position so the user doesn't jump
      this.$nextTick(() => {
        if (container) {
          const newHeight = container.scrollHeight;
          container.scrollTop = newHeight - prevHeight;
        }
      });
    },
    onScroll() {
      const container = this.$refs.messagesContainer;
      if (!container) return;
      // When scrolled to top, trigger load more
      if (container.scrollTop <= 10 && this.ai_prompt.has_more && !this.ai_prompt.loading_history) {
        this.loadMore();
      }
    },
    formatTime(ts) {
      const d = new Date(ts);
      const hh = String(d.getHours()).padStart(2, '0');
      const mm = String(d.getMinutes()).padStart(2, '0');
      return `${hh}:${mm}`;
    },
    isChartMessage(m) {
      if (!m.text || m.typing) return false;
      const t = m.text.toLowerCase();
      return t.includes('apexchart') || t.includes('aichart') || t.includes('new apexcharts');
    },
    scrollToBottom() {
      const el = this.$refs.messagesContainer;
      if (!el) return;
      this.$nextTick(() => {
        el.scrollTop = el.scrollHeight;
      });
    },
    async send() {
      const text = (this.input || '').trim();
      if (!text) return;

      const userMsg = {
        id: Date.now() + Math.random(),
        role: 'user',
        text: text,
        ts: Date.now(),
      };

      this.ai_prompt.add_assistant_messages(userMsg);
      this.input = '';
      this.$nextTick(() => this.scrollToBottom());

      this.typingPlaceholder = {
        id: 'typing_' + Date.now() + '_' + Math.random(),
        role: 'agent',
        typing: true,
        text: '',
        ts: Date.now(),
      };

      this.sending = true;
      this.loading = true;

      try {
        await this.ai_prompt.askAssistant(userMsg.text);
      } catch (err) {
        const errMsg = {
          id: Date.now() + Math.random(),
          role: 'agent',
          text: 'Sorry — an error occurred. Please try again.',
          ts: Date.now(),
        };
        try {
          this.ai_prompt.add_assistant_messages(errMsg);
        } catch (e) {
          console.error(e);
        }
      } finally {
        this.typingPlaceholder = null;
        this.sending = false;
        this.loading = false;
        this.$nextTick(() => this.scrollToBottom());
      }
    },
    handleBubbleClick(event) {
      // Handle .ai-nav-btn buttons (from AI backend)
      const navBtn = event.target.closest('.ai-nav-btn');
      if (navBtn) {
        const route = navBtn.getAttribute('data-route');
        if (route) {
          event.preventDefault();
          event.stopPropagation();
          this.$router.push(route);
          return;
        }
      }
      // Handle standard <a> links
      const anchor = event.target.closest('a');
      if (!anchor) return;
      const href = anchor.getAttribute('href');
      if (!href) return;
      event.preventDefault();
      event.stopPropagation();
      try {
        const url = new URL(href, window.location.origin);
        if (url.origin === window.location.origin) {
          this.$router.push(url.pathname + url.search + url.hash);
        } else {
          window.open(href, '_blank', 'noopener');
        }
      } catch {
        this.$router.push(href);
      }
    },
    deleteMessage(msgId) {
      this.ai_prompt.delete_assistant_message(msgId);
      this.hoveredMsgId = null;
    },
    reactMessage(msgId, reaction) {
      this.ai_prompt.react_to_message(msgId, reaction);
    },
  },
  mounted() {
    if (this.isOpen) {
      this.loadHistoryIfNeeded();
      this.scrollToBottom();
    } else {
      // Pre-load history in background
      this.ai_prompt.loadChatHistory().then(() => {
        this.initChat();
      });
    }
  },
};
</script>

<style scoped>
/* ============================================================
   AI Assistant — Chat UI with Vuetify Info Color (#2196F3)
   Clean, beautiful design with dark/light theme support.
   ============================================================ */

/* Vuetify info = #2196F3, info-darken-1 = #1976D2, info-lighten-1 = #42A5F5 */

/* ---------- Theme Tokens – Dark ---------- */
.ai-theme-dark {
  --ai-panel-bg: #121212;
  --ai-panel-border: rgba(255, 255, 255, 0.08);
  --ai-panel-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 4px 16px rgba(0, 0, 0, 0.3);
  --ai-header-bg: linear-gradient(135deg, #1976D2 0%, #2196F3 50%, #42A5F5 100%);
  --ai-header-border: transparent;
  --ai-title-color: #ffffff;
  --ai-status-color: rgba(255, 255, 255, 0.75);
  --ai-body-bg: #121212;
  --ai-body-scroll-thumb: rgba(255, 255, 255, 0.1);
  --ai-body-scroll-thumb-hover: rgba(255, 255, 255, 0.2);
  --ai-bubble-agent-bg: #1E1E1E;
  --ai-bubble-agent-border: rgba(255, 255, 255, 0.06);
  --ai-bubble-agent-color: rgba(255, 255, 255, 0.87);
  --ai-bubble-user-bg: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
  --ai-bubble-user-color: #ffffff;
  --ai-bubble-user-shadow: 0 4px 16px rgba(33, 150, 243, 0.3);
  --ai-time-color: rgba(255, 255, 255, 0.38);
  --ai-input-bg: #1E1E1E;
  --ai-input-border: rgba(255, 255, 255, 0.08);
  --ai-input-focus-border: #2196F3;
  --ai-input-focus-shadow: 0 0 0 2px rgba(33, 150, 243, 0.15);
  --ai-input-color: rgba(255, 255, 255, 0.87);
  --ai-input-placeholder: rgba(255, 255, 255, 0.38);
  --ai-input-area-bg: #121212;
  --ai-input-area-border: rgba(255, 255, 255, 0.06);
  --ai-send-idle-bg: rgba(255, 255, 255, 0.06);
  --ai-send-idle-color: rgba(255, 255, 255, 0.38);
  --ai-chart-border: rgba(33, 150, 243, 0.2);
  --ai-chart-header-bg: rgba(33, 150, 243, 0.08);
  --ai-chart-header-border: rgba(33, 150, 243, 0.12);
  --ai-chart-label-color: #42A5F5;
  --ai-strong-color: #64B5F6;
  --ai-code-bg: rgba(255, 255, 255, 0.06);
  --ai-link-color: #42A5F5;
  --ai-link-hover-color: #64B5F6;
  --ai-avatar-bg: linear-gradient(135deg, #1976D2, #2196F3);
  --ai-typing-bg: #1E1E1E;
  --ai-typing-border: rgba(255, 255, 255, 0.06);
}

/* ---------- Theme Tokens – Light ---------- */
.ai-theme-light {
  --ai-panel-bg: #ffffff;
  --ai-panel-border: rgba(0, 0, 0, 0.08);
  --ai-panel-shadow: 0 20px 60px rgba(0, 0, 0, 0.12), 0 4px 16px rgba(0, 0, 0, 0.06);
  --ai-header-bg: linear-gradient(135deg, #1976D2 0%, #2196F3 50%, #42A5F5 100%);
  --ai-header-border: transparent;
  --ai-title-color: #ffffff;
  --ai-status-color: rgba(255, 255, 255, 0.8);
  --ai-body-bg: #FAFAFA;
  --ai-body-scroll-thumb: rgba(0, 0, 0, 0.08);
  --ai-body-scroll-thumb-hover: rgba(0, 0, 0, 0.15);
  --ai-bubble-agent-bg: #ffffff;
  --ai-bubble-agent-border: rgba(0, 0, 0, 0.06);
  --ai-bubble-agent-color: #212121;
  --ai-bubble-user-bg: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
  --ai-bubble-user-color: #ffffff;
  --ai-bubble-user-shadow: 0 4px 16px rgba(33, 150, 243, 0.2);
  --ai-time-color: rgba(0, 0, 0, 0.38);
  --ai-input-bg: #ffffff;
  --ai-input-border: rgba(0, 0, 0, 0.1);
  --ai-input-focus-border: #2196F3;
  --ai-input-focus-shadow: 0 0 0 2px rgba(33, 150, 243, 0.12);
  --ai-input-color: #212121;
  --ai-input-placeholder: rgba(0, 0, 0, 0.38);
  --ai-input-area-bg: #FAFAFA;
  --ai-input-area-border: rgba(0, 0, 0, 0.06);
  --ai-send-idle-bg: rgba(0, 0, 0, 0.04);
  --ai-send-idle-color: rgba(0, 0, 0, 0.3);
  --ai-chart-border: rgba(33, 150, 243, 0.15);
  --ai-chart-header-bg: rgba(33, 150, 243, 0.05);
  --ai-chart-header-border: rgba(33, 150, 243, 0.1);
  --ai-chart-label-color: #1976D2;
  --ai-strong-color: #1565C0;
  --ai-code-bg: rgba(0, 0, 0, 0.04);
  --ai-link-color: #1976D2;
  --ai-link-hover-color: #1565C0;
  --ai-avatar-bg: linear-gradient(135deg, #1976D2, #2196F3);
  --ai-typing-bg: #ffffff;
  --ai-typing-border: rgba(0, 0, 0, 0.06);
}

/* ---------- FAB Button ---------- */
.ai-fab-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ai-fab-glow {
  position: absolute;
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(33, 150, 243, 0.35) 0%, transparent 70%);
  animation: ai-glow-pulse 2.5s ease-in-out infinite;
  pointer-events: none;
}

.ai-fab {
  width: 56px !important;
  height: 56px !important;
  min-width: 56px !important;
  min-height: 56px !important;
  padding: 0 !important;
  border-radius: 50% !important;
  background: linear-gradient(135deg, #1976D2 0%, #2196F3 60%, #42A5F5 100%) !important;
  box-shadow:
    0 6px 28px rgba(33, 150, 243, 0.4),
    0 2px 8px rgba(0, 0, 0, 0.12) !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  position: relative;
  z-index: 1;
}

.ai-fab:hover {
  transform: translateY(-3px) scale(1.06) !important;
  box-shadow:
    0 10px 36px rgba(33, 150, 243, 0.5),
    0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.ai-fab-inner {
  display: flex;
  align-items: center;
  justify-content: center;
}

@keyframes ai-glow-pulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.4); opacity: 0; }
}

/* ---------- Panel Slide ---------- */
.ai-panel-slide-enter-active {
  transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.ai-panel-slide-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 1, 1);
}
.ai-panel-slide-enter-from {
  transform: translateY(20px) scale(0.95);
  opacity: 0;
}
.ai-panel-slide-enter-to,
.ai-panel-slide-leave-from {
  transform: translateY(0) scale(1);
  opacity: 1;
}
.ai-panel-slide-leave-to {
  transform: translateY(20px) scale(0.95);
  opacity: 0;
}

/* ---------- Chat Panel ---------- */
.ai-chat-panel {
  position: fixed;
  bottom: 96px;
  right: 24px;
  width: 400px;
  height: 560px;
  z-index: 2100;
  display: flex;
  flex-direction: column;
  border-radius: 20px;
  overflow: hidden;
  background: var(--ai-panel-bg);
  box-shadow: var(--ai-panel-shadow);
  border: 1px solid var(--ai-panel-border);
  transition: all 0.3s ease;
}

/* ---------- Header ---------- */
.ai-chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  background: var(--ai-header-bg);
  border-bottom: 1px solid var(--ai-header-border);
}

.ai-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ai-avatar-ring {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  padding: 0px;
  background: rgba(255, 255, 255, 0.25);
}

.ai-avatar {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
}

.ai-header-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.ai-header-title {
  font-weight: 700;
  font-size: 15px;
  color: var(--ai-title-color);
  letter-spacing: 0.2px;
}

.ai-header-status {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--ai-status-color);
  font-weight: 500;
}

.ai-status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #4CAF50;
  box-shadow: 0 0 6px rgba(76, 175, 80, 0.6);
  animation: ai-status-blink 2s ease-in-out infinite;
}

@keyframes ai-status-blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.ai-header-actions {
  display: flex;
  gap: 2px;
}

.ai-header-btn {
  border-radius: 10px !important;
}

.ai-header-btn:hover {
  background: rgba(255, 255, 255, 0.15) !important;
}

/* ---------- Chat Body ---------- */
.ai-chat-body {
  flex: 1;
  overflow: hidden;
  background: var(--ai-body-bg);
}

.ai-chat-messages {
  height: 100%;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  scroll-behavior: smooth;
}

.ai-chat-messages::-webkit-scrollbar {
  width: 4px;
}
.ai-chat-messages::-webkit-scrollbar-track {
  background: transparent;
}
.ai-chat-messages::-webkit-scrollbar-thumb {
  background: var(--ai-body-scroll-thumb);
  border-radius: 10px;
}
.ai-chat-messages::-webkit-scrollbar-thumb:hover {
  background: var(--ai-body-scroll-thumb-hover);
}

/* ---------- Message Row ---------- */
.ai-msg-row {
  display: flex;
  max-width: 100%;
  animation: ai-msg-appear 0.3s ease;
}

@keyframes ai-msg-appear {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.ai-msg-agent {
  justify-content: flex-start;
  gap: 8px;
  align-items: flex-end;
}

.ai-msg-user {
  justify-content: flex-end;
}

.ai-msg-avatar {
  width: 28px;
  height: 28px;
  min-width: 28px;
  border-radius: 50%;
  background: var(--ai-avatar-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ---------- Message Bubbles ---------- */
.ai-msg-bubble {
  max-width: 82%;
  padding: 11px 15px;
  border-radius: 18px;
  font-size: 13.5px;
  line-height: 1.55;
  word-break: break-word;
  transition: all 0.25s ease;
}

.ai-bubble-agent {
  background: var(--ai-bubble-agent-bg);
  border: 1px solid var(--ai-bubble-agent-border);
  color: var(--ai-bubble-agent-color);
  border-bottom-left-radius: 4px;
}

.ai-bubble-user {
  background: var(--ai-bubble-user-bg);
  color: var(--ai-bubble-user-color);
  border-bottom-right-radius: 4px;
  box-shadow: var(--ai-bubble-user-shadow);
}

.ai-bubble-typing {
  background: var(--ai-typing-bg);
  border: 1px solid var(--ai-typing-border);
  padding: 13px 18px;
  border-bottom-left-radius: 4px;
}

/* ---------- Chart ---------- */
.ai-bubble-chart {
  max-width: 100% !important;
  padding: 0 !important;
  background: var(--ai-bubble-agent-bg) !important;
  border: 1px solid var(--ai-chart-border) !important;
  border-radius: 16px !important;
  overflow: hidden;
}

.ai-chart-container {
  width: 100%;
}

.ai-chart-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: var(--ai-chart-header-bg);
  border-bottom: 1px solid var(--ai-chart-header-border);
}

.ai-chart-label {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--ai-chart-label-color);
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.ai-chart-content {
  padding: 14px;
  overflow-x: auto;
}

/* Dark chart overrides */
.ai-theme-dark .ai-chart-content :deep(.bg-white) {
  background-color: transparent !important;
}
.ai-theme-dark .ai-chart-content :deep(.v-container) {
  background-color: transparent !important;
}
.ai-theme-dark .ai-chart-content :deep(.v-card) {
  background-color: #1E1E1E !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  border-radius: 12px !important;
  color: rgba(255, 255, 255, 0.87) !important;
}
.ai-theme-dark .ai-chart-content :deep(.v-data-table) {
  background-color: transparent !important;
  color: rgba(255, 255, 255, 0.87) !important;
}
.ai-theme-dark .ai-chart-content :deep(.v-data-table th) {
  color: rgba(255, 255, 255, 0.60) !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}
.ai-theme-dark .ai-chart-content :deep(.v-data-table td) {
  color: rgba(255, 255, 255, 0.87) !important;
  border-color: rgba(255, 255, 255, 0.06) !important;
}

/* Light chart overrides */
.ai-theme-light .ai-chart-content :deep(.v-card) {
  background-color: #ffffff !important;
  border: 1px solid rgba(0, 0, 0, 0.06) !important;
  border-radius: 12px !important;
}
.ai-theme-light .ai-chart-content :deep(.v-data-table) {
  background-color: #ffffff !important;
}

/* ---------- Message Text ---------- */
.ai-msg-text {
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
}

.ai-msg-text :deep(ul),
.ai-msg-text :deep(ol) {
  margin-left: 18px !important;
  padding-left: 0;
}

.ai-msg-text :deep(p) {
  margin-bottom: 4px;
}

.ai-msg-text :deep(strong) {
  color: var(--ai-strong-color);
}

.ai-msg-text :deep(code) {
  background: var(--ai-code-bg);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  font-family: 'Fira Code', 'Consolas', monospace;
}

.ai-msg-text :deep(a) {
  color: var(--ai-link-color);
  text-decoration: none;
  border-bottom: 1px dotted currentColor;
}

.ai-msg-text :deep(a:hover) {
  color: var(--ai-link-hover-color);
}

/* ---------- Timestamp + Inline Actions ---------- */
.ai-msg-footer {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 5px;
  min-height: 22px;
}

.ai-footer-agent {
  flex-direction: row;
}

.ai-footer-user {
  flex-direction: row-reverse;
}

.ai-msg-time {
  font-size: 10px;
  color: var(--ai-time-color);
  font-weight: 500;
  white-space: nowrap;
  line-height: 1;
}

.ai-bubble-user .ai-msg-time {
  color: rgba(255, 255, 255, 0.55);
}


/* Inline action buttons */
.ai-msg-actions-inline {
  display: inline-flex;
  align-items: center;
  gap: 1px;
}

.ai-action-btn {
  width: 22px;
  height: 22px;
  min-width: 22px;
  border-radius: 6px;
  border: none;
  background: transparent;
  color: var(--ai-time-color);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  padding: 0;
}

.ai-action-btn:hover {
  background: rgba(33, 150, 243, 0.12);
  color: #2196F3;
}

.ai-bubble-user .ai-action-btn {
  color: rgba(255, 255, 255, 0.5);
}

.ai-bubble-user .ai-action-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
}

.ai-bubble-user .ai-action-btn.ai-action-delete:hover {
  background: rgba(255, 100, 100, 0.2);
  color: #ffcdd2;
}

.ai-action-btn.ai-action-active {
  color: #2196F3;
}

.ai-action-btn.ai-action-dislike {
  color: #f44336;
}

.ai-action-btn.ai-action-dislike:hover {
  color: #f44336;
  background: rgba(244, 67, 54, 0.12);
}

.ai-action-btn.ai-action-delete:hover {
  color: #f44336;
  background: rgba(244, 67, 54, 0.1);
}

/* ---------- Typing Indicator ---------- */
.ai-typing-indicator {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 20px;
  padding: 0 4px;
}

.ai-typing-dot {
  width: 7px;
  height: 7px;
  background: #2196F3;
  border-radius: 50%;
  display: inline-block;
  animation: ai-dot-bounce 1.2s infinite ease-in-out;
}

.ai-typing-dot:nth-child(1) { animation-delay: 0ms; }
.ai-typing-dot:nth-child(2) { animation-delay: 160ms; }
.ai-typing-dot:nth-child(3) { animation-delay: 320ms; }

@keyframes ai-dot-bounce {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.35; }
  30% { transform: translateY(-8px); opacity: 1; }
}

/* ---------- Input Area ---------- */
.ai-chat-input-area {
  padding: 10px 14px 14px;
  border-top: 1px solid var(--ai-input-area-border);
  background: var(--ai-input-area-bg);
}

.ai-input-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--ai-input-bg);
  border: 1.5px solid var(--ai-input-border);
  border-radius: 14px;
  padding: 3px 4px 3px 14px;
  transition: all 0.25s ease;
}

.ai-input-wrapper:focus-within {
  border-color: var(--ai-input-focus-border);
  box-shadow: var(--ai-input-focus-shadow);
}

.ai-chat-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: var(--ai-input-color);
  font-size: 13.5px;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  padding: 9px 0;
}

.ai-chat-input::placeholder {
  color: var(--ai-input-placeholder);
}

.ai-send-btn {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 50%;
  border: none;
  background: var(--ai-send-idle-bg);
  color: var(--ai-send-idle-color);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.ai-send-btn:disabled {
  cursor: not-allowed;
  opacity: 0.35;
}

.ai-send-btn.ai-send-active {
  background: linear-gradient(135deg, #1976D2, #2196F3);
  color: #fff;
  box-shadow: 0 4px 14px rgba(33, 150, 243, 0.35);
}

.ai-send-btn.ai-send-active:hover {
  transform: scale(1.1) rotate(-15deg);
  box-shadow: 0 6px 20px rgba(33, 150, 243, 0.45);
}

/* ---------- Input Hint ---------- */
.ai-input-hint {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 5px 4px 0;
  justify-content: flex-end;
}

.ai-input-hint span {
  font-size: 10.5px;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  font-weight: 500;
  color: var(--ai-time-color);
  letter-spacing: 0.2px;
}

.ai-hint-icon {
  color: var(--ai-time-color) !important;
  opacity: 0.7;
}

/* ---------- Spinner ---------- */
.ai-spin {
  animation: ai-spin-rotate 0.8s linear infinite;
}

@keyframes ai-spin-rotate {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

/* ---------- Responsive ---------- */
@media (max-width: 480px) {
  .ai-chat-panel {
    right: 10px;
    left: 10px;
    width: auto;
    bottom: 90px;
    height: 70vh;
  }
  .ai-fab-container {
    right: 14px;
    bottom: 14px;
  }
}

/* ---------- Reduced Motion ---------- */
@media (prefers-reduced-motion: reduce) {
  .ai-fab-glow { animation: none !important; }
  .ai-panel-slide-enter-active,
  .ai-panel-slide-leave-active { transition: none !important; }
  .ai-typing-dot { animation: none !important; opacity: 1 !important; }
  .ai-status-dot { animation: none !important; }
  .ai-msg-row { animation: none !important; }
}

/* ---------- Infinite Scroll Loader ---------- */
.ai-load-more {
  display: flex;
  justify-content: center;
  padding: 8px 0 12px;
}

.ai-load-more-spinner {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ai-load-more-text {
  font-size: 11px;
  font-weight: 500;
  color: var(--ai-time-color);
  letter-spacing: 0.2px;
}

.ai-load-more-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 14px;
  border: 1px solid var(--ai-input-border);
  border-radius: 20px;
  background: var(--ai-bubble-agent-bg);
  color: #2196F3;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
}

.ai-load-more-btn:hover {
  background: rgba(33, 150, 243, 0.08);
  border-color: rgba(33, 150, 243, 0.3);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(33, 150, 243, 0.15);
}
</style>
