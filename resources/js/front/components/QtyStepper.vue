<template>
    <div class="tt-qty" :class="{ 'is-small': small }" role="group" :aria-label="$t('Quantity')">
        <button type="button" :disabled="modelValue <= min" :aria-label="$t('Less')" @click="set(modelValue - 1)"><v-icon :size="small ? 16 : 18">mdi-minus</v-icon></button>
        <input :value="modelValue" type="number" inputmode="numeric" :min="min" :max="max" :aria-label="$t('Quantity')" @change="set(Number($event.target.value))" />
        <button type="button" :disabled="modelValue >= max" :aria-label="$t('More')" @click="set(modelValue + 1)"><v-icon :size="small ? 16 : 18">mdi-plus</v-icon></button>
    </div>
</template>

<script>
/* − 2 + quantity control. <QtyStepper v-model="qty" :max="stock" /> */
export default {
    name: "QtyStepper",
    props: {
        modelValue: { type: Number, default: 1 },
        min: { type: Number, default: 1 },
        max: { type: Number, default: 99 },
        small: { type: Boolean, default: false },
    },
    emits: ["update:modelValue"],
    methods: {
        set(v) {
            const n = Math.max(this.min, Math.min(Number.isFinite(v) ? Math.round(v) : this.min, Math.max(this.max, this.min)));
            this.$emit("update:modelValue", n);
            if (n !== v) this.$forceUpdate();
        },
    },
};
</script>
