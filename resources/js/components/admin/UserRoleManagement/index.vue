<template>
<v-card class="urm-card" elevation="0">
  <!-- Hero Header -->
  <div class="urm-hero">
    <div class="urm-hero-inner">
      <div class="d-flex align-center ga-4">
        <div class="urm-hero-icon"><v-icon size="28" color="white">mdi-shield-account</v-icon></div>
        <div>
          <div class="urm-hero-title">{{ $t('user_role_management') }}</div>
          <div class="text-white text-caption" style="opacity:.75">{{ $t('user_role_management_desc') }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Overview Strip -->
  <div class="urm-kpi-row mx-4 mt-3 mb-1 d-flex flex-wrap ga-3">
    <div class="urm-kpi-tile">
      <div class="urm-kpi-icon-pill" style="background: rgba(173, 20, 87, 0.12); color: #AD1457;">
        <v-icon size="20">mdi-account-multiple</v-icon>
      </div>
      <div class="urm-kpi-info">
        <span class="urm-kpi-label">{{ $t('user_management_kpi_users') }}</span>
        <span class="urm-kpi-val">{{ users.length }}</span>
      </div>
    </div>

    <div class="urm-kpi-tile">
      <div class="urm-kpi-icon-pill" style="background: rgba(46, 125, 50, 0.12); color: #2E7D32;">
        <v-icon size="20">mdi-account-check</v-icon>
      </div>
      <div class="urm-kpi-info">
        <span class="urm-kpi-label">{{ $t('user_management_kpi_active') }}</span>
        <span class="urm-kpi-val">{{ activeUsersCount }}</span>
      </div>
    </div>

    <div class="urm-kpi-tile">
      <div class="urm-kpi-icon-pill" style="background: rgba(21, 101, 192, 0.12); color: #1565C0;">
        <v-icon size="20">mdi-shield-lock-outline</v-icon>
      </div>
      <div class="urm-kpi-info">
        <span class="urm-kpi-label">{{ $t('user_management_kpi_roles') }}</span>
        <span class="urm-kpi-val">{{ roles.length }}</span>
      </div>
    </div>

    <div class="urm-kpi-tile">
      <div class="urm-kpi-icon-pill" style="background: rgba(230, 81, 0, 0.12); color: #E65100;">
        <v-icon size="20">mdi-crown</v-icon>
      </div>
      <div class="urm-kpi-info">
        <span class="urm-kpi-label">{{ $t('user_management_kpi_super') }}</span>
        <span class="urm-kpi-val">{{ superAdminRolesCount }}</span>
      </div>
    </div>
  </div>

  <!-- ============================================================
       Period Toggle + Filter Chips + Search Toolbar
       ============================================================ -->
  <div class="urm-toolbar-card mx-4 mt-2 mb-2 pa-2 rounded-xl border d-flex flex-wrap align-center justify-space-between ga-3">
    <!-- Left Section: Entity Pill Toggle + Dividers + Status Chips -->
    <div class="d-flex flex-wrap align-center ga-3">
      <SegmentedPillToggle
        v-model="activeTab"
        :items="[
          { value: 0, label: $t('users'), icon: 'mdi-account-multiple', badge: users.length },
          { value: 1, label: $t('roles'), icon: 'mdi-shield-lock-outline', badge: roles.length }
        ]"
        color="#AD1457"
      />

      <v-divider vertical class="mx-1 d-none d-sm-block" style="height:28px;"></v-divider>

      <!-- Status Chips for Users -->
      <div v-if="activeTab === 0" class="d-flex align-center ga-2">
        <v-chip
          :variant="userStatusFilter === 'all' ? 'flat' : 'outlined'"
          color="#AD1457"
          size="small"
          class="cursor-pointer font-weight-medium"
          @click="userStatusFilter = 'all'"
        >
          {{ $t('all') }}
        </v-chip>
        <v-chip
          :variant="userStatusFilter === 'active' ? 'flat' : 'outlined'"
          color="#2E7D32"
          size="small"
          class="cursor-pointer font-weight-medium"
          @click="userStatusFilter = userStatusFilter === 'active' ? 'all' : 'active'"
        >
          <v-icon start size="14">{{ userStatusFilter === 'active' ? 'mdi-check-circle' : 'mdi-checkbox-blank-circle-outline' }}</v-icon>
          {{ $t('active') }}
        </v-chip>
        <v-chip
          :variant="userStatusFilter === 'disabled' ? 'flat' : 'outlined'"
          color="#E65100"
          size="small"
          class="cursor-pointer font-weight-medium"
          @click="userStatusFilter = userStatusFilter === 'disabled' ? 'all' : 'disabled'"
        >
          <v-icon start size="14">{{ userStatusFilter === 'disabled' ? 'mdi-close-circle' : 'mdi-checkbox-blank-circle-outline' }}</v-icon>
          {{ $t('disabled') }}
        </v-chip>
      </div>

      <!-- Filter Chips for Roles -->
      <div v-else-if="activeTab === 1" class="d-flex align-center ga-2">
        <v-chip
          :variant="roleFilter === 'all' ? 'flat' : 'outlined'"
          color="#AD1457"
          size="small"
          class="cursor-pointer font-weight-medium"
          @click="roleFilter = 'all'"
        >
          {{ $t('all') }}
        </v-chip>
        <v-chip
          :variant="roleFilter === 'super_admin' ? 'flat' : 'outlined'"
          color="#AD1457"
          size="small"
          class="cursor-pointer font-weight-medium"
          @click="roleFilter = roleFilter === 'super_admin' ? 'all' : 'super_admin'"
        >
          <v-icon start size="14">mdi-crown</v-icon>
          {{ $t('super_admin') }}
        </v-chip>
      </div>
    </div>

    <!-- Right Section: Search Toolbar (AddCharge style) + Action Button -->
    <div class="d-flex align-center ga-2 flex-grow-1 flex-sm-grow-0 justify-end">
      <div class="urm-search-wrap">
        <v-icon size="16" class="urm-search-icon">mdi-magnify</v-icon>
        <input
          v-model="searchQuery"
          type="text"
          class="urm-search-input"
          :placeholder="activeTab === 0 ? $t('search_users') : $t('search_roles')"
          spellcheck="false"
        />
        <v-btn
          v-if="searchQuery"
          icon
          size="20"
          variant="text"
          class="urm-search-clear"
          @click="searchQuery = ''"
        >
          <v-icon size="12">mdi-close</v-icon>
        </v-btn>
      </div>

      <v-btn
        v-if="activeTab === 0"
        color="#AD1457"
        variant="flat"
        rounded
        class="urm-add-btn"
        prepend-icon="mdi-account-plus"
        @click="openUserDialog(null)"
      >
        {{ $t('add_user') }}
      </v-btn>
      <v-btn
        v-else
        color="#AD1457"
        variant="flat"
        rounded
        class="urm-add-btn"
        prepend-icon="mdi-shield-plus"
        @click="openRoleDialog(null)"
      >
        {{ $t('add_role') }}
      </v-btn>
    </div>
  </div>

  <v-divider class="mx-4 mt-1" color="#AD1457" style="opacity: 0.35;"></v-divider>

  <!-- ==================== TAB 0: USERS ==================== -->
  <v-card-text v-if="activeTab === 0" class="pa-4">
    <v-data-table
      :headers="userHeaders"
      :items="filteredUsers"
      :loading="loadingUsers"
      density="comfortable"
      class="rounded-lg border"
      hover
    >
      <template v-slot:[`item.name`]="{ item }">
        <div class="d-flex align-center ga-2 py-1">
          <v-avatar size="32" color="rgba(173, 20, 87, 0.12)">
            <span class="text-caption font-weight-bold" style="color: #AD1457;">
              {{ (item.name || 'U').charAt(0).toUpperCase() }}
            </span>
          </v-avatar>
          <div class="font-weight-medium">{{ item.name }}</div>
        </div>
      </template>

      <template v-slot:[`item.role`]="{ item }">
        <v-chip :color="item.role?.is_super_admin ? '#AD1457' : 'indigo'" size="small" variant="tonal" class="font-weight-medium">
          <v-icon v-if="item.role?.is_super_admin" size="14" class="mr-1">mdi-crown</v-icon>
          <v-icon v-else size="14" class="mr-1">mdi-shield-outline</v-icon>
          {{ item.role?.name || '-' }}
        </v-chip>
      </template>

      <template v-slot:[`item.is_active`]="{ item }">
        <v-chip :color="item.is_active ? 'success' : 'error'" size="small" variant="tonal" :prepend-icon="item.is_active ? 'mdi-check-circle' : 'mdi-close-circle'">
          {{ item.is_active ? $t('active') : $t('disabled') }}
        </v-chip>
      </template>

      <template v-slot:[`item.created_at`]="{ item }">
        <span class="text-caption text-medium-emphasis">
          {{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}
        </span>
      </template>

      <template v-slot:[`item.actions`]="{ item }">
        <v-btn icon size="x-small" variant="tonal" color="#AD1457" class="mr-1" @click="openUserDialog(item)">
          <v-icon size="14">mdi-pencil</v-icon>
        </v-btn>
        <v-tooltip :text="item.is_active ? $t('disable_user') : $t('enable_user')" location="top">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              icon
              size="x-small"
              variant="tonal"
              :color="item.is_active ? 'warning' : 'success'"
              @click="toggleUserActive(item)"
              :disabled="item.id === auth.user?.id"
              :loading="togglingUserId === item.id"
            >
              <v-icon size="14">{{ item.is_active ? 'mdi-account-lock' : 'mdi-account-check' }}</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
      </template>
    </v-data-table>
  </v-card-text>

  <!-- ==================== TAB 1: ROLES ==================== -->
  <v-card-text v-else-if="activeTab === 1" class="pa-4">
    <v-data-table
      :headers="roleHeaders"
      :items="filteredRoles"
      :loading="loadingRoles"
      density="comfortable"
      class="rounded-lg border"
      hover
    >
      <template v-slot:[`item.name`]="{ item }">
        <div class="d-flex align-center ga-2 py-1">
          <v-avatar size="32" :color="item.is_super_admin ? 'rgba(173, 20, 87, 0.15)' : 'rgba(21, 101, 192, 0.12)'">
            <v-icon size="17" :color="item.is_super_admin ? '#AD1457' : '#1565C0'">
              {{ item.is_super_admin ? 'mdi-crown' : 'mdi-shield-lock-outline' }}
            </v-icon>
          </v-avatar>
          <span class="font-weight-bold">{{ item.name }}</span>
        </div>
      </template>

      <template v-slot:[`item.is_super_admin`]="{ item }">
        <v-chip v-if="item.is_super_admin" color="#AD1457" size="small" variant="tonal" prepend-icon="mdi-crown" class="font-weight-bold">
          {{ $t('super_admin') }}
        </v-chip>
        <span v-else class="text-grey">-</span>
      </template>

      <template v-slot:[`item.permissions_count`]="{ item }">
        <v-chip size="small" :color="item.is_super_admin ? '#AD1457' : '#1565C0'" variant="tonal" class="font-weight-medium">
          <v-icon start size="14">{{ item.is_super_admin ? 'mdi-all-inclusive' : 'mdi-key-chain' }}</v-icon>
          {{ item.is_super_admin ? $t('all_permissions') : (item.permissions?.length || 0) + ' / ' + totalSystemPermissionsCount }}
        </v-chip>
      </template>

      <template v-slot:[`item.users_count`]="{ item }">
        <v-chip size="small" color="info" variant="tonal" prepend-icon="mdi-account-multiple">
          {{ item.users_count || 0 }}
        </v-chip>
      </template>

      <template v-slot:[`item.actions`]="{ item }">
        <v-btn icon size="x-small" variant="tonal" color="#AD1457" class="mr-1" @click="openRoleDialog(item)">
          <v-icon size="14">mdi-pencil</v-icon>
        </v-btn>
        <v-btn
          icon
          size="x-small"
          variant="tonal"
          color="error"
          @click="confirmDeleteRole(item)"
          :disabled="item.users_count > 0 || item.is_super_admin"
        >
          <v-icon size="14">mdi-delete</v-icon>
        </v-btn>
      </template>
    </v-data-table>
  </v-card-text>
</v-card>

<!-- ==================== USER DIALOG ==================== -->
<v-dialog v-model="userDialog" max-width="560" persistent>
  <v-card class="rounded-xl">
    <div class="urm-dialog-header">
      <v-icon size="22" color="white" class="mr-2">{{ editingUser ? 'mdi-account-edit' : 'mdi-account-plus' }}</v-icon>
      {{ editingUser ? $t('edit_user') : $t('add_user') }}
      <v-spacer></v-spacer>
      <v-btn icon variant="text" color="white" size="small" @click="userDialog = false"><v-icon>mdi-close</v-icon></v-btn>
    </div>
    <v-card-text class="pa-5">
      <v-row dense>
        <v-col cols="12">
          <v-text-field
            v-model="userForm.name"
            :label="$t('name')"
            variant="outlined"
            density="compact"
            rounded
            color="#AD1457"
            class="text-urm"
            prepend-inner-icon="mdi-account"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" class="mt-2">
          <v-text-field
            v-model="userForm.email"
            :label="$t('email')"
            type="email"
            variant="outlined"
            density="compact"
            rounded
            color="#AD1457"
            class="text-urm"
            prepend-inner-icon="mdi-email"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" class="mt-2">
          <v-text-field
            v-model="userForm.password"
            :label="editingUser ? $t('new_password') + ' (' + $t('optional') + ')' : $t('password')"
            type="password"
            variant="outlined"
            density="compact"
            rounded
            color="#AD1457"
            class="text-urm"
            prepend-inner-icon="mdi-lock"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" class="mt-2">
          <v-select
            v-model="userForm.role_id"
            :items="roles"
            item-title="name"
            item-value="id"
            :label="$t('role')"
            variant="outlined"
            density="compact"
            rounded
            color="#AD1457"
            class="text-urm"
            prepend-inner-icon="mdi-shield-account"
            hide-details
          >
            <template #item="{ item, props: p }">
              <v-list-item v-bind="p">
                <template #prepend>
                  <v-icon v-if="item.raw.is_super_admin" color="#AD1457" size="18">mdi-crown</v-icon>
                  <v-icon v-else size="18" color="#1565C0">mdi-shield-outline</v-icon>
                </template>
              </v-list-item>
            </template>
          </v-select>
        </v-col>
      </v-row>
    </v-card-text>
    <v-card-actions class="pa-5 pt-0">
      <v-spacer></v-spacer>
      <v-btn @click="userDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
      <v-btn color="#AD1457" variant="flat" rounded @click="saveUser" :loading="savingUser" :disabled="!userFormValid" class="urm-action-btn">
        {{ $t('save') }}
      </v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>

<!-- ==================== ROLE DIALOG (GRANULAR MODULE PERMISSIONS) ==================== -->
<v-dialog v-model="roleDialog" max-width="880" persistent scrollable>
  <v-card class="rounded-xl">
    <div class="urm-dialog-header">
      <v-icon size="22" color="white" class="mr-2">{{ editingRole ? 'mdi-shield-edit' : 'mdi-shield-plus' }}</v-icon>
      {{ editingRole ? $t('edit_role') : $t('add_role') }}
      <v-spacer></v-spacer>
      <v-btn icon variant="text" color="white" size="small" @click="roleDialog = false"><v-icon>mdi-close</v-icon></v-btn>
    </div>

    <v-card-text class="pa-5" style="max-height: 70vh; overflow-y: auto;">
      <!-- Role Basic Info Form -->
      <v-row dense class="mb-2">
        <v-col cols="12" sm="7">
          <v-text-field
            v-model="roleForm.name"
            :label="$t('role_name')"
            variant="outlined"
            density="compact"
            rounded
            color="#AD1457"
            class="text-urm"
            prepend-inner-icon="mdi-tag"
            hide-details
          ></v-text-field>
        </v-col>
        <v-col cols="12" sm="5" class="d-flex align-center justify-end">
          <v-switch
            v-model="roleForm.is_super_admin"
            :label="$t('super_admin')"
            color="#AD1457"
            hide-details
            density="compact"
            inset
            :disabled="!auth.isSuperAdmin"
          ></v-switch>
        </v-col>
      </v-row>

      <v-divider class="my-3" color="#AD1457" style="opacity: 0.25;"></v-divider>

      <!-- Super Admin Callout -->
      <div v-if="roleForm.is_super_admin" class="text-center pa-5 rounded-xl mb-3" style="background: rgba(173,20,87,.06); border: 1.5px solid rgba(173,20,87,.2);">
        <v-icon size="44" color="#AD1457">mdi-crown</v-icon>
        <div class="text-subtitle-1 font-weight-bold mt-2" style="color: #AD1457;">{{ $t('super_admin') }}</div>
        <div class="text-body-2 font-weight-medium text-medium-emphasis mt-1">{{ $t('super_admin_all_permissions') }}</div>
      </div>

      <!-- Granular Permission Matrix -->
      <div v-else>
        <!-- Permissions Toolbar -->
        <div class="d-flex flex-wrap align-center justify-space-between ga-2 mb-3 pa-2 rounded-lg" style="background: rgba(var(--v-theme-surface), 0.7); border: 1px solid rgba(var(--v-theme-on-surface), 0.08);">
          <div class="d-flex align-center ga-2">
            <v-icon color="#AD1457" size="20">mdi-key-chain</v-icon>
            <span class="text-subtitle-2 font-weight-bold">{{ $t('permissions') }}</span>
            <v-chip size="small" color="#AD1457" variant="tonal" class="font-weight-bold">
              {{ roleForm.permissions.length }} / {{ totalSystemPermissionsCount }}
            </v-chip>
          </div>

          <div class="d-flex align-center ga-2 flex-grow-1 flex-sm-grow-0 justify-end">
            <!-- Search permissions -->
            <div class="urm-search-wrap" style="min-width: 190px; padding: 3px 10px;">
              <v-icon size="14" class="urm-search-icon">mdi-magnify</v-icon>
              <input
                v-model="permissionSearch"
                type="text"
                class="urm-search-input text-caption"
                :placeholder="$t('search_permissions')"
                spellcheck="false"
              />
              <v-btn v-if="permissionSearch" icon size="16" variant="text" class="urm-search-clear" @click="permissionSearch = ''">
                <v-icon size="10">mdi-close</v-icon>
              </v-btn>
            </div>

            <v-btn size="x-small" variant="tonal" color="#AD1457" rounded @click="selectAllPermissions" class="font-weight-medium">
              {{ $t('select_all') }}
            </v-btn>
            <v-btn size="x-small" variant="tonal" color="grey" rounded @click="clearAllPermissions" class="font-weight-medium">
              {{ $t('clear_all') }}
            </v-btn>
          </div>
        </div>

        <!-- Groups Cards Loop -->
        <div
          v-for="(perms, group) in filteredPermissionGroups"
          :key="group"
          class="perm-group-card mb-3 rounded-xl border"
          :style="{ borderLeft: '4px solid ' + getGroupColor(group) + ' !important' }"
        >
          <!-- Group Header -->
          <div class="perm-group-header d-flex align-center justify-space-between pa-3" :style="{ background: hexToRgba(getGroupColor(group), 0.05) }">
            <div class="d-flex align-center ga-2 flex-grow-1">
              <v-checkbox
                :model-value="isGroupFullySelected(group)"
                :indeterminate="isGroupPartiallySelected(group)"
                @update:model-value="toggleGroup(group, $event)"
                :color="getGroupColor(group)"
                hide-details
                density="compact"
                class="flex-shrink-0"
              ></v-checkbox>

              <div class="perm-group-icon-pill" :style="{ background: hexToRgba(getGroupColor(group), 0.14), color: getGroupColor(group) }">
                <v-icon size="18">{{ getGroupIcon(group) }}</v-icon>
              </div>

              <div>
                <span class="text-subtitle-2 font-weight-bold">{{ $t('perm_group_' + group) }}</span>
              </div>
            </div>

            <div class="d-flex align-center ga-2">
              <v-chip size="x-small" :color="isGroupFullySelected(group) ? getGroupColor(group) : 'grey'" variant="tonal" class="font-weight-bold">
                {{ getGroupSelectedCount(group) }} / {{ perms.length }}
              </v-chip>
            </div>
          </div>

          <!-- Group Body: Master Banner + Sub-permissions Grid -->
          <div class="perm-group-body pa-3">
            <!-- Master Permission Callout (if module has master key) -->
            <div
              v-if="getMasterKeyForGroup(group)"
              class="perm-master-banner mb-3 pa-2 rounded-lg d-flex align-center justify-space-between"
              :class="{ 'master-banner-active': roleForm.permissions.includes(getMasterKeyForGroup(group)) }"
            >
              <div class="d-flex align-center ga-2">
                <v-icon size="18" color="amber-darken-3">mdi-shield-crown-outline</v-icon>
                <v-checkbox
                  v-model="roleForm.permissions"
                  :value="getMasterKeyForGroup(group)"
                  :color="getGroupColor(group)"
                  hide-details
                  density="compact"
                  class="font-weight-bold text-caption"
                >
                  <template #label>
                    <span class="font-weight-bold" style="font-size: 0.85rem;">
                      {{ $t('perm_' + getMasterKeyForGroup(group).replace(/\./g, '_')) }}
                    </span>
                  </template>
                </v-checkbox>
                <v-chip size="x-small" color="amber-darken-3" variant="flat" class="text-white font-weight-bold ml-1">
                  {{ $t('master_permission') }}
                </v-chip>
              </div>
              <span class="text-caption text-medium-emphasis d-none d-sm-inline">
                {{ $t('master_permission_hint') }}
              </span>
            </div>

            <!-- Granular Permissions Grid -->
            <div class="perm-tiles-grid">
              <div
                v-for="perm in getNonMasterPerms(group, perms)"
                :key="perm"
                class="perm-tile rounded-lg border pa-2"
                :class="{
                  'perm-tile-selected': roleForm.permissions.includes(perm),
                  'perm-tile-mastered': isCoveredByMaster(group)
                }"
              >
                <v-checkbox
                  v-model="roleForm.permissions"
                  :value="perm"
                  :color="getGroupColor(group)"
                  hide-details
                  density="compact"
                  :disabled="isCoveredByMaster(group)"
                >
                  <template #label>
                    <span class="perm-tile-label">{{ $t('perm_' + perm.replace(/\./g, '_')) }}</span>
                  </template>
                </v-checkbox>
              </div>
            </div>
          </div>
        </div>
      </div>
    </v-card-text>

    <v-card-actions class="pa-5 pt-2">
      <v-spacer></v-spacer>
      <v-btn @click="roleDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
      <v-btn color="#AD1457" variant="flat" rounded @click="saveRole" :loading="savingRole" :disabled="!roleForm.name" class="urm-action-btn">
        {{ $t('save') }}
      </v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>

