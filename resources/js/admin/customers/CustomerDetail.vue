<template>
    <div>
        <PageHeader :title="c ? c.name : $t('Customer')" :subtitle="c ? $t('Customer since {date}', { date: $date(c.created_at, { dateStyle: 'medium' }) }) : ''">
            <template v-if="c">
                <v-btn-secondary prepend-icon="mdi-phone-outline" :href="c.phone ? `tel:${c.phone}` : undefined" :disabled="!c.phone">{{ $t("Call") }}</v-btn-secondary>
                <v-btn-primary prepend-icon="mdi-plus" :to="{ path: '/admin/orders/new' }">{{ $t("Create order") }}</v-btn-primary>
            </template>
        </PageHeader>

        <div v-if="!c" class="tt-box tt-box-body tt-skel-group"><span v-for="n in 5" :key="n" class="tt-skel"></span></div>

        <template v-else>
            <div class="tt-stat-tiles">
                <div class="tt-stat-tile is-green"><span class="tt-stat-ico"><v-icon size="20">mdi-receipt-text-outline</v-icon></span><span><small>{{ $t("Orders") }}</small><b>{{ $num(stats.orders) }}</b></span></div>
                <div class="tt-stat-tile is-blue"><span class="tt-stat-ico"><v-icon size="20">mdi-cash-multiple</v-icon></span><span><small>{{ $t("Spent") }}</small><b>{{ $money(stats.spent) }}</b></span></div>
                <div class="tt-stat-tile is-violet"><span class="tt-stat-ico"><v-icon size="20">mdi-scale-balance</v-icon></span><span><small>{{ $t("Average order") }}</small><b>{{ $money(stats.average) }}</b></span></div>
                <div class="tt-stat-tile is-amber"><span class="tt-stat-ico"><v-icon size="20">mdi-heart-outline</v-icon></span><span><small>{{ $t("Wishlist") }}</small><b>{{ $num(stats.wishlist) }}</b></span></div>
                <div class="tt-stat-tile is-red"><span class="tt-stat-ico"><v-icon size="20">mdi-close-circle-outline</v-icon></span><span><small>{{ $t("Cancelled / returned") }}</small><b>{{ $num(stats.cancelled) }}</b></span></div>
            </div>

            <div class="tt-detail">
                <div class="tt-detail-main">
                    <section class="tt-box">
                        <div class="tt-box-head">
                            <span class="tt-box-icon"><v-icon size="20">mdi-receipt-text-outline</v-icon></span>
                            <div class="flex-grow-1"><h2>{{ $t("Orders") }}</h2></div>
                        </div>
                        <DataTable :headers="headers" :items="orders" class="tt-edt-clickable" :hide-footer="orders.length <= 10" @click-row="(row) => $router.push(`/admin/orders/${row.id}`)">
                            <template #item-order_number="row">
                                <div class="tt-two-line"><b>{{ row.order_number }}</b><small>{{ $date(row.created_at) }}</small></div>
                            </template>
                            <template #item-items_count="row">{{ $tn(row.items_count, "{n} item", "{n} items") }}</template>
                            <template #item-total="row"><b class="tt-num">{{ $money(row.total) }}</b></template>
                            <template #item-status="row"><StatusChip :label="$t(st(row.status).label)" :tone="st(row.status).tone" /></template>
                            <template #empty-message><EmptyState icon="mdi-receipt-text-outline" title="No orders yet" /></template>
                        </DataTable>
                    </section>
                </div>

                <aside class="tt-detail-side">
                    <section class="tt-box">
                        <div class="tt-box-head">
                            <span class="tt-box-icon"><v-icon size="20">mdi-card-account-details-outline</v-icon></span>
                            <div class="flex-grow-1"><h2>{{ $t("Contact") }}</h2></div>
                        </div>
                        <div class="tt-box-body tt-detail-facts">
                            <p v-if="c.phone"><v-icon size="16">mdi-phone-outline</v-icon><a :href="`tel:${c.phone}`">{{ c.phone }}</a></p>
                            <p v-if="c.email"><v-icon size="16">mdi-email-outline</v-icon><a :href="`mailto:${c.email}`">{{ c.email }}</a></p>
                            <p v-if="address"><v-icon size="16">mdi-map-marker-outline</v-icon>{{ address.address }}, {{ address.city }}</p>
                            <p v-if="stats.first_order_at"><v-icon size="16">mdi-calendar-start</v-icon>{{ $t("First order {date}", { date: $date(stats.first_order_at) }) }}</p>
                            <p><v-icon size="16">mdi-star-outline</v-icon>{{ $tn(stats.reviews, "{n} review", "{n} reviews") }}</p>
                        </div>
                    </section>
                    <section class="tt-box">
                        <div class="tt-box-head">
                            <span class="tt-box-icon"><v-icon size="20">mdi-account-lock-outline</v-icon></span>
                            <div class="flex-grow-1"><h2>{{ $t("Account") }}</h2><p>{{ $t("A disabled customer cannot sign in. Their orders are kept.") }}</p></div>
                        </div>
                        <div class="tt-box-body">
                            <v-switch-trz :model-value="c.is_active" :label="$t(c.is_active ? 'Can sign in' : 'Disabled')" :loading="busy" @update:model-value="toggle" />
                        </div>
                    </section>
                </aside>
            </div>
        </template>
    </div>
</template>

<script>
import api from "@/api";
import { ORDER_STATUS } from "@/utils/orders";

export default {
    name: "CustomerDetail",
    data: () => ({ c: null, stats: {}, orders: [], address: null, busy: false }),
    computed: {
        headers() {
            return [
                { text: this.$t("Order"), value: "order_number" },
                { text: this.$t("Items"), value: "items_count" },
                { text: this.$t("Total"), value: "total" },
                { text: this.$t("Status"), value: "status" },
            ];
        },
    },
    watch: { "$route.params.id": { immediate: true, handler: "load" } },
    methods: {
        st: (s) => ORDER_STATUS[s] || { label: s, tone: "grey" },
        async load(id) {
            if (!id) return;
            const { data } = await api.get(`/admin/customers/${id}`);
            this.c = data.customer;
            this.stats = data.stats;
            this.orders = data.orders;
            this.address = data.address;
        },
        async toggle(value) {
            this.busy = true;
            try {
                this.c = (await api.put(`/admin/customers/${this.c.id}`, { is_active: value })).data;
                this.$store.dispatch("notify", this.$t(value ? "Customer can sign in again." : "Customer disabled and signed out."));
            } finally {
                this.busy = false;
            }
        },
    },
};
</script>
