<template>
  <div class="productCell">
    <div class="image">
      test
    </div>
    <div>
      <div>{{ value }}</div>
      <div class="stockCell">{{ data.category }}</div>
    </div>
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
.productCell {
  height: 100%;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
}

.image {
  max-width: 100%;
  max-height: 100px;
  background-color: rgba(201, 201, 201, 0.2);
  border-radius: 8px;
  margin: 8px;
}

.image img {
  width: 60px;
  height: 60px;
}

.productCell div:first-child {
  font-weight: 500;
}

.productCell div {
  padding-bottom: 0;
  line-height: 1.5;
}

.productCell img {
  border-radius: 8px;
}

.stockCell {
  background-color: var(--color-bg-secondary);
  color: var(--color-text-secondary);
  width: fit-content;
  border: 1px solid #c0c0c057;
  border-radius: 6px;
  padding-top: 2px;
  margin-top: 4px;
  padding-right: 4px;
  padding-left: 4px;
  font-size: 12px;
}
</style>
