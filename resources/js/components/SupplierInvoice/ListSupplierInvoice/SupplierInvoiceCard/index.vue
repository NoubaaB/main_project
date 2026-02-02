<template>
    <v-row>
        <v-col>
            <ChartCard/>
        </v-col>
        <v-col cols="3">
            <SideCard/>
        </v-col>
    </v-row>
</template>

<script>
    import ChartCard from './ChartCard.vue';
    import SideCard from './SideCard.vue';

    export default {
        components:{
            ChartCard,
            SideCard
        }
    }
</script>

<style lang="scss" scoped>

</style>