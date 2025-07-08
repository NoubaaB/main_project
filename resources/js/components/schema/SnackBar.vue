<template>
    <v-snackbar
        class="absolute_index"
        v-model="receipt.snackbar_action_command"
        location="top"
        :color="main.color_theme_reverse"
        :timeout="2000"
        rounded="pill"
    >
        <v-row>
            <v-col cols="2">
                <v-icon :color="receipt.snackbar_action_command_icon_color">
                    {{ receipt.snackbar_action_command_icon  }}
                </v-icon>
            </v-col>
            <v-col>
                {{ receipt.snackbar_action_command_text }}
            </v-col>
        </v-row>
        <template v-slot:actions>
        <v-btn
            color="blue"
            variant="text"
            @click="receipt.snackbar_action_command = false"
        >
            {{ $t("close") }}
        </v-btn>
        </template>
    </v-snackbar>
</template>
<script>
import { useReceipt } from '@/stores/Receipt';
import { useMain } from '@/stores/Main';
export default {
    computed: {
        receipt: function () {
            return useReceipt();
        },
        main: function () {
            return useMain();
        }
    }
}
</script>