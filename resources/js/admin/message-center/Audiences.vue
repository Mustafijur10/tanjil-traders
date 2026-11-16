<template>
    <v-defaults-provider :defaults="fieldDefaults">
        <div class="tt-page">
            <!-- ═════════ Hero ═════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="d-flex flex-wrap align-center ga-3">
                            <h1 class="tt-h1">Audiences</h1>
                            <span class="tt-pill"><v-icon size="13">mdi-account-group-outline</v-icon>{{ audiences.length }} audiences</span>
                        </div>
                        <div class="tt-hero-sub">Group customers so every message reaches the right people</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-file-upload-outline" @click="$refs.importInput.click()">Import list</v-btn>
                        <input ref="importInput" type="file" accept=".csv" hidden @change="onImport" />
                        <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="openNew">New audience</v-btn>
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
                        <div v-if="h.progress != null" class="tt-hero-progress"><span :style="{ width: h.progress + '%' }"></span></div>
                    </div>
                </div>
            </header>

            <div class="tt-layout">
                <!-- ═════════════ LIST ═════════════ -->
                <div class="tt-main">
                    <section class="tt-card a-teal">
                        <div class="tt-head tt-head-wrap">
                            <div class="tt-head-ic"><v-icon size="16">mdi-account-multiple-outline</v-icon></div>
                            <div><div class="tt-title">Your audiences</div><div class="tt-subt">Pick one to see who is in it</div></div>
                            <v-spacer />
                            <v-text-field v-model="search" class="tt-search" hide-details clearable prepend-inner-icon="mdi-magnify" placeholder="Search audiences" />
                            <div class="tt-seg" role="tablist" aria-label="Audience type">
                                <button v-for="f in typeFilters" :key="f.value" type="button" role="tab" :aria-selected="typeFilter === f.value" :class="{ on: typeFilter === f.value }" @click="typeFilter = f.value">{{ f.label }}</button>
                            </div>
                        </div>
                        <div class="tt-body">
                            <div
                                v-for="a in filtered"
                                :key="a.id"
                                class="aud"
                                :class="{ on: sel && sel.id === a.id }"
                                :style="{ '--c': a.color }"
                                role="button"
                                tabindex="0"
                                :aria-pressed="!!sel && sel.id === a.id"
                                @click="sel = a"
                                @keydown.enter="sel = a"
                            >
                                <span class="aud-ic"><v-icon size="18" color="white">{{ a.icon }}</v-icon></span>
                                <div class="aud-main">
                                    <div class="aud-name">
                                        <b>{{ a.name }}</b>
                                        <span class="tt-chip" :class="a.type === 'auto' ? 'tone-blue' : 'tone-gray'">{{ a.type === "auto" ? "Updates itself" : "Imported list" }}</span>
                                    </div>
                                    <div class="tt-subt aud-desc">{{ a.desc }}</div>
                                    <div class="aud-bar"><span :style="{ width: Math.max(3, Math.round((a.sms / maxSms) * 100)) + '%' }"></span></div>
                                </div>
                                <div class="aud-nums">
                                    <b>{{ a.sms.toLocaleString("en-IN") }}</b>
                                    <small>can get SMS</small>
                                </div>
                                <div class="aud-nums">
                                    <b>{{ a.email.toLocaleString("en-IN") }}</b>
                                    <small>can get email</small>
                                </div>
                                <span class="aud-growth" :class="a.growth >= 0 ? 'up' : 'down'"><v-icon size="12">{{ a.growth >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(a.growth) }}%</span>
                            </div>

                            <div v-if="!filtered.length" class="tt-empty">
                                <div class="tt-empty-ic"><v-icon size="26" color="#0f9d6b">mdi-account-search-outline</v-icon></div>
                                <div class="tt-strong mt-3">No audiences match</div>
                                <div class="tt-subt">Clear the search or create a new audience.</div>
                            </div>
                        </div>
                    </section>

                    <section class="tt-card a-amber">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-shield-check-outline</v-icon></div>
                            <div><div class="tt-title">Who can receive your messages</div><div class="tt-subt">Only customers who agreed are ever included</div></div>
                        </div>
                        <div class="tt-body">
                            <div class="consent">
                                <div v-for="c in consent" :key="c.label" class="consent-item">
                                    <div class="d-flex align-center justify-space-between"><span class="tt-strong">{{ c.label }}</span><b>{{ c.pct }}%</b></div>
                                    <div class="tt-meter"><span :style="{ width: c.pct + '%', background: c.color }"></span></div>
                                    <small class="tt-subt">{{ c.note }}</small>
                                </div>
                            </div>
                            <div class="tt-note"><v-icon size="15">mdi-information-outline</v-icon>Customers who unsubscribe are removed from every audience automatically. Collect consent at checkout to grow these numbers.</div>
                        </div>
                    </section>
                </div>

                <!-- ═════════════ DETAIL ═════════════ -->
                <aside class="tt-side">
                    <section v-if="sel" class="tt-card a-blue tt-sticky">
                        <div class="tt-head">
                            <span class="detail-ic" :style="{ background: sel.color }"><v-icon size="18" color="white">{{ sel.icon }}</v-icon></span>
                            <div class="detail-title"><div class="tt-title">{{ sel.name }}</div><div class="tt-subt">Updated {{ sel.updated }}</div></div>
                        </div>
                        <div class="tt-body">
                            <div class="tt-tiles">
                                <div class="tt-tile" style="--c: #0f9d6b"><small>SMS reach</small><b>{{ sel.sms.toLocaleString("en-IN") }}</b></div>
                                <div class="tt-tile" style="--c: #7c3aed"><small>Email reach</small><b>{{ sel.email.toLocaleString("en-IN") }}</b></div>
                            </div>

                            <div class="tt-field-label">Who is in it</div>
                            <div v-if="sel.rules.length" class="rule-list">
                                <span v-for="(r, i) in sel.rules" :key="i" class="rule-chip">{{ ruleText(r) }}</span>
                                <div class="tt-subt mt-1">Customers must match all of these.</div>
                            </div>
                            <div v-else class="tt-subt">{{ sel.type === "manual" ? "Added from a file. It does not change on its own." : "Every customer who agreed to receive messages." }}</div>

                            <div class="tt-field-label">Top cities</div>
                            <div v-for="c in sel.cities" :key="c.name" class="city-row">
                                <span>{{ c.name }}</span>
                                <div class="tt-meter"><span :style="{ width: c.pct + '%' }"></span></div>
                                <b>{{ c.pct }}%</b>
                            </div>

                            <div class="tt-field-label">Some customers in this audience</div>
                            <div v-for="p in sampleCustomers" :key="p.name" class="cust">
                                <span class="cust-av">{{ p.name[0] }}</span>
                                <div class="cust-main"><div class="tt-strong">{{ p.name }}</div><div class="tt-subt">{{ p.city }}</div></div>
                                <span class="tt-subt">{{ money(p.spent) }}</span>
                            </div>

                            <v-btn block variant="flat" color="#0f9d6b" class="text-none tt-publish" prepend-icon="mdi-send" @click="sendCampaign(sel)">Send a campaign</v-btn>
                            <div class="d-flex ga-2 mt-2">
                                <v-btn variant="outlined" class="text-none tt-half" prepend-icon="mdi-pencil-outline" :disabled="sel.system || sel.type === 'manual'" @click="openEdit(sel)">Edit rules</v-btn>
                                <v-btn variant="outlined" class="text-none tt-half" prepend-icon="mdi-download-outline" @click="exportCsv(sel)">Export</v-btn>
                            </div>
                            <v-btn v-if="!sel.system" block variant="text" class="text-none mt-1" color="#b4381a" prepend-icon="mdi-delete-outline" @click="deleting = sel">Delete audience</v-btn>
                        </div>
                    </section>
                    <section v-else class="tt-card a-blue">
                        <div class="tt-body tt-empty"><div class="tt-empty-ic"><v-icon size="26" color="#0f9d6b">mdi-cursor-default-click-outline</v-icon></div><div class="tt-strong mt-3">Select an audience</div><div class="tt-subt">You will see its rules, cities and customers here.</div></div>
                    </section>
                </aside>
            </div>

            <!-- ═════════ Builder ═════════ -->
            <v-dialog v-model="builderOpen" max-width="720" scrollable content-class="tt-overlay">
                <div class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg"><v-icon size="20">mdi-filter-variant</v-icon></div>
                        <div class="ml-3"><div class="tt-info-title">{{ form.id ? "Edit audience" : "New audience" }}</div><div class="tt-subt">It updates itself as customers change</div></div>
                        <v-spacer />
                        <v-btn icon size="small" variant="text" aria-label="Close" @click="builderOpen = false"><v-icon size="18">mdi-close</v-icon></v-btn>
                    </div>
                    <div class="tt-dialog-body">
                        <v-text-field v-model="form.name" label="Audience name" prepend-inner-icon="mdi-tag-outline" hide-details="auto" :error-messages="touched && !form.name.trim() ? 'Give the audience a name' : ''" />

                        <div class="tt-field-label">Customers who match all of these</div>
                        <div v-for="(r, i) in form.rules" :key="i" class="rule-row">
                            <v-select v-model="r.field" :items="fields" item-title="label" item-value="value" hide-details :menu-props="overlayMenu" aria-label="Field" @update:model-value="resetRule(r)" />
                            <v-select v-model="r.op" :items="opsFor(r.field)" item-title="label" item-value="value" hide-details :menu-props="overlayMenu" aria-label="Condition" />
                            <v-text-field v-model="r.value" :type="typeOf(r.field) === 'num' ? 'number' : 'text'" :prefix="r.field === 'spent' ? '৳' : ''" hide-details placeholder="Value" aria-label="Value" />
                            <v-btn icon variant="text" size="small" aria-label="Remove rule" :disabled="form.rules.length === 1" @click="form.rules.splice(i, 1)"><v-icon size="18" color="#5c726e">mdi-close</v-icon></v-btn>
                        </div>
                        <div v-if="touched && !validRules.length" class="tt-warn"><v-icon size="15" color="#b4381a">mdi-alert-circle-outline</v-icon> Add at least one complete rule.</div>
                        <v-btn variant="text" size="small" class="text-none mt-1 tt-link" prepend-icon="mdi-plus" @click="form.rules.push(newRule())">Add another rule</v-btn>

                        <div class="estimate">
                            <div><small>About this many people</small><b>{{ estimate.sms.toLocaleString("en-IN") }}</b><span>by SMS</span></div>
                            <div><b>{{ estimate.email.toLocaleString("en-IN") }}</b><span>by email</span></div>
                            <div class="tt-subt est-note">A rough count for now. The exact number appears after you save.</div>
                        </div>
                    </div>
                    <div class="tt-dialog-foot">
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-icon-btn" @click="builderOpen = false">Cancel</v-btn>
                        <v-btn variant="flat" color="#0f9d6b" class="text-none tt-send-btn" prepend-icon="mdi-content-save-outline" @click="save">Save audience</v-btn>
                    </div>
                </div>
            </v-dialog>

            <!-- ═════════ Delete confirm ═════════ -->
            <v-dialog :model-value="!!deleting" max-width="420" content-class="tt-overlay" @update:model-value="deleting = null">
                <div v-if="deleting" class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg tt-head-ic--red"><v-icon size="20">mdi-delete-outline</v-icon></div>
                        <div class="ml-3"><div class="tt-info-title">Delete "{{ deleting.name }}"?</div><div class="tt-subt">The customers stay in your store. Campaigns and automations that use this audience will need a new one.</div></div>
                    </div>
                    <div class="tt-dialog-foot">
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-icon-btn" @click="deleting = null">Keep it</v-btn>
                        <v-btn variant="flat" color="#dc2626" class="text-none tt-send-btn" @click="confirmDelete">Delete audience</v-btn>
                    </div>
                </div>
            </v-dialog>

            <v-snackbar v-model="snackbar" color="#0a5548" timeout="3500">{{ snackbarText }}</v-snackbar>
        </div>
    </v-defaults-provider>
