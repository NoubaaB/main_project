<template>
  <v-card class="mini-card ma-1 rounded-xl"  
  elevation="6" 
  :style="cardStyle" 
  :disabled="!data"
  :color="!data?`grey-${main.mode}ten-5`:''"
  @click="showDialog"
  :loading="sell.loading"
  >
    <template v-slot:loader="{ isActive }">
      <v-progress-linear v-if="isActive && !!data" color="info" indeterminate></v-progress-linear>
    </template>
    <div class="card-inner d-flex" >
      <!-- LEFT: numbers -->
      <div style="flex:1; min-width:0;">
        <div class="kpi-master">
          <div class="kpi-row d-flex align-center justify-space-between" :class="{'disabled-text': !ability_warehouse_category_operation.allow_price}">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="green lighten-1">mdi-cash</v-icon>
              <div class="kpi-label">{{$t("price")}}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(filterDumpValue(summary.total_price,ability_warehouse_category_operation.allow_price)) }} <small>{{main.currency}}</small></div>
          </div>
  
          <div class="kpi-row d-flex align-center justify-space-between" :class="{'disabled-text': !ability_warehouse_category_operation.allow_weight}">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="info lighten-1">mdi-weight-kilogram</v-icon>
              <div class="kpi-label">{{$t("weight")}}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(filterDumpValue(summary.total_weight,ability_warehouse_category_operation.allow_weight)) }} <small>Kg</small></div>
          </div>
  
          <div class="kpi-row d-flex align-center justify-space-between" :class="{'disabled-text': !ability_warehouse_category_operation.allow_package_type}">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="purple lighten-1">mdi-package-variant-closed</v-icon>
              <div class="kpi-label">{{$t("boxes")}}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumberNonFloat(filterDumpValue(summary.total_package_count,ability_warehouse_category_operation.allow_package_type)) }} <small>Un</small></div>
          </div>
  
          <div class="kpi-row d-flex align-center justify-space-between" :class="{'disabled-text': !(ability_warehouse_category_operation.allow_price && ability_warehouse_category_operation.allow_weight)}">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" color="orange lighten-1">mdi-percent</v-icon>
              <div class="kpi-label">{{$t("avg_price")}}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(filterDumpValue(summary.unit_price,(ability_warehouse_category_operation.allow_price && ability_warehouse_category_operation.allow_weight))) }} <small>{{main.currency}}/Kg</small></div>
          </div>

        </div>
        <v-divider color="info"></v-divider>
        <div class="mt-1">
          <h4 class="d-inline">{{warehouse_category_operation_name}}</h4>
          <sub>
            <small>
              ({{ entries_length }} {{$t("entries_short")}})

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
    <ShowDetailChart @closeDialog="closeDialog" :summary="summary" :details="data.grouped_details"/>
  </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useSell } from '@/stores/Sell';
import moment from 'moment';
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';
import ApexCharts from 'vue3-apexcharts';
import ShowDetailChart from "./ShowDetailChart.vue";

