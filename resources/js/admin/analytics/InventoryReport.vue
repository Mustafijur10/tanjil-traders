<template>
    <div>
        <PageHeader title="Inventory Insights" subtitle="What will run out soon, what is not selling, and where your money sits in stock.">
            <RangeTabs v-model="days" :options="[7, 30, 90, 365]" />
            <v-btn-secondary prepend-icon="mdi-warehouse" to="/admin/inventory">{{ $t("Stock overview") }}</v-btn-secondary>
        </PageHeader>

        <MetricTiles :items="tiles" />

        <div class="tt-an-grid">
            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon is-amber"><v-icon size="20">mdi-timer-sand</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Running out soon") }}</h2><p>{{ $t("Days of stock left at the last 30 days' selling speed.") }}</p></div>
                </div>
                <div class="tt-box-body">
                    <ul v-if="d?.running_out.length" class="tt-an-list">
                        <li v-for="p in d.running_out" :key="p.id">
                            <router-link :to="`/admin/products/${p.id}`" class="tt-ellipsis">{{ p.name }}</router-link>
                            <span class="tt-an-days" :class="p.days <= 7 ? 'is-red' : p.days <= 21 ? 'is-amber' : 'is-green'">
                                {{ $tn(p.days, "{n} day", "{n} days") }}
                            </span>
                            <small class="tt-muted">{{ $t("{s} left, sells {r} a day", { s: $num(p.stock), r: $num(p.per_day) }) }}</small>
                        </li>
                    </ul>
                    <p v-else class="tt-muted tt-small">{{ $t("Not enough sales in the last 30 days to estimate.") }}</p>
                </div>
            </section>

            <section class="tt-box">
                <div class="tt-box-head">
                    <span class="tt-box-icon is-red"><v-icon size="20">mdi-sleep</v-icon></span>
                    <div class="flex-grow-1"><h2>{{ $t("Not sold in 60 days") }}</h2><p>{{ $t("Consider a discount or a flash sale for these.") }}</p></div>
                    <v-btn-tertiary size="small" to="/admin/flash-sales" append-icon="mdi-arrow-right">{{ $t("Flash sale") }}</v-btn-tertiary>
                </div>
                <div class="tt-box-body">
                    <ul v-if="d?.dead.length" class="tt-an-list">
                        <li v-for="p in d.dead" :key="p.id">
                            <router-link :to="`/admin/products/${p.id}`" class="tt-ellipsis">{{ p.name }}</router-link>
                            <b class="tt-num">{{ $money(p.value) }}</b>
                            <small class="tt-muted">{{ $t("{n} in stock", { n: $num(p.stock) }) }} · {{ p.last_sale ? $t("last sold {date}", { date: $date(p.last_sale) }) : $t("never sold") }}</small>
                        </li>
                    </ul>
                    <p v-else class="tt-muted tt-small">{{ $t("Everything in stock sold in the last 60 days.") }}</p>
                </div>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-shape-outline</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Stock value by category") }}</h2><p>{{ $t("At sale price. Hover a bar for units and cost.") }}</p></div></div>
                <div class="tt-box-body"><ChartOrEmpty :rows="d?.by_category"><apexchart type="bar" :height="Math.max(160, d.by_category.length * 40 + 40)" :options="categoryOpts" :series="[{ name: $t('Sale value'), data: d.by_category.map((r) => +r.retail) }]" /></ChartOrEmpty></div>
            </section>

            <section class="tt-box tt-an-wide">
                <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-swap-vertical</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Stock in and out in this period") }}</h2></div></div>
                <div class="tt-box-body">
                    <div v-if="d?.movements.length" class="tt-move-grid">
                        <div v-for="m in d.movements" :key="m.name">
                            <small>{{ $t(reasons[m.name]?.label || m.name) }}</small>
                            <b class="tt-num" :class="m.units > 0 ? 'tt-up-text' : ''">{{ (m.units > 0 ? "+" : "") + $num(m.units) }}</b>
                            <span class="tt-muted tt-small">{{ $tn(m.moves, "{n} change", "{n} changes") }}</span>
                        </div>
                    </div>
                    <p v-else class="tt-muted tt-small">{{ $t("No stock changes in this period.") }}</p>
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
import ChartOrEmpty from "@/components/analytics/ChartOrEmpty.vue";
import { rankChart, fmt, esc } from "@/utils/charts";
import { MOVEMENT_REASON } from "@/utils/orders";

export default {
    name: "InventoryReport",
    components: { apexchart: VueApexCharts, RangeTabs, MetricTiles, ChartOrEmpty },
    data() {
        return { days: Number(this.$route.query.days) || 30, d: null, reasons: MOVEMENT_REASON };
    },
    computed: {
        tiles() {
            const t = this.d?.totals || {};
            return [
                { label: "Units in stock", value: this.$num(t.units ?? 0), icon: "mdi-package-variant-closed", tone: "green" },
                { label: "Stock value (cost)", value: this.$money(t.cost ?? 0), icon: "mdi-cash-multiple", tone: "blue" },
                { label: "Units sold", value: this.$num(t.sold_units ?? 0), icon: "mdi-cart-outline", tone: "violet", note: this.$t("In this period") },
                { label: "Low or out of stock", value: `${this.$num(t.low ?? 0)} / ${this.$num(t.out ?? 0)}`, icon: "mdi-alert-outline", tone: "amber", note: this.$t("Low / out") },
                { label: "Slow stock value", value: this.$money(t.dead_value ?? 0), icon: "mdi-sleep", tone: "red", note: this.$t("Not sold in 60 days") },
            ];
        },
        categoryOpts() {
            this.$store.getters["prefs/isDark"];
            const rows = this.d.by_category;
            const o = rankChart({ names: rows.map((r) => this.$t(r.name)), format: fmt.money });
            o.tooltip = {
                ...o.tooltip,
                custom: ({ dataPointIndex }) => {
                    const r = rows[dataPointIndex];
                    return `<div class="tt-chart-tip"><b>${esc(this.$t(r.name))}</b><span>${esc(this.$t("Sale value"))}: ${this.$money(r.retail)}</span><span>${esc(this.$t("Cost"))}: ${this.$money(r.cost)}</span><span>${esc(this.$t("Units"))}: ${this.$num(r.units)}</span></div>`;
                },
            };
            return o;
        },
    },
    watch: { days: { immediate: true, handler: "load" } },
    methods: {
        async load() {
            this.$router.replace({ query: this.days !== 30 ? { days: this.days } : {} });
            this.d = (await api.get("/admin/analytics/inventory", { params: { days: this.days } })).data;
        },
    },
};
</script>
