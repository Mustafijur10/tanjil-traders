<template>
    <div>
        <PageHeader title="Brands" subtitle="Brands appear on product pages, in the shop filters and on brand pages. Drag cards to set their order.">
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add brand") }}</v-btn-primary>
        </PageHeader>

        <div class="tt-toolbar tt-toolbar-bare">
            <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search brands')" clearable :aria-label="$t('Search')" />
            <div class="tt-segments" role="tablist" :aria-label="$t('Filter')">
                <button v-for="f in filters" :key="f.value" type="button" role="tab" :class="{ 'is-on': filter === f.value }" :aria-selected="filter === f.value" @click="filter = f.value">
                    {{ $t(f.label) }}<span class="tt-count tt-num">{{ $num(f.count) }}</span>
                </button>
            </div>
        </div>

        <div v-if="loading" class="tt-brand-grid">
            <div v-for="n in 8" :key="n" class="tt-brand-card tt-skel-group"><span class="tt-skel"></span><span class="tt-skel"></span></div>
        </div>

        <section v-else-if="!brands.length" class="tt-box">
            <EmptyState icon="mdi-tag-multiple-outline" title="No brands yet" text="Add the brands you sell, such as Apple, Samsung or Xiaomi.">
                <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add brand") }}</v-btn-primary>
            </EmptyState>
        </section>

        <section v-else-if="!visible.length" class="tt-box">
            <EmptyState icon="mdi-magnify" title="Nothing matches" text="Try a different search or filter." />
        </section>

        <VueDraggable v-else v-model="brands" handle=".tt-drag" :disabled="isFiltered" :animation="150" class="tt-brand-grid" @end="saveOrder">
                <article v-for="b in brands" v-show="isVisible(b)" :key="b.id" class="tt-brand-card" :class="{ 'is-off': !b.is_active }">
                    <v-icon v-if="!isFiltered" class="tt-drag tt-brand-drag" size="18" :aria-label="$t('Drag to reorder')">mdi-drag</v-icon>
                    <button type="button" class="tt-brand-logo" :aria-label="$t('Edit {name}', { name: b.name })" @click="openForm(b)">
                        <img v-if="b.logo" :src="url(b.logo)" :alt="b.name" loading="lazy" />
                        <span v-else>{{ initials(b.name) }}</span>
                    </button>
                    <div class="tt-brand-name">{{ b.name }}</div>
                    <div class="tt-brand-meta">{{ $tn(b.products_count, "{n} product", "{n} products") }}</div>
                    <div class="tt-brand-foot">
                        <v-btn-tertiary
                            size="small"
                            icon
                            :color="b.is_featured ? 'accent' : undefined"
                            :aria-label="$t(b.is_featured ? 'Remove from home page' : 'Feature on home page')"
                            :title="$t(b.is_featured ? 'Featured on home page' : 'Feature on home page')"
                            @click="quickUpdate(b, { is_featured: !b.is_featured })"
                        >
                            <v-icon size="19">{{ b.is_featured ? "mdi-star" : "mdi-star-outline" }}</v-icon>
                        </v-btn-tertiary>
                        <v-switch-trz :model-value="b.is_active" :aria-label="$t('Visible on the website')" :title="$t('Visible on the website')" @update:model-value="quickUpdate(b, { is_active: $event })" />
                        <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(b)" />
                    </div>
                </article>
        </VueDraggable>

        <v-dialog-trz v-model="dialog" max-width="640" scrollable :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit brand" : "Add brand") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model="form.name" :label="$t('Name')" :error-messages="errors.name" autofocus @update:model-value="autoSlug" />
                        <v-input-trz v-model="form.slug" :label="$t('Web address')" prefix="/" :error-messages="errors.slug" @update:model-value="slugTouched = true" />
                        <div class="is-full">
                            <ImageUpload v-model="form.logo" folder="brands" label="Logo" hint="A transparent PNG or SVG on a white background looks best." wide />
                        </div>
                        <v-textarea-trz v-model="form.description" class="is-full" :label="$t('About the brand')" :hint="$t('Shown on the brand page.')" persistent-hint />
                        <v-switch-trz v-model="form.is_active" :label="$t('Visible on the website')" />
                        <v-switch-trz v-model="form.is_featured" :label="$t('Feature on the home page')" />
                        <v-input-trz v-model="form.meta_title" class="is-full" :label="$t('Page title for search engines')" counter="60" :placeholder="form.name" />
                        <v-textarea-trz v-model="form.meta_description" class="is-full" :label="$t('Description for search results')" counter="160" />
                    </div>
                </v-card-text>
                <v-divider />
                <div class="tt-box-foot">
                    <v-btn-danger v-if="form.id" variant="text" class="mr-auto" :disabled="saving" @click="deleteDialog = true">{{ $t("Delete") }}</v-btn-danger>
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.name" @click="save">{{ $t(form.id ? "Save changes" : "Add brand") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog
            v-model="deleteDialog"
            :title="$t('Delete “{name}”?', { name: form.name })"
            text="Its products stay in the shop, just without a brand."
            :loading="removing"
            @confirm="remove"
        />
    </div>
