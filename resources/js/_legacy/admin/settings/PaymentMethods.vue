<template>
    <div class="ts-page">
        <!-- ═════════ Hero ═════════ -->
        <header class="ts-hero">
            <div class="d-flex flex-wrap align-center ga-4">
                <div class="ts-hero-logo"><v-icon size="26" color="white">mdi-credit-card-multiple-outline</v-icon></div>
                <div>
                    <div class="d-flex align-center ga-3 flex-wrap">
                        <h1 class="ts-h1">Payment Methods</h1>
                        <span class="ts-pill" :class="{ warn: rules.testMode }"><span class="ts-pill-dot"></span>{{ rules.testMode ? "Test mode" : "Live payments" }}</span>
                        <v-chip v-if="isDirty" size="x-small" color="warning" variant="flat" class="font-weight-bold">Unsaved changes</v-chip>
                    </div>
                    <div class="ts-hero-sub">Choose how customers pay · currency BDT (৳)</div>
                </div>
                <v-spacer />
                <div class="d-flex ga-2 flex-wrap">
                    <v-btn variant="outlined" class="text-none ts-btn-ghost" prepend-icon="mdi-restore" :disabled="!isDirty || saving" @click="discard">Discard</v-btn>
                    <v-btn variant="flat" class="text-none ts-btn-solid" prepend-icon="mdi-content-save-check" :loading="saving" :disabled="!isDirty" @click="saveAll">Save changes</v-btn>
                </div>
            </div>
        </header>

        <!-- ═════════ Stats ═════════ -->
        <div class="ts-stats">
            <div v-for="s in stats" :key="s.label" class="ts-stat">
                <span class="ts-stat-icon" :style="{ background: s.color }"><v-icon size="20" color="white">{{ s.icon }}</v-icon></span>
                <div>
                    <div class="ts-stat-value">{{ s.value }}</div>
                    <div class="ts-stat-label">{{ s.label }}</div>
                </div>
            </div>
        </div>

        <v-row dense class="mt-4">
            <!-- ═════════ Method list ═════════ -->
            <v-col cols="12" lg="8">
                <v-card flat class="ts-card">
                    <div class="ts-card-head">
                        <div>
                            <div class="ts-card-title">Payment methods</div>
                            <div class="ts-card-sub">Customers see the enabled methods in this order. Use the arrows to reorder.</div>
                        </div>
                    </div>

                    <div v-for="(m, i) in ordered" :key="m.key" class="ts-method" :class="{ 'is-off': !state[m.key].enabled }">
                        <div class="ts-order-btns">
                            <button type="button" class="ts-order-btn" :disabled="i === 0" :aria-label="'Move ' + m.name + ' up'" @click="move(i, -1)"><v-icon size="16">mdi-chevron-up</v-icon></button>
                            <button type="button" class="ts-order-btn" :disabled="i === ordered.length - 1" :aria-label="'Move ' + m.name + ' down'" @click="move(i, 1)"><v-icon size="16">mdi-chevron-down</v-icon></button>
                        </div>
                        <span class="ts-method-icon" :style="{ background: m.color }"><v-icon size="22" color="white">{{ m.icon }}</v-icon></span>
                        <div class="ts-method-main">
                            <div class="ts-method-name">{{ m.name }}<span class="ts-badge" :class="badge(m).cls">{{ badge(m).text }}</span></div>
                            <div class="ts-method-meta">{{ m.kind }} · {{ meta(m) }}</div>
                        </div>
                        <v-btn variant="text" size="small" class="text-none" prepend-icon="mdi-cog-outline" @click="open(m.key)">Configure</v-btn>
                        <v-switch v-model="state[m.key].enabled" class="ts-switch" color="success" hide-details density="compact" :aria-label="'Enable ' + m.name" />
                    </div>
                </v-card>
            </v-col>

            <!-- ═════════ Preview + global rules ═════════ -->
            <v-col cols="12" lg="4">
                <v-card flat class="ts-card mb-4">
                    <div class="ts-card-head">
                        <div>
                            <div class="ts-card-title">Checkout preview</div>
                            <div class="ts-card-sub">What customers will see</div>
                        </div>
                    </div>
                    <div class="ts-body">
                        <div v-for="m in enabledList" :key="m.key" class="ts-preview-row" :class="{ 'is-default': m.key === defaultKey }">
                            <span class="ts-radio"></span>
                            <span v-if="rules.showIcons" class="ts-preview-icon" :style="{ background: m.color }"><v-icon size="16" color="white">{{ m.icon }}</v-icon></span>
                            <div class="flex-grow-1" style="min-width: 0">
                                <div class="text-body-2 font-weight-medium">{{ m.name }}</div>
                                <div v-if="state[m.key].passFee" class="ts-muted ts-small">+ {{ state[m.key].fee }}% fee</div>
                            </div>
                        </div>
                        <div v-if="!enabledList.length" class="ts-note">No payment method is on, so customers cannot check out.</div>
                    </div>
                </v-card>

                <v-card flat class="ts-card">
                    <div class="ts-card-head"><div class="ts-card-title">Global rules</div></div>
                    <div class="ts-body">
                        <div class="ts-switch-box mb-3">
                            <v-switch v-model="rules.testMode" color="warning" density="compact" hide-details label="Test mode for all gateways" />
                        </div>
                        <div class="ts-muted ts-small mb-4">While on, wallets and cards run in sandbox and no real money moves.</div>

                        <v-select v-model="rules.defaultMethod" :items="enabledList.map((m) => ({ title: m.name, value: m.key }))" label="Preselected method" variant="outlined" density="comfortable" class="mb-1" :disabled="!enabledList.length" />
                        <v-text-field v-model.number="rules.autoCancelMins" label="Cancel unpaid online orders after" type="number" suffix="min" variant="outlined" density="comfortable" hint="0 keeps them open" persistent-hint class="mb-4" />
                        <div class="ts-switch-box">
                            <v-switch v-model="rules.showIcons" color="success" density="compact" hide-details label="Show method icons at checkout" />
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Configure dialog ═════════ -->
        <v-dialog v-model="dialog" max-width="560" scrollable>
            <v-card v-if="active" class="ts-dialog">
                <div class="ts-dialog-head">
                    <span class="ts-dialog-icon" :style="{ background: active.color }"><v-icon size="20" color="white">{{ active.icon }}</v-icon></span>
                    <div>
                        <div class="ts-card-title">{{ active.name }}</div>
                        <div class="ts-card-sub">{{ active.desc }}</div>
                    </div>
                    <v-spacer />
                    <v-btn icon="mdi-close" variant="text" size="small" aria-label="Close" @click="dialog = false" />
                </div>

                <v-card-text class="pt-4">
                    <v-form ref="form" @submit.prevent="apply">
                        <div class="ts-switch-box mb-4">
                            <v-switch v-model="draft.enabled" color="success" density="compact" hide-details :label="'Accept payments with ' + active.name" />
                        </div>

                        <div v-for="f in active.fields" :key="f.key" class="mb-3">
                            <v-switch v-if="f.type === 'switch'" v-model="draft[f.key]" :label="f.label" color="success" density="compact" hide-details />

                            <v-textarea v-else-if="f.type === 'textarea'" v-model="draft[f.key]" :label="f.label" :hint="f.hint" rows="3" auto-grow variant="outlined" density="comfortable" />

                            <v-select v-else-if="f.type === 'select'" v-model="draft[f.key]" :items="f.items" :label="f.label" :hint="f.hint" variant="outlined" density="comfortable" />

                            <v-text-field
                                v-else-if="f.type === 'secret'"
                                v-model="secretDraft[f.key]"
                                :label="f.label"
                                :type="reveal[f.key] ? 'text' : 'password'"
                                :placeholder="draft.configured ? 'Saved. Leave blank to keep it' : ''"
                                :append-inner-icon="reveal[f.key] ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                                autocomplete="new-password"
                                variant="outlined"
                                density="comfortable"
                                @click:append-inner="reveal[f.key] = !reveal[f.key]"
                            />

                            <v-text-field v-else v-model="draft[f.key]" :label="f.label" :hint="f.hint" :type="f.type === 'number' ? 'number' : 'text'" :rules="rules_(f)" variant="outlined" density="comfortable" />
                        </div>

                        <div v-if="active.needsKeys" class="ts-muted ts-small">Secrets are sent to your server when you save. They are never kept in the browser or shown again.</div>
                    </v-form>
                </v-card-text>

                <v-card-actions class="ts-dialog-foot">
                    <v-btn v-if="active.needsKeys" variant="text" class="text-none" prepend-icon="mdi-lan-connect" :loading="testing" @click="testConnection">Test connection</v-btn>
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="#0a5548" variant="flat" class="text-none" @click="apply">Apply</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="toast.show" :color="toast.color" timeout="2800" location="bottom right">{{ toast.text }}</v-snackbar>
    </div>
