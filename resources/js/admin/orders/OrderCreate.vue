<template>
    <div>
        <PageHeader title="Create Order" subtitle="For orders taken by phone, Facebook or in the shop. Stock is taken out when you save.">
            <v-btn-tertiary to="/admin/orders">{{ $t("Cancel") }}</v-btn-tertiary>
            <v-btn-primary prepend-icon="mdi-check" :loading="saving" :disabled="!lines.length" @click="save">{{ $t("Create order") }}</v-btn-primary>
        </PageHeader>

        <div class="tt-detail">
            <div class="tt-detail-main">
                <!-- Products -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-package-variant-closed</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Products") }}</h2><p>{{ $t("Search by name or SKU and pick the option.") }}</p></div>
                    </div>
                    <div class="tt-box-body">
                        <v-autocomplete-trz
                            v-model="picked"
                            v-model:search="productSearch"
                            :items="results"
                            item-title="label"
                            return-object
                            :loading="searching"
                            no-filter
                            clearable
                            prepend-inner-icon="mdi-magnify"
                            :placeholder="$t('Add a product…')"
                            :no-data-text="$t(productSearch ? 'No products found' : 'Type to search')"
                            @update:model-value="addPicked"
                        >
                            <template #item="{ props, item }">
                                <v-list-item v-bind="props" :title="item.raw.label" :disabled="item.raw.stock <= 0">
                                    <template #prepend>
                                        <span class="tt-thumb mr-3"><img v-if="item.raw.image" :src="url(item.raw.image)" alt="" /><v-icon v-else size="16">mdi-image-outline</v-icon></span>
                                    </template>
                                    <template #subtitle>{{ $money(item.raw.price) }} · {{ item.raw.stock > 0 ? $t("{n} in stock", { n: item.raw.stock }) : $t("Out of stock") }}</template>
                                </v-list-item>
                            </template>
                        </v-autocomplete-trz>
                        <p v-if="errors.items" class="tt-field-error">{{ errors.items }}</p>

                        <EmptyState v-if="!lines.length" icon="mdi-cart-outline" title="No products yet" text="Search above to add the first product." />
                        <ul v-else class="tt-detail-items is-edit">
                            <li v-for="(l, i) in lines" :key="l.key">
                                <span class="tt-thumb"><img v-if="l.image" :src="url(l.image)" alt="" /><v-icon v-else size="18">mdi-image-outline</v-icon></span>
                                <span class="tt-detail-item-name">
                                    <span>{{ l.name }}</span>
                                    <small class="tt-muted">{{ [l.variant_label, $t("{n} in stock", { n: l.stock })].filter(Boolean).join(" · ") }}</small>
                                </span>
                                <QtyStepper v-model="l.qty" :max="l.stock" small />
                                <b class="tt-num">{{ $money(l.price * l.qty) }}</b>
                                <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Remove')" @click="lines.splice(i, 1)" />
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Customer -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-account-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Customer and delivery") }}</h2><p>{{ $t("If the phone matches a customer account, the order is added to it.") }}</p></div>
                    </div>
                    <div class="tt-box-body tt-form-grid">
                        <v-input-trz v-model.trim="form.phone" :label="$t('Mobile number')" placeholder="01XXXXXXXXX" :error-messages="errors.phone" @update:model-value="errors.phone = null" />
                        <v-input-trz v-model.trim="form.name" :label="$t('Name')" :error-messages="errors.name" @update:model-value="errors.name = null" />
                        <v-combobox-trz v-model="form.city" :items="districts" :label="$t('City / District')" :error-messages="errors.city" @update:model-value="errors.city = null" />
                        <v-input-trz v-model.trim="form.email" :label="$t('Email (optional)')" :error-messages="errors.email" />
                        <v-textarea-trz v-model.trim="form.address" class="is-full" rows="2" :label="$t('Address')" :error-messages="errors.address" @update:model-value="errors.address = null" />
                        <v-textarea-trz v-model.trim="form.note" class="is-full" rows="1" auto-grow :label="$t('Customer note (optional)')" />
                    </div>
                </section>
            </div>

            <aside class="tt-detail-side">
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-calculator-variant-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Totals") }}</h2></div>
                    </div>
                    <div class="tt-box-body tt-vstack">
                        <v-select-trz v-model="form.shipping_method_id" :items="shippingOptions" :label="$t('Delivery option')" density="compact" clearable @update:model-value="setShipping" />
                        <v-input-trz v-model.number="form.shipping_cost" type="number" min="0" :label="$t('Delivery charge')" :prefix="currency" density="compact" />
                        <v-input-trz v-model.number="form.discount" type="number" min="0" :label="$t('Discount')" :prefix="currency" density="compact" />
                        <div class="tt-detail-totals is-flat">
                            <div><span>{{ $t("Subtotal") }}</span><b>{{ $money(subtotal) }}</b></div>
                            <div><span>{{ $t("Delivery") }}</span><b>{{ $money(form.shipping_cost || 0) }}</b></div>
                            <div v-if="form.discount"><span>{{ $t("Discount") }}</span><b>−{{ $money(Math.min(form.discount, subtotal)) }}</b></div>
                            <div class="is-total"><span>{{ $t("Total") }}</span><b>{{ $money(total) }}</b></div>
                        </div>
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-cash-multiple</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Payment") }}</h2></div>
                    </div>
                    <div class="tt-box-body tt-vstack">
                        <v-select-trz v-model="form.payment_method" :items="methodOptions" :label="$t('Payment method')" density="compact" />
                        <v-select-trz v-model="form.payment_status" :items="payStatusOptions" :label="$t('Payment status')" density="compact" />
                        <v-input-trz v-if="form.payment_method !== 'cod'" v-model.trim="form.transaction_id" :label="$t('Transaction ID')" density="compact" />
                        <v-switch-trz v-model="form.confirm" :label="$t('Already confirmed with the customer')" />
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<script>
import api, { fileUrl, fieldErrors } from "@/api";
import QtyStepper from "@/front/components/QtyStepper.vue";
import { DISTRICTS } from "@/utils/bd";
import { PAYMENT_METHOD } from "@/utils/orders";

