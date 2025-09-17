<template>
  <div class="price">
    <span class="priceAmount">£{{ value }}</span>
    <span class="increase">{{ data.priceIncrease }}% increase</span>
  </div>
</template>

<script>
export default {
  props: ["params"],

  computed: {
    value() {
      return this.params.value;
    },
    data() {
      return this.params.data;
    },
  },
};
</script>

<style>
.price {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.price span {
  line-height: 100%;
}

.increase {
  opacity: 0.7;
}
</style>
