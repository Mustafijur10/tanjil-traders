<template>
    <div v-if="rows === undefined || rows === null" class="tt-skel tt-chart-skel"></div>
    <div v-else-if="!rows.length || allZero" class="tt-chart-empty">
        <v-icon size="28">mdi-chart-box-outline</v-icon>
        <span>{{ $t(text) }}</span>
    </div>
    <slot v-else />
</template>

<script>
/* Shows a skeleton while loading, a calm empty message when there is no data, otherwise the chart. */
export default {
    name: "ChartOrEmpty",
    props: {
        rows: { type: Array, default: undefined },
        field: { type: String, default: "" }, // when set, rows whose field is all zero count as empty
        text: { type: String, default: "No data for this period yet." },
    },
    computed: {
        allZero() {
            return this.field ? this.rows.every((r) => !Number(r[this.field])) : false;
        },
    },
};
</script>
