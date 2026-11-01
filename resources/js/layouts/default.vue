<template>
  <v-app :theme="main.is_dark ? 'dark' : 'light'">
    <!-- snack-bar start -->
    <SnackBar/>
    <!-- snack-bar end -->
    <!-- notification toast -->
    <NotificationToast/>
    <!-- navigation drawer start -->
    <NavigationDrawer v-if="auth.is_auth"/>
    <!-- navigation drawer end -->
    <!-- system bar start -->
    <SystemBar/>
    <!-- system bar end -->

    <!-- main start -->
    <v-main @click="collapseSidebar">
      <v-container fluid class="mt-15" style="position: relative; max-width: 100%;">
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
    <AiAssistant v-if="auth.is_auth"/>
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

