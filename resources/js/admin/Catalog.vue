<template>
    <v-slide-x-transition appear>
        <div class="tt-catalog-list">
            <v-card flat class="tt-card">
                <div class="tt-head">
                    <span class="tt-dot"></span>
                    <span class="tt-card-title">Products</span>
                    <span class="tt-tab-pill">{{ activeTabLabel }}</span>
                    <v-spacer />
                    <v-chip size="small" variant="outlined" color="primary">{{ items.length }} total</v-chip>
                </div>

                <!-- ═════════ Tabs ═════════ -->
                <v-tabs v-model="activeTab" class="tt-tabs" color="primary" density="comfortable" show-arrows>
                    <v-tab value="all" class="text-none">
                        Product list
                        <v-chip size="x-small" variant="tonal" class="ml-2">{{ items.length }}</v-chip>
                    </v-tab>
                    <v-tab value="in" class="text-none">
                        In stock
                        <v-chip size="x-small" variant="tonal" color="success" class="ml-2">{{ inStockItems.length }}</v-chip>
                    </v-tab>
                    <v-tab value="out" class="text-none">
                        Out of stock
                        <v-chip size="x-small" variant="tonal" color="error" class="ml-2">{{ outOfStockItems.length }}</v-chip>
                    </v-tab>
                    <v-tab value="low" class="text-none">
                        Low stock
                        <v-chip size="x-small" variant="tonal" color="#B45309" class="ml-2">{{ lowStockItems.length }}</v-chip>
                    </v-tab>
                    <v-tab value="featured" class="text-none">
                        Featured
                        <v-chip size="x-small" variant="tonal" color="purple" class="ml-2">{{ featuredItems.length }}</v-chip>
                    </v-tab>
                    <v-tab value="sale" class="text-none">
                        On sale
                        <v-chip size="x-small" variant="tonal" color="pink" class="ml-2">{{ onSaleItems.length }}</v-chip>
                    </v-tab>
                    <v-tab value="attention" class="text-none">
                        Needs attention
                        <v-chip size="x-small" variant="tonal" color="#B45309" class="ml-2">{{ needsAttentionItems.length }}</v-chip>
                    </v-tab>
                </v-tabs>

                <v-card-text class="tt-body">
                    <v-row no-gutters class="mb-3" align="center">
                        <v-col cols="12" sm="4">
                            <v-text-field
                                v-model="searchValue"
                                label="Search products…"
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
                                Add product
                            </v-btn>
                        </v-col>
                    </v-row>

                    <v-window v-model="activeTab">
                        <!-- All / In stock / Out of stock / Featured / On sale share the same table shape -->
                        <v-window-item v-for="tab in ['all', 'in', 'out', 'featured', 'sale']" :key="tab" :value="tab">
                            <EasyDataTable
                                :headers="headers"
                                :items="tabItems(tab)"
                                table-class-name="customize-table"
                                buttons-pagination
                                :rows-per-page="25"
                                :fixedIndex="true"
                                :search-value="searchValue"
                                :loading="loading"
                                :empty-message="emptyMessage(tab)"
                            >
                                <template #item-image="item">
                                    <v-avatar rounded="lg" size="42" class="my-1" color="grey-lighten-3">
                                        <v-img v-if="item.image" :src="item.image" cover />
                                        <v-icon v-else icon="mdi-image-off-outline" size="18" color="grey" />
                                    </v-avatar>
                                </template>

                                <!-- Name cell rendered as a v-list-item: gives the row a slide-on-hover
                                     affordance and a consistent target for opening the product. -->
                                <template #item-name="item">
                                    <v-list-item class="tt-name-item" density="compact" min-height="40" @click="editItem(item)">
                                        <template #prepend>
                                            <v-icon size="14" class="tt-name-caret" icon="mdi-chevron-right" />
                                        </template>
                                        <v-list-item-title class="tt-name">
                                            {{ item.name }}
                                            <v-icon v-if="item.featured" size="13" color="purple" icon="mdi-star" class="ml-1" />
                                        </v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">{{ item.sku }}</v-list-item-subtitle>
                                    </v-list-item>
                                </template>

                                <template #item-slug="item">
                                    <span class="text-medium-emphasis">/p/{{ item.slug }}</span>
                                </template>

                                <template #item-status="item">
                                    <v-chip
                                        size="small"
                                        :color="item.status === 'Active' ? 'primary' : undefined"
                                        :variant="item.status === 'Active' ? 'flat' : 'outlined'"
                                    >
                                        {{ item.status === "Active" ? "Active" : "Inactive" }}
                                    </v-chip>
                                </template>

                                <template #item-quantity="item">
                                    <span :class="stockClass(item)">{{ item.quantity }}</span>
                                </template>

                                <template #item-operation="item">
                                    <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" class="mr-1" @click="editItem(item)" />
                                    <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="removeItem(item)" />
                                </template>
                            </EasyDataTable>
                        </v-window-item>

                        <!-- Low stock: quantity under 5, plus category so it doubles as a restock-by-category view -->
                        <v-window-item value="low">
                            <v-table v-if="lowStockItemsFiltered.length" density="compact" class="tt-lowstock-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in lowStockItemsFiltered" :key="item.id">
                                        <td class="tt-lowstock-avatar">
                                            <v-avatar rounded="lg" size="34" color="grey-lighten-3">
                                                <v-img v-if="item.image" :src="item.image" cover />
                                                <v-icon v-else icon="mdi-image-off-outline" size="16" color="grey" />
                                            </v-avatar>
                                        </td>
                                        <td>
                                            <v-list-item class="tt-name-item" density="compact" min-height="36" @click="editItem(item)">
                                                <v-list-item-title class="tt-name">{{ item.name }}</v-list-item-title>
                                            </v-list-item>
                                        </td>
                                        <td class="text-medium-emphasis">{{ item.sku }}</td>
                                        <td>
                                            <v-chip size="x-small" variant="outlined">{{ item.category || "—" }}</v-chip>
                                        </td>
                                        <td>
                                            <span class="text-warning font-weight-bold">{{ item.quantity }}</span>
                                        </td>
                                        <td class="text-right">
                                            <v-btn size="small" variant="tonal" color="#B45309" class="text-none" @click="editItem(item)">Restock</v-btn>
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                            <v-empty-state
                                v-else
                                icon="mdi-check-circle-outline"
                                title="Nothing running low"
                                text="Every product is at or above 5 units."
                            />
                        </v-window-item>

                        <!-- Needs attention: incomplete listings, with exactly what's missing spelled out -->
                        <v-window-item value="attention">
                            <v-table v-if="needsAttentionItemsFiltered.length" density="compact" class="tt-lowstock-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Missing</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in needsAttentionItemsFiltered" :key="item.id">
                                        <td class="tt-lowstock-avatar">
                                            <v-avatar rounded="lg" size="34" color="grey-lighten-3">
                                                <v-img v-if="item.image" :src="item.image" cover />
                                                <v-icon v-else icon="mdi-image-off-outline" size="16" color="grey" />
                                            </v-avatar>
                                        </td>
                                        <td>
                                            <v-list-item class="tt-name-item" density="compact" min-height="36" @click="editItem(item)">
                                                <v-list-item-title class="tt-name">{{ item.name || "Untitled product" }}</v-list-item-title>
                                            </v-list-item>
                                        </td>
                                        <td class="text-medium-emphasis">{{ item.sku || "—" }}</td>
                                        <td>
                                            <v-chip
                                                v-for="field in missingFields(item)"
                                                :key="field"
                                                size="x-small"
                                                variant="outlined"
                                                color="#B45309"
                                                class="mr-1 mb-1"
                                            >
                                                {{ field }}
                                            </v-chip>
                                        </td>
                                        <td class="text-right">
                                            <v-btn size="small" variant="tonal" color="primary" class="text-none" @click="editItem(item)">Fix</v-btn>
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                            <v-empty-state
                                v-else
                                icon="mdi-check-circle-outline"
                                title="All listings look complete"
                                text="Every product has an image, description, category and SKU."
                            />
                        </v-window-item>
                    </v-window>
                </v-card-text>
            </v-card>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="400">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Delete product</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="pa-4">
                        Are you sure you want to delete <b>{{ deletedItem?.name }}</b>? This can't be undone.
                    </v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" @click="cancelDelete">Cancel</v-btn>
                        <v-btn color="error" variant="flat" :loading="deleting" @click="confirmDelete">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </div>
    </v-slide-x-transition>
