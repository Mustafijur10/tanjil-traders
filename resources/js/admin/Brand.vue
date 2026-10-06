<template>
    <v-slide-x-transition appear>
        <div class="tt-brand">
            <v-window v-model="tab">
                <!-- ═════════════════════════ Brand list ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">Brands</h1>
                            <div class="tt-sub">{{ items.length }} brands · {{ activeCount }} active · {{ featuredCount }} featured</div>
                        </div>
                        <v-spacer />
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-add-btn" prepend-icon="mdi-plus" @click="openAdd">
                            Add Brand
                        </v-btn>
                    </div>

                    <v-card flat class="tt-card mt-4">
                        <div class="tt-head tt-head-wrap">
                            <span class="tt-card-title">All Brands</span>
                            <v-spacer />
                            <v-select
                                v-model="statusFilter"
                                :items="['All', 'Active', 'Draft', 'Inactive']"
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="max-width: 140px"
                                class="mr-2"
                            />
                            <v-text-field
                                v-model="searchValue"
                                placeholder="Search brands…"
                                prepend-inner-icon="mdi-magnify"
                                clearable
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="max-width: 220px"
                                @click:clear="searchValue = ''"
                            />
                        </div>

                        <v-card-text class="tt-body">
                            <EasyDataTable
                                :headers="headers"
                                :items="filteredItems"
                                table-class-name="customize-table"
                                buttons-pagination
                                :rows-per-page="25"
                                :fixedIndex="true"
                                :search-value="searchValue"
                                :loading="loading"
                            >
                                <template #item-logo="item">
                                    <v-avatar rounded="lg" size="38" class="my-1" color="grey-lighten-3">
                                        <v-img v-if="item.logo" :src="item.logo" cover />
                                        <v-icon v-else icon="mdi-image-off-outline" size="16" color="grey" />
                                    </v-avatar>
                                </template>

                                <template #item-title="item">
                                    <v-list-item class="tt-name-item" density="compact" min-height="40" @click="editItem(item)">
                                        <template #prepend>
                                            <v-icon size="14" class="tt-name-caret" icon="mdi-chevron-right" />
                                        </template>
                                        <v-list-item-title class="tt-name">{{ item.title }}</v-list-item-title>
                                        <v-list-item-subtitle class="text-caption">{{ item.tagline || "Brand" }}</v-list-item-subtitle>
                                    </v-list-item>
                                </template>

                                <template #item-country="item">
                                    <span v-if="item.country">{{ item.country }}</span>
                                    <span v-else class="tt-muted">—</span>
                                </template>

                                <template #item-warranty="item">
                                    <span v-if="item.warrantyMonths || item.warranty_months">{{ item.warrantyMonths || item.warranty_months }} months</span>
                                    <span v-else class="tt-muted">—</span>
                                </template>

                                <template #item-featured="item">
                                    <v-icon v-if="item.featureHomepage || item.feature_homepage" size="16" color="#0f9d6b">mdi-check-all</v-icon>
                                    <span v-else class="tt-muted">—</span>
                                </template>

                                <template #item-status="item">
                                    <v-chip size="small" :color="statusColor(item.status)" variant="tonal" label>{{ item.status || "Active" }}</v-chip>
                                </template>

                                <template #item-operation="item">
                                    <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" class="mr-1" @click.prevent="editItem(item)" />
                                    <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="removeOption(item)" />
                                </template>
                            </EasyDataTable>
                        </v-card-text>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ Add / Edit brand (embedded) ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">{{ form.id ? "Edit brand" : "Add brand" }}</h1>
                            <div class="tt-sub">
                                {{ form.id ? "Update the details for \"" + form.title + "\"" : "Create a new electronics brand for your catalog" }}
                            </div>
                        </div>
                        <v-spacer />

                        <v-btn variant="outlined" class="text-none tt-ghost-btn mr-2" prepend-icon="mdi-arrow-left" @click="closeAdd">
                            Back
                        </v-btn>
                        <v-btn variant="outlined" class="text-none mr-2" prepend-icon="mdi-content-save-outline" :loading="savingDraft" @click="saveBrand('Draft')">
                            Save as Draft
                        </v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-submit-btn" prepend-icon="mdi-check" :loading="saving" @click="saveBrand(form.status)">
                            {{ form.id ? "Save changes" : "Create Brand" }}
                        </v-btn>
                    </div>

                    <v-row dense class="mt-1">
                        <v-col cols="12" md="8">
                            <!-- Basic info -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Basic Info</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Brand name <span class="tt-required">*</span></div>
                                            <v-text-field v-model="form.title" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Samsung" autofocus @update:model-value="autoHandle" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">URL handle</div>
                                            <v-text-field v-model="form.urlHandle" variant="outlined" density="comfortable" hide-details class="mb-4" prefix="/brands/" placeholder="samsung" @update:model-value="handleTouched = true" />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-field-label">Tagline</div>
                                    <v-text-field v-model="form.tagline" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Do what you can't" />

                                    <div class="tt-field-label">Description</div>
                                    <v-textarea v-model="form.description" variant="outlined" density="comfortable" rows="4" hide-details class="mb-4" placeholder="A short story about the brand, shown on the brand landing page…" />

                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Brand tier</div>
                                            <v-select v-model="form.tier" :items="tierOptions" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Sort order</div>
                                            <v-text-field v-model.number="form.sortOrder" type="number" min="0" variant="outlined" density="comfortable" hide-details placeholder="0" />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Logo & media -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Logo &amp; Media</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Logo</div>
                                            <div class="d-flex align-center ga-3">
                                                <v-avatar rounded="lg" size="64" color="grey-lighten-3">
                                                    <v-img v-if="logoPreview" :src="logoPreview" cover />
                                                    <v-icon v-else icon="mdi-image-off-outline" size="22" color="grey" />
                                                </v-avatar>
                                                <v-file-input
                                                    v-model="logoFile"
                                                    label="Upload logo"
                                                    accept="image/*"
                                                    variant="outlined"
                                                    density="compact"
                                                    prepend-icon=""
                                                    prepend-inner-icon="mdi-tray-arrow-up"
                                                    hide-details
                                                    @update:model-value="(f) => onFileSelected(f, 'logo')"
                                                />
                                            </div>
                                            <div class="tt-sub mt-2">Square PNG or SVG, at least 400 × 400 px, transparent background works best.</div>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Banner image</div>
                                            <div class="tt-banner-preview mb-2">
                                                <v-img v-if="bannerPreview" :src="bannerPreview" cover height="64" />
                                                <v-icon v-else icon="mdi-panorama-variant-outline" size="22" color="grey" />
                                            </div>
                                            <v-file-input
                                                v-model="bannerFile"
                                                label="Upload banner"
                                                accept="image/*"
                                                variant="outlined"
                                                density="compact"
                                                prepend-icon=""
                                                prepend-inner-icon="mdi-tray-arrow-up"
                                                hide-details
                                                @update:model-value="(f) => onFileSelected(f, 'banner')"
                                            />
                                            <div class="tt-sub mt-2">Wide image, around 1600 × 400 px, shown on the brand page header.</div>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Company details -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Company Details</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Country of origin</div>
                                            <v-autocomplete v-model="form.country" :items="countries" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="Select country" clearable />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Headquarters</div>
                                            <v-text-field v-model="form.headquarters" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Suwon, South Korea" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Founded year</div>
                                            <v-text-field v-model.number="form.foundedYear" type="number" min="1800" :max="currentYear" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. 1969" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Official website</div>
                                            <v-text-field v-model="form.website" variant="outlined" density="comfortable" hide-details class="mb-4" prepend-inner-icon="mdi-web" placeholder="https://www.samsung.com" />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-field-label">Product categories</div>
                                    <v-autocomplete
                                        v-model="form.categoryIds"
                                        :items="categoryOptions"
                                        item-title="title"
                                        item-value="id"
                                        multiple
                                        chips
                                        closable-chips
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details
                                        placeholder="e.g. Smartphones, Laptops, Televisions"
                                    />
                                    <div class="tt-sub mt-2">Categories this brand sells in. Used for brand filters on category pages.</div>
                                </v-card-text>
                            </v-card>

                            <!-- Warranty & service -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Warranty &amp; Service</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Default warranty (months)</div>
                                            <v-text-field v-model.number="form.warrantyMonths" type="number" min="0" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="12" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Warranty type</div>
                                            <v-select v-model="form.warrantyType" :items="warrantyTypes" variant="outlined" density="comfortable" hide-details class="mb-4" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Import / supply type</div>
                                            <v-select v-model="form.supplyType" :items="supplyTypes" variant="outlined" density="comfortable" hide-details class="mb-4" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Return window (days)</div>
                                            <v-text-field v-model.number="form.returnDays" type="number" min="0" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="7" />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-field-label">Warranty terms</div>
                                    <v-textarea v-model="form.warrantyTerms" variant="outlined" density="comfortable" rows="3" hide-details class="mb-4" placeholder="What is covered, what is not, and how to claim…" />

                                    <div class="tt-field-label">Service center locations</div>
                                    <v-textarea v-model="form.serviceCenters" variant="outlined" density="comfortable" rows="3" hide-details placeholder="One per line, e.g. Dhaka – Bashundhara City, Level 5" />
                                </v-card-text>
                            </v-card>

                            <!-- Support & compliance -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Support &amp; Compliance</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Support email</div>
                                            <v-text-field v-model="form.supportEmail" type="email" variant="outlined" density="comfortable" hide-details class="mb-4" prepend-inner-icon="mdi-email-outline" placeholder="support@brand.com" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Support phone</div>
                                            <v-text-field v-model="form.supportPhone" variant="outlined" density="comfortable" hide-details class="mb-4" prepend-inner-icon="mdi-phone-outline" placeholder="+880 1XXX-XXXXXX" />
                                        </v-col>
                                        <v-col cols="12">
                                            <div class="tt-field-label">Support / warranty-check URL</div>
                                            <v-text-field v-model="form.supportUrl" variant="outlined" density="comfortable" hide-details class="mb-4" prepend-inner-icon="mdi-lifebuoy" placeholder="https://support.brand.com/warranty" />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-field-label">Certifications</div>
                                    <v-select
                                        v-model="form.certifications"
                                        :items="certificationOptions"
                                        multiple
                                        chips
                                        closable-chips
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details
                                        placeholder="Select certifications"
                                    />
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="4">
                            <div>
                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Status</span></div>
                                    <v-card-text class="tt-body">
                                        <div v-for="opt in statusOptions" :key="opt.value" class="tt-radio-box" :class="{ 'is-selected': form.status === opt.value }" @click="form.status = opt.value">
                                            <span class="tt-radio-dot" :class="{ 'is-on': form.status === opt.value }"></span>{{ opt.label }}
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Options</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-option-row"><span>Authorized / official store</span><v-checkbox-btn v-model="form.isOfficial" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Feature on homepage</span><v-checkbox-btn v-model="form.featureHomepage" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Mark as popular brand</span><v-checkbox-btn v-model="form.isPopular" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Show in navigation menu</span><v-checkbox-btn v-model="form.showInNav" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Show in product filters</span><v-checkbox-btn v-model="form.showInFilters" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Allow product reviews</span><v-checkbox-btn v-model="form.allowReviews" color="#0f9d6b" /></div>
                                    </v-card-text>
                                </v-card>

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Brand Preview</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-preview">
                                            <div class="tt-preview-banner">
                                                <v-img v-if="bannerPreview" :src="bannerPreview" cover height="72" />
                                            </div>
                                            <div class="tt-preview-main">
                                                <v-avatar rounded="lg" size="56" color="grey-lighten-3" class="tt-preview-logo">
                                                    <v-img v-if="logoPreview" :src="logoPreview" cover />
                                                    <v-icon v-else icon="mdi-image-off-outline" size="20" color="grey" />
                                                </v-avatar>
                                                <div class="font-weight-bold mt-2">{{ form.title || "Brand name" }}</div>
                                                <div class="tt-sub">{{ form.tagline || "Your tagline appears here" }}</div>
                                                <div class="d-flex flex-wrap ga-1 mt-3">
                                                    <v-chip size="x-small" variant="tonal" label>{{ form.tier }}</v-chip>
                                                    <v-chip v-if="form.country" size="x-small" variant="tonal" label>{{ form.country }}</v-chip>
                                                    <v-chip v-if="form.warrantyMonths" size="x-small" variant="tonal" color="success" label>{{ form.warrantyMonths }} mo warranty</v-chip>
                                                    <v-chip v-if="form.isOfficial" size="x-small" variant="tonal" color="primary" label>Official store</v-chip>
                                                </div>
                                                <div class="tt-sub mt-3">brandstore.com/brands/{{ form.urlHandle || "your-handle" }}</div>
                                            </div>
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head">
                                        <span class="tt-dot"></span><span class="tt-card-title">Profile Completeness</span>
                                        <v-spacer />
                                        <span class="tt-sub font-weight-bold">{{ completeness.percent }}%</span>
                                    </div>
                                    <v-card-text class="tt-body">
                                        <v-progress-linear :model-value="completeness.percent" color="#0f9d6b" bg-color="#eef0ee" height="6" rounded class="mb-3" />
                                        <div v-for="c in completeness.checks" :key="c.label" class="tt-check-row" :class="{ 'is-done': c.done }">
                                            <v-icon size="16" :color="c.done ? '#0f9d6b' : '#d8cfc7'">{{ c.done ? "mdi-check-circle" : "mdi-circle-outline" }}</v-icon>
                                            <span>{{ c.label }}</span>
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Social Links</span></div>
                                    <v-card-text class="tt-body">
                                        <v-row dense>
                                            <v-col v-for="s in socialFields" :key="s.key" cols="12">
                                                <div class="tt-field-label">{{ s.label }}</div>
                                                <v-text-field v-model="form.social[s.key]" variant="outlined" density="comfortable" hide-details class="mb-3" :prepend-inner-icon="s.icon" :placeholder="s.placeholder" />
                                            </v-col>
                                        </v-row>
                                    </v-card-text>
                                </v-card>

                                <v-alert variant="tonal" color="#0f9d6b" class="tt-ai-alert mb-5" icon="mdi-creation">
                                    <div class="font-weight-medium mb-1">AI Suggestion</div>
                                    <div class="tt-sub">{{ aiSuggestion }}</div>
                                </v-alert>
                            </div>
                        </v-col>
                    </v-row>

                    <!-- Full-width search appearance -->
                    <v-card flat class="tt-card mb-5">
                        <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Search appearance</span></div>
                        <v-card-text class="tt-body">
                            <div class="tt-serp">
                                <div class="tt-serp-label">Search result preview</div>
                                <div class="tt-serp-title">{{ serpTitle }}</div>
                                <div class="tt-serp-url">tanjiltraders.com/brands/{{ form.urlHandle || "brand" }}</div>
                                <div class="tt-serp-desc">{{ serpDescription }}</div>
                            </div>

                            <v-row dense class="mt-3">
                                <v-col cols="12" md="6">
                                    <div class="tt-field-label">Meta title</div>
                                    <v-text-field v-model="form.metaTitle" variant="outlined" density="comfortable" hide-details="auto" class="mb-1" :placeholder="form.title || 'Meta title'" />
                                    <div class="tt-sub mb-4" :class="{ 'tt-warn': (form.metaTitle || '').length > 60 }">{{ (form.metaTitle || "").length }} / 60 characters</div>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <div class="tt-field-label">Keywords</div>
                                    <v-combobox v-model="form.keywords" multiple chips closable-chips variant="outlined" density="comfortable" hide-details placeholder="Type a keyword and press Enter" />
                                </v-col>
                                <v-col cols="12">
                                    <div class="tt-field-label">Meta description</div>
                                    <v-textarea v-model="form.metaDescription" variant="outlined" density="comfortable" rows="3" hide-details class="mb-1" placeholder="Meta description" />
                                    <div class="tt-sub" :class="{ 'tt-warn': (form.metaDescription || '').length > 160 }">{{ (form.metaDescription || "").length }} / 160 characters</div>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-window-item>
            </v-window>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="400">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Delete brand</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="pa-4">Are you sure you want to delete this brand? This can't be undone.</v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" @click="cancel">Cancel</v-btn>
                        <v-btn color="error" variant="flat" :loading="deleting" @click="agree">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
