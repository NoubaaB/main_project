<template>
  <div class="tm-page-wrap">
    <v-card class="tm-card border" elevation="0">
      <!-- ================= HERO HEADER ================= -->
      <div class="tm-hero">
        <div class="tm-hero-inner">
          <div class="d-flex flex-wrap align-center justify-space-between ga-4">
            <div class="d-flex align-center ga-3">
              <div class="tm-hero-icon">
                <v-icon size="28" color="white">mdi-account-group</v-icon>
              </div>
              <div>
                <div class="tm-hero-title">{{ $t('team_management') }}</div>
                <div class="text-white text-caption" style="opacity: .85;">{{ $t('manage_teams_desc') }}</div>
              </div>
            </div>

            <!-- Hero KPI Chips -->
            <div class="d-flex flex-wrap align-center ga-2">
              <div class="tm-kpi-chip">
                <v-icon size="16" color="#90CAF9" class="mr-1">mdi-account-group</v-icon>
                <span class="tm-kpi-num">{{ teamStore.list.length }}</span>
                <span class="tm-kpi-lbl">{{ $t('total_teams') }}</span>
              </div>
              <div class="tm-kpi-chip">
                <v-icon size="16" color="#A5D6A7" class="mr-1">mdi-account-hard-hat</v-icon>
                <span class="tm-kpi-num">{{ totalAssignedWorkforces }}</span>
                <span class="tm-kpi-lbl">{{ $t('total_assigned_workforces') }}</span>
              </div>
              <div class="tm-kpi-chip">
                <v-icon size="16" color="#FFE082" class="mr-1">mdi-account-tie</v-icon>
                <span class="tm-kpi-num">{{ chefsCount }}</span>
                <span class="tm-kpi-lbl">{{ $t('chef_de_equipe') }}</span>
              </div>
              <div class="tm-kpi-chip">
                <v-icon size="16" color="#80DEEA" class="mr-1">mdi-chart-pie</v-icon>
                <span class="tm-kpi-num">{{ avgTeamSize }}</span>
                <span class="tm-kpi-lbl">avg size</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= TOOLBAR & ACTIONS ================= -->
      <v-card-text class="pa-4 tm-card-body">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-3">
          <!-- Left: Search & Quick Filter Pills -->
          <div class="d-flex flex-wrap align-center ga-2 flex-grow-1" style="max-width: 680px;">
            <v-text-field
              v-model="quickSearch"
              :placeholder="$t('search_teams') || 'Search teams by name, chef, members...'"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              rounded
              color="#42A5F5"
              class="tm-search-input text-workforce"
              hide-details
              clearable
              style="min-width: 260px;"
            ></v-text-field>

            <v-btn-toggle
              v-model="quickFilter"
              density="compact"
              rounded
              mandatory
              variant="outlined"
              color="#42A5F5"
              class="tm-filter-toggle"
            >
              <v-btn value="all" size="small">{{ $t('all') }} ({{ teamStore.list.length }})</v-btn>
              <v-btn value="active" size="small">{{ $t('with_members') || 'With Members' }} ({{ activeTeamsCount }})</v-btn>
              <v-btn value="empty" size="small">{{ $t('empty_teams') || 'Empty' }} ({{ teamStore.list.length - activeTeamsCount }})</v-btn>
            </v-btn-toggle>
          </div>

          <!-- Right: Actions -->
          <div class="d-flex align-center ga-2">
            <v-btn
              v-if="hasActiveColumnFilters || quickSearch"
              variant="tonal"
              color="warning"
              rounded
              size="small"
              prepend-icon="mdi-filter-off"
              @click="clearAllFilters"
            >
              {{ $t('clear_filters') }}
            </v-btn>
            <v-btn
              v-if="selectedIds.length > 0"
              color="error"
              prepend-icon="mdi-delete-sweep"
              rounded
              size="small"
              variant="flat"
              @click="bulkDeleteDialog = true"
            >
              {{ $t('delete') }} ({{ selectedIds.length }})
            </v-btn>
            <v-btn
              color="#42A5F5"
              prepend-icon="mdi-plus"
              rounded
              size="small"
              variant="flat"
              class="text-white font-weight-bold shadow-sm"
              @click="openDialog(null)"
            >
              {{ $t('add_team') }}
            </v-btn>
          </div>
        </div>

        <!-- ================= RESPONSIVE DATA TABLE ================= -->
        <div class="tm-table-wrap">
          <v-data-table-virtual
            :headers="headers"
            :items="filteredItems"
            :loading="loading ? '#42A5F5' : false"
            density="compact"
            class="rounded-xl tm-table border"
            hover
            show-select
            v-model="selectedIds"
            item-value="id"
            :return-object="false"
            fixed-header
            :height="tableHeight"
          >
            <!-- Custom headers with integrated column filters -->
            <template v-slot:headers="{ columns, isSorted, getSortIcon, toggleSort }">
              <tr>
                <th v-for="col in columns" :key="col.key" class="tm-th">
                  <template v-if="col.key === 'data-table-select'">
                    <v-checkbox-btn
                      :model-value="selectAll"
                      :indeterminate="someSelected && !selectAll"
                      color="#42A5F5"
                      density="compact"
                      hide-details
                      @update:model-value="toggleSelectAll"
                    ></v-checkbox-btn>
                  </template>
                  <div
                    v-else
                    class="tm-th-label"
                    @click="col.sortable !== false ? toggleSort(col) : null"
                    :style="{ cursor: col.sortable !== false ? 'pointer' : 'default' }"
                  >
                    {{ col.title }}
                    <v-icon v-if="isSorted(col)" size="14" class="ms-1">{{ getSortIcon(col) }}</v-icon>
                  </div>
                </th>
              </tr>
              <tr class="tm-filter-row">
                <th v-for="col in columns" :key="col.key + '-filter'" class="tm-filter-th">
                  <v-autocomplete
                    v-if="col.key !== 'actions' && col.key !== 'data-table-select'"
                    v-model="columnFilters[col.key]"
                    :items="getColumnOptions(col.key)"
                    density="compact"
                    variant="outlined"
                    rounded
                    color="#42A5F5"
                    class="tm-col-filter text-workforce"
                    hide-details
                    clearable
                    multiple
                    chips
                    closable-chips
                    :placeholder="$t('filter')"
                  >
                    <template #chip="{ item, props }">
                      <v-chip v-bind="props" size="x-small" color="#42A5F5" variant="tonal" closable>{{ item.title }}</v-chip>
                    </template>
                  </v-autocomplete>
                </th>
              </tr>
            </template>

            <!-- Team Name -->
            <template v-slot:[`item.name`]="{ item }">
              <div class="d-flex align-center ga-2 py-1">
                <div class="tm-avatar-circle">
                  <v-icon size="16" color="#1565C0">mdi-account-group</v-icon>
                </div>
                <div>
                  <div class="font-weight-bold text-body-2 text-truncate" style="max-width: 240px;">
                    {{ item.name }}
                  </div>
                  <div v-if="item.description" class="text-caption text-medium-emphasis text-truncate" style="max-width: 240px;">
                    {{ item.description }}
                  </div>
                </div>
              </div>
            </template>

            <!-- Corporal / Chef of Team -->
            <template v-slot:[`item.corporal`]="{ item }">
              <div v-if="item.corporal" class="d-inline-flex align-center ga-2 py-1 px-2 rounded-pill corporal-badge">
                <v-avatar size="24" color="#1565C0" class="text-white text-caption font-weight-bold">
                  {{ getInitials(item.corporal.full_name) }}
                </v-avatar>
                <span class="text-body-2 font-weight-medium">{{ item.corporal.full_name }}</span>
                <v-chip size="x-small" color="blue-lighten-4" class="text-blue-darken-3 font-weight-bold" variant="flat">
                  {{ $t('chef_de_equipe') }}
                </v-chip>
              </div>
              <span v-else class="text-grey text-caption">-</span>
            </template>

            <!-- Workforces in Team -->
            <template v-slot:[`item.workforces`]="{ item }">
              <div class="d-flex align-center ga-2 justify-center py-1">
                <v-chip
                  size="small"
                  :color="getWorkforceCountColor(item.workforces_count || (item.workforces ? item.workforces.length : 0))"
                  variant="tonal"
                  prepend-icon="mdi-account-hard-hat"
                  class="cursor-pointer font-weight-bold"
                  @click="openMembersDialog(item)"
                >
                  {{ item.workforces_count || (item.workforces ? item.workforces.length : 0) }} {{ $t('workforces') }}
                </v-chip>
                <v-btn
                  v-if="item.workforces && item.workforces.length > 0"
                  icon
                  size="x-small"
                  variant="text"
                  color="#42A5F5"
                  @click="openMembersDialog(item)"
                  :title="$t('view_members')"
                >
                  <v-icon size="16">mdi-eye-outline</v-icon>
                </v-btn>
              </div>
            </template>

            <!-- Description -->
            <template v-slot:[`item.description`]="{ item }">
              <span class="text-caption text-medium-emphasis">{{ item.description || '-' }}</span>
            </template>

            <!-- Actions -->
            <template v-slot:[`item.actions`]="{ item }">
              <div class="d-flex align-center justify-center ga-1">
                <v-btn icon size="x-small" variant="tonal" color="#42A5F5" @click="openDialog(item)" :title="$t('edit')">
                  <v-icon size="15">mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDelete(item)" :title="$t('delete')">
                  <v-icon size="15">mdi-delete</v-icon>
                </v-btn>
              </div>
            </template>
          </v-data-table-virtual>
        </div>
      </v-card-text>
    </v-card>

    <!-- ============= CREATE / EDIT TEAM DIALOG ============= -->
    <v-dialog v-model="dialog" max-width="720" persistent scrollable>
      <v-card class="rounded-2xl overflow-hidden elevation-12">
        <!-- Dialog Header Banner -->
        <div class="tm-dialog-hero">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="tm-dialog-icon">
                <v-icon size="24" color="white">{{ editing ? 'mdi-account-group' : 'mdi-account-multiple-plus' }}</v-icon>
              </div>
              <div>
                <div class="text-subtitle-1 font-weight-bold text-white">
                  {{ editing ? $t('edit_team') : $t('add_team') }}
                </div>
                <div class="text-caption text-white" style="opacity: .8;">
                  {{ editing ? form.name : $t('manage_teams_desc') }}
                </div>
              </div>
            </div>
            <v-btn icon size="small" variant="text" color="white" @click="dialog = false">
              <v-icon size="20">mdi-close</v-icon>
            </v-btn>
          </div>
        </div>

        <v-card-text class="pa-5" style="max-height: 72vh;">
          <!-- Live Preview Card -->
          <div class="tm-preview-card mb-4 pa-3 rounded-xl border">
            <div class="d-flex align-center ga-3">
              <div class="tm-avatar-circle" style="width: 44px; height: 44px;">
                <v-icon size="22" color="#1565C0">mdi-account-group</v-icon>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-center ga-2">
                  <span class="font-weight-bold text-body-1 text-truncate">
                    {{ form.name || $t('team_name') }}
                  </span>
                  <v-chip size="x-small" color="#1565C0" variant="tonal" class="font-weight-bold">
                    {{ (form.workforce_ids || []).length }} {{ $t('workforces') }}
                  </v-chip>
                </div>
                <div class="d-flex flex-wrap align-center ga-2 mt-1">
                  <v-chip v-if="selectedCorporal" size="x-small" color="blue-lighten-4" class="text-blue-darken-3 font-weight-bold" variant="flat">
                    <v-icon size="12" class="mr-1">mdi-account-tie</v-icon>
                    {{ selectedCorporal.full_name }} ({{ $t('chef_de_equipe') }})
                  </v-chip>
                  <span v-else class="text-caption text-disabled">{{ $t('no_chef_assigned') || 'No chef selected yet' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Fields -->
          <v-row dense>
            <!-- Team Name -->
            <v-col cols="12">
              <v-text-field
                v-model="form.name"
                :label="$t('team_name')"
                variant="outlined"
                density="compact"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-account-group"
                :rules="[v => !!v || $t('is_required', { type: $t('team_name') })]"
                @keyup.enter="save"
              ></v-text-field>
            </v-col>

            <!-- Chef of Team (Corporal) -->
            <v-col cols="12">
              <v-autocomplete
                v-model="form.corporal_id"
                :items="corporalStore.list"
                item-value="id"
                item-title="full_name"
                :label="$t('chef_of_team')"
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="text-workforce"
                prepend-inner-icon="mdi-account-tie"
                :rules="[v => !!v || $t('is_required', { type: $t('chef_of_team') })]"
                clearable
              >
                <template #item="{ item, props }">
                  <v-list-item v-bind="props" class="rounded-lg my-1">
                    <template #prepend>
                      <v-avatar size="28" color="#1565C0" class="text-white text-caption font-weight-bold mr-2">
                        {{ getInitials(item.raw.full_name) }}
                      </v-avatar>
                    </template>
                    <v-list-item-title class="font-weight-medium">{{ item.raw.full_name }}</v-list-item-title>
                    <v-list-item-subtitle class="text-caption text-medium-emphasis">
                      {{ item.raw.cni || $t('no_cni') }}
                      <span v-if="item.raw.daily_price > 0" class="ml-2 text-success">
                        • {{ formatNumber(item.raw.daily_price) }} {{ main.currency || 'DH' }}
                      </span>
                    </v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </v-col>

            <!-- Workforces Multi-Select with Action Header -->
            <v-col cols="12">
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption font-weight-bold text-medium-emphasis">
                  <v-icon size="14" color="#42A5F5" class="mr-1">mdi-account-hard-hat</v-icon>
                  {{ $t('associated_workforces') }}
                </span>
                <div class="d-flex align-center ga-2">
                  <v-btn
                    variant="text"
                    size="x-small"
                    color="#1565C0"
                    @click="selectAllWorkforces"
                  >
                    {{ $t('select_all') || 'Select All' }}
                  </v-btn>
                  <v-btn
                    v-if="(form.workforce_ids || []).length > 0"
                    variant="text"
                    size="x-small"
                    color="grey"
                    @click="form.workforce_ids = []"
                  >
                    {{ $t('clear') || 'Clear' }}
                  </v-btn>
                  <v-chip size="x-small" color="#42A5F5" variant="tonal">
                    {{ (form.workforce_ids || []).length }} {{ $t('selected') }}
                  </v-chip>
                </div>
              </div>

              <v-autocomplete
                v-model="form.workforce_ids"
                :items="workforceStore.list"
                item-value="id"
                :item-title="workforceItemTitle"
                :label="$t('select_workforces')"
                multiple
                chips
                closable-chips
                density="compact"
                variant="outlined"
                rounded
                color="#42A5F5"
                class="text-workforce mb-2"
                prepend-inner-icon="mdi-account-multiple"
                clearable
              >
                <template #chip="{ item, props }">
                  <v-chip v-bind="props" size="small" color="#1565C0" variant="tonal" closable>
                    {{ item.title }}
                  </v-chip>
                </template>
                <template #item="{ item, props }">
                  <v-list-item v-bind="props" class="rounded-lg my-1">
                    <template #prepend>
                      <v-avatar size="26" color="blue-lighten-4" class="text-blue-darken-3 text-caption font-weight-bold mr-2">
                        {{ getInitials(item.raw.full_name) }}
                      </v-avatar>
                      <v-checkbox-btn
                        :model-value="(form.workforce_ids || []).includes(item.value)"
                        color="#42A5F5"
                        density="compact"
                        class="mr-1"
                      ></v-checkbox-btn>
                    </template>
                    <v-list-item-title class="font-weight-medium">{{ item.raw.full_name }}</v-list-item-title>
                    <v-list-item-subtitle class="text-caption text-medium-emphasis">
                      #{{ item.raw.matricule }}
                      <v-chip v-if="item.raw.fixed_daily_pay" size="x-small" color="amber-darken-2" variant="flat" class="ml-2 text-white">
                        {{ $t('fixed_pay') }}: {{ formatNumber(item.raw.daily_price) }}
                      </v-chip>
                    </v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-autocomplete>

              <!-- Selected Members Tray (Interactive list to review/remove) -->
              <div v-if="(form.workforce_ids || []).length > 0" class="pa-2 rounded-xl bg-grey-lighten-5 border mb-2">
                <div class="text-caption font-weight-bold text-medium-emphasis mb-1">
                  {{ $t('enrolled_members') || 'Enrolled Members' }} ({{ form.workforce_ids.length }}):
                </div>
                <div class="d-flex flex-wrap ga-1" style="max-height: 120px; overflow-y: auto;">
                  <v-chip
                    v-for="wId in form.workforce_ids"
                    :key="wId"
                    size="x-small"
                    color="#1565C0"
                    variant="tonal"
                    closable
                    @click:close="removeWorkforceFromForm(wId)"
                  >
                    {{ getWorkforceDisplayName(wId) }}
                  </v-chip>
                </div>
              </div>
            </v-col>

            <!-- Description / Notes -->
            <v-col cols="12">
              <v-textarea
                v-model="form.description"
                :label="$t('description')"
                variant="outlined"
                density="compact"
                rounded
                rows="2"
                color="#42A5F5"
                class="text-workforce"
                hide-details
              ></v-textarea>
            </v-col>
          </v-row>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn
            color="#42A5F5"
            variant="flat"
            rounded
            class="text-white px-5 font-weight-bold"
            @click="save"
            :loading="saving"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= VIEW MEMBERS DIALOG ============= -->
    <v-dialog v-model="membersDialog" max-width="640" scrollable>
      <v-card class="rounded-2xl overflow-hidden">
        <div class="tm-dialog-hero">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center ga-3">
              <div class="tm-dialog-icon">
                <v-icon size="24" color="white">mdi-account-group</v-icon>
              </div>
              <div>
                <div class="text-subtitle-1 font-weight-bold text-white">
                  {{ membersTarget?.name }}
                </div>
                <div class="text-caption text-white" style="opacity: .8;">
                  {{ membersTarget?.workforces?.length || 0 }} {{ $t('team_members') }}
                </div>
              </div>
            </div>
            <v-btn icon size="small" variant="text" color="white" @click="membersDialog = false">
              <v-icon size="20">mdi-close</v-icon>
            </v-btn>
          </div>
        </div>

        <v-card-text class="pa-4" style="max-height: 480px;">
          <!-- Chef info -->
          <div v-if="membersTarget?.corporal" class="pa-3 mb-3 rounded-xl d-flex align-center ga-3" style="background: rgba(21, 101, 192, 0.08); border: 1px solid rgba(21, 101, 192, 0.2);">
            <v-avatar size="36" color="#1565C0" class="text-white font-weight-bold">
              {{ getInitials(membersTarget.corporal.full_name) }}
            </v-avatar>
            <div>
              <div class="text-body-2 font-weight-bold">{{ membersTarget.corporal.full_name }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ $t('chef_de_equipe') }} • {{ membersTarget.corporal.cni || $t('no_cni') }}
              </div>
            </div>
            <v-spacer></v-spacer>
            <v-chip size="x-small" color="#1565C0" variant="flat" class="text-white font-weight-bold">
              {{ $t('chef') }}
            </v-chip>
          </div>

          <!-- Members search -->
          <v-text-field
            v-model="memberSearch"
            :placeholder="$t('search_members')"
            density="compact"
            variant="outlined"
            rounded
            prepend-inner-icon="mdi-magnify"
            color="#42A5F5"
            class="mb-3 text-workforce"
            hide-details
            clearable
          ></v-text-field>

          <!-- Members list -->
          <div v-if="filteredMembers.length > 0" class="d-flex flex-column ga-2">
            <div
              v-for="w in filteredMembers"
              :key="w.id"
              class="d-flex align-center ga-3 pa-2 rounded-xl"
              style="border: 1px solid rgba(var(--v-border-color), .12);"
            >
              <v-avatar size="32" color="blue-lighten-4" class="text-blue-darken-3 font-weight-bold text-caption">
                {{ getInitials(w.full_name) }}
              </v-avatar>
              <div>
                <div class="text-body-2 font-weight-medium">{{ w.full_name }}</div>
                <div class="text-caption text-medium-emphasis">#{{ w.matricule }}</div>
              </div>
              <v-spacer></v-spacer>
              <div class="text-right">
                <v-chip v-if="w.fixed_daily_pay" size="x-small" color="amber-darken-2" variant="tonal" class="font-weight-bold">
                  {{ $t('fixed_pay') }}: {{ formatNumber(w.daily_price) }} {{ main.currency || 'DH' }}
                </v-chip>
                <v-chip v-else size="x-small" color="grey" variant="tonal">
                  {{ $t('standard_pay') }}
                </v-chip>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-6 text-medium-emphasis text-caption">
            <v-icon size="36" color="grey" class="mb-2">mdi-account-off-outline</v-icon>
            <div>{{ $t('no_workforces_in_team') }}</div>
          </div>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" rounded @click="membersDialog = false">{{ $t('close') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= CONFIRM DELETE DIALOG ============= -->
    <v-dialog v-model="confirmDeleteDialog" max-width="420">
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
          {{ $t('confirm_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('confirm_delete_team_message') }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="confirmDeleteDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeDelete" :loading="deleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= BULK DELETE CONFIRMATION DIALOG ============= -->
    <v-dialog v-model="bulkDeleteDialog" max-width="450">
      <v-card class="rounded-2xl">
        <v-card-title class="d-flex align-center pa-5 pb-2">
          <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
          {{ $t('bulk_delete') }}
        </v-card-title>
        <v-card-text class="pa-5 pt-2 text-body-1">
          {{ $t('bulk_delete_teams_confirm', { count: selectedIds.length }) }}
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn @click="bulkDeleteDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
          <v-btn color="error" variant="flat" rounded @click="executeBulkDelete" :loading="bulkDeleting">{{ $t('delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============= SUMMARY SNACKBAR ============= -->
    <transition name="tm-snackbar-anim">
      <div v-if="snackbar.show" class="tm-summary-snackbar">
        <div class="tm-snackbar-card">
          <div class="tm-snackbar-accent" style="background: #42A5F5;"></div>
          <div class="tm-snackbar-body">
            <div class="tm-snackbar-header">
              <v-icon :color="snackbar.color" size="20" class="mr-2">{{ snackbar.icon }}</v-icon>
              <span class="tm-snackbar-title">{{ snackbar.title }}</span>
              <v-btn icon size="x-small" variant="text" class="ml-auto tm-snackbar-close" @click="snackbar.show = false">
                <v-icon size="16">mdi-close</v-icon>
              </v-btn>
            </div>
            <div class="tm-snackbar-grid">
              <div v-for="(f, i) in snackbar.fields" :key="i" class="tm-snackbar-field">
                <span class="tm-field-label">{{ f.label }}</span>
                <span class="tm-field-val">
                  <v-icon v-if="f.icon" size="14" class="mr-1" style="opacity:.7">{{ f.icon }}</v-icon>
                  {{ f.value }}
                </span>
              </div>
            </div>
            <div class="tm-snackbar-progress">
              <div class="tm-progress-bar" style="background: #42A5F5;" :style="{ width: snackbarProgress + '%' }"></div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useTeam } from '@/stores/Team';
import { useCorporal } from '@/stores/Corporal';
import { useWorkforce } from '@/stores/Workforce';

export default {
  name: 'TeamManagement',
  data() {
    return {
      windowHeight: typeof window !== 'undefined' ? window.innerHeight : 800,
      loading: false,
      quickSearch: '',
      quickFilter: 'all',
      columnFilters: {},
      dialog: false,
      editing: null,
      form: this.emptyForm(),
      saving: false,
      confirmDeleteDialog: false,
      deleteTarget: null,
      deleting: false,
      bulkDeleteDialog: false,
      bulkDeleting: false,
      selectedIds: [],
      membersDialog: false,
      membersTarget: null,
      memberSearch: '',
      snackbar: {
        show: false,
        title: '',
        color: 'success',
        icon: 'mdi-check-circle',
        fields: [],
      },
      snackbarProgress: 100,
      snackbarTimer: null,
      snackbarInterval: null,
    };
  },
  computed: {
    main() { return useMain(); },
    teamStore() { return useTeam(); },
    corporalStore() { return useCorporal(); },
    workforceStore() { return useWorkforce(); },
    items() { return this.teamStore.list || []; },

    tableHeight() {
      return Math.max(340, this.windowHeight - 275);
    },

    totalAssignedWorkforces() {
      const allWfIds = new Set();
      this.items.forEach(t => {
        if (t.workforces && Array.isArray(t.workforces)) {
          t.workforces.forEach(w => allWfIds.add(w.id));
        }
      });
      return allWfIds.size;
    },
    chefsCount() {
      const set = new Set();
      this.items.forEach(t => { if (t.corporal_id) set.add(t.corporal_id); });
      return set.size;
    },
    activeTeamsCount() {
      return this.items.filter(t => (t.workforces_count || (t.workforces ? t.workforces.length : 0)) > 0).length;
    },
    avgTeamSize() {
      if (!this.items.length) return '0';
      const sum = this.items.reduce((acc, t) => acc + (t.workforces_count || (t.workforces ? t.workforces.length : 0)), 0);
      return (sum / this.items.length).toFixed(1);
    },

    selectedCorporal() {
      if (!this.form.corporal_id) return null;
      return this.corporalStore.list.find(c => c.id === this.form.corporal_id);
    },

    headers() {
      return [
        { title: this.$t('team_name'), key: 'name', align: 'start', minWidth: '220px' },
        { title: this.$t('chef_of_team'), key: 'corporal', align: 'start', minWidth: '220px' },
        { title: this.$t('associated_workforces'), key: 'workforces', align: 'center', minWidth: '180px' },
        { title: this.$t('description'), key: 'description', align: 'start', minWidth: '160px' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false, minWidth: '90px' },
      ];
    },

    filteredItems() {
      let result = this.items;

      if (this.quickFilter === 'active') {
        result = result.filter(t => (t.workforces_count || (t.workforces ? t.workforces.length : 0)) > 0);
      } else if (this.quickFilter === 'empty') {
        result = result.filter(t => (t.workforces_count || (t.workforces ? t.workforces.length : 0)) === 0);
      }

      if (this.quickSearch && this.quickSearch.trim()) {
        const q = this.quickSearch.toLowerCase().trim();
        result = result.filter(item => {
          const matchChef = item.corporal?.full_name && item.corporal.full_name.toLowerCase().includes(q);
          const matchDesc = item.description && item.description.toLowerCase().includes(q);
          const matchMembers = (item.workforces || []).some(w => w.full_name && w.full_name.toLowerCase().includes(q));
          return (
            (item.name && item.name.toLowerCase().includes(q)) ||
            matchChef ||
            matchDesc ||
            matchMembers
          );
        });
      }

      return result.filter(item => {
        return Object.entries(this.columnFilters).every(([key, selected]) => {
          if (!selected || !selected.length) return true;
          return selected.includes(this.getCellDisplayValue(key, item));
        });
      });
    },

    filteredMembers() {
      if (!this.membersTarget || !this.membersTarget.workforces) return [];
      if (!this.memberSearch || !this.memberSearch.trim()) return this.membersTarget.workforces;
      const q = this.memberSearch.toLowerCase().trim();
      return this.membersTarget.workforces.filter(w =>
        (w.full_name && w.full_name.toLowerCase().includes(q)) ||
        (w.matricule && String(w.matricule).toLowerCase().includes(q))
      );
    },

    hasActiveColumnFilters() {
      return Object.values(this.columnFilters).some(f => f && f.length > 0);
    },
    selectAll() {
      return this.selectedIds.length === this.filteredItems.length && this.filteredItems.length > 0;
    },
    someSelected() {
      return this.selectedIds.length > 0;
    },
  },

  mounted() {
    this.fetchData();
    window.addEventListener('resize', this.onResize);
  },
  unmounted() {
    window.removeEventListener('resize', this.onResize);
    this.clearSnackbarTimers();
  },

  methods: {
    onResize() {
      this.windowHeight = window.innerHeight;
    },
    formatNumber(val) {
      if (!val && val !== 0) return '0.00';
      return parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    getInitials(name) {
      if (!name) return 'TM';
      const parts = name.trim().split(/\s+/);
      if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
      return name.slice(0, 2).toUpperCase();
    },
    emptyForm() {
      return {
        name: '',
        corporal_id: null,
        description: '',
        workforce_ids: [],
      };
    },
    workforceItemTitle(item) {
      if (!item) return '';
      return `${item.full_name} (${item.matricule})`;
    },
    getWorkforceDisplayName(id) {
      const w = this.workforceStore.list.find(x => x.id === id);
      return w ? `${w.full_name} (#${w.matricule})` : `#${id}`;
    },
    removeWorkforceFromForm(id) {
      this.form.workforce_ids = (this.form.workforce_ids || []).filter(x => x !== id);
    },
    selectAllWorkforces() {
      this.form.workforce_ids = this.workforceStore.list.map(w => w.id);
    },
    getWorkforceCountColor(count) {
      if (count === 0) return 'grey';
      if (count < 5) return 'blue';
      if (count < 15) return 'teal';
      return 'purple';
    },
    getCellDisplayValue(key, item) {
      if (key === 'corporal') return item.corporal ? item.corporal.full_name : '-';
      if (key === 'workforces') return String(item.workforces_count || (item.workforces ? item.workforces.length : 0));
      return String(item[key] ?? '');
    },
    getColumnOptions(key) {
      const set = new Set();
      this.items.forEach(item => set.add(this.getCellDisplayValue(key, item)));
      return [...set].sort();
    },
    clearAllFilters() {
      this.columnFilters = {};
      this.quickSearch = '';
      this.quickFilter = 'all';
    },
    toggleSelectAll(v) {
      this.selectedIds = v ? this.filteredItems.map(i => i.id) : [];
    },
    async fetchData() {
      this.loading = true;
      await Promise.all([
        this.teamStore.fetch(),
        this.corporalStore.fetch(),
        this.workforceStore.fetch(),
      ]);
      this.loading = false;
    },
    openDialog(item) {
      this.editing = item;
      if (item) {
        this.form = {
          name: item.name || '',
          corporal_id: item.corporal_id || null,
          description: item.description || '',
          workforce_ids: item.workforces ? item.workforces.map(w => w.id) : [],
        };
      } else {
        this.form = this.emptyForm();
      }
      this.dialog = true;
    },
    openMembersDialog(item) {
      this.membersTarget = item;
      this.memberSearch = '';
      this.membersDialog = true;
    },
    async save() {
      if (!this.form.name || !this.form.corporal_id) return;
      this.saving = true;
      try {
        let team;
        if (this.editing) {
          team = await this.teamStore.update(this.editing.id, this.form);
          this.triggerSummarySnackbar({
            title: this.$t('team_updated') || 'Team Updated',
            icon: 'mdi-check-circle',
            color: 'success',
            fields: [
              { label: this.$t('team_name'), value: team.name, icon: 'mdi-account-group' },
              { label: this.$t('chef_of_team'), value: team.corporal?.full_name || '', icon: 'mdi-account-tie' },
              { label: this.$t('workforces'), value: (team.workforces || []).length, icon: 'mdi-account-hard-hat' },
            ],
          });
        } else {
          team = await this.teamStore.create(this.form);
          this.triggerSummarySnackbar({
            title: this.$t('team_created') || 'Team Created',
            icon: 'mdi-account-multiple-check',
            color: 'success',
            fields: [
              { label: this.$t('team_name'), value: team.name, icon: 'mdi-account-group' },
              { label: this.$t('chef_of_team'), value: team.corporal?.full_name || '', icon: 'mdi-account-tie' },
              { label: this.$t('workforces'), value: (team.workforces || []).length, icon: 'mdi-account-hard-hat' },
            ],
          });
        }
        this.dialog = false;
        await this.teamStore.fetch();
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('team_save_error') || 'Error saving team', color: 'error' });
      } finally {
        this.saving = false;
      }
    },
    confirmDelete(item) {
      this.deleteTarget = item;
      this.confirmDeleteDialog = true;
    },
    async executeDelete() {
      if (!this.deleteTarget) return;
      this.deleting = true;
      const target = { ...this.deleteTarget };
      try {
        await this.teamStore.remove(target.id);
        this.confirmDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('team_deleted') || 'Team Deleted',
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('team'), value: target.name, icon: 'mdi-account-group' },
          ],
        });
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('team_delete_error') || 'Error deleting team', color: 'error' });
      } finally {
        this.deleting = false;
        this.deleteTarget = null;
      }
    },
    async executeBulkDelete() {
      if (!this.selectedIds.length) return;
      this.bulkDeleting = true;
      const count = this.selectedIds.length;
      try {
        await this.teamStore.bulkRemove(this.selectedIds);
        this.bulkDeleteDialog = false;
        this.triggerSummarySnackbar({
          title: this.$t('bulk_deleted_success', { count, model: this.$t('teams') || 'teams' }) || `${count} teams deleted`,
          icon: 'mdi-delete-sweep',
          color: 'error',
          fields: [
            { label: this.$t('deleted_count'), value: count, icon: 'mdi-delete-sweep' },
          ],
        });
        this.selectedIds = [];
      } catch (e) {
        console.error(e);
        this.main.add_snackbar({ show: true, message: this.$t('bulk_delete_error') || 'Error in bulk deletion', color: 'error' });
      } finally {
        this.bulkDeleting = false;
      }
    },

    triggerSummarySnackbar({ title, color = 'success', icon = 'mdi-check-circle', fields = [] }) {
      this.clearSnackbarTimers();
      this.snackbar = { show: true, title, color, icon, fields };
      this.snackbarProgress = 100;

      const duration = 4000;
      const step = 50;
      const decrement = (step / duration) * 100;

      this.snackbarInterval = setInterval(() => {
        this.snackbarProgress = Math.max(0, this.snackbarProgress - decrement);
      }, step);

      this.snackbarTimer = setTimeout(() => {
        this.snackbar.show = false;
        this.clearSnackbarTimers();
      }, duration);
    },
    clearSnackbarTimers() {
      if (this.snackbarTimer) { clearTimeout(this.snackbarTimer); this.snackbarTimer = null; }
      if (this.snackbarInterval) { clearInterval(this.snackbarInterval); this.snackbarInterval = null; }
    },
  },
};
</script>

