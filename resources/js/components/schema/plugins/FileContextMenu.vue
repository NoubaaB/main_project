<template>
  <v-menu
    v-model="isOpen"
    :location="location"
    :close-on-content-click="closeOnContentClick"
    offset="8"
    transition="scroll-y-transition"
  >
    <template #activator="{ props: menuProps }">
      <slot name="activator" :props="menuProps" :isOpen="isOpen">
        <v-btn
          v-bind="menuProps"
          icon
          :size="btnSize"
          :variant="btnVariant"
          :color="btnColor"
          class="file-context-trigger-btn"
          :title="$t('more_actions')"
        >
          <v-badge
            v-if="badgeCount > 0"
            :content="badgeCount"
            color="error"
            floating
            dot
          >
            <v-icon :size="iconSize">mdi-dots-vertical</v-icon>
          </v-badge>
          <v-icon v-else :size="iconSize">mdi-dots-vertical</v-icon>
        </v-btn>
      </slot>
    </template>

    <!-- Context Menu: Two-Card Layout as in Vuetify File Context Menu -->
    <div class="file-context-menu-container">
      <!-- Card 1: Common Actions (Horizontal Toolbar Card) -->
      <v-card
        v-if="topCardActions.length > 0"
        class="file-context-card file-context-card-top rounded-lg"
        :elevation="elevation"
        :min-width="minWidth"
        :max-width="maxWidth"
      >
        <div
          class="d-flex align-center pa-1 px-1.5 w-100"
          :class="topCardActions.length >= 4 ? 'justify-space-between' : 'justify-space-around'"
        >
          <template v-for="(act, idx) in topCardActions" :key="'top-' + idx">
            <v-tooltip :text="act.title" location="top" :disabled="!act.title">
              <template #activator="{ props: tipProps }">
                <v-btn
                  v-bind="tipProps"
                  icon
                  size="small"
                  variant="text"
                  :disabled="act.disabled"
                  :color="act.color || 'default'"
                  class="file-context-icon-btn"
                  @click="handleActionClick(act)"
                >
                  <v-icon :size="18">{{ getNormalizedIcon(act.icon) }}</v-icon>
                </v-btn>
              </template>
            </v-tooltip>
          </template>
        </div>
      </v-card>

      <!-- Card 2: Workflows, Details & Danger Actions List Card -->
      <v-card
        v-if="bottomSections.length > 0"
        :class="['file-context-card file-context-card-bottom rounded-lg', { 'mt-2': topCardActions.length > 0 }]"
        :elevation="elevation"
        :min-width="minWidth"
        :max-width="maxWidth"
      >
        <v-list density="compact" nav class="pa-1 file-context-list" lines="one">
          <template v-for="(sec, sIdx) in bottomSections" :key="'sec-' + sIdx">
            <v-divider v-if="sIdx > 0" class="my-1 file-context-divider"></v-divider>

            <v-list-subheader
              v-if="showSubheaders && sec.subheader"
              class="file-context-subheader text-uppercase font-weight-bold"
            >
              {{ sec.subheader }}
            </v-list-subheader>

            <template v-for="(act, aIdx) in sec.items" :key="'act-' + sIdx + '-' + aIdx">
              <v-list-item
                v-if="act && act.show !== false"
                density="compact"
                :disabled="act.disabled"
                :class="[
                  'file-context-item',
                  act.destructive ? 'text-error file-context-destructive' : '',
                  act.class || ''
                ]"
                @click="handleActionClick(act)"
              >
                <template #prepend>
                  <v-icon
                    :size="18"
                    :color="act.destructive ? 'error' : (act.color || 'default')"
                    class="file-context-item-icon"
                  >
                    {{ getNormalizedIcon(act.icon) }}
                  </v-icon>
                </template>

                <v-list-item-title class="file-context-title text-caption font-weight-medium">
                  {{ act.title }}
                </v-list-item-title>

                <template #append>
                  <!-- Badge for issues or counts -->
                  <v-chip
                    v-if="act.badge != null && act.badge !== '' && act.badge !== 0"
                    size="x-small"
                    :color="act.badgeColor || 'error'"
                    variant="flat"
                    class="ms-2 font-weight-bold file-context-badge"
                  >
                    {{ act.badge }}
                  </v-chip>

                  <!-- Keyboard Shortcut / Hotkey (e.g. Alt+D, DELETE) -->
                  <span
                    v-else-if="act.shortcut || act.destructive"
                    class="file-context-shortcut"
                    :class="{ 'text-error font-weight-bold': act.destructive }"
                  >
                    {{ act.shortcut || (act.destructive ? 'DELETE' : '') }}
                  </span>

                  <v-icon v-else-if="act.external" size="13" class="ms-1 opacity-50">mdi-open-in-new</v-icon>
                </template>
              </v-list-item>
            </template>
          </template>
        </v-list>
      </v-card>
    </div>
  </v-menu>
