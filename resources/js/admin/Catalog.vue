<template>
    <v-slide-x-transition appear>
        <div class="tt-catalog-list">
            <!-- ═════════ Page header ═════════ -->
            <div class="tt-page-head">
                <div>
                    <h1 class="tt-page-title">Products</h1>
                    <div class="tt-page-sub">
                        {{ items.length }} products across {{ categoryCount }} categories · {{ shortMoney(totalInventoryValue) }} total inventory value
                    </div>
                </div>
                <v-spacer />
                <div class="tt-head-actions">
                    <v-btn variant="outlined" class="text-none" prepend-icon="mdi-tray-arrow-down" @click="importProducts">
                        Import
                    </v-btn>
                    <v-btn variant="outlined" class="text-none" prepend-icon="mdi-tray-arrow-up" @click="exportProducts">
                        Export
                    </v-btn>
                    <v-btn color="primary" variant="flat" class="text-none" prepend-icon="mdi-plus" @click="addNew">
                        Add product
                    </v-btn>
                </div>
            </div>

            <!-- ═════════ KPI cards ═════════ -->
            <div class="tt-kpis">
                <div v-for="k in kpiCards" :key="k.label" class="tt-kpi">
                    <div class="tt-kpi-top">
                        <span class="tt-kpi-ic" :style="{ background: k.tint }">
                            <v-icon :icon="k.icon" :color="k.color" size="20" />
                        </span>
                        <v-spacer />
                        <span
                            v-if="k.trendPct !== null"
                            class="tt-trend"
                            :class="k.trendPct >= 0 ? 'up' : 'down'"
                            title="Change since your last visit to this page"
                        >
                            <v-icon size="13">{{ k.trendPct >= 0 ? "mdi-trending-up" : "mdi-trending-down" }}</v-icon>
                            {{ Math.abs(k.trendPct) }}%
                        </span>
                    </div>
                    <div class="tt-kpi-value">{{ k.value }}</div>
                    <div class="tt-kpi-label">{{ k.label }}</div>
                    <div class="tt-kpi-bar"><i :style="{ width: k.pct + '%', background: k.color }"></i></div>
                </div>
            </div>

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
                    <!-- ═════════ Search + filters ═════════ -->
                    <div class="tt-toolbar">
                        <v-text-field
                            v-model="searchValue"
                            label="Search products…"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            variant="outlined"
                            density="compact"
                            hide-details
                            class="tt-search"
                            @click:clear="searchValue = ''"
                        />

                        <v-select
                            v-model="categoryFilter"
                            :items="categoryOptions"
                            label="All categories"
                            variant="outlined"
                            density="compact"
                            hide-details
                            clearable
                            class="tt-filter"
                        />

                        <v-select
                            v-model="statusFilter"
                            :items="['Active', 'Inactive']"
                            label="All statuses"
                            variant="outlined"
                            density="compact"
                            hide-details
                            clearable
                            class="tt-filter"
                        />

                        <v-menu v-model="dateMenu" :close-on-content-click="false">
                            <template #activator="{ props }">
                                <v-btn v-bind="props" variant="outlined" class="text-none tt-date-btn" prepend-icon="mdi-calendar-outline">
                                    {{ dateRangeLabel }}
                                </v-btn>
                            </template>
                            <v-card class="pa-4" min-width="280">
                                <v-text-field v-model="dateFrom" type="date" label="From" density="compact" variant="outlined" hide-details class="mb-3" />
                                <v-text-field v-model="dateTo" type="date" label="To" density="compact" variant="outlined" hide-details />
                                <div class="d-flex justify-end mt-3 ga-2">
                                    <v-btn size="small" variant="text" class="text-none" @click="clearDateRange">Clear</v-btn>
                                    <v-btn size="small" color="primary" variant="flat" class="text-none" @click="dateMenu = false">Done</v-btn>
                                </div>
                            </v-card>
                        </v-menu>

                        <v-menu :close-on-content-click="false">
                            <template #activator="{ props }">
                                <v-btn v-bind="props" variant="outlined" class="text-none tt-columns-btn" prepend-icon="mdi-tune-variant">
                                    Filters
                                    <v-chip v-if="activeFilterCount" size="x-small" color="primary" class="ml-2">{{ activeFilterCount }}</v-chip>
                                </v-btn>
                            </template>
                            <v-card class="pa-3" min-width="240">
                                <div v-if="!activeFilterCount" class="text-caption text-medium-emphasis pa-2">No filters applied.</div>
                                <v-chip
                                    v-if="categoryFilter"
                                    closable
                                    size="small"
                                    variant="tonal"
                                    class="ma-1"
                                    @click:close="categoryFilter = null"
                                >
                                    Category: {{ categoryFilter }}
                                </v-chip>
                                <v-chip
                                    v-if="statusFilter"
                                    closable
                                    size="small"
                                    variant="tonal"
                                    class="ma-1"
                                    @click:close="statusFilter = null"
                                >
                                    Status: {{ statusFilter }}
                                </v-chip>
                                <v-chip
                                    v-if="dateFrom || dateTo"
                                    closable
                                    size="small"
                                    variant="tonal"
                                    class="ma-1"
                                    @click:close="clearDateRange"
                                >
                                    Date: {{ dateRangeLabel }}
                                </v-chip>
                                <v-chip
                                    v-if="searchValue"
                                    closable
                                    size="small"
                                    variant="tonal"
                                    class="ma-1"
                                    @click:close="searchValue = ''"
                                >
                                    Search: {{ searchValue }}
                                </v-chip>
                                <v-btn
                                    v-if="activeFilterCount"
                                    size="small"
                                    variant="text"
                                    class="text-none mt-1"
                                    block
                                    @click="clearAllFilters"
                                >
                                    Clear all
                                </v-btn>
                            </v-card>
                        </v-menu>

                        <v-menu :close-on-content-click="false">
                            <template #activator="{ props }">
                                <v-btn v-bind="props" variant="outlined" class="text-none tt-columns-btn" prepend-icon="mdi-view-column-outline">
                                    Columns
                                </v-btn>
                            </template>
                            <v-list density="compact" class="tt-columns-menu">
                                <v-list-item v-for="c in toggleableColumns" :key="c.key">
                                    <v-checkbox-btn v-model="columnsVisible[c.key]" :label="c.label" density="compact" color="primary" />
                                </v-list-item>
                            </v-list>
                        </v-menu>

                        <v-spacer />

                        <v-btn-toggle
                            v-if="!['low', 'attention'].includes(activeTab)"
                            v-model="viewMode"
                            mandatory
                            density="comfortable"
                            variant="outlined"
                            divided
                            class="tt-view-toggle"
                        >
                            <v-btn value="list" icon="mdi-view-list" size="small" />
                            <v-btn value="grid" icon="mdi-view-grid-outline" size="small" />
                        </v-btn-toggle>
                    </div>

                    <!-- ═════════ Bulk selection bar ═════════ -->
                    <div v-if="selectedItems.length" class="tt-bulkbar">
                        <span>{{ selectedItems.length }} selected</span>
                        <v-spacer />
                        <v-btn size="small" variant="text" class="text-none" @click="selectedItems = []">Clear</v-btn>
                        <v-btn size="small" color="error" variant="tonal" class="text-none" @click="bulkDeleteDialog = true">
                            Delete selected
                        </v-btn>
                    </div>

                    <v-window v-model="activeTab">
                        <!-- All / In stock / Out of stock / Featured / On sale share the same table shape -->
                        <v-window-item v-for="tab in ['all', 'in', 'out', 'featured', 'sale']" :key="tab" :value="tab">
                            <!-- Grid view -->
                            <div v-if="viewMode === 'grid'" class="tt-grid">
                                <div v-for="item in visibleTabItems(tab)" :key="item.id" class="tt-grid-card" @click="editItem(item)">
                                    <div class="tt-grid-media">
                                        <v-img v-if="item.image" :src="item.image" cover height="140" />
                                        <v-icon v-else icon="mdi-image-off-outline" size="28" color="grey" />
                                        <v-chip
                                            size="x-small"
                                            class="tt-grid-status"
                                            :color="item.status === 'Active' ? 'primary' : undefined"
                                            :variant="item.status === 'Active' ? 'flat' : 'outlined'"
                                        >
                                            {{ item.status === "Active" ? "Active" : "Inactive" }}
                                        </v-chip>
                                    </div>
                                    <div class="tt-grid-body">
                                        <div class="tt-grid-name">
                                            {{ item.name }}
                                            <v-icon v-if="item.featured" size="12" color="purple" icon="mdi-star" />
                                        </div>
                                        <div class="tt-grid-sku">{{ item.sku }}</div>
                                        <div class="tt-stock-cell mt-2">
                                            <span class="tt-stock-text" :class="stockClass(item)">
                                                <i class="tt-stock-dot" :class="stockDotClass(item)"></i>
                                                {{ item.quantity }} in stock
                                            </span>
                                            <div class="tt-stock-bar">
                                                <i :style="{ width: stockBarPct(item) + '%' }" :class="stockDotClass(item)"></i>
                                            </div>
                                        </div>
                                        <div class="tt-grid-actions">
                                            <v-btn icon="mdi-pencil" size="x-small" variant="text" color="primary" @click.stop="editItem(item)" />
                                            <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" @click.stop="removeItem(item)" />
                                        </div>
                                    </div>
                                </div>
                                <v-empty-state
                                    v-if="!visibleTabItems(tab).length"
                                    icon="mdi-package-variant"
                                    :title="emptyMessage(tab)"
                                    class="tt-grid-empty"
                                />
                            </div>

                            <!-- List view -->
                            <EasyDataTable
                                v-else
                                v-model:items-selected="selectedItems"
                                :headers="headers"
                                :items="visibleTabItems(tab)"
                                table-class-name="customize-table"
                                buttons-pagination
                                :rows-items="[10, 25, 50, 100]"
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

                                <template #item-category="item">
                                    <v-chip size="x-small" variant="outlined">{{ item.category || "—" }}</v-chip>
                                </template>

                                <template #item-brand="item">
                                    <span class="text-medium-emphasis">{{ item.brand || "—" }}</span>
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
                                    <div class="tt-stock-cell">
                                        <span class="tt-stock-text" :class="stockClass(item)">
                                            <i class="tt-stock-dot" :class="stockDotClass(item)"></i>
                                            {{ item.quantity }} in stock
                                        </span>
                                        <div class="tt-stock-bar">
                                            <i :style="{ width: stockBarPct(item) + '%' }" :class="stockDotClass(item)"></i>
                                        </div>
                                    </div>
                                </template>

                                <template #item-sales="item">
                                    {{ (item.sales || 0).toLocaleString() }}
                                </template>

                                <template #item-revenue="item">
                                    {{ shortMoney(item.revenue || 0) }}
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

            <!-- Bulk delete confirm dialog -->
            <v-dialog v-model="bulkDeleteDialog" max-width="400">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Delete {{ selectedItems.length }} products</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="pa-4"> This can't be undone. </v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" @click="bulkDeleteDialog = false">Cancel</v-btn>
                        <v-btn color="error" variant="flat" :loading="deleting" @click="confirmBulkDelete">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </div>
    </v-slide-x-transition>
