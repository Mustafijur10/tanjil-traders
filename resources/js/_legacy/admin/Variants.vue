<template>
    <v-slide-x-transition appear>
        <div class="tt-var">
            <v-window v-model="tab">
                <!-- ═════════════════════════ Variant list ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <header class="tt-hero">
                        <div class="d-flex flex-wrap align-end ga-4">
                            <div>
                                <h1 class="tt-h1">Product variants</h1>
                                <div class="tt-hero-sub">Every colour, size and capacity you sell, with its own SKU, price and stock</div>
                            </div>
                            <v-spacer />
                            <div class="d-flex flex-wrap align-center ga-3">
                                <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-download" @click="exportCsv">Export CSV</v-btn>
                                <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="openAdd()">Create variants</v-btn>
                            </div>
                        </div>

                        <div class="tt-hero-strip">
                            <div v-for="s in heroStats" :key="s.label" class="tt-hero-item" :style="{ '--hue': s.color }">
                                <div class="tt-hero-icon"><v-icon :icon="s.icon" size="20" color="white" /></div>
                                <div class="flex-grow-1">
                                    <div class="tt-hero-value">{{ s.value }}</div>
                                    <div class="tt-hero-label">{{ s.label }}</div>
                                    <div class="tt-hero-note">{{ s.note }}</div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Insight row -->
                    <v-row dense class="mt-3">
                        <v-col cols="12" md="5">
                            <v-card flat class="tt-card h-100">
                                <div class="tt-head">
                                    <div class="tt-head-ic"><v-icon size="16">mdi-warehouse</v-icon></div>
                                    <div>
                                        <div class="tt-card-title">Stock health</div>
                                        <div class="tt-sub">Select a row to filter the table</div>
                                    </div>
                                    <v-spacer />
                                    <v-btn v-if="segment !== 'all'" variant="text" size="small" class="text-none tt-link" @click="segment = 'all'">Clear filter</v-btn>
                                </div>
                                <div class="tt-body">
                                    <div class="tt-stack"><span v-for="h in health" :key="h.value" :style="{ width: h.pct + '%', background: h.color }"></span></div>
                                    <button v-for="h in health" :key="h.value" type="button" class="tt-dist" :class="{ on: segment === h.value }" :aria-pressed="segment === h.value" @click="segment = segment === h.value ? 'all' : h.value">
                                        <span class="tt-dist-dot" :style="{ background: h.color }"></span>
                                        <span class="tt-dist-label">{{ h.label }}</span>
                                        <span class="tt-dist-count">{{ h.count }}</span>
                                        <span class="tt-dist-pct">{{ h.pct }}%</span>
                                    </button>
                                </div>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="7">
                            <v-card flat class="tt-card h-100">
                                <div class="tt-head">
                                    <div class="tt-head-ic"><v-icon size="16">mdi-lightbulb-on-outline</v-icon></div>
                                    <div>
                                        <div class="tt-card-title">Needs your attention</div>
                                        <div class="tt-sub">Gaps that lose sales or confuse shoppers</div>
                                    </div>
                                </div>
                                <div class="tt-body">
                                    <div v-for="a in attention" :key="a.title" class="tt-attn" :style="{ '--c': a.color }">
                                        <div class="tt-attn-ic"><v-icon size="18" color="white">{{ a.icon }}</v-icon></div>
                                        <div class="flex-grow-1">
                                            <div class="tt-strong">{{ a.title }}</div>
                                            <div class="tt-sub">{{ a.text }}</div>
                                        </div>
                                        <v-btn variant="outlined" size="small" class="text-none tt-attn-btn" :disabled="!a.count" @click="a.apply">{{ a.label }}</v-btn>
                                    </div>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- Table -->
                    <v-card flat class="tt-card mt-5">
                        <div class="tt-head tt-head-wrap">
                            <div class="tt-head-ic"><v-icon size="16">mdi-source-branch</v-icon></div>
                            <div>
                                <div class="tt-card-title">All variants</div>
                                <div class="tt-sub">{{ filteredItems.length }} of {{ items.length }} shown</div>
                            </div>
                            <v-spacer />
                            <v-select v-model="productFilter" :items="productOptions" prepend-inner-icon="mdi-package-variant-closed" variant="outlined" density="compact" hide-details class="tt-filter mr-2" />
                            <v-text-field v-model="searchValue" placeholder="Search name, SKU, option…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details class="tt-search" @click:clear="searchValue = ''" />
                        </div>

                        <div class="tt-segments" role="tablist" aria-label="Stock status">
                            <button v-for="s in segments" :key="s.value" type="button" role="tab" class="tt-segment" :class="{ on: segment === s.value }" :aria-selected="segment === s.value" @click="segment = s.value">
                                {{ s.label }}<span class="tt-segment-count">{{ s.count }}</span>
                            </button>
                        </div>

                        <div v-if="selected.length" class="tt-bulk" role="status">
                            <span class="tt-strong">{{ selected.length }} selected</span>
                            <v-spacer />
                            <v-btn size="small" variant="flat" class="text-none tt-bulk-ok" prepend-icon="mdi-pencil-outline" @click="bulkOpen = true">Update</v-btn>
                            <v-btn size="small" variant="text" class="text-none" @click="selected = []">Clear</v-btn>
                        </div>

                        <v-card-text class="tt-body pa-0">
                            <EasyDataTable
                                v-model:items-selected="selected"
                                :headers="headers"
                                :items="filteredItems"
                                table-class-name="customize-table"
                                buttons-pagination
                                :rows-per-page="12"
                                :rows-items="[10, 12, 25, 50]"
                                :fixedIndex="true"
                                :loading="loading"
                                sort-by="sold"
                                sort-type="desc"
                                empty-message="No variants match these filters"
                            >
                                <template #item-product="item">
                                    <div class="tt-person" @click="openDetail(item)">
                                        <div class="tt-person-text">
                                            <div class="tt-name tt-clamp-1">{{ item.product }}</div>
                                            <div class="tt-vals mt-1">
                                                <span v-for="(v, k) in item.options" :key="k" class="tt-val"><i v-if="attrType(k) === 'Colour'" class="tt-dot" :style="{ background: colourDot(v) }"></i>{{ v }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #item-sku="item">
                                    <span class="tt-key">{{ item.sku }}</span>
                                </template>

                                <template #item-price="item">
                                    <div class="text-right">
                                        <div class="tt-strong">{{ money(item.price) }}</div>
                                        <div v-if="item.comparePrice > item.price" class="tt-sub tt-strike">{{ money(item.comparePrice) }}</div>
                                    </div>
                                </template>

                                <template #item-stock="item">
                                    <div class="tt-stockcell">
                                        <span class="tt-strong">{{ item.stock }}</span>
                                        <span class="tt-stockbar"><span :style="{ width: Math.min(100, (item.stock / 60) * 100) + '%', background: stateColor(item.state) }"></span></span>
                                    </div>
                                </template>

                                <template #item-sold="item">
                                    <span class="tt-muted">{{ item.sold }}</span>
                                </template>

                                <template #item-state="item">
                                    <span class="tt-chip" :class="'tone-' + stateTone(item.state)">{{ item.state }}</span>
                                </template>

                                <template #item-operation="item">
                                    <div class="tt-actions">
                                        <v-btn icon="mdi-pencil-outline" size="small" variant="text" color="primary" aria-label="Edit variant" @click="openDetail(item)" />
                                        <v-btn icon="mdi-plus-box-multiple-outline" size="small" variant="text" color="#0a5548" aria-label="Add more variants to this product" @click="openAdd(item.productId)" />
                                        <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" aria-label="Delete variant" @click="removeItem(item)" />
                                    </div>
                                </template>
                            </EasyDataTable>
                        </v-card-text>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ Create variants ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <header class="tt-hero tt-hero-slim">
                        <div class="d-flex flex-wrap align-center ga-3">
                            <div>
                                <h1 class="tt-h1">Create variants</h1>
                                <div class="tt-hero-sub">Pick a product and its options. We'll list every combination so you can set prices and stock in one go.</div>
                            </div>
                            <v-spacer />
                            <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-arrow-left" @click="closeAdd">Back to variants</v-btn>
                            <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-check" :loading="saving" :disabled="!newRows.length" @click="saveVariants">
                                {{ newRows.length ? "Create " + newRows.length + (newRows.length === 1 ? " variant" : " variants") : "Create variants" }}
                            </v-btn>
                        </div>
                    </header>

                    <v-row dense class="mt-3">
                        <v-col cols="12" md="8">
                            <!-- Step: product -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-package-variant-closed</v-icon></div><span class="tt-card-title">Product</span></div>
                                <v-card-text class="tt-body">
                                    <v-autocomplete v-model="form.productId" :items="productChoices" variant="outlined" density="comfortable" hide-details placeholder="Search for a product" prepend-inner-icon="mdi-magnify" @update:model-value="onProductChange" />
                                    <div v-if="product" class="tt-sub mt-2">
                                        {{ product.brand }} · base price {{ money(product.basePrice) }} · {{ existingCount }} {{ existingCount === 1 ? "variant" : "variants" }} already created
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Step: options -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-tune-variant</v-icon></div><span class="tt-card-title">Options</span></div>
                                <v-card-text class="tt-body">
                                    <div v-if="!product" class="tt-empty">Choose a product first to see its options.</div>
                                    <div v-else-if="!productAttrs.length" class="tt-empty">
                                        This product's category has no variant options. Mark an attribute as “Use for product variants” on the Attributes page.
                                    </div>
                                    <div v-for="a in productAttrs" :key="a.name" class="tt-optrow">
                                        <div class="tt-optrow-head">
                                            <v-checkbox-btn v-model="form.use[a.name]" color="#0f9d6b" :aria-label="'Use ' + a.name" @update:model-value="rows = []" />
                                            <span class="tt-strong">{{ a.name }}</span>
                                            <span class="tt-sub">{{ a.options.length }} values available</span>
                                        </div>
                                        <v-combobox v-if="form.use[a.name]" v-model="form.values[a.name]" :items="a.options" multiple chips closable-chips variant="outlined" density="compact" hide-details :placeholder="'Choose ' + a.name.toLowerCase() + ' values'" class="mt-2" @update:model-value="rows = []" />
                                    </div>

                                    <div v-if="product && productAttrs.length" class="d-flex flex-wrap align-center ga-3 mt-4">
                                        <div>
                                            <div class="tt-field-label">Starting price (৳)</div>
                                            <v-text-field v-model.number="form.basePrice" type="number" min="0" variant="outlined" density="compact" hide-details style="width: 170px" />
                                        </div>
                                        <div>
                                            <div class="tt-field-label">Starting stock</div>
                                            <v-text-field v-model.number="form.baseStock" type="number" min="0" variant="outlined" density="compact" hide-details style="width: 140px" />
                                        </div>
                                        <v-btn variant="flat" class="text-none tt-btn-solid align-self-end" prepend-icon="mdi-table-plus" @click="generate">List combinations</v-btn>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Step: matrix -->
                            <v-card v-if="rows.length" flat class="tt-card mb-5">
                                <div class="tt-head tt-head-wrap">
                                    <div class="tt-head-ic"><v-icon size="16">mdi-table-edit</v-icon></div>
                                    <div>
                                        <div class="tt-card-title">Set price and stock</div>
                                        <div class="tt-sub">{{ rows.length }} combinations · {{ rows.length - newRows.length }} skipped</div>
                                    </div>
                                    <v-spacer />
                                    <v-btn variant="text" size="small" class="text-none tt-link" prepend-icon="mdi-arrow-collapse-down" @click="applyToAll">Use starting price and stock for all</v-btn>
                                </div>
                                <div class="tt-matrix-wrap">
                                    <v-table class="tt-table">
                                        <thead>
                                            <tr><th>Variant</th><th>SKU</th><th class="text-right">Price (৳)</th><th class="text-right">Stock</th><th class="text-center">On sale</th><th></th></tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(r, i) in rows" :key="r.key" :class="{ 'tt-row-off': r.exists }">
                                                <td>
                                                    <div class="tt-vals">
                                                        <span v-for="(v, k) in r.options" :key="k" class="tt-val"><i v-if="attrType(k) === 'Colour'" class="tt-dot" :style="{ background: colourDot(v) }"></i>{{ v }}</span>
                                                    </div>
                                                    <span v-if="r.exists" class="tt-chip tone-gray mt-1">Already exists</span>
                                                </td>
                                                <td><v-text-field v-model="r.sku" variant="outlined" density="compact" hide-details :disabled="r.exists" class="tt-cell tt-cell-sku" :aria-label="'SKU for ' + r.key" /></td>
                                                <td><v-text-field v-model.number="r.price" type="number" min="0" variant="outlined" density="compact" hide-details :disabled="r.exists" class="tt-cell" :aria-label="'Price for ' + r.key" /></td>
                                                <td><v-text-field v-model.number="r.stock" type="number" min="0" variant="outlined" density="compact" hide-details :disabled="r.exists" class="tt-cell" :aria-label="'Stock for ' + r.key" /></td>
                                                <td class="text-center"><v-checkbox-btn v-model="r.active" color="#0f9d6b" :disabled="r.exists" :aria-label="'On sale ' + r.key" /></td>
                                                <td><v-btn icon="mdi-close" size="small" variant="text" color="#6b7f7b" aria-label="Remove this combination" @click="rows.splice(i, 1)" /></td>
                                            </tr>
                                        </tbody>
                                    </v-table>
                                </div>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="4">
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-clipboard-check-outline</v-icon></div><span class="tt-card-title">What will be created</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-summary">
                                        <div><small>New variants</small><b>{{ newRows.length }}</b></div>
                                        <div><small>Skipped</small><b>{{ rows.length - newRows.length }}</b></div>
                                        <div><small>Total stock</small><b>{{ newStock }}</b></div>
                                        <div><small>Price range</small><b>{{ priceRange }}</b></div>
                                    </div>
                                    <div v-if="!rows.length" class="tt-sub mt-3">Pick options and choose “List combinations” to preview them here.</div>
                                    <div v-else class="tt-sub mt-3">Combinations that already exist are skipped, so nothing is overwritten.</div>
                                </v-card-text>
                            </v-card>

                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-help-circle-outline</v-icon></div><span class="tt-card-title">Good to know</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-line"><v-icon size="16">mdi-barcode</v-icon><span>SKUs are made from the brand and options. Edit them if you already use your own codes.</span></div>
                                    <div class="tt-line"><v-icon size="16">mdi-counter</v-icon><span>You can create up to {{ MAX_COMBOS }} combinations at a time.</span></div>
                                    <div class="tt-line"><v-icon size="16">mdi-eye-off-outline</v-icon><span>Turn off “On sale” to keep a variant hidden until stock arrives.</span></div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- ═════════════════════════ Edit drawer ═════════════════════════ -->
            <v-navigation-drawer v-model="drawer" location="right" temporary width="420" class="tt-drawer">
                <template v-if="current">
                    <div class="tt-drawer-hero">
                        <div class="d-flex align-start">
                            <span class="tt-chip tone-light">{{ current.state }}</span>
                            <v-spacer />
                            <v-btn icon="mdi-close" size="small" variant="text" color="white" aria-label="Close" @click="drawer = false" />
                        </div>
                        <div class="tt-drawer-name">{{ current.product }}</div>
                        <div class="tt-vals mt-2">
                            <span v-for="(v, k) in current.options" :key="k" class="tt-val tt-val-light"><i v-if="attrType(k) === 'Colour'" class="tt-dot" :style="{ background: colourDot(v) }"></i>{{ k }}: {{ v }}</span>
                        </div>
                    </div>

                    <div class="tt-drawer-stats">
                        <div class="tt-stat"><small>In stock</small><b>{{ current.stock }}</b></div>
                        <div class="tt-stat"><small>Sold (30d)</small><b>{{ current.sold }}</b></div>
                        <div class="tt-stat"><small>Stock value</small><b>{{ shortMoney(current.price * current.stock) }}</b></div>
                    </div>

                    <div class="tt-drawer-body">
                        <div class="tt-field-label">SKU</div>
                        <v-text-field v-model="edit.sku" variant="outlined" density="comfortable" hide-details class="mb-4" />

                        <v-row dense>
                            <v-col cols="6">
                                <div class="tt-field-label">Price (৳)</div>
                                <v-text-field v-model.number="edit.price" type="number" min="0" variant="outlined" density="comfortable" hide-details class="mb-4" />
                            </v-col>
                            <v-col cols="6">
                                <div class="tt-field-label">Old price (৳)</div>
                                <v-text-field v-model.number="edit.comparePrice" type="number" min="0" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="Optional" />
                            </v-col>
                        </v-row>
                        <div v-if="edit.comparePrice > edit.price && edit.price > 0" class="tt-sub mb-4">Shoppers see {{ Math.round((1 - edit.price / edit.comparePrice) * 100) }}% off.</div>

                        <div class="tt-field-label">Stock</div>
                        <div class="d-flex align-center ga-2 mb-4">
                            <v-btn icon="mdi-minus" size="small" variant="outlined" aria-label="Remove 1 from stock" @click="edit.stock = Math.max(0, (Number(edit.stock) || 0) - 1)" />
                            <v-text-field v-model.number="edit.stock" type="number" min="0" variant="outlined" density="comfortable" hide-details class="tt-stock-input" />
                            <v-btn icon="mdi-plus" size="small" variant="outlined" aria-label="Add 1 to stock" @click="edit.stock = (Number(edit.stock) || 0) + 1" />
                        </div>

                        <div class="tt-option-row">
                            <div><div>On sale</div><div class="tt-sub">Turn off to hide this variant from the shop</div></div>
                            <v-checkbox-btn v-model="edit.active" color="#0f9d6b" />
                        </div>
                    </div>

                    <div class="tt-drawer-foot">
                        <v-btn variant="outlined" class="text-none flex-grow-1" prepend-icon="mdi-delete-outline" color="error" @click="removeFromDrawer">Delete</v-btn>
                        <v-btn variant="flat" class="text-none flex-grow-1 tt-btn-solid" prepend-icon="mdi-check" :loading="saving" @click="saveEdit">Save changes</v-btn>
                    </div>
                </template>
            </v-navigation-drawer>

            <!-- Bulk update dialog -->
            <v-dialog v-model="bulkOpen" max-width="440">
                <v-card class="tt-dialog">
                    <v-card-title class="text-subtitle-1 font-weight-bold pt-5 px-5">Update {{ selected.length }} variants</v-card-title>
                    <v-card-text class="px-5">
                        <div class="tt-field-label">What do you want to change?</div>
                        <v-select v-model="bulkMode" :items="bulkModes" variant="outlined" density="comfortable" hide-details class="mb-4" />
                        <template v-if="bulkNeedsValue">
                            <div class="tt-field-label">{{ bulkLabel }}</div>
                            <v-text-field v-model.number="bulkValue" type="number" variant="outlined" density="comfortable" hide-details />
                        </template>
                    </v-card-text>
                    <v-card-actions class="pb-4 px-5">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="bulkOpen = false">Cancel</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-solid" :loading="bulkBusy" @click="applyBulk">Apply</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="440">
                <v-card class="tt-dialog">
                    <v-card-title class="text-subtitle-1 font-weight-bold pt-5 px-5">Delete variant</v-card-title>
                    <v-card-text class="px-5">Delete <b>{{ deletedName }}</b>? Past orders keep their record, but shoppers can no longer buy this variant. To pause it instead, turn off On sale.</v-card-text>
                    <v-card-actions class="pb-4 px-5">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="cancel">Keep variant</v-btn>
                        <v-btn color="error" variant="flat" class="text-none" :loading="deleting" @click="agree">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
const LOW_AT = 10;
const MAX_COMBOS = 100;
const fullMoney = (v) => "৳ " + Number(v || 0).toLocaleString("en-IN");
const shortMoney = (v) => {
    v = Number(v || 0);
    return "৳ " + (v >= 100000 ? (v / 100000).toFixed(1).replace(".0", "") + "L" : v >= 1000 ? Math.round(v / 1000) + "k" : v);
};
const COLOUR_MAP = { black: "#1f2937", white: "#f3f4f6", blue: "#2f5be7", red: "#dc2626", silver: "#c0c7cf", gray: "#6b7280", grey: "#6b7280", green: "#0f9d6b", pink: "#ec4899", gold: "#d4a017" };
const STATE_COLOR = { "In stock": "#0f9d6b", "Low stock": "#f59e0b", "Out of stock": "#ef6a45", Inactive: "#b9c6c2" };

const comboKey = (opts) => Object.keys(opts).sort().map((k) => k + "=" + opts[k]).join("|");
const cross = (lists) => lists.reduce((acc, list) => acc.flatMap((a) => list.values.map((v) => ({ ...a, [list.name]: v }))), [{}]);

const emptyForm = () => ({ productId: null, use: {}, values: {}, basePrice: 0, baseStock: 20 });

/* Sample data so the page looks right before your API is connected.
   Set to false once /api/variants, /api/products and /api/attributes return real data. */
const USE_SAMPLE_DATA_ON_ERROR = true;
const SAMPLE_ATTRS = [
    { name: "Colour", type: "Colour", options: ["Black", "White", "Blue", "Red", "Silver"] },
    { name: "Battery capacity", type: "Select", options: ["5000mAh", "10000mAh", "20000mAh", "30000mAh"] },
    { name: "Storage size", type: "Select", options: ["32GB", "64GB", "128GB", "256GB"] },
    { name: "Cable length", type: "Select", options: ["0.5m", "1m", "2m", "3m"] },
];
const SAMPLE_PRODUCTS = [
    { id: 1, name: "Anker PowerCore", brand: "Anker", basePrice: 2800, attrs: ["Battery capacity", "Colour"], pick: { "Battery capacity": ["10000mAh", "20000mAh"], Colour: ["Black", "White"] } },
    { id: 2, name: "Baseus Bowie E9 Earbuds", brand: "Baseus", basePrice: 1490, attrs: ["Colour"], pick: { Colour: ["Black", "White", "Blue"] } },
    { id: 3, name: "Xiaomi Smart Band 8", brand: "Xiaomi", basePrice: 3150, attrs: ["Colour"], pick: { Colour: ["Black", "Blue", "Silver"] } },
    { id: 4, name: "SanDisk Pendrive", brand: "SanDisk", basePrice: 780, attrs: ["Storage size"], pick: { "Storage size": ["64GB", "128GB", "256GB"] } },
    { id: 5, name: "Hoco Type-C Cable", brand: "Hoco", basePrice: 420, attrs: ["Cable length", "Colour"], pick: { "Cable length": ["1m", "2m", "3m"], Colour: ["Black", "White"] } },
    { id: 6, name: "Realme Buds T300", brand: "Realme", basePrice: 2790, attrs: ["Colour"], pick: { Colour: ["Black", "Silver"] } },
    { id: 7, name: "Logitech G102 Mouse", brand: "Logitech", basePrice: 1650, attrs: ["Colour"], pick: { Colour: ["Black", "White"] } },
];
const makeSku = (p, opts) => p.brand.slice(0, 2).toUpperCase() + "-" + String(p.id).padStart(3, "0") + "-" + Object.values(opts).map((v) => String(v).replace(/[^a-z0-9]/gi, "").slice(0, 4).toUpperCase()).join("-");
const makeSampleVariants = () => {
    let s = 19;
    const r = () => ((s = (s * 9301 + 49297) % 233280) / 233280);
    let id = 1;
    return SAMPLE_PRODUCTS.flatMap((p) => {
        const lists = p.attrs.map((name) => ({ name, values: p.pick[name] }));
        return cross(lists).map((opts) => {
            const step = Object.entries(opts).reduce((a, [k, v]) => (k === "Colour" ? a : a + p.pick[k].indexOf(v)), 0);
            const price = Math.round((p.basePrice * (1 + 0.32 * step)) / 10) * 10;
            const roll = r();
            return {
                id: id++, productId: p.id, product: p.name, sku: makeSku(p, opts), options: opts, price,
                comparePrice: r() < 0.3 ? Math.round((price * 1.12) / 10) * 10 : 0,
                stock: roll < 0.14 ? 0 : roll < 0.3 ? Math.floor(1 + r() * 9) : Math.floor(12 + r() * 60),
                sold: Math.floor(r() * 110), active: r() > 0.07,
            };
        });
    });
};

export default {
    name: "ProductVariants",

    data() {
        return {
            tab: "list",
            searchValue: "",
            segment: "all",
            productFilter: "All products",
            loading: false,
            MAX_COMBOS,

            items: [],
            products: [],
            attributes: [],
            selected: [],

            form: emptyForm(),
            rows: [],
            saving: false,

            drawer: false,
            current: null,
            edit: { sku: "", price: 0, comparePrice: 0, stock: 0, active: true },

            bulkOpen: false,
            bulkBusy: false,
            bulkMode: "Set stock to",
            bulkValue: null,
            bulkModes: ["Set stock to", "Add to stock", "Set price to", "Change price by %", "Turn on sale", "Turn off sale"],

            deleteDialog: false,
            deleting: false,
            deletedId: -1,
            deletedName: "",

            snackbar: false,
            snackbarText: "",
            snackbarColor: "#0f9d6b",

            headers: [
                { text: "Product and options", value: "product", sortable: true, width: 300 },
                { text: "SKU", value: "sku", sortable: true, width: 160 },
                { text: "Price", value: "price", sortable: true, width: 120 },
                { text: "Stock", value: "stock", sortable: true, width: 140 },
                { text: "Sold (30d)", value: "sold", sortable: true, width: 100 },
                { text: "Status", value: "state", sortable: true, width: 120 },
                { text: "Actions", value: "operation", width: 140 },
            ],
        };
    },

    computed: {
        rowsAll() {
            return this.items.map((v) => ({ ...v, state: this.stateOf(v) }));
        },
        productOptions() {
            return ["All products", ...[...new Set(this.items.map((v) => v.product))].sort()];
        },
        productChoices() {
            return this.products.map((p) => ({ value: p.id, title: p.name + " · " + p.brand }));
        },
        segments() {
            const n = (s) => this.rowsAll.filter((v) => v.state === s).length;
            return [
                { value: "all", label: "All", count: this.rowsAll.length },
                { value: "In stock", label: "In stock", count: n("In stock") },
                { value: "Low stock", label: "Low stock", count: n("Low stock") },
                { value: "Out of stock", label: "Out of stock", count: n("Out of stock") },
                { value: "Inactive", label: "Inactive", count: n("Inactive") },
            ];
        },
        filteredItems() {
            const q = (this.searchValue || "").trim().toLowerCase();
            return this.rowsAll.filter((v) => {
                if (this.segment !== "all" && v.state !== this.segment) return false;
                if (this.productFilter !== "All products" && v.product !== this.productFilter) return false;
                return !q || (v.product + " " + v.sku + " " + Object.values(v.options).join(" ")).toLowerCase().includes(q);
            });
        },
        health() {
            const total = Math.max(1, this.rowsAll.length);
            return this.segments.slice(1).map((s) => ({ ...s, color: STATE_COLOR[s.value], pct: Math.round((s.count / total) * 100) }));
        },
        heroStats() {
            const units = this.items.reduce((a, v) => a + v.stock, 0);
            const value = this.items.reduce((a, v) => a + v.stock * v.price, 0);
            const risk = this.rowsAll.filter((v) => v.state === "Low stock" || v.state === "Out of stock").length;
            return [
                { icon: "mdi-source-branch", color: "#0f9d6b", value: this.items.length.toLocaleString("en-IN"), label: "Variants", note: "Across " + new Set(this.items.map((v) => v.productId)).size + " products" },
                { icon: "mdi-package-variant", color: "#2f5be7", value: units.toLocaleString("en-IN"), label: "Units in stock", note: "Counting every variant" },
                { icon: "mdi-cash-multiple", color: "#7c3aed", value: shortMoney(value), label: "Stock value", note: "At selling price" },
                { icon: "mdi-alert-outline", color: "#ef6a45", value: String(risk), label: "Need restocking", note: "Low or out of stock" },
            ];
        },
        attention() {
            const topOut = this.rowsAll.filter((v) => v.state === "Out of stock" && v.sold >= 30);
            const low = this.rowsAll.filter((v) => v.state === "Low stock");
            const hiddenStock = this.rowsAll.filter((v) => v.state === "Inactive" && v.stock > 0);
            return [
                {
                    icon: "mdi-cart-off", color: "#ef6a45", count: topOut.length, label: "Show them",
                    title: topOut.length + " popular " + (topOut.length === 1 ? "variant is" : "variants are") + " out of stock",
                    text: "These sold 30 or more in the last month, so restocking them first pays off.",
                    apply: () => { this.segment = "Out of stock"; this.productFilter = "All products"; },
                },
                {
                    icon: "mdi-battery-low", color: "#f59e0b", count: low.length, label: "Review",
                    title: low.length + " " + (low.length === 1 ? "variant has" : "variants have") + " " + LOW_AT + " or fewer left",
                    text: low.length ? low[0].product + " (" + Object.values(low[0].options).join(" / ") + ") is running low." : "Nothing is running low.",
                    apply: () => { this.segment = "Low stock"; this.productFilter = "All products"; },
                },
                {
                    icon: "mdi-eye-off-outline", color: "#7c3aed", count: hiddenStock.length, label: "Check",
                    title: hiddenStock.length + " hidden " + (hiddenStock.length === 1 ? "variant still has" : "variants still have") + " stock",
                    text: "Stock that can't be sold. Turn On sale back on or move it.",
                    apply: () => { this.segment = "Inactive"; this.productFilter = "All products"; },
                },
            ];
        },

        /* create form */
        product() {
            return this.products.find((p) => p.id === this.form.productId) || null;
        },
        productAttrs() {
            return this.product ? this.attributes.filter((a) => this.product.attrs.includes(a.name)) : [];
        },
        existingKeys() {
            return new Set(this.items.filter((v) => v.productId === this.form.productId).map((v) => comboKey(v.options)));
        },
        existingCount() {
            return this.items.filter((v) => v.productId === this.form.productId).length;
        },
        newRows() {
            return this.rows.filter((r) => !r.exists);
        },
        newStock() {
            return this.newRows.reduce((a, r) => a + (Number(r.stock) || 0), 0);
        },
        priceRange() {
            const p = this.newRows.map((r) => Number(r.price) || 0);
            if (!p.length) return "—";
            const lo = Math.min(...p), hi = Math.max(...p);
            return lo === hi ? fullMoney(lo) : shortMoney(lo) + " to " + shortMoney(hi);
        },

        /* bulk dialog */
        bulkNeedsValue() {
            return !["Turn on sale", "Turn off sale"].includes(this.bulkMode);
        },
        bulkLabel() {
            return { "Set stock to": "New stock for each variant", "Add to stock": "Units to add (use a minus to remove)", "Set price to": "New price (৳)", "Change price by %": "Percent change (e.g. 10 or -5)" }[this.bulkMode];
        },
    },

    watch: {
        segment() { this.selected = []; },
        productFilter() { this.selected = []; },
        searchValue() { this.selected = []; },
    },

    methods: {
        money: fullMoney,
        shortMoney,

        /* ── helpers ── */
        stateOf(v) {
            return !v.active ? "Inactive" : v.stock === 0 ? "Out of stock" : v.stock <= LOW_AT ? "Low stock" : "In stock";
        },
        stateTone(s) {
            return { "In stock": "green", "Low stock": "amber", "Out of stock": "red", Inactive: "gray" }[s] || "gray";
        },
        stateColor(s) {
            return STATE_COLOR[s] || "#b9c6c2";
        },
        attrType(name) {
            const a = this.attributes.find((x) => x.name === name);
            return a ? a.type : "Select";
        },
        colourDot(name) {
            return COLOUR_MAP[String(name).trim().toLowerCase()] || "#c8d4d0";
        },

        /* ── data ── */
        normalize(v) {
            return {
                ...v,
                productId: v.product_id ?? v.productId,
                product: v.product_name ?? v.product ?? "Product",
                options: v.options && typeof v.options === "object" ? v.options : {},
                price: Number(v.price || 0),
                comparePrice: Number(v.compare_price ?? v.comparePrice ?? 0),
                stock: Number(v.stock || 0),
                sold: Number(v.sold_30d ?? v.sold ?? 0),
                active: v.active !== undefined ? Boolean(v.active) : true,
            };
        },
        async allItem() {
            this.loading = true;
            try {
                const [v, p, a] = await Promise.all([this.axios.get("/api/variants"), this.axios.get("/api/products?variants=1"), this.axios.get("/api/attributes?variant=1")]);
                if (v.data && v.data.success) {
                    this.items = v.data.data.map(this.normalize);
                    this.products = (p.data?.data || []).map((x) => ({ id: x.id, name: x.name, brand: x.brand || "Brand", basePrice: Number(x.price || 0), attrs: x.variant_attributes || [] }));
                    this.attributes = (a.data?.data || []).map((x) => ({ name: x.name, type: x.type, options: x.options || [] }));
                } else if (USE_SAMPLE_DATA_ON_ERROR) this.useSample();
            } catch (error) {
                console.error("Failed to load variants:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) this.useSample();
                else this.showError("Couldn't load variants. Check your connection and refresh.");
            } finally {
                this.loading = false;
            }
        },
        useSample() {
            this.attributes = SAMPLE_ATTRS;
            this.products = SAMPLE_PRODUCTS.map(({ pick, ...p }) => p);
            this.items = makeSampleVariants().map(this.normalize);
        },

        /* ── create flow ── */
        openAdd(productId = null) {
            this.form = emptyForm();
            this.rows = [];
            this.drawer = false;
            if (productId) {
                this.form.productId = productId;
                this.onProductChange();
            }
            this.tab = "add";
        },
        closeAdd() {
            this.tab = "list";
            this.form = emptyForm();
            this.rows = [];
        },
        onProductChange() {
            this.rows = [];
            const use = {}, values = {};
            this.productAttrs.forEach((a) => { use[a.name] = true; values[a.name] = []; });
            this.form.use = use;
            this.form.values = values;
            this.form.basePrice = this.product ? this.product.basePrice : 0;
        },
        generate() {
            const lists = this.productAttrs.filter((a) => this.form.use[a.name] && (this.form.values[a.name] || []).length).map((a) => ({ name: a.name, values: this.form.values[a.name] }));
            if (!lists.length) return this.showError("Choose at least one option and add some values.");
            const total = lists.reduce((a, l) => a * l.values.length, 1);
            if (total > MAX_COMBOS) return this.showError("That makes " + total + " combinations. Keep it to " + MAX_COMBOS + " or fewer, and create the rest in a second batch.");

            const old = new Map(this.rows.map((r) => [r.key, r]));
            this.rows = cross(lists).map((opts) => {
                const key = comboKey(opts);
                return old.get(key) || { key, options: opts, sku: makeSku(this.product, opts), price: Number(this.form.basePrice) || 0, stock: Number(this.form.baseStock) || 0, active: true, exists: this.existingKeys.has(key) };
            });
            if (!this.newRows.length) this.showError("Every one of these combinations already exists for this product.");
        },
        applyToAll() {
            this.rows.forEach((r) => { if (!r.exists) { r.price = Number(this.form.basePrice) || 0; r.stock = Number(this.form.baseStock) || 0; } });
        },
        validateRows() {
            if (!this.newRows.length) return "There are no new variants to create.";
            const skus = new Set(this.items.map((v) => v.sku.toLowerCase()));
            for (const r of this.newRows) {
                const label = Object.values(r.options).join(" / ");
                if (!r.sku || !String(r.sku).trim()) return "Enter a SKU for " + label + ".";
                if (skus.has(String(r.sku).trim().toLowerCase())) return "The SKU " + r.sku + " is already used. Change it for " + label + ".";
                skus.add(String(r.sku).trim().toLowerCase());
                if (!(Number(r.price) > 0)) return "Enter a price above 0 for " + label + ".";
                if (!(Number(r.stock) >= 0) || !Number.isInteger(Number(r.stock))) return "Stock for " + label + " must be a whole number, 0 or more.";
            }
            return null;
        },
        async saveVariants() {
            const err = this.validateRows();
            if (err) return this.showError(err);

            this.saving = true;
            const payload = this.newRows.map((r) => ({ options: r.options, sku: r.sku.trim(), price: Number(r.price), stock: Number(r.stock), active: r.active }));
            const count = payload.length;
            try {
                const res = await this.axios.post(`/api/products/${this.form.productId}/variants`, { variants: payload });
                if (res.data && res.data.success !== false) {
                    this.showSuccess(count + (count === 1 ? " variant created." : " variants created."));
                    await this.allItem();
                    this.closeAdd();
                } else this.showError(res.data?.message || "Couldn't create the variants. Try again.");
            } catch (error) {
                console.error("Save error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    const p = this.product;
                    let id = Date.now();
                    const made = payload.map((v) => this.normalize({ ...v, id: id++, productId: p.id, product: p.name, comparePrice: 0, sold: 0 }));
                    this.items = [...made, ...this.items];
                    this.showSuccess(count + (count === 1 ? " variant created" : " variants created") + " (demo data).");
                    this.closeAdd();
                } else this.showError("Couldn't create the variants. Check your connection and try again.");
            } finally {
                this.saving = false;
            }
        },

        /* ── drawer edit ── */
        openDetail(item) {
            this.current = this.rowsAll.find((v) => v.id === item.id) || item;
            this.edit = { sku: this.current.sku, price: this.current.price, comparePrice: this.current.comparePrice, stock: this.current.stock, active: this.current.active };
            this.drawer = true;
        },
        async saveEdit() {
            const e = this.edit;
            if (!e.sku || !String(e.sku).trim()) return this.showError("Enter a SKU.");
            if (this.items.some((v) => v.id !== this.current.id && v.sku.toLowerCase() === String(e.sku).trim().toLowerCase())) return this.showError("That SKU is already used by another variant.");
            if (!(Number(e.price) > 0)) return this.showError("Enter a price above 0.");
            if (Number(e.comparePrice) && Number(e.comparePrice) <= Number(e.price)) return this.showError("The old price must be higher than the price, or left empty.");
            if (!(Number(e.stock) >= 0) || !Number.isInteger(Number(e.stock))) return this.showError("Stock must be a whole number, 0 or more.");

            this.saving = true;
            const id = this.current.id;
            const changes = { sku: String(e.sku).trim(), price: Number(e.price), comparePrice: Number(e.comparePrice) || 0, stock: Number(e.stock), active: Boolean(e.active) };
            const apply = () => {
                this.items = this.items.map((v) => (v.id === id ? { ...v, ...changes } : v));
                this.current = this.rowsAll.find((v) => v.id === id);
            };
            try {
                const res = await this.axios.put(`/api/variants/${id}`, changes);
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                apply();
                this.showSuccess("Variant updated.");
            } catch (error) {
                console.error("Update error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    apply();
                    this.showSuccess("Variant updated (demo data).");
                } else this.showError("Couldn't update the variant. Try again.");
            } finally {
                this.saving = false;
            }
        },

        /* ── bulk update ── */
        bulkChange(v) {
            const n = Number(this.bulkValue);
            switch (this.bulkMode) {
                case "Set stock to": return { stock: Math.round(n) };
                case "Add to stock": return { stock: Math.max(0, v.stock + Math.round(n)) };
                case "Set price to": return { price: Math.round(n) };
                case "Change price by %": return { price: Math.max(1, Math.round((v.price * (1 + n / 100)) / 10) * 10) };
                case "Turn on sale": return { active: true };
                default: return { active: false };
            }
        },
        async applyBulk() {
            const n = Number(this.bulkValue);
            if (this.bulkNeedsValue) {
                if (this.bulkValue === null || this.bulkValue === "" || Number.isNaN(n)) return this.showError("Enter a number to apply.");
                if (this.bulkMode === "Set stock to" && n < 0) return this.showError("Stock can't be below 0.");
                if (this.bulkMode === "Set price to" && n <= 0) return this.showError("Price must be above 0.");
                if (this.bulkMode === "Change price by %" && n <= -100) return this.showError("A price can't drop by 100% or more.");
            }
            const ids = this.selected.map((v) => v.id);
            if (!ids.length) return;
            this.bulkBusy = true;
            const apply = () => {
                this.items = this.items.map((v) => (ids.includes(v.id) ? { ...v, ...this.bulkChange(v) } : v));
                this.selected = [];
                this.bulkOpen = false;
                this.bulkValue = null;
            };
            try {
                const res = await this.axios.post("/api/variants/bulk", { ids, mode: this.bulkMode, value: this.bulkValue });
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                apply();
                this.showSuccess(ids.length + " variants updated.");
            } catch (error) {
                console.error("Bulk error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    apply();
                    this.showSuccess(ids.length + " variants updated (demo data).");
                } else this.showError("Couldn't update the variants. Try again.");
            } finally {
                this.bulkBusy = false;
            }
        },

        /* ── delete ── */
        removeItem(item) {
            this.deletedId = item.id;
            this.deletedName = item.product + " (" + Object.values(item.options).join(" / ") + ")";
            this.deleteDialog = true;
        },
        removeFromDrawer() {
            const v = this.current;
            this.drawer = false;
            if (v) this.removeItem(v);
        },
        cancel() {
            this.deleteDialog = false;
            this.deletedId = -1;
            this.deletedName = "";
        },
        async agree() {
            this.deleting = true;
            const id = this.deletedId;
            const drop = () => {
                this.items = this.items.filter((v) => v.id !== id);
                this.selected = this.selected.filter((v) => v.id !== id);
            };
            try {
                const res = await this.axios.delete(`/api/variants/${id}`);
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                drop();
                this.showSuccess("Variant deleted.");
            } catch (error) {
                console.error("Delete error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    drop();
                    this.showSuccess("Variant deleted (demo data).");
                } else this.showError("Couldn't delete the variant. Try again.");
            } finally {
                this.deleting = false;
                this.cancel();
            }
        },

        /* ── export ── */
        exportCsv() {
            const rows = this.filteredItems;
            if (!rows.length) return this.showError("There are no variants to export.");
            const head = ["Product", "Options", "SKU", "Price", "Old price", "Stock", "Sold (30d)", "Status"];
            const esc = (v) => '"' + String(v ?? "").replace(/"/g, '""') + '"';
            const lines = rows.map((v) => [v.product, Object.entries(v.options).map(([k, x]) => k + ": " + x).join(" | "), v.sku, v.price, v.comparePrice || "", v.stock, v.sold, v.state].map(esc).join(","));
            const blob = new Blob(["\ufeff" + [head.map(esc).join(","), ...lines].join("\n")], { type: "text/csv;charset=utf-8" });
            const el = document.createElement("a");
            el.href = URL.createObjectURL(blob);
            el.download = "product-variants.csv";
            el.click();
            URL.revokeObjectURL(el.href);
            this.showSuccess("Exported " + rows.length + " variants.");
        },

        showSuccess(msg) {
            this.snackbarText = msg;
            this.snackbarColor = "#0f9d6b";
            this.snackbar = true;
        },
        showError(msg) {
            this.snackbarText = msg;
            this.snackbarColor = "#b3261e";
            this.snackbar = true;
        },
    },

    created() {
        document.title = "Product variants";
        this.allItem();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-var {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg2: #f7faf9;
    --accent: #0f9d6b;
    --tint: #e3f6ee;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    padding-bottom: 36px;
    font-variant-numeric: tabular-nums;
    -webkit-font-smoothing: antialiased;
}

/* ───────── Hero ───────── */
.tt-hero {
    position: relative; overflow: hidden; color: #fff; padding: 24px 26px 26px; border-radius: 18px;
    background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%);
    box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95);
}
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-hero-slim { padding: 20px 26px 22px; }
.tt-h1 { font-size: 30px; font-weight: 700; line-height: 1.15; letter-spacing: -0.6px; color: #fff; }
.tt-hero-slim .tt-h1 { font-size: 24px; }
.tt-hero-sub { font-size: 13.5px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; max-width: 620px; }
.tt-btn-ghost { height: 40px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 40px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-btn-primary.v-btn--disabled { opacity: 0.55; }
.tt-btn-solid { background: #0a5548 !important; color: #fff !important; font-weight: 600; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 22px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: flex-start; gap: 14px; padding: 15px 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); min-width: 0; }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -40px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.25; filter: blur(10px); }
.tt-hero-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-value { font-size: 22px; font-weight: 700; line-height: 1.2; letter-spacing: -0.4px; }
.tt-hero-label { font-size: 12.5px; font-weight: 500; color: rgba(255, 255, 255, 0.9); }
.tt-hero-note { font-size: 11.5px; color: rgba(255, 255, 255, 0.58); }
@media (max-width: 1279px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Cards ───────── */
.tt-card {
    position: relative; background: #fff; border: 1px solid var(--line) !important; border-radius: 16px !important;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35) !important; overflow: hidden;
}
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 8px; }
.tt-head-wrap { flex-wrap: wrap; row-gap: 10px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; line-height: 1.3; color: var(--ink); }
.tt-sub { font-size: 12px; color: var(--muted); margin-top: 1px; }
.tt-body { padding: 8px 18px 18px; }
.tt-muted { color: var(--muted); font-size: 13px; }
.tt-strong { font-weight: 600; color: var(--ink); }
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-key { font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 12px; color: #3a4a47; }
.tt-strike { text-decoration: line-through; }
.tt-link { color: #0a5548 !important; font-weight: 600; }
.tt-field-label { font-size: 12.5px; font-weight: 600; color: #3a4a47; margin-bottom: 8px; }
.tt-search { max-width: 300px; min-width: 210px; }
.tt-filter { max-width: 250px; min-width: 180px; }
.tt-search :deep(.v-field), .tt-filter :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-clamp-1 { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-empty { font-size: 13px; color: var(--muted); background: var(--bg2); border: 1px dashed #c8d4d0; border-radius: 10px; padding: 14px; }

/* ───────── Stock health ───────── */
.tt-stack { display: flex; gap: 3px; height: 12px; margin: 6px 0 14px; border-radius: 12px; overflow: hidden; background: #e8eeec; }
.tt-stack span { display: block; height: 100%; border-radius: 4px; }
.tt-dist { display: flex; align-items: center; gap: 12px; width: 100%; border: 0; background: transparent; font: inherit; text-align: left; padding: 8px 10px; border-radius: 10px; cursor: pointer; transition: background 0.15s; }
.tt-dist:hover { background: var(--bg2); }
.tt-dist.on { background: var(--tint); }
.tt-dist:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-dist-dot { width: 10px; height: 10px; border-radius: 50%; flex: none; }
.tt-dist-label { flex: 1; font-size: 13px; font-weight: 600; }
.tt-dist-count { font-size: 13px; font-weight: 700; }
.tt-dist-pct { flex: 0 0 42px; text-align: right; font-size: 12px; color: var(--muted); }

/* ───────── Attention ───────── */
.tt-attn { --c: #ef6a45; display: flex; align-items: center; gap: 14px; padding: 12px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); margin-bottom: 10px; }
.tt-attn:last-child { margin-bottom: 0; }
.tt-attn-ic { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; background: var(--c); flex: none; box-shadow: 0 8px 14px -8px var(--c); }
.tt-attn-btn { border-color: #0a5548 !important; color: #0a5548 !important; font-weight: 600; flex: none; }
@media (max-width: 599px) { .tt-attn { flex-wrap: wrap; } .tt-attn-btn { width: 100%; } }

/* ───────── Segments + bulk bar ───────── */
.tt-segments { display: flex; gap: 4px; padding: 6px 14px 10px; overflow-x: auto; scrollbar-width: none; border-bottom: 1px solid var(--line); }
.tt-segments::-webkit-scrollbar { display: none; }
.tt-segment { flex: none; display: inline-flex; align-items: center; gap: 8px; border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: var(--muted); padding: 7px 14px; border-radius: 11px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-segment:hover { color: var(--ink); background: #eaf2ef; }
.tt-segment.on { background: #0a5548; color: #fff; font-weight: 600; }
.tt-segment:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-segment-count { font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 10px; background: #e3eae8; color: #3a4a47; }
.tt-segment.on .tt-segment-count { background: rgba(255, 255, 255, 0.22); color: #fff; }
.tt-bulk { display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: #eaf8f1; border-bottom: 1px solid #b7e6cd; font-size: 13px; }
.tt-bulk-ok { background: #0f9d6b !important; color: #fff !important; font-weight: 600; }

/* ───────── Table cells ───────── */
.tt-person { padding: 6px 0; cursor: pointer; min-width: 0; }
.tt-person:hover .tt-name { color: #0a5548; text-decoration: underline; text-underline-offset: 3px; }
.tt-person-text { min-width: 0; }
.tt-actions { display: flex; gap: 0; }
.tt-vals { display: flex; flex-wrap: wrap; gap: 5px; }
.tt-val { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 500; padding: 2px 9px; border-radius: 8px; background: var(--bg2); border: 1px solid var(--line); color: #3a4a47; white-space: nowrap; }
.tt-val-light { background: rgba(255, 255, 255, 0.14); border-color: rgba(255, 255, 255, 0.28); color: #fff; }
.tt-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(15, 42, 38, 0.18); }
.tt-stockcell { display: flex; align-items: center; gap: 10px; }
.tt-stockcell .tt-strong { min-width: 26px; }
.tt-stockbar { flex: 1; max-width: 70px; height: 6px; border-radius: 6px; background: #e8eeec; overflow: hidden; }
.tt-stockbar span { display: block; height: 100%; border-radius: 6px; }

.tt-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; white-space: nowrap; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-red { background: #fce1d8; color: #b4381a; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.tone-light { background: rgba(255, 255, 255, 0.16); color: #fff; border: 1px solid rgba(255, 255, 255, 0.28); }

/* ───────── Create form ───────── */
.tt-optrow { padding: 12px 0; border-bottom: 1px solid var(--line); }
.tt-optrow:last-of-type { border-bottom: 0; }
.tt-optrow-head { display: flex; align-items: center; gap: 8px; }
.tt-matrix-wrap { overflow-x: auto; }
.tt-table { background: transparent !important; min-width: 640px; }
.tt-table th { font-size: 12px !important; font-weight: 600 !important; background: #0a5548 !important; color: #e6f4ef !important; white-space: nowrap; border-bottom: 0 !important; height: 42px !important; padding: 0 12px !important; }
.tt-table td { border-bottom: 1px solid #eef3f1 !important; padding: 8px 12px !important; vertical-align: middle; }
.tt-row-off { opacity: 0.55; }
.tt-cell { min-width: 96px; max-width: 130px; margin-left: auto; }
.tt-cell-sku { min-width: 150px; max-width: 200px; margin-left: 0; }
.tt-cell :deep(.v-field) { font-size: 13px; border-radius: 8px; background: var(--bg2); }
.tt-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.tt-summary > div { border-radius: 10px; padding: 10px 12px; background: var(--bg2); border: 1px solid var(--line); min-width: 0; }
.tt-summary small { display: block; font-size: 11px; color: var(--muted); }
.tt-summary b { display: block; font-size: 16px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-line { display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; line-height: 1.5; padding: 6px 0; color: #3a4a47; }
.tt-line .v-icon { color: var(--accent); margin-top: 2px; }
.tt-option-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; font-size: 13.5px; }

/* ───────── Drawer ───────── */
.tt-drawer { font-family: Poppins, "Segoe UI", sans-serif; }
.tt-drawer-hero { color: #fff; padding: 18px 20px 20px; background: radial-gradient(420px 200px at 100% 0%, rgba(110, 231, 183, 0.22), transparent 62%), linear-gradient(135deg, #053d35 0%, #0a5548 60%, #0b6150 100%); }
.tt-drawer-name { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; margin-top: 12px; }
.tt-drawer-stats { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid var(--line); background: #f4faf7; }
.tt-stat { padding: 12px 14px; border-left: 1px solid var(--line); min-width: 0; }
.tt-stat:first-child { border-left: 0; }
.tt-stat small { display: block; font-size: 11px; color: var(--muted); }
.tt-stat b { display: block; font-size: 14px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-drawer-body { padding: 18px 20px 96px; }
.tt-stock-input { max-width: 120px; }
.tt-stock-input :deep(input) { text-align: center; }
.tt-drawer-foot { position: absolute; left: 0; right: 0; bottom: 0; display: flex; gap: 10px; padding: 14px 20px; background: #fff; border-top: 1px solid var(--line); }
.tt-dialog { border-radius: 16px !important; }

/* ───────── EasyDataTable theme ───────── */
:deep(.customize-table) {
    --easy-table-border: 0;
    --easy-table-row-border: 1px solid #eef3f1;
    --easy-table-header-font-size: 12px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #e6f4ef;
    --easy-table-header-background-color: #0a5548;
    --easy-table-body-row-height: 66px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #f4f9f7;
    --easy-table-body-item-padding: 6px 14px;
    --easy-table-footer-background-color: #f7faf9;
    --easy-table-footer-font-color: #3a4a47;
    --easy-table-footer-height: 52px;
    --easy-table-loading-mask-background-color: #ffffff;
    --easy-table-scrollbar-track-color: #eef3f1;
    --easy-table-scrollbar-color: #c8d4d0;
    --easy-table-buttons-pagination-border: 1px solid #d5dfdc;
    font-family: Poppins, "Segoe UI", sans-serif;
}
:deep(.customize-table .buttons-pagination .item.button.active) { background: #0a5548; border-color: #0a5548; color: #fff; }
:deep(.customize-table .vue3-easy-data-table__header th) { font-weight: 600; letter-spacing: 0.2px; }
:deep(.customize-table .easy-checkbox input:checked + label::before) { background: #0f9d6b; border-color: #0f9d6b; }
@media (prefers-reduced-motion: reduce) { .tt-segment, .tt-dist { transition: none; } }
</style>