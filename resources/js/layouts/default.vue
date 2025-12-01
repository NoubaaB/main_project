<template>
  <v-app>
    <!-- snack-bar start -->
    <SnackBar/> 
    <!-- snack-bar end -->
    <!-- navigation drawer start -->
    <NavigationDrawer v-if="auth.is_auth"/>
    <!-- navigation drawer end -->
    <!-- system bar start -->
    <SystemBar/>
    <!-- system bar end -->
    
    <!-- main start -->
    <v-container max-width="1400" class="mt-10 ma-auto" :class="{'py-15':auth.is_auth,'py-5':!auth.is_auth}" style="position: relative">
      <router-view />
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
    </v-container>
    <!-- main end -->

  </v-app>
</template>

<script>
  import SnackBar from "@/components/schema/SnackBar.vue" ;
  import NavigationDrawer from "@/components/schema/NavigationDrawer.vue" ;
  import SystemBar from "@/components/schema/SystemBar.vue" ;
  import  { useAuth } from "@/stores/Auth";
  import {useMain} from "@/stores/Main";
  export default {
      components: {
        SnackBar,
        NavigationDrawer,
        SystemBar
      },
      computed:{
        auth: function () {
          return useAuth();
        },
        main: function () {
          return useMain();
        }
      }
  }
</script>
