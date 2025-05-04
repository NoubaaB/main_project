import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { useItem } from './Item';
import { useMain } from "./Main";

export const useDashboardWaiter = defineStore("dashboard_waiter", {
    state: () => {
        return {
            date_picker: {
                start: moment().subtract(6, "days").format("YYYY-MM-DD"),
                end: moment().format("YYYY-MM-DD"),
            },
            daily: {
                commands: [],
                commands_items:[]
            },
            item: useItem(),
            main: useMain(),
            clients_not_paid: [],
            monthlies: []
        };
    },
    actions: {
        getData: async function () {

            return await axios.get(`/api/dailies/show_date/`, {
                params: {
                    date_start: moment(this.date_picker.start).format('YYYY-MM-DD'),
                    date_end: moment(this.date_picker.end).format('YYYY-MM-DD')
                }
            })
                .then(response => {
                    this.daily.commands = [];
                    let commands_items = []
                    response.data.dailies.forEach(daily => {
                        daily.commands.forEach(command => {
                            commands_items.push(...command.command_items)
                        });
                        this.daily.commands.push(...daily.commands);
                    });
                    this.clients_not_paid = response.data.clients_not_paid;

                    // Group by item_id
                    const grouped = _.groupBy(commands_items, 'item_id');

                    // Sum up prices for each item_id
                    const result = _.map(grouped, (group, item_id) => ({
                        item_id: parseInt(item_id), // Convert key back to integer
                        total_price: +_.sumBy(group, 'total').toFixed(2),
                        name: this.item.getName(item_id)
                    }));
                    this.daily.commands_items = result;
                    
                    this.monthlies = response.data.monthlies;
                    
                    return this.daily
                })
        }

    },
    getters: {
        houres: _ => _.daily.commands.map(command => new Date(command.date).getTime()),
        commands_list: state => {
            let commands = []
            for (const command of state.daily.commands) {
                let date = new Date(command.date).getTime();
                let is_paid = command.is_paid;
                let total_paid = 0.00;
                let total_non_paid = 0.00;
                let tips = command.tips;
                if (is_paid == true) {
                    total_paid = command.total;
                } else {
                    total_non_paid = command.total;
                }

                commands.push({
                    date,
                    is_paid,
                    total_paid,
                    total_non_paid,
                    tips
                })
            }
            return commands;
        },
        commands_paid: state => {
            return _.sortBy(state.commands_list.filter(e => e.is_paid).map(e => [e.date, +e.total_paid]), row => row[0]);
        },
        commands_non_paid: state => {
            return _.sortBy(state.commands_list.filter(e => !e.is_paid).map(e => [e.date, +e.total_non_paid]), row => row[0]);
        },
        commands_total: state => {
            return state.commands_list.map(e => (+e.total_non_paid + +e.total_paid).toFixed(2))
        },
        commands_tips: state => {
            return _.sortBy(state.commands_list.filter(e => e.tips).map(e => [e.date, +e.tips]), row => row[0]);
        },
        commands_total_paid_tips: state => {
            return state.commands_list.map(e => (+e.total_paid + +e.tips).toFixed(2))
        },
        total_command_paid: state => _.sumBy(state.commands_paid,row=>row[1]),
        total_command_non_paid: state => _.sumBy(state.commands_non_paid, row => row[1]),
        total_command_total: state => state.total_command_paid + state.total_command_non_paid,
        total_command_total_tips: state => _.sumBy(state.commands_tips, row => row[1]),
        total_command_total_items: state => _.sumBy(state.daily.commands_items, row => row.total_price),
        total_command_total_paid_tips: state => state.daily.total_paid + state.daily.total_tips,
        getMonthsList: state => {
            let year = moment(state.date_picker.start).format('YYYY');
            let locale = state.main.annyang;
            return _.range(1, 13).map(monthNumber => {
                const firstDay = new Date(year, monthNumber - 1, 1); // First day of the month
                const lastDay = new Date(year, monthNumber, 0); // Last day of the month
                return {
                    month_name: new Intl.DateTimeFormat(locale, { month: 'short' }).format(firstDay),
                    first_day_month: firstDay.toISOString().split('T')[0], // "YYYY-MM-DD"
                    last_day_month: lastDay.toISOString().split('T')[0] // "YYYY-MM-DD"
                };
            });
        },
        sumMonthlyTotals : state => {

            return {
                total_paid: state.monthlies.map(e => +e.total_paid.toFixed(2)),
                total_tips: state.monthlies.map(e => +e.total_tips.toFixed(2)),
                grand_total: state.monthlies.map(e => +(e.total_paid + e.total_tips).toFixed(2)),
                total_non_paid: state.monthlies.map(e => +e.total_non_paid.toFixed(2))
            };
        },
        total_coffee_waiter: state => {
            return {
                total_paid: _.sum(state.sumMonthlyTotals.total_paid),
                total_tips: _.sum(state.sumMonthlyTotals.total_tips),
                grand_total: _.sum(state.sumMonthlyTotals.grand_total),
                total_non_paid: _.sum(state.sumMonthlyTotals.total_non_paid)
            }
        },
        total_not_paid: state => {
            return _.sumBy(state.clients_not_paid, e => +e.total_price);
        },
    },
});