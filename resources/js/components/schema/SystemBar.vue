<template>
    <v-app-bar
        style="z-index: 3"
        height="auto"
        width="100vw"
        flat
        :class="['systembar-premium', main.is_dark ? 'systembar-dark' : 'systembar-light']"
        elevation="0"
    >
        <div class="systembar-inner">
            <!-- Left: Nav toggle -->
            <div class="systembar-left">
                <v-btn
                    v-if="auth.is_auth"
                    icon
                    variant="tonal"
                    color="info"
                    size="36"
                    class="systembar-nav-btn"
                    @click.prevent="main.sidebar_mini = !main.sidebar_mini"
                >
                    <v-icon size="20">mdi-menu</v-icon>
                </v-btn>
            </div>

            <!-- Center: Logo / Title -->
            <div class="systembar-center">
                <router-link to="/" class="systembar-logo-link">
                    <img :src="miniLogoUrl" alt="Jupiter" class="systembar-mini-logo" />
                    <span class="systembar-logo-text">
                        {{ $t("title_logo") }} App
                    </span>
                </router-link>
            </div>

            <!-- Right: Actions -->
            <div class="systembar-right">

                <!-- Online Users Button -->
                <v-menu
                    transition="scale-transition"
                    :close-on-content-click="false"
                    v-if="auth.is_auth"
                >
                    <template v-slot:activator="{ props }">
                        <v-btn
                            v-bind="props"
                            icon
                            variant="tonal"
                            color="green"
                            size="36"
                            class="systembar-action-btn"
                            id="online-users-btn"
                        >
                            <v-badge
                                v-if="_online.onlineCount > 0"
                                :content="_online.onlineCount"
                                color="green"
                                text-color="white"
                                location="bottom end"
                                offset-x="2"
                                offset-y="2"
                            >
                                <v-icon size="19" :class="{ 'online-pulse-icon': _online.onlineCount > 0 }">mdi-account-group</v-icon>
                            </v-badge>
                            <v-icon v-else size="19">mdi-account-group-outline</v-icon>
                            <v-tooltip activator="parent" location="bottom">{{ $t('online_users') }}</v-tooltip>
                        </v-btn>
                    </template>
                    <v-card
                        class="online-users-panel mt-2"
                        :class="main.is_dark ? 'archive-dark' : 'archive-light'"
                        min-width="260"
                        max-width="340"
                        rounded="xl"
                        elevation="12"
                    >
                        <v-card-title class="archive-title">
                            <v-icon class="mr-2" size="20" color="green">mdi-circle</v-icon>
                            {{ $t('online_users') }} ({{ _online.onlineCount }})
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-list density="compact" class="pa-2" v-if="_online.otherUsers.length > 0">
                            <v-list-item
                                v-for="user in _online.otherUsers"
                                :key="user.id"
                                class="online-user-item"
                                rounded="lg"
                            >
                                <template v-slot:prepend>
                                    <v-avatar size="30" color="green" class="mr-3">
                                        <v-icon size="16" color="white">mdi-account</v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-body-2 font-weight-medium">
                                    {{ user.name }}
                                </v-list-item-title>
                                <template v-slot:append>
                                    <v-icon size="10" color="green">mdi-circle</v-icon>
                                </template>
                            </v-list-item>
                        </v-list>
                        <div v-else class="text-medium-emphasis d-flex align-center justify-center py-6">
                            <v-icon icon="mdi-account-off-outline" size="20" class="mr-2"></v-icon>
                            <span>{{ $t('no_users_online') || 'No users online' }}</span>
                        </div>
                    </v-card>
                </v-menu>

                <!-- Language Selector -->
                <v-menu
                    transition="scale-transition"
                    :close-on-content-click="true"
                    offset="8"
                >
                    <template v-slot:activator="{ props }">
                        <v-btn
                            v-bind="props"
                            variant="tonal"
                            color="info"
                            size="36"
                            class="systembar-action-btn systembar-lang-btn"
                        >
                            <img :src="currentLangFlag" class="lang-current-flag" :alt="main.lang ? main.lang.code : 'lang'" />
                            <v-tooltip activator="parent" location="bottom">{{ $t('language') || 'Language' }}</v-tooltip>
                        </v-btn>
                    </template>
                    <v-card
                        class="lang-dropdown mt-1"
                        :class="main.is_dark ? 'lang-dropdown-dark' : 'lang-dropdown-light'"
                        rounded="xl"
                        elevation="12"
                        min-width="200"
                    >
                        <div class="lang-dropdown-header">
                            <v-icon size="18" class="mr-2" color="info">mdi-translate</v-icon>
                            <span>{{ $t('language') || 'Language' }}</span>
                        </div>
                        <v-divider></v-divider>
                        <div class="lang-dropdown-list">
                            <button
                                v-for="lang in main.langs"
                                :key="lang.id + lang.code"
                                @click="changeLanguage(lang)"
                                :class="['lang-option', { 'lang-option-active': main.lang && main.lang.code === lang.code }]"
                            >
                                <img :src="getLangFlag(lang.code)" class="lang-option-flag" :alt="lang.code" />
                                <div class="lang-option-info">
                                    <span class="lang-option-name">{{ getLangName(lang.code) }}</span>
                                    <span class="lang-option-code">{{ lang.code.toUpperCase() }}</span>
                                </div>
                                <v-icon
                                    v-if="main.lang && main.lang.code === lang.code"
                                    size="18"
                                    color="info"
                                    class="lang-option-check"
                                >mdi-check-circle</v-icon>
                            </button>
                        </div>
                    </v-card>
                </v-menu>

                <!-- Dark/Light Mode Toggle -->
                <v-btn
                    icon
                    variant="tonal"
                    :color="main.is_dark ? 'amber' : 'indigo'"
                    size="36"
                    class="systembar-action-btn systembar-theme-btn"
                    @click="main.is_dark = !main.is_dark"
                >
                    <v-icon size="19" :class="{ 'theme-icon-rotate': main.is_dark }">
                        {{ main.is_dark ? 'mdi-white-balance-sunny' : 'mdi-weather-night' }}
                    </v-icon>
                    <v-tooltip activator="parent" location="bottom">
                        {{ main.is_dark ? 'Light Mode' : 'Dark Mode' }}
                    </v-tooltip>
                </v-btn>
            </div>
        </div>
    </v-app-bar>