<style scoped>
.tm-page-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.tm-card {
  border-radius: 20px !important;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  background: rgb(var(--v-theme-surface));
}
.tm-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  flex-shrink: 0;
}
.tm-hero-inner {
  position: relative;
  z-index: 1;
  padding: 16px 24px;
  color: #fff;
}
.tm-hero-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
  flex-shrink: 0;
}
.tm-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.3px;
}
.tm-kpi-chip {
  padding: 4px 12px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: inline-flex;
  align-items: center;
  color: #fff;
  font-size: 12px;
}
.tm-kpi-num {
  font-weight: 800;
  margin-right: 4px;
}
.tm-kpi-lbl {
  opacity: 0.85;
  font-size: 11px;
}

.tm-card-body {
  flex: 1 1 0%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.tm-search-input :deep(.v-field) {
  border-radius: 24px !important;
}
.tm-filter-toggle {
  border-color: rgba(66, 165, 245, 0.3) !important;
}

.tm-table-wrap {
  flex: 1 1 0%;
  min-height: 0;
  width: 100%;
  overflow-x: auto;
  border-radius: 12px;
}
.tm-table {
  width: 100%;
  border-color: rgba(var(--v-border-color), 0.12) !important;
}

.tm-avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: rgba(66, 165, 245, 0.12);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid rgba(66, 165, 245, 0.25);
}

