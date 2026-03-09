<template>
    <v-window v-model="tabValue">
        <v-window-item value="attribute" lazy>
            <v-toolbar class="bg-secondary">
                <v-toolbar-title class="hidden-sm-and-down font-weight-light">
                    <v-row no-gutters>
                        <v-col cols="2" class="pt-1" align="left">{{ component_title }}</v-col>
                        <v-col class="pr-2" cols="2" align="right" offset="8">
                            <v-btn @click="$emit('comeBack')">Back</v-btn>
                        </v-col>
                    </v-row>
                </v-toolbar-title>
            </v-toolbar>

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
    </v-window>

    <!-- Add / Edit Dialog -->
    <v-dialog v-model="showForm" width="700">
        <v-card>
            <v-card-title class="headline black" primary-title>
                {{ fdata.id ? 'Edit Sub-Category' : 'Add Sub-Category' }}
                <span class="mdi mdi-close float-right cursor-pointer" @click="closeModal()"></span>
            </v-card-title>
            <v-card-text class="pa-5">
                <v-text-field
                    v-model="fdata.title"
                    :label="fdata.id ? 'Edit Sub-Category' : 'Enter Sub-Category'"
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
</template>

<script>
export default {
    props: [
        "attribute",
        "attributeOptions",
        "title",
        "categoryOptionId",
    ],

    emits: ["comeBack"],

    data() {
        return {
            showForm: false,
            saving: false,
            deleteDialog: false,
            tabValue: "attribute",

            items: Array.isArray(this.attribute) ? this.attribute : [],
            options: this.attributeOptions,
            component_title: this.title,

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
                this.showError("Sub-category title is required.");
                return;
            }
            this.saving = true;
            this.fdata.slug = "parts";
            this.fdata.parent_attribute_option_id = this.categoryOptionId;
            try {
                if (this.fdata.id) {
                    const response = await this.axios.put("/api/attribute-option-update", this.fdata);
                    if (response.data.success !== false) {
                        this.items = response.data.options ?? this.items;
                        this.showSuccess("Updated successfully.");
                        this.showForm = false;
                    } else {
                        this.showError(response.data.message || "Update failed.");
                    }
                } else {
                    const response = await this.axios.post("/api/save-option", this.fdata);
                    if (response.data.success !== false) {
                        this.items = response.data.data ?? this.items;
                        this.showSuccess("Saved successfully.");
                        this.showForm = false;
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

        refreshItems() {
            if (!this.categoryOptionId) return;
            this.axios
                .get(`/api/attribute-options?parent_attribute_option_id=${this.categoryOptionId}`)
                .then((response) => {
                    if (response.data.success) {
                        this.items = response.data.data;
                    }
                })
                .catch((error) => console.error(error));
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
        if (!this.attribute || this.attribute.length === 0) {
            this.refreshItems();
        }
    },

    watch: {
        categoryOptionId(newVal) {
            if (newVal) this.refreshItems();
        },
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