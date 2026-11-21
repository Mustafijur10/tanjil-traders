<template>
    <div>
        <PageHeader title="SEO" subtitle="How well your products are set up for Google, visits from search engines, and Google Search Console results.">
            <RangeTabs v-model="days" :options="[7, 30, 90]" />
            <ExportMenu :build="exportSpec" :disabled="!d" />
        </PageHeader>

        <div class="tt-an-grid">
            <!-- Catalog health -->
            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-clipboard-check-outline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Product page health") }}</h2><p>{{ $t("Published products with no problems below.") }}</p></div>
                </div>
                <div class="tt-box-body">
                    <div v-if="d" class="tt-seo-score" :class="scoreTone">
                        <b class="tt-num">{{ $num(d.score) }}</b><span>/100</span>
                        <p>{{ $t("{ok} of {all} products are ready for search engines.", { ok: $num(Math.round((d.score / 100) * d.products)), all: $num(d.products) }) }}</p>
                    </div>
                    <ul v-if="d" class="tt-seo-issues">
                        <li v-for="i in d.issues" :key="i.key" :class="{ 'is-ok': !i.count }">
                            <v-icon size="18">{{ i.count ? "mdi-alert-circle-outline" : "mdi-check-circle-outline" }}</v-icon>
                            <span>
                                <b>{{ $t(labels[i.key].title) }}</b>
                                <small>{{ $t(labels[i.key].fix) }}</small>
                                <span v-if="i.count" class="tt-seo-items">
                                    <router-link v-for="p in i.items" :key="p.id" :to="`/admin/products/${p.id}`">{{ p.name }}</router-link>
                                </span>
                            </span>
                            <em class="tt-num">{{ i.count ? $num(i.count) : $t("OK") }}</em>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Organic visits -->
            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-google</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Visits from search engines") }}</h2><p v-if="d">{{ $tn(d.organic.sessions, "{n} visit in this period", "{n} visits in this period") }}</p></div>
                </div>
                <div class="tt-box-body">
                    <apexchart v-if="d" type="area" height="200" :options="organicOpts" :series="[{ name: $t('Visits'), data: d.organic.series.map((s) => s.sessions) }]" />
                    <h3 class="tt-an-sub">{{ $t("Pages people land on from search") }}</h3>
                    <ShareRows :rows="(d?.organic.landing || []).map((r) => ({ name: r.path, value: +r.sessions }))" />
                </div>
            </section>

            <!-- Search Console -->
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-chart-timeline-variant</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Google Search Console") }}</h2><p>{{ $t("How your pages show up on Google: impressions, clicks, position and the words people search.") }}</p></div>
                </div>
                <div class="tt-box-body">
                    <div v-if="d && !gsc.configured" class="tt-setup">
                        <v-icon size="28">mdi-link-variant-plus</v-icon>
                        <div>
                            <b>{{ $t("Connect Search Console to see Google data here") }}</b>
                            <ol>
                                <li>{{ $t("Add your website in Google Search Console and verify it.") }}</li>
                                <li>{{ $t("In Google Cloud, create a service account and download its JSON key.") }}</li>
                                <li>{{ $t("In Search Console › Settings › Users, add the service account email with Restricted access.") }}</li>
                                <li>{{ $t("Paste the property name and the JSON key in Settings › Integrations & SEO.") }}</li>
                            </ol>
                            <v-btn-secondary size="small" to="/admin/settings/integrations">{{ $t("Open Integrations & SEO") }}</v-btn-secondary>
                        </div>
                    </div>
                    <v-alert v-else-if="gsc.error" type="warning" variant="tonal" density="compact">{{ gsc.error }}</v-alert>
                    <template v-else-if="gsc.configured">
                        <MetricTiles :items="gscTiles" />
                        <apexchart type="line" height="220" :options="gscOpts" :series="[{ name: $t('Clicks'), data: gsc.series.map((s) => s.clicks) }]" />
                        <div class="tt-an-grid is-inner">
                            <DataTable :headers="queryHeaders" :items="gsc.queries" hide-footer>
                                <template #item-ctr="row">{{ $num(row.ctr) }}%</template>
                                <template #item-position="row">{{ $num(row.position) }}</template>
                            </DataTable>
                            <DataTable :headers="pageHeaders" :items="gsc.pages" hide-footer>
                                <template #item-key="row"><span class="tt-ellipsis tt-path">{{ shortUrl(row.key) }}</span></template>
                            </DataTable>
                        </div>
                    </template>
                </div>
            </section>

            <section v-if="d" class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-sitemap-outline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Sitemap") }}</h2><p>{{ $t("Built automatically from your products, categories, brands and pages. Submit it once in Search Console.") }}</p></div>
                    <v-btn-secondary size="small" :href="d.sitemap.url" target="_blank" append-icon="mdi-open-in-new">sitemap.xml</v-btn-secondary>
                    <v-btn-tertiary size="small" :href="d.sitemap.robots" target="_blank" append-icon="mdi-open-in-new">robots.txt</v-btn-tertiary>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";
import api from "@/api";
import { periodLabel } from "@/utils/exporter";
import RangeTabs from "@/components/analytics/RangeTabs.vue";
import MetricTiles from "@/components/analytics/MetricTiles.vue";
import ShareRows from "@/components/analytics/ShareRows.vue";
import { timeChart, fmt, palette } from "@/utils/charts";

