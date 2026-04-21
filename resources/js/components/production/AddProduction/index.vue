<template>
  <v-form ref="form" @submit.prevent="submitForm">
    <v-row>
      <v-col cols="12" md="5">
        <NecessaryInformation />
      </v-col>
      <v-divider vertical></v-divider>
      <v-col cols="12" md="7">
        <!-- Warehouse Category Operations -->
        <ProductionDetail class="mb-5" v-for="(warehouse_category_operation, index) in _production.production.warehouse_category_operations" :key="warehouse_category_operation.id" :warehouse_category_operation="warehouse_category_operation" :number="index+1" />
        <v-btn
          block
          variant="tonal"
          color="teal"
          rounded
          class="mb-5"
          prepend-icon="mdi-plus"
          @click="_production.add_warehouse_category_operation()"
        >
          {{ $t('add_details') }}
        </v-btn>
        <v-divider class="my-5"></v-divider>
        <!-- Production Summary -->
        <ProductionSummary />
      </v-col>
    </v-row>
  </v-form>
</template>

<script>
import { useProduction } from '@/stores/Production';
import NecessaryInformation from './NecessaryInformation.vue';
import ProductionDetail from './ProductionDetail.vue';
import ProductionSummary from './ProductionSummary.vue';

export default {
  components: {
    NecessaryInformation,
    ProductionDetail,
    ProductionSummary
  },
  computed: {
    _production() { return useProduction(); },
  },
  mounted() {
    const chargeId = this.$route.params.chargeId;
    this._production.init_production(chargeId ? parseInt(chargeId) : null);
  },
  methods: {
    submitForm() {
      this.$refs.form.validate();
    },
  },
}
</script>
