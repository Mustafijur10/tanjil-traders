<template>
    <div class="tt-segments" role="tablist" :aria-label="$t('Period')">
        <button v-for="r in ranges" :key="r.days" type="button" role="tab" :class="{ 'is-on': modelValue === r.days }" :aria-selected="modelValue === r.days" @click="$emit('update:modelValue', r.days)">
            {{ $t(r.label) }}
        </button>
    </div>
</template>

<script>
/* Period picker for Analytics pages. v-model is the number of days (1 = today). */
export default {
    name: "RangeTabs",
    props: {
        modelValue: { type: Number, default: 30 },
        options: { type: Array, default: () => [1, 7, 30, 90, 365] },
    },
    emits: ["update:modelValue"],
    computed: {
        ranges() {
            const label = { 1: "Today", 7: "7 days", 30: "30 days", 90: "90 days", 365: "12 months" };
            return this.options.map((days) => ({ days, label: label[days] }));
        },
    },
};
</script>