</template>

<script>
import "./settings.scss";

const STORAGE_KEY = "tt_payment_methods";
const MODES = [
    { title: "Live", value: "live" },
    { title: "Sandbox (test)", value: "test" },
];
const mode = { key: "mode", label: "Mode", type: "select", items: MODES, default: "live" };
// Fees below are starting values you can edit. Check them against your own provider contract.
const feeFields = (fee) => [
    { key: "fee", label: "Gateway fee (%)", type: "number", max: 100, default: fee, hint: "Charged by the provider" },
    { key: "passFee", label: "Add the fee to the customer's total", type: "switch", default: false },
    { key: "min", label: "Minimum order (৳)", type: "number", default: 0 },
    { key: "max", label: "Maximum order (৳)", type: "number", default: 0, hint: "0 means no limit" },
];

// One entry per method. `fields` drives the dialog; `required` fields decide when a method counts as set up.
const METHODS = [
    { key: "bkash", name: "bKash", kind: "Mobile wallet", gateway: true, needsKeys: true, icon: "mdi-wallet-outline", color: "#e2136e", desc: "Tokenized checkout with a bKash merchant account",
      fields: [
        { key: "merchantNumber", label: "Merchant number", type: "text", required: true, default: "" },
        { key: "appKey", label: "App key", type: "text", required: true, default: "" },
        { key: "appSecret", label: "App secret", type: "secret", required: true },
        { key: "username", label: "API username", type: "text", required: true, default: "" },
        { key: "password", label: "API password", type: "secret", required: true },
        mode, ...feeFields(1.5),
      ] },
    { key: "nagad", name: "Nagad", kind: "Mobile wallet", gateway: true, needsKeys: true, icon: "mdi-cellphone-nfc", color: "#ed1c24", desc: "Nagad merchant payment gateway",
      fields: [
        { key: "merchantNumber", label: "Merchant number", type: "text", required: true, default: "" },
        { key: "merchantId", label: "Merchant ID", type: "text", required: true, default: "" },
        { key: "privateKey", label: "Merchant private key", type: "secret", required: true },
        mode, ...feeFields(1.45),
      ] },
    { key: "rocket", name: "Rocket", kind: "Mobile wallet", gateway: true, needsKeys: true, icon: "mdi-rocket-launch-outline", color: "#8c3494", desc: "DBBL Rocket merchant payments",
      fields: [
        { key: "merchantNumber", label: "Merchant number", type: "text", required: true, default: "" },
        { key: "apiKey", label: "API key", type: "secret", required: true },
        mode, ...feeFields(1.8),
      ] },
    { key: "cards", name: "Cards & Internet Banking", kind: "SSLCommerz", gateway: true, needsKeys: true, icon: "mdi-credit-card-outline", color: "#2f5be7", desc: "Visa, Mastercard, Amex and local banks via SSLCommerz",
      fields: [
        { key: "storeId", label: "Store ID", type: "text", required: true, default: "" },
        { key: "storePassword", label: "Store password", type: "secret", required: true },
        { key: "threeDS", label: "Require 3-D Secure", type: "switch", default: true },
        mode, ...feeFields(2.5),
      ] },
    { key: "bank", name: "Bank Transfer", kind: "Manual", gateway: false, needsKeys: false, icon: "mdi-bank-outline", color: "#0f766e", desc: "Customers pay by bank transfer and you confirm manually",
      fields: [
        { key: "bankName", label: "Bank name", type: "text", default: "" },
        { key: "accountName", label: "Account name", type: "text", default: "" },
        { key: "accountNumber", label: "Account number", type: "text", default: "" },
        { key: "branch", label: "Branch", type: "text", default: "" },
        { key: "routing", label: "Routing number", type: "text", default: "" },
        { key: "instructions", label: "Instructions shown to customers", type: "textarea", default: "Send your payment slip to our WhatsApp after transferring." },
        { key: "holdHours", label: "Cancel unpaid orders after (hours)", type: "number", default: 24 },
      ] },
    { key: "cod", name: "Cash on Delivery", kind: "Cash", gateway: false, needsKeys: false, icon: "mdi-cash-multiple", color: "#167a5e", desc: "Customers pay the courier when the parcel arrives",
      fields: [
        { key: "maxOrder", label: "Maximum COD order (৳)", type: "number", default: 25000, hint: "Larger orders must be paid online" },
        { key: "codFee", label: "COD handling fee (৳)", type: "number", default: 0 },
        { key: "otp", label: "Verify COD orders with an SMS OTP", type: "switch", default: true },
        { key: "advance", label: "Ask for an advance delivery fee outside Dhaka", type: "switch", default: true },
        { key: "advanceAmount", label: "Advance delivery fee (৳)", type: "number", default: 150 },
      ] },
    { key: "stripe", name: "Stripe", kind: "International cards", gateway: true, needsKeys: true, icon: "mdi-earth", color: "#635bff", desc: "Accept foreign cards in USD, EUR or GBP",
      fields: [
        { key: "publishableKey", label: "Publishable key", type: "text", required: true, default: "" },
        { key: "secretKey", label: "Secret key", type: "secret", required: true },
        { key: "webhookSecret", label: "Webhook signing secret", type: "secret" },
        { key: "currency", label: "Charge currency", type: "select", items: ["USD", "EUR", "GBP"], default: "USD" },
        mode, ...feeFields(2.9),
      ] },
];
const ON_BY_DEFAULT = ["cod"];
const n = (v) => Number(v || 0).toLocaleString("en-IN");

