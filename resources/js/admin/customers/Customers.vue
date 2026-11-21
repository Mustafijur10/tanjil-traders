<template>
    <div>
        <PageHeader title="Customers" subtitle="People with an account on your website, with how much they buy. Guests who order without an account are found by phone in Orders." />

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
                <div class="tt-segments" role="tablist" :aria-label="$t('Filter customers')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': tab === t.value }" :aria-selected="tab === t.value" @click="setTab(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value] ?? 0) }}</span>
                    </button>
                </div>
                <div class="tt-toolbar-grow"></div>
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Name, phone or email')" clearable :aria-label="$t('Search')" />
            </div>

            <DataTable
                v-model:server-options="serverOptions"
                :server-items-length="total"
                :headers="headers"
                :items="items"
                :loading="loading"
                must-sort
                class="tt-edt-clickable"
                @click-row="(row) => $router.push(`/admin/customers/${row.id}`)"
            >
                <template #item-name="row">
                    <div class="tt-product-cell">
                        <span class="tt-initials is-small">{{ initials(row.name) }}</span>
                        <div class="tt-two-line">
                            <b>{{ row.name }}</b>
                            <small>{{ row.phone || row.email }}</small>
                        </div>
                    </div>
                </template>
                <template #item-orders_count="row"><span class="tt-num">{{ $num(row.orders_count) }}</span></template>
                <template #item-spent="row"><b class="tt-num">{{ $money(row.spent || 0) }}</b></template>
                <template #item-last_order_at="row">
                    <span class="tt-muted">{{ row.last_order_at ? $date(row.last_order_at) : "—" }}</span>
                </template>
                <template #item-created_at="row"><span class="tt-muted">{{ $date(row.created_at) }}</span></template>
                <template #item-is_active="row">
                    <StatusChip :label="$t(row.is_active ? 'Active' : 'Disabled')" :tone="row.is_active ? 'green' : 'red'" />
                </template>
                <template #empty-message>
                    <EmptyState :icon="search ? 'mdi-magnify' : 'mdi-account-group-outline'" :title="search ? 'No customers match' : 'No customers yet'" :text="search ? 'Try another search.' : 'Customers appear here when they create an account on your website.'" />
                </template>
            </DataTable>
        </section>
    </div>
</template>

<script>
import api from "@/api";

export default {
    name: "Customers",
    data() {
        return {
            items: [],
            total: 0,
            counts: {},
            summary: {},
            loading: true,
            tab: this.$route.query.tab || "all",
            search: "",
            serverOptions: { page: 1, rowsPerPage: 25, sortBy: "created_at", sortType: "desc" },
            timer: null,
        };
    },
    computed: {
        tabs: () => [
            { value: "all", label: "All" },
            { value: "buyers", label: "Have bought" },
            { value: "new", label: "New (30 days)" },
            { value: "disabled", label: "Disabled" },
        ],
        stats() {
            const c = this.counts;
            const s = this.summary;
            return [
                { key: "all", tab: "all", label: "Accounts", value: this.$num(c.all ?? 0), icon: "mdi-account-group-outline", tone: "green" },
                { key: "buyers", tab: "buyers", label: "Have bought", value: this.$num(c.buyers ?? 0), icon: "mdi-shopping-outline", tone: "blue" },
                { key: "repeat", tab: null, label: "Ordered 2+ times", value: this.$num(s.repeat ?? 0), icon: "mdi-repeat", tone: "violet" },
                { key: "new", tab: "new", label: "New this month", value: this.$num(c.new ?? 0), icon: "mdi-account-plus-outline", tone: "amber" },
                { key: "guest", tab: null, label: "Guest orders", value: this.$num(s.guest_orders ?? 0), icon: "mdi-account-question-outline", tone: "red" },
            ];
        },
        headers() {
            return [
                { text: this.$t("Customer"), value: "name", sortable: true, width: 260 },
                { text: this.$t("Orders"), value: "orders_count", sortable: true },
                { text: this.$t("Spent"), value: "spent", sortable: true },
                { text: this.$t("Last order"), value: "last_order_at", sortable: true },
                { text: this.$t("Joined"), value: "created_at", sortable: true },
                { text: this.$t("Status"), value: "is_active" },
            ];
        },
        params() {
            const o = this.serverOptions;
            return { page: o.page, per_page: o.rowsPerPage, sort: o.sortBy, dir: o.sortType, status: this.tab, search: this.search || undefined };
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
        initials: (n) => (n || "?").split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase(),
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
                const { data } = await api.get("/admin/customers", { params: this.params });
                this.items = data.items.data;
                this.total = data.items.total;
                this.counts = data.counts;
                this.summary = data.stats;
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
