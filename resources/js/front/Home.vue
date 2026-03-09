<template>
  <div class="home-page">    

  <v-row>
      <v-col cols="9">
        <v-carousel show-arrows="hover">

          <v-carousel-item src="/images/carousel/all-off-eid-notice-web-banner.webp" cover></v-carousel-item>
          <v-carousel-item src="/images/carousel/eid-salami-offer.webp" cover></v-carousel-item>
          <v-carousel-item src="/images/carousel/eid-notice.webp" cover ></v-carousel-item>
          
        </v-carousel>
      </v-col>

      <v-col cols="3">
        <v-card class="mx-auto bg-primary" elevation="1" max-width="500">
          <v-card-title class="pb-5 font-weight-black text-center">Compare Products</v-card-title>

          <v-card-text class="text-center">
            Choose Two Products to Compare
          </v-card-text>

          <v-card-text>

            <v-text-field :loading="loading" append-inner-icon="mdi-magnify" density="compact" label="Search and Select Product"
              variant="outlined" single-line @click:append-inner="onClick">
            </v-text-field>

            <v-text-field :loading="loading" append-inner-icon="mdi-magnify" density="compact" label="Search and Select Product"
              variant="outlined" single-line @click:append-inner="onClick">
            </v-text-field>

            <v-btn class="text-none" size="x-large" variant="outlined" density="compact" block>
              View Comparison
            </v-btn>
          </v-card-text>
        </v-card>

        <v-img src="/images/ac-ton-calculator.webp" class="header-logo mt-5"></v-img>
        
      </v-col>
    </v-row>

    
  </div>
  <Navbar></Navbar>
</template>

<script setup>
// import Navbar from './Navbar.vue'
 
</script>

<script>
  
    export default {

      data() {
        
          return {   
            loading: false,
          }
      },
        methods: { },
        watch: {
          loading (val) {
            if (!val) return

            setTimeout(() => (this.loading = false), 2000)
          },
        },
        created() {      
            
        },
        
    }
</script>