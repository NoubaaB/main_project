<template>
<div class="col-hdr">
  <div class="col-hdr-top">
    <span class="col-hdr-title" @click="$emit('sort',col.key)">{{ col.title }}</span>
    <v-btn v-if="sortDir" icon variant="text" size="16" @click="$emit('remove-sort',col.key)">
      <v-icon size="12" :color="sortDir?'info':'grey'">{{ sortDir==='asc'?'mdi-arrow-up':'mdi-arrow-down' }}</v-icon>
    </v-btn>
    <span v-if="sortIndex" class="sort-badge">{{ sortIndex }}</span>
    <v-btn icon variant="text" size="16" @click="$emit('pin',col.key)">
      <v-icon size="12" :color="pinned?'warning':'grey'">mdi-pin{{ pinned?'':'-off-outline' }}</v-icon>
    </v-btn>
  </div>
  <v-menu v-model="menu" :close-on-content-click="false" location="bottom">
    <template #activator="{props}"><v-btn v-bind="props" icon variant="text" size="20" class="mt-1"><v-icon size="14" :color="hasFilter?'info':'grey'">mdi-filter</v-icon></v-btn></template>
    <v-card min-width="220" class="rounded-lg pa-2">
      <v-autocomplete v-model="localFilter" :items="options" density="compact" variant="outlined" color="info" hide-details multiple chips closable-chips :placeholder="$t('filter')" class="text-info" @update:model-value="$emit('filter',{key:col.key,val:$event})">
        <template #chip="{item,props}"><v-chip v-bind="props" size="x-small" color="info" variant="tonal" closable>{{item.title}}</v-chip></template>
      </v-autocomplete>
    </v-card>
  </v-menu>
</div>
</template>
<script>
export default {
  props:['col','sortDir','sortIndex','pinned','options','modelValue'],
  emits:['sort','remove-sort','pin','filter'],
  data(){return{menu:false,localFilter:this.modelValue||[]}},
  computed:{hasFilter(){return this.localFilter&&this.localFilter.length>0}},
  watch:{modelValue(v){this.localFilter=v||[]}}
}
</script>
<style scoped>
.col-hdr{display:flex;flex-direction:column;align-items:center;gap:2px;min-width:80px}
.col-hdr-top{display:flex;align-items:center;gap:2px;white-space:nowrap}
.col-hdr-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;opacity:.7;cursor:pointer}
.sort-badge{font-size:9px;background:#1976D2;color:#fff;border-radius:50%;width:14px;height:14px;display:flex;align-items:center;justify-content:center}
</style>
