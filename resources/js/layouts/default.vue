<template>
  <v-app :theme="main.is_dark ? 'dark' : 'light'">
    <!-- snack-bar start -->
    <SnackBar/>
    <!-- snack-bar end -->
    <!-- notification toast -->
    <NotificationToast/>
    <!-- navigation drawer start -->
    <NavigationDrawer v-if="auth.is_auth && !main.is_aiq_fullscreen"/>
    <!-- navigation drawer end -->
    <!-- system bar start -->
    <SystemBar v-if="!main.is_aiq_fullscreen"/>
    <!-- system bar end -->

    <!-- main start -->
    <v-main @click="collapseSidebar" :style="main.is_aiq_fullscreen ? 'padding: 0 !important;' : ''">
      <v-container
        fluid
        :class="[
          !auth.is_auth
            ? 'pa-0 fill-height auth-page-container'
            : (main.is_aiq_fullscreen ? 'pa-0 ma-0' : ($route.name === 'ai_prompt' ? 'pa-2 pa-sm-3 mt-14' : 'mt-15'))
        ]"
        :style="main.is_aiq_fullscreen ? 'padding: 0 !important; margin: 0 !important; max-width: 100vw !important;' : 'position: relative; max-width: 100%;'"
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
  import  { useAuth } from "@/stores/Auth";
  import {useMain} from "@/stores/Main";
  import pinia from "@/stores";

  export default {
      components: {
        SnackBar,
        NotificationToast,
        NavigationDrawer,
        SystemBar,
        AiAssistant
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
</style>