</template>

<script>
import api, { fieldErrors, fileUrl } from "@/api";

const blank = () => ({ id: null, name: "", slug: "", logo: null, description: "", meta_title: "", meta_description: "", is_active: true, is_featured: false });
const slugify = (s) => String(s || "").toLowerCase().normalize("NFKD").replace(/[^\w\s-]/g, "").trim().replace(/[\s_]+/g, "-").replace(/-+/g, "-");

export default {
    name: "Brands",
    data: () => ({
        brands: [],
        loading: true,
        search: "",
        filter: "all",
        dialog: false,
        form: blank(),
        errors: {},
        slugTouched: false,
        saving: false,
        deleteDialog: false,
        removing: false,
    }),
    computed: {
        filters() {
            const b = this.brands;
            return [
                { value: "all", label: "All", count: b.length },
                { value: "featured", label: "Featured", count: b.filter((x) => x.is_featured).length },
                { value: "hidden", label: "Hidden", count: b.filter((x) => !x.is_active).length },
                { value: "empty", label: "No products", count: b.filter((x) => !x.products_count).length },
            ];
        },
        isFiltered() {
            return !!this.search || this.filter !== "all";
        },
        visible() {
            return this.brands.filter(this.isVisible);
        },
    },
    created() {
        this.load();
    },
    methods: {
        url: fileUrl,
        initials(name) {
            return name.split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase();
        },
        isVisible(b) {
            const q = (this.search || "").trim().toLowerCase();
            if (q && !b.name.toLowerCase().includes(q)) return false;
            if (this.filter === "featured") return b.is_featured;
            if (this.filter === "hidden") return !b.is_active;
            if (this.filter === "empty") return !b.products_count;
            return true;
        },
        async load() {
            this.loading = true;
            try {
                this.brands = (await api.get("/admin/brands")).data;
            } finally {
                this.loading = false;
            }
        },
        openForm(brand = null) {
            this.errors = {};
            this.slugTouched = !!brand;
            this.form = brand ? { ...blank(), ...brand } : blank();
            this.dialog = true;
        },
        autoSlug(name) {
            if (!this.slugTouched) this.form.slug = slugify(name);
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                if (this.form.id) await api.put(`/admin/brands/${this.form.id}`, this.form);
                else await api.post("/admin/brands", this.form);
                this.dialog = false;
                this.$store.dispatch("notify", this.$t(this.form.id ? "Brand saved." : "Brand added."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        // Star and visibility switch on the card save at once, and roll back if the server refuses.
        async quickUpdate(brand, change) {
            const before = { ...brand };
            Object.assign(brand, change);
            try {
                await api.put(`/admin/brands/${brand.id}`, { ...before, ...change });
            } catch (e) {
                Object.assign(brand, before);
            }
        },
        async saveOrder() {
            try {
                await api.post("/admin/brands/reorder", { ids: this.brands.map((b) => b.id) });
                this.$store.dispatch("notify", this.$t("Order saved."));
            } catch (e) {
                this.load();
            }
        },
        async remove() {
            this.removing = true;
            try {
                await api.delete(`/admin/brands/${this.form.id}`);
                this.deleteDialog = false;
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Brand deleted."));
                this.load();
            } finally {
                this.removing = false;
            }
        },
    },
};
</script>
