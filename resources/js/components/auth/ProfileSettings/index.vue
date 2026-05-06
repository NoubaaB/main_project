<template>
  <v-card class="profile-card" elevation="0">
    <!-- Hero Header -->
    <div class="profile-hero">
      <div class="profile-hero-inner">
        <div class="d-flex align-center ga-4">
          <div class="profile-hero-icon">
            <v-icon size="28" color="white">mdi-account-cog</v-icon>
          </div>
          <div>
            <div class="profile-hero-title">{{ $t('profile_settings') }}</div>
            <div class="text-white text-caption" style="opacity:.7">{{ $t('profile_settings_desc') }}</div>
          </div>
        </div>
        <div class="profile-hero-actions">
          <v-btn variant="tonal" color="white" rounded size="small" prepend-icon="mdi-arrow-left" @click="$router.back()">{{ $t('back') }}</v-btn>
        </div>
      </div>
    </div>

    <v-card-text class="pa-6">
      <v-row justify="center">
        <v-col cols="12" md="8" lg="6">
          <!-- User Avatar & Role Badge -->
          <div class="d-flex flex-column align-center mb-6">
            <v-avatar size="80" class="profile-avatar mb-3">
              <v-icon size="44" color="white">mdi-account</v-icon>
            </v-avatar>
            <v-chip color="#1976D2" variant="tonal" size="small" prepend-icon="mdi-shield-account">
              {{ auth.user.role?.name || '—' }}
            </v-chip>
          </div>

          <v-form ref="profileForm" @submit.prevent="saveProfile">
            <!-- Section 1: Personal Info -->
            <div class="profile-section mb-4">
              <div class="profile-section-header">
                <v-icon size="18" color="#1976D2" class="mr-2">mdi-account-outline</v-icon>
                <span>{{ $t('personal_information') }}</span>
              </div>

              <v-row dense class="mt-3">
                <v-col cols="12">
                  <v-text-field
                    v-model="form.name"
                    :label="$t('full_name')"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#1976D2"
                    prepend-inner-icon="mdi-account"
                    :rules="[v => !!v || $t('field_required')]"
                    counter="50"
                    maxlength="50"
                    id="profile-name-input"
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="form.email"
                    :label="$t('email')"
                    variant="outlined"
                    density="compact"
                    rounded
                    color="#1976D2"
                    prepend-inner-icon="mdi-email-outline"
                    :rules="[v => !!v || $t('field_required'), v => /.+@.+\..+/.test(v) || $t('invalid_email')]"
                    id="profile-email-input"
                  ></v-text-field>
                </v-col>
              </v-row>
            </div>

            <!-- Section 2: Change Password (Optional) -->
            <div class="profile-section mb-4">
              <div class="profile-section-header">
                <v-icon size="18" color="#1976D2" class="mr-2">mdi-lock-outline</v-icon>
                <span>{{ $t('change_password') }}</span>
                <v-chip size="x-small" color="grey" variant="tonal" class="ml-2">{{ $t('optional') }}</v-chip>
              </div>

              <v-row dense class="mt-3">
                <v-col cols="12">
                  <v-text-field
                    v-model="form.password"
                    :label="$t('new_password')"
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
                  ></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="form.password_confirmation"
                    :label="$t('confirm_password')"
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
                  ></v-text-field>
                </v-col>
              </v-row>
            </div>

            <!-- Actions -->
            <v-row dense class="mt-2">
              <v-col cols="6">
                <v-btn
                  block rounded
                  variant="flat"
                  color="#1976D2"
                  :loading="saving"
                  :disabled="!hasChanges"
                  @click="saveProfile"
                  prepend-icon="mdi-content-save"
                  id="profile-save-btn"
                >{{ $t('save_changes') }}</v-btn>
              </v-col>
              <v-col cols="6">
                <v-btn
                  block rounded
                  variant="tonal"
                  color="red-lighten-1"
                  @click="resetForm"
                  prepend-icon="mdi-undo"
                  id="profile-reset-btn"
                >{{ $t('reset') }}</v-btn>
              </v-col>
            </v-row>
          </v-form>
        </v-col>
      </v-row>
    </v-card-text>

    <!-- Summary Snackbar -->
    <v-snackbar v-model="snackbar.show" :timeout="5000" location="top right" color="surface" rounded="lg" elevation="12" min-width="320" max-width="400">
      <v-progress-linear :model-value="snackbarProgress" color="#1976D2" height="3" class="mb-2" rounded></v-progress-linear>
      <div class="d-flex align-center mb-1">
        <v-icon :color="snackbar.success ? 'success' : 'error'" class="mr-2">{{ snackbar.success ? 'mdi-check-circle' : 'mdi-alert-circle' }}</v-icon>
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
import { useAuth } from '@/stores/Auth';
import { useMain } from '@/stores/Main';

export default {
  name: 'ProfileSettings',
  data() {
    return {
      form: { name: '', email: '', password: '', password_confirmation: '' },
      original: { name: '', email: '' },
      saving: false,
      showPass: false,
      showConfirm: false,
      snackbar: { show: false, success: true, message: '' },
      snackbarProgress: 100,
      snackbarTimer: null,
    };
  },
  computed: {
    auth() { return useAuth(); },
    main() { return useMain(); },
    hasChanges() {
      return this.form.name !== this.original.name
        || this.form.email !== this.original.email
        || (this.form.password && this.form.password.length >= 6);
    },
  },
  mounted() {
    this.loadUser();
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
    async saveProfile() {
      const { valid } = await this.$refs.profileForm.validate();
      if (!valid) return;

      this.saving = true;
      try {
        const payload = { name: this.form.name, email: this.form.email };
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
        if (this.snackbarProgress <= 0) clearInterval(this.snackbarTimer);
      }, 100);
    },
  },
};
</script>

<style scoped>
.profile-card { border-radius: 20px !important; font-family: 'Inter', sans-serif; }
.profile-hero { background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%); border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0; min-height: 90px; }
.profile-hero-inner { position: relative; z-index: 1; padding: 20px 24px; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.profile-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.18); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.15); }
.profile-hero-title { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
.profile-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.profile-avatar { background: linear-gradient(135deg, #1565C0, #42A5F5) !important; box-shadow: 0 4px 20px rgba(25, 118, 210, .3); }
.profile-section { border: 1.5px solid rgba(25, 118, 210, .12); border-radius: 14px; padding: 16px; background: rgba(25, 118, 210, .02); }
.profile-section-header { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #1976D2; display: flex; align-items: center; }
</style>