</template>

<script>
const C = { teal: "#0f9d6b", blue: "#2f5be7", violet: "#7c3aed", amber: "#f59e0b" };
const PEOPLE = [
    { name: "Rahim Traders", city: "Dhanmondi, Dhaka", spent: 186400 },
    { name: "Nusrat Jahan", city: "Uttara, Dhaka", spent: 22100 },
    { name: "Karim Electronics", city: "Rajshahi", spent: 512000 },
    { name: "Sabbir Hossain", city: "Chattogram", spent: 12900 },
    { name: "Farhan Kabir", city: "Sylhet", spent: 64200 },
    { name: "Tania Islam", city: "Barishal", spent: 5600 },
    { name: "Ayesha Rahman", city: "Khulna", spent: 4800 },
    { name: "Mehedi Hasan", city: "Mirpur, Dhaka", spent: 9300 },
];

export default {
    name: "Audiences",

    data() {
        return {
            fieldDefaults: {
                VTextField: { variant: "outlined", density: "compact" },
                VSelect: { variant: "outlined", density: "compact" },
            },
            overlayMenu: { contentClass: "tt-overlay" },
            snackbar: false,
            snackbarText: "",

            search: "",
            typeFilter: "all",
            typeFilters: [{ value: "all", label: "All" }, { value: "auto", label: "Updates itself" }, { value: "manual", label: "Imported" }],
            sel: null,
            deleting: null,
            builderOpen: false,
            touched: false,
            nextId: 100,
            form: { id: null, name: "", rules: [] },

            fields: [
                { value: "spent", label: "Total spent", type: "num" },
                { value: "orders", label: "Number of orders", type: "num" },
                { value: "lastOrder", label: "Days since last order", type: "num" },
                { value: "cartDays", label: "Left items in cart (days ago)", type: "num" },
                { value: "city", label: "City", type: "text" },
                { value: "tag", label: "Tag", type: "text" },
            ],
            ops: {
                num: [{ value: "gte", label: "is at least" }, { value: "lte", label: "is at most" }, { value: "eq", label: "is exactly" }],
                text: [{ value: "is", label: "is" }, { value: "isnot", label: "is not" }],
            },
            // share of customers who agreed to each channel: replace with GET /api/admin/audiences/consent
            consent: [
                { label: "SMS", pct: 95, color: "#0f9d6b", note: "Collected at checkout with the phone number" },
                { label: "Email", pct: 81, color: "#7c3aed", note: "Many customers skip the email field at checkout" },
            ],

            // replace with GET /api/admin/audiences (email/sms = people who agreed to that channel)
            audiences: [
                { id: 1, key: "all", system: true, type: "auto", name: "All customers", desc: "Everyone who agreed to receive messages", icon: "mdi-account-group-outline", color: "#0f9d6b", email: 980, sms: 1150, growth: 4.2, updated: "today", rules: [], cities: [{ name: "Dhaka", pct: 61 }, { name: "Chattogram", pct: 14 }, { name: "Sylhet", pct: 7 }] },
                { id: 2, key: "vip", type: "auto", name: "VIP customers", desc: "Spent ৳ 1,50,000 or more", icon: "mdi-crown-outline", color: "#7c3aed", email: 88, sms: 96, growth: 6.7, updated: "today", rules: [{ field: "spent", op: "gte", value: 150000 }], cities: [{ name: "Dhaka", pct: 66 }, { name: "Rajshahi", pct: 12 }, { name: "Chattogram", pct: 10 }] },
                { id: 3, key: "new", type: "auto", name: "New this month", desc: "First order in the last 30 days", icon: "mdi-account-plus-outline", color: "#2f5be7", email: 120, sms: 141, growth: 12.4, updated: "today", rules: [{ field: "orders", op: "eq", value: 1 }, { field: "lastOrder", op: "lte", value: 30 }], cities: [{ name: "Dhaka", pct: 58 }, { name: "Chattogram", pct: 17 }, { name: "Khulna", pct: 8 }] },
                { id: 4, key: "cart", type: "auto", name: "Left items in cart", desc: "Added products but did not order in the last 7 days", icon: "mdi-cart-outline", color: "#f59e0b", email: 54, sms: 63, growth: -3.1, updated: "today", rules: [{ field: "cartDays", op: "lte", value: 7 }], cities: [{ name: "Dhaka", pct: 64 }, { name: "Chattogram", pct: 12 }, { name: "Sylhet", pct: 9 }] },
                { id: 5, key: "dhaka", type: "auto", name: "Dhaka customers", desc: "Inside the same-day delivery area", icon: "mdi-map-marker-outline", color: "#ef6a45", email: 590, sms: 702, growth: 3.8, updated: "today", rules: [{ field: "city", op: "is", value: "Dhaka" }], cities: [{ name: "Dhanmondi", pct: 21 }, { name: "Uttara", pct: 18 }, { name: "Mirpur", pct: 16 }] },
                { id: 6, key: "wholesale", type: "auto", name: "Wholesale buyers", desc: "Shops and resellers tagged as wholesale", icon: "mdi-storefront-outline", color: "#0f766e", email: 36, sms: 38, growth: 2.7, updated: "yesterday", rules: [{ field: "tag", op: "is", value: "Wholesale" }], cities: [{ name: "Dhaka", pct: 47 }, { name: "Rajshahi", pct: 18 }, { name: "Sylhet", pct: 13 }] },
                { id: 7, key: "inactive", type: "auto", name: "Not ordered in 90 days", desc: "Good for win-back offers", icon: "mdi-sleep", color: "#ec4899", email: 171, sms: 214, growth: -1.4, updated: "today", rules: [{ field: "lastOrder", op: "gte", value: 90 }], cities: [{ name: "Dhaka", pct: 55 }, { name: "Chattogram", pct: 16 }, { name: "Barishal", pct: 8 }] },
                { id: 8, key: "giveaway", type: "manual", name: "Facebook giveaway entrants", desc: "Imported from a CSV file on 4 Nov 2026", icon: "mdi-gift-outline", color: "#1877f2", email: 0, sms: 412, growth: 0, updated: "4 Nov 2026", rules: [], cities: [{ name: "Dhaka", pct: 49 }, { name: "Chattogram", pct: 19 }, { name: "Khulna", pct: 9 }] },
            ],
        };
    },

    computed: {
        filtered() {
            const q = (this.search || "").trim().toLowerCase();
            return this.audiences.filter((a) => (this.typeFilter === "all" || a.type === this.typeFilter) && (!q || a.name.toLowerCase().includes(q) || a.desc.toLowerCase().includes(q)));
        },
        maxSms() {
            return Math.max(...this.audiences.map((a) => a.sms), 1);
        },
        everyone() {
            return this.audiences.find((a) => a.system) || this.audiences[0];
        },
        heroTiles() {
            const auto = this.audiences.filter((a) => a.type === "auto").length;
            const vip = this.audiences.find((a) => a.key === "vip");
            return [
                { icon: "mdi-account-group-outline", color: C.teal, value: this.audiences.length, label: "Audiences", tag: auto + " update themselves" },
                { icon: "mdi-account-check-outline", color: C.blue, value: this.everyone.sms.toLocaleString("en-IN"), label: "Customers reachable by SMS", tag: this.consent[0].pct + "% agreed", progress: this.consent[0].pct },
                { icon: "mdi-email-check-outline", color: C.violet, value: this.everyone.email.toLocaleString("en-IN"), label: "Customers reachable by email", tag: this.consent[1].pct + "% agreed", todo: this.consent[1].pct < 85, progress: this.consent[1].pct },
                { icon: "mdi-crown-outline", color: C.amber, value: vip ? vip.sms : 0, label: "VIP customers", tag: vip ? "▲ " + vip.growth + "%" : "-" },
            ];
        },
        sampleCustomers() {
            if (!this.sel) return [];
            const start = this.sel.id % PEOPLE.length;
            return [0, 1, 2, 3].map((i) => PEOPLE[(start + i) % PEOPLE.length]);
        },
        validRules() {
            return this.form.rules.filter((r) => r.field && r.op && String(r.value).trim() !== "");
        },
        // rough count until the server calculates the real one: replace with POST /api/admin/audiences/preview { rules }
        estimate() {
            let f = 1;
            this.validRules.forEach((r) => {
                const v = Number(r.value);
                if (r.field === "spent") f *= v >= 150000 ? 0.08 : v >= 50000 ? 0.2 : 0.5;
                else if (r.field === "orders") f *= v >= 10 ? 0.1 : v >= 3 ? 0.4 : 0.8;
                else if (r.field === "lastOrder") f *= r.op === "gte" ? (v >= 90 ? 0.18 : 0.5) : 0.4;
                else if (r.field === "cartDays") f *= 0.06;
                else if (r.field === "city") f *= String(r.value).toLowerCase() === "dhaka" ? 0.6 : 0.12;
                else f *= 0.15;
            });
            const sms = Math.max(0, Math.round(this.everyone.sms * f));
            return { sms, email: Math.round(sms * 0.85) };
        },
    },

    mounted() {
        this.sel = this.audiences[0];
    },

    methods: {
        money: (v) => "৳ " + Number(v || 0).toLocaleString("en-IN"),
        notify(text) {
            this.snackbarText = text;
            this.snackbar = true;
        },
        typeOf(field) {
            const f = this.fields.find((x) => x.value === field);
            return f ? f.type : "num";
        },
        opsFor(field) {
            return this.ops[this.typeOf(field)];
        },
        newRule() {
            return { field: "spent", op: "gte", value: "" };
        },
        resetRule(r) {
            r.op = this.opsFor(r.field)[0].value;
            r.value = "";
        },
        ruleText(r) {
            const f = this.fields.find((x) => x.value === r.field);
            const o = (this.ops[f.type] || []).find((x) => x.value === r.op);
            const v = r.field === "spent" ? this.money(r.value) : r.value;
            return `${f.label} ${o ? o.label : ""} ${v}`;
        },

        openNew() {
            this.form = { id: null, name: "", rules: [this.newRule()] };
            this.touched = false;
            this.builderOpen = true;
        },
        openEdit(a) {
            this.form = { id: a.id, name: a.name, rules: a.rules.map((r) => ({ ...r })) };
            this.touched = false;
            this.builderOpen = true;
        },
        save() {
            this.touched = true;
            if (!this.form.name.trim() || !this.validRules.length) return;
            const rules = this.validRules.map((r) => ({ ...r }));
            // POST /api/admin/audiences (or PUT /:id) with { name, rules }. The server returns the exact counts.
            if (this.form.id) {
                const a = this.audiences.find((x) => x.id === this.form.id);
                Object.assign(a, { name: this.form.name.trim(), rules, sms: this.estimate.sms, email: this.estimate.email, updated: "just now" });
                this.sel = a;
            } else {
                const a = { id: ++this.nextId, key: "custom" + this.nextId, type: "auto", name: this.form.name.trim(), desc: rules.map(this.ruleText).join(", "), icon: "mdi-filter-outline", color: "#0a5548", email: this.estimate.email, sms: this.estimate.sms, growth: 0, updated: "just now", rules, cities: this.everyone.cities };
                this.audiences.push(a);
                this.sel = a;
            }
            this.builderOpen = false;
            this.notify("Audience saved.");
        },
        confirmDelete() {
            this.audiences = this.audiences.filter((a) => a.id !== this.deleting.id); // DELETE /api/admin/audiences/:id
            if (this.sel && this.sel.id === this.deleting.id) this.sel = this.audiences[0] || null;
            this.deleting = null;
            this.notify("Audience deleted.");
        },
        sendCampaign(a) {
            // Email & SMS can read this.$route.query.audience to pre-select the audience
            this.$router.push({ path: "/admin/campaigns", query: { audience: a.key } });
        },
        exportCsv(a) {
            this.notify(`Preparing ${a.name} as a CSV file…`); // GET /api/admin/audiences/:id/export
        },
        onImport(e) {
            const file = e.target.files && e.target.files[0];
            e.target.value = "";
            if (!file) return;
            // POST /api/admin/audiences/import (FormData: file). The file needs a phone or email column.
            this.notify(`"${file.name}" is ready to import. Upload it to your server to create the list.`);
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
.tt-strong { font-weight: 600; color: var(--ink); }

/* ───────── Hero ───────── */
.tt-hero { position: relative; overflow: hidden; color: #fff; padding: 18px 22px; border-radius: 18px; background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%); box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95); }
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.15; letter-spacing: -0.5px; color: #fff; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.22); color: #fff; }
.tt-btn-ghost { height: 38px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 38px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 13px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.22; filter: blur(10px); }
.tt-hero-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-txt { flex: 1; min-width: 0; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.3px; white-space: nowrap; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.82); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tt-hero-progress { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: rgba(255, 255, 255, 0.14); }
.tt-hero-progress span { display: block; height: 100%; background: var(--hue); }
.tt-live { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(110, 231, 183, 0.5); white-space: nowrap; }
.tt-live.todo { color: #fcd34d; background: rgba(245, 158, 11, 0.16); border-color: rgba(252, 211, 77, 0.5); }
@media (max-width: 1199px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Layout & cards ───────── */
.tt-layout { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 16px; align-items: start; margin-top: 16px; }
.tt-main { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.tt-side { min-width: 0; }
.tt-sticky { position: sticky; top: 12px; }
.tt-card { --accent: #2f5be7; --tint: #e6edff; position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35); overflow: hidden; color: var(--ink); }
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, var(--accent), transparent 85%); }
.a-blue { --accent: #2f5be7; --tint: #e6edff; }
.a-teal { --accent: #0f9d6b; --tint: #dff5ec; }
.a-amber { --accent: #f59e0b; --tint: #fdecc8; }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 6px; }
.tt-head-wrap { flex-wrap: wrap; padding-bottom: 12px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-head-ic--lg { width: 42px; height: 42px; border-radius: 12px; background: #dff5ec; color: #0f9d6b; }
.tt-head-ic--red { background: #fce1d8; color: #b4381a; }
.tt-title { font-size: 14px; font-weight: 600; line-height: 1.3; }
.tt-body { padding: 10px 18px 18px; }
.tt-seg { display: inline-flex; padding: 3px; border-radius: 12px; background: #eef3f1; border: 1px solid var(--line); }
.tt-seg button { border: 0; background: transparent; font: inherit; font-size: 12.5px; font-weight: 500; color: var(--muted); padding: 5px 14px; border-radius: 9px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-seg button:hover { color: var(--ink); }
.tt-seg button.on { background: #fff; color: #0a5548; font-weight: 600; box-shadow: 0 1px 3px rgba(15, 42, 38, 0.14); }
.tt-seg button:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-search { max-width: 250px; min-width: 170px; }
.tt-search :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-icon-btn { border-color: var(--line) !important; color: #3d5450 !important; border-radius: 10px !important; font-weight: 600; }
.tt-send-btn { color: #fff !important; font-weight: 600; border-radius: 10px !important; }
.tt-link { color: #0a5548 !important; font-weight: 600; }
.tt-chip { display: inline-block; font-size: 10.5px; font-weight: 600; padding: 2px 9px; border-radius: 20px; white-space: nowrap; }
.tone-blue { background: #e6edff; color: #1f4e9c; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.tt-meter { height: 6px; border-radius: 6px; background: #e8eeec; overflow: hidden; margin: 4px 0 6px; flex: 1; }
.tt-meter span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0f9d6b, #6ee7b7); }
.tt-empty { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 36px 20px; }
.tt-empty-ic { width: 56px; height: 56px; border-radius: 50%; background: #e3f6ee; display: grid; place-items: center; }
.tt-note { display: flex; align-items: flex-start; gap: 8px; margin-top: 14px; padding: 9px 12px; border-radius: 10px; background: #f0faf5; border: 1px solid #c6ebd9; font-size: 12px; line-height: 1.5; color: #2f4f49; }

/* ───────── Audience rows ───────── */
.aud { --c: #0f9d6b; display: flex; align-items: center; gap: 14px; padding: 12px; margin-bottom: 8px; border: 1.5px solid var(--line); border-radius: 13px; background: #fff; cursor: pointer; outline: none; transition: border-color 0.15s, background 0.15s; }
.aud:last-of-type { margin-bottom: 0; }
.aud:hover { border-color: var(--c); }
.aud:focus-visible { box-shadow: 0 0 0 2px #0f9d6b; }
.aud.on { border-color: var(--c); background: color-mix(in srgb, var(--c) 6%, white); }
.aud-ic { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; background: var(--c); flex: none; box-shadow: 0 8px 14px -8px var(--c); }
.aud-main { flex: 1; min-width: 0; }
.aud-name { display: flex; align-items: center; flex-wrap: wrap; gap: 4px 8px; }
.aud-name b { font-size: 13.5px; font-weight: 600; }
.aud-desc { margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.aud-bar { height: 4px; border-radius: 4px; background: #e8eeec; overflow: hidden; margin-top: 7px; max-width: 280px; }
.aud-bar span { display: block; height: 100%; border-radius: 4px; background: var(--c); }
.aud-nums { text-align: right; line-height: 1.3; min-width: 78px; }
.aud-nums b { display: block; font-size: 14px; font-weight: 700; }
.aud-nums small { font-size: 10.5px; color: var(--muted); }
.aud-growth { display: inline-flex; align-items: center; font-size: 11.5px; font-weight: 600; padding: 2px 8px 2px 5px; border-radius: 20px; flex: none; }
.aud-growth.up { background: #dff5ec; color: #0f7a4d; }
.aud-growth.down { background: #fce1d8; color: #b4381a; }
@media (max-width: 760px) { .aud { flex-wrap: wrap; } .aud-nums { text-align: left; } }

.consent { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.consent-item b { font-size: 14px; }
@media (max-width: 599px) { .consent { grid-template-columns: 1fr; } }

/* ───────── Detail panel ───────── */
.detail-ic { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; flex: none; }
.detail-title { min-width: 0; }
.tt-tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.tt-tile { --c: #0f9d6b; border-radius: 12px; padding: 9px 11px; background: var(--bg2); border: 1px solid var(--line); border-bottom: 3px solid var(--c); }
.tt-tile small { display: block; font-size: 11.5px; color: var(--muted); }
.tt-tile b { font-size: 18px; font-weight: 700; color: var(--c); }
.tt-field-label { font-size: 12px; font-weight: 600; margin: 16px 0 8px; padding-top: 14px; border-top: 1px dashed #d5dfdc; }
.rule-list { display: flex; flex-wrap: wrap; gap: 6px; }
.rule-chip { font-size: 12px; font-weight: 600; color: #0a5548; background: #e3f6ee; border-radius: 20px; padding: 3px 11px; }
.city-row { display: flex; align-items: center; gap: 10px; font-size: 12.5px; padding: 1px 0; }
.city-row > span { width: 90px; flex: none; color: var(--muted); }
.city-row > b { width: 36px; text-align: right; font-weight: 600; }
.cust { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: 12.5px; }
.cust + .cust { border-top: 1px solid #eef3f1; }
.cust-av { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: 12px; background: #e6edff; color: #1d4ed8; flex: none; }
.cust-main { flex: 1; min-width: 0; }
.tt-publish { margin-top: 18px; height: 42px !important; color: #fff !important; font-weight: 600; border-radius: 12px !important; box-shadow: 0 12px 20px -14px #0f9d6b; }
.tt-half { flex: 1; height: 38px !important; border-color: var(--line) !important; color: #3d5450 !important; border-radius: 10px !important; font-weight: 600; }

/* ───────── Dialogs ───────── */
.tt-dialog { position: relative; background: #fff; color: var(--ink); border-radius: 16px; overflow: hidden; font-family: Poppins, "Segoe UI", sans-serif; display: flex; flex-direction: column; max-height: 92vh; }
.tt-dialog::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-dialog-head { display: flex; align-items: center; padding: 20px 20px 14px; border-bottom: 1px solid var(--line); }
.tt-info-title { font-size: 15px; font-weight: 600; }
.tt-dialog-body { padding: 16px 20px 8px; overflow-y: auto; }
.tt-dialog-foot { display: flex; align-items: center; gap: 8px; padding: 12px 20px 16px; border-top: 1px solid var(--line); }
.rule-row { display: grid; grid-template-columns: 1.3fr 1fr 1fr auto; gap: 8px; align-items: center; margin-bottom: 8px; }
@media (max-width: 640px) { .rule-row { grid-template-columns: 1fr 1fr; } }
.tt-warn { display: flex; align-items: center; gap: 6px; margin: 2px 0 6px; font-size: 12.5px; color: #b4381a; }
.estimate { display: flex; align-items: baseline; flex-wrap: wrap; gap: 4px 18px; margin-top: 16px; padding: 12px 14px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); }
.estimate small { display: block; font-size: 11.5px; color: var(--muted); }
.estimate b { font-size: 20px; font-weight: 700; margin-right: 6px; }
.estimate span { font-size: 12px; color: var(--muted); }
.est-note { flex-basis: 100%; }
@media (prefers-reduced-motion: reduce) { .aud, .tt-seg button { transition: none; } }
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