const LABELS = {
    no_meta: { title: "No search description", fix: "Add a short description or a meta description, shown under the title on Google." },
    no_image: { title: "No photo", fix: "Products without photos rarely get clicks." },
    no_alt: { title: "Photos without alt text", fix: "Describe each photo in a few words; it helps Google Images and screen readers." },
    thin: { title: "Very short description", fix: "Write at least two or three sentences about the product." },
    long_title: { title: "Title too long", fix: "Keep the page title under 65 characters so Google does not cut it." },
    duplicate_name: { title: "Same name as another product", fix: "Give each product its own name, e.g. add the colour or size." },
};

export default {
    name: "SeoReport",
    components: { apexchart: VueApexCharts, RangeTabs, MetricTiles, ShareRows },
    data() {
        return { days: Number(this.$route.query.days) || 30, d: null, labels: LABELS };
    },
    computed: {
        gsc() {
            return this.d?.console || {};
        },
        scoreTone() {
            const s = this.d?.score ?? 0;
            return s >= 80 ? "is-good" : s >= 50 ? "is-mid" : "is-low";
        },
        organicOpts() {
            this.$store.getters["prefs/isDark"];
            return timeChart({ keys: this.d.organic.series.map((s) => s.key), bucket: this.d.period.bucket, series: [{}] });
        },
        gscTiles() {
            const t = this.gsc.totals || {};
            return [
                { label: "Clicks from Google", value: this.$num(t.clicks ?? 0), icon: "mdi-cursor-default-click-outline", tone: "green" },
                { label: "Shown on Google", value: this.$num(t.impressions ?? 0), icon: "mdi-eye-outline", tone: "blue" },
                { label: "Click rate", value: fmt.pct(t.ctr), icon: "mdi-percent-outline", tone: "violet" },
                { label: "Average position", value: this.$num(t.position ?? 0), icon: "mdi-format-list-numbered", tone: "amber", note: this.$t("Lower is better") },
            ];
        },
        gscOpts() {
            this.$store.getters["prefs/isDark"];
            return timeChart({ keys: this.gsc.series.map((s) => s.key), bucket: "day", series: [{}], kind: "line", colors: [palette()[1]] });
        },
        queryHeaders() {
            return [
                { text: this.$t("Search on Google"), value: "key", width: 220 },
                { text: this.$t("Clicks"), value: "clicks", sortable: true },
                { text: this.$t("Shown"), value: "impressions", sortable: true },
                { text: this.$t("Click rate"), value: "ctr" },
                { text: this.$t("Position"), value: "position", sortable: true },
            ];
        },
        pageHeaders() {
            return [
                { text: this.$t("Page"), value: "key", width: 240 },
                { text: this.$t("Clicks"), value: "clicks", sortable: true },
                { text: this.$t("Shown"), value: "impressions", sortable: true },
            ];
        },
    },
    watch: { days: { immediate: true, handler: "load" } },
    methods: {
        shortUrl: (u) => String(u || "").replace(/^https?:\/\/[^/]+/, "") || "/",
        exportSpec() {
            const d = this.d;
            const g = this.gsc;
            const tables = [
                {
                    title: "Product page health",
                    columns: [
                        { text: "Check", value: "key", get: (r) => this.$t(LABELS[r.key].title) },
                        { text: "Products", value: "count", type: "number" },
                        { text: "Examples", value: "items", get: (r) => r.items.map((p) => p.name).join(", ") },
                        { text: "How to fix", value: "fix", get: (r) => this.$t(LABELS[r.key].fix) },
                    ],
                    rows: d.issues,
                },
                { title: "Visits from search engines", columns: [{ text: "Date", value: "key" }, { text: "Visits", value: "sessions", type: "number" }], rows: d.organic.series },
                { title: "Landing pages from search", columns: [{ text: "Page", value: "path" }, { text: "Visits", value: "sessions", type: "number" }], rows: d.organic.landing },
            ];
            if (g.configured && !g.error) {
                const cols = (first) => [
                    { text: first, value: "key" },
                    { text: "Clicks", value: "clicks", type: "number" },
                    { text: "Shown", value: "impressions", type: "number" },
                    { text: "Click rate", value: "ctr", type: "percent" },
                    { text: "Position", value: "position", type: "number" },
                ];
                tables.push({ title: "Google searches", columns: cols("Search on Google"), rows: g.queries }, { title: "Google pages", columns: cols("Page"), rows: g.pages });
            }
            return {
                title: "SEO report",
                filename: "seo",
                subtitle: periodLabel(d.period),
                kpis: [
                    { label: "Product page health score", value: d.score, type: "number" },
                    { label: "Published products", value: d.products, type: "number" },
                    { label: "Visits from search engines", value: d.organic.sessions, type: "number" },
                    ...(g.configured && !g.error
                        ? [
                              { label: "Clicks from Google", value: g.totals.clicks, type: "number" },
                              { label: "Shown on Google", value: g.totals.impressions, type: "number" },
                              { label: "Average position", value: g.totals.position, type: "number" },
                          ]
                        : []),
                ],
                tables,
            };
        },
        async load() {
            this.$router.replace({ query: this.days !== 30 ? { days: this.days } : {} });
            this.d = (await api.get("/admin/analytics/seo", { params: { days: this.days } })).data;
        },
    },
};
</script>
