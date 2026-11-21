<template>
    <div class="tt-wrap tt-page">
        <div v-if="loading" class="tt-page-card tt-skel-group"><span v-for="n in 6" :key="n" class="tt-skel"></span></div>

        <PageNotFound v-else-if="missing" />

        <article v-else class="tt-page-card">
            <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
                <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t(page.title) }}</span>
            </nav>
            <h1 class="tt-doc-title">{{ $t(page.title) }}</h1>
            <p class="tt-muted tt-small">{{ $t("Last updated {date}", { date: $date(page.updated_at) }) }}</p>
            <div class="cms-content" v-html="page.content"></div>
        </article>
    </div>
</template>

<script>
/* Information pages written in Admin › Content › Pages (About us, Warranty policy ...). The HTML is cleaned on the server. */
import api from "@/api";
import PageNotFound from "@/PageNotFound.vue";

export default {
    name: "CmsPage",
    components: { PageNotFound },
    data: () => ({ page: null, loading: true, missing: false }),
    watch: { "$route.params.slug": { immediate: true, handler: "load" } },
    methods: {
        async load(slug) {
            if (!slug) return;
            this.loading = true;
            this.missing = false;
            try {
                this.page = (await api.get(`/pages/${slug}`, { quiet: true })).data;
                const site = this.$store.state.prefs.settings["store.name"] || "";
                document.title = `${this.page.meta_title || this.$t(this.page.title)} · ${site}`;
            } catch (e) {
                this.missing = true;
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
