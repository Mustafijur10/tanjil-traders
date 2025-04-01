<template>
    


    <v-app :theme="theme" id="auth_main">
      
      <v-main >      
        <v-container fluid>
          <router-view></router-view>
      </v-container> 
      </v-main>
     
  </v-app>  



</template>


<script setup>
  // import App from '@/app.vue';
  // import { createApp } from 'vue';
  // import { VueReCaptcha } from 'vue-recaptcha-v3'
  // const app = createApp(App);
  // app.use(VueReCaptcha, { siteKey: '6Lf0rbgkAAAAAHAiACx7tJL_0QCZfjYUeEcr7Ynj' })
  
</script>
