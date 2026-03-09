<template> 
    <div >
        <v-window v-model="tab">
            <v-window-item value="attribute" lazy>
                <v-container class="ml-0 pa-0">
                    <div class="trz_trz">
                        <v-container class="pa-0">
                            <v-row no-gutters>
                                <v-col cols="3">
                                    <v-text-field v-model="searchValue" label="Search.." clearable variant="outlined" 
                                        class="mt-4 text-black opacity-0" density="compact" @click:clear="searchValue = ''">
                                    </v-text-field>
                                </v-col>
                                <v-col cols="3" align="right" offset="6" class="mt-4">
                                    <v-btn class="backgrond-color-teal" @click="addNew">
                                        <v-icon class="mr-2">fa fa-plus</v-icon>
                                        Add New
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-container>
                    </div>

                    <EasyDataTable
                        :headers="headers"
                        :items="items"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="25"
                        :fixedIndex="true"
                        :search-value="searchValue"
                    >
                        <template #item-title="item">
                            <v-hover>
                                <template v-slot:default="{ isHovering, props }">
                                    <div class="cursor-pointer" v-bind="props">{{ item.title }}</div>
                                    <div v-bind="props" v-if="isHovering">
                                        <span @click.prevent="editItem(item)" class="cursor-pointer">Edit</span>
                                        |
                                        <span @click.prevent="viewModels(item)" class="cursor-pointer">View Models</span>
                                    </div>
                                </template>
                            </v-hover>
                        </template>

                        <template #item-operation="item">
                            <div class="">
                                <v-btn class="mr-2 backgrond-color-teal" size="x-small" icon="mdi-pencil" @click.prevent="editItem(item)" />
                                <v-btn class="backgrond-color-teal" size="x-small" icon="mdi-delete" @click="removeOption(item)" />
                            </div>
                        </template>
                    </EasyDataTable>
                </v-container>
            </v-window-item>

            <v-window-item value="models" lazy>
                <SubCategory
                    :attribute="modelItems"
                    :attributeOptions="options"
                    :title="current_title"
                    :parentId="parentId"
                    @come-back="comeBack"
                    :key="key"
                />
            </v-window-item>
        </v-window>

        <!-- Add / Edit Dialog -->
        <v-dialog v-model="showForm" width="700">
            <v-card>
                <v-card-title class="headline black" primary-title>
                    {{ fdata.id ? 'Edit Maker' : 'Add Maker' }}
                    <span class="mdi mdi-close float-right cursor-pointer" @click="closeModal()"></span>
                </v-card-title>
                <v-card-text class="pa-5">
                    <v-text-field
                        v-model="fdata.title"
                        :label="fdata.id ? 'Edit Maker Name' : 'Enter Maker Name'"
                        variant="outlined"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions class="pa-5">
                    <v-btn class="backgrond-color-teal" @click="saveForm" :loading="saving">Save</v-btn>
                    <v-btn variant="text" @click="closeModal">Cancel</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirm Dialog -->
        <v-dialog v-model="deleteDialog" :max-width="400">
            <v-card>
                <v-toolbar color="secondary" dense flat height="40">
                    <v-toolbar-title class="font-weight-bold">Confirm</v-toolbar-title>
                </v-toolbar>
                <v-card-text class="pa-4 black--text">Are you sure you want to delete this?</v-card-text>
                <v-card-actions class="pt-3">
                    <v-spacer></v-spacer>
                    <v-btn color="grey" text class="body-2 font-weight-bold" @click="cancel">Cancel</v-btn>
                    <v-btn color="primary" class="body-2 font-weight-bold" outlined @click="agree">OK</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import Auth from "@/auth.js";

export default {
    data() {
        return {
            
            tab: "attribute",
            showForm: false,
            saving: false,
            deleteDialog: false,

            items: [],
            modelItems: [],
            options: [],

            current_title: "",
            parentId: null,
            key: 0,

            fdata: {},
            deletedId: -1,
            searchValue: "",

            headers: [
                { text: "Title", value: "title", sortable: true, width: 600 },
                { text: "Operation", value: "operation", width: 25 },
            ],
        };
    },

    methods: {

        allItem() {
            this.axios.get("/api/attribute/makers").then((response) => {
                this.items = response.data.data;
            });
        },

        addNew() {
            this.fdata = {};
            this.showForm = true;
        },

        editItem(item) {
            this.fdata = { ...item };
            this.showForm = true;
        },

        async saveForm() {
            if (!this.fdata.title || !this.fdata.title.trim()) {
                this.showError("Maker name is required.");
                return;
            }
            this.saving = true;
            this.fdata.slug = "maker";
            try {
                if (this.fdata.id) {
                    const response = await this.axios.put("/api/attribute-option-update", this.fdata);
                    if (response.data.success !== false) {
                        this.showSuccess("Updated successfully.");
                        this.showForm = false;
                        this.allItem();
                    } else {
                        this.showError(response.data.message || "Update failed.");
                    }
                } else {
                    const response = await this.axios.post("/api/save-option", this.fdata);
                    if (response.data.success !== false) {
                        this.showSuccess("Saved successfully.");
                        this.showForm = false;
                        this.allItem();
                    } else {
                        this.showError(response.data.message || "Save failed.");
                    }
                }
            } catch (error) {
                console.error(error);
                this.showError("An error occurred while saving.");
            } finally {
                this.saving = false;
            }
        },

        viewModels(item) {
            this.axios
                .get(`/api/attribute-options?parent_attribute_option_id=${item.id}`)
                .then((response) => {
                    if (response.data.success) {
                        this.parentId = item.id;
                        this.modelItems = response.data.data;
                        this.current_title = "Models";
                        this.key += 1;
                        this.tab = "models";
                    } else {
                        this.showError(response.data.message || "Failed to load models.");
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.showError("An error occurred while fetching models.");
                });
        },

        comeBack() {
            this.tab = "attribute";
            this.parentId = null;
            this.allItem();
        },

        removeOption(item) {
            this.deletedId = item.id;
            this.deleteDialog = true;
        },

        cancel() {
            this.deleteDialog = false;
            this.deletedId = -1;
        },

        agree() {
            this.axios
                .post("/api/remove-option", { id: this.deletedId })
                .then((response) => {
                    if (response.data.success) {
                        this.items = this.items.filter((v) => v.id !== this.deletedId);
                        this.showSuccess(response.data.message || "Deleted successfully.");
                        this.deleteDialog = false;
                        this.deletedId = -1;
                    } else {
                        this.showError(response.data.message || "Delete failed.");
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.showError("An error occurred while deleting.");
                });
        },

        closeModal() {
            this.showForm = false;
            this.fdata = {};
        },
    },

    created() {
        document.title = "Category";
        this.allItem();
      
    },
};
</script>

<style>
.backgrond-color-teal {
    background-color: #0a5d5c;
    color: #ffffff;
}
.cursor-pointer {
    cursor: pointer;
}
</style>