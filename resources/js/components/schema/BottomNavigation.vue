<template>
    <v-bottom-navigation active color="blue" mode="shift" v-model="btn_navigation_value">
        <v-btn stacked value="recent">
            <v-badge v-if="command.bills_count" color="blue" :content="command.bills_count">
                <v-icon>mdi-history</v-icon>
            </v-badge>
            <v-icon v-else>mdi-history</v-icon>
            <span>{{ $t('bills') }}</span>
        </v-btn>
        
        <v-btn stacked icon value="favorites" color="red" @click="main.command_drawer = !main.command_drawer">
            <v-badge v-if="command.commands_count" color="blue" :content="command.commands_count">
                <v-icon size="40">mdi-microphone</v-icon>
            </v-badge>
            <v-icon v-else size="40">mdi-microphone</v-icon>
            <span>{{ $t('command') }}</span>
        </v-btn>

        <v-btn stacked value="nearby">
            <v-icon>mdi-map-marker</v-icon>

            <span>{{ $t('favorites') }}</span>
        </v-btn>
    </v-bottom-navigation>
</template>

<script>
import { useMain } from '@/stores/Main';
import { useCommand } from '@/stores/Command';
import annyang from "annyang";
export default {
    data() {
        return {
            btn_navigation_value: null
        }
    },
    computed:{
        main: function () {
            return useMain()
        },
        command: function () {
            return useCommand()
        }        
    },
    methods: {
        is_fav: function (value) {
            console.log("get favorite");
        },
        get_localization: function () {
            console.log("get localization");
        },
        get_history: function () {
            console.log("get history");
        },
        addCommand: function () {
            console.log("test")
            this.command.add()
        }
    }
}
</script>