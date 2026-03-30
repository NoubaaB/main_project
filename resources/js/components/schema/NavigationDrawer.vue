<template>
    <v-navigation-drawer 
      v-model="main.menu_drawer"
      temporary 
      class="mx-auto pa-2">
      <v-sheet
        class="pa-4 text-center"
      >
        <v-avatar
          class="mb-4"
          color="blue-lighten-5"
          size="70"
        >
          <v-icon size="50" class="auth-animated-title text-center">
            mdi-account
          </v-icon>
        </v-avatar>

        <div>john@google.com</div>
      </v-sheet>

      <v-divider></v-divider>

      <v-list  v-model:opened="open">
        <template v-for="([icon , text , childrens , url], i) in links" :key="i">
          <v-list-item 
            v-if="childrens == null"
            class="my-2"
            :prepend-icon="icon" 
            :title="text" 
            :value="text" 
            rounded="xl" 
            color="info"
            :exact="true"
            :to="{'name':url}"
            ></v-list-item>
          
          <v-list-group v-else :value="text">
            <template v-slot:activator="{ isOpen , props }">
              <v-list-item
                class="my-2"
                v-bind="props"
                :prepend-icon="icon"
                :title="text"
                rounded="xl"
                color="info"
                :active="isOpen"
                :class-active="isOpen"
                :exact="true"
              ></v-list-item>
            </template>
              <v-list-item
                class="my-2"
                v-for="([_icon , _text , _url], j) in childrens"
                :key="j"
                :title="_text"
                :prepend-icon="_icon"
                :value="_text"
                rounded="xl"
                color="info"
                :to="{'name':_url}"
                :exact="true"
              ></v-list-item>
          </v-list-group>
              
        </template>
      </v-list>

      <template v-slot:append>
        <v-divider></v-divider>

        <v-list density="compact" nav class="px-0">
          <v-sheet
            :class="main.is_dark ? 'bg-grey-darken-3':'bg-info-lighten-5'"
            class="mx-auto my-auto rounded-lg px-2 py-2"
          >
          <div class="d-flex align-center hide-menu">
            <v-avatar
              class="mb-4"
              color="blue-lighten-5"
              size="40"
            >
              <v-icon size="30" class="auth-animated-title">
                mdi-account
              </v-icon>
            </v-avatar>
            <div class="ml-3 d-block">
              <div class="font-weight-black">
                {{auth.user.name}}
              </div>
              <div class="font-weight-light">
                {{auth.user.role.name}}
              </div>
            </div>
            <v-btn
            variant="text"
            icon="mdi-power"
            color="info"
            size="large"
            @click.prevent="logout()"
            rounded="lg"
            class="ma-auto">
              
            </v-btn>
          </div>
          </v-sheet>
        </v-list>
      </template>
    </v-navigation-drawer>
</template>

<script>
    import {useDashboardWaiter} from "@/stores/DashboardWaiter"; 
    import {useMain} from "@/stores/Main"; 
    import { useAuth } from "@/stores/Auth";
    
    export default {
        data() {
          return {
            open:null
          }
        },
        computed:{
          dashboard_waiter: function () {
            return useDashboardWaiter();
          },
          main:function(){
            return useMain();
          },
          auth:function(){
            return useAuth();
          },
          links: function () {
           return [
              ['mdi-human-male-board-poll', this.$t("general"),null,"view_supplier_invoice"],
              ['mdi-robot', this.$t("ai_prompt"),null,"ai_prompt"],
              ['mdi-warehouse', this.$t('charges'),[
                ['mdi-sticker-plus-outline', this.$t("add_charge"),"add_charge"],
                ['mdi-view-list-outline', this.$t("list_charge"),"list_charge"],
              ]],
              ['mdi-file-document-check', this.$t('invoics'),[
                ['mdi-file-document-multiple', this.$t("list_supplier_invoice"),"list_supplier_invoice"],
              ]],
              ['mdi-pencil', this.$t('commands'),[
                ['mdi-pencil-plus', this.$t("main"),"error404"],
                ['mdi-pencil-plus', this.$t("create_command"),"error404"],
                ['mdi-view-list', this.$t("list_command"),"error404"],
                ['mdi-playlist-remove', this.$t("cancelled_command"),"error404"],
                ['mdi-poll', this.$t("statistics_command"),"error404"],
              ]],
            ]}
        },
        methods:{
          logout:function(){
            this.auth.logout().then(res => {
                this.$router.push({ "name": "login" });
            })
          }
        }
    }
</script>

<style lang="scss" scoped>
  .v-list-group__items .v-list-item{
    padding-inline-start: calc(-24px + var(--indent-padding)) !important;
  }
  .auth-animated-title {
    background-color: linear-gradient(
      270deg,
      #1565c0,
      #6dc7ff,
      #2979ff,
      #1565c0
    );
    background-size: 300% 300%;
    animation: gradientMove 6s ease-in-out infinite;
  }

  /* Gradient sweep */
  @keyframes gradientMove {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }
</style>