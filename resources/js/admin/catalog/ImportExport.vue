<template>
    <div>
        <PageHeader title="Import / Export" subtitle="Add or update many records at once from Excel. Download a template, fill it in, upload it, check the preview, then import." />

        <div class="tt-imp-types" role="tablist" :aria-label="$t('What to import')">
            <button v-for="ty in typeList" :key="ty.key" type="button" role="tab" class="tt-imp-type" :class="{ 'is-on': type === ty.key }" :aria-selected="type === ty.key" @click="setType(ty.key)">
                <v-icon size="22">{{ ty.icon }}</v-icon>
                <b>{{ $t(ty.label) }}</b>
                <small>{{ $t(ty.hint) }}</small>
            </button>
        </div>

        <div class="tt-detail">
            <div class="tt-detail-main">
                <!-- Upload -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-file-upload-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Upload {what}", { what: $t(current.label).toLowerCase() }) }}</h2><p>{{ $t(current.note) }}</p></div>
                    </div>
                    <div class="tt-box-body">
                        <label
                            class="tt-dropzone"
                            :class="{ 'is-over': dragOver }"
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="onDrop"
                        >
                            <input type="file" accept=".xlsx" class="tt-sr-only" @change="onFile($event.target.files[0]); $event.target.value = ''" />
                            <v-icon size="36">mdi-microsoft-excel</v-icon>
                            <b>{{ file ? file.name : $t("Drop your Excel file here, or click to choose") }}</b>
                            <small>{{ $t("Excel .xlsx, up to 2,000 rows. Use the template so the columns match.") }}</small>
                        </label>
                        <v-alert v-if="readError" type="error" variant="tonal" density="compact" class="mt-4">{{ readError }}</v-alert>
                        <v-alert v-if="unknown.length" type="info" variant="tonal" density="compact" class="mt-4">
                            {{ $t("These columns were not recognised and will be ignored: {list}", { list: unknown.join(", ") }) }}
                        </v-alert>
                    </div>
                </section>

                <!-- Preview / result -->
                <section v-if="rows.length" class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">{{ done ? "mdi-check-decagram-outline" : "mdi-table-eye" }}</v-icon></span>
                        <div class="flex-grow-1">
                            <h2>{{ $t(done ? "Import finished" : "Preview") }}</h2>
                            <p>{{ $t(done ? "Here is what happened to each row." : "Nothing is saved yet. Rows with a problem are skipped; fix them in Excel and upload again.") }}</p>
                        </div>
                    </div>
                    <div class="tt-box-body">
                        <div class="tt-imp-summary">
                            <span class="is-create"><b class="tt-num">{{ $num(summary.create || 0) }}</b>{{ $t(done ? "created" : "new") }}</span>
                            <span class="is-update"><b class="tt-num">{{ $num(summary.update || 0) }}</b>{{ $t(done ? "updated" : "will update") }}</span>
                            <span class="is-error"><b class="tt-num">{{ $num(summary.error || 0) }}</b>{{ $t("with problems") }}</span>
                            <div class="tt-toolbar-grow"></div>
                            <div class="tt-segments">
                                <button type="button" :class="{ 'is-on': show === 'all' }" @click="show = 'all'">{{ $t("All rows") }}</button>
                                <button type="button" :class="{ 'is-on': show === 'error' }" @click="show = 'error'">{{ $t("Problems") }}</button>
                            </div>
                        </div>
                    </div>
                    <DataTable :headers="previewHeaders" :items="previewItems" :loading="checking" :rows-per-page="25" :hide-footer="previewItems.length <= 25">
                        <template #item-_row="r"><span class="tt-muted tt-num">{{ r._row }}</span></template>
                        <template #item-_status="r">
                            <StatusChip v-if="r._status" :label="$t(statusLabel[r._status])" :tone="{ create: 'green', update: 'blue', error: 'red' }[r._status]" />
                        </template>
                        <template #item-_errors="r"><span class="tt-danger-text tt-small">{{ (r._errors || []).join(" ") }}</span></template>
                    </DataTable>
                    <div v-if="!done" class="tt-box-foot">
                        <v-btn-tertiary @click="reset">{{ $t("Cancel") }}</v-btn-tertiary>
                        <v-btn-primary prepend-icon="mdi-database-import-outline" :loading="importing" :disabled="checking || !(summary.create + summary.update)" @click="runImport">
                            {{ $tn(summary.create + summary.update, "Import {n} row", "Import {n} rows") }}
                        </v-btn-primary>
                    </div>
                    <div v-else class="tt-box-foot">
                        <v-btn-secondary prepend-icon="mdi-upload" @click="reset">{{ $t("Import another file") }}</v-btn-secondary>
                        <v-btn-primary :to="current.to">{{ $t("Open {what}", { what: $t(current.label).toLowerCase() }) }}</v-btn-primary>
                    </div>
                </section>
            </div>

            <aside class="tt-detail-side">
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-file-table-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("1. Get the template") }}</h2><p>{{ $t("Column names, an example row and a sheet explaining each column.") }}</p></div>
                    </div>
                    <div class="tt-box-body tt-vstack">
                        <v-btn-secondary block prepend-icon="mdi-download-outline" :disabled="!columns" @click="template">{{ $t("Empty template") }}</v-btn-secondary>
                        <v-btn-tertiary block prepend-icon="mdi-table-arrow-down" :loading="exporting" :disabled="!columns" @click="exportCurrent">{{ $t("Current {what} in this format", { what: $t(current.label).toLowerCase() }) }}</v-btn-tertiary>
                        <p class="tt-muted tt-small">{{ $t("Download what you have, change prices or details in Excel, and import it back. Matching records are updated, not duplicated.") }}</p>
                    </div>
                </section>
                <section class="tt-box">
                    <div class="tt-box-head"><span class="tt-box-icon"><v-icon size="20">mdi-format-list-checks</v-icon></span><div class="flex-grow-1"><h2>{{ $t("Columns") }}</h2></div></div>
                    <ul class="tt-imp-cols">
                        <li v-for="c in columns || []" :key="c.key">
                            <b>{{ c.label }}<em v-if="c.required"> *</em></b>
                            <small v-if="c.help">{{ c.help }}</small>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>

