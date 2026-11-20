<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Orders</h1>
                    <div class="tt-hero-sub">All orders · Status · Payments · Fulfillment</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-btn color="white" variant="flat" class="text-none tt-add" prepend-icon="fa fa-plus" to="/admin/order/create">Create order</v-btn>
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

        <!-- ═════════ Status summary ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-chart-box-outline</v-icon><span>Order status breakdown</span></div>
        <v-row dense>
            <v-col v-for="s in statusKpis" :key="s.label" cols="12" sm="6" md="3">
                <v-card flat class="tt-card tt-kpi tt-status-kpi" :class="{ 'is-active': statusFilter === s.filter }" @click="statusFilter = s.filter">
                    <div class="d-flex align-center">
                        <div class="tt-kpi-icon" :style="{ background: s.tint }">
                            <v-icon :icon="s.icon" size="20" :color="s.color" />
                        </div>
                        <div class="tt-kpi-label ml-3">{{ s.label }}</div>
                    </div>
                    <div class="tt-kpi-value mt-3" :style="{ color: s.color }">{{ s.count }}</div>
                    <div class="tt-sub">{{ money(s.total) }} total value</div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Orders table ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>All orders</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-blue">
                    <div class="tt-head tt-head-wrap">
                        <span class="tt-dot"></span><span class="tt-card-title">{{ filteredOrders.length }} orders</span>
                        <v-spacer />
                        <v-select
                            v-model="statusFilter"
                            :items="statusFilterOptions"
                            label="Status"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="max-width: 160px"
                            class="mr-2"
                        />
                        <v-select
                            v-model="paymentFilter"
                            :items="paymentFilterOptions"
                            label="Payment"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="max-width: 170px"
                            class="mr-2"
                        />
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
                        :headers="orderHeaders"
                        :items="filteredOrders"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="6"
                        :search-value="search"
                        empty-message="No orders match your filters."
                    >
                        <template #item-id="o">
                            <span class="font-weight-medium">{{ o.id }}</span>
                        </template>
                        <template #item-items="o">
                            <span class="text-right d-block">{{ o.items.length }}</span>
                        </template>
                        <template #item-total="o">
                            <span class="font-weight-medium">{{ money(o.total) }}</span>
                        </template>
                        <template #item-status="o">
                            <v-select
                                :model-value="o.status"
                                :items="statusOptions"
                                density="compact"
                                variant="plain"
                                hide-details
                                class="tt-status-select"
                                :class="'is-' + o.status.toLowerCase()"
                                @update:model-value="(val) => setStatus(o, val)"
                            />
                        </template>
                        <template #item-actions="o">
                            <v-btn icon size="small" variant="text" @click="openDetail(o)">
                                <v-icon size="16">fa fa-eye</v-icon>
                            </v-btn>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Order detail dialog ═════════ -->
        <v-dialog v-model="detailOpen" max-width="640">
            <v-card v-if="activeOrder" class="tt-detail-card">
                <div class="tt-detail-head">
                    <div>
                        <div class="tt-detail-title">{{ activeOrder.id }}</div>
                        <div class="tt-sub">Placed {{ activeOrder.date }}</div>
                    </div>
                    <v-spacer />
                    <v-chip :color="statusColor[activeOrder.status]" size="small" variant="tonal" label>{{ activeOrder.status }}</v-chip>
                    <v-btn icon variant="text" size="small" class="ml-2" @click="detailOpen = false">
                        <v-icon size="16">fa fa-times</v-icon>
                    </v-btn>
                </div>

                <div class="tt-detail-body">
                    <div class="tt-detail-grid mb-4">
                        <div>
                            <div class="tt-detail-label">Customer</div>
                            <div class="font-weight-medium">{{ activeOrder.customer }}</div>
                            <div class="tt-sub">{{ activeOrder.phone }}</div>
                        </div>
                        <div>
                            <div class="tt-detail-label">Shipping address</div>
                            <div class="tt-sub">{{ activeOrder.address }}</div>
                        </div>
                        <div>
                            <div class="tt-detail-label">Payment method</div>
                            <div class="font-weight-medium">{{ activeOrder.payment }}</div>
                        </div>
                        <div>
                            <div class="tt-detail-label">Total</div>
                            <div class="font-weight-medium">{{ money(activeOrder.total) }}</div>
                        </div>
                    </div>

                    <div class="tt-detail-label mb-2">Items</div>
                    <v-table class="tt-table tt-detail-table mb-4">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="it in activeOrder.items" :key="it.name">
                                <td>{{ it.name }}</td>
                                <td class="text-right">{{ it.qty }}</td>
                                <td class="text-right">{{ money(it.price) }}</td>
                                <td class="text-right font-weight-medium">{{ money(it.qty * it.price) }}</td>
                            </tr>
                        </tbody>
                    </v-table>

                    <div class="tt-detail-label mb-2">Status history</div>
                    <div class="tt-timeline">
                        <div v-for="(step, i) in activeOrder.timeline" :key="i" class="tt-timeline-item" :class="{ done: step.done }">
                            <span class="tt-timeline-dot"></span>
                            <div>
                                <div class="font-weight-medium">{{ step.label }}</div>
                                <div class="tt-sub">{{ step.date || "Pending" }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
const GREEN = "#0f9d6b";
const BLUE = "#2f5be7";
const AMBER = "#f59e0b";
const RED = "#dc2626";
const INFO = "#0284c7";

const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");

const buildTimeline = (status) => {
    const stages = ["Pending", "Processing", "Delivered"];
    if (status === "Cancelled") {
        return [
            { label: "Order placed", done: true, date: "18 Sep 2026, 10:12 AM" },
            { label: "Cancelled", done: true, date: "18 Sep 2026, 2:40 PM" },
        ];
    }
    const idx = stages.indexOf(status);
    return stages.map((s, i) => ({
        label: s === "Pending" ? "Order placed" : s,
        done: i <= idx,
        date: i <= idx ? "18 Sep 2026" : null,
    }));
};

export default {
    name: "Orders",

    data() {
        return {
            search: "",
            statusFilter: "All",
            paymentFilter: "All",
            detailOpen: false,
            activeOrder: null,

            statusFilterOptions: ["All", "Pending", "Processing", "Delivered", "Cancelled"],
            paymentFilterOptions: ["All", "bKash", "Nagad", "Cash on delivery", "Card"],
            statusOptions: ["Pending", "Processing", "Delivered", "Cancelled"],
            statusColor: { Pending: "warning", Processing: "info", Delivered: "success", Cancelled: "error" },

            // ── EasyDataTable header definitions ──
            orderHeaders: [
                { text: "Order", value: "id", sortable: true },
                { text: "Customer", value: "customer", sortable: true },
                { text: "Date", value: "date", sortable: true },
                { text: "Items", value: "items", sortable: false },
                { text: "Payment", value: "payment", sortable: true },
                { text: "Total", value: "total", sortable: true },
                { text: "Status", value: "status", sortable: true },
                { text: "Actions", value: "actions", sortable: false },
            ],

            // ── Sample data: replace with API calls (e.g. GET /api/admin/orders) ──
            orders: [
                {
                    id: "#TT-10482", customer: "Rahim Traders", phone: "+880 1711-223344", address: "House 12, Road 4, Dhanmondi, Dhaka",
                    date: "19 Sep 2026", payment: "bKash", status: "Pending", total: 48500,
                    items: [{ name: "Anker PowerCore 20000", qty: 2, price: 4200 }, { name: "Ugreen 65W Charger", qty: 3, price: 2100 }],
                },
                {
                    id: "#TT-10481", customer: "Sabbir Hossain", phone: "+880 1611-778899", address: "Flat 3B, Khulshi, Chattogram",
                    date: "19 Sep 2026", payment: "Cash on delivery", status: "Processing", total: 12900,
                    items: [{ name: "Baseus Bowie E9 Earbuds", qty: 1, price: 1490 }, { name: "Hoco cable 1m", qty: 5, price: 420 }],
                },
                {
                    id: "#TT-10480", customer: "Dhaka Mobile Hub", phone: "+880 1911-556677", address: "Shop 22, Motijheel, Dhaka",
                    date: "18 Sep 2026", payment: "Card", status: "Delivered", total: 186000,
                    items: [{ name: "Xiaomi Smart Band 8", qty: 40, price: 3150 }, { name: "JBL Go 4", qty: 20, price: 1650 }],
                },
                {
                    id: "#TT-10479", customer: "Nusrat Jahan", phone: "+880 1811-334455", address: "House 5, Sector 7, Uttara, Dhaka",
                    date: "18 Sep 2026", payment: "Nagad", status: "Delivered", total: 7400,
                    items: [{ name: "Hoco cable 1m", qty: 2, price: 420 }, { name: "TP-Link RJ45 connector", qty: 10, price: 656 }],
                },
                {
                    id: "#TT-10478", customer: "Gadget Point", phone: "+880 1511-991122", address: "Shop 8, GEC Circle, Chattogram",
                    date: "17 Sep 2026", payment: "bKash", status: "Cancelled", total: 23100,
                    items: [{ name: "Realme Buds T300", qty: 3, price: 2790 }, { name: "SanDisk 128GB", qty: 5, price: 3138 }],
                },
                {
                    id: "#TT-10477", customer: "Farhan Kabir", phone: "+880 1711-887766", address: "House 9, Zindabazar, Sylhet",
                    date: "17 Sep 2026", payment: "Cash on delivery", status: "Delivered", total: 31200,
                    items: [{ name: "Anker PowerCore 20000", qty: 4, price: 4200 }, { name: "Logitech G102", qty: 6, price: 2400 }],
                },
                {
                    id: "#TT-10476", customer: "Ayesha Rahman", phone: "+880 1611-445566", address: "House 2, Boyra, Khulna",
                    date: "16 Sep 2026", payment: "bKash", status: "Delivered", total: 9800,
                    items: [{ name: "Baseus Bowie E9 Earbuds", qty: 2, price: 1490 }, { name: "Hoco cable 1m", qty: 6, price: 420 }],
                },
                {
                    id: "#TT-10475", customer: "Karim Electronics", phone: "+880 1911-223311", address: "Shop 14, Shaheb Bazar, Rajshahi",
                    date: "16 Sep 2026", payment: "Card", status: "Processing", total: 142500,
                    items: [{ name: "Xiaomi Smart Band 8", qty: 30, price: 3150 }, { name: "Ugreen 65W Charger", qty: 20, price: 2100 }],
                },
                {
                    id: "#TT-10474", customer: "Tania Islam", phone: "+880 1511-667788", address: "House 18, Kotwali, Barishal",
                    date: "15 Sep 2026", payment: "bKash", status: "Pending", total: 5600,
                    items: [{ name: "Hoco cable 1m", qty: 8, price: 420 }, { name: "SanDisk 128GB", qty: 1, price: 2000 }],
                },
                {
                    id: "#TT-10473", customer: "Mizanur Rahman", phone: "+880 1811-998877", address: "Shop 4, Station Road, Rangpur",
                    date: "15 Sep 2026", payment: "Nagad", status: "Delivered", total: 21400,
                    items: [{ name: "JBL Go 4", qty: 10, price: 1650 }, { name: "Realme Buds T300", qty: 2, price: 2790 }],
                },
            ],
        };
    },

    computed: {
        ordersWithTimeline() {
            return this.orders.map((o) => ({ ...o, timeline: buildTimeline(o.status) }));
        },

        statusKpis() {
            const groups = { Pending: [], Processing: [], Delivered: [], Cancelled: [] };
            this.orders.forEach((o) => groups[o.status]?.push(o));
            const meta = {
                Pending: { icon: "mdi-clock-outline", color: AMBER, tint: "#fff2d6", filter: "Pending" },
                Processing: { icon: "mdi-progress-clock", color: INFO, tint: "#def2fc", filter: "Processing" },
                Delivered: { icon: "mdi-check-circle-outline", color: GREEN, tint: "#dff5ec", filter: "Delivered" },
                Cancelled: { icon: "mdi-close-circle-outline", color: RED, tint: "#fde8e8", filter: "Cancelled" },
            };
            return Object.keys(groups).map((k) => ({
                label: k,
                count: groups[k].length,
                total: groups[k].reduce((a, o) => a + o.total, 0),
                ...meta[k],
            }));
        },

        heroStats() {
            const totalRevenue = this.orders.reduce((a, o) => a + o.total, 0);
            const avg = Math.round(totalRevenue / this.orders.length);
            return [
                { icon: "mdi-receipt-text-outline", value: String(this.orders.length), label: "Total orders", note: "All time (sample)", tag: "Live" },
                { icon: "mdi-cash-multiple", value: this.money(totalRevenue), label: "Total order value", note: "All time (sample)", tag: "Live" },
                { icon: "mdi-basket-outline", value: this.money(avg), label: "Average order value", note: "All time (sample)", tag: "Live" },
            ];
        },

        filteredOrders() {
            // Text search is handled by EasyDataTable's own search-value prop below;
            // this only narrows by the status and payment filters in the header.
            return this.ordersWithTimeline.filter((o) => {
                const matchesStatus = this.statusFilter === "All" || o.status === this.statusFilter;
                const matchesPayment = this.paymentFilter === "All" || o.payment === this.paymentFilter;
                return matchesStatus && matchesPayment;
            });
        },
    },

    methods: {
        money: fullMoney,

        setStatus(order, value) {
            // Hook this up to your real update endpoint, e.g. PATCH /api/admin/orders/:id { status: value }
            order.status = value;
        },

        openDetail(order) {
            this.activeOrder = { ...order, timeline: buildTimeline(order.status) };
            this.detailOpen = true;
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
.tt-blue { --accent: #2f5be7; --tint: #e9efff; }

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

/* ───────── Clickable status KPI cards ───────── */
.tt-kpi { padding: 16px; }
.tt-kpi-icon { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-kpi-label { font-size: 12.5px; font-weight: 500; color: var(--muted); }
.tt-kpi-value { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
.tt-status-kpi { cursor: pointer; border: 1px solid var(--line); transition: border-color 0.15s, box-shadow 0.15s; }
.tt-status-kpi:hover { border-color: #c4d3d0; }
.tt-status-kpi.is-active { border-color: var(--accent, #2f5be7); box-shadow: 0 0 0 2px rgba(47, 91, 231, 0.12); }

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

/* status dropdown chip look, colour follows selected status */
.tt-status-select { min-width: 120px; }
.tt-status-select :deep(.v-field__input) { font-size: 12.5px; font-weight: 600; padding-top: 2px; padding-bottom: 2px; }
.tt-status-select.is-pending :deep(.v-field__input) { color: #8a5a00; }
.tt-status-select.is-processing :deep(.v-field__input) { color: #0369a1; }
.tt-status-select.is-delivered :deep(.v-field__input) { color: #0f7a4d; }
.tt-status-select.is-cancelled :deep(.v-field__input) { color: #b3261e; }

/* ───────── Order detail dialog ───────── */
.tt-detail-card { font-family: Poppins, "Segoe UI", sans-serif; border-radius: 12px !important; }
.tt-detail-head {
    display: flex; align-items: center; gap: 8px;
    padding: 18px 20px; border-bottom: 1px solid var(--line);
}
.tt-detail-title { font-size: 17px; font-weight: 700; color: var(--ink); }
.tt-detail-body { padding: 18px 20px; }
.tt-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 20px; }
.tt-detail-label { font-size: 11px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #3d5450; margin-bottom: 2px; }
.tt-detail-table { background: transparent; }
.tt-detail-table th {
    font-size: 11px !important; font-weight: 600 !important; letter-spacing: 0.4px; text-transform: uppercase;
    color: #3d5450 !important; background: #e9f0ee !important;
}
.tt-detail-table td { font-size: 12.5px !important; }

.tt-timeline { display: flex; flex-direction: column; gap: 14px; }
.tt-timeline-item { display: flex; align-items: flex-start; gap: 10px; opacity: 0.45; }
.tt-timeline-item.done { opacity: 1; }
.tt-timeline-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--line); margin-top: 4px; flex: none; }
.tt-timeline-item.done .tt-timeline-dot { background: #0f9d6b; }

@media (max-width: 599px) {
    .tt-detail-grid { grid-template-columns: 1fr; }
}
</style>