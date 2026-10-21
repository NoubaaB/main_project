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
      "main.is_dark": {
        immediate: true,
        handler(value) {
          const theme = value ? "dark" : "light";
          if (this.$vuetify?.theme?.change) {
            this.$vuetify.theme.change(theme);
          } else if (this.$vuetify?.theme?.global) {
            this.$vuetify.theme.global.name = theme;
          }
          localStorage.setItem("theme", theme);

          // Synchronize PWA Theme Color & Mobile Status Bar
          const themeColor = value ? "#121212" : "#FFFFFF";
          const metaTheme = document.getElementById("meta-theme-color") || document.querySelector('meta[name="theme-color"]');
          if (metaTheme) {
            metaTheme.setAttribute("content", themeColor);
          } else {
            const m = document.createElement("meta");
            m.id = "meta-theme-color";
            m.name = "theme-color";
            m.content = themeColor;
            document.head.appendChild(m);
          }

          const metaBg = document.getElementById("meta-background-color") || document.querySelector('meta[name="background_color"]');
          if (metaBg) {
            metaBg.setAttribute("content", themeColor);
          }

          const metaStatus = document.getElementById("meta-status-bar-style") || document.querySelector('meta[name="apple-mobile-web-app-status-bar-style"]');
          if (metaStatus) {
            metaStatus.setAttribute("content", value ? "black-translucent" : "default");
          }

          const manifestLink = document.getElementById("pwa-manifest-link") || document.querySelector('link[rel="manifest"]');
          if (manifestLink) {
            manifestLink.setAttribute("href", `/manifest.json?theme=${theme}`);
          }

          // Sync root document theme attributes & body background
          document.documentElement.classList.remove("theme-dark", "theme-light");
          document.documentElement.classList.add(`theme-${theme}`);
          document.documentElement.setAttribute("data-theme", theme);
          if (document.body) {
            document.body.style.backgroundColor = themeColor;
          }
        },
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