</template>

<script>
    import { useDashboardWaiter } from "@/stores/DashboardWaiter"; 
    import { useMain } from "@/stores/Main"; 
    import { useAuth } from "@/stores/Auth";
    import { useOnlineUsers } from "@/stores/OnlineUsers";
    import annyang from "annyang";

    export default {
        data:function(){
          return {
          }
        },
        computed:{
            dashboard_waiter: function () {
                return useDashboardWaiter();
            },
            main: function () {
                return useMain();
            },
            auth: function () {
                return useAuth();
            },
            _online: function () {
                return useOnlineUsers();
            },
            currentLangFlag: function () {
                if (!this.main.lang) return 'https://flagcdn.com/24x18/gb.png';
                return this.getLangFlag(this.main.lang.code);
            },
            miniLogoUrl: function () {
                return new URL('/images/jupiter-logo.png', window.location.origin).href;
            },

        },
        methods:{
            getLangFlag: function (code) {
                const flags = {
                    'en': 'https://flagcdn.com/24x18/gb.png',
                    'fr': 'https://flagcdn.com/24x18/fr.png',
                    'es': 'https://flagcdn.com/24x18/es.png',
                    'ar': 'https://flagcdn.com/24x18/ma.png',
                };
                return flags[code] || 'https://flagcdn.com/24x18/gb.png';
            },
            getLangName: function (code) {
                const names = {
                    'en': this.$t('english'),
                    'fr': this.$t('french'),
                    'es': this.$t('spanish'),
                };
                return names[code] || code;
            },
            changeLanguage: function (lang) {
                // this.$vuetify.locale.rtl = (lang.code == "ar");
                localStorage.setItem("language", lang.code);
                this.$vuetify.locale.current = lang.code;
                this.$i18n.locale = lang.code;
                console.log(lang)
                annyang.setLanguage(lang.annyang);
                this.main.lang = lang;
            },

        },
        mounted() {
            if (this.auth.is_auth) {
                this._online.connect();
            }
        },
        beforeUnmount() {
            this._online.disconnect();
        }
    }
</script>

<style scoped>
/* ============================================================
   SystemBar - Premium Top Bar
   ============================================================ */

.systembar-premium {
    border: none !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.systembar-light {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
}

.systembar-dark {
    background: rgba(33, 33, 33, 0.88) !important;
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
}

/* ---------- Inner Layout ---------- */
.systembar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 6px 16px;
    min-height: 52px;
}

.systembar-left {
    display: flex;
    align-items: center;
    min-width: 50px;
}

.systembar-center {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
}

