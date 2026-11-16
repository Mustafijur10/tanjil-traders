<template>
    <v-defaults-provider :defaults="fieldDefaults">
        <div class="tt-page">
            <!-- ═════════ Hero ═════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="d-flex flex-wrap align-center ga-3">
                            <h1 class="tt-h1">Coupons &amp; discounts</h1>
                            <span class="tt-pill tt-pill-live"><span class="tt-pulse"></span>{{ activeCount }} coupons live</span>
                        </div>
                        <div class="tt-hero-sub">Create codes and automatic offers, and see which ones bring in orders</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <div class="tt-seg" role="tablist" aria-label="Discount type">
                            <button v-for="v in views" :key="v.value" type="button" role="tab" :aria-selected="view === v.value" :class="{ on: view === v.value }" @click="view = v.value">{{ v.label }}</button>
                        </div>
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-send-outline" to="/admin/campaigns">Send a coupon</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="openNew">New coupon</v-btn>
                    </div>
                </div>

                <div class="tt-hero-strip">
                    <div v-for="h in heroTiles" :key="h.label" class="tt-hero-item" :style="{ '--hue': h.color }">
                        <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                        <div class="tt-hero-txt">
                            <div class="tt-hero-value">{{ h.value }}</div>
                            <div class="tt-hero-label">{{ h.label }}</div>
                        </div>
                        <span class="tt-live" :class="{ todo: h.todo }">{{ h.tag }}</span>
                    </div>
                </div>
            </header>

            <!-- ═════════════════ COUPON CODES ═════════════════ -->
            <template v-if="view === 'codes'">
                <div class="best-grid mt-4">
                    <div v-for="(b, i) in bestCoupons" :key="b.id" class="best" :style="{ '--c': bestColors[i] }">
                        <span class="best-rank">{{ i + 1 }}</span>
                        <div class="best-main">
                            <div class="best-code">{{ b.code }}</div>
                            <div class="tt-subt">{{ discountText(b) }} · used {{ b.used.toLocaleString("en-IN") }} times</div>
                        </div>
                        <div class="best-rev"><b>{{ money(b.revenue) }}</b><small>in orders</small></div>
                    </div>
                </div>

                <section class="tt-card a-teal mt-4">
                    <div class="tt-head tt-head-wrap">
                        <div class="tt-head-ic"><v-icon size="16">mdi-ticket-percent-outline</v-icon></div>
                        <div><div class="tt-title">All coupons</div><div class="tt-subt">{{ filtered.length }} of {{ coupons.length }} coupons</div></div>
                        <v-spacer />
                        <v-text-field v-model="search" class="tt-search" hide-details clearable prepend-inner-icon="mdi-magnify" placeholder="Search by code or name" @update:model-value="page = 1" />
                        <div class="tt-seg tt-seg-light" role="tablist" aria-label="Status filter">
                            <button v-for="s in statusTabs" :key="s" type="button" role="tab" :aria-selected="statusFilter === s" :class="{ on: statusFilter === s }" @click="setStatus(s)">{{ s }}</button>
                        </div>
                    </div>

                    <v-table class="tt-table">
                        <thead>
                            <tr><th>Code</th><th>Discount</th><th>Who and what</th><th>Used</th><th class="text-right">Orders worth</th><th>Valid until</th><th>Status</th><th class="text-right">Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in paged" :key="c.id">
                                <td>
                                    <div class="code-cell">
                                        <span class="code-badge">{{ c.code }}</span>
                                        <v-btn icon variant="text" size="x-small" :aria-label="'Copy code ' + c.code" @click="copy(c.code)"><v-icon size="15" color="#5c726e">mdi-content-copy</v-icon></v-btn>
                                    </div>
                                    <div class="tt-subt">{{ c.name }}</div>
                                </td>
                                <td><span class="tt-chip" :class="'type-' + c.type">{{ discountText(c) }}</span></td>
                                <td class="tt-muted who">
                                    <div>{{ c.eligibility }}</div>
                                    <div class="tt-subt">{{ c.minOrder ? "Orders over " + money(c.minOrder) : "No minimum order" }} · {{ c.appliesTo }}</div>
                                </td>
                                <td>
                                    <div class="tt-minibar"><span class="tt-minibar-track"><span :style="{ width: usedPct(c) + '%' }"></span></span><b>{{ c.used.toLocaleString("en-IN") }}<template v-if="c.limit"> / {{ c.limit.toLocaleString("en-IN") }}</template></b></div>
                                </td>
                                <td class="text-right tt-strong">{{ c.revenue ? money(c.revenue) : "-" }}</td>
                                <td class="tt-muted">{{ c.end ? fmtDate(c.end) : "No end date" }}</td>
                                <td><span class="tt-chip" :class="'tone-' + tones[status(c)]">{{ status(c) }}</span></td>
                                <td class="text-right actions">
                                    <v-switch v-if="status(c) !== 'Expired'" :model-value="!c.paused" color="#0f9d6b" density="compact" hide-details inset :aria-label="(c.paused ? 'Resume ' : 'Pause ') + c.code" @update:model-value="togglePause(c)" />
                                    <v-btn icon variant="text" size="small" :aria-label="'Edit ' + c.code" @click="openEdit(c)"><v-icon size="18" color="#4d6561">mdi-pencil-outline</v-icon></v-btn>
                                    <v-btn icon variant="text" size="small" :aria-label="'Delete ' + c.code" @click="deleting = c"><v-icon size="18" color="#b4381a">mdi-delete-outline</v-icon></v-btn>
                                </td>
                            </tr>
                            <tr v-if="!paged.length"><td colspan="8" class="text-center tt-muted py-6">No coupons match. Clear the search or create a new coupon.</td></tr>
                        </tbody>
                    </v-table>
                    <div class="tt-foot">
                        <span class="tt-subt">{{ showing }}</span>
                        <v-spacer />
                        <v-pagination v-model="page" :length="pageCount" :total-visible="5" density="comfortable" size="small" rounded="circle" active-color="#0a5548" />
                    </div>
                </section>
            </template>

            <!-- ═════════════════ AUTOMATIC DISCOUNTS ═════════════════ -->
            <div v-else class="mt-4">
                <div class="auto-intro">
                    <div class="tt-strong">Offers that apply without a code</div>
                    <div class="tt-subt">Customers see the discount in their cart automatically when they qualify.</div>
                </div>
                <div class="auto-grid">
                    <section v-for="(a, i) in automatic" :key="a.id" class="tt-card auto" :class="accents[i % accents.length]">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">{{ a.icon }}</v-icon></div>
                            <div class="auto-title"><div class="tt-title">{{ a.name }}</div><div class="tt-subt">{{ a.rule }}</div></div>
                            <v-spacer />
                            <v-switch v-model="a.on" color="#0f9d6b" density="compact" hide-details inset :aria-label="'Turn ' + a.name + ' on or off'" @update:model-value="toggleAuto(a)" />
                        </div>
                        <div class="tt-body">
                            <p class="auto-desc">{{ a.desc }}</p>
                            <div class="auto-meta">
                                <div><small>Orders helped</small><b>{{ a.orders.toLocaleString("en-IN") }}</b></div>
                                <div><small>Discount given</small><b>{{ money(a.given) }}</b></div>
                                <span class="tt-chip" :class="a.on ? 'tone-green' : 'tone-gray'">{{ a.on ? "On" : "Off" }}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- ═════════ Coupon editor ═════════ -->
            <v-dialog v-model="editorOpen" max-width="760" scrollable content-class="tt-overlay">
                <div class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg"><v-icon size="20">mdi-ticket-percent-outline</v-icon></div>
                        <div class="ml-3"><div class="tt-info-title">{{ form.id ? "Edit coupon" : "New coupon" }}</div><div class="tt-subt">Customers type the code at checkout</div></div>
                        <v-spacer />
                        <v-btn icon size="small" variant="text" aria-label="Close" @click="editorOpen = false"><v-icon size="18">mdi-close</v-icon></v-btn>
                    </div>

                    <div class="tt-dialog-body">
                        <div class="preview-ticket">
                            <span class="code-badge big">{{ form.code || "YOURCODE" }}</span>
                            <div><b>{{ previewText }}</b><div class="tt-subt">{{ previewSub }}</div></div>
                        </div>

                        <v-row dense class="mt-3">
                            <v-col cols="12" sm="6">
                                <v-text-field v-model="form.code" label="Coupon code" prepend-inner-icon="mdi-ticket-outline" hide-details="auto" :error-messages="touched ? codeError : ''" :hint="'Letters and numbers only, 4 to 16 characters'" persistent-hint @update:model-value="form.code = String($event || '').toUpperCase().replace(/[^A-Z0-9]/g, '')">
                                    <template #append-inner><v-btn variant="text" size="small" class="text-none tt-link" @click="form.code = genCode()">Generate</v-btn></template>
                                </v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6"><v-text-field v-model="form.name" label="Name for your team" prepend-inner-icon="mdi-tag-outline" hint="Customers don't see this" persistent-hint /></v-col>
                        </v-row>

                        <div class="tt-field-label">What the customer gets</div>
                        <div class="type-grid">
                            <button v-for="t in types" :key="t.value" type="button" class="type" :class="{ on: form.type === t.value }" :aria-pressed="form.type === t.value" @click="form.type = t.value">
                                <v-icon size="18">{{ t.icon }}</v-icon><b>{{ t.label }}</b><small>{{ t.hint }}</small>
                            </button>
                        </div>
                        <v-row v-if="form.type !== 'shipping'" dense class="mt-3">
                            <v-col cols="12" sm="6"><v-text-field v-model.number="form.value" type="number" :label="form.type === 'fixed' ? 'Amount off' : 'Percentage'" :prefix="form.type === 'fixed' ? '৳' : ''" :suffix="form.type === 'fixed' ? '' : '%'" hide-details="auto" :error-messages="touched ? valueError : ''" /></v-col>
                            <v-col v-if="form.type !== 'fixed'" cols="12" sm="6"><v-text-field v-model.number="form.cap" type="number" label="Most it can take off (optional)" prefix="৳" hide-details /></v-col>
                        </v-row>
                        <div v-if="marginWarning" class="ed-note warn"><v-icon size="15">mdi-alert-outline</v-icon>{{ marginWarning }}</div>

                        <div class="tt-field-label">Who can use it, and on what</div>
                        <v-row dense>
                            <v-col cols="12" sm="6"><v-select v-model="form.eligibility" :items="eligibility" label="Customers" hide-details :menu-props="overlayMenu" /></v-col>
                            <v-col cols="12" sm="6"><v-text-field v-model.number="form.minOrder" type="number" label="Minimum order (optional)" prefix="৳" hide-details /></v-col>
                            <v-col cols="12" sm="6"><v-select v-model="form.appliesKind" :items="['All products', 'Categories', 'Brands']" label="Applies to" hide-details :menu-props="overlayMenu" /></v-col>
                            <v-col v-if="form.appliesKind !== 'All products'" cols="12" sm="6"><v-select v-model="form.appliesList" :items="form.appliesKind === 'Brands' ? brands : categories" :label="form.appliesKind === 'Brands' ? 'Choose brands' : 'Choose categories'" multiple chips closable-chips hide-details :menu-props="overlayMenu" /></v-col>
                        </v-row>

                        <div class="tt-field-label">Limits and dates</div>
                        <v-row dense>
                            <v-col cols="6" sm="3"><v-text-field v-model.number="form.limit" type="number" label="Total uses" hint="0 = no limit" persistent-hint /></v-col>
                            <v-col cols="6" sm="3"><v-text-field v-model.number="form.perCustomer" type="number" label="Per customer" hint="0 = no limit" persistent-hint /></v-col>
                            <v-col cols="6" sm="3"><v-text-field v-model="form.start" type="date" label="Starts" hide-details="auto" /></v-col>
                            <v-col cols="6" sm="3"><v-text-field v-model="form.end" type="date" :min="form.start" label="Ends (optional)" hide-details="auto" :error-messages="touched ? dateError : ''" /></v-col>
                        </v-row>
                        <div class="tt-switch-row">
                            <div><b>Can be used with other offers</b><span>Turn off to stop it stacking with automatic discounts</span></div>
                            <v-switch v-model="form.stack" color="#0f9d6b" density="compact" hide-details inset />
                        </div>
                    </div>

                    <div class="tt-dialog-foot">
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-icon-btn" @click="editorOpen = false">Cancel</v-btn>
                        <v-btn variant="flat" color="#0f9d6b" class="text-none tt-send-btn" :loading="saving" prepend-icon="mdi-content-save-outline" @click="save">Save coupon</v-btn>
                    </div>
                </div>
            </v-dialog>

            <!-- ═════════ Delete confirm ═════════ -->
            <v-dialog :model-value="!!deleting" max-width="420" content-class="tt-overlay" @update:model-value="deleting = null">
                <div v-if="deleting" class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg tt-head-ic--red"><v-icon size="20">mdi-delete-outline</v-icon></div>
                        <div class="ml-3"><div class="tt-info-title">Delete {{ deleting.code }}?</div><div class="tt-subt">Customers who type it will see "invalid code". Past orders are not affected. To stop it for now, pause it instead.</div></div>
                    </div>
                    <div class="tt-dialog-foot">
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-icon-btn" @click="deleting = null">Keep it</v-btn>
                        <v-btn variant="flat" color="#dc2626" class="text-none tt-send-btn" @click="confirmDelete">Delete coupon</v-btn>
                    </div>
                </div>
            </v-dialog>

            <v-snackbar v-model="snackbar" color="#0a5548" timeout="3500">{{ snackbarText }}</v-snackbar>
        </div>
    </v-defaults-provider>
