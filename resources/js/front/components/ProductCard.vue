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
            <v-btn-primary block :to="link" size="small">{{ $t(p.has_options ? "Choose options" : "View details") }}</v-btn-primary>
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
    },
    methods: {
        url: fileUrl,
    },
};
</script>
