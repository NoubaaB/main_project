<template>
    <div class="text-center" style="padding-top: 10%">
        <v-img
            alt="cart_coffee"
            style="mix-blend-mode: multiply"
            class="mx-auto"
            max-width="50%"
            src="/coffee-cart.png"
        ></v-img>
        <div class="text-grey-lighten-1 py-2">
            {{ $t("alert_cart_is_empty_text") }}
            <div>
                <v-btn
                    color="blue"
                    variant="tonal"
                    class="my-2"
                    @click="add_command()"
                >
                    {{ $t("add_command") }}
                </v-btn>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        add_command:Function
    }
}
</script>