<!-- Delete Role Confirmation Dialog -->
<v-dialog v-model="deleteRoleDialog" max-width="420">
  <v-card class="rounded-xl">
    <v-card-title class="pa-5 pb-2 d-flex align-center">
      <v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>
      {{ $t('confirm_delete') }}
    </v-card-title>
    <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_role_message') }}</v-card-text>
    <v-card-actions class="pa-5 pt-0">
      <v-spacer></v-spacer>
      <v-btn @click="deleteRoleDialog = false" variant="text" rounded>{{ $t('cancel') }}</v-btn>
      <v-btn color="error" variant="flat" rounded @click="execDeleteRole" :loading="deleting">{{ $t('delete') }}</v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>

<!-- ============================================================
     Summary Snackbar with Countdown Progress Bar (Rule 5)
     ============================================================ -->
<v-snackbar
  v-model="summarySnackbar.show"
  :timeout="7000"
  location="top right"
  color="surface"
  rounded="lg"
  elevation="12"
  min-width="360"
  max-width="440"
  @update:modelValue="val => { if (!val) clearInterval(snackbarTimer); }"
>
  <v-progress-linear :model-value="snackbarProgress" color="#AD1457" height="3" class="mb-2" rounded></v-progress-linear>
  <div class="d-flex align-center justify-space-between mb-2">
    <div class="d-flex align-center">
      <v-icon :color="summarySnackbar.type === 'error' ? 'error' : '#AD1457'" class="mr-2">
        {{ summarySnackbar.icon || 'mdi-check-circle' }}
      </v-icon>
      <span class="text-subtitle-2 font-weight-bold">{{ summarySnackbar.title }}</span>
    </div>
    <v-chip v-if="summarySnackbar.isRemote" size="x-small" color="info" variant="flat" class="font-weight-bold text-white">
      <v-icon start size="12">mdi-access-point</v-icon>
      {{ $t('realtime_update') }}
    </v-chip>
  </div>
  <v-divider class="mb-2"></v-divider>

  <!-- User Details -->
  <div v-if="summarySnackbar.entity === 'User'" class="d-flex flex-column ga-1 text-body-2">
    <div v-if="summarySnackbar.details.name">
      <v-icon size="14" color="#AD1457" class="mr-1">mdi-account</v-icon>
      <span class="text-medium-emphasis">{{ $t('name') }}:</span>
      <strong>{{ summarySnackbar.details.name }}</strong>
    </div>
    <div v-if="summarySnackbar.details.email">
      <v-icon size="14" color="#AD1457" class="mr-1">mdi-email</v-icon>
      <span class="text-medium-emphasis">{{ $t('email') }}:</span>
      <strong>{{ summarySnackbar.details.email }}</strong>
    </div>
    <div v-if="summarySnackbar.details.role">
      <v-icon size="14" color="#AD1457" class="mr-1">mdi-shield-account</v-icon>
      <span class="text-medium-emphasis">{{ $t('role') }}:</span>
      <strong class="text-urm">{{ summarySnackbar.details.role }}</strong>
    </div>
  </div>

  <!-- Role Details -->
  <div v-else-if="summarySnackbar.entity === 'Role'" class="d-flex flex-column ga-1 text-body-2">
    <div v-if="summarySnackbar.details.name">
      <v-icon size="14" color="#AD1457" class="mr-1">mdi-tag</v-icon>
      <span class="text-medium-emphasis">{{ $t('role_name') }}:</span>
      <strong>{{ summarySnackbar.details.name }}</strong>
    </div>
    <div v-if="summarySnackbar.details.permissionsCount !== undefined">
      <v-icon size="14" color="#AD1457" class="mr-1">mdi-key-chain</v-icon>
      <span class="text-medium-emphasis">{{ $t('permissions') }}:</span>
      <strong>{{ summarySnackbar.details.permissionsCount }}</strong>
    </div>
  </div>

  <!-- Generic message fallback -->
  <div v-else class="text-body-2">
    {{ summarySnackbar.message }}
  </div>

  <template #actions>
    <v-btn variant="text" size="small" icon="mdi-close" @click="summarySnackbar.show = false"></v-btn>
  </template>
