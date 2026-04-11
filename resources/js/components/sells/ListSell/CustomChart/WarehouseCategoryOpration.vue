<template>
  
  <v-card class="mini-card ma-1 rounded-xl"  
  elevation="6" 
  :style="cardStyle" 
  :disabled="chartSeries.length == 0"
  :color="chartSeries.length == 0?`grey-${main.mode}ten-5`:''"
  @click="showDialog"
  :loading="sell.loading"
  >
    <template v-slot:loader="{ isActive }">
      <v-progress-linear v-if="isActive && chartSeries.length == 0" color="info" indeterminate></v-progress-linear>
    </template>
    <div class="card-inner d-flex" >
      <div style="flex:1; min-width:0;">
        <div class="kpi-master">

          <div class="kpi-row d-flex align-center justify-space-between"  :style="{'color':card_detail.color}" v-for="(card_detail , index) in card_details" :key="index">
            <div class="d-flex align-center">
              <v-icon small class="mr-1" :color="card_detail.color">{{card_detail.mdi_icon}}</v-icon>
              <div class="kpi-label" style="color: initial">{{card_detail.name}}</div>
            </div>
            <div class="kpi-value">{{ main.formatDisplayNumber(card_detail.total) }} <small style="color: initial">{{card_detail.scale}}</small></div>
          </div>

        </div>
        <v-divider color="info"></v-divider>
        <div class="mt-1">
          <h4 class="d-inline">{{warehouse_category_operation_name}}</h4>
        </div>
      </div>
      

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
    <ShowDetailChart @closeDialog="closeDialog" :card_details="card_details" :details="chartSeries" :formula="formula"/>
  </v-dialog>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useSell } from '@/stores/Sell';
import moment from 'moment';
import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';
import ApexCharts from 'vue3-apexcharts';
import ShowDetailChart from "./ShowDetailChart.vue";
import { Parser }  from 'expr-eval';

export default {
  components: { ApexCharts , ShowDetailChart},
  props: {
    formula: Object,
    date:Object
  },
  data () {
    return {
      chartHeight: 90,
      dialog:false
    }
  },
  computed: {
    // computed average price for the summary KPI
    main: () => useMain(),
    sell: () => useSell(),
    parser: () => new Parser(),
    warehouse_category_operation: () => useWarehouseCategoryOperation(),
    warehouse_category_operation_name: function () {
      return this.formula[`name_${this.main.lang.code}`];
    },
    ability_warehouse_category_operation : function(){
      return _.mapValues(_.keyBy(this.formula.allow_variables), () => true);
    },
    card_details: function () {
      return this.chartSeries.map(s => ({
          name: s.name,
          mdi_icon: s.mdi_icon,
          total: s.total_data,
          color: s.mdi_icon_color,
          scale: s.scale
        }));
    },
    // build series arrays (numbers) and categories (dates)
    chartLabels: function () {
      return _.uniq(
        _.map(this.sell.get_fetched_operations, 'date')
      )
    },
    chartSeries: function () {
      return this.getDataSeries();
    },
    chartColors: function () {
      return this.formula.expressions.map((exp) =>exp.chart_config.chart_color);
    },
    yaxisOptions: function () {
      return this.formula.expressions.filter(exp=>exp.chart_config.display_yaxis).map((exp) => {
        return {
                seriesName: exp.chart_config[`name_${this.main.lang.code}`],
                opposite: exp.chart_config.opposite == 1,
                show:false,
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: exp.chart_config.chart_color
                },
                labels: {
                    style: {
                        colors: exp.chart_config.chart_color,
                    },
                    formatter: (value) =>{
                        return this.main.formatDisplayNumber(value); // Adds '$' and formats to 2 decimal places
                    }
                },
                title: {
                    text: exp.chart_config.scale,
                    style: {
                        color: exp.chart_config.chart_color,
                    }
                },
                tooltip: {
                    enabled: true
                },
            }
      });
    },
    chartOptions() {
      return {
        chart: {
          type: 'line',
          background: 'transparent',
          sparkline: { enabled: true },
          toolbar: { show: false },
          animations: { enabled: true, easing: 'easeout', speed: 400 }
        },
        stroke: {
          curve: 'smooth',
        },
        theme: {
            mode: this.main.mode,
        },
        labels:this.chartLabels,
        fill: {
          type: 'line',
          gradient: {
            shadeIntensity: 20,
            inverseColors: false,
            opacityFrom: 1,
            opacityTo: 1,
          }
        },
        stroke: {
            curve: 'smooth',
        },
        colors: this.chartColors,
        markers: { size: 0 },
        grid: { show: false },
        xaxis: {
          categories: this.chartSeries.map(d => d.date),
          labels: { show: false },
          axisBorder: { show: false },
          axisTicks: { show: false }
        },
        yaxis: this.yaxisOptions,
        legend: { show: false },
        tooltip: {
          enabled: true,
          theme: this.main.mode,
          x: {
            show: true, formatter: (val) => this.main.formateDate(this.chartLabels[val-1])},
          y: {
            formatter: (val, { seriesIndex }) => {
              // nicer formatting depending on series
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
    },
    getDataSeries: function () {
      if (this.chartLabels.length == 0) return [];
      let data = [];
      this.formula.expressions.forEach(exp => {
        let total_expression = [];
        let g_variables = [];
        this.chartLabels.forEach(date => {
          let variables = [];
          exp.variables.forEach(variable => {
            let items = this.sell.get_fetched_operations.filter(detail => detail.date == date && detail.sell_category_id == variable.category_id);
            if (variable.stockable_type != null) {
              items = items.filter(detail => detail.stockable_type == variable.stockable_type);
            }
            if (variable.stockable_id != null) {
              items = items.filter(detail => detail.stockable_id == variable.stockable_id);
            }
            let total = _.sumBy(items, variable.field);
            variables.push({
              [variable.alias]: total
            });
            g_variables.push({
              [variable.alias]: total
            });
          })

          let params = Object.assign({}, ...variables);
          let total_value = this.parser.evaluate(exp.expression, params);
          let default_return = null;
          if (["line", "area"].find(e => e == exp.chart_config.chart_type)) {
              default_return = 0.00
          } else {
              default_return = null
          }
          total_expression.push(Number.isNaN(total_value) ? default_return : +total_value);
        })
        const result = _(g_variables)
                    .flatMap(_.entries)
                    .groupBy(0)
                    .map((items, key) => ({
                      [key]: _.sumBy(items, i => Number(i[1]) || 0)
                    }))
                    .value();
        let params = Object.assign({}, ...result);
        let total_value = this.parser.evaluate(exp.expression, params);
        data.push({
          name: exp.chart_config[`name_${this.main.lang.code}`],
          type: exp.chart_config.chart_type,
          data: total_expression,
          total_data: total_value,
          mdi_icon: exp.chart_config.mdi_icon,
          mdi_icon_color: exp.chart_config.chart_color,
          scale: exp.chart_config.scale,
        });
      });
      return data;
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
