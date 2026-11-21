<template>
    <div>
        <PageHeader title="Homepage Sections" subtitle="Build the home page from blocks. Drag them into the order you want; switched-off or empty blocks are not shown.">
            <v-btn-secondary prepend-icon="mdi-open-in-new" href="/" target="_blank">{{ $t("View home page") }}</v-btn-secondary>
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add section") }}</v-btn-primary>
        </PageHeader>

        <div class="tt-split">
            <section class="tt-box">
                <div v-if="loading" class="tt-box-body tt-skel-group"><span v-for="n in 5" :key="n" class="tt-skel"></span></div>

                <EmptyState v-else-if="!sections.length" icon="mdi-view-dashboard-edit-outline" title="Your home page has no sections yet" text="Start with a recommended layout and change it any time.">
                    <div class="d-flex flex-wrap justify-center ga-2">
                        <v-btn-primary prepend-icon="mdi-auto-fix" :loading="seeding" @click="addRecommended">{{ $t("Add recommended layout") }}</v-btn-primary>
                        <v-btn-secondary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add one section") }}</v-btn-secondary>
                    </div>
                </EmptyState>

                <VueDraggable v-else v-model="sections" handle=".tt-drag" :animation="150" @end="saveOrder">
                    <div v-for="(s, i) in sections" :key="s.id" class="tt-section-row" :class="{ 'is-off': !s.is_active }">
                        <v-icon class="tt-drag" :aria-label="$t('Drag to reorder')">mdi-drag-vertical</v-icon>
                        <span class="tt-section-num">{{ $num(i + 1) }}</span>
                        <span class="tt-box-ico"><v-icon size="18">{{ typeOf(s).icon }}</v-icon></span>
                        <div class="tt-section-text">
                            <b>{{ s.title }}</b>
                            <span class="tt-muted tt-small">{{ subline(s) }}</span>
                        </div>
                        <v-switch-trz :model-value="s.is_active" :aria-label="$t('Show on the home page')" @update:model-value="quickToggle(s, $event)" />
                        <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(s)" />
                        <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(s)" />
                    </div>
                </VueDraggable>
            </section>

            <aside class="tt-split-aside">
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-ico"><v-icon size="18">mdi-cellphone</v-icon></span>
                        <div>
                            <h2>{{ $t("Page outline") }}</h2>
                            <p>{{ $t("The order visitors scroll through.") }}</p>
                        </div>
                    </div>
                    <div class="tt-box-body">
                        <div class="tt-outline">
                            <div class="tt-outline-block is-bar">{{ $t("Announcement bar") }}</div>
                            <div class="tt-outline-block is-header">{{ $t("Header and menu") }}</div>
                            <div class="tt-outline-block is-hero"><v-icon size="18">mdi-view-carousel-outline</v-icon>{{ $t("Carousel") }}</div>
                            <div v-for="s in sections.filter((x) => x.is_active)" :key="s.id" class="tt-outline-block" :class="'is-' + typeOf(s).shape">
                                <v-icon size="16">{{ typeOf(s).icon }}</v-icon>
                                <span class="tt-ellipsis">{{ s.title }}</span>
                            </div>
                            <div class="tt-outline-block is-footer">{{ $t("Footer") }}</div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>

        <v-dialog-trz v-model="dialog" max-width="720" scrollable :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit section" : "Add section") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-field-label">{{ $t("What should this section show?") }}</div>
                    <div class="tt-type-grid" role="radiogroup" :aria-label="$t('Section type')">
                        <button
                            v-for="t in types"
                            :key="t.value"
                            type="button"
                            role="radio"
                            class="tt-type-card"
                            :class="{ 'is-on': form.type === t.value }"
                            :aria-checked="form.type === t.value"
                            @click="pickType(t)"
                        >
                            <v-icon size="20">{{ t.icon }}</v-icon>
                            <b>{{ $t(t.label) }}</b>
                            <small>{{ $t(t.help) }}</small>
                        </button>
                    </div>

                    <div class="tt-form-grid mt-5">
                        <v-input-trz v-model="form.title" :label="$t('Heading')" :error-messages="errors.title" />
                        <v-input-trz v-model="form.subtitle" :label="$t('Small text under the heading (optional)')" />
                        <v-autocomplete-trz v-if="form.type === 'category'" v-model="form.category_id" :items="categoryOptions" :label="$t('Category')" :error-messages="errors.category_id" />
                        <v-autocomplete-trz v-if="form.type === 'brand'" v-model="form.brand_id" :items="brandOptions" :label="$t('Brand')" :error-messages="errors.brand_id" />
                        <v-select-trz v-model="form.item_limit" :items="[4, 6, 8, 10, 12, 16, 20, 24]" :label="$t('How many to show')" />
                        <v-switch-trz v-model="form.is_active" :label="$t('Show on the home page')" />
                    </div>
                    <v-alert v-if="currentType.note" type="info" variant="tonal" density="compact" class="mt-4">{{ $t(currentType.note) }}</v-alert>
                </v-card-text>
                <v-divider />
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.title" @click="save">{{ $t(form.id ? "Save changes" : "Add section") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: deleting?.title || '' })" text="Only the section is removed. Products stay in the shop." :loading="removing" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";

