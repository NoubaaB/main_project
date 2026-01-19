<template>
    <v-container class="invoice-wrap pa-0">
        <div class="header-wrap">
            <v-row no-gutters>
                <v-col cols="12" class="bg-light-blue-darken-4 header-wrap-strick">
                </v-col>
            </v-row>
            <v-row no-gutters class="header-wrap-divided bg-light-blue-lighten-2 ">
                <v-col cols="4" class="bg-light-blue-darken-4 text-center-h-v pl-3">
                    <v-img  src="/storage/images/invoice_title.png"></v-img>
                </v-col>
                <v-col cols="2" class="bg-light-blue-darken-4 header-cursev-dark">
                    
                </v-col>
                <v-col cols="2" class="bg-white header-cursev-light">
    
                </v-col>
                <v-col cols="*" class="bg-white text-light-blue-darken-4 text-center-h-v invoice-title">
                    INVOICE
                </v-col>
            </v-row>
        </div>
        <!-- HEADER -->

        <div class="wrap-title">
            <v-row no-gutters>
                <v-col>
                    <v-row >
                        <v-col class="text-center text-light-blue-darken-4">
                            <strong class="mx-3" v-for="n in 9" :key="n">▷</strong>
                        </v-col>
                    </v-row>
                    <v-row >
                        <v-col style="margin-left : 1cm">
                            <v-row no-gutters>
                                <v-col class="text-black">
                                    <strong>
                                        Societé : IGLO FISH
                                    </strong>
                                </v-col>
                            </v-row>
                            <v-row no-gutters>
                                <v-col class="text-black">
                                    <strong>
                                        Adresse : LAAYOUNE
                                    </strong>
                                </v-col>
                            </v-row>
                        </v-col>
                    </v-row>
                </v-col>
                <v-col >
                    <v-row  class="text-center-h-v text-center bg-light-blue-lighten-2 rounded-s-xl">
                        <v-col>
                            Facture N° :
                            <strong>
                             2500047
                            </strong>
                        </v-col>
                        <v-col cols="1">
                            │
                        </v-col>
                        <v-col>
                            Date :
                            <strong>
                             03/08/2025
                            </strong>
                        </v-col>
                    </v-row>
                    <v-row >
                        <v-col class="anti-slash text-light-blue-lighten-2">
                            <strong v-for="n in 15" :key="n">/</strong>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>
            <div class="title_doughnut_top"></div>
        </div>
        <div class="content-wrap">
            <img class="invoice_body" src="/storage/images/invoice_body.png"/>
            test
            <table class="wrap-table text-black">
                <tr>
                    <th v-for="n in 7" :key="n">th</th>
                </tr>
                <tr>
                    <td v-for="n in 7" :key="n">td</td>
                </tr>
            </table>
            <div class="wrap-summary">

            <div class="summary_doughnut_top"></div>
            </div>
        </div>
        <!-- FOOTER -->
        <div class="footer-wrap">
            <v-row no-gutters class="footer-wrap-divided bg-light-blue-lighten-2">
                <v-col cols="4" class="bg-white text-light-blue-darken-4 text-center px-8 invoice-director">
                    <div class="mb-2">
                        La Direction
                    </div>
                    <v-divider opacity=".7" thickness="2" class="mb-8 text-light-blue-darken-4"></v-divider>
                    <v-divider opacity=".5" thickness="2" variant="dashed" class="text-light-blue-lighten-2 mx-5"></v-divider>
                </v-col>
                <v-col cols="2" class="bg-white footer-cursev-light">
    
                </v-col>
                <v-col cols="2" class="bg-light-blue-darken-4 footer-cursev-dark">
                    
                </v-col>
                <v-col cols="4" class="bg-light-blue-darken-4">
    
                </v-col>
            </v-row>
            <v-row no-gutters>
                <v-col cols="12" class="bg-light-blue-darken-4 footer-wrap-strick">
    
                </v-col>
            </v-row>
        </div>
    </v-container>
</template>

<script>
    export default {
        
    }
</script>

