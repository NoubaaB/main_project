<template>
  <v-card class="mini-card ma-1 rounded-xl"  
  elevation="6" 
  :style="cardStyle" 
  :disabled="!data"
  @click="showDialog"
  >
    <div class="card-inner d-flex" >
      <!-- LEFT: numbers -->
      <div style="flex:1; min-width:0;">
        <div class="kpi-row d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-icon small class="mr-1" color="green lighten-1">mdi-cash</v-icon>
            <div class="kpi-label">{{$t("price")}}</div>
          </div>
          <div class="kpi-value">{{ main.formatDisplayNumber(summary.total_price) }} {{main.currency}}</div>
        </div>

        <div class="kpi-row d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-icon small class="mr-1" color="blue lighten-1">mdi-weight-kilogram</v-icon>
            <div class="kpi-label">{{$t("weight")}}</div>
          </div>
          <div class="kpi-value">{{ main.formatDisplayNumber(summary.total_weight) }} Kg</div>
        </div>

        <div class="kpi-row d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-icon small class="mr-1" color="purple lighten-1">mdi-package-variant-closed</v-icon>
            <div class="kpi-label">{{$t("boxes")}}</div>
          </div>
          <div class="kpi-value">{{ summary.total_package_count }}</div>
        </div>

        <div class="kpi-row d-flex align-center justify-space-between">
          <div class="d-flex align-center">
            <v-icon small class="mr-1" color="orange lighten-1">mdi-percent</v-icon>
            <div class="kpi-label">{{$t("avg")}}</div>
          </div>
          <div class="kpi-value">{{ main.formatDisplayNumber(avgPrice) }} {{main.currency}}/Kg</div>
        </div>
        <v-divider color="info"></v-divider>
        <div class="mt-1">
          <h4>{{warehouse_category_operation_name}}</h4>
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
    <ShowDetailChart :details="data.grouped_details"/>
  </v-dialog>
</template>

<script>
import { useMain } from '../../../stores/Main';
import { useCharge } from '../../../stores/Charge';
import ApexCharts from 'vue3-apexcharts'
import ShowDetailChart from "./ShowDetailChart.vue"
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
      seriesData: [],
      dialog:false
    }
  },
  mounted: function () {
    if (this.data) {
      this.summary = this.data;
      this.seriesData = this.data.grouped_details;
    } else {
      this.generateLastDays().forEach((date, idx) => {
        let rand_1 = Math.random();
        let rand_2 = Math.random();
        this.seriesData.push({
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
    
  },
  computed: {
    warehouse_category_operation_name: function () {
      return this.charge.warehouse_category_operation.getName(this.id || this.summary.warehouse_category_operation_id)
    },
    // computed average price for the summary KPI
    avgPrice () {
      return this.summary.unit_price
    },
    main: () => useMain(),
    charge: () => useCharge(),
    // build series arrays (numbers) and categories (dates)
    chartSeries () {
      // price series = total_price
      const price = this.seriesData.map(d => Number(d.total_price || 0))
      // weight series = total_weight
      const weight = this.seriesData.map(d => Number(d.total_weight || 0))
      // avg series (per record)
      const avg = this.seriesData.map(d => Number(d.unit_price || 0))
      
      return [
        {
          name: this.$t("price"),
          type: 'column',
          data: price
        },
        {
          name: this.$t('weight'),
          type: 'area',
          data: weight
        },
        {
          name: this.$t('avg_price'),
          type: 'line',
          data: avg
        }
      ]
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
        colors: ['#1c9ff8', '#00e396', '#feb019'],
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
              // nicer formatting depending on series
              if (seriesIndex === 1) return `${this.main.formatDisplayNumber(val)} kg` // weight
              return this.formatCurrency(val)
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
    showDialog() {
      console.log("event, chartContext, config")
      this.dialog = true
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

/* icons size tweak inside small card */
.v-icon {
  font-size: 14px;
}
</style>
