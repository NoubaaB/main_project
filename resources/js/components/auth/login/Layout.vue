<template>
  <div class="auth-layout-shell d-flex align-center justify-center">
    <!-- Atmospheric Ambient Glow Orbs -->
    <div class="auth-ambient-orb orb-primary"></div>
    <div class="auth-ambient-orb orb-secondary"></div>
    <div class="auth-ambient-grid"></div>

    <!-- Centered Glass Master Card Container -->
    <v-card class="auth-master-card rounded-3xl" elevation="12">
      <v-row no-gutters class="fill-height">
        <!-- ======================================================== -->
        <!-- LEFT COLUMN: Industrial Visual Showcase & Plant Authority -->
        <!-- ======================================================== -->
        <v-col
          cols="12"
          md="6"
          lg="7"
          class="auth-visual-col d-flex flex-column justify-space-between pa-6 pa-sm-8 pa-lg-10"
        >
          <!-- Glassmorphic Gradient Scrim Overlay -->
          <div class="auth-scrim-overlay"></div>

          <!-- Top Cluster: Plant Identity & Live Telemetry Status -->
          <div class="auth-visual-top d-flex align-center justify-space-between flex-wrap ga-3 position-relative" style="z-index: 2;">
            <div class="d-flex align-center ga-3">
              <v-avatar size="44" class="auth-top-avatar elevation-3">
                <v-img src="/images/white-jupiter-logo.png" alt="Jupiter Logo" contain></v-img>
              </v-avatar>
              <div>
                <div class="text-subtitle-1 font-weight-black text-white text-uppercase" style="letter-spacing: 0.1em; line-height: 1.2;">
                  JUPITER
                </div>
                <div class="text-caption text-white opacity-80" style="letter-spacing: 0.03em;">
                  {{ $t('operational_management_suite') }}
                </div>
              </div>
            </div>

            <!-- Live Status Indicator -->
            <v-chip
              size="small"
              variant="flat"
              class="auth-status-chip text-white font-weight-bold"
            >
              <span class="auth-live-dot mr-1"></span>
              {{ $t('system_status_operational') }}
            </v-chip>
          </div>

          <!-- Center Cluster: Floating Industrial Highlights (3 Cards) -->
          <div class="auth-visual-center my-6 my-lg-8 position-relative" style="z-index: 2;">
            <div class="auth-feature-cards-grid d-flex flex-column ga-3">
              <!-- Feature 1: Cold Chain & Blast Freezing -->
              <div class="auth-feature-pill d-flex align-center ga-3 pa-3 pa-sm-4 rounded-xl">
                <div class="auth-feature-icon-wrap cold-accent">
                  <v-icon size="22" color="cyan-lighten-3">mdi-snowflake</v-icon>
                </div>
                <div>
                  <div class="text-subtitle-2 font-weight-bold text-white">
                    {{ $t('cold_chain_telemetry') }}
                  </div>
                  <div class="text-caption text-white opacity-80">
                    {{ $t('cold_chain_desc') }}
                  </div>
                </div>
              </div>

              <!-- Feature 2: Dynamic Yield Control -->
              <div class="auth-feature-pill d-flex align-center ga-3 pa-3 pa-sm-4 rounded-xl">
                <div class="auth-feature-icon-wrap yield-accent">
                  <v-icon size="22" color="teal-lighten-3">mdi-scale-balance</v-icon>
                </div>
                <div>
                  <div class="text-subtitle-2 font-weight-bold text-white">
                    {{ $t('production_yield_control') }}
                  </div>
                  <div class="text-caption text-white opacity-80">
                    {{ $t('production_yield_desc') }}
                  </div>
                </div>
              </div>

              <!-- Feature 3: Certified Traceability -->
              <div class="auth-feature-pill d-flex align-center ga-3 pa-3 pa-sm-4 rounded-xl">
                <div class="auth-feature-icon-wrap trace-accent">
                  <v-icon size="22" color="amber-lighten-3">mdi-shield-check</v-icon>
                </div>
                <div>
                  <div class="text-subtitle-2 font-weight-bold text-white">
                    {{ $t('traceability_audit') }}
                  </div>
                  <div class="text-caption text-white opacity-80">
                    {{ $t('traceability_desc') }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Cluster: Tagline & Certified Metric Pills -->
          <div class="auth-visual-bottom position-relative" style="z-index: 2;">
            <p class="text-caption text-white font-italic opacity-90 mb-3" style="letter-spacing: 0.03em;">
              "Streamline Operations. Ensure Quality. Maximize Efficiency."
            </p>
            <div class="d-flex align-center flex-wrap ga-2">
              <v-chip size="x-small" variant="tonal" color="white" class="font-weight-medium">
                <v-icon start size="12">mdi-thermometer-low</v-icon>
                4 Chambers (-25°C)
              </v-chip>
              <v-chip size="x-small" variant="tonal" color="white" class="font-weight-medium">
                <v-icon start size="12">mdi-wind-power</v-icon>
                2 Blast Tunnels (-40°C)
              </v-chip>
              <v-chip size="x-small" variant="tonal" color="white" class="font-weight-medium">
                <v-icon start size="12">mdi-certificate-outline</v-icon>
                HACCP & ONP Certified
              </v-chip>
            </div>
          </div>
        </v-col>

        <!-- ======================================================== -->
        <!-- RIGHT COLUMN: Authentication Form Component             -->
        <!-- ======================================================== -->
        <v-col
          cols="12"
          md="6"
          lg="5"
          class="auth-form-col d-flex align-center justify-center pa-6 pa-sm-8 pa-lg-12"
        >
          <LoginForm />
        </v-col>
      </v-row>
    </v-card>
  </div>
</template>

<script>
import LoginForm from "./Form.vue";

export default {
  name: "LoginLayout",
  components: {
    LoginForm,
  },
};
</script>

<style lang="scss" scoped>
.auth-layout-shell {
  position: relative;
  width: 100%;
  min-height: calc(100vh - 58px);
  padding: 24px 16px;
  overflow: hidden;
  background: radial-gradient(circle at 10% 20%, rgba(2, 132, 199, 0.08) 0%, rgba(248, 250, 252, 0.98) 70%);
}

:deep(.v-theme--dark) .auth-layout-shell,
.v-theme--dark .auth-layout-shell {
  background: radial-gradient(circle at 10% 20%, rgba(14, 165, 233, 0.12) 0%, #0B0F19 80%);
}

/* Ambient Background Lights */
.auth-ambient-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
  opacity: 0.6;
  z-index: 0;
  transition: opacity 0.5s ease;
}

.orb-primary {
  width: 420px;
  height: 420px;
  top: -80px;
  left: -80px;
  background: radial-gradient(circle, rgba(14, 165, 233, 0.28) 0%, rgba(14, 165, 233, 0) 70%);
}

.orb-secondary {
  width: 460px;
  height: 460px;
  bottom: -100px;
  right: -80px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.22) 0%, rgba(99, 102, 241, 0) 70%);
}

