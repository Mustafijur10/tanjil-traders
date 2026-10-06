<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Sales report</h1>
                    <div class="tt-hero-sub">Revenue · Discounts · Refunds · Transactions</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-select
                        v-model="category"
                        :items="categoryFilterOptions"
                        label="Category"
                        density="comfortable"
                        variant="outlined"
                        hide-details
                        class="tt-select"
                        style="min-width: 170px"
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
                    <span class="tt-live" :class="{ down: h.down }">{{ h.tag }}</span>
                </div>
            </div>
            <div class="tt-hero-line"></div>
        </header>

        <!-- ═════════ Key metrics ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-chart-box-outline</v-icon><span>Summary vs previous period</span></div>
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

        <!-- ═════════ Revenue trend ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-cash-multiple</v-icon><span>Revenue trend</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Gross revenue vs. previous period</span><v-spacer /><span class="tt-tag">Live</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Total {{ money(totalRevenue) }} this period, {{ money(previousTotalRevenue) }} last period</div>
                        <apexchart type="line" height="320" :options="revenueOptions" :series="revenueSeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Sales by category</span></div>
                    <div class="tt-body">
                        <apexchart type="donut" height="330" :options="categoryOptions" :series="categoryData.map((c) => c.value)" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Discounts + payment ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-ticket-percent-outline</v-icon><span>Discounts &amp; payment mix</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Discount given vs. net revenue</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">How much of gross revenue is discounted away</div>
                        <apexchart type="bar" height="300" :options="discountOptions" :series="discountSeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Payment methods</span></div>
                    <div class="tt-body"><apexchart type="pie" height="300" :options="paymentOptions" :series="paymentData.map((p) => p.value)" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Brands + region ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-map-marker-outline</v-icon><span>Brands &amp; regions</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Revenue by brand</span></div>
                    <div class="tt-body"><apexchart type="bar" height="300" :options="brandOptions" :series="[{ name: 'Revenue', data: brandData.map((b) => b.value) }]" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Revenue by region</span></div>
                    <div class="tt-body"><apexchart type="bar" height="300" :options="regionOptions" :series="[{ name: 'Revenue', data: regionData.map((r) => r.value) }]" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Refunds ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-backup-restore</v-icon><span>Refunds &amp; cancellations</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-purple">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Refund amount vs. refund rate</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Bars show refunded amount, line shows refund rate as a share of revenue</div>
                        <apexchart type="line" height="280" :options="refundOptions" :series="refundSeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Transactions table (full width) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>Transactions</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-blue">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">All transactions</span>
                        <v-spacer />
                        <v-text-field
                            v-model="search"
                            density="compact"
                            variant="outlined"
                            hide-details
                            placeholder="Search order or customer"
                            prepend-inner-icon="fa fa-search"
                            style="max-width: 260px"
                            class="mr-2"
                        />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/orders">View all orders</v-btn>
                    </div>
                    <EasyDataTable
                        :headers="transactionHeaders"
                        :items="filteredTransactions"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="8"
                        :search-value="search"
                        empty-message="No transactions match your search."
                    >
                        <template #item-id="t">
                            <span class="font-weight-medium">{{ t.id }}</span>
                        </template>
                        <template #item-discount="t">
                            {{ t.discount ? money(t.discount) : "—" }}
                        </template>
                        <template #item-total="t">
                            <span class="font-weight-medium">{{ money(t.total) }}</span>
                        </template>
                        <template #item-status="t">
                            <v-chip :color="statusColor[t.status]" size="small" variant="tonal" label>{{ t.status }}</v-chip>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Top products + discount codes (half width each) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-trophy-outline</v-icon><span>Top products &amp; discount codes</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Top selling products</span>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/catalog">View catalog</v-btn>
                    </div>
                    <EasyDataTable
                        :headers="topProductHeaders"
                        :items="topProducts"
                        table-class-name="customize-table tt-table-green"
                        buttons-pagination
                        :rows-per-page="5"
                    >
                        <template #item-name="p">
                            <span class="font-weight-medium">{{ p.name }}</span>
                        </template>
                        <template #item-revenue="p">
                            <span class="font-weight-medium">{{ money(p.revenue) }}</span>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Discount code performance</span>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/coupons">Manage coupons</v-btn>
                    </div>
                    <EasyDataTable
                        :headers="discountHeaders"
                        :items="discountCodes"
                        table-class-name="customize-table tt-table-amber"
                        buttons-pagination
                        :rows-per-page="5"
                    >
                        <template #item-code="d">
                            <span class="font-weight-medium">{{ d.code }}</span>
                        </template>
                        <template #item-given="d">
                            {{ money(d.given) }}
                        </template>
                        <template #item-revenue="d">
                            <span class="font-weight-medium">{{ money(d.revenue) }}</span>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";

