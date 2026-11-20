/*
 * App entry. Every global package, component and alias is registered here, once.
 * Styles: resources/sass/app.scss is the only stylesheet; components have no <style> blocks.
 */
import "../sass/app.scss";

import { createApp } from "vue";

import App from "@/app.vue";
import router from "@/router";
import store from "@/store";
import vuetify from "@/plugins/vuetify";
import i18n from "@/plugins/i18n";

// Shared UI building blocks, available in every template without importing
import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import ImageUpload from "@/components/ui/ImageUpload.vue";
import StatusChip from "@/components/ui/StatusChip.vue";
import PrefToggles from "@/components/ui/PrefToggles.vue";

const app = createApp(App);

app.use(store);
app.use(router);
app.use(vuetify);
app.use(i18n);
// Charts (vue3-apexcharts) are registered by the pages that use them, so other pages stay light.

app.component("PageHeader", PageHeader);
app.component("EmptyState", EmptyState);
app.component("ConfirmDialog", ConfirmDialog);
app.component("ImageUpload", ImageUpload);
app.component("StatusChip", StatusChip);
app.component("PrefToggles", PrefToggles);

// Theme and language are applied before the first page renders.
store.dispatch("prefs/boot");

router.isReady().then(() => app.mount("#app"));
