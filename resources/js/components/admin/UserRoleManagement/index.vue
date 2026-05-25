<template>
<v-card class="urm-card" elevation="0">
  <!-- Hero Header -->
  <div class="urm-hero"><div class="urm-hero-inner">
    <div class="d-flex align-center ga-4">
      <div class="urm-hero-icon"><v-icon size="28" color="white">mdi-shield-account</v-icon></div>
      <div>
        <div class="urm-hero-title">{{ $t('user_role_management') }}</div>
        <div class="text-white text-caption" style="opacity:.7">{{ $t('user_role_management_desc') }}</div>
      </div>
    </div>
  </div></div>

  <!-- Tabs -->
  <v-tabs v-model="activeTab" color="#AD1457" class="mx-4 mt-2" slider-color="#AD1457">
    <v-tab :value="0" prepend-icon="mdi-account-multiple">{{ $t('users') }}</v-tab>
    <v-tab :value="1" prepend-icon="mdi-shield-lock-outline">{{ $t('roles') }}</v-tab>
  </v-tabs>
  <v-divider class="mx-4" color="#AD1457"></v-divider>

  <!-- ==================== TAB 0: USERS ==================== -->
  <v-card-text v-if="activeTab === 0" class="pa-4">
    <div class="d-flex justify-end mb-3">
      <v-btn color="#AD1457" variant="flat" rounded prepend-icon="mdi-account-plus" @click="openUserDialog(null)">{{ $t('add_user') }}</v-btn>
    </div>
    <v-data-table
      :headers="userHeaders"
      :items="users"
      :loading="loadingUsers"
      density="comfortable"
      class="rounded-lg"
      hover
    >
      <template v-slot:[`item.role`]="{ item }">
        <v-chip :color="item.role?.is_super_admin ? '#AD1457' : 'grey'" size="small" variant="tonal">
          <v-icon v-if="item.role?.is_super_admin" size="14" class="mr-1">mdi-crown</v-icon>
          {{ item.role?.name || '-' }}
        </v-chip>
      </template>
      <template v-slot:[`item.is_active`]="{ item }">
        <v-chip :color="item.is_active ? 'success' : 'error'" size="small" variant="tonal" :prepend-icon="item.is_active ? 'mdi-check-circle' : 'mdi-close-circle'">
          {{ item.is_active ? $t('active') : $t('disabled') }}
        </v-chip>
      </template>
      <template v-slot:[`item.created_at`]="{ item }">
        {{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '-' }}
      </template>
      <template v-slot:[`item.actions`]="{ item }">
        <v-btn icon size="x-small" variant="tonal" color="#AD1457" class="mr-1" @click="openUserDialog(item)"><v-icon size="14">mdi-pencil</v-icon></v-btn>
        <v-tooltip :text="item.is_active ? $t('disable_user') : $t('enable_user')" location="top">
          <template #activator="{ props }">
            <v-btn v-bind="props" icon size="x-small" variant="tonal" :color="item.is_active ? 'warning' : 'success'" @click="toggleUserActive(item)" :disabled="item.id === auth.user?.id" :loading="togglingUserId === item.id">
              <v-icon size="14">{{ item.is_active ? 'mdi-account-lock' : 'mdi-account-check' }}</v-icon>
            </v-btn>
          </template>
        </v-tooltip>
      </template>
    </v-data-table>
  </v-card-text>

  <!-- ==================== TAB 1: ROLES ==================== -->
  <v-card-text v-else-if="activeTab === 1" class="pa-4">
    <div class="d-flex justify-end mb-3">
      <v-btn color="#AD1457" variant="flat" rounded prepend-icon="mdi-shield-plus" @click="openRoleDialog(null)">{{ $t('add_role') }}</v-btn>
    </div>
    <v-data-table
      :headers="roleHeaders"
      :items="roles"
      :loading="loadingRoles"
      density="comfortable"
      class="rounded-lg"
      hover
    >
      <template v-slot:[`item.is_super_admin`]="{ item }">
        <v-chip v-if="item.is_super_admin" color="#AD1457" size="small" variant="tonal" prepend-icon="mdi-crown">{{ $t('super_admin') }}</v-chip>
        <span v-else class="text-grey">-</span>
      </template>
      <template v-slot:[`item.permissions_count`]="{ item }">
        <v-chip size="small" color="#AD1457" variant="tonal">
          {{ item.is_super_admin ? $t('all_permissions') : (item.permissions?.length || 0) }}
        </v-chip>
      </template>
      <template v-slot:[`item.users_count`]="{ item }">
        <v-chip size="small" color="info" variant="tonal">{{ item.users_count || 0 }}</v-chip>
      </template>
      <template v-slot:[`item.actions`]="{ item }">
        <v-btn icon size="x-small" variant="tonal" color="#AD1457" class="mr-1" @click="openRoleDialog(item)"><v-icon size="14">mdi-pencil</v-icon></v-btn>
        <v-btn icon size="x-small" variant="tonal" color="error" @click="confirmDeleteRole(item)" :disabled="item.users_count > 0 || item.is_super_admin"><v-icon size="14">mdi-delete</v-icon></v-btn>
      </template>
    </v-data-table>
  </v-card-text>