export default {
    name: "OrderCreate",
    components: { QtyStepper },
    data: () => ({
        lines: [],
        picked: null,
        productSearch: "",
        results: [],
        searching: false,
        searchTimer: null,
        shipping: [],
        form: {
            name: "", phone: "", email: "", city: "Dhaka", address: "", note: "",
            shipping_method_id: null, shipping_cost: 0, discount: 0,
            payment_method: "cod", payment_status: "unpaid", transaction_id: "", confirm: true,
        },
        errors: {},
        saving: false,
        districts: DISTRICTS,
    }),
    computed: {
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
        subtotal() {
            return this.lines.reduce((n, l) => n + l.price * l.qty, 0);
        },
        total() {
            return Math.max(0, this.subtotal - Math.min(this.form.discount || 0, this.subtotal)) + (this.form.shipping_cost || 0);
        },
        shippingOptions() {
            return this.shipping.map((m) => ({ value: m.id, title: `${this.$t(m.name)} · ${this.$money(m.charge)}` }));
        },
        methodOptions() {
            return Object.entries(PAYMENT_METHOD).map(([value, label]) => ({ value, title: this.$t(label) }));
        },
        payStatusOptions() {
            return [
                { value: "unpaid", title: this.$t("Unpaid") },
                { value: "paid", title: this.$t("Paid") },
            ];
        },
    },
    watch: {
        productSearch(q) {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => this.search(q), 250);
        },
    },
    async created() {
        const [{ data: methods }] = await Promise.all([api.get("/admin/shipping-methods", { quiet: true }).catch(() => ({ data: [] })), this.search("")]);
        this.shipping = methods.filter((m) => m.is_active);
        if (this.shipping[0]) this.setShipping((this.form.shipping_method_id = this.shipping[0].id));
    },
    methods: {
        url: fileUrl,
        async search(q) {
            this.searching = true;
            try {
                const { data } = await api.get("/admin/orders/products", { params: { q: q || undefined } });
                // One row per sellable option
                this.results = data.flatMap((p) =>
                    p.variants.length
                        ? p.variants.map((v) => ({ key: `${p.id}:${v.id}`, product_id: p.id, variant_id: v.id, name: p.name, variant_label: v.label, label: `${p.name} — ${v.label}`, image: p.image, price: v.price, stock: v.stock }))
                        : [{ key: `${p.id}:0`, product_id: p.id, variant_id: null, name: p.name, variant_label: null, label: p.name, image: p.image, price: p.price, stock: p.stock }]
                );
            } finally {
                this.searching = false;
            }
        },
        addPicked(row) {
            if (!row) return;
            const existing = this.lines.find((l) => l.key === row.key);
            if (existing) existing.qty = Math.min(existing.qty + 1, row.stock);
            else this.lines.push({ ...row, qty: 1 });
            this.errors.items = null;
            this.$nextTick(() => {
                this.picked = null;
                this.productSearch = "";
            });
        },
        setShipping(id) {
            const m = this.shipping.find((s) => s.id === id);
            this.form.shipping_cost = m ? m.charge : 0;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const items = this.lines.map(({ product_id, variant_id, qty }) => ({ product_id, variant_id, qty }));
                const body = { ...this.form, items, transaction_id: this.form.payment_method === "cod" ? null : this.form.transaction_id, email: this.form.email || null };
                const { data } = await api.post("/admin/orders", body, { quiet: true });
                this.$store.dispatch("notify", this.$t("Order {number} created.", { number: data.number }));
                this.$router.replace(`/admin/orders/${data.id}`);
            } catch (e) {
                this.errors = fieldErrors(e);
                this.$store.dispatch("notify", { text: this.$t(Object.values(this.errors)[0] || e.response?.data?.message || "Could not create the order."), type: "error" });
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
