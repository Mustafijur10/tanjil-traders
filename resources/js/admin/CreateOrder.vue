<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Create product</h1>
                    <div class="tt-hero-sub">Add a new product to your catalog and publish it to your sales channels</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-btn variant="outlined" class="text-none tt-ghost-btn" prepend-icon="fa fa-arrow-left" to="/admin/catalog">All products</v-btn>
                </div>
            </div>

            <div class="tt-hero-strip">
                <div v-for="h in heroStats" :key="h.label" class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                    <div class="flex-grow-1">
                        <div class="tt-hero-value">{{ h.value }}</div>
                        <div class="tt-hero-label">{{ h.label }}</div>
                        <div class="tt-hero-note">{{ h.note }}</div>
                    </div>
                </div>
            </div>
            <div class="tt-hero-line"></div>
        </header>

        <v-row dense class="mt-1">
            <!-- ═════════════════════════ LEFT / MAIN column ═════════════════════════ -->
            <v-col cols="12" md="8">
                <!-- ── Basic info ── -->
                <div class="tt-section"><v-icon size="16">mdi-information-outline</v-icon><span>Basic info</span></div>
                <v-card flat class="tt-card tt-blue mb-5">
                    <div class="pa-4">
                        <div class="tt-field-label">Product name *</div>
                        <v-text-field
                            v-model="form.name"
                            variant="outlined"
                            density="comfortable"
                            hide-details
                            class="mb-4"
                            placeholder="e.g. Wireless Noise-Cancelling Headphones"
                        />

                        <div class="tt-field-label">Description</div>
                        <v-textarea
                            v-model="form.description"
                            variant="outlined"
                            density="comfortable"
                            rows="4"
                            hide-details
                            class="mb-4"
                            placeholder="Describe the product's features, materials, and benefits…"
                        />

                        <v-row dense>
                            <v-col cols="12" sm="6">
                                <div class="tt-field-label">Category *</div>
                                <v-select v-model="form.category" :items="categoryOptions" variant="outlined" density="comfortable" hide-details />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <div class="tt-field-label">Brand</div>
                                <v-select v-model="form.brand" :items="brandOptions" variant="outlined" density="comfortable" hide-details />
                            </v-col>
                        </v-row>
                    </div>
                </v-card>

                <!-- ── Media ── -->
                <div class="tt-section"><v-icon size="16">mdi-image-multiple-outline</v-icon><span>Media</span></div>
                <v-card flat class="tt-card tt-blue mb-5">
                    <div class="pa-4">
                        <div
                            class="tt-dropzone"
                            :class="{ 'is-dragover': dragOver }"
                            @click="$refs.fileInput.click()"
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="onDrop"
                        >
                            <v-icon size="26" color="#6b7f7b">mdi-tray-arrow-up</v-icon>
                            <div class="font-weight-medium mt-2">Drop product images here</div>
                            <div class="tt-sub">or click to browse · PNG, JPG up to 5MB each</div>
                            <input ref="fileInput" type="file" accept="image/*" multiple class="d-none" @change="onFilesSelected" />
                        </div>

                        <div v-if="images.length" class="tt-media-grid mt-3">
                            <div v-for="(img, i) in images" :key="img.id" class="tt-media-thumb">
                                <img :src="img.url" alt="" />
                                <span v-if="i === 0" class="tt-media-cover"><v-icon size="10">mdi-circle</v-icon> Cover</span>
                                <v-btn icon size="x-small" variant="flat" class="tt-media-remove" @click="removeImage(img)">
                                    <v-icon size="12">fa fa-times</v-icon>
                                </v-btn>
                            </div>
                        </div>
                    </div>
                </v-card>

                <!-- ── Pricing & inventory ── -->
                <div class="tt-section"><v-icon size="16">mdi-currency-usd</v-icon><span>Pricing &amp; inventory</span></div>
                <v-card flat class="tt-card tt-blue mb-5">
                    <div class="pa-4">
                        <v-row dense>
                            <v-col cols="12" sm="4">
                                <div class="tt-field-label">Price *</div>
                                <v-text-field v-model.number="form.price" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="0.00" />
                            </v-col>
                            <v-col cols="12" sm="4">
                                <div class="tt-field-label">Compare-at price</div>
                                <v-text-field v-model.number="form.compareAtPrice" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="0.00" />
                            </v-col>
                            <v-col cols="12" sm="4">
                                <div class="tt-field-label">SKU</div>
                                <v-text-field v-model="form.sku" variant="outlined" density="comfortable" hide-details placeholder="e.g. WH-2024-BLK" />
                            </v-col>

                            <v-col cols="12" sm="4">
                                <div class="tt-field-label">Stock quantity</div>
                                <v-text-field v-model.number="form.stockQty" type="number" min="0" variant="outlined" density="comfortable" hide-details placeholder="0" />
                            </v-col>
                            <v-col cols="12" sm="4">
                                <div class="tt-field-label">Low stock threshold</div>
                                <v-text-field v-model.number="form.lowStockThreshold" type="number" min="0" variant="outlined" density="comfortable" hide-details placeholder="10" />
                            </v-col>
                            <v-col cols="12" sm="4">
                                <div class="tt-field-label">Weight (kg)</div>
                                <v-text-field v-model.number="form.weight" type="number" min="0" step="0.1" variant="outlined" density="comfortable" hide-details placeholder="0.0" />
                            </v-col>
                        </v-row>

                        <div class="tt-switch-row mt-4">
                            <span>Track inventory for this product</span>
                            <v-switch v-model="form.trackInventory" color="#0f9d6b" hide-details density="comfortable" />
                        </div>
                    </div>
                </v-card>

                <!-- ── Variants ── -->
                <div class="tt-section"><v-icon size="16">mdi-palette-swatch-outline</v-icon><span>Variants</span></div>
                <v-card flat class="tt-card tt-blue mb-5">
                    <div class="pa-4">
                        <div v-for="group in variantGroups" :key="group.name" class="tt-variant-group mb-3">
                            <span class="tt-variant-name"><span class="tt-dot-sm" :style="{ background: group.color }"></span>{{ group.name }}</span>
                            <div class="tt-variant-options">
                                <v-btn
                                    v-for="opt in group.options"
                                    :key="opt"
                                    size="small"
                                    variant="outlined"
                                    class="text-none tt-variant-chip"
                                    :class="{ 'is-selected': group.selected === opt }"
                                    @click="group.selected = opt"
                                >
                                    {{ opt }}
                                </v-btn>
                            </div>
                            <v-btn icon size="small" variant="text" class="tt-variant-edit"><v-icon size="15">fa fa-pencil</v-icon></v-btn>
                        </div>

                        <v-btn variant="outlined" class="text-none" prepend-icon="fa fa-plus" @click="addVariantOption">Add variant option</v-btn>
                    </div>
                </v-card>

                <!-- ── Shipping ── -->
                <div class="tt-section"><v-icon size="16">mdi-truck-outline</v-icon><span>Shipping</span></div>
                <v-card flat class="tt-card tt-blue mb-5">
                    <div class="pa-4">
                        <v-row dense>
                            <v-col cols="12" sm="6">
                                <div class="tt-field-label">Shipping class</div>
                                <v-select v-model="form.shippingClass" :items="shippingClassOptions" variant="outlined" density="comfortable" hide-details />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <div class="tt-field-label">Dimensions (cm)</div>
                                <div class="d-flex ga-2">
                                    <v-text-field v-model.number="form.dimensions.l" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="L" style="max-width: 33%" />
                                    <v-text-field v-model.number="form.dimensions.w" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="W" style="max-width: 33%" />
                                    <v-text-field v-model.number="form.dimensions.h" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="H" style="max-width: 33%" />
                                </div>
                            </v-col>
                        </v-row>
                    </div>
                </v-card>

                <!-- ── SEO ── -->
                <div class="tt-section"><v-icon size="16">mdi-magnify-expand</v-icon><span>SEO</span></div>
                <v-card flat class="tt-card tt-blue mb-5">
                    <div class="pa-4">
                        <div class="tt-field-label">Meta title</div>
                        <v-text-field v-model="form.metaTitle" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="Appears in search engine results" />

                        <div class="tt-field-label">Meta description</div>
                        <v-textarea v-model="form.metaDescription" variant="outlined" density="comfortable" rows="2" hide-details class="mb-4" placeholder="A short summary shown under the title in search results…" />

                        <div class="tt-field-label">URL handle</div>
                        <v-text-field v-model="form.urlHandle" variant="outlined" density="comfortable" hide-details prefix="/products/" placeholder="wireless-headphones" />
                    </div>
                </v-card>
            </v-col>

            <!-- ═════════════════════════ RIGHT: sidebar ═════════════════════════ -->
            <v-col cols="12" md="4">
                <div class="tt-sticky">
                    <!-- Visibility -->
                    <div class="tt-section"><v-icon size="16">mdi-eye-outline</v-icon><span>Visibility</span></div>
                    <v-card flat class="tt-card tt-green mb-5">
                        <div class="pa-4">
                            <div
                                v-for="opt in visibilityOptions"
                                :key="opt.value"
                                class="tt-radio-box"
                                :class="{ 'is-selected': form.visibility === opt.value }"
                                @click="form.visibility = opt.value"
                            >
                                <span class="tt-radio-dot" :class="{ 'is-on': form.visibility === opt.value }"></span>
                                {{ opt.label }}
                            </div>
                        </div>
                    </v-card>

                    <!-- Organization -->
                    <div class="tt-section"><v-icon size="16">mdi-shape-outline</v-icon><span>Organization</span></div>
                    <v-card flat class="tt-card tt-purple mb-5">
                        <div class="pa-4">
                            <div class="tt-field-label">Collections</div>
                            <div class="d-flex flex-wrap ga-2 mb-4">
                                <v-btn
                                    v-for="c in collections"
                                    :key="c.name"
                                    size="small"
                                    variant="outlined"
                                    class="text-none tt-collection-chip"
                                    :class="{ 'is-selected': c.selected }"
                                    @click="c.selected = !c.selected"
                                >
                                    <span class="tt-dot-sm" :style="{ background: c.selected ? '#0f9d6b' : '#9ca8a5' }"></span>{{ c.name }}
                                </v-btn>
                            </div>

                            <div class="tt-field-label">Vendor</div>
                            <v-text-field v-model="form.vendor" variant="outlined" density="comfortable" hide-details placeholder="Supplier or vendor name" />
                        </div>
                    </v-card>

                    <!-- Channels -->
                    <div class="tt-section"><v-icon size="16">mdi-broadcast</v-icon><span>Channels</span></div>
                    <v-card flat class="tt-card tt-teal mb-5">
                        <div class="pa-4">
                            <div v-for="ch in channels" :key="ch.name" class="tt-channel-row">
                                <v-icon size="16" color="#3d5450">{{ ch.icon }}</v-icon>
                                <span class="flex-grow-1">{{ ch.name }}</span>
                                <v-checkbox-btn v-model="ch.enabled" color="#0f9d6b" />
                            </div>
                        </div>
                    </v-card>

                    <!-- AI Suggestions -->
                    <v-alert variant="tonal" color="#0f9d6b" class="tt-ai-alert mb-5" icon="mdi-creation">
                        <div class="font-weight-medium mb-1">AI Suggestions</div>
                        <div class="tt-sub">{{ aiSuggestion }}</div>
                    </v-alert>

                    <v-btn block size="large" color="#0f9d6b" class="text-none tt-submit-btn" :loading="submitting" @click="submitProduct">
                        {{ form.visibility === "Draft" ? "Save as draft" : "Publish product" }}
                    </v-btn>
                    <v-btn block variant="text" class="text-none mt-1" to="/admin/catalog">Cancel</v-btn>
                </div>
            </v-col>
        </v-row>

        <v-snackbar v-model="successSnackbar" color="#0f9d6b" timeout="4000">
            Product saved successfully.
        </v-snackbar>
    </div>
