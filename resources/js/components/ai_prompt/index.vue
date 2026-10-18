<template>
  <div :class="['aiq-page', main.is_dark ? 'aiq-dark' : 'aiq-light']">
    <!-- Hero Header -->
    <div class="aiq-hero">
      <div class="aiq-hero-bg"></div>
      <div class="aiq-hero-inner">
        <div class="d-flex align-center ga-3">
          <div class="aiq-hero-icon">
            <v-icon size="26" color="white">mdi-robot-happy-outline</v-icon>
          </div>
          <div>
            <div class="aiq-hero-title">{{ $t('app_name_ai') }}</div>
            <div class="aiq-hero-subtitle">
              <span class="aiq-status-dot"></span>
              <span>{{ $t('online') }}</span>
              <span class="aiq-subtitle-divider">•</span>
              <span class="aiq-model-pill">{{ $t('ai_model_label') }}</span>
            </div>
          </div>
        </div>
        <v-spacer></v-spacer>
        <div class="d-flex align-center ga-2">
          <!-- Thinking Mode Header Pill -->
          <v-menu
            class="aiq-thinking-menu"
            :z-index="999999"
            location="bottom end"
            :close-on-content-click="true"
            :offset="8"
          >
            <template v-slot:activator="{ props }">
              <button
                v-bind="props"
                class="aiq-thinking-badge"
                :title="$t('thinking_level')"
              >
                <v-icon size="14" class="me-1">mdi-brain</v-icon>
                <span>{{ $t(ai_prompt.thinking_level) || ai_prompt.thinking_level }}</span>
                <v-icon size="12" class="ms-1 opacity-70">mdi-chevron-down</v-icon>
              </button>
            </template>
            <v-list density="compact" nav class="rounded-xl aiq-menu-list">
              <v-list-item rounded="lg" @click="ai_prompt.thinking_level = 'low'" :active="ai_prompt.thinking_level === 'low'">
                <template v-slot:prepend>
                  <v-icon size="14" :color="ai_prompt.thinking_level === 'low' ? '#00BFA5' : 'transparent'" class="mr-2">mdi-check</v-icon>
                </template>
                <template v-slot:append>
                  <v-icon size="15" color="#00BFA5">mdi-gauge-empty</v-icon>
                </template>
                <v-list-item-title class="text-caption font-weight-medium">{{ $t('thinking_low') }}</v-list-item-title>
              </v-list-item>
              <v-list-item rounded="lg" @click="ai_prompt.thinking_level = 'medium'" :active="ai_prompt.thinking_level === 'medium'">
                <template v-slot:prepend>
                  <v-icon size="14" :color="ai_prompt.thinking_level === 'medium' ? '#2979FF' : 'transparent'" class="mr-2">mdi-check</v-icon>
                </template>
                <template v-slot:append>
                  <v-icon size="15" color="#2979FF">mdi-gauge</v-icon>
                </template>
                <v-list-item-title class="text-caption font-weight-medium">{{ $t('thinking_medium') }}</v-list-item-title>
              </v-list-item>
              <v-list-item rounded="lg" @click="ai_prompt.thinking_level = 'high'" :active="ai_prompt.thinking_level === 'high'">
                <template v-slot:prepend>
                  <v-icon size="14" :color="ai_prompt.thinking_level === 'high' ? '#7C4DFF' : 'transparent'" class="mr-2">mdi-check</v-icon>
                </template>
                <template v-slot:append>
                  <v-icon size="15" color="#7C4DFF">mdi-gauge-full</v-icon>
                </template>
                <v-list-item-title class="text-caption font-weight-medium">{{ $t('thinking_high') }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>

          <!-- Export Chat Button -->
          <v-btn
            variant="tonal"
            color="white"
            size="small"
            rounded="lg"
            @click="exportChat"
            class="aiq-hero-btn"
            :disabled="ai_prompt.messages.length === 0"
          >
            <v-icon start size="16">mdi-tray-arrow-down</v-icon>
            {{ $t('export_chat_history') }}
          </v-btn>

          <!-- Clear Messages Button -->
          <v-btn
            variant="tonal"
            color="white"
            size="small"
            rounded="lg"
            @click="clearChat"
            class="aiq-hero-btn"
            :disabled="ai_prompt.messages.length === 0"
          >
            <v-icon start size="16">mdi-broom</v-icon>
            {{ $t('clear_messages') }}
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Chat Body -->
    <div class="aiq-chat-body" ref="chatBody" @scroll="onScroll">
      <div class="aiq-chat-container">
        <!-- Welcome message if empty -->
        <div v-if="ai_prompt.messages.length === 0" class="aiq-welcome">
          <div class="aiq-welcome-badge">
            <div class="aiq-welcome-badge-glow"></div>
            <div class="aiq-welcome-icon">
              <v-icon size="30" color="white">mdi-robot-happy-outline</v-icon>
            </div>
          </div>
          <div class="aiq-welcome-title">{{ $t('app_name_ai') }}</div>
          <div class="aiq-welcome-subtitle">
            {{ $t('ai_welcome_subtitle') }}
          </div>

          <!-- Categorized 2x2 Prompt Cards -->
          <div class="aiq-cards-grid">
            <div
              v-for="card in promptCards"
              :key="card.query"
              class="aiq-prompt-card"
              @click="askQuestion(card.query)"
            >
              <div class="aiq-card-header">
                <div class="aiq-card-icon-wrap" :style="{ background: card.bg, color: card.color }">
                  <v-icon size="18">{{ card.icon }}</v-icon>
                </div>
                <v-icon size="15" class="aiq-card-arrow">mdi-arrow-top-right</v-icon>
              </div>
              <div class="aiq-card-title">{{ card.title }}</div>
              <div class="aiq-card-desc">{{ card.desc }}</div>
            </div>
          </div>
        </div>

        <!-- Load more indicator -->
        <div v-if="ai_prompt.query_loading_history" class="aiq-loading-more">
          <v-progress-circular size="22" width="2" indeterminate color="#42A5F5" />
        </div>

        <!-- Messages list -->
        <div
          v-for="(msg, index) in ai_prompt.messages"
          :key="msg.id || index"
          :class="['aiq-msg-row', msg.sender !== 'AI' ? 'aiq-msg-user' : 'aiq-msg-ai']"
        >
          <!-- User message -->
          <template v-if="msg.sender !== 'AI'">
            <div class="aiq-bubble-user">
              <div class="aiq-bubble-user-text">{{ msg.text }}</div>
              <div
                class="aiq-bubble-footer"
                @mouseenter="hoveredMsgId = msg.id"
                @mouseleave="hoveredMsgId = null"
              >
                <button
                  v-if="hoveredMsgId === msg.id"
                  class="aiq-action-btn aiq-action-delete-user"
                  @click.stop="deleteMessage(msg.id)"
                  :title="$t('delete')"
                >
                  <v-icon size="12">mdi-delete-outline</v-icon>
                </button>
                <span class="aiq-bubble-time-user my-1">{{ formatMsgTime(msg.ts) }}</span>
              </div>
            </div>
            <div class="aiq-user-avatar">
              <v-icon size="14" color="white">mdi-account</v-icon>
            </div>
          </template>

          <!-- AI message -->
          <template v-else>
            <AiMessageBox :msg="msg" :index="index" />
            <!-- AI message footer with reactions + delete -->
            <div
              class="aiq-ai-footer"
              @mouseenter="hoveredMsgId = msg.id"
              @mouseleave="hoveredMsgId = null"
            >
              <span class="aiq-ai-time">{{ formatMsgTime(msg.ts) }}</span>
              <button
                v-if="hoveredMsgId === msg.id || msg.reaction === 'like'"
                class="aiq-action-btn"
                :class="{ 'aiq-action-active': msg.reaction === 'like' }"
                @click.stop="reactMessage(msg.id, 'like')"
                :title="$t('helpful')"
              >
                <v-icon size="13">{{ msg.reaction === 'like' ? 'mdi-thumb-up' : 'mdi-thumb-up-outline' }}</v-icon>
              </button>
              <button
                v-if="hoveredMsgId === msg.id || msg.reaction === 'dislike'"
                class="aiq-action-btn"
                :class="{ 'aiq-action-active aiq-action-dislike': msg.reaction === 'dislike' }"
                @click.stop="reactMessage(msg.id, 'dislike')"
                :title="$t('not_helpful')"
              >
                <v-icon size="13">{{ msg.reaction === 'dislike' ? 'mdi-thumb-down' : 'mdi-thumb-down-outline' }}</v-icon>
              </button>
              <button
                v-if="hoveredMsgId === msg.id"
                class="aiq-action-btn aiq-action-delete-ai"
                @click.stop="deleteMessage(msg.id)"
                :title="$t('delete')"
              >
                <v-icon size="13">mdi-delete-outline</v-icon>
              </button>
            </div>
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
    </div>

    <!-- Floating scroll-to-bottom button -->
    <transition name="aiq-fab-fade">
      <button
        v-if="!isAtBottom && ai_prompt.messages.length > 0"
        class="aiq-scroll-bottom-fab"
        @click="scrollToBottom(true)"
      >
        <v-icon size="20" color="white">mdi-chevron-down</v-icon>
      </button>
    </transition>

    <!-- Input Area -->
    <div class="aiq-input-area">
      <div class="aiq-input-dock-container">
        <!-- Excel file attachment indicators -->
        <div v-if="excelFiles.length" class="aiq-excel-badges">
          <div v-for="(f, idx) in excelFiles" :key="idx" class="aiq-excel-badge">
            <v-icon size="14" color="#4CAF50">mdi-file-excel</v-icon>
            <span class="aiq-excel-name">{{ f.name }}</span>
            <button class="aiq-excel-remove" @click="removeExcel(idx)" :title="$t('remove')">
              <v-icon size="12">mdi-close</v-icon>
            </button>
          </div>
        </div>

        <div class="aiq-input-wrapper">
          <v-icon class="aiq-input-icon" size="20">mdi-lightbulb-on-outline</v-icon>
          <input
            ref="messageInput"
            v-model="newMessage"
            :placeholder="excelFiles.length ? $t('describe_what_to_do_with_excel') : $t('ask_anything_placeholder')"
            class="aiq-input"
            @keyup.enter="sendMessage"
            :disabled="loading"
          />
          <input
            ref="excelInput"
            type="file"
            accept=".xlsx,.xls,.csv"
            multiple
            style="display:none"
            @change="onExcelSelected"
          />

          <!-- Thinking Level Button -->
          <v-menu
            class="aiq-thinking-menu"
            :z-index="999999"
            location="top"
            :close-on-content-click="true"
            :offset="10"
          >
            <template v-slot:activator="{ props }">
              <button
                type="button"
                class="aiq-dock-btn"
                v-bind="props"
                :disabled="loading"
                :title="$t('thinking_level') + ': ' + $t(ai_prompt.thinking_level)"
              >
                <v-icon size="18" color="#42A5F5">mdi-brain</v-icon>
              </button>
            </template>
            <v-list density="compact" nav class="rounded-xl aiq-menu-list">
              <v-list-item rounded="lg" @click="ai_prompt.thinking_level = 'low'" :active="ai_prompt.thinking_level === 'low'">
                <template v-slot:prepend>
                  <v-icon size="14" :color="ai_prompt.thinking_level === 'low' ? '#00BFA5' : 'transparent'" class="mr-2">mdi-check</v-icon>
                </template>
                <template v-slot:append>
                  <v-icon size="15" color="#00BFA5">mdi-gauge-empty</v-icon>
                </template>
                <v-list-item-title class="text-caption font-weight-medium">{{ $t('thinking_low') }}</v-list-item-title>
              </v-list-item>
              <v-list-item rounded="lg" @click="ai_prompt.thinking_level = 'medium'" :active="ai_prompt.thinking_level === 'medium'">
                <template v-slot:prepend>
                  <v-icon size="14" :color="ai_prompt.thinking_level === 'medium' ? '#2979FF' : 'transparent'" class="mr-2">mdi-check</v-icon>
                </template>
                <template v-slot:append>
                  <v-icon size="15" color="#2979FF">mdi-gauge</v-icon>
                </template>
                <v-list-item-title class="text-caption font-weight-medium">{{ $t('thinking_medium') }}</v-list-item-title>
              </v-list-item>
              <v-list-item rounded="lg" @click="ai_prompt.thinking_level = 'high'" :active="ai_prompt.thinking_level === 'high'">
                <template v-slot:prepend>
                  <v-icon size="14" :color="ai_prompt.thinking_level === 'high' ? '#7C4DFF' : 'transparent'" class="mr-2">mdi-check</v-icon>
                </template>
                <template v-slot:append>
                  <v-icon size="15" color="#7C4DFF">mdi-gauge-full</v-icon>
                </template>
                <v-list-item-title class="text-caption font-weight-medium">{{ $t('thinking_high') }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>

          <!-- Excel Upload Button -->
          <button
            type="button"
            class="aiq-dock-btn"
            @click="$refs.excelInput.click()"
            :disabled="loading"
            :title="$t('import_excel')"
            :class="{ 'aiq-dock-btn-excel-active': excelFiles.length }"
          >
            <v-icon size="18" :color="excelFiles.length ? '#4CAF50' : 'currentColor'">mdi-file-excel-outline</v-icon>
          </button>

          <!-- Send Button -->
          <button
            type="button"
            class="aiq-send-btn"
            @click="sendMessage"
            :disabled="loading || (!newMessage.trim() && !excelFiles.length)"
            :class="{ 'aiq-send-active': (newMessage.trim() || excelFiles.length) && !loading }"
            :title="$t('send') || 'Send'"
          >
            <v-icon v-if="loading" size="18" class="aiq-spin">mdi-loading</v-icon>
            <v-icon v-else size="18">mdi-send-variant</v-icon>
          </button>
        </div>

        <div class="aiq-input-hint">
          <v-icon size="12" class="me-1 opacity-50">mdi-keyboard-return</v-icon>
          <span>{{ $t('press_enter_to_send') || 'Press Enter to send' }}</span>
        </div>
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
      excelFiles: [],
      excelParsedData: [],
      hoveredMsgId: null,
      isAtBottom: true,
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
    promptCards() {
      return [
        {
          title: this.$t('quick_prompt_charges_title'),
          desc: this.$t('quick_prompt_charges_desc'),
          query: this.$t('quick_prompt_charges_query'),
          icon: 'mdi-cash-multiple',
          color: '#42A5F5',
          bg: 'rgba(66, 165, 245, 0.12)',
        },
        {
          title: this.$t('quick_prompt_workforce_title'),
          desc: this.$t('quick_prompt_workforce_desc'),
          query: this.$t('quick_prompt_workforce_query'),
          icon: 'mdi-account-group',
          color: '#26A69A',
          bg: 'rgba(38, 166, 154, 0.12)',
        },
        {
          title: this.$t('quick_prompt_stock_title'),
          desc: this.$t('quick_prompt_stock_desc'),
          query: this.$t('quick_prompt_stock_query'),
          icon: 'mdi-warehouse',
          color: '#AB47BC',
          bg: 'rgba(171, 71, 188, 0.12)',
        },
        {
          title: this.$t('quick_prompt_shipping_title'),
          desc: this.$t('quick_prompt_shipping_desc'),
          query: this.$t('quick_prompt_shipping_query'),
          icon: 'mdi-truck-fast-outline',
          color: '#FFA726',
          bg: 'rgba(255, 167, 38, 0.12)',
        },
      ];
    },
  },
  mounted() {
    this.ai_prompt.loadQueryHistory().then(() => {
      this.$nextTick(() => this.scrollToBottom());
    });
  },
  methods: {
    formatTime() {
      const d = new Date();
      return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    },
    formatMsgTime(ts) {
      if (!ts) return this.formatTime();
      const d = new Date(ts);
      return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    },
    askQuestion(q) {
      this.newMessage = q;
      this.sendMessage();
    },
    async onExcelSelected(event) {
      const files = Array.from(event.target.files || []);
      if (!files.length) return;

      try {
        const XLSX = (await import('xlsx')).default || (await import('xlsx'));

        for (const file of files) {
          const data = await this.readFileAsArrayBuffer(file);
          const workbook = XLSX.read(new Uint8Array(data), { type: 'array' });
          const sheetName = workbook.SheetNames[0];
          const sheet = workbook.Sheets[sheetName];
          const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1 });

          if (jsonData.length === 0) continue;

          const headers = jsonData[0] || [];
          const rows = jsonData.slice(1).filter(r => r.some(c => c !== null && c !== undefined && c !== ''));

          this.excelFiles.push({ name: file.name });
          this.excelParsedData.push({ name: file.name, headers, rows });
        }
      } catch (e) {
        console.error('Excel parse error:', e);
      }

      // Reset file input
      if (this.$refs.excelInput) this.$refs.excelInput.value = '';
    },
    readFileAsArrayBuffer(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
      });
    },
    removeExcel(index) {
      this.excelFiles.splice(index, 1);
      this.excelParsedData.splice(index, 1);
    },
    async sendMessage() {
      if (!this.newMessage.trim() && !this.excelParsedData.length) return;
      this.loading = true;
      let message = this.newMessage || 'Analyze the attached Excel file(s)';
      this.newMessage = "";

      try {
        if (this.excelParsedData.length) {
          await this.ai_prompt.sendMessageWithExcel(message, this.excelParsedData);
          this.excelFiles = [];
          this.excelParsedData = [];
        } else {
          const res = await this.ai_prompt.sendMessage(message);
          if (!res?.answer) {
            console.warn('No answer received');
          }
        }
      } catch (err) {
        console.error("Error:", err);
      } finally {
        this.loading = false;
        this.$nextTick(() => {
          this.scrollToBottom(true);
          this.$refs.messageInput?.focus();
        });
      }
    },
    deleteMessage(msgId) {
      this.ai_prompt.deleteQueryMessage(msgId);
      this.hoveredMsgId = null;
    },
    reactMessage(msgId, reaction) {
      this.ai_prompt.reactQueryMessage(msgId, reaction);
    },
    scrollToBottom(force) {
      const el = this.$refs.chatBody;
      if (el && (force || this.isAtBottom)) {
        this.$nextTick(() => {
          el.scrollTop = el.scrollHeight;
          this.isAtBottom = true;
        });
      }
    },
    checkIsAtBottom() {
      const el = this.$refs.chatBody;
      if (!el) return;
      this.isAtBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 40;
    },
    onScroll() {
      this.checkIsAtBottom();
      const el = this.$refs.chatBody;
      if (el && el.scrollTop < 60 && this.ai_prompt.query_has_more && !this.ai_prompt.query_loading_history) {
        const oldHeight = el.scrollHeight;
        this.ai_prompt.loadMoreQueryHistory().then(() => {
          this.$nextTick(() => {
            el.scrollTop = el.scrollHeight - oldHeight;
          });
        });
      }
    },
    clearChat() {
      this.ai_prompt.clearQueryHistory();
    },
    exportChat() {
      if (!this.ai_prompt.messages || this.ai_prompt.messages.length === 0) return;
      let text = `# Jupiter AI Workspace Conversation - ${new Date().toLocaleString()}\n\n`;
      this.ai_prompt.messages.forEach(m => {
        const sender = m.sender === 'AI' ? 'Jupiter AI' : (m.sender || 'User');
        const time = this.formatMsgTime(m.ts);
        const cleanText = m.text ? m.text.replace(/<[^>]*>?/gm, '').trim() : '';
        text += `### ${sender} (${time})\n\n${cleanText}\n\n---\n\n`;
      });
      const blob = new Blob([text], { type: 'text/markdown;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.setAttribute('href', url);
      link.setAttribute('download', `ai_conversation_${Date.now()}.md`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
      this.main.add_snackbar({
        show: true,
        message: this.$t('export_chat_downloaded') || 'Conversation exported',
        color: '#42A5F5'
      });
    },
  },
};
</script>

<style scoped>
/* ============================================================
   AI Prompt - Dedicated Intelligence Workspace
   Aligned with claude.md Module Design System
   ============================================================ */

/* -- Page Layout -- */
.aiq-page {
  position: relative;
  display: flex;
  flex-direction: column;
  height: calc(100vh - 110px);
  max-width: 100%;
  border-radius: 20px;
  overflow: hidden;
  margin: 0;
  width: 100%;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--aiq-border);
  transition: background 0.3s ease, border-color 0.3s ease;
}

