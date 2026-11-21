<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("My account") }}</span>
        </nav>

        <div class="tt-account">
            <aside class="tt-account-side">
                <div class="tt-account-user">
                    <span class="tt-initials">{{ initials }}</span>
                    <span>
                        <b>{{ user?.name }}</b>
                        <small class="tt-muted">{{ user?.phone || user?.email }}</small>
                    </span>
                </div>
                <nav class="tt-account-nav">
                    <router-link v-for="t in tabs" :key="t.to" :to="t.to" :class="{ 'is-on': tab === t.key }"><v-icon size="20">{{ t.icon }}</v-icon>{{ $t(t.label) }}</router-link>
                    <router-link to="/wishlist"><v-icon size="20">mdi-heart-outline</v-icon>{{ $t("Wishlist") }}</router-link>
                    <button type="button" @click="logout"><v-icon size="20">mdi-logout</v-icon>{{ $t("Sign out") }}</button>
                </nav>
            </aside>

            <div class="tt-account-main">
                <!-- Overview -->
                <template v-if="tab === 'overview'">
                    <h1 class="tt-doc-title">{{ $t("Hello, {name}", { name: firstName }) }}</h1>
                    <div class="tt-account-stats">
                        <div><small>{{ $t("Orders") }}</small><b>{{ $num(summary.orders ?? 0) }}</b></div>
                        <div><small>{{ $t("In progress") }}</small><b>{{ $num(summary.open ?? 0) }}</b></div>
                        <div><small>{{ $t("Total spent") }}</small><b>{{ $money(summary.spent ?? 0) }}</b></div>
                        <div><small>{{ $t("Saved items") }}</small><b>{{ $num(summary.wishlist ?? 0) }}</b></div>
                    </div>
                    <section class="tt-page-card">
                        <div class="tt-card-head">
                            <h2 class="tt-card-title">{{ $t("Recent orders") }}</h2>
                            <router-link v-if="orders.length" to="/account/orders" class="tt-flink">{{ $t("See all") }}</router-link>
                        </div>
                        <OrderList :orders="orders.slice(0, 3)" :loading="loadingOrders" />
                    </section>
                </template>

                <!-- Orders -->
                <template v-else-if="tab === 'orders'">
                    <h1 class="tt-doc-title">{{ $t("My orders") }}</h1>
                    <section class="tt-page-card">
                        <OrderList :orders="orders" :loading="loadingOrders" />
                        <div v-if="lastPage > 1" class="tt-shop-pages">
                            <v-pagination v-model="page" :length="lastPage" density="comfortable" total-visible="5" />
                        </div>
                    </section>
                </template>

                <!-- Profile -->
                <template v-else>
                    <h1 class="tt-doc-title">{{ $t("Profile and password") }}</h1>
                    <section class="tt-page-card">
                        <form class="tt-form-grid" @submit.prevent="saveProfile">
                            <v-input-trz v-model.trim="profile.name" :label="$t('Full name')" :error-messages="errors.name" />
                            <v-input-trz v-model.trim="profile.phone" :label="$t('Mobile number')" type="tel" :error-messages="errors.phone" />
                            <v-input-trz v-model.trim="profile.email" class="is-full" :label="$t('Email')" type="email" :error-messages="errors.email" />
                            <h3 class="is-full tt-card-title mt-2">{{ $t("Change password") }}</h3>
                            <v-input-trz v-model="profile.current_password" class="is-full" :label="$t('Current password')" type="password" autocomplete="current-password" :error-messages="errors.current_password" />
                            <v-input-trz v-model="profile.password" :label="$t('New password')" type="password" autocomplete="new-password" :error-messages="errors.password" />
                            <v-input-trz v-model="profile.password_confirmation" :label="$t('Confirm new password')" type="password" autocomplete="new-password" />
                            <div class="is-full">
                                <v-btn-primary type="submit" :loading="saving">{{ $t("Save changes") }}</v-btn-primary>
                            </div>
                        </form>
                    </section>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";
import OrderList from "@/front/components/OrderList.vue";

export default {
    name: "Account",
    components: { OrderList },
    data: () => ({
        summary: {},
        orders: [],
        loadingOrders: true,
        page: 1,
        lastPage: 1,
        profile: { name: "", phone: "", email: "", current_password: "", password: "", password_confirmation: "" },
        errors: {},
        saving: false,
    }),
    computed: {
        tabs: () => [
            { key: "overview", to: "/account", label: "Overview", icon: "mdi-view-dashboard-outline" },
            { key: "orders", to: "/account/orders", label: "My orders", icon: "mdi-receipt-text-outline" },
            { key: "profile", to: "/account/profile", label: "Profile", icon: "mdi-account-cog-outline" },
        ],
        tab() {
            return this.$route.meta.tab || "overview";
        },
        user() {
            return this.$store.state.auth.user;
        },
        firstName() {
            return (this.user?.name || "").split(" ")[0];
        },
        initials() {
            return (this.user?.name || "?").split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase();
        },
    },
    watch: {
        tab: { immediate: true, handler: "load" },
        page: "loadOrders",
    },
    methods: {
        load() {
            if (this.tab === "overview") {
                api.get("/me/summary").then(({ data }) => (this.summary = data));
                this.loadOrders();
            } else if (this.tab === "orders") {
                this.loadOrders();
            } else {
                const u = this.user || {};
                this.profile = { name: u.name || "", phone: u.phone || "", email: u.email || "", current_password: "", password: "", password_confirmation: "" };
            }
        },
        async loadOrders() {
            this.loadingOrders = true;
            try {
                const { data } = await api.get("/me/orders", { params: { page: this.page } });
                this.orders = data.data;
                this.lastPage = data.last_page;
            } finally {
                this.loadingOrders = false;
            }
        },
        async saveProfile() {
            this.saving = true;
            this.errors = {};
            try {
                const body = { ...this.profile, email: this.profile.email || null, phone: this.profile.phone || null };
                if (!body.password) ["password", "password_confirmation", "current_password"].forEach((k) => delete body[k]);
                const { data } = await api.put("/me", body, { quiet: true });
                this.$store.commit("auth/setUser", data);
                this.profile.current_password = this.profile.password = this.profile.password_confirmation = "";
                this.$store.dispatch("notify", this.$t("Your profile is saved."));
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        async logout() {
            await this.$store.dispatch("auth/logout");
            this.$store.dispatch("wishlist/reset");
            this.$router.push("/");
        },
    },
};
</script>
