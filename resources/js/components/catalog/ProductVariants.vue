<template>
    <div>
        <EmptyState
            v-if="!options.length"
            icon="mdi-shape-plus-outline"
            title="No option specs for this category"
            text="Mark a spec as “Can be a product option” in Attributes & Specs, e.g. Storage or Colour, and add it to this category."
        />

        <template v-else>
            <p class="tt-muted tt-small mb-4">{{ $t("Pick the options this product is sold in. One variant is created for each combination, with its own price and stock.") }}</p>
            <div class="tt-variant-options">
                <v-autocomplete-trz
                    v-for="a in options"
                    :key="a.id"
                    v-model="picked[a.id]"
                    :items="a.values.map((v) => ({ value: v.id, title: v.value }))"
                    :label="a.name"
                    multiple
                    chips
                    closable-chips
                    density="compact"
                />
            </div>
            <div class="d-flex flex-wrap align-center ga-3 mt-3">
                <v-btn-secondary prepend-icon="mdi-auto-fix" :disabled="!combinations.length" @click="generate">
                    {{ variants.length ? $t("Update variants") : $t("Create {n} variants", { n: $num(combinations.length) }) }}
                </v-btn-secondary>
                <span v-if="combinations.length > 100" class="tt-warn-text"><v-icon size="15">mdi-alert-outline</v-icon>{{ $t("That is more than 100 combinations. Pick fewer options.") }}</span>
            </div>
        </template>

        <template v-if="variants.length">
            <div class="tt-variant-bulk">
                <span class="tt-muted tt-small">{{ $t("Set for all variants:") }}</span>
                <v-input-trz v-model="bulk.price" type="number" density="compact" :label="$t('Price')" :prefix="currency" min="0" />
                <v-input-trz v-model="bulk.stock_qty" type="number" density="compact" :label="$t('Stock')" min="0" />
                <v-btn-tertiary size="small" :disabled="bulk.price === '' && bulk.stock_qty === ''" @click="applyBulk">{{ $t("Apply") }}</v-btn-tertiary>
            </div>

            <div class="tt-variant-table" role="table" :aria-label="$t('Variants')">
                <div class="tt-variant-row is-head" role="row">
                    <span role="columnheader">{{ $t("Variant") }}</span>
                    <span role="columnheader">{{ $t("Price") }}</span>
                    <span role="columnheader">{{ $t("Sale price") }}</span>
                    <span role="columnheader">{{ $t("Stock") }}</span>
                    <span role="columnheader">{{ $t("SKU") }}</span>
                    <span role="columnheader">{{ $t("Available") }}</span>
                    <span></span>
                </div>
                <div v-for="(v, i) in variants" :key="v.key" class="tt-variant-row" :class="{ 'is-off': !v.is_active }" role="row">
                    <b role="cell">{{ label(v) }}</b>
                    <v-input-trz v-model.number="v.price" type="number" density="compact" :prefix="currency" min="0" :aria-label="$t('Price')" :error-messages="errors[`variants.${i}.price`]" />
                    <v-input-trz v-model.number="v.sale_price" type="number" density="compact" :prefix="currency" min="0" :aria-label="$t('Sale price')" :error-messages="saleError(v)" />
                    <v-input-trz v-model.number="v.stock_qty" type="number" density="compact" min="0" :aria-label="$t('Stock')" />
                    <v-input-trz v-model="v.sku" density="compact" :aria-label="$t('SKU')" :error-messages="errors[`variants.${i}.sku`]" />
                    <v-switch-trz v-model="v.is_active" :aria-label="$t('Available to buy')" />
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Remove variant')" @click="removeRow(i)" />
                </div>
            </div>
            <p class="tt-muted tt-small mt-2">{{ $t("The product shows the lowest price, and its stock is the total of all variants.") }}</p>
        </template>
    </div>
</template>

<script>
/*
 * Variant builder. v-model: [{ id?, sku, price, sale_price, stock_qty, is_active, value_ids: [] }]
 * `attributes` are the option specs (is_variant) available for the product's category.
 */
let keySeq = 0;

export default {
    name: "ProductVariants",
    props: {
        modelValue: { type: Array, default: () => [] },
        attributes: { type: Array, default: () => [] },
        basePrice: { type: [Number, String], default: 0 },
        errors: { type: Object, default: () => ({}) },
    },
    emits: ["update:modelValue"],
    data() {
        return { picked: {}, bulk: { price: "", stock_qty: "" } };
    },
    computed: {
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
        variants() {
            return this.modelValue;
        },
        options() {
            return this.attributes.filter((a) => a.is_variant && a.values.length);
        },
        valueIndex() {
            const map = {};
            this.attributes.forEach((a) => a.values.forEach((v) => (map[v.id] = { ...v, attribute: a })));
            return map;
        },
        // Every combination of the picked values, in spec order.
        combinations() {
            const lists = this.options.map((a) => this.picked[a.id] || []).filter((l) => l.length);
            if (!lists.length) return [];
            return lists.reduce((acc, list) => acc.flatMap((combo) => list.map((id) => [...combo, id])), [[]]);
        },
    },
    watch: {
        // Rebuild the option pickers from existing variants (when editing a product).
        attributes: { immediate: true, handler: "syncPicked" },
    },
    methods: {
        syncPicked() {
            const picked = {};
            this.variants.forEach((v) =>
                v.value_ids.forEach((id) => {
                    const a = this.valueIndex[id]?.attribute;
                    if (a) picked[a.id] = [...new Set([...(picked[a.id] || []), id])];
                })
            );
            this.picked = picked;
        },
        label(v) {
            return v.value_ids.map((id) => this.valueIndex[id]?.value || "?").join(" / ");
        },
        saleError(v) {
            return v.sale_price !== null && v.sale_price !== "" && Number(v.sale_price) >= Number(v.price) ? this.$t("Must be lower than the price") : null;
        },
        // Keeps rows that still match (with their prices and stock), adds new combinations, drops the rest.
        generate() {
            const key = (ids) => [...ids].sort((a, b) => a - b).join("-");
            const existing = Object.fromEntries(this.variants.map((v) => [key(v.value_ids), v]));
            const rows = this.combinations.slice(0, 100).map(
                (ids) =>
                    existing[key(ids)] || {
                        key: `n${++keySeq}`,
                        id: null,
                        sku: "",
                        price: Number(this.basePrice) || 0,
                        sale_price: null,
                        stock_qty: 0,
                        is_active: true,
                        value_ids: ids,
                    }
            );
            this.$emit("update:modelValue", rows);
        },
        removeRow(i) {
            const rows = this.variants.filter((_, j) => j !== i);
            this.$emit("update:modelValue", rows);
            this.$nextTick(this.syncPicked);
        },
        applyBulk() {
            this.variants.forEach((v) => {
                if (this.bulk.price !== "") v.price = Number(this.bulk.price);
                if (this.bulk.stock_qty !== "") v.stock_qty = Number(this.bulk.stock_qty);
            });
            this.bulk = { price: "", stock_qty: "" };
        },
    },
};
</script>
