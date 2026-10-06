<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Customer report</h1>
                    <div class="tt-hero-sub">Growth · Segments · Retention · Lifetime value</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-select
                        v-model="segment"
                        :items="segmentFilterOptions"
                        label="Segment"
                        density="comfortable"
                        variant="outlined"
                        hide-details
                        class="tt-select"
                        style="min-width: 160px"
                    />
                    <v-btn-toggle v-model="range" mandatory density="comfortable" variant="outlined" divided class="tt-range">
                        <v-btn value="7" class="text-none">7 days</v-btn>
                        <v-btn value="30" class="text-none">30 days</v-btn>
                        <v-btn value="12" class="text-none">12 months</v-btn>
                    </v-btn-toggle>
                    <v-btn color="white" variant="flat" class="text-none tt-add" prepend-icon="fa fa-download" @click="exportReport">Export</v-btn>
                </div>
            </div>

            <div class="tt-hero-strip">
                <div v-for="h in heroStats" :key="h.label" class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                    <div class="flex-grow-1">
                        <div class="tt-hero-value">{{ h.value }}</div>
                        <div class="tt-hero-label">{{ h.label }}</div>
                        <div class="tt-hero-note">{{ h.note }}</div>
                    </div>
                    <span class="tt-live" :class="{ warn: h.warn }">{{ h.tag }}</span>
                </div>
            </div>
            <div class="tt-hero-line"></div>
        </header>

        <!-- ═════════ Key metrics ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-chart-box-outline</v-icon><span>Key metrics</span></div>
        <v-row dense>
            <v-col v-for="k in kpis" :key="k.label" cols="12" sm="6" md="4">
                <v-card flat class="tt-card tt-kpi">
                    <div class="d-flex align-center">
                        <div class="tt-kpi-icon" :style="{ background: k.tint }">
                            <v-icon :icon="k.icon" size="20" :color="k.color" />
                        </div>
                        <div class="tt-kpi-label ml-3">{{ k.label }}</div>
                    </div>
                    <div class="tt-kpi-box mt-3">
                        <div class="d-flex align-center justify-space-between">
                            <div class="tt-kpi-value" :style="{ color: k.color }">{{ k.value }}</div>
                            <apexchart type="area" width="110" height="42" :options="sparkOptions(k.color)" :series="[{ data: k.spark }]" />
                        </div>
                    </div>
                    <span class="tt-delta mt-3" :class="(k.change >= 0) !== !!k.inverse ? 'up' : 'down'">
                        <v-icon size="13">{{ k.change >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>
                        {{ Math.abs(k.change) }}% vs last period
                    </span>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Growth ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-account-multiple-plus-outline</v-icon><span>Customer growth</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">New vs. returning customers</span><v-spacer /><span class="tt-tag">Live</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">{{ totalCustomers }} total customers this period</div>
                        <apexchart type="area" height="320" :options="growthOptions" :series="growthSeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Segments by value</span></div>
                    <div class="tt-body">
                        <apexchart type="donut" height="330" :options="segmentOptions" :series="segmentData.map((s) => s.value)" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Region + channel ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-map-marker-outline</v-icon><span>Region &amp; acquisition</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Customers by region</span></div>
                    <div class="tt-body"><apexchart type="bar" height="300" :options="regionOptions" :series="[{ name: 'Customers', data: regionData.map((r) => r.value) }]" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Acquisition channel</span></div>
                    <div class="tt-body"><apexchart type="pie" height="300" :options="channelOptions" :series="channelData.map((c) => c.value)" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Retention ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-repeat</v-icon><span>Retention</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-green">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Repeat purchase rate</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Share of customers who placed a second order within 90 days</div>
                        <apexchart type="line" height="280" :options="retentionOptions" :series="retentionSeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ All customers (full width) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>All customers</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-blue">
                    <div class="tt-head tt-head-wrap">
                        <span class="tt-dot"></span><span class="tt-card-title">{{ filteredCustomers.length }} customers</span>
                        <v-spacer />
                        <v-text-field
                            v-model="search"
                            density="compact"
                            variant="outlined"
                            hide-details
                            placeholder="Search customer"
                            prepend-inner-icon="fa fa-search"
                            style="max-width: 250px"
                        />
                    </div>
                    <EasyDataTable
                        :headers="customerHeaders"
                        :items="filteredCustomers"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="8"
                        :search-value="search"
                        empty-message="No customers match your filters."
                    >
                        <template #item-name="c"><span class="font-weight-medium">{{ c.name }}</span></template>
                        <template #item-spent="c"><span class="font-weight-medium">{{ money(c.spent) }}</span></template>
                        <template #item-segment="c">
                            <v-chip :color="segmentColor[c.segment]" size="small" variant="tonal" label>{{ c.segment }}</v-chip>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Top + at-risk customers (half width each) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-trophy-outline</v-icon><span>Top &amp; at-risk customers</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Top customers by spend</span>
                    </div>
                    <EasyDataTable
                        :headers="topCustomerHeaders"
                        :items="topCustomers"
                        table-class-name="customize-table tt-table-green"
                        buttons-pagination
                        :rows-per-page="5"
                    >
                        <template #item-name="c"><span class="font-weight-medium">{{ c.name }}</span></template>
                        <template #item-spent="c"><span class="font-weight-medium">{{ money(c.spent) }}</span></template>
                    </EasyDataTable>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">At-risk (no order in 45+ days)</span>
                        <v-spacer />
                        <span class="tt-tag warn">{{ atRiskCustomers.length }}</span>
                    </div>
                    <EasyDataTable
                        :headers="atRiskHeaders"
                        :items="atRiskCustomers"
                        table-class-name="customize-table tt-table-amber"
                        buttons-pagination
                        :rows-per-page="5"
                    >
                        <template #item-name="c"><span class="font-weight-medium">{{ c.name }}</span></template>
                        <template #item-spent="c">{{ money(c.spent) }}</template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";

// ── Palette (same as the rest of the admin) ──
const BLUE = "#2f5be7";
const GREEN = "#0f9d6b";
const PURPLE = "#7c3aed";
const TEAL = "#0f766e";
const AMBER = "#f59e0b";
const PINK = "#ec4899";
const INK = "#0f2a26";
const RED = "#dc2626";
const COLORS = [BLUE, GREEN, PURPLE, TEAL, AMBER, PINK];
const FONT = "Poppins, Segoe UI, sans-serif";

const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");

const seeded = (seed) => {
    let s = seed;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
};
const makeSeries = (n, base, spread, seed) => {
    const r = seeded(seed);
    return Array.from({ length: n }, (_, i) => Math.round(base * (1 + i * 0.02) + (r() - 0.5) * spread));
};

export default {
    name: "CustomerReport",
    components: { apexchart: VueApexCharts },

    data() {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const days30 = Array.from({ length: 30 }, (_, i) => `${i + 1} Sep`);
        const days7 = ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"];

        return {
            range: "12",
            segment: "All segments",
            search: "",

            segmentFilterOptions: ["All segments", "VIP", "Regular", "New", "At-risk"],
            segmentColor: { VIP: "success", Regular: "info", New: "warning", "At-risk": "error" },

            growthDatasets: {
                12: { labels: months, newC: [42, 51, 63, 58, 72, 80, 94, 101, 96, 118, 130, 141], returning: [30, 34, 41, 47, 55, 61, 70, 78, 86, 95, 108, 119] },
                30: { labels: days30, newC: makeSeries(30, 6, 3, 5), returning: makeSeries(30, 9, 4, 15) },
                7: { labels: days7, newC: makeSeries(7, 8, 3, 3), returning: makeSeries(7, 12, 4, 9) },
            },

            kpis: [
                { label: "Total customers", value: "1,208", change: 4.6, icon: "mdi-account-group-outline", color: BLUE, tint: "#e6edff", spark: [44, 50, 47, 52, 49, 46, 48, 45, 44] },
                { label: "New customers", value: "141", change: 8.5, icon: "mdi-account-plus-outline", color: GREEN, tint: "#dff5ec", spark: [30, 34, 41, 47, 55, 61, 70, 78, 86] },
                { label: "Returning customers", value: "63%", change: 2.1, icon: "mdi-account-sync-outline", color: TEAL, tint: "#dcf3f0", spark: [56, 57, 58, 59, 60, 61, 61, 62, 63] },
                { label: "Average lifetime value", value: "৳ 18,400", change: 3.9, icon: "mdi-cash-multiple", color: "#d97706", tint: "#fff1d1", spark: [15, 15.6, 16, 16.5, 17, 17.3, 17.8, 18.1, 18.4].map((n) => n * 1000) },
                { label: "Churn rate", value: "4.2%", change: -0.6, inverse: true, icon: "mdi-account-remove-outline", color: RED, tint: "#fde8e8", spark: [5.6, 5.4, 5.1, 5.0, 4.8, 4.6, 4.5, 4.3, 4.2] },
                { label: "Avg. orders / customer", value: "2.8", change: 1.4, icon: "mdi-repeat", color: PURPLE, tint: "#efe8ff", spark: [2.2, 2.3, 2.4, 2.4, 2.5, 2.6, 2.6, 2.7, 2.8].map((n) => n * 10) },
            ],

            segmentData: [
                { name: "VIP", value: 4820000 },
                { name: "Regular", value: 3160000 },
                { name: "New", value: 940000 },
                { name: "At-risk", value: 560000 },
            ],

            regionData: [
                { name: "Dhaka", value: 620 },
                { name: "Chattogram", value: 240 },
                { name: "Sylhet", value: 110 },
                { name: "Khulna", value: 84 },
                { name: "Rajshahi", value: 62 },
            ],

            channelData: [
                { name: "Facebook", value: 34 },
                { name: "Google search", value: 28 },
                { name: "Direct", value: 16 },
                { name: "Instagram", value: 10 },
                { name: "Referral", value: 7 },
                { name: "WhatsApp", value: 5 },
            ],

            retentionMonths: months,
            retentionRate: [38, 40, 41, 43, 44, 46, 48, 49, 51, 52, 53, 55],

            // ── Sample data: replace with API calls (e.g. GET /api/admin/reports/customers) ──
            customers: [
                { name: "Dhaka Mobile Hub", segment: "VIP", orders: 42, spent: 712000, lastOrder: "18 Sep 2026", region: "Dhaka" },
                { name: "Gadget Point", segment: "VIP", orders: 31, spent: 486500, lastOrder: "17 Sep 2026", region: "Chattogram" },
                { name: "Rahim Traders", segment: "Regular", orders: 19, spent: 268400, lastOrder: "19 Sep 2026", region: "Dhaka" },
                { name: "Karim Electronics", segment: "Regular", orders: 14, spent: 198200, lastOrder: "16 Sep 2026", region: "Rajshahi" },
                { name: "Sabbir Hossain", segment: "Regular", orders: 8, spent: 96200, lastOrder: "19 Sep 2026", region: "Chattogram" },
                { name: "Nusrat Jahan", segment: "New", orders: 3, spent: 22100, lastOrder: "18 Sep 2026", region: "Dhaka" },
                { name: "Farhan Kabir", segment: "New", orders: 2, spent: 15600, lastOrder: "17 Sep 2026", region: "Sylhet" },
                { name: "Sultana Begum", segment: "At-risk", orders: 11, spent: 142800, lastOrder: "28 Jul 2026", region: "Khulna" },
                { name: "Habibur Rahman", segment: "At-risk", orders: 6, spent: 58400, lastOrder: "12 Jul 2026", region: "Rajshahi" },
                { name: "Anika Ferdous", segment: "At-risk", orders: 4, spent: 31200, lastOrder: "3 Aug 2026", region: "Sylhet" },
            ],
        };
    },

    computed: {
        current() {
            return this.growthDatasets[this.range];
        },

        totalCustomers() {
            return this.current.newC.reduce((a, b) => a + b, 0) + this.current.returning.reduce((a, b) => a + b, 0);
        },

        heroStats() {
            const rangeText = { 7: "Last 7 days", 30: "Last 30 days", 12: "Last 12 months" }[this.range];
            const atRisk = this.customers.filter((c) => c.segment === "At-risk").length;
            return [
                { icon: "mdi-account-group-outline", value: String(this.totalCustomers), label: "Customers", note: rangeText, tag: "Live" },
                { icon: "mdi-cash-multiple", value: this.money(this.segmentData.reduce((a, s) => a + s.value, 0)), label: "Lifetime value tracked", note: rangeText, tag: "Live" },
                { icon: "mdi-account-alert-outline", value: String(atRisk), label: "At-risk customers", note: "Needs a nudge", tag: atRisk ? "Reach out" : "All clear", warn: atRisk > 0 },
            ];
        },

        filteredCustomers() {
            return this.customers.filter((c) => this.segment === "All segments" || c.segment === this.segment);
        },

        topCustomers() {
            return [...this.customers].sort((a, b) => b.spent - a.spent).slice(0, 5);
        },
        atRiskCustomers() {
            return this.customers.filter((c) => c.segment === "At-risk");
        },

        customerHeaders() {
            return [
                { text: "Customer", value: "name", sortable: true, width: 220 },
                { text: "Segment", value: "segment", sortable: true },
                { text: "Orders", value: "orders", sortable: true },
                { text: "Total spent", value: "spent", sortable: true },
                { text: "Last order", value: "lastOrder", sortable: true },
                { text: "Region", value: "region", sortable: true },
            ];
        },
        topCustomerHeaders() {
            return [
                { text: "Customer", value: "name", sortable: true },
                { text: "Orders", value: "orders", sortable: true },
                { text: "Total spent", value: "spent", sortable: true },
            ];
        },
        atRiskHeaders() {
            return [
                { text: "Customer", value: "name", sortable: true },
                { text: "Last order", value: "lastOrder", sortable: true },
                { text: "Total spent", value: "spent", sortable: true },
            ];
        },

        /* ── New vs. returning ── */
        growthSeries() {
            return [
                { name: "New customers", data: this.current.newC },
                { name: "Returning customers", data: this.current.returning },
            ];
        },
        growthOptions() {
            return {
                chart: { stacked: true, toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [PURPLE, "#a78bfa"],
                stroke: { curve: "smooth", width: 2 },
                fill: { type: "gradient", gradient: { opacityFrom: 0.5, opacityTo: 0.05 } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.current.labels, tickAmount: this.range === "30" ? 10 : undefined, axisBorder: { show: false } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
            };
        },

        /* ── Segment donut ── */
        segmentOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.segmentData.map((s) => s.name),
                colors: [GREEN, BLUE, AMBER, RED],
                legend: { position: "bottom", fontSize: "12px" },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ["#fff"] },
                plotOptions: {
                    pie: {
                        donut: {
                            size: "68%",
                            labels: {
                                show: true,
                                total: { show: true, label: "Total value", formatter: (w) => fullMoney(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
                                value: { formatter: fullMoney },
                            },
                        },
                    },
                },
            };
        },

        /* ── Region bars ── */
        regionOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: "60%", distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: true, style: { fontSize: "12px" } },
                xaxis: { categories: this.regionData.map((r) => r.name) },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                colors: [TEAL, "#22b8a0", "#5fcdb9", "#9bdfd2", "#c9efe8"],
                tooltip: { y: { formatter: (v) => v + " customers" } },
            };
        },

        /* ── Channel pie ── */
        channelOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.channelData.map((c) => c.name),
                colors: COLORS,
                legend: { position: "bottom", fontSize: "12px" },
                stroke: { width: 2, colors: ["#fff"] },
                dataLabels: { formatter: (v) => Math.round(v) + "%" },
            };
        },

        /* ── Retention line ── */
        retentionSeries() {
            return [{ name: "Repeat purchase rate", data: this.retentionRate }];
        },
        retentionOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                colors: [GREEN],
                stroke: { width: 3, curve: "smooth" },
                dataLabels: { enabled: false },
                markers: { size: 4 },
                xaxis: { categories: this.retentionMonths, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: (v) => v + "%" }, min: 0, max: 70 },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                tooltip: { y: { formatter: (v) => v + "%" } },
            };
        },
    },

    methods: {
        money: fullMoney,

        sparkOptions(color) {
            return {
                chart: { sparkline: { enabled: true } },
                stroke: { curve: "smooth", width: 2 },
                colors: [color],
                fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                tooltip: { enabled: false },
            };
        },

        exportReport() {
            // Hook this up to your real export endpoint, e.g. GET /api/admin/reports/customers/export
            // eslint-disable-next-line no-console
            console.log("Export customer report", { range: this.range, segment: this.segment });
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-dash {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    background: var(--bg);
    padding-bottom: 32px;
}

/* ───────── Hero ───────── */
.tt-hero {
    position: relative;
    background: linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b5f50 100%);
    color: #fff;
    border-radius: 14px;
    padding: 24px 24px 22px;
    overflow: hidden;
}
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.2; letter-spacing: -0.3px; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; }
.tt-add { color: #0a5548 !important; font-weight: 600; }
.tt-range { border-color: rgba(255, 255, 255, 0.35) !important; }
.tt-range .v-btn { height: 36px !important; color: #fff !important; font-size: 13px; }
.tt-range .v-btn--active { background: rgba(255, 255, 255, 0.18) !important; }

.tt-select :deep(.v-field) { background: rgba(255, 255, 255, 0.08); border-radius: 8px; }
.tt-select :deep(.v-field__outline) { color: rgba(255, 255, 255, 0.35) !important; }
.tt-select :deep(input),
.tt-select :deep(.v-select__selection-text),
.tt-select :deep(.v-label) { color: #fff !important; }
.tt-select :deep(.v-icon) { color: #fff !important; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 22px; }
.tt-hero-item { display: flex; align-items: flex-start; gap: 12px; padding: 4px 22px; border-left: 1px solid rgba(255, 255, 255, 0.14); }
.tt-hero-item:first-child { border-left: 0; padding-left: 0; }
.tt-hero-icon { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.2); flex: none; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.2; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.85); }
.tt-hero-note { font-size: 11px; color: rgba(255, 255, 255, 0.55); }
.tt-live {
    font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 12px;
    color: #6ee7b7; background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(110, 231, 183, 0.5);
}
.tt-live.warn { color: #fca5a5; background: rgba(220, 38, 38, 0.14); border-color: rgba(252, 165, 165, 0.5); }
.tt-hero-line { position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: linear-gradient(90deg, #ec4899, rgba(236, 72, 153, 0) 70%); }

@media (max-width: 959px) {
    .tt-hero-strip { grid-template-columns: 1fr; gap: 14px; }
    .tt-hero-item { border-left: 0; padding-left: 0; }
}

/* ───────── Section headings ───────── */
.tt-section {
    display: flex; align-items: center; gap: 8px;
    margin: 26px 0 12px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase;
    color: #3d5450;
}

/* ───────── Cards ───────── */
.tt-card {
    --accent: #2f5be7;
    --tint: #eaf0ff;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px !important;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04);
    overflow: hidden;
}
.tt-blue   { --accent: #2f5be7; --tint: #e9efff; }
.tt-green  { --accent: #0f9d6b; --tint: #e3f6ee; }
.tt-purple { --accent: #7c3aed; --tint: #f0e9ff; }
.tt-teal   { --accent: #0f766e; --tint: #ddf3f0; }
.tt-amber  { --accent: #d97706; --tint: #fff2d6; }

.tt-head {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 16px;
    background: linear-gradient(90deg, var(--tint), #fff);
    border-bottom: 1px solid var(--line);
    border-left: 3px solid var(--accent);
}
.tt-head-wrap { flex-wrap: wrap; row-gap: 8px; }
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-tag {
    font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px;
    color: var(--accent); background: #fff; border: 1px solid var(--accent);
}
.tt-tag.warn { color: #8a5a00; background: #fdf1cf; border-color: #f3d78a; }
.tt-body { padding: 14px 18px 16px; }
.tt-sub { font-size: 12.5px; color: var(--muted); }
.tt-muted { color: var(--muted); }

/* ───────── KPI cards ───────── */
.tt-kpi { padding: 16px; }
.tt-kpi-icon { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-kpi-label { font-size: 12.5px; font-weight: 500; color: var(--muted); }
.tt-kpi-box { border: 1px solid var(--line); border-radius: 8px; padding: 8px 12px; background: #fff; }
.tt-kpi-value { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
.tt-delta {
    font-size: 11px; font-weight: 600; padding: 2px 10px 2px 6px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 2px;
}
.tt-delta.up { background: #e0f5ea; color: #0f7a4d; border: 1px solid #b7e6cd; }
.tt-delta.down { background: #fde9e7; color: #b3261e; border: 1px solid #f5c2bd; }

/* ───────── Tables (EasyDataTable) ───────── */
:deep(.customize-table) {
    --easy-table-border: 1px solid #e3eae8;
    --easy-table-row-border: 1px solid #e3eae8;
    --easy-table-header-font-size: 11px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #3d5450;
    --easy-table-header-background-color: #e9f0ee;
    --easy-table-body-row-height: 52px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #f6faf9;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
:deep(.tt-table-green.customize-table) { --easy-table-body-row-hover-background-color: #eaf8f1; }
:deep(.tt-table-amber.customize-table) { --easy-table-body-row-hover-background-color: #fff8ec; }
</style>