<template>
  <v-locale-provider>
      <router-view />
  </v-locale-provider>
</template>

<script >
import { useMain } from './stores/Main.js';

export default {
    computed: {
      main : function(){
        return useMain();
      }
    },  
    watch: {
      "main.is_dark": function (value) {
          const theme = value ? "dark" : "light";
          // this.$vuetify.theme.global.name = theme;
          this.$vuetify.theme.change(theme)
          localStorage.setItem("theme", theme);
          // document.documentElement.style.setProperty(
          //     "--theme",
          //     // this.configuration[theme]
          // );
          // document.documentElement.style.setProperty(
          //     "--inverse_theme",
          //     value ? "black" : "white"
          // );
      },
  },
}
</script>