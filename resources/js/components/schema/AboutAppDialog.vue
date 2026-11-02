<template>
  <v-dialog
    v-model="internalModel"
    max-width="520"
    transition="dialog-bottom-transition"
    class="about-app-dialog"
  >
    <v-card
      class="about-card rounded-2xl overflow-hidden"
      :class="main.is_dark ? 'about-card--dark' : 'about-card--light'"
      elevation="16"
    >
      <!-- Hero Banner with gradient -->
      <div class="about-hero position-relative pa-6 text-center">
        <div class="about-hero-glow"></div>

        <!-- Jupiter Orb Logo -->
        <div class="about-logo-wrapper mx-auto mb-3">
          <img
            :src="logoUrl"
            alt="Jupiter ERP"
            class="about-logo-img"
          />
          <span class="about-pulse-dot" :class="isOnline ? 'pulse-live' : 'pulse-cut'"></span>
        </div>

        <h2 class="text-h5 font-weight-black tracking-wide text-white mb-1">
          Jupiter ERP
        </h2>
        <div class="text-caption text-white opacity-80 mb-3">
          {{ $t('operational_management_suite') || 'Operational & Cold Chain Management Suite' }}
        </div>

        <!-- Version Badge -->
        <div class="d-inline-flex align-center ga-2 px-3 py-1.5 rounded-pill about-version-pill">
          <v-icon size="15" color="#42A5F5">mdi-tag-outline</v-icon>
          <span class="font-weight-bold text-white text-caption">v{{ main.app_version }}</span>
          <span class="about-sep"></span>
          <span class="text-caption text-white opacity-90">{{ $t('build') || 'Build' }} {{ main.app_build }}</span>
        </div>
      </div>

      <v-card-text class="pa-5">
        <!-- Details Grid -->
        <div class="about-details-grid mb-4">
          <!-- Build Time -->
          <div class="about-info-item pa-3 rounded-xl">
            <div class="d-flex align-center ga-2 text-caption text-medium-emphasis mb-1">
              <v-icon size="14" color="primary">mdi-calendar-clock</v-icon>
              <span>{{ $t('last_updated') || 'Last Updated' }}</span>
            </div>
            <div class="text-body-2 font-weight-bold">
              {{ main.app_build_time || '-' }}
            </div>
          </div>

          <!-- Commit Hash -->
          <div class="about-info-item pa-3 rounded-xl">
            <div class="d-flex align-center ga-2 text-caption text-medium-emphasis mb-1">
              <v-icon size="14" color="secondary">mdi-source-commit</v-icon>
              <span>{{ $t('git_commit') || 'Git Commit' }}</span>
            </div>
            <div class="text-body-2 font-weight-bold font-mono">
              <code>{{ main.app_commit || 'local' }}</code>
            </div>
          </div>

          <!-- Environment -->
          <div class="about-info-item pa-3 rounded-xl">
            <div class="d-flex align-center ga-2 text-caption text-medium-emphasis mb-1">
              <v-icon size="14" color="info">mdi-server-network</v-icon>
              <span>{{ $t('environment') || 'Environment' }}</span>
            </div>
            <div class="text-body-2 font-weight-bold text-capitalize">
              {{ main.app_env || 'production' }}
            </div>
          </div>

          <!-- Real-Time Connectivity -->
          <div class="about-info-item pa-3 rounded-xl">
            <div class="d-flex align-center ga-2 text-caption text-medium-emphasis mb-1">
              <v-icon size="14" :color="isSocketConnected ? 'success' : 'warning'">
                {{ isSocketConnected ? 'mdi-lightning-bolt' : 'mdi-lightning-bolt-outline' }}
              </v-icon>
              <span>{{ $t('realtime_sync') || 'Realtime Sync' }}</span>
            </div>
            <div class="text-body-2 font-weight-bold" :class="isSocketConnected ? 'text-success' : 'text-warning'">
              {{ isSocketConnected ? ($t('connected') || 'Connected') : ($t('reconnecting') || 'Connecting...') }}
            </div>
          </div>
        </div>

        <!-- Tech Stack Pill Strip -->
        <div class="d-flex align-center justify-center flex-wrap ga-2 pt-1 mb-2">
          <v-chip size="x-small" variant="tonal" color="primary">Vue 3.5</v-chip>
          <v-chip size="x-small" variant="tonal" color="info">Vuetify 3</v-chip>
          <v-chip size="x-small" variant="tonal" color="error">Laravel 12</v-chip>
          <v-chip size="x-small" variant="tonal" color="purple">PHP 8.2</v-chip>
          <v-chip size="x-small" variant="tonal" color="teal">Pusher / Echo</v-chip>
        </div>
      </v-card-text>

      <v-divider></v-divider>

      <!-- Actions Footer -->
      <v-card-actions class="pa-4 d-flex align-center justify-space-between">
        <v-btn
          variant="tonal"
          color="primary"
          rounded="xl"
          size="small"
          prepend-icon="mdi-refresh"
          :loading="checking"
          @click="checkUpdate"
        >
          {{ $t('check_for_updates') || 'Check for Updates' }}
        </v-btn>

        <v-btn
          variant="text"
          rounded="xl"
          size="small"
          @click="internalModel = false"
        >
          {{ $t('close') || 'Close' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { usePwa } from '@/stores/Pwa';
import { useOnlineUsers } from '@/stores/OnlineUsers';

export default {
  name: 'AboutAppDialog',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      main: useMain(),
      pwa: usePwa(),
      onlineUsers: useOnlineUsers(),
      checking: false,
    };
  },
  computed: {
    internalModel: {
      get() {
        return this.modelValue;
      },
      set(val) {
        this.$emit('update:modelValue', val);
      }
    },
    logoUrl() {
      return '/images/white-jupiter-logo.png';
    },
    isOnline() {
      return this.pwa.is_online;
    },
    isSocketConnected() {
      return Boolean(window.Echo && window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection.state === 'connected');
    }
  },
  methods: {
    async checkUpdate() {
      this.checking = true;
      try {
        const oldVersion = this.main.app_version;
        await this.main.fetchAppVersion();
        const newVersion = this.main.app_version;

        if (this.main.add_snackbar) {
          if (oldVersion !== newVersion) {
            this.main.add_snackbar({
              show: true,
              message: `${this.$t('new_version_available') || 'New version detected'}: v${newVersion}!`,
              color: 'success'
            });
          } else {
            this.main.add_snackbar({
              show: true,
              message: `${this.$t('app_is_up_to_date') || 'Application is up to date'} (v${newVersion})`,
              color: 'info'
            });
          }
        }
      } finally {
        setTimeout(() => {
          this.checking = false;
        }, 500);
      }
    }
  }
};
</script>

