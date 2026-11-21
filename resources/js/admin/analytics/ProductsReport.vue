<template>
    <div>
        <PageHeader title="Product Performance" subtitle="Best sellers, most viewed products, and the ones that need attention.">
            <RangeTabs v-model="days" />
            <ExportMenu :build="exportSpec" :disabled="!d" />
        </PageHeader>

        <div class="tt-an-grid">
            <section class="tt-box tt-an-wide">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-trophy-outline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Best sellers") }}</h2><p>{{ $t("By sales in this period. Margin shows for products with a cost price.") }}</p></div>
                </div>
                <DataTable :headers="topHeaders" :items="d?.top || []" :loading="!d" :rows-per-page="10" :hide-footer="(d?.top.length || 0) <= 10">
                    <template #item-name="row"><router-link v-if="row.id" :to="`/admin/products/${row.id}`">{{ row.name }}</router-link><span v-else>{{ row.name }}</span></template>
                    <template #item-units="row"><span class="tt-num">{{ $num(row.units) }}</span></template>
                    <template #item-revenue="row"><b class="tt-num">{{ $money(row.revenue) }}</b></template>
                    <template #item-profit="row"><span class="tt-num">{{ row.profit === null ? "—" : $money(row.profit) }}</span></template>
                    <template #item-margin="row"><span class="tt-num">{{ row.margin === null ? "—" : $num(row.margin) + "%" }}</span></template>
                    <template #empty-message><EmptyState icon="mdi-trophy-outline" title="No sales in this period" /></template>
                </DataTable>
            </section>

            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon"><v-icon size="20">mdi-eye-outline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Most viewed") }}</h2><p>{{ $t("Visitors who opened the product page, and how many of them ordered.") }}</p></div>
                </div>
                <DataTable :headers="viewHeaders" :items="d?.viewed || []" :loading="!d" hide-footer>
                    <template #item-name="row"><router-link :to="`/admin/products/${row.id}`" class="tt-ellipsis">{{ row.name }}</router-link></template>
                    <template #item-visitors="row"><span class="tt-num">{{ $num(row.visitors) }}</span></template>
                    <template #item-orders="row"><span class="tt-num">{{ $num(row.orders) }}</span></template>
                    <template #item-conversion="row"><span class="tt-num">{{ $num(row.conversion) }}%</span></template>
                    <template #empty-message><EmptyState icon="mdi-eye-outline" title="No product views yet" text="Views are counted from now on, as visitors browse the website." /></template>
                </DataTable>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-tag-multiple-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Sales by brand") }}</h2></div></div>
                <div class="tt-box-body"><ChartOrEmpty :rows="d?.by_brand"><apexchart type="bar" :height="Math.max(160, d.by_brand.length * 42 + 40)" :options="brandOpts" :series="[{ name: $t('Sales'), data: d.by_brand.map((r) => +r.revenue) }]" /></ChartOrEmpty></div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon is-amber"><v-icon size="20">mdi-alert-outline</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Viewed a lot, rarely bought") }}</h2><p>{{ $t("Check the price, photos and description of these.") }}</p></div>
                </div>
                <div class="tt-box-body">
                    <ul v-if="d?.attention.length" class="tt-an-list">
                        <li v-for="p in d.attention" :key="p.id">
                            <router-link :to="`/admin/products/${p.id}`" class="tt-ellipsis">{{ p.name }}</router-link>
                            <span class="tt-muted tt-small">{{ $t("{v} visitors, {o} orders", { v: $num(p.visitors), o: $num(p.orders) }) }}</span>
                        </li>
                    </ul>
                    <p v-else class="tt-muted tt-small">{{ $t("Nothing stands out. Products with at least 10 visitors and under 2% ordering show here.") }}</p>
                </div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon is-red"><v-icon size="20">mdi-package-variant-remove</v-icon></span>
                    <div class="flex-grow-1">
                        <h2>{{ $t("In stock but not sold") }}</h2>
                        <p v-if="d">{{ $tn(d.unsold.count, "{n} product", "{n} products") }} · {{ $t("{amount} of stock", { amount: $money(d.unsold.value) }) }}</p>
                    </div>
                </div>
                <div class="tt-box-body">
                    <ul v-if="d?.unsold.items.length" class="tt-an-list">
                        <li v-for="p in d.unsold.items" :key="p.id">
                            <router-link :to="`/admin/products/${p.id}`" class="tt-ellipsis">{{ p.name }}</router-link>
                            <span class="tt-muted tt-small">{{ $t("{n} in stock", { n: $num(p.stock_qty) }) }}</span>
                        </li>
                    </ul>
                    <p v-else class="tt-muted tt-small">{{ $t("Every product in stock sold at least once in this period.") }}</p>
                </div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head"><span class="tt-box-icon is-red"><v-icon size="20">mdi-star-half-full</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Low ratings") }}</h2><p>{{ $t("Products rated under 3.5 by at least two customers.") }}</p></div></div>
                <div class="tt-box-body">
                    <ul v-if="d?.low_rated.length" class="tt-an-list">
                        <li v-for="p in d.low_rated" :key="p.id">
                            <router-link :to="`/admin/reviews`" class="tt-ellipsis">{{ p.name }}</router-link>
                            <span class="tt-small"><v-icon size="14" color="amber">mdi-star</v-icon> {{ $num(p.rating_avg) }} · {{ $tn(p.review_count, "{n} review", "{n} reviews") }}</span>
                        </li>
                    </ul>
                    <p v-else class="tt-muted tt-small">{{ $t("No poorly rated products.") }}</p>
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
import ChartOrEmpty from "@/components/analytics/ChartOrEmpty.vue";
import { rankChart, fmt } from "@/utils/charts";