</template>

<script>
const C = { teal: "#0f9d6b", blue: "#2f5be7", amber: "#f59e0b", coral: "#ef6a45" };
const pad = (n) => String(n).padStart(2, "0");
const keyOf = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const addDays = (d, n) => {
    const x = new Date(d);
    x.setDate(x.getDate() + n);
    return x;
};
const GROSS_MARGIN = 34.8; // from the dashboard's product profit card: replace with a live figure
const DELIVERY_FEE = 70; // Dhaka delivery charge, used to estimate what free delivery costs
const blank = () => ({ id: null, code: "", name: "", type: "percent", value: 10, cap: 0, minOrder: 0, eligibility: "Everyone", appliesKind: "All products", appliesList: [], limit: 0, perCustomer: 1, start: keyOf(new Date()), end: "", stack: false, paused: false, used: 0, revenue: 0 });

export default {
    name: "CouponsDiscounts",

    data() {
        const at = (n) => keyOf(addDays(new Date(), n));
        return {
            fieldDefaults: {
                VTextField: { variant: "outlined", density: "compact" },
                VSelect: { variant: "outlined", density: "compact" },
            },
            overlayMenu: { contentClass: "tt-overlay" },
            snackbar: false,
            snackbarText: "",
            tones: { Active: "green", Scheduled: "blue", Expired: "gray", Paused: "amber" },
            accents: ["a-teal", "a-blue", "a-amber", "a-coral"],
            bestColors: ["#0f9d6b", "#2f5be7", "#7c3aed"],

            view: "codes",
            views: [{ value: "codes", label: "Coupon codes" }, { value: "automatic", label: "Automatic" }],
            search: "",
            statusFilter: "All",
            statusTabs: ["All", "Active", "Scheduled", "Paused", "Expired"],
            page: 1,
            perPage: 6,

            editorOpen: false,
            touched: false,
            saving: false,
            form: blank(),
            deleting: null,
            nextId: 100,

            types: [
                { value: "percent", label: "Percentage off", hint: "Such as 10% off", icon: "mdi-percent-outline" },
                { value: "fixed", label: "Fixed amount off", hint: "Such as ৳ 300 off", icon: "mdi-cash-minus" },
                { value: "shipping", label: "Free delivery", hint: "Removes the delivery fee", icon: "mdi-truck-fast-outline" },
                { value: "cashback", label: "bKash cashback", hint: "Paid back to the wallet", icon: "mdi-cellphone-check" },
            ],
            eligibility: ["Everyone", "New customers", "VIP customers", "Dhaka customers", "Wholesale buyers"],
            categories: ["Power banks", "Chargers", "Earbuds", "Headphones", "Smart watches", "Cables", "Gaming", "Networking", "Storage", "Speakers"],
            brands: ["Anker", "Baseus", "Xiaomi", "JBL", "Ugreen", "Realme", "Logitech", "TP-Link", "SanDisk"],

            // replace with GET /api/admin/coupons (dates as YYYY-MM-DD)
            coupons: [
                { id: 1, code: "NEWUSER10", name: "First order welcome", type: "percent", value: 10, cap: 500, minOrder: 1000, eligibility: "New customers", appliesKind: "All products", appliesList: [], used: 286, limit: 0, perCustomer: 1, revenue: 684000, start: at(-120), end: "", stack: false, paused: false },
                { id: 2, code: "EID26", name: "Eid offer", type: "percent", value: 15, cap: 1500, minOrder: 2000, eligibility: "Everyone", appliesKind: "All products", appliesList: [], used: 412, limit: 500, perCustomer: 1, revenue: 1240000, start: at(-200), end: at(-150), stack: false, paused: false },
                { id: 3, code: "POWER300", name: "Power bank saver", type: "fixed", value: 300, cap: 0, minOrder: 3000, eligibility: "Everyone", appliesKind: "Categories", appliesList: ["Power banks"], used: 148, limit: 300, perCustomer: 2, revenue: 392000, start: at(-30), end: at(14), stack: false, paused: false },
                { id: 4, code: "AUDIO20", name: "Audio week", type: "percent", value: 20, cap: 1000, minOrder: 1500, eligibility: "Everyone", appliesKind: "Categories", appliesList: ["Earbuds", "Headphones"], used: 96, limit: 0, perCustomer: 1, revenue: 251000, start: at(-14), end: at(7), stack: false, paused: false },
                { id: 5, code: "FREESHIP", name: "Free delivery over ৳ 3,000", type: "shipping", value: 0, cap: 0, minOrder: 3000, eligibility: "Everyone", appliesKind: "All products", appliesList: [], used: 530, limit: 0, perCustomer: 0, revenue: 1580000, start: at(-90), end: "", stack: true, paused: false },
                { id: 6, code: "WATCH500", name: "Smart watch launch", type: "fixed", value: 500, cap: 0, minOrder: 4000, eligibility: "VIP customers", appliesKind: "Categories", appliesList: ["Smart watches"], used: 0, limit: 100, perCustomer: 1, revenue: 0, start: at(9), end: at(30), stack: false, paused: false },
                { id: 7, code: "GAMER12", name: "Gaming gear", type: "percent", value: 12, cap: 800, minOrder: 1500, eligibility: "Everyone", appliesKind: "Categories", appliesList: ["Gaming"], used: 73, limit: 0, perCustomer: 1, revenue: 264000, start: at(-45), end: "", stack: false, paused: true },
                { id: 8, code: "BKASH5", name: "bKash cashback", type: "cashback", value: 5, cap: 200, minOrder: 1000, eligibility: "Everyone", appliesKind: "All products", appliesList: [], used: 620, limit: 0, perCustomer: 0, revenue: 1910000, start: at(-60), end: at(40), stack: true, paused: false },
                { id: 9, code: "WINTER26", name: "Winter sale", type: "percent", value: 8, cap: 600, minOrder: 2000, eligibility: "Everyone", appliesKind: "All products", appliesList: [], used: 0, limit: 0, perCustomer: 1, revenue: 0, start: at(25), end: at(55), stack: false, paused: false },
                { id: 10, code: "FLASH24", name: "24 hour flash sale", type: "percent", value: 25, cap: 2000, minOrder: 2500, eligibility: "Everyone", appliesKind: "All products", appliesList: [], used: 188, limit: 200, perCustomer: 1, revenue: 540000, start: at(-8), end: at(-7), stack: false, paused: false },
            ],

            // replace with GET /api/admin/discounts/automatic
            automatic: [
                { id: 1, name: "Free delivery over ৳ 3,000", rule: "Cart total ৳ 3,000 or more", icon: "mdi-truck-fast-outline", desc: "Takes the delivery fee off big orders so customers add one more item.", orders: 530, given: 530 * DELIVERY_FEE, on: true },
                { id: 2, name: "Charger and cable bundle", rule: "Charger and cable in the same cart", icon: "mdi-package-variant-closed", desc: "10% off the cable when it is bought with any charger or power bank.", orders: 184, given: 38400, on: true },
                { id: 3, name: "Pay with bKash", rule: "Payment by bKash", icon: "mdi-cellphone-check", desc: "5% cashback up to ৳ 200 on orders paid through bKash.", orders: 620, given: 95500, on: true },
                { id: 4, name: "Buy 2 earbuds, save 8%", rule: "Two earbuds in the cart", icon: "mdi-headphones", desc: "Encourages gifting and sharing. Applies to all earbuds.", orders: 47, given: 9100, on: false },
            ],
        };
    },

    computed: {
        activeCount() {
            return this.coupons.filter((c) => this.status(c) === "Active").length;
        },
        heroTiles() {
            const used = this.coupons.reduce((a, c) => a + c.used, 0);
            const revenue = this.coupons.reduce((a, c) => a + c.revenue, 0);
            const given = this.coupons.reduce((a, c) => a + this.costOf(c), 0);
            const pct = revenue ? (given / revenue) * 100 : 0;
            const scheduled = this.coupons.filter((c) => this.status(c) === "Scheduled").length;
            return [
                { icon: "mdi-ticket-percent-outline", color: C.teal, value: this.activeCount, label: "Live coupons", tag: scheduled + " scheduled" },
                { icon: "mdi-cart-check", color: C.blue, value: used.toLocaleString("en-IN"), label: "Times used", tag: "All time" },
                { icon: "mdi-cash-multiple", color: C.amber, value: this.money(revenue), label: "Orders with a coupon", tag: "All time" },
                { icon: "mdi-tag-minus-outline", color: C.coral, value: this.money(Math.round(given)), label: "Estimated discount given", tag: pct.toFixed(1) + "% of orders", todo: pct > GROSS_MARGIN * 0.3 },
            ];
        },
        bestCoupons() {
            return [...this.coupons].sort((a, b) => b.revenue - a.revenue).slice(0, 3);
        },
        filtered() {
            const q = (this.search || "").trim().toLowerCase();
            return this.coupons.filter((c) => (this.statusFilter === "All" || this.status(c) === this.statusFilter) && (!q || c.code.toLowerCase().includes(q) || (c.name || "").toLowerCase().includes(q)));
        },
        paged() {
            return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage);
        },
        pageCount() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },
        showing() {
            const n = this.filtered.length;
            if (!n) return "No results";
            return `Showing ${(this.page - 1) * this.perPage + 1}–${Math.min(this.page * this.perPage, n)} of ${n}`;
        },

        // editor
        previewText() {
            const f = this.form;
            return this.discountText(f);
        },
        previewSub() {
            const f = this.form;
            const parts = [f.minOrder ? "Orders over " + this.money(f.minOrder) : "No minimum order", f.appliesKind === "All products" ? "All products" : (f.appliesList.join(", ") || f.appliesKind), f.eligibility];
            return parts.join(" · ");
        },
        codeError() {
            const c = this.form.code;
            if (!/^[A-Z0-9]{4,16}$/.test(c)) return "Use 4 to 16 letters or numbers";
            if (this.coupons.some((x) => x.code === c && x.id !== this.form.id)) return "This code already exists";
            return "";
        },
        valueError() {
            const f = this.form;
            if (f.type === "shipping") return "";
            if (!(f.value > 0)) return "Enter a value above 0";
            if (f.type !== "fixed" && f.value > 100) return "A percentage can't be above 100";
            return "";
        },
        dateError() {
            return this.form.end && this.form.start && this.form.end < this.form.start ? "The end date is before the start date" : "";
        },
        marginWarning() {
            const f = this.form;
            if ((f.type === "percent" || f.type === "cashback") && f.value > GROSS_MARGIN * 0.7) {
                return `Your gross margin is about ${GROSS_MARGIN}%. A ${f.value}% discount leaves very little profit${f.cap ? "" : ", so consider setting a maximum amount"}.`;
            }
            return "";
        },
    },

    methods: {
        money: (v) => "৳ " + Number(v || 0).toLocaleString("en-IN"),
        notify(text) {
            this.snackbarText = text;
            this.snackbar = true;
        },
        fmtDate(k) {
            const [y, m, d] = k.split("-").map(Number);
            return new Date(y, m - 1, d).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },
        discountText(c) {
            if (c.type === "percent") return `${c.value || 0}% off`;
            if (c.type === "fixed") return `${this.money(c.value)} off`;
            if (c.type === "shipping") return "Free delivery";
            return `${c.value || 0}% bKash cashback`;
        },
        status(c) {
            const today = keyOf(new Date());
            if (c.end && c.end < today) return "Expired";
            if (c.limit && c.used >= c.limit) return "Expired";
            if (c.paused) return "Paused";
            if (c.start && c.start > today) return "Scheduled";
            return "Active";
        },
        usedPct(c) {
            if (c.limit) return Math.min(100, Math.round((c.used / c.limit) * 100));
            return Math.min(100, Math.round((c.used / 600) * 100));
        },
        // rough cost of a coupon to the shop, for the summary tile
        costOf(c) {
            if (c.type === "percent" || c.type === "cashback") return (c.revenue * c.value) / 100;
            if (c.type === "fixed") return c.used * c.value;
            return c.used * DELIVERY_FEE;
        },
        setStatus(s) {
            this.statusFilter = s;
            this.page = 1;
        },
        genCode() {
            const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
            return "TT" + Array.from({ length: 6 }, () => chars[Math.floor(Math.random() * chars.length)]).join("");
        },
        async copy(code) {
            try {
                await navigator.clipboard.writeText(code);
                this.notify(`${code} copied.`);
            } catch (e) {
                this.notify(`Copy this code: ${code}`);
            }
        },

        openNew() {
            this.form = blank();
            this.touched = false;
            this.editorOpen = true;
        },
        openEdit(c) {
            this.form = { ...blank(), ...c, appliesList: [...c.appliesList] };
            this.touched = false;
            this.editorOpen = true;
        },
        save() {
            this.touched = true;
            if (this.codeError || this.valueError || this.dateError) return;
            this.saving = true;
            try {
                const f = { ...this.form, value: this.form.type === "shipping" ? 0 : Number(this.form.value), appliesList: this.form.appliesKind === "All products" ? [] : this.form.appliesList };
                // POST /api/admin/coupons (or PUT /:id)
                if (f.id) {
                    const i = this.coupons.findIndex((c) => c.id === f.id);
                    this.coupons.splice(i, 1, f);
                } else {
                    this.coupons.unshift({ ...f, id: ++this.nextId });
                }
                this.editorOpen = false;
                this.notify(`${f.code} saved.`);
            } finally {
                this.saving = false;
            }
        },
        togglePause(c) {
            c.paused = !c.paused; // PATCH /api/admin/coupons/:id { paused }
            this.notify(`${c.code} ${c.paused ? "paused. Customers can't use it." : "is live again."}`);
        },
        confirmDelete() {
            this.coupons = this.coupons.filter((c) => c.id !== this.deleting.id); // DELETE /api/admin/coupons/:id
            this.deleting = null;
            this.page = Math.min(this.page, this.pageCount);
            this.notify("Coupon deleted.");
        },
        toggleAuto(a) {
            // PATCH /api/admin/discounts/automatic/:id { on }
            this.notify(`${a.name} is ${a.on ? "on" : "off"}.`);
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

/* Same tokens as the Dashboard, Inbox and Create Post pages. Vuetify colour tokens are pinned so inputs stay dark on white. */
.tt-page {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
    --bg2: #f7faf9;
    --v-theme-on-surface: 15, 42, 38;
    --v-theme-on-background: 15, 42, 38;
    --v-theme-surface: 255, 255, 255;
    --v-theme-background: 243, 247, 246;
    --v-theme-primary: 15, 157, 107;
    --v-theme-on-primary: 255, 255, 255;
    --v-border-color: 15, 42, 38;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    background: var(--bg);
    padding-bottom: 32px;
    font-variant-numeric: tabular-nums;
    -webkit-font-smoothing: antialiased;
}
.tt-page :deep(.v-btn) { letter-spacing: 0; }
.tt-subt { font-size: 12px; color: var(--muted); }
.tt-muted { color: var(--muted); }
.tt-strong { font-weight: 600; color: var(--ink); }

/* ───────── Hero ───────── */
.tt-hero { position: relative; overflow: hidden; color: #fff; padding: 18px 22px; border-radius: 18px; background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%); box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95); }
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.15; letter-spacing: -0.5px; color: #fff; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.22); color: #fff; }
.tt-pill-live { color: #6ee7b7; }
.tt-pulse { width: 7px; height: 7px; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }
.tt-seg { display: inline-flex; padding: 3px; border-radius: 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-seg button { border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: rgba(255, 255, 255, 0.75); padding: 6px 15px; border-radius: 9px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-seg button:hover { color: #fff; }
.tt-seg button.on { background: rgba(255, 255, 255, 0.22); color: #fff; font-weight: 600; }
.tt-seg button:focus-visible { outline: 2px solid #6ee7b7; outline-offset: 2px; }
.tt-seg-light { background: #eef3f1; border-color: var(--line); }
.tt-seg-light button { color: var(--muted); padding: 5px 13px; font-size: 12.5px; }
.tt-seg-light button:hover { color: var(--ink); }
.tt-seg-light button.on { background: #fff; color: #0a5548; box-shadow: 0 1px 3px rgba(15, 42, 38, 0.14); }
.tt-seg-light button:focus-visible { outline-color: #0f9d6b; }
.tt-btn-ghost { height: 38px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 38px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 13px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.22; filter: blur(10px); }
.tt-hero-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-txt { flex: 1; min-width: 0; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.3px; white-space: nowrap; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.82); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tt-live { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(110, 231, 183, 0.5); white-space: nowrap; }
.tt-live.todo { color: #fcd34d; background: rgba(245, 158, 11, 0.16); border-color: rgba(252, 211, 77, 0.5); }
@media (max-width: 1199px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Cards ───────── */
.tt-card { --accent: #2f5be7; --tint: #e6edff; position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35); overflow: hidden; color: var(--ink); }
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, var(--accent), transparent 85%); }
.a-blue { --accent: #2f5be7; --tint: #e6edff; }
.a-teal { --accent: #0f9d6b; --tint: #dff5ec; }
.a-coral { --accent: #ef6a45; --tint: #fce1d8; }
.a-amber { --accent: #f59e0b; --tint: #fdecc8; }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 6px; }
.tt-head-wrap { flex-wrap: wrap; padding-bottom: 12px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-head-ic--lg { width: 42px; height: 42px; border-radius: 12px; background: #dff5ec; color: #0f9d6b; }
.tt-head-ic--red { background: #fce1d8; color: #b4381a; }
.tt-title { font-size: 14px; font-weight: 600; line-height: 1.3; }
.tt-body { padding: 10px 18px 18px; }
.tt-search { max-width: 260px; min-width: 180px; }
.tt-search :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-icon-btn { border-color: var(--line) !important; color: #3d5450 !important; border-radius: 10px !important; font-weight: 600; }
.tt-send-btn { color: #fff !important; font-weight: 600; border-radius: 10px !important; }
.tt-link { color: #0a5548 !important; font-weight: 600; }
.tt-chip { display: inline-block; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; white-space: nowrap; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-blue { background: #e6edff; color: #1f4e9c; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.type-percent { background: #ebe5ff; color: #5b3fd1; }
.type-fixed { background: #e6edff; color: #1f4e9c; }
.type-shipping { background: #d3f1f2; color: #0b6b72; }
.type-cashback { background: #fbe1ec; color: #a21d58; }

/* ───────── Best performers ───────── */
.best-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.best { --c: #0f9d6b; display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #fff; border: 1px solid var(--line); border-left: 4px solid var(--c); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05); min-width: 0; }
.best-rank { width: 28px; height: 28px; border-radius: 50%; background: var(--c); color: #fff; display: grid; place-items: center; font-size: 12.5px; font-weight: 700; flex: none; }
.best-main { flex: 1; min-width: 0; }
.best-code { font-weight: 700; font-size: 14px; letter-spacing: 0.4px; }
.best-main .tt-subt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.best-rev { text-align: right; line-height: 1.3; }
.best-rev b { display: block; font-size: 14px; }
.best-rev small { font-size: 11px; color: var(--muted); }
@media (max-width: 959px) { .best-grid { grid-template-columns: 1fr; } }

/* ───────── Table ───────── */
.tt-table { background: transparent !important; }
.tt-table th { font-size: 12px !important; font-weight: 600 !important; background: #0a5548 !important; color: #e6f4ef !important; white-space: nowrap; border-bottom: 0 !important; height: 42px !important; padding: 0 14px !important; }
.tt-table td { font-size: 13.5px; border-bottom: 1px solid #eef3f1 !important; height: 62px !important; padding: 0 14px !important; }
.tt-table tbody tr:hover { background: #f4f9f7; }
.tt-table td.text-right, .tt-table th.text-right { white-space: nowrap; }
.code-cell { display: flex; align-items: center; gap: 4px; }
.code-badge { display: inline-block; font-weight: 700; font-size: 12.5px; letter-spacing: 0.5px; padding: 2px 10px; border-radius: 8px; background: #f0faf5; border: 1.5px dashed #7fd8b2; color: #0a5548; }
.code-badge.big { font-size: 15px; padding: 6px 14px; }
.who { max-width: 230px; line-height: 1.4; }
.who .tt-subt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-minibar { display: flex; align-items: center; gap: 8px; min-width: 130px; }
.tt-minibar-track { flex: 1; height: 6px; border-radius: 6px; background: #e8eeec; overflow: hidden; }
.tt-minibar-track span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0f9d6b, #6ee7b7); }
.tt-minibar b { font-size: 12px; white-space: nowrap; }
.actions { display: flex; align-items: center; justify-content: flex-end; gap: 0; height: 62px; }
.actions :deep(.v-switch) { flex: none; margin-right: 4px; }
.tt-foot { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 6px 14px; border-top: 1px solid var(--line); background: var(--bg2); }

/* ───────── Automatic discounts ───────── */
.auto-intro { margin: 0 2px 12px; }
.auto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
.auto-title { min-width: 0; }
.auto-desc { font-size: 13px; line-height: 1.55; margin: 4px 0 12px; min-height: 40px; }
.auto-meta { display: flex; align-items: center; gap: 22px; padding-top: 10px; border-top: 1px dashed #d5dfdc; }
.auto-meta small { display: block; font-size: 11px; color: var(--muted); }
.auto-meta b { font-size: 14px; }
.auto-meta .tt-chip { margin-left: auto; }

/* ───────── Dialogs ───────── */
.tt-dialog { position: relative; background: #fff; color: var(--ink); border-radius: 16px; overflow: hidden; font-family: Poppins, "Segoe UI", sans-serif; display: flex; flex-direction: column; max-height: 92vh; }
.tt-dialog::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-dialog-head { display: flex; align-items: center; padding: 20px 20px 14px; border-bottom: 1px solid var(--line); }
.tt-info-title { font-size: 15px; font-weight: 600; }
.tt-dialog-body { padding: 16px 20px 8px; overflow-y: auto; }
.tt-dialog-foot { display: flex; align-items: center; gap: 8px; padding: 12px 20px 16px; border-top: 1px solid var(--line); }
.preview-ticket { display: flex; align-items: center; gap: 14px; padding: 12px 14px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); }
.preview-ticket b { font-size: 14px; }
.tt-field-label { font-size: 12.5px; font-weight: 600; margin: 18px 0 10px; padding-top: 14px; border-top: 1px dashed #d5dfdc; }
.type-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.type { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; padding: 10px 11px; border: 1.5px solid var(--line); border-radius: 12px; background: #fff; font: inherit; color: var(--ink); text-align: left; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.type:hover { border-color: #b7e6cd; }
.type:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.type.on { border-color: #0f9d6b; background: #f2fbf7; }
.type .v-icon { color: #0f9d6b; margin-bottom: 4px; }
.type b { font-size: 12.5px; font-weight: 600; }
.type small { font-size: 11px; color: var(--muted); line-height: 1.35; }
@media (max-width: 640px) { .type-grid { grid-template-columns: repeat(2, 1fr); } }
.ed-note { display: flex; align-items: flex-start; gap: 8px; margin-top: 10px; padding: 9px 12px; border-radius: 10px; font-size: 12px; line-height: 1.5; }
.ed-note.warn { background: #fff8e8; border: 1px solid #f3e3b8; color: #7a4f00; }
.tt-switch-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 0 4px; margin-top: 8px; border-top: 1px dashed #d5dfdc; }
.tt-switch-row b { font-size: 12.8px; font-weight: 600; display: block; }
.tt-switch-row span { font-size: 11.5px; color: var(--muted); }
@media (prefers-reduced-motion: reduce) { .type, .tt-seg button { transition: none; } }
</style>

<!-- Menus and dialogs open outside the page, so they need their own pinned colours -->
<style>
.tt-overlay {
    --v-theme-on-surface: 15, 42, 38;
    --v-theme-surface: 255, 255, 255;
    --v-theme-primary: 15, 157, 107;
    --v-border-color: 15, 42, 38;
    color: #0f2a26;
    font-family: Poppins, "Segoe UI", sans-serif;
}
.tt-overlay .v-list { background: #fff; color: #0f2a26; border-radius: 12px; }
.tt-overlay .v-list-item--active { color: #0a5548; background: #eaf8f1; }
</style>
