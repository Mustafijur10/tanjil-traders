<template>
    <div class="tt-metric-tiles">
        <div v-for="m in items" :key="m.label" class="tt-metric" :class="'is-' + (m.tone || 'green')">
            <div class="tt-metric-top">
                <span class="tt-metric-ico"><v-icon :icon="m.icon" size="18" /></span>
                <small>{{ $t(m.label) }}</small>
            </div>
            <b class="tt-num">{{ m.value }}</b>
            <span v-if="m.delta !== undefined" class="tt-metric-delta" :class="deltaClass(m)">
                <template v-if="m.delta === null">{{ $t("No earlier data") }}</template>
                <template v-else>
                    <v-icon size="14">{{ m.delta > 0 ? "mdi-arrow-up" : m.delta < 0 ? "mdi-arrow-down" : "mdi-minus" }}</v-icon>
                    {{ Math.abs(m.delta) > 999 ? "999+" : $num(Math.abs(m.delta)) }}% <span>{{ $t("vs previous") }}</span>
                </template>
            </span>
            <span v-else-if="m.note" class="tt-metric-delta">{{ m.note }}</span>
        </div>
    </div>
</template>

<script>
/*
 * Headline numbers with the change against the previous period.
 * items: [{ label, value (formatted), delta (% or null), icon, tone, invert (true when lower is better) }]
 * The arrow and the words carry the meaning, the colour only supports them.
 */
export default {
    name: "MetricTiles",
    props: {
        items: { type: Array, required: true },
    },
    methods: {
        deltaClass(m) {
            if (!m.delta) return "is-flat";
            const good = m.invert ? m.delta < 0 : m.delta > 0;
            return good ? "is-good" : "is-bad";
        },
    },
};
</script>
