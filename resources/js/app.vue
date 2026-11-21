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
import { h } from "vue";
import { RouterView } from "vue-router";
import AdminLayout from "@/AppLayout.vue";
import AuthLayout from "@/AuthLayout.vue";
import FrontLayout from "@/FrontLayout.vue";

// Printed pages (invoices): just the page, nothing around it
const PrintLayout = { name: "PrintLayout", render: () => h(RouterView) };

export default {
    name: "App",
    components: { AdminLayout, AuthLayout, FrontLayout, PrintLayout },
    computed: {
        layout() {
            return { admin: "AdminLayout", auth: "AuthLayout", print: "PrintLayout" }[this.$store.getters.layout] || "FrontLayout";
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
