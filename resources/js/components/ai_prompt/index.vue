<template>
  <div :class="['aiq-page', main.is_dark ? 'aiq-dark' : 'aiq-light']">
    <!-- Hero Header -->
    <div class="aiq-hero">
      <div class="aiq-hero-bg"></div>
      <div class="aiq-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="aiq-hero-icon">
            <v-icon size="26" color="white">mdi-robot-happy-outline</v-icon>
          </div>
          <div>
            <div class="aiq-hero-title">{{ $t('app_name_ai') }}</div>
            <div class="aiq-hero-subtitle">
              <span class="aiq-status-dot"></span>
              {{ $t('online') }} · {{ $t('ai_assistant') }}
            </div>
          </div>
        </div>
        <v-spacer></v-spacer>
        <v-btn
          variant="tonal"
          color="white"
          size="small"
          rounded="lg"
          @click="clearChat"
          class="aiq-clear-btn"
        >
          <v-icon start size="16">mdi-broom</v-icon>
          {{ $t('clear_messages') || 'Clear' }}
        </v-btn>
      </div>
    </div>

    <!-- Chat Body -->
    <div class="aiq-chat-body" ref="chatBody">
      <!-- Welcome message if empty -->
      <div v-if="ai_prompt.messages.length === 0" class="aiq-welcome">
        <div class="aiq-welcome-icon">
          <v-icon size="44" color="#2196F3">mdi-robot-happy-outline</v-icon>
        </div>
        <div class="aiq-welcome-title">{{ $t('ai_assistant') }}</div>
        <div class="aiq-welcome-text">
          {{ $t('ai_welcome_text') }}
        </div>
        <div class="aiq-quick-actions">
          <v-chip
            v-for="q in quickQuestions"
            :key="q.text"
            variant="outlined"
            color="info"
            size="small"
            class="ma-1 aiq-quick-chip"
            @click="askQuestion(q.text)"
          >
            <v-icon start size="14">{{ q.icon }}</v-icon>
            {{ q.text }}
          </v-chip>
        </div>
      </div>

      <!-- Messages -->
      <div
        v-for="(msg, index) in ai_prompt.messages"
        :key="index"
        :class="['aiq-msg-row', msg.sender !== 'AI' ? 'aiq-msg-user' : 'aiq-msg-ai']"
      >
        <!-- User message -->
        <template v-if="msg.sender !== 'AI'">
          <div class="aiq-bubble-user">
            <div class="aiq-bubble-user-text">{{ msg.text }}</div>
            <div class="aiq-bubble-time-user">{{ formatTime() }}</div>
          </div>
          <div class="aiq-user-avatar">
            <v-icon size="14" color="white">mdi-account</v-icon>
          </div>
        </template>

        <!-- AI message -->
        <template v-else>
          <AiMessageBox :msg="msg" :index="index" />
        </template>
      </div>

      <!-- Typing indicator -->
      <div v-if="loading" class="aiq-msg-row aiq-msg-ai">
        <div class="aiq-typing-card">
          <div class="aiq-typing-header">
            <div class="aiq-typing-avatar">
              <v-icon size="14" color="white">mdi-robot-happy-outline</v-icon>
            </div>
            <span class="aiq-typing-label">{{ $t('app_name_ai') }}</span>
          </div>
          <div class="aiq-typing-dots">
            <span class="aiq-dot"></span>
            <span class="aiq-dot"></span>
            <span class="aiq-dot"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Input Area -->
    <div class="aiq-input-area">
      <div class="aiq-input-wrapper">
        <v-icon class="aiq-input-icon" size="20">mdi-lightbulb-on-outline</v-icon>
        <input
          v-model="newMessage"
          :placeholder="$t('type_a_message') || 'Ask about your data...'"
          class="aiq-input"
          @keyup.enter="sendMessage"
          :disabled="loading"
        />
        <button
          class="aiq-send-btn"
          @click="sendMessage"
          :disabled="loading || !newMessage.trim()"
          :class="{ 'aiq-send-active': newMessage.trim() && !loading }"
        >
          <v-icon v-if="loading" size="18" class="aiq-spin">mdi-loading</v-icon>
          <v-icon v-else size="18">mdi-send-variant</v-icon>
        </button>
      </div>
      <div class="aiq-input-hint">
        <v-icon size="12" class="me-1" style="opacity:0.4">mdi-keyboard-return</v-icon>
        <span>{{ $t('press_enter_to_send') || 'Press Enter to send' }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { useAIPrompt } from "@/stores/AIPrompt";
import { useMain } from "@/stores/Main";
import AiMessageBox from "@/components/ai_prompt/AiMessageBox.vue";

export default {
  components: { AiMessageBox },
  data() {
    return {
      newMessage: "",
      loading: false,
      quickQuestions: [
        { text: "Total charges this month?", icon: "mdi-cash-multiple" },
        { text: "Top 5 suppliers by weight?", icon: "mdi-podium-gold" },
        { text: "Daily revenue last 7 days", icon: "mdi-chart-line" },
        { text: "Stock by warehouse", icon: "mdi-warehouse" },
      ],
    };
  },
  computed: {
    ai_prompt() { return useAIPrompt(); },
    main() { return useMain(); },
  },
  methods: {
    formatTime() {
      const d = new Date();
      return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    },
    askQuestion(q) {
      this.newMessage = q;
      this.sendMessage();
    },
    async sendMessage() {
      if (!this.newMessage.trim()) return;
      this.loading = true;
      let message = this.newMessage;
      this.newMessage = "";
      try {
        const res = await this.ai_prompt.sendMessage(message);
        if (!res?.answer) {
          console.warn('No answer received');
        }
      } catch (err) {
        console.error("Error:", err);
      } finally {
        this.loading = false;
        this.$nextTick(() => this.scrollToBottom());
      }
    },
    scrollToBottom() {
      const el = this.$refs.chatBody;
      if (el) {
        this.$nextTick(() => { el.scrollTop = el.scrollHeight; });
      }
    },
    clearChat() {
      this.ai_prompt.messages = [];
    },
  },
  updated() {
    this.scrollToBottom();
  },
};
</script>

<style scoped>
/* ── Page Layout ── */
.aiq-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 140px); /* Safely offset layout paddings/margins to prevent body scroll */
  max-width: 100%;
  border-radius: 20px;
  overflow: hidden;
  margin: 0;
  width: 100%;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--aiq-input-border);
}

