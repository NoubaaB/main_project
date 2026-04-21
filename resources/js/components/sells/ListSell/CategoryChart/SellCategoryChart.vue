<template>
  <v-card class="mini-card ma-1 rounded-xl"
    elevation="6"
    :style="cardStyle"
    @click="showDialog"
    :loading="sell.loading"
  >
    <template v-slot:loader="{ isActive }">
      <v-progress-linear v-if="isActive" color="deep-purple" indeterminate></v-progress-linear>
    </template>
    <div class="card-inner d-flex">
      <!-- LEFT: numbers -->
      <div style="flex:1; min-width:0;">
        <div class="kpi-master">
          <div class="kpi-row d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="green lighten-1">mdi-cash</v-icon>
              <div class="kpi-label">{{ $t("price") }}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(data.total_price) }} <small>{{ main.currency }}</small></div>
          </div>

          <div class="kpi-row d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="info lighten-1">mdi-weight-kilogram</v-icon>
              <div class="kpi-label">{{ $t("weight") }}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(data.total_weight) }} <small>Kg</small></div>
          </div>

          <div class="kpi-row d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="purple lighten-1">mdi-package-variant-closed</v-icon>
              <div class="kpi-label">{{ $t("boxes") }}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumberNonFloat(data.total_package_count) }} <small>Un</small></div>
          </div>

          <div class="kpi-row d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="orange lighten-1">mdi-percent</v-icon>
              <div class="kpi-label">{{ $t("avg_price") }}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(data.unit_price) }} <small>{{ main.currency }}/Kg</small></div>
          </div>
        </div>
        <v-divider color="deep-purple"></v-divider>
        <div class="mt-1">
          <h4 class="d-inline">{{ categoryName }}</h4>
          <sub>
            <small>
              ({{ data.sells_count }} {{ $t("entries_short") }})
            </small>
          </sub>
        </div>
      </div>

      <!-- RIGHT: small sparkline chart -->
      <div style="width: 130px; display:flex; align-items:center; justify-content:center;">
        <ApexCharts
          type="area"
          :options="chartOptions"
          :series="chartSeries"
          :height="chartHeight"
          :width="130"
        />
      </div>
    </div>
  </v-card>
  <v-dialog v-model="dialog" max-width="1300" max-height="700">
    <SellCategoryDetailChart @closeDialog="closeDialog" :summary="data" :details="data.grouped_details" :categoryName="categoryName"/>
  </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useSell } from '@/stores/Sell';
import { useSellCategory } from '@/stores/SellCategory';
import moment from 'moment';
import ApexCharts from 'vue3-apexcharts';
import SellCategoryDetailChart from './SellCategoryDetailChart.vue';

export default {
  components: { ApexCharts, SellCategoryDetailChart },
  props: {
    data: Object,
  },
  data () {
    return {
      chartHeight: 90,
      dialog: false,
    }
  },
  computed: {
    main: () => useMain(),
    sell: () => useSell(),
    sellCategory: () => useSellCategory(),
    categoryName() {
      const cat = this.sellCategory.getList.find(c => c.id === this.data.sell_category_id);
      return cat ? cat.name : this.$t('n/a');
    },
    seriesData() {
      return this.data.grouped_details || [];
    },
    chartSeries() {
      return [
        {
          name: this.$t("price"),
          type: 'column',
          scale: this.main.currency,
          data: this.seriesData.map(d => Number(d.total_price || 0))
        },
        {
          name: this.$t('weight'),
          type: 'area',
          scale: 'Kg',
          data: this.seriesData.map(d => Number(d.total_weight || 0))
        },
        {
          name: this.$t('avg_price'),
          type: 'line',
          scale: `${this.main.currency}/Kg`,
          data: this.seriesData.map(d => Number(d.unit_price || 0))
        }
      ];
    },
    chartOptions() {
      return {
        chart: {
          type: 'area',
          background: 'transparent',
          sparkline: { enabled: true },
          toolbar: { show: false },
          animations: { enabled: true, easing: 'easeout', speed: 400 }
        },
        stroke: {
          width: [0, 2, 3],
          curve: 'smooth',
          dashArray: [0, 0, 5]
        },
        theme: {
          mode: this.main.mode,
        },
        labels: this.seriesData.map(d => d.date),
        fill: {
          type: 'line',
          gradient: {
            shadeIntensity: 20,
            inverseColors: false,
            opacityFrom: 1,
            opacityTo: 1,
            stops: [0, 80, 100]
          }
        },
        colors: ['#00e396', '#2196F3', '#feb019'],
        markers: { size: 0 },
        grid: { show: false },
        xaxis: {
          categories: this.seriesData.map(d => d.date),
          labels: { show: false },
          axisBorder: { show: false },
          axisTicks: { show: false }
        },
        yaxis: [
          { show: false },
          { show: false, opposite: true },
          { show: false },
        ],
        legend: { show: false },
        tooltip: {
          enabled: true,
          theme: this.main.mode,
          x: {
            show: true, formatter: (val) => this.main.formateDate(this.seriesData[val-1]?.date)
          },
          y: {
            formatter: (val, { seriesIndex }) => {
              return `${this.main.formatDisplayNumber(val)} <small>${this.chartSeries[seriesIndex].scale}</small>`;
            }
          },
        }
      }
    },
    cardStyle () {
      return {
        width: '32.5%',
        height: '150px',
        padding: '8px',
        'padding-top': '21px',
        overflow: 'hidden'
      }
    }
  },
  methods: {
    closeDialog() {
      this.dialog = false;
    },
    showDialog() {
      this.dialog = true;
    }
  }
}
</script>

<style scoped>
.mini-card {
  display: flex;
  align-items: stretch;
  cursor: pointer;
}
.card-inner {
  width: 100%;
  height: 100%;
  padding: 4px 6px;
  box-sizing: border-box;
}
.kpi-row {
  font-size: 13px;
  line-height: 1;
  margin-bottom: 6px;
}
.kpi-row .kpi-label {
  font-size: 13px;
  margin-left: 4px;
}
.kpi-value {
  font-weight: 700;
  font-size: 13px;
  min-width: 60px;
  text-align: right;
}
.kpi-master {
  height: 80% !important;
}
.v-icon {
  font-size: 14px;
}
</style>
