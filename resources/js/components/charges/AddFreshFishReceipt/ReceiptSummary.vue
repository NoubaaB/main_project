<template>
    <v-card class="ma-auto rounded-lg">
        <v-card-title>
          <span>
            Receipt Summary 
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
                    <v-icon color="yellow" class="text-blue">mdi-lightbulb-alert</v-icon>
                  </v-col>
                </v-row>
                <v-row class="pa-0">
                  <v-col class="pa-0">
                    <span>Summary</span>
                  </v-col>
                </v-row>
              </div>
              <div class="flex-grow-1">
                <v-divider class="border-opacity-25" color="info"></v-divider>
              </div>
            </v-col>
          </v-row>
          <v-row class="pt-2">
            <!-- Total Price -->
            <v-col cols="12" md="6">
              <v-card
              border="opacity-50 success sm"
              class="mx-auto"
              max-width="360"
              rounded="xl"
              variant="text"
            >
              <template v-slot:title>
                <div class="text-caption font-weight-bold">Total Price</div>
              </template>
        
              <template v-slot:append>
                <v-chip
                  border="success sm opacity-100"
                  color="green"
                  size="small"
                  variant="text"
                >
                  <v-icon icon="mdi-arrow-up" start></v-icon> 13%
                </v-chip>
              </template>
        
              <template v-slot:text>
                <div class="text-h4 font-weight-black">$ 9,232,215</div>
        
                <small class="text-caption text-medium-emphasis d-flex justify-space-between align-center">
                  <div>
                    <span class="text-green">
                      <v-avatar icon="mdi-arrow-up" size="small" variant="tonal"></v-avatar>
                      + $ 3,295
                    </span>
        
                    in the last week
                  </div>
        
                  <v-btn
                    icon="mdi-arrow-right"
                    size="x-small"
                    variant="text"
                    border
                  ></v-btn>
                </small>
              </template>
              </v-card>            
            </v-col>
            <!--  Total Weight -->
            <v-col cols="12" md="6">
              <v-card
              border="opacity-50 info sm"
              class="mx-auto"
              max-width="360"
              rounded="xl"
              variant="text"
            >
              <template v-slot:title>
                <div class="text-caption font-weight-bold">Total Weight</div>
              </template>
        
              <template v-slot:append>
                <v-chip
                  border="success sm opacity-100"
                  color="info"
                  size="small"
                  variant="text"
                >
                  <v-icon icon="mdi-scale" center></v-icon>
                </v-chip>
              </template>
        
              <template v-slot:text>
                <div class="text-h4 font-weight-black">Kg 9,232</div>
        
                <small class="text-caption text-medium-emphasis d-flex justify-space-between align-center">

        
                  <v-btn
                    icon="mdi-truck-check"
                    size="x-small"
                    variant="text"
                    color="info"
                    border="info sm"
                  ></v-btn>
                </small>
              </template>
              </v-card>    
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
</template>

<script>
    export default {
        
    }
</script>

<style scoped>

</style>