<template>
    <v-empty-state
    :headline="$t('whoops_404')"
    :title="$t('page_not_found')"
    :text="$t('page_not_found_text')"
    image="/storage/images/error-404.png"
    class="mt-10"
    >
        <template v-slot:actions>
            <v-btn
                color="blue"
                elevation="1"
                rounded="xl"
                :text="$t('back_home')"
                @click="$router.push({name:'index'})"
            ></v-btn>

            <v-btn
                color="white"
                variant="plain"
                rounded="xl"
                :text="$t('back_previous')"
                @click="$router.back()"
            ></v-btn>
        </template>
    </v-empty-state>
</template>
<script>
export default {
}
</script>