<template>
  <div
    ref="containerRef"
    class="segmented-pill-container"
    :class="[
      `segmented-pill--${size}`,
      {
        'has-indicator': isIndicatorReady,
        'segmented-pill--block': block
      }
    ]"
    :style="containerStyle"
    role="tablist"
  >
    <!-- Sliding active indicator pill with spring animation -->
    <div
      v-show="isIndicatorReady"
      class="segmented-pill-indicator"
      :style="indicatorStyle"
    ></div>

    <!-- Segment buttons -->
    <button
      v-for="(item, idx) in resolvedItems"
      :key="item.value !== undefined ? item.value : idx"
      :ref="el => setButtonRef(el, item.value)"
      type="button"
      role="tab"
      :aria-selected="modelValue === item.value"
      :title="item.label"
      class="segmented-pill-btn"
      :class="{
        'is-active': modelValue === item.value
      }"
      @click="selectItem(item.value)"
    >
      <v-icon
        v-if="item.icon"
        size="16"
        class="segmented-pill-icon"
      >
        {{ item.icon }}
      </v-icon>
      <span v-if="item.label" class="segmented-pill-label">{{ item.label }}</span>
      <span v-if="item.badge != null" class="segmented-pill-badge">{{ item.badge }}</span>
    </button>
  </div>
</template>

