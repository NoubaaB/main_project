<template>
  <v-menu
    v-model="menuOpen"
    :close-on-content-click="false"
    location="bottom end"
    transition="slide-y-transition"
    width="320"
  >
    <!-- Activator Button -->
    <template #activator="{ props: menuProps }">
      <v-btn
        v-bind="menuProps"
        variant="tonal"
        :color="color"
        rounded
        size="small"
        prepend-icon="mdi-view-column-outline"
        class="font-weight-medium"
      >
        {{ $t('columns') }}
        <v-badge
          :content="`${visibleCount}/${totalCount}`"
          :color="color"
          inline
          class="ml-1"
        ></v-badge>
      </v-btn>
    </template>

    <v-card class="rounded-xl pa-3 elevation-8" elevation="8">
      <!-- Header -->
      <div class="d-flex align-center justify-space-between mb-2 pb-2 border-b">
        <div class="d-flex align-center ga-1 font-weight-bold text-caption" :style="{ color: color }">
          <v-icon size="16" :color="color">mdi-view-column-outline</v-icon>
          <span>{{ $t('column_visibility') }}</span>
        </div>
        <v-btn icon="mdi-close" size="x-small" variant="text" @click="menuOpen = false"></v-btn>
      </div>

      <!-- Quick Action Controls: Show All, Hide All, Reset -->
      <div class="d-flex align-center ga-1 mb-2">
        <v-btn
          size="x-small"
          variant="tonal"
          :color="color"
          rounded="lg"
          class="flex-grow-1"
          @click="$emit('show-all')"
        >
          {{ $t('show_all_columns') }}
        </v-btn>
        <v-btn
          size="x-small"
          variant="tonal"
          color="grey"
          rounded="lg"
          class="flex-grow-1"
          @click="$emit('hide-all')"
        >
          {{ $t('hide_all_columns') }}
        </v-btn>
        <v-btn
          size="x-small"
          variant="tonal"
          color="warning"
          icon="mdi-restore"
          rounded="lg"
          :title="$t('reset_table_config')"
          @click="$emit('reset')"
        ></v-btn>
      </div>

      <!-- Search Field (AddCharge style: compact, outlined, rounded, color-matched) -->
      <div class="mb-2">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          rounded="lg"
          :color="color"
          hide-details
          clearable
          prepend-inner-icon="mdi-magnify"
          :placeholder="$t('search_columns')"
          style="font-size: 11px;"
        ></v-text-field>
      </div>

      <!-- Columns List with Checkbox, Move Up/Down, Pin Left/Right -->
      <div class="table-col-manager-list" style="max-height: 280px; overflow-y: auto;">
        <div
          v-for="(h, idx) in filteredColumns"
          :key="h.key"
          class="d-flex align-center justify-space-between pa-1 px-2 mb-1 rounded col-item"
          :class="{ 'col-item-hidden': !isVisible(h.key), 'col-item-dragover': dragOverKey === h.key }"
          style="font-size: 11px;"
          draggable="true"
          @dragstart="onDragStart(h.key, $event)"
          @dragover.prevent="onDragOver(h.key)"
          @dragleave="onDragLeave(h.key)"
          @drop="onDrop(h.key)"
        >
          <!-- Left: Drag handle, Checkbox & Title -->
          <div class="d-flex align-center ga-1 text-truncate" style="max-width: 175px;">
            <v-icon size="14" color="grey-lighten-1" class="col-drag-handle" style="cursor: grab;">mdi-drag-vertical</v-icon>
            <v-checkbox-btn
              :model-value="isVisible(h.key)"
              :color="color"
              density="compact"
              hide-details
              class="ma-0 pa-0 flex-shrink-0"
              @update:model-value="$emit('toggle-visibility', h.key)"
            ></v-checkbox-btn>
            <span
              class="text-truncate font-weight-medium"
              :class="{ 'text-medium-emphasis': !isVisible(h.key) }"
              :title="h.title"
              style="cursor: pointer;"
              @click="$emit('toggle-visibility', h.key)"
            >
              {{ h.title }}
            </span>
          </div>

          <!-- Right: Move Up/Down & Pin Left/Right -->
          <div class="d-flex align-center ga-1">
            <!-- Move Up -->
            <v-btn
              size="20"
              icon
              variant="text"
              color="grey"
              :disabled="idx === 0"
              :title="$t('move_up')"
              @click="$emit('move-up', h.key)"
            >
              <v-icon size="12">mdi-arrow-up</v-icon>
            </v-btn>
            <!-- Move Down -->
            <v-btn
              size="20"
              icon
              variant="text"
              color="grey"
              :disabled="idx === filteredColumns.length - 1"
              :title="$t('move_down')"
              @click="$emit('move-down', h.key)"
            >
              <v-icon size="12">mdi-arrow-down</v-icon>
            </v-btn>
            <!-- Pin Left -->
            <v-btn
              size="20"
              icon
              :variant="pinSide(h.key) === 'left' ? 'flat' : 'text'"
              :color="pinSide(h.key) === 'left' ? color : 'grey'"
              :title="$t('pin_left')"
              @click="$emit('set-pin', { key: h.key, side: pinSide(h.key) === 'left' ? 'none' : 'left' })"
            >
              <v-icon size="12">mdi-dock-left</v-icon>
            </v-btn>
            <!-- Pin Right -->
            <v-btn
              size="20"
              icon
              :variant="pinSide(h.key) === 'right' ? 'flat' : 'text'"
              :color="pinSide(h.key) === 'right' ? '#FF7043' : 'grey'"
              :title="$t('pin_right')"
              @click="$emit('set-pin', { key: h.key, side: pinSide(h.key) === 'right' ? 'none' : 'right' })"
            >
              <v-icon size="12">mdi-dock-right</v-icon>
            </v-btn>
          </div>
        </div>
      </div>
    </v-card>
  </v-menu>
