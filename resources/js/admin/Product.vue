<template>
    <v-slide-x-transition appear>
        <div class="tt-catalog">
            <!-- ═════════ Top bar ═════════ -->
            <v-card flat class="tt-topbar mb-4">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="tt-crumbs">
                            <router-link to="/admin/catalog">Products</router-link>
                            <v-icon size="12">mdi-chevron-right</v-icon>
                            <span>{{ isEdit ? "Edit product" : "Add product" }}</span>
                        </div>
                        <h1 class="tt-h1">{{ product.name || "New product" }}</h1>
                    </div>
                    <v-spacer />
                    <v-chip :color="dirty ? 'warning' : 'success'" variant="tonal" size="small">
                        <v-icon start size="14">{{ dirty ? "mdi-circle-medium" : "mdi-check-circle-outline" }}</v-icon>
                        {{ dirty ? "Unsaved changes" : "All changes saved" }}
                    </v-chip>
                    <v-btn variant="text" class="text-none" @click="$router.push('/admin/catalog')">Back</v-btn>
                    <v-btn variant="outlined" color="secondary" class="text-none" @click="saveDraft" :loading="savingDraft">
                        Save as draft
                    </v-btn>
                    <v-btn color="primary" variant="flat" class="text-none" prepend-icon="mdi-cloud-upload-outline" @click="publish" :loading="publishing">
                        Publish
                    </v-btn>
                </div>
            </v-card>

            <v-skeleton-loader v-if="loading" type="card, article, article" />

            <v-form v-else ref="formRef" v-model="valid">
                <v-row dense>
                    <!-- ═══════════════ MAIN COLUMN ═══════════════ -->
                    <v-col cols="12" lg="8">
                        <!-- General information -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">General information</span></div>
                            <v-card-text class="tt-body">
                                <v-row dense>
                                    <v-col cols="12" md="8">
                                        <v-text-field
                                            v-model="product.name"
                                            label="Product name"
                                            variant="outlined"
                                            density="compact"
                                            :rules="[rules.required]"
                                            placeholder="e.g. Baseus Bowie E9 True Wireless Earbuds"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-select v-model="product.warranty" :items="warrantyOptions" label="Warranty period" variant="outlined" density="compact" />
                                    </v-col>

                                    <v-col cols="12" md="6">
                                        <v-text-field v-model="product.slug" :placeholder="autoSlug" label="URL slug" prepend-inner-icon="mdi-link-variant" variant="outlined" density="compact" />
                                    </v-col>
                                    <v-col cols="6" md="3">
                                        <v-text-field v-model="product.sku" label="SKU" :rules="[rules.required]" placeholder="BSE-E9-BLK" variant="outlined" density="compact" />
                                    </v-col>
                                    <v-col cols="6" md="3">
                                        <v-text-field v-model="product.barcode" label="Barcode" placeholder="EAN / UPC" variant="outlined" density="compact" />
                                    </v-col>

                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="product.shortDescription"
                                            label="Short description"
                                            variant="outlined"
                                            density="compact"
                                            rows="2"
                                            auto-grow
                                            counter="160"
                                            hint="Shown on product cards and search results"
                                            persistent-hint
                                        />
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>

                        <!-- Description -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span><span class="tt-card-title">Full description</span>
                                <v-spacer />
                                <v-chip size="x-small" variant="outlined" color="primary">{{ (product.description || "").length }} characters</v-chip>
                            </div>
                            <v-card-text class="tt-body">
                                <v-textarea
                                    v-model="product.description"
                                    label="Description"
                                    variant="outlined"
                                    density="compact"
                                    rows="7"
                                    auto-grow
                                    hint="Materials, what's in the box, anything a buyer would ask before purchasing"
                                    persistent-hint
                                />
                            </v-card-text>
                        </v-card>

                        <!-- Specifications -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span><span class="tt-card-title">Specifications</span>
                                <v-spacer />
                                <v-btn variant="tonal" color="primary" size="small" prepend-icon="mdi-plus" @click="addSpecRow">Add row</v-btn>
                            </div>
                            <v-card-text class="tt-body">
                                <div class="tt-sub mb-3">Shown as bullet points on the product card and detail page</div>
                                <v-data-table :headers="specHeaders" :items="product.specs" :items-per-page="-1" hide-default-footer density="comfortable">
                                    <template #item.label="{ item }">
                                        <v-text-field v-model="item.label" density="compact" variant="outlined" hide-details placeholder="Attribute, e.g. Battery life" />
                                    </template>
                                    <template #item.value="{ item }">
                                        <v-text-field v-model="item.value" density="compact" variant="outlined" hide-details placeholder="Value, e.g. 35 hours total playback" />
                                    </template>
                                    <template #item.actions="{ index }">
                                        <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="removeSpecRow(index)" />
                                    </template>
                                </v-data-table>
                            </v-card-text>
                        </v-card>

                        <!-- Images -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span><span class="tt-card-title">Product images</span>
                                <v-spacer />
                                <v-chip size="x-small" variant="outlined" color="primary">{{ product.images.length }}/8</v-chip>
                            </div>
                            <v-card-text class="tt-body">
                                <v-row dense>
                                    <v-col cols="12" md="8">
                                        <v-file-input
                                            v-model="newFiles"
                                            label="Upload images"
                                            prepend-icon=""
                                            prepend-inner-icon="mdi-tray-arrow-up"
                                            accept="image/*"
                                            multiple
                                            chips
                                            show-size
                                            counter
                                            variant="outlined"
                                            density="compact"
                                            @update:model-value="onFilesSelected"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-text-field v-model="product.videoUrl" label="Product video URL" prepend-inner-icon="mdi-youtube" placeholder="https://youtube.com/watch?v=…" variant="outlined" density="compact" />
                                    </v-col>
                                </v-row>

                                <v-row dense v-if="product.images.length">
                                    <v-col v-for="(img, i) in product.images" :key="img.id" cols="6" sm="4" md="3">
                                        <v-hover v-slot="{ isHovering, props }">
                                            <v-card v-bind="props" :elevation="isHovering ? 4 : 1" :class="{ 'thumb-primary': i === 0 }" rounded="lg">
                                                <v-img :src="img.url" aspect-ratio="1" cover>
                                                    <v-chip v-if="i === 0" color="primary" size="x-small" class="ma-2">Primary</v-chip>
                                                    <v-fade-transition>
                                                        <div v-if="isHovering" class="d-flex align-center justify-center fill-height thumb-overlay">
                                                            <v-btn v-if="i !== 0" icon="mdi-star-outline" size="small" variant="tonal" color="white" class="mr-2" @click="makePrimaryImage(i)" />
                                                            <v-btn icon="mdi-delete-outline" size="small" variant="tonal" color="white" @click="removeImage(i)" />
                                                        </div>
                                                    </v-fade-transition>
                                                </v-img>
                                            </v-card>
                                        </v-hover>
                                    </v-col>
                                </v-row>
                                <v-alert v-else type="info" variant="tonal" density="compact">No images yet — the first upload becomes the primary thumbnail.</v-alert>
                            </v-card-text>
                        </v-card>

                        <!-- Variants -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span><span class="tt-card-title">Variants</span>
                                <v-spacer />
                                <v-switch v-model="hasVariants" color="primary" density="compact" hide-details inset />
                            </div>
                            <v-expand-transition>
                                <v-card-text v-if="hasVariants" class="tt-body">
                                    <div class="d-flex align-center mb-3">
                                        <span class="tt-sub">Each variant can have its own SKU, price adjustment and stock</span>
                                        <v-spacer />
                                        <v-btn variant="tonal" color="primary" size="small" prepend-icon="mdi-plus" @click="addVariantRow">Add variant</v-btn>
                                    </div>
                                    <v-data-table :headers="variantHeaders" :items="product.variants" :items-per-page="-1" hide-default-footer density="comfortable">
                                        <template #item.option="{ item }">
                                            <v-text-field v-model="item.option" density="compact" variant="outlined" hide-details placeholder="e.g. Colour: Black" />
                                        </template>
                                        <template #item.sku="{ item }">
                                            <v-text-field v-model="item.sku" density="compact" variant="outlined" hide-details placeholder="Variant SKU" />
                                        </template>
                                        <template #item.priceDelta="{ item }">
                                            <v-text-field v-model.number="item.priceDelta" type="number" density="compact" variant="outlined" hide-details prefix="৳" />
                                        </template>
                                        <template #item.quantity="{ item }">
                                            <v-text-field v-model.number="item.quantity" type="number" density="compact" variant="outlined" hide-details />
                                        </template>
                                        <template #item.actions="{ index }">
                                            <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="removeVariantRow(index)" />
                                        </template>
                                    </v-data-table>
                                </v-card-text>
                                <v-card-text v-else class="tt-body">
                                    <div class="tt-sub">This product has a single price and stock count. Turn on variants if it comes in different colours, sizes or storage options.</div>
                                </v-card-text>
                            </v-expand-transition>
                        </v-card>

                        <!-- Shipping & fulfillment -->
                        <v-card flat class="tt-card">
                            <div class="tt-head">
                                <span class="tt-dot"></span><span class="tt-card-title">Shipping &amp; fulfillment</span>
                                <v-spacer />
                                <v-switch v-model="product.digital" color="primary" density="compact" hide-details inset label="Digital product" />
                            </div>
                            <v-expand-transition>
                                <v-card-text v-if="!product.digital" class="tt-body">
                                    <v-row dense>
                                        <v-col cols="6" md="3"><v-text-field v-model.number="product.weight" type="number" label="Weight" suffix="kg" variant="outlined" density="compact" /></v-col>
                                        <v-col cols="6" md="3"><v-text-field v-model.number="product.length" type="number" label="Length" suffix="cm" variant="outlined" density="compact" /></v-col>
                                        <v-col cols="6" md="3"><v-text-field v-model.number="product.width" type="number" label="Width" suffix="cm" variant="outlined" density="compact" /></v-col>
                                        <v-col cols="6" md="3"><v-text-field v-model.number="product.height" type="number" label="Height" suffix="cm" variant="outlined" density="compact" /></v-col>
                                        <v-col cols="12" md="6"><v-select v-model="product.shippingClass" :items="shippingClasses" label="Shipping class" variant="outlined" density="compact" /></v-col>
                                        <v-col cols="12" md="6"><v-select v-model="product.originCountry" :items="countries" label="Country of origin" variant="outlined" density="compact" /></v-col>
                                    </v-row>
                                </v-card-text>
                                <v-card-text v-else class="tt-body">
                                    <div class="tt-sub">Digital products skip weight, dimensions and courier assignment at checkout.</div>
                                </v-card-text>
                            </v-expand-transition>
                        </v-card>
                    </v-col>

                    <!-- ═══════════════ SIDEBAR ═══════════════ -->
                    <v-col cols="12" lg="4">
                        <!-- Pricing & inventory -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Pricing &amp; inventory</span></div>
                            <v-card-text class="tt-body">
                                <v-text-field
                                    v-model.number="product.price"
                                    type="number"
                                    label="Selling price"
                                    prefix="৳"
                                    variant="outlined"
                                    density="compact"
                                    :rules="[rules.required, rules.positive]"
                                />
                                <v-text-field
                                    v-model.number="product.oldPrice"
                                    type="number"
                                    label="Compare-at price"
                                    prefix="৳"
                                    variant="outlined"
                                    density="compact"
                                    class="mt-2"
                                    hint="Optional — shown struck through"
                                    persistent-hint
                                />
                                <v-text-field
                                    v-model.number="product.costPrice"
                                    type="number"
                                    label="Cost price"
                                    prefix="৳"
                                    variant="outlined"
                                    density="compact"
                                    class="mt-2"
                                    hint="For your margin — never shown to customers"
                                    persistent-hint
                                />

                                <v-alert v-if="discountPct || marginPct" density="compact" variant="tonal" :color="discountPct ? 'error' : 'info'" class="mt-3">
                                    <span v-if="discountPct">{{ discountPct }}% off vs. compare-at price. </span>
                                    <span v-if="marginPct">Margin at current cost: {{ marginPct }}%.</span>
                                </v-alert>

                                <v-divider class="my-4" />

                                <v-row dense>
                                    <v-col cols="6"><v-select v-model="product.taxClass" :items="taxClasses" label="Tax class" variant="outlined" density="compact" /></v-col>
                                    <v-col cols="6"><v-select v-model="product.unit" :items="unitOptions" label="Sold by unit" variant="outlined" density="compact" /></v-col>
                                </v-row>

                                <v-switch v-model="product.trackInventory" color="primary" inset label="Track inventory" hide-details class="mt-1" />

                                <v-row dense class="mt-1">
                                    <v-col cols="6">
                                        <v-text-field
                                            v-model.number="product.quantity"
                                            type="number"
                                            label="Quantity"
                                            variant="outlined"
                                            density="compact"
                                            :disabled="!product.trackInventory"
                                            :rules="product.trackInventory ? [rules.required] : []"
                                        />
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field v-model.number="product.lowStockAt" type="number" label="Low-stock alert" variant="outlined" density="compact" :disabled="!product.trackInventory" />
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field v-model.number="product.minOrderQty" type="number" label="Min order qty" variant="outlined" density="compact" />
                                    </v-col>
                                    <v-col cols="6">
                                        <v-text-field v-model.number="product.maxOrderQty" type="number" label="Max order qty" variant="outlined" density="compact" />
                                    </v-col>
                                </v-row>

                                <v-switch v-model="product.allowBackorder" color="primary" inset label="Allow orders when out of stock" :disabled="!product.trackInventory" hide-details />
                            </v-card-text>
                        </v-card>

                        <!-- Organize -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Organize</span></div>
                            <v-card-text class="tt-body">
                                <v-select
                                    v-model="product.category"
                                    :items="categories"
                                    item-title="name"
                                    item-value="id"
                                    label="Category"
                                    variant="outlined"
                                    density="compact"
                                    :rules="[rules.required]"
                                    @update:model-value="onCategoryChange"
                                />
                                <v-select
                                    v-model="product.subcategory"
                                    :items="subcategories"
                                    item-title="name"
                                    item-value="id"
                                    label="Sub-category"
                                    variant="outlined"
                                    density="compact"
                                    class="mt-2"
                                    :disabled="!subcategories.length"
                                    placeholder="Select a category first"
                                />
                                <v-autocomplete v-model="product.brand" :items="brands" label="Brand" variant="outlined" density="compact" clearable class="mt-2" />
                                <v-combobox
                                    v-model="product.tags"
                                    :items="suggestedTags"
                                    label="Tags"
                                    variant="outlined"
                                    density="compact"
                                    multiple
                                    chips
                                    closable-chips
                                    class="mt-2"
                                    hint="Press enter to add a custom tag"
                                    persistent-hint
                                />
                            </v-card-text>
                        </v-card>

                        <!-- Status -->
                        <v-card flat class="tt-card mb-4">
                            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Status &amp; visibility</span></div>
                            <v-card-text class="tt-body">
                                <v-select v-model="product.status" :items="statusOptions" label="Status" variant="outlined" density="compact" />

                                <v-menu v-if="product.status === 'Scheduled'" v-model="dateMenu" :close-on-content-click="false">
                                    <template #activator="{ props }">
                                        <v-text-field
                                            v-bind="props"
                                            v-model="product.publishDate"
                                            label="Publish date"
                                            prepend-inner-icon="mdi-calendar-outline"
                                            variant="outlined"
                                            density="compact"
                                            readonly
                                            class="mt-2"
                                        />
                                    </template>
                                    <v-date-picker v-model="product.publishDate" @update:model-value="dateMenu = false" />
                                </v-menu>

                                <div class="toggle-row">
                                    <div><b>Show in storefront</b><span>Hidden products can still be linked to directly</span></div>
                                    <v-switch v-model="product.visible" color="primary" density="compact" hide-details inset />
                                </div>
                                <div class="toggle-row">
                                    <div><b>Feature on homepage</b><span>Eligible for the Popular and Deals sections</span></div>
                                    <v-switch v-model="product.featured" color="primary" density="compact" hide-details inset />
                                </div>

                                <v-autocomplete
                                    v-model="product.relatedProducts"
                                    :items="allProducts"
                                    item-title="name"
                                    item-value="id"
                                    label="Related products"
                                    variant="outlined"
                                    density="compact"
                                    multiple
                                    chips
                                    closable-chips
                                    class="mt-3"
                                />
                            </v-card-text>
                        </v-card>

                        <!-- SEO -->
                        <v-card flat class="tt-card">
                            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Search appearance</span></div>
                            <v-card-text class="tt-body">
                                <v-card variant="tonal" color="surface-variant" class="pa-3 mb-3">
                                    <div class="text-caption text-medium-emphasis mb-1">Search result preview</div>
                                    <div class="text-body-2" style="color: #1a0dab">{{ product.metaTitle || product.name || "Product title" }}</div>
                                    <div class="text-caption" style="color: #0b7a53">tanjiltraders.com/p/{{ product.slug || autoSlug || "product" }}</div>
                                    <div class="text-caption text-medium-emphasis text-truncate">
                                        {{ product.metaDescription || product.shortDescription || "Add a description to see how this looks in search results." }}
                                    </div>
                                </v-card>
                                <v-text-field v-model="product.metaTitle" label="Meta title" variant="outlined" density="compact" counter="60" />
                                <v-textarea v-model="product.metaDescription" label="Meta description" variant="outlined" density="compact" rows="2" auto-grow counter="160" class="mt-2" />
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-form>
        </div>
    </v-slide-x-transition>
