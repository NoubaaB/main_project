<template>
    <v-snackbar
        class="absolute_index"
        v-model="main.snackbar.action_command"
        location="top"
        :color="main.color_theme_reverse"
        :timeout="8000"
        rounded="pill"
    >
        <v-row>
            <v-col cols="1">
                <v-icon :color="main.snackbar.action_command_icon_color">
                    {{ main.snackbar.action_command_icon  }}
                </v-icon>
            </v-col>
            <v-col>
                {{ main.snackbar.action_command_text }}
            </v-col>
        </v-row>
        <template v-slot:actions>
        <v-btn
            color="info"
            variant="text"
            @click="main.snackbar.action_command = false"
        >
            {{ $t("close") }}
        </v-btn>
        </template>
    </v-snackbar>
</template>
<script>
import { useMain } from '@/stores/Main';
export default {
    computed: {
        main: function () {
            return useMain();
        }
    }
}
</script>