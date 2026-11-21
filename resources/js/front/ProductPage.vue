<template>
    <div class="tt-wrap tt-pdp">
        <div v-if="loading" class="tt-pdp-top">
            <span class="tt-skel tt-pdp-skel-img"></span>
            <div class="tt-skel-group"><span v-for="n in 7" :key="n" class="tt-skel"></span></div>
        </div>

        <PageNotFound v-else-if="missing" />

        <template v-else>
            <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
                <router-link to="/">{{ $t("Home") }}</router-link>
                <template v-if="p.category?.parent">
                    <v-icon size="14">mdi-chevron-right</v-icon>
                    <router-link :to="`/category/${p.category.parent.slug}`">{{ $t(p.category.parent.name) }}</router-link>
                </template>
                <template v-if="p.category">
                    <v-icon size="14">mdi-chevron-right</v-icon>
                    <router-link :to="`/category/${p.category.slug}`">{{ $t(p.category.name) }}</router-link>
                </template>
                <v-icon size="14">mdi-chevron-right</v-icon>
                <span class="tt-ellipsis">{{ p.name }}</span>
            </nav>

            <div class="tt-pdp-top">
                <!-- Gallery -->
                <section class="tt-gallery-view" :aria-label="$t('Product photos')">
                    <div
                        class="tt-gallery-main"
                        :class="{ 'is-zoom': zoom }"
                        :style="{ '--zx': zx + '%', '--zy': zy + '%' }"
                        @mouseenter="zoom = !!mainImage"
                        @mouseleave="zoom = false"
                        @mousemove="onZoom"
                    >
                        <img v-if="mainImage" :src="url(mainImage.path)" :alt="mainImage.alt || p.name" fetchpriority="high" />
                        <span v-else class="tt-pcard-noimg"><v-icon size="56">mdi-image-outline</v-icon></span>
                        <span v-if="discountPct" class="tt-pcard-badge is-sale">−{{ $num(discountPct) }}%</span>
                    </div>
                    <div v-if="images.length > 1" class="tt-gallery-thumbs" role="tablist">
                        <button
                            v-for="(img, i) in images"
                            :key="img.path"
                            type="button"
                            role="tab"
                            :class="{ 'is-on': i === imageIndex }"
                            :aria-selected="i === imageIndex"
                            :aria-label="$t('Photo {n}', { n: i + 1 })"
                            @click="imageIndex = i"
                        >
                            <img :src="url(img.path)" :alt="img.alt || ''" loading="lazy" />
                        </button>
                    </div>
                </section>

                <!-- Buying box -->
                <section class="tt-buybox">
                    <router-link v-if="p.brand" :to="`/brand/${p.brand.slug}`" class="tt-pcard-brand">{{ p.brand.name }}</router-link>
                    <h1 class="tt-pdp-title">{{ p.name }}</h1>
                    <div class="tt-pdp-meta">
                        <a v-if="p.reviews" href="#reviews" class="tt-pcard-rating" @click.prevent="scrollTo('reviews')">
                            <v-icon v-for="n in 5" :key="n" size="16" :class="n <= Math.round(p.rating) ? '' : 'is-off'">mdi-star</v-icon>
                            {{ $num(p.rating) }} <small>({{ $tn(p.reviews, "{n} review", "{n} reviews") }})</small>
                        </a>
                        <span v-if="sku" class="tt-muted tt-small">{{ $t("SKU") }}: {{ sku }}</span>
                    </div>

                    <div class="tt-pdp-price">
                        <b>{{ $money(price) }}</b>
                        <template v-if="regular > price">
                            <s>{{ $money(regular) }}</s>
                            <span class="tt-pdp-save">{{ $t("Save {amount}", { amount: $money(regular - price) }) }}</span>
                        </template>
                    </div>
                    <div v-if="saleLeft" class="tt-pdp-sale-ends">
                        <v-icon size="16">mdi-timer-outline</v-icon>{{ $t("Offer ends in {time}", { time: saleLeft }) }}
                    </div>

                    <div class="tt-pdp-stock" :class="stock > 0 ? (stock <= 3 ? 'is-low' : 'is-in') : 'is-out'">
                        <v-icon size="18">{{ stock > 0 ? "mdi-check-circle" : "mdi-close-circle" }}</v-icon>
                        <span v-if="stock <= 0">{{ $t("Out of stock") }}</span>
                        <span v-else-if="stock <= 3">{{ $tn(stock, "Only {n} left in stock", "Only {n} left in stock") }}</span>
                        <span v-else>{{ $t("In stock") }}</span>
                    </div>

                    <ul v-if="features.length" class="tt-pdp-features">
                        <li v-for="f in features" :key="f"><v-icon size="16">mdi-check</v-icon>{{ f }}</li>
                    </ul>

                    <!-- Options (variants) -->
                    <div v-for="o in p.options" :key="o.id" class="tt-pdp-option">
                        <div class="tt-pdp-option-label">{{ $t(o.name) }}: <b>{{ pickedValue(o)?.value || $t("Choose") }}</b></div>
                        <div class="tt-pdp-option-values" role="radiogroup" :aria-label="$t(o.name)">
                            <button
                                v-for="v in o.values"
                                :key="v.id"
                                type="button"
                                role="radio"
                                class="tt-optbtn"
                                :class="{ 'is-on': picked[o.id] === v.id, 'is-na': !available(o.id, v.id), 'has-swatch': v.swatch }"
                                :aria-checked="picked[o.id] === v.id"
                                :title="available(o.id, v.id) ? v.value : $t('{value}: not available with this choice', { value: v.value })"
                                @click="pick(o.id, v.id)"
                            >
                                <i v-if="v.swatch" :style="{ '--swatch': v.swatch }"></i>{{ v.value }}
                            </button>
                        </div>
                    </div>

                    <!-- Ordering: phone and WhatsApp until the cart opens -->
                    <div class="tt-pdp-actions">
                        <v-btn-primary v-if="whatsapp" size="large" prepend-icon="mdi-whatsapp" :href="whatsappLink" target="_blank" rel="noopener" :disabled="stock <= 0 || needsChoice">
                            {{ $t("Order on WhatsApp") }}
                        </v-btn-primary>
                        <v-btn-secondary v-if="phone" size="large" prepend-icon="mdi-phone-outline" :href="`tel:${phone}`">{{ $t("Call to order") }}</v-btn-secondary>
                        <v-btn-secondary v-if="!whatsapp && !phone" size="large" prepend-icon="mdi-message-outline" to="/contact">{{ $t("Ask about this product") }}</v-btn-secondary>
                        <v-btn-tertiary size="large" :prepend-icon="inCompare ? 'mdi-check' : 'mdi-compare-horizontal'" :color="inCompare ? 'primary' : undefined" @click="toggleCompare">
                            {{ $t(inCompare ? "Added to compare" : "Compare") }}
                        </v-btn-tertiary>
                    </div>
                    <p v-if="needsChoice" class="tt-muted tt-small">{{ $t("Choose an option above to continue.") }}</p>

                    <ul class="tt-pdp-trust">
                        <li v-if="p.warranty"><v-icon size="20">mdi-shield-check-outline</v-icon><span><b>{{ $t("Warranty") }}</b>{{ $t(p.warranty) }}</span></li>
                        <li><v-icon size="20">mdi-truck-fast-outline</v-icon><span><b>{{ $t("Delivery") }}</b>{{ $t("All over Bangladesh") }}</span></li>
                        <li><v-icon size="20">mdi-certificate-outline</v-icon><span><b>{{ $t("Original") }}</b>{{ $t("100% genuine product") }}</span></li>
                    </ul>
                </section>
            </div>

            <!-- Details -->
            <nav class="tt-pdp-tabs" :aria-label="$t('Product details')">
                <button v-if="p.specs.length" type="button" @click="scrollTo('specs')">{{ $t("Specifications") }}</button>
                <button v-if="p.description" type="button" @click="scrollTo('description')">{{ $t("Description") }}</button>
                <button type="button" @click="scrollTo('reviews')">{{ $t("Reviews") }} ({{ $num(p.reviews) }})</button>
            </nav>

            <section v-if="p.specs.length" id="specs" class="tt-pdp-section">
                <h2>{{ $t("Specifications") }}</h2>
                <div class="tt-spec-table">
                    <template v-for="g in p.specs" :key="g.group">
                        <div class="tt-spec-group">{{ $t(g.group) }}</div>
                        <div v-for="r in g.rows" :key="r.name" class="tt-spec-tr">
                            <span>{{ $t(r.name) }}</span>
                            <b>{{ r.value }}</b>
                        </div>
                    </template>
                </div>
            </section>

            <section v-if="p.description" id="description" class="tt-pdp-section">
                <h2>{{ $t("Description") }}</h2>
                <div class="cms-content" v-html="p.description"></div>
            </section>

            <section id="reviews" class="tt-pdp-section">
                <h2>{{ $t("Customer reviews") }}</h2>
                <div class="tt-reviews">
                    <div class="tt-reviews-summary">
                        <b class="tt-reviews-avg">{{ p.reviews ? $num(p.rating) : "—" }}</b>
                        <span class="tt-rating-stars"><v-icon v-for="n in 5" :key="n" size="20" :class="{ 'is-on': n <= Math.round(p.rating) }">mdi-star</v-icon></span>
                        <span class="tt-muted tt-small">{{ $tn(p.reviews, "{n} review", "{n} reviews") }}</span>
                        <div class="tt-reviews-bars">
                            <div v-for="r in [5, 4, 3, 2, 1]" :key="r" class="tt-reviews-bar">
                                <span>{{ $num(r) }}★</span>
                                <i><em :style="{ '--w': p.reviews ? (reviews.breakdown[r] / p.reviews) * 100 + '%' : '0%' }"></em></i>
                                <small>{{ $num(reviews.breakdown[r] || 0) }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="tt-reviews-list">
                        <EmptyState v-if="!reviews.items.length" icon="mdi-star-outline" title="No reviews yet" text="Bought this product? Be the first to share what you think." />
                        <article v-for="r in reviews.items" :key="r.id" class="tt-review-card">
                            <div class="tt-review-meta">
                                <span class="tt-sidebar-avatar">{{ initials(r.name) }}</span>
                                <div>
                                    <b>{{ r.name }}</b>
                                    <span class="tt-rating-stars"><v-icon v-for="n in 5" :key="n" size="14" :class="{ 'is-on': n <= r.rating }">mdi-star</v-icon></span>
                                </div>
                                <small class="tt-muted ml-auto">{{ $date(r.created_at) }}</small>
                            </div>
                            <p v-if="r.comment" class="tt-review-text">{{ r.comment }}</p>
                            <div v-if="r.reply" class="tt-review-reply"><b>{{ $t("Reply from {store}", { store: storeName }) }}</b><p>{{ r.reply }}</p></div>
                        </article>

                        <form class="tt-review-form" @submit.prevent="sendReview">
                            <h3>{{ $t("Write a review") }}</h3>
                            <v-alert v-if="reviewSent" type="success" variant="tonal" density="compact" :text="$t('Thank you! Your review will appear after we check it.')" />
                            <template v-else>
                                <div class="tt-star-input" role="radiogroup" :aria-label="$t('Your rating')">
                                    <button v-for="n in 5" :key="n" type="button" role="radio" :aria-checked="review.rating === n" :aria-label="$tn(n, '{n} star', '{n} stars')" @click="review.rating = n">
                                        <v-icon size="28" :class="{ 'is-on': n <= review.rating }">mdi-star</v-icon>
                                    </button>
                                </div>
                                <div class="tt-form-grid">
                                    <v-input-trz v-model="review.name" :label="$t('Your name')" :error-messages="reviewErrors.name" />
                                    <div></div>
                                    <v-textarea-trz v-model="review.comment" class="is-full" :label="$t('What did you like or dislike?')" rows="3" :error-messages="reviewErrors.comment" />
                                </div>
                                <input v-model="review.website" class="tt-visually-hidden" tabindex="-1" autocomplete="off" aria-hidden="true" />
                                <v-btn-primary type="submit" class="mt-3" :loading="sendingReview" :disabled="!review.rating || !review.name">{{ $t("Send review") }}</v-btn-primary>
                            </template>
                        </form>
                    </div>
                </div>
            </section>

            <ProductRail v-if="related.length" :title="$t('You may also like')" :items="related" />
        </template>
    </div>
</template>

<script>
import api, { fileUrl, fieldErrors } from "@/api";
import PageNotFound from "@/PageNotFound.vue";
import ProductRail from "@/front/components/ProductRail.vue";

export default {
    name: "ProductPage",
    components: { PageNotFound, ProductRail },
    data: () => ({
        loading: true,
        missing: false,
        p: null,
        reviews: { items: [], breakdown: {} },
        related: [],
        picked: {},
        imageIndex: 0,
        zoom: false,
        zx: 50,
        zy: 50,
        now: Date.now(),
        timer: null,
        review: { name: "", rating: 0, comment: "", website: "" },
        reviewErrors: {},
        sendingReview: false,
        reviewSent: false,
    }),
    computed: {
        settings() {
            return this.$store.state.prefs.settings;
        },
        storeName() {
            return this.settings["store.name"] || "";
        },
        phone() {
            return this.settings["store.phone"];
        },
        whatsapp() {
            return String(this.settings["social.whatsapp"] || "").replace(/\D/g, "");
        },
        // The variant matching every chosen option (or null)
        variant() {
            if (!this.p?.variants.length) return null;
            const ids = Object.values(this.picked).filter(Boolean).map(Number).sort((a, b) => a - b);
            if (ids.length !== this.p.options.length) return null;
            return this.p.variants.find((v) => v.value_ids.join(",") === ids.join(",")) || null;
        },
        needsChoice() {
            return !!this.p?.variants.length && !this.variant;
        },
        price() {
            return this.variant ? this.variant.final_price : this.p.final_price;
        },
        regular() {
            return this.variant ? this.variant.price : this.p.price;
        },
        discountPct() {
            return this.regular > this.price ? Math.round(((this.regular - this.price) / this.regular) * 100) : 0;
        },
        stock() {
            return this.variant ? this.variant.stock_qty : this.p.stock_qty;
        },
        sku() {
            return this.variant?.sku || this.p.sku;
        },
        images() {
            const list = [...this.p.images];
            if (this.variant?.image && !list.some((i) => i.path === this.variant.image)) list.unshift({ path: this.variant.image, alt: this.p.name });
            return list;
        },
        mainImage() {
            return this.images[this.imageIndex] || this.images[0] || null;
        },
        features() {
            return String(this.p.short_description || "").split("\n").map((s) => s.trim()).filter(Boolean);
        },
        inCompare() {
            return this.$store.getters["compare/has"](this.p.id);
        },
        saleLeft() {
            if (!this.p.sale_ends_at || this.variant) return "";
            let s = Math.floor((new Date(this.p.sale_ends_at).getTime() - this.now) / 1000);
            if (s <= 0) return "";
            const d = Math.floor(s / 86400);
            s %= 86400;
            const hms = [Math.floor(s / 3600), Math.floor((s % 3600) / 60), s % 60].map((n) => String(n).padStart(2, "0")).join(":");
            return d ? this.$tn(d, "{n} day", "{n} days") + " " + hms : hms;
        },
        whatsappLink() {
            const choice = this.variant ? ` (${this.variant.label})` : "";
            const text = this.$t("Hello, I want to order: {product}{choice} – {price}. {link}", {
                product: this.p.name,
                choice,
                price: this.$money(this.price),
                link: window.location.href,
            });
            return `https://wa.me/${this.whatsapp}?text=${encodeURIComponent(text)}`;
        },
    },
    watch: {
        "$route.params.slug": { immediate: true, handler: "load" },
        variant(v) {
            if (v?.image) this.imageIndex = 0;
        },
    },
    mounted() {
        this.timer = setInterval(() => (this.now = Date.now()), 1000);
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },
    methods: {
        url: fileUrl,
        initials: (name) => (name || "?").split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase(),
        async load(slug) {
            if (!slug) return;
            this.loading = true;
            this.missing = false;
            try {
                const { data } = await api.get(`/products/${slug}`, { quiet: true });
                this.p = data.product;
                this.reviews = data.reviews;
                this.related = data.related;
                this.imageIndex = 0;
                this.reviewSent = false;
                this.review = { name: this.$store.state.auth.user?.name || "", rating: 0, comment: "", website: "" };
                this.preselect();
                document.title = `${this.p.meta_title || this.p.name} · ${this.storeName}`;
            } catch (e) {
                this.missing = true;
            } finally {
                this.loading = false;
            }
        },
        // Start with the cheapest variant that is in stock
        preselect() {
            this.picked = {};
            if (!this.p.variants.length) return;
            const best = [...this.p.variants].sort((a, b) => (b.stock_qty > 0) - (a.stock_qty > 0) || a.final_price - b.final_price)[0];
            const owner = {};
            this.p.options.forEach((o) => o.values.forEach((v) => (owner[v.id] = o.id)));
            best.value_ids.forEach((id) => (this.picked[owner[id]] = id));
        },
        pickedValue(o) {
            return o.values.find((v) => v.id === this.picked[o.id]);
        },
        // Is there an in-stock variant with this value and the other current choices?
        available(optionId, valueId) {
            const others = Object.entries(this.picked).filter(([k, v]) => Number(k) !== optionId && v).map(([, v]) => v);
            return this.p.variants.some((v) => v.stock_qty > 0 && v.value_ids.includes(valueId) && others.every((o) => v.value_ids.includes(o)));
        },
        pick(optionId, valueId) {
            const next = { ...this.picked, [optionId]: valueId };
            // If that combination does not exist, keep this choice and pick the closest variant that has it
            const ids = Object.values(next).map(Number);
            if (!this.p.variants.some((v) => ids.every((id) => v.value_ids.includes(id)))) {
                const match = this.p.variants.find((v) => v.value_ids.includes(valueId) && v.stock_qty > 0) || this.p.variants.find((v) => v.value_ids.includes(valueId));
                if (match) {
                    const owner = {};
                    this.p.options.forEach((o) => o.values.forEach((v) => (owner[v.id] = o.id)));
                    match.value_ids.forEach((id) => (next[owner[id]] = id));
                }
            }
            this.picked = next;
        },
        onZoom(e) {
            const r = e.currentTarget.getBoundingClientRect();
            this.zx = Math.round(((e.clientX - r.left) / r.width) * 100);
            this.zy = Math.round(((e.clientY - r.top) / r.height) * 100);
        },
        scrollTo(id) {
            document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
        },
        toggleCompare() {
            this.$store.dispatch("compare/toggle", { id: this.p.id, name: this.p.name, slug: this.p.slug, image: this.p.images[0]?.path });
        },
        async sendReview() {
            this.sendingReview = true;
            this.reviewErrors = {};
            try {
                await api.post(`/products/${this.p.slug}/reviews`, this.review, { quiet: true });
                this.reviewSent = true;
            } catch (e) {
                this.reviewErrors = fieldErrors(e);
                if (e.response?.status === 429) this.$store.dispatch("notify", { text: this.$t("Too many reviews. Please try again later."), type: "warning" });
            } finally {
                this.sendingReview = false;
            }
        },
    },
};
</script>
