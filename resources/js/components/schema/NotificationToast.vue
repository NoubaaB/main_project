<template>
  <teleport to="body">
    <div class="notif-toast-stack">
      <transition-group name="notif-slide" tag="div">
        <div
          v-for="n in notifications"
          :key="n.id"
          :class="['notif-toast', `notif-toast--${n.color}`, { 'notif-toast--exit': !n.visible }]"
        >
          <!-- Color accent bar -->
          <div :class="['notif-accent', `bg-${n.color}`]"></div>

          <!-- Content -->
          <div class="notif-body">
            <!-- Header row -->
            <div class="notif-header">
              <div class="notif-header-left">
                <v-icon :color="n.color" size="22" class="notif-icon">{{ n.icon }}</v-icon>
                <span class="notif-title">{{ n.title }}</span>
              </div>
              <v-btn
                icon
                size="x-small"
                variant="text"
                class="notif-close"
                @click="dismiss(n.id)"
              >
                <v-icon size="16">mdi-close</v-icon>
              </v-btn>
            </div>

            <!-- Message -->
            <div v-if="n.message" class="notif-message">{{ n.message }}</div>

            <!-- Detail fields -->
            <div v-if="n.fields.length" class="notif-fields">
              <div v-for="(f, fi) in n.fields" :key="fi" class="notif-field">
                <v-icon v-if="f.icon" :color="n.color" size="14" class="notif-field-icon">{{ f.icon }}</v-icon>
                <span class="notif-field-label">{{ f.label }}:</span>
                <span class="notif-field-value">{{ f.value }}</span>
              </div>
            </div>

            <!-- Progress bar (auto-dismiss countdown) -->
            <div v-if="n.duration > 0" class="notif-progress-track">
              <div
                :class="['notif-progress-bar', `bg-${n.color}`]"
                :style="{ animationDuration: n.duration + 'ms' }"
              ></div>
            </div>
          </div>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script>
import { useNotification } from '@/stores/Notification';

export default {
  computed: {
    store() {
      return useNotification();
    },
    notifications() {
      return this.store.notifications;
    },
  },
  methods: {
    dismiss(id) {
      this.store.dismiss(id);
    },
  },
};
</script>

<style scoped>
/* ============================================================
   NotificationToast — Top-Right Stacked Toast Notifications
   Premium design with accent bar, detail fields, progress bar
   ============================================================ */

.notif-toast-stack {
  position: fixed;
  top: 72px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 400px;
  min-width: 320px;
  pointer-events: none;
}

.notif-toast {
  display: flex;
  border-radius: 14px;
  overflow: hidden;
  box-shadow:
    0 8px 30px rgba(0, 0, 0, 0.12),
    0 2px 8px rgba(0, 0, 0, 0.06);
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), 0.08);
  pointer-events: all;
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Accent bar on left side */
.notif-accent {
  width: 5px;
  flex-shrink: 0;
  border-radius: 14px 0 0 14px;
}

/* Body */
.notif-body {
  flex: 1;
  padding: 13px 14px 10px;
  min-width: 0;
}

/* Header */
.notif-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 4px;
}

.notif-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.notif-icon {
  flex-shrink: 0;
}

.notif-title {
  font-weight: 700;
  font-size: 13.5px;
  letter-spacing: 0.1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notif-close {
  flex-shrink: 0;
  opacity: 0.4;
  transition: opacity 0.2s ease;
}

.notif-close:hover {
  opacity: 1;
}

/* Message */
.notif-message {
  font-size: 12px;
  opacity: 0.6;
  margin-bottom: 6px;
  line-height: 1.4;
}

/* Detail fields */
.notif-fields {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-top: 6px;
  padding-top: 6px;
  border-top: 1px solid rgba(var(--v-border-color), 0.06);
}

.notif-field {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  line-height: 1.5;
}

.notif-field-icon {
  flex-shrink: 0;
}

.notif-field-label {
  font-weight: 600;
  opacity: 0.55;
  white-space: nowrap;
}

.notif-field-value {
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Progress bar */
.notif-progress-track {
  height: 2px;
  margin-top: 8px;
  border-radius: 2px;
  overflow: hidden;
  background: rgba(var(--v-border-color), 0.06);
}

.notif-progress-bar {
  height: 100%;
  width: 100%;
  border-radius: 2px;
  animation: notif-shrink linear forwards;
  transform-origin: left;
}

@keyframes notif-shrink {
  from { transform: scaleX(1); }
  to   { transform: scaleX(0); }
}

/* ─── Slide Transition ─── */
.notif-slide-enter-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.notif-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 1, 1);
}
.notif-slide-enter-from {
  transform: translateX(100%) scale(0.9);
  opacity: 0;
}
.notif-slide-leave-to {
  transform: translateX(100%) scale(0.9);
  opacity: 0;
}

/* Exit state */
.notif-toast--exit {
  transform: translateX(100%);
  opacity: 0;
}

/* ─── Responsive ─── */
@media (max-width: 480px) {
  .notif-toast-stack {
    right: 10px;
    left: 10px;
    max-width: none;
    min-width: 0;
  }
}
</style>
