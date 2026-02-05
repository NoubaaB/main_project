<template>
  <div class="sidebar-cards">
    <!-- Each card is small, stacked, and fits narrow sidebar -->
    <v-card class="sidebar-card" elevation="1" outlined>
      <div class="card-header">
        <div class="card-title">{{$t("top_supplier")}}</div>
        <div class="card-sub">{{ get_supplier_name }}</div>
      </div>

      <div class="card-body">
        <v-row no-gutters>
            <v-col class="text-left">
                <div class="amount">{{ main.formatDisplayNumber(data.top_supplier.current.price) }} {{main.currency}}</div>
                <div class="growth-row">
                  <v-icon
                    v-if="growth.topSupplierPrice && growth.topSupplierPrice.value !== null"
                    :color="growth.topSupplierPrice.color"
                    class="icon-small"
                  >
                    {{ growth.topSupplierPrice.icon }}
                  </v-icon>
        
                  <div class="growth-text">
                    <span v-if="growth.topSupplierPrice && growth.topSupplierPrice.value !== null">
                      {{ growth.topSupplierPrice.value }}%
                    </span>
                    <v-divider v-else class="dash"></v-divider>
                    <div class="tiny">vs {{ previousLabel }}</div>
                  </div>
                </div>
            </v-col>
            <v-col class="text-right">
                <div class="amount">{{ main.formatDisplayNumber(data.top_supplier.current.weight) }} Kg</div>

        
                <div class="meta-row tiny-muted">
                  {{ $t("previous_weight") }}:
                  <div>
                    {{ main.formatDisplayNumber(data.top_supplier.previous.weight) }} Kg
                  </div>
                </div>
            </v-col>
        </v-row>

      </div>
    </v-card>

    <v-card class="sidebar-card mt-3" elevation="1" outlined>
      <div class="card-header">
        <div class="card-title">{{$t("total_all_invoice")}}</div>
        <div class="card-sub">{{ $t("period") }}: {{ data.period || periodProp }}</div>
      </div>

      <div class="card-body">
        <v-row no-gutters>
            <v-col class="text-left">
                <div class="amount">{{ main.formatDisplayNumber(data.current.price) }} {{main.currency}}</div>
        
                <div class="growth-row">
                  <v-icon
                    v-if="growth.totalPrice && growth.totalPrice.value !== null"
                    :color="growth.totalPrice.color"
                    class="icon-small"
                  >
                    {{ growth.totalPrice.icon }}
                  </v-icon>
        
                  <div class="growth-text">
                    <span v-if="growth.totalPrice && growth.totalPrice.value !== null">
                      {{ growth.totalPrice.value }}%
                    </span>
                    <v-divider v-else class="dash"></v-divider>
                    <div class="tiny">vs {{ previousLabel }}</div>
                  </div>
                </div>
            </v-col>
            <v-col class="text-right">
                <div class="amount">{{ main.formatDisplayNumber(data.current.weight) }} Kg</div>
        
                <div class="meta-row tiny-muted">
                  {{ $t("previous_weight") }}:
                  <div>
                    {{ main.formatDisplayNumber(data.previous.weight) }} Kg
                  </div>
                </div>
            </v-col>
        </v-row>

      </div>
    </v-card>

    <v-card class="sidebar-card mt-3" elevation="1" outlined>
      <div class="card-header">
        <div class="card-title">{{$t("by_invoice_types")}}</div>
        <div class="card-sub">{{ $t("period") }}: {{ data.period || periodProp }}</div>
      </div>

      <div class="card-body">
        <div class="row-split">
            <div class="types">
              <div class="type-row">
                <div class="type-name">
                    <v-chip prepend-icon="mdi-truck" variant="tonal" color="blue">{{$t("local")}}</v-chip>
                    <div class="type-amount">{{ main.formatDisplayNumber(data.types.current.local.price) }} {{main.currency}}</div>
                    <div class="type-amount">{{ main.formatDisplayNumber(data.types.current.local.weight) }} Kg</div>
                    <div class="type-prev tiny-muted">{{$t("previous_short")}}: {{ main.formatDisplayNumber(data.types.previous.local.price) }} {{main.currency}}</div>
                    <div class="type-growth ">
                      <v-icon
                        v-if="growth.local && growth.local.value !== null"
                        :color="growth.local.color"
                        class="icon-small"
                      >
                        {{ growth.local.icon }}
                      </v-icon>
                      <span v-if="growth.local && growth.local.value !== null">{{ growth.local.value }}%</span>
                      <v-divider v-else class="dash"></v-divider>
                </div>
                </div>
              </div>
    
              <div class="type-row mt-2 ">
                <div class="type-name">
                    <v-chip prepend-icon="mdi-truck-delivery" variant="tonal" color="warning">{{$t("export")}}</v-chip>
                    <div class="type-amount">{{ main.formatDisplayNumber(data.types.current.export.price) }} {{main.currency}}</div>
                    <div class="type-amount">{{main.formatDisplayNumber(data.types.current.export.weight) }} Kg</div>
                    <div class="type-prev tiny-muted">{{$t("previous_short")}}: {{ main.formatDisplayNumber(data.types.previous.export.price) }} {{main.currency}}</div>
                    <div class="type-growth">
                      <v-icon
                        v-if="growth.export && growth.export.value !== null"
                        :color="growth.export.color"
                        class="icon-small"
                      >
                        {{ growth.export.icon }}
                      </v-icon>
                      <span v-if="growth.export && growth.export.value !== null">{{ growth.export.value }}%</span>
                      <v-divider v-else class="dash"></v-divider>
                </div>
                </div>
              </div>
            </div>
    
            <div class="donut-area">
              <apexchart
                type="donut"
                height="90"
                :options="donutOptions"
                :series="data.donut.series"
              />
            </div>
        </div>
      </div>
    </v-card>
  </div>
