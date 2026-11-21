<template>
    <div>
        <PageHeader title="Attributes & Specs" subtitle="The specs your products have, like RAM, Storage or Colour. They drive the product form, the shop filters and product comparison.">
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add spec") }}</v-btn-primary>
        </PageHeader>

        <section class="tt-box">
            <div class="tt-toolbar">
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search specs or values')" clearable :aria-label="$t('Search')" />
                <v-select-trz v-model="group" density="compact" :items="groupOptions" :aria-label="$t('Group')" />
                <div class="tt-toolbar-grow"></div>
                <span class="tt-muted tt-small">{{ $tn(attributes.length, "{n} spec", "{n} specs") }}</span>
            </div>

            <div v-if="loading" class="tt-box-body tt-skel-group"><span v-for="n in 6" :key="n" class="tt-skel"></span></div>

            <EmptyState v-else-if="!attributes.length" icon="mdi-tune-variant" title="No specs yet" text="Add specs such as RAM, Storage or Colour, then pick which categories use them.">
                <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add spec") }}</v-btn-primary>
            </EmptyState>

            <EmptyState v-else-if="!grouped.length" icon="mdi-magnify" title="Nothing matches" text="Try a different search or group." />

            <template v-for="g in grouped" :key="g.name">
                <div class="tt-group-title">{{ g.name ? $t(g.name) : $t("No group") }}</div>
                <div v-for="a in g.items" :key="a.id" class="tt-spec-row">
                    <div class="tt-spec-main">
                        <b>{{ a.name }}<span v-if="a.unit" class="tt-muted"> · {{ a.unit }}</span></b>
                        <div class="tt-spec-flags">
                            <span v-if="a.type === 'text'" class="tt-spec-flag">{{ $t("Free text") }}</span>
                            <span v-if="a.is_filterable" class="tt-spec-flag is-on"><v-icon size="13">mdi-filter-variant</v-icon>{{ $t("Filter") }}</span>
                            <span v-if="a.is_comparable" class="tt-spec-flag is-on"><v-icon size="13">mdi-compare-horizontal</v-icon>{{ $t("Compare") }}</span>
                            <span v-if="a.is_variant" class="tt-spec-flag is-on"><v-icon size="13">mdi-shape-plus-outline</v-icon>{{ $t("Variants") }}</span>
                            <span class="tt-spec-flag">{{ $tn(a.category_ids.length, "{n} category", "{n} categories") }}</span>
                        </div>
                    </div>
                    <div class="tt-spec-values">
                        <span v-for="v in a.values.slice(0, 10)" :key="v.id" class="tt-value-chip" :title="$t('{n} products', { n: $num(v.product_count) })">
                            <i v-if="v.swatch" class="tt-value-swatch" :style="{ '--swatch': v.swatch }"></i>{{ v.value }}
                        </span>
                        <span v-if="a.values.length > 10" class="tt-value-chip is-more">+{{ $num(a.values.length - 10) }}</span>
                        <span v-if="a.type === 'select' && !a.values.length" class="tt-muted tt-small">{{ $t("No values yet") }}</span>
                    </div>
                    <div class="tt-tree-actions">
                        <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(a)" />
                        <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(a)" />
                    </div>
                </div>
            </template>
        </section>

        <v-dialog-trz v-model="dialog" max-width="760" scrollable :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit spec" : "Add spec") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model="form.name" :label="$t('Name')" :placeholder="$t('e.g. RAM')" :error-messages="errors.name" autofocus />
                        <v-combobox-trz
                            v-model="form.group"
                            :items="groups"
                            :label="$t('Group')"
                            :hint="$t('Specs in the same group are shown together, e.g. Memory or Display.')"
                            persistent-hint
                            />
                        <v-select-trz
                            v-model="form.type"
                            :label="$t('Type')"
                            :items="[
                                { value: 'select', title: $t('Choose from a list (can be filtered)') },
                                { value: 'text', title: $t('Free text per product') },
                            ]"
                        />
                        <v-input-trz v-model="form.unit" :label="$t('Unit (optional)')" :placeholder="$t('e.g. GB, inch, mAh')" />
                        <v-switch-trz v-model="form.is_filterable" :disabled="form.type === 'text'" :label="$t('Show as a filter in the shop')" />
                        <v-switch-trz v-model="form.is_comparable" :label="$t('Show in product comparison')" />
                        <v-switch-trz v-model="form.is_variant" :disabled="form.type === 'text'" :label="$t('Can be a product option (variant)')" class="is-full" :hint="$t('e.g. a phone sold in 128GB and 256GB at different prices.')" persistent-hint />
                    </div>

                    <template v-if="form.type === 'select'">
                        <div class="tt-sub-head">
                            <h3>{{ $t("Values") }}</h3>
                            <v-switch-trz v-model="useSwatches" :label="$t('Colour swatches')" />
                        </div>
                        <v-alert v-if="errors.values" type="error" variant="tonal" density="compact" class="mb-3">{{ errors.values }}</v-alert>
                        <VueDraggable v-model="form.values" handle=".tt-drag" :animation="150" class="tt-order-list">
                                <div v-for="(v, index) in form.values" :key="v.key" class="tt-order-item">
                                    <v-icon class="tt-drag" size="18">mdi-drag-vertical</v-icon>
                                    <input v-if="useSwatches" v-model="v.swatch" type="color" class="tt-color-input" :aria-label="$t('Colour')" />
                                    <v-input-trz v-model="v.value" density="compact" :aria-label="$t('Value')" :error-messages="errors[`values.${index}.value`]" />
                                    <span class="tt-muted tt-small tt-nowrap">{{ v.product_count ? $tn(v.product_count, "{n} product", "{n} products") : "" }}</span>
                                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Remove value')" @click="removeValue(index)" />
                                </div>
                        </VueDraggable>
                        <div class="d-flex ga-2 mt-3">
                            <v-input-trz
                                v-model="newValues"
                                density="compact"
                                :placeholder="$t('Add values, separated by commas: 4GB, 8GB, 16GB')"
                                :aria-label="$t('New values')"
                                @keydown.enter.prevent="addValues"
                            />
                            <v-btn-secondary :disabled="!newValues.trim()" @click="addValues">{{ $t("Add") }}</v-btn-secondary>
                        </div>
                        <p v-if="removedInUse" class="tt-warn-text mt-2">
                            <v-icon size="15">mdi-alert-outline</v-icon>
                            {{ $t("Removed values are also removed from {n} products.", { n: $num(removedInUse) }) }}
                        </p>
                    </template>

                    <h3 class="tt-sub-head">{{ $t("Used by categories") }}</h3>
                    <v-autocomplete-trz v-model="form.category_ids" :items="categoryOptions" :label="$t('Categories')" multiple chips closable-chips />
                </v-card-text>
                <v-divider />
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.name" @click="save">{{ $t(form.id ? "Save changes" : "Add spec") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog
            v-model="deleteDialog"
            :title="$t('Delete “{name}”?', { name: deleting?.name || '' })"
            text="It is removed from every product and category that uses it. This cannot be undone."
            :loading="removing"
            @confirm="remove"
        />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";