/* ── Theme ── */
.aiq-dark { height: 90vh; --aiq-body-bg: #0a0a0a; --aiq-input-bg: #1a1a1a; --aiq-input-border: rgba(255,255,255,0.08); --aiq-card-bg: #141414; --aiq-text: rgba(255,255,255,0.87); --aiq-text-muted: rgba(255,255,255,0.4); }
.aiq-light { height: 90vh; --aiq-body-bg: #f8f9fa; --aiq-input-bg: #ffffff; --aiq-input-border: rgba(0,0,0,0.1); --aiq-card-bg: #ffffff; --aiq-text: #212121; --aiq-text-muted: rgba(0,0,0,0.38); }

/* ── Hero ── */
.aiq-hero { position: relative; flex-shrink: 0; overflow: hidden; }
.aiq-hero-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
}
.aiq-hero-inner {
  position: relative; z-index: 1;
  display: flex; align-items: center;
  padding: 12px 22px;
  color: #fff;
}
.aiq-hero-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: rgba(255,255,255,.18);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255,255,255,.15);
}
.aiq-hero-title { font-size: 17px; font-weight: 800; letter-spacing: .3px; }
.aiq-hero-subtitle { font-size: 11px; opacity: .75; display: flex; align-items: center; gap: 6px; }
.aiq-status-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #4CAF50;
  box-shadow: 0 0 6px rgba(76,175,80,0.6);
  animation: aiq-blink 2s ease-in-out infinite;
}
@keyframes aiq-blink { 0%,100% { opacity:1; } 50% { opacity:0.4; } }
.aiq-clear-btn { text-transform: none !important; font-weight: 600; font-size: 11px; }

/* ── Chat Body ── */
.aiq-chat-body {
  flex: 1 1 0;
  min-height: 0;
  overflow-y: auto;
  padding: 20px 22px;
  background: var(--aiq-body-bg);
  display: flex;
  flex-direction: column;
  gap: 14px;
  scroll-behavior: smooth;
}
.aiq-chat-body::-webkit-scrollbar { width: 4px; }
.aiq-chat-body::-webkit-scrollbar-thumb { background: rgba(128,128,128,0.2); border-radius: 10px; }

/* ── Welcome ── */
.aiq-welcome {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; flex: 1; text-align: center; gap: 12px;
}
.aiq-welcome-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: rgba(33,150,243,0.08);
  display: flex; align-items: center; justify-content: center;
}
.aiq-welcome-title { font-size: 22px; font-weight: 800; color: var(--aiq-text); }
.aiq-welcome-text { font-size: 13px; color: var(--aiq-text-muted); max-width: 380px; line-height: 1.6; }
.aiq-quick-actions { display: flex; flex-wrap: wrap; justify-content: center; margin-top: 8px; max-width: 420px; }
.aiq-quick-chip { cursor: pointer !important; transition: all 0.2s; text-transform: none !important; font-weight: 500; }
.aiq-quick-chip:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(33,150,243,0.2); }

