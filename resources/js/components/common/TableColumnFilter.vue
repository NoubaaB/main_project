<template>
  <v-menu
    v-model="menuOpen"
    :close-on-content-click="false"
    location="bottom end"
    transition="slide-y-transition"
    min-width="240"
    max-width="320"
  >
    <!-- Activator button in table header -->
    <template #activator="{ props: menuProps }">
      <v-btn
        v-bind="menuProps"
        icon
        variant="text"
        :size="buttonSize"
        class="table-col-filter-btn"
        :class="{ 'filter-active': isActive }"
        :style="activatorStyle"
        :title="$t('filter') + (title ? ': ' + title : '')"
        @click.stop
      >
        <v-icon :size="iconSize" :color="isActive ? color : 'grey'">
          {{ isActive ? 'mdi-filter' : 'mdi-filter-outline' }}
        </v-icon>
        <span v-if="isActive" class="filter-count-badge" :style="{ backgroundColor: color }">
          {{ selectedCount }}
        </span>
      </v-btn>
    </template>

    <!-- Filter Card Content -->
    <v-card class="table-col-filter-card rounded-xl elevation-8" elevation="8">
      <!-- Card Header -->
      <div class="filter-card-header px-3 py-2 d-flex align-center justify-space-between">
        <div class="d-flex align-center ga-1 text-truncate" style="max-width: 190px;">
          <v-icon size="15" :color="color">mdi-filter-variant</v-icon>
          <span class="text-caption font-weight-bold text-truncate">{{ title || $t('filter') }}</span>
          <v-chip
            v-if="isActive"
            size="x-small"
            :color="color"
            variant="tonal"
            class="ml-1 font-weight-bold"
          >
            {{ selectedCount }}
          </v-chip>
        </div>
        <div class="d-flex align-center ga-1">
          <v-btn
            v-if="isActive"
            icon
            variant="text"
            size="22"
            color="warning"
            :title="$t('clear_all') || 'Clear'"
            @click.stop="clearSelection"
          >
            <v-icon size="14">mdi-filter-off</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            size="22"
            color="grey"
            @click.stop="menuOpen = false"
          >
            <v-icon size="14">mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <v-divider></v-divider>

      <!-- Search Input (AddCharge style: compact, outlined, rounded, color-matched) -->
      <div class="px-2 pt-2 pb-1">
        <v-text-field
          v-model="searchQuery"
          density="compact"
          variant="outlined"
          rounded="lg"
          :color="color"
          hide-details
          clearable
          prepend-inner-icon="mdi-magnify"
          :placeholder="$t('search_values') || 'Search values...'"
          class="filter-search-input"
          @click.stop
        ></v-text-field>
      </div>

      <!-- Quick Action Controls -->
      <div class="px-2 py-1 d-flex align-center justify-space-between text-caption">
        <v-btn
          variant="text"
          size="x-small"
          :color="color"
          class="text-none px-1 font-weight-medium"
          @click.stop="selectAll"
        >
          <v-icon size="12" class="mr-1">mdi-checkbox-multiple-marked-outline</v-icon>
          {{ $t('select_all') || 'Select All' }}
        </v-btn>
        <v-btn
          v-if="isActive"
          variant="text"
          size="x-small"
          color="warning"
          class="text-none px-1 font-weight-medium"
          @click.stop="clearSelection"
        >
          <v-icon size="12" class="mr-1">mdi-close-box-multiple-outline</v-icon>
          {{ $t('clear_all') || 'Clear' }}
        </v-btn>
      </div>

      <v-divider></v-divider>

      <!-- Scrollable Options List -->
      <div class="filter-options-list" style="max-height: 220px; overflow-y: auto;">
        <template v-if="filteredNormalizedOptions.length > 0">
          <div
            v-for="opt in filteredNormalizedOptions"
            :key="opt.value"
            class="filter-option-row px-2 py-1 d-flex align-center justify-space-between cursor-pointer"
            :class="{ 'option-selected': isSelected(opt.value) }"
            @click.stop="toggleOption(opt.value)"
          >
            <div class="d-flex align-center ga-2 text-truncate" style="flex: 1; min-width: 0;">
              <v-checkbox-btn
                :model-value="isSelected(opt.value)"
                :color="color"
                density="compact"
                hide-details
                @click.stop="toggleOption(opt.value)"
              ></v-checkbox-btn>
              <span class="text-caption text-truncate" :title="opt.label">
                {{ opt.label }}
              </span>
            </div>
            <v-chip
              v-if="opt.count != null"
              size="x-small"
              variant="tonal"
              :color="isSelected(opt.value) ? color : 'grey'"
              class="ml-1 font-weight-medium option-count-chip"
            >
              {{ opt.count }}
            </v-chip>
          </div>
        </template>
        <div v-else class="pa-4 text-center text-caption text-grey d-flex flex-column align-center ga-1">
          <v-icon size="24" color="grey-lighten-1">mdi-magnify-close</v-icon>
          <span>{{ $t('no_options_found') || 'No options found' }}</span>
        </div>
      </div>
    </v-card>
  </v-menu>
