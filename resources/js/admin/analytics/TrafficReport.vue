<template>
    <div>
        <PageHeader title="Traffic" subtitle="Visitors to your website, where they came from, and how many went on to order. Staff and search-engine robots are not counted.">
            <RangeTabs v-model="days" />
            <ExportMenu :build="exportSpec" :disabled="!d" />
        </PageHeader>

        <MetricTiles :items="tiles" />

        <div class="tt-an-grid">
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-account-multiple-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Visitors") }}</h2></div></div>
                <div class="tt-box-body"><apexchart v-if="d" type="area" height="280" :options="visitorsOpts" :series="visitorsSeries" /></div>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-filter-variant</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("From visit to order") }}</h2><p>{{ $t("How many visits reached each step.") }}</p></div>
                </div>
                <div class="tt-box-body">
                    <ol v-if="d" class="tt-funnel">
                        <li v-for="(f, i) in d.funnel" :key="f.step">
                            <span class="tt-funnel-name">{{ $t(f.step) }}</span>
                            <span class="tt-funnel-bar"><i :style="{ '--w': pctOf(f.sessions, d.funnel[0].sessions) + '%' }"></i></span>
                            <b class="tt-num">{{ $num(f.sessions) }}</b>
                            <em class="tt-num">{{ i ? $num(pctOf(f.sessions, d.funnel[0].sessions)) + "%" : "" }}</em>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-web</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Traffic sources") }}</h2><p>{{ $t("Orders are credited to the last source that brought the buyer in the 30 days before ordering.") }}</p></div></div>
                <DataTable :headers="sourceHeaders" :items="d?.sources || []" :loading="!d" hide-footer>
                    <template #item-name="row"><span class="tt-source-dot" :class="'is-' + row.name"></span>{{ $t(sources[row.name] || row.name) }}</template>
                    <template #item-sessions="row"><span class="tt-num">{{ $num(row.sessions) }}</span></template>
                    <template #item-orders="row"><span class="tt-num">{{ $num(row.orders) }}</span></template>
                    <template #item-revenue="row"><b class="tt-num">{{ $money(row.revenue) }}</b></template>
                    <template #item-conversion="row"><span class="tt-num">{{ $num(row.conversion) }}%</span></template>
                    <template #empty-message><EmptyState icon="mdi-web" title="No visits yet" text="Visits are counted from now on as people browse the website." /></template>
                </DataTable>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-link-variant</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Top referrers") }}</h2><p>{{ $t("Search engines, social networks and websites.") }}</p></div></div>
                <div class="tt-box-body"><ShareRows :rows="(d?.referrers || []).map((r) => ({ name: r.name, value: +r.sessions, sub: $t(sources[r.source] || r.source) }))" /></div>
            </section>
            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-cellphone-link</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Devices") }}</h2></div></div>
                <div class="tt-box-body"><ChartOrEmpty :rows="d?.devices"><apexchart type="donut" height="260" :options="deviceOpts" :series="d.devices.map((r) => +r.sessions)" /></ChartOrEmpty></div>
            </section>
            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-google-chrome</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Browsers") }}</h2></div></div>
                <div class="tt-box-body"><ShareRows :rows="(d?.browsers || []).map((r) => ({ name: $t(r.name), value: +r.sessions }))" /></div>
            </section>
            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-monitor-cellphone</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Operating systems") }}</h2></div></div>
                <div class="tt-box-body"><ShareRows :rows="(d?.os || []).map((r) => ({ name: $t(r.name), value: +r.sessions }))" /></div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-file-document-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Most viewed pages") }}</h2></div></div>
                <DataTable :headers="pageHeaders" :items="d?.pages || []" :loading="!d" :rows-per-page="10" :hide-footer="(d?.pages.length || 0) <= 10">
                    <template #item-path="row"><a :href="row.path" target="_blank" rel="noopener" class="tt-ellipsis tt-path">{{ row.path }}</a></template>
                    <template #item-views="row"><span class="tt-num">{{ $num(row.views) }}</span></template>
                    <template #item-visitors="row"><span class="tt-num">{{ $num(row.visitors) }}</span></template>
                </DataTable>
            </section>
            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-login-variant</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Landing pages") }}</h2><p>{{ $t("The first page of each visit.") }}</p></div></div>
                <DataTable :headers="landingHeaders" :items="d?.landing || []" :loading="!d" hide-footer>
                    <template #item-path="row"><a :href="row.path" target="_blank" rel="noopener" class="tt-ellipsis tt-path">{{ row.path }}</a></template>
                    <template #item-sessions="row"><span class="tt-num">{{ $num(row.sessions) }}</span></template>
                </DataTable>
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
import ChartOrEmpty from "@/components/analytics/ChartOrEmpty.vue";
import { timeChart, donutChart, fmt, TRAFFIC_SOURCES } from "@/utils/charts";

const SOURCES = TRAFFIC_SOURCES;