<style lang="scss" scoped>
    .content-wrap{
        height: calc(
            var(--invoice-height) 
            - var(--header-wrap-divided-height) 
            - var(--header-wrap-divided-height) 
            - var(--header-wrap-strick-height) 
            - var(--header-wrap-strick-height) 
            - var(--wrap-title-height) 
            - var(--wrap-title-margin-top-height)
        ); // 
        position: relative;
    }

    .invoice-wrap{
        height: var(--invoice-height);
        width: var(--invoice-width);
        overflow: hidden;
        background-color: white;
    }

    .wrap-title{
        height: var(--wrap-title-height);
        margin-top: var(--wrap-title-margin-top-height);
    }

    //stylish header-wrap
    .header-wrap-strick{
        height: var(--header-wrap-strick-height);
    }
    .header-wrap-divided{
        height: var(--header-wrap-divided-height);
    }
    .header-cursev-dark{
        clip-path: polygon(100% 0%, 0% 0% , 0.00% 100.00%, 1.00% 99.98%, 2.00% 99.90%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 99.00% 0.02%, 100.00% 0.00%)
    }
    .header-cursev-light{
        clip-path: polygon(100% 100%, 0% 100% , 0.00% 99.38%, 1.00% 99.35%, 2.00% 99.27%, 3.00% 99.15%, 4.00% 98.97%, 5.00% 98.74%, 6.00% 98.47%, 7.00% 98.14%, 8.00% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48.00% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53.00% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 97.00% -2.90%, 98.00% -3.02%, 99.00% -3.10%, 100.00% -3.13%)
    }


    //stylish footer-wrap
    .footer-wrap-strick{
        height: var(--header-wrap-strick-height);
    }
    .footer-wrap-divided{
        height: var(--header-wrap-divided-height);
    }
    .footer-cursev-dark{
        clip-path: polygon(100% 100%, 0% 100% , 0.00% 99.38%, 1.00% 99.35%, 2.00% 99.27%, 3.00% 99.15%, 4.00% 98.97%, 5.00% 98.74%, 6.00% 98.47%, 7.00% 98.14%, 8.00% 97.76%, 9.00% 97.34%, 10.00% 96.87%, 11.00% 96.35%, 12.00% 95.78%, 13.00% 95.16%, 14.00% 94.50%, 15.00% 93.79%, 16.00% 93.04%, 17.00% 92.24%, 18.00% 91.40%, 19.00% 90.51%, 20.00% 89.59%, 21.00% 88.62%, 22.00% 87.61%, 23.00% 86.57%, 24.00% 85.48%, 25.00% 84.36%, 26.00% 83.21%, 27.00% 82.02%, 28.00% 80.79%, 29.00% 79.54%, 30.00% 78.25%, 31.00% 76.93%, 32.00% 75.59%, 33.00% 74.21%, 34.00% 72.81%, 35.00% 71.39%, 36.00% 69.95%, 37.00% 68.48%, 38.00% 66.99%, 39.00% 65.49%, 40.00% 63.96%, 41.00% 62.42%, 42.00% 60.87%, 43.00% 59.30%, 44.00% 57.73%, 45.00% 56.14%, 46.00% 54.55%, 47.00% 52.95%, 48.00% 51.34%, 49.00% 49.73%, 50.00% 48.12%, 51.00% 46.52%, 52.00% 44.91%, 53.00% 43.30%, 54.00% 41.70%, 55.00% 40.11%, 56.00% 38.52%, 57.00% 36.95%, 58.00% 35.38%, 59.00% 33.83%, 60.00% 32.29%, 61.00% 30.76%, 62.00% 29.26%, 63.00% 27.77%, 64.00% 26.30%, 65.00% 24.86%, 66.00% 23.44%, 67.00% 22.04%, 68.00% 20.66%, 69.00% 19.32%, 70.00% 18.00%, 71.00% 16.71%, 72.00% 15.46%, 73.00% 14.23%, 74.00% 13.04%, 75.00% 11.89%, 76.00% 10.77%, 77.00% 9.68%, 78.00% 8.64%, 79.00% 7.63%, 80.00% 6.66%, 81.00% 5.74%, 82.00% 4.85%, 83.00% 4.01%, 84.00% 3.21%, 85.00% 2.46%, 86.00% 1.75%, 87.00% 1.09%, 88.00% 0.47%, 89.00% -0.10%, 90.00% -0.62%, 91.00% -1.09%, 92.00% -1.51%, 93.00% -1.89%, 94.00% -2.22%, 95.00% -2.49%, 96.00% -2.72%, 97.00% -2.90%, 98.00% -3.02%, 99.00% -3.10%, 100.00% -3.13%)
    }
    .footer-cursev-light{
        clip-path: polygon(100% 0%, 0% 0% , 0.00% 100.00%, 1.00% 99.98%, 2.00% 99.90%, 3.00% 99.78%, 4.00% 99.61%, 5.00% 99.38%, 6.00% 99.11%, 7.00% 98.80%, 8.00% 98.43%, 9.00% 98.01%, 10.00% 97.55%, 11.00% 97.04%, 12.00% 96.49%, 13.00% 95.89%, 14.00% 95.24%, 15.00% 94.55%, 16.00% 93.82%, 17.00% 93.04%, 18.00% 92.22%, 19.00% 91.35%, 20.00% 90.45%, 21.00% 89.51%, 22.00% 88.53%, 23.00% 87.51%, 24.00% 86.45%, 25.00% 85.36%, 26.00% 84.23%, 27.00% 83.07%, 28.00% 81.87%, 29.00% 80.65%, 30.00% 79.39%, 31.00% 78.10%, 32.00% 76.79%, 33.00% 75.45%, 34.00% 74.09%, 35.00% 72.70%, 36.00% 71.29%, 37.00% 69.86%, 38.00% 68.41%, 39.00% 66.94%, 40.00% 65.45%, 41.00% 63.95%, 42.00% 62.43%, 43.00% 60.91%, 44.00% 59.37%, 45.00% 57.82%, 46.00% 56.27%, 47.00% 54.71%, 48.00% 53.14%, 49.00% 51.57%, 50.00% 50.00%, 51.00% 48.43%, 52.00% 46.86%, 53.00% 45.29%, 54.00% 43.73%, 55.00% 42.18%, 56.00% 40.63%, 57.00% 39.09%, 58.00% 37.57%, 59.00% 36.05%, 60.00% 34.55%, 61.00% 33.06%, 62.00% 31.59%, 63.00% 30.14%, 64.00% 28.71%, 65.00% 27.30%, 66.00% 25.91%, 67.00% 24.55%, 68.00% 23.21%, 69.00% 21.90%, 70.00% 20.61%, 71.00% 19.35%, 72.00% 18.13%, 73.00% 16.93%, 74.00% 15.77%, 75.00% 14.64%, 76.00% 13.55%, 77.00% 12.49%, 78.00% 11.47%, 79.00% 10.49%, 80.00% 9.55%, 81.00% 8.65%, 82.00% 7.78%, 83.00% 6.96%, 84.00% 6.18%, 85.00% 5.45%, 86.00% 4.76%, 87.00% 4.11%, 88.00% 3.51%, 89.00% 2.96%, 90.00% 2.45%, 91.00% 1.99%, 92.00% 1.57%, 93.00% 1.20%, 94.00% 0.89%, 95.00% 0.62%, 96.00% 0.39%, 97.00% 0.22%, 98.00% 0.10%, 99.00% 0.02%, 100.00% 0.00%)
    }

    .text-center-h-v{
        display: flex;        /* 1. Activates the flexbox layout */
        justify-content: center; /* 2. Centers content horizontally along the main axis */
        align-items: center;     /* 3. Centers content vertically along the cross axis */
    }

    .anti-slash{
        font-family: math;
        font-size: xxx-large;
        font-style: italic;
    }

    .invoice-title{
        padding-top: 3%;
        font-weight: 900;
        font-family: sans-serif;
        font-size: xxx-large;
        height: 3.05cm;
        text-transform: uppercase;
    }

    .invoice-director{
        font-weight: 600;
        font-family: sans-serif;
        font-size: x-large;
    }

    .title_doughnut_top{
        border-radius: 100px;
        border: 22px solid rgba(135, 207, 235, 0.1529411765);
        height: 3.5cm !important;
        width: 3.5cm;
        position: relative;
        left: -1.5cm;
        top: -2cm;
        z-index: 0;
    }

    .summary_doughnut_top{
        border-radius: 100px;
        border: 22px solid rgba(135, 207, 235, 0.1529411765);
        height: 3.5cm !important;
        width: 3.5cm;
        position: relative;
        left: 19cm;
        top: 13cm;
        z-index: 0;
    }

    .wrap-table{
        width: var(--invoice-height);
    }

    .invoice_body{
        opacity: 0.1;
        top: 13%;
        position: absolute;
        right: 15%;
        pointer-events: none;    
    }
</style>