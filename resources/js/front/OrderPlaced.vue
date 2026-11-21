<template>
    <div class="tt-wrap tt-page">
        <section class="tt-page-card tt-thanks">
            <span class="tt-thanks-icon"><v-icon size="40">mdi-check-bold</v-icon></span>
            <h1>{{ $t("Thank you! Your order is placed.") }}</h1>
            <p>
                {{ $t("Your order number is") }} <b class="tt-thanks-number">{{ number }}</b>.
                {{ $t("We will call you soon to confirm it.") }}
            </p>
            <div class="tt-thanks-actions">
                <v-btn-primary to="/shop" prepend-icon="mdi-storefront-outline">{{ $t("Continue shopping") }}</v-btn-primary>
                <v-btn-secondary v-if="signedIn" :to="`/account/orders/${number}`">{{ $t("View in my account") }}</v-btn-secondary>
                <v-btn-secondary v-else :to="{ path: '/track-order', query: { number } }" prepend-icon="mdi-map-marker-path">{{ $t("Track this order") }}</v-btn-secondary>
            </div>
            <p v-if="!signedIn" class="tt-muted tt-small">{{ $t("Save your order number. You can track the order any time with it and your phone number.") }}</p>
        </section>

        <OrderDetails v-if="order" :order="order" />
    </div>
</template>

<script>
import api from "@/api";
import OrderDetails from "@/front/components/OrderDetails.vue";
import { storage } from "@/store/storage";

export default {
    name: "OrderPlaced",
    components: { OrderDetails },
    data: () => ({ order: null }),
    computed: {
        number() {
            return this.$route.params.number;
        },
        signedIn() {
            return this.$store.getters["auth/isCustomer"];
        },
    },
    async created() {
        // The phone saved at checkout lets us show the full order on this page
        const last = storage.get("tt_last_order");
        if (last?.number !== this.number) return;
        try {
            this.order = (await api.post("/orders/track", last, { quiet: true })).data;
        } catch (e) {
            /* the thank-you message is enough */
        }
    },
};
</script>
