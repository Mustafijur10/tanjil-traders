<template>
    <v-slide-x-transition appear>
        <div class="tt-brand">
            <v-window v-model="tab">
                <!-- ═════════════════════════ All pages ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">Pages</h1>
                            <div class="tt-sub">{{ counts.all }} pages · {{ counts.published }} published · {{ counts.draft }} drafts</div>
                        </div>
                        <v-spacer />
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-add-btn" prepend-icon="mdi-plus" @click="$router.push('/admin/pages/new')">Add New Page</v-btn>
                    </div>

                    <v-card flat class="tt-card mt-4">
                        <div class="tt-head tt-head-wrap">
                            <span class="tt-card-title">All Pages</span>
                            <v-spacer />
                            <v-select v-model="statusFilter" :items="statusItems" variant="outlined" density="compact" hide-details style="max-width: 150px" class="mr-2" @update:model-value="reloadFirstPage" />
                            <v-text-field v-model="search" placeholder="Search title or URL…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details style="max-width: 240px" />
                        </div>
                        <v-card-text class="tt-body">
                            <EasyDataTable
                                v-model:server-options="serverOptions"
                                :server-items-length="total"
                                :headers="headers"
                                :items="items"
                                :loading="loading"
                                :rows-items="[10, 25, 50]"
                                table-class-name="customize-table"
                                buttons-pagination
                            >
                                <template #item-title="item">
                                    <v-list-item class="tt-name-item" density="compact" min-height="40" @click="$router.push(`/admin/pages/${item.id}/edit`)">
                                        <v-list-item-title class="tt-name">{{ item.title }}</v-list-item-title>
                                        <v-list-item-subtitle v-if="item.visibility === 'unlisted'" class="text-caption">Unlisted</v-list-item-subtitle>
                                    </v-list-item>
                                </template>
                                <template #item-slug="item"><code class="tt-slug">/{{ item.slug }}</code></template>
                                <template #item-status="item">
                                    <v-chip size="small" :color="item.status === 'published' ? 'success' : 'default'" variant="tonal" label>{{ item.status === "published" ? "Published" : "Draft" }}</v-chip>
                                </template>
                                <template #item-seo_status="item">
                                    <v-chip size="small" :color="seoColor[item.seo_status]" variant="tonal" label>{{ seoLabel[item.seo_status] }}</v-chip>
                                </template>
                                <template #item-created_at="item"><span class="tt-muted">{{ fmtDate(item.created_at) }}</span></template>
                                <template #item-updated_at="item"><span class="tt-muted">{{ fmtDate(item.updated_at, true) }}</span></template>
                                <template #item-operation="item">
                                    <div class="d-flex">
                                        <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click="$router.push(`/admin/pages/${item.id}/edit`)"><v-tooltip activator="parent" location="top">Edit</v-tooltip></v-btn>
                                        <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="openPreview(item.id)"><v-tooltip activator="parent" location="top">Preview</v-tooltip></v-btn>
                                        <v-btn :icon="item.status === 'published' ? 'mdi-cloud-off-outline' : 'mdi-cloud-upload-outline'" size="small" variant="text" :color="item.status === 'published' ? 'warning' : 'success'" @click="toggleStatus(item)">
                                            <v-tooltip activator="parent" location="top">{{ item.status === "published" ? "Unpublish" : "Publish" }}</v-tooltip>
                                        </v-btn>
                                        <v-btn icon="mdi-content-copy" size="small" variant="text" @click="duplicate(item)"><v-tooltip activator="parent" location="top">Duplicate</v-tooltip></v-btn>
                                        <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="askDelete(item)"><v-tooltip activator="parent" location="top">Delete</v-tooltip></v-btn>
                                    </div>
                                </template>
                                <template #empty-message>
                                    <div class="py-8 text-center">
                                        <v-icon size="40" color="grey">mdi-file-document-outline</v-icon>
                                        <div class="font-weight-medium mt-2">{{ search || statusFilter !== "all" ? "No pages match your filters" : "No pages yet" }}</div>
                                        <div class="tt-sub mb-3">{{ search || statusFilter !== "all" ? "Try a different search or status." : "Create your first page, such as About Us or Privacy Policy." }}</div>
                                        <v-btn v-if="!search && statusFilter === 'all'" color="#0f9d6b" variant="flat" class="text-none tt-add-btn" prepend-icon="mdi-plus" @click="$router.push('/admin/pages/new')">Add New Page</v-btn>
                                    </div>
                                </template>
                            </EasyDataTable>
                        </v-card-text>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ Add / edit page ═════════════════════════ -->
                <v-window-item value="edit" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">{{ form.id ? "Edit page" : "Add new page" }}</h1>
                            <div class="tt-sub">
                                <v-chip size="x-small" :color="isPublished ? 'success' : 'default'" variant="tonal" label class="mr-2">{{ isPublished ? "Published" : "Draft" }}</v-chip>
                                {{ form.id ? `Last saved ${fmtDate(form.updated_at, true)}` : "Not saved yet" }}
                            </div>
                        </div>
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-ghost-btn mr-2" prepend-icon="mdi-arrow-left" @click="$router.push('/admin/pages')">Back</v-btn>
                        <v-btn variant="outlined" class="text-none mr-2" prepend-icon="mdi-eye-outline" @click="openPreview()">Preview</v-btn>
                        <v-btn v-if="!isPublished" variant="outlined" class="text-none mr-2" prepend-icon="mdi-content-save-outline" :loading="savingDraft" @click="save('draft')">Save as Draft</v-btn>
                        <v-btn v-if="isPublished" variant="outlined" color="warning" class="text-none mr-2" prepend-icon="mdi-cloud-off-outline" @click="askUnpublish">Unpublish</v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-submit-btn" :prepend-icon="isPublished ? 'mdi-check' : 'mdi-cloud-upload-outline'" :loading="saving" @click="save('published')">
                            {{ isPublished ? "Update Page" : "Publish" }}
                        </v-btn>
                    </div>

                    <v-skeleton-loader v-if="loadingPage" type="card, card" class="mt-4" />
                    <v-row v-else dense class="mt-1">
                        <v-col cols="12" md="8">
                            <!-- A. Basic information -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Basic Information</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">Page title <span class="tt-required">*</span></div>
                                    <v-text-field v-model="form.title" variant="outlined" density="comfortable" class="mb-3" placeholder="e.g. Privacy Policy" autofocus :error-messages="errors.title" @update:model-value="autoSlug" />

                                    <div class="tt-field-label">Page URL / slug <span class="tt-required">*</span></div>
                                    <v-text-field v-model="form.slug" variant="outlined" density="comfortable" :prefix="origin + '/'" placeholder="privacy-policy" :error-messages="errors.slug" hint="Lowercase letters, numbers and hyphens. It is generated from the title until you edit it." persistent-hint @update:model-value="slugTouched = true" @blur="form.slug = slugify(form.slug)" />
                                    <div class="tt-final-url mt-2 mb-4"><v-icon size="15">mdi-link-variant</v-icon> Final URL: <b>{{ finalUrl }}</b></div>

                                    <div class="tt-field-label">Short description <span class="tt-muted">(optional)</span></div>
                                    <v-textarea v-model="form.short_description" variant="outlined" density="comfortable" rows="2" auto-grow hide-details="auto" class="mb-1" placeholder="A one or two sentence summary shown under the page title." :error-messages="errors.short_description" counter="500" />
                                </v-card-text>
                            </v-card>

                            <!-- Content -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Content</span></div>
                                <v-card-text class="tt-body">
                                    <RichEditor v-model="form.content" :pages="pageLinks" :error="!!errors.content" />
                                    <div v-if="errors.content" class="tt-warn mt-2 text-caption">{{ errors.content[0] }}</div>
                                </v-card-text>
                            </v-card>

                            <!-- B. SEO -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">SEO</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-serp">
                                        <div class="tt-serp-label">Search result preview</div>
                                        <div class="tt-serp-url">{{ serpUrl }}</div>
                                        <div class="tt-serp-title">{{ serpTitle }}</div>
                                        <div class="tt-serp-desc">{{ serpDesc }}</div>
                                    </div>
                                    <div class="tt-sub mt-2 mb-4">An approximation only. Search engines decide how and whether a page appears, and rankings are never guaranteed.</div>

                                    <v-alert v-if="seoWarnings.length" type="warning" variant="tonal" density="compact" class="mb-4">
                                        <div v-for="w in seoWarnings" :key="w" class="text-body-2">• {{ w }}</div>
                                    </v-alert>

                                    <div class="tt-field-label">Meta title</div>
                                    <v-text-field v-model="form.meta_title" variant="outlined" density="comfortable" hide-details="auto" class="mb-1" :placeholder="form.title || 'Meta title'" :error-messages="errors.meta_title" />
                                    <div class="tt-sub mb-4" :class="{ 'tt-warn': titleLen > 60 }">{{ titleLen }} / 60 characters</div>

                                    <div class="tt-field-label">Meta description</div>
                                    <v-textarea v-model="form.meta_description" variant="outlined" density="comfortable" rows="3" hide-details="auto" class="mb-1" :error-messages="errors.meta_description" />
                                    <div class="tt-sub mb-4" :class="{ 'tt-warn': descLen > 160 }">{{ descLen }} / 160 characters (aim for 70–160)</div>

                                    <v-row dense>
                                        <v-col cols="12" md="6">
                                            <div class="tt-field-label">Meta keywords <span class="tt-muted">(optional)</span></div>
                                            <v-text-field v-model="form.meta_keywords" variant="outlined" density="comfortable" hide-details="auto" placeholder="comma, separated" />
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <div class="tt-field-label">Canonical URL <span class="tt-muted">(optional)</span></div>
                                            <v-text-field v-model="form.canonical_url" variant="outlined" density="comfortable" hide-details="auto" :placeholder="finalUrl" :error-messages="errors.canonical_url" />
                                        </v-col>
                                        <v-col cols="12" md="6" class="mt-3">
                                            <div class="tt-field-label">Open Graph title</div>
                                            <v-text-field v-model="form.og_title" variant="outlined" density="comfortable" hide-details="auto" :placeholder="form.meta_title || form.title" />
                                        </v-col>
                                        <v-col cols="12" md="6" class="mt-3">
                                            <div class="tt-field-label">Open Graph image</div>
                                            <div class="d-flex align-center ga-3">
                                                <v-avatar rounded="lg" size="44" color="grey-lighten-3"><v-img v-if="form.og_image" :src="form.og_image" cover /><v-icon v-else icon="mdi-image-off-outline" size="18" color="grey" /></v-avatar>
                                                <v-file-input label="Upload" accept="image/png,image/jpeg,image/webp,image/gif" variant="outlined" density="compact" prepend-icon="" prepend-inner-icon="mdi-tray-arrow-up" hide-details :loading="uploading.og_image" @update:model-value="(f) => upload(f, 'og_image')" />
                                                <v-btn v-if="form.og_image" icon="mdi-close" size="x-small" variant="text" @click="form.og_image = ''" />
                                            </div>
                                        </v-col>
                                        <v-col cols="12" class="mt-3">
                                            <div class="tt-field-label">Open Graph description</div>
                                            <v-textarea v-model="form.og_description" variant="outlined" density="comfortable" rows="2" hide-details="auto" :placeholder="form.meta_description" />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-option-row mt-3"><span>Allow search engines to <b>index</b> this page</span><v-switch v-model="form.robots_index" color="#0f9d6b" density="compact" hide-details inset /></div>
                                    <div class="tt-option-row"><span>Allow search engines to <b>follow</b> links on this page</span><v-switch v-model="form.robots_follow" color="#0f9d6b" density="compact" hide-details inset /></div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Sidebar -->
                        <v-col cols="12" md="4">
                            <div class="tt-sticky">
                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Featured Image</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-banner-preview mb-3"><v-img v-if="form.featured_image" :src="form.featured_image" cover height="110" /><v-icon v-else icon="mdi-image-outline" size="26" color="grey" /></div>
                                        <v-file-input label="Upload image" accept="image/png,image/jpeg,image/webp,image/gif" variant="outlined" density="compact" prepend-icon="" prepend-inner-icon="mdi-tray-arrow-up" hide-details :loading="uploading.featured_image" @update:model-value="(f) => upload(f, 'featured_image')" />
                                        <v-btn v-if="form.featured_image" variant="text" size="small" color="error" class="text-none mt-1" @click="form.featured_image = ''">Remove image</v-btn>
                                        <div class="tt-sub mt-1">JPG, PNG, WebP or GIF, up to 4 MB. Optional.</div>
                                    </v-card-text>
                                </v-card>

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Display Settings</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-option-row"><span>Show in footer</span><v-checkbox-btn v-model="form.show_in_footer" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Show in main navigation</span><v-checkbox-btn v-model="form.show_in_navigation" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Include in sitemap</span><v-checkbox-btn v-model="form.show_in_sitemap" color="#0f9d6b" /></div>
                                        <div class="tt-field-label mt-4">Visibility</div>
                                        <v-select v-model="form.visibility" :items="visibilityItems" item-title="title" item-value="value" variant="outlined" density="comfortable" hide-details />
                                        <div class="tt-sub mt-1 mb-4">{{ form.visibility === "unlisted" ? "Reachable by its URL only. Hidden from menus and the sitemap, and not indexed." : "Visible to everyone once published." }}</div>
                                        <div class="tt-field-label">Display order</div>
                                        <v-text-field v-model.number="form.display_order" type="number" min="0" variant="outlined" density="comfortable" hide-details placeholder="0" />
                                        <div class="tt-sub mt-1">Lower numbers appear first in menus.</div>
                                    </v-card-text>
                                </v-card>

                                <v-btn v-if="form.id" block variant="outlined" color="error" class="text-none" prepend-icon="mdi-delete-outline" @click="askDelete(form)">Delete page</v-btn>
                            </div>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- Confirmation dialog (delete / unpublish) -->
            <v-dialog v-model="confirm.open" max-width="420">
                <v-card>
                    <v-toolbar :color="confirm.color" flat height="48"><v-toolbar-title class="text-subtitle-1 font-weight-bold">{{ confirm.title }}</v-toolbar-title></v-toolbar>
                    <v-card-text class="pa-4">{{ confirm.text }}</v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="confirm.open = false">Cancel</v-btn>
                        <v-btn :color="confirm.color" variant="flat" class="text-none" :loading="confirm.loading" @click="runConfirm">{{ confirm.label }}</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Preview dialog (drafts are previewed here, never on the public site) -->
            <v-dialog v-model="preview.open" max-width="900" scrollable>
                <v-card>
                    <v-toolbar flat height="48" color="#f5f1ec">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Preview</v-toolbar-title>
                        <v-chip size="small" variant="tonal" label class="mr-2">{{ preview.status === "published" ? "Published" : "Draft: not public" }}</v-chip>
                        <v-btn icon="mdi-close" variant="text" @click="preview.open = false" />
                    </v-toolbar>
                    <v-card-text class="pa-6">
                        <v-progress-linear v-if="preview.loading" indeterminate color="#0f9d6b" />
                        <article v-else class="tt-preview-page">
                            <h1 class="tt-preview-title">{{ preview.title || "Untitled page" }}</h1>
                            <p v-if="preview.short" class="tt-preview-lead">{{ preview.short }}</p>
                            <img v-if="preview.image" :src="preview.image" alt="" class="tt-preview-img" />
                            <div class="cms-content" v-html="preview.content || '<p><em>No content yet.</em></p>'"></div>
                        </article>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
