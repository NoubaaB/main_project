<template>
    <v-card >
        <v-card-text>
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

            <ApexCharts
            type="area"
            :options="chartOptions"
            :series="series"
            height="350"
            />
        </v-card-text>
        <v-divider ></v-divider>
        <v-card-actions>
            <v-row class="text-center">
                <v-col class="d-inline">
                    <v-chip color="green">
                        <span class="d-inline mr-2" >
                            <v-icon small class="mr-1" color="green lighten-1">mdi-cash</v-icon>
                            <span >{{$t("price")}}</span>
                        </span>
                        <strong >{{ main.formatDisplayNumber(summary.total_price) }} ({{main.currency}})</strong>
                    </v-chip>
                </v-col>
                <v-col>
                    <v-chip color="blue">
                        <span class="d-inline mr-2" >
                            <v-icon small class="mr-1" color="green lighten-1">mdi-weight-kilogram</v-icon>
                            <span >{{$t("weight")}}</span>
                        </span>
                        <strong >{{ main.formatDisplayNumber(summary.total_weight) }} (Kg)</strong>
                    </v-chip>
                </v-col>
                <v-col>
                    <v-chip color="purple">
                        <span class="d-inline mr-2" >
                            <v-icon small class="mr-1" color="green lighten-1">mdi-package-variant-closed</v-icon>
                            <span >{{$t("boxes")}}</span>
                        </span>
                        <strong >{{ main.formatDisplayNumberNonFloat(summary.total_weight) }} (Un)</strong>
                    </v-chip>
                </v-col>
                <v-col>
                    <v-chip color="purple">
                        <span class="d-inline mr-2" >
                            <v-icon small class="mr-1" color="orange lighten-1">mdi-percent</v-icon>
                            <span >{{$t("avg_price")}}</span>
                        </span>
                        <strong >{{ main.formatDisplayNumber(avgPrice) }} {{main.currency}}/Kg</strong>
                    </v-chip>
                </v-col>
            </v-row>
        </v-card-actions>
    </v-card>
</template>

<script>
    import ApexCharts from 'vue3-apexcharts'
    import { useMain } from '../../../stores/Main';

    export default {
        components: { ApexCharts },
        props:{
            details:Array,
            summary:Object
        },
        data:function(){
            return {
                type: 'day'
            }
        },
        computed: {
            main: () => useMain(),       
            generateRange: function () {
                const firstDate = _.minBy(this.details, o => new Date(o.date))?.date;
                const latestDate = _.maxBy(this.details, o => new Date(o.date))?.date;
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
            series: function () {
                return [
                 {
                        name: this.$t("price"),
                        type: 'column',
                        data: this.getDataSeries("total_price",false)
                    },
                    {
                        name: this.$t("weight"),
                        type: 'area',
                        data: this.getDataSeries("total_weight",false)
                    },
                    {
                        name: this.$t("avg_price"),
                        type: 'line',
                        data: this.getDataSeries("unit_price",true)
                    },
                ]
            },
            chartOptions: function () {
                return {
                    chart: {
                        type: 'line',
                        height: 350,
                        stacked: false,
                        events: {
                            selection: function (chart, e) {
                                console.log(new Date(e.xaxis.min))
                            }
                        },
                    },
                    labels:this.generateRange,
                    theme: {
                        mode: this.main.mode,
                    },
                    tooltip: {
                        enabled: true,
                        theme: this.main.mode,
                        x: {
                            show: true, formatter: (val) => this.main.formateDate(val)},
                        y: {
                            formatter: (val, { seriesIndex }) => {
                                // nicer formatting depending on series
                                if (seriesIndex === 1) return `${this.main.formatDisplayNumber(val)} kg` // weight
                                return this.formatCurrency(val)
                            }
                        },
                    },

                    colors: ['#1c9ff8', '#00e396', '#feb019'],
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        width: [0, 2, 3],
                        curve: 'smooth',
                        dashArray: [0, 0, 5]
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
                            opacityFrom: 0.85,
                            opacityTo: 0.55,
                            stops: [100, 100, 100, 100]
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
                        type: 'datetime'
                    },
                    yaxis: [
                            {
                                seriesName: this.$t("weight"),
                                opposite: true,
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
                                tooltip: {
                                    enabled: true
                                },
                            },
                            {
                                seriesName: this.$t("price"),
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
                                seriesName: this.$t("unit_price"),
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
                    ]
                }
            },
        },
        methods: {
            formatCurrency(v) {
                return `${this.main.formatDisplayNumber(v)} ${this.main.currency}`
            },
            getDataSeries: function (prop,is_divided=false) {
                const periods = this.generateRange;
                if (!periods.length) return [];

                const fmt = this.type === 'month' ? 'YYYY-MM' : this.type === 'year' ? 'YYYY' : 'YYYY-MM-DD';

                // group items by formatted period (skip invalid dates)
                const grouped = _.groupBy(this.details, item => {
                    const m = moment.utc(item.date);
                    return m.isValid() ? m.format(fmt) : '__invalid__';
                });
                // map periods in order and sum total_weight (coerce NaN -> 0)
                return periods.map(p => (
                    _.sumBy(grouped[p] || [], it => {
                        
                        const n = Number(_.get(it, prop, 0));
                        // console.log("grouped.length",prop,grouped[p].length,Number.isFinite(n) ? n : 0,is_divided)
                        if (is_divided) {
                            return (Number.isFinite(n) ? n : 0)/grouped[p].length;
                        }
                        return (Number.isFinite(n) ? n : 0);
                            
                    })
                ));
            }
        }
    }
</script>

<style lang="scss" scoped>

</style>