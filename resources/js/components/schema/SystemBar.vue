<template>
    <v-app-bar rounded="shaped" style="z-index:3" height="10vh" width="100vw" :color="main.color_theme" flat density="compact">
        <v-card  width="100vw">
            <v-toolbar
                :color="main.color_theme"
                density="compact"
                prominent
                rounded
            >
                <v-app-bar-nav-icon
                    v-if="auth.is_auth"
                    @click.prevent="main.menu_drawer = !main.menu_drawer"
                ></v-app-bar-nav-icon>

                <v-toolbar-title class="text-center" single-line>
                    <v-btn variant="plain" to="/">
                        {{
                            $t("title_logo")
                        }}
                    </v-btn>
                </v-toolbar-title>
                
                <div class="d-flex">
                    <v-switch
                        v-model="main.is_dark"
                        true-icon="mdi-white-balance-sunny"
                        false-icon="mdi-weather-night"
                        hide-details
                        color="light-blue-darken-3"
                        class="text-white mx-1"
                        base-color="blue"
                        inset
                    >
                    </v-switch>
                    <v-menu transition="scroll-y-transition" class="mx-1">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                color="blue"
                                variant="tonal"
                                v-bind="props"
                                icon="mdi-translate"
                                class="mt-1"
                            >
                            </v-btn>
                        </template>
                        <v-btn
                            v-for="lang in main.langs"
                            :key="lang.id"
                            @click="changeLanguage(lang)"
                            variant="tonal"
                            class="my-2 mx-3"
                            style="overflow: hidden"
                            icon
                            size="40"
                        >
                            <span
                                :class="`flag f-${lang.short} rounded big-flag`"
                            >
                            {{lang.code}}
                            </span>
                        </v-btn>
                    </v-menu>
                </div>
            </v-toolbar>
            <v-divider></v-divider>
        </v-card>
    </v-app-bar>
</template>

<script>
    import { useDashboardWaiter } from "@/stores/DashboardWaiter"; 
    import { useMain } from "@/stores/Main"; 
    import { useAuth } from "@/stores/Auth";
    import annyang from "annyang";

    export default {
        computed:{
            dashboard_waiter: function () {
                return useDashboardWaiter();
            },
            main: function () {
                return useMain();
            },
            auth: function () {
                return useAuth();
            }
        },
        methods:{
            changeLanguage: function (lang) {
                // this.$vuetify.locale.rtl = (lang.code == "ar");
                localStorage.setItem("language", lang.code);
                this.$vuetify.locale.current = lang.code;
                this.$i18n.locale = lang.code;
                console.log(lang)
                annyang.setLanguage(lang.annyang);
                
            },
        }
    }
</script>

<style>
.v-input{
    flex:unset;
}
</style>