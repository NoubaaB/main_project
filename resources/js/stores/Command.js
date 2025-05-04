import axios from "axios";
import moment from "moment";
import { defineStore } from "pinia";
import { v4 as uuidv4 } from 'uuid';
import { useItem } from "./Item";
import { useWebWorkerFn } from '@vueuse/core';
import { useClient } from "./Client";

export const useCommand = defineStore("command", {
    state: () => {
        return {
            item: useItem(),
            client: useClient(),
            setTimeoutArr: [],
            setTimeoutStorage: null,
            dialog_client: false,
            cmd: null,
            model_command: {
                show: false,
                date: new Date(),
                description: "",
                is_paid: false,
                tips: 0,
                total: 0.00,
                index: 1,
                qte: 1,
                command_items:[],
                client_id: null,
                daily_id: null,
            },
            model_command_item: {
                qte: 1,
                total: 0.00,
                description: "",
                command_id: null,
                item_id: null
            },
            commands: [],
            commands_serving: [],
            commands_completed: [],
            deleted_commands: [],
            commands_db: [],
            bills: [],
            MAX_QTE: 30,
            MIN_QTE: 1,
        };
    },
    actions: {
        initAnimation: function (commands) {
            this.setTimeoutArr.forEach(t => clearTimeout(t));
            this.setTimeoutArr = [];
            this.setAnimationtoDefault(commands);
        },
        delete: function (data) {
            if ('length' in data) {
                data.forEach(command => {
                    this._delete(command)
                })
            } else {
                this._delete(data)
            }
            this.delaySetCartStorage();
        },
        _delete: function (command) {
            let commands = this.commands.filter(e => command.id == e.id);
            this.initAnimation(commands);
            let deleted_cmds = commands.map(e => { e.animation = true; return e });
            deleted_cmds.forEach(deleted_cmd => {
                if (!deleted_cmds.find(e => e.id != deleted_cmd.id)) {
                    this.deleted_commands.push(deleted_cmd)
                }
            })
            this.commands = this.commands.filter(c => command.id != c.id);
        },
        calcQte: function (command) {
            return command.command_items.reduce((acc, el) => acc + +el.qte, 0.00);
        },
        calcTotalPrice: function (command) {
            let total = 0.00;
            command.command_items.forEach(command_item => {
                let item = this.item.getList.find(e => e.id == command_item.item_id);
                if (item) {
                    total += item.price * command_item.qte;
                }

            })
            return total;

        },
        showItem: function (command) {
            return 0.00
        },
        changeCounter: function (command, value) {
            let qte = +command.qte + value;
            if (qte <= 0 || qte >= (this.MAX_QTE + 1)) {
                qte = 1;
            }
            command.qte = +qte;
            if (command.command_items) {
                while (command.qte != command.command_items.length) {
                    if (command.qte > command.command_items.length) {
                        this.command_item = {
                            id: uuidv4(),
                            qte: 1,
                            total: 0.00,
                            description: "",
                            command_id: null,
                            item_id: null
                        }
                        command.command_items.push(_.cloneDeep(this.command_item));
                    } else {
                        command.command_items.pop();
                    }
                }
            }
            this.delaySetCartStorage();
        },
        deleteCommandByClient: function (_client) {
            console.log("deleteCommandByClient", _client)
            if (_client.toLowerCase() == "divers") {
                console.log("ererer")
                this.delete(this.commands.filter(e => e.client_id == null))
                // this.deleted_commands.push(...this.commands.filter(e => e.client_id == null)) 
                // this.commands = this.commands.filter(e => e.client_id != null);
            } else {
                let client_id = _client ? this.filterCloseNames(this.client.getMinList, _client) : null;
                if (client_id) {
                    this.delete(this.commands.filter(e => e.client_id == client_id))
                    // this.deleted_commands.push(...this.commands.filter(e => e.client_id == client_id))
                    // this.commands = this.commands.filter(e => e.client_id != client_id);
                }
            }
            this.delaySetCartStorage();
        },
        restore: function () {
            this.commands.push(...this.deleted_commands)
            this.deleted_commands = [];
        },
        clear_restore: function () {
            this.deleted_commands = [];
            localStorage.setItem("deleted_commands", "[]")
        },
        restorCommandByClient: function (client_id) {
            this.commands.push(...this.deleted_commands.filter(c => c.client_id == client_id));
            this.deleted_commands = this.deleted_commands.filter(c => c.client_id != client_id);
        },
        deleteCommandItem: function (command, command_item) {
            if (command.command_items.length <= 1) {
                return 0;
            }
            command.command_items = command.command_items.filter(e => e.id != command_item.id);
            command.qte--;
            return this.delaySetCartStorage();
        },
        delaySetCartStorage: function () {
            clearTimeout(this.setTimeoutStorage);
            this.setTimeoutStorage = setTimeout(() => {
                this.setCartStorage();
            }, 1000);
        },
        setCartStorage: function () {
            let { workerFn } = useWebWorkerFn(({ commands, deleted_commands ,commands_serving , commands_completed }) => {
                return {
                    commands: JSON.stringify(commands),
                    deleted_commands: JSON.stringify(deleted_commands),
                    commands_serving: JSON.stringify(commands_serving),
                    commands_completed: JSON.stringify(commands_completed)
                };
            });
            workerFn({
                commands: JSON.parse(JSON.stringify(this.commands)),
                deleted_commands: JSON.parse(JSON.stringify(this.deleted_commands)),
                commands_serving: JSON.parse(JSON.stringify(this.commands_serving)),
                commands_completed: JSON.parse(JSON.stringify(this.commands_completed))
            }).then(({ commands, deleted_commands ,commands_serving , commands_completed }) => {
                localStorage.setItem('commands', commands);
                localStorage.setItem('deleted_commands', deleted_commands);
                localStorage.setItem('commands_serving', commands_serving);
                localStorage.setItem('commands_completed', commands_completed);
            });
            // localStorage.setItem('cart', JSON.stringify(this.collect.map(e => { return { id: e.id, type: e.type, details: e.details, qte: e.qte } })));
        },
        calcTotalPriceCommandItem: function (command_item) {
            let item = this.item.getList.find(e => e.id == command_item.item_id);
            if (!item) {
                return 0.00
            }
            return +(command_item.qte * item.price).toFixed(2);
        },
        setAnimationtoDefault: function (commands = null) {
            if (commands != null) {
                commands.forEach(command => {
                    command.animation = false;
                    command.danger = false;
                })
            } else {
                this.commands.forEach(command => {
                    command.animation = false;
                    command.danger = false;
                })
            }
            this.delaySetCartStorage();
        },
        levenshtein: function (a, b) {
            if (a.length === 0) return b.length;
            if (b.length === 0) return a.length;
            if (a.length > b.length) {
                // tmp = a;
                // a = b;
                // b = tmp;
                [a, b] = [b, a]
            }

            var i, j, res, alen = a.length, blen = b.length, row = Array(alen);
            for (i = 0; i <= alen; i++) {
                row[i] = i;
            }
            var tmp;
            for (i = 1; i <= blen; i++) {
                res = i;
                for (j = 1; j <= alen; j++) {
                    tmp = row[j - 1];
                    row[j - 1] = res;
                    res = b[i - 1] === a[j - 1] ? tmp : Math.min(tmp + 1, Math.min(res + 1, row[j] + 1));
                }
            }
            return res;
        },
        filterCloseNames: function (array, variable, threshold) {
            // Threshold determines how close the names should be, e.g., Levenshtein distance <= threshold
            threshold = threshold || 3; // Default threshold

            let arr = array.filter(obj => {
                // Calculate Levenshtein distance between the name property and the variable
                const distance = this.levenshtein(obj.name.toLowerCase(), variable.toLowerCase());
                return distance <= threshold;
            });
            if (arr.length > 0) {
                return arr[0].id
            }
            return null;
        },
        add: function (_client = null, _item = null, qte = 1) {
            let client_id = _client ? this.filterCloseNames(this.client.getMinList, _client) : null
            let item_id = _item ? this.filterCloseNames(this.item.getMinList, _item) : null
            let command = {
                id: uuidv4(),
                show: false,
                animation: true,
                date: moment().format('YYYY-MM-DDThh:mm'),
                description: "",
                danger: false,
                is_paid: false,
                tips: null,
                total: 0.00,
                index: 1,
                qte: 1,
                command_items: [
                    {
                        id: uuidv4(),
                        qte: !isNaN(qte) ? qte : 1,
                        total: 0.00,
                        description: "",
                        command_id: null,
                        item_id: item_id
                    }
                ],
                client_id: client_id,
                daily_id: null,
            };
            this.getCommandFirstItemInfo(command);
            console.log("command",command)
            this.commands.push(command);
            this.setTimeoutArr.push(setTimeout(() => {
                this.setAnimationtoDefault([command]);
            }, 3000));
            this.delaySetCartStorage();
        },
        deleteAllCommands: function (force_delete = false) {
            if (!force_delete) {
                this.commands.forEach(command => command.animation = true)
                this.deleted_commands.push(...this.commands)
            }
            this.commands = [];
        },
        post: async function () {
            let commands = [];
            this.commands.filter(command => command.qte > 0 && command.command_items.filter(command_item => command_item.item_id != null).length>0).forEach(command => {
                commands.push({
                    date: command.date,
                    description: command.description,
                    is_paid: command.is_paid,
                    tips: +command.tips,
                    index: 1,
                    qte: +command.qte,
                    command_items: command.command_items.
                        filter(command_item => command_item.item_id != null).
                        map(command_item => {
                        return {
                            qte: command_item.qte,
                            description: command_item.description,
                            item_id: command_item.item_id,
                        }
                    }),
                    client_id: command.client_id,
                })
            })
            if (commands.length == 0) {
                let cmds = this.commands.filter(command => command.command_items.filter(command_item => command_item.item_id == null));
                this.initAnimation(cmds);

                this.setTimeoutArr.push(setTimeout(() => {
                    cmds.forEach(e => {
                        e.danger = true;
                    });
                }, 0));
                this.setTimeoutArr.push(setTimeout(() => {
                    this.setAnimationtoDefault(cmds);
                }, 3000));
                return await false;
            }
            return await axios.post("/api/commands", {
                commands
            }).then(res => {
                console.log("res1", res);
                this.deleteAllCommands(true);
                this.bills.push(...res.data.commands)
                return res;
            })
        },
        gtetClientName: function (client_id) {
            return this.client.list.find(c => c.id == client_id)?.name || "N/A";
        },
        getItemName: function (item_id) {
            return this.item.list.find(c => c.id == item_id)?.name || "N/A";
        },
        addClient: function (value) {
            console.log("value",value)
        },
        getBill: function (matricule) {
            return this.bills.find(e => e.matricule == matricule);
        },
        getItem: function (item_id) {
            return this.item.list.find(e => e.id == item_id);  
        },
        getItemImage: function (item_id) {
            return this.getItem(item_id)?.image || "/null.png";  
        },
        getCommandFirstItemInfo: function (command) {
            let command_items = command.command_items.filter(command_item => command_item.item_id != null);
            let data = {
                "first_item_name": "N/A",
                "first_item_color": "242,133,147",
                "first_item_description": "N/A",
                "first_item_img": "https://cdn.vuetifyjs.com/docs/images/graphics/mice/1.png",
            };
            if (command_items.length) {
                let item = this.getItem(command_items[0].item_id);
                data =  {
                    "first_item_name": item.name,
                    "first_item_color": item.color,
                    "first_item_description": item.description,
                    // "first_item_img": "https://cdn.vuetifyjs.com/docs/images/graphics/mice/1.png",
                    "first_item_img": item.image,
                }
            }
            command.first_item_name = data.first_item_name;
            command.first_item_color = data.first_item_color;
            command.first_item_description = data.first_item_description;
            command.first_item_img = data.first_item_img;
        },
        calcCommand: function (cmd) {
            let total = 0.00;
            cmd.command_items.forEach(command_item => {
                let item = this.getItem(command_item.item_id);
                if (item) {
                    command_item.total = item.price * command_item.qte;
                }
                total += command_item.total;
            });
            cmd.total = total;
        }
    },
    getters: {
        commands_count: state => state.commands.length,
        bills_count: state => state.bills.length
    },
});