.systembar-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ---------- Nav Button ---------- */
.systembar-nav-btn {
    border-radius: 50% !important;
    transition: all 0.2s ease !important;
}

.systembar-nav-btn:hover {
    background: rgba(33, 150, 243, 0.08) !important;
}

/* ---------- Logo ---------- */
.systembar-logo-link {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.systembar-mini-logo {
    width: 28px;
    height: 28px;
    object-fit: contain;
    border-radius: 6px;
    flex-shrink: 0;
}

.systembar-logo-text {
    font-family: 'Inter', 'Poppins', system-ui, sans-serif;
    font-weight: 800;
    font-size: 1.35rem;
    letter-spacing: 0.5px;
    background: linear-gradient(
        135deg,
        #1565c0 0%,
        #42a5f5 25%,
        #2196F3 50%,
        #1976D2 75%,
        #1565c0 100%
    );
    background-size: 200% 200%;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: logoGradient 4s ease-in-out infinite;
    transition: opacity 0.2s ease;
}

.systembar-logo-link:hover .systembar-logo-text {
    opacity: 0.85;
}

@keyframes logoGradient {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ---------- Action Buttons ---------- */
.systembar-action-btn {
    border-radius: 50% !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative;
}

.systembar-action-btn:hover {
    background: rgba(33, 150, 243, 0.08) !important;
    transform: translateY(-1px);
}

/* ---------- Language Button ---------- */
.systembar-lang-btn {
    min-width: auto !important;
    padding: 0 10px !important;
    gap: 6px;
    height: 36px !important;
    border-radius: 20px !important;
}

.lang-current-flag {
    width: 22px;
    height: 16px;
    border-radius: 3px;
    object-fit: cover;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.lang-current-code {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    opacity: 0.7;
}

.lang-chevron {
    opacity: 0.5;
    transition: transform 0.2s ease;
}

.systembar-lang-btn:hover .lang-chevron {
    transform: translateY(1px);
}

/* ---------- Language Dropdown ---------- */
.lang-dropdown {
    overflow: hidden;
}

.lang-dropdown-light {
    background: rgba(255, 255, 255, 0.98) !important;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.lang-dropdown-dark {
    background: rgba(40, 40, 40, 0.98) !important;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.lang-dropdown-header {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    opacity: 0.7;
    text-transform: uppercase;
}

.lang-dropdown-list {
    padding: 6px 8px 8px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lang-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    text-align: left;
    color: inherit;
    font-family: inherit;
}

.lang-option:hover {
    background: rgba(33, 150, 243, 0.08);
}

.lang-option-active {
    background: rgba(33, 150, 243, 0.1);
}

.lang-option-active:hover {
    background: rgba(33, 150, 243, 0.14);
}

.lang-option-flag {
    width: 32px;
    height: 24px;
    border-radius: 4px;
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
}

.lang-option-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.lang-option-name {
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.2;
}

.lang-option-code {
    font-size: 0.68rem;
    font-weight: 500;
    opacity: 0.5;
    letter-spacing: 0.5px;
}

.lang-option-check {
    flex-shrink: 0;
}

/* ---------- Theme Toggle ---------- */
.systembar-theme-btn {
    border-radius: 50% !important;
}

.systembar-theme-btn .v-icon {
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}

.theme-icon-rotate {
    transform: rotate(180deg);
}

/* ---------- Charges Archive Panel ---------- */
.charges-archive-panel {
    overflow: hidden;
}

.archive-light {
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.archive-dark {
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.archive-title {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
    font-weight: 700;
    padding: 14px 20px;
    letter-spacing: 0.2px;
}

.archive-actions {
    display: flex;
    gap: 8px;
}

/* ---------- Online Users ---------- */
.online-pulse-icon {
    animation: onlinePulse 1.8s ease-in-out infinite;
}

@keyframes onlinePulse {
    0%   { opacity: 1; transform: scale(1); }
    50%  { opacity: 0.6; transform: scale(1.15); }
    100% { opacity: 1; transform: scale(1); }
}

.online-users-panel {
    overflow: hidden;
}

.online-user-item {
    transition: background 0.2s ease;
}

.online-user-item:hover {
    background: rgba(76, 175, 80, 0.06);
}

/* ---------- Responsive ---------- */
@media (max-width: 600px) {
    .systembar-inner {
        padding: 4px 8px;
    }

    .lang-current-code {
        display: none;
    }

    .systembar-logo-text {
        font-size: 1.1rem;
    }
}
</style>