/* -- Theme Tokens -- */
.aiq-dark {
  --aiq-body-bg: #121212;
  --aiq-border: rgba(255, 255, 255, 0.08);
  --aiq-card-bg: #1E1E1E;
  --aiq-card-border: rgba(255, 255, 255, 0.08);
  --aiq-text: rgba(255, 255, 255, 0.92);
  --aiq-text-muted: rgba(255, 255, 255, 0.52);
  --aiq-dock-bg: rgba(30, 30, 30, 0.88);
  --aiq-dock-border: rgba(66, 165, 245, 0.22);
}

.aiq-light {
  --aiq-body-bg: #f4f6fa;
  --aiq-border: rgba(0, 0, 0, 0.08);
  --aiq-card-bg: #ffffff;
  --aiq-card-border: rgba(0, 0, 0, 0.08);
  --aiq-text: #1a202c;
  --aiq-text-muted: rgba(0, 0, 0, 0.5);
  --aiq-dock-bg: rgba(255, 255, 255, 0.9);
  --aiq-dock-border: rgba(66, 165, 245, 0.28);
}

/* -- Hero Header -- */
.aiq-hero {
  position: relative;
  flex-shrink: 0;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(21, 101, 192, 0.22);
}

.aiq-hero-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
}

.aiq-hero-inner {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  padding: 12px 24px;
  color: #fff;
}

