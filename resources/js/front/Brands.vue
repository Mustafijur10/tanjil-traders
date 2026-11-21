<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("Brands") }}</span>
        </nav>
        <h1 class="tt-doc-title">{{ $t("Shop by brand") }}</h1>
        <p class="tt-muted mb-6">{{ $tn(brands.length, "{n} brand", "{n} brands") }}</p>

        <div v-if="loading" class="tt-brand-strip"><span v-for="n in 12" :key="n" class="tt-skel tt-brand-skel"></span></div>
        <div v-else class="tt-brand-strip is-page">
            <router-link v-for="b in brands" :key="b.id" :to="`/brand/${b.slug}`" class="tt-brand-chip is-page">
                <img v-if="b.logo" :src="url(b.logo)" :alt="b.name" loading="lazy" />
                <span v-else>{{ b.name }}</span>
                <small>{{ $tn(b.products_count, "{n} product", "{n} products") }}</small>
            </router-link>
        </div>
    </div>
</template>

<script>
import api, { fileUrl } from "@/api";

export default {
    name: "Brands",
    data: () => ({ loading: true, brands: [] }),
    async created() {
        try {
            this.brands = (await api.get("/brands", { quiet: true })).data.filter((b) => b.products_count > 0);
        } finally {
            this.loading = false;
        }
    },
    methods: { url: fileUrl },
};
</script>
