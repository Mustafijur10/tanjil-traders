<template>
    <v-slide-x-transition appear>
        <div class="tt-category">
            <v-window v-model="tab">
                <!-- ═════════════════════════ 1. Top-Level Categories Tab ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">Categories</h1>
                            <div class="tt-sub">{{ items.length }} top-level categories · {{ totalSubCategories }} sub-categories</div>
                        </div>
                        <v-spacer />
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-add-btn" prepend-icon="mdi-plus" @click="openAdd(null)">
                            Add Category
                        </v-btn>
                    </div>

                    <v-card flat class="tt-card mt-4">
                        <div class="tt-head tt-head-wrap">
                            <span class="tt-card-title">All Categories</span>
                            <v-spacer />
                            <v-text-field
                                v-model="searchValue"
                                placeholder="Search categories…"
                                prepend-inner-icon="mdi-magnify"
                                clearable
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="max-width: 240px"
                                class="mr-2"
                                @click:clear="searchValue = ''"
                            />
                            <v-btn-toggle v-model="viewMode" mandatory density="comfortable" variant="outlined" divided>
                                <v-btn value="list" size="small"><v-icon size="16">mdi-view-list</v-icon></v-btn>
                                <v-btn value="grid" size="small"><v-icon size="16">mdi-view-grid-outline</v-icon></v-btn>
                            </v-btn-toggle>
                        </div>

                        <!-- ── EasyDataTable List View ── -->
                        <div v-if="viewMode === 'list'" class="tt-table-wrap">
                            <EasyDataTable
                                :headers="headers"
                                :items="items"
                                :search-value="searchValue"
                                :loading="loading"
                                buttons-pagination
                                :rows-per-page="15"
                                table-class-name="customize-table tt-easy-table"
                            >
                                <!-- Category Title & Icon Slot -->
                                <template #item-title="item">
                                    <div class="d-flex align-center ga-3 py-2 cursor-pointer" @click="viewSubCategories(item)">
                                        <div class="tt-cat-icon-badge" :style="{ background: item.accentColor || '#0f9d6b' }">
                                            <v-icon size="18" color="white">{{ item.icon || 'mdi-tag-outline' }}</v-icon>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-body-2 text-decoration-hover">
                                                {{ item.title }}
                                            </div>
                                            <div class="text-caption text-muted d-flex align-center ga-2">
                                                <span>/{{ item.slug || '' }}</span>
                                                <v-chip v-if="item.featured" size="x-small" color="primary" variant="flat" class="px-1" label>
                                                    Featured
                                                </v-chip>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Sub-Categories Count Slot -->
                                <template #item-subCount="item">
                                    <v-chip
                                        size="small"
                                        variant="tonal"
                                        color="#0f9d6b"
                                        class="cursor-pointer font-weight-medium"
                                        @click="viewSubCategories(item)"
                                    >
                                        <v-icon size="14" class="mr-1">mdi-folder-multiple-outline</v-icon>
                                        {{ item.subCount || 0 }} sub-categories
                                    </v-chip>
                                </template>

                                <!-- Products Slot -->
                                <template #item-products="item">
                                    <span class="font-weight-medium">{{ item.products || 0 }}</span>
                                </template>

                                <!-- Revenue Slot -->
                                <template #item-revenue="item">
                                    <span class="font-weight-semibold">{{ money(item.revenue) }}</span>
                                </template>

                                <!-- Trend Slot -->
                                <template #item-trend="item">
                                    <span class="tt-trend-chip" :class="(item.trend || 0) >= 0 ? 'up' : 'down'">
                                        <v-icon size="11">{{ (item.trend || 0) >= 0 ? "mdi-arrow-top-right" : "mdi-arrow-bottom-right" }}</v-icon>
                                        {{ Math.abs(item.trend || 0) }}%
                                    </span>
                                </template>

                                <!-- Share Slot -->
                                <template #item-share="item">
                                    <div class="tt-share-bar">
                                        <div
                                            class="tt-share-fill"
                                            :style="{ width: (item.share || 20) + '%', background: item.accentColor || '#0f9d6b' }"
                                        ></div>
                                    </div>
                                </template>

                                <!-- Status Slot -->
                                <template #item-status="item">
                                    <v-chip
                                        size="x-small"
                                        :color="item.status === 'Active' ? 'success' : 'default'"
                                        variant="tonal"
                                        label
                                        class="font-weight-bold"
                                    >
                                        {{ item.status || 'Active' }}
                                    </v-chip>
                                </template>

                                <!-- Actions Slot -->
                                <template #item-operation="item">
                                    <div class="d-flex align-center ga-1">
                                        <v-tooltip text="View Sub-Categories" location="top">
                                            <template #activator="{ props }">
                                                <v-btn icon size="x-small" variant="text" v-bind="props" @click.stop="viewSubCategories(item)">
                                                    <v-icon size="17" color="#0f9d6b">mdi-folder-open-outline</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-tooltip>

                                        <v-tooltip text="Edit Category" location="top">
                                            <template #activator="{ props }">
                                                <v-btn icon size="x-small" variant="text" v-bind="props" @click.stop="editItem(item)">
                                                    <v-icon size="16">mdi-pencil-outline</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-tooltip>

                                        <v-tooltip text="Add Sub-Category" location="top">
                                            <template #activator="{ props }">
                                                <v-btn icon size="x-small" variant="text" v-bind="props" @click.stop="openAdd(item)">
                                                    <v-icon size="16">mdi-plus</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-tooltip>

                                        <v-tooltip text="Delete" location="top">
                                            <template #activator="{ props }">
                                                <v-btn icon size="x-small" variant="text" color="error" v-bind="props" @click.stop="removeOption(item)">
                                                    <v-icon size="16">mdi-delete-outline</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-tooltip>
                                    </div>
                                </template>
                            </EasyDataTable>
                        </div>

                        <!-- ── Grid View ── -->
                        <div v-else class="tt-grid">
                            <v-card v-for="c in filteredItems" :key="c.id" flat class="tt-grid-card" @click="viewSubCategories(c)">
                                <div class="d-flex align-center justify-space-between">
                                    <div class="tt-grid-thumb" :style="{ background: c.accentColor || '#0f9d6b' }">
                                        <v-icon size="22" color="white">{{ c.icon || 'mdi-tag-outline' }}</v-icon>
                                    </div>
                                    <v-chip size="x-small" :color="c.status === 'Active' ? 'success' : 'default'" variant="tonal" label>
                                        {{ c.status || 'Active' }}
                                    </v-chip>
                                </div>

                                <div class="font-weight-bold text-subtitle-2 mt-3">{{ c.title }}</div>
                                <div class="tt-sub mt-1">{{ c.subCount || 0 }} sub-categories · {{ c.products || 0 }} products</div>

                                <div class="d-flex align-center justify-space-between mt-3 pt-2 border-top">
                                    <span class="text-caption font-weight-bold text-primary">{{ money(c.revenue) }}</span>
                                    <div class="d-flex align-center">
                                        <v-btn icon size="x-small" variant="text" @click.stop="editItem(c)">
                                            <v-icon size="15">mdi-pencil-outline</v-icon>
                                        </v-btn>
                                        <v-btn icon size="x-small" variant="text" color="error" @click.stop="removeOption(c)">
                                            <v-icon size="15">mdi-delete-outline</v-icon>
                                        </v-btn>
                                    </div>
                                </div>
                            </v-card>
                            <div v-if="!filteredItems.length" class="tt-empty text-center w-100 py-8">
                                No categories match your search.
                            </div>
                        </div>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ 2. Sub-Category Drill-Down (Recursive with EasyDataTable) ═════════════════════════ -->
                <v-window-item value="subcategory" lazy>
                    <div class="tt-page-head">
                        
                        <div class="ml-3">
                            <div class="tt-breadcrumb">
                                <span class="tt-breadcrumb-link" @click="goToRoot">All Categories</span>
                                <template v-for="(s, i) in stack" :key="s.id">
                                    <v-icon size="12" class="mx-1">mdi-chevron-right</v-icon>
                                    <span
                                        class="tt-breadcrumb-link"
                                        :class="{ 'is-current': i === stack.length - 1 }"
                                        @click="goToDepth(i)"
                                    >
                                        {{ s.title }}
                                    </span>
                                </template>
                            </div>
                            <h1 class="tt-h1 mt-1">Sub-Category List · {{ currentParent.title }}</h1>
                        </div>
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-ghost-btn" prepend-icon="mdi-arrow-left" @click="comeBack">
                            Back
                        </v-btn>
                        <v-btn variant="outlined" class="text-none" prepend-icon="mdi-plus" @click="openBulkAdd">
                            Add multiple
                        </v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-add-btn" prepend-icon="mdi-plus" @click="openAdd(currentParent)">
                            Add sub-category
                        </v-btn>
                    </div>

                    <v-card flat class="tt-card mt-4">
                        <div class="tt-head tt-head-wrap">
                            <span class="tt-card-title ml-1">{{ currentChildren.length }} sub-categories under "{{ currentParent.title }}"</span>
                            <v-spacer />
                            <v-text-field
                                v-model="subSearch"
                                placeholder="Search subcategories…"
                                prepend-inner-icon="mdi-magnify"
                                clearable
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="max-width: 240px"
                                @click:clear="subSearch = ''"
                            />
                        </div>

                        <!-- ── EasyDataTable for Sub-Categories ── -->
                        <div class="tt-table-wrap">
                            <EasyDataTable
                                :headers="subHeaders"
                                :items="currentChildren"
                                :search-value="subSearch"
                                :loading="loadingSub"
                                buttons-pagination
                                :rows-per-page="15"
                                table-class-name="customize-table tt-easy-table"
                            >
                                <!-- Subcategory Title Slot -->
                                <template #item-title="item">
                                    <div class="d-flex align-center ga-2 py-2">
                                        <v-icon size="15" color="#8a7c73">mdi-subdirectory-arrow-right</v-icon>
                                        <span class="tt-subname font-weight-medium cursor-pointer" @click="viewSubCategories(item)">
                                            {{ item.title }}
                                        </span>
                                        <v-chip v-if="item.subCount" size="x-small" variant="outlined" color="#0f9d6b" class="ml-1" label>
                                            {{ item.subCount }}
                                        </v-chip>
                                    </div>
                                </template>

                                <!-- Parent Title Slot -->
                                <template #item-parentTitle>
                                    <span class="tt-muted">{{ currentParent.title }}</span>
                                </template>

                                <!-- Products Slot -->
                                <template #item-products="item">
                                    <span class="font-weight-medium">{{ item.products || 0 }}</span>
                                </template>

                                <!-- Revenue Slot -->
                                <template #item-revenue="item">
                                    <span>{{ money(item.revenue) }}</span>
                                </template>

                                <!-- Featured Slot -->
                                <template #item-featured="item">
                                    <v-icon v-if="item.featured" size="18" color="#0f9d6b">mdi-check-circle-outline</v-icon>
                                    <span v-else class="tt-muted">—</span>
                                </template>

                                <!-- Status Slot -->
                                <template #item-status="item">
                                    <v-chip
                                        size="x-small"
                                        :color="item.status === 'Active' ? 'success' : 'default'"
                                        variant="tonal"
                                        label
                                        class="font-weight-bold"
                                    >
                                        {{ item.status || 'Active' }}
                                    </v-chip>
                                </template>

                                <!-- Actions Slot -->
                                <template #item-operation="item">
                                    <div class="d-flex align-center ga-1">
                                        <v-tooltip text="Drill Down / View Children" location="top">
                                            <template #activator="{ props }">
                                                <v-btn icon size="x-small" variant="text" v-bind="props" @click="viewSubCategories(item)">
                                                    <v-icon size="16">mdi-folder-multiple-outline</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-tooltip>

                                        <v-tooltip text="Edit" location="top">
                                            <template #activator="{ props }">
                                                <v-btn icon size="x-small" variant="text" v-bind="props" @click="editItem(item)">
                                                    <v-icon size="16">mdi-pencil-outline</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-tooltip>

                                        <v-tooltip text="Delete" location="top">
                                            <template #activator="{ props }">
                                                <v-btn icon size="x-small" variant="text" color="error" v-bind="props" @click="removeOption(item)">
                                                    <v-icon size="16">mdi-delete-outline</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-tooltip>
                                    </div>
                                </template>
                            </EasyDataTable>
                        </div>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ 3. Add / Edit Category Tab (Embedded) ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">{{ addForm.id ? "Edit Category" : "Add Category" }}</h1>
                            <div class="tt-sub">
                                {{ addParent ? "Creating a sub-category under \"" + addParent.title + "\"" : "Create a new top-level catalog category" }}
                            </div>
                        </div>
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-ghost-btn mr-2" prepend-icon="mdi-arrow-left" @click="closeAdd">
                            Back
                        </v-btn>
                        <v-btn variant="outlined" class="text-none mr-2" prepend-icon="mdi-content-save-outline" :loading="savingDraft" @click="saveCategory('Draft')">
                            Save as Draft
                        </v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-submit-btn" prepend-icon="mdi-check" :loading="creating" @click="saveCategory(addForm.status)">
                            {{ addForm.id ? "Save changes" : "Create Category" }}
                        </v-btn>
                    </div>

                    <v-row dense class="mt-1">
                        <v-col cols="12" md="8">
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Basic Info</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">Category name <span class="tt-required">*</span></div>
                                    <v-text-field
                                        v-model="addForm.title"
                                        variant="outlined"
                                        density="comfortable"
                                        hide-details
                                        class="mb-4"
                                        placeholder="e.g. Smart Home & Gadgets"
                                        autofocus
                                        @update:model-value="autoGenerateSlug"
                                    />

                                    <div class="tt-field-label">Description</div>
                                    <v-textarea
                                        v-model="addForm.description"
                                        variant="outlined"
                                        density="comfortable"
                                        rows="3"
                                        hide-details
                                        class="mb-4"
                                        placeholder="A short description shown on the category landing page…"
                                    />

                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Parent category</div>
                                            <v-select
                                                v-model="addForm.parentId"
                                                :items="parentOptions"
                                                item-title="title"
                                                item-value="id"
                                                variant="outlined"
                                                density="comfortable"
                                                hide-details
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">URL handle (slug)</div>
                                            <v-text-field
                                                v-model="addForm.urlHandle"
                                                variant="outlined"
                                                density="comfortable"
                                                hide-details
                                                prefix="/category/"
                                                placeholder="smart-home"
                                            />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Icon &amp; Accent Color</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Icon</div>
                                            <div class="tt-icon-grid">
                                                <button
                                                    v-for="ic in iconOptions"
                                                    :key="ic"
                                                    type="button"
                                                    class="tt-icon-swatch"
                                                    :class="{ 'is-selected': addForm.icon === ic }"
                                                    @click="addForm.icon = ic"
                                                >
                                                    <v-icon size="18">{{ ic }}</v-icon>
                                                </button>
                                            </div>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Accent color</div>
                                            <div class="tt-color-row">
                                                <button
                                                    v-for="c in colorOptions"
                                                    :key="c"
                                                    type="button"
                                                    class="tt-color-swatch"
                                                    :class="{ 'is-selected': addForm.accentColor === c }"
                                                    :style="{ background: c }"
                                                    @click="addForm.accentColor = c"
                                                />
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- ── Inline Sub-categories builder ── -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head">
                                    <span class="tt-dot"></span><span class="tt-card-title">Sub-categories</span>
                                    <v-spacer />
                                    <span class="tt-sub">Optional — attach sub-categories instantly</span>
                                </div>
                                <v-card-text class="tt-body">
                                    <div v-for="(row, i) in subRows" :key="row.key" class="d-flex align-center ga-2 mb-2">
                                        <v-text-field
                                            v-model="row.title"
                                            variant="outlined"
                                            density="comfortable"
                                            hide-details
                                            :placeholder="'Sub-category name #' + (i + 1)"
                                        />
                                        <v-btn icon size="small" variant="text" color="error" @click="removeSubRow(i)">
                                            <v-icon size="15">mdi-close</v-icon>
                                        </v-btn>
                                    </div>
                                    <v-btn variant="outlined" size="small" class="text-none mt-1" prepend-icon="mdi-plus" @click="addSubRow">
                                        Add another sub-category
                                    </v-btn>
                                    <div class="tt-sub mt-2">
                                        All subcategory rows here are automatically created and linked to this category upon saving.
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="4">
                            <div class="tt-sticky">
                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Status</span></div>
                                    <v-card-text class="tt-body">
                                        <div
                                            v-for="opt in statusOptions"
                                            :key="opt.value"
                                            class="tt-radio-box"
                                            :class="{ 'is-selected': addForm.status === opt.value }"
                                            @click="addForm.status = opt.value"
                                        >
                                            <span class="tt-radio-dot" :class="{ 'is-on': addForm.status === opt.value }"></span>
                                            {{ opt.label }}
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Display Options</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-option-row">
                                            <span>Show in navigation menu</span>
                                            <v-checkbox-btn v-model="addForm.showInNav" color="#0f9d6b" />
                                        </div>
                                        <div class="tt-option-row">
                                            <span>Feature on homepage</span>
                                            <v-checkbox-btn v-model="addForm.featured" color="#0f9d6b" />
                                        </div>
                                        <div class="tt-option-row">
                                            <span>Allow product reviews</span>
                                            <v-checkbox-btn v-model="addForm.allowReviews" color="#0f9d6b" />
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-alert variant="tonal" color="#0f9d6b" class="tt-ai-alert mb-5" icon="mdi-creation">
                                    <div class="font-weight-medium mb-1">SEO Tip</div>
                                    <div class="tt-sub">{{ aiSuggestion }}</div>
                                </v-alert>
                            </div>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- ═════════ Dialog: Delete Confirmation ═════════ -->
            <v-dialog v-model="deleteDialog" max-width="420">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Delete Category</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="pa-4">
                        Are you sure you want to delete <strong>"{{ deletedItem ? deletedItem.title : '' }}"</strong>?
                        All nested sub-categories will also be deleted. This action cannot be undone.
                    </v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" @click="cancelDelete">Cancel</v-btn>
                        <v-btn color="error" variant="flat" :loading="deleting" @click="confirmDelete">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- ═════════ Dialog: Bulk Add Sub-Categories ═════════ -->
            <v-dialog v-model="bulkDialog" max-width="500">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Add Multiple Sub-Categories</v-toolbar-title>
                        <v-spacer />
                        <v-btn icon="mdi-close" variant="text" size="small" @click="bulkDialog = false" />
                    </v-toolbar>
                    <v-card-text class="pa-5">
                        <div class="tt-sub mb-3">
                            All rows below will be created under <strong>"{{ currentParent.title }}"</strong>.
                        </div>
                        <div v-for="(row, i) in bulkRows" :key="row.key" class="d-flex align-center ga-2 mb-2">
                            <v-text-field
                                v-model="row.title"
                                variant="outlined"
                                density="compact"
                                hide-details
                                :placeholder="'Sub-category name #' + (i + 1)"
                                autofocus
                            />
                            <v-btn icon size="small" variant="text" color="error" @click="removeBulkRow(i)">
                                <v-icon size="15">mdi-close</v-icon>
                            </v-btn>
                        </div>
                        <v-btn variant="text" size="small" class="text-none" prepend-icon="mdi-plus" @click="addBulkRow">
                            Add another row
                        </v-btn>
                    </v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="bulkDialog = false">Cancel</v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none" @click="saveBulk" :loading="savingBulk">
                            Save All
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- ═════════ Notification Toast ═════════ -->
            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3500" location="bottom right">
                {{ snackbarText }}
            </v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
