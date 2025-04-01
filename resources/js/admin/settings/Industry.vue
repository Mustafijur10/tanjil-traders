<template>    
    <v-card flat min-height=300 class="mt-10">
        <v-card-title class="pa-6">
            <span class="semibold-24">Industry</span>
        </v-card-title>
        <v-card-text>    
            <v-form ref="form" @submit.prevent="saveForm" lazy-validation>
                <v-row>
                    <v-col cols="4">
                        <v-text-field v-model="formData.title" label="Title" clearable variant="outlined" :rules="[rules.required]"></v-text-field>
                    </v-col>
                    <v-col cols="4">
                        <v-btn @click="saveForm" prepend-icon="mdi-plus" outlined color="primary" class='text-none' v-if="!formData.id"> Add </v-btn>
                        <v-btn @click="saveForm" outlined color="primary" class='text-none' v-else> Save </v-btn>
                    </v-col>
                </v-row>

            </v-form>

 
            <div v-for="item in salesType" :key="item.id" class="clearfix float-none">

                <v-row>
                    <v-col cols="4">
                        <span>{{ item.title }}</span>
                    </v-col>
                    <v-col cols="4">
                        <v-icon icon="mdi-pencil" class="" @click="editItem(item)"></v-icon> 
                        <v-icon icon="mdi-close" class="ml-3" @click="deleteItem(item.id)"></v-icon> 
                    </v-col>
                </v-row>
            </div>

        </v-card-text>
    </v-card>

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
                formData : {id:null, title:null, status:1},    
                rules : Rules,
                salesType : [],
                deleteDialog: false,   
                deletedId : null,   
                
                
            }
        },
        methods: {
            agree() {
                this.deleteDialog = false;
                this.axios
                    .delete(`/api/settings/industry/${this.deletedId}`)
                    .then(response => {
                        this.allItem();
                        this.deletedId = null; 
                    }); 
                    this.formData = {id:null, title:null, status: 1};
            },
            cancel() {
                this.deleteDialog = false;
            },
            
            addNew() {
                this.formData = {id:null, title:null, status: 1};
            },
            editItem(item) {
                this.formData = item;
            },
            deleteItem(id) {                
                this.deleteDialog = true;    
                this.deletedId = id; 
            },            
            allItem(){
                this.axios
                    .get('/api/settings/industry')
                    .then(response => {
                        this.salesType = response.data;
                    });
            },

            async saveForm() {
                
                const { valid } = await this.$refs.form.validate();

                if (valid)
                {
                    let call_url = '/api/settings/industry';

                    if(this.formData.id != null)
                    {
                        call_url = call_url + '/' + this.formData.id+'?_method=PUT';
                    }

                    this.axios
                        .post(call_url , this.formData)
                        .then(response => {

                            if(response)
                            {
                                this.allItem();
                                this.formData = {id:null, title:null, status: 1};
                                this.showSuccess('Item Saved')
                            }
                            
                        });
                }
            },
        },
        created() {            
            this.allItem();            
        },
        
    }
</script>