<style scoped>
.about-card--light {
  background: #ffffff !important;
  color: #1e293b !important;
}

.about-card--dark {
  background: #0f172a !important;
  color: #f8fafc !important;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.about-hero {
  background: linear-gradient(135deg, #0D47A1 0%, #1565C0 40%, #42A5F5 100%);
  overflow: hidden;
}

.about-hero-glow {
  position: absolute;
  top: -50%;
  left: 50%;
  transform: translateX(-50%);
  width: 280px;
  height: 280px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.25) 0%, transparent 70%);
  pointer-events: none;
}

.about-logo-wrapper {
  position: relative;
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(8px);
  border: 1.5px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
}

.about-logo-img {
  width: 42px;
  height: 42px;
  object-fit: contain;
}

.about-pulse-dot {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid #ffffff;
}

.pulse-live {
  background-color: #4CAF50;
  box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.35);
}

.pulse-cut {
  background-color: #EF5350;
}

.about-version-pill {
  background: rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.about-sep {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.6);
}

.about-details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.about-info-item {
  background: rgba(var(--v-theme-surface-variant), 0.35);
  border: 1px solid rgba(var(--v-border-color), 0.1);
}

.about-card--dark .about-info-item {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.font-mono {
  font-family: 'JetBrains Mono', 'Roboto Mono', monospace;
}
</style>

