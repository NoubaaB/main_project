import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { useItem } from "./Item";
import { useMain } from "./Main";

export const useDashboardOwner = defineStore("dashboard_owner", {
    state: () => {
        return {
            item: useItem(),
            main: useMain(),
            date_picker: {
                start: moment().subtract(6, "days").format("YYYY-MM-DD"),
                end: moment().format("YYYY-MM-DD"),
            },
            daily: { 
                commands: []
            },
        };
    },
    actions: {
        getData: async function () {
            return await axios.get(`/api/dailies/show_date/${moment().format('YYYY-MM-DD')}`)
                .then(response => {
                    if (response.data.daily) {
                        this.daily = response.data.daily;
                    }
                    return this.daily
                })
        }
    },
    getters: {
        commands_date: state => state.daily.commands.map(e => e.date),
        commands_list: state => {
            let commands = []
            for (const date of state.commands_date) {
                let paid_commands = state.daily.commands.filter(command => (command.date == date) && (command.is_paid == true));
                let total_paid = _.sumBy(paid_commands, "total");

                let non_paid_commands = state.daily.commands.filter(command => (command.date == date) && (command.is_paid == false));
                let total_non_paid = _.sumBy(non_paid_commands, "total");
                commands.push({
                    date,
                    total_paid,
                    total_non_paid
                })
            }
            return commands
        },
        commands_paid: state => {
            return state.commands_list.map(e => e.total_paid)
        },
        commands_non_paid: state => {
            return state.commands_list.map(e => e.total_non_paid)
        },
        total_command_paid: state => state.commands_paid.reduce((acc, el) => acc + el.total, 0.00),
        total_command_non_paid: state => state.commands_non_paid.reduce((acc, el) => acc + el.total, 0.00)
    },
});