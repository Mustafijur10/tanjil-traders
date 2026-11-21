<template>
    <div class="tt-order">
        <!-- Progress -->
        <section class="tt-page-card">
            <div class="tt-order-head">
                <div>
                    <span class="tt-muted tt-small">{{ $t("Order") }}</span>
                    <h2>{{ order.number }}</h2>
                    <span class="tt-muted tt-small">{{ $t("Placed on {date}", { date: $date(order.created_at, { dateStyle: "medium", timeStyle: "short" }) }) }}</span>
                </div>
                <StatusChip :label="$t(status.label)" :tone="status.tone" />
            </div>

            <ol v-if="!stopped" class="tt-steps" :aria-label="$t('Order progress')">
                <li v-for="(s, i) in steps" :key="s" :class="{ 'is-done': i < stepIndex, 'is-now': i === stepIndex }">
                    <span class="tt-steps-dot"><v-icon size="18">{{ i < stepIndex ? "mdi-check" : statusOf(s).icon }}</v-icon></span>
                    <b>{{ $t(statusOf(s).label) }}</b>
                    <small v-if="stepDate(s)">{{ $date(stepDate(s), { dateStyle: "medium" }) }}</small>
                </li>
            </ol>
            <p class="tt-order-hint"><v-icon size="18">{{ status.icon }}</v-icon>{{ $t(status.hint) }}</p>

            <div v-if="order.courier || order.tracking_code" class="tt-order-courier">
                <v-icon size="22">mdi-truck-delivery-outline</v-icon>
                <span>
                    <b>{{ order.courier || $t("Courier") }}</b>
                    <small v-if="order.tracking_code">{{ $t("Tracking code") }}: {{ order.tracking_code }}</small>
                </span>
                <v-btn-secondary v-if="order.tracking_link" size="small" :href="order.tracking_link" target="_blank" rel="noopener" append-icon="mdi-open-in-new">{{ $t("Track parcel") }}</v-btn-secondary>
            </div>

            <div v-if="$slots.actions" class="tt-order-actions"><slot name="actions" /></div>
        </section>

        <div class="tt-order-grid">
            <!-- Items and totals -->
            <section class="tt-page-card">
                <h3 class="tt-card-title">{{ $tn(order.items.length, "{n} item", "{n} items") }}</h3>
                <ul class="tt-order-items">
                    <li v-for="i in order.items" :key="i.id">
                        <span class="tt-thumb"><img v-if="i.image" :src="url(i.image)" alt="" loading="lazy" /><v-icon v-else size="18">mdi-image-outline</v-icon></span>
                        <span class="tt-order-item-name">
                            <router-link v-if="i.slug" :to="`/product/${i.slug}`">{{ i.name }}</router-link>
                            <span v-else>{{ i.name }}</span>
                            <small class="tt-muted">{{ [i.variant_label, `${$money(i.price)} × ${$num(i.qty)}`].filter(Boolean).join(" · ") }}</small>
                        </span>
                        <b>{{ $money(i.total) }}</b>
                    </li>
                </ul>
                <div class="tt-summary">
                    <div class="tt-summary-row"><span>{{ $t("Subtotal") }}</span><b>{{ $money(order.subtotal) }}</b></div>
                    <div class="tt-summary-row">
                        <span>{{ $t("Delivery") }}<small v-if="order.shipping" class="tt-muted"> · {{ $t(order.shipping.name) }}</small></span>
                        <b>{{ order.shipping_cost ? $money(order.shipping_cost) : $t("Free") }}</b>
                    </div>
                    <div v-if="order.discount" class="tt-summary-row is-save"><span>{{ order.coupon_code ? $t("Coupon {code}", { code: order.coupon_code }) : $t("Discount") }}</span><b>−{{ $money(order.discount) }}</b></div>
                    <div class="tt-summary-row is-total"><span>{{ $t("Total") }}</span><b>{{ $money(order.total) }}</b></div>
                </div>
            </section>

            <!-- Delivery and payment -->
            <section class="tt-page-card tt-order-info">
                <div>
                    <h3 class="tt-card-title">{{ $t("Delivery address") }}</h3>
                    <p>
                        <b>{{ order.name }}</b><br />
                        {{ order.phone }}<br />
                        {{ order.address }}, {{ order.city }}
                    </p>
                    <p v-if="order.note" class="tt-muted tt-small">{{ $t("Note") }}: {{ order.note }}</p>
                </div>
                <div>
                    <h3 class="tt-card-title">{{ $t("Payment") }}</h3>
                    <p>
                        {{ $t(paymentMethod) }}
                        <StatusChip :label="$t(payStatus.label)" :tone="payStatus.tone" />
                    </p>
                    <p v-if="order.transaction_id" class="tt-muted tt-small">{{ $t("Transaction ID") }}: {{ order.transaction_id }}</p>
                </div>
                <div v-if="order.returns?.length">
                    <h3 class="tt-card-title">{{ $t("Returns") }}</h3>
                    <p v-for="r in order.returns" :key="r.id" class="tt-order-return">
                        <StatusChip :label="$t(returnStatus(r.status).label)" :tone="returnStatus(r.status).tone" />
                        <span>{{ $t(reasons[r.reason] || r.reason) }} · {{ $date(r.created_at) }}</span>
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
/* Order view for customers: progress, courier tracking, items, totals, address and payment. */
import { fileUrl } from "@/api";
import { ORDER_STATUS, ORDER_STEPS, PAYMENT_METHOD, PAYMENT_STATUS, RETURN_STATUS, RETURN_REASONS } from "@/utils/orders";

export default {
    name: "OrderDetails",
    props: {
        order: { type: Object, required: true },
    },
    data: () => ({ steps: ORDER_STEPS, reasons: RETURN_REASONS }),
    computed: {
        status() {
            return ORDER_STATUS[this.order.status] || ORDER_STATUS.pending;
        },
        stopped() {
            return ["cancelled", "returned"].includes(this.order.status);
        },
        stepIndex() {
            return Math.max(0, this.steps.indexOf(this.order.status));
        },
        paymentMethod() {
            return PAYMENT_METHOD[this.order.payment_method] || this.order.payment_method;
        },
        payStatus() {
            return PAYMENT_STATUS[this.order.payment_status] || PAYMENT_STATUS.unpaid;
        },
    },
    methods: {
        url: fileUrl,
        statusOf: (s) => ORDER_STATUS[s],
        returnStatus: (s) => RETURN_STATUS[s] || { label: s, tone: "grey" },
        stepDate(s) {
            return this.order.timeline?.find((t) => t.status === s)?.at;
        },
    },
};
</script>
