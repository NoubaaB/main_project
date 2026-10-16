import { defineStore } from 'pinia';
import axios from 'axios';
import mitt from '@/plugins/mitt';

export const useInvoiceLayout = defineStore('invoiceLayout', {
  state: () => ({
    settings: {
      client: {
        header_divided_height: '3cm',
        header_strick_height: '0.3cm',
        header_primary_color: '#006064',
        header_secondary_color: '#4DD0E1',
        header_text_color: '#006064',
        header_bg_color: '#FFFFFF',
        header_style: 'curved',
        header_logo_width: '6cm',
        header_title_size: 'xxx-large',
        header_title_text: null,
        footer_divided_height: '3cm',
        footer_strick_height: '0.3cm',
        footer_primary_color: '#006064',
        footer_secondary_color: '#4DD0E1',
        footer_text_color: '#006064',
        footer_bg_color: '#FFFFFF',
        footer_style: 'curved',
        footer_director_title: null,
        footer_director_size: 'x-large',
        footer_line_style: 'dashed',
        footer_show_director: true,
        options: {},
      },
      supplier: {
        header_divided_height: '3cm',
        header_strick_height: '0.3cm',
        header_primary_color: '#01579B',
        header_secondary_color: '#4FC3F7',
        header_text_color: '#01579B',
        header_bg_color: '#FFFFFF',
        header_style: 'curved',
        header_logo_width: '6cm',
        header_title_size: 'xxx-large',
        header_title_text: null,
        footer_divided_height: '3cm',
        footer_strick_height: '0.3cm',
        footer_primary_color: '#01579B',
        footer_secondary_color: '#4FC3F7',
        footer_text_color: '#01579B',
        footer_bg_color: '#FFFFFF',
        footer_style: 'curved',
        footer_director_title: null,
        footer_director_size: 'x-large',
        footer_line_style: 'dashed',
        footer_show_director: true,
        options: {},
      },
    },
    loading: false,
    saving: false,
    initialized: false,
  }),

  actions: {
    /**
     * Fetch settings from the API.
     */
    async fetchSettings() {
      this.loading = true;
      try {
        const res = await axios.get('/api/invoice-layout-settings');
        if (res.data?.settings) {
          if (res.data.settings.client) {
            this.settings.client = { ...this.settings.client, ...res.data.settings.client };
          }
          if (res.data.settings.supplier) {
            this.settings.supplier = { ...this.settings.supplier, ...res.data.settings.supplier };
          }
        }
        this.initialized = true;
      } catch (err) {
        console.error('[InvoiceLayout] Failed to fetch settings:', err);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Save settings to database.
     */
    async saveSettings(type, payload) {
      this.saving = true;
      try {
        const res = await axios.put(`/api/invoice-layout-settings/${type}`, payload);
        if (res.data?.setting) {
          this.settings[type] = { ...this.settings[type], ...res.data.setting };
        }
        return res.data;
      } catch (err) {
        console.error(`[InvoiceLayout] Failed to save settings for ${type}:`, err);
        throw err;
      } finally {
        this.saving = false;
      }
    },

    /**
     * Reset settings to system defaults in database.
     */
    async resetSettings(type) {
      this.saving = true;
      try {
        const res = await axios.post(`/api/invoice-layout-settings/${type}/reset`);
        if (res.data?.setting) {
          this.settings[type] = { ...this.settings[type], ...res.data.setting };
        }
        return res.data;
      } catch (err) {
        console.error(`[InvoiceLayout] Failed to reset settings for ${type}:`, err);
        throw err;
      } finally {
        this.saving = false;
      }
    },

    /**
     * Update settings in store (e.g. from WebSocket broadcast).
     */
    setSettingsFromBroadcast(settingData) {
      if (!settingData || !settingData.invoice_type) return;
      const type = settingData.invoice_type;
      this.settings[type] = { ...this.settings[type], ...settingData };
    },

    /**
     * Initialize WebSocket listener.
     */
    initSync() {
      if (this._syncInitialized) return;
      this._syncInitialized = true;
      mitt.on('model-changed', (data) => {
        if (data.model === 'InvoiceLayoutSetting') {
          if (data.record) {
            this.setSettingsFromBroadcast(data.record);
          } else {
            this.fetchSettings();
          }
        }
      });
      // Initial fetch if not done yet
      if (!this.initialized) {
        this.fetchSettings();
      }
    },
  },

  getters: {
    clientLayout: (state) => state.settings.client,
    supplierLayout: (state) => state.settings.supplier,
    getLayout: (state) => (type) => state.settings[type] || state.settings.client,
  },
});
