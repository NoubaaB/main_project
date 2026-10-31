<template>
  <v-card class="profile-card" elevation="0">
    <!-- Hero Header -->
    <div class="profile-hero">
      <div class="profile-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="profile-hero-icon">
            <v-icon size="28" color="white">mdi-account-cog-outline</v-icon>
          </div>
          <div>
            <div class="profile-hero-title">{{ $t('profile_settings') }}</div>
            <div class="text-white text-caption" style="opacity:.75">{{ $t('profile_settings_desc') }}</div>
          </div>
        </div>
        <div class="profile-hero-actions">
          <v-btn
            variant="tonal"
            color="white"
            rounded
            size="small"
            prepend-icon="mdi-chart-timeline-variant"
            @click="$router.push('/user-statistics')"
            class="mr-2"
          >
            {{ $t('user_statistics') || 'User Statistics' }}
          </v-btn>
          <v-btn
            variant="tonal"
            color="white"
            rounded
            size="small"
            prepend-icon="mdi-arrow-left"
            @click="$router.back()"
          >
            {{ $t('back') }}
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Main Container -->
    <v-card-text class="pa-6">
      <v-row>
        <!-- LEFT COLUMN: Identity & Preferences -->
        <v-col cols="12" md="4" lg="4">
          <!-- User Identity Card -->
          <div class="profile-panel mb-4 text-center pa-5">
            <div class="profile-avatar-wrap mb-3">
              <v-avatar size="88" class="profile-avatar">
                <span class="text-h4 font-weight-bold text-white">{{ userInitials }}</span>
              </v-avatar>
              <div class="status-beacon" title="Active"></div>
            </div>

            <div class="text-h6 font-weight-bold mb-1" id="profile-display-name">{{ auth.user?.name || '—' }}</div>
            <div class="text-caption text-medium-emphasis mb-3" id="profile-display-email">{{ auth.user?.email || '—' }}</div>

            <div class="d-flex justify-center mb-4">
              <v-chip
                color="#1976D2"
                variant="tonal"
                size="small"
                :prepend-icon="isSuperAdmin ? 'mdi-shield-crown' : 'mdi-shield-account'"
                class="font-weight-bold px-3"
              >
                {{ isSuperAdmin ? 'SUPER ADMIN' : (auth.user?.role?.name || 'USER') }}
              </v-chip>
            </div>

            <v-divider class="my-3 opacity-20"></v-divider>

            <!-- Metadata List -->
            <div class="profile-meta-list text-left">
              <div class="d-flex justify-space-between align-center py-1">
                <span class="text-caption text-medium-emphasis">{{ $t('user_id') }}</span>
                <span class="text-caption font-weight-bold">#{{ auth.user?.id || '—' }}</span>
              </div>
              <div class="d-flex justify-space-between align-center py-1">
                <span class="text-caption text-medium-emphasis">{{ $t('account_status') }}</span>
                <v-chip color="success" size="x-small" variant="flat" class="font-weight-bold">
                  {{ $t('active_status') }}
                </v-chip>
              </div>
              <div class="d-flex justify-space-between align-center py-1">
                <span class="text-caption text-medium-emphasis">{{ $t('member_since') }}</span>
                <span class="text-caption font-weight-medium">{{ memberSinceDate }}</span>
              </div>
            </div>
          </div>

          <!-- Preferences Card -->
          <div class="profile-panel mb-4 pa-4">
            <div class="panel-section-title mb-3">
              <v-icon size="18" color="#1976D2" class="mr-2">mdi-tune-vertical-variant</v-icon>
              <span>{{ $t('user_preferences') }}</span>
            </div>

            <!-- Theme Mode Switcher -->
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1 font-weight-medium">{{ $t('theme_mode') }}</div>
              <div class="d-flex ga-2">
                <v-btn
                  :variant="!main.is_dark ? 'flat' : 'tonal'"
                  :color="!main.is_dark ? '#1976D2' : undefined"
                  size="small"
                  rounded
                  class="flex-grow-1"
                  prepend-icon="mdi-white-balance-sunny"
                  @click="setTheme(false)"
                >
                  {{ $t('light_mode') || 'Light' }}
                </v-btn>
                <v-btn
                  :variant="main.is_dark ? 'flat' : 'tonal'"
                  :color="main.is_dark ? '#1976D2' : undefined"
                  size="small"
                  rounded
                  class="flex-grow-1"
                  prepend-icon="mdi-weather-night"
                  @click="setTheme(true)"
                >
                  {{ $t('dark_mode') || 'Dark' }}
                </v-btn>
              </div>
            </div>

            <!-- Language Switcher -->
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1 font-weight-medium">{{ $t('interface_language') }}</div>
              <div class="d-flex ga-2">
                <v-btn
                  v-for="l in availableLanguages"
                  :key="l.code"
                  :variant="$i18n.locale === l.code ? 'flat' : 'tonal'"
                  :color="$i18n.locale === l.code ? '#1976D2' : undefined"
                  size="small"
                  rounded
                  class="flex-grow-1 font-weight-bold"
                  @click="changeLang(l.code)"
                >
                  {{ l.label }}
                </v-btn>
              </div>
            </div>

            <!-- System Session Info -->
            <div class="pt-2">
              <div class="text-caption text-medium-emphasis mb-1 font-weight-medium">{{ $t('system_session') }}</div>
              <div class="d-flex align-center justify-space-between session-info-box pa-2 rounded-lg">
                <div class="d-flex align-center ga-2">
                  <v-icon size="16" color="#1976D2">mdi-laptop</v-icon>
                  <span class="text-caption">{{ clientInfo }}</span>
                </div>
                <v-chip color="success" size="x-small" variant="tonal" class="font-weight-medium">
                  {{ $t('session_active') }}
                </v-chip>
              </div>
            </div>
          </div>

          <!-- Web Push Notifications Card -->
          <div class="profile-panel mb-4 pa-4">
            <div class="panel-section-title mb-2">
              <v-icon size="18" color="#1976D2" class="mr-2">mdi-bell-badge-outline</v-icon>
              <span>{{ $t('web_push_notifications') }}</span>
            </div>

            <div class="text-caption text-medium-emphasis mb-3">
              {{ $t('enable_push_notifications_desc') }}
            </div>

            <div v-if="!webPush.isSupported" class="text-caption text-error d-flex align-center ga-2 pa-2 rounded-lg bg-red-lighten-5">
              <v-icon size="16" color="error">mdi-alert-circle-outline</v-icon>
              <span>{{ $t('push_notifications_not_supported') }}</span>
            </div>

            <div v-else>
              <!-- Status Box -->
              <div class="d-flex align-center justify-space-between session-info-box pa-2.5 rounded-lg mb-3">
                <div class="d-flex align-center ga-2">
                  <v-icon
                    size="18"
                    :color="webPush.isEnabled ? '#4CAF50' : webPush.isBlocked ? '#EF5350' : '#FF9800'"
                  >
                    {{ webPush.isEnabled ? 'mdi-bell-check' : webPush.isBlocked ? 'mdi-bell-cancel' : 'mdi-bell-sleep-outline' }}
                  </v-icon>
                  <div>
                    <div class="text-caption font-weight-bold">
                      {{ webPush.isEnabled ? $t('webpush_status_active') : webPush.isBlocked ? $t('webpush_status_blocked') : $t('disabled') }}
                    </div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 10.5px !important;">
                      {{ webPush.deviceCount }} {{ $t('devices_registered') }}
                    </div>
                  </div>
                </div>

                <v-chip
                  :color="webPush.isEnabled ? 'success' : webPush.isBlocked ? 'error' : 'warning'"
                  size="x-small"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  {{ webPush.isEnabled ? $t('active') : webPush.isBlocked ? $t('blocked') : $t('disabled') }}
                </v-chip>
              </div>

              <!-- Action Controls -->
              <div class="d-flex ga-2">
                <v-btn
                  v-if="!webPush.isEnabled"
                  color="#1976D2"
                  variant="flat"
                  size="small"
                  rounded
                  class="flex-grow-1 font-weight-bold text-none"
                  prepend-icon="mdi-bell-ring-outline"
                  :loading="webPush.loading"
                  @click="webPush.subscribe()"
                  id="profile-webpush-enable-btn"
                >
                  {{ $t('enable') }}
                </v-btn>

                <v-btn
                  v-if="webPush.isEnabled"
                  color="red-lighten-1"
                  variant="tonal"
                  size="small"
                  rounded
                  class="flex-grow-1 font-weight-bold text-none"
                  prepend-icon="mdi-bell-off-outline"
                  :loading="webPush.loading"
                  @click="webPush.unsubscribe()"
                  id="profile-webpush-disable-btn"
                >
                  {{ $t('disable') }}
                </v-btn>

                <v-btn
                  v-if="webPush.isEnabled"
                  color="#1976D2"
                  variant="tonal"
                  size="small"
                  rounded
                  class="font-weight-bold text-none"
                  prepend-icon="mdi-send-outline"
                  :loading="webPush.loading"
                  @click="webPush.sendTestNotification()"
                  id="profile-webpush-test-btn"
                >
                  {{ $t('test_push') }}
                </v-btn>
              </div>
            </div>
          </div>

          <!-- Permissions & Role Overview Card -->
          <div class="profile-panel pa-4">
            <div class="panel-section-title mb-3">
              <v-icon size="18" color="#1976D2" class="mr-2">mdi-shield-check-outline</v-icon>
              <span>{{ $t('permissions_overview') }}</span>
            </div>

            <div v-if="isSuperAdmin" class="super-admin-banner pa-3 rounded-lg mb-2">
              <div class="d-flex align-center ga-2 mb-1">
                <v-icon size="20" color="#1976D2">mdi-shield-crown-outline</v-icon>
                <span class="text-subtitle-2 font-weight-bold text-primary">Super Administrator</span>
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ $t('all_permissions_granted') }}
              </div>
            </div>

            <div v-else-if="auth.user?.role?.permissions && auth.user.role.permissions.length" class="d-flex flex-wrap ga-1">
              <v-chip
                v-for="(perm, idx) in auth.user.role.permissions.slice(0, 8)"
                :key="idx"
                size="x-small"
                variant="tonal"
                color="#1976D2"
              >
                {{ perm }}
              </v-chip>
              <v-chip
                v-if="auth.user.role.permissions.length > 8"
                size="x-small"
                variant="outlined"
              >
                +{{ auth.user.role.permissions.length - 8 }} more
              </v-chip>
            </div>
            <div v-else class="text-caption text-medium-emphasis">
              Standard User Access
            </div>
          </div>
        </v-col>

        <!-- RIGHT COLUMN: Edit Forms & Security -->
        <v-col cols="12" md="8" lg="8">
          <v-form ref="profileForm" @submit.prevent="saveProfile">
            <!-- Personal Info Card -->
            <div class="profile-panel mb-4 pa-5">
              <div class="panel-section-title mb-4">
                <v-icon size="20" color="#1976D2" class="mr-2">mdi-account-details-outline</v-icon>
                <span>{{ $t('personal_information') }}</span>
              </div>

              <v-row dense>
                <v-col cols="12" sm="6">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('full_name') }}</div>
                  <v-text-field
                    v-model="form.name"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#1976D2"
                    prepend-inner-icon="mdi-account"
                    :rules="[v => !!v || $t('field_required')]"
                    counter="50"
                    maxlength="50"
                    id="profile-name-input"
                    hide-details="auto"
                  ></v-text-field>
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('email') }}</div>
                  <v-text-field
                    v-model="form.email"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#1976D2"
                    prepend-inner-icon="mdi-email-outline"
                    :rules="[v => !!v || $t('field_required'), v => /.+@.+\..+/.test(v) || $t('invalid_email')]"
                    id="profile-email-input"
                    hide-details="auto"
                  ></v-text-field>
                </v-col>
              </v-row>
            </div>

            <!-- Security & Password Card -->
            <div class="profile-panel mb-4 pa-5">
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="panel-section-title">
                  <v-icon size="20" color="#1976D2" class="mr-2">mdi-shield-lock-outline</v-icon>
                  <span>{{ $t('change_password') }}</span>
                </div>
                <v-chip size="x-small" color="grey" variant="tonal">{{ $t('optional') }}</v-chip>
              </div>

              <v-alert
                type="info"
                variant="tonal"
                density="compact"
                rounded="lg"
                class="mb-4 text-caption"
                icon="mdi-information-outline"
                color="#1976D2"
              >
                {{ $t('leave_blank_password') }}
              </v-alert>

              <v-row dense>
                <v-col cols="12" sm="6">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('new_password') }}</div>
                  <v-text-field
                    v-model="form.password"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#1976D2"
                    prepend-inner-icon="mdi-lock-outline"
                    :append-inner-icon="showPass ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showPass = !showPass"
                    :type="showPass ? 'text' : 'password'"
                    :rules="form.password ? [v => v.length >= 6 || $t('password_min_6')] : []"
                    id="profile-password-input"
                    hide-details="auto"
                  ></v-text-field>

                  <!-- Password Strength Meter -->
                  <div v-if="form.password" class="mt-2">
                    <div class="d-flex justify-space-between align-center mb-1">
                      <span class="text-caption font-weight-medium">{{ $t('password_strength') }}</span>
                      <span class="text-caption font-weight-bold" :style="{ color: passwordStrengthColor }">
                        {{ passwordStrengthLabel }}
                      </span>
                    </div>
                    <v-progress-linear
                      :model-value="passwordStrengthScore"
                      :color="passwordStrengthColor"
                      height="4"
                      rounded
                    ></v-progress-linear>
                  </div>
                </v-col>

                <v-col cols="12" sm="6">
                  <div class="text-caption font-weight-bold mb-1">{{ $t('confirm_password') }}</div>
                  <v-text-field
                    v-model="form.password_confirmation"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#1976D2"
                    prepend-inner-icon="mdi-lock-check-outline"
                    :append-inner-icon="showConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append-inner="showConfirm = !showConfirm"
                    :type="showConfirm ? 'text' : 'password'"
                    :rules="form.password ? [v => v === form.password || $t('passwords_do_not_match')] : []"
                    id="profile-confirm-password-input"
                    hide-details="auto"
                  ></v-text-field>
                </v-col>
              </v-row>
            </div>

            <!-- Action Toolbar Card -->
            <div class="profile-panel pa-4 d-flex align-center justify-space-between flex-wrap ga-3">
              <div class="d-flex align-center ga-2">
                <v-icon size="18" :color="hasChanges ? 'warning' : 'grey'">
                  {{ hasChanges ? 'mdi-alert-circle-outline' : 'mdi-check-all' }}
                </v-icon>
                <span class="text-caption text-medium-emphasis">
                  {{ hasChanges ? 'You have unsaved changes' : 'All changes saved' }}
                </span>
              </div>

              <div class="d-flex ga-3">
                <v-btn
                  variant="tonal"
                  color="red-lighten-1"
                  rounded
                  size="default"
                  prepend-icon="mdi-undo"
                  @click="resetForm"
                  :disabled="!hasChanges"
                  id="profile-reset-btn"
                >
                  {{ $t('discard_changes') || $t('reset') }}
                </v-btn>

                <v-btn
                  variant="flat"
                  color="#1976D2"
                  rounded
                  size="default"
                  prepend-icon="mdi-content-save-check"
                  @click="saveProfile"
                  :loading="saving"
                  :disabled="!hasChanges"
                  id="profile-save-btn"
                >
                  {{ $t('save_changes') }}
                </v-btn>
              </div>
            </div>
          </v-form>
        </v-col>
      </v-row>
    </v-card-text>

    <!-- Summary Snackbar (Rule 5) -->
    <v-snackbar
      v-model="snackbar.show"
      :timeout="5000"
      location="top right"
      color="surface"
      rounded="lg"
      elevation="12"
      min-width="320"
      max-width="400"
    >
      <v-progress-linear
        :model-value="snackbarProgress"
        color="#1976D2"
        height="3"
        class="mb-2"
        rounded
      ></v-progress-linear>
      <div class="d-flex align-center mb-1">
        <v-icon :color="snackbar.success ? 'success' : 'error'" class="mr-2">
          {{ snackbar.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}
        </v-icon>
        <span class="text-subtitle-2 font-weight-bold">{{ snackbar.message }}</span>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" @click="snackbar.show = false"></v-btn>
      </template>
    </v-snackbar>
  </v-card>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import { useAuth } from '@/stores/Auth';
import { useMain } from '@/stores/Main';
import { useWebPush } from '@/stores/WebPush';

export default {
  name: 'ProfileSettings',
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      },
      original: {
        name: '',
        email: ''
      },
      saving: false,
      showPass: false,
      showConfirm: false,
      snackbar: {
        show: false,
        success: true,
        message: ''
      },
      snackbarProgress: 100,
      snackbarTimer: null,
      availableLanguages: [
        { code: 'en', label: 'English' },
        { code: 'es', label: 'Español' },
        { code: 'fr', label: 'Français' },
      ],
    };
  },
  computed: {
    auth() {
      return useAuth();
    },
    webPush() {
      return useWebPush();
    },
    main() {
      return useMain();
    },
    isSuperAdmin() {
      return this.auth.user?.role?.is_super_admin === true || this.auth.user?.role?.name?.toLowerCase().includes('admin');
    },
    userInitials() {
      const name = this.auth.user?.name || '';
      if (!name) return 'U';
      const parts = name.trim().split(/\s+/);
      if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
      }
      return name.slice(0, 2).toUpperCase();
    },
    memberSinceDate() {
      const d = this.auth.user?.created_at;
      if (!d) return '—';
      return moment(d).format('DD/MM/YYYY');
    },
    clientInfo() {
      const ua = navigator.userAgent;
      if (ua.includes('Edg')) return 'Edge / Windows';
      if (ua.includes('Chrome')) return 'Chrome / Web';
      if (ua.includes('Firefox')) return 'Firefox';
      if (ua.includes('Safari')) return 'Safari';
      return 'Web Client';
    },
    hasChanges() {
      return (
        this.form.name !== this.original.name ||
        this.form.email !== this.original.email ||
        (Boolean(this.form.password) && this.form.password.length >= 6)
      );
    },
    passwordStrengthScore() {
      const p = this.form.password || '';
      if (!p) return 0;
      let score = 0;
      if (p.length >= 6) score += 30;
      if (p.length >= 10) score += 20;
      if (/[A-Z]/.test(p)) score += 20;
      if (/[0-9]/.test(p)) score += 15;
      if (/[^A-Za-z0-9]/.test(p)) score += 15;
      return Math.min(score, 100);
    },
    passwordStrengthLabel() {
      const s = this.passwordStrengthScore;
      if (s < 40) return this.$t('strength_weak') || 'Weak';
      if (s < 75) return this.$t('strength_medium') || 'Medium';
      return this.$t('strength_strong') || 'Strong';
    },
    passwordStrengthColor() {
      const s = this.passwordStrengthScore;
      if (s < 40) return '#F44336';
      if (s < 75) return '#FB8C00';
      return '#4CAF50';
    },
  },
  mounted() {
    this.loadUser();
    this.webPush.init();
  },
  methods: {
    loadUser() {
      const u = this.auth.user;
      if (!u) return;
      this.form.name = u.name || '';
      this.form.email = u.email || '';
      this.form.password = '';
      this.form.password_confirmation = '';
      this.original = { name: u.name || '', email: u.email || '' };
    },
    resetForm() {
      this.loadUser();
      this.showPass = false;
      this.showConfirm = false;
    },
    setTheme(isDark) {
      this.main.is_dark = isDark;
      const theme = isDark ? 'dark' : 'light';
      if (this.$vuetify?.theme?.change) {
        this.$vuetify.theme.change(theme);
      } else if (this.$vuetify?.theme?.global) {
        this.$vuetify.theme.global.name = theme;
      }
      localStorage.setItem('theme', theme);
    },
    changeLang(langCode) {
      this.$i18n.locale = langCode;
      localStorage.setItem('language', langCode);
      const matched = this.main.langs?.find(l => l.code === langCode);
      if (matched) {
        this.main.lang = matched;
      }
    },
    async saveProfile() {
      const { valid } = await this.$refs.profileForm.validate();
      if (!valid) return;

      this.saving = true;
      try {
        const payload = {
          name: this.form.name,
          email: this.form.email
        };
        if (this.form.password) {
          payload.password = this.form.password;
          payload.password_confirmation = this.form.password_confirmation;
        }
        const res = await axios.post('/api/update', payload);
        this.auth.user = res.data.user;
        this.loadUser();
        this.showSnackbar(true, this.$t('profile_updated_success'));
      } catch (err) {
        const msg = err.response?.data?.message || this.$t('profile_update_failed');
        this.showSnackbar(false, msg);
      }
      this.saving = false;
    },
    showSnackbar(success, message) {
      clearInterval(this.snackbarTimer);
      this.snackbar = { show: true, success, message };
      this.snackbarProgress = 100;
      this.snackbarTimer = setInterval(() => {
        this.snackbarProgress -= 2;
        if (this.snackbarProgress <= 0) {
          clearInterval(this.snackbarTimer);
        }
      }, 100);
    },
  },
};
</script>

