<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Pending orders</h1>
                    <div class="tt-hero-sub">Orders waiting to be confirmed or moved into processing</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-btn variant="outlined" class="text-none tt-ghost-btn" prepend-icon="fa fa-list" to="/admin/orders">All orders</v-btn>
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

        <!-- ═════════ Aging breakdown ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-clock-alert-outline</v-icon><span>How long orders have been waiting</span></div>
        <v-row dense>
            <v-col v-for="a in agingBuckets" :key="a.label" cols="12" sm="6" md="3">
                <v-card flat class="tt-card tt-kpi tt-status-kpi" :class="{ 'is-active': agingFilter === a.filter }" @click="agingFilter = a.filter">
                    <div class="d-flex align-center">
                        <div class="tt-kpi-icon" :style="{ background: a.tint }">
                            <v-icon :icon="a.icon" size="20" :color="a.color" />
                        </div>
                        <div class="tt-kpi-label ml-3">{{ a.label }}</div>
                    </div>
                    <div class="tt-kpi-value mt-3" :style="{ color: a.color }">{{ a.count }}</div>
                    <div class="tt-sub">{{ money(a.total) }} total value</div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Pending queue ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>Pending queue</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-amber">
                    <div class="tt-head tt-head-wrap">
                        <span class="tt-dot"></span><span class="tt-card-title">{{ filteredOrders.length }} waiting</span>
                        <v-spacer />
                        <transition name="fade">
                            <div v-if="selected.length" class="d-flex align-center ga-2 mr-2">
                                <span class="tt-sub">{{ selected.length }} selected</span>
                                <v-btn size="small" variant="flat" color="#0f9d6b" class="text-none" @click="bulkAction('Processing')">
                                    Move to processing
                                </v-btn>
                                <v-btn size="small" variant="outlined" color="#dc2626" class="text-none" @click="bulkAction('Cancelled')">
                                    Cancel
                                </v-btn>
                            </div>
                        </transition>
                        <v-text-field
                            v-model="search"
                            density="compact"
                            variant="outlined"
                            hide-details
                            placeholder="Search order or customer"
                            prepend-inner-icon="fa fa-search"
                            style="max-width: 240px"
                        />
                    </div>

                    <EasyDataTable
                        :headers="pendingHeaders"
                        :items="filteredOrders"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="8"
                        :search-value="search"
                        :body-row-class-name="rowClassName"
                        empty-message="Nothing waiting — all caught up."
                    >
                        <template #header-select>
                            <v-checkbox-btn :model-value="allSelected" @update:model-value="toggleAll" />
                        </template>
                        <template #item-select="o">
                            <v-checkbox-btn :model-value="selected.includes(o.id)" @update:model-value="toggleOne(o.id)" />
                        </template>
                        <template #item-id="o">
                            <span class="font-weight-medium">{{ o.id }}</span>
                        </template>
                        <template #item-placedLabel="o">
                            <span class="tt-muted">{{ o.placedLabel }}</span>
                        </template>
                        <template #item-waitingLabel="o">
                            <span class="tt-wait-chip" :class="{ urgent: o.hoursWaiting >= 24 }">
                                <v-icon size="12">{{ o.hoursWaiting >= 24 ? "mdi-alert-circle-outline" : "mdi-clock-outline" }}</v-icon>
                                {{ o.waitingLabel }}
                            </span>
                        </template>
                        <template #item-total="o">
                            <span class="font-weight-medium">{{ money(o.total) }}</span>
                        </template>
                        <template #item-actions="o">
                            <v-btn size="small" variant="text" color="#0f9d6b" class="text-none" @click="setStatus(o, 'Processing')">
                                Accept
                            </v-btn>
                            <v-btn size="small" variant="text" color="#dc2626" class="text-none" @click="setStatus(o, 'Cancelled')">
                                Cancel
                            </v-btn>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
const GREEN = "#0f9d6b";
const AMBER = "#f59e0b";
const RED = "#dc2626";
const INK = "#0f2a26";

const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");

