<template>
    <div class="tt-print">
        <div class="tt-print-bar">
            <b>{{ $t(type === "slip" ? "Packing slips" : "Invoices") }} · {{ $tn(orders.length, "{n} order", "{n} orders") }}</b>
            <span class="tt-toolbar-grow"></span>
            <v-btn-secondary size="small" @click="type = type === 'slip' ? 'invoice' : 'slip'">{{ $t(type === "slip" ? "Show invoices" : "Show packing slips") }}</v-btn-secondary>
            <v-btn-primary size="small" prepend-icon="mdi-printer-outline" :disabled="loading" @click="print">{{ $t("Print") }}</v-btn-primary>
        </div>

        <p v-if="loading" class="tt-print-loading">{{ $t("Loading…") }}</p>

        <article v-for="o in orders" :key="o.id" class="tt-print-page" :class="'is-' + type">
            <header class="tt-print-head">
                <div class="tt-print-store">
                    <img v-if="logo" :src="logo" alt="" />
                    <div>
                        <b>{{ store.name }}</b>
                        <span v-if="store.address">{{ store.address }}</span>
                        <span>{{ [store.phone, store.email].filter(Boolean).join(" · ") }}</span>
                    </div>
                </div>
                <div class="tt-print-title">
                    <h1>{{ $t(type === "slip" ? "Packing slip" : "Invoice") }}</h1>
                    <b>{{ o.order_number }}</b>
                    <span>{{ $date(o.created_at, { dateStyle: "medium" }) }}</span>
                </div>
            </header>

            <!-- Packing slip: what the courier and packer need -->
            <template v-if="type === 'slip'">
                <section class="tt-print-ship">
                    <div>
                        <small>{{ $t("Deliver to") }}</small>
                        <b class="tt-print-big">{{ o.name }}</b>
                        <b class="tt-print-big">{{ o.phone }}</b>
                        <p>{{ o.address }}<br />{{ o.city }}</p>
                    </div>
                    <div class="tt-print-cod" :class="{ 'is-paid': o.payment_status === 'paid' }">
                        <small>{{ o.payment_status === "paid" ? $t("Paid. Collect nothing") : $t("Collect cash") }}</small>
                        <b>{{ o.payment_status === "paid" ? "৳0" : $money(o.total) }}</b>
                        <span v-if="o.courier">{{ o.courier.name }}<template v-if="o.tracking_code"> · {{ o.tracking_code }}</template></span>
                    </div>
                </section>
                <table class="tt-print-table">
                    <thead>
                        <tr><th class="is-check"></th><th>{{ $t("Item") }}</th><th>{{ $t("SKU") }}</th><th class="is-num">{{ $t("Qty") }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="i in o.items" :key="i.id">
                            <td class="is-check"><span class="tt-print-box"></span></td>
                            <td>{{ i.name }}<small v-if="i.variant_label"> · {{ i.variant_label }}</small></td>
                            <td>{{ i.sku || "—" }}</td>
                            <td class="is-num"><b>{{ i.qty }}</b></td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="o.note" class="tt-print-note"><b>{{ $t("Customer note") }}:</b> {{ o.note }}</p>
            </template>

            <!-- Invoice -->
            <template v-else>
                <section class="tt-print-parties">
                    <div>
                        <small>{{ $t("Bill to") }}</small>
                        <b>{{ o.name }}</b>
                        <span>{{ o.phone }}<template v-if="o.email"> · {{ o.email }}</template></span>
                        <span>{{ o.address }}, {{ o.city }}</span>
                    </div>
                    <div>
                        <small>{{ $t("Payment") }}</small>
                        <b>{{ $t(methodLabel(o.payment_method)) }} · {{ $t(payLabel(o.payment_status)) }}</b>
                        <span v-if="o.transaction_id">{{ $t("Transaction ID") }}: {{ o.transaction_id }}</span>
                        <span v-if="o.shipping_method">{{ $t("Delivery") }}: {{ $t(o.shipping_method.name) }}</span>
                    </div>
                </section>
                <table class="tt-print-table">
                    <thead>
                        <tr><th>#</th><th>{{ $t("Item") }}</th><th class="is-num">{{ $t("Price") }}</th><th class="is-num">{{ $t("Qty") }}</th><th class="is-num">{{ $t("Amount") }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="(i, n) in o.items" :key="i.id">
                            <td>{{ n + 1 }}</td>
                            <td>{{ i.name }}<small v-if="i.variant_label"> · {{ i.variant_label }}</small><small v-if="i.sku"> · {{ i.sku }}</small></td>
                            <td class="is-num">{{ $money(i.price) }}</td>
                            <td class="is-num">{{ i.qty }}</td>
                            <td class="is-num">{{ $money(i.total) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="tt-print-totals">
                    <div><span>{{ $t("Subtotal") }}</span><b>{{ $money(o.subtotal) }}</b></div>
                    <div><span>{{ $t("Delivery") }}</span><b>{{ $money(o.shipping_cost) }}</b></div>
                    <div v-if="o.discount"><span>{{ $t("Discount") }}</span><b>−{{ $money(o.discount) }}</b></div>
                    <div class="is-total"><span>{{ $t("Total") }}</span><b>{{ $money(o.total) }}</b></div>
                    <div v-if="o.payment_status !== 'paid'" class="is-due"><span>{{ $t("Amount due") }}</span><b>{{ $money(o.total) }}</b></div>
                </div>
                <footer class="tt-print-foot">
                    <p>{{ $t("Thank you for shopping with us. Keep this invoice for warranty claims.") }}</p>
                </footer>
            </template>
        </article>
    </div>
</template>

<script>
/* Invoices and packing slips, one order per printed page. /admin/orders/print?ids=1,2&type=invoice|slip */
import api, { fileUrl } from "@/api";
import { PAYMENT_METHOD, PAYMENT_STATUS } from "@/utils/orders";

export default {
    name: "OrderPrint",
    data() {
        return { orders: [], loading: true, type: this.$route.query.type === "slip" ? "slip" : "invoice" };
    },
    computed: {
        store() {
            const s = this.$store.state.prefs.settings;
            return { name: s["store.name"], address: s["store.address"], phone: s["store.phone"], email: s["store.email"] };
        },
        logo() {
            const path = this.$store.state.prefs.settings["store.logo"];
            return path ? fileUrl(path) : "";
        },
    },
    async created() {
        const ids = String(this.$route.query.ids || "").split(",").map(Number).filter(Boolean).slice(0, 50);
        try {
            const results = await Promise.all(ids.map((id) => api.get(`/admin/orders/${id}`).then((r) => r.data.order)));
            this.orders = results;
            document.title = ids.length === 1 ? `${results[0].order_number} · ${this.store.name}` : this.store.name;
            this.$nextTick(() => setTimeout(() => this.print(), 400));
        } finally {
            this.loading = false;
        }
    },
    methods: {
        methodLabel: (m) => PAYMENT_METHOD[m] || m,
        payLabel: (s) => PAYMENT_STATUS[s]?.label || s,
        print() {
            window.print();
        },
    },
};
</script>
