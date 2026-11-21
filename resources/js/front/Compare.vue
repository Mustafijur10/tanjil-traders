<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("Compare products") }}</span>
        </nav>
        <div class="tt-compare-head">
            <h1 class="tt-doc-title">{{ $t("Compare products") }}</h1>
            <v-switch-trz v-if="products.length > 1" v-model="onlyDiff" :label="$t('Show only differences')" />
        </div>

        <div v-if="loading" class="tt-page-card tt-skel-group"><span v-for="n in 8" :key="n" class="tt-skel"></span></div>

        <section v-else-if="products.length < 2" class="tt-page-card">
            <EmptyState icon="mdi-compare-horizontal" title="Pick at least two products" text="Tap the compare button on any product to add it here. You can compare up to three at once.">
                <v-btn-primary to="/shop" prepend-icon="mdi-storefront-outline">{{ $t("Browse products") }}</v-btn-primary>
            </EmptyState>
        </section>

        <template v-else>
            <v-alert v-if="categories.length > 1" type="info" variant="tonal" density="compact" class="mb-4">
                {{ $t("These products are from different categories ({list}), so some specs cannot be compared.", { list: categories.join(", ") }) }}
            </v-alert>

            <div class="tt-compare-scroll">
                <table class="tt-compare-table" :class="'cols-' + products.length">
                    <thead>
                        <tr>
                            <th scope="col" class="tt-compare-label"></th>
                            <th v-for="p in products" :key="p.id" scope="col">
                                <div class="tt-compare-product">
                                    <button type="button" class="tt-compare-remove" :aria-label="$t('Remove {name}', { name: p.name })" @click="remove(p.id)"><v-icon size="18">mdi-close</v-icon></button>
                                    <router-link :to="`/product/${p.slug}`" class="tt-compare-img">
                                        <img v-if="p.image" :src="url(p.image)" :alt="p.image_alt" />
                                        <v-icon v-else size="40">mdi-image-outline</v-icon>
                                    </router-link>
                                    <span v-if="p.brand" class="tt-pcard-brand">{{ p.brand }}</span>
                                    <router-link :to="`/product/${p.slug}`" class="tt-pcard-name">{{ p.name }}</router-link>
                                    <div class="tt-pcard-price"><b>{{ $money(p.final_price) }}</b><s v-if="p.on_sale">{{ $money(p.price) }}</s></div>
                                    <v-btn-primary size="small" block :to="`/product/${p.slug}`">{{ $t("View details") }}</v-btn-primary>
                                </div>
                            </th>
                            <th v-if="products.length < 3" scope="col" class="tt-compare-add">
                                <router-link to="/shop" class="tt-compare-add-box"><v-icon size="28">mdi-plus</v-icon>{{ $t("Add a product") }}</router-link>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="tt-compare-group"><th :colspan="cols">{{ $t("Overview") }}</th></tr>
                        <tr v-for="r in overviewRows" v-show="!onlyDiff || differs(r.values)" :key="r.name" :class="{ 'is-diff': differs(r.values) }">
                            <th scope="row" class="tt-compare-label">{{ $t(r.name) }}</th>
                            <td v-for="(v, i) in r.values" :key="i" :class="{ 'is-best': r.best === i }">{{ v ?? "—" }}</td>
                            <td v-if="products.length < 3"></td>
                        </tr>
                        <template v-for="g in groups" :key="g.name">
                            <tr v-if="!onlyDiff || g.rows.some((r) => differs(r.values))" class="tt-compare-group"><th :colspan="cols">{{ $t(g.name) }}</th></tr>
                            <tr v-for="r in g.rows" v-show="!onlyDiff || differs(r.values)" :key="r.name" :class="{ 'is-diff': differs(r.values) }">
                                <th scope="row" class="tt-compare-label">{{ $t(r.name) }}</th>
                                <td v-for="(v, i) in r.values" :key="i">{{ v ?? "—" }}</td>
                                <td v-if="products.length < 3"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>

<script>
/* Side-by-side comparison of 2–3 products. Ids come from the URL (shareable) or from the compare list. */
import api, { fileUrl } from "@/api";

export default {
    name: "Compare",
    data: () => ({ loading: true, products: [], rows: [], categories: [], onlyDiff: false }),
    computed: {
        cols() {
            return Math.max(this.products.length, 3) + 1;
        },
        overviewRows() {
            const ps = this.products;
            const prices = ps.map((p) => p.final_price);
            return [
                { name: "Price", values: ps.map((p) => this.$money(p.final_price)), best: prices.indexOf(Math.min(...prices)) },
                { name: "Rating", values: ps.map((p) => (p.reviews ? `${this.$num(p.rating)} ★ (${this.$num(p.reviews)})` : null)) },
                { name: "Availability", values: ps.map((p) => this.$t(p.in_stock ? "In stock" : "Out of stock")) },
                { name: "Warranty", values: ps.map((p) => (p.warranty ? this.$t(p.warranty) : null)) },
                { name: "Category", values: ps.map((p) => (p.category ? this.$t(p.category.name) : null)) },
            ];
        },
        groups() {
            const map = new Map();
            this.rows.forEach((r) => map.set(r.group, [...(map.get(r.group) || []), r]));
            return [...map.entries()].map(([name, rows]) => ({ name, rows }));
        },
    },
    watch: { "$route.query.ids": { immediate: true, handler: "load" } },
    methods: {
        url: fileUrl,
        differs(values) {
            return new Set(values.map((v) => v ?? "—")).size > 1;
        },
        async load() {
            const fromUrl = String(this.$route.query.ids || "").split(",").map(Number).filter(Boolean);
            const ids = fromUrl.length ? fromUrl : this.$store.getters["compare/ids"];
            if (ids.length < 2) {
                this.products = [];
                this.loading = false;
                return;
            }
            this.loading = true;
            try {
                const { data } = await api.get("/compare", { params: { ids: ids.join(",") }, quiet: true });
                this.products = data.products;
                this.rows = data.rows;
                this.categories = data.categories;
                // Keep the floating list in step with what is shown
                this.$store.commit("compare/set", data.products.map((p) => ({ id: p.id, name: p.name, slug: p.slug, image: p.image })));
            } finally {
                this.loading = false;
            }
        },
        remove(id) {
            this.$store.dispatch("compare/remove", id);
            this.$router.replace({ query: { ids: this.$store.getters["compare/ids"].join(",") } });
        },
    },
};
</script>
