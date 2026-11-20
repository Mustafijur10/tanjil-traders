<template>
    <header class="tt-page-head">
        <div class="tt-page-head-text">
            <h1>{{ $t(title) }}</h1>
            <p v-if="subtitle">{{ $t(subtitle) }}</p>
        </div>
        <div v-if="$slots.default" class="tt-page-head-actions">
            <slot />
        </div>
    </header>
</template>

<script>
/* Page title, one-line description and action buttons (default slot). */
export default {
    name: "PageHeader",
    props: {
        title: { type: String, required: true },
        subtitle: { type: String, default: "" },
    },
};
</script>
