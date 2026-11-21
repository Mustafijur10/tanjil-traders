<template>
    <div>
        <PageHeader title="Pages" subtitle="Information pages such as About us, Warranty policy and Return policy. Pages marked “In footer” are linked at the bottom of every page.">
            <v-btn-primary prepend-icon="mdi-plus" to="/admin/pages/new">{{ $t("Add page") }}</v-btn-primary>
        </PageHeader>

        <section class="tt-box">
            <div class="tt-toolbar">
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search pages')" clearable :aria-label="$t('Search')" />
                <div class="tt-toolbar-grow"></div>
                <v-btn-secondary v-if="missingStandard.length" size="small" prepend-icon="mdi-file-document-multiple-outline" :loading="seeding" @click="addStandard">
                    {{ $tn(missingStandard.length, "Add {n} standard page", "Add {n} standard pages") }}
                </v-btn-secondary>
            </div>

            <DataTable :headers="headers" :items="filtered" :loading="loading" class="tt-edt-clickable" @click-row="(row) => $router.push(`/admin/pages/${row.id}`)">
                <template #item-title="row">
                    <div class="tt-two-line">
                        <b>{{ row.title }}</b>
                        <small>/{{ row.slug }}</small>
                    </div>
                </template>
                <template #item-show_in_footer="row">
                    <v-icon v-if="row.show_in_footer" size="18" color="primary" :aria-label="$t('In footer')">mdi-check-circle</v-icon>
                    <span v-else class="tt-muted">—</span>
                </template>
                <template #item-seo="row">
                    <StatusChip :label="row.meta_description ? 'Ready' : 'Missing description'" :tone="row.meta_description ? 'green' : 'amber'" />
                </template>
                <template #item-is_published="row">
                    <StatusChip :label="row.is_published ? 'Published' : 'Draft'" :tone="row.is_published ? 'green' : 'amber'" />
                </template>
                <template #item-updated_at="row"><span class="tt-muted tt-nowrap-cell">{{ $date(row.updated_at) }}</span></template>
                <template #item-actions="row">
                    <div class="d-flex justify-end" @click.stop>
                        <v-btn-tertiary v-if="row.is_published" icon="mdi-open-in-new" size="small" :href="`/${row.slug}`" target="_blank" :aria-label="$t('View on website')" />
                        <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(row)" />
                    </div>
                </template>
                <template #empty-message>
                    <EmptyState icon="mdi-file-document-outline" title="No pages yet" text="Most shops need About us, Warranty, Return and Privacy pages. Add them in one click and fill in the text.">
                        <v-btn-primary prepend-icon="mdi-file-document-multiple-outline" :loading="seeding" @click="addStandard">{{ $t("Add standard pages") }}</v-btn-primary>
                    </EmptyState>
                </template>
            </DataTable>
        </section>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: deleting?.title || '' })" text="Links to this page will stop working." :loading="removing" @confirm="remove" />
    </div>
</template>

<script>
import api from "@/api";

// Pages most Bangladeshi online shops need; created as drafts with a starting outline.
const STANDARD = [
    { title: "About us", slug: "about-us", content: "<h2>Who we are</h2><p>Tell customers about your shop, how long you have been in business and what you sell.</p>" },
    { title: "Warranty policy", slug: "warranty-policy", content: "<h2>Warranty</h2><p>Explain which products have official or brand warranty, how long it lasts and how to claim it.</p>" },
    { title: "Return & refund policy", slug: "return-policy", content: "<h2>Returns</h2><p>Explain how many days customers have to return a product, in what condition, and how refunds are paid.</p>" },
    { title: "Delivery information", slug: "delivery-information", content: "<h2>Delivery</h2><p>Delivery areas, charges and how long delivery takes inside and outside Dhaka.</p>" },
    { title: "Privacy policy", slug: "privacy-policy", content: "<h2>Your information</h2><p>Explain what customer information you keep and how you use it.</p>" },
    { title: "Terms & conditions", slug: "terms", content: "<h2>Terms</h2><p>The rules for buying from your shop.</p>" },
];

export default {
    name: "Pages",
    data: () => ({ items: [], loading: true, search: "", seeding: false, deleteDialog: false, deleting: null, removing: false }),
    computed: {
        headers() {
            return [
                { text: this.$t("Page"), value: "title", sortable: true },
                { text: this.$t("In footer"), value: "show_in_footer", width: 110 },
                { text: this.$t("Search engines"), value: "seo" },
                { text: this.$t("Status"), value: "is_published", sortable: true },
                { text: this.$t("Updated"), value: "updated_at", sortable: true },
                { text: "", value: "actions", width: 100 },
            ];
        },
        filtered() {
            const q = (this.search || "").trim().toLowerCase();
            return q ? this.items.filter((p) => p.title.toLowerCase().includes(q) || p.slug.includes(q)) : this.items;
        },
        missingStandard() {
            const slugs = new Set(this.items.map((p) => p.slug));
            return this.items.length ? STANDARD.filter((s) => !slugs.has(s.slug)) : [];
        },
    },
    created() {
        this.load();
    },
    methods: {
        async load() {
            this.loading = true;
            try {
                this.items = (await api.get("/admin/pages")).data;
            } finally {
                this.loading = false;
            }
        },
        async addStandard() {
            const slugs = new Set(this.items.map((p) => p.slug));
            this.seeding = true;
            try {
                for (const p of STANDARD.filter((s) => !slugs.has(s.slug))) {
                    await api.post("/admin/pages", { ...p, title: this.$t(p.title), is_published: false, show_in_footer: true });
                }
                this.$store.dispatch("notify", this.$t("Pages added as drafts. Open each one, write your text and publish it."));
                this.load();
            } finally {
                this.seeding = false;
            }
        },
        askDelete(p) {
            this.deleting = p;
            this.deleteDialog = true;
        },
        async remove() {
            this.removing = true;
            try {
                await api.delete(`/admin/pages/${this.deleting.id}`);
                this.deleteDialog = false;
                this.load();
            } finally {
                this.removing = false;
            }
        },
    },
};
</script>
