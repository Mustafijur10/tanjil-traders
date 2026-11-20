<template>
    <v-dialog-trz :model-value="modelValue" max-width="420" @update:model-value="$emit('update:modelValue', $event)">
        <v-card class="tt-confirm pa-6">
            <div class="tt-confirm-icon" :class="{ 'is-primary': tone === 'primary' }">
                <v-icon :icon="tone === 'primary' ? 'mdi-help-circle-outline' : 'mdi-alert-outline'" size="22" />
            </div>
            <h3>{{ $t(title) }}</h3>
            <p v-if="text">{{ $t(text) }}</p>
            <div class="d-flex justify-end ga-2 mt-6">
                <v-btn-tertiary :disabled="loading" @click="$emit('update:modelValue', false)">{{ $t("Cancel") }}</v-btn-tertiary>
                <v-btn-primary v-if="tone === 'primary'" :loading="loading" @click="$emit('confirm')">{{ $t(confirmText) }}</v-btn-primary>
                <v-btn-danger v-else :loading="loading" @click="$emit('confirm')">{{ $t(confirmText) }}</v-btn-danger>
            </div>
        </v-card>
    </v-dialog-trz>
</template>

<script>
/*
 * "Are you sure?" dialog.
 *   <ConfirmDialog v-model="askDelete" title="Delete this brand?" text="Products keep working without a brand."
 *                  confirm-text="Delete" :loading="deleting" @confirm="remove" />
 */
export default {
    name: "ConfirmDialog",
    props: {
        modelValue: { type: Boolean, default: false },
        title: { type: String, required: true },
        text: { type: String, default: "" },
        confirmText: { type: String, default: "Delete" },
        tone: { type: String, default: "danger" }, // danger | primary
        loading: { type: Boolean, default: false },
    },
    emits: ["update:modelValue", "confirm"],
};
</script>
