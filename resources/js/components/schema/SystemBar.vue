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
                    <v-btn class="auth-animated-title" variant="plain" to="/">
                        {{
                            $t("title_logo")
                        }}
                    </v-btn>
                </v-toolbar-title>
                
                <div class="d-flex">
                    <v-menu transition="scroll-y-transition"       
                    :close-on-content-click="false"
                    >
                        <template v-slot:activator="{ props }">
                            <v-btn
                                color="green"
                                variant="tonal"
                                v-bind="props"
                                icon
                                class="mt-1 mx-3"
                                v-if="auth.is_auth"
                            >
                                <v-badge
                                v-if="_charge.charges.length > 0"
                                color="yellow" 
                                :content="_charge.charges.length"
                                location="bottom end"
                                >
                                    <v-icon>
                                        mdi-file-document-multiple
                                    </v-icon>
                                </v-badge>
                                <v-icon v-else>mdi-file-document-multiple-outline</v-icon>
                            </v-btn>
                        </template>
                        <v-sheet
                            class="pa-4 mt-4 text-center mx-auto"
                            elevation="12"
                            max-width="600"
                            rounded="lg"
                            width="100%"
                        >
                            <h2 class="text-h5 mb-6">Charges Archivied</h2>
                            <v-data-table
                                v-model="selected"
                                :headers="headers"
                                :items="_charge.charges"
                                tabindex="0"
                                item-value="id"
                                show-select
                                density="compact"
                                item-key="id"
                                hide-default-footer
                            >
                                <template v-slot:[`header.data-table-select`]="{ allSelected, selectAll }">
                                <v-checkbox
                                    :model-value="allSelected"
                                    @update:model-value="selectAll"
                                    :indeterminate="selected.length > 0 && !allSelected"
                                    color="info"
                                    class="px-0"
                                    hide-details
                                ></v-checkbox>
                                </template>
                                <template v-slot:[`item.data-table-select`]="{ internalItem, isSelected, toggleSelect }">
                                <v-checkbox-btn
                                    :model-value="isSelected(internalItem)"
                                    color="info"
                                    @update:model-value="toggleSelect(internalItem)"
                                ></v-checkbox-btn>
                                </template>
                                <template v-slot:no-data>
                                    <div class="text-orange">
                                        <v-icon icon="mdi-database-alert-outline" size="large" class="mx-2"></v-icon>
                                        <span>No data available</span>
                                    </div>
                                </template>
                                <template v-slot:[`item.tracking_numbers`]="{ item }">
                                    <v-chip  color="secondary" v-for="tracking_number in item.tracking_numbers" :key="tracking_number" class="mx-1 mt-1">
                                        {{ tracking_number }}
                                    </v-chip>
                                </template>
                                <template v-slot:[`item.date`]="{ item }">
                                    {{ main.formateDate(item.date) }}
                                </template>
                                <template v-slot:[`item.charge_category_id`]="{ item }">
                                    <v-chip
                                        color="info"
                                        class="mt-1"
                                        :text="_charge_category.get_charge_category_name_by_id(item.charge_category_id)"
                                    ></v-chip>
                                </template>
                                <template v-slot:[`item.supplier_ids`]="{ item }">
                                    <v-responsive class="overflow-y-auto" max-height="280">
                                        <v-chip
                                            v-for="suppliers_id in item.supplier_ids"
                                            color="info"
                                            class="mt-1"
                                            size="small"
                                            :key="suppliers_id"
                                            :text="_supplier.get_supplier_name_by_id(suppliers_id)"
                                        ></v-chip>
                                    </v-responsive>
                                </template>
                                <template v-slot:[`item.total_weight`]="{ item }">
                                    {{ main.formatDisplayNumber(item.total_weight) }}
                                </template>
                                <template v-slot:[`item.total_price`]="{ item }">
                                    {{ main.formatDisplayNumber(item.total_price) }}
                                </template>
                                <template v-slot:[`item.actions`]="{ item }">
                                <div class="d-flex ga-2 justify-end">
                                    <v-icon color="amber" icon="mdi-pencil" size="small" @click="edit(item.id)"></v-icon>

                                    <v-icon color="red" icon="mdi-delete" size="small" @click="remove(item.id)"></v-icon>
                                </div>
                                </template>
                            </v-data-table>
                            <v-row class="mt-1">
                                <v-col>
                                    <div class="text-center">
                                        <v-btn
                                            :disabled="selected.length == 0"
                                            class="text-none"
                                            color="info"
                                            variant="tonal"
                                            width="90"
                                            rounded
                                            block
                                        >
                                            {{ `${selected.length == 0 ? '': `(${selected.length})`} ${$t('submit')}` }}
                                        </v-btn>
                                    </div>
                                </v-col>
                                <v-col>
                                    <div class="text-center">
                                        <v-btn
                                            :disabled="selected.length == 0"
                                            class="text-none"
                                            color="red"
                                            variant="outlined"
                                            width="90"
                                            rounded
                                            block
                                            @click="delete_archived_charges"
                                        >
                                            {{ `${selected.length == 0 ? '': `(${selected.length})`} ${$t('delete')}` }}
                                        </v-btn>
                                    </div>
                                </v-col>
                            </v-row>

                        </v-sheet>
                    </v-menu>                    
                    <v-menu transition="scroll-y-transition">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                color="info"
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
                            class="my-2 mx-2"
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
                    <v-switch
                        v-model="main.is_dark"
                        true-icon="mdi-white-balance-sunny"
                        false-icon="mdi-weather-night"
                        hide-details
                        color="light-info-darken-3"
                        class="text-white mx-1"
                        base-color="info"
                        inset
                    >
                    </v-switch>
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
    import { useCharge } from "@/stores/Charge";
    import { useSupplier } from "@/stores/Supplier";
    import { useChargeCategory } from "@/stores/ChargeCategory";
    import annyang from "annyang";

    export default {
        data:function(){
          return {
            selected: [],
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
            _charge: function () {
                return useCharge();
            },
            _supplier: function () {
                return useSupplier();
            },
            _charge_category: function () {
                return useChargeCategory();
            },
            headers: function () {
                return [
                    { title: this.$t("charge.date"), value: "date" },
                    { title: this.$t("charge.charge_category"), value: "charge_category_id" },
                    { title: this.$t("charge.suppliers"), value: "supplier_ids" },
                    { title: this.$t("charge.tracking_numbers"), value: "tracking_numbers" },
                    { title: this.$t("charge.total_price"), value: "total_price" },
                    { title: this.$t("charge.total_weight"), value: "total_weight" },
                    { title: this.$t("charge.actions"), value: "actions", align: 'end', sortable: false }
                ];
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
                this.main.lang = lang;
            },
            edit: function (item_id) {
                this._charge.edit_archived_charge(item_id);
            },
            remove: function (item_id) {
                this.selected = [item_id];
                this.delete_archived_charges();
            },
            delete_archived_charges: function () {
                this._charge.deleteMany(this.selected);
            }
        }
    }
</script>

<style>
.v-input{
    flex:unset;
}
.auth-animated-title {
  font-family: 'Poppins', sans-serif;
  font-weight: 700;
  font-size: 1.55rem;
  letter-spacing: 1px;
  background: linear-gradient(
    270deg,
    #1565c0,
    #6dc7ff,
    #2979ff,
    #1565c0
  );
  background-size: 300% 300%;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: gradientMove 6s ease-in-out infinite;
}

/* Gradient sweep */
@keyframes gradientMove {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
</style>