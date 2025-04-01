<template>
  
    <div class="list-page ma-5">
      <v-row>
          <v-col cols="">
            <v-row class="no-gutters">
              <v-col cols="" class="d-flex align-center pr-0">
                <div class="text-h4 text-greymain font-weight-bold">New Order</div>
              </v-col>
              <v-col cols="" class="text-right my-auto">

                <router-link to="" class="text-decoration-none">
                    <v-btn class="bg-white text-secondary rounded-lg text-body-2 font-weight-bold" variant="outlined">Preview</v-btn>
                </router-link>
                <router-link to="" class="text-decoration-none">
                    <v-btn rounded="lg" color="success" class="text-body-2 font-weight-bold ms-5 bg-blue">Save as Draft</v-btn>
                </router-link>
                
              </v-col>
            </v-row>
          </v-col>
      </v-row>
  
    <v-row>       
        <v-col cols="7">
            <v-card flat class="pa-6 mt-5 rounded-lg">

                <v-row class="text-greymain">
                    <v-col cols="6" class="pa-4">
                        <v-btn class="pa-10 text-h6 text-greymain bg-secondary_lite border-secondary rounded-lg border-md" size="x-large" variant="outlined" block>
                            <v-avatar color="customer_btn text-white" class="me-5"> <v-icon icon="mdi-account-outline"></v-icon></v-avatar> 
                            Add a Customer
                        </v-btn>
                    </v-col>
                    <v-col cols="6" class="pa-4">
                        <v-btn class="pa-10 text-h6 text-greymain bg-purple_lite border-secondary rounded-lg border-md" size="x-large" variant="outlined" block>
                            <v-avatar color="purple text-white" class="me-5"><v-icon icon="mdi-home-variant-outline"></v-icon></v-avatar>
                            Add a Supplier
                        </v-btn>
                    </v-col>
                    <v-col cols="6">
                        <v-row>
                            <v-col cols="4">PO #</v-col>
                            <v-col cols="8"><VInputTrz></VInputTrz></v-col>
                        </v-row>
                    </v-col>
                    <v-col cols="6">
                        <v-row>
                            <v-col cols="4" >PI #</v-col>
                            <v-col cols="8"><VInputTrz append-inner-icon="mdi-cloud-upload-outline"></VInputTrz></v-col>
                        </v-row>
                    </v-col>

                    <v-col cols="6">
                        <v-row>
                            <v-col cols="4" >Project</v-col>
                            <v-col cols="8"><VSelectTrz></VSelectTrz></v-col>
                        </v-row>
                    </v-col>
                    <v-col cols="6">
                        <v-row>
                            <v-col cols="4" >Terms</v-col>
                            <v-col cols="8"><VSelectTrz></VSelectTrz></v-col>
                        </v-row>
                    </v-col>
                    <v-col cols="6">
                        <v-row>
                            <v-col cols="4" >Spent By</v-col>
                            <v-col cols="8"><VSelectTrz></VSelectTrz></v-col>
                        </v-row>
                    </v-col>
                    <v-col cols="6">
                        <v-row>
                            <v-col cols="4" >Adv. Amt.</v-col>
                            <v-col cols="8"><VInputTrz></VInputTrz></v-col>
                        </v-row>
                    </v-col>
                    <v-col class="text-right"><v-btn class="text-none text-primary" prepend-icon="mdi-plus" variant="text">Add more Adv. Amt.</v-btn></v-col>
                    
                </v-row>
            </v-card>      
        </v-col>
        
        <v-col cols="5">
            <v-card flat class="pa-6 mt-5 rounded-lg">
                <v-row class="text-greymain">
                    <v-col cols="4" >Order #</v-col>
                    <v-col cols="8" ><VInputTrz></VInputTrz></v-col>

                    <v-col cols="4">Closed Date</v-col>
                    <v-col cols="8" ><VInputTrz type="date"></VInputTrz></v-col>

                    <v-col cols="4" >Rep</v-col>
                    <v-col cols="8" ><VSelectTrz></VSelectTrz></v-col>

                    <v-col cols="4" >Department</v-col>
                    <v-col cols="8" ><VSelectTrz></VSelectTrz></v-col>

                    <v-col cols="4" >Status</v-col>
                    <v-col cols="8" ><VSelectTrz></VSelectTrz></v-col>
                </v-row>

            </v-card>
            
        </v-col>

        <v-col cols="12">
            <v-card flat class="pa-6 mt-5 rounded-lg">
                <v-row class="text-greymain">
                    
                    <v-col cols="">
                        <v-row>
                            <v-col cols="" >Shipping</v-col>
                            <v-col cols="2"><VSelectTrz></VSelectTrz></v-col>
                            <v-col cols="" >Port</v-col>
                            <v-col cols="2"><VSelectTrz></VSelectTrz></v-col>
                            <v-col cols="" >ETD</v-col>
                            <v-col cols="2"><VInputTrz type="date"></VInputTrz></v-col>
                            <v-col cols="" >Transport</v-col>
                            <v-col cols="1"><VSelectTrz></VSelectTrz></v-col>
                            <v-col cols="1"><VInputTrz></VInputTrz></v-col>
                            <v-col cols="1">
                                <v-btn class="text-secondary bg-secondary_lite" variant="raised" size="small">
                                    <v-icon icon="mdi-dots-horizontal" class="v-card-icon text-h4"></v-icon>
                                </v-btn>
                            </v-col>
                            
                        </v-row>
                    </v-col>  
                </v-row>
            </v-card>
        </v-col>
        <v-col cols="12">
            <v-card flat class="pa-6 mt-5 rounded-lg">
                <v-row class="mb-4">
                    <v-col cols="1" class="d-flex align-center pr-0">
                        <div class="text-h6 text-greymain font-weight-bold">Item</div>
                    </v-col>
                    <v-col cols="" class="my-auto">
                        <v-btn rounded="md" color="secondary" class="text-body-2 font-weight-medium ms-5" size="small" prepend-icon="mdi-plus"  >Add New</v-btn>
                    </v-col>
                </v-row>

                <v-row class="text-greyheader">
                    <v-col>Product / Service</v-col>
                    <v-col>Sub-Category</v-col>
                    <v-col>Description</v-col>
                    <v-col cols="1">Unit</v-col>
                    <v-col cols="2">Quantity</v-col>
                    <v-col cols="2">Value</v-col>
                    <v-col cols="1"></v-col>
                </v-row>
                <v-row>
                    <v-col><hr></v-col>
                </v-row>
                <v-row class="text-greymain">
                    <v-col><VSelectTrz></VSelectTrz></v-col>
                    <v-col><VSelectTrz></VSelectTrz></v-col>
                    <v-col><VInputTrz></VInputTrz></v-col>
                    <v-col cols="1"><VSelectTrz></VSelectTrz></v-col>
                    <v-col cols="2"><VSelectTrz></VSelectTrz></v-col>
                    <v-col cols="2"><VInputTrz></VInputTrz></v-col>
                    <v-col cols="1" class="text-center"> <v-icon icon="mdi-window-close" class="v-card-icon" ></v-icon></v-col>
                </v-row>
                <v-row class="text-greymain">
                    <v-col><VSelectTrz></VSelectTrz></v-col>
                    <v-col><VSelectTrz></VSelectTrz></v-col>
                    <v-col><VInputTrz></VInputTrz></v-col>
                    <v-col cols="1"><VSelectTrz></VSelectTrz></v-col>
                    <v-col cols="2"><VSelectTrz></VSelectTrz></v-col>
                    <v-col cols="2"><VInputTrz></VInputTrz></v-col>
                    <v-col cols="1" class="text-center"> <v-icon icon="mdi-window-close" class="v-card-icon" ></v-icon></v-col>
                    
                </v-row>
                <v-row class="text-greymain">
                    <v-col><VSelectTrz></VSelectTrz></v-col>
                    <v-col><VSelectTrz></VSelectTrz></v-col>
                    <v-col><VInputTrz></VInputTrz></v-col>
                    <v-col cols="1"><VSelectTrz></VSelectTrz></v-col>
                    <v-col cols="2"><VSelectTrz></VSelectTrz></v-col>
                    <v-col cols="2"><VInputTrz></VInputTrz></v-col>
                    <v-col cols="1" class="text-center"> <v-icon icon="mdi-window-close" class="v-card-icon" ></v-icon></v-col>
                </v-row>
                
                <v-row class="text-greymain">
                    <v-col ><hr></v-col>
                </v-row>
                <v-row class="text-greymain">
                    <v-col cols="">% Total</v-col>
                    <v-col cols="2"><VInputTrz></VInputTrz></v-col>
                    <v-col cols="">Commission</v-col>
                    <v-col cols="2"><VInputTrz></VInputTrz></v-col>
                    <v-col cols="">Rate</v-col>
                    <v-col cols="2"><VInputTrz></VInputTrz></v-col>
                    <v-col cols="">Total</v-col>
                    <v-col cols="1"><VSelectTrz></VSelectTrz></v-col>
                    
                    <v-col cols="1"><VInputTrz></VInputTrz></v-col>
                    <v-col cols="1"></v-col>                    
                </v-row>
            </v-card>
        </v-col>
      </v-row>
      
      <v-row class="mt-5 mx-16">
            <v-col class="text-center text-h6 text-secondary font-weight-bold">Notes</v-col>
            <v-col class="text-center text-body-1 text-greyheader font-weight-bold">|</v-col>
            <v-col class="text-center text-h6 text-secondary font-weight-bold">Files</v-col>
            <v-col class="text-center text-body-1 text-greyheader font-weight-bold">|</v-col>
               
            <v-col class="text-center text-h6 text-secondary font-weight-bold">Shipment</v-col>
            <v-col class="text-center text-body-1 text-greyheader font-weight-bold">|</v-col>
            <v-col class="text-center text-h6 text-secondary font-weight-bold">Payment</v-col>
            <v-col class="text-center text-body-1 text-greyheader font-weight-bold">|</v-col>
               
            <v-col class="text-center text-h6 text-secondary font-weight-bold">Commission</v-col>
            <v-col class="text-center text-body-1 text-greyheader font-weight-bold">|</v-col>
             
            <v-col class="text-center text-h6 text-secondary font-weight-bold">Documents</v-col>
            <v-col class="text-center text-body-1 text-greyheader font-weight-bold">|</v-col>
            <v-col class="text-center text-h6 text-secondary font-weight-bold">Query</v-col>
                
        </v-row>
    </div>
    
  </template>
  <script setup>
   document.title = "Order Form";
  </script>
  
  <script>
    
      export default {
  
        data() {
          
            return {}
        },

        methods: {},

        watch: {
            loading (val) {
              if (!val) return
              setTimeout(() => (this.loading = false), 2000)
            },
        },
        created() {},

        computed: {},
          
    }
  </script>