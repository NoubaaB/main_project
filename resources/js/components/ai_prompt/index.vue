<template>
  <div :class="['aiq-page', main.is_dark ? 'aiq-dark' : 'aiq-light', { 'aiq-expanded': isExpanded, 'aiq-fullscreen': isFullscreen }]">
    <!-- Hero Header -->
    <div class="aiq-hero">
      <div class="aiq-hero-bg"></div>
      <div class="aiq-hero-inner">
        <div class="d-flex align-center ga-3">
          <div class="aiq-hero-icon">
            <v-icon size="26" color="white">mdi-robot-happy-outline</v-icon>
          </div>
          <div>
            <div class="aiq-hero-title d-flex align-center flex-wrap ga-2">
              <span>{{ $t('app_name_ai') }}</span>
              <div class="aiq-creator-pill" :title="$t('ai_creator_title')">
                <v-icon size="13" color="#42A5F5" class="me-1">mdi-shield-check</v-icon>
                <span>{{ $t('ai_creator_badge') }}</span>
                <v-icon size="12" color="#42A5F5" class="ms-1">mdi-check-decagram</v-icon>
              </div>
            </div>
            <div class="aiq-hero-subtitle">
              <span class="aiq-status-dot"></span>
              <span>{{ $t('online') }}</span>
              <span class="aiq-subtitle-divider">•</span>
              <span class="aiq-model-pill">{{ $t('ai_model_label') }}</span>
              <span class="aiq-subtitle-divider">•</span>
              <span class="text-caption font-weight-medium" style="color: rgba(255,255,255,0.9);">
                {{ $t('ai_creator_badge') }}
              </span>
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

          <!-- Expand / Wide View Button -->
          <v-btn
            variant="tonal"
            color="white"
            size="small"
            rounded="lg"
            @click="toggleExpand"
            class="aiq-hero-btn"
            :class="{ 'aiq-hero-btn-active': isExpanded }"
            :title="isExpanded ? $t('collapse_view') : $t('expand_view')"
          >
            <v-icon start size="16">{{ isExpanded ? 'mdi-arrow-collapse-horizontal' : 'mdi-arrow-expand-horizontal' }}</v-icon>
            {{ isExpanded ? $t('normal_width') : $t('wide_view') }}
          </v-btn>

          <!-- Fullscreen Workspace Button -->
          <v-btn
            variant="tonal"
            color="white"
            size="small"
            rounded="lg"
            @click="toggleFullscreen"
            class="aiq-hero-btn"
            :class="{ 'aiq-hero-btn-active': isFullscreen }"
            :title="isFullscreen ? $t('exit_fullscreen') : $t('fullscreen_view')"
          >
            <v-icon start size="16">{{ isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen' }}</v-icon>
            {{ isFullscreen ? $t('exit_fullscreen') : $t('fullscreen_view') }}
          </v-btn>

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

      <!-- Domain Quick Filters Bar -->
      <div class="aiq-domain-chips-bar">
        <button class="aiq-domain-pill" @click="askQuestion($t('quick_prompt_overview_query') || 'Donne moi le résumé exécutif et les KPIs globaux avec graphiques')">
          <v-icon size="13" color="#42A5F5" class="me-1">mdi-view-dashboard</v-icon>
          <span>{{ $t('ai_filter_all_kpis') }}</span>
        </button>
        <button class="aiq-domain-pill" @click="askQuestion($t('quick_prompt_charges_query') || 'Analyse détaillée des charges et réceptions')">
          <v-icon size="13" color="#90CAF9" class="me-1">mdi-truck-delivery</v-icon>
          <span>{{ $t('ai_filter_charges') }}</span>
        </button>
        <button class="aiq-domain-pill" @click="askQuestion($t('quick_prompt_shipping_query') || 'Analyse des ventes et expéditions clients')">
          <v-icon size="13" color="#b39ddb" class="me-1">mdi-ship-wheel</v-icon>
          <span>{{ $t('ai_filter_shippings') }}</span>
        </button>
        <button class="aiq-domain-pill" @click="askQuestion('Analyse du rendement et production usine')">
          <v-icon size="13" color="#80cbc4" class="me-1">mdi-factory</v-icon>
          <span>{{ $t('ai_filter_productions') }}</span>
        </button>
        <button class="aiq-domain-pill" @click="askQuestion($t('quick_prompt_stock_query') || 'Situation des stocks et entrepôts')">
          <v-icon size="13" color="#ce93d8" class="me-1">mdi-warehouse</v-icon>
          <span>{{ $t('ai_filter_warehouses') }}</span>
        </button>
        <button class="aiq-domain-pill" @click="askQuestion('Situation de la facturation et finances')">
          <v-icon size="13" color="#ffcc80" class="me-1">mdi-file-document-outline</v-icon>
          <span>{{ $t('ai_filter_finances') }}</span>
        </button>
        <button class="aiq-domain-pill" @click="askQuestion($t('quick_prompt_workforce_query') || 'Analyse de la main d\'oeuvre et quinzaine')">
          <v-icon size="13" color="#a7ffeb" class="me-1">mdi-account-group</v-icon>
          <span>{{ $t('ai_filter_workforce') }}</span>
        </button>
        <button class="aiq-domain-pill aiq-domain-pill-creator" @click="askQuestion($t('quick_prompt_creator_query') || 'Présentez-moi l\'architecture système et le moteur IA')">
          <v-icon size="13" color="#FFD700" class="me-1">mdi-crown</v-icon>
          <span>{{ $t('ai_filter_creator') }}</span>
        </button>
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
          :id="'aiq-msg-' + index"
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
            <div
              class="aiq-ai-msg-block"
              @mouseenter="hoveredMsgId = msg.id"
              @mouseleave="hoveredMsgId = null"
            >
              <AiMessageBox
                :msg="msg"
                :index="index"
                :is-expanded-mode="isExpanded"
              />
              <!-- AI message footer placed cleanly BELLOW the message -->
              <div class="aiq-ai-footer">
                <span class="aiq-ai-time">{{ formatMsgTime(msg.ts) }}</span>
                <div class="aiq-ai-footer-actions">
                  <button
                    class="aiq-action-btn"
                    :class="{ 'aiq-action-active': msg.reaction === 'like' }"
                    @click.stop="reactMessage(msg.id, 'like')"
                    :title="$t('helpful') || 'Helpful'"
                  >
                    <v-icon size="13">{{ msg.reaction === 'like' ? 'mdi-thumb-up' : 'mdi-thumb-up-outline' }}</v-icon>
                  </button>
                  <button
                    class="aiq-action-btn"
                    :class="{ 'aiq-action-active aiq-action-dislike': msg.reaction === 'dislike' }"
                    @click.stop="reactMessage(msg.id, 'dislike')"
                    :title="$t('not_helpful') || 'Not Helpful'"
                  >
                    <v-icon size="13">{{ msg.reaction === 'dislike' ? 'mdi-thumb-down' : 'mdi-thumb-down-outline' }}</v-icon>
                  </button>
                  <button
                    class="aiq-action-btn"
                    @click.stop="copyAiText(msg, index)"
                    :title="$t('copy') || 'Copy'"
                  >
                    <v-icon size="13">{{ copiedMsgId === msg.id ? 'mdi-check' : 'mdi-content-copy' }}</v-icon>
                  </button>
                  <button
                    class="aiq-action-btn aiq-action-delete-ai"
                    @click.stop="deleteMessage(msg.id)"
                    :title="$t('delete') || 'Delete'"
                  >
                    <v-icon size="13">mdi-delete-outline</v-icon>
                  </button>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Typing indicator -->
        <div v-if="loading" id="aiq-typing-row" class="aiq-msg-row aiq-msg-ai">
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
            @keydown.enter.prevent="sendMessage"
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

    <!-- Summary Snackbar (Rule 5) -->
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3500"
      rounded="pill"
      location="bottom end"
    >
      <div class="d-flex align-center ga-2">
        <v-icon size="18">{{ snackbar.icon }}</v-icon>
        <span class="font-weight-medium">{{ snackbar.text }}</span>
      </div>
    </v-snackbar>
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
      isExpanded: localStorage.getItem('aiq_is_expanded') === 'true',
      isFullscreen: false,
      copiedMsgId: null,
      snackbar: {
        show: false,
        text: '',
        color: '#42A5F5',
        icon: 'mdi-information-outline'
      },
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
          title: this.$t('quick_prompt_overview_title') || 'Executive KPI Summary',
          desc: this.$t('quick_prompt_overview_desc') || 'Comprehensive business KPIs across purchases, production, stock, and sales',
          query: this.$t('quick_prompt_overview_query') || 'Give me the executive overview and global KPIs with charts',
          icon: 'mdi-chart-line',
          color: '#42A5F5',
          bg: 'rgba(66, 165, 245, 0.12)',
        },
        {
          title: this.$t('quick_prompt_charges_title'),
          desc: this.$t('quick_prompt_charges_desc'),
          query: this.$t('quick_prompt_charges_query'),
          icon: 'mdi-truck-delivery-outline',
          color: '#1565C0',
          bg: 'rgba(21, 101, 192, 0.12)',
        },
        {
          title: this.$t('quick_prompt_shipping_title'),
          desc: this.$t('quick_prompt_shipping_desc'),
          query: this.$t('quick_prompt_shipping_query'),
          icon: 'mdi-ship-wheel',
          color: '#6c66bb',
          bg: 'rgba(108, 102, 187, 0.12)',
        },
        {
          title: this.$t('quick_prompt_stock_title'),
          desc: this.$t('quick_prompt_stock_desc'),
          query: this.$t('quick_prompt_stock_query'),
          icon: 'mdi-warehouse',
          color: '#a21ee5',
          bg: 'rgba(162, 30, 229, 0.12)',
        },
        {
          title: this.$t('quick_prompt_workforce_title'),
          desc: this.$t('quick_prompt_workforce_desc'),
          query: this.$t('quick_prompt_workforce_query'),
          icon: 'mdi-account-group',
          color: '#00897B',
          bg: 'rgba(0, 137, 123, 0.12)',
        },
        {
          title: this.$t('quick_prompt_creator_title') || 'System Architecture & AI Core',
          desc: this.$t('quick_prompt_creator_desc') || 'Explore the enterprise system design, data pipeline, and AI intelligence suite',
          query: this.$t('quick_prompt_creator_query') || 'Tell me about the system architecture and AI capabilities',
          icon: 'mdi-shield-check',
          color: '#42A5F5',
          bg: 'rgba(66, 165, 245, 0.12)',
        },
      ];
    },
  },
  watch: {
    'ai_prompt.messages.length': {
      handler(newLen, oldLen) {
        if (newLen > (oldLen || 0)) {
          this.scrollToLatestMessage(true);
          setTimeout(() => this.scrollToLatestMessage(true), 50);
          setTimeout(() => this.scrollToLatestMessage(true), 150);
        }
      }
    },
    loading(val) {
      if (val) {
        this.scrollToLatestMessage(true);
        setTimeout(() => this.scrollToLatestMessage(true), 60);
      }
    }
  },
  mounted() {
    this.main.is_aiq_fullscreen = false;
    if (typeof window !== 'undefined') {
      window.scrollTo(0, 0);
      if (document.body) document.body.scrollTop = 0;
      if (document.documentElement) document.documentElement.scrollTop = 0;
    }
    if (typeof document !== 'undefined') {
      document.body.classList.add('aiq-active-route');
      document.documentElement.classList.add('aiq-active-route');
    }
    document.addEventListener('keydown', this.handleGlobalKeydown);
    window.addEventListener('ai:content-rendered', this.onContentRendered);
    if (this.isExpanded) {
      this.main.sidebar_mini = true;
    }
    this.ai_prompt.loadQueryHistory().then(() => {
      this.$nextTick(() => this.scrollToLatestMessage(false));
      setTimeout(() => this.scrollToLatestMessage(false), 150);
    });
  },
  beforeUnmount() {
    if (typeof document !== 'undefined') {
      document.body.classList.remove('aiq-active-route');
      document.documentElement.classList.remove('aiq-active-route');
    }
    document.removeEventListener('keydown', this.handleGlobalKeydown);
    window.removeEventListener('ai:content-rendered', this.onContentRendered);
    if (this.isFullscreen) {
      this.isFullscreen = false;
      this.main.is_aiq_fullscreen = false;
      if (typeof document !== 'undefined') {
        document.body.classList.remove('aiq-body-fullscreen');
        document.documentElement.classList.remove('aiq-html-fullscreen');
      }
    }
  },
  methods: {
    toggleExpand() {
      this.isExpanded = !this.isExpanded;
      localStorage.setItem('aiq_is_expanded', this.isExpanded);
      if (this.isExpanded) {
        this.main.sidebar_mini = true;
      }
      this.showFeedback(
        this.isExpanded
          ? (this.$t('wide_view_enabled') || 'Wide visualization mode enabled')
          : (this.$t('normal_view_enabled') || 'Standard view restored'),
        '#42A5F5',
        this.isExpanded ? 'mdi-arrow-expand-horizontal' : 'mdi-arrow-collapse-horizontal'
      );
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
      });
    },
    toggleFullscreen() {
      this.isFullscreen = !this.isFullscreen;
      this.main.is_aiq_fullscreen = this.isFullscreen;
      if (typeof document !== 'undefined') {
        document.body.classList.toggle('aiq-body-fullscreen', this.isFullscreen);
        document.documentElement.classList.toggle('aiq-html-fullscreen', this.isFullscreen);
      }
      if (this.isFullscreen) {
        this.main.sidebar_mini = true;
      }
      this.showFeedback(
        this.isFullscreen
          ? (this.$t('fullscreen_enabled') || 'Fullscreen visualization mode enabled')
          : (this.$t('fullscreen_disabled') || 'Fullscreen mode exited'),
        '#42A5F5',
        this.isFullscreen ? 'mdi-fullscreen-exit' : 'mdi-fullscreen'
      );
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
      });
    },
    handleGlobalKeydown(e) {
      if (e.key === 'Escape' && this.isFullscreen) {
        this.toggleFullscreen();
      }
    },
    showFeedback(text, color = '#42A5F5', icon = 'mdi-information-outline') {
      this.snackbar = {
        show: true,
        text,
        color,
        icon
      };
    },
    copyAiText(msg, index) {
      const container = document.getElementById(`_ai_message_${index}`);
      const text = container ? (container.innerText || container.textContent) : msg.text;
      if (!text) return;

      const onCopied = () => {
        this.copiedMsgId = msg.id;
        this.showFeedback(this.$t('copied') || 'Copied to clipboard', '#42A5F5', 'mdi-check-circle-outline');
        setTimeout(() => { this.copiedMsgId = null; }, 2000);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text.trim()).then(onCopied).catch(() => {
          this.fallbackCopy(text.trim());
          onCopied();
        });
      } else {
        this.fallbackCopy(text.trim());
        onCopied();
      }
    },
    fallbackCopy(text) {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.opacity = '0';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
    },
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
      const message = this.newMessage || 'Analyze the attached Excel file(s)';
      const excelData = [...this.excelParsedData];
      this.newMessage = "";
      this.excelFiles = [];
      this.excelParsedData = [];
      this.loading = true;

      // Instantly scroll down when user submits the message
      this.scrollToLatestMessage(true);
      setTimeout(() => this.scrollToLatestMessage(true), 50);
      setTimeout(() => this.scrollToLatestMessage(true), 150);

      try {
        if (excelData.length) {
          await this.ai_prompt.sendMessageWithExcel(message, excelData);
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
          this.scrollToLatestMessage(true);
          this.$refs.messageInput?.focus();
        });
        setTimeout(() => this.scrollToLatestMessage(true), 100);
        setTimeout(() => this.scrollToLatestMessage(true), 300);
      }
    },
    deleteMessage(msgId) {
      this.ai_prompt.deleteQueryMessage(msgId);
      this.hoveredMsgId = null;
    },
    reactMessage(msgId, reaction) {
      this.ai_prompt.reactQueryMessage(msgId, reaction);
    },
    scrollToLatestMessage(smooth = true) {
      this.isAtBottom = true;
      this.$nextTick(() => {
        const el = this.$refs.chatBody;
        if (!el) return;

        try {
          el.scrollTo({
            top: el.scrollHeight + 1000,
            behavior: smooth ? 'smooth' : 'auto'
          });
        } catch (e) {
          el.scrollTop = el.scrollHeight;
        }

        const typingEl = document.getElementById('aiq-typing-row');
        const rows = el.querySelectorAll('.aiq-msg-row');
        const target = typingEl || (rows.length ? rows[rows.length - 1] : null);
        if (target && typeof target.scrollIntoView === 'function') {
          try {
            target.scrollIntoView({
              behavior: smooth ? 'smooth' : 'auto',
              block: 'end',
              inline: 'nearest'
            });
          } catch (e) {}
        }
      });
    },
    scrollToBottom(force) {
      this.scrollToLatestMessage(true);
    },
    onContentRendered() {
      if (this.isAtBottom) {
        this.scrollToLatestMessage(false);
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
  flex: 1 1 0%;
  height: 100% !important;
  max-height: 100% !important;
  min-height: 0;
  max-width: 100%;
  border-radius: 20px;
  overflow: hidden !important;
  margin: 0;
  width: 100%;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--aiq-border);
  background: var(--aiq-body-bg);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Fullscreen Immersive Mode */
.aiq-page.aiq-fullscreen {
  position: fixed !important;
  inset: 0 !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  max-width: 100vw !important;
  max-height: 100vh !important;
  border-radius: 0 !important;
  z-index: 999999 !important;
  margin: 0 !important;
  border: none !important;
  box-shadow: none !important;
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
  --ai-card-bg: #1a2332;
  --ai-card-border: rgba(66, 165, 245, 0.25);
  --ai-inner-bg: rgba(255, 255, 255, 0.04);
  --ai-inner-border: rgba(255, 255, 255, 0.08);
  --ai-text-color: #e2e8f0;
  --ai-sub-color: #94a3b8;
  --ai-table-header-bg: rgba(255, 255, 255, 0.06);
  --ai-table-border: rgba(255, 255, 255, 0.08);
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
  --ai-card-bg: #ffffff;
  --ai-card-border: rgba(0, 0, 0, 0.08);
  --ai-inner-bg: #f8fafc;
  --ai-inner-border: rgba(0, 0, 0, 0.06);
  --ai-text-color: #1e293b;
  --ai-sub-color: #64748b;
  --ai-table-header-bg: #f1f5f9;
  --ai-table-border: #e2e8f0;
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

.aiq-creator-pill {
  display: inline-flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(255, 215, 0, 0.4);
  padding: 2px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(4px);
}

.aiq-domain-chips-bar {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 24px 12px 24px;
  overflow-x: auto;
  scrollbar-width: none;
}
.aiq-domain-chips-bar::-webkit-scrollbar {
  display: none;
}

.aiq-domain-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.22);
  color: #ffffff;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}
.aiq-domain-pill:hover {
  background: rgba(255, 255, 255, 0.26);
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.4);
}
.aiq-domain-pill-creator {
  background: rgba(255, 215, 0, 0.18);
  border-color: rgba(255, 215, 0, 0.45);
}
.aiq-domain-pill-creator:hover {
  background: rgba(255, 215, 0, 0.3);
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

.aiq-hero-btn-active {
  background: rgba(255, 255, 255, 0.32) !important;
  border-color: rgba(255, 255, 255, 0.6) !important;
  box-shadow: 0 0 12px rgba(255, 255, 255, 0.35) !important;
}

/* -- Chat Body & Container -- */
.aiq-chat-body {
  flex: 1 1 0% !important;
  min-height: 0 !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 24px 20px 14px 20px;
  background: var(--aiq-body-bg);
  display: flex;
  flex-direction: column;
  scroll-behavior: smooth;
  scrollbar-width: thin !important;
  scrollbar-color: rgba(66, 165, 245, 0.35) transparent !important;
  transition: padding 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.aiq-chat-body:hover {
  scrollbar-color: rgba(66, 165, 245, 0.7) transparent !important;
}

.aiq-chat-body::-webkit-scrollbar {
  width: 6px !important;
}

.aiq-chat-body::-webkit-scrollbar-track {
  background: transparent !important;
  margin: 6px 0 !important;
}

.aiq-chat-body::-webkit-scrollbar-thumb {
  background: rgba(66, 165, 245, 0.35) !important;
  border-radius: 10px !important;
  transition: background 0.2s ease;
}

.aiq-chat-body::-webkit-scrollbar-thumb:hover {
  background: rgba(66, 165, 245, 0.75) !important;
}

.aiq-chat-container {
  width: 100%;
  max-width: 960px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 100%;
  transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.aiq-page.aiq-expanded .aiq-chat-container,
.aiq-page.aiq-fullscreen .aiq-chat-container {
  max-width: min(1820px, 98%);
  width: 100%;
}

.aiq-page.aiq-expanded .aiq-chat-body,
.aiq-page.aiq-fullscreen .aiq-chat-body {
  padding: 24px 32px;
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
  display: flex;
  flex-direction: column !important;
  align-items: flex-start !important;
  width: 100%;
}

.aiq-ai-msg-block {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  width: 100%;
  position: relative;
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

/* -- AI Footer (Cleanly placed BELLOW the message) -- */
.aiq-ai-footer {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 4px 12px 10px 16px;
  margin-top: 2px;
  opacity: 0.85;
  transition: opacity 0.2s ease;
  width: fit-content;
}

.aiq-ai-footer:hover,
.aiq-ai-msg-block:hover .aiq-ai-footer {
  opacity: 1;
}

.aiq-ai-footer-actions {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.aiq-ai-time {
  font-size: 11px;
  color: var(--aiq-text-muted);
  font-weight: 500;
  letter-spacing: 0.2px;
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
  flex: 0 0 auto !important;
  flex-shrink: 0;
  padding: 8px 16px 12px 16px !important;
  background: transparent;
  box-sizing: border-box !important;
  z-index: 10;
}

.aiq-input-dock-container {
  width: 100%;
  max-width: 960px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.aiq-page.aiq-expanded .aiq-input-dock-container,
.aiq-page.aiq-fullscreen .aiq-input-dock-container {
  max-width: min(1820px, 98%);
  width: 100%;
}

.aiq-input-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--aiq-dock-bg);
  border: 1.5px solid var(--aiq-dock-border);
  border-radius: 28px;
  padding: 4px 6px 4px 16px;
  box-sizing: border-box;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  transition: all 0.25s ease;
}

.aiq-input-wrapper:hover {
  border-color: rgba(66, 165, 245, 0.55);
}

.aiq-input-wrapper:focus-within {
  border-color: #42A5F5;
  box-shadow: 0 0 0 3px rgba(66, 165, 245, 0.2), 0 8px 24px rgba(66, 165, 245, 0.12);
}

.aiq-input-icon {
  color: #42A5F5 !important;
}

.aiq-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: var(--aiq-text);
  font-size: 13.5px;
  font-family: inherit;
  padding: 8px 0;
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
  background: #42A5F5 !important;
  color: #fff !important;
  box-shadow: 0 4px 14px rgba(66, 165, 245, 0.45);
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
