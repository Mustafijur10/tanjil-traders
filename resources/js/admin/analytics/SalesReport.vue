<template>
    <div>
        <PageHeader title="Sales" subtitle="What you sold, what you earned and where it came from. Cancelled and returned orders are not counted.">
            <RangeTabs v-model="days" />
        </PageHeader>

        <MetricTiles :items="tiles" />

        <div class="tt-an-grid">
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-chart-areaspline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Sales and profit") }}</h2><p>{{ $t("Profit is sales minus product cost and discounts, for products with a cost price.") }}</p></div>
                </div>
                <div class="tt-box-body"><apexchart v-if="d" type="area" height="300" :options="moneyOpts" :series="moneySeries" /></div>
            </section>
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-chart-bar</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Orders") }}</h2></div>
                </div>
                <div class="tt-box-body"><apexchart v-if="d" type="bar" height="220" :options="ordersOpts" :series="ordersSeries" /></div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-shape-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Sales by category") }}</h2></div></div>
                <div class="tt-box-body"><ChartOrEmpty :rows="d?.by_category"><apexchart type="donut" height="300" :options="categoryOpts" :series="d.by_category.map((r) => +r.revenue)" /></ChartOrEmpty></div>
            </section>
            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-web</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Sales by traffic source") }}</h2><p>{{ $t("Where buyers came from before ordering.") }}</p></div></div>
                <div class="tt-box-body"><ChartOrEmpty :rows="d?.by_traffic"><apexchart type="bar" :height="barHeight(d.by_traffic)" :options="trafficOpts" :series="[{ name: $t('Sales'), data: d.by_traffic.map((r) => +r.revenue) }]" /></ChartOrEmpty></div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-map-marker-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Top cities") }}</h2></div></div>
                <div class="tt-box-body"><ChartOrEmpty :rows="d?.by_city"><apexchart type="bar" :height="barHeight(d.by_city)" :options="cityOpts" :series="[{ name: $t('Sales'), data: d.by_city.map((r) => +r.revenue) }]" /></ChartOrEmpty></div>
            </section>
            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-cash-multiple</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Payment and channel") }}</h2></div></div>
                <div class="tt-box-body tt-an-rows">
                    <h3>{{ $t("Payment method") }}</h3>
                    <ShareRows :rows="(d?.by_payment || []).map((r) => ({ name: $t(methods[r.name] || r.name), value: +r.revenue, sub: $tn(r.orders, '{n} order', '{n} orders') }))" money />
                    <h3>{{ $t("Who placed the order") }}</h3>
                    <ShareRows :rows="(d?.by_channel || []).map((r) => ({ name: $t(r.name === 'admin' ? 'Staff (phone, shop)' : 'Customer on the website'), value: +r.revenue, sub: $tn(r.orders, '{n} order', '{n} orders') }))" money />
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";
import api from "@/api";
import RangeTabs from "@/components/analytics/RangeTabs.vue";
import MetricTiles from "@/components/analytics/MetricTiles.vue";
import ShareRows from "@/components/analytics/ShareRows.vue";
import ChartOrEmpty from "@/components/analytics/ChartOrEmpty.vue";
import { timeChart, rankChart, donutChart, fmt, palette, TRAFFIC_SOURCES } from "@/utils/charts";
import { PAYMENT_METHOD } from "@/utils/orders";

const SOURCE = TRAFFIC_SOURCES;

export default {
    name: "SalesReport",
    components: { apexchart: VueApexCharts, RangeTabs, MetricTiles, ShareRows, ChartOrEmpty },
    data() {
        return { days: Number(this.$route.query.days) || 30, d: null, methods: PAYMENT_METHOD };
    },
    computed: {
        dark() {
            return this.$store.getters["prefs/isDark"];
        },
        tiles() {
            const t = this.d?.totals || {};
            const v = (k) => t[k]?.value ?? 0;
            const dl = (k) => t[k]?.delta ?? null;
            return [
                { label: "Sales", value: this.$money(v("revenue")), delta: dl("revenue"), icon: "mdi-cash-multiple", tone: "green" },
                { label: "Orders", value: this.$num(v("orders")), delta: dl("orders"), icon: "mdi-receipt-text-outline", tone: "blue" },
                { label: "Average order", value: this.$money(v("aov")), delta: dl("aov"), icon: "mdi-scale-balance", tone: "violet" },
                { label: "Profit", value: this.$money(v("profit")), delta: dl("profit"), icon: "mdi-trending-up", tone: "green" },
                { label: "Units sold", value: this.$num(v("units")), delta: dl("units"), icon: "mdi-package-variant-closed", tone: "amber" },
                { label: "Cancelled", value: fmt.pct(v("cancel_rate")), delta: dl("cancel_rate"), icon: "mdi-close-circle-outline", tone: "red", invert: true },
            ];
        },
        keys() {
            return (this.d?.series || []).map((s) => s.key);
        },
        moneySeries() {
            return [
                { name: this.$t("Sales"), data: this.d.series.map((s) => s.revenue) },
                { name: this.$t("Profit"), data: this.d.series.map((s) => s.profit) },
            ];
        },
        moneyOpts() {
            this.dark;
            return timeChart({ keys: this.keys, bucket: this.d.period.bucket, series: this.moneySeries, format: fmt.money });
        },
        ordersSeries() {
            return [{ name: this.$t("Orders"), data: this.d.series.map((s) => s.orders) }];
        },
        ordersOpts() {
            this.dark;
            return timeChart({ keys: this.keys, bucket: this.d.period.bucket, series: this.ordersSeries, kind: "bar", colors: [palette()[1]] });
        },
        categoryOpts() {
            this.dark;
            return donutChart({ names: this.d.by_category.map((r) => this.$t(r.name)), format: fmt.money, total: this.$t("Sales") });
        },
        trafficOpts() {
            this.dark;
            return rankChart({ names: this.d.by_traffic.map((r) => this.$t(SOURCE[r.name] || r.name)), format: fmt.money, color: palette()[1] });
        },
        cityOpts() {
            this.dark;
            return rankChart({ names: this.d.by_city.map((r) => r.name), format: fmt.money });
        },
    },
    watch: { days: { immediate: true, handler: "load" } },
    methods: {
        barHeight: (rows) => Math.max(140, (rows?.length || 0) * 42 + 40),
        async load() {
            this.$router.replace({ query: this.days !== 30 ? { days: this.days } : {} });
            this.d = (await api.get("/admin/analytics/sales", { params: { days: this.days } })).data;
        },
    },
};
</script>