</template>

<script>
export default {
  name: 'TableColumnManager',
  props: {
    columns: {
      type: Array,
      default: () => [],
    },
    color: {
      type: String,
      default: '#42A5F5',
    },
    hiddenColumns: {
      type: Array,
      default: () => [],
    },
    pinState: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: [
    'toggle-visibility',
    'show-all',
    'hide-all',
    'move-up',
    'move-down',
    'set-pin',
    'reset',
    'reorder',
  ],
  data() {
    return {
      menuOpen: false,
      search: '',
      dragKey: null,
      dragOverKey: null,
    };
  },
  computed: {
    filteredColumns() {
      const q = (this.search || '').trim().toLowerCase();
      if (!q) return this.columns;
      return (this.columns || []).filter(c =>
        (c.title || c.key || '').toLowerCase().includes(q)
      );
    },
    totalCount() {
      return (this.columns || []).length;
    },
    visibleCount() {
      const hidden = new Set(this.hiddenColumns || []);
      return (this.columns || []).filter(c => !hidden.has(c.key)).length;
    },
  },
  methods: {
    isVisible(key) {
      return !(this.hiddenColumns || []).includes(key);
    },
    pinSide(key) {
      return (this.pinState && this.pinState[key]) || null;
    },
    onDragStart(key, e) {
      this.dragKey = key;
      if (e && e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', key);
      }
    },
    onDragOver(key) {
      this.dragOverKey = key;
    },
    onDragLeave(key) {
      if (this.dragOverKey === key) {
        this.dragOverKey = null;
      }
    },
    onDrop(targetKey) {
      if (this.dragKey && targetKey && this.dragKey !== targetKey) {
        this.$emit('reorder', { fromKey: this.dragKey, toKey: targetKey });
      }
      this.dragKey = null;
      this.dragOverKey = null;
    },
  },
};
</script>

<style scoped>
.col-item {
  background: rgba(var(--v-theme-surface-variant), 0.35);
  transition: all 0.15s ease;
  user-select: none;
}
.col-item:hover {
  background: rgba(var(--v-theme-surface-variant), 0.6);
}
.col-item-hidden {
  opacity: 0.6;
}
.col-item-dragover {
  border: 1px dashed currentColor !important;
}
.col-drag-handle {
  cursor: grab;
}
.col-drag-handle:active {
  cursor: grabbing;
}

.table-col-manager-list {
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.12) transparent;
}
.table-col-manager-list:hover {
  scrollbar-color: rgba(0, 0, 0, 0.32) transparent;
}
.table-col-manager-list::-webkit-scrollbar {
  width: 4px;
}
.table-col-manager-list::-webkit-scrollbar-track {
  background: transparent !important;
}
.table-col-manager-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.12);
  border-radius: 999px;
  transition: background 0.2s ease;
}
.table-col-manager-list:hover::-webkit-scrollbar-thumb,
.table-col-manager-list::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.32);
}
.table-col-manager-list::-webkit-scrollbar-thumb:active {
  background: rgba(0, 0, 0, 0.55);
}
:deep(.v-theme--dark) .table-col-manager-list,
.v-theme--dark .table-col-manager-list {
  scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
}
:deep(.v-theme--dark) .table-col-manager-list:hover,
.v-theme--dark .table-col-manager-list:hover {
  scrollbar-color: rgba(255, 255, 255, 0.32) transparent;
}
:deep(.v-theme--dark) .table-col-manager-list::-webkit-scrollbar-thumb,
.v-theme--dark .table-col-manager-list::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.12);
}
:deep(.v-theme--dark) .table-col-manager-list:hover::-webkit-scrollbar-thumb,
.v-theme--dark .table-col-manager-list:hover::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.32);
}
:deep(.v-theme--dark) .table-col-manager-list::-webkit-scrollbar-thumb:active,
.v-theme--dark .table-col-manager-list::-webkit-scrollbar-thumb:active {
  background: rgba(255, 255, 255, 0.55);
}
</style>

