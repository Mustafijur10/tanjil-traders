<template>
    <div>
        <PageHeader title="Announcement Bar" subtitle="Short messages that scroll across the very top of every page, e.g. delivery offers, holiday timings or a hotline number." />

        <section class="tt-box mb-4">
            <div class="tt-box-head">
                <span class="tt-box-ico"><v-icon size="18">mdi-eye-outline</v-icon></span>
                <div>
                    <h2>{{ $t("Live preview") }}</h2>
                    <p>{{ $t("What visitors see right now. Only switched-on messages within their dates are shown.") }}</p>
                </div>
            </div>
            <div class="tt-announce-preview" aria-hidden="true">
                <div v-if="liveItems.length" class="tt-announce-track">
                    <span v-for="n in 2" :key="n" class="tt-announce-run">
                        <span v-for="a in liveItems" :key="a.id + '-' + n" class="tt-announce-item"><v-icon size="15">mdi-bullhorn-outline</v-icon>{{ a.title }}</span>
                    </span>
                </div>
                <span v-else class="tt-announce-empty">{{ $t("The bar is hidden while there are no live messages.") }}</span>
            </div>
        </section>

        <section class="tt-box">
            <div class="tt-toolbar">
                <v-input-trz
                    v-model="newText"
                    class="tt-toolbar-grow"
                    density="compact"
                    prepend-inner-icon="mdi-bullhorn-outline"
                    :placeholder="$t('Write a message, e.g. Free delivery in Dhaka on orders over ৳20,000')"
                    counter="160"
                    :aria-label="$t('New message')"
                    @keydown.enter.prevent="quickAdd"
                />
                <v-btn-primary prepend-icon="mdi-plus" :disabled="!newText.trim()" :loading="adding" @click="quickAdd">{{ $t("Add message") }}</v-btn-primary>
            </div>

            <div v-if="loading" class="tt-box-body tt-skel-group"><span v-for="n in 3" :key="n" class="tt-skel"></span></div>

            <EmptyState v-else-if="!items.length" icon="mdi-bullhorn-outline" title="No messages yet" text="Type a message above and press Enter." />

            <VueDraggable v-else v-model="items" handle=".tt-drag" :animation="150" @end="saveOrder">
                <div v-for="a in items" :key="a.id" class="tt-announce-row">
                    <v-icon class="tt-drag" :aria-label="$t('Drag to reorder')">mdi-drag-vertical</v-icon>
                    <div class="tt-announce-text">
                        <b>{{ a.title }}</b>
                        <span class="tt-muted tt-small">
                            <template v-if="a.link"><v-icon size="13">mdi-link-variant</v-icon>{{ a.link }}</template>
                            <template v-if="a.starts_at || a.ends_at"> <v-icon size="13">mdi-calendar-range</v-icon>{{ range(a) }}</template>
                        </span>
                    </div>
                    <StatusChip :label="schedule[state(a)].label" :tone="schedule[state(a)].tone" />
                    <v-switch-trz :model-value="a.is_active" :aria-label="$t('Show on the website')" @update:model-value="quickToggle(a, $event)" />
                    <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(a)" />
                    <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(a)" />
                </div>
            </VueDraggable>
        </section>

        <v-dialog-trz v-model="dialog" max-width="620" :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t("Edit message") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model="form.title" class="is-full" :label="$t('Message')" counter="160" :error-messages="errors.title" />
                        <v-input-trz v-model="form.link" class="is-full" :label="$t('Link (optional)')" placeholder="/deals" :hint="$t('Makes the message clickable.')" persistent-hint :error-messages="errors.link" />
                        <v-input-trz v-model="form.starts_at" type="datetime-local" :label="$t('Show from (optional)')" />
                        <v-input-trz v-model="form.ends_at" type="datetime-local" :label="$t('Show until (optional)')" :error-messages="errors.ends_at" />
                        <v-switch-trz v-model="form.is_active" class="is-full" :label="$t('Show on the website')" />
                    </div>
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.title" @click="save">{{ $t("Save changes") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" title="Delete this message?" :loading="removing" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";
import { toLocalInput, fromLocalInput, scheduleState, SCHEDULE_LABELS } from "@/utils/datetime";

export default {
    name: "Announcements",
    data: () => ({
        items: [],
        loading: true,
        newText: "",
        adding: false,
        dialog: false,
        form: {},
        errors: {},
        saving: false,
        deleteDialog: false,
        deleting: null,
        removing: false,
    }),
    computed: {
        schedule: () => SCHEDULE_LABELS,
        liveItems() {
            return this.items.filter((a) => scheduleState(a) === "live");
        },
    },
    created() {
        this.load();
    },
    methods: {
        state: (a) => scheduleState(a),
        range(a) {
            const f = (d) => (d ? this.$date(d, { day: "numeric", month: "short" }) : "…");
            return `${f(a.starts_at)} – ${f(a.ends_at)}`;
        },
        async load() {
            this.loading = true;
            try {
                this.items = (await api.get("/admin/banners", { params: { type: "marquee" } })).data;
            } finally {
                this.loading = false;
            }
        },
        async quickAdd() {
            const title = this.newText.trim();
            if (!title) return;
            this.adding = true;
            try {
                await api.post("/admin/banners", { type: "marquee", title, is_active: true });
                this.newText = "";
                this.load();
            } finally {
                this.adding = false;
            }
        },
        openForm(a) {
            this.errors = {};
            this.form = { ...a, starts_at: toLocalInput(a.starts_at), ends_at: toLocalInput(a.ends_at) };
            this.dialog = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                await api.put(`/admin/banners/${this.form.id}`, { ...this.form, starts_at: fromLocalInput(this.form.starts_at), ends_at: fromLocalInput(this.form.ends_at) });
                this.dialog = false;
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        async quickToggle(a, value) {
            a.is_active = value;
            try {
                await api.put(`/admin/banners/${a.id}`, { ...a });
            } catch (e) {
                a.is_active = !value;
            }
        },
        async saveOrder() {
            await api.post("/admin/banners/reorder", { ids: this.items.map((a) => a.id) }).catch(this.load);
        },
        askDelete(a) {
            this.deleting = a;
            this.deleteDialog = true;
        },
        async remove() {
            this.removing = true;
            try {
                await api.delete(`/admin/banners/${this.deleting.id}`);
                this.deleteDialog = false;
                this.load();
            } finally {
                this.removing = false;
            }
        },
    },
};
</script>