/* ── Message Rows ── */
.aiq-msg-row {
  display: flex;
  max-width: 100%;
  animation: aiq-msg-in 0.3s ease;
}
@keyframes aiq-msg-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.aiq-msg-user { justify-content: flex-end; gap: 8px; align-items: flex-end; }
.aiq-msg-ai { justify-content: flex-start; }

/* ── User Bubble ── */
.aiq-bubble-user {
  max-width: 70%;
  padding: 10px 16px;
  border-radius: 18px;
  border-bottom-right-radius: 4px;
  background: linear-gradient(135deg, #1976D2, #2196F3);
  color: #fff;
  box-shadow: 0 4px 14px rgba(33,150,243,0.25);
}
.aiq-bubble-user-text { font-size: 13.5px; line-height: 1.5; }
.aiq-bubble-time-user { font-size: 10px; opacity: 0.5; text-align: right; margin-top: 4px; }
.aiq-user-avatar {
  width: 26px; height: 26px; border-radius: 50%;
  background: linear-gradient(135deg, #1565C0, #1E88E5);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ── Typing ── */
.aiq-typing-card {
  background: var(--aiq-card-bg);
  border: 1px solid rgba(var(--v-border-color), 0.08);
  border-radius: 16px; border-top-left-radius: 4px;
  padding: 12px 16px;
}
.aiq-typing-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.aiq-typing-avatar {
  width: 24px; height: 24px; border-radius: 50%;
  background: linear-gradient(135deg, #1976D2, #42A5F5);
  display: flex; align-items: center; justify-content: center;
}
.aiq-typing-label { font-size: 11px; font-weight: 700; color: #1976D2; }
.aiq-typing-dots { display: flex; gap: 5px; padding: 2px 0; }
.aiq-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #2196F3; display: inline-block;
  animation: aiq-bounce 1.2s infinite ease-in-out;
}
.aiq-dot:nth-child(1) { animation-delay: 0ms; }
.aiq-dot:nth-child(2) { animation-delay: 160ms; }
.aiq-dot:nth-child(3) { animation-delay: 320ms; }
@keyframes aiq-bounce {
  0%,60%,100% { transform: translateY(0); opacity: 0.35; }
  30% { transform: translateY(-8px); opacity: 1; }
}

/* ── Input Area ── */
.aiq-input-area {
  flex-shrink: 0;
  padding: 10px 22px 14px;
  background: var(--aiq-card-bg);
  border-top: 1px solid rgba(var(--v-border-color), 0.06);
}
.aiq-input-wrapper {
  display: flex; align-items: center; gap: 8px;
  background: var(--aiq-input-bg);
  border: 1.5px solid var(--aiq-input-border);
  border-radius: 14px;
  padding: 3px 4px 3px 14px;
  transition: all 0.25s;
}
.aiq-input-wrapper:focus-within {
  border-color: #2196F3;
  box-shadow: 0 0 0 2px rgba(33,150,243,0.12);
}
.aiq-input-icon { color: var(--aiq-text-muted) !important; }
.aiq-input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--aiq-text); font-size: 13.5px;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  padding: 10px 0;
}
.aiq-input::placeholder { color: var(--aiq-text-muted); }
.aiq-send-btn {
  width: 38px; height: 38px; min-width: 38px; border-radius: 50%;
  border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  background: rgba(128,128,128,0.06); color: var(--aiq-text-muted);
  transition: all 0.3s cubic-bezier(0.4,0,0.2,1); flex-shrink: 0;
}
.aiq-send-btn:disabled { cursor: not-allowed; opacity: 0.35; }
.aiq-send-btn.aiq-send-active {
  background: linear-gradient(135deg, #1976D2, #2196F3);
  color: #fff;
  box-shadow: 0 4px 14px rgba(33,150,243,0.35);
}
.aiq-send-btn.aiq-send-active:hover {
  transform: scale(1.1) rotate(-15deg);
  box-shadow: 0 6px 20px rgba(33,150,243,0.45);
}
.aiq-input-hint {
  display: flex; align-items: center; justify-content: flex-end;
  padding: 5px 4px 0;
  font-size: 10px; color: var(--aiq-text-muted); font-weight: 500;
}
.aiq-spin { animation: aiq-spin-r 0.8s linear infinite; }
@keyframes aiq-spin-r { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>
