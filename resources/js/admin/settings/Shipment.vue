<template>    
    <v-row>
        <v-col>
            <v-card flat class="mt-10" min-height=300>
                <v-card-title class="pa-6">
                    <span class="semibold-24">Shipping Line/Forwarder</span>
                </v-card-title>
                <v-card-text>    
                    <v-form ref="shippingLineForm" @submit.prevent="saveShippingLineForm" lazy-validation>
                        <v-row>
                            <v-col cols="12" md="8">
                                <v-text-field v-model="shippingLineFormData.title" label="Title" clearable variant="outlined" :rules="[rules.required]"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-btn @click="saveShippingLineForm" prepend-icon="mdi-plus" outlined color="primary" class='text-none' v-if="!shippingLineFormData.id"> Add </v-btn>
                                <v-btn @click="saveShippingLineForm" outlined color="primary" class='text-none' v-else> Save </v-btn>
                            </v-col>
                        </v-row>

                    </v-form>

        
                    <div v-for="item in countries" :key="item.id" class="clearfix float-none">

                        <v-row>
                            <v-col cols="12" md="6">
                                <span>{{ item.title }}</span>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-icon icon="mdi-pencil" class="" @click="editShippingLineItem(item)"></v-icon> 
                                <v-icon icon="mdi-close" class="ml-3" @click="deleteShippingLineItem(item.id)"></v-icon> 
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>
            </v-card>

        </v-col>
        <v-col>
            <v-card flat class="mt-10" min-height=300>
                <v-card-title class="pa-6">
                    <span class="semibold-24">Port {{ shippingLineFormData.id ? '/ ' : '' }}<span class="text-primary"> {{ shippingLineFormData.id ? shippingLineFormData.title : '' }} </span></span>
                </v-card-title>
                <v-card-text v-if="shippingLineFormData.id">    
                    <v-form ref="portForm" @submit.prevent="savePortForm" lazy-validation>
                        <v-row>
                            <v-col cols="12" md="8">
                                <v-text-field v-model="portformData.title" label="Title" clearable variant="outlined" :rules="[rules.required]"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-btn @click="savePortForm" prepend-icon="mdi-plus" outlined color="primary" class='text-none' v-if="!portformData.id"> Add </v-btn>
                                <v-btn @click="savePortForm" outlined color="primary" class='text-none' v-else> Save </v-btn>
                            </v-col>
                        </v-row>
                    </v-form>
        
                    <div v-for="item in ports" :key="item.id" class="clearfix float-none">
                        <v-row>
                            <v-col cols="12" md="6">
                                <span>{{ item.title }}</span>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-icon icon="mdi-pencil" class="" @click="editPortItem(item)"></v-icon> 
                                <v-icon icon="mdi-close" class="ml-3" @click="deletePortItem(item.id)"></v-icon> 
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
                shippingLineFormData : {id:null, title:null, status:1},    
                portformData : {id:null, title:null, status:1},    
                rules : Rules,
                countries : [],
                ports : [],
                deleteDialog: false,   
                deletedLineId : null,      
                deletedPortId : null,        
            }
        },
        methods: {
            agree() {
                this.deleteDialog = false;
                if(this.deletedLineId){
                    this.axios
                        .delete(`/api/settings/shipping-line/${this.deletedLineId}`)
                        .then(response => {
                            this.allShippingLineItem();
                            this.deletedLineId = null; 
                            this.shippingLineFormData = {id:null, title:null, status:1}
                        }); 
                } 
                if(this.deletedPortId){
                    this.axios
                        .delete(`/api/settings/port/${this.deletedPortId}`)
                        .then(response => {
                            this.allPortItem(this.shippingLineFormData.id);
                            this.deletedPortId = null; 
                            this.portformData = {id:null, title:null, status:1} 
                        }); 
                } 
                
                
            },
            cancel() {
                this.deleteDialog = false;
                this.deletedPortId = null; 
                this.deletedLineId = null; 
            },
            editShippingLineItem(item) {
                this.shippingLineFormData = item;
                this.allPortItem(this.shippingLineFormData.id)
                this.portformData.shipping_line_id = this.shippingLineFormData.id;
            },
            editPortItem(item) {
                this.portformData = item;
                this.portformData.shipping_line_id = this.shippingLineFormData.id;
            },
            deletePortItem(id) {                
                // this.axios
                //     .delete(`/api/settings/port/${id}`)
                //     .then(response => {
                //         this.allPortItem(this.shippingLineFormData.id);
                //     });
                this.deleteDialog = true;  
                this.deletedPortId = id; 
            }, 
            deleteShippingLineItem(id) {                
                // this.axios
                //     .delete(`/api/settings/shipping-line/${id}`)
                //     .then(response => {
                //         this.allShippingLineItem();
                //     });
                this.deleteDialog = true;  
                this.deletedLineId = id; 
            },             
            allShippingLineItem(){
                this.axios
                    .get('/api/settings/shipping-line')
                    .then(response => {
                        this.countries = response.data;
                        console.log(response.data)
                    });
            },
            allPortItem(shippingLineId){
                this.axios.get('/api/settings/portListByShippingLine',{params: { shippingLineId: shippingLineId }})
                    .then(response => {
                        this.ports = response.data;
                    });
            },

            async saveShippingLineForm() {
                
                const { valid } = await this.$refs.shippingLineForm.validate();
                if (valid)
                {
                    let call_url = '/api/settings/shipping-line';

                    if(this.shippingLineFormData.id != null)
                    {
                        call_url = call_url + '/' + this.shippingLineFormData.id+'?_method=PUT';
                    }

                    this.axios
                        .post(call_url , this.shippingLineFormData)
                        .then(response => {

                            if(response)
                            {
                                this.allShippingLineItem();
                                this.shippingLineFormData = {id:null, title:null, status: 1};
                                this.showSuccess('Item Saved')
                            }
                            
                        });
                }
            },
            async savePortForm() {
                
                const { valid } = await this.$refs.portForm.validate();

                if (valid)
                {
                    let call_url = '/api/settings/port';

                    if(this.portformData.id != null)
                    {
                        call_url = call_url + '/' + this.portformData.id+'?_method=PUT';
                    }

                    this.axios
                        .post(call_url , this.portformData)
                        .then(response => {

                            if(response)
                            {
                                this.allPortItem(this.shippingLineFormData.id);
                                this.portformData = {id:null, title:null, status: 1 , shipping_line_id : this.shippingLineFormData.id};
                                this.showSuccess('Item Saved')
                            }
                            
                        });
                }
            },
        },
        created() {            
            this.allShippingLineItem();            
        },
        
    }
</script>