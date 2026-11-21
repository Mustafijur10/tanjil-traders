<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("Track your order") }}</span>
        </nav>

        <section class="tt-page-card tt-track">
            <div class="tt-track-intro">
                <span class="tt-track-icon"><v-icon size="28">mdi-map-marker-path</v-icon></span>
                <div>
                    <h1 class="tt-doc-title">{{ $t("Track your order") }}</h1>
                    <p class="tt-muted">{{ $t("Enter your order number and the phone number used for the order.") }}</p>
                </div>
            </div>
            <form class="tt-track-form" @submit.prevent="track">
                <v-input-trz v-model.trim="number" :label="$t('Order number')" placeholder="TT-2611-0001" prepend-inner-icon="mdi-receipt-text-outline" />
                <v-input-trz v-model.trim="phone" :label="$t('Mobile number')" type="tel" inputmode="tel" placeholder="01XXXXXXXXX" prepend-inner-icon="mdi-phone-outline" />
                <v-btn-primary type="submit" size="large" :loading="loading" :disabled="!number || !phone">{{ $t("Track") }}</v-btn-primary>
            </form>
            <v-alert v-if="error" type="warning" variant="tonal" density="compact" class="mt-4">{{ $t(error) }}</v-alert>
            <p v-if="signedIn && !order" class="tt-muted tt-small mt-4">
                {{ $t("Signed in?") }} <router-link to="/account/orders">{{ $t("See all your orders") }}</router-link>
            </p>
        </section>

        <OrderDetails v-if="order" :order="order" />
    </div>
</template>

<script>
import api from "@/api";
import OrderDetails from "@/front/components/OrderDetails.vue";
import { storage } from "@/store/storage";

export default {
    name: "TrackOrder",
    components: { OrderDetails },
    data() {
        const last = storage.get("tt_last_order") || {};
        const number = this.$route.query.number || last.number || "";
        return { number, phone: number === last.number ? last.phone || "" : "", loading: false, error: "", order: null };
    },
    computed: {
        signedIn() {
            return this.$store.getters["auth/isCustomer"];
        },
    },
    created() {
        if (this.number && this.phone) this.track();
    },
    methods: {
        async track() {
            this.loading = true;
            this.error = "";
            try {
                this.order = (await api.post("/orders/track", { number: this.number, phone: this.phone }, { quiet: true })).data;
            } catch (e) {
                this.order = null;
                this.error = e.response?.status === 404 ? "No order found with this number and phone." : e.response?.data?.message || "Could not check the order. Please try again.";
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