</template>

<script>
let imgSeq = 0;

export default {
    name: "CatalogAddProduct",

    data() {
        return {
            loading: false,
            valid: false,
            dateMenu: false,
            savingDraft: false,
            publishing: false,
            hasVariants: false,
            newFiles: [],

            specHeaders: [
                { title: "Attribute", key: "label" },
                { title: "Value", key: "value" },
                { title: "", key: "actions", width: 56, sortable: false },
            ],
            variantHeaders: [
                { title: "Option", key: "option" },
                { title: "SKU", key: "sku" },
                { title: "Price adjustment", key: "priceDelta", width: 160 },
                { title: "Quantity", key: "quantity", width: 110 },
                { title: "", key: "actions", width: 56, sortable: false },
            ],

            categories: [
                { id: 1, name: "Cables & adapters" },
                { id: 2, name: "Chargers & power" },
                { id: 3, name: "Audio & headphones" },
                { id: 4, name: "Keyboard & mouse" },
                { id: 5, name: "Storage & drives" },
                { id: 6, name: "Power banks" },
                { id: 7, name: "Networking" },
                { id: 8, name: "Cooling & fans" },
            ],
            subcategoriesMap: {
                1: [{ id: 11, name: "USB-C to USB-C" }, { id: 12, name: "HDMI" }, { id: 13, name: "Ethernet" }],
                3: [{ id: 31, name: "True wireless" }, { id: 32, name: "Over-ear" }, { id: 33, name: "Bluetooth speakers" }],
            },
            subcategories: [],

            brands: ["Anker", "UGREEN", "Baseus", "Logitech", "Samsung", "SanDisk", "TP-Link", "Xiaomi", "Sony", "Edifier", "Keychron", "Orico"],
            suggestedTags: ["fast-charging", "wireless", "noise-cancelling", "best-seller", "new", "warranty-2yr"],
            warrantyOptions: ["No warranty", "6 months", "1 year", "2 years"],
            taxClasses: ["Standard — 15% VAT", "Zero-rated", "Exempt"],
            unitOptions: ["Piece", "Set", "Pair", "Pack"],
            shippingClasses: ["Standard", "Fragile", "Oversized"],
            countries: ["Bangladesh", "China", "Vietnam", "USA", "Germany", "Japan"],
            statusOptions: ["Draft", "Active", "Scheduled"],
            allProducts: [
                { id: 101, name: "Baseus 65W GaN Fast Charger" },
                { id: 102, name: "TP-Link Archer AX55" },
                { id: 103, name: "Anker HDMI 2.1 Cable" },
            ],

            rules: {
                required: (v) => (v !== null && v !== undefined && String(v).trim() !== "") || "Required",
                positive: (v) => (v === null || v === undefined || v === "" || Number(v) > 0) || "Must be greater than 0",
            },

            // ── Replace with GET /api/admin/products/:id when editing ──
            product: {
                id: null,
                name: "", slug: "", sku: "", barcode: "",
                brand: null, category: null, subcategory: null, tags: [],
                warranty: "No warranty",
                shortDescription: "", description: "",

                price: null, oldPrice: null, costPrice: null,
                taxClass: "Standard — 15% VAT", unit: "Piece",
                trackInventory: true, quantity: null, lowStockAt: 5,
                allowBackorder: false, minOrderQty: 1, maxOrderQty: null,

                images: [], videoUrl: "",
                specs: [{ label: "", value: "" }],
                variants: [{ option: "", sku: "", priceDelta: 0, quantity: null }],

                digital: false, weight: null, length: null, width: null, height: null,
                shippingClass: "Standard", originCountry: "Bangladesh",

                status: "Draft", publishDate: null, visible: true, featured: false,
                relatedProducts: [], metaTitle: "", metaDescription: "",
            },
        };
    },

    computed: {
        isEdit() {
            return !!this.$route.params.id;
        },
        autoSlug() {
            return (this.product.name || "").toLowerCase().trim().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
        },
        discountPct() {
            if (!this.product.price || !this.product.oldPrice || this.product.oldPrice <= this.product.price) return 0;
            return Math.round(((this.product.oldPrice - this.product.price) / this.product.oldPrice) * 100);
        },
        marginPct() {
            if (!this.product.price || !this.product.costPrice) return 0;
            return Math.round(((this.product.price - this.product.costPrice) / this.product.price) * 100);
        },
        dirty() {
            return !!(this.product.name || this.product.sku || this.product.price);
        },
    },

    created() {
        document.title = this.isEdit ? "Edit product" : "Add product";
        if (this.isEdit) {
            this.loadProduct();
        }
        // this.loadLookups()
    },

    methods: {
        loadProduct() {
            this.loading = true;
            this.axios
                .get(`/api/admin/products/${this.$route.params.id}`)
                .then((response) => {
                    if (response.data.success) {
                        const data = response.data.data;
                        this.product = { ...this.product, ...data };
                        this.hasVariants = !!(data.variants && data.variants.length);
                        if (!this.product.specs || !this.product.specs.length) this.product.specs = [{ label: "", value: "" }];
                        if (!this.product.variants || !this.product.variants.length) this.product.variants = [{ option: "", sku: "", priceDelta: 0, quantity: null }];
                        if (this.product.category) this.onCategoryChange(this.product.category, true);
                    } else {
                        this.showError(response.data.message || "Failed to load product.");
                    }
                })
                .catch((error) => {
                    console.error(error);
                    this.showError("An error occurred while loading the product.");
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        onCategoryChange(categoryId, keepSubcategory) {
            if (!keepSubcategory) this.product.subcategory = null;
            this.subcategories = this.subcategoriesMap[categoryId] || [];
        },

        addSpecRow() {
            this.product.specs.push({ label: "", value: "" });
        },
        removeSpecRow(i) {
            this.product.specs.splice(i, 1);
            if (!this.product.specs.length) this.addSpecRow();
        },

        addVariantRow() {
            this.product.variants.push({ option: "", sku: "", priceDelta: 0, quantity: null });
        },
        removeVariantRow(i) {
            this.product.variants.splice(i, 1);
            if (!this.product.variants.length) this.addVariantRow();
        },

        onFilesSelected(files) {
            (files || []).forEach((file) => {
                if (this.product.images.length >= 8) return;
                this.product.images.push({ id: ++imgSeq, name: file.name, url: URL.createObjectURL(file), file });
            });
            this.newFiles = [];
        },
        makePrimaryImage(i) {
            const [img] = this.product.images.splice(i, 1);
            this.product.images.unshift(img);
        },
        removeImage(i) {
            this.product.images.splice(i, 1);
        },

        buildPayload() {
            const form = new FormData();
            const { images, ...rest } = this.product;
            form.append("data", JSON.stringify({ ...rest, hasVariants: this.hasVariants, slug: this.product.slug || this.autoSlug }));
            images.forEach((img, i) => img.file && form.append(`images[${i}]`, img.file));
            return form;
        },

        async saveDraft() {
            this.savingDraft = true;
            try {
                this.product.status = "Draft";
                const response = this.isEdit
                    ? await this.axios.post(`/api/admin/products/${this.product.id}?_method=PUT`, this.buildPayload())
                    : await this.axios.post("/api/admin/products", this.buildPayload());
                if (response.data.success !== false) {
                    this.showSuccess("Draft saved.");
                    if (!this.isEdit && response.data.data?.id) {
                        this.$router.replace(`/admin/catalog/product/${response.data.data.id}`);
                    }
                } else {
                    this.showError(response.data.message || "Save failed.");
                }
            } catch (error) {
                console.error(error);
                this.showError("An error occurred while saving.");
            } finally {
                this.savingDraft = false;
            }
        },

        async publish() {
            const { valid } = await this.$refs.formRef.validate();
            if (!valid) {
                this.showError("Please fill in all required fields before publishing.");
                return;
            }
            this.publishing = true;
            try {
                this.product.status = this.product.status === "Scheduled" ? "Scheduled" : "Active";
                const response = this.isEdit
                    ? await this.axios.post(`/api/admin/products/${this.product.id}?_method=PUT`, this.buildPayload())
                    : await this.axios.post("/api/admin/products", this.buildPayload());
                if (response.data.success !== false) {
                    this.showSuccess("Product published.");
                    this.$router.push("/admin/catalog");
                } else {
                    this.showError(response.data.message || "Publish failed.");
                }
            } catch (error) {
                console.error(error);
                this.showError("An error occurred while publishing.");
            } finally {
                this.publishing = false;
            }
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-catalog {
    --ink: #1c1917;
    --muted: #78716c;
    --line: #e7e2dd;
    --accent: #e25311;
    --tint: #fdece3;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
}

/* ───────── Top bar ───────── */
.tt-topbar { background: #fff; border: 1px solid var(--line) !important; border-radius: 12px !important; padding: 16px 20px; }
.tt-crumbs { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--muted); margin-bottom: 4px; }
.tt-crumbs a { color: var(--muted); text-decoration: none; }
.tt-crumbs a:hover { color: var(--accent); }
.tt-h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }

/* ───────── tt-card system: single brand accent, varied only by icon/label ───────── */
.tt-card {
    background: #fff;
    border: 1px solid var(--line) !important;
    border-radius: 10px !important;
    box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04) !important;
    overflow: hidden;
}
.tt-head {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 16px;
    background: linear-gradient(90deg, var(--tint), #fff);
    border-bottom: 1px solid var(--line);
    border-left: 3px solid var(--accent);
}
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-body { padding: 16px 18px 18px; }
.tt-sub { font-size: 12.5px; color: var(--muted); }

/* ───────── images ───────── */
.thumb-primary { outline: 2px solid var(--accent); }
.thumb-overlay { background: rgba(28, 25, 23, 0.55); }

/* ───────── toggle rows ───────── */
.toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-top: 1px solid var(--line); }
.toggle-row b { font-size: 12.8px; font-weight: 600; display: block; }
.toggle-row span { font-size: 11px; color: var(--muted); }
</style>