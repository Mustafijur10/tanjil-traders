<template>
    <div>
        <PageHeader :title="o ? $t('Order {number}', { number: o.order_number }) : $t('Order')" :subtitle="o ? placedLine : ''">
            <template v-if="o">
                <v-btn-secondary prepend-icon="mdi-printer-outline" :href="`/admin/orders/print?ids=${o.id}&type=invoice`" target="_blank">{{ $t("Invoice") }}</v-btn-secondary>
                <v-btn-secondary prepend-icon="mdi-package-variant-closed" :href="`/admin/orders/print?ids=${o.id}&type=slip`" target="_blank">{{ $t("Packing slip") }}</v-btn-secondary>
            </template>
        </PageHeader>

        <div v-if="!o" class="tt-box tt-box-body tt-skel-group"><span v-for="n in 6" :key="n" class="tt-skel"></span></div>

        <div v-else class="tt-detail">
            <div class="tt-detail-main">
                <!-- Status and next step -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">{{ status.icon }}</v-icon></span>
                        <div class="flex-grow-1">
                            <h2>{{ $t(status.label) }}</h2>
                            <p>{{ $t(nextHint) }}</p>
                        </div>
                        <StatusChip :label="$t(status.label)" :tone="status.tone" />
                    </div>
                    <div class="tt-box-body">
                        <ol v-if="!stopped" class="tt-steps is-compact">
                            <li v-for="(s, i) in steps" :key="s" :class="{ 'is-done': i < stepIndex, 'is-now': i === stepIndex }">
                                <span class="tt-steps-dot"><v-icon size="16">{{ i < stepIndex ? "mdi-check" : statusOf(s).icon }}</v-icon></span>
                                <b>{{ $t(statusOf(s).label) }}</b>
                                <small v-if="stepDate(s)">{{ $date(stepDate(s), { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }) }}</small>
                            </li>
                        </ol>
                        <div class="tt-detail-actions">
                            <template v-for="n in forward" :key="n">
                                <v-btn-primary :prepend-icon="statusOf(n).icon" :loading="busy === n" @click="move(n)">{{ $t(actionLabel(n)) }}</v-btn-primary>
                            </template>
                            <v-btn-danger v-if="next.includes('cancelled')" variant="outlined" prepend-icon="mdi-close" @click="cancelDialog = true">{{ $t("Cancel order") }}</v-btn-danger>
                            <v-btn-secondary v-if="canReturn" prepend-icon="mdi-keyboard-return" @click="openReturn">{{ $t("Record a return") }}</v-btn-secondary>
                        </div>
                    </div>
                </section>

                <!-- Items -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-package-variant-closed</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $tn(units, "{n} item", "{n} items") }}</h2></div>
                    </div>
                    <ul class="tt-detail-items">
                        <li v-for="i in o.items" :key="i.id">
                            <span class="tt-thumb"><img v-if="i.image" :src="url(i.image)" alt="" /><v-icon v-else size="18">mdi-image-off-outline</v-icon></span>
                            <span class="tt-detail-item-name">
                                <router-link v-if="i.product" :to="`/admin/products/${i.product.id}`">{{ i.name }}</router-link>
                                <span v-else>{{ i.name }} <small class="tt-muted">({{ $t("deleted") }})</small></span>
                                <small class="tt-muted">{{ [i.variant_label, i.sku].filter(Boolean).join(" · ") }}</small>
                            </span>
                            <span class="tt-num tt-muted">{{ $money(i.price) }} × {{ $num(i.qty) }}</span>
                            <b class="tt-num">{{ $money(i.total) }}</b>
                        </li>
                    </ul>
                    <div class="tt-detail-totals">
                        <div><span>{{ $t("Subtotal") }}</span><b>{{ $money(o.subtotal) }}</b></div>
                        <div><span>{{ $t("Delivery") }}<small v-if="o.shipping_method"> · {{ $t(o.shipping_method.name) }}</small></span><b>{{ $money(o.shipping_cost) }}</b></div>
                        <div v-if="o.discount"><span>{{ o.coupon_code ? $t("Coupon {code}", { code: o.coupon_code }) : $t("Discount") }}</span><b>−{{ $money(o.discount) }}</b></div>
                        <div class="is-total"><span>{{ $t("Total") }}</span><b>{{ $money(o.total) }}</b></div>
                        <div v-if="profit !== null" class="is-profit"><span>{{ $t("Profit on products") }}</span><b>{{ $money(profit) }}</b></div>
                    </div>
                </section>

                <!-- Returns -->
                <section v-if="o.returns.length" class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-keyboard-return</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Returns") }}</h2></div>
                        <v-btn-tertiary size="small" to="/admin/returns" append-icon="mdi-arrow-right">{{ $t("All returns") }}</v-btn-tertiary>
                    </div>
                    <ul class="tt-detail-returns">
                        <li v-for="r in o.returns" :key="r.id">
                            <StatusChip :label="$t(returnStatus(r.status).label)" :tone="returnStatus(r.status).tone" />
                            <span>
                                <b>#{{ r.id }} · {{ $t(reasons[r.reason] || r.reason) }}</b>
                                <small class="tt-muted">{{ returnItems(r) }} · {{ $date(r.created_at) }}</small>
                            </span>
                            <b v-if="r.refund_amount" class="tt-num">{{ $money(r.refund_amount) }}</b>
                        </li>
                    </ul>
                </section>

                <!-- Timeline -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-timeline-clock-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Timeline") }}</h2><p>{{ $t("Notes here are for staff only. Customers only see the status.") }}</p></div>
                    </div>
                    <div class="tt-box-body">
                        <form class="tt-note-form" @submit.prevent="addNote">
                            <v-input-trz v-model.trim="note" density="compact" :placeholder="$t('Add a note, e.g. called the customer, will collect on Friday')" />
                            <v-btn-secondary type="submit" :disabled="!note" :loading="busy === 'note'">{{ $t("Add note") }}</v-btn-secondary>
                        </form>
                        <ul class="tt-timeline">
                            <li v-for="h in o.history" :key="h.id">
                                <span class="tt-timeline-dot" :class="'is-' + statusOf(h.status).tone"></span>
                                <div>
                                    <b>{{ $t(statusOf(h.status).label) }}</b>
                                    <p v-if="h.note">{{ h.note }}</p>
                                    <small class="tt-muted">{{ $date(h.created_at, { day: "numeric", month: "short", year: "numeric", hour: "numeric", minute: "2-digit" }) }} · {{ h.user?.name || $t("Customer") }}</small>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>

            <aside class="tt-detail-side">
                <!-- Customer -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-account-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Customer") }}</h2></div>
                        <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openEdit" />
                    </div>
                    <div class="tt-box-body tt-detail-facts">
                        <p><b>{{ o.name }}</b><router-link v-if="o.user" :to="`/admin/customers/${o.user.id}`" class="tt-flink ml-2">{{ $t("Account") }}</router-link></p>
                        <p class="tt-detail-contact">
                            <a :href="`tel:${o.phone}`"><v-icon size="16">mdi-phone-outline</v-icon>{{ o.phone }}</a>
                            <a :href="whatsapp" target="_blank" rel="noopener"><v-icon size="16">mdi-whatsapp</v-icon>WhatsApp</a>
                        </p>
                        <p v-if="o.email"><v-icon size="16">mdi-email-outline</v-icon>{{ o.email }}</p>
                        <p><v-icon size="16">mdi-map-marker-outline</v-icon>{{ o.address }}, {{ o.city }}</p>
                        <div class="tt-detail-history" :class="{ 'is-risk': riskText }">
                            <span>{{ $tn(cs.orders, "{n} order with this phone", "{n} orders with this phone") }}</span>
                            <small>{{ $t("{d} delivered, {c} cancelled or returned", { d: cs.delivered, c: cs.cancelled }) }}</small>
                            <small v-if="riskText" class="tt-detail-risk"><v-icon size="14">mdi-alert-outline</v-icon>{{ $t(riskText) }}</small>
                        </div>
                        <div v-if="o.note" class="tt-detail-note">
                            <small>{{ $t("Customer note") }}</small>
                            <p>{{ o.note }}</p>
                        </div>
                    </div>
                </section>

                <!-- Payment -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-cash-multiple</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Payment") }}</h2><p>{{ $t(methodLabel) }}</p></div>
                        <StatusChip :label="$t(pay.label)" :tone="pay.tone" />
                    </div>
                    <div class="tt-box-body tt-vstack">
                        <v-select-trz v-model="payForm.payment_status" :items="paymentStatusOptions" :label="$t('Payment status')" density="compact" />
                        <v-input-trz v-if="o.payment_method !== 'cod' || payForm.transaction_id" v-model.trim="payForm.transaction_id" :label="$t('Transaction ID')" density="compact" />
                        <v-btn-secondary :disabled="!payChanged" :loading="busy === 'pay'" @click="save(payForm, 'pay')">{{ $t("Save payment") }}</v-btn-secondary>
                    </div>
                </section>

                <!-- Delivery -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-truck-delivery-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Delivery") }}</h2><p v-if="o.shipping_method">{{ $t(o.shipping_method.name) }}</p></div>
                    </div>
                    <div class="tt-box-body tt-vstack">
                        <v-select-trz v-model="shipForm.courier_id" :items="courierOptions" :label="$t('Courier')" density="compact" clearable>
                            <template #no-data><div class="pa-3 tt-small">{{ $t("No couriers yet.") }} <router-link to="/admin/couriers">{{ $t("Add one") }}</router-link></div></template>
                        </v-select-trz>
                        <v-input-trz v-model.trim="shipForm.tracking_code" :label="$t('Tracking / consignment code')" density="compact" />
                        <a v-if="o.tracking_link" :href="o.tracking_link" target="_blank" rel="noopener" class="tt-flink">{{ $t("Open courier tracking") }}<v-icon size="14">mdi-open-in-new</v-icon></a>
                        <v-btn-secondary :disabled="!shipChanged" :loading="busy === 'ship'" @click="save(shipForm, 'ship')">{{ $t("Save delivery") }}</v-btn-secondary>
                    </div>
                </section>
            </aside>
        </div>

        <!-- Ship with courier -->
        <v-dialog-trz v-model="shipDialog" max-width="460">
            <v-card>
                <div class="tt-box-head"><h2>{{ $t("Hand to courier") }}</h2></div>
                <v-card-text class="pa-5 tt-vstack">
                    <v-select-trz v-model="shipForm.courier_id" :items="courierOptions" :label="$t('Courier')" clearable />
                    <v-input-trz v-model.trim="shipForm.tracking_code" :label="$t('Tracking / consignment code (optional)')" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="shipDialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="busy === 'shipped'" @click="move('shipped', true)">{{ $t("Mark shipped") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <!-- Cancel -->
        <v-dialog-trz v-model="cancelDialog" max-width="460">
            <v-card>
                <div class="tt-box-head"><h2>{{ $t("Cancel this order?") }}</h2></div>
                <v-card-text class="pa-5">
                    <p class="mb-4">{{ $t("All items go back into stock. This cannot be undone.") }}</p>
                    <v-textarea-trz v-model.trim="cancelNote" rows="2" :label="$t('Reason (kept in the timeline)')" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="cancelDialog = false">{{ $t("Keep order") }}</v-btn-tertiary>
                    <v-btn-danger :loading="busy === 'cancelled'" @click="move('cancelled', true)">{{ $t("Cancel order") }}</v-btn-danger>
                </div>
            </v-card>
        </v-dialog-trz>

        <!-- Edit customer -->
        <v-dialog-trz v-model="editDialog" max-width="560">
            <v-card>
                <div class="tt-box-head"><h2>{{ $t("Customer and address") }}</h2></div>
                <v-card-text class="pa-5 tt-form-grid">
                    <v-input-trz v-model.trim="edit.name" :label="$t('Name')" :error-messages="editErrors.name" />
                    <v-input-trz v-model.trim="edit.phone" :label="$t('Phone')" :error-messages="editErrors.phone" />
                    <v-input-trz v-model.trim="edit.email" class="is-full" :label="$t('Email')" :error-messages="editErrors.email" />
                    <v-combobox-trz v-model="edit.city" :items="districts" :label="$t('City / District')" />
                    <v-textarea-trz v-model.trim="edit.address" class="is-full" rows="2" :label="$t('Address')" :error-messages="editErrors.address" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="editDialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="busy === 'edit'" @click="saveEdit">{{ $t("Save") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <!-- Record return -->
        <v-dialog-trz v-model="returnDialog" max-width="560">
            <v-card>
                <div class="tt-box-head">
                    <div class="flex-grow-1">
                        <h2>{{ $t("Record a return") }}</h2>
                        <p>{{ $t("Choose what the customer is sending back.") }}</p>
                    </div>
                </div>
                <v-card-text class="pa-5">
                    <ul class="tt-return-pick">
                        <li v-for="i in o?.items || []" :key="i.id">
                            <span class="tt-ellipsis">{{ i.name }}<small v-if="i.variant_label" class="tt-muted"> · {{ i.variant_label }}</small></span>
                            <QtyStepper v-model="returnQty[i.id]" :min="0" :max="returnable[i.id] ?? 0" small />
                        </li>
                    </ul>
                    <v-select-trz v-model="returnForm.reason" :items="reasonOptions" :label="$t('Reason')" class="mt-4" />
                    <v-textarea-trz v-model.trim="returnForm.details" :label="$t('Details (optional)')" rows="2" class="mt-3" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="returnDialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="busy === 'return'" :disabled="!returnCount || !returnForm.reason" @click="saveReturn">{{ $t("Save return") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>
    </div>
