<template>    
    <v-row>
        <v-col>
            <v-card flat class="mt-10" min-height=300>
                <v-card-title class="pa-6">
                    <span class="semibold-24">Category</span>
                </v-card-title>
                <v-card-text>    
                    <v-form ref="categoryForm" @submit.prevent="saveCategoryForm" lazy-validation>
                        <v-row>
                            <v-col cols="12" md="8">
                                <v-text-field v-model="categoryFormData.title" label="Title" clearable variant="outlined" :rules="[rules.required]"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-btn @click="saveCategoryForm" prepend-icon="mdi-plus" outlined color="primary" class='text-none' v-if="!categoryFormData.id"> Add </v-btn>
                                <v-btn @click="saveCategoryForm" outlined color="primary" class='text-none' v-else> Save </v-btn>
                            </v-col>
                        </v-row>

                    </v-form>

        
                    <div v-for="item in countries" :key="item.id" class="clearfix float-none">

                        <v-row>
                            <v-col cols="12" md="6">
                                <span>{{ item.title }}</span>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-icon icon="mdi-pencil" class="" @click="editCategoryItem(item)"></v-icon> 
                                <v-icon icon="mdi-close" class="ml-3" @click="deleteCategoryItem(item.id)"></v-icon> 
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>
            </v-card>

        </v-col>
        <v-col>
            <v-card flat class="mt-10" min-height=300>
                <v-card-title class="pa-6">
                    <span class="semibold-24">Sub Category {{ categoryFormData.id ? '/ ' : '' }}<span class="text-primary"> {{ categoryFormData.id ? categoryFormData.title : '' }} </span></span>
                </v-card-title>
                <v-card-text v-if="categoryFormData.id">    
                    <v-form ref="subCategoryForm" @submit.prevent="saveSubCategoryForm" lazy-validation>
                        <v-row>
                            <v-col cols="12" md="8">
                                <v-text-field v-model="subCategoryformData.title" label="Title" clearable variant="outlined" :rules="[rules.required]"></v-text-field>
                            </v-col>
                            <v-col cols="12" md="4">
                                <v-btn @click="saveSubCategoryForm" prepend-icon="mdi-plus" outlined color="primary" class='text-none' v-if="!subCategoryformData.id"> Add </v-btn>
                                <v-btn @click="saveSubCategoryForm" outlined color="primary" class='text-none' v-else> Save </v-btn>                                
                            </v-col>
                        </v-row>
                    </v-form>
        
                    <div v-for="item in subCategorys" :key="item.id" class="clearfix float-none">
                        <v-row>
                            <v-col cols="12" md="6">
                                <span>{{ item.title }}</span>
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-icon icon="mdi-pencil" class="" @click="editSubCategoryItem(item)"></v-icon> 
                                <v-icon icon="mdi-close" class="ml-3" @click="deleteSubCategoryItem(item.id)"></v-icon> 
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
                    <v-btn color="white" class="ml-3 font-weight-bold text-secondary" style="border: 1px solid #001659" elevation=0 border outlined @click.native="cancel"> <span class="text-secondary">Cancel</span></v-btn>
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
                categoryFormData : {id:null,parent_id:0, title:null, status:1},    
                subCategoryformData : {id:null, title:null, status:1},    
                rules : Rules,
                countries : [],
                subCategorys : [],
                deleteDialog: false,   
                deletedId : null,   
            }
        },
        methods: {
            agree() {
                this.deleteDialog = false;
                this.axios
                    .delete(`/api/settings/category/${this.deletedId}`)
                    .then(response => {
                        if(this.categoryFormData.id){
                            this.allSubCategoryItem(this.categoryFormData.id);
                        }
                        this.allCategoryItem();
                        this.deletedId = null; 
                    });  
            },
            cancel() {
                this.deleteDialog = false;
            },
            editCategoryItem(item) {
                this.categoryFormData = item;
                this.allSubCategoryItem(this.categoryFormData.id)
                this.subCategoryformData.parent_id = this.categoryFormData.id;
            },
            editSubCategoryItem(item) {
                this.subCategoryformData = item;
                this.subCategoryformData.parent_id = this.categoryFormData.id;
            },
            deleteSubCategoryItem(id) {                
                this.deleteDialog = true;    
                this.deletedId = id; 
            }, 
            deleteCategoryItem(id) {                
                this.deleteDialog = true;    
                this.deletedId = id; 
            },             
            allCategoryItem(){
                this.axios
                    .get('/api/settings/parent-category')
                    .then(response => {
                        this.countries = response.data;
                    });
            },
            allSubCategoryItem(categoryId){
                this.axios.get('/api/settings/sub-category',{params: { categoryId: categoryId }})
                    .then(response => {
                        this.subCategorys = response.data;
                    });
            },

            async saveCategoryForm() {
                
                const { valid } = await this.$refs.categoryForm.validate();
                if (valid)
                {
                    let call_url = '/api/settings/category';

                    if(this.categoryFormData.id != null)
                    {
                        call_url = call_url + '/' + this.categoryFormData.id+'?_method=PUT';
                    }
                    this.categoryFormData.parent_id = 0

                    this.axios
                        .post(call_url , this.categoryFormData)
                        .then(response => {

                            if(response)
                            {
                                this.allCategoryItem();
                                this.categoryFormData = {id:null, title:null, status: 1};
                                this.showSuccess('Item Saved')
                            }
                            
                        });
                }
            },
            async saveSubCategoryForm() {
                
                const { valid } = await this.$refs.subCategoryForm.validate();

                if (valid)
                {
                    let call_url = '/api/settings/category';

                    if(this.subCategoryformData.id != null)
                    {
                        call_url = call_url + '/' + this.subCategoryformData.id+'?_method=PUT';
                    }

                    this.axios
                        .post(call_url , this.subCategoryformData)
                        .then(response => {

                            if(response)
                            {
                                this.allSubCategoryItem(this.categoryFormData.id);
                                this.subCategoryformData = {id:null, title:null, status: 1 , parent_id : this.categoryFormData.id};
                                this.showSuccess('Item Saved')
                            }
                            
                        });
                }
            },
        },
        created() {            
            this.allCategoryItem();            
        },
        
    }
</script>