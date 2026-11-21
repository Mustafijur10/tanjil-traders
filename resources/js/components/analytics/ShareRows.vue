<template>
    <ul v-if="rows.length" class="tt-share-rows">
        <li v-for="r in rows" :key="r.name">
            <span class="tt-share-name">{{ r.name }}<small v-if="r.sub">{{ r.sub }}</small></span>
            <b class="tt-num">{{ money ? $money(r.value) : $num(r.value) }}</b>
            <span class="tt-share-bar" :title="pct(r) + '%'"><i :style="{ '--w': pct(r) + '%' }"></i></span>
            <em class="tt-num">{{ $num(pct(r)) }}%</em>
        </li>
    </ul>
    <p v-else class="tt-muted tt-small">{{ $t("No data for this period yet.") }}</p>
</template>

<script>
/* A ranked list with each row's share of the total: label, value, a thin bar and the percentage. */
export default {
    name: "ShareRows",
    props: {
        rows: { type: Array, default: () => [] }, // [{ name, value, sub? }]
        money: { type: Boolean, default: false },
    },
    computed: {
        total() {
            return this.rows.reduce((n, r) => n + (Number(r.value) || 0), 0);
        },
    },
    methods: {
        pct(r) {
            return this.total ? Math.round((r.value / this.total) * 1000) / 10 : 0;
        },
    },
};
</script>
