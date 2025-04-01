<template>
  <div class="list-page">
    <v-row>
        <v-col cols="4">
          <v-row class="no-gutters">
            <v-col cols="4" class="d-flex align-center pr-0">
              <div class="text-h5 text-greymain font-weight-medium ">My List</div>
            </v-col>
            <v-col cols="6" class="d-flex align-center pl-0">
              <router-link
              to="/dash-2"
                class="text-decoration-none"
              >
              <v-btn 
                prepend-icon="mdi-plus"  elevation="3"
                :disabled="loading"
                :loading="loading"
                rounded="lg"
                color="primary"
                block
                @click="loading = !loading"
                >Create New
              </v-btn>
              </router-link>
            </v-col>
          </v-row>
        </v-col>
    </v-row>


    <v-row>
        <v-col>
          <v-card flat class="">
          <v-card-text>
            <v-row>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">New</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">In Production</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">For Delivery</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">In Transit</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">Payment</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">Documents</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">Shipment</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-medium">Complaint</v-col>
            </v-row>
            <v-row>
              <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
              <v-col class="text-center text-body-1 text-success font-weight-bold">200</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
              <v-col class="text-center text-body-1 text-greymain font-weight-bold">01</v-col>
              <v-col class="text-center text-body-1 text-orange font-weight-bold">02</v-col>
            </v-row>
          </v-card-text>
          </v-card>
          <v-card flat class="pa-6 mt-5">
            <v-row>
              <v-col cols="3">
                  <v-text-field
                      clearable
                      variant="outlined"
                      density="compact"
                      class="rounded"
                      placeholder="Search 1"
                  ></v-text-field>
              </v-col>
              <v-col cols="2">
                
                  <v-select
                      density="compact"
                      variant="outlined"
                      placeholder="Select 1"
                      v-model="country"
                      :items="countryOption"
                      item-title="text"
                      item-value="id"
                      class="rounded"
                  ></v-select>
              </v-col>
              <v-col cols="2">
                
                  <v-select
                      density="compact"
                      variant="outlined"
                      placeholder="Select 2"
                      v-model="supplierTypes"
                      :items="supplierOption"
                      item-title="text"
                      class="rounded"
                      item-value="id"
                  ></v-select>
              </v-col>
              <v-col cols="2">
                <v-text-field
                    clearable
                    variant="outlined"
                    density="compact"
                    class="rounded"
                    :v-model="inputModel"
                    placeholder="Search 2"
                >
                </v-text-field>
              </v-col>
              <v-col
              cols="12"
              sm="6"
              md="4"
              >
              <v-menu
                ref="menu"
                v-model="menu"
                :close-on-content-click="false"
                :return-value.sync="date"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="date"
                    label="Picker in menu"
                    prepend-icon="mdi-calendar"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="date"
                  no-title
                  scrollable
                >
                  <v-spacer></v-spacer>
                  <v-btn
                    text
                    color="primary"
                    @click="menu = false"
                  >
                    Cancel
                  </v-btn>
                  <v-btn
                    text
                    color="primary"
                    @click="$refs.menu.save(date)"
                  >
                    OK
                  </v-btn>
                </v-date-picker>
              </v-menu>
            </v-col>
              
            </v-row>
            <v-row>
              <v-col cols="4" class="my-auto text-right">
                  <v-btn
                      icon="mdi-share-outline"
                      class="text-secondary bg-secondary_lite"
                      variant="text"
                  ></v-btn>
                  <v-btn
                      icon="mdi-tray-arrow-down"
                      class="ms-5 text-secondary bg-secondary_lite"
                      variant="text"
                  ></v-btn>
              </v-col>
            </v-row>

          </v-card>
          </v-col>
    </v-row>
  </div>
</template>
<script setup>
 
</script>

<script>
  
    export default {

      data() {
        
          return {   
            loading: false,
            countryOption:[{text:'Select 1',id:0},{text:'Option 1',id:1},{text:'Option 2',id:2},{text:'Option 3',id:3}],
            supplierOption:[{text:'Select 2',id:0},{text:'Option 1',id:1},{text:'Option 2',id:2},{text:'Option 3',id:3}],
            supplierTypes:0,
            country:0, 
            inputModel:'',
            menu: false,
            date: (new Date(Date.now() - (new Date()).getTimezoneOffset() * 60000)).toISOString().substr(0, 10),
          }
      },
        methods: {         
          
                
          
            
        },
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