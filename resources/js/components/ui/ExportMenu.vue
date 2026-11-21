<template>
    <v-menu location="bottom end">
        <template #activator="{ props }">
            <v-btn-secondary v-bind="props" prepend-icon="mdi-download-outline" append-icon="mdi-chevron-down" :loading="busy" :disabled="disabled">{{ $t(label) }}</v-btn-secondary>
        </template>
        <v-list density="compact" min-width="210">
            <v-list-item prepend-icon="mdi-file-pdf-box" :title="$t('PDF')" :subtitle="$t('To print or share')" @click="run('pdf')" />
            <v-list-item prepend-icon="mdi-microsoft-excel" :title="$t('Excel')" :subtitle="$t('To work with the numbers')" @click="run('excel')" />
        </v-list>
    </v-menu>
</template>

<script>
/*
 * "Download" button with PDF and Excel. The page passes a function that returns the report
 * (see utils/exporter.js); it may be async, e.g. to fetch every page of a long list first.
 *   <ExportMenu :build="exportSpec" />
 */
import { exportExcel, exportPdf } from "@/utils/exporter";

export default {
    name: "ExportMenu",
    props: {
        build: { type: Function, required: true },
        label: { type: String, default: "Download" },
        disabled: { type: Boolean, default: false },
    },
    data: () => ({ busy: false }),
    methods: {
        async run(format) {
            this.busy = true;
            try {
                const spec = await this.build();
                await (format === "pdf" ? exportPdf(spec) : exportExcel(spec));
            } catch (e) {
                console.error(e);
                this.$store.dispatch("notify", { text: this.$t("The download could not be made. Please try again."), type: "error" });
            } finally {
                this.busy = false;
            }
        },
    },
};
</script>
