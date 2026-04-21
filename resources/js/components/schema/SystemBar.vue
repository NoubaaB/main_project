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
                    @click.prevent="main.sidebar_mini = !main.sidebar_mini; main.menu_drawer = true"
                >
                    <v-icon size="20">mdi-menu</v-icon>
                </v-btn>
            </div>

            <!-- Center: Logo / Title -->
            <div class="systembar-center">
                <router-link to="/" class="systembar-logo-link">
                    <span class="systembar-logo-text">
                        {{ $t("title_logo") }}
                    </span>
                </router-link>
            </div>

            <!-- Right: Actions -->
            <div class="systembar-right">
                <!-- Archives Button -->
                <v-btn
                    v-if="auth.is_auth"
                    icon
                    variant="tonal"
                    color="teal"
                    size="36"
                    class="systembar-action-btn"
                    @click="archiveDialog = true"
                    id="archive-btn"
                >
                    <v-badge
                        v-if="_archive.totalCount > 0"
                        :content="_archive.totalCount"
                        color="amber"
                        text-color="white"
                        location="bottom end"
                        offset-x="2"
                        offset-y="2"
                    >
                        <v-icon size="19">mdi-archive-check</v-icon>
                    </v-badge>
                    <v-icon v-else size="19">mdi-archive-outline</v-icon>
                    <v-tooltip activator="parent" location="bottom">{{ $t('archives') }}</v-tooltip>
                </v-btn>

                <!-- Archive Dialog -->
                <v-dialog v-model="archiveDialog" max-width="960" scrollable>
                    <v-card rounded="xl" :class="main.is_dark ? 'archive-dark' : 'archive-light'">
                        <v-card-title class="d-flex align-center pa-4">
                            <v-icon class="mr-2" size="24" color="teal">mdi-archive-check</v-icon>
                            <span class="text-h6 font-weight-bold">{{ $t('archives') }}</span>
                            <v-chip class="ml-2" size="small" color="teal" variant="tonal">{{ _archive.totalCount }}</v-chip>
                            <v-spacer></v-spacer>
                            <v-btn icon variant="text" size="small" @click="archiveDialog = false">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-tabs v-model="archiveTab" color="teal" grow density="compact">
                            <v-tab value="charges">
                                <v-icon start size="18">mdi-warehouse</v-icon>
                                {{ $t('charges') }}
                                <v-chip v-if="_archive.chargeCount > 0" class="ml-1" size="x-small" color="info" variant="tonal">{{ _archive.chargeCount }}</v-chip>
                            </v-tab>
                            <v-tab value="sells">
                                <v-icon start size="18">mdi-cart-check</v-icon>
                                {{ $t('sells') }}
                                <v-chip v-if="_archive.sellCount > 0" class="ml-1" size="x-small" color="green" variant="tonal">{{ _archive.sellCount }}</v-chip>
                            </v-tab>
                            <v-tab value="productions">
                                <v-icon start size="18">mdi-cog-outline</v-icon>
                                {{ $t('productions') }}
                                <v-chip v-if="_archive.productionCount > 0" class="ml-1" size="x-small" color="teal" variant="tonal">{{ _archive.productionCount }}</v-chip>
                            </v-tab>
                        </v-tabs>
                        <v-divider></v-divider>
                        <v-card-text class="pa-0" style="max-height: 60vh; overflow-y: auto;">
                            <v-tabs-window v-model="archiveTab">
                                <!-- ═══ Charges Tab ═══ -->
                                <v-tabs-window-item value="charges">
                                    <div v-if="_archive.charges.length > 0" class="pa-2">
                                        <div class="d-flex align-center ga-2 pa-2">
                                            <v-btn size="small" color="teal" variant="flat" rounded="lg" :disabled="selectedCharges.length === 0" @click="submitSelectedCharges" :loading="archiveLoading">
                                                <v-icon start size="16">mdi-check-all</v-icon>
                                                {{ $t('submit_selected') }} {{ selectedCharges.length > 0 ? `(${selectedCharges.length})` : '' }}
                                            </v-btn>
                                            <v-btn size="small" color="error" variant="tonal" rounded="lg" :disabled="selectedCharges.length === 0" @click="deleteSelectedCharges">
                                                <v-icon start size="16">mdi-delete-outline</v-icon>
                                                {{ $t('delete_selected') }} {{ selectedCharges.length > 0 ? `(${selectedCharges.length})` : '' }}
                                            </v-btn>
                                        </div>
                                        <v-data-table
                                            v-model="selectedCharges"
                                            :headers="chargeHeaders"
                                            :items="_archive.charges"
                                            item-value="id"
                                            show-select
                                            density="compact"
                                            hide-default-footer
                                            class="archive-table"
                                        >
                                            <template v-slot:[`item.date`]="{ item }">
                                                {{ main.formateDate(item.date) }}
                                            </template>
                                            <template v-slot:[`item.charge_category_id`]="{ item }">
                                                <v-chip color="info" size="x-small" variant="tonal">{{ _charge_category.get_charge_category_name_by_id(item.charge_category_id) }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.reception_tracking_number`]="{ item }">
                                                <v-chip v-for="tn in (item.reception_tracking_number || [])" :key="tn" size="x-small" color="secondary" variant="tonal" class="mr-1 mt-1">{{ tn }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.supplier_ids`]="{ item }">
                                                <v-chip v-for="sid in (item.supplier_ids || [])" :key="sid" size="x-small" color="info" variant="tonal" class="mr-1 mt-1">{{ _supplier.get_supplier_name_by_id(sid) }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.total_weight`]="{ item }">
                                                {{ main.formatDisplayNumber(item.total_weight) }}
                                            </template>
                                            <template v-slot:[`item.total_price`]="{ item }">
                                                {{ main.formatDisplayNumber(item.total_price) }}
                                            </template>
                                            <template v-slot:[`item.actions`]="{ item }">
                                                <div class="d-flex ga-1">
                                                    <v-btn icon variant="text" size="x-small" color="amber" @click="editChargeArchive(item.id)">
                                                        <v-icon size="16">mdi-pencil</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('edit') }}</v-tooltip>
                                                    </v-btn>
                                                    <v-btn icon variant="text" size="x-small" color="teal" @click="submitChargeArchive(item.id)" :loading="archiveLoading">
                                                        <v-icon size="16">mdi-send-check</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('submit') }}</v-tooltip>
                                                    </v-btn>
                                                    <v-btn icon variant="text" size="x-small" color="red" @click="deleteChargeArchive(item.id)">
                                                        <v-icon size="16">mdi-delete</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('delete') }}</v-tooltip>
                                                    </v-btn>
                                                </div>
                                            </template>
                                        </v-data-table>
                                    </div>
                                    <div v-else class="text-medium-emphasis d-flex flex-column align-center justify-center py-10">
                                        <v-icon size="48" color="grey" class="mb-2">mdi-archive-off-outline</v-icon>
                                        <span>{{ $t('no_archives') }}</span>
                                    </div>
                                </v-tabs-window-item>

                                <!-- ═══ Sells Tab ═══ -->
                                <v-tabs-window-item value="sells">
                                    <div v-if="_archive.sells.length > 0" class="pa-2">
                                        <div class="d-flex align-center ga-2 pa-2">
                                            <v-btn size="small" color="green" variant="flat" rounded="lg" :disabled="selectedSells.length === 0" @click="submitSelectedSells" :loading="archiveLoading">
                                                <v-icon start size="16">mdi-check-all</v-icon>
                                                {{ $t('submit_selected') }} {{ selectedSells.length > 0 ? `(${selectedSells.length})` : '' }}
                                            </v-btn>
                                            <v-btn size="small" color="error" variant="tonal" rounded="lg" :disabled="selectedSells.length === 0" @click="deleteSelectedSells">
                                                <v-icon start size="16">mdi-delete-outline</v-icon>
                                                {{ $t('delete_selected') }} {{ selectedSells.length > 0 ? `(${selectedSells.length})` : '' }}
                                            </v-btn>
                                        </div>
                                        <v-data-table
                                            v-model="selectedSells"
                                            :headers="sellHeaders"
                                            :items="_archive.sells"
                                            item-value="id"
                                            show-select
                                            density="compact"
                                            hide-default-footer
                                            class="archive-table"
                                        >
                                            <template v-slot:[`item.date`]="{ item }">
                                                {{ main.formateDate(item.date) }}
                                            </template>
                                            <template v-slot:[`item.sell_category_id`]="{ item }">
                                                <v-chip color="green" size="x-small" variant="tonal">{{ _sell_category.get_sell_category_name_by_id(item.sell_category_id) }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.tracking_numbers`]="{ item }">
                                                <v-chip v-for="tn in (item.tracking_numbers || [])" :key="tn" size="x-small" color="secondary" variant="tonal" class="mr-1 mt-1">{{ tn }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.client_ids`]="{ item }">
                                                <v-chip v-for="cid in (item.client_ids || [])" :key="cid" size="x-small" color="green" variant="tonal" class="mr-1 mt-1">{{ _client.get_client_name_by_id(cid) }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.total_weight`]="{ item }">
                                                {{ main.formatDisplayNumber(item.total_weight) }}
                                            </template>
                                            <template v-slot:[`item.total_price`]="{ item }">
                                                {{ main.formatDisplayNumber(item.total_price) }}
                                            </template>
                                            <template v-slot:[`item.actions`]="{ item }">
                                                <div class="d-flex ga-1">
                                                    <v-btn icon variant="text" size="x-small" color="amber" @click="editSellArchive(item.id)">
                                                        <v-icon size="16">mdi-pencil</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('edit') }}</v-tooltip>
                                                    </v-btn>
                                                    <v-btn icon variant="text" size="x-small" color="green" @click="submitSellArchive(item.id)" :loading="archiveLoading">
                                                        <v-icon size="16">mdi-send-check</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('submit') }}</v-tooltip>
                                                    </v-btn>
                                                    <v-btn icon variant="text" size="x-small" color="red" @click="deleteSellArchive(item.id)">
                                                        <v-icon size="16">mdi-delete</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('delete') }}</v-tooltip>
                                                    </v-btn>
                                                </div>
                                            </template>
                                        </v-data-table>
                                    </div>
                                    <div v-else class="text-medium-emphasis d-flex flex-column align-center justify-center py-10">
                                        <v-icon size="48" color="grey" class="mb-2">mdi-archive-off-outline</v-icon>
                                        <span>{{ $t('no_archives') }}</span>
                                    </div>
                                </v-tabs-window-item>

                                <!-- ═══ Productions Tab ═══ -->
                                <v-tabs-window-item value="productions">
                                    <div v-if="_archive.productions.length > 0" class="pa-2">
                                        <div class="d-flex align-center ga-2 pa-2">
                                            <v-btn size="small" color="teal" variant="flat" rounded="lg" :disabled="selectedProductions.length === 0" @click="submitSelectedProductions" :loading="archiveLoading">
                                                <v-icon start size="16">mdi-check-all</v-icon>
                                                {{ $t('submit_selected') }} {{ selectedProductions.length > 0 ? `(${selectedProductions.length})` : '' }}
                                            </v-btn>
                                            <v-btn size="small" color="error" variant="tonal" rounded="lg" :disabled="selectedProductions.length === 0" @click="deleteSelectedProductions">
                                                <v-icon start size="16">mdi-delete-outline</v-icon>
                                                {{ $t('delete_selected') }} {{ selectedProductions.length > 0 ? `(${selectedProductions.length})` : '' }}
                                            </v-btn>
                                        </div>
                                        <v-data-table
                                            v-model="selectedProductions"
                                            :headers="productionHeaders"
                                            :items="_archive.productions"
                                            item-value="id"
                                            show-select
                                            density="compact"
                                            hide-default-footer
                                            class="archive-table"
                                        >
                                            <template v-slot:[`item.date`]="{ item }">
                                                {{ main.formateDate(item.date) }}
                                            </template>
                                            <template v-slot:[`item.production_category_id`]="{ item }">
                                                <v-chip color="teal" size="x-small" variant="tonal">{{ item.production_category_id }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.production_tracking_numbers`]="{ item }">
                                                <v-chip v-for="tn in (item.production_tracking_numbers || [])" :key="tn" size="x-small" color="teal" variant="tonal" class="mr-1 mt-1">{{ tn }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.supplier_ids`]="{ item }">
                                                <v-chip v-for="sid in (item.supplier_ids || [])" :key="sid" size="x-small" color="teal" variant="tonal" class="mr-1 mt-1">{{ _supplier.get_supplier_name_by_id(sid) }}</v-chip>
                                            </template>
                                            <template v-slot:[`item.total_weight`]="{ item }">
                                                {{ main.formatDisplayNumber(item.total_weight) }}
                                            </template>
                                            <template v-slot:[`item.total_price`]="{ item }">
                                                {{ main.formatDisplayNumber(item.total_price) }}
                                            </template>
                                            <template v-slot:[`item.actions`]="{ item }">
                                                <div class="d-flex ga-1">
                                                    <v-btn icon variant="text" size="x-small" color="amber" @click="editProductionArchive(item.id)">
                                                        <v-icon size="16">mdi-pencil</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('edit') }}</v-tooltip>
                                                    </v-btn>
                                                    <v-btn icon variant="text" size="x-small" color="teal" @click="submitProductionArchive(item.id)" :loading="archiveLoading">
                                                        <v-icon size="16">mdi-send-check</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('submit') }}</v-tooltip>
                                                    </v-btn>
                                                    <v-btn icon variant="text" size="x-small" color="red" @click="deleteProductionArchive(item.id)">
                                                        <v-icon size="16">mdi-delete</v-icon>
                                                        <v-tooltip activator="parent" location="top">{{ $t('delete') }}</v-tooltip>
                                                    </v-btn>
                                                </div>
                                            </template>
                                        </v-data-table>
                                    </div>
                                    <div v-else class="text-medium-emphasis d-flex flex-column align-center justify-center py-10">
                                        <v-icon size="48" color="grey" class="mb-2">mdi-archive-off-outline</v-icon>
                                        <span>{{ $t('no_archives') }}</span>
                                    </div>
                                </v-tabs-window-item>
                            </v-tabs-window>
                        </v-card-text>
                    </v-card>
                </v-dialog>

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
    import { useCharge } from "@/stores/Charge";
    import { useSupplier } from "@/stores/Supplier";
    import { useChargeCategory } from "@/stores/ChargeCategory";
    import { useSell } from "@/stores/Sell";
    import { useClient } from "@/stores/Client";
    import { useSellCategory } from "@/stores/SellCategory";
    import { useOnlineUsers } from "@/stores/OnlineUsers";
    import { useArchive } from "@/stores/Archive";
    import { useProductionCategory } from "@/stores/ProductionCategory";
    import annyang from "annyang";

    export default {
        data:function(){
          return {
            archiveDialog: false,
            archiveTab: 'charges',
            archiveLoading: false,
            selectedCharges: [],
            selectedSells: [],
            selectedProductions: [],
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
            _sell: function () {
                return useSell();
            },
            _client: function () {
                return useClient();
            },
            _online: function () {
                return useOnlineUsers();
            },
            _sell_category: function () {
                return useSellCategory();
            },
            currentLangFlag: function () {
                if (!this.main.lang) return 'https://flagcdn.com/24x18/gb.png';
                return this.getLangFlag(this.main.lang.code);
            },
            _archive: function () {
                return useArchive();
            },
            _production_category: function () {
                return useProductionCategory();
            },
            chargeHeaders: function () {
                return [
                    { title: this.$t('date'), value: 'date', width: '100px' },
                    { title: this.$t('charge_category'), value: 'charge_category_id' },
                    { title: this.$t('reception_tracking_number'), value: 'reception_tracking_number' },
                    { title: this.$t('suppliers'), value: 'supplier_ids' },
                    { title: this.$t('total_weight'), value: 'total_weight', width: '100px' },
                    { title: this.$t('total_price'), value: 'total_price', width: '100px' },
                    { title: this.$t('actions'), value: 'actions', align: 'end', sortable: false, width: '120px' },
                ];
            },
            sellHeaders: function () {
                return [
                    { title: this.$t('date'), value: 'date', width: '100px' },
                    { title: this.$t('sell_category'), value: 'sell_category_id' },
                    { title: this.$t('tracking_numbers'), value: 'tracking_numbers' },
                    { title: this.$t('clients'), value: 'client_ids' },
                    { title: this.$t('total_weight'), value: 'total_weight', width: '100px' },
                    { title: this.$t('total_price'), value: 'total_price', width: '100px' },
                    { title: this.$t('actions'), value: 'actions', align: 'end', sortable: false, width: '120px' },
                ];
            },
            productionHeaders: function () {
                return [
                    { title: this.$t('date'), value: 'date', width: '100px' },
                    { title: this.$t('production_category'), value: 'production_category_id' },
                    { title: this.$t('production_tracking_numbers'), value: 'production_tracking_numbers' },
                    { title: this.$t('suppliers'), value: 'supplier_ids' },
                    { title: this.$t('total_weight'), value: 'total_weight', width: '100px' },
                    { title: this.$t('total_price'), value: 'total_price', width: '100px' },
                    { title: this.$t('actions'), value: 'actions', align: 'end', sortable: false, width: '120px' },
                ];
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
                    'en': 'English',
                    'fr': 'Français',
                    'es': 'Español',
                    'ar': 'العربية',
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
            // ─── Archive actions ───
            editChargeArchive(id) {
                this._archive.editChargeArchive(id, this.$router);
                this.archiveDialog = false;
            },
            async submitChargeArchive(id) {
                this.archiveLoading = true;
                await this._archive.submitCharge(id);
                this.archiveLoading = false;
            },
            deleteChargeArchive(id) {
                this._archive.removeCharge(id);
            },
            async submitSelectedCharges() {
                this.archiveLoading = true;
                await this._archive.submitCharges([...this.selectedCharges]);
                this.selectedCharges = [];
                this.archiveLoading = false;
            },
            deleteSelectedCharges() {
                this._archive.removeCharges([...this.selectedCharges]);
                this.selectedCharges = [];
            },
            editSellArchive(id) {
                this._archive.editSellArchive(id, this.$router);
                this.archiveDialog = false;
            },
            async submitSellArchive(id) {
                this.archiveLoading = true;
                await this._archive.submitSell(id);
                this.archiveLoading = false;
            },
            deleteSellArchive(id) {
                this._archive.removeSell(id);
            },
            async submitSelectedSells() {
                this.archiveLoading = true;
                await this._archive.submitSells([...this.selectedSells]);
                this.selectedSells = [];
                this.archiveLoading = false;
            },
            deleteSelectedSells() {
                this._archive.removeSells([...this.selectedSells]);
                this.selectedSells = [];
            },
            editProductionArchive(id) {
                this._archive.editProductionArchive(id, this.$router);
                this.archiveDialog = false;
            },
            async submitProductionArchive(id) {
                this.archiveLoading = true;
                await this._archive.submitProduction(id);
                this.archiveLoading = false;
            },
            deleteProductionArchive(id) {
                this._archive.removeProduction(id);
            },
            async submitSelectedProductions() {
                this.archiveLoading = true;
                await this._archive.submitProductions([...this.selectedProductions]);
                this.selectedProductions = [];
                this.archiveLoading = false;
            },
            deleteSelectedProductions() {
                this._archive.removeProductions([...this.selectedProductions]);
                this.selectedProductions = [];
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
   SystemBar — Premium Top Bar
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