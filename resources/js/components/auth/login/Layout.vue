<template>
    <v-card class="rounded-xl layout_bg">
        <v-layout full-height>
            <v-container class="justify-center align-center">
                <v-row>
                    <v-col class="text-center">
                        <div class="static_height">
                            <v-img
                                :alt="$t('login_image')"
                                class="hidden-xs rounded-lg"
                                src="/images/fish-plant-bg.png"
                                cover
                            ></v-img>
                            <v-overlay
                            scrim="#7cb9ff80"
                            model-value="true"
                            class="align-center justify-center"
                            contained
                            >
                            <div class="auth-title-wrapper">
                                <div class="d-flex flex-column align-center mb-6">
                                    <v-avatar size="150" class="profile-avatar mb-3" >
                                            <v-icon class="auth-animated-title" size="150">mdi-fingerprint</v-icon>
                                    </v-avatar>
                                </div>
                            </div>
                            </v-overlay>
                        </div>
                    </v-col>
                    <v-divider vertical color="light-blue-darken-4"></v-divider>
                    <v-col class="text-center form_login">
                        <Form></Form>
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

    /* -- Animated Auth Title -- */
    .auth-title-col {
    padding-top: 12px;
    padding-bottom: 4px;
    }
    .profile-avatar {
         background: linear-gradient(135deg, #7cb9ff80, #8bcbffa7) !important;
        box-shadow: rgba(25, 92, 187, 0.719) 0px 30px 90px;
    }
    .auth-title-wrapper {
        animation: floatTitle 3s ease-in-out infinite;
    }

    .auth-lock-icon {
        animation: colorShift 6s ease-in-out infinite;
    }

    .auth-animated-title {
    font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 1.55rem;
        letter-spacing: 1px;
        background: linear-gradient(
            270deg,
            #1565c0,
            #6dc7ff,
            #2979ff,
            #1565c0
        );
        background-size: 300% 300%;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: gradientMove 6s ease-in-out infinite;
    }

    /* Gradient sweep */
    @keyframes gradientMove {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Gentle float */
    @keyframes floatTitle {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-4px); }
    }

    /* Icon color cycle */
    @keyframes colorShift {
        0%   { color: #1565c0; }
        50%  { color: #0043bf; }
        100% { color: #1565c0; }
    }

    .static_height{
        height: 76vh;
        position: relative;
    }

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