import RichEditor from "./RichEditor.vue";
// import "../../css/cms-content.scss";
import "./css/cms-content.scss";

const slugify = (s) =>
    (s || "").toLowerCase().trim().replace(/&/g, "and").replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");

const emptyForm = () => ({
    id: null, title: "", slug: "", short_description: "", content: "", featured_image: "",
    meta_title: "", meta_description: "", meta_keywords: "", canonical_url: "", og_title: "", og_description: "", og_image: "",
    robots_index: true, robots_follow: true, show_in_footer: false, show_in_navigation: false, show_in_sitemap: true,
    display_order: 0, visibility: "public", status: "draft", updated_at: null,
});

export default {
    name: "CmsPages",
    components: { RichEditor },

    data() {
        return {
            tab: "list",
            // list
            items: [], total: 0, loading: false, search: "", statusFilter: "all",
            counts: { all: 0, published: 0, draft: 0 },
            serverOptions: { page: 1, rowsPerPage: 10, sortBy: "updated_at", sortType: "desc" },
            headers: [
                { text: "Title", value: "title", sortable: true, width: 260 },
                { text: "URL / Slug", value: "slug", sortable: true, width: 190 },
                { text: "Status", value: "status", sortable: true, width: 110 },
                { text: "SEO", value: "seo_status", width: 110 },
                { text: "Created", value: "created_at", sortable: true, width: 120 },
                { text: "Updated", value: "updated_at", sortable: true, width: 150 },
                { text: "Actions", value: "operation", width: 190 },
            ],
            statusItems: [{ title: "All", value: "all" }, { title: "Published", value: "published" }, { title: "Draft", value: "draft" }],
            seoLabel: { good: "Complete", partial: "Needs work", missing: "Missing" },
            seoColor: { good: "success", partial: "warning", missing: "error" },
            visibilityItems: [{ title: "Public", value: "public" }, { title: "Unlisted (URL only)", value: "unlisted" }],
            searchTimer: null,
            // editor
            form: emptyForm(), errors: {}, slugTouched: false, loadingPage: false,
            saving: false, savingDraft: false, pageLinks: [],
            uploading: { featured_image: false, og_image: false },
            // dialogs
            confirm: { open: false, title: "", text: "", label: "", color: "error", loading: false, action: null },
            preview: { open: false, loading: false, title: "", short: "", content: "", image: "", status: "draft" },
            snackbar: false, snackbarText: "", snackbarColor: "#0f9d6b",
        };
    },

    computed: {
        origin() { return window.location.origin; },
        isPublished() { return this.form.status === "published"; },
        finalUrl() { return `${this.origin}/${this.form.slug || "your-page-url"}`; },
        titleLen() { return (this.form.meta_title || "").length; },
        descLen() { return (this.form.meta_description || "").length; },
        serpTitle() { const t = (this.form.meta_title || this.form.title || "Page title").trim(); return t.length > 60 ? t.slice(0, 57) + "…" : t; },
        serpUrl() { return `${this.origin.replace(/^https?:\/\//, "")} › ${this.form.slug || "your-page-url"}`; },
        serpDesc() {
            const d = (this.form.meta_description || this.form.short_description || "").trim();
            if (!d) return "Add a meta description to control the text shown under the title in search results.";
            return d.length > 160 ? d.slice(0, 157) + "…" : d;
        },
        seoWarnings() {
            const w = [];
            if (!this.form.meta_title) w.push("No meta title: the page title will be used.");
            else if (this.titleLen > 60) w.push("Meta title is over 60 characters and may be cut off.");
            else if (this.titleLen < 30) w.push("Meta title is short. 30–60 characters works best.");
            if (!this.form.meta_description) w.push("No meta description: search engines will pick text from the page.");
            else if (this.descLen > 160) w.push("Meta description is over 160 characters and may be cut off.");
            else if (this.descLen < 70) w.push("Meta description is short. 70–160 characters works best.");
            if (!this.form.robots_index) w.push("This page is set to noindex and will not appear in search results.");
            return w;
        },
    },

    watch: {
        $route: { immediate: true, handler(r) { this.syncRoute(r); } },
        serverOptions: { deep: true, handler() { if (this.tab === "list") this.loadPages(); } },
        search() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(this.reloadFirstPage, 350);
        },
    },

    methods: {
        slugify,

        // ── routing: /admin/pages, /admin/pages/new, /admin/pages/:id/edit ──
        syncRoute(r) {
            if (!r.path.startsWith("/admin/pages")) return; // navigating away
            if (r.path.endsWith("/pages/new")) return this.startNew();
            if (r.params.id) return this.startEdit(r.params.id);
            this.tab = "list";
            this.form = emptyForm();
            const st = ["draft", "published"].includes(r.query.status) ? r.query.status : "all";
            this.statusFilter = st;
            this.reloadFirstPage();
        },
        startNew() {
            this.form = emptyForm();
            this.errors = {};
            this.slugTouched = false;
            this.tab = "edit";
            this.loadPageLinks();
        },
        async startEdit(id) {
            this.tab = "edit";
            this.loadPageLinks();
            if (String(this.form.id) === String(id)) return; // just saved, already loaded
            this.loadingPage = true;
            this.errors = {};
            try {
                const { data } = await this.axios.get(`/api/cms/pages/${id}`);
                this.fill(data.data);
            } catch (e) {
                this.showError("Could not load this page.");
                this.$router.replace("/admin/pages");
            } finally {
                this.loadingPage = false;
            }
        },
        fill(p) {
            const f = emptyForm();
            Object.keys(f).forEach((k) => { f[k] = p[k] ?? f[k]; });
            this.form = f;
            this.slugTouched = true;
        },

        // ── list ──
        reloadFirstPage() {
            if (this.serverOptions.page !== 1) this.serverOptions.page = 1; // watcher reloads
            else this.loadPages();
        },
        async loadPages() {
            this.loading = true;
            const o = this.serverOptions;
            try {
                const { data } = await this.axios.get("/api/cms/pages", {
                    params: { page: o.page, per_page: o.rowsPerPage, sort_by: o.sortBy, sort_type: o.sortType, q: this.search || undefined, status: this.statusFilter === "all" ? undefined : this.statusFilter },
                });
                this.items = data.data;
                this.total = data.meta.total;
                this.counts = data.counts;
            } catch (e) {
                this.showError(this.msg(e, "Failed to load pages."));
            } finally {
                this.loading = false;
            }
        },
        async loadPageLinks() {
            try {
                const { data } = await this.axios.get("/api/cms/pages", { params: { per_page: 100, status: "published", sort_by: "title", sort_type: "asc" } });
                this.pageLinks = data.data.map((p) => ({ title: p.title, slug: p.slug }));
            } catch (e) { /* link picker simply stays empty */ }
        },
        fmtDate(v, time = false) {
            if (!v) return "—";
            const d = new Date(v);
            return d.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" }) + (time ? ", " + d.toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" }) : "");
        },

        // ── row actions ──
        toggleStatus(item) {
            if (item.status === "published") {
                this.ask({ title: "Unpublish page", text: `"${item.title}" will no longer be visible to visitors. You can publish it again any time.`, label: "Unpublish", color: "warning", action: () => this.setStatus(item.id, "draft") });
            } else {
                this.setStatus(item.id, "published");
            }
        },
        async setStatus(id, status) {
            try {
                await this.axios.post(`/api/cms/pages/${id}/status`, { status });
                this.showSuccess(status === "published" ? "Page published." : "Page unpublished.");
                if (this.tab === "list") this.loadPages();
                else this.form.status = status;
            } catch (e) {
                this.showError(this.msg(e, "Could not change the status."));
            }
        },
        async duplicate(item) {
            try {
                await this.axios.post(`/api/cms/pages/${item.id}/duplicate`);
                this.showSuccess("Page duplicated as a draft.");
                this.loadPages();
            } catch (e) {
                this.showError(this.msg(e, "Could not duplicate the page."));
            }
        },
        askDelete(item) {
            this.ask({
                title: "Delete page", label: "Delete", color: "error",
                text: `Delete "${item.title}" permanently? Its URL /${item.slug} will stop working. This can't be undone.`,
                action: async () => {
                    await this.axios.delete(`/api/cms/pages/${item.id}`);
                    this.showSuccess("Page deleted.");
                    if (this.tab === "edit") this.$router.push("/admin/pages");
                    else this.loadPages();
                },
            });
        },
        askUnpublish() {
            this.ask({ title: "Unpublish page", label: "Unpublish", color: "warning", text: "This page will become a draft and disappear from the website. Your changes are saved.", action: () => this.save("draft") });
        },
        ask(c) { this.confirm = { open: true, loading: false, ...c }; },
        async runConfirm() {
            this.confirm.loading = true;
            try {
                await this.confirm.action();
                this.confirm.open = false;
            } catch (e) {
                this.showError(this.msg(e, "Something went wrong."));
            } finally {
                this.confirm.loading = false;
            }
        },

        // ── editor ──
        autoSlug(v) {
            if (!this.slugTouched && !this.form.id) this.form.slug = slugify(v);
        },
        async save(status) {
            this.errors = {};
            if (!this.form.title.trim()) {
                this.errors = { title: ["Page title is required."] };
                return this.showError("Page title is required.");
            }
            if (!this.form.slug) this.form.slug = slugify(this.form.title);
            const flag = status === "draft" ? "savingDraft" : "saving";
            this[flag] = true;
            try {
                const wasNew = !this.form.id;
                const body = { ...this.form, status };
                const { data } = wasNew ? await this.axios.post("/api/cms/pages", body) : await this.axios.put(`/api/cms/pages/${this.form.id}`, body);
                this.fill(data.data);
                this.showSuccess(status === "published" ? (wasNew ? "Page published." : "Page updated.") : "Draft saved.");
                if (wasNew) this.$router.replace(`/admin/pages/${data.data.id}/edit`);
            } catch (e) {
                if (e.response && e.response.status === 422) this.errors = e.response.data.errors || {};
                this.showError(this.msg(e, "Could not save the page."));
            } finally {
                this[flag] = false;
            }
        },
        async upload(file, field) {
            const f = Array.isArray(file) ? file[0] : file;
            if (!f) return;
            if (!/^image\/(png|jpe?g|webp|gif)$/.test(f.type)) return this.showError("Only JPG, PNG, WebP or GIF images are allowed.");
            if (f.size > 4 * 1024 * 1024) return this.showError("Image must be 4 MB or smaller.");
            this.uploading[field] = true;
            try {
                const body = new FormData();
                body.append("image", f);
                const { data } = await this.axios.post("/api/cms/uploads", body);
                this.form[field] = data.url;
            } catch (e) {
                this.showError(this.msg(e, "Image upload failed."));
            } finally {
                this.uploading[field] = false;
            }
        },

        // ── preview ──
        async openPreview(id) {
            this.preview = { open: true, loading: !!id, title: "", short: "", content: "", image: "", status: "draft" };
            let p = this.form;
            if (id) {
                try { p = (await this.axios.get(`/api/cms/pages/${id}`)).data.data; }
                catch (e) { this.preview.open = false; return this.showError("Could not load the preview."); }
            }
            Object.assign(this.preview, { loading: false, title: p.title, short: p.short_description, content: p.content, image: p.featured_image, status: p.status });
        },

        // ── feedback ──
        msg(e, fallback) {
            const d = e.response && e.response.data;
            return (d && (d.message || (d.errors && Object.values(d.errors)[0] && Object.values(d.errors)[0][0]))) || fallback;
        },
        showSuccess(m) { this.snackbarText = m; this.snackbarColor = "#0f9d6b"; this.snackbar = true; },
        showError(m) { this.snackbarText = m; this.snackbarColor = "#b3261e"; this.snackbar = true; },
    },

    created() { document.title = "Pages"; },
};
</script>

