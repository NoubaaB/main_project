<template>
  <div
    class="aimb-wrapper"
    :class="[
      main.is_dark ? 'aimb-dark' : 'aimb-light',
      { 'is-loading': isLoading, 'aimb-expanded': isCardExpanded }
    ]"
  >
    <!-- AI avatar + header -->
    <div class="aimb-header">
      <div class="aimb-avatar-container">
        <div class="aimb-avatar-glow"></div>
        <div class="aimb-avatar-ring"></div>
        <div class="aimb-avatar">
          <v-icon size="18" color="white" class="aimb-robot-icon">mdi-robot-happy-outline</v-icon>
        </div>
        <span class="aimb-online-badge"></span>
      </div>
      <div class="aimb-meta">
        <div class="aimb-name-row">
          <span class="aimb-name">{{ $t('app_name_ai') }}</span>
          <v-icon size="14" class="ms-1 aimb-verified-badge">mdi-check-decagram</v-icon>
          <span class="aimb-role-badge">{{ $t('assistant') }}</span>
          <span class="aimb-creator-tag" :title="$t('ai_creator_title')">
            <v-icon size="12" color="#42A5F5" class="me-1">mdi-shield-check</v-icon>
            {{ $t('ai_creator_badge') }}
          </span>
        </div>
        <span class="aimb-time">{{ formatTime }}</span>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="isLoading" class="aimb-loading">
      <div class="aimb-skeleton-card">
        <div class="aimb-skeleton-line" style="width: 85%"></div>
        <div class="aimb-skeleton-line" style="width: 60%"></div>
        <div class="aimb-skeleton-line" style="width: 72%"></div>
      </div>
    </div>

    <!-- Rendered AI content -->
    <div
      v-else
      :id="`_ai_message_${index}`"
      class="aimb-content"
      ref="contentBox"
    ></div>

    <!-- Action bar (Copy + Export Excel + Expand Visualization) -->
    <div v-if="!isLoading" class="aimb-export-bar">
      <button class="aimb-export-btn" @click="copyResponse" :title="$t('copy_response') || $t('copy')">
        <v-icon size="14" class="me-1">{{ copied ? 'mdi-check' : 'mdi-content-copy' }}</v-icon>
        {{ copied ? ($t('copied') || 'Copied') : ($t('copy') || 'Copy') }}
      </button>
      <button v-if="hasTable" class="aimb-export-btn" @click="exportToExcel" :title="$t('export_excel')">
        <v-icon size="14" class="me-1">mdi-file-excel-outline</v-icon>
        {{ $t('export_excel') }}
      </button>
      <button
        class="aimb-export-btn aimb-expand-btn"
        @click="toggleCardExpand"
        :title="isCardExpanded ? ($t('collapse_message') || 'Collapse') : ($t('expand_message') || 'Expand')"
      >
        <v-icon size="14" class="me-1">{{ isCardExpanded ? 'mdi-arrow-collapse-horizontal' : 'mdi-arrow-expand-horizontal' }}</v-icon>
        {{ isCardExpanded ? ($t('collapse') || 'Collapse') : ($t('expand_view') || 'Expand') }}
      </button>
    </div>
  </div>
</template>

<script>
import { loadExternalContent } from '@/helpers/dynamic-loader';
import { useAIPrompt } from "@/stores/AIPrompt";
import { useMain } from "@/stores/Main";
import * as XLSX from 'xlsx';