</template>

<script>
import axios from 'axios';
import moment from 'moment';
import VueApexCharts from 'vue3-apexcharts';
import { useSupplierInvoice } from '@/stores/SupplierInvoice';
import { useSupplier } from '@/stores/Supplier';
import { useMain } from '@/stores/Main';
export default {
  components: { apexchart: VueApexCharts },
  data() {
    return {
      data: {
        period: null,
        current: { price: 0, weight: 0 },
        previous: { price: 0, weight: 0 },
        top_supplier: { name: null, current: { price: 0, weight: 0 }, previous: { price: 0, weight: 0 } },
        types: { current: { local: { price: 0, weight: 0 }, export: { price: 0, weight: 0 } },
                 previous: { local: { price: 0, weight: 0 }, export: { price: 0, weight: 0 } } },
        donut: { labels: ['local','export'], series: [0,0] },
      },
      growth: {
        topSupplierPrice: { value: null, icon: '', color: '' },
        totalPrice: { value: null, icon: '', color: '' },
        local: { value: null, icon: '', color: '' },
        export: { value: null, icon: '', color: '' },
      },
      donutOptions: {
        chart: { toolbar: { show: true } },
        labels: ['local','export'],
        colors: ["#2196f3","#feb019"],
        legend: { show: false },
        tooltip: {
          y: {
            formatter: function (val) { return Number(val).toLocaleString() + ' DH'; }
          }
        },
        dataLabels: { enabled: false },
        stroke: { show: true }
      }
    };
  },
  watch: {
    periodProp(newVal) {
      if (newVal) {
        this.reload();
      }
    }
  },
  mounted() {
    this.reload();
  },
  computed: {
    supplier_invoice: () => useSupplierInvoice(),
    supplier: () => useSupplier(),
    main: () => useMain(),
    periodProp: function () {
        return moment(`${this.supplier_invoice.selectedYear}-${moment().format("MM-DD")}`).format('YYYY-MM')    
    },
    previousLabel() {
      if (!this.data.period && !this.periodProp) return '';
      const base = this.data.period || this.periodProp;
      return moment(base, 'YYYY-MM').subtract(1, 'year').format('YYYY-MM');
    },
    get_supplier_name: function(){
        return this.supplier.get_supplier_name_by_id(this.data.top_supplier.name)
    }
  },
  methods: {
    async reload() {
      try {
        const res = await axios.get('/api/supplier_invoice/supplier-invoices-monthly-cards', { params: { period: this.periodProp } });
        this.data = res.data;
        if (!this.data.donut || !Array.isArray(this.data.donut.series)) {
          this.data.donut = { labels: this.data.donut.labels.map(e=>this.$t(e)), series: [0, 0] };
        }
        if (this.data.donut.labels) {
          this.donutOptions = { ...this.donutOptions, labels: this.data.donut.labels.map(e=>this.$t(e)) };
        }
        this.computeGrowthAll();
      } catch (e) {
        // keep defaults if API fails
        console.error('Failed to fetch sidebar cards', e);
      }
    },

    computeGrowthAll() {
      const compute = (cur, prev) => {
        cur = Number(cur || 0);
        prev = Number(prev || 0);
        if (!prev || prev === 0) return { value: null, icon: '', color: '' };
        const raw = ((cur - prev) / prev) * 100;
        const value = Math.round((raw + Number.EPSILON) * 100) / 100;
        if (value > 0) return { value, icon: 'mdi-arrow-up-bold', color: 'green' };
        if (value < 0) return { value: Math.abs(value), icon: 'mdi-arrow-down-bold', color: 'red' };
        return { value: 0, icon: 'mdi-minus', color: 'grey' };
      };

      this.growth.topSupplierPrice = compute(this.data.top_supplier.current.price, this.data.top_supplier.previous.price);
      this.growth.totalPrice = compute(this.data.current.price, this.data.previous.price);
      this.growth.local = compute(this.data.types.current.local.price, this.data.types.previous.local.price);
      this.growth.export = compute(this.data.types.current.export.price, this.data.types.previous.export.price);
    },
  }
};
</script>

