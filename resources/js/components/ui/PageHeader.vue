<template>
    <header class="tt-page-head">
        <span v-if="entryIcon" class="tt-page-icon"><v-icon :icon="entryIcon" size="22" /></span>
        <div class="tt-page-head-text">
            <nav v-if="crumbs.length" class="tt-crumbs" :aria-label="$t('Breadcrumb')">
                <template v-for="(c, i) in crumbs" :key="i">
                    <router-link v-if="c.to" :to="c.to">{{ $t(c.title) }}</router-link>
                    <span v-else>{{ $t(c.title) }}</span>
                    <v-icon v-if="i < crumbs.length - 1" size="14">mdi-chevron-right</v-icon>
                </template>
            </nav>
            <h1>{{ $t(title) }}</h1>
            <p v-if="subtitle">{{ $t(subtitle) }}</p>
        </div>
        <div v-if="$slots.default" class="tt-page-head-actions">
            <slot />
        </div>
    </header>
</template>

<script>
/*
 * Page title with the section icon and breadcrumb taken from the admin menu, plus action buttons (default slot).
 *   <PageHeader title="Products" subtitle="Everything in your catalog." />
 */
import menu, { findMenuEntry } from "@/admin/menu.js";

export default {
    name: "PageHeader",
    props: {
        title: { type: String, required: true },
        subtitle: { type: String, default: "" },
        icon: { type: String, default: "" },
    },
    computed: {
        entry() {
            return this.$route.path.startsWith("/admin") ? findMenuEntry(this.$route.path, menu) : null;
        },
        entryIcon() {
            return this.icon || this.entry?.group?.icon || this.entry?.item?.icon || "";
        },
        // e.g. Catalog › All Products › Samsung Galaxy A55
        crumbs() {
            const e = this.entry;
            if (!e) return [];
            const list = [];
            if (e.group) list.push({ title: e.group.title });
            const isTitlePage = e.item.title === this.title;
            list.push({ title: e.item.title, to: isTitlePage ? null : e.item.to });
            return list;
        },
    },
};
</script>