export default {
  props: ['msg', 'index', 'isExpandedMode'],
  data() {
    return {
      isLoading: true,
      hasTable: false,
      hasVisualization: false,
      copied: false,
      localExpanded: false,
    };
  },
  computed: {
    main() {
      return useMain();
    },
    ai_prompt() {
      return useAIPrompt();
    },
    isCardExpanded() {
      return this.localExpanded || Boolean(this.isExpandedMode);
    },
    formatTime() {
      const d = new Date();
      return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    },
  },
  watch: {
    'main.is_dark': {
      handler(isDark) {
        this.updateChartsTheme(isDark);
      }
    },
    isExpandedMode: {
      handler() {
        this.$nextTick(() => {
          this.updateChartsTheme(this.main.is_dark);
        });
      }
    }
  },
  mounted() {
    window.addEventListener('ai:content-rendered', this.onContentRendered);
    this.$nextTick(() => {
      this.isLoading = false;
      this.$nextTick(() => {
        loadExternalContent(this.msg.text, `_ai_message_${this.index}`);

        // Check if rendered content contains a table or visualization
        const container = this.$refs.contentBox;
        if (container) {
          container.addEventListener('click', this.handleContentClick);
          this.$nextTick(() => {
            this.hasTable = container.querySelector('table') !== null;
            this.hasVisualization = container.querySelector('.apexcharts-canvas, svg, canvas, .ai-analytics-card') !== null;
            this.updateChartsTheme(this.main.is_dark);
          });
        }
      });
    });
  },
  beforeUnmount() {
    window.removeEventListener('ai:content-rendered', this.onContentRendered);
    const container = this.$refs.contentBox;
    if (container) {
      container.removeEventListener('click', this.handleContentClick);
    }
  },
  methods: {
    toggleCardExpand() {
      this.localExpanded = !this.localExpanded;
      this.$nextTick(() => {
        window.dispatchEvent(new Event('resize'));
        this.updateChartsTheme(this.main.is_dark);
      });
    },
    onContentRendered(e) {
      if (e.detail?.containerId === `_ai_message_${this.index}`) {
        this.updateChartsTheme(this.main.is_dark);
      }
    },
    updateChartsTheme(isDark) {
      this.$nextTick(() => {
        const container = this.$refs.contentBox;
        if (!container) return;
        const mode = isDark ? 'dark' : 'light';
        const axisColor = isDark ? '#94a3b8' : '#64748b';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

        const chartElements = container.querySelectorAll('[id^="aichart_"]');
        chartElements.forEach(el => {
          const id = el.id;
          if (window.__ai_charts && window.__ai_charts[id] && typeof window.__ai_charts[id].updateOptions === 'function') {
            try {
              window.__ai_charts[id].updateOptions({
                theme: { mode: mode },
                grid: { borderColor: gridColor },
                xaxis: { labels: { style: { colors: axisColor } } },
                yaxis: { labels: { style: { colors: axisColor } } },
                tooltip: { theme: mode },
              });
            } catch (err) {}
          } else if (window.ApexCharts) {
            try {
              window.ApexCharts.exec(id, 'updateOptions', {
                theme: { mode: mode },
                grid: { borderColor: gridColor },
                xaxis: { labels: { style: { colors: axisColor } } },
                yaxis: { labels: { style: { colors: axisColor } } },
                tooltip: { theme: mode },
              });
            } catch (err) {}
          }
        });
      });
    },
    async copyResponse() {
      const container = this.$refs.contentBox;
      if (!container) return;
      const text = container.innerText || container.textContent || '';
      try {
        await navigator.clipboard.writeText(text.trim());
        this.copied = true;
        setTimeout(() => { this.copied = false; }, 2000);
      } catch (e) {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text.trim();
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        this.copied = true;
        setTimeout(() => { this.copied = false; }, 2000);
      }
    },
    exportToExcel() {
      const container = this.$refs.contentBox;
      if (!container) return;

      const tables = container.querySelectorAll('table');
      if (!tables.length) return;

      const wb = XLSX.utils.book_new();

      tables.forEach((table, idx) => {
        const ws = XLSX.utils.table_to_sheet(table);
        const sheetName = tables.length === 1 ? 'Data' : `Sheet${idx + 1}`;
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
      });

      const now = new Date();
      const timestamp = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}_${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}`;
      XLSX.writeFile(wb, `ai_export_${timestamp}.xlsx`);
    },
    handleContentClick(e) {
      // Handle .ai-nav-btn navigation buttons
      const navBtn = e.target.closest('.ai-nav-btn');
      if (navBtn) {
        const route = navBtn.getAttribute('data-route');
        if (route) {
          e.preventDefault();
          e.stopPropagation();
          this.$router.push(route);
          return;
        }
      }

      // Handle .ai-confirm-btn (confirm action plan)
      const confirmBtn = e.target.closest('.ai-confirm-btn');
      if (confirmBtn) {
        e.preventDefault();
        e.stopPropagation();
        this.$emit('confirm-action');
        return;
      }

      // Handle .ai-cancel-btn (cancel action plan)
      const cancelBtn = e.target.closest('.ai-cancel-btn');
      if (cancelBtn) {
        e.preventDefault();
        e.stopPropagation();
        this.$emit('cancel-action');
        return;
      }

      // Handle standard <a> links
      const anchor = e.target.closest('a');
      if (!anchor) return;
      const href = anchor.getAttribute('href');
      if (!href) return;
      e.preventDefault();
      e.stopPropagation();
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
    }
  }
};
</script>

<style scoped>
/* Main Wrapper - Ultra Premium Look */
.aimb-wrapper {
  position: relative;
  display: block;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 24px;
  border-top-left-radius: 6px;
  padding: 20px 24px;
  margin: 6px 0;
  max-width: 100%;
  min-width: 260px;
  width: 100%;
  box-sizing: border-box;
  transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease, padding 0.3s ease;
  z-index: 1;
}

.aimb-wrapper.aimb-expanded {
  padding: 24px 30px;
}

.aimb-wrapper.aimb-dark {
  background: rgba(22, 27, 38, 0.94);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 
    0 10px 30px -10px rgba(0, 0, 0, 0.55), 
    0 4px 12px -5px rgba(0, 0, 0, 0.35),
    inset 0 1px 1px rgba(255, 255, 255, 0.08);
  --ai-card-bg: #1c212c;
  --ai-card-border: rgba(66, 165, 245, 0.25);
  --ai-inner-bg: rgba(255, 255, 255, 0.04);
  --ai-inner-border: rgba(255, 255, 255, 0.08);
  --ai-text-color: #e2e8f0;
  --ai-sub-color: #94a3b8;
  --ai-table-header-bg: rgba(255, 255, 255, 0.06);
  --ai-table-border: rgba(255, 255, 255, 0.08);
  --ai-code-bg: rgba(255, 255, 255, 0.06);
}

.aimb-wrapper.aimb-light {
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 
    0 10px 30px -10px rgba(0, 0, 0, 0.08), 
    0 4px 12px -5px rgba(0, 0, 0, 0.04),
    inset 0 1px 1px rgba(255, 255, 255, 0.85);
  --ai-card-bg: #ffffff;
  --ai-card-border: rgba(0, 0, 0, 0.08);
  --ai-inner-bg: #f8fafc;
  --ai-inner-border: rgba(0, 0, 0, 0.06);
  --ai-text-color: #1e293b;
  --ai-sub-color: #64748b;
  --ai-table-header-bg: #f1f5f9;
  --ai-table-border: #e2e8f0;
  --ai-code-bg: rgba(0, 0, 0, 0.04);
}

/* Subtle Holographic Animated Border */
.aimb-wrapper::before {
  content: "";
  position: absolute;
  inset: -1px;
  border-radius: inherit;
  padding: 1px;
  background: linear-gradient(135deg, 
    rgba(25, 118, 210, 0.4), 
    rgba(0, 200, 83, 0.2), 
    rgba(25, 118, 210, 0.1), 
    rgba(156, 39, 176, 0.3)
  );
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  background-size: 300% 300%;
  animation: aimb-holographic 8s ease infinite;
  z-index: -1;
  pointer-events: none;
}

.aimb-wrapper:hover {
  transform: translateY(-2px);
  box-shadow: 
    0 15px 35px -10px rgba(0, 0, 0, 0.12), 
    0 8px 16px -5px rgba(0, 0, 0, 0.06),
    inset 0 1px 1px rgba(255, 255, 255, 0.2);
}

.aimb-wrapper.aimb-dark.is-loading {
  background: linear-gradient(145deg, rgba(30, 35, 48, 0.8), rgba(20, 24, 34, 0.6));
}

.aimb-wrapper.aimb-light.is-loading {
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.9), rgba(245, 247, 250, 0.7));
}

/* Header & Avatar */
.aimb-header {
  position: relative;
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 18px;
}

.aimb-avatar-container {
  position: relative;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.aimb-avatar-glow {
  position: absolute;
  inset: -2px;
  background: conic-gradient(from 0deg, #1565C0, #00C853, #9C27B0, #42A5F5, #1565C0);
  border-radius: 50%;
  filter: blur(8px);
  opacity: 0.65;
  z-index: 0;
  animation: aimb-glow-pulse-spin 6s linear infinite;
}

.aimb-avatar-ring {
  position: absolute;
  inset: -1px;
  border-radius: 50%;
  padding: 2px;
  background: linear-gradient(135deg, #1565C0, #42A5F5, #00C853, #9C27B0);
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: aimb-simple-spin 8s linear infinite;
  z-index: 1;
}

.aimb-avatar {
  position: relative;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0d47a1 0%, #1976D2 50%, #42A5F5 100%);
  box-shadow: 
    0 4px 12px rgba(21, 101, 192, 0.4), 
    inset 0 2px 4px rgba(255, 255, 255, 0.3),
    inset 0 -2px 4px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
  transition: all 0.3s ease;
}

.aimb-avatar-container:hover .aimb-avatar {
  transform: scale(1.05);
  box-shadow: 
    0 6px 16px rgba(21, 101, 192, 0.6), 
    inset 0 2px 4px rgba(255, 255, 255, 0.4);
}

.aimb-robot-icon {
  animation: aimb-float 3s ease-in-out infinite;
}

@keyframes aimb-glow-pulse-spin {
  0% {
    transform: rotate(0deg) scale(0.95);
    filter: blur(8px);
    opacity: 0.5;
  }
  50% {
    transform: rotate(180deg) scale(1.05);
    filter: blur(10px);
    opacity: 0.75;
  }
  100% {
    transform: rotate(360deg) scale(0.95);
    filter: blur(8px);
    opacity: 0.5;
  }
}

@keyframes aimb-simple-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.aimb-online-badge {
  position: absolute;
  bottom: 0px;
  right: 0px;
  width: 10px;
  height: 10px;
  background: #00E676;
  border: 2px solid rgba(var(--v-theme-surface), 0.9);
  border-radius: 50%;
  z-index: 3;
  box-shadow: 0 0 8px #00E676;
  animation: aimb-online-blink 2s ease-in-out infinite;
}

@keyframes aimb-online-blink {
  0%, 100% {
    opacity: 1;
    box-shadow: 0 0 6px #00E676;
  }
  50% {
    opacity: 0.6;
    box-shadow: 0 0 2px #00E676;
  }
}

/* Metadata */
.aimb-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.aimb-name-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.aimb-name {
  font-size: 15px;
  font-weight: 800;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 50%, #00B0FF 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.4px;
}

.aimb-verified-badge {
  color: #2196F3 !important;
  filter: drop-shadow(0 0 2px rgba(33, 150, 243, 0.4));
  animation: aimb-pulse-verified 4s ease-in-out infinite;
}

@keyframes aimb-pulse-verified {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.aimb-role-badge {
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 1px 6px;
  border-radius: 6px;
  background: rgba(33, 150, 243, 0.08);
  color: #2196F3;
  border: 1.2px solid rgba(33, 150, 243, 0.2);
  display: inline-flex;
  align-items: center;
  height: fit-content;
  line-height: 1;
}

.aimb-creator-tag {
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 1px 8px;
  border-radius: 6px;
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.12), rgba(66, 165, 245, 0.15));
  color: #42A5F5;
  border: 1px solid rgba(255, 193, 7, 0.35);
  display: inline-flex;
  align-items: center;
  height: fit-content;
  line-height: 1;
}

.aimb-time {
  font-size: 10.5px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Premium Content Rendering */
.aimb-content {
  width: 100%;
  overflow-x: auto;
  font-size: 14.5px;
  line-height: 1.8;
  color: rgba(var(--v-theme-on-surface), 0.85);
  letter-spacing: 0.2px;
}

.aimb-content :deep(p) {
  margin-bottom: 14px;
}

.aimb-content :deep(strong) {
  font-weight: 700;
  color: rgba(var(--v-theme-on-surface), 0.95);
}

.aimb-content :deep(ul), .aimb-content :deep(ol) {
  margin-bottom: 14px;
  padding-left: 24px;
}

.aimb-content :deep(li) {
  margin-bottom: 6px;
}

.aimb-content :deep(pre), .aimb-content :deep(code) {
  background: rgba(var(--v-theme-on-surface), 0.05);
  border-radius: 6px;
  padding: 2px 6px;
  font-family: 'Fira Code', 'Courier New', monospace;
  font-size: 13px;
  color: #D81B60;
}

.aimb-content :deep(pre) {
  padding: 12px;
  overflow-x: auto;
  color: rgba(var(--v-theme-on-surface), 0.9);
  margin-bottom: 14px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
}

.aimb-content :deep(pre code) {
  background: transparent;
  color: inherit;
  padding: 0;
}

/* ============================================================
   Ultra-Responsive Theme Synchronization for AI Responses
   Instantly adapts on dark/light switch without page reload
   ============================================================ */

/* Content Typography */
.aimb-dark .aimb-content {
  color: #e2e8f0;
}
.aimb-light .aimb-content {
  color: #1e293b;
}

.aimb-dark .aimb-content :deep(p),
.aimb-dark .aimb-content :deep(li),
.aimb-dark .aimb-content :deep(span:not([class*="badge"]):not([class*="pill"]):not([class*="tag"])) {
  color: #e2e8f0;
}
.aimb-dark .aimb-content :deep(strong) {
  color: #ffffff !important;
}

.aimb-light .aimb-content :deep(p),
.aimb-light .aimb-content :deep(li),
.aimb-light .aimb-content :deep(span:not([class*="badge"]):not([class*="pill"]):not([class*="tag"])) {
  color: #1e293b;
}
.aimb-light .aimb-content :deep(strong) {
  color: #0f172a !important;
}

/* Headings */
.aimb-dark .aimb-content :deep(h1),
.aimb-dark .aimb-content :deep(h2),
.aimb-dark .aimb-content :deep(h3),
.aimb-dark .aimb-content :deep(h4) {
  color: #f1f5f9 !important;
}
.aimb-light .aimb-content :deep(h1),
.aimb-light .aimb-content :deep(h2),
.aimb-light .aimb-content :deep(h3),
.aimb-light .aimb-content :deep(h4) {
  color: #0f172a !important;
}

/* Vuetify components mounted inside message */
.aimb-dark .aimb-content :deep(.v-card) {
  background: #1c212c !important;
  color: #e2e8f0 !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
}
.aimb-dark .aimb-content :deep(.v-container) {
  background: transparent !important;
  color: #e2e8f0 !important;
}
.aimb-dark .aimb-content :deep(.v-table),
.aimb-dark .aimb-content :deep(.v-data-table) {
  background: transparent !important;
  color: #e2e8f0 !important;
}

.aimb-light .aimb-content :deep(.v-card) {
  background: #ffffff !important;
  color: #1e293b !important;
  border: 1px solid rgba(0, 0, 0, 0.06) !important;
}
.aimb-light .aimb-content :deep(.v-container) {
  background: transparent !important;
  color: #1e293b !important;
}
.aimb-light .aimb-content :deep(.v-table),
.aimb-light .aimb-content :deep(.v-data-table) {
  background: #ffffff !important;
  color: #1e293b !important;
}

/* Analytics Cards */
.aimb-dark .aimb-content :deep(.ai-analytics-card) {
  background: #1c212c !important;
  color: #e2e8f0 !important;
  border-color: rgba(66, 165, 245, 0.25) !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
  transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
.aimb-dark .aimb-content :deep(.ai-analytics-inner) {
  background: rgba(255, 255, 255, 0.04) !important;
  color: #e2e8f0 !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
  transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
.aimb-dark .aimb-content :deep(.ai-analytics-text) {
  color: #e2e8f0 !important;
}
.aimb-dark .aimb-content :deep(.ai-analytics-subtext) {
  color: #94a3b8 !important;
}

.aimb-light .aimb-content :deep(.ai-analytics-card) {
  background: #ffffff !important;
  color: #1e293b !important;
  border-color: rgba(0, 0, 0, 0.08) !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06) !important;
  transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
.aimb-light .aimb-content :deep(.ai-analytics-inner) {
  background: #f8fafc !important;
  color: #1e293b !important;
  border-color: rgba(0, 0, 0, 0.06) !important;
  transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
.aimb-light .aimb-content :deep(.ai-analytics-text) {
  color: #1e293b !important;
}
.aimb-light .aimb-content :deep(.ai-analytics-subtext) {
  color: #64748b !important;
}

/* Tables in Dark & Light */
.aimb-dark .aimb-content :deep(.ai-analytics-table),
.aimb-dark .aimb-content :deep(table) {
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(20, 24, 33, 0.6) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2) !important;
}
.aimb-dark .aimb-content :deep(.ai-analytics-table th),
.aimb-dark .aimb-content :deep(th) {
  padding: 12px 16px;
  text-align: left;
  font-weight: 700;
  font-size: 11.5px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  background: rgba(255, 255, 255, 0.06) !important;
  color: #94a3b8 !important;
  border-bottom: 2px solid rgba(255, 255, 255, 0.08) !important;
}
.aimb-dark .aimb-content :deep(.ai-analytics-table td),
.aimb-dark .aimb-content :deep(td) {
  padding: 11px 16px;
  color: #e2e8f0 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
}
.aimb-dark .aimb-content :deep(.ai-analytics-table tr:hover td),
.aimb-dark .aimb-content :deep(tr:hover td) {
  background: rgba(255, 255, 255, 0.04) !important;
}

.aimb-light .aimb-content :deep(.ai-analytics-table),
.aimb-light .aimb-content :deep(table) {
  width: 100% !important;
  border-collapse: separate !important;
  border-spacing: 0;
  border-radius: 12px;
  overflow: hidden;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06) !important;
}
.aimb-light .aimb-content :deep(.ai-analytics-table th),
.aimb-light .aimb-content :deep(th) {
  padding: 12px 16px;
  text-align: left;
  font-weight: 700;
  font-size: 11.5px;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  background: #f1f5f9 !important;
  color: #64748b !important;
  border-bottom: 2px solid #e2e8f0 !important;
}
.aimb-light .aimb-content :deep(.ai-analytics-table td),
.aimb-light .aimb-content :deep(td) {
  padding: 11px 16px;
  color: #1e293b !important;
  border-bottom: 1px solid #f1f5f9 !important;
}
.aimb-light .aimb-content :deep(.ai-analytics-table tr:hover td),
.aimb-light .aimb-content :deep(tr:hover td) {
  background: #f8fafc !important;
}

/* Action & Confirmation Cards */
.aimb-dark .aimb-content :deep(.ai-confirm-card),
.aimb-dark .aimb-content :deep(.ai-cancel-card),
.aimb-dark .aimb-content :deep(.ai-excel-display-card),
.aimb-dark .aimb-content :deep(.ai-action-success-card),
.aimb-dark .aimb-content :deep(.ai-action-error-card) {
  background: #1c212c !important;
  color: #e2e8f0 !important;
  transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

.aimb-light .aimb-content :deep(.ai-confirm-card),
.aimb-light .aimb-content :deep(.ai-cancel-card),
.aimb-light .aimb-content :deep(.ai-excel-display-card),
.aimb-light .aimb-content :deep(.ai-action-success-card),
.aimb-light .aimb-content :deep(.ai-action-error-card) {
  background: #f8fafc !important;
  color: #1e293b !important;
  transition: background 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

/* Legacy & hardcoded inline styles overrides in dark mode */
.aimb-dark .aimb-content :deep(div[style*="background: #ffffff"]),
.aimb-dark .aimb-content :deep(div[style*="background:#ffffff"]),
.aimb-dark .aimb-content :deep(div[style*="background: #fff"]),
.aimb-dark .aimb-content :deep(div[style*="background:#fff"]),
.aimb-dark .aimb-content :deep(div[style*="background: white"]),
.aimb-dark .aimb-content :deep(div[style*="background: #f8fafc"]),
.aimb-dark .aimb-content :deep(div[style*="background:#f8fafc"]),
.aimb-dark .aimb-content :deep(div[style*="background: #f1f5f9"]),
.aimb-dark .aimb-content :deep(div[style*="background:#f1f5f9"]),
.aimb-dark .aimb-content :deep(div[style*="background: rgb(255, 255, 255)"]),
.aimb-dark .aimb-content :deep(div[style*="background:#ECEFF1"]),
.aimb-dark .aimb-content :deep(div[style*="background: #ECEFF1"]) {
  background: #1c212c !important;
  color: #e2e8f0 !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.aimb-dark .aimb-content :deep([style*="color: #1e293b"]),
.aimb-dark .aimb-content :deep([style*="color:#1e293b"]),
.aimb-dark .aimb-content :deep([style*="color: #333"]),
.aimb-dark .aimb-content :deep([style*="color:#333"]),
.aimb-dark .aimb-content :deep([style*="color: #455A64"]),
.aimb-dark .aimb-content :deep([style*="color:#455A64"]),
.aimb-dark .aimb-content :deep([style*="color: #555"]),
.aimb-dark .aimb-content :deep([style*="color:#555"]) {
  color: #e2e8f0 !important;
}

.aimb-dark .aimb-content :deep([style*="color: #64748b"]),
.aimb-dark .aimb-content :deep([style*="color:#64748b"]) {
  color: #94a3b8 !important;
}

/* Legacy & hardcoded inline styles overrides in light mode */
.aimb-light .aimb-content :deep(div[style*="background: #1a2332"]),
.aimb-light .aimb-content :deep(div[style*="background:#1a2332"]),
.aimb-light .aimb-content :deep(div[style*="background: #1e2634"]),
.aimb-light .aimb-content :deep(div[style*="background:#1e2634"]),
.aimb-light .aimb-content :deep(div[style*="background: #141c28"]),
.aimb-light .aimb-content :deep(div[style*="background:#141c28"]),
.aimb-light .aimb-content :deep(div[style*="background: #1c212c"]),
.aimb-light .aimb-content :deep(div[style*="background:#1c212c"]),
.aimb-light .aimb-content :deep(div[style*="background: #1e1e1e"]),
.aimb-light .aimb-content :deep(div[style*="background:#1e1e1e"]),
.aimb-light .aimb-content :deep(div[style*="background: #14171f"]),
.aimb-light .aimb-content :deep(div[style*="background:#14171f"]),
.aimb-light .aimb-content :deep(div[style*="background: #121212"]),
.aimb-light .aimb-content :deep(div[style*="background:#121212"]) {
  background: #ffffff !important;
  color: #1e293b !important;
  border-color: rgba(0, 0, 0, 0.08) !important;
}

.aimb-light .aimb-content :deep([style*="color: #e2e8f0"]),
.aimb-light .aimb-content :deep([style*="color:#e2e8f0"]),
.aimb-light .aimb-content :deep([style*="color: #ffffff"]),
.aimb-light .aimb-content :deep([style*="color:#ffffff"]),
.aimb-light .aimb-content :deep([style*="color: #fff"]),
.aimb-light .aimb-content :deep([style*="color:#fff"]),
.aimb-light .aimb-content :deep([style*="color: rgba(255, 255, 255"]),
.aimb-light .aimb-content :deep([style*="color: #E2E8F0"]) {
  color: #1e293b !important;
}

.aimb-light .aimb-content :deep([style*="color: #94a3b8"]),
.aimb-light .aimb-content :deep([style*="color:#94a3b8"]) {
  color: #64748b !important;
}

/* ApexCharts container adjustments */
.aimb-dark .aimb-content :deep(.apexcharts-canvas) {
  margin: 16px auto;
  border-radius: 16px;
  padding: 8px;
  background: rgba(28, 33, 44, 0.7) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  filter: drop-shadow(0 8px 24px rgba(0,0,0,0.2));
}

.aimb-light .aimb-content :deep(.apexcharts-canvas) {
  margin: 16px auto;
  border-radius: 16px;
  padding: 8px;
  background: #ffffff !important;
  border: 1px solid rgba(0, 0, 0, 0.06) !important;
  filter: drop-shadow(0 8px 24px rgba(0,0,0,0.06));
}

/* Advanced Shimmer Skeleton */
.aimb-skeleton-card {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 8px 0 12px;
}

.aimb-skeleton-line {
  height: 16px;
  border-radius: 8px;
  background: linear-gradient(90deg,
    rgba(var(--v-theme-on-surface), 0.04) 25%,
    rgba(var(--v-theme-on-surface), 0.1) 50%,
    rgba(var(--v-theme-on-surface), 0.04) 75%
  );
  background-size: 200% 100%;
  animation: aimb-shimmer 1.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

/* Animations */
@keyframes aimb-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@keyframes aimb-pulse {
  0% { transform: translate(-50%, -50%) scale(1); opacity: 0.4; }
  100% { transform: translate(-50%, -50%) scale(1.15); opacity: 0.8; }
}

@keyframes aimb-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}

@keyframes aimb-holographic {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* aimb-spin keyframe retired */

/* Export Excel & Expand Bar */
.aimb-export-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 14px;
  padding-top: 10px;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.aimb-export-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid rgba(76, 175, 80, 0.3);
  background: rgba(76, 175, 80, 0.08);
  color: #4CAF50;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s ease;
  letter-spacing: 0.3px;
}

.aimb-export-btn:hover {
  background: rgba(76, 175, 80, 0.15);
  border-color: rgba(76, 175, 80, 0.5);
  box-shadow: 0 2px 10px rgba(76, 175, 80, 0.2);
  transform: translateY(-1px);
}

.aimb-expand-btn {
  border: 1px solid rgba(66, 165, 245, 0.35) !important;
  background: rgba(66, 165, 245, 0.08) !important;
  color: #42A5F5 !important;
}

.aimb-expand-btn:hover {
  background: rgba(66, 165, 245, 0.18) !important;
  border-color: rgba(66, 165, 245, 0.6) !important;
  box-shadow: 0 2px 10px rgba(66, 165, 245, 0.25) !important;
  color: #1E88E5 !important;
}

/* Visualization responsiveness */
.aimb-content :deep(.apexcharts-canvas) {
  width: 100% !important;
  max-width: 100% !important;
}

.aimb-content :deep(.apexcharts-canvas svg) {
  width: 100% !important;
}

.aimb-content :deep(table) {
  width: 100% !important;
}

.aimb-content :deep(.ai-analytics-card) {
  width: 100% !important;
}
</style>