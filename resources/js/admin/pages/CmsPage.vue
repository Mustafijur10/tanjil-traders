<template>
    <div class="cms-page">
        <v-progress-linear v-if="loading" indeterminate color="primary" />

        <v-container v-else-if="state === 'notfound'" class="cms-state">
            <div class="cms-state-code">404</div>
            <h1 class="cms-state-title">Page not found</h1>
            <p class="cms-state-text">The page you are looking for doesn't exist or is no longer available.</p>
            <v-btn color="primary" variant="flat" class="text-none" to="/">Back to home</v-btn>
        </v-container>

        <v-container v-else-if="state === 'error'" class="cms-state">
            <h1 class="cms-state-title">Something went wrong</h1>
            <p class="cms-state-text">We couldn't load this page. Please check your connection and try again.</p>
            <v-btn color="primary" variant="flat" class="text-none" @click="load(slug)">Try again</v-btn>
        </v-container>

        <article v-else-if="page" class="cms-wrap">
            <header class="cms-header">
                <h1 class="cms-title">{{ page.title }}</h1>
                <p v-if="page.short_description" class="cms-lead">{{ page.short_description }}</p>
                <img v-if="page.featured_image" :src="page.featured_image" :alt="page.title" class="cms-hero" />
            </header>
            <!-- Content is sanitized on the server when saved (allow-list), so it is safe to render -->
            <div class="cms-content" v-html="page.content"></div>
            <footer v-if="updated" class="cms-updated">Last updated {{ updated }}</footer>
        </article>
    </div>
</template>

<script>
import "../../../css/cms-content.scss";

const KEYS = ["description", "keywords", "robots", "og:title", "og:description", "og:image", "og:url", "og:type"];

export default {
    name: "CmsPage",

    data() {
        return { page: null, loading: true, state: "ok", originalTitle: document.title };
    },

    computed: {
        slug() { return this.$route.params.slug; },
        updated() {
            return this.page && this.page.updated_at
                ? new Date(this.page.updated_at).toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" })
                : "";
        },
    },

    watch: {
        slug: { immediate: true, handler(s) { this.load(s); } },
    },

    methods: {
        async load(slug) {
            this.loading = true;
            this.state = "ok";
            this.page = null;
            try {
                const { data } = await this.axios.get(`/api/pages/${encodeURIComponent(slug)}`);
                this.page = data.data;
                this.applyMeta(this.page);
            } catch (e) {
                this.state = e.response && e.response.status === 404 ? "notfound" : "error";
                document.title = this.state === "notfound" ? "Page not found" : this.originalTitle;
                this.setMeta("robots", "noindex,nofollow");
                this.setLink("canonical", "");
            } finally {
                this.loading = false;
            }
        },

        // Updates (or creates) the <head> tags. The server already renders the same tags for crawlers.
        applyMeta(p) {
            const abs = (u) => (u ? new URL(u, window.location.origin).href : "");
            const desc = p.meta_description || (p.short_description || "").slice(0, 160);
            const url = `${window.location.origin}/${p.slug}`;
            document.title = p.meta_title || p.title;
            this.setMeta("description", desc);
            this.setMeta("keywords", p.meta_keywords);
            this.setMeta("robots", p.robots);
            this.setMeta("og:type", "article");
            this.setMeta("og:title", p.og_title || p.meta_title || p.title);
            this.setMeta("og:description", p.og_description || desc);
            this.setMeta("og:image", abs(p.og_image || p.featured_image));
            this.setMeta("og:url", url);
            this.setLink("canonical", p.canonical_url || url);
        },
        setMeta(key, value) {
            const attr = key.startsWith("og:") ? "property" : "name";
            let el = document.head.querySelector(`meta[${attr}="${key}"]`);
            if (!value) return el && el.remove();
            if (!el) {
                el = document.createElement("meta");
                el.setAttribute(attr, key);
                document.head.appendChild(el);
            }
            el.setAttribute("content", value);
        },
        setLink(rel, href) {
            let el = document.head.querySelector(`link[rel="${rel}"]`);
            if (!href) return el && el.remove();
            if (!el) {
                el = document.createElement("link");
                el.setAttribute("rel", rel);
                document.head.appendChild(el);
            }
            el.setAttribute("href", href);
        },
    },

    // leave no page-specific tags behind for the next route
    beforeUnmount() {
        document.title = this.originalTitle;
        KEYS.forEach((k) => this.setMeta(k, ""));
        this.setLink("canonical", "");
    },
};
</script>

<style scoped>
.cms-wrap { max-width: 820px; margin: 0 auto; padding: 40px 20px 72px; }
.cms-title { font-size: 2.2rem; line-height: 1.2; font-weight: 700; letter-spacing: -0.3px; margin-bottom: 0.4em; }
.cms-lead { font-size: 1.15rem; line-height: 1.6; color: #57534e; margin-bottom: 1.2em; }
.cms-hero { width: 100%; max-height: 380px; object-fit: cover; border-radius: 10px; margin-bottom: 1.6em; }
.cms-updated { margin-top: 3em; padding-top: 1em; border-top: 1px solid #e7e2dd; font-size: 0.85rem; color: #78716c; }
.cms-state { text-align: center; padding: 96px 16px; max-width: 560px; }
.cms-state-code { font-size: 4.5rem; font-weight: 700; color: #d6d3d1; line-height: 1; }
.cms-state-title { font-size: 1.6rem; font-weight: 700; margin: 0.4em 0; }
.cms-state-text { color: #57534e; margin-bottom: 1.5em; }
@media (max-width: 600px) { .cms-wrap { padding: 24px 16px 48px; } .cms-title { font-size: 1.7rem; } }
</style>
