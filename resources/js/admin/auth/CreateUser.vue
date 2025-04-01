<template>  
    <v-container class='fluid fill-height '>
        
            <v-card class="" v-if="can_register == true">
                <v-card-text>
                    <v-form ref="form" @submit.prevent="saveForm" lazy-validation>
                        <v-text-field prepend-icon="mdi-account" v-model="user.name" label="Name" :rules="[rules.required]"></v-text-field>
                        <v-text-field v-model="user.email"  prepend-icon="mdi-email"  label="Email" :rules="[rules.required, rules.email]"></v-text-field>                    
                    
                        <v-text-field label="Password" prepend-icon="mdi-lock" v-model="user.password" :rules="[rules.required]" type="password" required></v-text-field>
                        <v-text-field label="Confirm Password" prepend-icon="mdi-lock" v-model="user.password_confirmation" :rules="[(user.password === user.password_confirmation) || 'Password must match']" type="password" required></v-text-field>
                                    
                    </v-form>
                </v-card-text>
                <v-card-actions class="pa-5">
                    <v-btn class="ml-auto" @click="saveForm" outlined color="primary">Create User</v-btn>
                </v-card-actions>
            </v-card>
          
        
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

              can_register : false,
          };
      },

      methods: {
            async saveForm() 
            { 

                const { valid } = await this.$refs.form.validate();

                if (!valid) return;

                this.axios.post('/api/createuser', this.user)
                    .then(({data}) => {
                        
                        this.showSuccess('User Created');
                        this.$refs.form.reset();
                      
                    })
                    .catch((error) => {
                    });
            }
        },
        created() {          
            
                if(Auth.user.role == 1)           
                    this.can_register = true;
        },
    }
</script>