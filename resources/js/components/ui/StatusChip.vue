<template>
    <span class="tt-status" :class="'is-' + toneName">{{ $t(label) }}</span>
</template>

<script>
/*
 * Small coloured status label.
 *   <StatusChip label="Delivered" tone="green" />
 *   <StatusChip :label="order.status" />   tone is picked from the word when not given
 */
const TONES = {
    green: ["active", "published", "reviewed", "delivered", "paid", "approved", "in stock", "yes", "on"],
    amber: ["pending", "draft", "auto", "processing", "low stock", "unpaid", "needs review"],
    blue: ["confirmed", "shipped", "new"],
    red: ["cancelled", "missing", "out of stock", "refunded", "returned", "failed", "inactive", "off"],
};

export default {
    name: "StatusChip",
    props: {
        label: { type: String, required: true },
        tone: { type: String, default: "" },
    },
    computed: {
        toneName() {
            if (this.tone) return this.tone;
            const word = String(this.label).toLowerCase();
            return Object.keys(TONES).find((k) => TONES[k].includes(word)) || "grey";
        },
    },
};
</script>
