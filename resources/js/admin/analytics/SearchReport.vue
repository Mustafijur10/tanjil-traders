<template>
    <div>
        <PageHeader title="Search & Wishlist" subtitle="What customers look for, what they could not find, and what they save for later.">
            <RangeTabs v-model="days" />
        </PageHeader>

        <MetricTiles :items="tiles" />

        <div class="tt-an-grid">
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-magnify</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Searches") }}</h2></div></div>
                <div class="tt-box-body"><apexchart v-if="d" type="bar" height="240" :options="seriesOpts" :series="series" /></div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-format-list-numbered</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Top searches") }}</h2></div></div>
                <DataTable :headers="topHeaders" :items="d?.top || []" :loading="!d" :rows-per-page="10" :hide-footer="(d?.top.length || 0) <= 10">
                    <template #item-query="row"><a :href="`/search?q=${encodeURIComponent(row.query)}`" target="_blank" rel="noopener">{{ row.query }}</a></template>
                    <template #item-searches="row"><span class="tt-num">{{ $num(row.searches) }}</span></template>
                    <template #item-results="row"><span class="tt-num">{{ $num(row.results) }}</span></template>
                    <template #empty-message><EmptyState icon="mdi-magnify" title="No searches yet" /></template>
                </DataTable>
            </section>

            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon is-amber"><v-icon size="20">mdi-magnify-close</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Searches with no results") }}</h2><p>{{ $t("Products to stock, or words to add to product names.") }}</p></div>
                </div>
                <DataTable :headers="zeroHeaders" :items="d?.zero || []" :loading="!d" :rows-per-page="10" :hide-footer="(d?.zero.length || 0) <= 10">
                    <template #item-query="row"><b>{{ row.query }}</b></template>
                    <template #item-searches="row"><span class="tt-num">{{ $num(row.searches) }}</span></template>
                    <template #item-last_at="row"><span class="tt-muted">{{ $date(row.last_at, { day: "numeric", month: "short" }) }}</span></template>
                    <template #empty-message><EmptyState icon="mdi-check-circle-outline" title="Every search found something" /></template>
                </DataTable>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-heart-outline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Most saved to wishlists") }}</h2><p>{{ $t("Popular wishes are good candidates for a flash sale, or to keep in stock.") }}</p></div>
                </div>
                <DataTable :headers="wishHeaders" :items="d?.wishlist || []" :loading="!d" hide-footer>
                    <template #item-name="row"><router-link :to="`/admin/products/${row.id}`">{{ row.name }}</router-link></template>
                    <template #item-saves="row"><b class="tt-num">{{ $num(row.saves) }}</b></template>
                    <template #item-recent="row"><span class="tt-num">{{ $num(row.recent) }}</span></template>
                    <template #item-stock="row"><StatusChip :label="row.stock > 0 ? $t('{n} in stock', { n: $num(row.stock) }) : $t('Out of stock')" :tone="row.stock > 0 ? 'green' : 'red'" /></template>
                    <template #empty-message><EmptyState icon="mdi-heart-outline" title="No saved products yet" text="Customers save products with the heart button." /></template>
                </DataTable>
            </section>
        </div>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";
import api from "@/api";
import RangeTabs from "@/components/analytics/RangeTabs.vue";
import MetricTiles from "@/components/analytics/MetricTiles.vue";
import { timeChart, fmt, palette } from "@/utils/charts";

export default {
    name: "SearchReport",
    components: { apexchart: VueApexCharts, RangeTabs, MetricTiles },
    data() {
        return { days: Number(this.$route.query.days) || 30, d: null };
    },
    computed: {
        tiles() {
            const t = this.d?.totals || {};
            return [
                { label: "Searches", value: this.$num(t.searches ?? 0), icon: "mdi-magnify", tone: "green", note: this.$t("{n} different words", { n: this.$num(t.terms ?? 0) }) },
                { label: "Found nothing", value: fmt.pct(t.zero_share), icon: "mdi-magnify-close", tone: "amber", note: this.$t("Of all searches") },
                { label: "People searching", value: this.$num(t.searchers ?? 0), icon: "mdi-account-search-outline", tone: "blue" },
                { label: "Saved to wishlists", value: this.$num(t.saves ?? 0), icon: "mdi-heart-outline", tone: "red", note: this.$t("{n} customers have a wishlist", { n: this.$num(t.wishlisters ?? 0) }) },
            ];
        },
        series() {
            return [
                { name: this.$t("Found results"), data: this.d.series.map((s) => s.searches - s.zero) },
                { name: this.$t("Found nothing"), data: this.d.series.map((s) => s.zero) },
            ];
        },
        seriesOpts() {
            this.$store.getters["prefs/isDark"];
            const o = timeChart({ keys: this.d.series.map((s) => s.key), bucket: this.d.period.bucket, series: this.series, kind: "bar", colors: [palette()[0], palette()[2]] });
            o.chart.stacked = true;
            o.stroke = { width: 2, colors: ["transparent"] };
            return o;
        },
        topHeaders() {
            return [
                { text: this.$t("Search"), value: "query", width: 220 },
                { text: this.$t("Times"), value: "searches", sortable: true },
                { text: this.$t("Results"), value: "results" },
            ];
        },
        zeroHeaders() {
            return [
                { text: this.$t("Search"), value: "query", width: 220 },
                { text: this.$t("Times"), value: "searches", sortable: true },
                { text: this.$t("Last"), value: "last_at" },
            ];
        },
        wishHeaders() {
            return [
                { text: this.$t("Product"), value: "name", width: 300 },
                { text: this.$t("Saved (all time)"), value: "saves", sortable: true },
                { text: this.$t("In this period"), value: "recent", sortable: true },
                { text: this.$t("Stock"), value: "stock" },
            ];
        },
    },
    watch: { days: { immediate: true, handler: "load" } },
    methods: {
        async load() {
            this.$router.replace({ query: this.days !== 30 ? { days: this.days } : {} });
            this.d = (await api.get("/admin/analytics/search", { params: { days: this.days } })).data;
        },
    },
};
</script>
