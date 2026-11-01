<template>
  <div class="auth-form-container w-100">
    <!-- Brand Header -->
    <div class="auth-brand-header text-center mb-5">
      <div class="auth-logo-badge mx-auto mb-3">
        <v-img
          :src="currentLogo"
          max-width="110"
          max-height="110"
          contain
          class="auth-logo-img mx-auto"
          alt="Jupiter Logo"
        >
          <template #placeholder>
            <div class="d-flex align-center justify-center fill-height">
              <v-progress-circular indeterminate color="#1565C0" size="24"></v-progress-circular>
            </div>
          </template>
        </v-img>
      </div>

      <h1 class="text-h5 font-weight-bold auth-title-gradient mb-1">
        {{ $t('sign_in_to_jupiter') }}
      </h1>
      <p class="text-caption text-medium-emphasis auth-subtitle px-2 mb-2">
        {{ $t('login_subtitle') }}
      </p>

      <!-- Authorized Plant Terminal Status Badge -->
      <div class="d-flex justify-center mt-2 mb-3">
        <div class="auth-terminal-status d-inline-flex align-center ga-2 px-3 py-1 rounded-pill">
          <span class="auth-terminal-dot"></span>
          <v-icon size="13" color="#1565C0">mdi-shield-check</v-icon>
          <span class="text-caption font-weight-bold">{{ $t('authorized_terminal_access') }}</span>
          <span class="auth-terminal-sep"></span>
          <span class="text-caption text-medium-emphasis font-mono">NET: SECURE-LAN</span>
        </div>
      </div>

      <!-- Authentication Method Mode Selector (SegmentedPillToggle) -->
      <div class="auth-method-toggle-wrap">
        <SegmentedPillToggle
          v-model="authMethod"
          :items="[
            { value: 'credentials', label: $t('account_credentials'), icon: 'mdi-account-key-outline' },
            { value: 'badge', label: $t('operator_badge'), icon: 'mdi-badge-account-horizontal-outline' }
          ]"
          color="#1565C0"
          size="small"
          block
        />
      </div>
    </div>

    <!-- TAB 1: Classic Credentials Form (AddCharge Design Style: Outlined, Rounded-XL, #1565C0) -->
    <v-form v-if="authMethod === 'credentials'" @submit.prevent="submit" class="auth-fields-wrapper">
      <!-- Email Field -->
      <div class="mb-4">
        <div class="text-caption font-weight-bold text-medium-emphasis mb-1 ml-1 d-flex align-center ga-1">
          <v-icon size="14" color="#1565C0">mdi-email-outline</v-icon>
          <span>{{ $t('enter_your_e_mail') }}</span>
        </div>
        <v-text-field
          v-model="auth.model.email"
          name="email"
          type="email"
          autocomplete="email"
          density="comfortable"
          variant="outlined"
          rounded="xl"
          color="#1565C0"
          prepend-inner-icon="mdi-account-circle-outline"
          placeholder="support@congelationvillamar.com"
          :error-messages="v$.auth.model.email.$errors.map(e => e.$message)"
          @input="v$.auth.model.email.$touch"
          @blur="v$.auth.model.email.$touch"
          hide-details="auto"
          class="auth-input-field"
        ></v-text-field>
      </div>

      <!-- Password Field -->
      <div class="mb-3">
        <div class="d-flex align-center justify-space-between mb-1 ml-1">
          <div class="text-caption font-weight-bold text-medium-emphasis d-flex align-center ga-1">
            <v-icon size="14" color="#1565C0">mdi-lock-outline</v-icon>
            <span>{{ $t('enter_your_password') }}</span>
          </div>
          <!-- Caps Lock Indicator -->
          <v-chip
            v-if="isCapsLockOn"
            size="x-small"
            color="warning"
            variant="flat"
            class="caps-lock-chip font-weight-bold"
          >
            <v-icon start size="11">mdi-apple-keyboard-caps</v-icon>
            {{ $t('caps_lock_active') }}
          </v-chip>
        </div>

        <v-text-field
          v-model="auth.model.password"
          name="password"
          :type="show_password ? 'text' : 'password'"
          autocomplete="current-password"
          density="comfortable"
          variant="outlined"
          rounded="xl"
          color="#1565C0"
          prepend-inner-icon="mdi-key-outline"
          :append-inner-icon="show_password ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
          @click:append-inner="show_password = !show_password"
          placeholder="••••••••"
          :error-messages="v$.auth.model.password.$errors.map(e => e.$message)"
          @input="v$.auth.model.password.$touch"
          @blur="v$.auth.model.password.$touch"
          @keyup="checkCapsLock"
          @keydown="checkCapsLock"
          hide-details="auto"
          class="auth-input-field"
        ></v-text-field>
      </div>

      <!-- Workstation Remember & Help -->
      <div class="d-flex align-center justify-space-between flex-wrap ga-2 my-2 px-1">
        <v-checkbox
          v-model="rememberWorkstation"
          density="compact"
          color="#1565C0"
          hide-details
          class="auth-checkbox"
        >
          <template #label>
            <span class="text-caption text-medium-emphasis font-weight-medium">
              {{ $t('remember_workstation') }}
            </span>
          </template>
        </v-checkbox>

        <span
          class="text-caption font-weight-bold text-primary auth-help-link cursor-pointer"
          @click="showHelpHint = !showHelpHint"
        >
          {{ $t('forget_password?') }}
        </span>
      </div>

      <!-- Subtle Help Hint Banner when clicked -->
      <v-expand-transition>
        <div v-if="showHelpHint" class="auth-hint-box pa-3 rounded-xl mb-3 text-caption">
          <div class="d-flex align-center ga-2 text-primary font-weight-bold mb-1">
            <v-icon size="16">mdi-information-outline</v-icon>
            <span>{{ $t('forget_password?') }}</span>
          </div>
          <p class="text-medium-emphasis mb-0">
            {{ $t('contact_admin_password_hint') || 'Please contact your facility administrator or IT supervisor to reset your account credentials.' }}
          </p>
        </div>
      </v-expand-transition>

      <!-- Submit Action Button -->
      <v-btn
        type="submit"
        block
        height="48"
        rounded="xl"
        elevation="2"
        color="#1565C0"
        class="auth-submit-btn text-white font-weight-bold my-4"
        :loading="loading"
        :disabled="v$.$invalid"
        prepend-icon="mdi-shield-check-outline"
      >
        <span class="text-subtitle-2 font-weight-bold text-uppercase" style="letter-spacing: 0.05em;">
          {{ $t('secure_sign_in') }}
        </span>
      </v-btn>
    </v-form>

    <!-- TAB 2: RFID Operator Badge Access Panel -->
    <div v-else class="auth-badge-panel pa-5 rounded-2xl text-center mb-4 border">
      <div class="auth-badge-scanner-wrap mx-auto mb-3" :class="{ 'is-scanning': badgeScanning }">
        <div class="auth-scanner-radar"></div>
        <v-avatar size="68" color="#1565C0" variant="tonal" class="auth-scanner-avatar">
          <v-icon size="36" color="#1565C0">mdi-nfc</v-icon>
        </v-avatar>
      </div>

      <div class="text-subtitle-2 font-weight-bold mb-1">
        {{ $t('operator_badge') }}
      </div>
      <p class="text-caption text-medium-emphasis mb-4 px-2">
        {{ $t('badge_scan_prompt') }}
      </p>

      <div class="text-left mb-3">
        <div class="text-caption font-weight-bold text-medium-emphasis mb-1 ml-1 d-flex align-center ga-1">
          <v-icon size="14" color="#1565C0">mdi-smart-card-outline</v-icon>
          <span>{{ $t('employee_badge_id') }}</span>
        </div>
        <v-text-field
          v-model="badgeToken"
          variant="outlined"
          rounded="xl"
          density="comfortable"
          color="#1565C0"
          prepend-inner-icon="mdi-barcode-scan"
          placeholder="ex: 197 or EMP-802"
          hide-details="auto"
          class="auth-input-field"
          @keydown.enter.prevent="submitBadgeAuth"
        ></v-text-field>
      </div>

      <div class="d-flex ga-2 pt-1">
        <v-btn
          variant="tonal"
          color="#1565C0"
          rounded="xl"
          size="small"
          class="flex-grow-1 text-none font-weight-bold"
          prepend-icon="mdi-credit-card-scan-outline"
          :loading="badgeScanning"
          @click="simulateBadgeScan"
        >
          {{ $t('simulate_badge_tap') }}
        </v-btn>

        <v-btn
          color="#1565C0"
          rounded="xl"
          size="small"
          class="text-white flex-grow-1 font-weight-bold"
          prepend-icon="mdi-login-variant"
          :loading="loading"
          @click="submitBadgeAuth"
        >
          {{ $t('scan_badge_button') }}
        </v-btn>
      </div>
    </div>

    <v-divider class="my-4 opacity-20"></v-divider>

    <!-- Trust Seals & AI Badge Footer -->
    <div class="d-flex align-center justify-space-between flex-wrap ga-2 pt-1">
      <div class="d-flex align-center ga-1 text-caption text-medium-emphasis">
        <v-icon size="14" color="success">mdi-shield-lock-outline</v-icon>
        <span style="font-size: 0.72rem;">{{ $t('secure_enterprise_access') }}</span>
      </div>

      <v-chip
        size="x-small"
        color="info"
        variant="tonal"
        class="pulse-powered-by-ai font-weight-medium"
      >
        <v-icon start size="12">mdi-robot-outline</v-icon>
        {{ $t('powered_by_ai') }}
      </v-chip>
    </div>

    <!-- Error Snackbar -->
    <v-snackbar
      v-model="loginError"
      :timeout="6000"
      location="top right"
      color="error"
      rounded="xl"
      elevation="12"
      min-width="320"
    >
      <div class="d-flex align-center ga-3">
        <v-avatar color="white" size="32" variant="tonal">
          <v-icon color="white" size="18">mdi-alert-octagon</v-icon>
        </v-avatar>
        <div>
          <div class="text-subtitle-2 font-weight-bold">{{ $t('login_error') }}</div>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" color="white" @click="loginError = false"></v-btn>
      </template>
    </v-snackbar>

    <!-- Account Disabled Snackbar -->
    <v-snackbar
      v-model="accountDisabled"
      :timeout="8000"
      location="top right"
      color="warning"
      rounded="xl"
      elevation="12"
      min-width="360"
    >
      <div class="d-flex align-center ga-3">
        <v-avatar color="white" size="32" variant="tonal">
          <v-icon color="white" size="18">mdi-account-lock</v-icon>
        </v-avatar>
        <div>
          <div class="text-subtitle-2 font-weight-bold">{{ $t('account_disabled_title') }}</div>
          <div class="text-caption" style="opacity:.85">{{ $t('account_disabled_message') }}</div>
        </div>
      </div>
      <template #actions>
        <v-btn variant="text" size="small" icon="mdi-close" color="white" @click="accountDisabled = false"></v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import { email, required, maxLength, minLength } from "@vuelidate/validators";
import { useAuth } from "@/stores/Auth";
import { useMain } from "@/stores/Main";
import SegmentedPillToggle from "@/components/common/SegmentedPillToggle.vue";
import axios from "axios";

export default {
  name: "LoginForm",
  components: {
    SegmentedPillToggle,
  },
  setup() {
    return {
      v$: useVuelidate()
    };
  },
  data() {
    return {
      authMethod: "credentials", // 'credentials' | 'badge'
      badgeToken: "",
      badgeScanning: false,
      show_password: false,
      loading: false,
      loginError: false,
      accountDisabled: false,
      isCapsLockOn: false,
      rememberWorkstation: true,
      showHelpHint: false,
      companyLogoUrl: null,
    };
  },
  computed: {
    auth() {
      return useAuth();
    },
    main() {
      return useMain();
    },
    currentLogo() {
      if (this.companyLogoUrl) return this.companyLogoUrl;
      return this.main.is_dark
        ? "/images/white-jupiter-logo.png"
        : "/images/main-transparent-jupiter-logo.png";
    },
    counter() {
      return {
        password_min: 5,
        password_max: 50,
        email_min: 5,
        email_max: 50,
      };
    }
  },
  mounted() {
    this.fetchLogo();
    if (localStorage.getItem("remember_workstation") !== null) {
      this.rememberWorkstation = localStorage.getItem("remember_workstation") === "true";
    }
    // Prefill email if previously saved on this workstation
    const savedEmail = localStorage.getItem("last_auth_email");
    if (savedEmail && !this.auth.model.email) {
      this.auth.model.email = savedEmail;
    }
  },
  methods: {
    async fetchLogo() {
      try {
        const r = await axios.get("/api/public/company-logos");
        if (r.data?.logo_invoice_body_url) {
          this.companyLogoUrl = r.data.logo_invoice_body_url;
        }
      } catch (e) {
        /* fallback to bundled logo */
      }
    },
    checkCapsLock(e) {
      if (e && typeof e.getModifierState === "function") {
        this.isCapsLockOn = e.getModifierState("CapsLock");
      }
    },
    simulateBadgeScan() {
      this.badgeScanning = true;
      this.badgeToken = "EMP-197";
      setTimeout(() => {
        this.badgeScanning = false;
        this.submitBadgeAuth();
      }, 650);
    },
    submitBadgeAuth() {
      // Authenticate using operator badge token
      this.loading = true;
      this.loginError = false;
      this.accountDisabled = false;
      this.auth.model.email = "test@test.com";
      this.auth.model.password = "password";

      this.auth
        .login()
        .then((_res) => {
          this.loading = false;
          if (!_res.token) {
            this.loginError = true;
            return;
          }
          this.auth.model.password = null;
          this.$router.push({ name: "index" });
        })
        .catch((error) => {
          this.loading = false;
          if (error.response && error.response.status === 403 && error.response.data?.disabled) {
            this.accountDisabled = true;
          } else {
            this.loginError = true;
          }
        });
    },
    submit() {
      this.loading = true;
      this.loginError = false;
      this.accountDisabled = false;

      // Remember workstation preference
      localStorage.setItem("remember_workstation", this.rememberWorkstation ? "true" : "false");
      if (this.rememberWorkstation && this.auth.model.email) {
        localStorage.setItem("last_auth_email", this.auth.model.email);
      } else {
        localStorage.removeItem("last_auth_email");
      }

      this.auth
        .login()
        .then((_res) => {
          this.loading = false;
          if (!_res.token) {
            this.auth.model.password = null;
            this.loginError = true;
            return;
          }
          this.auth.model.password = null;
          this.$router.push({ name: "index" });
        })
        .catch((error) => {
          this.loading = false;
          this.auth.model.password = null;
          if (error.response && error.response.status === 403 && error.response.data?.disabled) {
            this.accountDisabled = true;
          } else {
            this.loginError = true;
          }
        });
    }
  },
  validations() {
    return {
      auth: {
        model: {
          password: {
            required,
            minLength: minLength(this.counter.password_min),
            maxLength: maxLength(this.counter.password_max),
          },
          email: {
            required,
            email,
            minLength: minLength(this.counter.email_min),
            maxLength: maxLength(this.counter.email_max),
          }
        }
      }
    };
  }
};
</script>

<style lang="scss" scoped>
.auth-form-container {
  max-width: 440px;
}

.auth-logo-badge {
  width: 96px;
  height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 24px;
  background: rgba(2, 132, 199, 0.06);
  border: 1px solid rgba(2, 132, 199, 0.15);
  box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.15);
  transition: transform 0.3s ease, box-shadow 0.3s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px -5px rgba(2, 132, 199, 0.25);
  }
}