<script>
export default {
  name: 'SegmentedPillToggle',
  props: {
    modelValue: {
      type: [String, Number, Boolean],
      required: true
    },
    items: {
      type: Array,
      default: () => []
    },
    options: {
      type: Array,
      default: () => []
    },
    color: {
      type: String,
      default: '#1976D2'
    },
    size: {
      type: String,
      default: 'medium', // 'x-small' | 'small' | 'medium'
      validator: v => ['x-small', 'small', 'medium'].includes(v)
    },
    block: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'change'],
  data() {
    return {
      buttonRefs: new Map(),
      indicatorStyle: {},
      isIndicatorReady: false,
      resizeObserver: null
    };
  },
  computed: {
    resolvedItems() {
      if (this.items && this.items.length) return this.items;
      if (this.options && this.options.length) return this.options;
      return [];
    },
    containerStyle() {
      const c = this.color || '#1976D2';
      const inactive = this.getInactiveColor(c);
      return {
        '--pill-accent': c,
        '--pill-container-bg': this.hexToRgba(c, 0.08),
        '--pill-container-border': this.hexToRgba(c, 0.28),
        '--pill-hover-bg': this.hexToRgba(c, 0.12),
        '--pill-hover-color': inactive,
        '--pill-inactive-color': inactive,
      };
    }
  },
  watch: {
    modelValue() {
      this.$nextTick(() => {
        this.updateIndicator();
      });
    },
    items: {
      deep: true,
      handler() {
        this.$nextTick(() => {
          this.updateIndicator();
        });
      }
    },
    options: {
      deep: true,
      handler() {
        this.$nextTick(() => {
          this.updateIndicator();
        });
      }
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.updateIndicator();
      // Second tick to account for font rendering or DOM settlements
      setTimeout(() => {
        this.updateIndicator();
      }, 50);
    });

    if (window.ResizeObserver && this.$refs.containerRef) {
      this.resizeObserver = new ResizeObserver(() => {
        this.updateIndicator();
      });
      this.resizeObserver.observe(this.$refs.containerRef);
    }
    window.addEventListener('resize', this.onWindowResize);
  },
  beforeUnmount() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
    window.removeEventListener('resize', this.onWindowResize);
  },
  methods: {
    hexToRgba(hex, alpha = 1) {
      if (!hex || typeof hex !== 'string') return `rgba(25, 118, 210, ${alpha})`;
      if (hex.startsWith('rgb')) return hex;
      let clean = hex.replace('#', '');
      if (clean.length === 3) clean = clean.split('').map(x => x + x).join('');
      if (clean.length !== 6) return `rgba(25, 118, 210, ${alpha})`;
      const num = parseInt(clean, 16);
      const r = (num >> 16) & 255;
      const g = (num >> 8) & 255;
      const b = num & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },
    getInactiveColor(colorHex) {
      if (!colorHex) return '#1565C0';
      const clean = String(colorHex).toLowerCase();
      // Match module theme colors to high-contrast, noticeable shades in light mode
      const map = {
        '#42a5f5': '#1565C0', // Workforce / Charge -> Rich royal cobalt blue matching description
        '#1e88e5': '#0D47A1', // Workforce primary blue -> Deep navy blue
        '#1976d2': '#0D47A1', // Primary blue -> Deep navy blue
        '#1565c0': '#0D47A1',
        '#0288d1': '#0369A1',
        '#00897b': '#004D40', // Production teal -> Deep dark teal
        '#26a69a': '#004D40',
        '#4643a0': '#2E2A72', // Shipping -> Deep indigo
        '#6c66bb': '#312E81',
        '#7b1fa2': '#4A148C', // Corporal -> Deep rich royal purple
        '#9c27b0': '#4A148C',
        '#a21ee5': '#6B21A8', // Warehouse -> Deep royal purple
        '#9742f5': '#581C87',
        '#6d4c41': '#3E2723', // Inventory -> Deep rich espresso brown
        '#8d6e63': '#4E342E',
        '#f9a825': '#B45309', // Transfer -> Deep rich amber
        '#f57f17': '#92400E',
        '#e65100': '#9A3412', // Invoice -> Deep rich burnt orange
        '#f57c00': '#C2410C',
        '#388e3c': '#14532D', // Guano -> Deep forest green
        '#546e7a': '#1E293B', // Package -> Deep slate
      };
      return map[clean] || '#1565C0';
    },
    setButtonRef(el, value) {
      if (el) {
        this.buttonRefs.set(value, el);
      } else {
        this.buttonRefs.delete(value);
      }
    },
    selectItem(val) {
      if (this.modelValue !== val) {
        this.$emit('update:modelValue', val);
        this.$emit('change', val);
      }
    },
    onWindowResize() {
      this.updateIndicator();
    },
    updateIndicator() {
      const container = this.$refs.containerRef;
      const activeBtn = this.buttonRefs.get(this.modelValue);

      if (!container || !activeBtn) {
        this.isIndicatorReady = false;
        return;
      }

      const containerRect = container.getBoundingClientRect();
      const btnRect = activeBtn.getBoundingClientRect();

      // Guard against hidden elements (display: none)
      if (btnRect.width === 0 && btnRect.height === 0) {
        return;
      }

      const left = btnRect.left - containerRect.left;
      const top = btnRect.top - containerRect.top;
      const width = btnRect.width;
      const height = btnRect.height;

      this.indicatorStyle = {
        transform: `translate3d(${left}px, ${top}px, 0)`,
        width: `${width}px`,
        height: `${height}px`,
        backgroundColor: this.color,
        boxShadow: `0 3px 10px ${this.hexToRgba(this.color, 0.35)}`
      };

      this.isIndicatorReady = true;
    }
  }
};
</script>