</template>

<script>
export default {
  name: 'FileContextMenu',
  props: {
    // Optional explicit quick actions for the top horizontal card
    quickActions: {
      type: Array,
      default: () => null
    },
    // Sections array for the menu
    sections: {
      type: Array,
      required: true,
      default: () => []
    },
    showSubheaders: {
      type: Boolean,
      default: false
    },
    location: {
      type: String,
      default: 'bottom end'
    },
    closeOnContentClick: {
      type: Boolean,
      default: true
    },
    minWidth: {
      type: [Number, String],
      default: 210
    },
    maxWidth: {
      type: [Number, String],
      default: 280
    },
    elevation: {
      type: [Number, String],
      default: 8
    },
    btnSize: {
      type: String,
      default: 'x-small'
    },
    btnVariant: {
      type: String,
      default: 'tonal'
    },
    btnColor: {
      type: String,
      default: 'blue-grey'
    },
    iconSize: {
      type: [Number, String],
      default: 15
    },
    badgeCount: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    isOpen: false
  }),
  computed: {
    commonSection() {
      if (!Array.isArray(this.sections) || this.sections.length === 0) return null;
      return this.sections.find(s => s && (s.key === 'common' || s.key === 'quick' || s.isQuick || s.subheader === this.$t('common_actions'))) || null;
    },
    topCardActions() {
      // 1. If quickActions prop was explicitly provided
      if (Array.isArray(this.quickActions) && this.quickActions.length > 0) {
        return this.quickActions.filter(a => a && a.show !== false);
      }

      // 2. Otherwise auto-extract the common actions section
      const commonSec = this.commonSection;
      if (commonSec && Array.isArray(commonSec.items)) {
        return commonSec.items.filter(a => a && a.show !== false);
      }

      // If no explicit common section but there are multiple sections and the first section has <= 4 icon-ready items
      if (this.sections.length > 1 && Array.isArray(this.sections[0]?.items) && this.sections[0].items.length <= 4) {
        return this.sections[0].items.filter(a => a && a.show !== false);
      }

      return [];
    },
    bottomSections() {
      if (!Array.isArray(this.sections)) return [];

      // If quickActions prop was explicitly provided, all sections stay in bottom card
      if (Array.isArray(this.quickActions) && this.quickActions.length > 0) {
        return this.sections.map(s => this.filterSection(s)).filter(Boolean);
      }

      const topSec = this.commonSection || (this.sections.length > 1 && Array.isArray(this.sections[0]?.items) && this.sections[0].items.length <= 4 ? this.sections[0] : null);

      if (topSec) {
        return this.sections
          .filter(s => s !== topSec)
          .map(s => this.filterSection(s))
          .filter(Boolean);
      }

      return this.sections.map(s => this.filterSection(s)).filter(Boolean);
    }
  },
  methods: {
    filterSection(sec) {
      if (!sec || !Array.isArray(sec.items)) return null;
      const visibleItems = sec.items.filter(item => item && item.show !== false);
      if (visibleItems.length === 0) return null;
      return {
        ...sec,
        items: visibleItems
      };
    },
    getNormalizedIcon(icon) {
      if (!icon) return 'mdi-circle-small';
      // Outline aesthetic enhancements matching Vuetify's file context menu design
      if (icon === 'mdi-eye') return 'mdi-eye-outline';
      if (icon === 'mdi-pencil') return 'mdi-pencil-outline';
      if (icon === 'mdi-delete') return 'mdi-delete-outline';
      if (icon === 'mdi-download') return 'mdi-download-outline';
      return icon;
    },
    handleActionClick(act) {
      if (act && typeof act.action === 'function' && !act.disabled) {
        act.action();
        if (this.closeOnContentClick) {
          this.isOpen = false;
        }
      }
    }
  }
};
</script>

