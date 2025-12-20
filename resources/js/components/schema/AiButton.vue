<template>
    <div>
      <!-- Floating action button -->
      <div class="fab-container" aria-hidden="false">
        <v-btn
          class="fab pulse"
          :style="{ backgroundColor: '#1c9ff8', color: 'white' }"
          rounded
          elevation="12"
          aria-label="Open assistant"
          @click="toggleAssistant"
        >
          <v-icon size="28">mdi-robot</v-icon>
        </v-btn>
      </div>

      <!-- Chat panel (slide up) -->
      <transition name="panel-slide">
        <v-sheet
          v-if="isOpen"
          class="chat-panel"
          elevation="14"
          rounded="xl"
          role="dialog"
          aria-label="Assistant chat"
        >
          <header class="chat-header my-gradient-div ">
            <div class="header-left">
              <v-icon size="20">mdi-robot</v-icon>
              <div class="header-title">
                <div class="title">Assistant</div>
                <div class="subtitle">
                  <v-badge
                    bordered
                    floating
                    :offset-x="-6"
                    location="right center"
                    color="green"
                    dot
                  >
                    {{ $t('online') }}
                  </v-badge>
                </div>
              </div>
            </div>

            <div class="header-actions">
              <v-btn
                icon
                size="x-small"
                variant="tonal"
                @click="closeAssistant"
                :aria-label="'Minimize chat'"
              >
                <v-icon>mdi-minus</v-icon>
              </v-btn>
            </div>
          </header>

          <div class="chat-body" ref="chatBody">
            <div class="chat-messages" ref="messagesContainer" aria-live="polite">
              <div
                v-for="(m, i) in messages"
                :key="m.id || i"
                :class="['message-row', m.role === 'agent' ? 'agent' : 'user']"
              >
                <div :class="['bubble elevation-5', m.role === 'agent' ? '' : 'bg-blue']">
                  <div class="bubble-text">
                    <!-- Typing indicator -->
                    <template v-if="m.typing">
                      <div class="typing-dots" aria-hidden="true">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                      </div>
                    </template>

                    <!-- Normal message content -->
                    <template v-else>
                      <div v-html="m.text"></div>
                    </template>
                  </div>

                  <div class="bubble-meta" v-if="!m.typing">{{ formatTime(m.ts) }}</div>
                </div>
              </div>
            </div>
          </div>

          <v-divider class="mb-3"></v-divider>

          <div>
            <v-text-field
              v-model="input"
              :label="$t('type_a_message')"
              outlined
              dense
              variant="solo-filled"
              rounded
              hide-details
              @keyup.enter="send"
              :disabled="loading"
            >
              <template #append-inner>
                <v-btn
                  v-if="loading"
                  :loading="loading"
                  icon="mdi-send"
                  variant="tonal"
                  readonly
                  color="primary"
                >
                </v-btn>
                <v-btn
                  v-else
                  @click="send"
                  color="primary"
                  icon="mdi-send"
                  variant="tonal"
                  size="small"
                ></v-btn>
              </template>
            </v-text-field>
          </div>
        </v-sheet>
      </transition>
    </div>
</template>

<script>
import { useAIPrompt } from '@/stores/AIPrompt';

