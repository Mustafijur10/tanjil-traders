<template>
    <div class="tt-empty-state" role="status">
        <div class="tt-empty-state-icon"><v-icon :icon="icon" size="26" /></div>
        <h3>{{ $t(title) }}</h3>
        <p v-if="text">{{ $t(text) }}</p>
        <slot />
    </div>
</template>

<script>
/* Shown when a list has nothing in it. Put a call-to-action button in the default slot. */
export default {
    name: "EmptyState",
    props: {
        icon: { type: String, default: "mdi-inbox-outline" },
        title: { type: String, required: true },
        text: { type: String, default: "" },
    },
};
</script>