<style scoped>
/* File Context Menu Container */
.file-context-trigger-btn {
  flex-shrink: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.file-context-menu-container {
  display: flex;
  flex-direction: column;
}

/* Base Card Styling (Light & Dark Themes) */
.file-context-card {
  background: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.12) !important;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08) !important;
  backdrop-filter: blur(8px);
  overflow: hidden;
}

:deep(.v-theme--dark) .file-context-card,
.v-theme--dark .file-context-card {
  background: #1e1e1e !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 8px 26px rgba(0, 0, 0, 0.5) !important;
}

/* Card 1: Top Horizontal Icon Bar */
.file-context-card-top {
  display: flex;
  align-items: center;
}

.file-context-icon-btn {
  border-radius: 8px !important;
  transition: all 0.15s ease-in-out;
  color: rgba(var(--v-theme-on-surface), 0.85);
}

.file-context-icon-btn:hover {
  background: rgba(var(--v-theme-on-surface), 0.08) !important;
}

:deep(.v-theme--dark) .file-context-icon-btn,
.v-theme--dark .file-context-icon-btn {
  color: rgba(255, 255, 255, 0.85) !important;
}

:deep(.v-theme--dark) .file-context-icon-btn:hover,
.v-theme--dark .file-context-icon-btn:hover {
  background: rgba(255, 255, 255, 0.1) !important;
}

/* Card 2: Bottom Context List */
.file-context-list {
  background: transparent !important;
}

.file-context-item {
  border-radius: 6px !important;
  min-height: 32px !important;
  padding: 0 10px !important;
  margin: 1px 0 !important;
  cursor: pointer;
  transition: background 0.15s ease;
}

.file-context-item:hover {
  background: rgba(var(--v-theme-on-surface), 0.06) !important;
}

:deep(.v-theme--dark) .file-context-item:hover,
.v-theme--dark .file-context-item:hover {
  background: rgba(255, 255, 255, 0.07) !important;
}

.file-context-item-icon {
  margin-inline-end: 10px !important;
}

.file-context-title {
  letter-spacing: 0.15px;
}

/* Destructive / Delete Item Styling */
.file-context-destructive {
  color: rgb(var(--v-theme-error)) !important;
}

.file-context-destructive .file-context-title {
  color: rgb(var(--v-theme-error)) !important;
}

.file-context-destructive:hover {
  background: rgba(var(--v-theme-error), 0.1) !important;
}

/* Hotkey / Shortcut Text (like Alt+D, DELETE) */
.file-context-shortcut {
  font-size: 11px;
  letter-spacing: 0.4px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  font-weight: 500;
  margin-left: 14px;
}

:deep(.v-theme--dark) .file-context-shortcut,
.v-theme--dark .file-context-shortcut {
  color: rgba(255, 255, 255, 0.45);
}

.file-context-shortcut.text-error {
  color: rgb(var(--v-theme-error)) !important;
  letter-spacing: 0.6px;
  opacity: 0.95;
}

/* Subheaders */
.file-context-subheader {
  font-size: 10px !important;
  letter-spacing: 0.6px !important;
  height: 22px !important;
  min-height: 22px !important;
  padding: 0 8px !important;
  opacity: 0.65;
  color: rgb(var(--v-theme-on-surface));
}

.file-context-divider {
  border-color: rgba(var(--v-border-color), 0.12) !important;
}

:deep(.v-theme--dark) .file-context-divider,
.v-theme--dark .file-context-divider {
  border-color: rgba(255, 255, 255, 0.08) !important;
}
</style>