</v-snackbar>
</template>

<script>
import axios from 'axios';
import { useAuth } from '@/stores/Auth';
import { useMain } from '@/stores/Main';
import mitt from '@/plugins/mitt';
import SegmentedPillToggle from '@/components/common/SegmentedPillToggle.vue';

export default {
  name: 'UserRoleManagement',
  components: { SegmentedPillToggle },
  data() {
    return {
      activeTab: 0,
      searchQuery: '',
      userStatusFilter: 'all', // 'all', 'active', 'disabled'
      roleFilter: 'all', // 'all', 'super_admin'
      // Users
      users: [],
      loadingUsers: false,
      userDialog: false,
      editingUser: null,
      userForm: { name: '', email: '', password: '', role_id: null },
      savingUser: false,
      togglingUserId: null,
      // Roles
      roles: [],
      loadingRoles: false,
      roleDialog: false,
      editingRole: null,
      roleForm: { name: '', is_super_admin: false, permissions: [] },
      savingRole: false,
      deleteRoleDialog: false,
      deleteRoleId: null,
      deleting: false,
      permissionGroups: {},
      permissionSearch: '',
      // Summary Snackbar with countdown (Rule 5)
      summarySnackbar: {
        show: false,
        title: '',
        message: '',
        type: 'success',
        icon: 'mdi-check-circle',
        entity: '',
        isRemote: false,
        details: {},
      },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    auth() { return useAuth(); },
    main() { return useMain(); },

    activeUsersCount() {
      return (this.users || []).filter(u => u.is_active).length;
    },
    superAdminRolesCount() {
      return (this.roles || []).filter(r => r.is_super_admin).length;
    },

    totalSystemPermissionsCount() {
      const all = Object.values(this.permissionGroups || {}).flat();
      return [...new Set(all)].length;
    },

    filteredPermissionGroups() {
      if (!this.permissionSearch.trim()) return this.permissionGroups;
      const q = this.permissionSearch.toLowerCase();
      const filtered = {};

      Object.entries(this.permissionGroups).forEach(([group, perms]) => {
        const groupMatches = this.$t('perm_group_' + group).toLowerCase().includes(q);
        const matchingPerms = perms.filter(p => {
          const permLabel = this.$t('perm_' + p.replace(/\./g, '_')).toLowerCase();
          return p.toLowerCase().includes(q) || permLabel.includes(q);
        });

        if (groupMatches || matchingPerms.length > 0) {
          filtered[group] = groupMatches ? perms : matchingPerms;
        }
      });

      return filtered;
    },

    filteredUsers() {
      let list = this.users || [];
      if (this.userStatusFilter === 'active') {
        list = list.filter(u => u.is_active);
      } else if (this.userStatusFilter === 'disabled') {
        list = list.filter(u => !u.is_active);
      }
      if (this.searchQuery.trim()) {
        const q = this.searchQuery.toLowerCase();
        list = list.filter(u =>
          (u.name && u.name.toLowerCase().includes(q)) ||
          (u.email && u.email.toLowerCase().includes(q)) ||
          (u.role?.name && u.role.name.toLowerCase().includes(q))
        );
      }
      return list;
    },

    filteredRoles() {
      let list = this.roles || [];
      if (this.roleFilter === 'super_admin') {
        list = list.filter(r => r.is_super_admin);
      }
      if (this.searchQuery.trim()) {
        const q = this.searchQuery.toLowerCase();
        list = list.filter(r =>
          (r.name && r.name.toLowerCase().includes(q))
        );
      }
      return list;
    },

    userHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'start' },
        { title: this.$t('email'), key: 'email', align: 'start' },
        { title: this.$t('role'), key: 'role', align: 'center' },
        { title: this.$t('status'), key: 'is_active', align: 'center' },
        { title: this.$t('created_at'), key: 'created_at', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },

    roleHeaders() {
      return [
        { title: this.$t('role_name'), key: 'name', align: 'start' },
        { title: this.$t('super_admin'), key: 'is_super_admin', align: 'center' },
        { title: this.$t('permissions'), key: 'permissions_count', align: 'center' },
        { title: this.$t('users_count'), key: 'users_count', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },

    userFormValid() {
      if (!this.userForm.name || !this.userForm.email || !this.userForm.role_id) return false;
      if (!this.editingUser && !this.userForm.password) return false;
      return true;
    },
  },
  watch: {
    'roleForm.permissions': {
      handler(newPerms, oldPerms) {
        if (!oldPerms) return;
        // Master-key modules: .manage grants all sub-permissions
        const masterModules = ['settings', 'workforce', 'supplier_invoices', 'client_invoices', 'quality_control', 'users'];
        masterModules.forEach(mod => {
          const groupPerms = this.permissionGroups[mod] || [];
          const subPerms = groupPerms.filter(p => p !== mod + '.manage');
          if (subPerms.length === 0) return;

          const hadMaster = oldPerms.includes(mod + '.manage');
          const hasMaster = newPerms.includes(mod + '.manage');

          // Master was just checked → add all sub-permissions
          if (!hadMaster && hasMaster) {
            subPerms.forEach(p => {
              if (!this.roleForm.permissions.includes(p)) this.roleForm.permissions.push(p);
            });
          }
          // Master was just unchecked → remove all sub-permissions
          else if (hadMaster && !hasMaster) {
            this.roleForm.permissions = this.roleForm.permissions.filter(p => !subPerms.includes(p));
          }
        });
      },
      deep: true,
    },
  },
  mounted() {
    this.fetchUsers();
    this.fetchRoles();
    mitt.on('model-changed', this._onMC);
  },
  beforeUnmount() {
    clearInterval(this.snackbarTimer);
    mitt.off('model-changed', this._onMC);
  },
  methods: {
    _onMC(d) {
      if (d.model === 'User') {
        this.fetchUsers();
        if (d.data?.user && d.data.user.id !== this.auth.user?.id) {
          this.triggerSummarySnackbar({
            title: this.$t('realtime_update'),
            message: `${d.data.user.name} (${d.action})`,
            type: 'info',
            icon: 'mdi-account-sync',
            entity: 'User',
            isRemote: true,
            details: {
              name: d.data.user.name,
              email: d.data.user.email,
              role: d.data.user.role?.name,
            }
          });
        }
      }
      if (d.model === 'Role') {
        this.fetchRoles();
        if (d.data?.role) {
          this.triggerSummarySnackbar({
            title: this.$t('realtime_update'),
            message: `${d.data.role.name} (${d.action})`,
            type: 'info',
            icon: 'mdi-shield-sync',
            entity: 'Role',
            isRemote: true,
            details: {
              name: d.data.role.name,
              permissionsCount: d.data.role.is_super_admin ? this.$t('all_permissions') : (d.data.role.permissions?.length || 0),
            }
          });
        }
      }
    },

    // ============ USERS ============
    async fetchUsers() {
      this.loadingUsers = true;
      try {
        const r = await axios.get('/api/admin/users');
        this.users = r.data.users;
      } catch (e) { console.error(e); }
      this.loadingUsers = false;
    },

    openUserDialog(user) {
      this.editingUser = user;
      if (user) {
        this.userForm = { name: user.name, email: user.email, password: '', role_id: user.role_id };
      } else {
        this.userForm = { name: '', email: '', password: '', role_id: null };
      }
      this.userDialog = true;
    },

    async saveUser() {
      this.savingUser = true;
      try {
        const payload = { ...this.userForm };
        if (this.editingUser && !payload.password) delete payload.password;
        let response;
        if (this.editingUser) {
          response = await axios.put(`/api/admin/users/${this.editingUser.id}`, payload);
          const updated = response.data.user;
          this.triggerSummarySnackbar({
            title: this.$t('user_updated'),
            type: 'success',
            icon: 'mdi-account-check',
            entity: 'User',
            isRemote: false,
            details: {
              name: updated.name,
              email: updated.email,
              role: updated.role?.name,
            }
          });
        } else {
          response = await axios.post('/api/admin/users', payload);
          const created = response.data.user;
          this.triggerSummarySnackbar({
            title: this.$t('user_created'),
            type: 'success',
            icon: 'mdi-account-plus',
            entity: 'User',
            isRemote: false,
            details: {
              name: created.name,
              email: created.email,
              role: created.role?.name,
            }
          });
        }
        this.userDialog = false;
        this.fetchUsers();
        this.fetchRoles();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.triggerSummarySnackbar({
          title: this.$t('error'),
          message: msg,
          type: 'error',
          icon: 'mdi-alert-circle',
          entity: '',
        });
      }
      this.savingUser = false;
    },

    async toggleUserActive(user) {
      this.togglingUserId = user.id;
      try {
        const r = await axios.patch(`/api/admin/users/${user.id}/toggle-active`);
        this.triggerSummarySnackbar({
          title: r.data.message || this.$t('user_saved'),
          type: 'success',
          icon: user.is_active ? 'mdi-account-lock' : 'mdi-account-check',
          entity: 'User',
          isRemote: false,
          details: {
            name: user.name,
            email: user.email,
            role: user.role?.name,
          }
        });
        this.fetchUsers();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.triggerSummarySnackbar({
          title: this.$t('error'),
          message: msg,
          type: 'error',
          icon: 'mdi-alert-circle',
          entity: '',
        });
      }
      this.togglingUserId = null;
    },

    // ============ ROLES ============
    async fetchRoles() {
      this.loadingRoles = true;
      try {
        const r = await axios.get('/api/admin/roles');
        this.roles = r.data.roles;
        this.permissionGroups = r.data.permission_groups || {};
      } catch (e) { console.error(e); }
      this.loadingRoles = false;
    },

    openRoleDialog(role) {
      this.editingRole = role;
      this.permissionSearch = '';
      if (role) {
        const perms = [...(role.permissions || [])];
        // For all master-key modules, expand sub-permissions if master is set
        const masterModules = ['settings', 'workforce', 'supplier_invoices', 'client_invoices', 'quality_control', 'users'];
        masterModules.forEach(mod => {
          if (perms.includes(mod + '.manage')) {
            const subPerms = (this.permissionGroups[mod] || []).filter(p => p !== mod + '.manage');
            subPerms.forEach(p => { if (!perms.includes(p)) perms.push(p); });
          }
        });
        this.roleForm = { name: role.name, is_super_admin: !!role.is_super_admin, permissions: perms };
      } else {
        this.roleForm = { name: '', is_super_admin: false, permissions: [] };
      }
      this.roleDialog = true;
    },

    async saveRole() {
      this.savingRole = true;
      try {
        const payload = { ...this.roleForm };
        // If a master key is checked, strip individual sub-permissions to keep DB clean
        const masterModules = ['settings', 'workforce', 'supplier_invoices', 'client_invoices', 'quality_control', 'users'];
        masterModules.forEach(mod => {
          if (payload.permissions.includes(mod + '.manage')) {
            const subPerms = (this.permissionGroups[mod] || []).filter(p => p !== mod + '.manage');
            payload.permissions = payload.permissions.filter(p => !subPerms.includes(p));
          }
        });

        let response;
        if (this.editingRole) {
          response = await axios.put(`/api/admin/roles/${this.editingRole.id}`, payload);
          const updated = response.data.role;
          this.triggerSummarySnackbar({
            title: this.$t('role_updated'),
            type: 'success',
            icon: 'mdi-shield-check',
            entity: 'Role',
            isRemote: false,
            details: {
              name: updated.name,
              permissionsCount: updated.is_super_admin ? this.$t('all_permissions') : (updated.permissions?.length || 0),
            }
          });
        } else {
          response = await axios.post('/api/admin/roles', payload);
          const created = response.data.role;
          this.triggerSummarySnackbar({
            title: this.$t('role_created'),
            type: 'success',
            icon: 'mdi-shield-plus',
            entity: 'Role',
            isRemote: false,
            details: {
              name: created.name,
              permissionsCount: created.is_super_admin ? this.$t('all_permissions') : (created.permissions?.length || 0),
            }
          });
        }
        this.roleDialog = false;
        this.fetchRoles();
        this.auth.attemp();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.triggerSummarySnackbar({
          title: this.$t('error'),
          message: msg,
          type: 'error',
          icon: 'mdi-alert-circle',
          entity: '',
        });
      }
      this.savingRole = false;
    },

    confirmDeleteRole(role) {
      this.deleteRoleId = role.id;
      this.deleteRoleDialog = true;
    },

    async execDeleteRole() {
      this.deleting = true;
      try {
        await axios.delete(`/api/admin/roles/${this.deleteRoleId}`);
        this.triggerSummarySnackbar({
          title: this.$t('role_deleted'),
          message: this.$t('role_deleted'),
          type: 'success',
          icon: 'mdi-delete-alert',
          entity: '',
        });
        this.deleteRoleDialog = false;
        this.fetchRoles();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.triggerSummarySnackbar({
          title: this.$t('error'),
          message: msg,
          type: 'error',
          icon: 'mdi-alert-circle',
          entity: '',
        });
      }
      this.deleting = false;
    },

    // ============ PERMISSION HELPERS ============
    getMasterKeyForGroup(group) {
      const candidates = [group + '.manage', 'users.manage'];
      const perms = this.permissionGroups[group] || [];
      for (const c of candidates) {
        if (perms.includes(c)) return c;
      }
      return null;
    },

    isCoveredByMaster(group) {
      const masterKey = this.getMasterKeyForGroup(group);
      return masterKey && this.roleForm.permissions.includes(masterKey);
    },

    getNonMasterPerms(group, perms) {
      const masterKey = this.getMasterKeyForGroup(group);
      if (!masterKey) return perms;
      return perms.filter(p => p !== masterKey);
    },

    getGroupSelectedCount(group) {
      const perms = this.permissionGroups[group] || [];
      return perms.filter(p => this.roleForm.permissions.includes(p)).length;
    },

    isGroupFullySelected(group) {
      const perms = this.permissionGroups[group] || [];
      return perms.length > 0 && perms.every(p => this.roleForm.permissions.includes(p));
    },

    isGroupPartiallySelected(group) {
      const perms = this.permissionGroups[group] || [];
      const some = perms.some(p => this.roleForm.permissions.includes(p));
      return some && !this.isGroupFullySelected(group);
    },

    toggleGroup(group, val) {
      const perms = this.permissionGroups[group] || [];
      if (val) {
        perms.forEach(p => { if (!this.roleForm.permissions.includes(p)) this.roleForm.permissions.push(p); });
      } else {
        this.roleForm.permissions = this.roleForm.permissions.filter(p => !perms.includes(p));
      }
    },

    selectAllPermissions() {
      const all = Object.values(this.permissionGroups).flat();
      this.roleForm.permissions = [...new Set(all)];
    },

    clearAllPermissions() {
      this.roleForm.permissions = [];
    },

    // Claude.md Design System Module Icons
    getGroupIcon(group) {
      const icons = {
        dashboard: 'mdi-view-dashboard-outline',
        user_statistics: 'mdi-chart-timeline-variant-shimmer',
        ai_prompt: 'mdi-robot',
        user_management: 'mdi-shield-account',
        charges: 'mdi-truck-cargo-container',
        production: 'mdi-factory',
        quality_control: 'mdi-clipboard-check-outline',
        shippings: 'mdi-package-variant-closed',
        warehouses: 'mdi-warehouse',
        warehouse_transfers: 'mdi-swap-horizontal',
        inventory: 'mdi-clipboard-text-clock-outline',
        workforce: 'mdi-account-hard-hat',
        supplier_invoices: 'mdi-file-document-check',
        client_invoices: 'mdi-account-cash',
        order_transfers: 'mdi-bank-transfer',
        settings: 'mdi-cog-outline',
      };
      return icons[group] || 'mdi-key';
    },

    // Claude.md Design System Module Colors
    getGroupColor(group) {
      const colors = {
        dashboard: '#1565C0',
        user_statistics: '#3949AB',
        ai_prompt: '#42A5F5',
        user_management: '#AD1457',
        charges: '#42A5F5',
        production: '#00897B',
        quality_control: '#26A69A',
        shippings: '#4643A0',
        warehouses: '#A21EE5',
        warehouse_transfers: '#F9A825',
        inventory: '#6D4C41',
        workforce: '#1565C0',
        supplier_invoices: '#E65100',
        client_invoices: '#00838F',
        order_transfers: '#00897B',
        settings: '#546E7A',
      };
      return colors[group] || '#AD1457';
    },

    hexToRgba(hex, alpha = 0.1) {
      if (!hex || !hex.startsWith('#')) return `rgba(173, 20, 87, ${alpha})`;
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },

    triggerSummarySnackbar(snap) {
      clearInterval(this.snackbarTimer);
      this.summarySnackbar = { show: true, ...snap };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 1.4;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
          this.summarySnackbar.show = false;
        }
      }, 100);
    },
  },
};
</script>