.aiq-hero-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.aiq-hero-title {
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 0.3px;
  line-height: 1.2;
}

.aiq-hero-subtitle {
  font-size: 11px;
  opacity: 0.9;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.aiq-subtitle-divider {
  opacity: 0.5;
}

.aiq-model-pill {
  background: rgba(255, 255, 255, 0.18);
  padding: 1px 7px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.aiq-status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #4CAF50;
  box-shadow: 0 0 6px rgba(76, 175, 80, 0.7);
  animation: aiq-blink 2s ease-in-out infinite;
}

@keyframes aiq-blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.aiq-thinking-badge {
  display: inline-flex;
  align-items: center;
  padding: 5px 12px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
  color: #ffffff;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.aiq-thinking-badge:hover {
  background: rgba(255, 255, 255, 0.28);
  border-color: rgba(255, 255, 255, 0.45);
  transform: translateY(-1px);
}

.aiq-hero-btn {
  text-transform: none !important;
  font-weight: 600 !important;
  font-size: 11.5px !important;
  border-radius: 10px !important;
  background: rgba(255, 255, 255, 0.16) !important;
  border: 1px solid rgba(255, 255, 255, 0.25) !important;
}

.aiq-hero-btn:hover {
  background: rgba(255, 255, 255, 0.26) !important;
}

/* -- Chat Body & Container -- */
.aiq-chat-body {
  flex: 1 1 0;
  min-height: 0;
  overflow-y: auto;
  padding: 24px 20px;
  background: var(--aiq-body-bg);
  display: flex;
  flex-direction: column;
  scroll-behavior: smooth;
}

.aiq-chat-body::-webkit-scrollbar {
  width: 5px;
}

.aiq-chat-body::-webkit-scrollbar-thumb {
  background: rgba(128, 128, 128, 0.25);
  border-radius: 10px;
}

.aiq-chat-container {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 100%;
}

/* -- Welcome / Empty State -- */
.aiq-welcome {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  text-align: center;
  padding: 14px 16px 8px;
  margin: auto 0;
}

.aiq-welcome-badge {
  position: relative;
  width: 58px;
  height: 58px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.aiq-welcome-badge-glow {
  position: absolute;
  inset: -3px;
  background: conic-gradient(from 0deg, #1565C0, #42A5F5, #00C853, #9C27B0, #1565C0);
  border-radius: 50%;
  filter: blur(8px);
  opacity: 0.6;
  animation: aiq-spin-r 12s linear infinite;
}

.aiq-welcome-icon {
  position: relative;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0D47A1 0%, #1976D2 50%, #42A5F5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 18px rgba(21, 101, 192, 0.35);
}

.aiq-welcome-title {
  font-size: 20px;
  font-weight: 800;
  color: var(--aiq-text);
  letter-spacing: -0.2px;
  margin-bottom: 4px;
}

.aiq-welcome-subtitle {
  font-size: 12px;
  color: var(--aiq-text-muted);
  max-width: 500px;
  line-height: 1.45;
  margin-bottom: 16px;
}

/* 2x2 Feature Cards Grid */
.aiq-cards-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  width: 100%;
  max-width: 720px;
}

.aiq-prompt-card {
  display: flex;
  flex-direction: column;
  padding: 12px 15px;
  border-radius: 14px;
  background: var(--aiq-card-bg);
  border: 1px solid var(--aiq-card-border);
  cursor: pointer;
  text-align: left;
  transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.aiq-prompt-card:hover {
  transform: translateY(-2px);
  border-color: #42A5F5;
  box-shadow: 0 8px 20px rgba(66, 165, 245, 0.16);
}

.aiq-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}

.aiq-card-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.aiq-card-arrow {
  color: var(--aiq-text-muted);
  transition: all 0.2s ease;
}

.aiq-prompt-card:hover .aiq-card-arrow {
  color: #42A5F5;
  transform: translate(2px, -2px);
}

.aiq-card-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--aiq-text);
  margin-bottom: 2px;
}