<style scoped>
.tt-brand { --ink: #1c1917; --muted: #78716c; --line: #e7e2dd; --accent: #e25311; --tint: #fdece3; font-family: Poppins, "Segoe UI", sans-serif; color: var(--ink); padding-bottom: 32px; }
.tt-page-head { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 10px; }
.tt-h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.2px; }
.tt-sub { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
.tt-warn { color: #dc2626; }
.tt-muted { color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0; }
.tt-ghost-btn { color: var(--accent) !important; border-color: var(--accent) !important; }
.tt-add-btn, .tt-submit-btn { color: #fff !important; font-weight: 600; }
.tt-card { background: #fff; border: 1px solid var(--line) !important; border-radius: 10px !important; box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04) !important; overflow: hidden; }
.tt-head { display: flex; align-items: center; gap: 8px; padding: 11px 16px; background: linear-gradient(90deg, var(--tint), #fff); border-bottom: 1px solid var(--line); border-left: 3px solid var(--accent); }
.tt-head-wrap { flex-wrap: wrap; row-gap: 8px; }
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; }
.tt-body { padding: 16px 18px 18px; }
.tt-field-label { font-size: 11.5px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #57534e; margin-bottom: 8px; }
.tt-required { color: #dc2626; }
.tt-sticky { position: sticky; top: 16px; }
.tt-final-url { font-size: 12.5px; color: var(--muted); word-break: break-all; }
.tt-final-url b { color: var(--ink); font-weight: 600; }
.tt-name-item { padding-inline: 4px !important; cursor: pointer; border-radius: 6px; transition: background-color 0.15s; }
.tt-name-item:hover { background-color: var(--tint); }
.tt-name { font-weight: 600; font-size: 13.5px; }
.tt-slug { font-size: 12.5px; background: #f5f5f4; padding: 2px 6px; border-radius: 4px; color: #44403c; }
.tt-serp { background: #e8e8e8; border-radius: 8px; padding: 14px 18px; }
.tt-serp-label { font-size: 12px; color: #5f5f5f; margin-bottom: 8px; }
.tt-serp-url { font-size: 13px; color: #188038; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-serp-title { font-size: 19px; color: #1a0dab; line-height: 1.3; margin-bottom: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-serp-desc { font-size: 13.5px; color: #5f5f5f; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.tt-banner-preview { min-height: 64px; border-radius: 8px; overflow: hidden; background: #f5f5f4; display: grid; place-items: center; border: 1px dashed var(--line); }
.tt-option-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 5px 0; font-size: 13.5px; border-bottom: 1px solid var(--line); }
.tt-option-row:last-child { border-bottom: 0; }
.tt-preview-page { max-width: 760px; margin: 0 auto; }
.tt-preview-title { font-size: 2rem; line-height: 1.25; font-weight: 700; margin-bottom: 0.4em; }
.tt-preview-lead { font-size: 1.1rem; color: #57534e; margin-bottom: 1.2em; }
.tt-preview-img { width: 100%; max-height: 360px; object-fit: cover; border-radius: 8px; margin-bottom: 1.2em; }
:deep(.customize-table) {
    --easy-table-border: 1px solid #e7e2dd; --easy-table-row-border: 1px solid #e7e2dd;
    --easy-table-header-font-size: 12px; --easy-table-header-height: 44px; --easy-table-header-font-color: #57534e;
    --easy-table-header-background-color: #f5f1ec; --easy-table-body-row-height: 56px; --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #fdece3; --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
</style>