</v-card>

<!-- ==================== USER DIALOG ==================== -->
<v-dialog v-model="userDialog" max-width="550" persistent>
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
          <v-text-field v-model="userForm.name" :label="$t('name')" variant="outlined" density="compact" rounded color="#AD1457" class="text-urm" prepend-inner-icon="mdi-account" hide-details></v-text-field>
        </v-col>
        <v-col cols="12">
          <v-text-field v-model="userForm.email" :label="$t('email')" variant="outlined" density="compact" rounded color="#AD1457" class="text-urm" prepend-inner-icon="mdi-email" hide-details></v-text-field>
        </v-col>
        <v-col cols="12">
          <v-text-field v-model="userForm.password" :label="editingUser ? $t('new_password') + ' (' + $t('optional') + ')' : $t('password')" type="password" variant="outlined" density="compact" rounded color="#AD1457" class="text-urm" prepend-inner-icon="mdi-lock" hide-details></v-text-field>
        </v-col>
        <v-col cols="12">
          <v-select v-model="userForm.role_id" :items="roles" item-title="name" item-value="id" :label="$t('role')" variant="outlined" density="compact" rounded color="#AD1457" class="text-urm" prepend-inner-icon="mdi-shield-account" hide-details>
            <template #item="{ item, props: p }">
              <v-list-item v-bind="p">
                <template #prepend>
                  <v-icon v-if="item.raw.is_super_admin" color="#AD1457" size="18">mdi-crown</v-icon>
                  <v-icon v-else size="18">mdi-shield-outline</v-icon>
                </template>
              </v-list-item>
            </template>
          </v-select>
        </v-col>
      </v-row>
    </v-card-text>
    <v-card-actions class="pa-5 pt-0">
      <v-spacer></v-spacer>
      <v-btn @click="userDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
      <v-btn color="#AD1457" variant="flat" rounded @click="saveUser" :loading="savingUser" :disabled="!userFormValid">{{ $t('save') }}</v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>

<!-- ==================== ROLE DIALOG ==================== -->
<v-dialog v-model="roleDialog" max-width="750" persistent scrollable>
  <v-card class="rounded-xl">
    <div class="urm-dialog-header">
      <v-icon size="22" color="white" class="mr-2">{{ editingRole ? 'mdi-shield-edit' : 'mdi-shield-plus' }}</v-icon>
      {{ editingRole ? $t('edit_role') : $t('add_role') }}
      <v-spacer></v-spacer>
      <v-btn icon variant="text" color="white" size="small" @click="roleDialog = false"><v-icon>mdi-close</v-icon></v-btn>
    </div>
    <v-card-text class="pa-5" style="max-height: 65vh; overflow-y: auto;">
      <v-row dense>
        <v-col cols="12" sm="6">
          <v-text-field v-model="roleForm.name" :label="$t('role_name')" variant="outlined" density="compact" rounded color="#AD1457" class="text-urm" prepend-inner-icon="mdi-tag" hide-details></v-text-field>
        </v-col>
        <v-col cols="12" sm="6" class="d-flex align-center">
          <v-switch v-model="roleForm.is_super_admin" :label="$t('super_admin')" color="#AD1457" hide-details density="compact" inset :disabled="!auth.isSuperAdmin"></v-switch>
        </v-col>
      </v-row>

      <v-divider class="my-4" color="#AD1457"></v-divider>

      <!-- Permission Matrix -->
      <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center">
        <v-icon color="#AD1457" class="mr-2" size="20">mdi-key-chain</v-icon>
        {{ $t('permissions') }}
        <v-spacer></v-spacer>
        <v-btn size="x-small" variant="tonal" color="#AD1457" rounded @click="selectAllPermissions" class="mr-2">{{ $t('select_all') }}</v-btn>
        <v-btn size="x-small" variant="tonal" color="grey" rounded @click="clearAllPermissions">{{ $t('clear_all') }}</v-btn>
      </div>

      <div v-if="roleForm.is_super_admin" class="text-center pa-4 rounded-lg mb-3" style="background: rgba(173,20,87,.06); border: 1px solid rgba(173,20,87,.15);">
        <v-icon size="40" color="#AD1457">mdi-crown</v-icon>
        <div class="text-body-2 font-weight-medium mt-2">{{ $t('super_admin_all_permissions') }}</div>
      </div>

      <div v-else>
        <div v-for="(perms, group) in permissionGroups" :key="group" class="perm-group mb-3">
          <div class="perm-group-header">
            <v-checkbox
              :model-value="isGroupFullySelected(group)"
              :indeterminate="isGroupPartiallySelected(group)"
              @update:model-value="toggleGroup(group, $event)"
              color="#AD1457"
              hide-details
              density="compact"
              class="mr-2"
            ></v-checkbox>
            <v-icon size="18" color="#AD1457" class="mr-1">{{ getGroupIcon(group) }}</v-icon>
            <span class="text-subtitle-2 font-weight-bold text-capitalize">{{ $t('perm_group_' + group) }}</span>
          </div>
          <div class="perm-group-body">
            <v-checkbox
              v-for="perm in perms" :key="perm"
              v-model="roleForm.permissions"
              :value="perm"
              :label="$t('perm_' + perm.replace(/\./g, '_'))"
              color="#AD1457"
              hide-details
              density="compact"
              class="perm-checkbox"
            ></v-checkbox>
          </div>
        </div>
      </div>
    </v-card-text>
    <v-card-actions class="pa-5 pt-2">
      <v-spacer></v-spacer>
      <v-btn @click="roleDialog = false" variant="text">{{ $t('cancel') }}</v-btn>
      <v-btn color="#AD1457" variant="flat" rounded @click="saveRole" :loading="savingRole" :disabled="!roleForm.name">{{ $t('save') }}</v-btn>
    </v-card-actions>
  </v-card>
