<template>
    <v-slide-x-transition appear>
        <div class="tt-attr">
            <v-window v-model="tab">
                <!-- ═════════════════════════ Attribute list ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <header class="tt-hero">
                        <div class="d-flex flex-wrap align-end ga-4">
                            <div>
                                <h1 class="tt-h1">Attributes and specifications</h1>
                                <div class="tt-hero-sub">Define the details shoppers compare, like battery size, connectivity and warranty, then choose which categories show them</div>
                            </div>
                            <v-spacer />
                            <div class="d-flex flex-wrap align-center ga-3">
                                <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-download" @click="exportCsv">Export CSV</v-btn>
                                <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="openAdd">Add attribute</v-btn>
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

                    <!-- Spec sheet preview + attention -->
                    <v-row dense class="mt-3">
                        <v-col cols="12" md="7">
                            <v-card flat class="tt-card h-100">
                                <div class="tt-head tt-head-wrap">
                                    <div class="tt-head-ic"><v-icon size="16">mdi-clipboard-text-outline</v-icon></div>
                                    <div>
                                        <div class="tt-card-title">Specification sheet preview</div>
                                        <div class="tt-sub">What a shopper sees on a product page, with example values</div>
                                    </div>
                                    <v-spacer />
                                    <v-select v-model="previewCategory" :items="categories" prepend-inner-icon="mdi-shape-outline" variant="outlined" density="compact" hide-details class="tt-filter" />
                                </div>
                                <div class="tt-body">
                                    <div v-if="!sheet.length" class="tt-empty">
                                        No attributes are shown for {{ previewCategory }} yet. Edit an attribute and add this category to it.
                                    </div>
                                    <div v-for="grp in sheet" :key="grp.group" class="tt-sheet-group">
                                        <div class="tt-sheet-t">{{ grp.group }}</div>
                                        <div v-for="a in grp.rows" :key="a.id" class="tt-sheet-row" @click="openDetail(a)">
                                            <span class="tt-sheet-k">{{ a.name }}</span>
                                            <span class="tt-sheet-v">{{ sampleValue(a) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="5">
                            <v-card flat class="tt-card h-100">
                                <div class="tt-head">
                                    <div class="tt-head-ic"><v-icon size="16">mdi-lightbulb-on-outline</v-icon></div>
                                    <div>
                                        <div class="tt-card-title">Worth a look</div>
                                        <div class="tt-sub">Fix these so filters and spec sheets work properly</div>
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
                            <div class="tt-head-ic"><v-icon size="16">mdi-tune-variant</v-icon></div>
                            <div>
                                <div class="tt-card-title">All attributes</div>
                                <div class="tt-sub">{{ filteredItems.length }} of {{ items.length }} shown</div>
                            </div>
                            <v-spacer />
                            <v-select v-model="groupFilter" :items="groupOptions" prepend-inner-icon="mdi-folder-outline" variant="outlined" density="compact" hide-details class="tt-filter mr-2" />
                            <v-text-field v-model="searchValue" placeholder="Search attributes…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details class="tt-search" @click:clear="searchValue = ''" />
                        </div>

                        <div class="tt-segments" role="tablist" aria-label="Attribute use">
                            <button v-for="s in segments" :key="s.value" type="button" role="tab" class="tt-segment" :class="{ on: segment === s.value }" :aria-selected="segment === s.value" @click="segment = s.value">
                                {{ s.label }}<span class="tt-segment-count">{{ s.count }}</span>
                            </button>
                        </div>

                        <v-card-text class="tt-body pa-0">
                            <EasyDataTable
                                :headers="headers"
                                :items="filteredItems"
                                table-class-name="customize-table"
                                buttons-pagination
                                :rows-per-page="12"
                                :rows-items="[10, 12, 25, 50]"
                                :fixedIndex="true"
                                :loading="loading"
                                sort-by="products"
                                sort-type="desc"
                                empty-message="No attributes match these filters"
                            >
                                <template #item-name="item">
                                    <div class="tt-person" @click="openDetail(item)">
                                        <span class="tt-type-ic"><v-icon size="18">{{ typeIcon(item.type) }}</v-icon></span>
                                        <div class="tt-person-text">
                                            <div class="tt-name">{{ item.name }}</div>
                                            <div class="tt-sub tt-key">{{ item.key }}</div>
                                        </div>
                                    </div>
                                </template>

                                <template #item-type="item">
                                    <span class="tt-chip tone-gray">{{ item.type }}</span>
                                </template>

                                <template #item-group="item">
                                    <span class="tt-muted">{{ item.group }}</span>
                                </template>

                                <template #item-values="item">
                                    <div v-if="item.options.length" class="tt-vals">
                                        <span v-for="o in item.options.slice(0, 3)" :key="o" class="tt-val"><i v-if="item.type === 'Colour'" class="tt-dot" :style="{ background: colourDot(o) }"></i>{{ o }}</span>
                                        <span v-if="item.options.length > 3" class="tt-val more">+{{ item.options.length - 3 }}</span>
                                    </div>
                                    <span v-else class="tt-muted">{{ item.type === "Number" ? "Number" + (item.unit ? " in " + item.unit : "") : item.type === "Yes/No" ? "Yes or No" : "Free text" }}</span>
                                </template>

                                <template #item-usage="item">
                                    <div class="d-flex flex-wrap ga-1">
                                        <span v-if="item.usedForVariants" class="tt-chip tone-violet">Variant</span>
                                        <span v-if="item.filterable" class="tt-chip tone-blue">Filter</span>
                                        <span v-if="item.required" class="tt-chip tone-amber">Required</span>
                                        <span v-if="!item.usedForVariants && !item.filterable && !item.required" class="tt-muted">Spec only</span>
                                    </div>
                                </template>

                                <template #item-catCount="item">
                                    <span v-if="item.catCount" class="tt-strong">{{ item.catCount }}</span>
                                    <span v-else class="tt-chip tone-red">None</span>
                                </template>

                                <template #item-products="item">
                                    <span class="tt-strong">{{ item.products }}</span>
                                </template>

                                <template #item-operation="item">
                                    <div class="tt-actions">
                                        <v-btn icon="mdi-eye-outline" size="small" variant="text" color="#0a5548" aria-label="View attribute" @click="openDetail(item)" />
                                        <v-btn icon="mdi-pencil-outline" size="small" variant="text" color="primary" aria-label="Edit attribute" @click="editItem(item)" />
                                        <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" aria-label="Delete attribute" @click="removeItem(item)" />
                                    </div>
                                </template>
                            </EasyDataTable>
                        </v-card-text>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ Add / Edit attribute ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <header class="tt-hero tt-hero-slim">
                        <div class="d-flex flex-wrap align-center ga-3">
                            <div>
                                <h1 class="tt-h1">{{ form.id ? "Edit attribute" : "Add attribute" }}</h1>
                                <div class="tt-hero-sub">{{ form.id ? "Update " + form.name + " and where it appears" : "Create a detail that products can carry, like Battery capacity or Water resistance" }}</div>
                            </div>
                            <v-spacer />
                            <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-arrow-left" @click="closeAdd">Back to attributes</v-btn>
                            <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-check" :loading="saving" @click="saveAttribute">{{ form.id ? "Save changes" : "Create attribute" }}</v-btn>
                        </div>
                    </header>

                    <v-row dense class="mt-3">
                        <v-col cols="12" md="8">
                            <!-- Basics -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-card-text-outline</v-icon></div><span class="tt-card-title">Attribute details</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Name <span class="tt-required">*</span></div>
                                            <v-text-field v-model="form.name" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Battery capacity" autofocus />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Spec group</div>
                                            <v-combobox v-model="form.group" :items="groupList" variant="outlined" density="comfortable" hide-details class="mb-4" />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-field-label">What kind of value is it?</div>
                                    <div class="tt-type-grid">
                                        <div v-for="t in typeChoices" :key="t.value" class="tt-radio-box" :class="{ 'is-selected': form.type === t.value }" role="radio" :aria-checked="form.type === t.value" tabindex="0" @click="setType(t.value)" @keydown.enter.prevent="setType(t.value)" @keydown.space.prevent="setType(t.value)">
                                            <span class="tt-type-ic"><v-icon size="18">{{ t.icon }}</v-icon></span>
                                            <div>
                                                <div>{{ t.value }}</div>
                                                <div class="tt-sub">{{ t.hint }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="form.type === 'Number'" class="mt-4">
                                        <div class="tt-field-label">Unit</div>
                                        <v-text-field v-model="form.unit" variant="outlined" density="comfortable" hide-details placeholder="e.g. mAh, W, hours, inch" style="max-width: 260px" />
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Values -->
                            <v-card v-if="isListType" flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-format-list-bulleted</v-icon></div><span class="tt-card-title">Allowed values</span></div>
                                <v-card-text class="tt-body">
                                    <v-combobox v-model="form.options" :items="[]" multiple chips closable-chips variant="outlined" density="comfortable" hide-details placeholder="Type a value and press Enter" />
                                    <div class="tt-sub mt-2">Keep wording identical across products, for example “20000mAh” everywhere, so filters group them together.</div>
                                    <div v-if="form.options.length" class="tt-vals mt-3">
                                        <span v-for="o in form.options" :key="o" class="tt-val"><i v-if="form.type === 'Colour'" class="tt-dot" :style="{ background: colourDot(o) }"></i>{{ o }}</span>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Where it is used -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-sitemap-outline</v-icon></div><span class="tt-card-title">Where it is used</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">Categories that use this attribute</div>
                                    <v-select v-model="form.categories" :items="categories" multiple chips closable-chips variant="outlined" density="comfortable" hide-details placeholder="Select categories" />
                                    <div class="tt-sub mt-2 mb-4">Products in these categories get this field on their edit page.</div>

                                    <div class="tt-option-row">
                                        <div><div>Show on the product page</div><div class="tt-sub">Appears in the specification table</div></div>
                                        <v-checkbox-btn v-model="form.showOnProduct" color="#0f9d6b" />
                                    </div>
                                    <div class="tt-option-row">
                                        <div><div>Use as a filter in the shop</div><div class="tt-sub">Shoppers can narrow category pages by this value</div></div>
                                        <v-checkbox-btn v-model="form.filterable" color="#0f9d6b" :disabled="form.type === 'Text'" />
                                    </div>
                                    <div class="tt-option-row">
                                        <div><div>Use for product variants</div><div class="tt-sub">Lets you create options like Black / 20000mAh</div></div>
                                        <v-checkbox-btn v-model="form.usedForVariants" color="#0f9d6b" :disabled="!isListType" />
                                    </div>
                                    <div class="tt-option-row">
                                        <div><div>Required on every product</div><div class="tt-sub">Products can't be published without it</div></div>
                                        <v-checkbox-btn v-model="form.required" color="#0f9d6b" />
                                    </div>
                                    <div v-if="!isListType" class="tt-sub mt-2">Variant options need a list of values, so pick Select, Multi-select or Colour to turn that on.</div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="4">
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-eye-outline</v-icon></div><span class="tt-card-title">How it will look</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-preview">
                                        <div class="tt-sub mb-2">Product page</div>
                                        <div class="tt-sheet-row static">
                                            <span class="tt-sheet-k">{{ form.name || "Attribute name" }}</span>
                                            <span class="tt-sheet-v">{{ sampleValue(form) }}</span>
                                        </div>

                                        <template v-if="form.filterable">
                                            <div class="tt-sub mt-4 mb-2">Shop filter</div>
                                            <div class="tt-filter-title">{{ form.name || "Attribute name" }}</div>
                                            <div v-if="isListType">
                                                <div v-for="o in (form.options.length ? form.options : ['Value 1', 'Value 2']).slice(0, 4)" :key="o" class="tt-filter-row"><span class="tt-box"></span>{{ o }}</div>
                                            </div>
                                            <div v-else-if="form.type === 'Yes/No'" class="tt-filter-row"><span class="tt-box"></span>Yes</div>
                                            <div v-else class="tt-filter-row"><span class="tt-range"></span>Range{{ form.unit ? " (" + form.unit + ")" : "" }}</div>
                                        </template>

                                        <div v-if="form.usedForVariants" class="mt-4">
                                            <div class="tt-sub mb-2">Variant picker</div>
                                            <div class="d-flex flex-wrap ga-2">
                                                <span v-for="(o, i) in (form.options.length ? form.options : ['Value 1', 'Value 2']).slice(0, 4)" :key="o" class="tt-pick" :class="{ on: i === 0 }"><i v-if="form.type === 'Colour'" class="tt-dot" :style="{ background: colourDot(o) }"></i>{{ o }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-key-outline</v-icon></div><span class="tt-card-title">Internal key</span></div>
                                <v-card-text class="tt-body">
                                    <v-text-field v-model="form.key" variant="outlined" density="comfortable" hide-details placeholder="Made from the name" />
                                    <div class="tt-sub mt-2">Used in links and imports. Leave blank to make it from the name.</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- ═════════════════════════ Detail drawer ═════════════════════════ -->
            <v-navigation-drawer v-model="drawer" location="right" temporary width="420" class="tt-drawer">
                <template v-if="current">
                    <div class="tt-drawer-hero">
                        <div class="d-flex align-start">
                            <span class="tt-type-ic tt-type-ic-lg"><v-icon size="24" color="white">{{ typeIcon(current.type) }}</v-icon></span>
                            <v-spacer />
                            <v-btn icon="mdi-close" size="small" variant="text" color="white" aria-label="Close" @click="drawer = false" />
                        </div>
                        <div class="tt-drawer-name">{{ current.name }}</div>
                        <div class="tt-drawer-sub">{{ current.type }}{{ current.unit ? " in " + current.unit : "" }} · {{ current.group }} · {{ current.key }}</div>
                        <div class="d-flex flex-wrap ga-2 mt-3">
                            <span v-if="current.usedForVariants" class="tt-chip tone-light">Variant option</span>
                            <span v-if="current.filterable" class="tt-chip tone-light">Shop filter</span>
                            <span v-if="current.required" class="tt-chip tone-light">Required</span>
                        </div>
                    </div>

                    <div class="tt-drawer-stats">
                        <div class="tt-stat"><small>Products</small><b>{{ current.products }}</b></div>
                        <div class="tt-stat"><small>Categories</small><b>{{ current.categories.length }}</b></div>
                        <div class="tt-stat"><small>Values</small><b>{{ current.options.length || "Any" }}</b></div>
                    </div>

                    <div class="tt-drawer-body">
                        <div v-if="current.options.length" class="tt-drawer-t">Allowed values</div>
                        <div v-if="current.options.length" class="tt-vals mb-5">
                            <span v-for="o in current.options" :key="o" class="tt-val"><i v-if="current.type === 'Colour'" class="tt-dot" :style="{ background: colourDot(o) }"></i>{{ o }}</span>
                        </div>

                        <div class="tt-drawer-t">Categories</div>
                        <div v-if="current.categories.length" class="d-flex flex-wrap ga-1 mb-5">
                            <span v-for="c in current.categories" :key="c" class="tt-chip tone-teal">{{ c }}</span>
                        </div>
                        <div v-else class="tt-empty mb-5">Not used in any category, so it never appears on a product.</div>

                        <div class="tt-drawer-t">Shown to shoppers</div>
                        <div class="tt-line"><v-icon size="16">mdi-clipboard-text-outline</v-icon><span>{{ current.showOnProduct ? "In the product specification table" : "Hidden from the specification table" }}</span></div>
                        <div class="tt-line"><v-icon size="16">mdi-filter-variant</v-icon><span>{{ current.filterable ? "As a filter on category pages" : "Not offered as a filter" }}</span></div>
                        <div class="tt-line"><v-icon size="16">mdi-source-branch</v-icon><span>{{ current.usedForVariants ? "As a variant picker" : "Not a variant option" }}</span></div>
                    </div>

                    <div class="tt-drawer-foot">
                        <v-btn variant="outlined" class="text-none flex-grow-1" prepend-icon="mdi-delete-outline" color="error" @click="removeFromDrawer">Delete</v-btn>
                        <v-btn variant="flat" class="text-none flex-grow-1 tt-btn-solid" prepend-icon="mdi-pencil-outline" @click="editFromDrawer">Edit attribute</v-btn>
                    </div>
                </template>
            </v-navigation-drawer>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="440">
                <v-card class="tt-dialog">
                    <v-card-title class="text-subtitle-1 font-weight-bold pt-5 px-5">Delete attribute</v-card-title>
                    <v-card-text class="px-5">
                        Delete <b>{{ deletedName }}</b>?
                        <span v-if="deletedProducts">{{ deletedProducts }} products use it. Their values for this attribute will be removed, and any variants built on it will stop working.</span>
                        <span v-else>No products use it yet.</span>
                    </v-card-text>
                    <v-card-actions class="pb-4 px-5">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="cancel">Keep attribute</v-btn>
                        <v-btn color="error" variant="flat" class="text-none" :loading="deleting" @click="agree">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
const CATEGORIES = ["Power banks", "Chargers", "Cables", "Earbuds", "Headphones", "Speakers", "Smart watches", "Gaming", "Networking", "Storage"];
const GROUPS = ["General", "Power", "Connectivity", "Display", "Audio", "Design"];
const LIST_TYPES = ["Select", "Multi-select", "Colour"];

const TYPE_CHOICES = [
    { value: "Select", icon: "mdi-form-dropdown", hint: "Pick one from a list" },
    { value: "Multi-select", icon: "mdi-format-list-checks", hint: "Pick several from a list" },
    { value: "Number", icon: "mdi-numeric", hint: "A number with a unit" },
    { value: "Yes/No", icon: "mdi-toggle-switch-outline", hint: "Has it or doesn't" },
    { value: "Text", icon: "mdi-form-textbox", hint: "Free typing" },
    { value: "Colour", icon: "mdi-palette-outline", hint: "A list of colours" },
];

const COLOUR_MAP = { black: "#1f2937", white: "#f3f4f6", blue: "#2f5be7", red: "#dc2626", silver: "#c0c7cf", gray: "#6b7280", grey: "#6b7280", green: "#0f9d6b", pink: "#ec4899", gold: "#d4a017", purple: "#7c3aed", orange: "#ef6a45", yellow: "#f59e0b", navy: "#1e3a8a" };

const slug = (s) => String(s || "").toLowerCase().trim().replace(/[^a-z0-9]+/g, "_").replace(/^_+|_+$/g, "");

const emptyForm = () => ({
    id: null,
    name: "",
    key: "",
    type: "Select",
    unit: "",
    group: "General",
    options: [],
    categories: [],
    showOnProduct: true,
    filterable: false,
    usedForVariants: false,
    required: false,
    products: 0,
});

/* Sample rows so the page looks right before your API is connected.
   Set to false once /api/attributes returns real data. */
const USE_SAMPLE_DATA_ON_ERROR = true;
const makeSample = () => {
    const a = (id, name, type, group, o) => ({ id, name, key: slug(name), type, group, unit: "", options: [], categories: [], showOnProduct: true, filterable: false, usedForVariants: false, required: false, products: 0, ...o });
    return [
        a(1, "Colour", "Colour", "Design", { options: ["Black", "White", "Blue", "Red", "Silver"], categories: ["Power banks", "Earbuds", "Headphones", "Smart watches", "Cables", "Speakers", "Gaming"], filterable: true, usedForVariants: true, products: 214 }),
        a(2, "Battery capacity", "Select", "Power", { options: ["5000mAh", "10000mAh", "20000mAh", "30000mAh"], categories: ["Power banks"], filterable: true, usedForVariants: true, required: true, products: 58 }),
        a(3, "Storage size", "Select", "General", { options: ["32GB", "64GB", "128GB", "256GB"], categories: ["Storage"], filterable: true, usedForVariants: true, required: true, products: 41 }),
        a(4, "Cable length", "Select", "General", { options: ["0.5m", "1m", "2m", "3m"], categories: ["Cables", "Chargers"], filterable: true, usedForVariants: true, products: 96 }),
        a(5, "Connectivity", "Multi-select", "Connectivity", { options: ["Bluetooth 5.3", "Wi-Fi 6", "USB-C", "Lightning", "3.5mm jack"], categories: ["Earbuds", "Headphones", "Speakers", "Networking", "Smart watches"], filterable: true, products: 187 }),
        a(6, "Output power", "Number", "Power", { unit: "W", categories: ["Power banks", "Chargers"], filterable: true, required: true, products: 112 }),
        a(7, "Battery life", "Number", "Power", { unit: "hours", categories: ["Earbuds", "Headphones", "Smart watches", "Speakers"], products: 143 }),
        a(8, "Screen size", "Number", "Display", { unit: "inch", categories: ["Smart watches"], filterable: true, products: 34 }),
        a(9, "Water resistance", "Select", "Design", { options: ["Not rated", "IPX4", "IPX7", "IP67", "IP68"], categories: ["Earbuds", "Speakers", "Smart watches"], filterable: true, products: 88 }),
        a(10, "Noise cancellation", "Yes/No", "Audio", { categories: ["Earbuds", "Headphones"], filterable: true, products: 52 }),
        a(11, "RGB lighting", "Yes/No", "Design", { categories: ["Gaming"], filterable: true, products: 27 }),
        a(12, "Warranty", "Select", "General", { options: ["No warranty", "6 months", "1 year", "2 years"], categories: ["Power banks", "Chargers", "Earbuds", "Smart watches", "Networking", "Gaming", "Storage"], filterable: true, required: true, products: 301 }),
        a(13, "Compatibility", "Multi-select", "General", { options: ["Android", "iPhone", "Windows", "macOS"], categories: [], products: 0 }),
        a(14, "Material", "Text", "Design", { categories: ["Cables", "Power banks"], products: 19 }),
        a(15, "Port count", "Select", "Connectivity", { options: ["1"], categories: ["Chargers", "Networking"], usedForVariants: false, products: 33 }),
    ];
};

export default {
    name: "AttributesList",

    data() {
        return {
            tab: "list",
            searchValue: "",
            segment: "all",
            groupFilter: "All groups",
            previewCategory: "Power banks",
            loading: false,

            items: [],
            form: emptyForm(),
            saving: false,
            deleting: false,
            deleteDialog: false,
            deletedId: -1,
            deletedName: "",
            deletedProducts: 0,

            drawer: false,
            current: null,

            snackbar: false,
            snackbarText: "",
            snackbarColor: "#0f9d6b",

            categories: CATEGORIES,
            typeChoices: TYPE_CHOICES,

            headers: [
                { text: "Attribute", value: "name", sortable: true, width: 250 },
                { text: "Type", value: "type", sortable: true, width: 120 },
                { text: "Group", value: "group", sortable: true, width: 120 },
                { text: "Values", value: "values", width: 260 },
                { text: "Used for", value: "usage", width: 200 },
                { text: "Categories", value: "catCount", sortable: true, width: 110 },
                { text: "Products", value: "products", sortable: true, width: 100 },
                { text: "Actions", value: "operation", width: 130 },
            ],
        };
    },

    computed: {
        isListType() {
            return LIST_TYPES.includes(this.form.type);
        },
        groupList() {
            return [...new Set([...GROUPS, ...this.items.map((a) => a.group)])];
        },
        groupOptions() {
            return ["All groups", ...this.groupList.filter((g) => this.items.some((a) => a.group === g))];
        },
        rows() {
            return this.items.map((a) => ({ ...a, catCount: a.categories.length }));
        },
        segments() {
            const n = (fn) => this.items.filter(fn).length;
            return [
                { value: "all", label: "All", count: this.items.length },
                { value: "variant", label: "Variant options", count: n((a) => a.usedForVariants) },
                { value: "filter", label: "Shop filters", count: n((a) => a.filterable) },
                { value: "required", label: "Required", count: n((a) => a.required) },
                { value: "unused", label: "Unused", count: n((a) => !a.categories.length) },
            ];
        },
        filteredItems() {
            const q = (this.searchValue || "").trim().toLowerCase();
            return this.rows.filter((a) => {
                if (this.segment === "variant" && !a.usedForVariants) return false;
                if (this.segment === "filter" && !a.filterable) return false;
                if (this.segment === "required" && !a.required) return false;
                if (this.segment === "unused" && a.categories.length) return false;
                if (this.groupFilter !== "All groups" && a.group !== this.groupFilter) return false;
                return !q || (a.name + " " + a.key + " " + a.options.join(" ")).toLowerCase().includes(q);
            });
        },
        heroStats() {
            const n = (fn) => this.items.filter(fn).length;
            const catsCovered = new Set(this.items.flatMap((a) => a.categories)).size;
            return [
                { icon: "mdi-tune-variant", color: "#0f9d6b", value: String(this.items.length), label: "Attributes", note: this.groupList.filter((g) => this.items.some((a) => a.group === g)).length + " spec groups" },
                { icon: "mdi-source-branch", color: "#7c3aed", value: String(n((a) => a.usedForVariants)), label: "Variant options", note: "Used to build variants" },
                { icon: "mdi-filter-variant", color: "#2f5be7", value: String(n((a) => a.filterable)), label: "Shop filters", note: "Shown on category pages" },
                { icon: "mdi-shape-outline", color: "#f59e0b", value: catsCovered + " of " + CATEGORIES.length, label: "Categories covered", note: "Have at least one attribute" },
            ];
        },
        attention() {
            const unused = this.items.filter((a) => !a.categories.length);
            const thin = this.items.filter((a) => LIST_TYPES.includes(a.type) && a.options.length < 2);
            const hidden = this.items.filter((a) => a.filterable && !a.showOnProduct);
            return [
                {
                    icon: "mdi-link-off", color: "#ef6a45", count: unused.length, label: "Show them",
                    title: unused.length + (unused.length === 1 ? " attribute isn't" : " attributes aren't") + " in any category",
                    text: unused.length ? unused[0].name + " never appears on a product." : "Every attribute is in use.",
                    apply: () => { this.segment = "unused"; this.groupFilter = "All groups"; },
                },
                {
                    icon: "mdi-format-list-bulleted-square", color: "#f59e0b", count: thin.length, label: "Add values",
                    title: thin.length + " list " + (thin.length === 1 ? "attribute has" : "attributes have") + " fewer than 2 values",
                    text: "A filter or picker with one choice is no help to shoppers.",
                    apply: () => this.editItem(thin[0]),
                },
                {
                    icon: "mdi-eye-off-outline", color: "#7c3aed", count: hidden.length, label: "Review",
                    title: hidden.length + " filters hidden on product pages",
                    text: "Shoppers can filter by these but never see the value on the product.",
                    apply: () => this.editItem(hidden[0]),
                },
            ];
        },
        sheet() {
            const rows = this.items.filter((a) => a.showOnProduct && a.categories.includes(this.previewCategory));
            return this.groupList
                .map((group) => ({ group, rows: rows.filter((a) => a.group === group) }))
                .filter((g) => g.rows.length);
        },
    },

    watch: {
        "form.name"(v) {
            // keep the key in step with the name until the person edits it by hand
            if (!this.form.id && (!this.form.key || this.form.key === slug(this._prevName))) this.form.key = slug(v);
            this._prevName = v;
        },
    },

    methods: {
        /* ── helpers ── */
        typeIcon(t) {
            return (TYPE_CHOICES.find((x) => x.value === t) || TYPE_CHOICES[0]).icon;
        },
        colourDot(name) {
            return COLOUR_MAP[String(name).trim().toLowerCase()] || "#c8d4d0";
        },
        sampleValue(a) {
            if (a.options && a.options.length) return a.type === "Multi-select" ? a.options.slice(0, 2).join(", ") : a.options[0];
            if (a.type === "Number") return "10" + (a.unit ? " " + a.unit : "");
            if (a.type === "Yes/No") return "Yes";
            return "Example value";
        },
        setType(t) {
            this.form.type = t;
            if (!LIST_TYPES.includes(t)) {
                this.form.options = [];
                this.form.usedForVariants = false;
            }
            if (t !== "Number") this.form.unit = "";
            if (t === "Text") this.form.filterable = false;
        },

        /* ── data ── */
        normalize(a) {
            return {
                ...a,
                key: a.key || slug(a.name),
                type: a.type || "Select",
                unit: a.unit ?? "",
                group: a.group || "General",
                options: Array.isArray(a.options) ? a.options : [],
                categories: Array.isArray(a.categories) ? a.categories : [],
                showOnProduct: Boolean(a.show_on_product ?? a.showOnProduct ?? true),
                filterable: Boolean(a.filterable ?? false),
                usedForVariants: Boolean(a.used_for_variants ?? a.usedForVariants ?? false),
                required: Boolean(a.required ?? false),
                products: Number(a.products_count ?? a.products ?? 0),
            };
        },
        async allItem() {
            this.loading = true;
            try {
                const res = await this.axios.get("/api/attributes");
                if (res.data && res.data.success) this.items = res.data.data.map(this.normalize);
                else if (USE_SAMPLE_DATA_ON_ERROR) this.items = makeSample();
            } catch (error) {
                console.error("Failed to load attributes:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) this.items = makeSample();
                else this.showError("Couldn't load attributes. Check your connection and refresh.");
            } finally {
                this.loading = false;
            }
        },

        /* ── navigation ── */
        openAdd() {
            this.form = emptyForm();
            this._prevName = "";
            this.tab = "add";
        },
        editItem(item) {
            if (!item) return;
            this.form = { ...emptyForm(), ...item, options: [...item.options], categories: [...item.categories] };
            this._prevName = item.name;
            this.tab = "add";
        },
        editFromDrawer() {
            const a = this.current;
            this.drawer = false;
            this.editItem(a);
        },
        removeFromDrawer() {
            const a = this.current;
            this.drawer = false;
            this.removeItem(a);
        },
        closeAdd() {
            this.tab = "list";
            this.form = emptyForm();
        },
        openDetail(item) {
            this.current = this.items.find((a) => a.id === item.id) || item;
            this.drawer = true;
        },

        /* ── save ── */
        validate() {
            const f = this.form;
            if (!f.name || !f.name.trim()) return "Enter an attribute name.";
            if (this.items.some((a) => a.id !== f.id && a.name.trim().toLowerCase() === f.name.trim().toLowerCase())) return "An attribute with this name already exists. Pick a different name.";
            if (LIST_TYPES.includes(f.type) && f.options.length < 1) return "Add at least one allowed value.";
            if (f.usedForVariants && f.options.length < 2) return "A variant option needs at least two values.";
            return null;
        },
        async saveAttribute() {
            const err = this.validate();
            if (err) return this.showError(err);

            this.saving = true;
            const wasEdit = Boolean(this.form.id);
            const payload = { ...this.form, name: this.form.name.trim(), key: slug(this.form.key || this.form.name), group: (this.form.group || "General").trim() };
            try {
                const res = wasEdit ? await this.axios.put(`/api/attributes/${payload.id}`, payload) : await this.axios.post("/api/attributes", payload);
                if (res.data && res.data.success !== false) {
                    this.showSuccess(wasEdit ? "Attribute updated." : "Attribute created.");
                    await this.allItem();
                    this.closeAdd();
                } else {
                    this.showError(res.data?.message || "Couldn't save the attribute. Try again.");
                }
            } catch (error) {
                console.error("Save error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    const row = this.normalize({ ...payload, id: payload.id || Date.now() });
                    this.items = wasEdit ? this.items.map((a) => (a.id === row.id ? row : a)) : [row, ...this.items];
                    this.showSuccess((wasEdit ? "Attribute updated" : "Attribute created") + " (demo data).");
                    this.closeAdd();
                } else {
                    this.showError("Couldn't save the attribute. Check your connection and try again.");
                }
            } finally {
                this.saving = false;
            }
        },

        /* ── delete ── */
        removeItem(item) {
            if (!item) return;
            this.deletedId = item.id;
            this.deletedName = item.name;
            this.deletedProducts = item.products || 0;
            this.deleteDialog = true;
        },
        cancel() {
            this.deleteDialog = false;
            this.deletedId = -1;
            this.deletedName = "";
            this.deletedProducts = 0;
        },
        async agree() {
            this.deleting = true;
            const id = this.deletedId;
            const drop = () => {
                this.items = this.items.filter((a) => a.id !== id);
                if (this.current && this.current.id === id) this.drawer = false;
            };
            try {
                const res = await this.axios.delete(`/api/attributes/${id}`);
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                drop();
                this.showSuccess("Attribute deleted.");
            } catch (error) {
                console.error("Delete error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    drop();
                    this.showSuccess("Attribute deleted (demo data).");
                } else this.showError("Couldn't delete the attribute. Try again.");
            } finally {
                this.deleting = false;
                this.cancel();
            }
        },

        /* ── export ── */
        exportCsv() {
            const rows = this.filteredItems;
            if (!rows.length) return this.showError("There are no attributes to export.");
            const head = ["Name", "Key", "Type", "Unit", "Group", "Values", "Categories", "Variant option", "Shop filter", "Required", "Products"];
            const esc = (v) => '"' + String(v ?? "").replace(/"/g, '""') + '"';
            const lines = rows.map((a) => [a.name, a.key, a.type, a.unit, a.group, a.options.join(" | "), a.categories.join(" | "), a.usedForVariants ? "Yes" : "No", a.filterable ? "Yes" : "No", a.required ? "Yes" : "No", a.products].map(esc).join(","));
            const blob = new Blob(["\ufeff" + [head.map(esc).join(","), ...lines].join("\n")], { type: "text/csv;charset=utf-8" });
            const el = document.createElement("a");
            el.href = URL.createObjectURL(blob);
            el.download = "attributes.csv";
            el.click();
            URL.revokeObjectURL(el.href);
            this.showSuccess("Exported " + rows.length + " attributes.");
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
        document.title = "Attributes and specifications";
        this.allItem();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-attr {
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
.tt-key { font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 11.5px; }
.tt-field-label { font-size: 12.5px; font-weight: 600; color: #3a4a47; margin-bottom: 8px; }
.tt-required { color: #dc2626; }
.tt-search { max-width: 280px; min-width: 200px; }
.tt-filter { max-width: 220px; min-width: 160px; }
.tt-search :deep(.v-field), .tt-filter :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-empty { font-size: 13px; color: var(--muted); background: var(--bg2); border: 1px dashed #c8d4d0; border-radius: 10px; padding: 14px; }

/* ───────── Spec sheet ───────── */
.tt-sheet-group { margin-bottom: 14px; }
.tt-sheet-group:last-child { margin-bottom: 0; }
.tt-sheet-t { font-size: 12.5px; font-weight: 700; color: #0a5548; padding: 6px 12px; background: var(--tint); border-radius: 8px 8px 0 0; }
.tt-sheet-row { display: flex; gap: 12px; padding: 9px 12px; font-size: 13px; border: 1px solid var(--line); border-top: 0; cursor: pointer; transition: background 0.15s; }
.tt-sheet-row:hover { background: var(--bg2); }
.tt-sheet-row.static { border-top: 1px solid var(--line); border-radius: 8px; cursor: default; }
.tt-sheet-row.static:hover { background: transparent; }
.tt-sheet-group .tt-sheet-row:last-child { border-radius: 0 0 8px 8px; }
.tt-sheet-k { flex: 0 0 42%; color: var(--muted); }
.tt-sheet-v { font-weight: 600; min-width: 0; overflow: hidden; text-overflow: ellipsis; }

/* ───────── Attention ───────── */
.tt-attn { --c: #ef6a45; display: flex; align-items: center; gap: 14px; padding: 12px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); margin-bottom: 10px; }
.tt-attn:last-child { margin-bottom: 0; }
.tt-attn-ic { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; background: var(--c); flex: none; box-shadow: 0 8px 14px -8px var(--c); }
.tt-attn-btn { border-color: #0a5548 !important; color: #0a5548 !important; font-weight: 600; flex: none; }
@media (max-width: 599px) { .tt-attn { flex-wrap: wrap; } .tt-attn-btn { width: 100%; } }

/* ───────── Segments ───────── */
.tt-segments { display: flex; gap: 4px; padding: 6px 14px 10px; overflow-x: auto; scrollbar-width: none; border-bottom: 1px solid var(--line); }
.tt-segments::-webkit-scrollbar { display: none; }
.tt-segment { flex: none; display: inline-flex; align-items: center; gap: 8px; border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: var(--muted); padding: 7px 14px; border-radius: 11px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-segment:hover { color: var(--ink); background: #eaf2ef; }
.tt-segment.on { background: #0a5548; color: #fff; font-weight: 600; }
.tt-segment:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-segment-count { font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 10px; background: #e3eae8; color: #3a4a47; }
.tt-segment.on .tt-segment-count { background: rgba(255, 255, 255, 0.22); color: #fff; }

/* ───────── Table cells ───────── */
.tt-person { display: flex; align-items: center; gap: 12px; padding: 6px 0; cursor: pointer; min-width: 0; }
.tt-person:hover .tt-name { color: #0a5548; text-decoration: underline; text-underline-offset: 3px; }
.tt-person-text { min-width: 0; }
.tt-type-ic { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-type-ic-lg { width: 50px; height: 50px; border-radius: 14px; background: rgba(255, 255, 255, 0.16); border: 1px solid rgba(255, 255, 255, 0.28); }
.tt-actions { display: flex; gap: 2px; }
.tt-vals { display: flex; flex-wrap: wrap; gap: 5px; }
.tt-val { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 500; padding: 2px 9px; border-radius: 8px; background: var(--bg2); border: 1px solid var(--line); color: #3a4a47; white-space: nowrap; }
.tt-val.more { color: var(--muted); }
.tt-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(15, 42, 38, 0.18); }

.tt-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; white-space: nowrap; }
.tone-blue { background: #e6edff; color: #1f4e9c; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-red { background: #fce1d8; color: #b4381a; }
.tone-violet { background: #ebe5ff; color: #5b3fd1; }
.tone-teal { background: #d3f1f2; color: #0b6b72; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.tone-light { background: rgba(255, 255, 255, 0.16); color: #fff; border: 1px solid rgba(255, 255, 255, 0.28); }

/* ───────── Form ───────── */
.tt-type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
@media (max-width: 959px) { .tt-type-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-type-grid { grid-template-columns: 1fr; } }
.tt-radio-box { display: flex; align-items: center; gap: 12px; font-size: 13.5px; border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; background: var(--bg2); cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tt-radio-box:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-radio-box.is-selected { border-color: #0f9d6b; background: #eaf8f1; font-weight: 600; }
.tt-option-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; font-size: 13.5px; border-bottom: 1px solid var(--line); }
.tt-option-row:last-of-type { border-bottom: 0; }
.tt-preview { border: 1px solid var(--line); border-radius: 12px; padding: 14px; background: var(--bg2); }
.tt-filter-title { font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.tt-filter-row { display: flex; align-items: center; gap: 8px; font-size: 12.5px; padding: 3px 0; }
.tt-box { width: 14px; height: 14px; border-radius: 4px; border: 1.5px solid #b9c6c2; background: #fff; flex: none; }
.tt-range { width: 100%; max-width: 160px; height: 4px; border-radius: 4px; background: #0f9d6b; opacity: 0.5; }
.tt-pick { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 5px 12px; border-radius: 20px; border: 1px solid var(--line); background: #fff; }
.tt-pick.on { border-color: #0f9d6b; background: #eaf8f1; font-weight: 600; }

/* ───────── Drawer ───────── */
.tt-drawer { font-family: Poppins, "Segoe UI", sans-serif; }
.tt-drawer-hero { color: #fff; padding: 18px 20px 20px; background: radial-gradient(420px 200px at 100% 0%, rgba(110, 231, 183, 0.22), transparent 62%), linear-gradient(135deg, #053d35 0%, #0a5548 60%, #0b6150 100%); }
.tt-drawer-name { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; margin-top: 12px; }
.tt-drawer-sub { font-size: 12.5px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-drawer-stats { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid var(--line); background: #f4faf7; }
.tt-stat { padding: 12px 14px; border-left: 1px solid var(--line); min-width: 0; }
.tt-stat:first-child { border-left: 0; }
.tt-stat small { display: block; font-size: 11px; color: var(--muted); }
.tt-stat b { display: block; font-size: 14px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-drawer-body { padding: 18px 20px 96px; }
.tt-drawer-t { font-size: 12.5px; font-weight: 600; margin-bottom: 8px; }
.tt-line { display: flex; align-items: center; gap: 10px; font-size: 13px; padding: 5px 0; }
.tt-line .v-icon { color: var(--accent); }
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
    --easy-table-body-row-height: 62px;
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
@media (prefers-reduced-motion: reduce) { .tt-segment, .tt-sheet-row, .tt-radio-box { transition: none; } }
</style>