<script>
import api from "@/api";
import { downloadTemplate, downloadRows, readSheet } from "@/utils/exporter";

const TYPES = [
    { key: "categories", label: "Categories", icon: "mdi-shape-outline", hint: "With subcategories", note: "Main categories and subcategories in one file. Write the main category in “Parent category”.", to: "/admin/categories" },
    { key: "brands", label: "Brands", icon: "mdi-tag-multiple-outline", hint: "Names and details", note: "Brands are matched by name.", to: "/admin/brands" },
    { key: "attributes", label: "Specs", icon: "mdi-format-list-bulleted-type", hint: "RAM, storage, colour…", note: "Spec names with their values, for filters and comparisons.", to: "/admin/attributes" },
    { key: "products", label: "Products", icon: "mdi-package-variant-closed", hint: "Prices, stock, specs", note: "Matched by SKU (or name). Import categories first. Products with options keep their options.", to: "/admin/products" },
    { key: "stock", label: "Stock count", icon: "mdi-warehouse", hint: "Update quantities by SKU", note: "Set the counted quantity for each SKU. Every change is logged in Stock Movements.", to: "/admin/inventory" },
];

export default {
    name: "ImportExport",
    data() {
        return {
            types: null,
            type: TYPES.some((t) => t.key === this.$route.query.type) ? this.$route.query.type : "products",
            file: null,
            rows: [],
            results: [],
            summary: {},
            unknown: [],
            readError: "",
            dragOver: false,
            checking: false,
            importing: false,
            exporting: false,
            done: false,
            show: "all",
            statusLabel: { create: "New", update: "Update", error: "Problem" },
        };
    },
    computed: {
        typeList: () => TYPES,
        current() {
            return TYPES.find((t) => t.key === this.type);
        },
        columns() {
            return this.types?.[this.type] || null;
        },
        previewHeaders() {
            const shown = (this.columns || []).filter((c) => this.rows.some((r) => r[c.key] !== null && r[c.key] !== undefined && r[c.key] !== "")).slice(0, 6);
            return [
                { text: this.$t("Row"), value: "_row", width: 60 },
                { text: this.$t("Result"), value: "_status", width: 110 },
                // The problem comes before the data columns so it is visible without scrolling
                ...(this.results.some((r) => r.status === "error") ? [{ text: this.$t("Problem"), value: "_errors", width: 240 }] : []),
                ...shown.map((c) => ({ text: c.label, value: c.key })),
            ];
        },
        previewItems() {
            const items = this.rows.map((r, i) => ({ ...r, _row: this.results[i]?.row ?? i + 2, _status: this.results[i]?.status, _errors: this.results[i]?.errors }));
            return this.show === "error" ? items.filter((r) => r._status === "error") : items;
        },
    },
    async created() {
        this.types = (await api.get("/admin/import/types")).data;
    },
    methods: {
        setType(key) {
            this.type = key;
            this.$router.replace({ query: { type: key } });
            this.reset();
        },
        reset() {
            this.file = null;
            this.rows = [];
            this.results = [];
            this.summary = {};
            this.unknown = [];
            this.readError = "";
            this.done = false;
            this.show = "all";
        },
        template() {
            downloadTemplate(this.type, this.columns, this.current.label);
        },
        async exportCurrent() {
            this.exporting = true;
            try {
                const { data } = await api.get(`/admin/import/${this.type}/rows`);
                await downloadRows(this.type, this.columns, data, this.current.label);
            } finally {
                this.exporting = false;
            }
        },
        onDrop(e) {
            this.dragOver = false;
            this.onFile(e.dataTransfer.files[0]);
        },
        async onFile(file) {
            if (!file) return;
            this.reset();
            if (!/\.xlsx$/i.test(file.name)) {
                this.readError = this.$t("Please choose an Excel .xlsx file. In Excel use File › Save As › Excel Workbook.");
                return;
            }
            this.file = file;
            try {
                const { rows, unknown, missing } = await readSheet(file, this.columns);
                this.unknown = unknown;
                if (missing.length) {
                    this.readError = this.$t("These required columns are missing: {list}. Use the template.", { list: missing.join(", ") });
                    return;
                }
                if (!rows.length) {
                    this.readError = this.$t("The file has no rows under the column names.");
                    return;
                }
                this.rows = rows;
                await this.send(true);
            } catch (e) {
                this.readError = this.$t("The file could not be read. Save it again as .xlsx and try once more.");
            }
        },
        async send(dryRun) {
            this[dryRun ? "checking" : "importing"] = true;
            try {
                const { data } = await api.post(`/admin/import/${this.type}`, { rows: this.rows, dry_run: dryRun });
                this.results = data.results;
                this.summary = data.summary;
                if (!dryRun) {
                    this.done = true;
                    this.$store.dispatch("notify", this.$t("{c} created, {u} updated.", { c: data.summary.create, u: data.summary.update }));
                }
                if (data.summary.error) this.show = dryRun && data.summary.error ? "all" : "error";
            } finally {
                this.checking = this.importing = false;
            }
        },
        runImport() {
            this.send(false);
        },
    },
};
</script>