</v-dialog>



<v-dialog v-model="deleteRoleDialog" max-width="420">
  <v-card class="rounded-xl">
    <v-card-title class="pa-5 pb-2 d-flex align-center"><v-icon color="error" class="mr-2">mdi-alert-circle-outline</v-icon>{{ $t('confirm_delete') }}</v-card-title>
    <v-card-text class="pa-5 pt-2">{{ $t('confirm_delete_role_message') }}</v-card-text>
    <v-card-actions class="pa-5 pt-0"><v-spacer></v-spacer><v-btn @click="deleteRoleDialog = false" variant="text">{{ $t('cancel') }}</v-btn><v-btn color="error" variant="flat" rounded @click="execDeleteRole" :loading="deleting">{{ $t('delete') }}</v-btn></v-card-actions>
  </v-card>
</v-dialog>

<!-- Summary Snackbar -->
<v-snackbar v-model="snackbar.show" :timeout="4000" :color="snackbar.color" rounded="lg" location="top right" elevation="12">
  <div class="d-flex align-center">
    <v-icon class="mr-2">{{ snackbar.icon }}</v-icon>
    <span class="text-subtitle-2 font-weight-medium">{{ snackbar.message }}</span>
  </div>
  <template #actions><v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn></template>
</v-snackbar>
</template>

<script>
import axios from 'axios';
import { useAuth } from '@/stores/Auth';
import { useMain } from '@/stores/Main';
import mitt from '@/plugins/mitt';

