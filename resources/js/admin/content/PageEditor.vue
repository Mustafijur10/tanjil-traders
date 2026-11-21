<template>
    <div>
        <PageHeader :title="isNew ? 'Add page' : form.title || 'Edit page'">
            <v-btn-tertiary prepend-icon="mdi-arrow-left" to="/admin/pages">{{ $t("All pages") }}</v-btn-tertiary>
            <v-btn-secondary v-if="!isNew && saved.is_published" prepend-icon="mdi-open-in-new" :href="`/${saved.slug}`" target="_blank">{{ $t("View") }}</v-btn-secondary>
        </PageHeader>

        <div v-if="loading" class="tt-product-layout">
            <div class="tt-box tt-box-body tt-skel-group"><span v-for="n in 6" :key="n" class="tt-skel"></span></div>
            <div class="tt-box tt-box-body tt-skel-group"><span v-for="n in 3" :key="n" class="tt-skel"></span></div>
        </div>

        <div v-else class="tt-product-layout">
            <div class="tt-vstack">
                <section class="tt-box">
                    <div class="tt-box-body tt-vstack-sm">
                        <v-input-trz v-model="form.title" :label="$t('Page title')" :error-messages="errors.title" @update:model-value="autoSlug" />
                        <div>
                            <label class="tt-field-label">{{ $t("Content") }}</label>
                            <RichText v-model="form.content" folder="pages" placeholder="Page content" />
                        </div>
                    </div>
                </section>
            </div>

            <aside class="tt-vstack tt-product-side">
                <section class="tt-box">
                    <div class="tt-box-head"><h2>{{ $t("Publishing") }}</h2></div>
                    <div class="tt-box-body tt-vstack-sm">
                        <v-switch-trz v-model="form.is_published" :label="$t('Published (visible on the website)')" />
                        <v-switch-trz v-model="form.show_in_footer" :label="$t('Link in the footer')" />
                        <v-input-trz v-model="form.slug" :label="$t('Web address')" prefix="/" :error-messages="errors.slug" @update:model-value="slugTouched = true" />
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-ico"><v-icon size="18">mdi-google</v-icon></span>
                        <div>
                            <h2>{{ $t("Google preview") }}</h2>
                            <p>{{ $t("How this page can look in search results") }}</p>
                        </div>
                    </div>
                    <div class="tt-box-body tt-vstack-sm">
                        <GooglePreview :title="form.meta_title || form.title" :description="form.meta_description || plain" :path="`/${form.slug || ''}`" />
                        <v-input-trz v-model="form.meta_title" :label="$t('Page title for Google (optional)')" counter="60" :placeholder="form.title" />
                        <v-textarea-trz v-model="form.meta_description" :label="$t('Description for Google')" counter="160" rows="3" />
                    </div>
                </section>
            </aside>
        </div>

        <div v-if="!loading && isDirty" class="tt-save-bar" role="region" :aria-label="$t('Save page')">
            <span>{{ isNew ? $t("New page, not saved yet.") : $t("You have unsaved changes.") }}</span>
            <v-btn-tertiary v-if="!isNew" color="white" :disabled="saving" @click="reset">{{ $t("Discard") }}</v-btn-tertiary>
            <v-btn-primary :loading="saving" :disabled="!form.title" @click="save">{{ $t(isNew ? "Save page" : "Save changes") }}</v-btn-primary>
        </div>
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";
import GooglePreview from "@/components/ui/GooglePreview.vue";

const slugify = (s) => String(s || "").toLowerCase().normalize("NFKD").replace(/[^\w\s-]/g, "").trim().replace(/[\s_]+/g, "-").replace(/-+/g, "-");
const blank = () => ({ title: "", slug: "", content: "", meta_title: "", meta_description: "", show_in_footer: true, is_published: false });

export default {
    name: "PageEditor",
    components: { GooglePreview },
    data: () => ({ loading: true, saving: false, form: blank(), saved: {}, snapshot: "", errors: {}, slugTouched: false }),
    computed: {
        isNew() {
            return !this.$route.params.id;
        },
        // First words of the content, used when no description is written.
        plain() {
            const div = document.createElement("div");
            div.innerHTML = this.form.content || "";
            return (div.textContent || "").replace(/\s+/g, " ").trim().slice(0, 160);
        },
        isDirty() {
            return JSON.stringify(this.form) !== this.snapshot;
        },
    },
    watch: { "$route.params.id": "load" },
    created() {
        this.load();
    },
    beforeRouteLeave() {
        return !this.isDirty || window.confirm(this.$t("You have unsaved changes. Leave without saving?"));
    },
    methods: {
        async load() {
            this.loading = true;
            try {
                this.saved = this.isNew ? {} : (await api.get(`/admin/pages/${this.$route.params.id}`)).data;
                this.reset();
            } finally {
                this.loading = false;
            }
        },
        reset() {
            this.form = { ...blank(), ...Object.fromEntries(Object.keys(blank()).map((k) => [k, this.saved[k] ?? blank()[k]])) };
            this.slugTouched = !this.isNew;
            this.$nextTick(() => (this.snapshot = JSON.stringify(this.form)));
        },
        autoSlug(title) {
            if (!this.slugTouched) this.form.slug = slugify(title);
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const { data } = this.isNew ? await api.post("/admin/pages", this.form) : await api.put(`/admin/pages/${this.saved.id}`, this.form);
                this.$store.dispatch("notify", this.$t("Page saved."));
                this.saved = data;
                this.reset();
                if (this.isNew) {
                    this.snapshot = JSON.stringify(this.form);
                    this.$router.replace(`/admin/pages/${data.id}`);
                }
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
