<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Dashboard</h1>
                    <div class="tt-hero-sub">Sales · Orders · Customers · Inventory</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-btn-toggle v-model="range" mandatory density="comfortable" variant="outlined" divided class="tt-range">
                        <v-btn value="7" class="text-none">7 days</v-btn>
                        <v-btn value="30" class="text-none">30 days</v-btn>
                        <v-btn value="12" class="text-none">12 months</v-btn>
                    </v-btn-toggle>
                    <v-btn color="white" variant="flat" class="text-none tt-add" prepend-icon="mdi-plus" to="/admin/catalog">Add product</v-btn>
                    <span class="tt-date-pill">
                        <v-icon size="14">mdi-clock</v-icon>
                        As on {{ today }}
                    </span>
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
                    <span class="tt-live">{{ h.tag }}</span>
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

        <!-- ═════════ Revenue ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-cash-multiple</v-icon><span>Revenue &amp; category sales</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Revenue and orders</span><v-spacer /><span class="tt-tag">Live</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Total {{ money(totalRevenue) }} in this period</div>
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

        <!-- ═════════ Orders ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-clipboard-check-outline</v-icon><span>Orders &amp; payment status</span></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Orders by weekday</span></div>
                    <div class="tt-body"><apexchart type="bar" height="260" :options="weekdayOptions" :series="[{ name: 'Orders', data: weekdayData }]" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Order status</span></div>
                    <div class="tt-body"><apexchart type="radialBar" height="260" :options="statusOptions" :series="statusData.map((s) => s.value)" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Payment methods</span></div>
                    <div class="tt-body"><apexchart type="pie" height="260" :options="paymentOptions" :series="paymentData.map((p) => p.value)" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Brands + customers ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-account-group-outline</v-icon><span>Brands &amp; customers</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Top brands by revenue</span></div>
                    <div class="tt-body"><apexchart type="bar" height="300" :options="brandOptions" :series="[{ name: 'Revenue', data: brandData.map((b) => b.value) }]" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Customer growth</span></div>
                    <div class="tt-body"><apexchart type="area" height="300" :options="customerOptions" :series="customerSeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Heatmap ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-clock-outline</v-icon><span>Busiest hours</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-green">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Busiest hours</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Orders by day and time of day</div>
                        <apexchart type="heatmap" height="280" :options="heatOptions" :series="heatSeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Gauge, radar, polar ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-target</v-icon><span>Targets &amp; insights</span></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Monthly sales target</span></div>
                    <div class="tt-body">
                        <div class="tt-sub">{{ money(targetSold) }} of {{ money(targetGoal) }}</div>
                        <apexchart type="radialBar" height="260" :options="gaugeOptions" :series="[Math.round((targetSold / targetGoal) * 100)]" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Category performance</span></div>
                    <div class="tt-body"><apexchart type="radar" height="290" :options="radarOptions" :series="radarSeries" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Traffic sources</span></div>
                    <div class="tt-body"><apexchart type="polarArea" height="300" :options="trafficOptions" :series="trafficData.map((t) => t.value)" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Product ratings + treemap (replaces conversion funnel) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-star-outline</v-icon><span>Product ratings &amp; revenue</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Rating vs. review volume</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Each dot is a product, grouped by category</div>
                        <apexchart type="scatter" height="320" :options="scatterOptions" :series="scatterSeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Revenue by product</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Bigger block means more revenue</div>
                        <apexchart type="treemap" height="320" :options="treemapOptions" :series="treemapSeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Bubble + range bar ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-truck-delivery-outline</v-icon><span>Pricing &amp; delivery</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Price vs. units sold</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Bubble size shows revenue</div>
                        <apexchart type="bubble" height="330" :options="bubbleOptions" :series="bubbleSeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Delivery time by region</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Fastest to slowest, in days</div>
                        <apexchart type="rangeBar" height="330" :options="deliveryOptions" :series="deliverySeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Order outcome + weekly order value (replaces box plot) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-chart-bar-stacked</v-icon><span>Order outcome &amp; weekly order value</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Order outcome by city</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Share of delivered, processing and cancelled</div>
                        <apexchart type="bar" height="300" :options="cityOptions" :series="citySeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Weekly order value range</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Opening, high, low and closing average order value per week</div>
                        <apexchart type="candlestick" height="300" :options="candleOptions" :series="candleSeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Tables ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>Recent orders &amp; restocking</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Recent orders</span>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/orders">View all orders</v-btn>
                    </div>
                    <v-table class="tt-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="o in orders" :key="o.id">
                                <td class="font-weight-medium">{{ o.id }}</td>
                                <td>{{ o.customer }}</td>
                                <td class="tt-muted">{{ o.date }}</td>
                                <td>
                                    <v-chip :color="statusColor[o.status]" size="small" variant="tonal" label>{{ o.status }}</v-chip>
                                </td>
                                <td class="text-right font-weight-medium">{{ money(o.total) }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Needs restocking</span>
                        <v-spacer />
                        <span class="tt-tag warn">{{ lowStock.length }}</span>
                    </div>
                    <div class="tt-body">
                        <v-list density="comfortable" class="pa-0 bg-transparent">
                            <v-list-item v-for="p in lowStock" :key="p.sku" class="px-0 tt-li">
                                <v-list-item-title class="text-body-2 font-weight-medium">{{ p.name }}</v-list-item-title>
                                <v-list-item-subtitle>{{ p.brand }}</v-list-item-subtitle>
                                <template #append>
                                    <span class="tt-stock" :class="{ zero: p.left === 0 }">
                                        {{ p.left === 0 ? "Out" : p.left + " left" }}
                                    </span>
                                </template>
                            </v-list-item>
                        </v-list>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Top products & customers (new tables) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-trophy-outline</v-icon><span>Top products &amp; customers</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Top selling products</span>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/catalog">View catalog</v-btn>
                    </div>
                    <v-table class="tt-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th class="text-right">Units sold</th>
                                <th class="text-right">Revenue</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in topProducts" :key="p.name">
                                <td class="font-weight-medium">{{ p.name }}</td>
                                <td class="tt-muted">{{ p.category }}</td>
                                <td class="text-right">{{ p.units }}</td>
                                <td class="text-right font-weight-medium">{{ money(p.revenue) }}</td>
                                <td>
                                    <v-chip :color="stockColor[p.stock]" size="small" variant="tonal" label>{{ p.stock }}</v-chip>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Top customers</span>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/customers">View all customers</v-btn>
                    </div>
                    <v-table class="tt-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th class="text-right">Orders</th>
                                <th class="text-right">Total spent</th>
                                <th>Last order</th>
                                <th>Tier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in topCustomers" :key="c.name">
                                <td class="font-weight-medium">{{ c.name }}</td>
                                <td class="text-right">{{ c.orders }}</td>
                                <td class="text-right font-weight-medium">{{ money(c.spent) }}</td>
                                <td class="tt-muted">{{ c.lastOrder }}</td>
                                <td>
                                    <v-chip :color="tierColor[c.tier]" size="small" variant="tonal" label>{{ c.tier }}</v-chip>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";

// ── Palette (matches the new design) ──
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

// short axis labels: 250000 -> 2.5L, 45000 -> 45k
const shortMoney = (v) => (v >= 100000 ? (v / 100000).toFixed(1).replace(".0", "") + "L" : v >= 1000 ? Math.round(v / 1000) + "k" : v);
const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");

// small deterministic pseudo-random so sample data looks natural but stays stable
const seeded = (seed) => {
    let s = seed;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
};

const makeSeries = (n, base, spread, seed) => {
    const r = seeded(seed);
    return Array.from({ length: n }, (_, i) => Math.round(base * (1 + i * 0.02) + (r() - 0.5) * spread));
};

export default {
    name: "AdminDashboard",
    components: { apexchart: VueApexCharts },

    data() {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const days30 = Array.from({ length: 30 }, (_, i) => `${i + 1} Sep`);
        const days7 = ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"];

        return {
            range: "12",

            // ── Sample data: replace with API calls (e.g. GET /api/admin/dashboard) ──
            datasets: {
                12: { labels: months, revenue: makeSeries(12, 520000, 160000, 7), orders: makeSeries(12, 190, 60, 11) },
                30: { labels: days30, revenue: makeSeries(30, 28000, 12000, 21), orders: makeSeries(30, 11, 6, 5) },
                7: { labels: days7, revenue: makeSeries(7, 31000, 14000, 3), orders: makeSeries(7, 12, 6, 9) },
            },

            kpis: [
                { label: "Revenue this month", value: "৳ 9,60,000", change: 6.1, icon: "mdi-cash-multiple", color: GREEN, tint: "#dff5ec", spark: [31, 40, 35, 50, 49, 60, 70, 91, 85] },
                { label: "Orders", value: "318", change: 12.4, icon: "mdi-receipt-text-outline", color: BLUE, tint: "#e6edff", spark: [10, 22, 18, 30, 26, 41, 38, 52, 60] },
                { label: "Products in catalog", value: "2,146", change: 1.8, icon: "mdi-package-variant-closed", color: TEAL, tint: "#dcf3f0", spark: [50, 52, 54, 55, 57, 58, 60, 61, 62] },
                { label: "Customers", value: "1,208", change: -0.9, icon: "mdi-account-group-outline", color: PURPLE, tint: "#efe8ff", spark: [44, 50, 47, 52, 49, 46, 48, 45, 44] },
                { label: "Average order value", value: "৳ 3,020", change: 3.4, icon: "mdi-basket-outline", color: "#d97706", tint: "#fff1d1", spark: [28, 30, 29, 32, 31, 33, 34, 33, 36] },
                { label: "Conversion rate", value: "5.2%", change: 0.6, icon: "mdi-percent-outline", color: PINK, tint: "#fde6f1", spark: [4.1, 4.4, 4.3, 4.7, 4.6, 4.9, 5.0, 5.1, 5.2] },
                { label: "Pending orders", value: "34", change: -8.1, inverse: true, icon: "mdi-clock-outline", color: "#0284c7", tint: "#def2fc", spark: [52, 48, 50, 45, 43, 41, 39, 36, 34] },
                { label: "Return rate", value: "2.1%", change: -0.4, inverse: true, icon: "mdi-backup-restore", color: "#dc2626", tint: "#fde8e8", spark: [3.2, 3.0, 2.8, 2.9, 2.6, 2.4, 2.3, 2.2, 2.1] },
                { label: "Low-stock items", value: "18", change: 5.9, inverse: true, icon: "mdi-alert-outline", color: "#ea580c", tint: "#ffe9dc", spark: [10, 11, 12, 12, 14, 15, 16, 17, 18] },
            ],

            categoryData: [
                { name: "Power banks and chargers", value: 2840000 },
                { name: "Earbuds and headphones", value: 1960000 },
                { name: "Smart watches", value: 1410000 },
                { name: "Cables and adapters", value: 980000 },
                { name: "Gaming gear", value: 720000 },
                { name: "Others", value: 420000 },
            ],

            weekdayData: [58, 44, 39, 41, 47, 63, 71],

            statusData: [
                { name: "Delivered", value: 68 },
                { name: "Processing", value: 17 },
                { name: "Pending", value: 10 },
                { name: "Cancelled", value: 5 },
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

            customerSeries: [
                { name: "New customers", data: [42, 51, 63, 58, 72, 80, 94, 101, 96, 118, 130, 141] },
                { name: "Returning customers", data: [30, 34, 41, 47, 55, 61, 70, 78, 86, 95, 108, 119] },
            ],

            targetSold: 960000,
            targetGoal: 1200000,

            radarCategories: ["Power", "Audio", "Watches", "Cables", "Gaming", "Networking"],
            radarThis: [80, 72, 58, 62, 47, 36],
            radarLast: [66, 63, 60, 49, 41, 39],

            trafficData: [
                { name: "Facebook", value: 34 },
                { name: "Google search", value: 28 },
                { name: "Direct", value: 16 },
                { name: "Instagram", value: 10 },
                { name: "YouTube", value: 7 },
                { name: "WhatsApp", value: 5 },
            ],

            // rating (x, 1–5) vs number of reviews (y), grouped by category — new scatter chart
            ratingData: [
                { name: "Power banks", data: [[4.6, 420], [4.3, 310], [4.1, 260]] },
                { name: "Earbuds", data: [[4.7, 610], [4.2, 380], [3.9, 190]] },
                { name: "Smart watches", data: [[4.5, 340], [4.0, 210]] },
                { name: "Cables", data: [[4.4, 280], [4.1, 150]] },
                { name: "Gaming", data: [[4.6, 260], [4.2, 175]] },
            ],

            productRevenue: [
                { x: "Anker PowerCore 20000", y: 352800 },
                { x: "Baseus Bowie E9", y: 312900 },
                { x: "Xiaomi Smart Band 8", y: 236250 },
                { x: "JBL Go 4", y: 219450 },
                { x: "Ugreen 65W Charger", y: 201000 },
                { x: "Logitech G102", y: 231000 },
                { x: "Hoco cable 1m", y: 218400 },
                { x: "Realme Buds T300", y: 167400 },
                { x: "SanDisk 128GB", y: 121500 },
                { x: "Havit H2002d", y: 94500 },
            ],

            // [price, units sold, revenue]
            bubbleData: [
                { name: "Power banks", data: [[4200, 84, 352800], [2800, 120, 336000], [5900, 40, 236000]] },
                { name: "Earbuds", data: [[1490, 210, 312900], [2790, 90, 251100], [990, 300, 297000]] },
                { name: "Smart watches", data: [[3150, 75, 236250], [6900, 30, 207000]] },
                { name: "Cables", data: [[420, 520, 218400], [690, 260, 179400]] },
                { name: "Gaming", data: [[1650, 140, 231000], [3990, 55, 219450]] },
            ],

            delivery: [
                { x: "Dhaka", y: [0, 1] },
                { x: "Chattogram", y: [1, 2] },
                { x: "Sylhet", y: [1, 3] },
                { x: "Khulna", y: [2, 3] },
                { x: "Rajshahi", y: [2, 4] },
                { x: "Barishal", y: [2, 4] },
                { x: "Rangpur", y: [3, 5] },
            ],

            cityLabels: ["Dhaka", "Chattogram", "Sylhet", "Khulna", "Rajshahi"],
            cityData: [
                { name: "Delivered", data: [210, 64, 28, 22, 19] },
                { name: "Processing", data: [40, 14, 6, 5, 4] },
                { name: "Cancelled", data: [12, 5, 2, 2, 1] },
            ],

            // weekly average order value: [open, high, low, close] — new candlestick chart
            candleData: [
                { x: "Wk 1", y: [2850, 3400, 2600, 3150] },
                { x: "Wk 2", y: [3150, 3600, 2950, 3020] },
                { x: "Wk 3", y: [3020, 3300, 2780, 3210] },
                { x: "Wk 4", y: [3210, 3750, 3100, 3480] },
                { x: "Wk 5", y: [3480, 3520, 2990, 3060] },
                { x: "Wk 6", y: [3060, 3410, 2900, 3340] },
                { x: "Wk 7", y: [3340, 3690, 3200, 3610] },
                { x: "Wk 8", y: [3610, 3820, 3350, 3020] },
            ],

            orders: [
                { id: "#TT-10482", customer: "Rahim Traders", date: "19 Sep 2026", status: "Pending", total: 48500 },
                { id: "#TT-10481", customer: "Sabbir Hossain", date: "19 Sep 2026", status: "Processing", total: 12900 },
                { id: "#TT-10480", customer: "Dhaka Mobile Hub", date: "18 Sep 2026", status: "Delivered", total: 186000 },
                { id: "#TT-10479", customer: "Nusrat Jahan", date: "18 Sep 2026", status: "Delivered", total: 7400 },
                { id: "#TT-10478", customer: "Gadget Point", date: "17 Sep 2026", status: "Cancelled", total: 23100 },
            ],
            statusColor: { Pending: "warning", Processing: "info", Delivered: "success", Cancelled: "error" },

            lowStock: [
                { sku: "PB-AN-20", name: "Anker PowerCore 20000", brand: "Anker", left: 3 },
                { sku: "EB-BS-09", name: "Baseus Bowie E9 Earbuds", brand: "Baseus", left: 5 },
                { sku: "SW-XM-08", name: "Xiaomi Smart Band 8", brand: "Xiaomi", left: 2 },
                { sku: "RT-TP-C6", name: "TP-Link Archer C6 Router", brand: "TP-Link", left: 0 },
                { sku: "SP-JB-G4", name: "JBL Go 4 Speaker", brand: "JBL", left: 4 },
            ],

            // ── New: top selling products table ──
            topProducts: [
                { name: "Anker PowerCore 20000", category: "Power banks", units: 412, revenue: 352800, stock: "Low stock" },
                { name: "Baseus Bowie E9 Earbuds", category: "Earbuds", units: 388, revenue: 312900, stock: "In stock" },
                { name: "Xiaomi Smart Band 8", category: "Smart watches", units: 315, revenue: 236250, stock: "Low stock" },
                { name: "JBL Go 4 Speaker", category: "Speakers", units: 274, revenue: 219450, stock: "In stock" },
                { name: "Ugreen 65W Charger", category: "Cables", units: 268, revenue: 201000, stock: "In stock" },
                { name: "TP-Link Archer C6 Router", category: "Networking", units: 96, revenue: 86400, stock: "Out of stock" },
            ],
            stockColor: { "In stock": "success", "Low stock": "warning", "Out of stock": "error" },

            // ── New: top customers table ──
            topCustomers: [
                { name: "Dhaka Mobile Hub", orders: 42, spent: 712000, lastOrder: "18 Sep 2026", tier: "VIP" },
                { name: "Gadget Point", orders: 31, spent: 486500, lastOrder: "17 Sep 2026", tier: "VIP" },
                { name: "Rahim Traders", orders: 19, spent: 268400, lastOrder: "19 Sep 2026", tier: "Regular" },
                { name: "Sabbir Hossain", orders: 8, spent: 96200, lastOrder: "19 Sep 2026", tier: "Regular" },
                { name: "Nusrat Jahan", orders: 3, spent: 22100, lastOrder: "18 Sep 2026", tier: "New" },
            ],
            tierColor: { VIP: "success", Regular: "info", New: "warning" },
        };
    },

    computed: {
        today() {
            return new Date().toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },

        current() {
            return this.datasets[this.range];
        },

        totalRevenue() {
            return this.current.revenue.reduce((a, b) => a + b, 0);
        },

        /* ── Three headline numbers shown in the green header ── */
        heroStats() {
            const rangeText = { 7: "Last 7 days", 30: "Last 30 days", 12: "Last 12 months" }[this.range];
            return [
                { icon: "mdi-cash-multiple", value: this.money(this.totalRevenue), label: "Revenue", note: rangeText, tag: "Live" },
                { icon: "mdi-target", value: this.money(this.targetSold), label: "Monthly sales target", note: `Goal ${this.money(this.targetGoal)}`, tag: Math.round((this.targetSold / this.targetGoal) * 100) + "%" },
                { icon: "mdi-alert-outline", value: String(this.lowStock.length), label: "Needs restocking", note: "Low-stock products", tag: "Check" },
            ];
        },

        /* ── Revenue (area) + orders (line) ── */
        revenueSeries() {
            return [
                { name: "Revenue", type: "area", data: this.current.revenue },
                { name: "Orders", type: "line", data: this.current.orders },
            ];
        },
        revenueOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [GREEN, BLUE],
                stroke: { width: [3, 3], curve: "smooth" },
                fill: { type: ["gradient", "solid"], gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 95, 100] } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.current.labels, tickAmount: this.range === "30" ? 10 : undefined, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: [
                    { title: { text: "Revenue" }, labels: { formatter: shortMoney } },
                    { opposite: true, title: { text: "Orders" } },
                ],
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { shared: true, y: [{ formatter: fullMoney }, { formatter: (v) => v + " orders" }] },
            };
        },

        /* ── Donut ── */
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

        /* ── Weekday columns ── */
        weekdayOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                plotOptions: { bar: { borderRadius: 6, columnWidth: "55%", distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: false },
                xaxis: { categories: ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"], axisBorder: { show: false }, axisTicks: { show: false } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                // highlight the busiest day
                colors: this.weekdayData.map((v) => (v === Math.max(...this.weekdayData) ? BLUE : "#b7c6f5")),
            };
        },

        /* ── Order status radial ── */
        statusOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.statusData.map((s) => s.name),
                colors: [GREEN, BLUE, AMBER, "#dc2626"],
                plotOptions: {
                    radialBar: {
                        hollow: { size: "32%" },
                        track: { background: "#eaf0ee" },
                        dataLabels: {
                            name: { fontSize: "13px" },
                            value: { fontSize: "18px", formatter: (v) => v + "%" },
                            total: { show: true, label: "Delivered", formatter: () => this.statusData[0].value + "%" },
                        },
                    },
                },
                legend: { show: true, position: "bottom", fontSize: "12px" },
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

        /* ── Customers stacked area ── */
        customerOptions() {
            return {
                chart: { stacked: true, toolbar: { show: false }, fontFamily: FONT },
                colors: [PURPLE, "#a78bfa"],
                stroke: { curve: "smooth", width: 2 },
                fill: { type: "gradient", gradient: { opacityFrom: 0.5, opacityTo: 0.05 } },
                dataLabels: { enabled: false },
                xaxis: { categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"], axisBorder: { show: false } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
            };
        },

        /* ── Semi-circle gauge (target) ── */
        gaugeOptions() {
            return {
                chart: { fontFamily: FONT, offsetY: -10 },
                colors: [GREEN],
                plotOptions: {
                    radialBar: {
                        startAngle: -90,
                        endAngle: 90,
                        hollow: { size: "58%" },
                        track: { background: "#eaf0ee", strokeWidth: "100%" },
                        dataLabels: {
                            name: { show: true, offsetY: -6, fontSize: "13px", color: "#5f7470" },
                            value: { offsetY: -30, fontSize: "30px", fontWeight: 600, formatter: (v) => v + "%" },
                        },
                    },
                },
                fill: { type: "gradient", gradient: { shade: "dark", type: "horizontal", gradientToColors: ["#34d399"], stops: [0, 100] } },
                stroke: { lineCap: "round" },
                labels: ["Target reached"],
            };
        },

        /* ── Radar ── */
        radarSeries() {
            return [
                { name: "This month", data: this.radarThis },
                { name: "Last month", data: this.radarLast },
            ];
        },
        radarOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                colors: [BLUE, GREEN],
                xaxis: { categories: this.radarCategories },
                yaxis: { show: false },
                stroke: { width: 2 },
                fill: { opacity: 0.25 },
                markers: { size: 4 },
                legend: { position: "bottom" },
            };
        },

        /* ── Polar area ── */
        trafficOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.trafficData.map((t) => t.name),
                colors: COLORS,
                stroke: { colors: ["#fff"] },
                fill: { opacity: 0.85 },
                yaxis: { show: false },
                legend: { position: "bottom", fontSize: "12px" },
                plotOptions: { polarArea: { rings: { strokeColor: "#e2e9e7" }, spokes: { connectorColors: "#e2e9e7" } } },
            };
        },

        /* ── Scatter: rating vs. review volume (new) ── */
        scatterSeries() {
            return this.ratingData;
        },
        scatterOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: COLORS,
                markers: { size: 6, strokeWidth: 0 },
                xaxis: { title: { text: "Average rating" }, min: 3.5, max: 5, tickAmount: 6 },
                yaxis: { title: { text: "Number of reviews" } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { y: { formatter: (v) => v + " reviews" } },
            };
        },

        /* ── Treemap ── */
        treemapSeries() {
            return [{ data: this.productRevenue }];
        },
        treemapOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                colors: COLORS,
                plotOptions: { treemap: { distributed: true, enableShades: false, borderRadius: 4 } },
                legend: { show: false },
                dataLabels: { style: { fontSize: "12px" } },
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        /* ── Bubble ── */
        bubbleSeries() {
            return this.bubbleData;
        },
        bubbleOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: COLORS,
                dataLabels: { enabled: false },
                fill: { opacity: 0.75 },
                plotOptions: { bubble: { minBubbleRadius: 8, maxBubbleRadius: 42 } },
                xaxis: { type: "numeric", min: 0, max: 7500, tickAmount: 7, title: { text: "Price (৳)" }, labels: { formatter: (v) => Math.round(v) } },
                yaxis: { title: { text: "Units sold" } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: {
                    custom: ({ seriesIndex, dataPointIndex, w }) => {
                        const [price, units, revenue] = w.config.series[seriesIndex].data[dataPointIndex];
                        return `<div style="padding:8px 10px;font-size:12px"><b>${w.config.series[seriesIndex].name}</b><br/>Price: ${fullMoney(price)}<br/>Units: ${units}<br/>Revenue: ${fullMoney(revenue)}</div>`;
                    },
                },
            };
        },

        /* ── Range bar (delivery days) ── */
        deliverySeries() {
            return [{ name: "Delivery time", data: this.delivery }];
        },
        deliveryOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                colors: [TEAL],
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: "55%", rangeBarGroupRows: true } },
                fill: { type: "gradient", gradient: { shade: "light", type: "horizontal", gradientToColors: [BLUE], stops: [0, 100] } },
                xaxis: { min: 0, max: 5, tickAmount: 5, title: { text: "Days" } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (v) => v + " day" + (v === 1 ? "" : "s") } },
            };
        },

        /* ── 100% stacked bar ── */
        citySeries() {
            return this.cityData;
        },
        cityOptions() {
            return {
                chart: { stacked: true, stackType: "100%", toolbar: { show: false }, fontFamily: FONT },
                colors: [GREEN, BLUE, "#dc2626"],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: "60%" } },
                stroke: { width: 1, colors: ["#fff"] },
                xaxis: { categories: this.cityLabels },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (v) => v + " orders" } },
            };
        },

        /* ── Candlestick: weekly average order value (new) ── */
        candleSeries() {
            return [{ data: this.candleData }];
        },
        candleOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                plotOptions: { candlestick: { colors: { upward: GREEN, downward: RED }, wick: { useFillColor: true } } },
                xaxis: { title: { text: "Average order value, weekly" } },
                yaxis: { labels: { formatter: shortMoney }, tooltip: { enabled: true } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        /* ── Heatmap ── */
        heatSeries() {
            const days = ["Fri", "Thu", "Wed", "Tue", "Mon", "Sun", "Sat"];
            const slots = ["9–12", "12–15", "15–18", "18–21", "21–24"];
            const r = seeded(42);
            return days.map((d) => ({
                name: d,
                data: slots.map((s, si) => ({ x: s, y: Math.round(4 + r() * 12 + (si === 3 ? 10 : 0) + (d === "Fri" || d === "Sat" ? 6 : 0)) })),
            }));
        },
        heatOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                dataLabels: { enabled: true, style: { fontSize: "12px" } },
                colors: [GREEN],
                stroke: { width: 3, colors: ["#fff"] },
                plotOptions: {
                    heatmap: {
                        radius: 6,
                        colorScale: {
                            ranges: [
                                { from: 0, to: 8, color: "#d6f3e6", name: "Low" },
                                { from: 9, to: 14, color: "#7fd8b2", name: "Medium" },
                                { from: 15, to: 22, color: "#1fae7c", name: "High" },
                                { from: 23, to: 60, color: "#0b5a4a", name: "Peak" },
                            ],
                        },
                    },
                },
                legend: { position: "top", horizontalAlign: "right" },
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

.tt-date-pill {
    display: inline-flex; align-items: center; gap: 6px;
    height: 36px; padding: 0 14px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.22);
}
.tt-add { color: #0a5548 !important; font-weight: 600; }
.tt-range { border-color: rgba(255, 255, 255, 0.35) !important; }
.tt-range .v-btn { height: 36px !important; color: #fff !important; font-size: 13px; }
.tt-range .v-btn--active { background: rgba(255, 255, 255, 0.18) !important; }

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

/* ───────── Tables & lists ───────── */
.tt-table { background: transparent; }
.tt-table th {
    font-size: 11px !important; font-weight: 600 !important; letter-spacing: 0.4px; text-transform: uppercase;
    color: #3d5450 !important; background: #e9f0ee !important; white-space: nowrap;
}
.tt-table td { font-size: 13.5px; }
.tt-table tbody tr:hover { background: #f6faf9; }

.tt-li { border-bottom: 1px solid var(--line); }
.tt-li:last-child { border-bottom: 0; }
.tt-stock { font-size: 12px; font-weight: 600; color: #8a5a00; background: #fdf1cf; padding: 2px 10px; border-radius: 12px; }
.tt-stock.zero { color: #b3261e; background: #fbe6e4; }
</style>