const defaultState = () => {
    const out = {};
    METHODS.forEach((m) => {
        out[m.key] = { enabled: ON_BY_DEFAULT.includes(m.key), configured: !m.needsKeys };
        m.fields.forEach((f) => {
            if (f.type !== "secret") out[m.key][f.key] = f.default;
        });
    });
    return out;
};
const defaultRules = () => ({ testMode: false, defaultMethod: "cod", autoCancelMins: 30, showIcons: true });

export default {
    name: "PaymentMethods",

    data() {
        return {
            methods: METHODS,
            order: METHODS.map((m) => m.key),
            state: defaultState(),
            rules: defaultRules(),
            snapshot: "",
            secretsQueue: {}, // secrets typed in the dialog, sent to the API on save (never stored in the browser)

            dialog: false,
            activeKey: null,
            draft: {},
            secretDraft: {},
            reveal: {},
            testing: false,
            saving: false,
            toast: { show: false, text: "", color: "success" },
        };
    },

    computed: {
        active() {
            return this.methods.find((m) => m.key === this.activeKey) || null;
        },
        ordered() {
            return this.order.map((k) => this.methods.find((m) => m.key === k)).filter(Boolean);
        },
        enabledList() {
            return this.ordered.filter((m) => this.state[m.key].enabled);
        },
        defaultKey() {
            return this.enabledList.some((m) => m.key === this.rules.defaultMethod) ? this.rules.defaultMethod : (this.enabledList[0] || {}).key;
        },
        isDirty() {
            return this.serialize() !== this.snapshot || Object.keys(this.secretsQueue).length > 0;
        },
        stats() {
            const gateways = this.methods.filter((m) => m.gateway && this.state[m.key].enabled);
            const live = gateways.filter((m) => this.state[m.key].configured && !this.rules.testMode && this.state[m.key].mode !== "test");
            const setup = this.methods.filter((m) => this.state[m.key].enabled && m.needsKeys && !this.state[m.key].configured);
            const def = this.methods.find((m) => m.key === this.defaultKey);
            return [
                { label: "Methods on", value: `${this.enabledList.length} of ${this.methods.length}`, icon: "mdi-toggle-switch-outline", color: "#167a5e" },
                { label: "Live gateways", value: live.length, icon: "mdi-lightning-bolt-outline", color: "#2d5f8f" },
                { label: "Need setup", value: setup.length, icon: "mdi-alert-circle-outline", color: setup.length ? "#c58a1e" : "#7a808e" },
                { label: "Preselected", value: def ? def.name : "None", icon: "mdi-star-circle-outline", color: "#7c3aed" },
            ];
        },
    },

    created() {
        // Load saved values. Swap for: axios.get("/api/admin/settings/payments")
        try {
            const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
            if (raw) {
                Object.keys(this.state).forEach((k) => Object.assign(this.state[k], (raw.state || {})[k] || {}));
                Object.assign(this.rules, raw.rules || {});
                const valid = (raw.order || []).filter((k) => this.methods.some((m) => m.key === k));
                this.order = [...valid, ...this.methods.map((m) => m.key).filter((k) => !valid.includes(k))];
            }
        } catch (e) {
            /* ignore corrupt storage */
        }
        this.snapshot = this.serialize();
    },

    methods: {
        serialize() {
            return JSON.stringify({ order: this.order, state: this.state, rules: this.rules });
        },
        badge(m) {
            const s = this.state[m.key];
            if (!s.enabled) return { cls: "off", text: "Off" };
            if (m.needsKeys && !s.configured) return { cls: "warn", text: "Needs setup" };
            if (m.gateway && (this.rules.testMode || s.mode === "test")) return { cls: "test", text: "Test" };
            return { cls: "ok", text: m.gateway ? "Live" : "On" };
        },
        meta(m) {
            const s = this.state[m.key];
            if (m.key === "cod") return `Up to ৳${n(s.maxOrder)}${s.otp ? " · SMS OTP" : ""}`;
            if (m.key === "bank") return s.bankName || "Add your bank details";
            return `${s.fee}% fee${s.passFee ? " · paid by customer" : ""}`;
        },
        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.order.length) return;
            const next = [...this.order];
            [next[i], next[j]] = [next[j], next[i]];
            this.order = next;
        },
        rules_(f) {
            if (f.type !== "number") return [];
            return [(v) => v === "" || v === null || Number(v) >= 0 || "Must be 0 or more", (v) => f.max === undefined || v === "" || Number(v) <= f.max || `Must be ${f.max} or less`];
        },

        // ── Dialog ──
        open(key) {
            this.activeKey = key;
            this.draft = JSON.parse(JSON.stringify(this.state[key]));
            this.secretDraft = {};
            this.reveal = {};
            this.dialog = true;
        },
        apply() {
            const m = this.active;
            const prev = this.state[m.key];
            const next = { ...prev, ...this.draft };
            m.fields.forEach((f) => {
                if (f.type === "number" && next[f.key] !== "" && next[f.key] !== null) next[f.key] = Number(next[f.key]);
            });
            const typed = Object.keys(this.secretDraft).filter((k) => this.secretDraft[k]);
            const missing = m.fields.some((f) => {
                if (!f.required) return false;
                if (f.type === "secret") return !(this.secretDraft[f.key] || prev.configured);
                return !String(next[f.key] ?? "").trim();
            });
            next.configured = m.needsKeys ? !missing : true;
            if (typed.length) {
                // Swap for: send `typed` secrets with your save request (e.g. PUT /api/admin/settings/payments/<key>)
                this.secretsQueue = { ...this.secretsQueue, [m.key]: typed.reduce((o, k) => ({ ...o, [k]: this.secretDraft[k] }), this.secretsQueue[m.key] || {}) };
            }
            this.state[m.key] = next;
            this.dialog = false;
            if (m.needsKeys && missing) this.notify(`${m.name} still needs its credentials`, "warning");
            else this.notify(`${m.name} updated. Save changes to publish`, "info");
        },
        testConnection() {
            const m = this.active;
            const ready = m.fields.filter((f) => f.required).every((f) => (f.type === "secret" ? this.secretDraft[f.key] || this.draft.configured : String(this.draft[f.key] ?? "").trim()));
            if (!ready) return this.notify("Fill in all required credentials first", "error");
            this.testing = true;
            // Swap for: await axios.post(`/api/admin/settings/payments/${m.key}/test`, {...})
            setTimeout(() => {
                this.testing = false;
                this.notify(`${m.name}: connection check passed (simulated)`);
            }, 900);
        },

        // ── Save / discard ──
        async saveAll() {
            const bad = this.methods.find((m) => this.state[m.key].enabled && m.needsKeys && !this.state[m.key].configured);
            if (bad) {
                this.notify(`Finish setting up ${bad.name} before turning it on`, "error");
                this.open(bad.key);
                return;
            }
            if (!this.enabledList.length) return this.notify("Turn on at least one payment method", "error");
            this.saving = true;
            try {
                // Swap for: await axios.put("/api/admin/settings/payments", { order, state, rules, secrets: this.secretsQueue });
                await new Promise((r) => setTimeout(r, 400));
                localStorage.setItem(STORAGE_KEY, JSON.stringify({ order: this.order, state: this.state, rules: this.rules }));
                this.secretsQueue = {};
                this.snapshot = this.serialize();
                this.notify("Payment settings saved");
            } catch (e) {
                this.notify("Could not save. Please try again.", "error");
            } finally {
                this.saving = false;
            }
        },
        discard() {
            const o = JSON.parse(this.snapshot);
            this.order = o.order;
            this.state = o.state;
            this.rules = o.rules;
            this.secretsQueue = {};
            this.notify("Changes discarded", "info");
        },
        notify(text, color = "success") {
            this.toast = { show: true, text, color };
        },
    },
};
</script>