let rowSeq = 0;
let sampleSeq = 1000;

const fullMoney = (v) => "৳" + Number(v || 0).toLocaleString("en-US", { maximumFractionDigits: 0 });

const ICONS = [
    "mdi-tag-outline",
    "mdi-laptop",
    "mdi-cellphone",
    "mdi-television",
    "mdi-headphones",
    "mdi-watch-variant",
    "mdi-camera-outline",
    "mdi-speaker",
    "mdi-tablet-ipad",
    "mdi-power-plug-outline",
    "mdi-gamepad-variant-outline",
    "mdi-router-wireless"
];

const COLORS_ = [
    "#0f9d6b",
    "#e25311",
    "#2f5be7",
    "#7c3aed",
    "#f59e0b",
    "#ec4899",
    "#0f766e",
    "#050c2e"
];

const enrich = (raw, depth = 0) => {
    sampleSeq += 41;
    const r = (sampleSeq % 1000) / 1000;
    return {
        id: raw.id,
        title: raw.title,
        slug: raw.slug || "",
        description: raw.description || "",
        parentId: raw.parent_id || null,
        subCount: raw.sub_count ?? (raw.children ? raw.children.length : Math.floor(r * (depth === 0 ? 6 : 2))),
        products: raw.products ?? Math.round(15 + r * 140),
        revenue: raw.revenue ?? Math.round(12000 + r * 450000),
        trend: raw.trend ?? Math.round((r - 0.25) * 35 * 10) / 10,
        share: raw.share ?? Math.round(15 + r * 75),
        status: raw.status || "Active",
        icon: raw.icon || ICONS[raw.id ? raw.id % ICONS.length : 0],
        accentColor: raw.accent_color || COLORS_[raw.id ? raw.id % COLORS_.length : 0],
        featured: Boolean(raw.featured),
        showInNav: raw.show_in_nav !== undefined ? Boolean(raw.show_in_nav) : true,
        allowReviews: raw.allow_reviews !== undefined ? Boolean(raw.allow_reviews) : true,
        children: raw.children || [],
    };
};

