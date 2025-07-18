<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <span>
            Add Frozeen Fish Process
          </span>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col class="d-flex align-center">
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
              <div class="px-2 d-block text-center">
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <v-icon class="text-blue">{{_receipt.add_fresh_fish_receive_details && _receipt.add_freeze_fish_process_details?'mdi-numeric-4-circle-outline':'mdi-numeric-3-circle-outline'}}</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>Add Details</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row>
            <v-col class="text-center">
              
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
</template>

<script>
    import { useReceipt } from '@/stores/Receipt';
    export default {
        computed: {
            _receipt() {
                return useReceipt();
            }
        },
    }
</script>

<style scoped>

</style>