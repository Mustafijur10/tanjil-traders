<template>
    <div>
        <PageHeader title="Live Performance" subtitle="Who is on the website right now, today so far, and how fast pages load for real visitors.">
            <span class="tt-live-pill"><span class="tt-live-dot"></span>{{ $t("Updates every 15 seconds") }}</span>
        </PageHeader>

        <div class="tt-an-grid">
            <section class="tt-box tt-live-now">
                <div class="tt-box-body">
                    <small>{{ $t("On the website now") }}</small>
                    <b class="tt-num">{{ $num(d?.now ?? 0) }}</b>
                    <span class="tt-muted tt-small">{{ $t("Visitors active in the last 5 minutes") }}</span>
                    <div class="tt-live-devices">
                        <span v-for="dv in d?.devices || []" :key="dv.name"><v-icon size="16">{{ deviceIcon[dv.name] }}</v-icon>{{ $num(dv.sessions) }}</span>
                    </div>
                    <apexchart v-if="d" type="bar" height="120" :options="minuteOpts" :series="[{ name: $t('Visitors'), data: d.per_minute.map((m) => m.visitors) }]" />
                </div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-calendar-today</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Today so far") }}</h2></div></div>
                <div class="tt-box-body tt-today-grid">
                    <div><small>{{ $t("Visitors") }}</small><b class="tt-num">{{ $num(d?.today.visitors ?? 0) }}</b></div>
                    <div><small>{{ $t("Page views") }}</small><b class="tt-num">{{ $num(d?.today.pageviews ?? 0) }}</b></div>
                    <div><small>{{ $t("Orders") }}</small><b class="tt-num">{{ $num(d?.today.orders ?? 0) }}</b></div>
                    <div><small>{{ $t("Sales") }}</small><b class="tt-num">{{ $money(d?.today.revenue ?? 0) }}</b></div>
                </div>
                <ul class="tt-live-orders">
                    <li v-for="o in d?.recent_orders || []" :key="o.id">
                        <router-link :to="`/admin/orders/${o.id}`"><b>{{ o.order_number }}</b></router-link>
                        <span class="tt-muted tt-small tt-ellipsis">{{ o.name }} · {{ o.city }}</span>
                        <b class="tt-num">{{ $money(o.total) }}</b>
                        <small class="tt-muted">{{ ago(o.created_at) }}</small>
                    </li>
                </ul>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-eye-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Pages being viewed") }}</h2></div></div>
                <div class="tt-box-body">
                    <ul v-if="d?.pages.length" class="tt-live-pages">
                        <li v-for="p in d.pages" :key="p.id">
                            <v-icon size="18" class="tt-muted">{{ deviceIcon[p.device] || "mdi-monitor" }}</v-icon>
                            <a :href="p.path" target="_blank" rel="noopener" class="tt-ellipsis">{{ p.product?.name || p.path }}</a>
                            <span class="tt-muted tt-small">{{ $t(sources[p.source] || p.source) }}<template v-if="p.source_name"> · {{ p.source_name }}</template></span>
                            <small class="tt-muted">{{ ago(p.last_seen_at) }}</small>
                        </li>
                    </ul>
                    <p v-else class="tt-muted tt-small">{{ $t("Nobody is on the website right now.") }}</p>
                </div>
            </section>

            <!-- Core Web Vitals -->
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-speedometer</v-icon></span>
                    <div class="flex-grow-1">
                        <h2>{{ $t("Speed for real visitors (Core Web Vitals)") }}</h2>
                        <p>{{ $t("Last 7 days, measured in visitors' browsers. Google uses these numbers for ranking.") }}<template v-if="v"> · {{ $tn(v.all.samples, "{n} page load", "{n} page loads") }}</template></p>
                    </div>
                </div>
                <div class="tt-box-body">
                    <div class="tt-vitals">
                        <div v-for="m in vitalList" :key="m.key" class="tt-vital" :class="'is-' + m.rating">
                            <small>{{ $t(m.label) }}</small>
                            <b class="tt-num">{{ m.display }}</b>
                            <span class="tt-vital-rating"><v-icon size="15">{{ ratingIcon[m.rating] }}</v-icon>{{ $t(ratingText[m.rating]) }}</span>
                            <em>{{ $t(m.hint) }}</em>
                        </div>
                    </div>
                    <div v-if="v && Object.keys(v.devices).length" class="tt-vitals-devices">
                        <h3 class="tt-an-sub">{{ $t("By device (page load time)") }}</h3>
                        <span v-for="(s, dev) in v.devices" :key="dev"><v-icon size="16">{{ deviceIcon[dev] }}</v-icon>{{ s.lcp ? sec(s.lcp) : "—" }} <small class="tt-muted">({{ $num(s.samples) }})</small></span>
                    </div>
                    <div v-if="v?.slowest.length">
                        <h3 class="tt-an-sub">{{ $t("Slowest pages") }}</h3>
                        <ul class="tt-an-list">
                            <li v-for="s in v.slowest" :key="s.path">
                                <a :href="s.path" target="_blank" rel="noopener" class="tt-ellipsis tt-path">{{ s.path }}</a>
                                <b class="tt-num" :class="s.lcp > 4000 ? 'tt-danger-text' : ''">{{ sec(s.lcp) }}</b>
                                <small class="tt-muted">{{ $tn(s.samples, "{n} load", "{n} loads") }}</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- PageSpeed -->
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-google</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Test a page with Google PageSpeed") }}</h2><p>{{ $t("A lab test from Google's servers, with tips to make the page faster. Takes about 20 seconds.") }}</p></div>
                </div>
                <div class="tt-box-body">
                    <form class="tt-psi-form" @submit.prevent="runTest">
                        <v-input-trz v-model.trim="psiPath" density="compact" :label="$t('Page address')" placeholder="/" prefix="/" />
                        <div class="tt-segments">
                            <button type="button" :class="{ 'is-on': strategy === 'mobile' }" @click="strategy = 'mobile'"><v-icon size="16">mdi-cellphone</v-icon> {{ $t("Mobile") }}</button>
                            <button type="button" :class="{ 'is-on': strategy === 'desktop' }" @click="strategy = 'desktop'"><v-icon size="16">mdi-monitor</v-icon> {{ $t("Computer") }}</button>
                        </div>
                        <v-btn-primary type="submit" :loading="testing">{{ $t("Run test") }}</v-btn-primary>
                    </form>
                    <v-alert v-if="psiError" type="warning" variant="tonal" density="compact" class="mt-4">{{ psiError }}</v-alert>
                    <div v-if="psi" class="tt-psi">
                        <div class="tt-psi-score" :class="psi.score >= 90 ? 'is-good' : psi.score >= 50 ? 'is-mid' : 'is-low'"><b class="tt-num">{{ $num(psi.score) }}</b><small>{{ $t("Performance") }}</small></div>
                        <div class="tt-psi-metrics">
                            <div v-for="(m, k) in psi.metrics" :key="k"><small>{{ $t(psiNames[k]) }}</small><b>{{ m.value || "—" }}</b></div>
                        </div>
                        <div v-if="psi.opportunities.length" class="tt-psi-tips">
                            <h3 class="tt-an-sub">{{ $t("What would help most") }}</h3>
                            <ul><li v-for="o in psi.opportunities" :key="o.title">{{ o.title }}<small v-if="o.saving"> · {{ o.saving }}</small></li></ul>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";
