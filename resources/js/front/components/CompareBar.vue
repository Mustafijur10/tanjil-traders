<template>
    <transition name="tt-slide-up">
        <aside v-if="items.length && !onComparePage" class="tt-compare-bar" :aria-label="$t('Products to compare')">
            <div class="tt-wrap tt-compare-bar-in">
                <span class="tt-compare-bar-title">
                    <v-icon size="20">mdi-compare-horizontal</v-icon>
                    <span>{{ $t("Compare") }} <b>{{ $num(items.length) }}/{{ $num(max) }}</b></span>
                </span>
                <div class="tt-compare-bar-items">
                    <div v-for="p in items" :key="p.id" class="tt-compare-chip">
                        <span class="tt-thumb"><img v-if="p.image" :src="url(p.image)" alt="" /><v-icon v-else size="18">mdi-image-outline</v-icon></span>
                        <span class="tt-ellipsis">{{ p.name }}</span>
                        <button type="button" :aria-label="$t('Remove {name}', { name: p.name })" @click="$store.dispatch('compare/remove', p.id)"><v-icon size="16">mdi-close</v-icon></button>
                    </div>
                    <span v-for="n in max - items.length" :key="'e' + n" class="tt-compare-chip is-empty">{{ $t("Add a product") }}</span>
                </div>
                <v-btn-tertiary size="small" class="tt-compare-clear" @click="$store.dispatch('compare/clear')">{{ $t("Clear") }}</v-btn-tertiary>
                <v-btn-primary :disabled="items.length < 2" :to="`/compare?ids=${ids}`" prepend-icon="mdi-compare-horizontal">
                    {{ $t(items.length < 2 ? "Pick one more" : "Compare now") }}
                </v-btn-primary>
            </div>
        </aside>
    </transition>
</template>

<script>
/* Floating bar listing the products picked for comparison. */
import { fileUrl } from "@/api";
import { COMPARE_MAX } from "@/store/compare";

export default {
    name: "CompareBar",
    computed: {
        items() {
            return this.$store.state.compare.items;
        },
        ids() {
            return this.items.map((i) => i.id).join(",");
        },
        max: () => COMPARE_MAX,
        onComparePage() {
            return this.$route.path === "/compare";
        },
    },
    methods: { url: fileUrl },
};
</script>
