<template>
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
                    <v-toolbar-title align="center" class="ma-0">Reset Password</v-toolbar-title>
                </v-toolbar>
                <v-card-text>
                  <v-form @submit.prevent="resetPassword">
                    <v-text-field class="d-none"
                      v-model="email"
                      label="Email"
                      required
                    ></v-text-field>
                    <v-text-field class="d-none"
                      v-model="token"
                      label="Token"
                      required
                    ></v-text-field>
                    <v-text-field
                      v-model="password"
                      label="New Password"
                      required
                      type="password"
                    ></v-text-field>
                    <v-text-field
                      v-model="password_confirmation"
                      label="Confirm New Password"
                      required
                      type="password"
                    ></v-text-field>
                    <div class="text-red mb-4" v-if="matchError">Password must Match</div>
                    <v-btn type="submit" color="primary">Reset Password</v-btn>
                  </v-form>
                </v-card-text>
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
        token: '',
        password: '',
        password_confirmation:'',
        matchError:false
      }
    },
    methods: {
      async resetPassword() {
        if(this.password != this.password_confirmation){
          this.matchError = true
        }else{

          let token = await this.checkRecaptcha()
          if (!token) 
          {
              return;
          }  

          axios.post('/api/password/reset', {
            email: this.email,
            token: this.token,
            password: this.password,
          }).then(response => {            
            	this.showSuccess('Password reset done');   
              
              setTimeout(() => {
                this.$router.push('/admin/login');
              }, 2000)
          }).catch(error => {
            // Error sending password reset link
          });

        }

        }
  
    },
    created() {          
  
      this.token = this.$route.query.token;
      this.email = this.$route.query.email;
    },
  }
  </script>
