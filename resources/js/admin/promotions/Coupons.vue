<template>
    <div>
        <PageHeader title="Coupons" subtitle="Discount codes customers type at checkout. See how many orders each one brought in.">
            <ExportMenu :build="exportSpec" :disabled="!items.length" />
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add coupon") }}</v-btn-primary>
        </PageHeader>

        <div class="tt-stat-tiles">
            <div class="tt-stat-tile is-green"><span class="tt-stat-ico"><v-icon size="20">mdi-ticket-percent-outline</v-icon></span><span><small>{{ $t("Active coupons") }}</small><b>{{ $num(stats.active ?? 0) }}</b></span></div>
            <div class="tt-stat-tile is-blue"><span class="tt-stat-ico"><v-icon size="20">mdi-receipt-text-outline</v-icon></span><span><small>{{ $t("Orders with a coupon") }}</small><b>{{ $num(stats.orders ?? 0) }}</b></span></div>
            <div class="tt-stat-tile is-violet"><span class="tt-stat-ico"><v-icon size="20">mdi-cash-multiple</v-icon></span><span><small>{{ $t("Sales from coupons") }}</small><b>{{ $money(stats.revenue ?? 0) }}</b></span></div>
            <div class="tt-stat-tile is-amber"><span class="tt-stat-ico"><v-icon size="20">mdi-sale</v-icon></span><span><small>{{ $t("Discount given") }}</small><b>{{ $money(stats.discount ?? 0) }}</b></span></div>
        </div>

        <section class="tt-box">
            <DataTable :headers="headers" :items="items" :loading="loading" :hide-footer="items.length <= 25" :rows-per-page="25" class="tt-edt-clickable" @click-row="openForm">
                <template #item-code="row">
                    <div class="tt-two-line">
                        <b class="tt-code">{{ row.code }}</b>
                        <small>{{ describe(row) }}</small>
                    </div>
                </template>
                <template #item-state="row"><StatusChip :label="$t(states[row.state].label)" :tone="states[row.state].tone" /></template>
                <template #item-used_count="row">
                    <span class="tt-num">{{ $num(row.used_count) }}<span v-if="row.usage_limit" class="tt-muted"> / {{ $num(row.usage_limit) }}</span></span>
                </template>
                <template #item-revenue="row">
                    <div class="tt-two-line tt-num">
                        <b>{{ $money(row.revenue) }}</b>
                        <small>{{ $tn(row.orders, "{n} order", "{n} orders") }} · −{{ $money(row.discount) }}</small>
                    </div>
                </template>
                <template #item-ends_at="row">
                    <span class="tt-muted tt-nowrap-cell">{{ period(row) }}</span>
                </template>
                <template #item-actions="row">
                    <div class="d-flex ga-1 justify-end">
                        <v-btn-tertiary icon="mdi-content-copy" size="small" :aria-label="$t('Copy code')" @click.stop="copy(row.code)" />
                        <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click.stop="askDelete(row)" />
                    </div>
                </template>
                <template #empty-message>
                    <EmptyState icon="mdi-ticket-percent-outline" title="No coupons yet" text="Create a code like EID10 for 10% off, or SAVE500 for ৳500 off orders over ৳10,000.">
                        <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add coupon") }}</v-btn-primary>
                    </EmptyState>
                </template>
            </DataTable>
        </section>

        <v-dialog-trz v-model="dialog" max-width="620" :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit coupon" : "Add coupon") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model="form.code" class="is-full tt-code-input" :label="$t('Code')" placeholder="EID10" :error-messages="errors.code" @update:model-value="errors.code = null">
                            <template #append-inner><v-btn-tertiary size="small" @click="generate">{{ $t("Generate") }}</v-btn-tertiary></template>
                        </v-input-trz>
                        <div class="is-full tt-segments is-full" role="tablist">
                            <button type="button" role="tab" :class="{ 'is-on': form.type === 'percent' }" @click="form.type = 'percent'">{{ $t("Percent off") }}</button>
                            <button type="button" role="tab" :class="{ 'is-on': form.type === 'fixed' }" @click="form.type = 'fixed'">{{ $t("Amount off") }}</button>
                        </div>
                        <v-input-trz v-model.number="form.value" type="number" min="0" :label="$t(form.type === 'percent' ? 'Discount (%)' : 'Discount amount')" :prefix="form.type === 'fixed' ? currency : undefined" :suffix="form.type === 'percent' ? '%' : undefined" :error-messages="errors.value" />
                        <v-input-trz v-if="form.type === 'percent'" v-model.number="form.max_discount" type="number" min="0" :prefix="currency" :label="$t('Maximum discount (optional)')" />
                        <v-input-trz v-model.number="form.min_order" type="number" min="0" :prefix="currency" :label="$t('Minimum order (optional)')" />
                        <v-input-trz v-model.number="form.usage_limit" type="number" min="1" :label="$t('Total uses allowed (optional)')" />
                        <v-input-trz v-model="form.starts_at" type="datetime-local" :label="$t('Starts (optional)')" />
                        <v-input-trz v-model="form.ends_at" type="datetime-local" :label="$t('Ends (optional)')" :error-messages="errors.ends_at" />
                        <v-switch-trz v-model="form.is_active" class="is-full" :label="$t('Customers can use this code')" />
                    </div>
                    <p v-if="form.value" class="tt-coupon-preview">
                        <v-icon size="18">mdi-ticket-percent-outline</v-icon>{{ describe(form) }}
                    </p>
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.code || !form.value" @click="save">{{ $t("Save") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete coupon {code}?', { code: deleting?.code || '' })" text="Customers can no longer use it. Past orders keep their discount." :loading="saving" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";
import { toLocalInput, fromLocalInput } from "@/utils/datetime";

