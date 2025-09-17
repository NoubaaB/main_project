<template>
  <div class="stock">
    <span>{{ data.available }}</span>
    &nbsp;<span class="stockText">Stock /</span>&nbsp;
    <span class="variantsText">{{ `${data.variants} Variants` }}</span>
  </div>
</template>

<script>
export default {
  props: ["params"],

  computed: {
    data() {
      return this.params.data;
    },
  },
};
</script>

<style>
.stockText {
  opacity: 0.7;
}

.variantsText {
  opacity: 0.7;
}
</style>
