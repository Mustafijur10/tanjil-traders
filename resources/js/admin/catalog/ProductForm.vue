<template>
    <div>
        <PageHeader :title="isNew ? 'Add product' : form.name || 'Edit product'" :subtitle="isNew ? 'Fill in the details, add photos and specs, then publish.' : ''">
            <v-btn-tertiary prepend-icon="mdi-arrow-left" to="/admin/products">{{ $t("All products") }}</v-btn-tertiary>
            <template v-if="!isNew">
                <v-btn-secondary v-if="saved.status === 'active'" prepend-icon="mdi-open-in-new" :href="`/product/${saved.slug}`" target="_blank">{{ $t("View") }}</v-btn-secondary>
                <v-menu location="bottom end">
                    <template #activator="{ props }">
                        <v-btn-secondary v-bind="props" icon="mdi-dots-horizontal" :aria-label="$t('More actions')" />
                    </template>
                    <v-list density="compact" min-width="180">
                        <v-list-item prepend-icon="mdi-content-copy" :title="$t('Duplicate')" @click="duplicate" />
                        <v-list-item prepend-icon="mdi-trash-can-outline" :title="$t('Delete')" base-color="error" @click="deleteDialog = true" />
                    </v-list>
                </v-menu>
            </template>
        </PageHeader>

        <div v-if="loading" class="tt-product-layout">
            <div class="tt-box tt-box-body tt-skel-group"><span v-for="n in 8" :key="n" class="tt-skel"></span></div>
            <div class="tt-box tt-box-body tt-skel-group"><span v-for="n in 5" :key="n" class="tt-skel"></span></div>
        </div>

        <div v-else class="tt-product-layout">
            <!-- ── Main column ── -->
            <div class="tt-vstack">
                <section class="tt-box">
                    <div class="tt-box-head"><h2>{{ $t("Basic information") }}</h2></div>
                    <div class="tt-box-body tt-form-grid">
                        <v-input-trz v-model="form.name" class="is-full" :label="$t('Product name')" :placeholder="$t('e.g. Xiaomi Redmi Note 13 Pro 5G')" :error-messages="errors.name" @update:model-value="autoSlug" />
                        <v-textarea-trz
                            v-model="form.short_description"
                            class="is-full"
                            :label="$t('Key features')"
                            :hint="$t('One feature per line. Shown as bullet points next to the price.')"
                            persistent-hint
                            rows="4"
                            :error-messages="errors.short_description"
                        />
                        <div class="is-full">
                            <label class="tt-field-label">{{ $t("Full description") }}</label>
                            <RichText v-model="form.description" folder="products" placeholder="Full description" />
                        </div>
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <h2>{{ $t("Photos") }}</h2>
                        <span class="tt-count ml-auto">{{ $num(form.images.length) }}</span>
                    </div>
                    <div class="tt-box-body">
                        <ProductImages v-model="form.images" :alt-default="form.name" />
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <div>
                            <h2>{{ $t("Specifications") }}</h2>
                            <p>{{ $t("Filled specs become shop filters and comparison rows.") }}</p>
                        </div>
                        <span class="tt-count ml-auto">{{ $t("{a} of {b} filled", { a: $num(filledSpecs), b: $num(specFields.length) }) }}</span>
                    </div>
                    <div class="tt-box-body">
                        <EmptyState v-if="!form.category_id" icon="mdi-shape-outline" title="Choose a category first" text="The specs depend on the category, e.g. laptops have RAM and Processor." />
                        <EmptyState v-else-if="!categorySpecs.length" icon="mdi-tune-variant" title="This category has no specs" text="Add specs to the category in Catalog › Categories.">
                            <v-btn-secondary to="/admin/categories">{{ $t("Open Categories") }}</v-btn-secondary>
                        </EmptyState>
                        <template v-else>
                        <p v-if="variantSpecNames.length" class="tt-muted tt-small mb-4">
                            <v-icon size="15">mdi-information-outline</v-icon>
                            {{ $t("{list} come from the variants below.", { list: variantSpecNames.join(", ") }) }}
                        </p>
                        <template v-for="g in specGroups" :key="g.name">
                            <div class="tt-group-title">{{ g.name ? $t(g.name) : $t("Other") }}</div>
                            <div class="tt-form-grid mb-4">
                                <template v-for="a in g.items" :key="a.id">
                                    <v-autocomplete-trz
                                        v-if="a.type === 'select'"
                                        v-model="specs[a.id]"
                                        :items="a.values.map((v) => ({ value: v.id, title: v.value }))"
                                        :label="a.unit ? `${a.name} (${a.unit})` : a.name"
                                        multiple
                                        chips
                                        closable-chips
                                        clearable
                                    />
                                    <v-input-trz v-else v-model="specs[a.id]" :label="a.unit ? `${a.name} (${a.unit})` : a.name" />
                                </template>
                            </div>
                        </template>
                        </template>
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <div>
                            <h2>{{ $t("Options and variants") }}</h2>
                            <p>{{ $t("For products sold in several versions, e.g. 8GB/128GB and 8GB/256GB.") }}</p>
                        </div>
                        <v-switch-trz v-model="hasVariants" class="ml-auto flex-grow-0" :label="$t('Has options')" :disabled="!form.category_id" />
                    </div>
                    <div v-if="hasVariants" class="tt-box-body">
                        <v-alert v-if="errors.variants" type="error" variant="tonal" density="compact" class="mb-3">{{ errors.variants }}</v-alert>
                        <ProductVariants v-model="form.variants" :attributes="categorySpecs" :base-price="form.price" :errors="errors" />
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <div>
                            <h2>{{ $t("Search engine listing") }}</h2>
                            <p>{{ $t("Leave empty to use the product name and key features. The Google preview on the right updates as you type.") }}</p>
                        </div>
                    </div>
                    <div class="tt-box-body">
                        <div class="tt-form-grid">
                            <v-input-trz v-model="form.meta_title" class="is-full" :label="$t('Page title')" counter="60" :placeholder="form.name" />
                            <v-textarea-trz v-model="form.meta_description" class="is-full" :label="$t('Description')" counter="160" :placeholder="plainShort" />
                            <v-input-trz
                                v-model="form.slug"
                                class="is-full"
                                :label="$t('Web address')"
                                prefix="/product/"
                                :error-messages="errors.slug"
                                @update:model-value="slugTouched = true"
                            />
                        </div>
                    </div>
                </section>
            </div>

            <!-- ── Side column ── -->
            <aside class="tt-vstack tt-product-side">
                <section class="tt-box">
                    <div class="tt-box-head"><h2>{{ $t("Status") }}</h2></div>
                    <div class="tt-box-body tt-vstack-sm">
                        <v-select-trz
                            v-model="form.status"
                            :items="[
                                { value: 'active', title: $t('Published: visible in the shop') },
                                { value: 'draft', title: $t('Draft: hidden from the shop') },
                            ]"
                            :aria-label="$t('Status')"
                        />
                        <v-switch-trz v-model="form.is_featured" :label="$t('Featured product')" />
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-ico"><v-icon size="18">mdi-google</v-icon></span>
                        <div>
                            <h2>{{ $t("Google preview") }}</h2>
                            <p>{{ $t("How this product can look in search results") }}</p>
                        </div>
                    </div>
                    <div class="tt-box-body">
                        <GooglePreview
                            :title="form.meta_title || form.name"
                            :description="form.meta_description || plainShort"
                            :path="`/product/${form.slug || ''}`"
                            :image="form.images[0] ? fileUrl(form.images[0].path) : ''"
                            :price="previewPrice"
                            :rating="saved.review_count ? saved.rating_avg : 0"
                            :reviews="saved.review_count || 0"
                            :stock="previewStock > 0"
                            :extra-checks="seoChecks"
                        />
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head"><h2>{{ $t("Organisation") }}</h2></div>
                    <div class="tt-box-body tt-vstack-sm">
                        <v-autocomplete-trz v-model="form.category_id" :items="categoryOptions" :label="$t('Category')" :error-messages="errors.category_id" />
                        <v-autocomplete-trz v-model="form.brand_id" :items="brandOptions" :label="$t('Brand')" clearable :error-messages="errors.brand_id" />
                        <v-combobox-trz v-model="form.warranty" :items="warrantyOptions" :label="$t('Warranty')" :hint="$t('e.g. 1 year official warranty')" persistent-hint />
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head"><h2>{{ $t("Pricing") }}</h2></div>
                    <div class="tt-box-body tt-vstack-sm">
                        <v-alert v-if="hasVariants" type="info" variant="tonal" density="compact">{{ $t("Prices are set on each variant below. The lowest one is shown in the shop.") }}</v-alert>
                        <template v-else>
                            <v-input-trz v-model.number="form.price" type="number" min="0" :label="$t('Price')" :prefix="currency" :error-messages="errors.price" />
                            <v-input-trz v-model.number="form.sale_price" type="number" min="0" :label="$t('Sale price (optional)')" :prefix="currency" :error-messages="errors.sale_price" />
                            <v-input-trz v-if="form.sale_price" v-model="form.sale_ends_at" type="datetime-local" :label="$t('Sale ends (optional)')" :error-messages="errors.sale_ends_at" />
                        </template>
                        <v-input-trz
                            v-model.number="form.cost_price"
                            type="number"
                            min="0"
                            :label="$t('Cost price (private)')"
                            :prefix="currency"
                            :hint="$t('What you paid. Only used for profit reports.')"
                            persistent-hint
                        />
                        <div v-if="margin !== null" class="tt-kv-line">
                            <span>{{ $t("Profit per unit") }}</span>
                            <b :class="margin < 0 ? 'tt-text-danger' : ''">{{ $money(margin) }} · {{ $num(marginPct) }}%</b>
                        </div>
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head"><h2>{{ $t("Inventory") }}</h2></div>
                    <div class="tt-box-body tt-vstack-sm">
                        <v-input-trz v-model="form.sku" :label="$t('SKU (optional)')" :hint="$t('Your own product code.')" persistent-hint :error-messages="errors.sku" />
                        <v-alert v-if="hasVariants" type="info" variant="tonal" density="compact">{{ $t("Stock is set on each variant. Total: {n}", { n: $num(variantStock) }) }}</v-alert>
                        <v-input-trz
                            v-else
                            v-model.number="form.stock_qty"
                            type="number"
                            min="0"
                            :label="$t('Quantity in stock')"
                            :hint="isNew ? '' : $t('Changes are recorded in the stock history.')"
                            persistent-hint
                            :error-messages="errors.stock_qty"
                        />
                    </div>
                </section>
            </aside>
        </div>

        <div v-if="!loading && (isDirty || isNew)" class="tt-save-bar" role="region" :aria-label="$t('Save product')">
            <span>{{ isNew ? $t("New product, not saved yet.") : $t("You have unsaved changes.") }}</span>
            <v-btn-tertiary v-if="!isNew" color="white" :disabled="saving" @click="reset">{{ $t("Discard") }}</v-btn-tertiary>
            <v-btn-primary :loading="saving" :disabled="!form.name" @click="save">{{ $t(isNew ? "Save product" : "Save changes") }}</v-btn-primary>
        </div>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: saved.name || '' })" text="Past orders keep the product's name and price. This cannot be undone." :loading="deleting" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors, fileUrl } from "@/api";