.corporal-badge {
  background: rgba(21, 101, 192, 0.06);
  border: 1px solid rgba(21, 101, 192, 0.15);
}

.tm-th {
  padding: 8px 6px 4px !important;
  background: rgba(var(--v-theme-surface-variant), 0.04);
}
.tm-th-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  opacity: 0.75;
  text-align: center;
  white-space: nowrap;
}
.tm-filter-row th {
  padding: 0 4px 6px !important;
}
.tm-filter-th {
  vertical-align: top;
}
.tm-col-filter {
  min-width: 80px;
}
.tm-col-filter :deep(.v-field) {
  font-size: 11px !important;
  min-height: 28px !important;
  border-radius: 8px !important;
}
.tm-col-filter :deep(.v-field__input) {
  padding: 2px 6px !important;
  min-height: 24px !important;
  font-size: 11px;
}
.tm-col-filter :deep(.v-field__clearable) {
  font-size: 13px;
}
.tm-col-filter :deep(.v-chip) {
  height: 18px !important;
  font-size: 10px !important;
}

/* Dialog styling */
.tm-dialog-hero {
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  padding: 16px 20px;
  display: flex;
  align-items: center;
}
.tm-dialog-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.tm-preview-card {
  background: linear-gradient(135deg, rgba(21, 101, 192, 0.03) 0%, rgba(66, 165, 245, 0.08) 100%);
  border-color: rgba(66, 165, 245, 0.3) !important;
}

