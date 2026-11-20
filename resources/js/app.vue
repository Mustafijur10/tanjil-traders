<template>
    <v-app>
        <component :is="layout" />

        <v-snackbar
            :model-value="snackbar.open"
            :color="snackbarColor"
            location="bottom right"
            timeout="3200"
            @update:model-value="!$event && $store.commit('hideSnackbar')"
        >
            {{ snackbar.text }}
            <template #actions>
                <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="$store.commit('hideSnackbar')" />
            </template>
        </v-snackbar>
    </v-app>
</template>

<script>
import AdminLayout from "@/AppLayout.vue";
import AuthLayout from "@/AuthLayout.vue";
import FrontLayout from "@/FrontLayout.vue";

export default {
    name: "App",
    components: { AdminLayout, AuthLayout, FrontLayout },
    computed: {
        layout() {
            return { admin: "AdminLayout", auth: "AuthLayout" }[this.$store.getters.layout] || "FrontLayout";
        },
        snackbar() {
            return this.$store.state.snackbar;
        },
        snackbarColor() {
            return { error: "error", warning: "warning", info: "info" }[this.snackbar.type] || "secondary";
        },
    },
};
</script>
