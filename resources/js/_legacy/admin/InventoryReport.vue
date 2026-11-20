<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Inventory report</h1>
                    <div class="tt-hero-sub">Stock levels · Warehouses · Reorder alerts · Dead stock</div>
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
                        style="min-width: 160px"
                    />
                    <v-select
                        v-model="warehouse"
                        :items="warehouseFilterOptions"
                        label="Warehouse"
                        density="comfortable"
                        variant="outlined"
                        hide-details
                        class="tt-select"
                        style="min-width: 160px"
                    />
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

        <!-- ═════════ Stock value ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-cash-multiple</v-icon><span>Stock value</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Stock in vs. stock out</span><v-spacer /><span class="tt-tag">Live</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Units received vs. units sold or shipped out, by month</div>
                        <apexchart type="line" height="320" :options="movementOptions" :series="movementSeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Stock value by category</span></div>
                    <div class="tt-body">
                        <apexchart type="donut" height="330" :options="categoryOptions" :series="categoryData.map((c) => c.value)" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Health + warehouse ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-warehouse</v-icon><span>Stock health &amp; warehouses</span></div>
        <v-row dense>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Stock health</span></div>
                    <div class="tt-body"><apexchart type="radialBar" height="290" :options="healthOptions" :series="healthData.map((h) => h.value)" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Stock value by warehouse</span></div>
                    <div class="tt-body"><apexchart type="bar" height="290" :options="warehouseOptions" :series="[{ name: 'Stock value', data: warehouseData.map((w) => w.value) }]" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ All inventory (full width) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>All inventory</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-blue">
                    <div class="tt-head tt-head-wrap">
                        <span class="tt-dot"></span><span class="tt-card-title">{{ filteredInventory.length }} SKUs</span>
                        <v-spacer />
                        <v-text-field
                            v-model="search"
                            density="compact"
                            variant="outlined"
                            hide-details
                            placeholder="Search SKU or product"
                            prepend-inner-icon="fa fa-search"
                            style="max-width: 250px"
                        />
                    </div>
                    <EasyDataTable
                        :headers="inventoryHeaders"
                        :items="filteredInventory"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="8"
                        :search-value="search"
                        empty-message="No inventory items match your filters."
                    >
                        <template #item-sku="i"><span class="font-weight-medium">{{ i.sku }}</span></template>
                        <template #item-value="i"><span class="font-weight-medium">{{ money(i.value) }}</span></template>
                        <template #item-status="i">
                            <v-chip :color="stockColor[i.status]" size="small" variant="tonal" label>{{ i.status }}</v-chip>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Reorder + dead stock (half width each) ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-alert-circle-outline</v-icon><span>Reorder alerts &amp; dead stock</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Needs reordering</span>
                        <v-spacer />
                        <span class="tt-tag warn">{{ reorderItems.length }}</span>
                    </div>
                    <EasyDataTable
                        :headers="reorderHeaders"
                        :items="reorderItems"
                        table-class-name="customize-table tt-table-amber"
                        buttons-pagination
                        :rows-per-page="5"
                    >
                        <template #item-name="i"><span class="font-weight-medium">{{ i.name }}</span></template>
                        <template #item-status="i">
                            <v-chip :color="stockColor[i.status]" size="small" variant="tonal" label>{{ i.status }}</v-chip>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Dead stock (no sales in 60+ days)</span>
                        <v-spacer />
                        <span class="tt-tag">{{ money(deadStockValue) }} tied up</span>
                    </div>
                    <EasyDataTable
                        :headers="deadStockHeaders"
                        :items="deadStock"
                        table-class-name="customize-table tt-table-purple"
                        buttons-pagination
                        :rows-per-page="5"
                    >
                        <template #item-name="i"><span class="font-weight-medium">{{ i.name }}</span></template>
                        <template #item-value="i"><span class="font-weight-medium">{{ money(i.value) }}</span></template>
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

const shortMoney = (v) => (v >= 100000 ? (v / 100000).toFixed(1).replace(".0", "") + "L" : v >= 1000 ? Math.round(v / 1000) + "k" : v);
const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");

const seeded = (seed) => {
    let s = seed;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
};
const makeSeries = (n, base, spread, seed) => {
    const r = seeded(seed);
    return Array.from({ length: n }, (_, i) => Math.round(base * (1 + i * 0.01) + (r() - 0.5) * spread));
};

