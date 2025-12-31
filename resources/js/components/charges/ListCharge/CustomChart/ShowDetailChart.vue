<template>
    <v-card >
        <v-card-title class="text-h6 shadow-sm text-center">
            <v-btn @click="close" block rounded color="primary" variant="tonal" >
                <v-icon class="mr-5">mdi-close-circle-outline</v-icon>
                {{ $t('close') }}
                {{ warehouse_category_operation_name }}
            </v-btn>
        </v-card-title>
        <v-divider virtecal></v-divider>
        <v-card-text>
            <v-row>
                <v-col>
                    <v-tabs
                    v-model="type"
                    color="primary"
                    height="60"
                    grow
                    align-tabs="center"
                    slider-color="#f78166"
                    >
                        <v-tab value="day" class="rounded-ts-xl">
                            <v-icon start>mdi-calendar-today</v-icon>
                            {{ $t("daily") }}
                        </v-tab>

                        <v-tab value="month">
                            <v-icon start>mdi-calendar-month</v-icon>
                            {{ $t("monthly") }}
                        </v-tab>

                        <v-tab value="year" class="rounded-te-xl">
                            <v-icon start>mdi-calendar</v-icon>
                            {{ $t("yearly") }}
                        </v-tab>
                    </v-tabs>
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <ApexCharts
                    type="area"
                    :options="chartOptions"
                    :series="series"
                    height="350"
                    />
                </v-col>
            </v-row>
        </v-card-text>
        <v-divider ></v-divider>
        <v-card-actions>
            <v-row no-gutters class="text-center mb-2 mt-1">
                <v-col :style="{'color':card_detail.color}" v-for="(card_detail , index) in card_details" :key="index">
                    <v-chip :color="card_detail.color">
                        <span class="mr-2" >
                            <v-icon small class="mr-1" :color="`${card_detail.color} lighten-1`">{{card_detail.mdi_icon}}</v-icon>
                            <span >{{card_detail.name}}</span>
                        </span>
                        <strong >{{ main.formatDisplayNumber(card_detail.total) }} ({{card_detail.scale}})</strong>
                    </v-chip>
                </v-col>
            </v-row>
        </v-card-actions>
    </v-card>
</template>

<script>
    import ApexCharts from 'vue3-apexcharts'
    import { useMain } from '@/stores/Main';
    import { useCharge } from '@/stores/Charge';
    import { useWarehouseCategoryOperation } from '@/stores/WarehouseCategoryOperation';
    import locals from "@/locales/apexcharts_local.js";
    import { Parser }  from 'expr-eval';
    
    export default {
        components: { ApexCharts },
        props:{
            formula:Object,
            card_details:Array
        },
        data:function(){
            return {
                type: 'day',
                tooltip_date_format:"DD/MM/YYYY",
            }
        },
        watch:{
            type: function () {
                switch (this.type) {
                    case "day":
                        this.tooltip_date_format = "DD/MM/YYYY";
                    break;
                    case "month":
                        this.tooltip_date_format = "MM/YYYY";
                        break;
                    case "year":
                        this.tooltip_date_format = "YYYY";
                        break;
                }
            }    
        },
        computed: {
            main: () => useMain(),
            parser: () => new Parser(),
            charge: () => useCharge(),
            warehouse_category_operation: () => useWarehouseCategoryOperation(),     
            warehouse_category_operation_name: function () {
                return this.formula[`name_${this.main.lang.code}`];
            },
            ability_warehouse_category_operation : function(){
                return _.mapValues(_.keyBy(this.formula.allow_variables), () => true);
            },
            generateRange: function () {
                const firstDate = _.minBy(this.charge.get_fetched_operations, o => new Date(o.date))?.date;
                const latestDate = _.maxBy(this.charge.get_fetched_operations, o => new Date(o.date))?.date;
                const s = moment.utc(firstDate), e = moment.utc(latestDate);
                if (!s.isValid() || !e.isValid() || s.isAfter(e)) return [];

                const fmt = this.type === 'month' ? 'YYYY-MM' : this.type === 'year' ? 'YYYY' : 'YYYY-MM-DD';
                const unit = this.type === 'month' ? 'months' : this.type === 'year' ? 'years' : 'days';

                const out = [];
                for (let m = s.clone().startOf(this.type); m.isSameOrBefore(e.clone().endOf(this.type)); m.add(1, unit)) {
                    out.push(m.format(fmt));
                }
                return out;
            },
            yaxisOptions: function () {
                return this.formula.expressions.filter(exp=>exp.chart_config.display_yaxis).map((exp) => {
                    return {
                            opposite: exp.chart_config.opposite == 1,
                            show:true,
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
            series: function () {
              return this.getDataSeries();
            },
            chartOptions: function () {
                return {
                    chart: {
                        type: 'line',
                        locales: Object.entries(locals).map(([key,value]) =>value),
                        defaultLocale: this.main.lang.short,
                        height: 350,
                        background: 'transparent',
                        stacked: false,
                    },
                    labels:this.generateRange,
                    theme: {
                        mode: this.main.mode,
                    },
                    tooltip: {
                        enabled: true,
                        theme: this.main.mode,
                        x: {
                            show: true, formatter: (val) => this.main.formateDateCustom(val,this.tooltip_date_format)},
                        y: {
                            formatter: (val, { seriesIndex }) => {
                            // nicer formatting depending on series
                                return `${this.main.formatDisplayNumber(val)} <small>${this.series[seriesIndex].scale}</small>`;
                            }
                        },
                    },

                    // colors: this.chartColors,
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        width: [0, 2, 3],
                        curve: 'smooth',
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '50%'
                        }
                    },
                    fill: {
                        opacity: [1, 0.25, 1],
                        gradient: {
                            inverseColors: false,
                            shade: 'light',
                            type: "vertical",
                            opacityFrom: 1,
                            opacityTo: 1,
                            stops: [100, 100, 100]
                        }
                    },
                    markers: {
                        size: 0
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left'
                    },
                    xaxis: {
                        type: "datetime"
                    },
                    yaxis: this.yaxisOptions,
                }
            },
        },
        methods: {
            formatCurrency(v) {
                return `${this.main.formatDisplayNumber(v)} ${this.main.currency}`
            },
            close: function () {
                this.$emit('closeDialog');
            },
            getDataSeries: function () {
                if (this.generateRange.length == 0) return [];
                let data = [];
                const fmt = this.type === 'month' ? 'YYYY-MM' : this.type === 'year' ? 'YYYY' : 'YYYY-MM-DD';
                this.formula.expressions.forEach(exp => {
                    let total_expression = [];
                    this.generateRange.forEach(date => {
                        let variables = [];
                        exp.variables.forEach(variable => {
                            let items = this.charge.get_fetched_operations.filter(detail => moment(detail.date).format(fmt) == moment(date).format(fmt) && detail.charge_category_id == variable.category_id);
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
                    let total_data = 0.00;
                    if (exp.chart_config.use_avg) {
                        let total_expression_length = total_expression.filter(val => val > 0).length;
                        total_data = +( _.sum(total_expression) / (total_expression_length > 0 ? total_expression_length : 1));
                    } else {
                        total_data = +_.sum(total_expression);
                    }
                    data.push({
                        name: exp.chart_config[`name_${this.main.lang.code}`],
                        type: exp.chart_config.chart_type,
                        data: total_expression,
                        total_data: total_data,
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

<style lang="scss" scoped>

</style>