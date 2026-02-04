<template>
  <div>
    <v-card class="ma-0 pa-0">
      <v-card-title class="d-flex justify-space-between align-center">
        <v-row>
        <v-col>
          <div class="text-h6">{{$t("supplier_invoice_list_overview")}}</div>
          <div class="text-subtitle-2">{{$t("supplier_invoice_list_description")}}</div>
        </v-col>
          <v-col cols="3">
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
          <v-col cols="3">
            <v-select
                dense
                color="info"
                class="text-info"
                density="compact"
                variant="outlined"
                rounded
                hide-details
                :items="yearOptions"
                v-model="selectedYear"
                :label="$t('year')"
                @update:model-value="onYearChange"
            />
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
export default {
  name: 'SupplierInvoicesMonthlyChart',
  components: {
    apexchart: VueApexCharts,
  },
  data() {
    const currentYear = new Date().getFullYear();
    const yearOptions = [...Array(6).keys()].map(i => currentYear-5 + i);
    return {
      selectedYear: currentYear,
      yearOptions: yearOptions,
      months: [],
      series: [],
      chartReady: false,
      selectMetrice: null,
      formatter_metrice: null,
    };
  },
  mounted() {
    this.selectMetrice = this.metrices[0]
    this.fetchData(this.selectedYear);
  },
  computed: {
    main: () => useMain(),
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
        chart: {
          background: 'transparent',
          type: 'bar',
          toolbar: { show: false },
          animations: { enabled: true },
        },
        theme: {
            mode: this.main.mode,
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '55%',
            borderRadius: 5,
            borderRadiusApplication: 'end'
          },
        },
        dataLabels: {
          enabled: false,
        },
        stroke: {
          show: true,
          width: 1,
        },
        xaxis: {
          categories: this.months,
          labels: {
            rotate: -15,
            style: { fontSize: '12px' },
          },
        },
        yaxis: {
          title: { text: this.formatter_metrice },
          labels: {
            formatter: (val) => {
              // format with thousand separators
              return Number(val).toLocaleString() + ` ${this.formatter_metrice.formatter}`;
            },
          },
        },
        tooltip: {
          y: {
            formatter:  (val) => {
              return Number(val).toLocaleString() + ` ${this.formatter_metrice.formatter}`;
            },
          },
        },
        legend: {
          position: 'top',
          horizontalAlign: 'right',
        },
        fill: {
          opacity: 1,
        },
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
        const response = await axios.get('/api/supplier_invoice/supplier-invoices-monthly', {
          params: { year: this.selectedYear , metrice : this.selectMetrice.value },
        });
        this.formatter_metrice = this.selectMetrice?.value == "total_price" ?
        { text: this.$t("total_price_(DH)", { currency: this.main.currency }), formatter: this.main.currency } :
        { text: this.$t("total_weight"), formatter: "Kg" };
        const payload = response.data;
        // ensure arrays are length 12
        const months = this.getMonthsByLang
        const current = _.map(payload.currentYear || [], (v) => Number(v || 0));
        const previous = _.map(payload.previousYear || [], (v) => Number(v || 0));

        // Ensure length 12
        const cur = _.concat(current).slice(0, 12);
        const prev = _.concat(previous).slice(0, 12);
        while (cur.length < 12) cur.push(0);
        while (prev.length < 12) prev.push(0);

        this.months = months;
        this.series = [
          { name: `${payload.years.current}`, data: cur },
          { name: `${payload.years.previous}`, data: prev },
        ];
        this.chartReady = true;
      } catch (error) {
        console.error('Failed to load chart data', error);
        this.chartReady = true; // avoid infinite loading; show empty chart state
        this.months = this._generateMonthLabels(year);
        this.series = [
          { name: `${year}`, data: Array(12).fill(0) },
          { name: `${year - 1}`, data: Array(12).fill(0) },
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