.aiq-card-desc {
  font-size: 11px;
  color: var(--aiq-text-muted);
  line-height: 1.35;
}

/* -- Loading Indicator -- */
.aiq-loading-more {
  display: flex;
  justify-content: center;
  padding: 12px 0;
}

/* -- Messages -- */
.aiq-msg-row {
  display: flex;
  max-width: 100%;
  animation: aiq-msg-in 0.3s ease;
}

@keyframes aiq-msg-in {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.aiq-msg-user {
  justify-content: flex-end;
  gap: 8px;
  align-items: flex-end;
}

.aiq-msg-ai {
  justify-content: flex-start;
}

/* -- User Bubble -- */
.aiq-bubble-user {
  max-width: 72%;
  padding: 11px 16px;
  border-radius: 18px;
  border-bottom-right-radius: 4px;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(21, 101, 192, 0.25);
}

.aiq-bubble-user-text {
  font-size: 13.5px;
  line-height: 1.5;
  word-break: break-word;
}

.aiq-bubble-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
  margin-top: 4px;
}

.aiq-bubble-time-user {
  font-size: 10px;
  opacity: 0.65;
}

.aiq-user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1565C0, #42A5F5);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(21, 101, 192, 0.3);
}

/* -- AI Footer -- */
.aiq-ai-footer {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 0 0 54px;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.aiq-ai-footer:hover {
  opacity: 1;
}

.aiq-ai-time {
  font-size: 10.5px;
  color: var(--aiq-text-muted);
  font-weight: 500;
}

/* -- Action Buttons -- */
.aiq-action-btn {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  color: var(--aiq-text-muted);
  transition: all 0.2s;
  padding: 0;
}

.aiq-action-btn:hover {
  background: rgba(66, 165, 245, 0.12);
  color: #42A5F5;
}

.aiq-action-active {
  color: #42A5F5 !important;
}

.aiq-action-dislike {
  color: #F44336 !important;
}

.aiq-action-delete-user {
  color: rgba(255, 255, 255, 0.6) !important;
}

.aiq-action-delete-user:hover {
  background: rgba(244, 67, 54, 0.2) !important;
  color: #F44336 !important;
}

.aiq-action-delete-ai:hover {
  background: rgba(244, 67, 54, 0.1) !important;
  color: #F44336 !important;
}

/* -- Typing Indicator -- */
.aiq-typing-card {
  background: var(--aiq-card-bg);
  border: 1px solid var(--aiq-card-border);
  border-radius: 16px;
  border-top-left-radius: 4px;
  padding: 12px 16px;
}

.aiq-typing-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.aiq-typing-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1565C0, #42A5F5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.aiq-typing-label {
  font-size: 11px;
  font-weight: 700;
  color: #1976D2;
}

