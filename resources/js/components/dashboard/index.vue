<template>
  <div :class="['dashboard', main.is_dark ? 'dashboard-dark' : 'dashboard-light']" class="pb-1 rounded-xl">

    <!-- Hero Section with Logo -->
    <div class="dashboard-hero">
      <div class="dashboard-hero-content">
        <img :src="logoUrl" alt="Jupiter" class="dashboard-logo" />
        <h1 class="dashboard-title">{{ $t('welcome_back') }}, <span class="dashboard-user-name">{{ auth.user?.name }}</span></h1>
        <p class="dashboard-subtitle">{{ $t('dashboard_subtitle') }}</p>
      </div>
    </div>

    <!-- Quick Navigation -->
    <div class="dashboard-section">
      <h2 class="dashboard-section-title">
        <v-icon size="22" class="mr-2" color="#42A5F5">mdi-compass-outline</v-icon>
        {{ $t('quick_navigation') }}
      </h2>

      <div class="dashboard-grid">
        <!-- General -->
        <div class="dashboard-card" @click="$router.push({ name: 'main_dashboard' })" style="--card-color: #42A5F5; --card-gradient: linear-gradient(135deg, #0D47A1, #42A5F5);">
          <div class="dashboard-card-icon-wrap">
            <v-icon size="28" color="white">mdi-human-male-board-poll</v-icon>
          </div>
          <div class="dashboard-card-info">
            <span class="dashboard-card-title">{{ $t('general') }}</span>
            <span class="dashboard-card-desc">{{ $t('dashboard') }}</span>
          </div>
          <v-icon size="18" class="dashboard-card-arrow">mdi-chevron-right</v-icon>
        </div>

        <!-- Add Charge -->
        <div class="dashboard-card" @click="$router.push({ name: 'add_charge' })" style="--card-color: #42A5F5; --card-gradient: linear-gradient(135deg, #0D47A1, #42A5F5);">
          <div class="dashboard-card-icon-wrap">
            <v-icon size="28" color="white">mdi-truck-cargo-container</v-icon>
          </div>
          <div class="dashboard-card-info">
            <span class="dashboard-card-title">{{ $t('add_charge') }}</span>
            <span class="dashboard-card-desc">{{ $t('charges') }}</span>
          </div>
          <v-icon size="18" class="dashboard-card-arrow">mdi-chevron-right</v-icon>
        </div>

        <!-- Add Sell -->
        <div class="dashboard-card" @click="$router.push({ name: 'add_shipping' })" style="--card-color: #4643a0; --card-gradient: linear-gradient(135deg, #372e7d, #6c66bb);">
          <div class="dashboard-card-icon-wrap">
            <v-icon size="28" color="white">mdi-cash-register</v-icon>
          </div>
          <div class="dashboard-card-info">
            <span class="dashboard-card-title">{{ $t('add_shipping') }}</span>
            <span class="dashboard-card-desc">{{ $t('shippings') }}</span>
          </div>
          <v-icon size="18" class="dashboard-card-arrow">mdi-chevron-right</v-icon>
        </div>

        <!-- Add Production -->
        <div class="dashboard-card" @click="$router.push({ name: 'add_production' })" style="--card-color: #00897B; --card-gradient: linear-gradient(135deg, #00695C, #26A69A);">
          <div class="dashboard-card-icon-wrap">
            <v-icon size="28" color="white">mdi-factory</v-icon>
          </div>
          <div class="dashboard-card-info">
            <span class="dashboard-card-title">{{ $t('add_production') }}</span>
            <span class="dashboard-card-desc">{{ $t('production') }}</span>
          </div>
          <v-icon size="18" class="dashboard-card-arrow">mdi-chevron-right</v-icon>
        </div>

        <!-- Fortnight Activities -->
        <div class="dashboard-card" @click="$router.push({ name: 'fortnight_management' })" style="--card-color: #1E88E5; --card-gradient: linear-gradient(135deg, #1565C0, #42A5F5);">
          <div class="dashboard-card-icon-wrap">
            <v-icon size="28" color="white">mdi-calendar-month</v-icon>
          </div>
          <div class="dashboard-card-info">
            <span class="dashboard-card-title">{{ $t('fortnight_activities') }}</span>
            <span class="dashboard-card-desc">{{ $t('workforce') }}</span>
          </div>
          <v-icon size="18" class="dashboard-card-arrow">mdi-chevron-right</v-icon>
        </div>

        <!-- User Statistics (admin only) -->
        <div v-if="auth.hasPermission('user_statistics.view')" class="dashboard-card" @click="$router.push({ name: 'user_statistics' })" style="--card-color: #7C4DFF; --card-gradient: linear-gradient(135deg, #4527A0, #B388FF);">
          <div class="dashboard-card-icon-wrap">
            <v-icon size="28" color="white">mdi-chart-timeline-variant-shimmer</v-icon>
          </div>
          <div class="dashboard-card-info">
            <span class="dashboard-card-title">{{ $t('user_statistics') }}</span>
            <span class="dashboard-card-desc">{{ $t('activity_tracking') }}</span>
          </div>
          <v-icon size="18" class="dashboard-card-arrow">mdi-chevron-right</v-icon>
        </div>
      </div>
    </div>

    <!-- AI Prompt Button -->
    <div class="dashboard-section">
      <div class="dashboard-ai-card" @click="$router.push({ name: 'ai_prompt' })">
        <div class="dashboard-ai-glow"></div>
        <div class="dashboard-ai-content">
          <div class="dashboard-ai-icon">
            <v-icon size="32" color="white">mdi-robot-excited-outline</v-icon>
          </div>
          <div class="dashboard-ai-text">
            <span class="dashboard-ai-title">{{ $t('ask_ai') }}</span>
            <span class="dashboard-ai-desc">{{ $t('ai_prompt') }}</span>
          </div>
          <v-icon size="22" color="white" class="dashboard-ai-arrow">mdi-arrow-right</v-icon>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { useMain } from "@/stores/Main";