const TYPES = [
    { value: "categories", label: "Shop by category", icon: "mdi-shape-outline", shape: "tiles", help: "Featured categories as tiles", note: "Shows categories marked “Feature on the home page” in Categories." },
    { value: "deals", label: "Flash deals", icon: "mdi-lightning-bolt-outline", shape: "row", help: "Products on sale, ending soonest first" },
    { value: "featured", label: "Featured products", icon: "mdi-star-outline", shape: "row", help: "Products you marked as featured" },
    { value: "new", label: "New arrivals", icon: "mdi-new-box", shape: "row", help: "The latest products" },
    { value: "best_sellers", label: "Best sellers", icon: "mdi-trophy-outline", shape: "row", help: "Most sold in the last 90 days", note: "Until there are orders, the best-reviewed products are shown." },
    { value: "category", label: "One category", icon: "mdi-folder-outline", shape: "row", help: "Products from a category you choose" },
    { value: "brand", label: "One brand", icon: "mdi-tag-outline", shape: "row", help: "Products from a brand you choose" },
    { value: "brands", label: "Brand logos", icon: "mdi-tag-multiple-outline", shape: "logos", help: "Featured brands", note: "Shows brands marked as featured in Brands." },
    { value: "promo", label: "Promo banners", icon: "mdi-image-area", shape: "banner", help: "Your promo banners", note: "Add the images in Carousel & Banners › Promo banners." },
];

const RECOMMENDED = [
    { type: "categories", title: "Shop by category", item_limit: 12 },
    { type: "deals", title: "Flash deals", subtitle: "Limited-time prices", item_limit: 10 },
    { type: "new", title: "New arrivals", item_limit: 10 },
    { type: "promo", title: "Offers", item_limit: 4 },
    { type: "best_sellers", title: "Best sellers", item_limit: 10 },
    { type: "featured", title: "Recommended for you", item_limit: 10 },
    { type: "brands", title: "Top brands", item_limit: 12 },
];

const blank = () => ({ id: null, type: "featured", title: "Featured products", subtitle: "", category_id: null, brand_id: null, item_limit: 10, is_active: true });

export default {
    name: "HomeSections",
    data: () => ({
        sections: [],
        categories: [],
        brands: [],
        loading: true,
        seeding: false,
        dialog: false,
        form: blank(),
        titleTouched: false,
        errors: {},
        saving: false,
        deleteDialog: false,
        deleting: null,
        removing: false,
    }),
    computed: {
        types: () => TYPES,
        currentType() {
            return TYPES.find((t) => t.value === this.form.type) || TYPES[0];
        },
        categoryOptions() {
            const parents = Object.fromEntries(this.categories.filter((c) => !c.parent_id).map((c) => [c.id, c.name]));
            return this.categories.map((c) => ({ value: c.id, title: c.parent_id ? `${parents[c.parent_id]} › ${c.name}` : c.name }));
        },
        brandOptions() {
            return this.brands.map((b) => ({ value: b.id, title: b.name }));
        },
    },
    created() {
        this.load();
    },
    methods: {
        typeOf(s) {
            return TYPES.find((t) => t.value === s.type) || TYPES[0];
        },
        // "One category: Laptops · up to 10 items" (the type is left out when the heading already says it)
        subline(s) {
            const type = this.$t(this.typeOf(s).label);
            const parts = [];
            if (type !== s.title) parts.push(type + this.detail(s));
            else if (this.detail(s)) parts.push(this.detail(s).replace(/^: /, ""));
            parts.push(this.$tn(s.item_limit, "up to {n} item", "up to {n} items"));
            return parts.join(" · ");
        },
        detail(s) {
            if (s.type === "category" && s.category) return `: ${s.category.name}`;
            if (s.type === "brand" && s.brand) return `: ${s.brand.name}`;
            return "";
        },
        async load() {
            this.loading = true;
            try {
                const [sections, form] = await Promise.all([api.get("/admin/home-sections"), api.get("/admin/products/form-data")]);
                this.sections = sections.data;
                this.categories = form.data.categories;
                this.brands = form.data.brands;
            } finally {
                this.loading = false;
            }
        },
        async addRecommended() {
            this.seeding = true;
            try {
                for (const s of RECOMMENDED) await api.post("/admin/home-sections", { is_active: true, ...s });
                this.$store.dispatch("notify", this.$t("Recommended layout added. Change anything you like."));
                this.load();
            } finally {
                this.seeding = false;
            }
        },
        openForm(s = null) {
            this.errors = {};
            this.titleTouched = !!s;
            this.form = s ? { ...blank(), ...s } : blank();
            this.dialog = true;
        },
        // Picking a type suggests a heading until you type your own.
        pickType(t) {
            const wasDefault = !this.titleTouched || TYPES.some((x) => this.$t(x.label) === this.form.title || x.label === this.form.title);
            this.form.type = t.value;
            if (wasDefault) this.form.title = this.$t(t.label);
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                if (this.form.id) await api.put(`/admin/home-sections/${this.form.id}`, this.form);
                else await api.post("/admin/home-sections", this.form);
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Section saved."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        async quickToggle(s, value) {
            s.is_active = value;
            try {
                await api.put(`/admin/home-sections/${s.id}`, { ...s });
            } catch (e) {
                s.is_active = !value;
            }
        },
        async saveOrder() {
            await api.post("/admin/home-sections/reorder", { ids: this.sections.map((s) => s.id) }).catch(this.load);
        },
        askDelete(s) {
            this.deleting = s;
            this.deleteDialog = true;
        },
        async remove() {
            this.removing = true;
            try {
                await api.delete(`/admin/home-sections/${this.deleting.id}`);
                this.deleteDialog = false;
                this.load();
            } finally {
                this.removing = false;
            }
        },
    },
};
</script>