.aiq-typing-dots {
  display: flex;
  gap: 5px;
  padding: 2px 0;
}

.aiq-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #42A5F5;
  display: inline-block;
  animation: aiq-bounce 1.2s infinite ease-in-out;
}

.aiq-dot:nth-child(1) { animation-delay: 0ms; }
.aiq-dot:nth-child(2) { animation-delay: 160ms; }
.aiq-dot:nth-child(3) { animation-delay: 320ms; }

@keyframes aiq-bounce {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.35; }
  30% { transform: translateY(-7px); opacity: 1; }
}

/* -- Floating Scroll-to-Bottom Button -- */
.aiq-scroll-bottom-fab {
  position: absolute;
  bottom: 120px;
  right: 36px;
  z-index: 10;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1565C0, #42A5F5);
  box-shadow: 0 4px 16px rgba(21, 101, 192, 0.4);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.aiq-scroll-bottom-fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(21, 101, 192, 0.55);
}

.aiq-fab-fade-enter-active,
.aiq-fab-fade-leave-active {
  transition: opacity 0.25s, transform 0.25s;
}

.aiq-fab-fade-enter-from,
.aiq-fab-fade-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.8);
}

/* -- Input Area & Centered Dock -- */
.aiq-input-area {
  flex-shrink: 0;
  padding: 10px 20px 14px;
  background: transparent;
}