export default {
  data() {
    return {
      isOpen: false,
      loading: false,
      input: '',
      sending: false,
      typingPlaceholder: null, // local placeholder (not stored in backend store)
    };
  },
  computed: {
    ai_prompt: () => useAIPrompt(),
    /**
     * Build the messages array for display: combine store messages
     * with a local typing placeholder when present. This avoids needing
     * to mutate the store to show the typing indicator.
     */
    messages() {
      const base = Array.isArray(this.ai_prompt.assistant_messages)
        ? this.ai_prompt.assistant_messages.slice()
        : [];
      if (this.typingPlaceholder) {
        // place typing placeholder at the end (after the user's message)
        base.push(this.typingPlaceholder);
      }
      return base;
    },
  },
  methods: {
    toggleAssistant() {
      this.isOpen = !this.isOpen;
      if (this.isOpen) {
        this.$nextTick(() => this.scrollToBottom());
      }
    },
    closeAssistant() {
      this.isOpen = false;
    },
    formatTime(ts) {
      const d = new Date(ts);
      const hh = String(d.getHours()).padStart(2, '0');
      const mm = String(d.getMinutes()).padStart(2, '0');
      return `${hh}:${mm}`;
    },
    scrollToBottom() {
      const el = this.$refs.messagesContainer;
      if (!el) return;
      // ensure scrolling after DOM updates
      this.$nextTick(() => {
        el.scrollTop = el.scrollHeight;
      });
    },
    /**
     * Send message:
     * - add user message to store
     * - add local typing placeholder (display-only)
     * - call ai_prompt.askAssistant(...) which is expected to push the agent reply into the store
     * - when the promise resolves (or rejects) remove the typing placeholder
     */
    async send() {
      const text = (this.input || '').trim();
      if (!text) return;

      const userMsg = {
        id: Date.now() + Math.random(),
        role: 'user',
        text: this.escapeHtml(text),
        ts: Date.now(),
      };

      // add user message to your store
      // your existing store method: add_assistant_messages
      this.ai_prompt.add_assistant_messages(userMsg);

      // reset input and scroll
      this.input = '';
      this.$nextTick(() => this.scrollToBottom());

      // show local typing placeholder (display only)
      this.typingPlaceholder = {
        id: 'typing_' + Date.now() + '_' + Math.random(),
        role: 'agent',
        typing: true,
        text: '',
        ts: Date.now(),
      };

      this.sending = true;

      try {
        // await backend / store action which should append the real agent message(s)
        // keep typing placeholder visible until this resolves
        await this.ai_prompt.askAssistant(userMsg.text);
      } catch (err) {
        // In case of error, optionally push an error message to store instead
        const errMsg = {
          id: Date.now() + Math.random(),
          role: 'agent',
          text: 'Sorry — an error occurred. Please try again.',
          ts: Date.now(),
        };
        try {
          // attempt to add error message to store
          this.ai_prompt.add_assistant_messages(errMsg);
        } catch (e) {
          // silent fallback
          console.error(e);
        }
      } finally {
        // remove typing placeholder and reset state
        this.typingPlaceholder = null;
        this.sending = false;
        this.$nextTick(() => this.scrollToBottom());
      }
    },
    escapeHtml(unsafe) {
      if (typeof unsafe !== 'string') return '';
      return unsafe
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    },
    simulateAgentReply(userText) {
      // kept for compatibility if you want to test without backend
      return `I received: "${userText}". For demo, here's a sample response — let me know if you want the real data.`;
    },
  },
  mounted() {
    if (this.isOpen) {
      this.scrollToBottom();
      }
      let msg =
          { id: 1, role: 'agent', text: this.$t("ai_assistant_msg"), ts: Date.now() - 60000 };

      this.ai_prompt.add_assistant_messages(msg);
  },
};
</script>

<style scoped>
/* ---------- FAB pulse ---------- */
.fab-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.fab {
  width: 54px;
  height: 54px;
  min-width: 54px;
  min-height: 54px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  pointer-events: auto;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
  transition: transform 150ms ease, box-shadow 150ms ease;
}

.fab:hover,
.fab:focus {
  transform: translateY(-2px) scale(1.04);
  box-shadow: 0 12px 26px rgba(0, 0, 0, 0.24);
}

.pulse {
  position: relative;
  overflow: visible;
}

.pulse::before,
.pulse::after {
  content: "";
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  border-radius: 50%;
  z-index: -1;
  pointer-events: none;
}

.pulse::before {
  box-shadow: 0 0 0 0 rgba(25, 118, 210, 0.35);
  animation: pulse-ring-1 1.2s infinite ease-out;
}

