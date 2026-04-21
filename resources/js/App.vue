<template>
  <v-locale-provider>
      <router-view />
      <!-- Global WebSocket Snackbar -->
      <v-snackbar
        v-model="_online.snackbar.show"
        :color="_online.snackbar.color"
        :timeout="3000"
        location="bottom right"
        rounded="lg"
        elevation="8"
        class="ws-snackbar"
      >
        <div class="d-flex align-center ga-2">
          <v-icon size="18">
            {{ _online.snackbar.color === 'success' ? 'mdi-account-check' : _online.snackbar.color === 'warning' ? 'mdi-account-off' : 'mdi-database-sync' }}
          </v-icon>
          <span>{{ _online.snackbar.text }}</span>
        </div>
        <template v-slot:actions>
          <v-btn variant="text" size="small" @click="_online.dismissSnackbar()">
            <v-icon size="16">mdi-close</v-icon>
          </v-btn>
        </template>
      </v-snackbar>
  </v-locale-provider>
</template>

<script >
import { useMain } from './stores/Main.js';
import { useOnlineUsers } from './stores/OnlineUsers.js';

export default {
    computed: {
      main : function(){
        return useMain();
      },
      _online: function () {
        return useOnlineUsers();
      },
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

<style scoped>
.ws-snackbar {
    font-weight: 500;
    letter-spacing: 0.3px;
}
</style>