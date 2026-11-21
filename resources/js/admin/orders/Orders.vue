<template>
    <div>
        <PageHeader title="All Orders" subtitle="Confirm new orders, pack them, hand them to a courier and follow them to the door.">
            <ExportMenu :build="exportSpec" />
            <v-btn-secondary prepend-icon="mdi-truck-outline" to="/admin/couriers">{{ $t("Couriers") }}</v-btn-secondary>
            <v-btn-primary prepend-icon="mdi-plus" to="/admin/orders/new">{{ $t("Create order") }}</v-btn-primary>
        </PageHeader>

        <div class="tt-stat-tiles">
            <button v-for="s in stats" :key="s.key" type="button" class="tt-stat-tile" :class="['is-' + s.tone, { 'is-on': s.tab && tab === s.tab }]" :disabled="!s.tab" @click="s.tab && setTab(s.tab)">
                <span class="tt-stat-ico"><v-icon :icon="s.icon" size="20" /></span>
                <span>
                    <small>{{ $t(s.label) }}</small>
                    <b>{{ s.value }}</b>
                </span>
            </button>
        </div>

        <section class="tt-box">
            <div class="tt-toolbar">
                <div class="tt-segments tt-segments-scroll" role="tablist" :aria-label="$t('Filter orders')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': tab === t.value }" :aria-selected="tab === t.value" @click="setTab(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value] ?? 0) }}</span>
                    </button>
                </div>
            </div>
            <div class="tt-toolbar">
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Order no., name, phone or tracking code')" clearable :aria-label="$t('Search')" />
                <v-select-trz v-model="filters.payment_status" density="compact" :items="paymentStatusOptions" :placeholder="$t('Any payment')" clearable :aria-label="$t('Payment status')" />
                <v-select-trz v-model="filters.payment_method" density="compact" :items="paymentMethodOptions" :placeholder="$t('Any method')" clearable :aria-label="$t('Payment method')" />
                <v-select-trz v-model="filters.courier_id" density="compact" :items="courierOptions" :placeholder="$t('Any courier')" clearable :aria-label="$t('Courier')" />
                <v-input-trz v-model="filters.from" type="date" density="compact" :aria-label="$t('From')" :max="filters.to || undefined" />
                <v-input-trz v-model="filters.to" type="date" density="compact" :aria-label="$t('To')" :min="filters.from || undefined" />
            </div>

            <div v-if="selected.length" class="tt-bulk-bar" role="region" :aria-label="$t('Bulk actions')">
                <span>{{ $tn(selected.length, "{n} order selected", "{n} orders selected") }}</span>
                <v-btn-tertiary size="small" prepend-icon="mdi-check" @click="bulk('confirmed')">{{ $t("Confirm") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" prepend-icon="mdi-package-variant" @click="bulk('processing')">{{ $t("Packing") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" prepend-icon="mdi-truck-fast-outline" @click="shipDialog = true">{{ $t("Shipped") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" prepend-icon="mdi-home-check-outline" @click="bulk('delivered')">{{ $t("Delivered") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" prepend-icon="mdi-printer-outline" @click="printSelected('invoice')">{{ $t("Invoices") }}</v-btn-tertiary>
                <v-btn-tertiary size="small" prepend-icon="mdi-package-variant-closed" @click="printSelected('slip')">{{ $t("Packing slips") }}</v-btn-tertiary>
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
                @click-row="(row) => $router.push(`/admin/orders/${row.id}`)"
            >
                <template #item-order_number="row">
                    <div class="tt-two-line">
                        <b>{{ row.order_number }}</b>
                        <small>{{ $date(row.created_at, { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }) }}<template v-if="row.source === 'admin'"> · {{ $t("by staff") }}</template></small>
                    </div>
                </template>
                <template #item-name="row">
                    <div class="tt-two-line">
                        <span>{{ row.name }}</span>
                        <small>{{ row.phone }} · {{ row.city }}</small>
                    </div>
                </template>
                <template #item-items_count="row">
                    <span class="tt-num">{{ $tn(row.units || row.items_count, "{n} item", "{n} items") }}</span>
                </template>
                <template #item-total="row">
                    <div class="tt-two-line tt-num">
                        <b>{{ $money(row.total) }}</b>
                        <small>{{ $t(methodLabel(row.payment_method)) }}</small>
                    </div>
                </template>
                <template #item-payment_status="row">
                    <StatusChip :label="$t(pay(row.payment_status).label)" :tone="pay(row.payment_status).tone" />
                </template>
                <template #item-status="row">
                    <StatusChip :label="$t(st(row.status).label)" :tone="st(row.status).tone" />
                </template>
                <template #item-courier="row">
                    <div v-if="row.courier" class="tt-two-line">
                        <span>{{ row.courier.name }}</span>
                        <small v-if="row.tracking_code">{{ row.tracking_code }}</small>
                    </div>
                    <span v-else class="tt-muted">—</span>
                </template>
                <template #item-actions="row">
                    <v-menu location="bottom end">
                        <template #activator="{ props }">
                            <v-btn-tertiary v-bind="props" icon="mdi-dots-vertical" size="small" :aria-label="$t('More actions')" @click.stop />
                        </template>
                        <v-list density="compact" min-width="200">
                            <v-list-item prepend-icon="mdi-eye-outline" :title="$t('Open')" :to="`/admin/orders/${row.id}`" />
                            <v-list-item prepend-icon="mdi-printer-outline" :title="$t('Print invoice')" :href="`/admin/orders/print?ids=${row.id}&type=invoice`" target="_blank" />
                            <v-list-item prepend-icon="mdi-package-variant-closed" :title="$t('Print packing slip')" :href="`/admin/orders/print?ids=${row.id}&type=slip`" target="_blank" />
                            <v-list-item v-if="row.phone" prepend-icon="mdi-phone-outline" :title="$t('Call customer')" :href="`tel:${row.phone}`" />
                        </v-list>
                    </v-menu>
                </template>
                <template #empty-message>
                    <EmptyState
                        :icon="hasFilters ? 'mdi-magnify' : 'mdi-receipt-text-outline'"
                        :title="hasFilters ? 'No orders match' : 'No orders yet'"
                        :text="hasFilters ? 'Try another search or filter.' : 'Orders from the website and the ones you create here show up in this list.'"
                    >
                        <v-btn-primary v-if="!hasFilters" prepend-icon="mdi-plus" to="/admin/orders/new">{{ $t("Create order") }}</v-btn-primary>
                    </EmptyState>
                </template>
            </DataTable>
        </section>

        <v-dialog-trz v-model="shipDialog" max-width="440">
            <v-card>
                <div class="tt-box-head"><h2>{{ $t("Mark as shipped") }}</h2></div>
                <v-card-text class="pa-5">
                    <p class="mb-4">{{ $tn(selected.length, "{n} order will be marked shipped.", "{n} orders will be marked shipped.") }}</p>
                    <v-select-trz v-model="shipCourier" :items="courierOptions" :label="$t('Courier')" clearable :hint="$t('You can add tracking codes on each order later.')" persistent-hint />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="shipDialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="busy" @click="bulk('shipped', shipCourier)">{{ $t("Mark shipped") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>
    </div>
</template>

<script>
import api, { fetchAll } from "@/api";
import { ORDER_STATUS, PAYMENT_STATUS, PAYMENT_METHOD, options } from "@/utils/orders";

const TABS = [
    { value: "all", label: "All" },
    { value: "pending", label: "New" },
    { value: "confirmed", label: "Confirmed" },
    { value: "processing", label: "Packing" },
    { value: "shipped", label: "Shipped" },
    { value: "delivered", label: "Delivered" },
    { value: "cancelled", label: "Cancelled" },
    { value: "returned", label: "Returned" },
];

export default {
    name: "Orders",
    data() {
        const q = this.$route.query;
        return {
            items: [],
            total: 0,
            counts: {},
            summary: {},
            couriers: [],
            loading: true,
            selected: [],
            tab: q.tab || "all",
            search: q.q || "",
            filters: { payment_status: q.payment || null, payment_method: null, courier_id: null, from: q.from || "", to: q.to || "" },
            serverOptions: { page: Number(q.page) || 1, rowsPerPage: 25, sortBy: "created_at", sortType: "desc" },
            loadTimer: null,
            busy: false,
            shipDialog: false,
            shipCourier: null,
        };
    },
    computed: {
        tabs: () => TABS,
        stats() {
            const s = this.summary;
            return [
                { key: "today", tab: null, label: "Orders today", value: this.$num(s.today_orders ?? 0), icon: "mdi-calendar-today", tone: "green" },
                { key: "sales", tab: null, label: "Sales today", value: this.$money(s.today_sales ?? 0), icon: "mdi-cash-multiple", tone: "blue" },
                { key: "pending", tab: "pending", label: "Waiting to confirm", value: this.$num(s.pending ?? 0), icon: "mdi-phone-check-outline", tone: "amber" },
                { key: "ship", tab: "to_ship", label: "To pack and ship", value: this.$num(s.to_ship ?? 0), icon: "mdi-package-variant", tone: "violet" },
                { key: "unpaid", tab: null, label: "Cash to collect", value: this.$money(s.unpaid_value ?? 0), icon: "mdi-hand-coin-outline", tone: "red" },
            ];
        },
        headers() {
            return [
                { text: this.$t("Order"), value: "order_number", sortable: true, width: 150 },
                { text: this.$t("Customer"), value: "name", sortable: true, width: 200 },
                { text: this.$t("Items"), value: "items_count" },
                { text: this.$t("Total"), value: "total", sortable: true },
                { text: this.$t("Payment"), value: "payment_status" },
                { text: this.$t("Status"), value: "status" },
                { text: this.$t("Courier"), value: "courier" },
                { text: "", value: "actions", width: 56 },
            ];
        },
        paymentStatusOptions() {
            return options(PAYMENT_STATUS, this.$t);
        },
        paymentMethodOptions() {
            return options(PAYMENT_METHOD, this.$t);
        },
        courierOptions() {
            return this.couriers.map((c) => ({ value: c.id, title: c.name }));
        },
        hasFilters() {
            return !!(this.search || this.tab !== "all" || Object.values(this.filters).some(Boolean));
        },
        params() {
            const o = this.serverOptions;
            const f = this.filters;
            return {
                page: o.page,
                per_page: o.rowsPerPage,
                sort: o.sortBy || "created_at",
                dir: o.sortType || "desc",
                status: this.tab,
                search: this.search || undefined,
                payment_status: f.payment_status || undefined,
                payment_method: f.payment_method || undefined,
                courier_id: f.courier_id || undefined,
                from: f.from || undefined,
                to: f.to || undefined,
            };
        },
    },
    watch: {
        params: { handler: "load", deep: true },
        search() {
            this.firstPage();
        },
        filters: {
            deep: true,
            handler() {
                this.firstPage();
            },
        },
    },
    created() {
        this.load();
    },
    methods: {
        st: (s) => ORDER_STATUS[s] || { label: s, tone: "grey" },
        pay: (s) => PAYMENT_STATUS[s] || { label: s, tone: "grey" },
        methodLabel: (m) => PAYMENT_METHOD[m] || m,
        firstPage() {
            this.serverOptions = { ...this.serverOptions, page: 1 };
        },
        setTab(value) {
            this.tab = value;
            this.selected = [];
            this.firstPage();
        },
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
                    ...(this.filters.from && { from: this.filters.from }),
                    ...(this.filters.to && { to: this.filters.to }),
                    ...(this.serverOptions.page > 1 && { page: this.serverOptions.page }),
                },
            });
            try {
                const { data } = await api.get("/admin/orders", { params: this.params });
                this.items = data.items.data;
                this.total = data.items.total;
                this.counts = data.counts;
                this.summary = data.stats;
                this.couriers = data.couriers;
            } finally {
                this.loading = false;
            }
        },
        async bulk(status, courierId = null) {
            this.busy = true;
            try {
                const { data } = await api.post("/admin/orders/bulk", { ids: this.selected.map((o) => o.id), status, courier_id: courierId });
                const msg = data.skipped.length
                    ? this.$t("{done} updated. Skipped {list}: that step is not possible for them.", { done: data.done, list: data.skipped.join(", ") })
                    : this.$tn(data.done, "{n} order updated.", "{n} orders updated.");
                this.$store.dispatch("notify", { text: msg, type: data.skipped.length ? "warning" : "success" });
                this.selected = [];
                this.shipDialog = false;
                this.fetch();
            } finally {
                this.busy = false;
            }
        },
        /** Every order matching the current tab, search and filters. */
        async exportSpec() {
            const rows = await fetchAll("/admin/orders", { ...this.params, page: undefined, per_page: undefined });
            const tab = TABS.find((t) => t.value === this.tab);
            return {
                title: "Orders",
                filename: "orders",
                subtitle: [this.$t(tab?.label || "All"), this.filters.from && this.filters.to ? `${this.$date(this.filters.from)} – ${this.$date(this.filters.to)}` : "", this.search].filter(Boolean).join(" · "),
                kpis: [
                    { label: "Orders", value: rows.length, type: "number" },
                    { label: "Total value", value: rows.reduce((n, r) => n + Number(r.total), 0), type: "money" },
                ],
                tables: [
                    {
                        title: "Orders",
                        columns: [
                            { text: "Order", value: "order_number" },
                            { text: "Date", value: "created_at", type: "datetime" },
                            { text: "Customer", value: "name" },
                            { text: "Phone", value: "phone" },
                            { text: "City", value: "city" },
                            { text: "Items", value: "units", type: "number" },
                            { text: "Total", value: "total", type: "money" },
                            { text: "Payment", value: "payment_method", get: (r) => this.$t(this.methodLabel(r.payment_method)) },
                            { text: "Paid", value: "payment_status", get: (r) => this.$t(this.pay(r.payment_status).label) },
                            { text: "Status", value: "status", get: (r) => this.$t(this.st(r.status).label) },
                            { text: "Courier", value: "courier", get: (r) => [r.courier?.name, r.tracking_code].filter(Boolean).join(" · ") },
                        ],
                        rows,
                    },
                ],
            };
        },
        printSelected(type) {
            window.open(`/admin/orders/print?ids=${this.selected.map((o) => o.id).join(",")}&type=${type}`, "_blank");
        },
    },
};
</script>