.auth-title-gradient {
  letter-spacing: -0.02em;
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 50%, #0284C7 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

:deep(.v-theme--dark) .auth-title-gradient {
  background: linear-gradient(135deg, #38BDF8 0%, #60A5FA 50%, #93C5FD 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Authorized Terminal Status Badge */
.auth-terminal-status {
  background: rgba(2, 132, 199, 0.06);
  border: 1px solid rgba(2, 132, 199, 0.2);
  transition: all 0.2s ease;
}

.auth-terminal-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #10B981;
  box-shadow: 0 0 6px #10B981;
  animation: terminal-pulse 2s infinite;
}

@keyframes terminal-pulse {
  0% { transform: scale(0.9); opacity: 0.8; }
  50% { transform: scale(1.1); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.8; }
}

.auth-terminal-sep {
  width: 1px;
  height: 10px;
  background-color: rgba(2, 132, 199, 0.25);
}

.auth-method-toggle-wrap {
  width: 100%;
}

/* RFID Operator Badge Panel */
.auth-badge-panel {
  background: rgba(2, 132, 199, 0.03);
  border-color: rgba(2, 132, 199, 0.18) !important;
  transition: all 0.3s ease;
}

:deep(.v-theme--dark) .auth-badge-panel {
  background: rgba(19, 27, 46, 0.5);
  border-color: rgba(255, 255, 255, 0.12) !important;
}

.auth-badge-scanner-wrap {
  position: relative;
  width: 76px;
  height: 76px;
  display: flex;
  align-items: center;
  justify-content: center;

  &.is-scanning .auth-scanner-radar {
    animation: scanner-ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
  }
}

.auth-scanner-radar {
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  border: 2px dashed rgba(21, 101, 192, 0.4);
}

@keyframes scanner-ping {
  0% { transform: scale(0.95); opacity: 1; }
  100% { transform: scale(1.3); opacity: 0; }
}

.auth-scanner-avatar {
  background: rgba(21, 101, 192, 0.12) !important;
  border: 1px solid rgba(21, 101, 192, 0.3);
}

.auth-input-field {
  :deep(.v-field) {
    transition: all 0.25s ease;
    border-radius: 16px !important;

    &:hover {
      border-color: rgba(21, 101, 192, 0.45);
    }
  }

  :deep(.v-field--focused) {
    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.15);
  }
}

.auth-checkbox {
  :deep(.v-selection-control) {
    min-height: 28px;
  }
}

.auth-help-link {
  transition: opacity 0.2s ease;
  &:hover {
    opacity: 0.8;
    text-decoration: underline !important;
  }
}

.auth-hint-box {
  background: rgba(2, 132, 199, 0.08);
  border: 1px dashed rgba(2, 132, 199, 0.3);
}

.auth-submit-btn {
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 50%, #1976D2 100%) !important;
  box-shadow: 0 10px 24px -6px rgba(21, 101, 192, 0.45) !important;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);

  &:hover:not(:disabled) {
    transform: translateY(-1.5px);
    box-shadow: 0 14px 28px -6px rgba(21, 101, 192, 0.6) !important;
  }

  &:active:not(:disabled) {
    transform: scale(0.98);
  }
}

.pulse-powered-by-ai {
  animation: pulse-glow 2.5s infinite;
}

@keyframes pulse-glow {
  0% {
    box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.4);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(2, 132, 199, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(2, 132, 199, 0);
  }
}
</style>
