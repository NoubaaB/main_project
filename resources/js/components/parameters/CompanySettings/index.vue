<template>
  <v-card class="cs-card" elevation="0">
    <div class="cs-hero"><div class="cs-hero-inner"><div class="d-flex align-center ga-4"><div class="cs-hero-icon"><v-icon size="28" color="white">mdi-domain</v-icon></div><div><div class="cs-hero-title">{{ $t('company_settings') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('company_settings_desc') }}</div></div></div></div></div>
    <v-card-text class="pa-6">
      <v-form ref="form" @submit.prevent="save">
        <!-- Section: General -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-office-building</v-icon>{{ $t('general_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="4"><v-text-field v-model="form.company_name" :label="$t('company_name')" prepend-inner-icon="mdi-domain" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="4"><v-text-field v-model="form.director_name" :label="$t('director_name')" prepend-inner-icon="mdi-account-tie" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-select v-model="form.director_gender" :items="genderOptions" :label="$t('director_gender')" prepend-inner-icon="mdi-gender-male-female" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-select></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.company_origin" :label="$t('company_origin')" prepend-inner-icon="mdi-earth" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>
        <v-row dense class="mt-1">
          <v-col cols="12"><v-text-field v-model="form.company_address" :label="$t('company_address')" prepend-inner-icon="mdi-map-marker" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Contact -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-phone</v-icon>{{ $t('contact_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="3"><v-text-field v-model="form.phone_number" :label="$t('phone_number')" prepend-inner-icon="mdi-phone" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.fax_number" :label="$t('fax_number')" prepend-inner-icon="mdi-fax" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.email" :label="$t('email')" type="email" prepend-inner-icon="mdi-email-outline" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.swift_code" :label="$t('swift_code')" prepend-inner-icon="mdi-bank-transfer" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Banking -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-bank</v-icon>{{ $t('banking_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="6"><v-text-field v-model="form.bank_name" :label="$t('bank_name')" prepend-inner-icon="mdi-bank" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="6"><v-text-field v-model="form.bank_account_number" :label="$t('bank_account_number')" prepend-inner-icon="mdi-credit-card-outline" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Legal -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-gavel</v-icon>{{ $t('legal_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="3"><v-text-field v-model="form.capital" :label="$t('capital')" prepend-inner-icon="mdi-cash-multiple" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.ice" :label="$t('ice')" prepend-inner-icon="mdi-card-account-details-outline" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.rc" :label="$t('rc')" prepend-inner-icon="mdi-file-certificate-outline" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.cnss" :label="$t('cnss')" prepend-inner-icon="mdi-shield-check-outline" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.tva_number" :label="$t('tva_number')" prepend-inner-icon="mdi-percent-outline" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Logos -->
        <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-image</v-icon>{{ $t('company_logos') }}</div>
        <v-row dense class="justify-center">
          <!-- Logo Invoice -->
          <v-col cols="12" md="4" class="d-flex flex-column align-center text-center">
            <div class="logo-label">{{ $t('logo_invoice') }}</div>
            <div
              class="logo-dropzone"
              :class="{ 'logo-dropzone-dragover': dragOver.logo_invoice, 'logo-dropzone-has-image': logoInvoiceUrl }"
              @click="triggerFileInput('logo_invoice')"
              @dragover.prevent="dragOver.logo_invoice = true"
              @dragleave.prevent="dragOver.logo_invoice = false"
              @drop.prevent="onDrop($event, 'logo_invoice')"
            >
              <!-- Loading spinner -->
              <div v-if="uploading.logo_invoice" class="logo-dropzone-loading">
                <v-progress-circular indeterminate color="#546E7A" size="32" width="3"></v-progress-circular>
              </div>
              <!-- Image preview -->
              <template v-else-if="logoInvoiceUrl">
                <img :src="logoInvoiceUrl" class="logo-preview-img" alt="Logo Invoice" />
                <div class="logo-overlay">
                  <v-btn icon size="x-small" variant="flat" color="error" @click.stop="removeLogo('logo_invoice')">
                    <v-icon size="16">mdi-close</v-icon>
                  </v-btn>
                </div>
              </template>
              <!-- Placeholder -->
              <template v-else>
                <v-icon size="44" class="logo-placeholder-icon">mdi-image-off-outline</v-icon>
                <div class="logo-placeholder-text">{{ $t('drag_drop_or_click') }}</div>
              </template>
              <input type="file" ref="fileInput_logo_invoice" accept="image/*" style="display:none" @change="onFileSelected($event, 'logo_invoice')" />
            </div>
            <div class="d-flex align-center justify-center ga-2 mt-2 w-100" style="max-width: 240px;">
              <v-btn
                size="small"
                variant="outlined"
                rounded
                color="#546E7A"
                prepend-icon="mdi-upload"
                class="text-none flex-grow-1"
                @click="triggerFileInput('logo_invoice')"
              >
                {{ $t('upload') }}
              </v-btn>
              <v-btn
                v-if="logoInvoiceUrl"
                size="small"
                variant="tonal"
                rounded
                color="error"
                icon="mdi-delete-outline"
                @click.stop="removeLogo('logo_invoice')"
                :title="$t('remove')"
              ></v-btn>
            </div>
            <div class="logo-helper-hint mt-2 text-center" style="max-width: 240px;">
              {{ $t('logo_invoice_hint') }}
            </div>
          </v-col>

          <!-- Logo Invoice Body -->
          <v-col cols="12" md="4" class="d-flex flex-column align-center text-center">
            <div class="logo-label">{{ $t('logo_invoice_body') }}</div>
            <div
              class="logo-dropzone"
              :class="{ 'logo-dropzone-dragover': dragOver.logo_invoice_body, 'logo-dropzone-has-image': logoInvoiceBodyUrl }"
              @click="triggerFileInput('logo_invoice_body')"
              @dragover.prevent="dragOver.logo_invoice_body = true"
              @dragleave.prevent="dragOver.logo_invoice_body = false"
              @drop.prevent="onDrop($event, 'logo_invoice_body')"
            >
              <div v-if="uploading.logo_invoice_body" class="logo-dropzone-loading">
                <v-progress-circular indeterminate color="#546E7A" size="32" width="3"></v-progress-circular>
              </div>
              <template v-else-if="logoInvoiceBodyUrl">
                <img :src="logoInvoiceBodyUrl" class="logo-preview-img" alt="Logo Invoice Body" />
                <div class="logo-overlay">
                  <v-btn icon size="x-small" variant="flat" color="error" @click.stop="removeLogo('logo_invoice_body')">
                    <v-icon size="16">mdi-close</v-icon>
                  </v-btn>
                </div>
              </template>
              <template v-else>
                <v-icon size="44" class="logo-placeholder-icon">mdi-image-off-outline</v-icon>
                <div class="logo-placeholder-text">{{ $t('drag_drop_or_click') }}</div>
              </template>
              <input type="file" ref="fileInput_logo_invoice_body" accept="image/*" style="display:none" @change="onFileSelected($event, 'logo_invoice_body')" />
            </div>
            <div class="d-flex align-center justify-center ga-2 mt-2 w-100" style="max-width: 240px;">
              <v-btn
                size="small"
                variant="outlined"
                rounded
                color="#546E7A"
                prepend-icon="mdi-upload"
                class="text-none flex-grow-1"
                @click="triggerFileInput('logo_invoice_body')"
              >
                {{ $t('upload') }}
              </v-btn>
              <v-btn
                v-if="logoInvoiceBodyUrl"
                size="small"
                variant="tonal"
                rounded
                color="error"
                icon="mdi-delete-outline"
                @click.stop="removeLogo('logo_invoice_body')"
                :title="$t('remove')"
              ></v-btn>
            </div>
            <div class="logo-helper-hint mt-2 text-center" style="max-width: 240px;">
              {{ $t('logo_invoice_body_hint') }}
            </div>
          </v-col>

          <!-- Invoice Header & Footer Image -->
          <v-col cols="12" md="4" class="d-flex flex-column align-center text-center">
            <div class="logo-label">{{ $t('invoice_header_image') }}</div>
            <div
              class="logo-dropzone"
              :class="{ 'logo-dropzone-dragover': dragOver.invoice_header_image, 'logo-dropzone-has-image': invoiceHeaderImageUrl }"
              @click="triggerFileInput('invoice_header_image')"
              @dragover.prevent="dragOver.invoice_header_image = true"
              @dragleave.prevent="dragOver.invoice_header_image = false"
              @drop.prevent="onDrop($event, 'invoice_header_image')"
            >
              <div v-if="uploading.invoice_header_image" class="logo-dropzone-loading">
                <v-progress-circular indeterminate color="#546E7A" size="32" width="3"></v-progress-circular>
              </div>
              <template v-else-if="invoiceHeaderImageUrl">
                <div class="invoice-header-img-preview-wrap">
                  <img :src="invoiceHeaderImageUrl" class="logo-preview-img logo-preview-header-img" alt="Invoice Header Image" />
                </div>
                <div class="logo-overlay">
                  <v-btn icon size="x-small" variant="flat" color="error" @click.stop="removeLogo('invoice_header_image')">
                    <v-icon size="16">mdi-close</v-icon>
                  </v-btn>
                </div>
              </template>
              <template v-else>
                <v-icon size="44" class="logo-placeholder-icon">mdi-image-plus-outline</v-icon>
                <div class="logo-placeholder-text">{{ $t('drag_drop_or_click_header_image') }}</div>
              </template>
              <input type="file" ref="fileInput_invoice_header_image" accept="image/*" style="display:none" @change="onFileSelected($event, 'invoice_header_image')" />
            </div>
            <div class="d-flex align-center justify-center ga-2 mt-2 w-100" style="max-width: 240px;">
              <v-btn
                size="small"
                variant="outlined"
                rounded
                color="#546E7A"
                prepend-icon="mdi-upload"
                class="text-none flex-grow-1"
                @click="triggerFileInput('invoice_header_image')"
              >
                {{ $t('choose_header_image') }}
              </v-btn>
              <v-btn
                v-if="invoiceHeaderImageUrl"
                size="small"
                variant="tonal"
                rounded
                color="error"
                icon="mdi-delete-outline"
                @click.stop="removeLogo('invoice_header_image')"
                :title="$t('remove')"
              ></v-btn>
            </div>
            <div class="logo-helper-hint mt-2 text-center" style="max-width: 240px;">
              {{ $t('invoice_header_image_hint') }}
            </div>
          </v-col>
        </v-row>

        <div class="d-flex justify-end mt-6"><v-btn color="#546E7A" rounded prepend-icon="mdi-content-save" :loading="saving" @click="save">{{ $t('save') }}</v-btn></div>
      </v-form>
    </v-card-text>

    <!-- Summary Snackbar (Rule 5: Countdown style) -->
    <v-snackbar
      v-model="snack.show"
      :color="snack.color"
      location="top right"
      :timeout="snack.timeout"
      elevation="4"
      class="summary-snackbar"
    >
      <div class="d-flex align-center ga-2">
        <v-icon :icon="snack.icon" size="20" color="white" />
        <span class="font-weight-medium text-white">{{ snack.msg }}</span>
      </div>
      <template #actions>
        <v-btn icon="mdi-close" variant="text" size="small" color="white" @click="closeSnackbar" />
      </template>
      <v-progress-linear
        :model-value="snack.progress"
        color="white"
        height="3"
        absolute
        location="bottom"
      />
    </v-snackbar>
  </v-card>
</template>

<script>
import axios from 'axios';
import { useCompanySettings } from '@/stores/CompanySettings';

export default {
  name: 'CompanySettingsPage',
  data() {
    return {
      form: { company_name:'',director_name:'',director_gender:'male',company_origin:'',company_address:'',swift_code:'',phone_number:'',fax_number:'',email:'',bank_name:'',bank_account_number:'',capital:'',ice:'',rc:'',cnss:'',tva_number:'' },
      logoInvoiceUrl: '', logoInvoiceBodyUrl: '', invoiceHeaderImageUrl: '',
      saving: false,
      snack: { show: false, msg: '', color: 'success', icon: 'mdi-check-circle', timeout: 4000, progress: 100 },
      snackTimer: null,
      genderOptions: [{ title: this.$t('male'), value: 'male' }, { title: this.$t('female'), value: 'female' }],
      csStore: useCompanySettings(),
      dragOver: { logo_invoice: false, logo_invoice_body: false, invoice_header_image: false },
      uploading: { logo_invoice: false, logo_invoice_body: false, invoice_header_image: false },
    };
  },
  mounted() { this.loadFromStore(); },
  beforeUnmount() { if (this.snackTimer) clearInterval(this.snackTimer); },
  methods: {
    showSnackbar(msg, color = 'success', icon = 'mdi-check-circle') {
      if (this.snackTimer) clearInterval(this.snackTimer);
      this.snack.msg = msg;
      this.snack.color = color;
      this.snack.icon = icon;
      this.snack.progress = 100;
      this.snack.show = true;
      const stepMs = 50;
      const decrement = 100 / (this.snack.timeout / stepMs);
      this.snackTimer = setInterval(() => {
        this.snack.progress = Math.max(0, this.snack.progress - decrement);
        if (this.snack.progress <= 0) {
          clearInterval(this.snackTimer);
          this.snack.show = false;
        }
      }, stepMs);
    },
    closeSnackbar() {
      if (this.snackTimer) clearInterval(this.snackTimer);
      this.snack.show = false;
    },
    /**
     * Load form from cached Pinia store (populated at auth/page-load).
     * Falls back to API if the store is empty.
     */
    loadFromStore() {
      const s = this.csStore.getSettings;
      if (s && Object.keys(s).length > 0) {
        this.populateForm(s);
      } else {
        this.fetchSettings();
      }
    },
    populateForm(s) {
      Object.keys(this.form).forEach(k => { if (s[k] !== undefined) this.form[k] = s[k] || ''; });
      this.logoInvoiceUrl = s.logo_invoice_url || '';
      this.logoInvoiceBodyUrl = s.logo_invoice_body_url || '';
      this.invoiceHeaderImageUrl = s.invoice_header_image_url || '';
    },
    async fetchSettings() {
      try {
        const r = await axios.get('/api/company-settings');
        const s = r.data.settings || {};
        this.populateForm(s);
        // Update the Pinia store cache
        this.csStore.setSettings(s);
      } catch(e) { console.error(e); }
    },
    async save() {
      this.saving = true;
      try {
        const r = await axios.put('/api/company-settings', this.form);
        const updatedSettings = r.data.settings || {};
        // Update local Pinia store cache
        this.csStore.setSettings(updatedSettings);
        this.populateForm(updatedSettings);
        this.showSnackbar(this.$t('settings_saved'), 'success', 'mdi-check-circle');
      } catch(e) {
        this.showSnackbar(this.$t('error_occurred'), 'error', 'mdi-alert-circle');
      }
      this.saving = false;
    },

    // ── Logo upload helpers ──
    triggerFileInput(type) {
      const ref = this.$refs[`fileInput_${type}`];
      if (ref) ref.click();
    },
    onFileSelected(event, type) {
      const file = event.target.files[0];
      if (file) this.uploadLogo(type, file);
      // Reset input so re-selecting the same file triggers change
      event.target.value = '';
    },
    onDrop(event, type) {
      this.dragOver[type] = false;
      const file = event.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        this.uploadLogo(type, file);
      }
    },
    async uploadLogo(type, file) {
      this.uploading[type] = true;
      const fd = new FormData();
      fd.append('logo', file);
      fd.append('logo_type', type);
      try {
        const r = await axios.post('/api/company-settings/upload-logo', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        if (type === 'logo_invoice') this.logoInvoiceUrl = r.data.url;
        else if (type === 'logo_invoice_body') this.logoInvoiceBodyUrl = r.data.url;
        else if (type === 'invoice_header_image') this.invoiceHeaderImageUrl = r.data.url;
        // Update the Pinia store with the new logo URL
        this.csStore.setSettings({ [`${type}_url`]: r.data.url, [`${type}_path`]: r.data.path });
        this.showSnackbar(
          type === 'invoice_header_image' ? this.$t('invoice_header_image_uploaded') : this.$t('logo_uploaded'),
          'success',
          'mdi-check-circle'
        );
      } catch(e) {
        this.showSnackbar(this.$t('error_occurred'), 'error', 'mdi-alert-circle');
      }
      this.uploading[type] = false;
    },
    async removeLogo(type) {
      try {
        await axios.delete(`/api/company-settings/logo/${type}`);
        if (type === 'logo_invoice') this.logoInvoiceUrl = '';
        else if (type === 'logo_invoice_body') this.logoInvoiceBodyUrl = '';
        else if (type === 'invoice_header_image') this.invoiceHeaderImageUrl = '';
        this.csStore.setSettings({ [`${type}_url`]: '', [`${type}_path`]: '' });
        this.showSnackbar(this.$t('logo_removed'), 'success', 'mdi-check-circle');
      } catch(e) {
        this.showSnackbar(this.$t('error_occurred'), 'error', 'mdi-alert-circle');
      }
    },
  },
};
</script>

<style scoped>
.cs-card{border-radius:20px!important;overflow:visible;font-family:'Inter',sans-serif}
.cs-hero{background:linear-gradient(135deg,#37474F 0%,#546E7A 40%,#78909C 100%);border-radius:inherit;border-bottom-left-radius:0;border-bottom-right-radius:0;height:100px}
.cs-hero-inner{position:relative;z-index:1;padding:22px 26px;color:#fff}
.cs-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.15)}
.cs-hero-title{font-size:19px;font-weight:800;letter-spacing:.3px}

/* ── Logo Dropzone ── */
.logo-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
  color: #546E7A;
  margin-bottom: 8px;
  text-align: center;
  min-height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-dropzone {
  position: relative;
  width: 100%;
  max-width: 240px;
  height: 160px;
  border: 2px dashed rgba(84, 110, 122, 0.5);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.25s ease;
  background: rgba(84, 110, 122, 0.03);
  overflow: hidden;
  box-sizing: border-box;
  margin: 0 auto;
}

.logo-dropzone:hover {
  border-color: rgba(84, 110, 122, 0.8);
  background: rgba(84, 110, 122, 0.06);
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(84, 110, 122, 0.12);
}

.logo-dropzone-dragover {
  border-color: #546E7A !important;
  background: rgba(84, 110, 122, 0.1) !important;
  box-shadow: 0 0 0 4px rgba(84, 110, 122, 0.15) !important;
  transform: scale(1.02);
}

.logo-dropzone-has-image {
  border-style: solid;
  border-color: rgba(84, 110, 122, 0.2);
}

.logo-dropzone-has-image:hover {
  border-color: rgba(84, 110, 122, 0.4);
}

/* Placeholder icon + text */
.logo-placeholder-icon {
  color: rgba(84, 110, 122, 0.25);
  transition: color 0.2s ease;
}
.logo-dropzone:hover .logo-placeholder-icon {
  color: rgba(84, 110, 122, 0.45);
}
.logo-placeholder-text {
  font-size: 11px;
  color: rgba(84, 110, 122, 0.45);
  margin-top: 6px;
  text-align: center;
  line-height: 1.3;
  padding: 0 12px;
  font-weight: 500;
}
.logo-dropzone:hover .logo-placeholder-text {
  color: rgba(84, 110, 122, 0.7);
}

/* Image preview */
.logo-preview-img {
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  padding: 10px;
  transition: opacity 0.2s ease;
}

/* Header image preview container styled with deep invoice theme contrast */
.invoice-header-img-preview-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #01579B 0%, #006064 100%);
  border-radius: 12px;
  padding: 8px 12px;
}

.logo-preview-header-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 0;
}

.logo-helper-hint {
  font-size: 11px;
  color: rgba(84, 110, 122, 0.75);
  line-height: 1.35;
  text-align: center;
  min-height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Hover overlay with remove button */
.logo-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.25s ease;
  border-radius: 14px;
}
.logo-dropzone:hover .logo-overlay {
  opacity: 1;
}

/* Loading spinner */
.logo-dropzone-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}
</style>

