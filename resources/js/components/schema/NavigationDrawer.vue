<template>
  <v-navigation-drawer
    v-model="main.menu_drawer"
    :rail="main.sidebar_mini"
    :rail-width="68"
    :width="270"
    :class="['nav-drawer-premium', main.is_dark ? 'nav-dark' : 'nav-light']"
    @click="expandSidebar"
  >
    <!-- Top branding / user section -->
    <div class="nav-brand-section">
      <v-avatar
        :size="main.sidebar_mini ? 36 : 48"
        class="nav-avatar"
      >
        <v-icon :size="main.sidebar_mini ? 20 : 28" color="white">
          mdi-account
        </v-icon>
      </v-avatar>
      <transition name="nav-fade">
        <div v-if="!main.sidebar_mini" class="nav-user-info">
          <div class="nav-user-name">{{ auth.user.name }}</div>
          <div class="nav-user-role">{{ auth.user.role.name }}</div>
        </div>
      </transition>
    </div>

    <v-divider class="mx-3 mb-1"></v-divider>

    <!-- ======== RAIL MODE: flat icons only ======== -->
    <v-list v-if="main.sidebar_mini" nav density="compact" class="nav-list">
      <template v-for="([icon, text, childrens, url], i) in flatLinks" :key="'rail-'+i">
        <v-list-item
          class="nav-list-item"
          :value="text"
          rounded="lg"
          color="info"
          :exact="true"
          :to="url ? { name: url } : undefined"
        >
          <template v-slot:prepend>
            <v-icon :size="22">{{ icon }}</v-icon>
          </template>
          <v-tooltip activator="parent" location="end">{{ text }}</v-tooltip>
        </v-list-item>
      </template>
    </v-list>

    <!-- ======== EXPANDED MODE: groups ======== -->
    <v-list v-else v-model:opened="open" nav density="compact" class="nav-list">
      <template v-for="([icon, text, childrens, url], i) in links" :key="'exp-'+i">
        <v-list-item
          v-if="childrens == null"
          class="nav-list-item"
          :prepend-icon="icon"
          :title="text"
          :value="text"
          rounded="lg"
          color="info"
          :exact="true"
          :to="{ name: url }"
        ></v-list-item>

        <v-list-group v-else :value="text">
          <template v-slot:activator="{ isOpen, props }">
            <v-list-item
              class="nav-list-item"
              v-bind="props"
              :prepend-icon="icon"
              :title="text"
              rounded="lg"
              color="info"
              :active="isOpen"
              :exact="true"
            ></v-list-item>
          </template>
          <v-list-item
            class="nav-list-item"
            v-for="([_icon, _text, _url], j) in childrens"
            :key="j"
            :title="_text"
            :prepend-icon="_icon"
            :value="_text"
            rounded="lg"
            color="info"
            :to="{ name: _url }"
            :exact="true"
          ></v-list-item>
        </v-list-group>
      </template>
    </v-list>

    <!-- Bottom footer section -->
    <template v-slot:append>
      <v-divider class="mx-3"></v-divider>
      <div class="nav-footer">
        <v-avatar
          :size="main.sidebar_mini ? 30 : 34"
          class="nav-footer-avatar"
        >
          <v-icon :size="main.sidebar_mini ? 16 : 20" color="white">
            mdi-account
          </v-icon>
        </v-avatar>
        <transition name="nav-fade">
          <div v-if="!main.sidebar_mini" class="nav-footer-info">
            <div class="nav-footer-name">{{ auth.user.name }}</div>
            <div class="nav-footer-role">{{ auth.user.role.name }}</div>
          </div>
        </transition>
        <v-btn
          v-if="!main.sidebar_mini"
          variant="text"
          icon
          size="small"
          class="nav-logout-btn"
          @click.stop="logout()"
        >
          <v-icon size="20">mdi-power</v-icon>
          <v-tooltip activator="parent" location="top">{{ $t('logout') || 'Logout' }}</v-tooltip>
        </v-btn>
      </div>
    </template>
  </v-navigation-drawer>

  <!-- Click-away overlay to minimize sidebar -->
  <div
    v-if="main.menu_drawer && !main.sidebar_mini"
    class="nav-click-away-overlay"
    @click="minimizeSidebar"
  ></div>
</template>

<script>
import { useDashboardWaiter } from "@/stores/DashboardWaiter";
import { useMain } from "@/stores/Main";
import { useAuth } from "@/stores/Auth";