const slugify = (s) =>
    (s || "")
        .toLowerCase()
        .trim()
        .replace(/&/g, "and")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");

const emptyForm = () => ({
    id: null,
    title: "",
    urlHandle: "",
    tagline: "",
    description: "",
    tier: "Premium",
    sortOrder: 0,

    logo: null,
    banner: null,

    country: null,
    headquarters: "",
    foundedYear: null,
    website: "",
    categoryIds: [],

    warrantyMonths: 12,
    warrantyType: "Official brand warranty",
    supplyType: "Official distributor",
    returnDays: 7,
    warrantyTerms: "",
    serviceCenters: "",

    supportEmail: "",
    supportPhone: "",
    supportUrl: "",
    certifications: [],

    social: { facebook: "", instagram: "", youtube: "", x: "", linkedin: "", tiktok: "" },

    metaTitle: "",
    metaDescription: "",
    keywords: [],

    status: "Active",
    isOfficial: false,
    featureHomepage: false,
    isPopular: false,
    showInNav: true,
    showInFilters: true,
    allowReviews: true,
});

export default {
    name: "BrandList",

    data() {
        return {
            tab: "list",
            searchValue: "",
            statusFilter: "All",
            loading: false,

            items: [],
            categoryOptions: [],

            form: emptyForm(),
            logoFile: null,
            logoPreview: null,
            bannerFile: null,
            bannerPreview: null,
            handleTouched: false,

            saving: false,
            savingDraft: false,
            deleting: false,

            deleteDialog: false,
            deletedId: -1,

            snackbar: false,
            snackbarText: "",
            snackbarColor: "#0f9d6b",

            currentYear: new Date().getFullYear(),

            headers: [
                { text: "Logo", value: "logo", sortable: false, width: 70 },
                { text: "Title", value: "title", sortable: true, width: 300 },
                { text: "Country", value: "country", sortable: true, width: 140 },
                { text: "Warranty", value: "warrantyMonths", sortable: true, width: 110 },
                { text: "Featured", value: "featured", width: 90 },
                { text: "Status", value: "status", sortable: true, width: 110 },
                { text: "Operation", value: "operation", width: 90 },
            ],

            tierOptions: ["Premium", "Mid-range", "Budget", "Gaming", "Enterprise", "Accessories"],
            warrantyTypes: ["Official brand warranty", "Seller warranty", "Importer warranty", "International warranty", "No warranty"],
            supplyTypes: ["Official distributor", "Authorized reseller", "Parallel import", "Local manufacturer"],
            certificationOptions: ["CE", "FCC", "RoHS", "BIS", "BSTI", "ISO 9001", "ISO 14001", "Energy Star", "UL", "TÜV"],
            countries: [
                "Bangladesh", "China", "India", "Japan", "South Korea", "Taiwan", "Singapore", "Malaysia", "Thailand",
                "United States", "Canada", "United Kingdom", "Germany", "France", "Netherlands", "Finland", "Sweden", "Italy", "Switzerland", "United Arab Emirates",
            ],
            socialFields: [
                { key: "facebook", label: "Facebook", icon: "mdi-facebook", placeholder: "https://facebook.com/brand" },
                { key: "instagram", label: "Instagram", icon: "mdi-instagram", placeholder: "https://instagram.com/brand" },
                { key: "youtube", label: "YouTube", icon: "mdi-youtube", placeholder: "https://youtube.com/@brand" },
                { key: "x", label: "X (Twitter)", icon: "mdi-twitter", placeholder: "https://x.com/brand" },
                { key: "linkedin", label: "LinkedIn", icon: "mdi-linkedin", placeholder: "https://linkedin.com/company/brand" },
                { key: "tiktok", label: "TikTok", icon: "mdi-music-note", placeholder: "https://tiktok.com/@brand" },
            ],
            statusOptions: [
                { label: "Active", value: "Active" },
                { label: "Draft", value: "Draft" },
                { label: "Inactive", value: "Inactive" },
            ],
        };
    },

    computed: {
        filteredItems() {
            return this.statusFilter === "All" ? this.items : this.items.filter((b) => (b.status || "Active") === this.statusFilter);
        },
        activeCount() {
            return this.items.filter((b) => (b.status || "Active") === "Active").length;
        },
        featuredCount() {
            return this.items.filter((b) => b.featureHomepage || b.feature_homepage).length;
        },
        serpTitle() {
            return (this.form.metaTitle || this.form.title || "Brand title").trim();
        },
        serpDescription() {
            const d = (this.form.metaDescription || "").trim();
            if (!d) return "Add a description to see how this looks in search results.";
            return d.length > 160 ? d.slice(0, 157) + "…" : d;
        },
        completeness() {
            const f = this.form;
            const checks = [
                { label: "Brand name and URL handle", done: !!(f.title && f.urlHandle) },
                { label: "Logo uploaded", done: !!(f.logo || this.logoFile) },
                { label: "Banner uploaded", done: !!(f.banner || this.bannerFile) },
                { label: "Description (40+ words)", done: (f.description || "").trim().split(/\s+/).filter(Boolean).length >= 40 },
                { label: "Country and website", done: !!(f.country && f.website) },
                { label: "Product categories linked", done: (f.categoryIds || []).length > 0 },
                { label: "Warranty details", done: !!(f.warrantyMonths && f.warrantyTerms) },
                { label: "Support contact", done: !!(f.supportEmail || f.supportPhone) },
                { label: "SEO title and description", done: !!(f.metaTitle && f.metaDescription) },
            ];
            const done = checks.filter((c) => c.done).length;
            return { checks, percent: Math.round((done / checks.length) * 100) };
        },
        aiSuggestion() {
            const words = (this.form.description || "").trim() ? this.form.description.trim().split(/\s+/).length : 0;
            if (!this.form.logo && !this.logoFile) return "Add a logo — brands with a logo get noticeably more clicks in filters and menus.";
            return words >= 40
                ? "This brand is set up well — a full description helps search ranking."
                : "Brand pages with a description over 40 words see better search ranking.";
        },
    },

    methods: {
        statusColor(s) {
            return { Active: "success", Draft: "default", Inactive: "error" }[s || "Active"] || "default";
        },

        async allItem() {
            this.loading = true;
            try {
                let res;
                try {
                    res = await this.axios.get("/api/brands");
                } catch {
                    res = await this.axios.get("/api/attribute/brand");
                }

                if (res.data && res.data.success) {
                    this.items = res.data.data.map((item) => ({
                        ...item,
                        urlHandle: item.slug || item.urlHandle || "",
                        sortOrder: item.sort_order ?? item.sortOrder ?? 0,
                        warrantyMonths: item.warranty_months ?? item.warrantyMonths ?? 12,
                        featureHomepage: Boolean(item.feature_homepage ?? item.featureHomepage),
                        isOfficial: Boolean(item.is_official ?? item.isOfficial),
                        isPopular: Boolean(item.is_popular ?? item.isPopular),
                        showInNav: item.show_in_nav !== undefined ? Boolean(item.show_in_nav) : (item.showInNav ?? true),
                        showInFilters: item.show_in_filters !== undefined ? Boolean(item.show_in_filters) : (item.showInFilters ?? true),
                        allowReviews: item.allow_reviews !== undefined ? Boolean(item.allow_reviews) : (item.allowReviews ?? true),
                        categoryIds: Array.isArray(item.category_ids) ? item.category_ids : (item.categoryIds || []),
                    }));
                }
            } catch (error) {
                console.error("Failed to load brands:", error);
                this.showError("Failed to load brands.");
            } finally {
                this.loading = false;
            }
        },

        async loadCategories() {
            try {
                let res;
                try {
                    res = await this.axios.get("/api/categories?parent_id=0");
                } catch {
                    res = await this.axios.get("/api/attribute/category");
                }

                if (res.data && res.data.success) {
                    this.categoryOptions = res.data.data.map((c) => ({ id: c.id, title: c.title }));
                }
            } catch (error) {
                console.error("Failed to load categories:", error);
            }
        },

        autoHandle(val) {
            if (!this.handleTouched && !this.form.id) this.form.urlHandle = slugify(val);
        },

        openAdd() {
            this.form = emptyForm();
            this.resetMedia();
            this.handleTouched = false;
            this.tab = "add";
        },

        editItem(item) {
            const base = emptyForm();
            this.form = {
                ...base,
                ...item,
                urlHandle: item.slug || item.urlHandle || "",
                sortOrder: item.sort_order ?? item.sortOrder ?? 0,
                warrantyMonths: item.warranty_months ?? item.warrantyMonths ?? 12,
                warrantyType: item.warranty_type ?? item.warrantyType ?? "Official brand warranty",
                supplyType: item.supply_type ?? item.supplyType ?? "Official distributor",
                returnDays: item.return_days ?? item.returnDays ?? 7,
                warrantyTerms: item.warranty_terms ?? item.warrantyTerms ?? "",
                serviceCenters: item.service_centers ?? item.serviceCenters ?? "",
                supportEmail: item.support_email ?? item.supportEmail ?? "",
                supportPhone: item.support_phone ?? item.supportPhone ?? "",
                supportUrl: item.support_url ?? item.supportUrl ?? "",
                metaTitle: item.meta_title ?? item.metaTitle ?? "",
                metaDescription: item.meta_description ?? item.metaDescription ?? "",
                isOfficial: Boolean(item.is_official ?? item.isOfficial),
                featureHomepage: Boolean(item.feature_homepage ?? item.featureHomepage),
                isPopular: Boolean(item.is_popular ?? item.isPopular),
                showInNav: item.show_in_nav !== undefined ? Boolean(item.show_in_nav) : (item.showInNav ?? true),
                showInFilters: item.show_in_filters !== undefined ? Boolean(item.show_in_filters) : (item.showInFilters ?? true),
                allowReviews: item.allow_reviews !== undefined ? Boolean(item.allow_reviews) : (item.allowReviews ?? true),
                social: { ...base.social, ...(item.social || {}) },
                categoryIds: Array.isArray(item.category_ids) ? item.category_ids : (item.categoryIds || []),
                certifications: Array.isArray(item.certifications) ? item.certifications : [],
                keywords: Array.isArray(item.keywords) ? item.keywords : [],
                status: item.status || "Active",
            };
            this.resetMedia();
            this.logoPreview = item.logo || null;
            this.bannerPreview = item.banner || null;
            this.handleTouched = true;
            this.tab = "add";
        },

        closeAdd() {
            this.tab = "list";
            this.form = emptyForm();
            this.resetMedia();
        },

        resetMedia() {
            this.logoFile = null;
            this.logoPreview = null;
            this.bannerFile = null;
            this.bannerPreview = null;
        },

        onFileSelected(file, kind) {
            const f = Array.isArray(file) ? file[0] : file;
            const fallback = this.form[kind] || null;
            const url = f ? URL.createObjectURL(f) : fallback;
            if (kind === "logo") this.logoPreview = url;
            else this.bannerPreview = url;
        },

        buildPayload(status) {
            const f = this.form;
            const form = new FormData();
            if (f.id) form.append("id", f.id);
            form.append("title", f.title);
            form.append("urlHandle", f.urlHandle || "");
            form.append("status", status || "Active");

            const scalars = [
                "tagline", "description", "tier", "sortOrder", "country", "headquarters",
                "foundedYear", "website", "warrantyMonths", "warrantyType", "supplyType", "returnDays",
                "warrantyTerms", "serviceCenters", "supportEmail", "supportPhone", "supportUrl",
                "metaTitle", "metaDescription",
            ];
            scalars.forEach((k) => form.append(k, f[k] ?? ""));

            const flags = ["isOfficial", "featureHomepage", "isPopular", "showInNav", "showInFilters", "allowReviews"];
            flags.forEach((k) => form.append(k, f[k] ? "1" : "0"));

            form.append("categoryIds", JSON.stringify(f.categoryIds || []));
            form.append("certifications", JSON.stringify(f.certifications || []));
            form.append("keywords", JSON.stringify(f.keywords || []));
            form.append("social", JSON.stringify(f.social || {}));

            const logo = Array.isArray(this.logoFile) ? this.logoFile[0] : this.logoFile;
            const banner = Array.isArray(this.bannerFile) ? this.bannerFile[0] : this.bannerFile;
            if (logo) form.append("logo", logo);
            if (banner) form.append("banner", banner);

            return form;
        },

        validate() {
            const f = this.form;
            if (!f.title || !f.title.trim()) return "Brand name is required.";
            if (f.website && !/^https?:\/\//i.test(f.website)) return "Website must start with http:// or https://";
            if (f.supportEmail && !/^\S+@\S+\.\S+$/.test(f.supportEmail)) return "Support email is not valid.";
            if (f.foundedYear && (f.foundedYear < 1800 || f.foundedYear > this.currentYear)) return "Founded year is not valid.";
            return null;
        },

        async saveBrand(status) {
            const err = this.validate();
            if (err) {
                this.showError(err);
                return;
            }
            const flag = status === "Draft" ? "savingDraft" : "saving";
            this[flag] = true;

            try {
                const payload = this.buildPayload(status);
                let res;

                if (this.form.id) {
                    try {
                        res = await this.axios.post(`/api/brands/${this.form.id}?_method=PUT`, payload);
                    } catch {
                        res = await this.axios.post("/api/attribute-option-update?_method=PUT", payload);
                    }
                } else {
                    try {
                        res = await this.axios.post("/api/brands", payload);
                    } catch {
                        res = await this.axios.post("/api/save-option", payload);
                    }
                }

                if (res.data && res.data.success !== false) {
                    this.showSuccess(this.form.id ? "Updated successfully." : "Saved successfully.");
                    this.allItem();
                    this.closeAdd();
                } else {
                    this.showError(res.data?.message || "Save failed.");
                }
            } catch (error) {
                console.error("Save error:", error);
                this.showError("An error occurred while saving.");
            } finally {
                this[flag] = false;
            }
        },

        removeOption(item) {
            this.deletedId = item.id;
            this.deleteDialog = true;
        },

        cancel() {
            this.deleteDialog = false;
            this.deletedId = -1;
        },

        async agree() {
            this.deleting = true;
            try {
                let res;
                try {
                    res = await this.axios.delete(`/api/brands/${this.deletedId}`);
                } catch {
                    res = await this.axios.post("/api/remove-option", { id: this.deletedId });
                }

                if (res.data && res.data.success !== false) {
                    this.items = this.items.filter((v) => v.id !== this.deletedId);
                    this.showSuccess("Deleted successfully.");
                } else {
                    this.showError(res.data?.message || "Delete failed.");
                }
            } catch (error) {
                console.error("Delete error:", error);
                this.showError("An error occurred while deleting.");
            } finally {
                this.deleting = false;
                this.deleteDialog = false;
                this.deletedId = -1;
            }
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
        document.title = "Brands";
        this.allItem();
        this.loadCategories();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-brand {
    --ink: #1c1917;
    --muted: #78716c;
    --line: #e7e2dd;
    --accent: #e25311;
    --tint: #fdece3;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    padding-bottom: 32px;
}

/* ───────── Page header ───────── */
.tt-page-head { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 10px; }
.tt-h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.2px; }
.tt-sub { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
.tt-warn { color: #dc2626; }
.tt-muted { color: var(--muted); }
.tt-ghost-btn { color: var(--accent) !important; border-color: var(--accent) !important; }
.tt-add-btn { color: #fff !important; font-weight: 600; }
.tt-submit-btn { color: #fff !important; font-weight: 600; }

/* ───────── Cards ───────── */
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
.tt-head-wrap { flex-wrap: wrap; row-gap: 8px; }
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-body { padding: 16px 18px 18px; }
.tt-field-label { font-size: 11.5px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #57534e; margin-bottom: 8px; }
.tt-required { color: #dc2626; }
.tt-sticky { position: sticky; top: 16px; }

/* ───────── List: name cell ───────── */
.tt-name-item {
    padding-inline: 4px !important;
    cursor: pointer;
    border-radius: 6px;
    transition: transform 0.15s ease, background-color 0.15s ease;
}
.tt-name-item:hover { background-color: var(--tint); transform: translateX(3px); }
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-name-caret {
    color: var(--accent);
    opacity: 0;
    transition: opacity 0.15s ease, transform 0.15s ease;
    transform: translateX(-4px);
}
.tt-name-item:hover .tt-name-caret { opacity: 1; transform: none; }

/* ───────── Sidebar: preview + checklist ───────── */
.tt-preview { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
.tt-preview-banner { height: 72px; background: linear-gradient(90deg, var(--tint), #f5f1ec); }
.tt-preview-main { padding: 0 14px 14px; }
.tt-preview-logo { margin-top: -28px; border: 3px solid #fff; }
.tt-check-row { display: flex; align-items: center; gap: 8px; padding: 5px 0; font-size: 13px; color: var(--muted); }
.tt-check-row.is-done { color: var(--ink); }

/* ───────── Search result preview ───────── */
.tt-serp { background: #e8e8e8; border-radius: 8px; padding: 14px 18px; }
.tt-serp-label { font-size: 13.5px; color: #5f5f5f; margin-bottom: 8px; }
.tt-serp-title { font-size: 18px; color: #1a0dab; line-height: 1.3; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-serp-url { font-size: 13px; color: #188038; margin-bottom: 4px; }
.tt-serp-desc { font-size: 13.5px; color: #5f5f5f; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

/* ───────── Banner preview ───────── */
.tt-banner-preview {
    height: 64px; border-radius: 8px; overflow: hidden; background: #f5f5f4;
    display: grid; place-items: center; border: 1px dashed var(--line);
}

/* ───────── Status radio / options ───────── */
.tt-radio-box {
    display: flex; align-items: center; gap: 10px; font-size: 13.5px;
    border: 1px solid var(--line); border-radius: 8px; padding: 10px 14px; margin-bottom: 10px;
    background: #fbfafa; cursor: pointer; transition: border-color 0.15s, background 0.15s;
}
.tt-radio-box:last-child { margin-bottom: 0; }
.tt-radio-box.is-selected { border-color: #0f9d6b; background: #eaf8f1; font-weight: 600; }
.tt-radio-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #d8cfc7; display: inline-block; position: relative; flex: none; }
.tt-radio-dot.is-on { border-color: #0f9d6b; }
.tt-radio-dot.is-on::after { content: ""; position: absolute; inset: 3px; border-radius: 50%; background: #0f9d6b; }
.tt-option-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; font-size: 13.5px; border-bottom: 1px solid var(--line); }
.tt-option-row:last-child { border-bottom: 0; }
.tt-ai-alert { border: 1px solid #b7e6cd !important; }

/* ───────── EasyDataTable theme ───────── */
:deep(.customize-table) {
    --easy-table-border: 1px solid #e7e2dd;
    --easy-table-row-border: 1px solid #e7e2dd;
    --easy-table-header-font-size: 12px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #57534e;
    --easy-table-header-background-color: #f5f1ec;
    --easy-table-body-row-height: 56px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #fdece3;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
</style>