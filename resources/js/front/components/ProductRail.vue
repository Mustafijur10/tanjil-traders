<template>
    <section class="tt-fsection" :class="{ 'is-deals': deals }">
        <header class="tt-fsection-head">
            <div>
                <h2>
                    <v-icon v-if="deals" size="24" class="tt-fsection-bolt">mdi-lightning-bolt</v-icon>{{ title }}
                </h2>
                <p v-if="subtitle">{{ subtitle }}</p>
            </div>
            <div v-if="countdown" class="tt-countdown" :aria-label="$t('Deal ends in')">
                <span class="tt-countdown-label">{{ $t("Ends in") }}</span>
                <span v-for="part in countdown" :key="part.label" class="tt-countdown-box"><b>{{ part.value }}</b><small>{{ $t(part.label) }}</small></span>
            </div>
            <div class="tt-fsection-tools">
                <router-link v-if="link" :to="link" class="tt-fsection-all">{{ $t("View all") }}<v-icon size="16">mdi-arrow-right</v-icon></router-link>
                <v-btn-tertiary icon="mdi-chevron-left" size="small" :aria-label="$t('Scroll left')" @click="scroll(-1)" />
                <v-btn-tertiary icon="mdi-chevron-right" size="small" :aria-label="$t('Scroll right')" @click="scroll(1)" />
            </div>
        </header>
        <div ref="track" class="tt-prow" tabindex="0" :aria-label="title">
            <ProductCard v-for="p in items" :key="p.id" :p="p" class="tt-prow-item" />
        </div>
    </section>
</template>

<script>
/* A titled, horizontally scrolling row of products. Deal rows show a countdown to the first sale that ends. */
import ProductCard from "./ProductCard.vue";

export default {
    name: "ProductRail",
    components: { ProductCard },
    props: {
        title: { type: String, required: true },
        subtitle: { type: String, default: "" },
        link: { type: String, default: "" },
        items: { type: Array, required: true },
        deals: { type: Boolean, default: false },
    },
    data: () => ({ now: Date.now(), timer: null }),
    computed: {
        endsAt() {
            if (!this.deals) return null;
            const times = this.items.map((p) => p.sale_ends_at && new Date(p.sale_ends_at).getTime()).filter((t) => t && t > this.now);
            return times.length ? Math.min(...times) : null;
        },
        countdown() {
            if (!this.endsAt) return null;
            let s = Math.max(0, Math.floor((this.endsAt - this.now) / 1000));
            const d = Math.floor(s / 86400);
            s %= 86400;
            const parts = [
                { label: "hrs", value: String(Math.floor(s / 3600)).padStart(2, "0") },
                { label: "min", value: String(Math.floor((s % 3600) / 60)).padStart(2, "0") },
                { label: "sec", value: String(s % 60).padStart(2, "0") },
            ];
            return d ? [{ label: d === 1 ? "day" : "days", value: String(d) }, ...parts] : parts;
        },
    },
    mounted() {
        if (this.deals) this.timer = setInterval(() => (this.now = Date.now()), 1000);
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },
    methods: {
        scroll(dir) {
            const el = this.$refs.track;
            el.scrollBy({ left: dir * el.clientWidth * 0.85, behavior: "smooth" });
        },
    },
};
</script>
