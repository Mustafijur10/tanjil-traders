<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon>
            <router-link to="/cart">{{ $t("Cart") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("Checkout") }}</span>
        </nav>
        <h1 class="tt-doc-title">{{ $t("Checkout") }}</h1>

        <section v-if="!lines.length" class="tt-page-card">
            <EmptyState icon="mdi-cart-outline" title="Your cart is empty" text="Add products to your cart before checking out.">
                <v-btn-primary to="/shop" prepend-icon="mdi-storefront-outline">{{ $t("Start shopping") }}</v-btn-primary>
            </EmptyState>
        </section>

        <section v-else-if="loaded && needsLogin" class="tt-page-card">
            <EmptyState icon="mdi-account-lock-outline" title="Please sign in to order" text="Sign in or create an account to place your order. Your cart is saved.">
                <v-btn-primary :to="{ path: '/login', query: { redirect: '/checkout' } }">{{ $t("Sign in") }}</v-btn-primary>
                <v-btn-secondary :to="{ path: '/register', query: { redirect: '/checkout' } }">{{ $t("Create account") }}</v-btn-secondary>
            </EmptyState>
        </section>

        <form v-else class="tt-checkout" novalidate @submit.prevent="place">
            <div class="tt-checkout-main">
                <v-alert v-if="!signedIn" type="info" variant="tonal" density="compact" class="mb-4">
                    {{ $t("Have an account?") }}
                    <router-link :to="{ path: '/login', query: { redirect: '/checkout' } }">{{ $t("Sign in") }}</router-link>
                    {{ $t("to track your orders easily. You can also order as a guest.") }}
                </v-alert>
                <v-alert v-for="i in issues" :key="i.key + i.type" type="warning" variant="tonal" density="compact" class="mb-3">
                    {{ $t(i.message) }} <router-link to="/cart">{{ $t("Review cart") }}</router-link>
                </v-alert>

                <!-- 1. Delivery details -->
                <section class="tt-page-card tt-checkout-step">
                    <h2><span class="tt-step-no">1</span>{{ $t("Delivery details") }}</h2>
                    <div class="tt-form-grid">
                        <v-input-trz v-model.trim="form.name" :label="$t('Full name')" autocomplete="name" :error-messages="errors.name" @update:model-value="errors.name = null" />
                        <v-input-trz
                            v-model.trim="form.phone"
                            :label="$t('Mobile number')"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            placeholder="01XXXXXXXXX"
                            :hint="$t('We call this number to confirm your order.')"
                            persistent-hint
                            :error-messages="errors.phone"
                            @update:model-value="errors.phone = null"
                        />
                        <v-combobox-trz
                            v-model="form.city"
                            :items="districts"
                            :label="$t('City / District')"
                            autocomplete="address-level2"
                            :error-messages="errors.city"
                            @update:model-value="errors.city = null"
                        />
                        <v-input-trz v-model.trim="form.email" type="email" :label="$t('Email (optional)')" autocomplete="email" :error-messages="errors.email" @update:model-value="errors.email = null" />
                        <v-textarea-trz
                            v-model.trim="form.address"
                            class="is-full"
                            rows="2"
                            auto-grow
                            :label="$t('Full address')"
                            :placeholder="$t('House, road, area, thana')"
                            autocomplete="street-address"
                            :error-messages="errors.address"
                            @update:model-value="errors.address = null"
                        />
                        <v-textarea-trz v-model.trim="form.note" class="is-full" rows="1" auto-grow :label="$t('Note for us (optional)')" :placeholder="$t('Preferred delivery time, landmark…')" />
                    </div>
                </section>

                <!-- 2. Delivery option -->
                <section v-if="shipping.length" class="tt-page-card tt-checkout-step">
                    <h2><span class="tt-step-no">2</span>{{ $t("Delivery option") }}</h2>
                    <div class="tt-choice-list" role="radiogroup" :aria-label="$t('Delivery option')">
                        <label v-for="m in shipping" :key="m.id" class="tt-choice" :class="{ 'is-on': form.shipping_method_id === m.id }">
                            <input v-model="form.shipping_method_id" type="radio" name="shipping" :value="m.id" />
                            <span class="tt-choice-body">
                                <b>{{ $t(m.name) }}</b>
                                <small v-if="m.delivery_time">{{ $t(m.delivery_time) }}</small>
                                <small v-if="m.free_over" class="tt-choice-note">{{ $t("Free on orders over {amount}", { amount: $money(m.free_over) }) }}</small>
                            </span>
                            <b class="tt-choice-price">{{ m.free_over && subtotal >= m.free_over ? $t("Free") : $money(m.charge) }}</b>
                        </label>
                    </div>
                    <p v-if="errors.shipping_method_id" class="tt-field-error">{{ errors.shipping_method_id }}</p>
                </section>

                <!-- 3. Payment -->
                <section class="tt-page-card tt-checkout-step">
                    <h2><span class="tt-step-no">{{ shipping.length ? 3 : 2 }}</span>{{ $t("Payment") }}</h2>
                    <div class="tt-choice-list" role="radiogroup" :aria-label="$t('Payment method')">
                        <label v-for="m in payments" :key="m.value" class="tt-choice" :class="{ 'is-on': form.payment_method === m.value }">
                            <input v-model="form.payment_method" type="radio" name="payment" :value="m.value" />
                            <span class="tt-pay-logo" :class="'is-' + m.value">{{ m.value === "cod" ? "" : m.label }}<v-icon v-if="m.value === 'cod'" size="22">mdi-cash</v-icon></span>
                            <span class="tt-choice-body">
                                <b>{{ $t(m.label) }}</b>
                                <small>{{ $t(m.value === "cod" ? "Pay in cash when you receive the parcel." : "Send money first, then enter the transaction ID.") }}</small>
                            </span>
                        </label>
                    </div>
                    <div v-if="mobilePay" class="tt-pay-steps">
                        <ol>
                            <li>{{ $t("Open your {app} app and choose Send Money.", { app: mobilePay.label }) }}</li>
                            <li>{{ $t("Send {amount} to {number}.", { amount: $money(quote.total), number: mobilePay.number || "—" }) }}</li>
                            <li>{{ $t("Enter the transaction ID (TrxID) from the confirmation message below.") }}</li>
                        </ol>
                        <v-input-trz
                            v-model.trim="form.transaction_id"
                            :label="$t('Transaction ID')"
                            placeholder="8N7A6B5C4D"
                            :error-messages="errors.transaction_id"
                            @update:model-value="errors.transaction_id = null"
                        />
                    </div>
                    <p v-if="!payments.length" class="tt-muted">{{ $t("Online ordering is paused right now. Please call us to order.") }}</p>
                </section>
            </div>

            <aside class="tt-checkout-side">
                <section class="tt-page-card tt-summary">
                    <h2>{{ $t("Your order") }}</h2>
                    <ul class="tt-summary-items">
                        <li v-for="l in lines" :key="l.product_id + ':' + (l.variant_id || 0)">
                            <span class="tt-thumb"><img v-if="l.image" :src="url(l.image)" alt="" /><v-icon v-else size="18">mdi-image-outline</v-icon><em>{{ $num(l.qty) }}</em></span>
                            <span class="tt-summary-item-name">
                                <span class="tt-ellipsis-2">{{ l.name }}</span>
                                <small v-if="l.variant_label" class="tt-muted">{{ l.variant_label }}</small>
                            </span>
                            <b>{{ $money(l.price * l.qty) }}</b>
                        </li>
                    </ul>

                    <div class="tt-coupon">
                        <v-input-trz
                            v-model.trim="couponInput"
                            density="compact"
                            :placeholder="$t('Coupon code')"
                            :error-messages="quote.coupon_error ? $t(quote.coupon_error) : null"
                            :disabled="!!form.coupon_code"
                            @keydown.enter.prevent="applyCoupon"
                        />
                        <v-btn-secondary v-if="!form.coupon_code" :disabled="!couponInput" :loading="quoting" @click="applyCoupon">{{ $t("Apply") }}</v-btn-secondary>
                        <v-btn-tertiary v-else @click="removeCoupon">{{ $t("Remove") }}</v-btn-tertiary>
                    </div>

                    <div class="tt-summary-row"><span>{{ $t("Subtotal") }}</span><b>{{ $money(quote.subtotal ?? subtotal) }}</b></div>
                    <div class="tt-summary-row"><span>{{ $t("Delivery") }}</span><b>{{ quote.shipping_cost ? $money(quote.shipping_cost) : form.shipping_method_id || !shipping.length ? $t("Free") : "—" }}</b></div>
                    <div v-if="quote.discount" class="tt-summary-row is-save"><span>{{ $t("Coupon {code}", { code: quote.coupon }) }}</span><b>−{{ $money(quote.discount) }}</b></div>
                    <div class="tt-summary-row is-total"><span>{{ $t("Total") }}</span><b>{{ $money(quote.total ?? subtotal) }}</b></div>

                    <v-btn-primary type="submit" block size="large" class="mt-4" :loading="placing" :disabled="!payments.length || !!issues.length">
                        {{ $t("Place order") }}
                    </v-btn-primary>
                    <p class="tt-muted tt-small mt-3 text-center">{{ $t("By placing the order you agree to our terms and return policy.") }}</p>
                </section>
            </aside>
        </form>
    </div>