</template>

<script>
import api, { fileUrl, fieldErrors } from "@/api";
import QtyStepper from "@/front/components/QtyStepper.vue";
import { DISTRICTS } from "@/utils/bd";
import { ORDER_STATUS, ORDER_STEPS, PAYMENT_STATUS, PAYMENT_METHOD, RETURN_STATUS, RETURN_REASONS, options } from "@/utils/orders";

const ACTION = { confirmed: "Confirm order", processing: "Start packing", shipped: "Mark shipped", delivered: "Mark delivered" };
const HINT = {
    pending: "Call the customer to confirm the order, then confirm it here.",
    confirmed: "Pack the items, then hand the parcel to a courier.",
    processing: "When the parcel is with the courier, mark it shipped.",
    shipped: "Mark delivered when the customer receives it. Cash on delivery is then marked paid.",
    delivered: "Done. Returns can be recorded here if the customer sends something back.",
    cancelled: "This order is cancelled and its items are back in stock.",
    returned: "Everything in this order was returned and refunded.",
};

export default {
    name: "OrderDetail",
    components: { QtyStepper },
    data: () => ({
        o: null,
        profit: null,
        next: [],
        returnable: {},
        couriers: [],
        cs: { orders: 0, delivered: 0, cancelled: 0 },
        busy: null,
        note: "",
        payForm: {},
        shipForm: {},
        shipDialog: false,
        cancelDialog: false,
        cancelNote: "",
        editDialog: false,
        edit: {},
        editErrors: {},
        returnDialog: false,
        returnQty: {},
        returnForm: { reason: null, details: "" },
        steps: ORDER_STEPS,
        reasons: RETURN_REASONS,
        districts: DISTRICTS,
    }),
    computed: {
        status() {
            return ORDER_STATUS[this.o.status];
        },
        stopped() {
            return ["cancelled", "returned"].includes(this.o.status);
        },
        stepIndex() {
            return Math.max(0, this.steps.indexOf(this.o.status));
        },
        forward() {
            return this.next.filter((n) => !["cancelled", "returned"].includes(n));
        },
        nextHint() {
            return HINT[this.o.status];
        },
        placedLine() {
            const when = this.$date(this.o.created_at, { dateStyle: "medium", timeStyle: "short" });
            return this.o.source === "admin" ? this.$t("Created by staff on {date}", { date: when }) : this.$t("Placed on the website on {date}", { date: when });
        },
        units() {
            return this.o.items.reduce((n, i) => n + i.qty, 0);
        },
        pay() {
            return PAYMENT_STATUS[this.o.payment_status];
        },
        methodLabel() {
            return PAYMENT_METHOD[this.o.payment_method] || this.o.payment_method;
        },
        paymentStatusOptions() {
            return options(PAYMENT_STATUS, this.$t);
        },
        courierOptions() {
            return this.couriers.map((c) => ({ value: c.id, title: c.name }));
        },
        reasonOptions() {
            return options(RETURN_REASONS, this.$t);
        },
        payChanged() {
            return this.payForm.payment_status !== this.o.payment_status || (this.payForm.transaction_id || null) !== (this.o.transaction_id || null);
        },
        shipChanged() {
            return (this.shipForm.courier_id || null) !== (this.o.courier_id || null) || (this.shipForm.tracking_code || null) !== (this.o.tracking_code || null);
        },
        whatsapp() {
            const n = String(this.o.phone).replace(/\D/g, "").replace(/^0/, "880");
            return `https://wa.me/${n}`;
        },
        riskText() {
            const { orders, cancelled } = this.cs;
            return orders >= 2 && cancelled / orders >= 0.5 ? "Many of this customer's orders were cancelled or returned. Confirm by phone first." : "";
        },
        canReturn() {
            return ["delivered", "returned"].includes(this.o.status) && Object.values(this.returnable).some((q) => q > 0);
        },
        returnCount() {
            return Object.values(this.returnQty).reduce((n, q) => n + q, 0);
        },
    },
    watch: { "$route.params.id": { immediate: true, handler: "load" } },
    methods: {
        url: fileUrl,
        statusOf: (s) => ORDER_STATUS[s] || { label: s, tone: "grey", icon: "mdi-circle-small" },
        returnStatus: (s) => RETURN_STATUS[s] || { label: s, tone: "grey" },
        actionLabel: (s) => ACTION[s] || s,
        stepDate(s) {
            return [...this.o.history].reverse().find((h) => h.status === s)?.created_at;
        },
        returnItems(r) {
            return r.items.map((ri) => `${this.o.items.find((i) => i.id === ri.order_item_id)?.name || "?"} × ${ri.qty}`).join(", ");
        },
        apply(data) {
            this.o = data.order;
            this.profit = data.profit;
            this.next = data.next;
            this.returnable = data.returnable;
            this.couriers = data.couriers;
            this.cs = data.customer_stats;
            this.payForm = { payment_status: this.o.payment_status, transaction_id: this.o.transaction_id };
            this.shipForm = { courier_id: this.o.courier_id, tracking_code: this.o.tracking_code };
        },
        async load(id) {
            if (!id) return;
            this.o = null;
            this.apply((await api.get(`/admin/orders/${id}`)).data);
        },
        async update(body, key, message) {
            this.busy = key;
            try {
                this.apply((await api.put(`/admin/orders/${this.o.id}`, body)).data);
                if (message) this.$store.dispatch("notify", this.$t(message));
                return true;
            } catch (e) {
                return false;
            } finally {
                this.busy = null;
            }
        },
        async move(status, fromDialog = false) {
            if (status === "shipped" && !fromDialog) {
                this.shipDialog = true;
                return;
            }
            const body = { status };
            if (status === "shipped") Object.assign(body, this.shipForm);
            if (status === "cancelled") body.note = this.cancelNote || null;
            if (await this.update(body, status, "Order updated.")) {
                this.shipDialog = this.cancelDialog = false;
                this.cancelNote = "";
            }
        },
        save(form, key) {
            this.update(form, key, "Saved.");
        },
        async addNote() {
            if (await this.update({ note: this.note }, "note")) this.note = "";
        },
        openEdit() {
            const { name, phone, email, city, address } = this.o;
            this.edit = { name, phone, email, city, address };
            this.editErrors = {};
            this.editDialog = true;
        },
        async saveEdit() {
            this.busy = "edit";
            try {
                this.apply((await api.put(`/admin/orders/${this.o.id}`, this.edit, { quiet: true })).data);
                this.editDialog = false;
                this.$store.dispatch("notify", this.$t("Saved."));
            } catch (e) {
                this.editErrors = fieldErrors(e);
            } finally {
                this.busy = null;
            }
        },
        openReturn() {
            this.returnQty = Object.fromEntries(this.o.items.map((i) => [i.id, 0]));
            this.returnForm = { reason: null, details: "" };
            this.returnDialog = true;
        },
        async saveReturn() {
            this.busy = "return";
            try {
                const items = Object.entries(this.returnQty).map(([id, qty]) => ({ order_item_id: Number(id), qty }));
                await api.post(`/admin/orders/${this.o.id}/returns`, { ...this.returnForm, items });
                this.returnDialog = false;
                this.$store.dispatch("notify", this.$t("Return recorded. Continue it on the Returns page."));
                this.load(this.o.id);
            } finally {
                this.busy = null;
            }
        },
    },
};
</script>