</template>

<script>
export default {
  name: 'TableColumnFilter',
  props: {
    modelValue: {
      type: Array,
      default: () => [],
    },
    options: {
      type: Array,
      default: () => [],
    },
    counts: {
      type: Object,
      default: () => ({}),
    },
    title: {
      type: String,
      default: '',
    },
    color: {
      type: String,
      default: '#42A5F5',
    },
    buttonSize: {
      type: [Number, String],
      default: 16,
    },
    iconSize: {
      type: [Number, String],
      default: 12,
    },
  },
  emits: ['update:modelValue', 'change'],
  data() {
    return {
      menuOpen: false,
      searchQuery: '',
    };
  },
  computed: {
    selectedSet() {
      return new Set(this.modelValue || []);
    },
    selectedCount() {
      return (this.modelValue || []).length;
    },
    isActive() {
      return this.selectedCount > 0;
    },
    activatorStyle() {
      if (!this.isActive) return {};
      return {
        position: 'relative',
        backgroundColor: this.color ? `${this.color}18` : 'rgba(66, 165, 245, 0.12)',
        borderRadius: '50%',
      };
    },
    normalizedOptions() {
      const opts = this.options || [];
      return opts.map((opt) => {
        if (opt && typeof opt === 'object') {
          const val = opt.value != null ? String(opt.value) : '';
          const label = opt.title != null ? String(opt.title) : (opt.label != null ? String(opt.label) : val);
          const count = opt.count != null ? opt.count : (this.counts && this.counts[val] != null ? this.counts[val] : null);
          return { value: val, label: label, count: count };
        }
        const val = String(opt);
        const count = this.counts && this.counts[val] != null ? this.counts[val] : null;
        return { value: val, label: val, count: count };
      });
    },
    filteredNormalizedOptions() {
      const q = (this.searchQuery || '').trim().toLowerCase();
      if (!q) return this.normalizedOptions;
      return this.normalizedOptions.filter((opt) => {
        return (opt.label || '').toLowerCase().includes(q) || (opt.value || '').toLowerCase().includes(q);
      });
    },
  },
  methods: {
    isSelected(value) {
      return this.selectedSet.has(value);
    },
    toggleOption(value) {
      const current = Array.isArray(this.modelValue) ? [...this.modelValue] : [];
      const idx = current.indexOf(value);
      if (idx === -1) {
        current.push(value);
      } else {
        current.splice(idx, 1);
      }
      this.$emit('update:modelValue', current);
      this.$emit('change', current);
    },
    selectAll() {
      // Select all currently visible matching options
      const current = new Set(this.modelValue || []);
      this.filteredNormalizedOptions.forEach((opt) => current.add(opt.value));
      const arr = Array.from(current);
      this.$emit('update:modelValue', arr);
      this.$emit('change', arr);
    },
    clearSelection() {
      this.$emit('update:modelValue', []);
      this.$emit('change', []);
    },
  },
};
</script>

<style scoped>
.table-col-filter-btn {
  position: relative;
  transition: all 0.2s ease;
  min-width: 0 !important;
  padding: 0 !important;
  margin: 0 1px !important;
}

.table-col-filter-btn:hover {
  transform: scale(1.1);
}

.filter-count-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 13px;
  height: 13px;
  border-radius: 9px;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 2px;
  line-height: 1;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.table-col-filter-card {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.filter-card-header {
  background: rgba(0, 0, 0, 0.02);
}

.filter-search-input :deep(.v-field) {
  border-radius: 8px !important;
  font-size: 0.78rem;
}

.filter-option-row {
  border-radius: 6px;
  transition: background-color 0.15s ease;
  user-select: none;
}

.filter-option-row:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.filter-option-row.option-selected {
  background-color: rgba(0, 0, 0, 0.03);
  font-weight: 600;
}

.option-count-chip {
  font-size: 9px !important;
  height: 18px !important;
  padding: 0 5px !important;
}

.filter-options-list::-webkit-scrollbar {
  width: 5px;
}

.filter-options-list::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.03);
}

.filter-options-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.18);
  border-radius: 4px;
}
</style>
