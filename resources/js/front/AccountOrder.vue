<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon>
            <router-link to="/account/orders">{{ $t("My orders") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $route.params.number }}</span>
        </nav>

        <div v-if="loading" class="tt-page-card tt-skel-group"><span v-for="n in 6" :key="n" class="tt-skel"></span></div>
        <PageNotFound v-else-if="missing" />

        <OrderDetails v-else :order="order">
            <template v-if="order.can_cancel || order.can_return || order.status === 'delivered'" #actions>
                <v-btn-danger v-if="order.can_cancel" variant="outlined" prepend-icon="mdi-close" @click="cancelDialog = true">{{ $t("Cancel order") }}</v-btn-danger>
                <v-btn-secondary v-if="order.can_return" prepend-icon="mdi-keyboard-return" @click="openReturn">{{ $t("Return items") }}</v-btn-secondary>
                <v-btn-tertiary v-for="i in reviewable" :key="i.id" :to="`/product/${i.slug}#reviews`" prepend-icon="mdi-star-outline">{{ $t("Review {name}", { name: short(i.name) }) }}</v-btn-tertiary>
            </template>
        </OrderDetails>

        <!-- Cancel -->
        <v-dialog-trz v-model="cancelDialog" max-width="460">
            <v-card>
                <div class="tt-box-head"><h2>{{ $t("Cancel this order?") }}</h2></div>
                <v-card-text class="pa-5">
                    <p class="mb-4">{{ $t("The items go back on sale and you will not be charged.") }}</p>
                    <v-select-trz v-model="cancelReason" :items="cancelReasons" :label="$t('Reason (optional)')" clearable />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="cancelDialog = false">{{ $t("Keep order") }}</v-btn-tertiary>
                    <v-btn-danger :loading="busy" @click="cancel">{{ $t("Cancel order") }}</v-btn-danger>
                </div>
            </v-card>
        </v-dialog-trz>

        <!-- Return -->
        <v-dialog-trz v-model="returnDialog" max-width="560">
            <v-card>
                <div class="tt-box-head">
                    <div class="flex-grow-1">
                        <h2>{{ $t("Return items") }}</h2>
                        <p>{{ $t("Choose what you want to send back. We will contact you to arrange pickup.") }}</p>
                    </div>
                </div>
                <v-card-text class="pa-5">
                    <ul class="tt-return-pick">
                        <li v-for="i in order.items" :key="i.id">
                            <span class="tt-ellipsis">{{ i.name }}<small v-if="i.variant_label" class="tt-muted"> · {{ i.variant_label }}</small></span>
                            <QtyStepper v-model="returnQty[i.id]" :min="0" :max="i.qty" small />
                        </li>
                    </ul>
                    <v-select-trz v-model="returnForm.reason" :items="reasonOptions" :label="$t('Reason')" class="mt-4" :error-messages="returnErrors.reason" />
                    <v-textarea-trz v-model.trim="returnForm.details" :label="$t('Tell us more (optional)')" rows="3" class="mt-3" />
                    <p v-if="returnErrors.items" class="tt-field-error">{{ returnErrors.items }}</p>
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="returnDialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="busy" :disabled="!returnCount || !returnForm.reason" @click="sendReturn">{{ $t("Send return request") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";
import OrderDetails from "@/front/components/OrderDetails.vue";
import QtyStepper from "@/front/components/QtyStepper.vue";
import PageNotFound from "@/PageNotFound.vue";
import { RETURN_REASONS, options } from "@/utils/orders";

export default {
    name: "AccountOrder",
    components: { OrderDetails, QtyStepper, PageNotFound },
    data: () => ({
        order: null,
        loading: true,
        missing: false,
        busy: false,
        cancelDialog: false,
        cancelReason: null,
        returnDialog: false,
        returnQty: {},
        returnForm: { reason: null, details: "" },
        returnErrors: {},
    }),
    computed: {
        cancelReasons() {
            return ["I changed my mind", "I found a better price", "I ordered by mistake", "Delivery takes too long", "Other"].map((r) => ({ value: r, title: this.$t(r) }));
        },
        reasonOptions() {
            return options(RETURN_REASONS, this.$t);
        },
        returnCount() {
            return Object.values(this.returnQty).reduce((n, q) => n + q, 0);
        },
        reviewable() {
            return this.order?.status === "delivered" ? this.order.items.filter((i) => i.slug).slice(0, 2) : [];
        },
    },
    watch: { "$route.params.number": { immediate: true, handler: "load" } },
    methods: {
        short: (name) => (name.length > 24 ? name.slice(0, 22) + "…" : name),
        async load(number) {
            if (!number) return;
            this.loading = true;
            try {
                this.order = (await api.get(`/me/orders/${number}`, { quiet: true })).data;
                document.title = `${number} · ${this.$store.state.prefs.settings["store.name"] || ""}`;
            } catch (e) {
                this.missing = true;
            } finally {
                this.loading = false;
            }
        },
        async cancel() {
            this.busy = true;
            try {
                this.order = (await api.post(`/me/orders/${this.order.number}/cancel`, { reason: this.cancelReason })).data;
                this.cancelDialog = false;
                this.$store.dispatch("notify", this.$t("Your order is cancelled."));
            } finally {
                this.busy = false;
            }
        },
        openReturn() {
            this.returnQty = Object.fromEntries(this.order.items.map((i) => [i.id, this.order.items.length === 1 ? i.qty : 0]));
            this.returnForm = { reason: null, details: "" };
            this.returnErrors = {};
            this.returnDialog = true;
        },
        async sendReturn() {
            this.busy = true;
            this.returnErrors = {};
            try {
                const items = Object.entries(this.returnQty).map(([id, qty]) => ({ order_item_id: Number(id), qty }));
                this.order = (await api.post(`/me/orders/${this.order.number}/return`, { ...this.returnForm, items }, { quiet: true })).data;
                this.returnDialog = false;
                this.$store.dispatch("notify", this.$t("Return request sent. We will contact you soon."));
            } catch (e) {
                this.returnErrors = fieldErrors(e);
            } finally {
                this.busy = false;
            }
        },
    },
};
</script>
