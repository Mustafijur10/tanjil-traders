<template>
    <div>
        <PageHeader title="Products" subtitle="Everything in your catalog. Click a product to edit it.">
            <v-btn-primary prepend-icon="mdi-plus" to="/admin/products/new">{{ $t("Add product") }}</v-btn-primary>
        </PageHeader>

        <section class="tt-box">
            <div class="tt-toolbar">
                <div class="tt-segments" role="tablist" :aria-label="$t('Filter products')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': tab === t.value }" :aria-selected="tab === t.value" @click="setTab(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value] ?? 0) }}</span>
                    </button>
                </div>
            </div>
            <div class="tt-toolbar">
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search name or SKU')" clearable :aria-label="$t('Search')" />
                <v-autocomplete-trz v-model="filters.category_id" density="compact" :items="categoryOptions" :placeholder="$t('All categories')" clearable :aria-label="$t('Category')" />
                <v-autocomplete-trz v-model="filters.brand_id" density="compact" :items="brandOptions" :placeholder="$t('All brands')" clearable :aria-label="$t('Brand')" />
            </div>

            <div v-if="selected.length" class="tt-bulk-bar" role="region" :aria-label="$t('Bulk actions')">
                <span>{{ $t("{n} selected", { n: $num(selected.length) }) }}</span>
                <v-btn-tertiary size="small" @click="bulk('active')">{{ $t("Publish") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" @click="bulk('draft')">{{ $t("Move to draft") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" @click="bulk('feature')">{{ $t("Feature") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" @click="bulk('unfeature')">{{ $t("Unfeature") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" color="error" @click="bulkDeleteDialog = true">{{ $t("Delete") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" class="ml-auto" @click="selected = []">{{ $t("Clear selection") }}</v-btn-tertiary>
            </div>

            <v-table-server-trz
                v-model="selected"
                v-model:items-per-page="perPage"
                v-model:page="page"
                v-model:sort-by="sortBy"
                :headers="headers"
                :items="items"
                :items-length="total"
                :loading="loading"
                show-select
                item-value="id"
                :items-per-page-text="$t('Rows per page')"
                :loading-text="$t('Loading…')"
                class="tt-products-table"
                @click:row="(e, { item }) => $router.push(`/admin/products/${item.id}`)"
            >
                <template #item.name="{ item }">
                    <div class="tt-product-cell">
                        <span class="tt-thumb"><img v-if="item.image" :src="url(item.image)" alt="" loading="lazy" /><v-icon v-else size="20">mdi-image-off-outline</v-icon></span>
                        <div class="tt-ellipsis">
                            <b class="tt-ellipsis">{{ item.name }}</b>
                            <span class="tt-muted tt-small">
                                <template v-if="item.sku">{{ item.sku }}</template>
                                <template v-if="item.sku && item.variants_count"> · </template>
                                <template v-if="item.variants_count">{{ $tn(item.variants_count, "{n} variant", "{n} variants") }}</template>
                                <template v-if="item.is_featured"><v-icon size="13" color="accent">mdi-star</v-icon>{{ $t("Featured") }}</template>
                            </span>
                        </div>
                    </div>
                </template>
                <template #item.category="{ item }">
                    <div class="tt-two-line">
                        <span>{{ item.category?.name || "—" }}</span>
                        <small v-if="parentName(item.category)">{{ parentName(item.category) }}</small>
                    </div>
                </template>
                <template #item.brand="{ item }">
                    <span class="tt-small">{{ item.brand?.name || "—" }}</span>
                </template>
                <template #item.price="{ item }">
                    <div class="tt-price-cell tt-num">
                        <b>{{ $money(item.final_price) }}</b>
                        <s v-if="item.final_price < item.price">{{ $money(item.price) }}</s>
                    </div>
                </template>
                <template #item.stock_qty="{ item }">
                    <StatusChip :label="stockLabel(item)" :tone="stockTone(item)" />
                </template>
                <template #item.status="{ item }">
                    <StatusChip :label="item.status === 'active' ? 'Published' : 'Draft'" :tone="item.status === 'active' ? 'green' : 'amber'" />
                </template>
                <template #item.updated_at="{ item }">
                    <span class="tt-small tt-muted">{{ $date(item.updated_at) }}</span>
                </template>
                <template #item.actions="{ item }">
                    <v-menu location="bottom end">
                        <template #activator="{ props }">
                            <v-btn-tertiary v-bind="props" icon="mdi-dots-vertical" size="small" :aria-label="$t('More actions')" @click.stop />
                        </template>
                        <v-list density="compact" min-width="190">
                            <v-list-item prepend-icon="mdi-pencil-outline" :title="$t('Edit')" :to="`/admin/products/${item.id}`" />
                            <v-list-item prepend-icon="mdi-content-copy" :title="$t('Duplicate')" @click="duplicate(item)" />
                            <v-list-item v-if="item.status === 'active'" prepend-icon="mdi-open-in-new" :title="$t('View on website')" :href="`/product/${item.slug}`" target="_blank" />
                            <v-list-item prepend-icon="mdi-trash-can-outline" :title="$t('Delete')" base-color="error" @click="askDelete(item)" />
                        </v-list>
                    </v-menu>
                </template>
                <template #no-data>
                    <EmptyState
                        :icon="hasFilters ? 'mdi-magnify' : 'mdi-package-variant-closed'"
                        :title="hasFilters ? 'No products match' : 'No products yet'"
                        :text="hasFilters ? 'Try another search or filter.' : 'Add your first product to start selling.'"
                    >
                        <v-btn-primary v-if="!hasFilters" prepend-icon="mdi-plus" to="/admin/products/new">{{ $t("Add product") }}</v-btn-primary>
                    </EmptyState>
                </template>
            </v-table-server-trz>
        </section>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: deleting?.name || '' })" text="Past orders keep the product's name and price. This cannot be undone." :loading="busy" @confirm="remove" />
        <ConfirmDialog v-model="bulkDeleteDialog" :title="$t('Delete {n} products?', { n: $num(selected.length) })" text="Past orders keep the products' names and prices. This cannot be undone." :loading="busy" @confirm="bulk('delete')" />
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
            page: Number(q.page) || 1,
            perPage: 25,
            sortBy: [{ key: "updated_at", order: "desc" }],
            loadTimer: null,
            deleteDialog: false,
            bulkDeleteDialog: false,
            deleting: null,
            busy: false,
        };
    },
    computed: {
        tabs: () => TABS,
        headers() {
            return [
                { title: this.$t("Product"), key: "name", sortable: true, minWidth: 280 },
                { title: this.$t("Category"), key: "category", sortable: false },
                { title: this.$t("Brand"), key: "brand", sortable: false },
                { title: this.$t("Price"), key: "price", sortable: true, align: "end" },
                { title: this.$t("Stock"), key: "stock_qty", sortable: true },
                { title: this.$t("Status"), key: "status", sortable: false },
                { title: this.$t("Updated"), key: "updated_at", sortable: true },
                { title: "", key: "actions", sortable: false, align: "end", width: 52 },
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
            const sort = this.sortBy[0] || { key: "updated_at", order: "desc" };
            return {
                page: this.page,
                per_page: this.perPage,
                sort: sort.key,
                dir: sort.order,
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
            this.page = 1;
        },
        "filters.category_id"() {
            this.page = 1;
        },
        "filters.brand_id"() {
            this.page = 1;
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
        setTab(value) {
            this.tab = value;
            this.page = 1;
            this.selected = [];
        },
        parentName(cat) {
            return cat?.parent_id ? this.categories.find((c) => c.id === cat.parent_id)?.name || "" : "";
        },
        stockLabel(p) {
            if (p.stock_qty <= 0) return this.$t("Out of stock");
            return this.$t("{n} in stock", { n: this.$num(p.stock_qty) });
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
                    ...(this.page > 1 && { page: this.page }),
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
                const { data } = await api.post("/admin/products/bulk", { ids: this.selected, action });
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