const STATES = {
    active: { label: "Active", tone: "green" },
    scheduled: { label: "Scheduled", tone: "blue" },
    paused: { label: "Paused", tone: "grey" },
    expired: { label: "Expired", tone: "red" },
    used_up: { label: "Used up", tone: "amber" },
};

export default {
    name: "Coupons",
    data: () => ({ items: [], stats: {}, loading: true, dialog: false, form: {}, errors: {}, saving: false, deleteDialog: false, deleting: null, states: STATES }),
    computed: {
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
        headers() {
            return [
                { text: this.$t("Coupon"), value: "code", width: 260 },
                { text: this.$t("Status"), value: "state" },
                { text: this.$t("Used"), value: "used_count", sortable: true },
                { text: this.$t("Sales"), value: "revenue", sortable: true },
                { text: this.$t("Valid"), value: "ends_at" },
                { text: "", value: "actions", width: 100 },
            ];
        },
    },
    created() {
        this.load();
    },
    methods: {
        async load() {
            try {
                const { data } = await api.get("/admin/coupons");
                this.items = data.items;
                this.stats = data.stats;
            } finally {
                this.loading = false;
            }
        },
        exportSpec() {
            const s = this.stats;
            return {
                title: "Coupons",
                filename: "coupons",
                kpis: [
                    { label: "Active coupons", value: s.active, type: "number" },
                    { label: "Orders with a coupon", value: s.orders, type: "number" },
                    { label: "Sales from coupons", value: s.revenue, type: "money" },
                    { label: "Discount given", value: s.discount, type: "money" },
                ],
                tables: [
                    {
                        title: "Coupons",
                        columns: [
                            { text: "Code", value: "code" },
                            { text: "Offer", value: "offer", get: (c) => this.describe(c) },
                            { text: "Status", value: "state", get: (c) => this.$t(this.states[c.state].label) },
                            { text: "Used", value: "used_count", type: "number" },
                            { text: "Limit", value: "usage_limit", type: "number" },
                            { text: "Orders", value: "orders", type: "number" },
                            { text: "Sales", value: "revenue", type: "money" },
                            { text: "Discount given", value: "discount", type: "money" },
                            { text: "Valid", value: "valid", get: (c) => this.period(c) },
                        ],
                        rows: this.items,
                    },
                ],
            };
        },
        describe(c) {
            const off = c.type === "percent" ? `${this.$num(c.value)}%` : this.$money(c.value || 0);
            const parts = [this.$t("{off} off", { off })];
            if (c.type === "percent" && c.max_discount) parts.push(this.$t("up to {amount}", { amount: this.$money(c.max_discount) }));
            if (c.min_order) parts.push(this.$t("orders over {amount}", { amount: this.$money(c.min_order) }));
            return parts.join(" · ");
        },
        period(c) {
            if (!c.starts_at && !c.ends_at) return this.$t("No end date");
            const f = (d) => this.$date(d, { day: "numeric", month: "short" });
            if (c.starts_at && c.ends_at) return `${f(c.starts_at)} – ${f(c.ends_at)}`;
            return c.ends_at ? this.$t("Until {date}", { date: f(c.ends_at) }) : this.$t("From {date}", { date: f(c.starts_at) });
        },
        generate() {
            const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
            this.form.code = Array.from({ length: 8 }, () => chars[Math.floor(Math.random() * chars.length)]).join("");
        },
        openForm(c = null) {
            this.form = c
                ? { ...c, starts_at: toLocalInput(c.starts_at), ends_at: toLocalInput(c.ends_at) }
                : { code: "", type: "percent", value: null, min_order: null, max_discount: null, usage_limit: null, starts_at: "", ends_at: "", is_active: true };
            this.errors = {};
            this.dialog = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            const f = this.form;
            const body = {
                code: f.code, type: f.type, value: f.value, is_active: f.is_active,
                min_order: f.min_order || null, max_discount: f.type === "percent" ? f.max_discount || null : null, usage_limit: f.usage_limit || null,
                starts_at: fromLocalInput(f.starts_at), ends_at: fromLocalInput(f.ends_at),
            };
            try {
                if (f.id) await api.put(`/admin/coupons/${f.id}`, body, { quiet: true });
                else await api.post("/admin/coupons", body, { quiet: true });
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Coupon saved."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        async copy(code) {
            try {
                await navigator.clipboard.writeText(code);
                this.$store.dispatch("notify", this.$t("Copied {code}.", { code }));
            } catch (e) {
                /* clipboard blocked */
            }
        },
        askDelete(c) {
            this.deleting = c;
            this.deleteDialog = true;
        },
        async remove() {
            this.saving = true;
            try {
                await api.delete(`/admin/coupons/${this.deleting.id}`);
                this.deleteDialog = false;
                this.load();
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
