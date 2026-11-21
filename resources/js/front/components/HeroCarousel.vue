<template>
    <section v-if="slides.length" class="tt-hero-slider" :aria-label="$t('Featured offers')" @mouseenter="paused = true" @mouseleave="paused = false">
        <div class="tt-hero-track" :style="{ '--slide': index }">
            <component
                :is="s.link ? (isExternal(s.link) ? 'a' : 'router-link') : 'div'"
                v-for="(s, i) in slides"
                :key="s.id"
                v-bind="s.link ? (isExternal(s.link) ? { href: s.link, rel: 'noopener' } : { to: s.link }) : {}"
                class="tt-hero-slide"
                :aria-hidden="i !== index"
                :tabindex="i === index ? 0 : -1"
            >
                <picture>
                    <source v-if="s.mobile_image" :srcset="url(s.mobile_image)" media="(max-width: 700px)" />
                    <img :src="url(s.image)" :alt="s.title || ''" :loading="i === 0 ? 'eager' : 'lazy'" :fetchpriority="i === 0 ? 'high' : 'auto'" />
                </picture>
                <div v-if="s.title || s.subtitle || s.button_text" class="tt-hero-copy">
                    <span v-if="s.subtitle" class="tt-hero-kicker">{{ $t(s.subtitle) }}</span>
                    <h2 v-if="s.title">{{ $t(s.title) }}</h2>
                    <span v-if="s.button_text" class="tt-hero-btn">{{ $t(s.button_text) }}<v-icon size="18">mdi-arrow-right</v-icon></span>
                </div>
            </component>
        </div>

        <template v-if="slides.length > 1">
            <button type="button" class="tt-hero-arrow is-prev" :aria-label="$t('Previous slide')" @click="go(index - 1)"><v-icon>mdi-chevron-left</v-icon></button>
            <button type="button" class="tt-hero-arrow is-next" :aria-label="$t('Next slide')" @click="go(index + 1)"><v-icon>mdi-chevron-right</v-icon></button>
            <div class="tt-hero-dots" role="tablist">
                <button v-for="(s, i) in slides" :key="s.id" type="button" role="tab" :class="{ 'is-on': i === index }" :aria-selected="i === index" :aria-label="$t('Slide {n}', { n: i + 1 })" @click="go(i)"></button>
            </div>
        </template>
    </section>
</template>

<script>
/* Home page carousel: rotates every 6 seconds, pauses on hover, swipes on phones, phone-sized image when given. */
import { fileUrl } from "@/api";

export default {
    name: "HeroCarousel",
    props: { slides: { type: Array, default: () => [] } },
    data: () => ({ index: 0, paused: false, timer: null, touchX: null }),
    mounted() {
        this.timer = setInterval(() => !this.paused && !document.hidden && this.go(this.index + 1), 6000);
        this.$el?.addEventListener?.("touchstart", this.onTouchStart, { passive: true });
        this.$el?.addEventListener?.("touchend", this.onTouchEnd, { passive: true });
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },
    methods: {
        url: fileUrl,
        isExternal: (link) => /^https?:\/\//.test(link),
        go(i) {
            const n = this.slides.length;
            if (n) this.index = (i + n) % n;
        },
        onTouchStart(e) {
            this.touchX = e.touches[0].clientX;
        },
        onTouchEnd(e) {
            if (this.touchX === null) return;
            const dx = e.changedTouches[0].clientX - this.touchX;
            if (Math.abs(dx) > 40) this.go(this.index + (dx < 0 ? 1 : -1));
            this.touchX = null;
        },
    },
};
</script>
