<template>
    <div>
        <PageHeader
            title="Language & Translations"
            subtitle="Every English text on your website and admin panel, with its Bangla version. Correct a Bangla line, save it, and it changes everywhere at once."
        >
            <v-btn-secondary prepend-icon="mdi-text-search" :loading="scanning" @click="scan">{{ $t("Scan for new text") }}</v-btn-secondary>
            <v-tooltip :disabled="canAuto" :text="$t('Add a Google Translate API key in Integrations & SEO to use this.')">
                <template #activator="{ props }">
                    <span v-bind="props">
                        <v-btn-primary prepend-icon="mdi-translate" :disabled="!canAuto || !counts.missing" :loading="translating" @click="autoTranslate">
                            {{ $t("Auto-translate missing") }}
                        </v-btn-primary>
                    </span>
                </template>
            </v-tooltip>
        </PageHeader>


            <section class="tt-box">
                <div class="tt-toolbar">
                    <div class="tt-segments" role="tablist" :aria-label="$t('Filter by status')">
                        <button
                            v-for="s in statuses"
                            :key="s.value"
                            type="button"
                            role="tab"
                            :class="{ 'is-on': filters.status === s.value }"
                            :aria-selected="filters.status === s.value"
                            @click="setFilter('status', s.value)"
                        >
                            {{ $t(s.label) }}<span class="tt-count tt-num">{{ $num(s.count) }}</span>
                        </button>
                    </div>
                    <div class="tt-toolbar-grow"></div>
                    <v-select-trz
                        :model-value="filters.group"
                        :items="groups.map((g) => ({ ...g, title: $t(g.title) }))"
                        density="compact"
                        :aria-label="$t('Text type')"
                        @update:model-value="setFilter('group', $event)"
                    />
                    <v-input-trz
                        v-model="search"
                        density="compact"
                        prepend-inner-icon="mdi-magnify"
                        :placeholder="$t('Search English or Bangla')"
                        clearable
                        :aria-label="$t('Search')"
                    />
                </div>

                <div class="tt-tr-list">
                    <div class="tt-tr-row is-head" aria-hidden="true">
                        <span>{{ $t("English") }}</span>
                        <span>{{ $t("Bangla") }}</span>
                        <span class="text-right">{{ $t("Status") }}</span>
                    </div>

                    <template v-if="loading && !items.length">
                        <div v-for="n in 6" :key="n" class="tt-tr-row tt-skel-group"><span class="tt-skel"></span><span class="tt-skel"></span><span></span></div>
                    </template>

                    <EmptyState
                        v-else-if="!items.length"
                        icon="mdi-translate"
                        :title="hasFilters ? 'Nothing matches' : 'No texts yet'"
                        :text="hasFilters ? 'Try another search or status.' : 'Click “Scan for new text” to collect every text used on the website.'"
                    />

                    <div v-for="row in items" :key="row.id" class="tt-tr-row">
                        <div class="tt-tr-en">
                            {{ row.source }}
                            <small>{{ $t(row.group === "content" ? "Content" : "Interface") }}</small>
                        </div>

                        <v-textarea-trz
                            v-model="drafts[row.id]"
                            class="tt-tr-bn"
                            rows="1"
                            auto-grow
                            lang="bn"
                            :placeholder="$t('Write the Bangla version…')"
                            :aria-label="$t('Bangla for: {text}', { text: row.source })"
                            @keydown.ctrl.enter.prevent="save(row)"
                            @keydown.meta.enter.prevent="save(row)"
                        />

                        <div class="tt-tr-side">
                            <StatusChip :label="statusLabel(row.status)" />
                            <v-btn-primary v-if="isDirty(row)" size="small" :loading="savingId === row.id" @click="save(row)">
                                {{ $t("Save") }}
                            </v-btn-primary>
                            <v-btn-secondary v-else-if="row.status === 'auto'" size="small" :loading="savingId === row.id" @click="approve([row])">
                                {{ $t("Looks right") }}
                            </v-btn-secondary>
                            <span v-else-if="justSaved === row.id" class="tt-tr-saved"><v-icon size="14">mdi-check</v-icon>{{ $t("Saved") }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="lastPage > 1 || autoOnPage.length" class="tt-box-foot align-center">
                    <v-btn-tertiary v-if="autoOnPage.length" color="primary" class="mr-auto" @click="approve(autoOnPage)">
                        {{ $t("Approve all {count} on this page", { count: $num(autoOnPage.length) }) }}
                    </v-btn-tertiary>
                    <v-pagination v-if="lastPage > 1" v-model="page" :length="lastPage" density="comfortable" total-visible="7" />
                </div>
            </section>
        <p class="tt-muted tt-small mt-3">{{ $t("Tip: press Ctrl + Enter to save the line you are typing in.") }}</p>
    </div>
</template>

<script>
import api from "@/api";

const STATUS_LABELS = { missing: "Missing", auto: "Needs review", reviewed: "Reviewed" };

export default {
    name: "Translations",

    data() {
        return {
            items: [],
            drafts: {},
            counts: {},
            canAuto: false,
            loading: true,
            page: 1,
            lastPage: 1,
            search: this.$route.query.q || "",
            filters: { status: this.$route.query.status || "", group: this.$route.query.group || "" },
            savingId: null,
            justSaved: null,
            scanning: false,
            translating: false,
            searchTimer: null,
            dictTimer: null,
        };
    },
    computed: {
        statuses() {
            const c = this.counts;
            const total = (c.missing || 0) + (c.auto || 0) + (c.reviewed || 0);
            return [
                { value: "", label: "All", count: total },
                { value: "missing", label: "Missing", count: c.missing || 0 },
                { value: "auto", label: "Needs review", count: c.auto || 0 },
                { value: "reviewed", label: "Reviewed", count: c.reviewed || 0 },
            ];
        },
        groups: () => [
            { value: "", title: "All texts" },
            { value: "ui", title: "Interface" },
            { value: "content", title: "Content" },
        ],
        hasFilters() {
            return !!(this.search || this.filters.status || this.filters.group);
        },
        autoOnPage() {
            return this.items.filter((r) => r.status === "auto" && !this.isDirty(r));
        },
    },
    watch: {
        page() {
            this.load();
        },
        search() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.page = 1;
                this.load();
            }, 350);
        },
    },
    created() {
        this.load();
    },
    beforeRouteLeave() {
        const unsaved = this.items.some((r) => this.isDirty(r));
        return !unsaved || window.confirm(this.$t("Some Bangla lines are not saved. Leave without saving?"));
    },
    methods: {
        statusLabel(status) {
            return STATUS_LABELS[status] || status;
        },
        isDirty(row) {
            return (this.drafts[row.id] || "").trim() !== (row.bn || "").trim();
        },
        setFilter(name, value) {
            this.filters[name] = value || "";
            this.page = 1;
            this.load();
        },
        async load() {
            this.loading = true;
            this.$router.replace({ query: { ...(this.search && { q: this.search }), ...(this.filters.status && { status: this.filters.status }), ...(this.filters.group && { group: this.filters.group }) } });
            try {
                const { data } = await api.get("/admin/translations", {
                    params: { page: this.page, search: this.search || undefined, status: this.filters.status || undefined, group: this.filters.group || undefined },
                });
                this.items = data.items.data;
                this.lastPage = data.items.last_page;
                this.counts = data.counts;
                this.canAuto = data.can_auto_translate;
                this.drafts = Object.fromEntries(this.items.map((r) => [r.id, r.bn || ""]));
            } finally {
                this.loading = false;
            }
        },
        // Keeps the row where it is (even if it no longer matches the filter) so the list does not jump while you work.
        replaceRow(updated) {
            const i = this.items.findIndex((r) => r.id === updated.id);
            if (i !== -1) {
                const before = this.items[i].status;
                this.items.splice(i, 1, updated);
                this.drafts[updated.id] = updated.bn || "";
                this.counts = { ...this.counts, [before]: (this.counts[before] || 1) - 1, [updated.status]: (this.counts[updated.status] || 0) + 1 };
            }
        },
        async save(row) {
            if (!this.isDirty(row)) return;
            this.savingId = row.id;
            try {
                const { data } = await api.put(`/admin/translations/${row.id}`, { bn: this.drafts[row.id] });
                this.replaceRow(data);
                this.flashSaved(row.id);
                this.refreshDictionary();
            } finally {
                this.savingId = null;
            }
        },
        async approve(rows) {
            const ids = rows.map((r) => r.id);
            this.savingId = ids.length === 1 ? ids[0] : null;
            try {
                await api.post("/admin/translations/approve", { ids });
                rows.forEach((r) => this.replaceRow({ ...r, status: "reviewed" }));
                this.$store.dispatch("notify", this.$t("Marked as reviewed."));
            } finally {
                this.savingId = null;
            }
        },
        flashSaved(id) {
            this.justSaved = id;
            setTimeout(() => {
                if (this.justSaved === id) this.justSaved = null;
            }, 2000);
        },
        // Batch dictionary refreshes so typing several lines in a row does one request.
        refreshDictionary() {
            clearTimeout(this.dictTimer);
            this.dictTimer = setTimeout(() => this.$store.dispatch("prefs/refreshDictionary"), 1200);
        },
        async scan() {
            this.scanning = true;
            try {
                const { data } = await api.post("/admin/translations/scan");
                this.$store.dispatch("notify", data.message);
                this.load();
            } finally {
                this.scanning = false;
            }
        },
        async autoTranslate() {
            this.translating = true;
            try {
                const { data } = await api.post("/admin/translations/auto");
                this.$store.dispatch("notify", data.message);
                this.load();
                this.refreshDictionary();
            } finally {
                this.translating = false;
            }
        },
    },
};
</script>