</template>

<script>
const shortMoney = (v) => {
    const n = Number(v) || 0;
    if (n >= 10000000) return "৳" + (n / 10000000).toFixed(1).replace(/\.0$/, "") + "Cr";
    if (n >= 100000) return "৳" + (n / 100000).toFixed(1).replace(/\.0$/, "") + "L";
    if (n >= 1000) return "৳" + Math.round(n / 1000) + "k";
    return "৳" + n.toLocaleString();
};

export default {
    name: "CatalogProductList",

    data() {
        return {
            loading: false,
            deleting: false,
            deleteDialog: false,
            bulkDeleteDialog: false,
            deletedItem: null,
            searchValue: "",
            categoryFilter: null,
            statusFilter: null,
            dateFrom: null,
            dateTo: null,
            dateMenu: false,
            viewMode: "list",
            activeTab: "all",
            selectedItems: [],
            prevSnapshot: null,

            items: [],

            columnsVisible: {
                category: true,
                brand: true,
                sales: false,
                revenue: false,
            },
            toggleableColumns: [
                { key: "category", label: "Category" },
                { key: "brand", label: "Brand" },
                { key: "sales", label: "Sales" },
                { key: "revenue", label: "Revenue" },
            ],

            tabLabels: {
                all: "Product list",
                in: "In stock",
                out: "Out of stock",
                low: "Low stock",
                featured: "Featured",
                sale: "On sale",
                attention: "Needs attention",
            },

            baseHeaders: [
                { text: "Image", value: "image", sortable: false, width: 70 },
                { text: "Name", value: "name", sortable: true, width: 300 },
                { text: "Slug", value: "slug", sortable: true },
            ],
            tailHeaders: [
                { text: "Status", value: "status", sortable: true, width: 110 },
                { text: "Quantity", value: "quantity", sortable: true, width: 150 },
                { text: "Action", value: "operation", sortable: false, width: 110 },
            ],
        };
    },

    watch: {
        activeTab() {
            this.selectedItems = [];
        },
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

        categoryOptions() {
            return [...new Set(this.items.map((i) => i.category).filter(Boolean))].sort();
        },
        categoryCount() {
            return this.categoryOptions.length;
        },
        totalInventoryValue() {
            return this.items.reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.quantity) || 0), 0);
        },

        dateRangeLabel() {
            if (!this.dateFrom && !this.dateTo) return "Select date range";
            if (this.dateFrom && this.dateTo) return `${this.dateFrom} → ${this.dateTo}`;
            return this.dateFrom ? `From ${this.dateFrom}` : `Until ${this.dateTo}`;
        },
        activeFilterCount() {
            return [this.categoryFilter, this.statusFilter, this.dateFrom || this.dateTo, this.searchValue].filter(Boolean).length;
        },

        kpiCards() {
            const total = this.items.length || 1;
            const activeCount = this.items.filter((i) => i.status === "Active").length;
            const raw = [
                { key: "total", label: "Total products", value: this.items.length, icon: "mdi-package-variant-closed", color: "#E25311", tint: "#fdece3" },
                { key: "active", label: "Active listings", value: activeCount, icon: "mdi-check-decagram-outline", color: "#1E7A34", tint: "#E3F3E6" },
                { key: "low", label: "Low stock", value: this.lowStockItems.length, icon: "mdi-alert-outline", color: "#B45309", tint: "#FDF1CF" },
                { key: "out", label: "Out of stock", value: this.outOfStockItems.length, icon: "mdi-package-variant-closed-remove", color: "#B3261E", tint: "#FBE6E4" },
            ];
            return raw.map((k) => ({
                ...k,
                pct: k.key === "total" ? 100 : (k.value / total) * 100,
                trendPct: this.trendFor(k.key, k.value),
            }));
        },

        headers() {
            const mid = [];
            if (this.columnsVisible.category) mid.push({ text: "Category", value: "category", sortable: true, width: 130 });
            if (this.columnsVisible.brand) mid.push({ text: "Brand", value: "brand", sortable: true, width: 120 });
            const afterStatus = [];
            if (this.columnsVisible.sales) afterStatus.push({ text: "Sales", value: "sales", sortable: true, width: 90 });
            if (this.columnsVisible.revenue) afterStatus.push({ text: "Revenue", value: "revenue", sortable: true, width: 110 });

            return [...this.baseHeaders, ...mid, ...this.tailHeaders.slice(0, 2), ...afterStatus, ...this.tailHeaders.slice(2)];
        },
    },

    created() {
        document.title = "Products";
        try {
            const raw = localStorage.getItem("tt-product-kpi-snapshot");
            if (raw) this.prevSnapshot = JSON.parse(raw);
        } catch (e) {
            this.prevSnapshot = null;
        }
        this.allItems();
    },

    methods: {
        shortMoney,

        tabItems(tab) {
            if (tab === "in") return this.inStockItems;
            if (tab === "out") return this.outOfStockItems;
            if (tab === "featured") return this.featuredItems;
            if (tab === "sale") return this.onSaleItems;
            return this.items;
        },
        visibleTabItems(tab) {
            let list = this.tabItems(tab);
            if (this.categoryFilter) list = list.filter((i) => i.category === this.categoryFilter);
            if (this.statusFilter) list = list.filter((i) => i.status === this.statusFilter);
            if (this.dateFrom || this.dateTo) {
                list = list.filter((i) => {
                    const d = i.created_at || i.createdAt;
                    if (!d) return false;
                    const t = new Date(d).getTime();
                    if (this.dateFrom && t < new Date(this.dateFrom).getTime()) return false;
                    if (this.dateTo && t > new Date(this.dateTo).getTime() + 86400000) return false;
                    return true;
                });
            }
            return list;
        },
        clearDateRange() {
            this.dateFrom = null;
            this.dateTo = null;
            this.dateMenu = false;
        },
        clearAllFilters() {
            this.categoryFilter = null;
            this.statusFilter = null;
            this.clearDateRange();
            this.searchValue = "";
        },

        // Trend badges compare today's counts to the last time this page was
        // loaded on this browser (stored in localStorage) — a real, honest
        // number rather than a fabricated "vs last week" percentage we have
        // no data to support.
        trendFor(key, value) {
            if (!this.prevSnapshot || this.prevSnapshot[key] == null) return null;
            const prev = this.prevSnapshot[key];
            if (prev === 0) return value === 0 ? 0 : 100;
            return Math.round(((value - prev) / prev) * 1000) / 10;
        },
        saveSnapshot() {
            const snapshot = {
                total: this.items.length,
                active: this.items.filter((i) => i.status === "Active").length,
                low: this.lowStockItems.length,
                out: this.outOfStockItems.length,
            };
            try {
                localStorage.setItem("tt-product-kpi-snapshot", JSON.stringify(snapshot));
            } catch (e) {
                /* localStorage unavailable — trend badges simply won't show */
            }
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
        stockDotClass(item) {
            if (item.quantity <= 0) return "tt-dot-red";
            if (item.quantity < 5) return "tt-dot-amber";
            return "tt-dot-green";
        },
        stockBarPct(item) {
            // Visual indicator only, scaled against a soft 50-unit reference so bars stay readable.
            return Math.max(4, Math.min(100, Math.round(((item.quantity || 0) / 50) * 100)));
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
                        this.saveSnapshot();
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

        importProducts() {
            // Hook up to your import flow (e.g. open a CSV upload dialog).
            this.$router.push("/admin/products/import");
        },

        exportProducts() {
            // Hook up to your export endpoint, e.g. window.location = '/api/admin/products/export'
            window.open("/api/admin/products/export", "_blank");
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

        confirmBulkDelete() {
            this.deleting = true;
            const ids = this.selectedItems.map((i) => i.id);
            this.axios
                .post("/api/admin/products/remove-bulk", { ids })
                .then((response) => {
                    if (response.data.success !== false) {
                        this.items = this.items.filter((v) => !ids.includes(v.id));
                        this.showSuccess(response.data.message || `${ids.length} products deleted.`);
                        this.bulkDeleteDialog = false;
                        this.selectedItems = [];
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

/* ---------------- page header ---------------- */
.tt-page-head {
    display: flex;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 18px;
}
.tt-page-title {
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 3px;
}
.tt-page-sub {
    font-size: 13px;
    color: var(--muted);
}
.tt-head-actions {
    display: flex;
    gap: 8px;
}

/* ---------------- KPI cards ---------------- */
.tt-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}
.tt-kpi {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 14px 16px;
}
.tt-kpi-top { display: flex; align-items: center; margin-bottom: 10px; }
.tt-kpi-ic {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: grid;
    place-items: center;
}
.tt-trend {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 12px;
    font-weight: 700;
    padding: 2px 7px 2px 5px;
    border-radius: 99px;
}
.tt-trend.up { background: #e3f3e6; color: #1e7a34; }
.tt-trend.down { background: #fbe6e4; color: #b3261e; }
.tt-kpi-value {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.1;
}
.tt-kpi-label {
    font-size: 12.5px;
    color: var(--muted);
    margin-top: 2px;
}
.tt-kpi-bar {
    height: 5px;
    background: #f0ece7;
    border-radius: 99px;
    margin-top: 10px;
    overflow: hidden;
}
.tt-kpi-bar i {
    display: block;
    height: 100%;
    border-radius: 99px;
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

/* ---------------- toolbar: search + filters ---------------- */
.tt-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.tt-search { flex: 1 1 220px; max-width: 300px; }
.tt-filter { flex: 0 0 170px; }
.tt-columns-btn, .tt-date-btn { border-color: var(--line); color: var(--ink); }
.tt-columns-menu { min-width: 180px; }
.tt-view-toggle .v-btn { height: 36px !important; }

/* ---------------- bulk action bar ---------------- */
.tt-bulkbar {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--tint);
    border: 1px solid #f6d2bd;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--accent);
}

/* ---------------- stock cell ---------------- */
.tt-stock-cell { min-width: 120px; }
.tt-stock-text { display: flex; align-items: center; gap: 6px; font-size: 12.5px; }
.tt-stock-dot { width: 7px; height: 7px; border-radius: 50%; flex: none; }
.tt-dot-green { background: #1e7a34; }
.tt-dot-amber { background: #b45309; }
.tt-dot-red { background: #b3261e; }
.tt-stock-bar { height: 4px; background: #f0ece7; border-radius: 99px; margin-top: 5px; overflow: hidden; }
.tt-stock-bar i { display: block; height: 100%; border-radius: 99px; }
.tt-stock-bar i.tt-dot-green { background: #1e7a34; }
.tt-stock-bar i.tt-dot-amber { background: #b45309; }
.tt-stock-bar i.tt-dot-red { background: #b3261e; }

/* ---------------- grid view ---------------- */
.tt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}
.tt-grid-card {
    border: 1px solid var(--line);
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    background: #fff;
    transition: border-color 0.15s ease, transform 0.15s ease;
}
.tt-grid-card:hover { border-color: var(--accent); transform: translateY(-2px); }
.tt-grid-media {
    position: relative;
    height: 140px;
    background: #f5f1ec;
    display: grid;
    place-items: center;
}
.tt-grid-status { position: absolute; top: 8px; left: 8px; }
.tt-grid-body { padding: 10px 12px 12px; }
.tt-grid-name {
    font-size: 13px;
    font-weight: 600;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 34px;
}
.tt-grid-sku { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
.tt-grid-actions { display: flex; justify-content: flex-end; gap: 2px; margin-top: 6px; }
.tt-grid-empty { grid-column: 1 / -1; }

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

@media (max-width: 960px) {
    .tt-kpis { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 600px) {
    .tt-kpis { grid-template-columns: 1fr; }
    .tt-head-actions { width: 100%; }
    .tt-head-actions .v-btn { flex: 1; }
}
</style>