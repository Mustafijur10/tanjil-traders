<template>
    <div v-if="loading && !orders.length" class="tt-skel-group"><span v-for="n in 3" :key="n" class="tt-skel"></span></div>
    <EmptyState v-else-if="!orders.length" icon="mdi-receipt-text-outline" title="No orders yet" text="When you place an order it shows up here.">
        <v-btn-primary to="/shop">{{ $t("Start shopping") }}</v-btn-primary>
    </EmptyState>
    <ul v-else class="tt-olist">
        <li v-for="o in orders" :key="o.number">
            <router-link :to="`/account/orders/${o.number}`" class="tt-olist-row">
                <span class="tt-olist-imgs">
                    <span v-for="(i, n) in o.items.slice(0, 3)" :key="n" class="tt-thumb"><img v-if="i.image" :src="url(i.image)" alt="" loading="lazy" /><v-icon v-else size="16">mdi-image-outline</v-icon></span>
                </span>
                <span class="tt-olist-main">
                    <b>{{ o.number }}</b>
                    <small class="tt-muted tt-ellipsis">{{ o.items.map((i) => i.name).join(", ") }}</small>
                    <small class="tt-muted">{{ $date(o.created_at) }}</small>
                </span>
                <span class="tt-olist-end">
                    <StatusChip :label="$t(status(o.status).label)" :tone="status(o.status).tone" />
                    <b>{{ $money(o.total) }}</b>
                </span>
                <v-icon size="20" class="tt-olist-chev">mdi-chevron-right</v-icon>
            </router-link>
        </li>
    </ul>
</template>

<script>
import { fileUrl } from "@/api";
import { ORDER_STATUS } from "@/utils/orders";

export default {
    name: "OrderList",
    props: {
        orders: { type: Array, default: () => [] },
        loading: { type: Boolean, default: false },
    },
    methods: {
        url: fileUrl,
        status: (s) => ORDER_STATUS[s] || { label: s, tone: "grey" },
    },
};
</script>
