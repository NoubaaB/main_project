<template>
    <v-snackbar
        class="absolute_index"
        v-model="command.snackbar_action_command"
        location="top"
        :color="main.color_theme_reverse"
        :timeout="2000"
        rounded="pill"
    >
        <v-row>
            <v-col cols="2">
                <v-icon :color="command.snackbar_action_command_icon_color">
                    {{ command.snackbar_action_command_icon  }}
                </v-icon>
            </v-col>
            <v-col>
                {{ command.snackbar_action_command_text }}
            </v-col>
        </v-row>
        <template v-slot:actions>
        <v-btn
            color="blue"
            variant="text"
            @click="command.snackbar_action_command = false"
        >
            {{ $t("close") }}
        </v-btn>
        </template>
    </v-snackbar>
</template>
<script>
import { useCommand } from '@/stores/Command';
import { useMain } from '@/stores/Main';
export default {
    computed: {
        command: function () {
            return useCommand();
        },
        main: function () {
            return useMain();
        }
    }
}
</script>