.aiq-input-dock-container {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
}

.aiq-input-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--aiq-dock-bg);
  border: 1.5px solid var(--aiq-dock-border);
  border-radius: 28px;
  padding: 4px 6px 4px 16px;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
  transition: all 0.25s ease;
}

.aiq-input-wrapper:hover {
  border-color: rgba(66, 165, 245, 0.45);
}

.aiq-input-wrapper:focus-within {
  border-color: #42A5F5;
  box-shadow: 0 0 0 3px rgba(66, 165, 245, 0.2), 0 8px 30px rgba(0, 0, 0, 0.12);
}

.aiq-input-icon {
  color: var(--aiq-text-muted) !important;
}

.aiq-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: var(--aiq-text);
  font-size: 13.5px;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  padding: 10px 0;
}

.aiq-input::placeholder {
  color: var(--aiq-text-muted);
}

.aiq-dock-btn {
  width: 36px;
  height: 36px;
  min-width: 36px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  color: var(--aiq-text-muted);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
  padding: 0;
}

.aiq-dock-btn:hover {
  background: rgba(66, 165, 245, 0.12);
  color: #42A5F5;
  transform: scale(1.05);
}

.aiq-dock-btn:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.aiq-dock-btn-excel-active {
  background: rgba(76, 175, 80, 0.15) !important;
  color: #4CAF50 !important;
  box-shadow: 0 0 0 1px rgba(76, 175, 80, 0.3);
}

