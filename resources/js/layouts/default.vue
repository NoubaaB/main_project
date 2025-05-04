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
    <v-container class="mt-10 ma-auto" :class="{'py-15':auth.is_auth,'py-5':!auth.is_auth}" style="position: relative">
      <router-view />
    </v-container>
    <!-- main end -->

    <!-- bnotom navigation start -->
    <BottomNavigation v-if="auth.is_auth"/>
    <!-- bnotom navigation end -->

    <!-- command navigation start -->
    <NavigationDrawerCommand v-if="auth.is_auth"/>
    <!-- command navigation end -->
  </v-app>
</template>

<script>
  import SnackBar from "@/components/schema/SnackBar.vue" ;
  import NavigationDrawer from "@/components/schema/NavigationDrawer.vue" ;
  import SystemBar from "@/components/schema/SystemBar.vue" ;
  import BottomNavigation from "@/components/schema/BottomNavigation.vue" ;
  import NavigationDrawerCommand from "@/components/schema/NavigationDrawerCommand.vue" ;
  import  { useAuth } from "@/stores/Auth";
export default {
      components: {
        SnackBar,
        NavigationDrawer,
        SystemBar,
        BottomNavigation,
        NavigationDrawerCommand
      },
      computed:{
        auth: function () {
          return useAuth();
        }
      }
  }
</script>
