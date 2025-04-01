 <template>
  <v-container>
    <v-row justify="center">
      <v-col cols="12" sm="8" md="6">
        <v-card class="elevation-12">
          <v-toolbar dark color="primary">
            <v-toolbar-title>Login</v-toolbar-title>
          </v-toolbar>
          <v-card-text>
            <v-form ref="form"  lazy-validation>
                <v-text-field prepend-icon="mdi-email" v-model="user.email" label="Email" :rules="[rules.required, rules.email]"></v-text-field>
                <v-text-field prepend-icon="mdi-lock" type="password" v-model="user.password" label="Password" :rules="[rules.required]"></v-text-field>
            </v-form>
          </v-card-text>
          <v-card-actions class="pa-5">
                  
            <v-btn class="ml-auto" @click="saveForm" outlined color="primary">Login</v-btn>
            
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>


<script setup>
document.title = 'Admin Login';
</script>


<script>
  import Auth from '@/auth.js';
  import { ref } from 'vue';
  const theme = ref('light');

    export default {
      data() {
          return {
              value:String,
              user: {
                  email: '',
                  password: '',
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


              this.axios.post('/api/login', this.user)
                  .then(({data}) => {
                      Auth.login(data.access_token,data.user);

                      this.$router.push('/admin');


                      
                  })
                  .catch((error) => {
                      alert('wrong access');
                  });
            }
      }
    }
</script>