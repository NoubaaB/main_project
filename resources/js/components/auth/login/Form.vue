<template>
    <v-card-title class="text-center">
        {{$t("authentication_form")}}
    </v-card-title>
    <v-form @submit.prevent="submit">
        <div class="mx-3 text-subtitle-1 text-medium-emphasis">{{$t('email')}}</div>
        
        <v-text-field
            v-model="auth.model.email"
            :error-messages="
                v$.auth.model.email.$errors.map(
                    (e) => e.$message
                )
            "
            flat
            name="email"
            variant="solo-filled"
            rounded
            prepend-inner-icon="mdi-email-outline"
            @input="v$.auth.model.email.$touch"
            @blur="v$.auth.model.email.$touch"
            :label="$t('enter_your_e_mail')"
        ></v-text-field>

        <div class="mx-3 text-subtitle-1 text-medium-emphasis">{{$t('password')}}</div>

        <v-text-field
            v-model="auth.model.password"
            :append-inner-icon="show_password ? 'mdi-eye' : 'mdi-eye-off'"
            :error-messages="
                v$.auth.model.password.$errors.map(
                    (e) => e.$message
                )
            "
            flat
            variant="solo-filled"
            rounded
            prepend-inner-icon="mdi-lock-outline"
            @input="v$.auth.model.password.$touch"
            @blur="v$.auth.model.password.$touch"
            :type="show_password ? 'text' : 'password'"
            name="password"
            autocomplete="on"
            :label="$t('enter_your_password')"
            @click:append-inner="show_password = !show_password"
        ></v-text-field>

        <!-- <router-link :to="{ name: 'forget_password'}" class="text-blue text-decoration-underline py-4">{{ $t('forget_password?') }}</router-link> -->

        <v-divider class="mt-2"></v-divider>

        <v-btn
        rounded
        class="my-4"
        color="blue"
        type="submit"
        block
        :variant="v$.$invalid ? 'tonal':'flat'"
        :disabled="v$.$invalid"
        :loading="loading"
        >
        {{ $t("authentication") }}
        </v-btn>
    </v-form>
</template>

<script>
    import useValidate from "@vuelidate/core";
    import {
        email,
        required,
        maxLength,
        minLength,
    } from "@vuelidate/validators";
    import { useAuth } from "@/stores/Auth";
    export default {
        data() {
            return {
                v$: useValidate(),
                show_password: false,
                loading: false,
            }
        },
        computed: {
            counter: function () {
                return {
                    password_min: 5,
                    password_max: 50,
                    email_min: 5,
                    email_max: 50,
                };
            },    
            auth: function () {
                return useAuth();
            }
        },
        methods: {
            submit: function () {
                this.loading = true;
                this.auth.login().then(_res => {
                    this.loading = false;
                    this.auth.model.password = null;
                    this.$router.push({"name":"index"});
                }).catch(error=>{
                    this.loading = false
                    this.auth.model.password = null;
                });
            }
        },
        validations: function () {
            return {
                auth: 
                {
                    model:{
                        password: {
                            required,
                            minLength: minLength(this.counter.password_min),
                            maxLength: maxLength(this.counter.password_max),
                        },
                        email: {
                            required,
                            email,
                            minLength: minLength(this.counter.email_min),
                            maxLength: maxLength(this.counter.email_max),
                        }
                    }
                },
            };
        }        
    }
</script>

<style lang="scss" scoped>

</style>