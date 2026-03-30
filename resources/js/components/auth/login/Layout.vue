<template>
    <v-card class="mt-12 rounded-xl layout_bg">
        <v-layout full-height>
            <v-container class="justify-center align-center">
                <v-row>
                    <v-col class="text-center">
                        <v-img
                            alt="login image"
                            class="hidden-xs"
                            src="/storage/images/login.svg"
                        ></v-img>
                    </v-col>
                    <v-divider vertical color="light-blue-darken-4"></v-divider>
                    <v-col class="text-center form_login">
                        <Form />
                    </v-col>
                </v-row>
            </v-container>
        </v-layout>
    </v-card>
</template>

<script>
    import Form from "@/components/auth/login/Form.vue";
    export default {
        components: {
            Form,
        },
    };
</script>

<style lang="scss" scoped>
    .layout_bg{
        background-color : #b7e4ff2d;
    }
    .form_login {
        display: flex;
        flex-direction: column;
        flex-wrap: wrap;
        justify-content: center;
        align-content: stretch;
        align-items: stretch;
    }
</style>