.aiq-send-btn {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(128, 128, 128, 0.08);
  color: var(--aiq-text-muted);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.aiq-send-btn:disabled {
  cursor: not-allowed;
  opacity: 0.35;
}

.aiq-send-btn.aiq-send-active {
  background: linear-gradient(135deg, #1565C0, #42A5F5);
  color: #fff;
  box-shadow: 0 4px 14px rgba(66, 165, 245, 0.4);
}

.aiq-send-btn.aiq-send-active:hover {
  transform: scale(1.08) rotate(-10deg);
  box-shadow: 0 6px 20px rgba(66, 165, 245, 0.55);
}

.aiq-input-hint {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 5px 6px 0;
  font-size: 10.5px;
  color: var(--aiq-text-muted);
  font-weight: 500;
}

.aiq-spin {
  animation: aiq-spin-r 0.8s linear infinite;
}

@keyframes aiq-spin-r {
  from { transform: rotate(0); }
  to   { transform: rotate(360deg); }
}

/* -- Excel Badges -- */
.aiq-excel-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 0 0 8px;
}

.aiq-excel-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(76, 175, 80, 0.08);
  border: 1px solid rgba(76, 175, 80, 0.25);
  border-radius: 20px;
  padding: 4px 10px 4px 8px;
  font-size: 11px;
  font-weight: 600;
  color: #4CAF50;
  animation: aiq-msg-in 0.3s ease;
}