let keySeq = 0;
const withKey = (v) => ({ ...v, key: ++keySeq });
const blank = () => ({ id: null, name: "", type: "select", unit: "", group: null, is_filterable: true, is_comparable: true, is_variant: false, values: [], category_ids: [] });

export default {
    name: "Attributes",
    data: () => ({
        attributes: [],
        categories: [],
        loading: true,
        search: "",
        group: "",
        dialog: false,
        form: blank(),
        originalValues: [],
        useSwatches: false,
        newValues: "",
        errors: {},
        saving: false,
        deleteDialog: false,
        deleting: null,
        removing: false,
    }),
    computed: {
        groups() {
            return [...new Set(this.attributes.map((a) => a.group).filter(Boolean))].sort();
        },
        groupOptions() {
            return [{ value: "", title: this.$t("All groups") }, ...this.groups.map((g) => ({ value: g, title: this.$t(g) }))];
        },
        grouped() {
            const q = (this.search || "").trim().toLowerCase();
            const list = this.attributes.filter((a) => {
                if (this.group && a.group !== this.group) return false;
                return !q || a.name.toLowerCase().includes(q) || a.values.some((v) => v.value.toLowerCase().includes(q));
            });
            const map = new Map();
            list.forEach((a) => map.set(a.group || "", [...(map.get(a.group || "") || []), a]));
            return [...map.entries()].map(([name, items]) => ({ name, items }));
        },
        categoryOptions() {
            const parents = Object.fromEntries(this.categories.filter((c) => !c.parent_id).map((c) => [c.id, c.name]));
            return this.categories.map((c) => ({ value: c.id, title: c.parent_id ? `${parents[c.parent_id]} › ${c.name}` : c.name }));
        },
        removedInUse() {
            const kept = new Set(this.form.values.map((v) => v.id).filter(Boolean));
            return this.originalValues.filter((v) => !kept.has(v.id)).reduce((n, v) => n + (v.product_count || 0), 0);
        },
    },
    created() {
        this.load();
    },
    methods: {
        async load() {
            this.loading = true;
            try {
                const [attrs, form] = await Promise.all([api.get("/admin/attributes"), api.get("/admin/products/form-data")]);
                this.attributes = attrs.data;
                this.categories = form.data.categories;
            } finally {
                this.loading = false;
            }
        },
        openForm(attr = null) {
            this.errors = {};
            this.newValues = "";
            this.form = attr ? { ...blank(), ...attr, values: attr.values.map(withKey), category_ids: [...attr.category_ids] } : blank();
            this.originalValues = attr ? attr.values : [];
            this.useSwatches = this.form.values.some((v) => v.swatch) || /colou?r/i.test(this.form.name);
            this.dialog = true;
        },
        addValues() {
            const existing = new Set(this.form.values.map((v) => v.value.trim().toLowerCase()));
            this.newValues
                .split(",")
                .map((s) => s.trim())
                .filter((s) => s && !existing.has(s.toLowerCase()))
                .forEach((value) => {
                    existing.add(value.toLowerCase());
                    this.form.values.push(withKey({ id: null, value, swatch: this.useSwatches ? "#888888" : null }));
                });
            this.newValues = "";
        },
        removeValue(i) {
            this.form.values.splice(i, 1);
        },
        async save() {
            this.saving = true;
            this.errors = {};
            const body = {
                ...this.form,
                values: this.form.type === "select" ? this.form.values.map(({ id, value, swatch }) => ({ id, value, swatch: this.useSwatches ? swatch : null })) : [],
            };
            try {
                if (this.form.id) await api.put(`/admin/attributes/${this.form.id}`, body);
                else await api.post("/admin/attributes", body);
                this.dialog = false;
                this.$store.dispatch("notify", this.$t(this.form.id ? "Spec saved." : "Spec added."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        askDelete(attr) {
            this.deleting = attr;
            this.deleteDialog = true;
        },
        async remove() {
            this.removing = true;
            try {
                await api.delete(`/admin/attributes/${this.deleting.id}`);
                this.deleteDialog = false;
                this.$store.dispatch("notify", this.$t("Spec deleted."));
                this.load();
            } finally {
                this.removing = false;
            }
        },
    },
};
</script>
