<template>
    <div>
        <PageHeader title="Stock Overview" subtitle="How much of everything you have, what it is worth, and what to reorder. Add new stock or correct a count here.">
            <ExportMenu :build="exportSpec" />
            <v-btn-secondary prepend-icon="mdi-history" to="/admin/inventory/movements">{{ $t("Stock movements") }}</v-btn-secondary>
        </PageHeader>

        <div class="tt-stat-tiles">
            <button v-for="s in tiles" :key="s.key" type="button" class="tt-stat-tile" :class="['is-' + s.tone, { 'is-on': s.tab && tab === s.tab }]" :disabled="!s.tab" @click="s.tab && setTab(s.tab)">
                <span class="tt-stat-ico"><v-icon :icon="s.icon" size="20" /></span>
                <span>
                    <small>{{ $t(s.label) }}</small>
                    <b>{{ s.value }}</b>
                </span>
            </button>
        </div>
        <v-alert v-if="stats.missing_cost" type="info" variant="tonal" density="compact" class="mb-4">
            {{ $tn(stats.missing_cost, "{n} product in stock has no cost price, so its value is not counted. Add it on the product page.", "{n} products in stock have no cost price, so their value is not counted. Add it on the product page.") }}
        </v-alert>

        <section class="tt-box">
            <div class="tt-toolbar">
                <div class="tt-segments" role="tablist" :aria-label="$t('Filter stock')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': tab === t.value }" :aria-selected="tab === t.value" @click="setTab(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value] ?? 0) }}</span>
                    </button>
                </div>
                <div class="tt-toolbar-grow"></div>
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Name or SKU')" clearable :aria-label="$t('Search')" />
            </div>

            <DataTable v-model:server-options="serverOptions" :server-items-length="total" :headers="headers" :items="items" :loading="loading" must-sort>
                <template #item-name="row">
                    <div class="tt-product-cell">
                        <span class="tt-thumb"><img v-if="row.image" :src="url(row.image)" alt="" loading="lazy" /><v-icon v-else size="20">mdi-image-off-outline</v-icon></span>
                        <div class="tt-ellipsis">
                            <router-link :to="`/admin/products/${row.id}`" class="tt-ellipsis"><b>{{ row.name }}</b></router-link>
                            <span class="tt-muted tt-small">{{ [row.sku, row.category, row.status === "draft" ? $t("Draft") : ""].filter(Boolean).join(" · ") }}</span>
                        </div>
                    </div>
                </template>
                <template #item-stock_qty="row">
                    <div v-if="row.variants.length" class="tt-stock-variants">
                        <button v-for="v in row.variants" :key="v.id" type="button" class="tt-stock-chip" :class="'is-' + tone(v.stock_qty)" :title="$t('Adjust {name}', { name: v.label })" @click="openAdjust(row, v)">
                            {{ v.label }} <b>{{ $num(v.stock_qty) }}</b>
                        </button>
                    </div>
                    <button v-else type="button" class="tt-stock-chip is-big" :class="'is-' + tone(row.stock_qty)" @click="openAdjust(row)">
                        <b>{{ $num(row.stock_qty) }}</b> {{ $t("in stock") }}
                    </button>
                </template>
                <template #item-value="row">
                    <div class="tt-two-line tt-num">
                        <b>{{ $money(row.value) }}</b>
                        <small>{{ row.cost_price ? $t("cost {price} each", { price: $money(row.cost_price) }) : $t("no cost price") }}</small>
                    </div>
                </template>
                <template #item-actions="row">
                    <v-btn-tertiary size="small" prepend-icon="mdi-plus-minus-variant" @click="openAdjust(row, row.variants[0] || null)">{{ $t("Adjust") }}</v-btn-tertiary>
                </template>
                <template #empty-message>
                    <EmptyState icon="mdi-warehouse" :title="search ? 'Nothing matches' : 'Nothing here'" :text="tab === 'out' ? 'Great, nothing is out of stock.' : 'Try another search or filter.'" />
                </template>
            </DataTable>
        </section>

        <v-dialog-trz v-model="dialog" max-width="480" :persistent="saving">
            <v-card v-if="adjusting">
                <div class="tt-box-head">
                    <div class="flex-grow-1">
                        <h2>{{ $t("Adjust stock") }}</h2>
                        <p>{{ adjusting.name }}</p>
                    </div>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5 tt-vstack">
                    <v-select-trz v-if="adjusting.variants.length" v-model="form.variant_id" :items="variantOptions" :label="$t('Option')" />
                    <div class="tt-segments is-full" role="tablist">
                        <button v-for="m in modes" :key="m.value" type="button" role="tab" :class="{ 'is-on': form.mode === m.value }" @click="setMode(m.value)">{{ $t(m.label) }}</button>
                    </div>
                    <v-input-trz v-model.number="form.qty" type="number" min="0" :label="$t(form.mode === 'set' ? 'Counted quantity' : 'Quantity')" :error-messages="errors.qty" autofocus />
                    <p class="tt-adjust-preview">
                        {{ $t("Now") }} <b>{{ $num(current) }}</b>
                        <v-icon size="16">mdi-arrow-right</v-icon>
                        {{ $t("after") }} <b :class="{ 'tt-danger-text': after < 0 }">{{ $num(after) }}</b>
                    </p>
                    <v-input-trz v-model.trim="form.note" :label="$t('Note (optional)')" :placeholder="$t(form.mode === 'add' ? 'Supplier, invoice no.' : 'Damaged, lost, counted on…')" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="after < 0 || after === current" @click="saveAdjust">{{ $t("Save") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>
    </div>
</template>

<script>
import api, { fileUrl, fieldErrors, fetchAll } from "@/api";

export default {
    name: "Stock",
    data() {
        return {
            items: [],
            total: 0,
            counts: {},
            stats: {},
            low: 5,
            loading: true,
            tab: this.$route.query.tab || "all",
            search: "",
            serverOptions: { page: 1, rowsPerPage: 25, sortBy: "stock_qty", sortType: "asc" },
            timer: null,
            dialog: false,
            adjusting: null,
            form: {},
            errors: {},
            saving: false,
        };
    },
    computed: {
        tabs: () => [
            { value: "all", label: "All" },
            { value: "low", label: "Low stock" },
            { value: "out", label: "Out of stock" },
            { value: "in", label: "In stock" },
        ],
        modes: () => [
            { value: "add", label: "Stock in" },
            { value: "remove", label: "Take out" },
            { value: "set", label: "Set count" },
        ],
        tiles() {
            const s = this.stats;
            const c = this.counts;
            return [
                { key: "units", tab: null, label: "Units in stock", value: this.$num(s.units ?? 0), icon: "mdi-package-variant-closed", tone: "green" },
                { key: "cost", tab: null, label: "Stock value (cost)", value: this.$money(s.cost_value ?? 0), icon: "mdi-cash-multiple", tone: "blue" },
                { key: "retail", tab: null, label: "Sale value", value: this.$money(s.retail_value ?? 0), icon: "mdi-tag-outline", tone: "violet" },
                { key: "low", tab: "low", label: "Low stock", value: this.$num(c.low ?? 0), icon: "mdi-alert-outline", tone: "amber" },
                { key: "out", tab: "out", label: "Out of stock", value: this.$num(c.out ?? 0), icon: "mdi-package-variant-remove", tone: "red" },
            ];
        },
        headers() {
            return [
                { text: this.$t("Product"), value: "name", sortable: true, width: 320 },
                { text: this.$t("Stock"), value: "stock_qty", sortable: true },
                { text: this.$t("Value"), value: "value", sortable: true },
                { text: "", value: "actions", width: 110 },
            ];
        },
        params() {
            const o = this.serverOptions;
            return { page: o.page, per_page: o.rowsPerPage, sort: o.sortBy, dir: o.sortType, status: this.tab, search: this.search || undefined };
        },
        variantOptions() {
            return this.adjusting.variants.map((v) => ({ value: v.id, title: `${v.label} (${this.$num(v.stock_qty)})` }));
        },
        current() {
            if (!this.adjusting) return 0;
            const v = this.adjusting.variants.find((x) => x.id === this.form.variant_id);
            return v ? v.stock_qty : this.adjusting.stock_qty;
        },
        after() {
            const q = Number(this.form.qty) || 0;
            return { add: this.current + q, remove: this.current - q, set: q }[this.form.mode];
        },
    },
    watch: {
        params: { deep: true, handler: "load" },
        search() {
            this.serverOptions = { ...this.serverOptions, page: 1 };
        },
    },
    created() {
        this.load();
    },
    methods: {
        url: fileUrl,
        tone(q) {
            return q <= 0 ? "red" : q <= this.low ? "amber" : "green";
        },
        /** One row per product, and one per option for products with options. */
        async exportSpec() {
            const products = await fetchAll("/admin/inventory", { ...this.params, page: undefined, per_page: undefined });
            const rows = products.flatMap((p) =>
                p.variants.length
                    ? p.variants.map((v) => ({ name: `${p.name} · ${v.label}`, sku: v.sku || p.sku, category: p.category, stock_qty: v.stock_qty, cost_price: p.cost_price, price: p.price, value: v.stock_qty * (p.cost_price ?? p.price ?? 0) }))
                    : [{ name: p.name, sku: p.sku, category: p.category, stock_qty: p.stock_qty, cost_price: p.cost_price, price: p.price, value: p.value }]
            );
            const s = this.stats;
            return {
                title: "Stock report",
                filename: "stock",
                subtitle: this.$t(this.tabs.find((t) => t.value === this.tab)?.label || "All"),
                kpis: [
                    { label: "Units in stock", value: s.units, type: "number" },
                    { label: "Stock value (cost)", value: s.cost_value, type: "money" },
                    { label: "Sale value", value: s.retail_value, type: "money" },
                ],
                tables: [
                    {
                        title: "Stock",
                        columns: [
                            { text: "Product", value: "name" },
                            { text: "SKU", value: "sku" },
                            { text: "Category", value: "category" },
                            { text: "In stock", value: "stock_qty", type: "number" },
                            { text: "Cost price", value: "cost_price", type: "money" },
                            { text: "Price", value: "price", type: "money" },
                            { text: "Stock value", value: "value", type: "money" },
                        ],
                        rows,
                    },
                ],
            };
        },
        setTab(v) {
            this.tab = v;
            this.serverOptions = { ...this.serverOptions, page: 1 };
        },
        load() {
            clearTimeout(this.timer);
            this.timer = setTimeout(this.fetch, 250);
        },
        async fetch() {
            this.loading = true;
            try {
                const { data } = await api.get("/admin/inventory", { params: this.params });
                this.items = data.items.data;
                this.total = data.items.total;
                this.counts = data.counts;
                this.stats = data.stats;
                this.low = data.low;
            } finally {
                this.loading = false;
            }
        },
        openAdjust(row, variant = null) {
            this.adjusting = row;
            this.form = { variant_id: variant?.id || null, mode: "add", qty: null, note: "" };
            this.errors = {};
            this.dialog = true;
        },
        setMode(m) {
            this.form.mode = m;
            if (m === "set") this.form.qty = this.current;
        },
        async saveAdjust() {
            this.saving = true;
            this.errors = {};
            try {
                await api.post(
                    "/admin/inventory/adjust",
                    { product_id: this.adjusting.id, ...this.form, qty: Number(this.form.qty) || 0, reason: this.form.mode === "add" ? "restock" : "adjustment" },
                    { quiet: true }
                );
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Stock updated."));
                this.fetch();
            } catch (e) {
                this.errors = fieldErrors(e);
                if (this.errors.variant_id) this.errors.qty = this.errors.variant_id;
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