export default {
  data() {
    return {
      open: null,
    };
  },
  computed: {
    dashboard_waiter: function () {
      return useDashboardWaiter();
    },
    main: function () {
      return useMain();
    },
    auth: function () {
      return useAuth();
    },
    links: function () {
      return [
        ['mdi-human-male-board-poll', this.$t("general"), null, "view_supplier_invoice"],
        ['mdi-robot', this.$t("ai_prompt"), null, "ai_prompt"],
        ['mdi-warehouse', this.$t('warehouses'), [
          ['mdi-view-dashboard-outline', this.$t('dashboard'), "warehouses"],
          ['mdi-clipboard-check-outline', this.$t('inventory'), "inventory"],
          ['mdi-history', this.$t('adjustment_history'), "inventory_history"],
        ]],
        ['mdi-truck-cargo-container', this.$t('charges'), [
          ['mdi-sticker-plus-outline', this.$t("add_charge"), "add_charge"],
          ['mdi-view-list-outline', this.$t("list_charge"), "list_charge"],
        ]],
        ['mdi-factory', this.$t('production'), [
          ['mdi-view-column-outline', this.$t("production_kanban"), "production_kanban"],
          ['mdi-format-list-bulleted', this.$t("production_list"), "production_list"],
          ['mdi-chart-waterfall', this.$t("production_yield"), "production_yield"],
        ]],
        ['mdi-cash-register', this.$t('sells'), [
          ['mdi-sticker-plus-outline', this.$t("add_sell"), "add_sell"],
          ['mdi-view-list-outline', this.$t("list_sell"), "list_sell"],
        ]],
        ['mdi-account-hard-hat', this.$t('workforce'), [
          ['mdi-account-multiple', this.$t("workforce_management"), "workforce_management"],
          ['mdi-account-tie', this.$t("corporal_management"), "corporal_management"],
          ['mdi-sitemap-outline', this.$t("section_management"), "section_management"],
          ['mdi-calendar-month', this.$t("fortnight_management"), "fortnight_management"],
          ['mdi-chart-bar', this.$t("workforce_statistics"), "workforce_statistics"],
        ]],
        ['mdi-file-document-check', this.$t('invoics'), [
          ['mdi-file-document-multiple', this.$t("list_supplier_invoice"), "list_supplier_invoice"],
        ]],
        ['mdi-cog-outline', this.$t('parameters'), [
          ['mdi-warehouse', this.$t("warehouse_management"), "warehouse_management"],
          ['mdi-fish', this.$t("fish_management"), "fish_management"],
          ['mdi-shape-outline', this.$t("charge_category_management"), "charge_category_management"],
          ['mdi-cog-transfer-outline', this.$t("wco_management"), "wco_management"],
          ['mdi-tag-multiple-outline', this.$t("sell_category_management"), "sell_category_management"],
          ['mdi-cog-outline', this.$t("production_category_management"), "production_category_management"],
        ]],
      ];
    },
    // Flatten all links for rail mode — parent groups show as single icon items
    flatLinks: function () {
      const flat = [];
      for (const [icon, text, childrens, url] of this.links) {
        if (childrens == null) {
          flat.push([icon, text, null, url]);
        } else {
          // Show parent icon in rail
          flat.push([icon, text, null, null]);
        }
      }
      return flat;
    }
  },
  methods: {
    logout: function () {
      this.auth.logout().then(res => {
        this.$router.push({ "name": "login" });
      });
    },
    expandSidebar() {
      if (this.main.sidebar_mini) {
        this.main.sidebar_mini = false;
      }
    },
    minimizeSidebar() {
      this.main.sidebar_mini = true;
    },
  },
  mounted() {
    this.main.sidebar_mini = false;
  }
};
</script>

<style scoped>
/* ============================================================
   Navigation Drawer — Clean Sidebar with Info Color
   ============================================================ */

.nav-drawer-premium {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

/* ---------- Brand Section ---------- */
.nav-brand-section {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 16px 14px;
  transition: all 0.3s ease;
}

.nav-avatar {
  background: linear-gradient(135deg, #2196F3, #1976D2) !important;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.nav-user-info {
  min-width: 0;
  overflow: hidden;
}

.nav-user-name {
  font-weight: 700;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-user-role {
  font-size: 11.5px;
  opacity: 0.55;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ---------- Nav List ---------- */
.nav-list {
  padding: 6px 8px !important;
}

.nav-list-item {
  margin-bottom: 2px !important;
  font-size: 13.5px !important;
  transition: all 0.2s ease !important;
}

/* List group children indent */
:deep(.v-list-group__items .v-list-item) {
  padding-inline-start: calc(-16px + var(--indent-padding)) !important;
}

/* ---------- Footer ---------- */
.nav-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px 14px;
}

.nav-footer-avatar {
  background: linear-gradient(135deg, #2196F3, #1976D2) !important;
  flex-shrink: 0;
}

.nav-footer-info {
  flex: 1;
  min-width: 0;
  overflow: hidden;
}

.nav-footer-name {
  font-weight: 600;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-footer-role {
  font-size: 10.5px;
  opacity: 0.5;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-logout-btn {
  flex-shrink: 0;
  opacity: 0.5;
  transition: all 0.2s ease !important;
}

.nav-logout-btn:hover {
  opacity: 1;
  color: #f44336 !important;
}

/* ---------- Transitions ---------- */
.nav-fade-enter-active {
  transition: all 0.25s ease 0.08s;
}
.nav-fade-leave-active {
  transition: all 0.15s ease;
}
.nav-fade-enter-from,
.nav-fade-leave-to {
  opacity: 0;
  transform: translateX(-6px);
}

/* ---------- Click-away overlay ---------- */
.nav-click-away-overlay {
  position: fixed;
  inset: 0;
  z-index: 1004;
  cursor: default;
}
</style>