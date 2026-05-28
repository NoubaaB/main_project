<template>
  <div class="border-dashed border-sm rounded-lg border-opacity-75 border-info auth-animated-icon pa-1">
    <v-card-title class="text-center ">
        <v-row>
            <v-col class="auth-title-col">
                <div class="auth-title-wrapper">
                  <img
                    src="/images/jupiter-logo.png"
                    width="200"
                    height="200"
                    class="auth-animated-title"
                    />
                </div>
            </v-col>
        </v-row>
        <v-row class="mb-5">
            <v-col class="auth-title-col">
                <div >
                    <span class="auth-animated-title">{{$t("authentication_form")}}</span>
                </div>
            </v-col>
        </v-row>
    </v-card-title>

    <v-form @submit.prevent="submit" class="pa-2">
        
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
  </div>
  <div class="text-right pt-3">
      <v-chip class="pulse-powered-by-ai" color="info" prepend-icon="mdi-robot" variant="tonal">
          {{ $t('powered_by_ai') }}
      </v-chip>
  </div>

  <!-- Login Error Snackbar -->
  <v-snackbar
    v-model="loginError"
    :timeout="5000"
    location="top right"
    color="error"
    rounded="lg"
    elevation="12"
    min-width="300"
  >
    <div class="d-flex align-center">
      <v-icon class="mr-2">mdi-alert-circle</v-icon>
      <span class="text-subtitle-2 font-weight-medium">{{ $t('login_error') }}</span>
    </div>
    <template #actions>
      <v-btn variant="text" size="small" icon="mdi-close" @click="loginError = false"></v-btn>
    </template>
  </v-snackbar>

  <!-- Account Disabled Snackbar -->
  <v-snackbar
    v-model="accountDisabled"
    :timeout="8000"
    location="top right"
    color="warning"
    rounded="lg"
    elevation="12"
    min-width="360"
  >
    <div class="d-flex align-center">
      <v-icon class="mr-2">mdi-account-lock</v-icon>
      <div>
        <div class="text-subtitle-2 font-weight-bold">{{ $t('account_disabled_title') }}</div>
        <div class="text-caption" style="opacity:.85">{{ $t('account_disabled_message') }}</div>
      </div>
    </div>
    <template #actions>
      <v-btn variant="text" size="small" icon="mdi-close" @click="accountDisabled = false"></v-btn>
    </template>
  </v-snackbar>
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
                loginError: false,
                accountDisabled: false,
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
                this.loginError = false;
                this.accountDisabled = false;
                this.auth.login().then(_res => {
                    this.loading = false;
                    if (!_res.token) {
                        this.auth.model.password = null;
                        this.loginError = true;
                        return;
                    }
                    this.auth.model.password = null;
                    this.$router.push({"name":"index"});
                }).catch(error=>{
                    this.loading = false;
                    this.auth.model.password = null;
                    if (error.response && error.response.status === 403 && error.response.data?.disabled) {
                        this.accountDisabled = true;
                    } else {
                        this.loginError = true;
                    }
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

/* -- Animated Auth Title -- */
.auth-title-col {
  padding-top: 12px;
  padding-bottom: 4px;
}

.auth-title-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
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

e-col {
  padding-top: 12px;
  padding-bottom: 4px;
}

.auth-animated-icon {
  background-color: #6dc7ff2d;
}

/* -- Powered By AI Pulse -- */
@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(2, 143, 251, 0.5);
  }
  70% {
    box-shadow: 0 0 0 15px rgba(228, 242, 253, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(228, 242, 253, 0);
  }
}

.pulse-powered-by-ai {
  animation: pulse 2s infinite;
}
</style>