<template>
    <div>
        <PageHeader title="All Products" subtitle="Everything in your catalog. Click a product to edit it.">
            <v-btn-secondary prepend-icon="mdi-shape-outline" to="/admin/categories">{{ $t("Categories") }}</v-btn-secondary>
            <v-btn-primary prepend-icon="mdi-plus" to="/admin/products/new">{{ $t("Add product") }}</v-btn-primary>
        </PageHeader>

        <div class="tt-stat-tiles">
            <button v-for="s in stats" :key="s.key" type="button" class="tt-stat-tile" :class="['is-' + s.tone, { 'is-on': tab === s.tab }]" :disabled="!s.tab" @click="s.tab && setTab(s.tab)">
                <span class="tt-stat-ico"><v-icon :icon="s.icon" size="20" /></span>
                <span>
                    <small>{{ $t(s.label) }}</small>
                    <b>{{ s.value }}</b>
                </span>
            </button>
        </div>

        <section class="tt-box">
            <div class="tt-toolbar">
                <div class="tt-segments" role="tablist" :aria-label="$t('Filter products')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': tab === t.value }" :aria-selected="tab === t.value" @click="setTab(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value] ?? 0) }}</span>
                    </button>
                </div>
                <div class="tt-toolbar-grow"></div>
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search name or SKU')" clearable :aria-label="$t('Search')" />
                <v-autocomplete-trz v-model="filters.category_id" density="compact" :items="categoryOptions" :placeholder="$t('All categories')" clearable :aria-label="$t('Category')" />
                <v-autocomplete-trz v-model="filters.brand_id" density="compact" :items="brandOptions" :placeholder="$t('All brands')" clearable :aria-label="$t('Brand')" />
            </div>

            <div v-if="selected.length" class="tt-bulk-bar" role="region" :aria-label="$t('Bulk actions')">
                <span>{{ $tn(selected.length, "{n} product selected", "{n} products selected") }}</span>
                <v-btn-tertiary size="small" prepend-icon="mdi-eye-outline" @click="bulk('active')">{{ $t("Publish") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" prepend-icon="mdi-eye-off-outline" @click="bulk('draft')">{{ $t("Move to draft") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" prepend-icon="mdi-star-outline" @click="bulk('feature')">{{ $t("Feature") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" @click="bulk('unfeature')">{{ $t("Unfeature") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" color="error" prepend-icon="mdi-trash-can-outline" @click="bulkDeleteDialog = true">{{ $t("Delete") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" class="ml-auto" @click="selected = []">{{ $t("Clear") }}</v-btn-tertiary>
            </div>

            <DataTable
                v-model:items-selected="selected"
                v-model:server-options="serverOptions"
                :server-items-length="total"
                :headers="headers"
                :items="items"
                :loading="loading"
                must-sort
                class="tt-edt-clickable"
                @click-row="(row) => $router.push(`/admin/products/${row.id}`)"
            >
                <template #item-name="row">
                    <div class="tt-product-cell">
                        <span class="tt-thumb"><img v-if="row.image" :src="url(row.image)" alt="" loading="lazy" /><v-icon v-else size="20">mdi-image-off-outline</v-icon></span>
                        <div class="tt-ellipsis">
                            <b class="tt-ellipsis">{{ row.name }}</b>
                            <span class="tt-muted tt-small">
                                <template v-if="row.sku">{{ row.sku }}</template>
                                <template v-if="row.sku && row.variants_count"> · </template>
                                <template v-if="row.variants_count">{{ $tn(row.variants_count, "{n} variant", "{n} variants") }}</template>
                                <template v-if="row.is_featured"><v-icon size="13" color="accent">mdi-star</v-icon>{{ $t("Featured") }}</template>
                            </span>
                        </div>
                    </div>
                </template>
                <template #item-category="row">
                    <div class="tt-two-line">
                        <span>{{ row.category?.name || "—" }}</span>
                        <small v-if="parentName(row.category)">{{ parentName(row.category) }}</small>
                    </div>
                </template>
                <template #item-brand="row">{{ row.brand?.name || "—" }}</template>
                <template #item-price="row">
                    <div class="tt-price-cell tt-num">
                        <b>{{ $money(row.final_price) }}</b>
                        <s v-if="row.final_price < row.price">{{ $money(row.price) }}</s>
                    </div>
                </template>
                <template #item-stock_qty="row">
                    <StatusChip :label="stockLabel(row)" :tone="stockTone(row)" />
                </template>
                <template #item-status="row">
                    <StatusChip :label="row.status === 'active' ? 'Published' : 'Draft'" :tone="row.status === 'active' ? 'green' : 'amber'" />
                </template>
                <template #item-updated_at="row">
                    <span class="tt-muted tt-nowrap-cell">{{ $date(row.updated_at) }}</span>
                </template>
                <template #item-actions="row">
                    <v-menu location="bottom end">
                        <template #activator="{ props }">
                            <v-btn-tertiary v-bind="props" icon="mdi-dots-vertical" size="small" :aria-label="$t('More actions')" @click.stop />
                        </template>
                        <v-list density="compact" min-width="190">
                            <v-list-item prepend-icon="mdi-pencil-outline" :title="$t('Edit')" :to="`/admin/products/${row.id}`" />
                            <v-list-item prepend-icon="mdi-content-copy" :title="$t('Duplicate')" @click="duplicate(row)" />
                            <v-list-item v-if="row.status === 'active'" prepend-icon="mdi-open-in-new" :title="$t('View on website')" :href="`/product/${row.slug}`" target="_blank" />
                            <v-list-item prepend-icon="mdi-trash-can-outline" :title="$t('Delete')" base-color="error" @click="askDelete(row)" />
                        </v-list>
                    </v-menu>
                </template>
                <template #empty-message>
                    <EmptyState
                        :icon="hasFilters ? 'mdi-magnify' : 'mdi-package-variant-closed'"
                        :title="hasFilters ? 'No products match' : 'No products yet'"
                        :text="hasFilters ? 'Try another search or filter.' : 'Add your first product to start selling.'"
                    >
                        <v-btn-primary v-if="!hasFilters" prepend-icon="mdi-plus" to="/admin/products/new">{{ $t("Add product") }}</v-btn-primary>
                    </EmptyState>
                </template>
            </DataTable>
        </section>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: deleting?.name || '' })" text="Past orders keep the product's name and price. This cannot be undone." :loading="busy" @confirm="remove" />
        <ConfirmDialog v-model="bulkDeleteDialog" :title="$tn(selected.length, 'Delete {n} product?', 'Delete {n} products?')" text="Past orders keep the products' names and prices. This cannot be undone." :loading="busy" @confirm="bulk('delete')" />
    </div>
</template>

<script>
import api, { fileUrl } from "@/api";

const TABS = [
    { value: "all", label: "All" },
    { value: "active", label: "Published" },
    { value: "draft", label: "Drafts" },
    { value: "low", label: "Low stock" },
    { value: "out", label: "Out of stock" },
];

export default {
    name: "Products",
    data() {
        const q = this.$route.query;
        return {
            items: [],
            total: 0,
            counts: {},
            categories: [],
            brands: [],
            lowStock: 5,
            loading: true,
            selected: [],
            tab: q.tab || "all",
            search: q.q || "",
            filters: { category_id: q.category ? Number(q.category) : null, brand_id: q.brand ? Number(q.brand) : null },
            serverOptions: { page: Number(q.page) || 1, rowsPerPage: 25, sortBy: "updated_at", sortType: "desc" },
            loadTimer: null,
            deleteDialog: false,
            bulkDeleteDialog: false,
            deleting: null,
            busy: false,
        };
    },
    computed: {
        tabs: () => TABS,
        stats() {
            const c = this.counts;
            return [
                { key: "all", tab: "all", label: "Products", value: this.$num(c.all ?? 0), icon: "mdi-package-variant-closed", tone: "green" },
                { key: "active", tab: "active", label: "Published", value: this.$num(c.active ?? 0), icon: "mdi-eye-outline", tone: "blue" },
                { key: "low", tab: "low", label: "Low stock", value: this.$num(c.low ?? 0), icon: "mdi-alert-outline", tone: "amber" },
                { key: "out", tab: "out", label: "Out of stock", value: this.$num(c.out ?? 0), icon: "mdi-package-variant-remove", tone: "red" },
                { key: "value", tab: null, label: "Stock value", value: this.$money(c.stock_value ?? 0), icon: "mdi-cash-multiple", tone: "violet" },
            ];
        },
        headers() {
            return [
                { text: this.$t("Product"), value: "name", sortable: true, width: 300 },
                { text: this.$t("Category"), value: "category" },
                { text: this.$t("Brand"), value: "brand" },
                { text: this.$t("Price"), value: "price", sortable: true },
                { text: this.$t("Stock"), value: "stock_qty", sortable: true },
                { text: this.$t("Status"), value: "status" },
                { text: this.$t("Updated"), value: "updated_at", sortable: true },
                { text: "", value: "actions", width: 56 },
            ];
        },
        categoryOptions() {
            const parents = Object.fromEntries(this.categories.filter((c) => !c.parent_id).map((c) => [c.id, c.name]));
            return this.categories.map((c) => ({ value: c.id, title: c.parent_id ? `${parents[c.parent_id]} › ${c.name}` : c.name }));
        },
        brandOptions() {
            return this.brands.map((b) => ({ value: b.id, title: b.name }));
        },
        hasFilters() {
            return !!(this.search || this.filters.category_id || this.filters.brand_id || this.tab !== "all");
        },
        params() {
            const o = this.serverOptions;
            return {
                page: o.page,
                per_page: o.rowsPerPage,
                sort: o.sortBy || "updated_at",
                dir: o.sortType || "desc",
                search: this.search || undefined,
                category_id: this.filters.category_id || undefined,
                brand_id: this.filters.brand_id || undefined,
                status: ["active", "draft"].includes(this.tab) ? this.tab : undefined,
                stock: ["low", "out"].includes(this.tab) ? this.tab : undefined,
            };
        },
    },
    watch: {
        params: { handler: "load", deep: true },
        search() {
            this.firstPage();
        },
        "filters.category_id"() {
            this.firstPage();
        },
        "filters.brand_id"() {
            this.firstPage();
        },
    },
    async created() {
        const { data } = await api.get("/admin/products/form-data");
        this.categories = data.categories;
        this.brands = data.brands;
        this.lowStock = data.low_stock;
        this.load();
    },
    methods: {
        url: fileUrl,
        firstPage() {
            this.serverOptions = { ...this.serverOptions, page: 1 };
        },
        setTab(value) {
            this.tab = value;
            this.selected = [];
            this.firstPage();
        },
        parentName(cat) {
            return cat?.parent_id ? this.categories.find((c) => c.id === cat.parent_id)?.name || "" : "";
        },
        stockLabel(p) {
            return p.stock_qty <= 0 ? this.$t("Out of stock") : this.$t("{n} in stock", { n: this.$num(p.stock_qty) });
        },
        stockTone(p) {
            if (p.stock_qty <= 0) return "red";
            return p.stock_qty <= this.lowStock ? "amber" : "green";
        },
        // Debounced: typing in the search box makes one request.
        load() {
            clearTimeout(this.loadTimer);
            this.loadTimer = setTimeout(this.fetch, 250);
        },
        async fetch() {
            this.loading = true;
            this.$router.replace({
                query: {
                    ...(this.tab !== "all" && { tab: this.tab }),
                    ...(this.search && { q: this.search }),
                    ...(this.filters.category_id && { category: this.filters.category_id }),
                    ...(this.filters.brand_id && { brand: this.filters.brand_id }),
                    ...(this.serverOptions.page > 1 && { page: this.serverOptions.page }),
                },
            });
            try {
                const { data } = await api.get("/admin/products", { params: this.params });
                this.items = data.items.data;
                this.total = data.items.total;
                this.counts = data.counts;
            } finally {
                this.loading = false;
            }
        },
        async duplicate(item) {
            const { data } = await api.post(`/admin/products/${item.id}/duplicate`);
            this.$store.dispatch("notify", this.$t("Copy created as a draft."));
            this.$router.push(`/admin/products/${data.id}`);
        },
        askDelete(item) {
            this.deleting = item;
            this.deleteDialog = true;
        },
        async remove() {
            this.busy = true;
            try {
                await api.delete(`/admin/products/${this.deleting.id}`);
                this.deleteDialog = false;
                this.$store.dispatch("notify", this.$t("Product deleted."));
                this.fetch();
            } finally {
                this.busy = false;
            }
        },
        async bulk(action) {
            this.busy = true;
            try {
                const { data } = await api.post("/admin/products/bulk", { ids: this.selected.map((p) => p.id), action });
                this.$store.dispatch("notify", this.$tn(data.count, "{n} product updated.", "{n} products updated."));
                this.selected = [];
                this.bulkDeleteDialog = false;
                this.fetch();
            } finally {
                this.busy = false;
            }
        },
    },
};
</script>