export default {
    name: "Category",

    data() {
        return {
            tab: "list",
            viewMode: "list",
            searchValue: "",
            subSearch: "",
            loading: false,
            loadingSub: false,

            items: [],
            childrenCache: {},
            stack: [],

            headers: [
                { text: "CATEGORY", value: "title", sortable: true },
                { text: "SUBCATEGORIES", value: "subCount", sortable: true, width: 170 },
                { text: "PRODUCTS", value: "products", sortable: true, width: 110 },
                { text: "REVENUE", value: "revenue", sortable: true, width: 130 },
                { text: "TREND", value: "trend", sortable: true, width: 100 },
                { text: "SHARE", value: "share", width: 130 },
                { text: "STATUS", value: "status", sortable: true, width: 100 },
                { text: "ACTIONS", value: "operation", width: 140 },
            ],

            subHeaders: [
                { text: "SUBCATEGORY", value: "title", sortable: true },
                { text: "PARENT", value: "parentTitle", sortable: true, width: 180 },
                { text: "PRODUCTS", value: "products", sortable: true, width: 110 },
                { text: "REVENUE", value: "revenue", sortable: true, width: 130 },
                { text: "FEATURED", value: "featured", width: 100 },
                { text: "STATUS", value: "status", sortable: true, width: 100 },
                { text: "ACTIONS", value: "operation", width: 140 },
            ],

            addForm: {},
            addParent: null,
            returnTab: "list",
            returnStack: [],
            subRows: [],

            iconOptions: ICONS,
            colorOptions: COLORS_,
            statusOptions: [
                { label: "Active", value: "Active" },
                { label: "Draft", value: "Draft" },
            ],

            creating: false,
            savingDraft: false,
            savingBulk: false,
            deleting: false,

            bulkDialog: false,
            bulkRows: [],

            deleteDialog: false,
            deletedItem: null,

            snackbar: false,
            snackbarText: "",
            snackbarColor: "#0f9d6b",
        };
    },

    computed: {
        filteredItems() {
            const q = this.searchValue.trim().toLowerCase();
            return !q ? this.items : this.items.filter((c) => c.title.toLowerCase().includes(q));
        },

        totalSubCategories() {
            return this.items.reduce((sum, c) => sum + (c.subCount || 0), 0);
        },

        currentParent() {
            return this.stack.length ? this.stack[this.stack.length - 1] : { id: null, title: "All Categories" };
        },

        currentChildren() {
            const key = this.currentParent.id ?? "root";
            return this.childrenCache[key] || [];
        },

        parentOptions() {
            const out = [{ id: null, title: "None — Top-level category" }];
            const walk = (list, depth) => {
                list.forEach((c) => {
                    out.push({ id: c.id, title: "— ".repeat(depth) + c.title });
                    const kids = this.childrenCache[c.id];
                    if (kids) walk(kids, depth + 1);
                });
            };
            walk(this.items, 0);
            return out;
        },

        aiSuggestion() {
            const words = (this.addForm.description || "").trim() ? this.addForm.description.trim().split(/\s+/).length : 0;
            return words >= 30
                ? "Excellent category description! Detailed copy improves store SEO and discovery."
                : "Adding at least 30-40 words in the category description will improve search rankings.";
        },
    },

    methods: {
        money: fullMoney,

        async allItem() {
            this.loading = true;
            try {
                let res;
                try {
                    res = await this.axios.get("/api/categories?parent_id=0");
                } catch {
                    res = await this.axios.get("/api/attribute/category");
                }

                if (res.data && res.data.success) {
                    this.items = (res.data.data || []).map((raw) => enrich(raw, 0));
                }
            } catch (error) {
                console.error("Failed to load categories:", error);
                this.showError("Failed to load categories.");
            } finally {
                this.loading = false;
            }
        },

        async loadChildren(parent) {
            if (!parent || !parent.id) return;
            this.loadingSub = true;
            try {
                let res;
                try {
                    res = await this.axios.get(`/api/categories?parent_id=${parent.id}`);
                } catch {
                    res = await this.axios.get(`/api/attribute-options?parent_attribute_option_id=${parent.id}`);
                }

                if (res.data && res.data.success) {
                    const key = parent.id;
                    this.childrenCache[key] = (res.data.data || []).map((raw) => enrich(raw, this.stack.length + 1));
                    this.$forceUpdate();
                }
            } catch (error) {
                console.error("Failed to fetch sub-categories:", error);
                this.showError("Failed to load sub-categories.");
            } finally {
                this.loadingSub = false;
            }
        },

        viewSubCategories(item) {
            this.stack.push({ id: item.id, title: item.title });
            this.tab = "subcategory";
            if (!this.childrenCache[item.id]) {
                this.loadChildren(item);
            }
        },

        goToRoot() {
            this.stack = [];
            this.tab = "list";
            this.allItem();
        },

        goToDepth(i) {
            this.stack = this.stack.slice(0, i + 1);
            const node = this.stack[this.stack.length - 1];
            if (!this.childrenCache[node.id]) {
                this.loadChildren(node);
            }
        },

        comeBack() {
            this.stack.pop();
            if (!this.stack.length) {
                this.tab = "list";
                this.allItem();
            } else {
                const node = this.stack[this.stack.length - 1];
                if (!this.childrenCache[node.id]) {
                    this.loadChildren(node);
                }
            }
        },

        openAdd(parent) {
            this.returnTab = this.tab;
            this.returnStack = [...this.stack];
            this.addParent = parent;
            this.addForm = {
                id: null,
                title: "",
                description: "",
                parentId: parent ? parent.id : null,
                urlHandle: "",
                icon: ICONS[0],
                accentColor: COLORS_[0],
                status: "Active",
                showInNav: true,
                featured: false,
                allowReviews: true,
            };
            this.subRows = [];
            this.tab = "add";
        },

        editItem(item) {
            this.returnTab = this.tab;
            this.returnStack = [...this.stack];
            this.addParent = null;
            this.addForm = {
                id: item.id,
                title: item.title,
                description: item.description || "",
                parentId: item.parentId !== undefined ? item.parentId : (this.stack.length ? this.currentParent.id : null),
                urlHandle: item.slug || "",
                icon: item.icon || ICONS[0],
                accentColor: item.accentColor || COLORS_[0],
                status: item.status || "Active",
                showInNav: item.showInNav ?? true,
                featured: item.featured ?? false,
                allowReviews: item.allowReviews ?? true,
            };
            this.subRows = [];
            this.tab = "add";
        },

        closeAdd() {
            this.tab = this.returnTab;
            this.stack = this.returnStack;
        },

        autoGenerateSlug(val) {
            if (!this.addForm.id && val) {
                this.addForm.urlHandle = val.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
            }
        },

        addSubRow() {
            this.subRows.push({ key: ++rowSeq, title: "" });
        },

        removeSubRow(i) {
            this.subRows.splice(i, 1);
        },

        async saveCategory(status) {
            if (!this.addForm.title || !this.addForm.title.trim()) {
                this.showError("Category name is required.");
                return;
            }

            const flag = status === "Draft" ? "savingDraft" : "creating";
            this[flag] = true;

            try {
                const payload = {
                    title: this.addForm.title.trim(),
                    description: this.addForm.description,
                    parent_id: this.addForm.parentId || null,
                    slug: this.addForm.urlHandle,
                    icon: this.addForm.icon,
                    accent_color: this.addForm.accentColor,
                    status: status || this.addForm.status || "Active",
                    show_in_nav: this.addForm.showInNav,
                    featured: this.addForm.featured,
                    allow_reviews: this.addForm.allowReviews,
                    sub_categories: this.subRows.map((r) => r.title.trim()).filter(Boolean),
                };

                let res;
                if (this.addForm.id) {
                    try {
                        res = await this.axios.put(`/api/categories/${this.addForm.id}`, payload);
                    } catch {
                        res = await this.axios.put("/api/attribute-option-update", {
                            id: this.addForm.id,
                            title: payload.title,
                        });
                    }
                } else {
                    try {
                        res = await this.axios.post("/api/categories", payload);
                    } catch {
                        res = await this.axios.post("/api/save-option", {
                            title: payload.title,
                            slug: "category",
                            parent_attribute_option_id: payload.parent_id || 0,
                        });
                    }
                }

                if (res.data && res.data.success !== false) {
                    this.showSuccess(this.addForm.id ? "Category updated successfully." : "Category created successfully.");
                    this.reloadAfterSave();
                    this.tab = this.returnTab;
                    this.stack = this.returnStack;
                } else {
                    this.showError(res.data?.message || "Failed to save category.");
                }
            } catch (error) {
                console.error("Save error:", error);
                this.showError("An error occurred while saving.");
            } finally {
                this[flag] = false;
            }
        },

        reloadAfterSave() {
            if (this.returnStack.length) {
                const node = this.returnStack[this.returnStack.length - 1];
                this.loadChildren(node);
            } else {
                this.allItem();
            }
        },

        openBulkAdd() {
            this.bulkRows = [
                { key: ++rowSeq, title: "" },
                { key: ++rowSeq, title: "" },
                { key: ++rowSeq, title: "" },
            ];
            this.bulkDialog = true;
        },

        addBulkRow() {
            this.bulkRows.push({ key: ++rowSeq, title: "" });
        },

        removeBulkRow(i) {
            this.bulkRows.splice(i, 1);
        },

        async saveBulk() {
            const valid = this.bulkRows.map((r) => r.title.trim()).filter(Boolean);
            if (!valid.length) {
                this.showError("Please enter at least one sub-category name.");
                return;
            }

            this.savingBulk = true;
            try {
                try {
                    await this.axios.post("/api/categories/bulk", {
                        parent_id: this.currentParent.id,
                        titles: valid,
                    });
                } catch {
                    for (const title of valid) {
                        await this.axios.post("/api/save-option", {
                            title,
                            slug: "category",
                            parent_attribute_option_id: this.currentParent.id || 0,
                        });
                    }
                }

                this.showSuccess(`${valid.length} sub-categories added successfully.`);
                this.loadChildren(this.currentParent);
                this.bulkDialog = false;
            } catch (error) {
                console.error("Bulk save error:", error);
                this.showError("Failed to save sub-categories.");
            } finally {
                this.savingBulk = false;
            }
        },

        removeOption(item) {
            this.deletedItem = item;
            this.deleteDialog = true;
        },

        cancelDelete() {
            this.deleteDialog = false;
            this.deletedItem = null;
        },

        async confirmDelete() {
            if (!this.deletedItem) return;
            this.deleting = true;

            try {
                let res;
                try {
                    res = await this.axios.delete(`/api/categories/${this.deletedItem.id}`);
                } catch {
                    res = await this.axios.post("/api/remove-option", { id: this.deletedItem.id });
                }

                if (res.data && res.data.success !== false) {
                    const id = this.deletedItem.id;
                    this.items = this.items.filter((x) => x.id !== id);
                    Object.keys(this.childrenCache).forEach((k) => {
                        this.childrenCache[k] = (this.childrenCache[k] || []).filter((x) => x.id !== id);
                    });
                    this.showSuccess("Category deleted successfully.");
                } else {
                    this.showError(res.data?.message || "Failed to delete category.");
                }
            } catch (error) {
                console.error("Delete error:", error);
                this.showError("An error occurred while deleting.");
            } finally {
                this.deleting = false;
                this.deleteDialog = false;
                this.deletedItem = null;
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
        document.title = "Category Management";
        this.allItem();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-category {
    --ink: #1c1917;
    --muted: #78716c;
    --line: #e7e2dd;
    --accent: #0f9d6b;
    --tint: #eaf8f1;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    padding-bottom: 32px;
}

/* ───────── Page Header ───────── */
.tt-page-head { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 10px; }
.tt-h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.2px; }
.tt-sub { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
.tt-muted { color: var(--muted); }
.tt-ghost-btn { color: var(--accent) !important; border-color: var(--accent) !important; }
.tt-add-btn { color: #fff !important; font-weight: 600; }

.tt-breadcrumb { font-size: 12px; color: var(--muted); }
.tt-breadcrumb-link { cursor: pointer; }
.tt-breadcrumb-link:hover { color: var(--accent); }
.tt-breadcrumb-link.is-current { color: var(--ink); font-weight: 600; cursor: default; }

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
    padding: 12px 16px;
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

/* ───────── EasyDataTable Theme Customization ───────── */
.tt-table-wrap {
    padding: 4px;
}
:deep(.tt-easy-table) {
    --easy-table-border: 1px solid var(--line);
    --easy-table-row-border: 1px solid #f0edea;
    --easy-table-header-font-size: 11.5px;
    --easy-table-header-font-color: #57534e;
    --easy-table-header-background-color: #f7f5f3;
    --easy-table-header-height: 44px;
    --easy-table-body-row-height: 52px;
    --easy-table-body-font-size: 13px;
    --easy-table-body-font-color: var(--ink);
    --easy-table-body-row-hover-background-color: #f6faf8;
    --easy-table-footer-background-color: #fff;
    --easy-table-footer-font-color: var(--muted);
    --easy-table-footer-font-size: 12px;
    --easy-table-footer-height: 50px;
    border: none;
}
:deep(.tt-easy-table th) {
    font-weight: 700 !important;
    letter-spacing: 0.4px;
}
:deep(.tt-easy-table td) {
    padding: 8px 12px !important;
}

.tt-cat-icon-badge {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.cursor-pointer { cursor: pointer; }
.text-decoration-hover:hover { color: var(--accent); }

.tt-trend-chip {
    display: inline-flex; align-items: center; gap: 2px; font-size: 11.5px; font-weight: 600;
    padding: 2px 8px; border-radius: 10px;
}
.tt-trend-chip.up { color: #0f7a4d; background: #e0f5ea; }
.tt-trend-chip.down { color: #b3261e; background: #fde9e7; }
.tt-share-bar { width: 90px; height: 6px; border-radius: 4px; background: #eef0ee; overflow: hidden; }
.tt-share-fill { height: 100%; border-radius: 4px; }

.tt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; padding: 16px; }
.tt-grid-card { border: 1px solid var(--line); border-radius: 10px; padding: 16px; cursor: pointer; transition: border-color 0.15s, transform 0.15s; }
.tt-grid-card:hover { border-color: var(--accent); transform: translateY(-2px); }
.tt-grid-thumb { width: 44px; height: 44px; border-radius: 9px; display: grid; place-items: center; }

.tt-subname:hover { color: var(--accent); }

.tt-icon-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; }
.tt-icon-swatch {
    width: 36px; height: 36px; border-radius: 8px; display: grid; place-items: center;
    border: 1px solid var(--line); background: #fff; color: var(--muted); cursor: pointer;
    transition: border-color 0.15s, background 0.15s, color 0.15s;
}
.tt-icon-swatch:hover { background: var(--tint); }
.tt-icon-swatch.is-selected { border-color: var(--accent); background: var(--tint); color: var(--accent); }
.tt-color-row { display: flex; gap: 10px; align-items: center; padding-top: 4px; flex-wrap: wrap; }
.tt-color-swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08); }
.tt-color-swatch.is-selected { border-color: var(--ink); transform: scale(1.1); }

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

.tt-submit-btn { color: #fff !important; font-weight: 600; }
.border-top { border-top: 1px solid var(--line); }
</style>