</template>

<script>
let imgSeq = 0;

export default {
    name: "CreateProduct",

    data() {
        return {
            dragOver: false,
            submitting: false,
            successSnackbar: false,

            // ── Sample option lists: replace with API calls ──
            categoryOptions: ["Electronics", "Fashion", "Beauty", "Home", "Sports", "Accessories"],
            brandOptions: ["Novatech", "Anker", "Baseus", "Xiaomi", "JBL", "Ugreen"],
            shippingClassOptions: ["Standard", "Fragile", "Oversized", "Digital (no shipping)"],

            form: {
                name: "",
                description: "",
                category: "Electronics",
                brand: "Novatech",

                price: null,
                compareAtPrice: null,
                sku: "",
                stockQty: 0,
                lowStockThreshold: 10,
                weight: null,
                trackInventory: true,

                shippingClass: "Standard",
                dimensions: { l: null, w: null, h: null },

                metaTitle: "",
                metaDescription: "",
                urlHandle: "",

                visibility: "Published",
                vendor: "",
            },

            images: [],

            variantGroups: [
                { name: "Color", color: "#0f9d6b", options: ["Black", "White", "Blue"], selected: "Black" },
                { name: "Size", color: "#f59e0b", options: ["S", "M", "L", "XL"], selected: "S" },
            ],

            visibilityOptions: [
                { label: "Published", value: "Published" },
                { label: "Draft", value: "Draft" },
                { label: "Scheduled", value: "Scheduled" },
            ],

            collections: [
                { name: "Summer Sale", selected: true },
                { name: "Trending", selected: false },
            ],

            channels: [
                { name: "Online Store", icon: "mdi-storefront-outline", enabled: true },
                { name: "Point of Sale", icon: "mdi-cash-register", enabled: false },
                { name: "Marketplaces", icon: "mdi-truck-outline", enabled: false },
            ],

            aiSuggestion: "Add at least 3 product images and a description over 40 words to improve search ranking.",
        };
    },

    computed: {
        heroStats() {
            const wordCount = this.form.description.trim() ? this.form.description.trim().split(/\s+/).length : 0;
            return [
                { icon: "mdi-image-multiple-outline", value: String(this.images.length), label: "Images added", note: this.images.length >= 3 ? "Good coverage" : "Add at least 3" },
                { icon: "mdi-palette-swatch-outline", value: String(this.variantGroups.length), label: "Variant options", note: this.variantGroups.map((g) => g.options.length).reduce((a, b) => a + b, 0) + " total combinations" },
                { icon: "mdi-cash-multiple", value: this.form.price ? "৳ " + Number(this.form.price).toLocaleString("en-IN") : "Not set", label: "Price", note: this.form.compareAtPrice ? "Compare-at ৳ " + this.form.compareAtPrice : "No compare-at price" },
                { icon: "mdi-text-long", value: wordCount + " words", label: "Description length", note: wordCount >= 40 ? "Good for SEO" : "Aim for 40+ words" },
            ];
        },
    },

    methods: {
        onFilesSelected(e) {
            this.addImages(e.target.files);
            e.target.value = "";
        },
        onDrop(e) {
            this.dragOver = false;
            this.addImages(e.dataTransfer.files);
        },
        addImages(fileList) {
            Array.from(fileList || [])
                .filter((f) => f.type.startsWith("image/"))
                .forEach((file) => {
                    this.images.push({ id: ++imgSeq, url: URL.createObjectURL(file), file });
                });
        },
        removeImage(img) {
            this.images = this.images.filter((i) => i.id !== img.id);
        },

        addVariantOption() {
            this.variantGroups.push({ name: "New option", color: "#2f5be7", options: ["Option A", "Option B"], selected: "Option A" });
        },

        async submitProduct() {
            this.submitting = true;
            const payload = {
                ...this.form,
                variants: this.variantGroups.map((g) => ({ name: g.name, options: g.options, selected: g.selected })),
                collections: this.collections.filter((c) => c.selected).map((c) => c.name),
                channels: this.channels.filter((c) => c.enabled).map((c) => c.name),
                images: this.images.map((i) => i.file),
            };

            try {
                // Hook this up to your real endpoint, e.g.:
                // const formData = new FormData();
                // Object.entries(payload).forEach(([k, v]) => formData.append(k, v));
                // await this.axios.post("/api/admin/products", formData);
                await new Promise((resolve) => setTimeout(resolve, 600)); // simulated latency
                this.successSnackbar = true;
                this.$router.push("/admin/catalog");
            } finally {
                this.submitting = false;
            }
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-dash {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    background: var(--bg);
    padding-bottom: 32px;
}

.tt-hero {
    position: relative;
    background: linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b5f50 100%);
    color: #fff;
    border-radius: 14px;
    padding: 24px 24px 22px;
    overflow: hidden;
}
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.2; letter-spacing: -0.3px; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; }
.tt-ghost-btn { color: #fff !important; border-color: rgba(255, 255, 255, 0.4) !important; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); margin-top: 22px; }
.tt-hero-item { display: flex; align-items: flex-start; gap: 12px; padding: 4px 18px; border-left: 1px solid rgba(255, 255, 255, 0.14); }
.tt-hero-item:first-child { border-left: 0; padding-left: 0; }
.tt-hero-icon { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.2); flex: none; }
.tt-hero-value { font-size: 19px; font-weight: 700; line-height: 1.2; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.85); }
.tt-hero-note { font-size: 11px; color: rgba(255, 255, 255, 0.55); }
.tt-hero-line { position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: linear-gradient(90deg, #ec4899, rgba(236, 72, 153, 0) 70%); }

@media (max-width: 959px) {
    .tt-hero-strip { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .tt-hero-item:nth-child(2n+1) { border-left: 0; padding-left: 0; }
}
@media (max-width: 599px) {
    .tt-hero-strip { grid-template-columns: 1fr; }
    .tt-hero-item { border-left: 0; padding-left: 0; }
}

.tt-section {
    display: flex; align-items: center; gap: 8px;
    margin: 26px 0 12px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase;
    color: #3d5450;
}
.tt-section:first-of-type { margin-top: 4px; }

.tt-card {
    --accent: #2f5be7;
    --tint: #eaf0ff;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px !important;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04);
    overflow: hidden;
}
.tt-blue   { --accent: #2f5be7; --tint: #e9efff; }
.tt-green  { --accent: #0f9d6b; --tint: #dff5ec; }
.tt-purple { --accent: #7c3aed; --tint: #f0e9ff; }
.tt-teal   { --accent: #0f766e; --tint: #ddf3f0; }

.tt-dot-sm { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.tt-sub { font-size: 12.5px; color: var(--muted); }
.tt-field-label { font-size: 12px; font-weight: 600; color: #3d5450; margin-bottom: 8px; letter-spacing: 0.3px; text-transform: uppercase; }

.tt-sticky { position: sticky; top: 16px; }

/* ───────── Media dropzone ───────── */
.tt-dropzone {
    border: 1px dashed #c7d3d0; border-radius: 10px; background: #fbfdfc;
    padding: 36px 16px; text-align: center; cursor: pointer; transition: border-color 0.15s, background 0.15s;
}
.tt-dropzone:hover, .tt-dropzone.is-dragover { border-color: #0f9d6b; background: #f0faf5; }
.tt-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 10px; }
.tt-media-thumb { position: relative; border-radius: 8px; overflow: hidden; aspect-ratio: 1; background: #eef2f1; }
.tt-media-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tt-media-cover {
    position: absolute; top: 6px; left: 6px; display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 600; color: #fff; background: rgba(15, 42, 38, 0.65);
    padding: 2px 8px; border-radius: 10px;
}
.tt-media-cover .v-icon { color: #6ee7b7 !important; }
.tt-media-remove {
    position: absolute; top: 4px; right: 4px; background: rgba(15, 42, 38, 0.55) !important;
    color: #fff !important; width: 20px !important; height: 20px !important;
}

/* ───────── Switch row ───────── */
.tt-switch-row { display: flex; align-items: center; justify-content: space-between; border: 1px solid var(--line); border-radius: 8px; padding: 10px 14px; font-size: 13.5px; background: #fbfdfc; }

/* ───────── Variants ───────── */
.tt-variant-group { display: flex; align-items: center; gap: 12px; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; background: #fbfdfc; flex-wrap: wrap; }
.tt-variant-name { font-size: 13px; font-weight: 600; display: flex; align-items: center; min-width: 70px; }
.tt-variant-options { display: flex; flex-wrap: wrap; gap: 8px; flex: 1; }
.tt-variant-chip { border-color: var(--line) !important; color: var(--ink) !important; }
.tt-variant-chip.is-selected { border-color: #0f9d6b !important; color: #0f7a4d !important; background: #eaf8f1 !important; }
.tt-variant-edit { margin-left: auto; }

/* ───────── Visibility radio boxes ───────── */
.tt-radio-box {
    display: flex; align-items: center; gap: 10px; font-size: 13.5px;
    border: 1px solid var(--line); border-radius: 8px; padding: 10px 14px; margin-bottom: 10px;
    background: #fbfdfc; cursor: pointer; transition: border-color 0.15s, background 0.15s;
}
.tt-radio-box:last-child { margin-bottom: 0; }
.tt-radio-box.is-selected { border-color: #0f9d6b; background: #eaf8f1; font-weight: 600; }
.tt-radio-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #c7d3d0; display: inline-block; position: relative; flex: none; }
.tt-radio-dot.is-on { border-color: #0f9d6b; }
.tt-radio-dot.is-on::after { content: ""; position: absolute; inset: 3px; border-radius: 50%; background: #0f9d6b; }

/* ───────── Collections chips ───────── */
.tt-collection-chip { border-color: var(--line) !important; color: var(--ink) !important; }
.tt-collection-chip.is-selected { border-color: #0f9d6b !important; color: #0f7a4d !important; background: #eaf8f1 !important; }

/* ───────── Channels ───────── */
.tt-channel-row { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line); font-size: 13.5px; }
.tt-channel-row:last-child { border-bottom: 0; }

/* ───────── AI suggestions alert ───────── */
.tt-ai-alert { border: 1px solid #b7e6cd !important; }

.tt-submit-btn { color: #fff !important; font-weight: 600; }
</style>