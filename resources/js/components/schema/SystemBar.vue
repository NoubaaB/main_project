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
                <!-- Charges Archive Button -->
                <v-menu
                    transition="scroll-y-transition"
                    :close-on-content-click="false"
                    v-if="auth.is_auth"
                >
                    <template v-slot:activator="{ props }">
                        <v-btn
                            v-bind="props"
                            icon
                            variant="tonal"
                            color="teal"
                            size="36"
                            class="systembar-action-btn"
                        >
                            <v-badge
                                v-if="_charge.charges.length > 0"
                                :content="_charge.charges.length"
                                color="amber"
                                text-color="white"
                                location="bottom end"
                                offset-x="2"
                                offset-y="2"
                            >
                                <v-icon size="19">mdi-file-document-multiple</v-icon>
                            </v-badge>
                            <v-icon v-else size="19">mdi-file-document-multiple-outline</v-icon>
                            <v-tooltip activator="parent" location="bottom">Charges Archive</v-tooltip>
                        </v-btn>
                    </template>
                    <v-card
                        class="charges-archive-panel mt-2"
                        :class="main.is_dark ? 'archive-dark' : 'archive-light'"
                        max-width="640"
                        width="100%"
                        rounded="xl"
                        elevation="16"
                    >
                        <v-card-title class="archive-title">
                            <v-icon class="mr-2" size="20" color="info">mdi-archive-outline</v-icon>
                            Charges Archived
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-card-text class="pa-4">
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
                                    <div class="text-medium-emphasis d-flex align-center justify-center py-4">
                                        <v-icon icon="mdi-database-alert-outline" size="20" class="mr-2"></v-icon>
                                        <span>No data available</span>
                                    </div>
                                </template>
                                <template v-slot:[`item.tracking_numbers`]="{ item }">
                                    <v-chip color="secondary" size="small" v-for="tracking_number in item.tracking_numbers" :key="tracking_number" class="mx-1 mt-1">
                                        {{ tracking_number }}
                                    </v-chip>
                                </template>
                                <template v-slot:[`item.date`]="{ item }">
                                    {{ main.formateDate(item.date) }}
                                </template>
                                <template v-slot:[`item.charge_category_id`]="{ item }">
                                    <v-chip
                                        color="info"
                                        size="small"
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
                                            size="x-small"
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
                                    <v-btn icon variant="text" size="x-small" color="amber" @click="edit(item.id)">
                                        <v-icon size="16">mdi-pencil</v-icon>
                                    </v-btn>
                                    <v-btn icon variant="text" size="x-small" color="red" @click="remove(item.id)">
                                        <v-icon size="16">mdi-delete</v-icon>
                                    </v-btn>
                                </div>
                                </template>
                            </v-data-table>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions class="archive-actions pa-4">
                            <v-btn
                                :disabled="selected.length == 0"
                                class="text-none"
                                color="info"
                                variant="flat"
                                rounded="lg"
                                size="small"
                                min-width="120"
                            >
                                <v-icon start size="16">mdi-check</v-icon>
                                {{ `${selected.length == 0 ? '': `(${selected.length})`} ${$t('submit')}` }}
                            </v-btn>
                            <v-spacer></v-spacer>
                            <v-btn
                                :disabled="selected.length == 0"
                                class="text-none"
                                color="error"
                                variant="tonal"
                                rounded="lg"
                                size="small"
                                min-width="120"
                                @click="delete_archived_charges"
                            >
                                <v-icon start size="16">mdi-delete-outline</v-icon>
                                {{ `${selected.length == 0 ? '': `(${selected.length})`} ${$t('delete')}` }}
                            </v-btn>
                        </v-card-actions>
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
            currentLangFlag: function () {
                if (!this.main.lang) return 'https://flagcdn.com/24x18/gb.png';
                return this.getLangFlag(this.main.lang.code);
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