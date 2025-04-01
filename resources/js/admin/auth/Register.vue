<!-- <template>  
<v-container class='fluid fill-height trz_builmaxshop_auth'>
        <v-layout  class='align-center justify-center pt-5 pb-8'>
          <v-flex  class='xs12 sm8 md4'>

        
              <v-card class="elevation-12 auth_card" >
                <v-container>
                <div align="center">
                <router-link to="/" tag="span" style="cursor: pointer">
                <v-img src='/images/logo.png' max-width="150"></v-img>
                </router-link>

                <p class="pa-1 font-bold">BuildMax Shop Admin Panel</p>
                </div>
               
                </v-container>
                <v-toolbar class="auth_toolbar" light color="primary" width="450px"> 
                    <v-toolbar-title>Register</v-toolbar-title>
                </v-toolbar>
                <v-card-text>
                    <v-form ref="form" @submit.prevent="saveForm" lazy-validation>
                        <v-text-field prepend-icon="mdi-account" v-model="user.name" label="Name" :rules="[rules.required]"></v-text-field>
                        <v-text-field v-model="user.email"  prepend-icon="mdi-email"  label="Email" :rules="[rules.required, rules.email]"></v-text-field>                    
                    
                        <v-text-field label="Password" prepend-icon="mdi-lock" v-model="user.password" :rules="[rules.required]" type="password" required></v-text-field>
                        <v-text-field label="Confirm Password" prepend-icon="mdi-lock" v-model="user.password_confirmation" :rules="[(user.password === user.password_confirmation) || 'Password must match']" type="password" required></v-text-field>
                                    
                    </v-form>
                </v-card-text>
                <v-card-actions class="pa-5">
                    <v-btn class="ml-auto" @click="saveForm" outlined color="primary">Register</v-btn>
                </v-card-actions>
              </v-card>
          </v-flex>
        </v-layout>
    </v-container>
</template> -->

<template>
    <v-container>
      <v-row justify="center">
        <v-col cols="12" sm="8" md="6">
          <v-card class="elevation-12">
            <v-toolbar dark color="primary">
              <v-toolbar-title>Login</v-toolbar-title>
            </v-toolbar>
            <v-card-text>
                <v-form ref="form" @submit.prevent="saveForm" lazy-validation>
                        <v-text-field prepend-icon="mdi-account" v-model="user.name" label="Name" :rules="[rules.required]"></v-text-field>
                        <v-text-field v-model="user.email"  prepend-icon="mdi-email"  label="Email" :rules="[rules.required, rules.email]"></v-text-field>                    
                    
                        <v-text-field label="Password" prepend-icon="mdi-lock" v-model="user.password" :rules="[rules.required]" type="password" required></v-text-field>
                        <v-text-field label="Confirm Password" prepend-icon="mdi-lock" v-model="user.password_confirmation" :rules="[(user.password === user.password_confirmation) || 'Password must match']" type="password" required></v-text-field>
                                    
                    </v-form>
            </v-card-text>
            <v-card-actions class="pa-5">
                    
              <v-btn class="ml-auto" @click="saveForm" outlined color="primary">Register</v-btn>
              
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </template>

<script setup>
document.title = 'Admin Registration';
</script>


<script>
  import Auth from '@/auth.js';
    export default {
      data() {
          return {
              user: {
                  name: '',
                  email: '',
                  password: '',
                  password_confirmation:''
              },    

              rules: Rules,
          };
      },

      methods: {
            async saveForm() 
            { 
                /*let token = await this.checkRecaptcha()
                if (!token) 
                {
                    return;
                }  */

                const { valid } = await this.$refs.form.validate();

                if (!valid) return;


                this.axios.post('/api/register', this.user)
                    .then(({data}) => {
                        this.$router.push('/admin/login');
                      
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            }
      }
    }
</script>