<style scoped>
.sidebar-cards { display: flex; flex-direction: column; }

/* compact card */
.sidebar-card {
  max-width: 280px; /* adjust to your sidebar width */
  padding: 10px;
  box-sizing: border-box;
  font-size: 13px;
}

/* header */
.card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.card-title { font-weight:600; font-size:13px; color: #2b2b2b; }
.card-sub { font-size:12px; color:#6b6f76; }

/* body content */
.card-body { padding: 4px 2px 2px 2px; }
.amount { font-size:16px; font-weight:700; margin-bottom:6px; }
.tiny { font-size:11px; color:#6b6f76; }
.tiny-muted { font-size:11px; color:#8a8f97; margin-top:6px; }

.growth-row { display:flex; align-items:center; gap:6px; margin-bottom:6px; }
.icon-small { font-size:16px; line-height:1; }
.growth-text { font-size:13px; color:#2b2b2b; }
.dash { color:#8a8f97; }

/* meta rows */
.meta-row { display:block; margin-top:6px; }
.meta-label { font-size:12px; color:#6b6f76; }
.meta-value { font-size:13px; font-weight:600; }

/* types & donut */
.row-split { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.types { flex:1; }
.donut-area { width:110px; display:flex; align-items:center; justify-content:center; }

/* type rows */
.type-row { align-items:flex-start; justify-content:space-between; gap:8px; }
.type-name { font-size:12px; color:#6b6f76; }
.type-amount { font-size:12px; font-weight:600; text-align:right; }
.type-prev { font-size:11px; color:#8a8f97; text-align:right; }
.type-growth { font-size:12px; color:#2b2b2b; display:flex; align-items:center; gap:6px; }

/* spacing */
.mt-2 { margin-top:6px; }
.mt-3 { margin-top:12px; }
</style>