export default {
    name: "ProductsReport",
    components: { apexchart: VueApexCharts, RangeTabs, ChartOrEmpty },
    data() {
        return { days: Number(this.$route.query.days) || 30, d: null };
    },
    computed: {
        topHeaders() {
            return [
                { text: this.$t("Product"), value: "name", width: 280 },
                { text: this.$t("Units"), value: "units", sortable: true },
                { text: this.$t("Orders"), value: "orders", sortable: true },
                { text: this.$t("Sales"), value: "revenue", sortable: true },
                { text: this.$t("Profit"), value: "profit", sortable: true },
                { text: this.$t("Margin"), value: "margin", sortable: true },
            ];
        },
        viewHeaders() {
            return [
                { text: this.$t("Product"), value: "name", width: 200 },
                { text: this.$t("Visitors"), value: "visitors" },
                { text: this.$t("Orders"), value: "orders" },
                { text: this.$t("Ordered"), value: "conversion" },
            ];
        },
        brandOpts() {
            this.$store.getters["prefs/isDark"];
            return rankChart({ names: this.d.by_brand.map((r) => this.$t(r.name)), format: fmt.money });
        },
    },
    watch: { days: { immediate: true, handler: "load" } },
    methods: {
        exportSpec() {
            const d = this.d;
            return {
                title: "Product performance",
                filename: "product-performance",
                subtitle: periodLabel(d.period),
                kpis: [
                    { label: "In stock but not sold", value: d.unsold.count, type: "number" },
                    { label: "Value of unsold stock", value: d.unsold.value, type: "money" },
                ],
                tables: [
                    {
                        title: "Best sellers",
                        columns: [
                            { text: "Product", value: "name" },
                            { text: "Units", value: "units", type: "number" },
                            { text: "Orders", value: "orders", type: "number" },
                            { text: "Sales", value: "revenue", type: "money" },
                            { text: "Profit", value: "profit", type: "money" },
                            { text: "Margin", value: "margin", type: "percent" },
                        ],
                        rows: d.top,
                    },
                    {
                        title: "Most viewed",
                        columns: [
                            { text: "Product", value: "name" },
                            { text: "Views", value: "views", type: "number" },
                            { text: "Visitors", value: "visitors", type: "number" },
                            { text: "Orders", value: "orders", type: "number" },
                            { text: "Ordered", value: "conversion", type: "percent" },
                        ],
                        rows: d.viewed,
                    },
                    { title: "Sales by brand", columns: [{ text: "Brand", value: "name" }, { text: "Sales", value: "revenue", type: "money" }, { text: "Units", value: "units", type: "number" }], rows: d.by_brand },
                    { title: "In stock but not sold", columns: [{ text: "Product", value: "name" }, { text: "In stock", value: "stock_qty", type: "number" }, { text: "Price", value: "price", type: "money" }], rows: d.unsold.items },
                    { title: "Low ratings", columns: [{ text: "Product", value: "name" }, { text: "Rating", value: "rating_avg", type: "number" }, { text: "Reviews", value: "review_count", type: "number" }], rows: d.low_rated },
                ],
            };
        },
        async load() {
            this.$router.replace({ query: this.days !== 30 ? { days: this.days } : {} });
            this.d = null;
            this.d = (await api.get("/admin/analytics/products", { params: { days: this.days } })).data;
        },
    },
};
</script>