@keyframes pulse-ring-1 {
  0% {
    transform: translate(-50%, -50%) scale(1);
    box-shadow: 0 0 0 0 rgba(25, 118, 210, 0.35);
    opacity: 1;
  }
  70% {
    transform: translate(-50%, -50%) scale(1.18);
    box-shadow: 0 0 0 20px rgba(25, 118, 210, 0);
    opacity: 0;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.18);
    box-shadow: 0 0 0 20px rgba(25, 118, 210, 0);
    opacity: 0;
  }
}

/* ---------- Chat panel layout & animations ---------- */
.panel-slide-enter-active,
.panel-slide-leave-active {
  transition: transform 220ms cubic-bezier(.2, .8, .2, 1), opacity 160ms ease;
}
.panel-slide-enter-from {
  transform: translateY(12px);
  opacity: 0;
}
.panel-slide-enter-to {
  transform: translateY(0);
  opacity: 1;
}
.panel-slide-leave-from {
  transform: translateY(0);
  opacity: 1;
}
.panel-slide-leave-to {
  transform: translateY(12px);
  opacity: 0;
}

.chat-panel {
  position: fixed;
  bottom: 100px; /* above FAB */
  right: 24px;
  width: 400px;
  height: 540px;
  z-index: 2100;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* header */
.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  background: linear-gradient(180deg, rgba(25, 118, 210, 0.06), transparent);
}
.header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}
.header-title .title {
  font-weight: 600;
  font-size: 15px;
}
.header-title .subtitle {
  font-size: 12px;
}

/* body */
.chat-body {
  flex: 1;
  overflow: hidden;
}
.chat-messages {
  height: 100%;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* message row */
.message-row {
  display: flex;
  max-width: 100%;
}
.message-row.agent {
  justify-content: flex-start;
}
.message-row.user {
  justify-content: flex-end;
}

/* bubble */
.bubble {
  max-width: 78%;
  padding: 10px 12px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  font-size: 14px;
  line-height: 1.3;
  word-break: break-word;
}
.bubble.agent {
  border-top-left-radius: 4px;
}
.bubble.user {
  border-top-right-radius: 4px;
}
.bubble-meta {
  margin-top: 6px;
  font-size: 11px;
  opacity: 0.65;
  text-align: right;
}

/* typing dots */
.typing-dots {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 18px;
  padding: 4px 6px;
}
.typing-dots .dot {
  width: 7px;
  height: 7px;
  background: rgba(0, 0, 0, 0.22);
  border-radius: 50%;
  display: inline-block;
  transform: translateY(0);
  animation: jump 900ms infinite cubic-bezier(.2, .6, .2, 1);
}
.typing-dots .dot:nth-child(1) {
  animation-delay: 0ms;
}
.typing-dots .dot:nth-child(2) {
  animation-delay: 120ms;
}
.typing-dots .dot:nth-child(3) {
  animation-delay: 240ms;
}

@keyframes jump {
  0% {
    transform: translateY(0);
    opacity: 0.6;
  }
  30% {
    transform: translateY(-6px);
    opacity: 1;
  }
  60% {
    transform: translateY(0);
    opacity: 0.6;
  }
  100% {
    transform: translateY(0);
    opacity: 0.6;
  }
}

/* responsive */
@media (max-width: 420px) {
  .chat-panel {
    right: 12px;
    left: 12px;
    width: auto;
    bottom: 90px;
    height: 60vh;
  }
  .fab-container {
    right: 12px;
    bottom: 18px;
  }
}

/* reduced motion */
@media (prefers-reduced-motion: reduce) {
  .pulse::before,
  .pulse::after {
    animation: none !important;
  }
  .panel-slide-enter-active,
  .panel-slide-leave-active {
    transition: none !important;
  }
  .typing-dots .dot {
    animation: none !important;
    transform: translateY(0) !important;
    opacity: 1 !important;
  }
}

.my-gradient-div {
  /* Use a Vuetify blue color and set the end to transparent */
  background-image: linear-gradient(to bottom, rgba(98, 182, 255, 0.3) 1%, transparent) !important;
}
</style>
