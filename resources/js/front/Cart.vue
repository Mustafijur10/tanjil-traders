<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("Cart") }}</span>
        </nav>
        <h1 class="tt-doc-title">{{ $t("Shopping cart") }}</h1>

        <section v-if="!lines.length" class="tt-page-card">
            <EmptyState icon="mdi-cart-outline" title="Your cart is empty" text="Browse the shop and add something you like.">
                <v-btn-primary to="/shop" prepend-icon="mdi-storefront-outline">{{ $t("Start shopping") }}</v-btn-primary>
            </EmptyState>
        </section>

        <div v-else class="tt-checkout">
            <div class="tt-checkout-main">
                <v-alert v-for="i in issues" :key="i.key + i.type" :type="i.type === 'qty_reduced' ? 'info' : 'warning'" variant="tonal" density="compact" class="mb-3">
                    {{ $t(i.message) }}
                </v-alert>
                <section class="tt-page-card tt-cart-card">
                    <div class="tt-cart-card-head">
                        <b>{{ $tn(count, "{n} item", "{n} items") }}</b>
                        <button type="button" class="tt-flink" @click="clearDialog = true">{{ $t("Empty cart") }}</button>
                    </div>
                    <ul class="tt-cart-lines">
                        <CartLine v-for="l in lines" :key="key(l)" :line="l" />
                    </ul>
                </section>
                <router-link to="/shop" class="tt-back-link"><v-icon size="18">mdi-arrow-left</v-icon>{{ $t("Continue shopping") }}</router-link>
            </div>

            <aside class="tt-checkout-side">
                <section class="tt-page-card tt-summary">
                    <h2>{{ $t("Order summary") }}</h2>
                    <div class="tt-summary-row"><span>{{ $t("Subtotal") }}</span><b>{{ $money(subtotal) }}</b></div>
                    <div v-if="saved > 0" class="tt-summary-row is-save"><span>{{ $t("You save") }}</span><b>−{{ $money(saved) }}</b></div>
                    <div class="tt-summary-row"><span>{{ $t("Delivery") }}</span><span class="tt-muted">{{ $t("Calculated at checkout") }}</span></div>
                    <v-btn-primary block size="large" to="/checkout" :disabled="hasProblems" class="mt-4">{{ $t("Proceed to checkout") }}</v-btn-primary>
                    <p v-if="hasProblems" class="tt-small tt-danger-text mt-2">{{ $t("Remove the items that are no longer available to continue.") }}</p>
                    <ul class="tt-summary-trust">
                        <li><v-icon size="18">mdi-cash</v-icon>{{ $t("Cash on delivery available") }}</li>
                        <li><v-icon size="18">mdi-certificate-outline</v-icon>{{ $t("100% genuine products") }}</li>
                        <li><v-icon size="18">mdi-shield-check-outline</v-icon>{{ $t("Official warranty") }}</li>
                    </ul>
                </section>
            </aside>
        </div>

        <ConfirmDialog v-model="clearDialog" title="Empty your cart?" text="All items will be removed." confirm-text="Empty cart" @confirm="clear" />
    </div>
</template>

<script>
import CartLine from "@/front/components/CartLine.vue";
import { keyOf } from "@/store/cart";

export default {
    name: "Cart",
    components: { CartLine },
    data: () => ({ clearDialog: false }),
    computed: {
        lines() {
            return this.$store.state.cart.lines;
        },
        issues() {
            return this.$store.state.cart.issues;
        },
        count() {
            return this.$store.getters["cart/count"];
        },
        subtotal() {
            return this.$store.getters["cart/subtotal"];
        },
        saved() {
            return this.lines.filter((l) => l.available !== false && l.regular_price > l.price).reduce((n, l) => n + (l.regular_price - l.price) * l.qty, 0);
        },
        hasProblems() {
            return this.$store.getters["cart/hasProblems"];
        },
    },
    created() {
        this.$store.dispatch("cart/refresh").catch(() => {});
    },
    methods: {
        key: keyOf,
        clear() {
            this.$store.dispatch("cart/clear");
            this.clearDialog = false;
        },
    },
};
</script>
