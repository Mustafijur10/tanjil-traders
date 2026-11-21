<template>
    <div>
        <PageHeader title="Shipping" subtitle="Delivery options customers choose at checkout, with their charge. Add a free-delivery amount to encourage bigger orders.">
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add delivery option") }}</v-btn-primary>
        </PageHeader>

        <section class="tt-box">
            <div class="tt-box-head">
                <span class="tt-box-icon"><v-icon size="20">mdi-truck-fast-outline</v-icon></span>
                <div class="flex-grow-1">
                    <h2>{{ $t("Delivery options") }}</h2>
                    <p>{{ $t("The first one is selected by default at checkout. Drag to reorder.") }}</p>
                </div>
            </div>

            <div v-if="loading" class="tt-box-body tt-skel-group"><span v-for="n in 2" :key="n" class="tt-skel"></span></div>
            <EmptyState v-else-if="!items.length" icon="mdi-truck-fast-outline" title="No delivery options" text="Without one, orders are delivered free. Most shops add Inside Dhaka and Outside Dhaka.">
                <v-btn-secondary prepend-icon="mdi-plus" @click="addDefaults">{{ $t("Add Inside and Outside Dhaka") }}</v-btn-secondary>
            </EmptyState>

            <VueDraggable v-else v-model="items" handle=".tt-drag" :animation="150" @end="saveOrder">
                <div v-for="m in items" :key="m.id" class="tt-announce-row">
                    <v-icon class="tt-drag" :aria-label="$t('Drag to reorder')">mdi-drag-vertical</v-icon>
                    <div class="tt-announce-text">
                        <b>{{ m.name }}</b>
                        <span class="tt-muted tt-small">
                            <template v-if="m.delivery_time"><v-icon size="13">mdi-clock-outline</v-icon>{{ m.delivery_time }} </template>
                            <template v-if="m.free_over"><v-icon size="13">mdi-gift-outline</v-icon>{{ $t("Free over {amount}", { amount: $money(m.free_over) }) }}</template>
                        </span>
                    </div>
                    <b class="tt-num">{{ $money(m.charge) }}</b>
                    <v-switch-trz :model-value="m.is_active" :aria-label="$t('Offer at checkout')" @update:model-value="quickToggle(m, $event)" />
                    <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(m)" />
                    <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(m)" />
                </div>
            </VueDraggable>
        </section>

        <v-dialog-trz v-model="dialog" max-width="560" :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit delivery option" : "Add delivery option") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model.trim="form.name" class="is-full" :label="$t('Name')" placeholder="Inside Dhaka" :error-messages="errors.name" />
                        <v-input-trz v-model.trim="form.delivery_time" :label="$t('Delivery time (optional)')" placeholder="1-2 days" />
                        <v-input-trz v-model.number="form.charge" type="number" min="0" :prefix="currency" :label="$t('Charge')" :error-messages="errors.charge" />
                        <v-input-trz
                            v-model.number="form.free_over"
                            class="is-full"
                            type="number"
                            min="0"
                            :prefix="currency"
                            :label="$t('Free delivery for orders over (optional)')"
                            :hint="$t('Leave empty to always charge.')"
                            persistent-hint
                        />
                        <v-switch-trz v-model="form.is_active" class="is-full" :label="$t('Offer at checkout')" />
                    </div>
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.name" @click="save">{{ $t("Save") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: deleting?.name || '' })" text="Past orders keep their delivery charge." :loading="saving" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";

export default {
    name: "Shipping",
    data: () => ({ items: [], loading: true, dialog: false, form: {}, errors: {}, saving: false, deleteDialog: false, deleting: null }),
    computed: {
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
    },
    created() {
        this.load();
    },
    methods: {
        async load() {
            try {
                this.items = (await api.get("/admin/shipping-methods")).data;
            } finally {
                this.loading = false;
            }
        },
        body(m) {
            return { name: m.name, delivery_time: m.delivery_time || null, charge: m.charge || 0, free_over: m.free_over || null, is_active: m.is_active };
        },
        openForm(m = null) {
            this.form = m ? { ...m } : { name: "", delivery_time: "", charge: 0, free_over: null, is_active: true };
            this.errors = {};
            this.dialog = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                if (this.form.id) await api.put(`/admin/shipping-methods/${this.form.id}`, this.body(this.form), { quiet: true });
                else await api.post("/admin/shipping-methods", this.body(this.form), { quiet: true });
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Delivery option saved."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        async addDefaults() {
            await api.post("/admin/shipping-methods", { name: "Inside Dhaka", delivery_time: "1-2 days", charge: 70, is_active: true });
            await api.post("/admin/shipping-methods", { name: "Outside Dhaka", delivery_time: "2-4 days", charge: 130, is_active: true });
            this.load();
        },
        async quickToggle(m, value) {
            m.is_active = value;
            await api.put(`/admin/shipping-methods/${m.id}`, this.body(m));
        },
        saveOrder() {
            api.post("/admin/delivery/reorder", { type: "shipping", ids: this.items.map((m) => m.id) });
        },
        askDelete(m) {
            this.deleting = m;
            this.deleteDialog = true;
        },
        async remove() {
            this.saving = true;
            try {
                await api.delete(`/admin/shipping-methods/${this.deleting.id}`);
                this.deleteDialog = false;
                this.load();
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