const hoursAgo = (h) => new Date(Date.now() - h * 3600 * 1000);
const fmtDateTime = (d) => d.toLocaleDateString("en-GB", { day: "numeric", month: "short" }) + ", " + d.toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });

const waitingLabel = (hours) => {
    if (hours < 1) return "Just now";
    if (hours < 24) return Math.floor(hours) + "h";
    const days = Math.floor(hours / 24);
    return days + "d " + Math.floor(hours % 24) + "h";
};

export default {
    name: "PendingOrders",

    data() {
        // ── Sample data: replace with API call (e.g. GET /api/admin/orders?status=Pending) ──
        const raw = [
            { id: "#TT-10482", customer: "Rahim Traders", payment: "bKash", total: 48500, hoursWaiting: 3 },
            { id: "#TT-10474", customer: "Tania Islam", payment: "bKash", total: 5600, hoursWaiting: 8 },
            { id: "#TT-10469", customer: "Nayeem Chowdhury", payment: "Cash on delivery", total: 16200, hoursWaiting: 14 },
            { id: "#TT-10461", customer: "Priya Das", payment: "Nagad", total: 9400, hoursWaiting: 26 },
            { id: "#TT-10455", customer: "Sultana Begum", payment: "Card", total: 63200, hoursWaiting: 31 },
            { id: "#TT-10448", customer: "Habibur Rahman", payment: "bKash", total: 4100, hoursWaiting: 52 },
            { id: "#TT-10440", customer: "Anika Ferdous", payment: "Cash on delivery", total: 27800, hoursWaiting: 74 },
        ];

        return {
            search: "",
            agingFilter: "All",
            selected: [],

            // ── EasyDataTable header definitions ──
            pendingHeaders: [
                { text: "", value: "select", sortable: false, width: 40 },
                { text: "Order", value: "id", sortable: true },
                { text: "Customer", value: "customer", sortable: true },
                { text: "Placed", value: "placedLabel", sortable: false },
                { text: "Waiting", value: "waitingLabel", sortable: false },
                { text: "Payment", value: "payment", sortable: true },
                { text: "Total", value: "total", sortable: true },
                { text: "Actions", value: "actions", sortable: false },
            ],

            orders: raw.map((o) => ({
                ...o,
                placed: hoursAgo(o.hoursWaiting),
                placedLabel: fmtDateTime(hoursAgo(o.hoursWaiting)),
                waitingLabel: waitingLabel(o.hoursWaiting),
            })),
        };
    },

    computed: {
        agingBuckets() {
            const under6 = this.orders.filter((o) => o.hoursWaiting < 6);
            const h6to24 = this.orders.filter((o) => o.hoursWaiting >= 6 && o.hoursWaiting < 24);
            const h24to48 = this.orders.filter((o) => o.hoursWaiting >= 24 && o.hoursWaiting < 48);
            const over48 = this.orders.filter((o) => o.hoursWaiting >= 48);
            const sum = (list) => list.reduce((a, o) => a + o.total, 0);
            return [
                { label: "Under 6 hours", count: under6.length, total: sum(under6), icon: "mdi-clock-outline", color: GREEN, tint: "#dff5ec", filter: "under6" },
                { label: "6–24 hours", count: h6to24.length, total: sum(h6to24), icon: "mdi-clock-outline", color: "#0284c7", tint: "#def2fc", filter: "6to24" },
                { label: "1–2 days", count: h24to48.length, total: sum(h24to48), icon: "mdi-alert-circle-outline", color: AMBER, tint: "#fff2d6", filter: "24to48" },
                { label: "Over 2 days", count: over48.length, total: sum(over48), icon: "mdi-alert-octagon-outline", color: RED, tint: "#fde8e8", filter: "over48" },
            ];
        },

        heroStats() {
            const total = this.orders.length;
            const totalValue = this.orders.reduce((a, o) => a + o.total, 0);
            const urgent = this.orders.filter((o) => o.hoursWaiting >= 24).length;
            return [
                { icon: "mdi-clock-outline", value: String(total), label: "Orders pending", note: "Right now", tag: "Live" },
                { icon: "mdi-cash-multiple", value: this.money(totalValue), label: "Value waiting", note: "Right now", tag: "Live" },
                { icon: "mdi-alert-octagon-outline", value: String(urgent), label: "Waiting over 24h", note: "Needs attention", tag: urgent ? "Act now" : "All clear", warn: urgent > 0 },
            ];
        },

        filteredOrders() {
            // Text search is handled by EasyDataTable's own search-value prop below;
            // this only narrows by the aging-bucket filter and keeps the oldest first.
            return this.orders
                .filter((o) => {
                    if (this.agingFilter === "under6") return o.hoursWaiting < 6;
                    if (this.agingFilter === "6to24") return o.hoursWaiting >= 6 && o.hoursWaiting < 24;
                    if (this.agingFilter === "24to48") return o.hoursWaiting >= 24 && o.hoursWaiting < 48;
                    if (this.agingFilter === "over48") return o.hoursWaiting >= 48;
                    return true;
                })
                .sort((a, b) => b.hoursWaiting - a.hoursWaiting);
        },

        allSelected() {
            return this.filteredOrders.length > 0 && this.filteredOrders.every((o) => this.selected.includes(o.id));
        },
    },

    methods: {
        money: fullMoney,

        rowClassName(item) {
            return item.hoursWaiting >= 24 ? "tt-row-urgent" : "";
        },

        toggleAll(val) {
            const ids = this.filteredOrders.map((o) => o.id);
            this.selected = val ? Array.from(new Set([...this.selected, ...ids])) : this.selected.filter((id) => !ids.includes(id));
        },
        toggleOne(id) {
            this.selected = this.selected.includes(id) ? this.selected.filter((x) => x !== id) : [...this.selected, id];
        },

        setStatus(order, status) {
            // Hook this up to your real update endpoint, e.g. PATCH /api/admin/orders/:id { status }
            this.orders = this.orders.filter((o) => o.id !== order.id);
            this.selected = this.selected.filter((id) => id !== order.id);
        },
        bulkAction(status) {
            this.orders = this.orders.filter((o) => !this.selected.includes(o.id));
            this.selected = [];
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
.tt-ghost-btn { color: #fff !important; border-color: rgba(255, 255, 255, 0.4) !important; }

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
.tt-live.warn { color: #fcd34d; background: rgba(245, 158, 11, 0.16); border-color: rgba(252, 211, 77, 0.5); }
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
.tt-amber { --accent: #d97706; --tint: #fff2d6; }

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
.tt-sub { font-size: 12.5px; color: var(--muted); }
.tt-muted { color: var(--muted); }

/* ───────── Clickable aging KPI cards ───────── */
.tt-kpi { padding: 16px; }
.tt-kpi-icon { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-kpi-label { font-size: 12.5px; font-weight: 500; color: var(--muted); }
.tt-kpi-value { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
.tt-status-kpi { cursor: pointer; border: 1px solid var(--line); transition: border-color 0.15s, box-shadow 0.15s; }
.tt-status-kpi:hover { border-color: #c4d3d0; }
.tt-status-kpi.is-active { border-color: #d97706; box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.14); }

/* ───────── Tables (EasyDataTable) ───────── */
:deep(.customize-table) {
    --easy-table-border: 1px solid #e3eae8;
    --easy-table-row-border: 1px solid #e3eae8;
    --easy-table-header-font-size: 11px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #3d5450;
    --easy-table-header-background-color: #e9f0ee;
    --easy-table-body-row-height: 56px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #f6faf9;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
:deep(.customize-table .tt-row-urgent) { background: #fff8ec; }
:deep(.customize-table .tt-row-urgent:hover) { background: #fef0d6; }

/* waiting-time chip */
.tt-wait-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12px; font-weight: 600; color: #0f7a4d;
    background: #e0f5ea; border: 1px solid #b7e6cd;
    padding: 2px 9px; border-radius: 12px;
}
.tt-wait-chip.urgent { color: #b3261e; background: #fbe6e4; border-color: #f5c2bd; }

/* selection toolbar fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>