</template>

<script>
import api, { fileUrl, fieldErrors } from "@/api";
import { DISTRICTS } from "@/utils/bd";
import { storage } from "@/store/storage";

export default {
    name: "Checkout",
    data: () => ({
        loaded: false,
        guest: true,
        shipping: [],
        payments: [],
        form: { name: "", phone: "", email: "", city: null, address: "", note: "", shipping_method_id: null, payment_method: "cod", transaction_id: "", coupon_code: "" },
        couponInput: "",
        quote: {},
        quoting: false,
        placing: false,
        placed: false,
        errors: {},
        districts: DISTRICTS,
        quoteTimer: null,
    }),
    computed: {
        lines() {
            return this.$store.state.cart.lines.filter((l) => l.available !== false);
        },
        issues() {
            return this.$store.state.cart.issues.filter((i) => i.type !== "qty_reduced");
        },
        subtotal() {
            return this.$store.getters["cart/subtotal"];
        },
        signedIn() {
            return this.$store.getters["auth/isLoggedIn"];
        },
        needsLogin() {
            return !this.guest && !this.signedIn;
        },
        mobilePay() {
            return this.form.payment_method !== "cod" ? this.payments.find((p) => p.value === this.form.payment_method) : null;
        },
    },
    watch: {
        "form.shipping_method_id": "requote",
        "$store.state.cart.lines": { deep: true, handler: "requote" },
    },
    async created() {
        this.restoreDraft();
        try {
            await this.$store.dispatch("cart/refresh");
        } catch (e) {
            /* prices are checked again when the order is placed */
        }
        const { data } = await api.get("/checkout");
        this.shipping = data.shipping;
        this.payments = data.payments;
        this.guest = data.guest;
        if (data.customer) {
            for (const k of ["name", "phone", "email", "address", "city"]) this.form[k] = this.form[k] || data.customer[k] || "";
            this.form.shipping_method_id ??= data.customer.shipping_method_id;
        }
        if (!this.shipping.some((m) => m.id === this.form.shipping_method_id)) this.form.shipping_method_id = this.shipping[0]?.id ?? null;
        if (!this.payments.some((p) => p.value === this.form.payment_method)) this.form.payment_method = this.payments[0]?.value;
        // An empty string counts as a value for the combobox; null shows the label in place
        this.form.city = this.form.city || null;
        this.loaded = true;
        this.requote();
    },
    beforeUnmount() {
        this.saveDraft();
    },
    methods: {
        url: fileUrl,
        items() {
            return this.$store.getters["cart/items"];
        },
        requote() {
            clearTimeout(this.quoteTimer);
            this.quoteTimer = setTimeout(this.loadQuote, 150);
        },
        async loadQuote() {
            if (!this.lines.length) return;
            this.quoting = true;
            try {
                const { data } = await api.post(
                    "/checkout/quote",
                    { items: this.items(), shipping_method_id: this.form.shipping_method_id, coupon_code: this.form.coupon_code || undefined },
                    { quiet: true }
                );
                this.quote = data;
                if (data.coupon_error) this.form.coupon_code = "";
            } finally {
                this.quoting = false;
            }
        },
        applyCoupon() {
            if (!this.couponInput) return;
            this.form.coupon_code = this.couponInput;
            this.loadQuote();
        },
        removeCoupon() {
            this.form.coupon_code = "";
            this.couponInput = "";
            this.loadQuote();
        },
        // The form is kept for this visit, so going back to the cart does not lose it
        saveDraft() {
            if (this.placed) return;
            const { name, phone, email, city, address, note } = this.form;
            try {
                sessionStorage.setItem("tt_checkout", JSON.stringify({ name, phone, email, city, address, note }));
            } catch (e) {
                /* storage unavailable */
            }
        },
        restoreDraft() {
            try {
                Object.assign(this.form, JSON.parse(sessionStorage.getItem("tt_checkout") || "{}"));
            } catch (e) {
                /* storage unavailable */
            }
        },
        validate() {
            const e = {};
            if (!this.form.name) e.name = this.$t("Enter your name.");
            if (!/^(?:\+?88)?01[3-9]\d{8}$/.test(this.form.phone.replace(/[\s-]/g, ""))) e.phone = this.$t("Enter a valid Bangladeshi mobile number, like 01712345678.");
            if (!this.form.city) e.city = this.$t("Choose your city or district.");
            if (!this.form.address) e.address = this.$t("Enter your full address.");
            if (this.shipping.length && !this.form.shipping_method_id) e.shipping_method_id = this.$t("Choose a delivery option.");
            if (this.mobilePay && !this.form.transaction_id) e.transaction_id = this.$t("Enter the transaction ID from your payment message.");
            this.errors = e;
            return !Object.keys(e).length;
        },
        async place() {
            if (!this.validate()) {
                this.$nextTick(() => document.querySelector(".tt-checkout .v-input--error")?.scrollIntoView({ behavior: "smooth", block: "center" }));
                return;
            }
            this.placing = true;
            try {
                const payload = { ...this.form, phone: this.form.phone.replace(/[\s-]/g, ""), items: this.items(), coupon_code: this.form.coupon_code || null };
                if (!this.mobilePay) payload.transaction_id = null;
                const { data } = await api.post("/checkout", payload, { quiet: true });
                this.placed = true;
                storage.set("tt_last_order", { number: data.number, phone: data.phone });
                this.$store.dispatch("cart/clear");
                sessionStorage.removeItem("tt_checkout");
                this.$router.replace(`/order-placed/${data.number}`);
            } catch (e) {
                this.errors = fieldErrors(e);
                const msg = e.response?.data?.message;
                if (this.errors.items) this.$store.dispatch("cart/refresh").catch(() => {});
                this.$store.dispatch("notify", { text: this.$t(this.errors.items || msg || "Could not place the order. Please try again."), type: "error" });
            } finally {
                this.placing = false;
            }
        },
    },
};
</script>
