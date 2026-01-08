<template>
    <v-snackbar
        class="absolute_index"
        v-model="charge.snackbar_action_command"
        location="top"
        :color="main.color_theme_reverse"
        :timeout="2000"
        rounded="pill"
    >
        <v-row>
            <v-col cols="2">
                <v-icon :color="charge.snackbar_action_command_icon_color">
                    {{ charge.snackbar_action_command_icon  }}
                </v-icon>
            </v-col>
            <v-col>
                {{ charge.snackbar_action_command_text }}
            </v-col>
        </v-row>
        <template v-slot:actions>
        <v-btn
            color="info"
            variant="text"
            @click="charge.snackbar_action_command = false"
        >
            {{ $t("close") }}
        </v-btn>
        </template>
    </v-snackbar>
</template>
<script>
import { useCharge } from '@/stores/Charge';
import { useMain } from '@/stores/Main';
export default {
    computed: {
        charge: function () {
            return useCharge();
        },
        main: function () {
            return useMain();
        }
    }
}
</script>