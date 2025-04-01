<template>
  <v-overlay v-model="overlay"></v-overlay>
  <v-container class='fluid fill-height trz_builmaxshop_auth'>
        <v-layout  class='align-center justify-center pt-5 pb-8'>
          <v-flex  class='xs12 sm8 md4'>

        
              <v-card class="elevation-12 auth_card" >
                <v-container>
                <div align="center">
                <router-link to="/" tag="span" style="cursor: pointer">
                <v-img src='images/logo.png' max-width="150"></v-img>
                </router-link>

                <p class="pa-1 font-bold">BuildMax Shop Admin Panel</p>
                </div>
               
                </v-container>
                <v-toolbar class="auth_toolbar" light color="primary" width="450px"> 
                    <v-toolbar-title align="center" class="ma-0">Forgot Password</v-toolbar-title>
                </v-toolbar>
                <v-card-text>
                  <v-form @submit.prevent="sendPasswordResetEmail">
                    <v-text-field
                      v-model="email"
                      label="Email"
                      required
                    ></v-text-field>
                    
                  </v-form>
                </v-card-text>
                <v-card-actions class="pa-5">
                  <v-btn type="submit" @click="sendPasswordResetEmail" color="primary">Send Password Reset Link</v-btn>                  
                </v-card-actions>
              </v-card>
          </v-flex>
        </v-layout>
    </v-container>
</template>
  
  <script>
  export default {
    data() {
      return {
        email: '',
        overlay: false,
      }
    },
    methods: {
      sendPasswordResetEmail() {
        this.overlay = true
        axios.post('/api/password/email', {
          email: this.email,
        }).then(response => {
          this.showSuccess('A Password reset link has been sent to your email.');
          this.overlay = false

          setTimeout(() => {
            this.$router.push('/admin/login');
          }, 2000)
          
          
        }).catch(error => {
          // Error sending password reset link
        });
  }
  
    }
  }
  </script>