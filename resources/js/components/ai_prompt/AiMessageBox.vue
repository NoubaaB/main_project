<template>
  <div class="aimb-wrapper" :class="{ 'is-loading': isLoading }">
    <!-- AI avatar + header -->
    <div class="aimb-header">
      <div class="aimb-avatar-glow"></div>
      <div class="aimb-avatar">
        <v-icon size="16" color="white" class="aimb-robot-icon">mdi-robot-happy-outline</v-icon>
      </div>
      <div class="aimb-meta">
        <span class="aimb-name">{{ $t('app_name_ai') }}<v-icon size="12" class="ms-1" color="success">mdi-check-decagram</v-icon></span>
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

    <!-- Export Excel button (shown when message contains table data) -->
    <div v-if="!isLoading && hasTable" class="aimb-export-bar">
      <button class="aimb-export-btn" @click="exportToExcel" :title="$t('export_excel')">
        <v-icon size="14" class="me-1">mdi-file-excel-outline</v-icon>
        {{ $t('export_excel') }}
      </button>
    </div>
  </div>
</template>

<script>
import { loadExternalContent } from '@/helpers/dynamic-loader';
import { useAIPrompt } from "@/stores/AIPrompt";
import * as XLSX from 'xlsx';

export default {
  props: ['msg', 'index'],
  data() {
    return {
      isLoading: true,
      hasTable: false,
    };
  },
  computed: {
    ai_prompt() {
      return useAIPrompt();
    },
    formatTime() {
      const d = new Date();
      return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    },
  },
  mounted() {
    this.$nextTick(() => {
      this.isLoading = false;
      this.$nextTick(() => {
        loadExternalContent(this.msg.text, `_ai_message_${this.index}`);

        // Check if rendered content contains a table
        const container = this.$refs.contentBox;
        if (container) {
          container.addEventListener('click', this.handleContentClick);
          this.$nextTick(() => {
            this.hasTable = container.querySelector('table') !== null;
          });
        }
      });
    });
  },
  beforeUnmount() {
    const container = this.$refs.contentBox;
    if (container) {
      container.removeEventListener('click', this.handleContentClick);
    }
  },
  methods: {
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
  display: inline-block;
  background: rgba(var(--v-theme-surface), 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 24px;
  border-top-left-radius: 6px;
  padding: 20px 24px;
  margin: 12px 0;
  max-width: 85%;
  min-width: 250px;
  width: fit-content;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 
    0 10px 30px -10px rgba(0, 0, 0, 0.08), 
    0 4px 12px -5px rgba(0, 0, 0, 0.04),
    inset 0 1px 1px rgba(255, 255, 255, 0.15);
  z-index: 1;
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

.aimb-wrapper.is-loading {
  background: linear-gradient(145deg, rgba(var(--v-theme-surface), 0.7), rgba(var(--v-theme-surface), 0.4));
}

/* Header & Avatar */
.aimb-header {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 18px;
}

.aimb-avatar-glow {
  position: absolute;
  top: 50%;
  left: 20px;
  width: 44px;
  height: 44px;
  background: conic-gradient(from 0deg, #1976D2, #00C853, #9C27B0, #1976D2);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  filter: blur(10px);
  opacity: 0.6;
  z-index: 0;
  animation: aimb-spin 6s linear infinite;
}

.aimb-avatar {
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1A237E, #1976D2);
  box-shadow: 0 4px 15px rgba(25, 118, 210, 0.4), inset 0 2px 4px rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 1;
}

.aimb-robot-icon {
  animation: aimb-float 3s ease-in-out infinite;
}

/* Metadata */
.aimb-meta {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.aimb-name {
  font-size: 14.5px;
  font-weight: 800;
  background: linear-gradient(90deg, #1565C0, #0277BD);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
}

.aimb-time {
  font-size: 11px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  font-weight: 600;
  letter-spacing: 0.6px;
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

/* Beautiful Data Tables */
.aimb-content :deep(table) {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  border-radius: 12px;
  overflow: hidden;
  font-size: 13.5px;
  margin: 16px 0;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  background: rgba(var(--v-theme-surface), 0.4);
}

.aimb-content :deep(th) {
  padding: 14px 18px;
  text-align: left;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
  background: rgba(var(--v-theme-on-surface), 0.05);
  color: rgba(var(--v-theme-on-surface), 0.8);
  border-bottom: 2px solid rgba(var(--v-theme-on-surface), 0.1);
}

.aimb-content :deep(td) {
  padding: 12px 18px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.04);
  transition: background 0.2s;
}

.aimb-content :deep(tr:last-child td) {
  border-bottom: none;
}

.aimb-content :deep(tr:hover td) {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

/* ApexCharts container adjustments */
.aimb-content :deep(.apexcharts-canvas) {
  margin: 16px auto;
  border-radius: 16px;
  padding: 8px;
  background: rgba(var(--v-theme-surface), 0.5);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
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

@keyframes aimb-spin {
  0% { transform: translate(-50%, -50%) rotate(0deg); }
  100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Export Excel Bar */
.aimb-export-bar {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.06);
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
</style>