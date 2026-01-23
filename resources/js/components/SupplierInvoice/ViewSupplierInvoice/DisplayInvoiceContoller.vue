<template>
        <v-row class="text-center controller-wrap border-dashed border-md rounded-lg border-info mt-2">
            <v-col>
                <v-row>
                    <v-col>
                        <v-btn v-print="printInvoice" rounded block @click="toPDF" prepend-icon="mdi-printer" color="red">{{$t("print")}}</v-btn>
                    </v-col>
                    <v-col>
                        <v-btn v-print="printInvoice" rounded block @click="toPDF" prepend-icon="mdi-microsoft-excel" color="green">{{$t("export_to_excel")}}</v-btn>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>
</template>

<script>
import print from 'vue3-print-nb'

export default {
        directives: {
                print   
        },
        props:{toPDF:Function},
        data() {
            return {
                printInvoice : {
                id: "invoice",
                popTitle: 'Supplier Invoice',
                closeCallback : (vue) => {
                        this.toPDF()
                        console.log('closeCallback')
                    },
                }
            }
        },
    }
</script>

<style lang="scss" scoped>
    .controller-wrap{
        position: fixed;
        width: 32vw;
    }
</style>