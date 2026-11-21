<template>
    <div>
        <PageHeader title="Categories" subtitle="Main categories and their subcategories. Drag to change the order they appear in the website menu.">
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add category") }}</v-btn-primary>
        </PageHeader>

        <section class="tt-box">
            <div class="tt-toolbar">
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search categories')" clearable :aria-label="$t('Search')" />
                <div class="tt-toolbar-grow"></div>
                <span class="tt-muted tt-small">{{ $t("{main} main · {sub} subcategories", { main: $num(tree.length), sub: $num(subCount) }) }}</span>
            </div>

            <div v-if="loading" class="tt-box-body tt-skel-group"><span v-for="n in 6" :key="n" class="tt-skel"></span></div>

            <EmptyState v-else-if="!tree.length" icon="mdi-shape-outline" title="No categories yet" text="Add your first category, for example Laptops or Smartphones.">
                <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add category") }}</v-btn-primary>
            </EmptyState>

            <EmptyState v-else-if="search && !visibleTree.length" icon="mdi-magnify" title="Nothing matches" text="Try a different search." />

            <VueDraggable v-else v-model="tree" handle=".tt-drag-main" :disabled="!!search" :animation="150" class="tt-tree" @end="saveOrder">
                    <div v-for="cat in tree" v-show="matches(cat)" :key="cat.id" class="tt-tree-group">
                        <div class="tt-tree-row is-main">
                            <v-icon class="tt-drag tt-drag-main" :class="{ 'is-off': search }" :aria-label="$t('Drag to reorder')">mdi-drag-vertical</v-icon>
                            <button type="button" class="tt-tree-toggle" :aria-expanded="isOpen(cat)" :aria-label="$t(isOpen(cat) ? 'Collapse' : 'Expand')" @click="toggle(cat)">
                                <v-icon size="20">{{ isOpen(cat) ? "mdi-chevron-down" : "mdi-chevron-right" }}</v-icon>
                            </button>
                            <span class="tt-tree-icon"><v-icon size="20">{{ cat.icon || "mdi-shape-outline" }}</v-icon></span>
                            <div class="tt-tree-text">
                                <b>{{ cat.name }}</b>
                                <span>/{{ cat.slug }} · {{ $tn(cat.children.length, "{n} subcategory", "{n} subcategories") }} · {{ $tn(cat.total_products, "{n} product", "{n} products") }}</span>
                            </div>
                            <div class="tt-tree-flags">
                                <StatusChip v-if="!cat.is_active" label="Hidden" tone="grey" />
                                <StatusChip v-else-if="!cat.show_in_nav" label="Not in menu" tone="grey" />
                                <StatusChip v-if="cat.is_featured" label="Featured" tone="blue" />
                                <span class="tt-tree-specs" :title="$t('Specs used by this category')"><v-icon size="15">mdi-tune-variant</v-icon>{{ $num(cat.attribute_ids.length) }}</span>
                            </div>
                            <div class="tt-tree-actions">
                                <v-btn-tertiary size="small" prepend-icon="mdi-plus" @click="openForm(null, cat)">{{ $t("Subcategory") }}</v-btn-tertiary>
                                <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(cat)" />
                                <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(cat)" />
                            </div>
                        </div>

                        <template v-if="isOpen(cat)">
                        <!-- A subcategory can be dragged into another main category; the empty list is still a drop zone. -->
                        <VueDraggable v-model="cat.children" handle=".tt-drag-sub" :disabled="!!search" group="sub" :animation="150" class="tt-tree-children" @end="saveOrder">
                                <div v-for="sub in cat.children" v-show="matches(sub, cat)" :key="sub.id" class="tt-tree-row">
                                    <v-icon class="tt-drag tt-drag-sub" :class="{ 'is-off': search }" :aria-label="$t('Drag to reorder')">mdi-drag-vertical</v-icon>
                                    <div class="tt-tree-text">
                                        <b>{{ sub.name }}</b>
                                        <span>/{{ sub.slug }} · {{ $tn(sub.product_count, "{n} product", "{n} products") }}</span>
                                    </div>
                                    <div class="tt-tree-flags">
                                        <StatusChip v-if="!sub.is_active" label="Hidden" tone="grey" />
                                        <StatusChip v-if="sub.is_featured" label="Featured" tone="blue" />
                                    </div>
                                    <div class="tt-tree-actions">
                                        <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(sub)" />
                                        <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(sub)" />
                                    </div>
                                </div>
                        </VueDraggable>
                        <div v-if="!cat.children.length" class="tt-tree-empty">{{ $t("No subcategories. Drag one here or add a new one.") }}</div>
                        </template>
                    </div>
            </VueDraggable>
        </section>

        <!-- Add / edit -->
        <v-dialog-trz v-model="dialog" max-width="760" scrollable :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <div class="flex-grow-1">
                        <h2>{{ $t(form.id ? "Edit category" : form.parent_id ? "Add subcategory" : "Add category") }}</h2>
                        <p v-if="form.parent_id">{{ $t("Inside {name}", { name: parentName(form.parent_id) }) }}</p>
                    </div>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-tabs v-model="tab" color="primary" density="compact" class="px-4">
                    <v-tab value="details">{{ $t("Details") }}</v-tab>
                    <v-tab value="specs">{{ $t("Specs") }} <span class="tt-count ml-2">{{ $num(form.attribute_ids.length) }}</span></v-tab>
                    <v-tab value="seo">{{ $t("Search engines") }}</v-tab>
                </v-tabs>
                <v-divider />

                <v-card-text class="pa-5">
                    <v-window v-model="tab">
                        <v-window-item value="details">
                            <div class="tt-form-grid">
                                <v-input-trz v-model="form.name" :label="$t('Name')" :error-messages="errors.name" autofocus @update:model-value="autoSlug" />
                                <v-select-trz
                                    v-model="form.parent_id"
                                    :label="$t('Parent category')"
                                    :items="parentOptions"
                                    :disabled="form.id && hasChildren(form.id)"
                                    :hint="form.id && hasChildren(form.id) ? $t('Categories with subcategories stay main categories.') : ''"
                                    persistent-hint
                                    :error-messages="errors.parent_id"
                                />
                                <v-input-trz v-model="form.slug" :label="$t('Web address')" prefix="/" :hint="$t('Used in the link. Letters, numbers and dashes.')" persistent-hint :error-messages="errors.slug" @update:model-value="slugTouched = true" />
                                <v-input-trz v-model="form.icon" :label="$t('Icon')" :prepend-inner-icon="form.icon || 'mdi-shape-outline'" :hint="$t('A Material Design icon name, e.g. mdi-laptop')" persistent-hint />
                                <div class="is-full tt-icon-picks">
                                    <v-btn-tertiary v-for="i in iconChoices" :key="i" icon size="small" :active="form.icon === i" :aria-label="i" @click="form.icon = i">
                                        <v-icon size="20">{{ i }}</v-icon>
                                    </v-btn-tertiary>
                                </div>
                                <v-textarea-trz v-model="form.description" class="is-full" :label="$t('Description')" :hint="$t('Shown at the top of the category page and read by search engines.')" persistent-hint />
                                <div class="is-full">
                                    <ImageUpload v-model="form.image" folder="categories" label="Category image" hint="Shown on the home page category tiles. A square image works best." />
                                </div>
                                <v-switch-trz v-model="form.is_active" :label="$t('Visible on the website')" />
                                <v-switch-trz v-model="form.show_in_nav" :label="$t('Show in the menu')" />
                                <v-switch-trz v-model="form.is_featured" :label="$t('Feature on the home page')" />
                            </div>
                        </v-window-item>

                        <v-window-item value="specs">
                            <p class="tt-muted tt-small mb-4">
                                {{ $t("Pick the specs products in this category have. They become the fields in the product form, the filters on the category page and the rows in product comparison.") }}
                            </p>
                            <v-alert v-if="inheritedSpecs.length" type="info" variant="tonal" density="compact" class="mb-4">
                                {{ $t("Also uses the specs of {name}: {list}", { name: parentName(form.parent_id), list: inheritedSpecs.join(", ") }) }}
                            </v-alert>
                            <v-autocomplete-trz
                                v-model="form.attribute_ids"
                                :items="attributeOptions"
                                :label="$t('Specs')"
                                multiple
                                chips
                                closable-chips
                                :no-data-text="$t('No specs yet. Add them in Attributes & Specs.')"
                            />
                            <VueDraggable v-if="form.attribute_ids.length" v-model="form.attribute_ids" handle=".tt-drag" :animation="150" class="tt-order-list mt-4">
                                <div v-for="(id, index) in form.attribute_ids" :key="id" class="tt-order-item">
                                    <v-icon class="tt-drag" size="18">mdi-drag-vertical</v-icon>
                                    <span class="tt-num tt-muted">{{ index + 1 }}</span>
                                    <span class="flex-grow-1">{{ attributeName(id) }}</span>
                                </div>
                            </VueDraggable>
                            <p class="tt-muted tt-small mt-2">{{ $t("Drag to set the order specs appear in on product pages.") }}</p>
                        </v-window-item>

                        <v-window-item value="seo">
                            <div class="tt-form-grid">
                                <v-input-trz v-model="form.meta_title" class="is-full" :label="$t('Page title')" counter="60" :placeholder="form.name" />
                                <v-textarea-trz v-model="form.meta_description" class="is-full" :label="$t('Description for search results')" counter="160" />
                            </div>
                        </v-window-item>
                    </v-window>
                </v-card-text>

                <v-divider />
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.name" @click="save">{{ $t(form.id ? "Save changes" : "Add category") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog
            v-model="deleteDialog"
            :title="$t('Delete “{name}”?', { name: deleting?.name || '' })"
            :text="deleting?.children?.length ? 'Its subcategories are deleted too. Categories that still have products cannot be deleted.' : 'This cannot be undone. Categories that still have products cannot be deleted.'"
            :loading="removing"
            @confirm="remove"
        />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";