.auth-ambient-grid {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(14, 165, 233, 0.12) 1px, transparent 1px);
  background-size: 32px 32px;
  pointer-events: none;
  z-index: 0;
  opacity: 0.65;
}

/* Glass Master Card */
.auth-master-card {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1220px;
  min-height: 640px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.88) !important;
  backdrop-filter: blur(20px);
  border: 1px solid rgba(2, 132, 199, 0.15) !important;
  box-shadow: 0 25px 60px -15px rgba(2, 44, 98, 0.18), 0 0 0 1px rgba(255, 255, 255, 0.5) inset !important;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

:deep(.v-theme--dark) .auth-master-card,
.v-theme--dark .auth-master-card {
  background: rgba(19, 27, 46, 0.88) !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08) inset !important;
}

/* Left Column Styling */
.auth-visual-col {
  position: relative;
  background: url('/images/login_template.png') center / cover no-repeat;
  overflow: hidden;
  min-height: 580px;

  @media (max-width: 959px) {
    min-height: 380px;
  }
}

.auth-scrim-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(13, 71, 161, 0.72) 0%, rgba(2, 44, 98, 0.86) 100%);
  backdrop-filter: blur(3px);
  z-index: 1;
}

:deep(.v-theme--dark) .auth-scrim-overlay,
.v-theme--dark .auth-scrim-overlay {
  background: linear-gradient(135deg, rgba(8, 47, 73, 0.75) 0%, rgba(11, 15, 25, 0.92) 100%);
}

.auth-top-avatar {
  background: rgba(255, 255, 255, 0.15) !important;
  border: 1px solid rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(8px);
}

.auth-status-chip {
  background: rgba(16, 185, 129, 0.22) !important;
  border: 1px solid rgba(16, 185, 129, 0.45);
  backdrop-filter: blur(6px);
  font-size: 0.75rem;
}

.auth-live-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #34D399;
  box-shadow: 0 0 8px #34D399;
  animation: pulse-dot 1.8s infinite;
}

@keyframes pulse-dot {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 6px rgba(52, 211, 153, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(52, 211, 153, 0);
  }
}

/* Feature Pills in Left Column */
.auth-feature-pill {
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(12px);
  transition: transform 0.25s ease, background-color 0.25s ease, border-color 0.25s ease;

  &:hover {
    transform: translateX(4px);
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.35);
  }
}

.auth-feature-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  &.cold-accent {
    background: rgba(6, 182, 212, 0.25);
    border: 1px solid rgba(6, 182, 212, 0.45);
  }

  &.yield-accent {
    background: rgba(20, 184, 166, 0.25);
    border: 1px solid rgba(20, 184, 166, 0.45);
  }

  &.trace-accent {
    background: rgba(245, 158, 11, 0.25);
    border: 1px solid rgba(245, 158, 11, 0.45);
  }
}

/* Right Form Column */
.auth-form-col {
  background-color: transparent;
}
</style>