export default {
    name: "TrafficReport",
    components: { apexchart: VueApexCharts, RangeTabs, MetricTiles, ShareRows, ChartOrEmpty },
    data() {
        return { days: Number(this.$route.query.days) || 30, d: null, sources: SOURCES };
    },
    computed: {
        tiles() {
            const t = this.d?.totals || {};
            const v = (k) => t[k]?.value ?? 0;
            const dl = (k) => t[k]?.delta ?? null;
            const mins = Math.floor(v("avg_duration") / 60);
            return [
                { label: "Visitors", value: this.$num(v("visitors")), delta: dl("visitors"), icon: "mdi-account-multiple-outline", tone: "green" },
                { label: "Visits", value: this.$num(v("sessions")), delta: dl("sessions"), icon: "mdi-login-variant", tone: "blue" },
                { label: "Page views", value: this.$num(v("pageviews")), delta: dl("pageviews"), icon: "mdi-file-eye-outline", tone: "violet" },
                { label: "Time per visit", value: `${this.$num(mins)}m ${this.$num(v("avg_duration") % 60)}s`, delta: dl("avg_duration"), icon: "mdi-timer-outline", tone: "amber" },
                { label: "Left after one page", value: fmt.pct(v("bounce_rate")), delta: dl("bounce_rate"), icon: "mdi-exit-run", tone: "red", invert: true },
                { label: "Visits that ordered", value: fmt.pct(v("conversion")), delta: dl("conversion"), icon: "mdi-cart-check", tone: "green" },
            ];
        },
        visitorsSeries() {
            return [
                { name: this.$t("Visitors"), data: this.d.series.map((s) => s.visitors) },
                { name: this.$t("Page views"), data: this.d.series.map((s) => s.views) },
            ];
        },
        visitorsOpts() {
            this.$store.getters["prefs/isDark"];
            return timeChart({ keys: this.d.series.map((s) => s.key), bucket: this.d.period.bucket, series: this.visitorsSeries });
        },
        deviceOpts() {
            this.$store.getters["prefs/isDark"];
            const name = { mobile: "Mobile", desktop: "Computer", tablet: "Tablet" };
            return donutChart({ names: this.d.devices.map((r) => this.$t(name[r.name] || r.name)), total: this.$t("Visits") });
        },
        sourceHeaders() {
            return [
                { text: this.$t("Source"), value: "name", width: 200 },
                { text: this.$t("Visits"), value: "sessions", sortable: true },
                { text: this.$t("Orders"), value: "orders", sortable: true },
                { text: this.$t("Sales"), value: "revenue", sortable: true },
                { text: this.$t("Visits that ordered"), value: "conversion", sortable: true },
            ];
        },
        pageHeaders() {
            return [
                { text: this.$t("Page"), value: "path", width: 240 },
                { text: this.$t("Views"), value: "views", sortable: true },
                { text: this.$t("Visitors"), value: "visitors", sortable: true },
            ];
        },
        landingHeaders() {
            return [
                { text: this.$t("Page"), value: "path", width: 260 },
                { text: this.$t("Visits"), value: "sessions" },
            ];
        },
    },
    watch: { days: { immediate: true, handler: "load" } },
    methods: {
        pctOf: (v, of) => (of ? Math.round((v / of) * 1000) / 10 : 0),
        exportSpec() {
            const d = this.d;
            const t = d.totals;
            const share = (rows) => ({ columns: [{ text: "Name", value: "name", get: (r) => this.$t(r.name) }, { text: "Visits", value: "sessions", type: "number" }], rows });
            return {
                title: "Traffic report",
                filename: "traffic",
                subtitle: periodLabel(d.period),
                kpis: [
                    { label: "Visitors", value: t.visitors.value, type: "number" },
                    { label: "Visits", value: t.sessions.value, type: "number" },
                    { label: "Page views", value: t.pageviews.value, type: "number" },
                    { label: "Pages per visit", value: t.pages_per.value, type: "number" },
                    { label: "Seconds per visit", value: t.avg_duration.value, type: "number" },
                    { label: "Left after one page", value: t.bounce_rate.value, type: "percent" },
                    { label: "Visits that ordered", value: t.conversion.value, type: "percent" },
                ],
                tables: [
                    { title: "Visitors by day", columns: [{ text: "Date", value: "key" }, { text: "Visitors", value: "visitors", type: "number" }, { text: "Page views", value: "views", type: "number" }], rows: d.series },
                    { title: "From visit to order", columns: [{ text: "Step", value: "step", get: (r) => this.$t(r.step) }, { text: "Visits", value: "sessions", type: "number" }], rows: d.funnel },
                    {
                        title: "Traffic sources",
                        columns: [
                            { text: "Source", value: "name", get: (r) => this.$t(SOURCES[r.name] || r.name) },
                            { text: "Visits", value: "sessions", type: "number" },
                            { text: "Orders", value: "orders", type: "number" },
                            { text: "Sales", value: "revenue", type: "money" },
                            { text: "Visits that ordered", value: "conversion", type: "percent" },
                        ],
                        rows: d.sources,
                    },
                    { title: "Top referrers", columns: [{ text: "Referrer", value: "name" }, { text: "Type", value: "source", get: (r) => this.$t(SOURCES[r.source] || r.source) }, { text: "Visits", value: "sessions", type: "number" }], rows: d.referrers },
                    { title: "Devices", ...share(d.devices) },
                    { title: "Browsers", ...share(d.browsers) },
                    { title: "Operating systems", ...share(d.os) },
                    { title: "Most viewed pages", columns: [{ text: "Page", value: "path" }, { text: "Views", value: "views", type: "number" }, { text: "Visitors", value: "visitors", type: "number" }], rows: d.pages },
                    { title: "Landing pages", columns: [{ text: "Page", value: "path" }, { text: "Visits", value: "sessions", type: "number" }], rows: d.landing },
                ],
            };
        },
        async load() {
            this.$router.replace({ query: this.days !== 30 ? { days: this.days } : {} });
            this.d = (await api.get("/admin/analytics/traffic", { params: { days: this.days } })).data;
        },
    },
};
</script>
