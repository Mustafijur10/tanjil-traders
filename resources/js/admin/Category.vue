<template>
    <v-slide-x-transition appear>
        <div class="tt-category">
            <v-window v-model="tab">
                <!-- ═════════ Categories ═════════ -->
                <v-window-item value="attribute" lazy>
                    <v-card flat class="tt-card">
                        <div class="tt-head">
                            <span class="tt-dot"></span>
                            <span class="tt-card-title">Categories</span>
                            <v-spacer />
                            <v-chip size="small" variant="outlined" color="primary">{{ items.length }} total</v-chip>
                        </div>

                        <v-card-text class="tt-body">
                            <v-row no-gutters class="mb-3" align="center">
                                <v-col cols="12" sm="4">
                                    <v-text-field
                                        v-model="searchValue"
                                        label="Search categories…"
                                        prepend-inner-icon="mdi-magnify"
                                        clearable
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                        @click:clear="searchValue = ''"
                                    />
                                </v-col>
                                <v-col cols="12" sm="4" offset-sm="4" class="d-flex justify-end align-center">
                                    <v-btn color="primary" variant="flat" class="text-none" prepend-icon="mdi-plus" @click="addNew">
                                        Add category
                                    </v-btn>
                                </v-col>
                            </v-row>

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
                                    <v-list-item class="tt-name-item" density="compact" min-height="40" @click="editItem(item)">
                                        <template #prepend>
                                            <v-icon size="14" class="tt-name-caret" icon="mdi-chevron-right" />
                                        </template>
                                        <v-list-item-title class="tt-name">{{ item.title }}</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">Category</v-list-item-subtitle>
                                    </v-list-item>
                                </template>

                                <template #item-operation="item">
                                    <v-btn
                                        variant="text"
                                        size="small"
                                        class="text-none mr-1"
                                        prepend-icon="mdi-folder-multiple-outline"
                                        color="primary"
                                        @click="viewSubCategories(item)"
                                    >
                                        Sub-categories
                                    </v-btn>
                                    <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" class="mr-1" @click.prevent="editItem(item)" />
                                    <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="removeOption(item)" />
                                </template>
                            </EasyDataTable>
                        </v-card-text>
                    </v-card>
                </v-window-item>

                <!-- ═════════ Sub-categories drill-down ═════════ -->
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

            <!-- Add / Edit dialog -->
            <v-dialog v-model="showForm" width="480">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">
                            {{ fdata.id ? "Edit category" : "Add category" }}
                        </v-toolbar-title>
                        <v-spacer />
                        <v-btn icon="mdi-close" variant="text" size="small" @click="closeModal" />
                    </v-toolbar>
                    <v-card-text class="pa-5">
                        <v-text-field
                            v-model="fdata.title"
                            :label="fdata.id ? 'Category name' : 'Enter category name'"
                            variant="outlined"
                            density="compact"
                            autofocus
                        />
                    </v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="closeModal">Cancel</v-btn>
                        <v-btn color="primary" variant="flat" class="text-none" @click="saveForm" :loading="saving">Save</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="400">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Delete category</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="pa-4">Are you sure you want to delete this category? This can't be undone.</v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" @click="cancel">Cancel</v-btn>
                        <v-btn color="error" variant="flat" @click="agree">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </div>
    </v-slide-x-transition>
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

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-category {
    --ink: #1c1917;
    --muted: #78716c;
    --line: #e7e2dd;
    --accent: #e25311;
    --tint: #fdece3;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
}

.tt-card {
    background: #fff;
    border: 1px solid var(--line) !important;
    border-radius: 10px !important;
    box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04) !important;
    overflow: hidden;
}
.tt-head {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 16px;
    background: linear-gradient(90deg, var(--tint), #fff);
    border-bottom: 1px solid var(--line);
    border-left: 3px solid var(--accent);
}
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-body { padding: 16px 18px 18px; }

.tt-name-item {
    padding-inline: 4px !important;
    cursor: pointer;
    border-radius: 6px;
    transition: transform 0.15s ease, background-color 0.15s ease;
}
.tt-name-item:hover {
    background-color: var(--tint);
    transform: translateX(3px);
}
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-name-caret {
    color: var(--accent);
    opacity: 0;
    transition: opacity 0.15s ease, transform 0.15s ease;
    transform: translateX(-4px);
}
.tt-name-item:hover .tt-name-caret { opacity: 1; transform: none; }

:deep(.customize-table) {
    --easy-table-border: 1px solid #e7e2dd;
    --easy-table-row-border: 1px solid #e7e2dd;
    --easy-table-header-font-size: 12px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #57534e;
    --easy-table-header-background-color: #f5f1ec;
    --easy-table-body-row-height: 56px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #fdece3;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
</style>