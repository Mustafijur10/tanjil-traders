<template>
    <article class="tt-pcard" :class="{ 'is-out': !p.in_stock }">
        <router-link :to="link" class="tt-pcard-media" :aria-label="p.name">
            <img v-if="p.image" :src="url(p.image)" :alt="p.image_alt" loading="lazy" decoding="async" class="tt-pcard-img" />
            <img v-if="p.hover_image" :src="url(p.hover_image)" alt="" loading="lazy" decoding="async" class="tt-pcard-img is-hover" />
            <span v-if="!p.image" class="tt-pcard-noimg"><v-icon size="34">mdi-image-outline</v-icon></span>
            <span v-if="p.discount_pct" class="tt-pcard-badge is-sale">−{{ $num(p.discount_pct) }}%</span>
            <span v-else-if="p.is_featured" class="tt-pcard-badge">{{ $t("Popular") }}</span>
            <span v-if="!p.in_stock" class="tt-pcard-out">{{ $t("Out of stock") }}</span>
        </router-link>

        <div class="tt-pcard-tools">
            <button
                type="button"
                class="tt-pcard-tool is-wish"
                :class="{ 'is-on': saved }"
                :aria-pressed="saved"
                :aria-label="$t(saved ? 'Remove from wishlist' : 'Save to wishlist')"
                :title="$t(saved ? 'Remove from wishlist' : 'Save to wishlist')"
                @click="$store.dispatch('wishlist/toggle', p.id)"
            >
                <v-icon size="18">{{ saved ? "mdi-heart" : "mdi-heart-outline" }}</v-icon>
            </button>
            <button
                type="button"
                class="tt-pcard-tool"
                :class="{ 'is-on': inCompare }"
                :aria-pressed="inCompare"
                :aria-label="$t(inCompare ? 'Remove from compare' : 'Add to compare')"
                :title="$t(inCompare ? 'Remove from compare' : 'Add to compare')"
                @click="$store.dispatch('compare/toggle', p)"
            >
                <v-icon size="18">{{ inCompare ? "mdi-check" : "mdi-compare-horizontal" }}</v-icon>
            </button>
        </div>

        <div class="tt-pcard-body">
            <span v-if="p.brand" class="tt-pcard-brand">{{ p.brand }}</span>
            <router-link :to="link" class="tt-pcard-name">{{ p.name }}</router-link>
            <span v-if="p.reviews" class="tt-pcard-rating" :aria-label="$t('{n} out of 5 stars', { n: p.rating })">
                <v-icon size="14">mdi-star</v-icon>{{ $num(p.rating) }}<small>({{ $num(p.reviews) }})</small>
            </span>
            <div class="tt-pcard-price">
                <b>{{ $money(p.final_price) }}</b>
                <s v-if="p.on_sale">{{ $money(p.price) }}</s>
            </div>
            <span v-if="p.in_stock && p.stock_qty <= 3" class="tt-pcard-low">{{ $tn(p.stock_qty, "Only {n} left", "Only {n} left") }}</span>
        </div>

        <div class="tt-pcard-actions">
            <v-btn-primary v-if="quickAdd" block size="small" prepend-icon="mdi-cart-plus" @click="addToCart">{{ $t("Add to cart") }}</v-btn-primary>
            <v-btn-primary v-else block :to="link" size="small">{{ $t(p.has_options && p.in_stock ? "Choose options" : "View details") }}</v-btn-primary>
        </div>
    </article>
</template>

<script>
/* Product card used on the home page and every product list. `p` comes from the API's ProductCard shape. */
import { fileUrl } from "@/api";

export default {
    name: "ProductCard",
    props: {
        p: { type: Object, required: true },
    },
    computed: {
        link() {
            return `/product/${this.p.slug}`;
        },
        inCompare() {
            return this.$store.getters["compare/has"](this.p.id);
        },
        saved() {
            return this.$store.getters["wishlist/has"](this.p.id);
        },
        // Products without options can go straight into the cart
        quickAdd() {
            return this.p.in_stock && !this.p.has_options;
        },
    },
    methods: {
        url: fileUrl,
        addToCart() {
            const p = this.p;
            this.$store.dispatch("cart/add", {
                line: { product_id: p.id, variant_id: null, qty: 1, name: p.name, slug: p.slug, image: p.image, price: p.final_price, regular_price: p.price, stock: p.stock_qty },
            });
        },
    },
};
</script>
