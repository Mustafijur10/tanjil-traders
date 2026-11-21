<template>
    <div>
        <PageHeader :title="section.title" :subtitle="section.subtitle" />

            <div class="tt-vstack">
                <template v-if="loading">
                    <div class="tt-box tt-box-body tt-skel-group">
                        <span v-for="n in 4" :key="n" class="tt-skel"></span>
                    </div>
                </template>

                <template v-else>
                    <section v-for="panel in section.panels" :key="panel.title" class="tt-box">
                        <div class="tt-box-head">
                            <div>
                                <h2>{{ $t(panel.title) }}</h2>
                                <p v-if="panel.subtitle">{{ $t(panel.subtitle) }}</p>
                            </div>
                        </div>

                        <template v-for="f in panel.fields" :key="f.key">
                            <div v-if="!f.showIf || form[f.showIf]" class="tt-setting-row" :class="{ 'is-switch': f.type === 'switch' }">
                                <div class="tt-setting-label">
                                    <b>{{ $t(f.label) }}</b>
                                    <span v-if="f.hint">{{ $t(f.hint) }}</span>
                                </div>

                                <div class="tt-setting-control">
                                    <v-switch-trz v-if="f.type === 'switch'" v-model="form[f.key]" :aria-label="$t(f.label)" />

                                    <v-select-trz
                                        v-else-if="f.type === 'select'"
                                        v-model="form[f.key]"
                                        :items="f.options.map((o) => ({ ...o, title: $t(o.title) }))"
                                        :aria-label="$t(f.label)"
                                    />

                                    <div v-else-if="f.type === 'swatches'" class="d-flex flex-wrap ga-2" role="radiogroup" :aria-label="$t(f.label)">
                                        <v-btn-secondary
                                            v-for="o in f.options"
                                            :key="o.value"
                                            :active="form[f.key] === o.value"
                                            :color="form[f.key] === o.value ? 'primary' : undefined"
                                            size="small"
                                            role="radio"
                                            :aria-checked="form[f.key] === o.value"
                                            @click="form[f.key] = o.value"
                                        >
                                            <v-icon start :color="o.hex">mdi-circle</v-icon>{{ $t(o.label) }}
                                        </v-btn-secondary>
                                    </div>

                                    <ImageUpload v-else-if="f.type === 'image'" v-model="form[f.key]" folder="store" :label="f.label" :wide="f.wide" />

                                    <template v-else-if="f.type === 'secret'">
                                        <v-alert v-if="form[f.key] === '__set__'" density="compact" variant="tonal" color="primary" class="mb-2">
                                            <div class="d-flex align-center ga-2">
                                                <span class="flex-grow-1">{{ $t("A key is saved.") }}</span>
                                                <v-btn-tertiary size="small" @click="form[f.key] = ''">{{ $t("Replace") }}</v-btn-tertiary>
                                            </div>
                                        </v-alert>
                                        <v-textarea-trz
                                            v-else-if="f.multiline"
                                            v-model="form[f.key]"
                                            autocomplete="off"
                                            :placeholder="$t('Paste the key here')"
                                            :aria-label="$t(f.label)"
                                        />
                                        <v-input-trz
                                            v-else
                                            v-model="form[f.key]"
                                            type="password"
                                            autocomplete="off"
                                            :placeholder="$t('Paste the key here')"
                                            :aria-label="$t(f.label)"
                                        />
                                    </template>

                                    <v-textarea-trz
                                        v-else-if="f.type === 'textarea'"
                                        v-model="form[f.key]"
                                        :counter="f.counter"
                                        :aria-label="$t(f.label)"
                                    />

                                    <v-input-trz
                                        v-else
                                        v-model="form[f.key]"
                                        :type="f.type === 'number' ? 'number' : 'text'"
                                        :prepend-inner-icon="f.icon"
                                        :suffix="f.suffix ? $t(f.suffix) : undefined"
                                        :counter="f.counter"
                                        :aria-label="$t(f.label)"
                                    />
                                </div>
                            </div>
                        </template>
                    </section>

                    <div v-if="changedKeys.length" class="tt-save-bar" role="region" :aria-label="$t('Unsaved changes')">
                        <span>{{ $t("You have unsaved changes.") }}</span>
                        <v-btn-tertiary color="white" :disabled="saving" @click="reset">{{ $t("Discard") }}</v-btn-tertiary>
                        <v-btn-primary :loading="saving" @click="save">{{ $t("Save changes") }}</v-btn-primary>
                    </div>
                </template>
            </div>
    </div>
</template>

<script>
/*
 * Every "simple" settings page (Store Information, Appearance & Language, Payments, Integrations & SEO).
 * The route's meta.section picks the field list from schema.js.
 */
import api from "@/api";
import schema from "./schema";

export default {
    name: "SettingsPage",

    data: () => ({
        loading: true,
        saving: false,
        saved: {},
        form: {},
    }),
    computed: {
        section() {
            return schema[this.$route.meta.section] || schema.store;
        },
        keys() {
            return this.section.panels.flatMap((p) => p.fields.map((f) => f.key));
        },
        changedKeys() {
            return this.keys.filter((k) => JSON.stringify(this.form[k]) !== JSON.stringify(this.saved[k]));
        },
    },
    watch: {
        "$route.meta.section"() {
            this.reset();
        },
    },
    async created() {
        try {
            const { data } = await api.get("/admin/settings");
            this.saved = data;
            this.reset();
        } finally {
            this.loading = false;
        }
        window.addEventListener("beforeunload", this.warnUnsaved);
    },
    beforeUnmount() {
        window.removeEventListener("beforeunload", this.warnUnsaved);
    },
    beforeRouteLeave() {
        return this.confirmLeave();
    },
    // Moving between two settings pages reuses this component.
    beforeRouteUpdate() {
        return this.confirmLeave();
    },
    methods: {
        confirmLeave() {
            return !this.changedKeys.length || window.confirm(this.$t("You have unsaved changes. Leave without saving?"));
        },
        reset() {
            this.form = Object.fromEntries(this.keys.map((k) => [k, this.saved[k] ?? null]));
        },
        warnUnsaved(e) {
            if (this.changedKeys.length) {
                e.preventDefault();
                e.returnValue = "";
            }
        },
        async save() {
            const fields = this.section.panels.flatMap((p) => p.fields);
            const missing = fields.find((f) => f.required && !String(this.form[f.key] ?? "").trim());
            if (missing) {
                this.$store.dispatch("notify", { text: this.$t("{field} is required.", { field: this.$t(missing.label) }), type: "error" });
                return;
            }

            const values = Object.fromEntries(
                this.changedKeys.map((k) => {
                    const field = fields.find((f) => f.key === k);
                    return [k, field.type === "number" ? Number(this.form[k] || 0) : this.form[k]];
                })
            );

            this.saving = true;
            try {
                const { data } = await api.put("/admin/settings", { values });
                this.saved = data;
                this.reset();
                this.$store.dispatch("prefs/refreshDictionary");
                this.$store.dispatch("notify", this.$t("Settings saved."));
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
