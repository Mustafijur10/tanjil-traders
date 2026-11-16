<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Processing orders</h1>
                    <div class="tt-hero-sub">Accepted orders being packed and prepared for handover</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-btn
                        variant="outlined"
                        class="text-none tt-ghost-btn"
                        prepend-icon="fa fa-list"
                        to="/admin/orders"
                    >
                        All orders
                    </v-btn>
                    <v-btn
                        color="white"
                        variant="flat"
                        class="text-none tt-add"
                        prepend-icon="fa fa-print"
                        @click="notify(`Pick list for ${filteredOrders.length} orders sent to the printer`)"
                    >
                        Print pick list
                    </v-btn>
                </div>
            </div>

            <div class="tt-hero-strip">
                <div v-for="h in heroStats" :key="h.label" class="tt-hero-item">
                    <div class="tt-hero-icon">
                        <v-icon :icon="h.icon" size="18" color="white" />
                    </div>
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

        <!-- ═════════ Fulfilment stages ═════════ -->
        <div class="tt-section">
            <v-icon size="16">mdi-package-variant-closed</v-icon>
            <span>Where the work is sitting</span>
            <v-spacer />
            <button v-if="stageFilter" type="button" class="tt-reset" @click="stageFilter = ''">
                <v-icon size="13">mdi-close-circle-outline</v-icon>
                Clear filter
            </button>
        </div>
        <v-row dense>
            <v-col v-for="s in stageKpis" :key="s.label" cols="12" sm="6" md="3">
                <v-card
                    flat
                    class="tt-card tt-kpi tt-status-kpi"
                    :class="{ 'is-active': stageFilter === s.value }"
                    :style="{ '--stage': s.color }"
                    role="button"
                    tabindex="0"
                    :aria-pressed="stageFilter === s.value"
                    @click="stageFilter = stageFilter === s.value ? '' : s.value"
                    @keydown.enter.prevent="stageFilter = stageFilter === s.value ? '' : s.value"
                    @keydown.space.prevent="stageFilter = stageFilter === s.value ? '' : s.value"
                >
                    <div class="d-flex align-center">
                        <div class="tt-kpi-icon" :style="{ background: s.tint }">
                            <v-icon :icon="s.icon" size="20" :color="s.color" />
                        </div>
                        <div class="tt-kpi-label ml-3">{{ s.label }}</div>
                    </div>
                    <div class="tt-kpi-value mt-3" :style="{ color: s.color }">{{ s.count }}</div>
                    <div class="tt-sub">{{ money(s.total) }} total value</div>
                    <div v-if="s.blocked" class="tt-kpi-flag">
                        <v-icon size="12">mdi-alert-circle-outline</v-icon>
                        {{ s.blocked }} blocked
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Processing queue ═════════ -->
        <div class="tt-section">
            <v-icon size="16">mdi-table</v-icon>
            <span>Processing queue</span>
        </div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-blue">
                    <div class="tt-head tt-head-wrap">
                        <span class="tt-dot"></span>
                        <span class="tt-card-title">{{ filteredOrders.length }} in processing</span>
                        <span class="tt-sub ml-1">· oldest promise first</span>
                        <v-spacer />

                        <transition name="fade">
                            <div v-if="selected.length" class="tt-bulk d-flex align-center ga-2 mr-2">
                                <span class="tt-sub">{{ selected.length }} selected</span>
                                <v-btn
                                    size="small"
                                    variant="flat"
                                    color="#0f9d6b"
                                    class="text-none"
                                    prepend-icon="mdi-package-check"
                                    @click="bulkReady"
                                >
                                    Mark ready
                                </v-btn>
                                <v-btn
                                    size="small"
                                    variant="outlined"
                                    color="#0284c7"
                                    class="text-none"
                                    prepend-icon="mdi-truck-fast-outline"
                                    @click="courierOpen = true"
                                >
                                    Assign courier
                                </v-btn>
                                <v-btn icon size="small" variant="text" aria-label="Clear selection" @click="selected = []">
                                    <v-icon size="16">fa fa-times</v-icon>
                                </v-btn>
                            </div>
                        </transition>

                        <v-select
                            v-model="courierFilter"
                            :items="courierFilterOptions"
                            label="Courier"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="max-width: 170px"
                            class="mr-2"
                        />
                        <v-select
                            v-model="slaFilter"
                            :items="slaFilterOptions"
                            label="Dispatch SLA"
                            density="compact"
                            variant="outlined"
                            hide-details
                            style="max-width: 180px"
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
                            aria-label="Search orders"
                        />
                    </div>

                    <EasyDataTable
                        :headers="processingHeaders"
                        :items="filteredOrders"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="8"
                        :search-value="search"
                        :body-row-class-name="rowClassName"
                        empty-message="Nothing in processing — accept pending orders to start packing."
                    >
                        <template #header-select>
                            <v-checkbox-btn
                                :model-value="allSelected"
                                :indeterminate="selected.length > 0 && !allSelected"
                                aria-label="Select all orders"
                                @update:model-value="toggleAll"
                            />
                        </template>

                        <template #item-select="o">
                            <v-checkbox-btn
                                :model-value="selected.includes(o.id)"
                                :aria-label="`Select ${o.id}`"
                                @update:model-value="toggleOne(o.id)"
                            />
                        </template>

                        <template #item-id="o">
                            <div class="tt-order">
                                <span class="font-weight-medium">{{ o.id }}</span>
                                <span v-if="o.priority === 'High'" class="tt-flag-high">Priority</span>
                            </div>
                        </template>

                        <template #item-customer="o">
                            <div class="tt-cust">
                                <span class="tt-cust-name">{{ o.customer }}</span>
                                <span class="tt-sub">{{ o.phone }}</span>
                            </div>
                        </template>

                        <template #item-placedLabel="o">
                            <span class="tt-muted">{{ o.placedLabel }}</span>
                        </template>

                        <template #item-stage="o">
                            <v-select
                                :model-value="o.stage"
                                :items="stageOptions"
                                density="compact"
                                variant="plain"
                                hide-details
                                class="tt-stage-select"
                                :class="'is-' + slug(o.stage)"
                                :aria-label="`Stage for ${o.id}`"
                                @update:model-value="(val) => setStage(o, val)"
                            />
                        </template>

                        <template #item-ageLabel="o">
                            <span class="tt-age-chip" :class="o.ageTone">
                                <v-icon size="12">
                                    {{ o.slaHours < 0 ? "mdi-alert-circle-outline" : "mdi-clock-outline" }}
                                </v-icon>
                                {{ o.ageLabel }}
                            </span>
                        </template>

                        <template #item-courierLabel="o">
                            <span v-if="o.courier" class="tt-courier">
                                <v-icon size="13">mdi-truck-outline</v-icon>
                                {{ o.courier }}
                            </span>
                            <button v-else type="button" class="tt-link-btn" @click="assignCourier(o)">
                                Assign courier
                            </button>
                        </template>

                        <template #item-itemsCount="o">
                            <span class="text-right d-block tt-muted">{{ o.itemsCount }} items</span>
                        </template>

                        <template #item-total="o">
                            <span class="font-weight-medium">{{ money(o.total) }}</span>
                        </template>

                        <template #item-actions="o">
                            <div class="tt-actions">
                                <v-btn
                                    v-if="o.stage !== 'Ready to ship'"
                                    size="small"
                                    variant="text"
                                    color="#0f9d6b"
                                    class="text-none"
                                    :disabled="o.stage === 'Awaiting stock'"
                                    @click="setStage(o, 'Ready to ship')"
                                >
                                    Ready
                                </v-btn>
                                <v-btn
                                    v-else
                                    size="small"
                                    variant="text"
                                    color="#0284c7"
                                    class="text-none"
                                    @click="dispatch(o)"
                                >
                                    Dispatch
                                </v-btn>
                                <v-btn
                                    icon
                                    size="small"
                                    variant="text"
                                    :aria-label="`View ${o.id}`"
                                    @click="openDetail(o)"
                                >
                                    <v-icon size="16">fa fa-eye</v-icon>
                                </v-btn>
                            </div>
                        </template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Order detail dialog ═════════ -->
        <v-dialog v-model="detailOpen" max-width="680">
            <v-card v-if="activeOrder" class="tt-detail-card">
                <div class="tt-detail-head">
                    <div>
                        <div class="tt-detail-title">{{ activeOrder.id }}</div>
                        <div class="tt-sub">
                            {{ activeOrder.customer }} · placed {{ activeOrder.placedLabel }}
                        </div>
                    </div>
                    <v-spacer />
                    <v-chip :color="stageColor[activeOrder.stage]" size="small" variant="tonal" label>
                        {{ activeOrder.stage }}
                    </v-chip>
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        class="ml-2"
                        aria-label="Close"
                        @click="detailOpen = false"
                    >
                        <v-icon size="16">fa fa-times</v-icon>
                    </v-btn>
                </div>

                <div class="tt-detail-body">
                    <div class="tt-detail-grid mb-4">
                        <div>
                            <div class="tt-detail-label">Contact</div>
                            <div class="font-weight-medium">{{ activeOrder.customer }}</div>
                            <div class="tt-sub">{{ activeOrder.phone }}</div>
                        </div>
                        <div>
                            <div class="tt-detail-label">Ship to</div>
                            <div class="tt-sub">{{ activeOrder.address }}</div>
                        </div>
                        <div>
                            <div class="tt-detail-label">Payment</div>
                            <div class="font-weight-medium">{{ activeOrder.payment }}</div>
                        </div>
                        <div>
                            <div class="tt-detail-label">Courier</div>
                            <div class="font-weight-medium">{{ activeOrder.courier || "Not assigned" }}</div>
                        </div>
                    </div>

                    <div class="tt-detail-label mb-2">Picking progress</div>
                    <div class="tt-pick mb-4">
                        <v-progress-linear
                            :model-value="pickProgress"
                            height="8"
                            rounded
                            color="#0f9d6b"
                            bg-color="#e3eae8"
                            class="flex-grow-1"
                        />
                        <span class="tt-sub">{{ pickedCount }} / {{ activeOrder.items.length }} items picked</span>
                    </div>

                    <v-table class="tt-table tt-detail-table mb-4">
                        <thead>
                            <tr>
                                <th style="width: 44px"></th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="it in activeOrder.items"
                                :key="it.sku"
                                :class="{ 'is-picked': it.picked }"
                            >
                                <td>
                                    <v-checkbox-btn
                                        v-model="it.picked"
                                        :aria-label="`Mark ${it.name} as picked`"
                                    />
                                </td>
                                <td>{{ it.name }}</td>
                                <td class="tt-muted">{{ it.sku }}</td>
                                <td class="text-right">{{ it.qty }}</td>
                                <td class="text-right font-weight-medium">
                                    {{ money(it.qty * it.price) }}
                                </td>
                            </tr>
                        </tbody>
                    </v-table>

                    <div class="tt-detail-label mb-2">Fulfilment timeline</div>
                    <div class="tt-timeline">
                        <div
                            v-for="(step, i) in activeOrder.timeline"
                            :key="i"
                            class="tt-timeline-item"
                            :class="step.state"
                        >
                            <span class="tt-timeline-dot"></span>
                            <div>
                                <div class="font-weight-medium">
                                    {{ step.label }}
                                    <span v-if="step.state === 'current'" class="tt-now">now</span>
                                </div>
                                <div class="tt-sub">{{ step.date || "Pending" }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="tt-detail-label mt-4 mb-2">Packing note</div>
                    <v-textarea
                        v-model="activeOrder.note"
                        density="compact"
                        variant="outlined"
                        rows="2"
                        hide-details
                        placeholder="e.g. fragile, gift wrap, call before delivery"
                    />
                </div>

                <div class="tt-detail-foot">
                    <v-btn variant="text" color="#dc2626" class="text-none" @click="cancelOrder(activeOrder)">
                        Cancel order
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="detailOpen = false">Close</v-btn>
                    <v-btn
                        v-if="activeOrder.stage !== 'Ready to ship'"
                        color="#0f9d6b"
                        variant="flat"
                        class="text-none"
                        prepend-icon="mdi-package-check"
                        :disabled="activeOrder.stage === 'Awaiting stock'"
                        @click="setStage(activeOrder, 'Ready to ship')"
                    >
                        Mark ready to ship
                    </v-btn>
                    <v-btn
                        v-else
                        color="#0284c7"
                        variant="flat"
                        class="text-none"
                        prepend-icon="mdi-truck-fast-outline"
                        @click="dispatch(activeOrder)"
                    >
                        Hand to courier
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>

        <!-- ═════════ Courier assignment dialog ═════════ -->
        <v-dialog v-model="courierOpen" max-width="460">
            <v-card class="tt-detail-card">
                <div class="tt-detail-head">
                    <div>
                        <div class="tt-detail-title">Assign courier</div>
                        <div class="tt-sub">
                            Applies to {{ courierTargets.length }} order{{ courierTargets.length === 1 ? "" : "s" }}
                        </div>
                    </div>
                    <v-spacer />
                    <v-btn icon variant="text" size="small" aria-label="Close" @click="courierOpen = false">
                        <v-icon size="16">fa fa-times</v-icon>
                    </v-btn>
                </div>
                <div class="tt-detail-body">
                    <v-select
                        v-model="courierForm.courier"
                        :items="couriers"
                        label="Courier partner"
                        density="compact"
                        variant="outlined"
                        class="mb-3"
                    />
                    <v-text-field
                        v-model="courierForm.pickup"
                        label="Pickup date"
                        type="date"
                        density="compact"
                        variant="outlined"
                        class="mb-3"
                    />
                    <v-textarea
                        v-model="courierForm.note"
                        label="Handover note"
                        density="compact"
                        variant="outlined"
                        rows="2"
                        hide-details
                    />
                </div>
                <div class="tt-detail-foot">
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="courierOpen = false">Cancel</v-btn>
                    <v-btn
                        color="#0284c7"
                        variant="flat"
                        class="text-none"
                        :disabled="!courierForm.courier"
                        @click="saveCourier"
                    >
                        Assign
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="toastOpen" color="#0f9d6b" timeout="2400" location="bottom">
            {{ toastText }}
        </v-snackbar>
    </div>
</template>

<script>
const GREEN = "#0f9d6b";
const BLUE = "#2f5be7";
const INFO = "#0284c7";
const AMBER = "#d97706";
const RED = "#dc2626";

const SLA_HOURS = 24; // promise: dispatch within 24h of starting processing

const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");
const slug = (v) => String(v).toLowerCase().replace(/[^a-z0-9]+/g, "-");

const hoursAgo = (h) => new Date(Date.now() - h * 3600 * 1000);
const fmtDateTime = (d) =>
    d.toLocaleDateString("en-GB", { day: "numeric", month: "short" }) +
    ", " +
    d.toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });

const stageColor = {
    "Awaiting stock": AMBER,
    Packing: INFO,
    "Ready to ship": GREEN,
    Dispatched: BLUE,
};

const buildTimeline = (order) => {
    const accepted = {
        label: "Order accepted",
        state: "done",
        date: fmtDateTime(order.placed),
    };

    if (order.stage === "Awaiting stock") {
        return [
            accepted,
            { label: "Awaiting stock", state: "current", date: order.blockedReason },
        ];
    }

    const step = (label, reached, date) => ({
        label,
        state: reached ? "done" : "next",
        date,
    });

    const packingDone = ["Ready to ship", "Dispatched"].includes(order.stage);
    const readyDone = order.stage === "Dispatched";

    return [
        accepted,
        step("Packing", packingDone, packingDone ? "Packed" : null),
        step("Ready to ship", readyDone, readyDone ? "Packed & labelled" : null),
        step("Handed to courier", readyDone, readyDone ? order.courier : null),
    ].map((s, i, all) => {
        if (s.state === "next" && all.slice(0, i).every((x) => x.state === "done")) {
            return { ...s, state: "current", date: null };
        }
        return s;
    });
};

export default {
    name: "ProcessingOrders",

    data() {
        // ── Sample data: replace with GET /api/admin/orders?status=Processing ──
        const raw = [
            {
                id: "#TT-10481", customer: "Sabbir Hossain", phone: "+880 1611-778899",
                address: "Flat 3B, Khulshi, Chattogram", payment: "Cash on delivery", total: 12900,
                stage: "Packing", courier: "Pathao", inProcessingHours: 2, priority: "High",
                items: [
                    { name: "Baseus Bowie E9 Earbuds", sku: "BAS-E9-BLK", qty: 1, price: 1490, picked: true },
                    { name: "Hoco cable 1m", sku: "HOC-1M-WHT", qty: 5, price: 420, picked: true },
                    { name: "Anker PowerCore 20000", sku: "ANK-PC20-BLK", qty: 1, price: 4200, picked: false },
                ],
            },
            {
                id: "#TT-10475", customer: "Karim Electronics", phone: "+880 1911-223311",
                address: "Shop 14, Shaheb Bazar, Rajshahi", payment: "Card", total: 142500,
                stage: "Packing", courier: "Steadfast", inProcessingHours: 9, priority: "High",
                items: [
                    { name: "Xiaomi Smart Band 8", sku: "XIA-SB8-BLK", qty: 30, price: 3150, picked: true },
                    { name: "Ugreen 65W Charger", sku: "UGR-65W-WHT", qty: 20, price: 2100, picked: false },
                ],
            },
            {
                id: "#TT-10472", customer: "Shahin Traders", phone: "+880 1711-445522",
                address: "Shop 3, New Market, Dhaka", payment: "bKash", total: 38700,
                stage: "Ready to ship", courier: "Pathao", inProcessingHours: 14, priority: "Normal",
                items: [
                    { name: "TP-Link Archer C6", sku: "TPL-C6", qty: 6, price: 3890, picked: true },
                    { name: "TP-Link RJ45 connector", sku: "TPL-RJ45", qty: 20, price: 656, picked: true },
                ],
            },
            {
                id: "#TT-10470", customer: "Mehedi Hasan", phone: "+880 1511-778811",
                address: "House 27, Mirpur 10, Dhaka", payment: "Nagad", total: 8900,
                stage: "Ready to ship", courier: "RedX", inProcessingHours: 21, priority: "Normal",
                items: [
                    { name: "Logitech G102", sku: "LOG-G102", qty: 2, price: 2400, picked: true },
                    { name: "Hoco cable 1m", sku: "HOC-1M-WHT", qty: 3, price: 420, picked: true },
                ],
            },
            {
                id: "#TT-10467", customer: "Farzana Akter", phone: "+880 1811-556677",
                address: "House 4, Zindabazar, Sylhet", payment: "bKash", total: 15600,
                stage: "Awaiting stock", courier: "", inProcessingHours: 27,
                blockedReason: "Waiting on 2× JBL Go 4 (ETA 2 days)", priority: "Normal",
                items: [
                    { name: "JBL Go 4", sku: "JBL-GO4-BLK", qty: 2, price: 1650, picked: false },
                    { name: "Anker PowerCore 20000", sku: "ANK-PC20-BLK", qty: 2, price: 4200, picked: true },
                ],
            },
            {
                id: "#TT-10463", customer: "Rakib Uddin", phone: "+880 1611-990033",
                address: "Flat 7A, Uttara Sector 4, Dhaka", payment: "Card", total: 42600,
                stage: "Ready to ship", courier: "Steadfast", inProcessingHours: 30, priority: "High",
                items: [
                    { name: "Samsung 980 1TB NVMe", sku: "SAM-980-1TB", qty: 3, price: 11200, picked: true },
                    { name: "SanDisk 128GB", sku: "SAN-128GB", qty: 3, price: 2000, picked: true },
                ],
            },
            {
                id: "#TT-10459", customer: "Nayeem Chowdhury", phone: "+880 1911-334466",
                address: "Shop 9, GEC Circle, Chattogram", payment: "Cash on delivery", total: 7400,
                stage: "Awaiting stock", courier: "Own rider", inProcessingHours: 38,
                blockedReason: "Damaged in audit — replacement requested", priority: "Normal",
                items: [
                    { name: "Baseus Bowie E9 Earbuds", sku: "BAS-E9-BLK", qty: 2, price: 1490, picked: false },
                    { name: "Hoco cable 1m", sku: "HOC-1M-WHT", qty: 8, price: 420, picked: true },
                ],
            },
            {
                id: "#TT-10456", customer: "Jannatul Ferdous", phone: "+880 1711-887799",
                address: "House 11, Boyra, Khulna", payment: "Nagad", total: 23100,
                stage: "Packing", courier: "RedX", inProcessingHours: 45, priority: "Normal",
                items: [
                    { name: "Realme Buds T300", sku: "REA-T300-WHT", qty: 5, price: 2790, picked: true },
                    { name: "Ugreen 65W Charger", sku: "UGR-65W-WHT", qty: 4, price: 2100, picked: true },
                ],
            },
        ];

        return {
            search: "",
            stageFilter: "",
            courierFilter: "All",
            slaFilter: "All",
            selected: [],
            detailOpen: false,
            activeOrder: null,
            courierOpen: false,
            courierForm: { courier: "", pickup: "", note: "" },
            toastOpen: false,
            toastText: "",

            stageOptions: ["Awaiting stock", "Packing", "Ready to ship"],
            stageColor,
            couriers: ["Pathao", "Steadfast", "RedX", "Sundarban", "Own rider", "Pickup at store"],
            courierFilterOptions: ["All", "Pathao", "Steadfast", "RedX", "Sundarban", "Own rider", "Unassigned"],
            slaFilterOptions: ["All", "On time", "Due in 6h", "Overdue"],

            processingHeaders: [
                { text: "", value: "select", sortable: false, width: 40 },
                { text: "Order", value: "id", sortable: true, width: 130 },
                { text: "Customer", value: "customer", sortable: true, width: 190 },
                { text: "Placed", value: "placedLabel", sortable: false, width: 130 },
                { text: "Stage", value: "stage", sortable: true, width: 150 },
                { text: "Age", value: "ageLabel", sortable: false, width: 100 },
                { text: "Courier", value: "courierLabel", sortable: true, width: 140 },
                { text: "Items", value: "itemsCount", sortable: true, width: 90 },
                { text: "Total", value: "total", sortable: true, width: 110 },
                { text: "", value: "actions", sortable: false, width: 140 },
            ],

            orders: raw.map((o) => {
                const slaHours = SLA_HOURS - o.inProcessingHours;
                return {
                    ...o,
                    placed: hoursAgo(o.inProcessingHours + 4),
                    placedLabel: fmtDateTime(hoursAgo(o.inProcessingHours + 4)),
                    itemsCount: o.items.reduce((a, it) => a + it.qty, 0),
                    courierLabel: o.courier || "Unassigned",
                    slaHours,
                    ageLabel:
                        slaHours >= 0
                            ? Math.ceil(slaHours) + "h left"
                            : Math.abs(Math.floor(slaHours / 24)) >= 1
                            ? Math.floor(Math.abs(slaHours) / 24) + "d late"
                            : Math.abs(slaHours) + "h late",
                    ageTone: slaHours < 0 ? "late" : slaHours <= 6 ? "soon" : "ok",
                    note: "",
                };
            }),
        };
    },

    computed: {
        stageKpis() {
            const order = [
                { value: "Awaiting stock", icon: "mdi-package-variant-remove", color: AMBER, tint: "#fff2d6" },
                { value: "Packing", icon: "mdi-package-variant", color: INFO, tint: "#def2fc" },
                { value: "Ready to ship", icon: "mdi-package-check", color: GREEN, tint: "#dff5ec" },
            ];
            const all = { value: "", label: "All in processing", icon: "mdi-layers-outline", color: BLUE, tint: "#e9efff" };

            const rows = order.map((meta) => {
                const list = this.orders.filter((o) => o.stage === meta.value);
                return {
                    ...meta,
                    label: meta.value,
                    count: list.length,
                    total: list.reduce((a, o) => a + o.total, 0),
                    blocked: meta.value === "Awaiting stock" ? list.length : 0,
                };
            });

            return [
                { ...all, count: this.orders.length, total: this.orders.reduce((a, o) => a + o.total, 0) },
                ...rows,
            ];
        },

        heroStats() {
            const totalValue = this.orders.reduce((a, o) => a + o.total, 0);
            const ready = this.orders.filter((o) => o.stage === "Ready to ship");
            const atRisk = this.orders.filter((o) => o.slaHours < 6 && o.stage !== "Awaiting stock");
            return [
                {
                    icon: "mdi-package-variant",
                    value: String(this.orders.length),
                    label: "In processing",
                    note: this.money(totalValue) + " order value",
                    tag: "Live",
                },
                {
                    icon: "mdi-package-check",
                    value: String(ready.length),
                    label: "Ready for handover",
                    note: ready.length ? "Waiting for courier pickup" : "Nothing waiting",
                    tag: "Pickup",
                },
                {
                    icon: "mdi-timer-alert-outline",
                    value: String(atRisk.length),
                    label: "Dispatch SLA at risk",
                    note: atRisk.length ? "Due within 6 hours" : "All within promise",
                    tag: atRisk.length ? "Act now" : "On track",
                    warn: atRisk.length > 0,
                },
            ];
        },

        filteredOrders() {
            // Text search is handled by EasyDataTable's search-value; this narrows the rest.
            return this.orders
                .filter((o) => {
                    const matchStage = !this.stageFilter || o.stage === this.stageFilter;
                    const matchCourier =
                        this.courierFilter === "All" ||
                        (this.courierFilter === "Unassigned" ? !o.courier : o.courier === this.courierFilter);
                    const matchSla =
                        this.slaFilter === "All" ||
                        (this.slaFilter === "On time" && o.slaHours >= 6) ||
                        (this.slaFilter === "Due in 6h" && o.slaHours >= 0 && o.slaHours < 6) ||
                        (this.slaFilter === "Overdue" && o.slaHours < 0);
                    return matchStage && matchCourier && matchSla;
                })
                .sort((a, b) => a.slaHours - b.slaHours);
        },

        allSelected() {
            return (
                this.filteredOrders.length > 0 &&
                this.filteredOrders.every((o) => this.selected.includes(o.id))
            );
        },

        courierTargets() {
            return this.orders.filter((o) => this.selected.includes(o.id));
        },

        pickedCount() {
            return this.activeOrder ? this.activeOrder.items.filter((it) => it.picked).length : 0;
        },

        pickProgress() {
            const items = this.activeOrder ? this.activeOrder.items : [];
            if (!items.length) return 0;
            return Math.round((this.pickedCount / items.length) * 100);
        },
    },

    methods: {
        money: fullMoney,
        slug,

        rowClassName(item) {
            if (item.stage === "Awaiting stock") return "tt-row-blocked";
            return item.slaHours < 0 ? "tt-row-late" : "";
        },

        notify(text) {
            this.toastText = text;
            this.toastOpen = true;
        },

        toggleAll(val) {
            const ids = this.filteredOrders.map((o) => o.id);
            this.selected = val
                ? Array.from(new Set([...this.selected, ...ids]))
                : this.selected.filter((id) => !ids.includes(id));
        },

        toggleOne(id) {
            this.selected = this.selected.includes(id)
                ? this.selected.filter((x) => x !== id)
                : [...this.selected, id];
        },

        setStage(order, stage) {
            // Hook this up to PATCH /api/admin/orders/:id { status }
            if (order.stage === "Awaiting stock" && stage !== "Awaiting stock") {
                this.notify(`${order.id} is blocked on stock — release stock before moving it forward.`);
                return;
            }
            if (order.stage === stage) return;
            order.stage = stage;
            order.timeline = buildTimeline(order);
            this.notify(`${order.id} moved to ${stage.toLowerCase()}`);
        },

        dispatch(order) {
            if (!order.courier) {
                this.selected = [order.id];
                this.courierForm.courier = "";
                this.courierOpen = true;
                this.notify(`Assign a courier before dispatching ${order.id}.`);
                return;
            }
            // Hook this up to PATCH /api/admin/orders/:id { status: 'Dispatched' }
            this.orders = this.orders.filter((o) => o.id !== order.id);
            this.selected = this.selected.filter((id) => id !== order.id);
            this.detailOpen = false;
            this.notify(`${order.id} handed to ${order.courier} — left the processing queue.`);
        },

        cancelOrder(order) {
            // Hook this up to PATCH /api/admin/orders/:id { status: 'Cancelled' }
            this.orders = this.orders.filter((o) => o.id !== order.id);
            this.selected = this.selected.filter((id) => id !== order.id);
            this.detailOpen = false;
            this.notify(`${order.id} cancelled and returned to stock.`);
        },

        bulkReady() {
            const movable = this.orders.filter(
                (o) => this.selected.includes(o.id) && o.stage !== "Awaiting stock"
            );
            movable.forEach((o) => {
                o.stage = "Ready to ship";
                o.timeline = buildTimeline(o);
            });
            const blocked = this.selected.length - movable.length;
            this.selected = [];
            this.notify(
                blocked
                    ? `${movable.length} marked ready · ${blocked} skipped (awaiting stock)`
                    : `${movable.length} orders marked ready to ship`
            );
        },

        assignCourier(order) {
            this.selected = [order.id];
            this.courierForm.courier = "";
            this.courierOpen = true;
        },

        saveCourier() {
            const partner = this.courierForm.courier;
            const targets = this.courierTargets;
            targets.forEach((o) => {
                o.courier = partner;
                o.courierLabel = partner;
                o.timeline = buildTimeline(o);
            });
            // Hook this up to POST /api/admin/orders/assign-courier { ids, courier, pickup, note }
            this.courierOpen = false;
            this.selected = [];
            this.courierForm = { courier: "", pickup: "", note: "" };
            this.notify(`${partner} assigned to ${targets.length} order${targets.length === 1 ? "" : "s"}`);
        },

        openDetail(order) {
            // Keep the same object so stage / picking changes in the dialog stay in sync with the table.
            this.activeOrder = order;
            this.activeOrder.timeline = buildTimeline(order);
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
.tt-ghost-btn { color: #fff !important; border-color: rgba(255, 255, 255, 0.4) !important; }
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
.tt-reset {
    display: inline-flex; align-items: center; gap: 4px;
    border: 0; background: transparent; padding: 2px 0;
    font: inherit; font-size: 11px; font-weight: 600; letter-spacing: 0;
    text-transform: none; color: #0a5548; cursor: pointer;
}
.tt-reset:hover { text-decoration: underline; }

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
.tt-bulk {
    padding: 4px 10px;
    border: 1px solid #cfe6da;
    border-radius: 999px;
    background: #f4fbf8;
}

/* ───────── Stage KPI cards ───────── */
.tt-kpi { padding: 16px; }
.tt-kpi-icon { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-kpi-label { font-size: 12.5px; font-weight: 500; color: var(--muted); }
.tt-kpi-value { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
.tt-kpi-flag {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 8px; padding: 2px 8px; border-radius: 10px;
    font-size: 11px; font-weight: 600; color: #b45309;
    background: #fff2d6; border: 1px solid #f6dcaa;
}
.tt-status-kpi {
    cursor: pointer;
    border: 1px solid var(--line);
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
}
.tt-status-kpi:hover { border-color: #c4d3d0; transform: translateY(-1px); }
.tt-status-kpi:focus-visible { outline: 2px solid #0a5548; outline-offset: 2px; }
.tt-status-kpi.is-active {
    border-color: var(--stage, #2f5be7);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--stage, #2f5be7) 18%, transparent);
}

/* ───────── Table ───────── */
:deep(.customize-table) {
    --easy-table-border: 1px solid #e3eae8;
    --easy-table-row-border: 1px solid #e3eae8;
    --easy-table-header-font-size: 11px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #3d5450;
    --easy-table-header-background-color: #e9f0ee;
    --easy-table-body-row-height: 58px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #f6faf9;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
:deep(.customize-table .tt-row-late) { background: #fff7f7; }
:deep(.customize-table .tt-row-late:hover) { background: #fdeeee; }
:deep(.customize-table .tt-row-blocked) { background: #fffbf2; }
:deep(.customize-table .tt-row-blocked:hover) { background: #fdf4e3; }

.tt-order { display: flex; align-items: center; gap: 6px; }
.tt-flag-high {
    font-size: 9.5px; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase;
    color: #b3261e; background: #fbe6e4; border: 1px solid #f5c2bd;
    padding: 1px 6px; border-radius: 8px;
}
.tt-cust { display: flex; flex-direction: column; line-height: 1.35; }
.tt-cust-name { font-weight: 500; }

.tt-stage-select { min-width: 128px; max-width: 150px; }
.tt-stage-select :deep(.v-field__input) { font-size: 12.5px; font-weight: 600; padding-top: 2px; padding-bottom: 2px; }
.tt-stage-select.is-awaiting-stock :deep(.v-field__input) { color: #b45309; }
.tt-stage-select.is-packing :deep(.v-field__input) { color: #0369a1; }
.tt-stage-select.is-ready-to-ship :deep(.v-field__input) { color: #0f7a4d; }

.tt-age-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11.5px; font-weight: 600;
    padding: 2px 9px; border-radius: 12px;
    color: #0f7a4d; background: #e0f5ea; border: 1px solid #b7e6cd;
}
.tt-age-chip.soon { color: #b45309; background: #fff4dd; border-color: #f6dcaa; }
.tt-age-chip.late { color: #b3261e; background: #fbe6e4; border-color: #f5c2bd; }

.tt-courier { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; }
.tt-link-btn {
    border: 0; background: transparent; padding: 0;
    font: inherit; font-size: 12px; font-weight: 600;
    color: #0284c7; cursor: pointer; text-decoration: underline;
}
.tt-actions { display: flex; align-items: center; justify-content: flex-end; gap: 2px; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ───────── Detail + courier dialogs ───────── */
.tt-detail-card { font-family: Poppins, "Segoe UI", sans-serif; border-radius: 12px !important; }
.tt-detail-head { display: flex; align-items: center; gap: 8px; padding: 18px 20px; border-bottom: 1px solid var(--line); }
.tt-detail-title { font-size: 17px; font-weight: 700; color: var(--ink); }
.tt-detail-body { padding: 18px 20px; }
.tt-detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 20px; }
.tt-detail-label {
    font-size: 11px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase;
    color: #3d5450; margin-bottom: 2px;
}
.tt-detail-foot {
    display: flex; align-items: center; gap: 8px;
    padding: 14px 20px; border-top: 1px solid var(--line); background: #fbfdfc;
}
.tt-detail-table { background: transparent; }
.tt-detail-table th {
    font-size: 11px !important; font-weight: 600 !important; letter-spacing: 0.4px; text-transform: uppercase;
    color: #3d5450 !important; background: #e9f0ee !important;
}
.tt-detail-table td { font-size: 12.5px !important; }
.tt-detail-table tr.is-picked td { color: #8fa39f; }
.tt-detail-table tr.is-picked td:nth-child(2) { text-decoration: line-through; }

.tt-pick { display: flex; align-items: center; gap: 12px; }

.tt-timeline { display: flex; flex-direction: column; gap: 14px; }
.tt-timeline-item { display: flex; align-items: flex-start; gap: 10px; opacity: 0.45; }
.tt-timeline-item.done { opacity: 1; }
.tt-timeline-item.current { opacity: 1; }
.tt-timeline-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--line); margin-top: 4px; flex: none; }
.tt-timeline-item.done .tt-timeline-dot { background: #0f9d6b; }
.tt-timeline-item.current .tt-timeline-dot {
    background: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.16);
}
.tt-now {
    font-size: 9.5px; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase;
    color: #0369a1; background: #def2fc; border-radius: 8px; padding: 1px 6px; margin-left: 4px;
}

@media (max-width: 599px) {
    .tt-detail-grid { grid-template-columns: 1fr; }
    .tt-detail-foot { flex-wrap: wrap; }
}
</style>