</template>

<script>
export default {
    name: "CatalogProductList",

    data() {
        return {
            loading: false,
            deleting: false,
            deleteDialog: false,
            deletedItem: null,
            searchValue: "",
            activeTab: "all",

            items: [],

            tabLabels: {
                all: "Product list",
                in: "In stock",
                out: "Out of stock",
                low: "Low stock",
                featured: "Featured",
                sale: "On sale",
                attention: "Needs attention",
            },

            headers: [
                { text: "Image", value: "image", sortable: false, width: 70 },
                { text: "Name", value: "name", sortable: true, width: 340 },
                { text: "Slug", value: "slug", sortable: true },
                { text: "Status", value: "status", sortable: true, width: 110 },
                { text: "Quantity", value: "quantity", sortable: true, width: 100 },
                { text: "Action", value: "operation", sortable: false, width: 110 },
            ],
        };
    },

    computed: {
        activeTabLabel() {
            return this.tabLabels[this.activeTab] || "";
        },
        inStockItems() {
            return this.items.filter((item) => item.quantity > 0);
        },
        outOfStockItems() {
            return this.items.filter((item) => item.quantity <= 0);
        },
        // "Low stock" = still on the shelf, but under 5 units.
        lowStockItems() {
            return this.items
                .filter((item) => item.quantity > 0 && item.quantity < 5)
                .sort((a, b) => a.quantity - b.quantity);
        },
        lowStockItemsFiltered() {
            return this.filterBySearch(this.lowStockItems);
        },
        featuredItems() {
            return this.items.filter((item) => item.featured);
        },
        onSaleItems() {
            return this.items.filter((item) => item.oldPrice && item.price && item.oldPrice > item.price);
        },
        // Incomplete listings: missing anything a buyer or the storefront layout needs.
        needsAttentionItems() {
            return this.items.filter((item) => this.missingFields(item).length > 0);
        },
        needsAttentionItemsFiltered() {
            return this.filterBySearch(this.needsAttentionItems);
        },
    },

    created() {
        document.title = "Products";
        this.allItems();
    },

    methods: {
        tabItems(tab) {
            if (tab === "in") return this.inStockItems;
            if (tab === "out") return this.outOfStockItems;
            if (tab === "featured") return this.featuredItems;
            if (tab === "sale") return this.onSaleItems;
            return this.items;
        },
        emptyMessage(tab) {
            if (tab === "in") return "No products in stock.";
            if (tab === "out") return "Nothing is out of stock.";
            if (tab === "featured") return "No products are featured yet.";
            if (tab === "sale") return "No products are on sale.";
            return "No products yet.";
        },
        stockClass(item) {
            if (item.quantity <= 0) return "text-error font-weight-bold";
            if (item.quantity < 5) return "text-warning font-weight-bold";
            return "";
        },
        missingFields(item) {
            const missing = [];
            if (!item.image) missing.push("Image");
            if (!item.shortDescription && !item.description) missing.push("Description");
            if (!item.category) missing.push("Category");
            if (!item.sku) missing.push("SKU");
            return missing;
        },
        filterBySearch(list) {
            const q = (this.searchValue || "").toLowerCase().trim();
            if (!q) return list;
            return list.filter(
                (item) =>
                    item.name?.toLowerCase().includes(q) ||
                    item.sku?.toLowerCase().includes(q) ||
                    item.category?.toLowerCase().includes(q)
            );
        },

        allItems() {
            this.loading = true;
            this.axios
                .get("/api/admin/products")
                .then((response) => {
                    if (response.data.success) {
                        this.items = response.data.data;
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.showError("Failed to load products.");
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        addNew() {
            this.$router.push("/admin/product");
        },

        editItem(item) {
            this.$router.push(`/admin/product/${item.id}`);
        },

        removeItem(item) {
            this.deletedItem = item;
            this.deleteDialog = true;
        },

        cancelDelete() {
            this.deleteDialog = false;
            this.deletedItem = null;
        },

        confirmDelete() {
            this.deleting = true;
            this.axios
                .post("/api/admin/products/remove", { id: this.deletedItem.id })
                .then((response) => {
                    if (response.data.success !== false) {
                        this.items = this.items.filter((v) => v.id !== this.deletedItem.id);
                        this.showSuccess(response.data.message || "Product deleted.");
                        this.deleteDialog = false;
                        this.deletedItem = null;
                    } else {
                        this.showError(response.data.message || "Delete failed.");
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.showError("An error occurred while deleting.");
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-catalog-list {
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
.tt-tab-pill {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--accent);
    background: #fff;
    border: 1px solid var(--tint);
    border-radius: 999px;
    padding: 3px 12px;
    margin-left: 4px;
}
.tt-body { padding: 16px 18px 18px; }

.tt-tabs {
    border-bottom: 1px solid var(--line);
    padding-inline: 8px;
}
.tt-tabs :deep(.v-tab) {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: normal;
    min-width: 0;
}

.tt-lowstock-table { background: transparent; }
.tt-lowstock-table :deep(th) {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--muted);
    text-transform: none;
}
.tt-lowstock-table :deep(td) { font-size: 13px; }
.tt-lowstock-avatar { width: 46px; }

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