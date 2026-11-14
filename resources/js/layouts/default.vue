<template>
  <v-app :theme="main.is_dark ? 'dark' : 'light'">
    <!-- snack-bar start -->
    <SnackBar/>
    <!-- snack-bar end -->
    <!-- notification toast -->
    <NotificationToast/>
    <!-- unresolved issues snackbars & dialog -->
    <UnresolvedIssuesSnackbar v-if="auth.is_auth"/>
    <UnresolvedIssuesDialog v-if="auth.is_auth"/>
    <!-- navigation drawer start -->
    <NavigationDrawer v-if="auth.is_auth && !main.is_aiq_fullscreen"/>
    <!-- navigation drawer end -->
    <!-- system bar start -->
    <SystemBar v-if="!main.is_aiq_fullscreen"/>
    <!-- system bar end -->

    <!-- main start -->
    <v-main
      @click="collapseSidebar"
      :class="{ 'v-main--aiq': $route.name === 'ai_prompt' }"
      :style="main.is_aiq_fullscreen ? 'padding: 0 !important;' : ''"
    >
      <v-container
        fluid
        :class="[
          !auth.is_auth
            ? 'pa-0 fill-height auth-page-container'
            : (main.is_aiq_fullscreen ? 'pa-0 ma-0' : ($route.name === 'ai_prompt' ? 'aiq-root-container pa-0 ma-0' : 'mt-15'))
        ]"
        :style="main.is_aiq_fullscreen ? 'padding: 0 !important; margin: 0 !important; max-width: 100vw !important;' : ''"
      >
        <router-view v-slot="{ Component, route }">
          <keep-alive :include="['ListCharge', 'ListShipping', 'ListProduction', 'WarehouseDashboard', 'ListSupplierInvoice', 'ListClientInvoice', 'SupplierSettlement', 'ClientSettlement']">
            <component :is="Component" :key="keepAliveNames.includes(Component?.type?.name) ? Component?.type?.name : route.fullPath"/>
          </keep-alive>
        </router-view>
      </v-container>
    </v-main>
    <v-snackbar
      v-model="main.snackbar.show"
    >
      {{ main.snackbar.message }}

      <template v-slot:actions>
        <v-btn
          :color="main.snackbar.color"
          variant="text"
          @click="main.snackbar.show = false"
        >
          {{ $t("close") }}
        </v-btn>
      </template>
    </v-snackbar>
    <!-- main end -->
    <AiAssistant v-if="auth.is_auth && !main.is_aiq_fullscreen && $route.name !== 'ai_prompt'"/>
  </v-app>
</template>

<script>
  import SnackBar from "@/components/schema/SnackBar.vue" ;
  import NotificationToast from "@/components/schema/NotificationToast.vue" ;
  import AiAssistant from "@/components/schema/AiAssistant.vue" ;
  import NavigationDrawer from "@/components/schema/NavigationDrawer.vue" ;
  import SystemBar from "@/components/schema/SystemBar.vue" ;
  import UnresolvedIssuesSnackbar from "@/components/shared/UnresolvedIssuesSnackbar.vue";
  import UnresolvedIssuesDialog from "@/components/shared/UnresolvedIssuesDialog.vue";
  import  { useAuth } from "@/stores/Auth";
  import {useMain} from "@/stores/Main";
  import pinia from "@/stores";

  export default {
      components: {
        SnackBar,
        NotificationToast,
        NavigationDrawer,
        SystemBar,
        AiAssistant,
        UnresolvedIssuesSnackbar,
        UnresolvedIssuesDialog
      },
      data() {
        return {
          keepAliveNames: ['ListCharge', 'ListShipping', 'ListProduction', 'WarehouseDashboard', 'ListSupplierInvoice', 'ListClientInvoice', 'SupplierSettlement', 'ClientSettlement', 'OrderTransferList'],
        };
      },
      computed:{
        auth() { return useAuth(pinia) },
        main() { return useMain(pinia) }
      },
      methods: {
        collapseSidebar() {
          if (!this.main.sidebar_mini) {
            this.main.sidebar_mini = true;
          }
        }
      }
  }
</script>

<style>
/* Global Layout Locking for Analytics & AI */
body.aiq-active-route,
html.aiq-active-route {
  overflow: hidden !important;
  height: 100% !important;
  max-height: 100vh !important;
  margin: 0 !important;
  padding: 0 !important;
}

body.aiq-active-route .v-application {
  height: 100vh !important;
  max-height: 100vh !important;
  overflow: hidden !important;
}

body.aiq-active-route .v-main,
.v-main--aiq {
  height: 100vh !important;
  max-height: 100vh !important;
  overflow: hidden !important;
  display: flex !important;
  flex-direction: column !important;
  box-sizing: border-box !important;
}

body.aiq-active-route .v-main > .v-container,
.v-container.aiq-root-container {
  flex: 1 1 0% !important;
  height: 100% !important;
  max-height: 100% !important;
  min-height: 0 !important;
  overflow: hidden !important;
  display: flex !important;
  flex-direction: column !important;
  margin: 0 !important;
  padding: 8px 14px 10px 14px !important;
  box-sizing: border-box !important;
}

/* Global Fullscreen Overrides for Analytics & AI */
body.aiq-body-fullscreen html,
body.aiq-body-fullscreen {
  overflow: hidden !important;
}

body.aiq-body-fullscreen .pro-nav-drawer,
body.aiq-body-fullscreen .v-navigation-drawer,
body.aiq-body-fullscreen .pro-footer-area,
body.aiq-body-fullscreen .systembar-premium,
body.aiq-body-fullscreen .v-app-bar,
body.aiq-body-fullscreen .v-system-bar,
body.aiq-body-fullscreen .ai-assistant-fab,
body.aiq-body-fullscreen .ai-chat-panel,
body.aiq-body-fullscreen .pro-flyout-menu,
body.aiq-body-fullscreen .pro-nav-tooltip {
  display: none !important;
  visibility: hidden !important;
  pointer-events: none !important;
  opacity: 0 !important;
  z-index: -9999 !important;
}

body.aiq-body-fullscreen .v-main {
  padding: 0 !important;
  margin: 0 !important;
}

body.aiq-body-fullscreen .v-main > .v-container {
  padding: 0 !important;
  margin: 0 !important;
  max-width: 100vw !important;
}

/* Table Expanded Workspace Mode Rules */
html.table-expanded-active,
body.table-expanded-active {
  overflow: hidden !important;
}

/* Ensure AI Assistant & Chat Box are explicitly displayed on top of expanded data tables and all other elements */
.ai-assistant-root,
.ai-fab-container,
.ai-chat-panel,
body.table-expanded-active .ai-fab-container,
body.table-expanded-active .ai-chat-panel {
  z-index: 100000 !important;
  pointer-events: auto !important;
}
</style>