import api from "@/api";
import { base, palette, TRAFFIC_SOURCES } from "@/utils/charts";


// Google's thresholds: good / needs improvement / poor
const LIMITS = { lcp: [2500, 4000], inp: [200, 500], cls: [0.1, 0.25], ttfb: [800, 1800] };

export default {
    name: "LiveReport",
    components: { apexchart: VueApexCharts },
    data: () => ({
        d: null,
        timer: null,
        now: Date.now(),
        psiPath: "",
        strategy: "mobile",
        testing: false,
        psi: null,
        psiError: "",
        sources: TRAFFIC_SOURCES,
        deviceIcon: { mobile: "mdi-cellphone", desktop: "mdi-monitor", tablet: "mdi-tablet" },
        ratingIcon: { good: "mdi-check-circle", mid: "mdi-alert", poor: "mdi-close-circle", none: "mdi-minus-circle-outline" },
        ratingText: { good: "Good", mid: "Needs work", poor: "Poor", none: "No data yet" },
        psiNames: { fcp: "First content", lcp: "Main content", tbt: "Blocking time", cls: "Layout shift", si: "Speed index" },
    }),
    computed: {
        v() {
            return this.d?.vitals;
        },
        vitalList() {
            const a = this.v?.all || {};
            const rate = (k, val) => (val === null || val === undefined ? "none" : val <= LIMITS[k][0] ? "good" : val <= LIMITS[k][1] ? "mid" : "poor");
            return [
                { key: "lcp", label: "Page load (LCP)", display: a.lcp != null ? this.sec(a.lcp) : "—", rating: rate("lcp", a.lcp), hint: "Main content visible. Good under 2.5 s." },
                { key: "inp", label: "Response to taps (INP)", display: a.inp != null ? `${this.$num(a.inp)} ms` : "—", rating: rate("inp", a.inp), hint: "Delay after a tap or click. Good under 200 ms." },
                { key: "cls", label: "Layout stability (CLS)", display: a.cls != null ? this.$num(a.cls) : "—", rating: rate("cls", a.cls), hint: "Content jumping while loading. Good under 0.1." },
                { key: "ttfb", label: "Server response (TTFB)", display: a.ttfb != null ? this.sec(a.ttfb) : "—", rating: rate("ttfb", a.ttfb), hint: "Time until the server answers. Good under 0.8 s." },
            ];
        },
        minuteOpts() {
            this.$store.getters["prefs/isDark"];
            return base({
                chart: { type: "bar", sparkline: { enabled: true } },
                colors: [palette()[0]],
                plotOptions: { bar: { borderRadius: 2, columnWidth: "70%" } },
                xaxis: { categories: this.d.per_minute.map((m) => m.key) },
                tooltip: { y: { formatter: (v) => this.$num(v) } },
            });
        },
    },
    created() {
        this.load();
        this.timer = setInterval(() => {
            this.now = Date.now();
            if (document.visibilityState === "visible") this.load();
        }, 15000);
    },
    beforeUnmount() {
        clearInterval(this.timer);
    },
    methods: {
        sec(ms) {
            return `${this.$num(Math.round(ms / 100) / 10)} s`;
        },
        ago(at) {
            const s = Math.max(0, Math.round((this.now - new Date(at).getTime()) / 1000));
            if (s < 60) return this.$t("just now");
            if (s < 3600) return this.$t("{n} min ago", { n: this.$num(Math.floor(s / 60)) });
            return this.$date(at, { hour: "numeric", minute: "2-digit" });
        },
        async load() {
            try {
                this.d = (await api.get("/admin/analytics/live", { quiet: !!this.d })).data;
            } catch (e) {
                /* keep the last numbers */
            }
        },
        async runTest() {
            this.testing = true;
            this.psiError = "";
            try {
                this.psi = (await api.post("/admin/analytics/pagespeed", { path: "/" + this.psiPath.replace(/^\/+/, ""), strategy: this.strategy }, { quiet: true, timeout: 90000 })).data;
            } catch (e) {
                this.psi = null;
                this.psiError = e.response?.data?.message || this.$t("The test could not run. Try again in a minute.");
            } finally {
                this.testing = false;
            }
        },
    },
};
</script>
