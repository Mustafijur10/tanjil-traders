<template>
    <div>
        <PageHeader title="Customer Insights" subtitle="New and returning buyers, your best customers and where they live. Buyers are counted by phone number, so guests are included.">
            <RangeTabs v-model="days" />
            <ExportMenu :build="exportSpec" :disabled="!d" />
        </PageHeader>

        <MetricTiles :items="tiles" />

        <div class="tt-an-grid">
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-account-multiple-outline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("New and returning buyers") }}</h2><p>{{ $t("New = first order ever in this period.") }}</p></div>
                </div>
                <div class="tt-box-body"><apexchart v-if="d" type="bar" height="280" :options="buyersOpts" :series="buyersSeries" /></div>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-crown-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Top customers") }}</h2></div></div>
                <DataTable :headers="headers" :items="d?.top || []" :loading="!d" :rows-per-page="10" :hide-footer="(d?.top.length || 0) <= 10">
                    <template #item-name="row">
                        <div class="tt-two-line">
                            <router-link v-if="row.user_id" :to="`/admin/customers/${row.user_id}`">{{ row.name }}</router-link>
                            <span v-else>{{ row.name }} <small class="tt-muted">({{ $t("guest") }})</small></span>
                            <small>{{ row.phone }}</small>
                        </div>
                    </template>
                    <template #item-orders="row"><span class="tt-num">{{ $num(row.orders) }}</span></template>
                    <template #item-spent="row"><b class="tt-num">{{ $money(row.spent) }}</b></template>
                    <template #empty-message><EmptyState icon="mdi-account-outline" title="No buyers in this period" /></template>
                </DataTable>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-map-marker-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Where buyers live") }}</h2></div></div>
                <div class="tt-box-body"><ShareRows :rows="(d?.by_city || []).map((r) => ({ name: r.name, value: +r.revenue, sub: $tn(r.buyers, '{n} buyer', '{n} buyers') }))" money /></div>
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
import { timeChart, fmt } from "@/utils/charts";

export default {
    name: "CustomersReport",
    components: { apexchart: VueApexCharts, RangeTabs, MetricTiles, ShareRows },
    data() {
        return { days: Number(this.$route.query.days) || 30, d: null };
    },
    computed: {
        tiles() {
            const t = this.d?.totals || {};
            return [
                { label: "Buyers", value: this.$num(t.buyers ?? 0), icon: "mdi-account-multiple-outline", tone: "green", note: this.$t("{n} new, {r} returning", { n: this.$num(t.new_buyers ?? 0), r: this.$num(t.returning ?? 0) }) },
                { label: "New accounts", value: this.$num(t.new_accounts ?? 0), icon: "mdi-account-plus-outline", tone: "blue", note: this.$t("Signed up on the website") },
                { label: "Order again", value: fmt.pct(t.repeat_rate), icon: "mdi-repeat", tone: "violet", note: this.$t("Of all buyers, ever") },
                { label: "Guest orders", value: fmt.pct(t.guest_share), icon: "mdi-account-question-outline", tone: "amber", note: this.$t("Ordered without an account") },
                { label: "Value per buyer", value: this.$money(t.avg_lifetime ?? 0), icon: "mdi-cash-multiple", tone: "green", note: this.$t("All orders, ever") },
            ];
        },
        buyersSeries() {
            return [
                { name: this.$t("New"), data: this.d.series.map((s) => s.new) },
                { name: this.$t("Returning"), data: this.d.series.map((s) => s.returning) },
            ];
        },
        buyersOpts() {
            this.$store.getters["prefs/isDark"];
            const o = timeChart({ keys: this.d.series.map((s) => s.key), bucket: this.d.period.bucket, series: this.buyersSeries, kind: "bar" });
            o.chart.stacked = true;
            o.stroke = { width: 2, colors: ["transparent"] }; // a thin gap between stacked parts
            return o;
        },
        headers() {
            return [
                { text: this.$t("Customer"), value: "name", width: 240 },
                { text: this.$t("City"), value: "city" },
                { text: this.$t("Orders"), value: "orders", sortable: true },
                { text: this.$t("Spent"), value: "spent", sortable: true },
            ];
        },
    },
    watch: { days: { immediate: true, handler: "load" } },
    methods: {
        exportSpec() {
            const d = this.d;
            const t = d.totals;
            return {
                title: "Customer insights",
                filename: "customers",
                subtitle: periodLabel(d.period),
                kpis: [
                    { label: "Buyers", value: t.buyers, type: "number" },
                    { label: "New buyers", value: t.new_buyers, type: "number" },
                    { label: "Returning buyers", value: t.returning, type: "number" },
                    { label: "New accounts", value: t.new_accounts, type: "number" },
                    { label: "Ordered more than once (all time)", value: t.repeat_rate, type: "percent" },
                    { label: "Guest orders", value: t.guest_share, type: "percent" },
                    { label: "Value per buyer (all time)", value: t.avg_lifetime, type: "money" },
                ],
                tables: [
                    {
                        title: "Top customers",
                        columns: [
                            { text: "Customer", value: "name" },
                            { text: "Phone", value: "phone" },
                            { text: "City", value: "city" },
                            { text: "Orders", value: "orders", type: "number" },
                            { text: "Spent", value: "spent", type: "money" },
                        ],
                        rows: d.top,
                    },
                    { title: "New and returning", columns: [{ text: "Date", value: "key" }, { text: "New", value: "new", type: "number" }, { text: "Returning", value: "returning", type: "number" }], rows: d.series },
                    { title: "By city", columns: [{ text: "City", value: "name" }, { text: "Buyers", value: "buyers", type: "number" }, { text: "Sales", value: "revenue", type: "money" }], rows: d.by_city },
                ],
            };
        },
        async load() {
            this.$router.replace({ query: this.days !== 30 ? { days: this.days } : {} });
            this.d = (await api.get("/admin/analytics/customers", { params: { days: this.days } })).data;
        },
    },
};
</script>
