<template>
  <div>
    <v-card class="ma-0 pa-0">
      <v-card-title class="d-flex justify-space-between align-center">
        <v-row>
        <v-col>
          <div class="text-h6">{{$t('progress_chart_for')}}<strong>{{ getSupplierName }}</strong></div>
          <div class="text-subtitle-2" v-html="$t('by_supplier_invoice_list_description',{supplier_name:getSupplierName})"></div>
        </v-col>
        <v-col>
          <v-row no-gutters class="mt-3">
            <v-col cols="6">
              <v-select
                  dense
                  color="info"
                  class="text-info"
                  density="compact"
                  variant="outlined"
                  rounded
                  item-value="value"
                  item-title="text"
                  hide-details
                  :items="metrices"
                  v-model="selectMetrice"
                  :label="$t('select_metrice')"
                  @update:model-value="onMetriceChange"
                  return-object
              />
            </v-col>
            <v-col cols="6">
              <v-select
                  dense
                  color="info"
                  class="text-info"
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  :items="yearOptions"
                  v-model="supplier_invoice.selectedYear"
                  :label="$t('year')"
                  @update:model-value="onYearChange"
              />
            </v-col>
          </v-row>
          <v-row no-gutters class="mt-3">
            <v-col >
              <v-select
                  dense
                  color="info"
                  class="text-info"
                  density="compact"
                  variant="outlined"
                  rounded
                  hide-details
                  :items="main.getSuppliers"
                  item-value="id"
                  item-title="name"
                  v-model="supplier_invoice.selected_supplier_id"
                  :label="$t('suppliers')"
                  @update:model-value="onYearChange"
              />
            </v-col>
          </v-row>
        </v-col>
        </v-row>
      </v-card-title>

      <v-card-text>
        <apexchart
          type="bar"
          height="380"
          :options="chartOptions"
          :series="series"
        />
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import VueApexCharts from 'vue3-apexcharts';
import { useMain } from '@/stores/Main';
import { useSupplierInvoice } from '@/stores/SupplierInvoice';
import { _ } from 'core-js';
export default {
  name: 'SupplierInvoicesMonthlyChart',
  components: {
    apexchart: VueApexCharts,
  },
  data() {
    return {
      months: [],
      series: [],
      chartReady: false,
      selectMetrice: null,
      formatter_metrice: {},
      previous_year: null,
      current_year: null
    };
  },
  mounted() {
    this.selectMetrice = this.metrices[0]
    this.supplier_invoice.selected_supplier_id = _.first(this.main.getSuppliers).id;
    this.fetchData(this.supplier_invoice.selectedYear);
  },
  computed: {
    main: () => useMain(),
    supplier_invoice: () => useSupplierInvoice(),
    length: function () {
      return new Date().getFullYear() - 2019;
    },
    getSupplierName: function () {
      if (this.supplier_invoice.selected_supplier_id == null) return this.$t("n/a");
      return this.main.getSuppliers.find(s => s.id == this.supplier_invoice.selected_supplier_id).name;
    },
    yearOptions: function () {
      return [...Array(this.length).keys()].map(i => new Date().getFullYear() - this.length + 1 + i);
    },
    metrices: function () {
      return [
        {
          text: this.$t('price'),
          value:'total_price'
        },
        {
          text: this.$t('weight'),
          value:'total_weight'
        }
      ]
    },
    chartOptions: function () {
      return {
        chart: { type: 'line',stacked: false, toolbar: { show: false }, animations: { enabled: true } , background : "transparent" },
        theme: {
            mode: this.main.mode,
        },
        colors:["#2196F3","#00E396",'#FEB019'],
        plotOptions: {
          bar: { horizontal: false, columnWidth: '54%', borderRadius: 6 , borderRadiusApplication: 'end' }
        },
        dataLabels: { enabled: false },
        stroke: { width: [0, 0, 3], curve: 'smooth' },
        xaxis: {categories: this.months,  labels: { rotate: -15, style: { fontSize: '12px' } } },
        yaxis: [
          {
            seriesName:this.current_year,
            title: { text: `${this.formatter_metrice.text} (${this.formatter_metrice.formatter})` },
            labels: {
              formatter:  (val) => { return Number(val).toLocaleString() + ` ${this.formatter_metrice.formatter}`; }
            }
          },
          {
            seriesName: this.previous_year,
            show: false,
            title: { text: `${this.formatter_metrice.text} (${this.formatter_metrice.formatter})` },
            labels: {
              formatter:  (val) => { return Number(val).toLocaleString() + ` ${this.formatter_metrice.formatter}`; }
            }
          },
          {
            seriesName:this.$t('growth'),
            opposite: true,
            title: { text: this.$t('growth') },
            labels: {
              formatter: function (val) { return (val === null ? '-' : Number(val).toLocaleString() + ' %'); }
            }
          }
        ],
        tooltip: {
          shared: true,
          intersect: false,
          y: {
            formatter:  (val, { seriesIndex }) => {
              if (seriesIndex === 2) {
                return (val === null || val === undefined) ? '-' : Number(val).toLocaleString() + ' %';
              } else {
                return Number(val).toLocaleString() + ` ${this.formatter_metrice.formatter}`;
              }
            }
          }
        },
        legend: { position: 'top', horizontalAlign: 'right' },
        fill: { opacity: 1 },
        markers: { size: 4 }
      }
    },
    getMonthsByLang : function() {
      // Set the moment locale
      let format = 'MMM';
      moment.locale(this.main.lang.short);

      // Use Lodash _.range (similar to _.times) to iterate from 0 to 11 (months)
      const months = _.range(12).map(monthIndex => {
        // Create a moment object for the specific month index and format it
        return moment().month(monthIndex).format(format);
      });

      return months;
    }
  },
  methods: {
    async fetchData() {
      try {
        this.chartReady = false;
        const response = await axios.get('/api/supplier_invoice/supplier-invoices-monthly-supplier', {
          params: {
            year: this.supplier_invoice.selectedYear,
            metrice: this.selectMetrice.value,
            supplier_id: this.supplier_invoice.selected_supplier_id
          },
        });
        this.formatter_metrice = this.selectMetrice?.value == "total_price" ?
        { text: this.$t("total_price"), formatter: this.main.currency } :
        { text: this.$t("total_weight"), formatter: "Kg" };
        const payload = response.data;
        const months = this.getMonthsByLang
        // ensure numeric arrays
        const current = _.map(payload.currentYear || [], v => Number(v || 0));
        const previous = _.map(payload.previousYear || [], v => Number(v || 0));
        this.current_year = payload.years.current;
        this.previous_year = payload.years.previous;
        // normalize lengths
        while (current.length < 12) current.push(0);
        while (previous.length < 12) previous.push(0);

        // compute growth client-side
        const growth = [];
        for (let i = 0; i < months.length; i++) {
          const cur = Number(current[i] || 0);
          const prev = Number(previous[i] || 0);
          if (prev === 0) {
            growth.push(null); // keep null to show gap / avoid infinite %
          } else {
            const raw = ((cur - prev) / prev) * 100;
            // round to 2 decimals
            const rounded = Math.round((raw + Number.EPSILON) * 100) / 100;
            growth.push(rounded);
          }
        }

        this.months = months;
        this.series = [
          { name: `${payload.years.current}`, type: 'column', data: current },
          { name: `${payload.years.previous}`, type: 'column', data: previous },
          { name: this.$t("growth"), type: 'line', data: growth },
        ];

        this.chartReady = true;
      } catch (error) {
        console.error('Failed to load chart data', error);
        this.chartReady = true;
        this.months = this._generateMonthLabels(year);
        this.series = [
          { name: this.current_year, type: 'column', data: Array(12).fill(null) },
          { name: this.previous_year, type: 'column', data: Array(12).fill(null) },
          { name: this.$t("growth"), type: 'line', data: Array(12).fill(null) },
        ];
      }
    },
    onYearChange(newYear) {
      this.fetchData();
    },
    onMetriceChange(newMetrice) {
      this.fetchData();
    },
    _generateMonthLabels(year) {
      // fallback if API didn't deliver labels
      const labels = [];
      for (let m = 0; m < 12; m++) {
        labels.push(moment().month(m).format('MMM'));
      }
      return labels;
    },
  },
  
};
</script>

<style scoped>
/* small visual polish */
.v-card {
  margin: 16px;
}
</style>
