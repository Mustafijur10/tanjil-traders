<template> 
    <div>
        <v-window v-model="tab">
            <v-window-item value="attribute" lazy>
                <v-container class="ml-0 pa-0">
                    <div class="trz_trz">
                        <v-container class="pa-0">
                            <v-row no-gutters>
                                <v-col cols="3">
                                    <v-text-field
                                        v-model="searchValue"
                                        label="Search.."
                                        clearable
                                        variant="outlined"
                                        class="mt-4"
                                        density="compact"
                                        @click:clear="searchValue = ''"
                                    ></v-text-field>
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
                                        <span @click.prevent="viewSubCategories(item)" class="cursor-pointer">View Sub-Categories</span>
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

            <v-window-item value="sub_category" lazy>
                <SubCategory
                    :attribute="subCategoryItems"
                    :title="current_title"
                    :categoryOptionId="categoryOptionId"
                    @come-back="comeBack"
                    :key="key"
                />
            </v-window-item>
        </v-window>

        <!-- Add / Edit Dialog -->
        <v-dialog v-model="showForm" width="700">
            <v-card>
                <v-card-title class="headline black" primary-title>
                    {{ fdata.id ? 'Edit Category' : 'Add Category' }}
                    <span class="mdi mdi-close float-right cursor-pointer" @click="closeModal()"></span>
                </v-card-title>
                <v-card-text class="pa-5">
                    <v-text-field
                        v-model="fdata.title"
                        :label="fdata.id ? 'Edit Category Name' : 'Enter Category Name'"
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
export default {
    data() {
        return {
            tab: "attribute",
            showForm: false,
            saving: false,
            deleteDialog: false,

            items: [],
            subCategoryItems: [],

            current_title: "",
            categoryOptionId: null,
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

        // Load all top-level categories (parent_attribute_option_id = 0)
        allItem() {
            this.axios.get("/api/attribute/category").then((response) => {
                if (response.data.success) {
                    this.items = response.data.data;
                }
            }).catch((error) => {
                console.error(error);
                this.showError("Failed to load categories.");
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
                this.showError("Category name is required.");
                return;
            }
            this.saving = true;
            try {
                if (this.fdata.id) {
                    // Update — only send id and title
                    const response = await this.axios.put("/api/attribute-option-update", {
                        id:    this.fdata.id,
                        title: this.fdata.title,
                    });
                    if (response.data.success !== false) {
                        this.showSuccess("Updated successfully.");
                        this.showForm = false;
                        this.allItem();
                    } else {
                        this.showError(response.data.message || "Update failed.");
                    }
                } else {
                    // Create — slug = "category", parent = 0 (top-level)
                    const response = await this.axios.post("/api/save-option", {
                        title:                      this.fdata.title,
                        slug:                       "category",
                        parent_attribute_option_id: 0,
                    });
                    this.showForm = false;
                    this.allItem();
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

        // Drill into sub-categories of a category
        viewSubCategories(item) {
            this.axios
                .get(`/api/attribute-options?parent_attribute_option_id=${item.id}`)
                .then((response) => {
                    if (response.data.success) {
                        this.categoryOptionId  = item.id;
                        this.subCategoryItems  = response.data.data;
                        this.current_title     = `Sub-Categories of "${item.title}"`;
                        this.key += 1;
                        this.tab = "sub_category";
                    } else {
                        this.showError(response.data.message || "Failed to load sub-categories.");
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.showError("An error occurred while fetching sub-categories.");
                });
        },

        comeBack() {
            this.tab = "attribute";
            this.categoryOptionId = null;
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