// ── Palette (same as the dashboard) ──
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

const shortMoney = (v) => (v >= 100000 ? (v / 100000).toFixed(1).replace(".0", "") + "L" : v >= 1000 ? Math.round(v / 1000) + "k" : v);
const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");

const seeded = (seed) => {
    let s = seed;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
};

const makeSeries = (n, base, spread, seed) => {
    const r = seeded(seed);
    return Array.from({ length: n }, (_, i) => Math.round(base * (1 + i * 0.015) + (r() - 0.5) * spread));
};

export default {
    name: "SalesReport",
    components: { apexchart: VueApexCharts },

    data() {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const days30 = Array.from({ length: 30 }, (_, i) => `${i + 1} Sep`);
        const days7 = ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"];

        return {
            range: "12",
            category: "All categories",
            search: "",

            categoryFilterOptions: ["All categories", "Power banks", "Earbuds", "Smart watches", "Cables", "Gaming"],

            // ── Sample data: replace with API calls (e.g. GET /api/admin/reports/sales) ──
            datasets: {
                12: {
                    labels: months,
                    revenue: makeSeries(12, 520000, 160000, 7),
                    prevRevenue: makeSeries(12, 470000, 150000, 17),
                    orders: makeSeries(12, 190, 60, 11),
                },
                30: {
                    labels: days30,
                    revenue: makeSeries(30, 28000, 12000, 21),
                    prevRevenue: makeSeries(30, 25500, 11000, 31),
                    orders: makeSeries(30, 11, 6, 5),
                },
                7: {
                    labels: days7,
                    revenue: makeSeries(7, 31000, 14000, 3),
                    prevRevenue: makeSeries(7, 28500, 13000, 13),
                    orders: makeSeries(7, 12, 6, 9),
                },
            },

            kpis: [
                { label: "Gross revenue", value: "৳ 9,60,000", change: 6.1, icon: "mdi-cash-multiple", color: GREEN, tint: "#dff5ec", spark: [31, 40, 35, 50, 49, 60, 70, 91, 85] },
                { label: "Net revenue", value: "৳ 8,84,600", change: 5.4, icon: "mdi-cash-check", color: TEAL, tint: "#dcf3f0", spark: [28, 36, 33, 46, 45, 55, 64, 84, 78] },
                { label: "Orders", value: "318", change: 12.4, icon: "mdi-receipt-text-outline", color: BLUE, tint: "#e6edff", spark: [10, 22, 18, 30, 26, 41, 38, 52, 60] },
                { label: "Average order value", value: "৳ 3,020", change: 3.4, icon: "mdi-basket-outline", color: "#d97706", tint: "#fff1d1", spark: [28, 30, 29, 32, 31, 33, 34, 33, 36] },
                { label: "Discount given", value: "৳ 41,200", change: 9.8, inverse: true, icon: "mdi-ticket-percent-outline", color: AMBER, tint: "#fff2d6", spark: [18, 20, 19, 24, 26, 30, 33, 38, 41] },
                { label: "Refunded amount", value: "৳ 34,200", change: -2.1, inverse: true, icon: "mdi-backup-restore", color: RED, tint: "#fde8e8", spark: [3.2, 3.0, 2.8, 2.9, 2.6, 2.4, 2.3, 2.2, 2.1].map((n) => n * 1000) },
            ],

            categoryData: [
                { name: "Power banks and chargers", value: 2840000 },
                { name: "Earbuds and headphones", value: 1960000 },
                { name: "Smart watches", value: 1410000 },
                { name: "Cables and adapters", value: 980000 },
                { name: "Gaming gear", value: 720000 },
                { name: "Others", value: 420000 },
            ],

            paymentData: [
                { name: "bKash", value: 44 },
                { name: "Cash on delivery", value: 31 },
                { name: "Nagad", value: 14 },
                { name: "Card", value: 11 },
            ],

            brandData: [
                { name: "Anker", value: 1250000 },
                { name: "Baseus", value: 980000 },
                { name: "Xiaomi", value: 870000 },
                { name: "JBL", value: 640000 },
                { name: "Ugreen", value: 510000 },
                { name: "Realme", value: 430000 },
            ],

            regionData: [
                { name: "Dhaka", value: 3120000 },
                { name: "Chattogram", value: 1180000 },
                { name: "Sylhet", value: 540000 },
                { name: "Khulna", value: 410000 },
                { name: "Rajshahi", value: 360000 },
            ],

            // discount vs net revenue, by month
            discountMonths: months,
            discountGiven: makeSeries(12, 32000, 9000, 51),
            netRevenue: makeSeries(12, 490000, 150000, 61),

            // refund amount + refund rate, by month
            refundMonths: months,
            refundAmount: makeSeries(12, 26000, 8000, 71),
            refundRate: [3.1, 2.9, 3.4, 2.8, 3.6, 3.2, 2.6, 3.0, 3.3, 2.7, 3.1, 2.9],

            transactions: [
                { id: "#TT-10482", customer: "Rahim Traders", date: "19 Sep 2026", category: "Power banks", payment: "bKash", discount: 500, total: 48500, status: "Pending" },
                { id: "#TT-10481", customer: "Sabbir Hossain", date: "19 Sep 2026", category: "Earbuds", payment: "Cash on delivery", discount: 0, total: 12900, status: "Processing" },
                { id: "#TT-10480", customer: "Dhaka Mobile Hub", date: "18 Sep 2026", category: "Smart watches", payment: "Card", discount: 4200, total: 186000, status: "Delivered" },
                { id: "#TT-10479", customer: "Nusrat Jahan", date: "18 Sep 2026", category: "Cables", payment: "Nagad", discount: 0, total: 7400, status: "Delivered" },
                { id: "#TT-10478", customer: "Gadget Point", date: "17 Sep 2026", category: "Gaming", payment: "bKash", discount: 900, total: 23100, status: "Cancelled" },
                { id: "#TT-10477", customer: "Farhan Kabir", date: "17 Sep 2026", category: "Power banks", payment: "Cash on delivery", discount: 300, total: 31200, status: "Delivered" },
                { id: "#TT-10476", customer: "Ayesha Rahman", date: "16 Sep 2026", category: "Earbuds", payment: "bKash", discount: 0, total: 9800, status: "Delivered" },
                { id: "#TT-10475", customer: "Karim Electronics", date: "16 Sep 2026", category: "Smart watches", payment: "Card", discount: 2100, total: 142500, status: "Processing" },
            ],
            statusColor: { Pending: "warning", Processing: "info", Delivered: "success", Cancelled: "error" },

            topProducts: [
                { name: "Anker PowerCore 20000", category: "Power banks", units: 412, revenue: 352800 },
                { name: "Baseus Bowie E9 Earbuds", category: "Earbuds", units: 388, revenue: 312900 },
                { name: "Xiaomi Smart Band 8", category: "Smart watches", units: 315, revenue: 236250 },
                { name: "JBL Go 4 Speaker", category: "Speakers", units: 274, revenue: 219450 },
                { name: "Ugreen 65W Charger", category: "Cables", units: 268, revenue: 201000 },
            ],

            discountCodes: [
                { code: "EID26", uses: 210, given: 18400, revenue: 412000 },
                { code: "FREESHIP", uses: 168, given: 6200, revenue: 268000 },
                { code: "WELCOME10", uses: 142, given: 9800, revenue: 184500 },
                { code: "FLASH50", uses: 54, given: 6800, revenue: 71200 },
            ],

            // ── EasyDataTable header definitions ──
            transactionHeaders: [
                { text: "Order", value: "id", sortable: true },
                { text: "Customer", value: "customer", sortable: true },
                { text: "Date", value: "date", sortable: true },
                { text: "Category", value: "category", sortable: true },
                { text: "Payment", value: "payment", sortable: true },
                { text: "Discount", value: "discount", sortable: true },
                { text: "Total", value: "total", sortable: true },
                { text: "Status", value: "status", sortable: true },
            ],
            topProductHeaders: [
                { text: "Product", value: "name", sortable: true, width: 260 },
                { text: "Category", value: "category", sortable: true },
                { text: "Units sold", value: "units", sortable: true },
                { text: "Revenue", value: "revenue", sortable: true },
            ],
            discountHeaders: [
                { text: "Code", value: "code", sortable: true },
                { text: "Uses", value: "uses", sortable: true },
                { text: "Discount given", value: "given", sortable: true },
                { text: "Revenue driven", value: "revenue", sortable: true },
            ],
        };
    },

    computed: {
        current() {
            return this.datasets[this.range];
        },

        totalRevenue() {
            return this.current.revenue.reduce((a, b) => a + b, 0);
        },
        previousTotalRevenue() {
            return this.current.prevRevenue.reduce((a, b) => a + b, 0);
        },
        revenueChangePct() {
            return Math.round(((this.totalRevenue - this.previousTotalRevenue) / this.previousTotalRevenue) * 1000) / 10;
        },

        totalDiscount() {
            return this.discountGiven.reduce((a, b) => a + b, 0);
        },
        totalRefund() {
            return this.refundAmount.reduce((a, b) => a + b, 0);
        },

        filteredTransactions() {
            // Text search is handled by EasyDataTable's own search-value prop below;
            // this only narrows by the category filter in the header.
            const cat = this.category;
            return this.transactions.filter((t) => cat === "All categories" || t.category === cat);
        },

        /* ── Hero strip ── */
        heroStats() {
            const rangeText = { 7: "Last 7 days", 30: "Last 30 days", 12: "Last 12 months" }[this.range];
            const up = this.revenueChangePct >= 0;
            return [
                { icon: "mdi-cash-multiple", value: this.money(this.totalRevenue), label: "Gross revenue", note: rangeText, tag: (up ? "+" : "") + this.revenueChangePct + "%", down: !up },
                { icon: "mdi-ticket-percent-outline", value: this.money(this.totalDiscount), label: "Discount given", note: rangeText, tag: "This period" },
                { icon: "mdi-backup-restore", value: this.money(this.totalRefund), label: "Refunded amount", note: rangeText, tag: "Check" },
            ];
        },

        /* ── Revenue line: this period vs previous period ── */
        revenueSeries() {
            return [
                { name: "This period", type: "area", data: this.current.revenue },
                { name: "Previous period", type: "line", data: this.current.prevRevenue },
            ];
        },
        revenueOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [GREEN, "#9fb6f0"],
                stroke: { width: [3, 2], curve: "smooth", dashArray: [0, 5] },
                fill: { type: ["gradient", "solid"], gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 95, 100] } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.current.labels, tickAmount: this.range === "30" ? 10 : undefined, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { title: { text: "Revenue" }, labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { shared: true, y: { formatter: fullMoney } },
            };
        },

        /* ── Donut: category ── */
        categoryOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.categoryData.map((c) => c.name),
                colors: COLORS,
                legend: { position: "bottom", fontSize: "12px" },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ["#fff"] },
                plotOptions: {
                    pie: {
                        donut: {
                            size: "68%",
                            labels: {
                                show: true,
                                total: { show: true, label: "Total sales", formatter: (w) => fullMoney(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
                                value: { formatter: fullMoney },
                            },
                        },
                    },
                },
            };
        },

        /* ── Discount given vs net revenue ── */
        discountSeries() {
            return [
                { name: "Net revenue", data: this.netRevenue },
                { name: "Discount given", data: this.discountGiven },
            ];
        },
        discountOptions() {
            return {
                chart: { stacked: true, toolbar: { show: false }, fontFamily: FONT },
                colors: [GREEN, AMBER],
                plotOptions: { bar: { borderRadius: 4, columnWidth: "55%" } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.discountMonths, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        /* ── Payment pie ── */
        paymentOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.paymentData.map((p) => p.name),
                colors: [PINK, INK, PURPLE, BLUE],
                legend: { position: "bottom", fontSize: "12px" },
                stroke: { width: 2, colors: ["#fff"] },
                dataLabels: { formatter: (v) => Math.round(v) + "%" },
            };
        },

        /* ── Brands horizontal bars ── */
        brandOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: "60%", distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: true, formatter: shortMoney, style: { fontSize: "12px" } },
                xaxis: { categories: this.brandData.map((b) => b.name), labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                colors: COLORS,
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        /* ── Region horizontal bars ── */
        regionOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: "60%", distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: true, formatter: shortMoney, style: { fontSize: "12px" } },
                xaxis: { categories: this.regionData.map((r) => r.name), labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                colors: [TEAL, "#22b8a0", "#5fcdb9", "#9bdfd2", "#c9efe8"],
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        /* ── Refund amount (bar) + refund rate (line) ── */
        refundSeries() {
            return [
                { name: "Refunded amount", type: "column", data: this.refundAmount },
                { name: "Refund rate (%)", type: "line", data: this.refundRate },
            ];
        },
        refundOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                colors: [RED, PURPLE],
                stroke: { width: [0, 3], curve: "smooth" },
                plotOptions: { bar: { borderRadius: 4, columnWidth: "45%" } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.refundMonths, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: [
                    { title: { text: "Refunded amount" }, labels: { formatter: shortMoney } },
                    { opposite: true, title: { text: "Refund rate" }, labels: { formatter: (v) => v + "%" } },
                ],
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { shared: true, y: [{ formatter: fullMoney }, { formatter: (v) => v + "%" }] },
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
            // Hook this up to your real export endpoint, e.g. GET /api/admin/reports/sales/export?range=...&category=...
            // eslint-disable-next-line no-console
            console.log("Export sales report", { range: this.range, category: this.category });
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

.tt-select :deep(.v-field) {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 8px;
}
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
.tt-live.down { color: #fca5a5; background: rgba(220, 38, 38, 0.14); border-color: rgba(252, 165, 165, 0.5); }
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

/* ───────── Cards with coloured header ───────── */
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
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-tag {
    font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px;
    color: var(--accent); background: #fff; border: 1px solid var(--accent);
}
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