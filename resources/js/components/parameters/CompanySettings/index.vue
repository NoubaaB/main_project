<template>
  <v-card class="cs-card" elevation="0">
    <div class="cs-hero"><div class="cs-hero-inner"><div class="d-flex align-center ga-4"><div class="cs-hero-icon"><v-icon size="28" color="white">mdi-domain</v-icon></div><div><div class="cs-hero-title">{{ $t('company_settings') }}</div><div class="text-white text-caption" style="opacity:.7">{{ $t('company_settings_desc') }}</div></div></div></div></div>
    <v-card-text class="pa-6">
      <v-form ref="form" @submit.prevent="save">
        <!-- Section: General -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-office-building</v-icon>{{ $t('general_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="4"><v-text-field v-model="form.company_name" :label="$t('company_name')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="4"><v-text-field v-model="form.director_name" :label="$t('director_name')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-select v-model="form.director_gender" :items="genderOptions" :label="$t('director_gender')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-select></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.company_origin" :label="$t('company_origin')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>
        <v-row dense class="mt-1">
          <v-col cols="12"><v-text-field v-model="form.company_address" :label="$t('company_address')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Contact -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-phone</v-icon>{{ $t('contact_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="3"><v-text-field v-model="form.phone_number" :label="$t('phone_number')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.fax_number" :label="$t('fax_number')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.email" :label="$t('email')" type="email" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.swift_code" :label="$t('swift_code')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Banking -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-bank</v-icon>{{ $t('banking_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="6"><v-text-field v-model="form.bank_name" :label="$t('bank_name')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="6"><v-text-field v-model="form.bank_account_number" :label="$t('bank_account_number')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Legal -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-gavel</v-icon>{{ $t('legal_info') }}</div>
        <v-row dense>
          <v-col cols="12" md="3"><v-text-field v-model="form.capital" :label="$t('capital')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="3"><v-text-field v-model="form.ice" :label="$t('ice')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.rc" :label="$t('rc')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.cnss" :label="$t('cnss')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
          <v-col cols="12" md="2"><v-text-field v-model="form.tva_number" :label="$t('tva_number')" density="compact" variant="outlined" rounded color="#546E7A" hide-details></v-text-field></v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Section: Logos -->
        <div class="text-subtitle-2 font-weight-bold mb-2 d-flex align-center"><v-icon size="18" color="#546E7A" class="mr-1">mdi-image</v-icon>{{ $t('company_logos') }}</div>
        <v-row dense>
          <v-col cols="12" md="4">
            <v-file-input v-model="logoInvoiceFile" :label="$t('logo_invoice')" density="compact" variant="outlined" rounded color="#546E7A" hide-details accept="image/*" prepend-icon="mdi-file-image" @update:model-value="uploadLogo('logo_invoice')"></v-file-input>
            <img v-if="logoInvoiceUrl" :src="logoInvoiceUrl" style="max-height:60px;margin-top:8px;border-radius:8px;border:1px solid #eee" />
          </v-col>
          <v-col cols="12" md="4">
            <v-file-input v-model="logoInvoiceBodyFile" :label="$t('logo_invoice_body')" density="compact" variant="outlined" rounded color="#546E7A" hide-details accept="image/*" prepend-icon="mdi-file-image" @update:model-value="uploadLogo('logo_invoice_body')"></v-file-input>
            <img v-if="logoInvoiceBodyUrl" :src="logoInvoiceBodyUrl" style="max-height:60px;margin-top:8px;border-radius:8px;border:1px solid #eee" />
          </v-col>
          <v-col cols="12" md="4">
            <v-file-input v-model="logoOriginFile" :label="$t('logo_origin')" density="compact" variant="outlined" rounded color="#546E7A" hide-details accept="image/*" prepend-icon="mdi-file-image" @update:model-value="uploadLogo('logo_origin')"></v-file-input>
            <img v-if="logoOriginUrl" :src="logoOriginUrl" style="max-height:60px;margin-top:8px;border-radius:8px;border:1px solid #eee" />
          </v-col>
        </v-row>

        <div class="d-flex justify-end mt-6"><v-btn color="#546E7A" rounded prepend-icon="mdi-content-save" :loading="saving" @click="save">{{ $t('save') }}</v-btn></div>
      </v-form>
    </v-card-text>

    <v-snackbar v-model="snack.show" :timeout="4000" :color="snack.color" location="top right">{{ snack.msg }}<template #actions><v-btn variant="text" size="small" @click="snack.show=false">{{ $t('close') }}</v-btn></template></v-snackbar>
  </v-card>
</template>

<script>
import axios from 'axios';
export default {
  name: 'CompanySettingsPage',
  data() {
    return {
      form: { company_name:'',director_name:'',director_gender:'male',company_origin:'',company_address:'',swift_code:'',phone_number:'',fax_number:'',email:'',bank_name:'',bank_account_number:'',capital:'',ice:'',rc:'',cnss:'',tva_number:'' },
      logoInvoiceFile: null, logoInvoiceBodyFile: null, logoOriginFile: null,
      logoInvoiceUrl: '', logoInvoiceBodyUrl: '', logoOriginUrl: '',
      saving: false,
      snack: { show:false, msg:'', color:'success' },
      genderOptions: [{ title: this.$t('male'), value: 'male' }, { title: this.$t('female'), value: 'female' }],
    };
  },
  mounted() { this.fetchSettings(); },
  methods: {
    async fetchSettings() {
      try {
        const r = await axios.get('/api/company-settings');
        const s = r.data.settings || {};
        Object.keys(this.form).forEach(k => { if (s[k] !== undefined) this.form[k] = s[k] || ''; });
        this.logoInvoiceUrl = s.logo_invoice_url || '';
        this.logoInvoiceBodyUrl = s.logo_invoice_body_url || '';
        this.logoOriginUrl = s.logo_origin_url || '';
      } catch(e) { console.error(e); }
    },
    async save() {
      this.saving = true;
      try {
        await axios.put('/api/company-settings', this.form);
        this.snack = { show:true, msg: this.$t('settings_saved'), color:'success' };
      } catch(e) {
        this.snack = { show:true, msg: this.$t('error_occurred'), color:'error' };
      }
      this.saving = false;
    },
    async uploadLogo(type) {
      let file;
      if (type === 'logo_invoice') file = this.logoInvoiceFile;
      else if (type === 'logo_invoice_body') file = this.logoInvoiceBodyFile;
      else file = this.logoOriginFile;
      if (!file) return;
      const fd = new FormData();
      fd.append('logo', file);
      fd.append('logo_type', type);
      try {
        const r = await axios.post('/api/company-settings/upload-logo', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        if (type === 'logo_invoice') this.logoInvoiceUrl = r.data.url;
        else if (type === 'logo_invoice_body') this.logoInvoiceBodyUrl = r.data.url;
        else this.logoOriginUrl = r.data.url;
        this.snack = { show:true, msg: this.$t('logo_uploaded'), color:'success' };
      } catch(e) {
        this.snack = { show:true, msg: this.$t('error_occurred'), color:'error' };
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
</style>
