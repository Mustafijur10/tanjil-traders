<template>
    <div>
        <PageHeader title="Returns & Refunds" subtitle="Approve a return, receive the items back into stock, then record the refund." />

        <section class="tt-box">
            <div class="tt-toolbar">
                <div class="tt-segments tt-segments-scroll" role="tablist" :aria-label="$t('Filter returns')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': status === t.value }" :aria-selected="status === t.value" @click="setStatus(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value] ?? 0) }}</span>
                    </button>
                </div>
                <div class="tt-toolbar-grow"></div>
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Order no., name or phone')" clearable :aria-label="$t('Search')" />
            </div>

            <div v-if="loading && !items.length" class="tt-box-body tt-skel-group"><span v-for="n in 4" :key="n" class="tt-skel"></span></div>
            <EmptyState
                v-else-if="!items.length"
                icon="mdi-keyboard-return"
                :title="status === 'open' ? 'No returns to handle' : 'No returns here'"
                text="Customers ask for returns from their order page within the return window. You can also record one from an order."
            />

            <article v-for="r in items" :key="r.id" class="tt-return-card">
                <div class="tt-return-main">
                    <div class="tt-return-top">
                        <StatusChip :label="$t(rs(r.status).label)" :tone="rs(r.status).tone" />
                        <b>#{{ r.id }} · {{ $t(reasons[r.reason] || r.reason) }}</b>
                        <span class="tt-muted tt-small">{{ $date(r.created_at, { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }) }}</span>
                    </div>
                    <p class="tt-return-order">
                        <router-link :to="`/admin/orders/${r.order_id}`">{{ r.order?.order_number }}</router-link>
                        · {{ r.order?.name }} · <a :href="`tel:${r.order?.phone}`">{{ r.order?.phone }}</a>
                        · {{ $t(methodLabel(r.order?.payment_method)) }}
                    </p>
                    <ul class="tt-return-items">
                        <li v-for="i in r.items" :key="i.id">{{ i.order_item?.name }}<small v-if="i.order_item?.variant_label"> · {{ i.order_item.variant_label }}</small> <b>× {{ i.qty }}</b></li>
                    </ul>
                    <p v-if="r.details" class="tt-return-details">“{{ r.details }}”</p>
                    <p v-if="r.admin_note" class="tt-muted tt-small"><v-icon size="14">mdi-note-text-outline</v-icon> {{ r.admin_note }}</p>
                </div>
                <div class="tt-return-side">
                    <span class="tt-muted tt-small">{{ $t(r.status === "refunded" ? "Refunded" : "Value") }}</span>
                    <b class="tt-num">{{ $money(r.refund_amount || 0) }}</b>
                    <small v-if="r.refund_method" class="tt-muted">{{ $t(refundMethods[r.refund_method]) }}</small>
                    <div class="tt-return-actions">
                        <v-btn-primary v-if="r.status === 'requested'" size="small" :loading="busy === r.id" @click="openStep(r, 'approved')">{{ $t("Approve") }}</v-btn-primary>
                        <v-btn-primary v-if="r.status === 'approved'" size="small" :loading="busy === r.id" @click="openStep(r, 'received')">{{ $t("Items received") }}</v-btn-primary>
                        <v-btn-primary v-if="r.status === 'received'" size="small" :loading="busy === r.id" @click="openStep(r, 'refunded')">{{ $t("Record refund") }}</v-btn-primary>
                        <v-btn-tertiary v-if="['requested', 'approved'].includes(r.status)" size="small" color="error" @click="openStep(r, 'rejected')">{{ $t("Reject") }}</v-btn-tertiary>
                    </div>
                </div>
            </article>

            <div v-if="lastPage > 1" class="tt-box-foot">
                <v-pagination v-model="page" :length="lastPage" density="comfortable" total-visible="7" />
            </div>
        </section>

        <v-dialog-trz v-model="dialog" max-width="480">
            <v-card v-if="current">
                <div class="tt-box-head">
                    <div class="flex-grow-1">
                        <h2>{{ $t(stepTitle) }}</h2>
                        <p>{{ $t(stepText) }}</p>
                    </div>
                </div>
                <v-card-text class="pa-5 tt-vstack">
                    <v-switch-trz v-if="form.status === 'received'" v-model="form.restock" :label="$t('Put the items back in stock (they can be sold again)')" />
                    <template v-if="form.status === 'refunded'">
                        <v-input-trz v-model.number="form.refund_amount" type="number" min="0" :prefix="currency" :label="$t('Refund amount')" :error-messages="errors.refund_amount" />
                        <v-select-trz v-model="form.refund_method" :items="refundOptions" :label="$t('Refunded by')" :error-messages="errors.refund_method" />
                    </template>
                    <v-textarea-trz v-model.trim="form.admin_note" rows="2" :label="$t('Note (staff only)')" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-danger v-if="form.status === 'rejected'" :loading="busy === current.id" @click="saveStep">{{ $t("Reject return") }}</v-btn-danger>
                    <v-btn-primary v-else :loading="busy === current.id" @click="saveStep">{{ $t("Save") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";
import { RETURN_STATUS, RETURN_REASONS, REFUND_METHODS, PAYMENT_METHOD, options } from "@/utils/orders";

const STEP = {
    approved: ["Approve this return?", "Tell the customer how to send the items back. Nothing changes in stock yet."],
    received: ["Items received", "Confirm the items are back with you."],
    refunded: ["Record the refund", "Enter how much was paid back to the customer."],
    rejected: ["Reject this return?", "Write the reason in the note so the team knows why."],
};

export default {
    name: "Returns",
    data() {
        return {
            items: [],
            counts: {},
            loading: true,
            status: this.$route.query.status || "open",
            search: "",
            page: 1,
            lastPage: 1,
            timer: null,
            busy: null,
            dialog: false,
            current: null,
            form: {},
            errors: {},
            reasons: RETURN_REASONS,
            refundMethods: REFUND_METHODS,
        };
    },
    computed: {
        tabs: () => [
            { value: "open", label: "To handle" },
            { value: "requested", label: "Requested" },
            { value: "approved", label: "Approved" },
            { value: "received", label: "Received" },
            { value: "refunded", label: "Refunded" },
            { value: "rejected", label: "Rejected" },
            { value: "all", label: "All" },
        ],
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
        refundOptions() {
            return options(REFUND_METHODS, this.$t);
        },
        stepTitle() {
            return STEP[this.form.status]?.[0] || "";
        },
        stepText() {
            return STEP[this.form.status]?.[1] || "";
        },
    },
    watch: {
        page: "load",
        search() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.page = 1;
                this.load();
            }, 300);
        },
    },
    created() {
        this.load();
    },
    methods: {
        rs: (s) => RETURN_STATUS[s] || { label: s, tone: "grey" },
        methodLabel: (m) => PAYMENT_METHOD[m] || m || "",
        setStatus(v) {
            this.status = v;
            this.page = 1;
            this.$router.replace({ query: v !== "open" ? { status: v } : {} });
            this.load();
        },
        async load() {
            this.loading = true;
            try {
                const { data } = await api.get("/admin/returns", { params: { page: this.page, status: this.status, search: this.search || undefined } });
                this.items = data.items.data;
                this.lastPage = data.items.last_page;
                this.counts = data.counts;
            } finally {
                this.loading = false;
            }
        },
        openStep(r, status) {
            this.current = r;
            const method = { bkash: "bkash", nagad: "nagad" }[r.order?.payment_method] || "cash";
            this.form = { status, restock: r.restock ?? true, refund_amount: r.refund_amount, refund_method: r.refund_method || method, admin_note: r.admin_note || "" };
            this.errors = {};
            this.dialog = true;
        },
        async saveStep() {
            this.busy = this.current.id;
            try {
                await api.put(`/admin/returns/${this.current.id}`, this.form, { quiet: true });
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Return updated."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
                if (!Object.keys(this.errors).length || this.errors.status) this.$store.dispatch("notify", { text: this.$t(this.errors.status || "Could not update the return."), type: "error" });
            } finally {
                this.busy = null;
            }
        },
    },
};
</script>
