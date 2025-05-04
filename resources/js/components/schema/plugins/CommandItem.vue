<template>
    <div :id="cmd.id" class="swiper-container">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <v-hover v-slot="{ isHovering, props }">
                    <v-card
                        v-bind="props"
                        class="my-3"
                        :elevation="(isHovering && allow_delete) ? 6 : 0"
                        :class="{
                            'on-hover': isHovering,
                            animation:
                                cmd.animation && main.command_drawer,
                            danger:
                                cmd.danger && main.command_drawer,
                            on_delete:
                                on_delete,
                        }"
                    >
                        <template v-slot:loader>
                            <v-progress-linear
                                :active="loading"
                                indeterminate
                                height="3"
                                :color="pause ? 'blue':'red-darken-4'"
                            ></v-progress-linear>
                        </template>
                        <v-card-item>
                            <v-card-title class="text-body-2">
                                <v-row class="text-center mb-1">
                                    <v-col cols="2">
                                        <v-btn
                                            v-if="!$vuetify.display.mobile && allow_delete"
                                            icon="mdi-close"
                                            color="red"
                                            size="x-small"
                                            class="ma-1"
                                            variant="tonal"
                                            v-ripple
                                            @click="deleteCommand"
                                        >
                                        </v-btn>
                                        <div v-else-if="loading" class="cursor-pointer" @click="rollback">
                                                <v-progress-circular
                                                :size="30"
                                                :width="1"
                                                color="primary"
                                                indeterminate
                                                >
                                                    <template v-slot:default> 
                                                        <v-icon v-if="pause">
                                                            mdi-pause
                                                        </v-icon>
                                                        <span v-else>
                                                            {{counter}} 
                                                        </span>
                                                    </template>
                                                </v-progress-circular>
                                        </div>
                                    </v-col>
                                    <v-col cols="5">
                                        <div class="d-inline">
                                            <small
                                                class="text-caption font-weight-thin"
                                            >
                                                <span
                                                    class="text-caption font-italic d-inline"
                                                    >{{
                                                        $t(
                                                            "client"
                                                        )
                                                    }}</span
                                                >
                                                :
                                                <v-chip
                                                    variant="tonal"
                                                    color="blue"
                                                    class="px-2"
                                                    @click="clientDialog"
                                                >
                                                    {{
                                                        command.gtetClientName(
                                                            cmd.client_id
                                                        ) || $t("divers")
                                                    }}
                                                </v-chip> 
                                            </small>
                                            <span
                                                class="text-blue d-block mt-2"
                                            >
                                                <v-badge
                                                    location="top start"
                                                    color="red"
                                                    floating
                                                    :content="
                                                        command.calcQte(
                                                            cmd
                                                        )
                                                    "
                                                >
                                                    <v-icon
                                                        icon="mdi-coffee"
                                                        color="success"
                                                    ></v-icon>
                                                </v-badge>
                                                =
                                                <span
                                                    class="text-caption font-weight-thin h6"
                                                    >
                                                    <strong>
                                                        {{
                                                            formatFloatNumber(command.calcTotalPrice(
                                                                cmd
                                                            ))
                                                        }}
                                                    </strong>
                                                    MAD</span
                                                >
                                            </span>
                                        </div>
                                    </v-col>
            
                                    <v-col>
                                        <div
                                            class="qte_wrapper justify-end"
                                        >
                                                <button
                                                    class="btn btn--minus mx-1 pl-1 rounded-s-xl"
                                                    @click="
                                                        command.changeCounter(
                                                            cmd,
                                                            -1
                                                        )
                                                    "
                                                    type="button"
                                                    name="remove item"
                                                    v-ripple
                                                >
                                                    <v-icon
                                                        >mdi-minus</v-icon
                                                    >
                                                </button>
                                                <input
                                                    class="quantity font-weight-medium"
                                                    type="number"
                                                    disabled
                                                    :name="`counter_${cmd.id}`"
                                                    v-model="cmd.qte"
                                                    @input="
                                                        command.changeCounter(
                                                            cmd,
                                                            0
                                                        )
                                                    "
                                                />
                                                <button
                                                    :disabled="
                                                        cmd.qte >=
                                                        command.MAX_QTE
                                                    "
                                                    class="btn btn--plus mx-1 pr-1 rounded-e-xl"
                                                    @click="
                                                        command.changeCounter(
                                                            cmd,
                                                            1
                                                        )
                                                    "
                                                    type="button"
                                                    name="add item"
                                                    v-ripple
                                                >
                                                    <v-icon
                                                        >mdi-plus</v-icon
                                                    >
                                                </button>
                                        </div>
                                    </v-col>
                                </v-row>
                            </v-card-title>
                            <v-card-actions>
                                <v-btn
                                    @click="cmd.show = !cmd.show"
                                    variant="tonal"
                                    size="small"
                                    :color="
                                        cmd.show
                                            ? 'orange'
                                            : 'blue'
                                    "
                                    block
                                    rounded
                                >
                                    <v-icon
                                        :icon="
                                            cmd.show
                                                ? 'mdi-chevron-up'
                                                : 'mdi-chevron-down'
                                        "
                                    ></v-icon>
                                    {{
                                        $t(
                                            cmd.show
                                                ? "command_item_hide"
                                                : "command_item_show"
                                        )
                                    }}
                                </v-btn>
                            </v-card-actions>
                            <v-expand-transition 
                                style="background-color: inherit;"
                            >
                                <v-card-text
                                    v-show="cmd.show"
                                    class="pa-0"
                                    style="background-color: inherit;"
                                >
                                    <v-row>
                                        <v-col cols="1" class="text-center" v-if="allow_delete">
                                            <v-checkbox
                                                v-model="cmd.is_paid"
                                                color="info"
                                                :name="`${cmd.id}_is_paid`"
                                                :id="`${cmd.id}_is_paid`"
                                                hide-details
                                            ></v-checkbox>
                                        </v-col>
                                        <v-col class="text-center mt-2">
                                            <input v-model="cmd.date" :max="date_command_max" type="datetime-local" :name="`time_local_${cmd.id}`" :id="`time_local_${cmd.id}`">
                                        </v-col>
                                        <v-col cols="3" class="text-center">
                                            <v-text-field
                                                    @update:model-value="
                                                    command.delaySetCartStorage()
                                                "
                                                :label="
                                                    $t(
                                                        'command_tips'
                                                    )
                                                "
                                                flat
                                                :name="`description-${command.id}`"
                                                variant="solo-filled"
                                                rounded
                                                hide-details
                                                v-model="cmd.tips"
                                                rows="1"
                                                type="number"
                                                color="blue"
                                                tabindex="1"
                                            ></v-text-field>
                                        </v-col>
                                    </v-row>
                                    <v-table
                                        fixed-header
                                        density="compact"
                                        style="background-color: inherit;"
                                    >
                                        <thead 
                                        style="background-color: inherit;"
                                        >
                                            <tr class="text-center"
                                            style="background-color: inherit;"
                                            >
                                                <th
                                                    class="text-center"
                                                ></th>
                                                <th
                                                    class="text-center"
                                                >
                                                {{ $t("command_item_name") }}
                                                </th>
                                                <th
                                                    class="text-center"
                                                >
                                                    {{
                                                        $t(
                                                            "command_item_total"
                                                        )
                                                    }}
                                                </th>
                                                <th
                                                    class="text-center"
                                                >
                                                    {{
                                                        $t(
                                                            "command_item_qte"
                                                        )
                                                    }}
                                                </th>
                                                <th
                                                    class="text-center"
                                                >
                                                    {{
                                                        $t(
                                                            "command_item_description"
                                                        )
                                                    }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <DetailCommand v-for="command_item in cmd.command_items"
                                             :key="command_item.id"
                                             :command_item="command_item"
                                             :cmd="cmd"
                                             />
                                        </tbody>
                                    </v-table>
                                </v-card-text>
                            </v-expand-transition>
                        </v-card-item>
                        <v-divider></v-divider>
                    </v-card>
                </v-hover>
            </div>
        </div>
    </div>
</template>
<script>
import { useMain } from '@/stores/Main.js';
import { useCommand } from '@/stores/Command.js';
import { useClient } from '@/stores/Client.js';
import useValidate from "@vuelidate/core";
import DetailCommand from "./DetailCommand.vue"
import { useDisplay } from "vuetify";

import {
    email,
    required,
    numeric,
    maxLength,
    minLength,
} from "@vuelidate/validators";

// import 'swiper/dist/css/swiper.min.css'import Swiper from 'swiper';


//https://github.com/davidgaroro/vuetify-swipeout/blob/master/src/components/SwipeoutItem.vue

export default {
    components: {
      DetailCommand  
    },
    props: {
        _deleteCommand:Function|null,
        cmd:Object,
        date_command_max: String,
        direction: {
            validator: value => {
                return ['right', 'left'].indexOf(value) !== -1
            }
        }
    },
    mounted: function () {
        // Initialize Swiper
        if (this.allow_delete) {
            this.$nextTick(() => {
                const el = '#' + this.cmd.id;
                const direction = this.direction;
                const swiper = new Swiper(el, {
                    resistanceRatio: 0.85,
                    initialSlide: this.direction === 'left' ? 0 : 1,
                    resistance: true,
                    allowSlideNext: false,
                    momentumBounce: true,
                    speed: 150,
                });
                swiper.on('transitionStart', () => {
                    this.on_delete = true;
                    this.loading = true;
                    if ((direction === 'right' && swiper.activeIndex === 0) || (direction === 'left' && swiper.activeIndex === 1)) {
                        this.setTimeout =  setTimeout(() => {
                            this.deleteCommand().then(res => {
                                swiper.destroy();
                            });
                        }, 3000);
                        this.setInterval = setInterval(() => {
                            ++this.counter;
                        }, 1000);
                        // Destroy slider instance and detach all events listeners
                    }
                })
            });
        }
    },
    data() { 
        return {
            v$: useValidate(),
            loading:false,
            on_delete:false,
            pause:false,
            setTimeout:null,
            setInterval:null,
            counter:1,
        }
    },
    computed: {
        main: function() {
            return useMain()
        },
        command:function(){
            return useCommand();
        },
        client:function(){
            return useClient();
        },
        color:function(){
            return this.main.color_theme;
        },
        display:function(){
            return useDisplay();
        },
        allow_delete:function(){
            console.log("display.mdAndUp.value",this.display.mdAndUp.value);
            return typeof this._deleteCommand == 'function' && !this.display.mdAndUp.value;
        }
    },
    methods: {
        deleteCommand: async function () {
            let client_id = this.cmd.client_id;
            this.command.delete(this.cmd);
            await this._deleteCommand(client_id == null ? this.$t('divers') : this.command.gtetClientName(client_id));
        },
        addClient:async function(val){
            await this.client.add(val.target._value).then(id=>{
                this.command.cmd.client_id = id;
                this.command.dialog_client = !this.command.dialog_client;
            });
        },
        clientDialog: function () {
            this.command.cmd = this.cmd;
            this.command.dialog_client = !this.command.dialog_client;
        },
        rollback: function () {
            this.pause = true;
            clearTimeout(this.setTimeout);
            clearInterval(this.setInterval);
            this.on_delete = false;
            this.counter = 1;
            setTimeout(() => {
                this.loading = false;
                this.pause = false;
            }, 1000);
        }
    },
    watch: {
        "cmd.command_items": {
            deep:true,
            handler: function (value) {
                this.command.calcCommand(this.cmd)
            }            
        }
    }
}
</script>
<style scoped>
.animation {
    animation: morph 3s linear;
}

@keyframes morph {
    0% {
        background-color: v-bind(color);
    }
    50% {
        background-color: rgb(138, 255, 196);
    }
    100% {
        background-color: v-bind(color);
    }
}

.danger {
    animation: danger_morph 3s linear;
}

@keyframes danger_morph {
    0% {
        background-color: v-bind(color);
    }
    50% {
        background-color: rgb(252, 64, 64);
    }
    100% {
        background-color: v-bind(color);
    }
}
/*
aliceblue
*/

/* Product Quantity */
.qte_wrapper {
    height: 30px;
    display: flex;
    justify-content: center;
}
.quantity {
    border: none;
    text-align: center;
    width: 30px;
    background-color: #b1b5b714 !important;
    font-size: 16px;
    color: var(--inverse_theme) !important;
    font-weight: 300;
    border: 1px solid #bec3c529;
}
.quantity::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.qte_wrapper-detail {
    height: 30px;
    display: flex;
    flex-direction: column-reverse;
    justify-content: center;
    align-items: center;
}
.quantity-detail {
    border: none;
    text-align: center;
    width: 30px;
    background-color: #b1b5b714 !important;
    font-size: 16px;
    color: var(--inverse_theme) !important;
    font-weight: 300;
    border: 1px solid #bec3c529;
}
.quantity-detail::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.btn {
    border: 1px solid #bec3c529;
    width: 30px;
    background-color: #b1b5b714 !important;
    color: var(--inverse_theme) !important;
    /*   border-radius: 6px; */
    cursor: pointer;
}
.btn-detail {
    border: 1px solid #bec3c529;
    width: 30px;
    background-color: #b1b5b714 !important;
    color: var(--inverse_theme) !important;
    /*   border-radius: 6px; */
    cursor: pointer;
}
.swiper-container{
    background-color: rgb(255, 180, 180) !important;
    background-image: url("/trash.png") !important;
    background-clip: padding-box;
    background-size: contain;
    background-position-x: left;
}
.swiper-wrapper{
    background-color: v-bind(color) !important;
}
.on_delete{
    animation: danger_morph 1s infinite;
}
</style>