<template>
    <div>
        <PageHeader title="Stock Movements" subtitle="Every change in stock: sales, stock received, corrections, cancellations and returns, with who made it.">
            <v-btn-secondary prepend-icon="mdi-warehouse" to="/admin/inventory">{{ $t("Stock overview") }}</v-btn-secondary>
        </PageHeader>

        <section class="tt-box">
            <div class="tt-toolbar">
                <div class="tt-segments tt-segments-scroll" role="tablist" :aria-label="$t('Filter movements')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': reason === t.value }" :aria-selected="reason === t.value" @click="setReason(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value || "all"] ?? 0) }}</span>
                    </button>
                </div>
            </div>
            <div class="tt-toolbar">
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Product name or SKU')" clearable :aria-label="$t('Search')" />
                <v-input-trz v-model="from" type="date" density="compact" :aria-label="$t('From')" :max="to || undefined" />
                <v-input-trz v-model="to" type="date" density="compact" :aria-label="$t('To')" :min="from || undefined" />
                <div class="tt-toolbar-grow"></div>
                <span v-if="reason && units[reason]" class="tt-muted tt-small">{{ $t("Units: {n}", { n: signed(units[reason]) }) }}</span>
            </div>

            <DataTable v-model:server-options="serverOptions" :server-items-length="total" :headers="headers" :items="items" :loading="loading">
                <template #item-created_at="row">
                    <span class="tt-muted tt-nowrap-cell">{{ $date(row.created_at, { day: "numeric", month: "short", year: "numeric", hour: "numeric", minute: "2-digit" }) }}</span>
                </template>
                <template #item-product="row">
                    <div class="tt-two-line">
                        <router-link v-if="row.product" :to="`/admin/products/${row.product.id}`">{{ row.product.name }}</router-link>
                        <span v-else class="tt-muted">{{ $t("Deleted product") }}</span>
                        <small>{{ [row.variant_label, row.product?.sku].filter(Boolean).join(" · ") }}</small>
                    </div>
                </template>
                <template #item-reason="row">
                    <StatusChip :label="$t(mr(row.reason).label)" :tone="mr(row.reason).tone" />
                </template>
                <template #item-change="row">
                    <b class="tt-num" :class="row.change > 0 ? 'tt-up-text' : 'tt-danger-text'">{{ signed(row.change) }}</b>
                </template>
                <template #item-balance="row"><span class="tt-num">{{ $num(row.balance) }}</span></template>
                <template #item-note="row">
                    <div class="tt-two-line">
                        <router-link v-if="row.order_number" :to="`/admin/orders/${row.order_id}`">{{ row.order_number }}</router-link>
                        <span v-if="row.note">{{ row.note }}</span>
                        <small>{{ row.user?.name || (row.order_id ? $t("Customer") : "") }}</small>
                    </div>
                </template>
                <template #empty-message>
                    <EmptyState icon="mdi-history" title="No movements" text="Stock changes are listed here as soon as they happen." />
                </template>
            </DataTable>
        </section>
    </div>
</template>

<script>
import api from "@/api";
import { MOVEMENT_REASON } from "@/utils/orders";

export default {
    name: "Movements",
    data() {
        return {
            items: [],
            total: 0,
            counts: {},
            units: {},
            loading: true,
            reason: this.$route.query.reason || "",
            search: "",
            from: "",
            to: "",
            serverOptions: { page: 1, rowsPerPage: 25 },
            timer: null,
        };
    },
    computed: {
        tabs: () => [{ value: "", label: "All" }, ...Object.entries(MOVEMENT_REASON).map(([value, r]) => ({ value, label: r.label }))],
        headers() {
            return [
                { text: this.$t("When"), value: "created_at", width: 170 },
                { text: this.$t("Product"), value: "product", width: 260 },
                { text: this.$t("Type"), value: "reason" },
                { text: this.$t("Change"), value: "change" },
                { text: this.$t("Left"), value: "balance" },
                { text: this.$t("Order / note"), value: "note" },
            ];
        },
        params() {
            return {
                page: this.serverOptions.page,
                per_page: this.serverOptions.rowsPerPage,
                reason: this.reason || undefined,
                search: this.search || undefined,
                from: this.from || undefined,
                to: this.to || undefined,
            };
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
        mr: (r) => MOVEMENT_REASON[r] || { label: r, tone: "grey" },
        signed(n) {
            return (n > 0 ? "+" : n < 0 ? "−" : "") + this.$num(Math.abs(n));
        },
        setReason(v) {
            this.reason = v;
            this.serverOptions = { ...this.serverOptions, page: 1 };
        },
        load() {
            clearTimeout(this.timer);
            this.timer = setTimeout(this.fetch, 250);
        },
        async fetch() {
            this.loading = true;
            try {
                const { data } = await api.get("/admin/inventory/movements", { params: this.params });
                this.items = data.items.data;
                this.total = data.items.total;
                this.counts = data.counts;
                this.units = data.units;
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
