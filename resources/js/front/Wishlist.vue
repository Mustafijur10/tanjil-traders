<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("Wishlist") }}</span>
        </nav>
        <h1 class="tt-doc-title">{{ $t("Wishlist") }}</h1>
        <p class="tt-muted mb-6">
            {{ $tn(products.length, "{n} saved product", "{n} saved products") }}
            <template v-if="!signedIn && products.length">
                · <router-link :to="{ path: '/login', query: { redirect: '/wishlist' } }">{{ $t("Sign in") }}</router-link> {{ $t("to keep them on all your devices.") }}
            </template>
        </p>

        <div v-if="loading" class="tt-pgrid"><span v-for="n in 4" :key="n" class="tt-skel tt-pcard-skel"></span></div>
        <section v-else-if="!products.length" class="tt-page-card">
            <EmptyState icon="mdi-heart-outline" title="Nothing saved yet" text="Tap the heart on any product to save it here for later.">
                <v-btn-primary to="/shop" prepend-icon="mdi-storefront-outline">{{ $t("Browse products") }}</v-btn-primary>
            </EmptyState>
        </section>
        <div v-else class="tt-pgrid is-wide">
            <ProductCard v-for="p in products" :key="p.id" :p="p" />
        </div>
    </div>
</template>

<script>
import api from "@/api";
import ProductCard from "@/front/components/ProductCard.vue";

export default {
    name: "Wishlist",
    components: { ProductCard },
    data: () => ({ loading: true, all: [] }),
    computed: {
        ids() {
            return this.$store.state.wishlist.ids;
        },
        // Removing a heart hides the card straight away
        products() {
            return this.all.filter((p) => this.ids.includes(p.id));
        },
        signedIn() {
            return this.$store.getters["auth/isCustomer"];
        },
    },
    async created() {
        try {
            if (this.signedIn) {
                this.all = (await api.get("/me/wishlist")).data.products;
            } else if (this.ids.length) {
                this.all = (await api.get("/product-cards", { params: { ids: this.ids.join(",") }, quiet: true })).data;
            }
        } finally {
            this.loading = false;
        }
    },
};
</script>
