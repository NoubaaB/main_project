<template>
  <v-app>
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
    <v-container class="pb-0" :max-width="auth.is_auth ? 1400 : 1200" :class="{'py-15':auth.is_auth,'py-5':!auth.is_auth}" style="position: relative">
    <router-view v-slot="{ Component }">
    <!-- keep-alive only when route.meta.keepAlive is true -->
    <keep-alive>
      <component :is="Component"/>
    </keep-alive>

    <!-- render normally when not cached -->
  </router-view>

    </v-container>
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

  export default {
      components: {
        SnackBar,
        NotificationToast,
        NavigationDrawer,
        SystemBar,
        AiAssistant
      },
      computed:{
        auth: () => useAuth(),
        main : () => useMain()
      }
  }
</script>
