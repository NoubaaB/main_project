<template>
    <v-navigation-drawer 
      v-model="main.menu_drawer"
      temporary 
      class="mx-auto pa-2">
      <v-sheet
        class="pa-4"
      >
        <v-avatar
          class="mb-4"
          color="grey-darken-1"
          size="64"
        ></v-avatar>

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
            color="blue"
            :exact="true"
            :to="{'name':url}"
            ></v-list-item>
          
          <v-list-group v-else :value="text">
            <template v-slot:activator="{ props }">
              <v-list-item
                class="my-2"
                v-bind="props"
                :prepend-icon="icon"
                :title="text"
                rounded="xl"
                color="blue"
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
                color="blue"
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
            :class="main.is_dark ? 'bg-grey-darken-3':'bg-blue-lighten-5'"
            class="mx-auto my-auto rounded-lg px-2 py-2"
          >
          <div class="d-flex align-center hide-menu">
            <v-avatar
              color="grey-darken-1"
              size="40"
            ></v-avatar>
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
            color="blue"
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
              ['mdi-human-male-board-poll', this.$t("general"),null,"index"],
              ['mdi-robot', this.$t("AI Prompt"),null,"ai_prompt"],
              ['mdi-food-fork-drink', this.$t('commands'),[
                ['mdi-pencil-plus', this.$t("main"),"index"],
                ['mdi-pencil-plus', this.$t("create_command"),"index"],
                ['mdi-view-list', this.$t("list_command"),"index"],
                ['mdi-playlist-remove', this.$t("cancelled_command"),"index"],
                ['mdi-poll', this.$t("statistics_command"),"index"],
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
</style>