<template>
    <li class="tt-cart-line" :class="{ 'is-gone': line.available === false, 'is-compact': compact }">
        <router-link :to="link" class="tt-cart-line-img" :aria-label="line.name" @click="$emit('navigate')">
            <img v-if="line.image" :src="url(line.image)" alt="" loading="lazy" />
            <v-icon v-else size="24">mdi-image-outline</v-icon>
        </router-link>
        <div class="tt-cart-line-body">
            <router-link :to="link" class="tt-cart-line-name" @click="$emit('navigate')">{{ line.name }}</router-link>
            <span v-if="line.variant_label" class="tt-muted tt-small">{{ line.variant_label }}</span>
            <span v-if="line.available === false" class="tt-cart-line-warn"><v-icon size="15">mdi-alert-circle-outline</v-icon>{{ $t("No longer available. Please remove it.") }}</span>
            <span v-else-if="line.stock !== undefined && line.stock <= 3" class="tt-cart-line-low">{{ $tn(line.stock, "Only {n} left", "Only {n} left") }}</span>
            <div class="tt-cart-line-row">
                <QtyStepper v-if="line.available !== false" :model-value="line.qty" :max="line.stock ?? 99" small @update:model-value="setQty" />
                <span v-if="line.available !== false" class="tt-cart-line-price">
                    <b>{{ $money(line.price * line.qty) }}</b>
                    <small v-if="line.qty > 1" class="tt-muted">{{ $t("{price} each", { price: $money(line.price) }) }}</small>
                </span>
            </div>
        </div>
        <button type="button" class="tt-cart-line-remove" :aria-label="$t('Remove {name}', { name: line.name })" @click="remove"><v-icon size="18">mdi-trash-can-outline</v-icon></button>
    </li>
</template>

<script>
/* One product line in the cart drawer and on the cart page. */
import QtyStepper from "@/front/components/QtyStepper.vue";
import { fileUrl } from "@/api";
import { keyOf } from "@/store/cart";

export default {
    name: "CartLine",
    components: { QtyStepper },
    props: {
        line: { type: Object, required: true },
        compact: { type: Boolean, default: false },
    },
    emits: ["navigate"],
    computed: {
        link() {
            return this.line.slug ? `/product/${this.line.slug}` : "/shop";
        },
    },
    methods: {
        url: fileUrl,
        setQty(qty) {
            this.$store.dispatch("cart/setQty", { key: keyOf(this.line), qty });
        },
        remove() {
            this.$store.dispatch("cart/remove", keyOf(this.line));
        },
    },
};
</script>