import { useAuth } from "@/stores/Auth";

export default {
  computed: {
    main() { return useMain(); },
    auth() { return useAuth(); },
    logoUrl() { return new URL('/images/jupiter-logo.png', window.location.origin).href; },
  },
};
</script>

<style scoped>
.dashboard {
  font-family: 'Inter', 'Segoe UI', sans-serif;
}
.dashboard-light { background: #f5f7fa; }
.dashboard-dark  { background: #121212; }

/* ===== Hero ===== */
.dashboard-hero {
  display: flex;
  align-items: center;
  justify-content: center;
}
.dashboard-hero-content {
  text-align: center;
  max-width: 480px;
}
.dashboard-logo {
  width: 140px;
  height: 140px;
  object-fit: contain;
  margin-bottom: 18px;
  filter: drop-shadow(0 4px 16px rgba(66, 165, 245, 0.18));
  animation: logoFloat 4s ease-in-out infinite;
}
@keyframes logoFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
.dashboard-title {
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: -0.3px;
  margin-bottom: 6px;
}
.dashboard-light .dashboard-title { color: #1e293b; }
.dashboard-dark  .dashboard-title { color: #e2e8f0; }

.dashboard-user-name {
  background: linear-gradient(135deg, #42A5F5, #1565C0);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
.dashboard-subtitle {
  font-size: 0.95rem;
  font-weight: 400;
  opacity: 0.6;
}
.dashboard-light .dashboard-subtitle { color: #475569; }
.dashboard-dark  .dashboard-subtitle { color: #94a3b8; }

/* ===== Sections ===== */
.dashboard-section {
  max-width: 720px;
  margin: 0 auto 32px;
}
.dashboard-section-title {
  display: flex;
  align-items: center;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 16px;
  letter-spacing: 0.01em;
}
.dashboard-light .dashboard-section-title { color: #334155; }
.dashboard-dark  .dashboard-section-title { color: #cbd5e1; }

/* ===== Quick Nav Grid ===== */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 14px;
}
.dashboard-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}
.dashboard-light .dashboard-card {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.06);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.dashboard-dark .dashboard-card {
  background: #1e1e2d;
  border: 1px solid rgba(255,255,255,0.06);
}
.dashboard-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  border-color: var(--card-color);
}
.dashboard-dark .dashboard-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.dashboard-card-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: var(--card-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 8px color-mix(in srgb, var(--card-color) 30%, transparent);
}
.dashboard-card-info {
  flex: 1;
  min-width: 0;
}
.dashboard-card-title {
  display: block;
  font-size: 0.88rem;
  font-weight: 600;
  line-height: 1.3;
}
.dashboard-light .dashboard-card-title { color: #1e293b; }
.dashboard-dark  .dashboard-card-title { color: #e2e8f0; }

.dashboard-card-desc {
  display: block;
  font-size: 0.72rem;
  font-weight: 400;
  opacity: 0.5;
  margin-top: 2px;
}
.dashboard-light .dashboard-card-desc { color: #64748b; }
.dashboard-dark  .dashboard-card-desc { color: #94a3b8; }

.dashboard-card-arrow {
  opacity: 0;
  transform: translateX(-4px);
  transition: all 0.2s ease;
  color: var(--card-color);
}
.dashboard-card:hover .dashboard-card-arrow {
  opacity: 0.7;
  transform: translateX(0);
}

/* ===== AI Button ===== */
.dashboard-ai-card {
  position: relative;
  padding: 22px 28px;
  border-radius: 18px;
  cursor: pointer;
  overflow: hidden;
  background: linear-gradient(135deg, #1565C0 0%, #1E88E5 40%, #42A5F5 100%);
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(21, 101, 192, 0.25);
}
.dashboard-ai-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(21, 101, 192, 0.35);
}
.dashboard-ai-glow {
  position: absolute;
  top: -50%;
  right: -30%;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
  pointer-events: none;
}
.dashboard-ai-content {
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
  z-index: 1;
}
.dashboard-ai-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.dashboard-ai-text {
  flex: 1;
}
.dashboard-ai-title {
  display: block;
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.01em;
}
.dashboard-ai-desc {
  display: block;
  font-size: 0.78rem;
  color: rgba(255,255,255,0.7);
  margin-top: 2px;
}
.dashboard-ai-arrow {
  flex-shrink: 0;
  opacity: 0.7;
  transition: transform 0.2s ease;
}
.dashboard-ai-card:hover .dashboard-ai-arrow {
  transform: translateX(4px);
  opacity: 1;
}

/* ===== Responsive ===== */
@media (max-width: 600px) {
  .dashboard-grid { grid-template-columns: 1fr; }
  .dashboard-logo { width: 100px; height: 100px; }
  .dashboard-title { font-size: 1.3rem; }
  .dashboard-hero { padding: 32px 16px 24px; }
}
</style>