import ProductImages from "@/components/catalog/ProductImages.vue";
import ProductVariants from "@/components/catalog/ProductVariants.vue";
import GooglePreview from "@/components/ui/GooglePreview.vue";

const slugify = (s) => String(s || "").toLowerCase().normalize("NFKD").replace(/[^\w\s-]/g, "").trim().replace(/[\s_]+/g, "-").replace(/-+/g, "-");
let keySeq = 0;

const blank = () => ({
    name: "", slug: "", sku: "", category_id: null, brand_id: null, short_description: "", description: "",
    price: null, sale_price: null, sale_ends_at: null, cost_price: null, stock_qty: 0, warranty: "",
    is_featured: false, status: "active", meta_title: "", meta_description: "", images: [], variants: [],
});

export default {
    name: "ProductForm",
    components: { ProductImages, ProductVariants, GooglePreview },
    data: () => ({
        loading: true,
        saving: false,
        deleting: false,
        deleteDialog: false,
        options: { categories: [], brands: [], attributes: [] },
        form: blank(),
        specs: {},
        hasVariants: false,
        saved: {},
        snapshot: "",
        errors: {},
        slugTouched: false,
        warrantyOptions: ["No warranty", "6 months service warranty", "1 year official warranty", "2 years official warranty", "1 year brand warranty"],
    }),
    computed: {
        isNew() {
            return !this.$route.params.id;
        },
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
        // ── Google preview ──
        previewPrice() {
            if (this.hasVariants) {
                const prices = this.form.variants.filter((v) => v.is_active).map((v) => Number(v.sale_price || v.price) || 0);
                return prices.length ? this.$money(Math.min(...prices)) : "";
            }
            const p = Number(this.form.sale_price || this.form.price);
            return p ? this.$money(p) : "";
        },
        previewStock() {
            return this.hasVariants ? this.variantStock : Number(this.form.stock_qty) || 0;
        },
        seoChecks() {
            const imgs = this.form.images;
            const features = (this.form.short_description || "").split("\n").filter((l) => l.trim()).length;
            return [
                imgs.length ? { ok: true, text: this.$tn(imgs.length, "{n} photo", "{n} photos") } : { ok: false, text: this.$t("Add at least one photo (Google shows it next to the result)") },
                imgs.length && imgs.some((i) => !i.alt)
                    ? { ok: false, warn: true, text: this.$t("Describe every photo for image search") }
                    : imgs.length ? { ok: true, text: this.$t("All photos are described") } : null,
                features >= 3 ? { ok: true, text: this.$t("Key features are filled in") } : { ok: false, warn: true, text: this.$t("Add at least 3 key features") },
                this.specFields.length
                    ? this.filledSpecs >= Math.ceil(this.specFields.length / 2)
                        ? { ok: true, text: this.$t("Specs are filled in, so the product shows up in filters") }
                        : { ok: false, warn: true, text: this.$t("Fill more specs so customers can find it with filters") }
                    : null,
            ].filter(Boolean);
        },
        categoryOptions() {
            const cats = this.options.categories;
            const parents = Object.fromEntries(cats.filter((c) => !c.parent_id).map((c) => [c.id, c.name]));
            return cats.map((c) => ({ value: c.id, title: c.parent_id ? `${parents[c.parent_id]} › ${c.name}` : c.name }));
        },
        brandOptions() {
            return this.options.brands.map((b) => ({ value: b.id, title: b.name }));
        },
        // Specs of the chosen category plus those of its main category.
        categorySpecs() {
            const cat = this.options.categories.find((c) => c.id === this.form.category_id);
            if (!cat) return [];
            const parent = cat.parent_id ? this.options.categories.find((c) => c.id === cat.parent_id) : null;
            const ids = [...new Set([...(parent?.attribute_ids || []), ...cat.attribute_ids])];
            return ids.map((id) => this.options.attributes.find((a) => a.id === id)).filter(Boolean);
        },
        // Specs chosen per variant (e.g. Storage) are filled from the variants on save, so they are not asked twice.
        variantAttrIds() {
            if (!this.hasVariants) return [];
            const owner = {};
            this.options.attributes.forEach((a) => a.values.forEach((v) => (owner[v.id] = a.id)));
            return [...new Set(this.form.variants.flatMap((v) => v.value_ids.map((id) => owner[id])))];
        },
        specFields() {
            return this.categorySpecs.filter((a) => !this.variantAttrIds.includes(a.id));
        },
        specGroups() {
            const map = new Map();
            this.specFields.forEach((a) => map.set(a.group || "", [...(map.get(a.group || "") || []), a]));
            return [...map.entries()].map(([name, items]) => ({ name, items }));
        },
        variantSpecNames() {
            return this.categorySpecs.filter((a) => this.variantAttrIds.includes(a.id)).map((a) => a.name);
        },
        filledSpecs() {
            return this.specFields.filter((a) => {
                const v = this.specs[a.id];
                return Array.isArray(v) ? v.length : !!(v && String(v).trim());
            }).length;
        },
        variantStock() {
            return this.form.variants.reduce((n, v) => n + (Number(v.stock_qty) || 0), 0);
        },
        plainShort() {
            return (this.form.short_description || "").split("\n").filter(Boolean).join(" · ").slice(0, 160);
        },
        margin() {
            const sell = this.hasVariants ? null : Number(this.form.sale_price || this.form.price);
            if (!sell || this.form.cost_price === null || this.form.cost_price === "") return null;
            return sell - Number(this.form.cost_price);
        },
        marginPct() {
            const sell = Number(this.form.sale_price || this.form.price);
            return sell ? Math.round((this.margin / sell) * 100) : 0;
        },
        payload() {
            const specs = this.specFields
                .map((a) => {
                    const v = this.specs[a.id];
                    return a.type === "select" ? { attribute_id: a.id, value_ids: v || [] } : { attribute_id: a.id, value: v || "" };
                })
                .filter((s) => (s.value_ids && s.value_ids.length) || (s.value && s.value.trim()));

            const variants = this.hasVariants
                ? this.form.variants.map(({ id, sku, price, sale_price, stock_qty, is_active, value_ids }) => ({
                      id, sku: sku || null, price: Number(price) || 0, sale_price: sale_price === "" ? null : sale_price, stock_qty: Number(stock_qty) || 0, is_active, value_ids,
                  }))
                : [];

            return {
                ...this.form,
                // With variants the server sets the real price from the cheapest one; this just passes validation.
                price: this.hasVariants ? (variants.length ? Math.min(...variants.map((v) => v.price)) : 0) : this.form.price,
                sale_price: this.hasVariants ? null : this.form.sale_price || null,
                sale_ends_at: this.form.sale_price ? this.form.sale_ends_at || null : null,
                cost_price: this.form.cost_price === "" ? null : this.form.cost_price,
                sku: this.form.sku || null,
                brand_id: this.form.brand_id || null,
                specs,
                variants,
            };
        },
        isDirty() {
            return JSON.stringify(this.payload) !== this.snapshot;
        },
    },
    watch: {
        "$route.params.id": "load",
    },
    created() {
        this.load();
        window.addEventListener("beforeunload", this.warnUnsaved);
    },
    beforeUnmount() {
        window.removeEventListener("beforeunload", this.warnUnsaved);
    },
    beforeRouteLeave() {
        return !this.isDirty || window.confirm(this.$t("You have unsaved changes. Leave without saving?"));
    },
    methods: {
        fileUrl,
        warnUnsaved(e) {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = "";
            }
        },
        async load() {
            this.loading = true;
            this.errors = {};
            try {
                const [opts, product] = await Promise.all([
                    api.get("/admin/products/form-data"),
                    this.isNew ? Promise.resolve(null) : api.get(`/admin/products/${this.$route.params.id}`),
                ]);
                this.options = opts.data;
                this.saved = product?.data || {};
                this.reset();
            } finally {
                this.loading = false;
            }
        },
        // Copies the saved product into the form (or a blank one).
        reset() {
            const p = this.saved;
            if (!p.id) {
                this.form = { ...blank(), category_id: Number(this.$route.query.category) || null };
                this.specs = {};
                this.hasVariants = false;
                this.slugTouched = false;
                this.snapshot = JSON.stringify(this.payload);
                return;
            }

            this.form = {
                ...blank(),
                ...Object.fromEntries(Object.keys(blank()).map((k) => [k, p[k] ?? blank()[k]])),
                sale_ends_at: p.sale_ends_at ? p.sale_ends_at.slice(0, 16) : null,
                images: p.images.map(({ path, alt }) => ({ path, alt })),
                variants: p.variants.map((v) => ({
                    key: `v${++keySeq}`, id: v.id, sku: v.sku || "", price: v.price, sale_price: v.sale_price, stock_qty: v.stock_qty, is_active: v.is_active,
                    value_ids: v.values.map((x) => x.id),
                })),
            };
            const specs = {};
            p.specs.forEach((s) => {
                if (s.attribute_value_id) specs[s.attribute_id] = [...(specs[s.attribute_id] || []), s.attribute_value_id];
                else specs[s.attribute_id] = s.value;
            });
            this.specs = specs;
            this.hasVariants = p.variants.length > 0;
            this.slugTouched = true;
            this.$nextTick(() => (this.snapshot = JSON.stringify(this.payload)));
        },
        autoSlug(name) {
            if (!this.slugTouched) this.form.slug = slugify(name);
        },
        async save() {
            if (this.hasVariants && !this.form.variants.length) {
                this.$store.dispatch("notify", { text: this.$t("Create the variants, or turn off “Has options”."), type: "error" });
                return;
            }
            this.saving = true;
            this.errors = {};
            try {
                const { data } = this.isNew ? await api.post("/admin/products", this.payload) : await api.put(`/admin/products/${this.saved.id}`, this.payload);
                this.$store.dispatch("notify", this.$t(this.isNew ? "Product added." : "Product saved."));
                if (this.isNew) {
                    this.snapshot = JSON.stringify(this.payload);
                    this.$router.replace(`/admin/products/${data.id}`);
                } else {
                    this.load();
                }
            } catch (e) {
                this.errors = fieldErrors(e);
                this.$nextTick(() => document.querySelector(".v-input--error")?.scrollIntoView({ behavior: "smooth", block: "center" }));
            } finally {
                this.saving = false;
            }
        },
        async duplicate() {
            const { data } = await api.post(`/admin/products/${this.saved.id}/duplicate`);
            this.$store.dispatch("notify", this.$t("Copy created as a draft."));
            this.$router.push(`/admin/products/${data.id}`);
        },
        async remove() {
            this.deleting = true;
            try {
                await api.delete(`/admin/products/${this.saved.id}`);
                this.snapshot = JSON.stringify(this.payload);
                this.deleteDialog = false;
                this.$store.dispatch("notify", this.$t("Product deleted."));
                this.$router.replace("/admin/products");
            } finally {
                this.deleting = false;
            }
        },
    },
};
</script>