export default {
    name: "InventoryReport",
    components: { apexchart: VueApexCharts },

    data() {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        return {
            category: "All categories",
            warehouse: "All warehouses",
            search: "",

            categoryFilterOptions: ["All categories", "Power banks", "Earbuds", "Smart watches", "Cables", "Gaming", "Networking"],
            warehouseFilterOptions: ["All warehouses", "Dhaka DC", "Chattogram DC", "Sylhet Hub"],

            kpis: [
                { label: "Total stock value", value: "৳ 1,26,40,000", change: 4.2, icon: "mdi-cash-multiple", color: GREEN, tint: "#dff5ec", spark: [40, 44, 42, 48, 47, 52, 55, 58, 60] },
                { label: "Total SKUs", value: "2,146", change: 1.8, icon: "mdi-package-variant-closed", color: BLUE, tint: "#e6edff", spark: [50, 52, 54, 55, 57, 58, 60, 61, 62] },
                { label: "Units in stock", value: "48,920", change: 2.6, icon: "mdi-cube-outline", color: TEAL, tint: "#dcf3f0", spark: [30, 34, 33, 38, 37, 40, 42, 44, 46] },
                { label: "Low-stock items", value: "18", change: 5.9, inverse: true, icon: "mdi-alert-outline", color: AMBER, tint: "#fff2d6", spark: [10, 11, 12, 12, 14, 15, 16, 17, 18] },
                { label: "Out-of-stock items", value: "6", change: 20, inverse: true, icon: "mdi-close-octagon-outline", color: RED, tint: "#fde8e8", spark: [2, 2, 3, 3, 4, 4, 5, 5, 6] },
                { label: "Dead stock value", value: "৳ 3,84,000", change: -6.4, inverse: true, icon: "mdi-package-variant", color: PURPLE, tint: "#efe8ff", spark: [12, 12, 11, 11, 10, 9, 9, 8, 8].map((n) => n * 4000) },
            ],

            categoryData: [
                { name: "Power banks and chargers", value: 3620000 },
                { name: "Earbuds and headphones", value: 2410000 },
                { name: "Smart watches", value: 2180000 },
                { name: "Cables and adapters", value: 1340000 },
                { name: "Gaming gear", value: 980000 },
                { name: "Networking", value: 3110000 },
            ],

            warehouseData: [
                { name: "Dhaka DC", value: 8420000 },
                { name: "Chattogram DC", value: 2860000 },
                { name: "Sylhet Hub", value: 1360000 },
            ],

            healthData: [
                { name: "Healthy", value: 74 },
                { name: "Low stock", value: 15 },
                { name: "Out of stock", value: 6 },
                { name: "Overstocked", value: 5 },
            ],

            // stock in vs. stock out, by month
            stockIn: makeSeries(12, 4200, 900, 11),
            stockOut: makeSeries(12, 3900, 850, 21),
            movementMonths: months,

            stockColor: { "In stock": "success", "Low stock": "warning", "Out of stock": "error", "Overstocked": "info" },

            // ── Sample data: replace with API calls (e.g. GET /api/admin/reports/inventory) ──
            inventory: [
                { sku: "PB-AN-20", name: "Anker PowerCore 20000", category: "Power banks", warehouse: "Dhaka DC", inStock: 3, reorderLevel: 20, value: 12600, status: "Low stock" },
                { sku: "EB-BS-09", name: "Baseus Bowie E9 Earbuds", category: "Earbuds", warehouse: "Dhaka DC", inStock: 5, reorderLevel: 25, value: 7450, status: "Low stock" },
                { sku: "SW-XM-08", name: "Xiaomi Smart Band 8", category: "Smart watches", warehouse: "Chattogram DC", inStock: 2, reorderLevel: 15, value: 6300, status: "Low stock" },
                { sku: "RT-TP-C6", name: "TP-Link Archer C6 Router", category: "Networking", warehouse: "Dhaka DC", inStock: 0, reorderLevel: 10, value: 0, status: "Out of stock" },
                { sku: "SP-JB-G4", name: "JBL Go 4 Speaker", category: "Gaming", warehouse: "Sylhet Hub", inStock: 4, reorderLevel: 20, value: 6600, status: "Low stock" },
                { sku: "CB-HC-1M", name: "Hoco cable 1m", category: "Cables", warehouse: "Dhaka DC", inStock: 640, reorderLevel: 100, value: 268800, status: "In stock" },
                { sku: "CH-UG-65", name: "Ugreen 65W Charger", category: "Cables", warehouse: "Chattogram DC", inStock: 210, reorderLevel: 40, value: 441000, status: "In stock" },
                { sku: "EB-RM-T3", name: "Realme Buds T300", category: "Earbuds", warehouse: "Sylhet Hub", inStock: 0, reorderLevel: 15, value: 0, status: "Out of stock" },
                { sku: "SD-128G", name: "SanDisk 128GB", category: "Gaming", warehouse: "Dhaka DC", inStock: 340, reorderLevel: 50, value: 680000, status: "In stock" },
                { sku: "MS-LG-102", name: "Logitech G102", category: "Gaming", warehouse: "Chattogram DC", inStock: 480, reorderLevel: 60, value: 1152000, status: "Overstocked" },
                { sku: "RT-TL-AX", name: "TP-Link Archer AX10", category: "Networking", warehouse: "Sylhet Hub", inStock: 60, reorderLevel: 20, value: 264000, status: "In stock" },
                { sku: "PB-BS-28", name: "Baseus 20000 Power Bank", category: "Power banks", warehouse: "Chattogram DC", inStock: 96, reorderLevel: 30, value: 268800, status: "In stock" },
            ],
        };
    },

    computed: {
        totalStockValue() {
            return this.categoryData.reduce((a, c) => a + c.value, 0);
        },

        heroStats() {
            const lowCount = this.inventory.filter((i) => i.status === "Low stock").length;
            const outCount = this.inventory.filter((i) => i.status === "Out of stock").length;
            return [
                { icon: "mdi-cash-multiple", value: this.money(this.totalStockValue), label: "Total stock value", note: "Across all warehouses", tag: "Live" },
                { icon: "mdi-alert-outline", value: String(lowCount), label: "Low-stock SKUs", note: "Need reordering soon", tag: lowCount ? "Watch" : "All clear" },
                { icon: "mdi-close-octagon-outline", value: String(outCount), label: "Out-of-stock SKUs", note: "Losing sales right now", tag: outCount ? "Act now" : "All clear", warn: outCount > 0 },
            ];
        },

        filteredInventory() {
            return this.inventory.filter((i) => {
                const matchesCategory = this.category === "All categories" || i.category === this.category;
                const matchesWarehouse = this.warehouse === "All warehouses" || i.warehouse === this.warehouse;
                return matchesCategory && matchesWarehouse;
            });
        },

        reorderItems() {
            return this.inventory.filter((i) => i.status === "Low stock" || i.status === "Out of stock");
        },

        deadStock() {
            // Sample "no recent sales" list — replace with a real last-sold-date query.
            return [
                { name: "Havit H2002d Headset", category: "Gaming", daysIdle: 74, value: 42000 },
                { name: "TP-Link RJ45 Connector Pack", category: "Networking", daysIdle: 91, value: 18600 },
                { name: "Old-model 10000mAh Power Bank", category: "Power banks", daysIdle: 118, value: 96000 },
                { name: "Wired Earphones (basic)", category: "Earbuds", daysIdle: 66, value: 15400 },
                { name: "USB-A to Mini-USB Cable", category: "Cables", daysIdle: 83, value: 12000 },
            ];
        },
        deadStockValue() {
            return this.deadStock.reduce((a, d) => a + d.value, 0);
        },

        inventoryHeaders() {
            return [
                { text: "SKU", value: "sku", sortable: true },
                { text: "Product", value: "name", sortable: true, width: 240 },
                { text: "Category", value: "category", sortable: true },
                { text: "Warehouse", value: "warehouse", sortable: true },
                { text: "In stock", value: "inStock", sortable: true },
                { text: "Reorder at", value: "reorderLevel", sortable: true },
                { text: "Value", value: "value", sortable: true },
                { text: "Status", value: "status", sortable: true },
            ];
        },
        reorderHeaders() {
            return [
                { text: "Product", value: "name", sortable: true },
                { text: "Warehouse", value: "warehouse", sortable: true },
                { text: "In stock", value: "inStock", sortable: true },
                { text: "Reorder at", value: "reorderLevel", sortable: true },
                { text: "Status", value: "status", sortable: true },
            ];
        },
        deadStockHeaders() {
            return [
                { text: "Product", value: "name", sortable: true },
                { text: "Category", value: "category", sortable: true },
                { text: "Days idle", value: "daysIdle", sortable: true },
                { text: "Value tied up", value: "value", sortable: true },
            ];
        },

        /* ── Stock in vs. out ── */
        movementSeries() {
            return [
                { name: "Stock in", data: this.stockIn },
                { name: "Stock out", data: this.stockOut },
            ];
        },
        movementOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [GREEN, "#9fb6f0"],
                stroke: { width: [3, 2], curve: "smooth" },
                fill: { type: ["gradient", "solid"], gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 95, 100] } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.movementMonths, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { title: { text: "Units" } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { shared: true, y: { formatter: (v) => v + " units" } },
            };
        },

        /* ── Category donut ── */
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
                                total: { show: true, label: "Total value", formatter: (w) => fullMoney(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
                                value: { formatter: fullMoney },
                            },
                        },
                    },
                },
            };
        },

        /* ── Stock health radial ── */
        healthOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.healthData.map((h) => h.name),
                colors: [GREEN, AMBER, RED, PURPLE],
                plotOptions: {
                    radialBar: {
                        hollow: { size: "32%" },
                        track: { background: "#eaf0ee" },
                        dataLabels: {
                            name: { fontSize: "13px" },
                            value: { fontSize: "18px", formatter: (v) => v + "%" },
                            total: { show: true, label: "Healthy", formatter: () => this.healthData[0].value + "%" },
                        },
                    },
                },
                legend: { show: true, position: "bottom", fontSize: "12px" },
            };
        },

        /* ── Warehouse bars ── */
        warehouseOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: "50%", distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: true, formatter: shortMoney, style: { fontSize: "12px" } },
                xaxis: { categories: this.warehouseData.map((w) => w.name), labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                colors: [TEAL, "#22b8a0", "#5fcdb9"],
                tooltip: { y: { formatter: fullMoney } },
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
            // Hook this up to your real export endpoint, e.g. GET /api/admin/reports/inventory/export
            // eslint-disable-next-line no-console
            console.log("Export inventory report", { category: this.category, warehouse: this.warehouse });
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
:deep(.tt-table-amber.customize-table) { --easy-table-body-row-hover-background-color: #fff8ec; }
:deep(.tt-table-purple.customize-table) { --easy-table-body-row-hover-background-color: #f5f0ff; }
</style>