const blank = () => ({
    id: null, parent_id: null, name: "", slug: "", icon: "", image: null, description: "",
    meta_title: "", meta_description: "", is_active: true, show_in_nav: true, is_featured: false, attribute_ids: [],
});

const slugify = (s) => String(s || "").toLowerCase().normalize("NFKD").replace(/[^\w\s-]/g, "").trim().replace(/[\s_]+/g, "-").replace(/-+/g, "-");

export default {
    name: "Categories",
    data: () => ({
        tree: [],
        attributes: [],
        open: {},
        search: "",
        loading: true,
        dialog: false,
        tab: "details",
        form: blank(),
        errors: {},
        slugTouched: false,
        saving: false,
        deleteDialog: false,
        deleting: null,
        removing: false,
        iconChoices: [
            "mdi-laptop", "mdi-cellphone", "mdi-tablet", "mdi-headphones", "mdi-speaker", "mdi-watch", "mdi-monitor",
            "mdi-keyboard-outline", "mdi-mouse", "mdi-power-plug", "mdi-battery-charging", "mdi-usb-port", "mdi-camera",
            "mdi-gamepad-variant-outline", "mdi-router-wireless", "mdi-harddisk", "mdi-printer", "mdi-television", "mdi-tag-outline",
        ],
    }),
    computed: {
        subCount() {
            return this.tree.reduce((n, c) => n + c.children.length, 0);
        },
        visibleTree() {
            return this.tree.filter((c) => this.matches(c));
        },
        parentOptions() {
            return [
                { value: null, title: this.$t("None (main category)") },
                ...this.tree.filter((c) => c.id !== this.form.id).map((c) => ({ value: c.id, title: c.name })),
            ];
        },
        attributeOptions() {
            return this.attributes.map((a) => ({ value: a.id, title: a.unit ? `${a.name} (${a.unit})` : a.name }));
        },
        inheritedSpecs() {
            const parent = this.tree.find((c) => c.id === this.form.parent_id);
            return parent ? parent.attribute_ids.filter((id) => !this.form.attribute_ids.includes(id)).map((id) => this.attributeName(id)) : [];
        },
    },
    created() {
        this.load();
    },
    methods: {
        async load() {
            this.loading = true;
            try {
                const [cats, attrs] = await Promise.all([api.get("/admin/categories"), api.get("/admin/attributes")]);
                this.tree = cats.data;
                this.attributes = attrs.data;
            } finally {
                this.loading = false;
            }
        },
        isOpen(cat) {
            return !!this.search || this.open[cat.id] !== false;
        },
        toggle(cat) {
            this.open = { ...this.open, [cat.id]: !this.isOpen(cat) };
        },
        matches(cat, parent = null) {
            const q = (this.search || "").trim().toLowerCase();
            if (!q) return true;
            const hit = (c) => c.name.toLowerCase().includes(q) || c.slug.includes(q);
            if (parent) return hit(cat) || hit(parent);
            return hit(cat) || cat.children.some(hit);
        },
        hasChildren(id) {
            return !!this.tree.find((c) => c.id === id)?.children.length;
        },
        parentName(id) {
            return this.tree.find((c) => c.id === id)?.name || "";
        },
        attributeName(id) {
            return this.attributes.find((a) => a.id === id)?.name || "";
        },
        openForm(cat = null, parent = null) {
            this.errors = {};
            this.tab = "details";
            this.slugTouched = !!cat;
            this.form = cat ? { ...blank(), ...cat, attribute_ids: [...(cat.attribute_ids || [])] } : { ...blank(), parent_id: parent?.id ?? null };
            this.dialog = true;
        },
        autoSlug(name) {
            if (!this.slugTouched) this.form.slug = slugify(name);
        },
        async save() {
            this.saving = true;
            this.errors = {};
            const body = { ...this.form };
            delete body.children;
            try {
                if (this.form.id) await api.put(`/admin/categories/${this.form.id}`, body);
                else await api.post("/admin/categories", body);
                this.dialog = false;
                this.$store.dispatch("notify", this.$t(this.form.id ? "Category saved." : "Category added."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
                if (this.errors.attribute_ids) this.tab = "specs";
                else if (this.errors.meta_title || this.errors.meta_description) this.tab = "seo";
                else this.tab = "details";
            } finally {
                this.saving = false;
            }
        },
        // Sends the full order after any drag; a subcategory dragged to another main category moves there.
        async saveOrder() {
            const items = [];
            this.tree.forEach((c, i) => {
                items.push({ id: c.id, parent_id: null, sort_order: i + 1 });
                c.children.forEach((s, j) => items.push({ id: s.id, parent_id: c.id, sort_order: j + 1 }));
            });
            try {
                await api.post("/admin/categories/reorder", { items });
                this.$store.dispatch("notify", this.$t("Order saved."));
            } catch (e) {
                this.load();
            }
        },
        askDelete(cat) {
            this.deleting = cat;
            this.deleteDialog = true;
        },
        async remove() {
            this.removing = true;
            try {
                await api.delete(`/admin/categories/${this.deleting.id}`);
                this.deleteDialog = false;
                this.$store.dispatch("notify", this.$t("Category deleted."));
                this.load();
            } finally {
                this.removing = false;
            }
        },
    },
};
</script>
