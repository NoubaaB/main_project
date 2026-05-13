import { defineStore } from "pinia";
import axios from "axios";

export const useCompanySettings = defineStore("companySettings", {
    state: () => {
        return {
            settings: {},
        };
    },
    actions: {
        /**
         * Fetch company settings from the API.
         */
        fetchSettings: async function () {
            try {
                const res = await axios.get("/api/company-settings");
                this.settings = res.data.settings || {};
                return this.settings;
            } catch (e) {
                console.error("[CompanySettings] Failed to fetch:", e);
            }
        },

        /**
         * Update settings from the WebSocket broadcast payload.
         * The payload is a key-value object of company settings.
         */
        setSettings: function (settingsData) {
            this.settings = { ...this.settings, ...settingsData };
        },
    },
    getters: {
        getSettings: (state) => state.settings,
        getValue: (state) => (key, defaultVal = null) => {
            return state.settings[key] ?? defaultVal;
        },
        companyName: (state) => state.settings.company_name || "",
        directorName: (state) => state.settings.director_name || "",
        directorGender: (state) => state.settings.director_gender || "male",
        companyOrigin: (state) => state.settings.company_origin || "",
        companyAddress: (state) => state.settings.company_address || "",
        swiftCode: (state) => state.settings.swift_code || "",
        phoneNumber: (state) => state.settings.phone_number || "",
        faxNumber: (state) => state.settings.fax_number || "",
        email: (state) => state.settings.email || "",
        bankName: (state) => state.settings.bank_name || "",
        bankAccountNumber: (state) => state.settings.bank_account_number || "",
        capital: (state) => state.settings.capital || "",
        ice: (state) => state.settings.ice || "",
        rc: (state) => state.settings.rc || "",
        cnss: (state) => state.settings.cnss || "",
        tvaNumber: (state) => state.settings.tva_number || "",
        logoInvoiceUrl: (state) => state.settings.logo_invoice_url || "",
        logoInvoiceBodyUrl: (state) => state.settings.logo_invoice_body_url || "",
        logoOriginUrl: (state) => state.settings.logo_origin_url || "",
    },
});