<style scoped>
.urm-card { border-radius: 20px !important; font-family: 'Inter', sans-serif; }
.urm-hero { background: linear-gradient(135deg, #B71C1C 0%, #880E4F 40%, #AD1457 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 80px; }
.urm-hero-inner { position: relative; z-index: 1; padding: 22px 26px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.urm-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.urm-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.text-urm { color: #AD1457 !important; }

/* KPI Overview Strip */
.urm-kpi-row {
  display: flex;
}
.urm-kpi-tile {
  flex: 1 1 180px;
  background: rgba(var(--v-theme-surface), 0.7);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 14px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: all 0.2s ease;
}
.urm-kpi-tile:hover {
  transform: translateY(-1px);
  border-color: rgba(173, 20, 87, 0.25);
}
.urm-kpi-icon-pill {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.urm-kpi-info {
  display: flex;
  flex-direction: column;
}
.urm-kpi-label {
  font-size: 0.73rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.urm-kpi-val {
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1.2;
}

.urm-dialog-header { background: linear-gradient(135deg, #B71C1C 0%, #880E4F 40%, #AD1457 100%); color: white; padding: 16px 22px; display: flex; align-items: center; font-size: 16px; font-weight: 700; border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }

/* Granular Permission Group Card */
.perm-group-card {
  background: rgba(var(--v-theme-surface), 1);
  border-color: rgba(var(--v-theme-on-surface), 0.09) !important;
  overflow: hidden;
  transition: all 0.2s ease;
}
.perm-group-header {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}
.perm-group-icon-pill {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Master Permission Banner */
.perm-master-banner {
  background: rgba(255, 179, 0, 0.08);
  border: 1.5px dashed rgba(255, 179, 0, 0.4);
  transition: all 0.2s ease;
}
.master-banner-active {
  background: rgba(255, 179, 0, 0.16);
  border: 1.5px solid rgba(255, 179, 0, 0.8);
  box-shadow: 0 2px 8px rgba(255, 179, 0, 0.15);
}

/* Granular Permissions Grid */
.perm-tiles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 8px;
}
.perm-tile {
  background: rgba(var(--v-theme-surface), 0.6);
  border-color: rgba(var(--v-theme-on-surface), 0.08) !important;
  transition: all 0.18s ease;
}
.perm-tile:hover {
  background: rgba(var(--v-theme-on-surface), 0.03);
  border-color: rgba(173, 20, 87, 0.25) !important;
}
.perm-tile-selected {
  background: rgba(var(--v-theme-surface), 1);
  border-color: rgba(173, 20, 87, 0.35) !important;
}
.perm-tile-mastered {
  opacity: 0.8;
  background: rgba(255, 179, 0, 0.05);
}
.perm-tile-label {
  font-size: 0.82rem;
  font-weight: 500;
  line-height: 1.25;
}

/* Toolbar styling */
.urm-toolbar-card {
  background: rgba(var(--v-theme-surface), 0.7);
  backdrop-filter: blur(8px);
  border-color: rgba(173, 20, 87, 0.15) !important;
}

.urm-search-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(var(--v-theme-surface), 0.9);
  border: 1.5px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 9999px;
  padding: 5px 14px;
  min-width: 220px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.urm-search-wrap:focus-within {
  border-color: #AD1457;
  box-shadow: 0 0 0 3px rgba(173, 20, 87, 0.18);
  background: rgb(var(--v-theme-surface));
}

.urm-search-icon {
  color: #AD1457;
  opacity: 0.7;
}

.urm-search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 0.82rem;
  font-family: inherit;
  color: inherit;
}
.urm-search-input::placeholder {
  opacity: 0.55;
}

.urm-search-clear {
  opacity: 0.6;
}

.urm-add-btn {
  font-weight: 600 !important;
  text-transform: none !important;
  box-shadow: 0 3px 10px rgba(173, 20, 87, 0.25) !important;
}

.urm-action-btn {
  font-weight: 600 !important;
  text-transform: none !important;
  box-shadow: 0 2px 8px rgba(173, 20, 87, 0.3) !important;
}

/* Segmented Pill Toggle */
:deep(.v-slide-group__content) {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 4px 6px !important;
  border-radius: 9999px !important;
  background: rgba(173, 20, 87, 0.08) !important;
  border: 1px solid rgba(173, 20, 87, 0.28) !important;
  width: fit-content !important;
}

:deep(.v-slide-group__content .v-tab) {
  border-radius: 9999px !important;
  font-weight: 600 !important;
  font-size: 0.84rem !important;
  min-height: 36px !important;
  height: 36px !important;
  padding: 0 18px !important;
  text-transform: none !important;
  color: rgba(var(--v-theme-on-surface), 0.75) !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

:deep(.v-slide-group__content .v-tab--selected) {
  background: #AD1457 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(173, 20, 87, 0.35) !important;
}

:deep(.v-slide-group__content .v-tab__slider) {
  display: none !important;
}
</style>