<style scoped>
.profile-card {
  border-radius: 20px !important;
  font-family: 'Inter', sans-serif;
  overflow: hidden;
}

.profile-hero {
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%);
  border-radius: inherit;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  min-height: 96px;
}

.profile-hero-inner {
  position: relative;
  z-index: 1;
  padding: 22px 28px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.profile-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-hero-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 0.3px;
}

.profile-hero-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.profile-panel {
  border: 1.5px solid rgba(25, 118, 210, 0.12);
  border-radius: 16px;
  background: rgba(var(--v-theme-surface), 0.98);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
  transition: border-color 0.2s, box-shadow 0.2s;
}

.profile-panel:hover {
  border-color: rgba(25, 118, 210, 0.22);
}

.panel-section-title {
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #1976D2;
  display: flex;
  align-items: center;
}

.profile-avatar-wrap {
  position: relative;
  display: inline-block;
}

.profile-avatar {
  background: linear-gradient(135deg, #0D47A1, #1976D2, #42A5F5) !important;
  box-shadow: 0 6px 24px rgba(25, 118, 210, 0.35);
  border: 3px solid rgba(255, 255, 255, 0.2);
}

.status-beacon {
  position: absolute;
  bottom: 4px;
  right: 6px;
  width: 15px;
  height: 15px;
  border-radius: 50%;
  background: #4CAF50;
  border: 2.5px solid rgb(var(--v-theme-surface));
  box-shadow: 0 0 10px rgba(76, 175, 80, 0.6);
  animation: beacon-pulse 2s infinite ease-in-out;
}

@keyframes beacon-pulse {
  0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
  70% { box-shadow: 0 0 0 7px rgba(76, 175, 80, 0); }
  100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
}

.profile-meta-list {
  font-size: 12px;
}

.session-info-box {
  background: rgba(var(--v-theme-on-surface), 0.03);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.super-admin-banner {
  background: rgba(25, 118, 210, 0.06);
  border: 1px solid rgba(25, 118, 210, 0.18);
}
</style>