<style scoped>
.segmented-pill-container {
  display: inline-flex;
  align-items: center;
  position: relative;
  box-sizing: border-box;
  background: var(--pill-container-bg, #EBF3FD);
  border: 1px solid var(--pill-container-border, rgba(66, 165, 245, 0.32));
  border-radius: 9999px;
  padding: 3px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  user-select: none;
  font-family: 'Inter', sans-serif;
  max-width: 100%;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.segmented-pill-container::-webkit-scrollbar {
  display: none;
}

.segmented-pill-container *,
.segmented-pill-container *::before,
.segmented-pill-container *::after {
  box-sizing: border-box;
}

/* Block / Full Width Mode */
.segmented-pill-container.segmented-pill--block {
  display: flex !important;
  width: 100% !important;
}

.segmented-pill--block .segmented-pill-btn {
  flex: 1 1 0 !important;
  min-width: 0 !important;
  justify-content: center !important;
  text-align: center;
}

/* Sliding active pill */
.segmented-pill-indicator {
  position: absolute;
  top: 0;
  left: 0;
  border-radius: 9999px;
  pointer-events: none;
  z-index: 1;
  transition: transform 0.32s cubic-bezier(0.34, 1.4, 0.64, 1),
              width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform, width;
}

/* Individual buttons */
.segmented-pill-btn {
  position: relative;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  gap: 7px;
  background: transparent;
  border: none;
  outline: none;
  cursor: pointer;
  border-radius: 9999px;
  font-family: inherit;
  font-weight: 600;
  font-size: 13px;
  color: var(--pill-inactive-color, #1565C0);
  padding: 0 16px;
  height: 32px;
  white-space: nowrap;
  letter-spacing: 0.15px;
  transition: color 0.22s ease, transform 0.15s ease;
}

.segmented-pill-btn:not(.is-active) {
  color: var(--pill-inactive-color, #1565C0);
}

/* Fallback styling before indicator positions */
.segmented-pill-container:not(.has-indicator) .segmented-pill-btn.is-active {
  background: var(--pill-accent, #1976D2);
  color: #FFFFFF !important;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
}

.segmented-pill-btn:hover:not(.is-active) {
  color: var(--pill-hover-color, #0D47A1);
  background: var(--pill-hover-bg, rgba(66, 165, 245, 0.12));
}

.segmented-pill-btn:active {
  transform: scale(0.96);
}

.segmented-pill-btn.is-active {
  color: #FFFFFF !important;
  font-weight: 700;
}

.segmented-pill-icon {
  font-size: 16px;
  transition: transform 0.25s ease, color 0.25s ease;
  color: var(--pill-inactive-color, #1565C0);
}

.segmented-pill-btn:hover:not(.is-active) .segmented-pill-icon {
  color: var(--pill-hover-color, #0D47A1);
}

.segmented-pill-btn.is-active .segmented-pill-icon {
  color: #FFFFFF !important;
  transform: scale(1.08);
}

.segmented-pill-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.segmented-pill-badge {
  font-size: 11px;
  padding: 1px 6px;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.25);
  font-weight: 700;
  margin-left: 2px;
}

/* Sizes */
.segmented-pill--x-small {
  padding: 2px;
}
.segmented-pill--x-small .segmented-pill-btn {
  height: 22px;
  padding: 0 4px;
  font-size: 9.5px;
  gap: 3px;
}
.segmented-pill--x-small .segmented-pill-icon {
  font-size: 11px;
}
.segmented-pill--small .segmented-pill-btn {
  height: 28px;
  padding: 0 12px;
  font-size: 12px;
  gap: 5px;
}
.segmented-pill--small .segmented-pill-icon {
  font-size: 14px;
}

/* Dark mode theme */
:deep(.v-theme--dark) .segmented-pill-container,
.v-theme--dark .segmented-pill-container {
  background: rgba(30, 41, 59, 0.85) !important;
  border: 1px solid var(--pill-container-border, rgba(66, 165, 245, 0.25)) !important;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important;
}

:deep(.v-theme--dark) .segmented-pill-btn:not(.is-active),
.v-theme--dark .segmented-pill-btn:not(.is-active) {
  color: #94A3B8 !important;
}

:deep(.v-theme--dark) .segmented-pill-btn:not(.is-active) .segmented-pill-icon,
.v-theme--dark .segmented-pill-btn:not(.is-active) .segmented-pill-icon {
  color: #94A3B8 !important;
}

:deep(.v-theme--dark) .segmented-pill-btn:hover:not(.is-active),
.v-theme--dark .segmented-pill-btn:hover:not(.is-active) {
  color: var(--pill-accent, #60A5FA) !important;
  background: var(--pill-hover-bg, rgba(96, 165, 250, 0.12)) !important;
}

:deep(.v-theme--dark) .segmented-pill-btn:hover:not(.is-active) .segmented-pill-icon,
.v-theme--dark .segmented-pill-btn:hover:not(.is-active) .segmented-pill-icon {
  color: var(--pill-accent, #60A5FA) !important;
}
</style>