export default {
  name: 'UserRoleManagement',
  data() {
    return {
      activeTab: 0,
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
      // Shared
      deleting: false,
      permissionGroups: {},
      snackbar: { show: false, message: '', color: 'success', icon: 'mdi-check-circle' },
    };
  },
  computed: {
    auth() { return useAuth(); },
    main() { return useMain(); },
    userHeaders() {
      return [
        { title: this.$t('name'), key: 'name', align: 'center' },
        { title: this.$t('email'), key: 'email', align: 'center' },
        { title: this.$t('role'), key: 'role', align: 'center' },
        { title: this.$t('status'), key: 'is_active', align: 'center' },
        { title: this.$t('created_at'), key: 'created_at', align: 'center' },
        { title: this.$t('actions'), key: 'actions', align: 'center', sortable: false },
      ];
    },
    roleHeaders() {
      return [
        { title: this.$t('role_name'), key: 'name', align: 'center' },
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
        if (!oldPerms || !this.permissionGroups.settings) return;
        const settingsSubPerms = (this.permissionGroups.settings || []).filter(p => p !== 'settings.manage');
        if (settingsSubPerms.length === 0) return;

        const hadMaster = oldPerms.includes('settings.manage');
        const hasMaster = newPerms.includes('settings.manage');

        // Master was just checked → add all sub-permissions
        if (!hadMaster && hasMaster) {
          settingsSubPerms.forEach(p => {
            if (!this.roleForm.permissions.includes(p)) this.roleForm.permissions.push(p);
          });
        }
        // Master was just unchecked → remove all sub-permissions
        else if (hadMaster && !hasMaster) {
          this.roleForm.permissions = this.roleForm.permissions.filter(p => !settingsSubPerms.includes(p));
        }
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
    mitt.off('model-changed', this._onMC);
  },
  methods: {
    _onMC(d) {
      if (d.model === 'User') this.fetchUsers();
      if (d.model === 'Role') this.fetchRoles();
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
        if (this.editingUser) {
          await axios.put(`/api/admin/users/${this.editingUser.id}`, payload);
          this.showSnack(this.$t('user_updated'), 'success', 'mdi-check-circle');
        } else {
          await axios.post('/api/admin/users', payload);
          this.showSnack(this.$t('user_created'), 'success', 'mdi-check-circle');
        }
        this.userDialog = false;
        this.fetchUsers();
        this.fetchRoles(); // refresh user counts
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.showSnack(msg, 'error', 'mdi-alert-circle');
      }
      this.savingUser = false;
    },
    async toggleUserActive(user) {
      this.togglingUserId = user.id;
      try {
        const r = await axios.patch(`/api/admin/users/${user.id}/toggle-active`);
        this.showSnack(r.data.message, 'success', 'mdi-check-circle');
        this.fetchUsers();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.showSnack(msg, 'error', 'mdi-alert-circle');
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
      if (role) {
        const perms = [...(role.permissions || [])];
        // If settings.manage (master) is set, expand to show all sub-settings as checked
        if (perms.includes('settings.manage')) {
          const settingsSubPerms = (this.permissionGroups.settings || []).filter(p => p !== 'settings.manage');
          settingsSubPerms.forEach(p => { if (!perms.includes(p)) perms.push(p); });
        }
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
        // If settings.manage is checked, strip individual sub-permissions to keep DB clean
        if (payload.permissions.includes('settings.manage')) {
          const settingsSubPerms = (this.permissionGroups.settings || []).filter(p => p !== 'settings.manage');
          payload.permissions = payload.permissions.filter(p => !settingsSubPerms.includes(p));
        }
        if (this.editingRole) {
          await axios.put(`/api/admin/roles/${this.editingRole.id}`, payload);
          this.showSnack(this.$t('role_updated'), 'success', 'mdi-check-circle');
        } else {
          await axios.post('/api/admin/roles', payload);
          this.showSnack(this.$t('role_created'), 'success', 'mdi-check-circle');
        }
        this.roleDialog = false;
        this.fetchRoles();
        // Re-fetch auth user in case own role was updated
        this.auth.attemp();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.showSnack(msg, 'error', 'mdi-alert-circle');
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
        this.showSnack(this.$t('role_deleted'), 'success', 'mdi-check-circle');
        this.deleteRoleDialog = false;
        this.fetchRoles();
      } catch (e) {
        const msg = e.response?.data?.message || this.$t('error');
        this.showSnack(msg, 'error', 'mdi-alert-circle');
      }
      this.deleting = false;
    },
    // ============ PERMISSION HELPERS ============
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
    getGroupIcon(group) {
      const icons = {
        dashboard: 'mdi-view-dashboard', ai_prompt: 'mdi-robot',
        user_management: 'mdi-shield-account', charges: 'mdi-truck-cargo-container',
        production: 'mdi-factory', shippings: 'mdi-package-variant-closed', warehouses: 'mdi-warehouse',
        warehouse_transfers: 'mdi-swap-horizontal', inventory: 'mdi-clipboard-text-clock-outline',
        workforce: 'mdi-account-hard-hat', supplier_invoices: 'mdi-file-document-check',
        client_invoices: 'mdi-account-cash', quality_control: 'mdi-clipboard-check-outline',
        settings: 'mdi-cog-outline',
      };
      return icons[group] || 'mdi-key';
    },
    showSnack(message, color, icon) {
      this.snackbar = { show: true, message, color, icon };
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
.urm-dialog-header { background: linear-gradient(135deg, #B71C1C 0%, #880E4F 40%, #AD1457 100%); color: white; padding: 16px 22px; display: flex; align-items: center; font-size: 16px; font-weight: 700; border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
.perm-group { border: 1px solid rgba(173,20,87,.12); border-radius: 12px; overflow: hidden; }
.perm-group-header { display: flex; align-items: center; padding: 8px 14px; background: rgba(173,20,87,.04); border-bottom: 1px solid rgba(173,20,87,.08); }
.perm-group-body { display: flex; flex-wrap: wrap; padding: 8px 14px; gap: 4px; }
.perm-checkbox { flex: 0 0 auto; min-width: 180px; }
</style>
