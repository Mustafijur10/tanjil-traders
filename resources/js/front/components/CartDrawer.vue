<template>
    <v-navigation-drawer :model-value="open" temporary location="right" width="400" class="tt-cart-drawer" @update:model-value="$store.commit('cart/drawer', $event)">
        <div class="tt-cart-drawer-in">
            <div class="tt-cart-drawer-head">
                <b><v-icon size="22">mdi-cart-outline</v-icon>{{ $t("Your cart") }}</b>
                <span class="tt-muted">{{ $tn(count, "{n} item", "{n} items") }}</span>
                <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="close" />
            </div>

            <div v-if="!lines.length" class="tt-cart-drawer-empty">
                <EmptyState icon="mdi-cart-outline" title="Your cart is empty" text="Browse the shop and add something you like.">
                    <v-btn-primary to="/shop" @click="close">{{ $t("Start shopping") }}</v-btn-primary>
                </EmptyState>
            </div>

            <ul v-else class="tt-cart-lines">
                <CartLine v-for="l in lines" :key="key(l)" :line="l" compact @navigate="close" />
            </ul>

            <div v-if="lines.length" class="tt-cart-drawer-foot">
                <div class="tt-cart-sum">
                    <span>{{ $t("Subtotal") }}</span>
                    <b>{{ $money(subtotal) }}</b>
                </div>
                <p class="tt-muted tt-small">{{ $t("Delivery charge is added at checkout.") }}</p>
                <v-btn-primary block size="large" to="/checkout" :disabled="hasProblems" @click="close">{{ $t("Checkout") }}</v-btn-primary>
                <v-btn-secondary block to="/cart" @click="close">{{ $t("View cart") }}</v-btn-secondary>
            </div>
        </div>
    </v-navigation-drawer>
</template>

<script>
import CartLine from "@/front/components/CartLine.vue";
import { keyOf } from "@/store/cart";

export default {
    name: "CartDrawer",
    components: { CartLine },
    computed: {
        open() {
            return this.$store.state.cart.drawer;
        },
        lines() {
            return this.$store.state.cart.lines;
        },
        count() {
            return this.$store.getters["cart/count"];
        },
        subtotal() {
            return this.$store.getters["cart/subtotal"];
        },
        hasProblems() {
            return this.$store.getters["cart/hasProblems"];
        },
    },
    methods: {
        key: keyOf,
        close() {
            this.$store.commit("cart/drawer", false);
        },
    },
};
</script>