/* Summary Snackbar */
.tm-summary-snackbar {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  max-width: 380px;
  width: calc(100vw - 48px);
}
.tm-snackbar-card {
  display: flex;
  background: rgb(var(--v-theme-surface));
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18), 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  overflow: hidden;
}
.tm-snackbar-accent {
  width: 5px;
  flex-shrink: 0;
}
.tm-snackbar-body {
  padding: 12px 14px;
  flex: 1;
  min-width: 0;
}
.tm-snackbar-header {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}
.tm-snackbar-title {
  font-size: 13px;
  font-weight: 700;
}
.tm-snackbar-close {
  opacity: 0.6;
}
.tm-snackbar-close:hover {
  opacity: 1;
}
.tm-snackbar-grid {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-bottom: 6px;
}
.tm-snackbar-field {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
}
.tm-field-label {
  color: rgba(var(--v-theme-on-surface), 0.65);
}
.tm-field-val {
  font-weight: 700;
  display: inline-flex;
  align-items: center;
}
.tm-snackbar-progress {
  height: 3px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 2px;
  overflow: hidden;
}
.tm-progress-bar {
  height: 100%;
  transition: width 0.05s linear;
}

.tm-snackbar-anim-enter-active,
.tm-snackbar-anim-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.tm-snackbar-anim-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
.tm-snackbar-anim-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}
</style>
