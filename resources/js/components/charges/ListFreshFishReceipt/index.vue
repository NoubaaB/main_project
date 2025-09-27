<template>
  <div>
    <div>
      <VDatePicker  
        :is-dark="main.is_dark"
        :max-date="new Date()"
        v-model.range="date"
        mode="date"
        expanded
      />
    </div>
    <v-data-table
      v-model="data_selected"
      :headers="headers"
      :items="data_row"
      item-selectable="selectable"
      density="compact"
      :hide-default-footer="true"
      :items-per-page="0"
      show-select
    ></v-data-table>
  </div>
</template>

<script>
    import { useMain } from '@/stores/Main';
    export default {
      data() {
        return {
          date: {
            start: new Date(2025, 8, 1),
            end: new Date(2025, 8, 17)
          },
          data_selected: [],
          data_row: [],
          loading: false,
        };
      },
      mounted: async function () {
          this.fetchData();
      },
      computed: {
        main() {
          return useMain();
        },
        headers() {
          return [
            { text: this.$t("date"), value: "date", align: "center" },
            { text: this.$t("tracking_number"), value: "tracking_numbers", align: "center" },
            { text: this.$t("supplier"), value: "suppliers", align: "center" },
            { text: this.$t("origin"), value: "origin", align: "center" },
            { text: this.$t("fishery"), value: "fishery", align: "center" },
            { text: this.$t("total_weight"), value: "total_weight", align: "center" },
            { text: this.$t("total_price"), value: "total_price", align: "center" },
          ];
        },
      },
      methods: {
        fetchData: async function () {
          try {
            this.loading = true;
            const response = await axios.get("/api/get_receipts", {
              params: {
                date_start: moment(this.date.start).format("YYYY-MM-DD"),
                date_end: moment(this.date.end).format("YYYY-MM-DD"),
              },
            });
            this.data_row = response.data.receipts;
          } catch (error) {
            console.error("Error fetching receipts:", error);
          } finally {
            this.loading = false;
          }
        }
      },
      watch:{
        date: {
          handler: async function (newDate) {
            if (!newDate.start || !newDate.end) return;
            this.fetchData();
          },
          deep: true,
        }
      }
    };
</script>

<style scoped>

</style>