export default {
  components: { ApexCharts , ShowDetailChart},
  props: {
    data: Object,
    id: Number,
    date:Object
  },
  data () {
    return {
      chartHeight: 90,
      summary: {
        id: 1,
        unit_price: 0,
        total_weight: 0.00,
        total_price: 0.00,
        total_package_count: 0,
        stockable_type: 'product'
      },
      dialog:false
    }
  },
  computed: {
    // computed average price for the summary KPI
    main: () => useMain(),
    sell: () => useSell(),
    entries_length: function () {
      return this.sell.countWarehouseCategoryOperationUsage(this.id);
    },
    seriesData: function () {
      let data = [];
      if (this.data) {
        this.summary = this.data;
        data = this.data.grouped_details;
      } else {
        this.generateLastDays().forEach((date, idx) => {
          let rand_1 = Math.random();
          let rand_2 = Math.random();
          data.push({
            id: idx,
            date: date,
            unit_price: (rand_1/rand_2)*2,
            total_price:  rand_1*10*rand_2,
            total_weight: rand_1*10,
            total_package_count: 12,
            stockable_type: 'product'
          });
        })
      }
      return data;
    },
    warehouse_category_operation: () => useWarehouseCategoryOperation(),
    warehouse_category_operation_name: function () {
      return this.warehouse_category_operation.getName(this.id )
    },
    ability_warehouse_category_operation : function(){
      return this.warehouse_category_operation.getList.find(e => e.id == this.id );
    },
    // build series arrays (numbers) and categories (dates)
    chartSeries() {
      let data = [] 
      if(this.ability_warehouse_category_operation.allow_price){
        data.push({
          name: this.$t("price"),
          type: 'column',
          scale: this.main.currency,
          data: this.seriesData.map(d => Number(d.total_price || 0))
        });
      }
      if(this.ability_warehouse_category_operation.allow_weight){
        data.push({
          name: this.$t('weight'),
          type: 'area',
          scale: 'Kg',
          data: this.seriesData.map(d => Number(d.total_weight || 0))
        });
      }
      if(this.ability_warehouse_category_operation.allow_price && this.ability_warehouse_category_operation.allow_weight){
        data.push({
          name: this.$t('avg_price'),
          type: 'line',
          scale: `${this.main.currency}/Kg`,
          data: this.seriesData.map(d => Number(d.unit_price || 0))
        });
      }
      return data;
    },
    chartColors: function () {
      const colors = [];
      if (this.ability_warehouse_category_operation.allow_price) {
          colors.push('#00e396'); // Price - Green
      }
      if (this.ability_warehouse_category_operation.allow_weight) {
          colors.push('#2196F3'); // Weight - info
      }
      if (this.ability_warehouse_category_operation.allow_price) {
          colors.push('#feb019'); // Avg Price - Orange
      }
      return colors;
    },

    chartOptions() {
      // simple sparkline style — hide axes, grid, legend; gradient fills
      return {
        chart: {
          type: 'area',
          background: 'transparent',
          sparkline: { enabled: true },
          toolbar: { show: false },
          animations: { enabled: true, easing: 'easeout', speed: 400 }
        },
        stroke: {
          curve: 'smooth',
          width: [2, 2, 2]
        },
        theme: {
            mode: this.main.mode,
        },
        labels:this.seriesData.map(d=>d.date),
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
        stroke: {
            width: [0, 2, 3],
            curve: 'smooth',
            dashArray: [0, 0, 5]
        },
        colors: this.chartColors,
        markers: { size: 0 },
        grid: { show: false },
        xaxis: {
          categories: this.seriesData.map(d => d.date),
          labels: { show: false },
          axisBorder: { show: false },
          axisTicks: { show: false }
        },
        yaxis: [
                {
                    seriesName: this.$t("weight"),
                    opposite: true,
                    show:false,
                    axisTicks: {
                        show: true,
                    },
                    axisBorder: {
                        show: true,
                        color: '#00e396'
                    },
                    labels: {
                        style: {
                            colors: '#00e396',
                        },
                        formatter: (value) =>{
                            return this.main.formatDisplayNumber(value); // Adds '$' and formats to 2 decimal places
                        }
                    },
                    title: {
                    text: this.$t("total_weight_on_kg"),
                        style: {
                            color: '#00e396',
                        }
                    },
                },
                {
                    seriesName: this.$t("price"),
                    show:false,
                    axisTicks: {
                        show: true,
                    },
                    axisBorder: {
                        show: true,
                        color: '#1c9ff8'
                    },
                    labels: {
                        style: {
                            colors: '#1c9ff8',
                        },
                        formatter: (value) =>{
                            return this.main.formatDisplayNumber(value); // Adds '$' and formats to 2 decimal places
                        }
                    },
                    title: {
                        text: this.$t("total_price_on", {currency:this.main.currency}),
                        style: {
                            color: '#1c9ff8',
                        }
                    },
                    tooltip: {
                        enabled: true
                    },
                },
                {
                    seriesName: this.$t("avg_price"),
                    show:false,
                    axisTicks: {
                        show: true,
                    },
                    axisBorder: {
                        show: true,
                        color: '#feb019'
                    },
                    labels: {
                        style: {
                            colors: '#feb019',
                        },
                        formatter: (value) =>{
                            return this.main.formatDisplayNumber(value); // Adds '$' and formats to 2 decimal places
                        }
                    },
                    title: {
                    text: this.$t("unit_price_on",{currency:this.main.currency}),
                        style: {
                            color: '#feb019',
                        }
                    }
                },
        ],
        legend: { show: false },
        tooltip: {
          enabled: true,
          theme: this.main.mode,
          x: {
            show: true, formatter: (val) => this.main.formateDate(this.seriesData[val-1].date)},
          y: {
            formatter: (val, { seriesIndex }) => {
              return `${this.main.formatDisplayNumber(val)} <small>${this.chartSeries[seriesIndex].scale}</small>`;
            }
          },
        }
      }
    },
    cardStyle () {
      // fixed size as requested
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
    formatCurrency (v) {
      return `${this.main.formatDisplayNumber(v)} ${this.main.currency}`
    },
    closeDialog:function () {
      this.dialog = false;
    },
    showDialog() {
      this.dialog = true
    },
    filterDumpValue:function(value,allowed){
      return allowed ? value : 0;
    },
    generateLastDays() {
      const dates = [];
      const today = new Date();

      for (let i = 0; i < 10; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        dates.push(moment(d).format(this.main.date_format));
      }

      return dates.reverse(); // oldest → newest
    }

  }
}
</script>

<style scoped>
  .apexcharts-tooltip {
    width: 50px; /* Set a fixed width */
    /* OR */
    max-width: 300px; /* Or a maximum width */
    padding: 10px;
}

.mini-card {
  display: flex;
  align-items: stretch;
}

/* internal layout */
.card-inner {
  width: 100%;
  height: 100%;
  padding: 4px 6px;
  box-sizing: border-box;
}

/* KPI row */
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

.kpi-master{
  height: 80% !important;
}
.disabled-text {
  color: #9e9e9e;              /* grey */
  text-decoration: line-through;
  opacity: 0.7;                /* optional: softer look */
  cursor: not-allowed;         /* optional UX hint */
  user-select: none;           /* optional */
}

/* icons size tweak inside small card */
.v-icon {
  font-size: 14px;
}
</style>
