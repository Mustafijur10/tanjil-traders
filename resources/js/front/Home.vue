<template>
    <div class="tt-home">
        <div class="tt-wrap">
            <div v-if="loading" class="tt-home-skel">
                <span class="tt-skel tt-home-skel-hero"></span>
                <div class="tt-prow"><span v-for="n in 5" :key="n" class="tt-skel tt-home-skel-card"></span></div>
            </div>

            <template v-else>
                <HeroCarousel :slides="hero" />

                <template v-for="s in sections" :key="s.id">
                    <!-- Category tiles -->
                    <section v-if="s.type === 'categories'" class="tt-fsection">
                        <header class="tt-fsection-head"><div><h2>{{ $t(s.title) }}</h2><p v-if="s.subtitle">{{ $t(s.subtitle) }}</p></div></header>
                        <div class="tt-cat-tiles">
                            <router-link v-for="c in s.items" :key="c.id" :to="`/category/${c.slug}`" class="tt-cat-tile">
                                <span class="tt-cat-tile-img">
                                    <img v-if="c.image" :src="url(c.image)" :alt="c.name" loading="lazy" />
                                    <v-icon v-else size="34">{{ c.icon || "mdi-shape-outline" }}</v-icon>
                                </span>
                                <span>{{ $t(c.name) }}</span>
                            </router-link>
                        </div>
                    </section>

                    <!-- Brand logos -->
                    <section v-else-if="s.type === 'brands'" class="tt-fsection">
                        <header class="tt-fsection-head">
                            <div><h2>{{ $t(s.title) }}</h2><p v-if="s.subtitle">{{ $t(s.subtitle) }}</p></div>
                            <router-link to="/brands" class="tt-fsection-all">{{ $t("All brands") }}<v-icon size="16">mdi-arrow-right</v-icon></router-link>
                        </header>
                        <div class="tt-brand-strip">
                            <router-link v-for="b in s.items" :key="b.id" :to="`/brand/${b.slug}`" class="tt-brand-chip" :aria-label="b.name">
                                <img v-if="b.logo" :src="url(b.logo)" :alt="b.name" loading="lazy" />
                                <span v-else>{{ b.name }}</span>
                            </router-link>
                        </div>
                    </section>

                    <!-- Promo banners -->
                    <section v-else-if="s.type === 'promo'" class="tt-fsection">
                        <header v-if="s.title" class="tt-fsection-head"><div><h2>{{ $t(s.title) }}</h2></div></header>
                        <div class="tt-promo-grid" :class="'is-' + Math.min(s.items.length, 3)">
                            <component
                                :is="b.link ? (/^https?:/.test(b.link) ? 'a' : 'router-link') : 'div'"
                                v-for="b in s.items"
                                :key="b.id"
                                v-bind="b.link ? (/^https?:/.test(b.link) ? { href: b.link, rel: 'noopener' } : { to: b.link }) : {}"
                                class="tt-promo"
                            >
                                <img :src="url(b.image)" :alt="b.title || ''" loading="lazy" />
                                <span v-if="b.title || b.button_text" class="tt-promo-copy">
                                    <b v-if="b.title">{{ $t(b.title) }}</b>
                                    <small v-if="b.subtitle">{{ $t(b.subtitle) }}</small>
                                    <em v-if="b.button_text">{{ $t(b.button_text) }} →</em>
                                </span>
                            </component>
                        </div>
                    </section>

                    <!-- Product rows: featured, new, deals, best sellers, one category, one brand -->
                    <ProductRail v-else :title="$t(s.title)" :subtitle="s.subtitle ? $t(s.subtitle) : ''" :link="s.link" :items="s.items" :deals="s.type === 'deals'" />
                </template>

                <EmptyState
                    v-if="!hero.length && !sections.length"
                    icon="mdi-storefront-outline"
                    title="The shop is getting ready"
                    text="Products and offers will appear here soon."
                />
            </template>
        </div>
    </div>
</template>

<script>
import api, { fileUrl } from "@/api";
import HeroCarousel from "@/front/components/HeroCarousel.vue";
import ProductRail from "@/front/components/ProductRail.vue";

export default {
    name: "Home",
    components: { HeroCarousel, ProductRail },
    data: () => ({ loading: true, hero: [], sections: [] }),
    async created() {
        try {
            const { data } = await api.get("/home", { quiet: true });
            this.hero = data.hero;
            this.sections = data.sections;
        } finally {
            this.loading = false;
        }
    },
    methods: {
        url: fileUrl,
    },
};
</script>