.aiq-excel-name {
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--aiq-text);
  font-weight: 500;
}

.aiq-excel-remove {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: none;
  background: rgba(244, 67, 54, 0.1);
  color: #F44336;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
}

.aiq-excel-remove:hover {
  background: rgba(244, 67, 54, 0.2);
  transform: scale(1.15);
}

/* -- Responsive -- */
@media (max-width: 768px) {
  .aiq-cards-grid {
    grid-template-columns: 1fr;
  }
  .aiq-welcome {
    padding: 20px 8px 16px;
  }
}

.v-overlay.aiq-thinking-menu {
  z-index: 999999 !important;
}

.aiq-thinking-menu .v-overlay__content {
  z-index: 999999 !important;
}

.aiq-menu-list {
  min-width: 175px !important;
  padding: 6px !important;
  border-radius: 16px !important;
  backdrop-filter: blur(24px) !important;
  -webkit-backdrop-filter: blur(24px) !important;
  box-shadow: 0 18px 48px rgba(0, 0, 0, 0.22), 0 4px 14px rgba(0, 0, 0, 0.08) !important;
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
}

.aiq-dark .aiq-menu-list {
  background: rgba(22, 26, 34, 0.96) !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.65), 0 6px 18px rgba(0, 0, 0, 0.4) !important;
}

.aiq-menu-list .v-list-item {
  transition: all 0.18s ease !important;
  margin-bottom: 2px !important;
  border-radius: 10px !important;
}

.aiq-menu-list .v-list-item:hover {
  background: rgba(66, 165, 245, 0.12) !important;
}

.aiq-menu-list .v-list-item--active {
  background: rgba(66, 165, 245, 0.18) !important;
  font-weight: 700 !important;
}
</style>
