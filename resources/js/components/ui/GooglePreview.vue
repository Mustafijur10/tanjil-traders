<template>
    <div class="tt-gp">
        <div class="tt-gp-tabs" role="tablist" :aria-label="$t('Preview device')">
            <button v-for="d in devices" :key="d" type="button" role="tab" :class="{ 'is-on': device === d }" :aria-selected="device === d" @click="device = d">
                <v-icon size="16">{{ d === "mobile" ? "mdi-cellphone" : "mdi-monitor" }}</v-icon>{{ $t(d === "mobile" ? "Mobile" : "Desktop") }}
            </button>
        </div>

        <!-- Looks like a Google result; a preview only, Google decides the final look. -->
        <div class="tt-gp-result" :class="'is-' + device" aria-hidden="true">
            <div class="tt-gp-site">
                <span class="tt-gp-favicon"><img v-if="favicon" :src="favicon" alt="" /><span v-else>{{ siteName.charAt(0) }}</span></span>
                <span class="tt-gp-site-text">
                    <b>{{ siteName }}</b>
                    <small>{{ host }}<template v-for="p in pathParts" :key="p"> › {{ p }}</template></small>
                </span>
            </div>
            <div class="tt-gp-body">
                <div class="tt-gp-text">
                    <div class="tt-gp-title">{{ shownTitle }}</div>
                    <div v-if="hasRich" class="tt-gp-rich">
                        <template v-if="rating">
                            <span class="tt-gp-stars">{{ rating.toFixed(1) }} <i v-for="n in 5" :key="n" :class="{ 'is-on': n <= Math.round(rating) }">★</i></span>
                            <span v-if="reviews">({{ $num(reviews) }})</span>
                            <span>·</span>
                        </template>
                        <span v-if="price">{{ price }}</span>
                        <template v-if="stock !== null"><span>·</span><span :class="stock ? 'is-in' : 'is-out'">{{ $t(stock ? "In stock" : "Out of stock") }}</span></template>
                    </div>
                    <div class="tt-gp-desc">{{ shownDescription }}</div>
                </div>
                <img v-if="image" :src="image" alt="" class="tt-gp-thumb" />
            </div>
        </div>

        <ul v-if="checks.length" class="tt-gp-checks">
            <li v-for="c in checks" :key="c.text" :class="c.ok ? 'is-ok' : c.warn ? 'is-warn' : 'is-bad'">
                <v-icon size="16">{{ c.ok ? "mdi-check-circle" : c.warn ? "mdi-alert-circle-outline" : "mdi-close-circle-outline" }}</v-icon>
                <span>{{ c.text }}</span>
            </li>
        </ul>
    </div>
</template>

<script>
/*
 * Shows how a page can look in Google search results, plus a short checklist.
 *   <GooglePreview :title="..." :description="..." path="/product/galaxy-a55" :price="..." :rating="4.5" :reviews="12" :stock="true" :image="..." />
 * Google shortens titles at about 60 characters and descriptions at about 160, so the preview does too.
 */
import { fileUrl } from "@/api";

const cut = (s, n) => (s && s.length > n ? s.slice(0, n - 1).trimEnd() + "…" : s);

export default {
    name: "GooglePreview",
    props: {
        title: { type: String, default: "" },
        description: { type: String, default: "" },
        path: { type: String, default: "/" },
        image: { type: String, default: "" },
        price: { type: String, default: "" },
        rating: { type: Number, default: 0 },
        reviews: { type: Number, default: 0 },
        stock: { type: Boolean, default: null },
        // Extra checks from the page: [{ ok, warn?, text }]
        extraChecks: { type: Array, default: () => [] },
    },
    data: () => ({ device: "mobile", devices: ["mobile", "desktop"] }),
    computed: {
        settings() {
            return this.$store.state.prefs.settings;
        },
        siteName() {
            return this.settings["store.name"] || "Tanjil Traders";
        },
        favicon() {
            return this.settings["store.favicon"] ? fileUrl(this.settings["store.favicon"]) : "";
        },
        host() {
            return window.location.host.replace(/^www\./, "");
        },
        pathParts() {
            return this.path.split("/").filter(Boolean).slice(0, 3);
        },
        shownTitle() {
            return cut(this.title || this.$t("Page title"), 60);
        },
        shownDescription() {
            return cut(this.description || this.$t("Add a description so people know what they will find on this page."), this.device === "mobile" ? 120 : 160);
        },
        hasRich() {
            return !!(this.price || this.rating || this.stock !== null);
        },
        checks() {
            const t = (this.title || "").length;
            const d = (this.description || "").length;
            return [
                t === 0
                    ? { ok: false, text: this.$t("Add a page title") }
                    : t > 60
                      ? { ok: false, warn: true, text: this.$t("Title is {n} characters. Google shows about 60.", { n: t }) }
                      : { ok: true, text: this.$t("Title length is good ({n}/60)", { n: t }) },
                d === 0
                    ? { ok: false, text: this.$t("Add a description (Google may pick random text)") }
                    : d < 70
                      ? { ok: false, warn: true, text: this.$t("Description is short ({n}). Aim for 120–160.", { n: d }) }
                      : d > 160
                        ? { ok: false, warn: true, text: this.$t("Description is {n} characters. Google shows about 160.", { n: d }) }
                        : { ok: true, text: this.$t("Description length is good ({n}/160)", { n: d }) },
                ...this.extraChecks,
            ];
        },
    },
};
</script>
