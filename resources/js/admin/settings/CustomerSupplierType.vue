<template>    
    <v-row>
        <v-col>
            <v-card flat min-height=300 class="mt-10">
                <v-card-title class="pa-6">
                    <span class="semibold-24">Customer Type</span>
                </v-card-title>
                <v-card-text>    
                    <v-form ref="customerForm" @submit.prevent="saveCustomerForm" lazy-validation>
                        <v-row>
                            <v-col cols="12" md="8">
                                <v-text-field v-model="customerFormData.title" label="Title" clearable variant="outlined" :rules="[rules.required]"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-btn @click="saveCustomerForm" prepend-icon="mdi-plus" outlined color="primary" class='text-none' v-if="!customerFormData.id"> Add </v-btn>
                                <v-btn @click="saveCustomerForm" outlined color="primary" class='text-none' v-else> Save </v-btn>
                            </v-col>
                        </v-row>

                    </v-form>

        
                    <div v-for="item in countries" :key="item.id" class="clearfix float-none">

                        <v-row>
                            <v-col cols="12" md="6">
                                <span>{{ item.title }}</span>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-icon icon="mdi-pencil" class="" @click="editCustomerItem(item)"></v-icon> 
                                <v-icon icon="mdi-close" class="ml-3" @click="deleteCustomerItem(item.id)"></v-icon> 
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>
            </v-card>

        </v-col>
        <v-col>
            <v-card flat min-height=300 class="mt-10">
                <v-card-title class="pa-6">
                    <span class="semibold-24">Supplier Type</span>
                </v-card-title>
                <v-card-text>    
                    <v-form ref="supplierForm" @submit.prevent="saveSupplierForm" lazy-validation>
                        <v-row>
                            <v-col cols="12" md="8">
                                <v-text-field v-model="supplierformData.title" label="Title" clearable variant="outlined" :rules="[rules.required]"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-btn @click="saveSupplierForm" prepend-icon="mdi-plus" outlined color="primary" class='text-none' v-if="!supplierformData.id"> Add </v-btn>
                                <v-btn @click="saveSupplierForm" outlined color="primary" class='text-none' v-else> Save </v-btn>
                                
                            </v-col>
                        </v-row>
                    </v-form>
        
                    <div v-for="item in supplierTerms" :key="item.id" class="clearfix float-none">
                        <v-row>
                            <v-col cols="12" md="6">
                                <span>{{ item.title }}</span>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-icon icon="mdi-pencil" class="" @click="editSupplierItem(item)"></v-icon> 
                                <v-icon icon="mdi-close" class="ml-3" @click="deleteSupplierItem(item.id)"></v-icon> 
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>
            </v-card>

        </v-col>

    </v-row>
    <v-dialog  v-model="deleteDialog" :max-width="600" >
        <v-card>
            <v-toolbar color="white" dense flat height="40">
                <div class="mt-2 ml-auto mr-3">
                    <v-icon icon="mdi-close" @click.native="cancel" class="pa-3"></v-icon>
                </div>
            </v-toolbar>
            <v-card-text class="pa-4 text-center font-weight-bold">Are you sure you want to delete this?</v-card-text>
                <div class="py-3 text-center">
                    <v-btn color="secondary" class="font-weight-bold" elevation=0 @click.native="agree">Delete</v-btn>
                    <v-btn color="white" class="ml-3 font-weight-bold text-secondary" style='border: 1px solid #001659' elevation=0 border outlined @click.native="cancel"> <span class="text-secondary">Cancel</span></v-btn>
                </div>
        </v-card>
    </v-dialog>
</template>
<script setup>
</script>
<script>

    export default {
        data() {
            return {               
                customerFormData : {id:null, title:null, status:1},    
                supplierformData : {id:null, title:null, status:1},    
                rules : Rules,
                countries : [],
                supplierTerms : [],
                deleteDialog: false,   
                deletedCustomerId : null,      
                deletedSupplierId : null,  
            }
        },
        methods: {
            agree() {
                this.deleteDialog = false;

                if(this.deletedCustomerId){
                    this.axios
                        .delete(`/api/settings/customer-types/${this.deletedCustomerId}`)
                        .then(response => {
                            this.allCustomerItem();
                            this.deletedCustomerId = null; 
                            this.customerFormData = {id:null, title:null, status:1}   
                        }); 
                } 
                if(this.deletedSupplierId){
                    this.axios
                        .delete(`/api/settings/supplier-types/${this.deletedSupplierId}`)
                        .then(response => {
                            this.allSupplierItem();
                            this.deletedSupplierId = null; 
                            this.supplierformData = {id:null, title:null, status:1}   
                        }); 
                } 
                this.paymentFormData = {id:null, title:null, status:1}
                this.deliveryformData = {id:null, title:null, status:1}
            },
            cancel() {
                this.deleteDialog = false;
                this.deletedCustomerId = null; 
                this.deletedSupplierId = null; 
            },
            editCustomerItem(item) {
                this.customerFormData = item;
                this.allSupplierItem(this.customerFormData.id)
            },
            editSupplierItem(item) {
                this.supplierformData = item;
            },
            deleteSupplierItem(id) {                
                this.deleteDialog = true;  
                this.deletedSupplierId = id; 
            }, 
            deleteCustomerItem(id) {                
                this.deleteDialog = true;  
                this.deletedCustomerId = id; 
            },             
            allCustomerItem(){
                this.axios
                    .get('/api/settings/customer-types')
                    .then(response => {
                        this.countries = response.data;
                    });
            },
            allSupplierItem(){
                this.axios.get('/api/settings/supplier-types')
                    .then(response => {
                        this.supplierTerms = response.data;
                        console.log(response.data)
                    });
            },

            async saveCustomerForm() {
                
                const { valid } = await this.$refs.customerForm.validate();
                if (valid)
                {
                    let call_url = '/api/settings/customer-types';

                    if(this.customerFormData.id != null)
                    {
                        call_url = call_url + '/' + this.customerFormData.id+'?_method=PUT';
                    }

                    this.axios
                        .post(call_url , this.customerFormData)
                        .then(response => {

                            if(response)
                            {
                                this.allCustomerItem();
                                this.customerFormData = {id:null, title:null, status: 1};
                                this.showSuccess('Item Saved')
                            }
                            
                        });
                }
            },
            async saveSupplierForm() {
                
                const { valid } = await this.$refs.supplierForm.validate();

                if (valid)
                {
                    let call_url = '/api/settings/supplier-types';

                    if(this.supplierformData.id != null)
                    {
                        call_url = call_url + '/' + this.supplierformData.id+'?_method=PUT';
                    }

                    this.axios
                        .post(call_url , this.supplierformData)
                        .then(response => {

                            if(response)
                            {
                                this.allSupplierItem(this.customerFormData.id);
                                this.supplierformData = {id:null, title:null, status: 1};
                                this.showSuccess('Item Saved')
                            }
                            
                        });
                }
            },
        },
        created() {            
            this.allCustomerItem();    
            this.allSupplierItem();        
        },
        
    }
</script>