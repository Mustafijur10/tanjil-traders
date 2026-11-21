/*
 * App entry. Every global package, component and alias is registered here, once.
 * Styles: resources/sass/app.scss is the only stylesheet; components have no <style> blocks.
 */
import "vue3-easy-data-table/dist/style.css"; // base table styles; colours are overridden in app.scss (.tt-edt)
import "../sass/app.scss";

import { createApp, defineAsyncComponent } from "vue";

import App from "@/app.vue";
import router from "@/router";
import store from "@/store";
import vuetify from "@/plugins/vuetify";
import i18n from "@/plugins/i18n";
import tracker from "@/plugins/tracker";

// Shared UI building blocks, available in every template without importing
import PageHeader from "@/components/ui/PageHeader.vue";
import EmptyState from "@/components/ui/EmptyState.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import ImageUpload from "@/components/ui/ImageUpload.vue";
import StatusChip from "@/components/ui/StatusChip.vue";
import PrefToggles from "@/components/ui/PrefToggles.vue";
import DataTable from "@/components/ui/DataTable.vue";
// Rich text editor (Tiptap) is loaded only by pages that use it.
const RichText = defineAsyncComponent(() => import("@/components/ui/RichText.vue"));
import { VueDraggable } from "vue-draggable-plus";

const app = createApp(App);

app.use(store);
app.use(router);
app.use(vuetify);
app.use(i18n);
app.use(tracker, { router }); // storefront visits and Core Web Vitals for Analytics
// Charts (vue3-apexcharts) are registered by the pages that use them, so other pages stay light.

app.component("PageHeader", PageHeader);
app.component("EmptyState", EmptyState);
app.component("ConfirmDialog", ConfirmDialog);
app.component("ImageUpload", ImageUpload);
app.component("StatusChip", StatusChip);
app.component("PrefToggles", PrefToggles);
// Every admin table: <DataTable> (vue3-easy-data-table with the store's look)
app.component("DataTable", DataTable);
app.component("RichText", RichText);
// Drag and drop lists: <VueDraggable v-model="items" handle=".tt-drag"> <div v-for="item in items" :key="item.id">…</div> </VueDraggable>
app.component("VueDraggable", VueDraggable);

// Theme and language are applied before the first page renders.
store.dispatch("prefs/boot");

router.isReady().then(() => app.mount("#app"));
