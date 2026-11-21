<template>
    <div>
        <PageHeader title="Couriers" subtitle="Delivery companies you hand parcels to. With a tracking link, customers can follow their parcel from the order page.">
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("Add courier") }}</v-btn-primary>
        </PageHeader>

        <section class="tt-box">
            <div class="tt-box-head">
                <span class="tt-box-icon"><v-icon size="20">mdi-truck-outline</v-icon></span>
                <div class="flex-grow-1">
                    <h2>{{ $tn(items.length, "{n} courier", "{n} couriers") }}</h2>
                    <p>{{ $t("Drag to set the order they appear in when shipping.") }}</p>
                </div>
            </div>

            <div v-if="loading" class="tt-box-body tt-skel-group"><span v-for="n in 3" :key="n" class="tt-skel"></span></div>
            <EmptyState v-else-if="!items.length" icon="mdi-truck-outline" title="No couriers yet" text="Add the companies you use, like Pathao, Steadfast, RedX or your own delivery person.">
                <div class="tt-chip-row">
                    <v-btn-secondary v-for="c in suggestions" :key="c.name" size="small" prepend-icon="mdi-plus" @click="openForm(null, c)">{{ c.name }}</v-btn-secondary>
                </div>
            </EmptyState>

            <VueDraggable v-else v-model="items" handle=".tt-drag" :animation="150" @end="saveOrder">
                <div v-for="c in items" :key="c.id" class="tt-announce-row">
                    <v-icon class="tt-drag" :aria-label="$t('Drag to reorder')">mdi-drag-vertical</v-icon>
                    <div class="tt-announce-text">
                        <b>{{ c.name }}</b>
                        <span class="tt-muted tt-small">
                            <template v-if="c.phone"><v-icon size="13">mdi-phone-outline</v-icon>{{ c.phone }} </template>
                            <template v-if="c.tracking_url"><v-icon size="13">mdi-link-variant</v-icon>{{ $t("Tracking link set") }}</template>
                            <template v-else>{{ $t("No tracking link") }}</template>
                        </span>
                    </div>
                    <span class="tt-courier-count tt-small">
                        <b class="tt-num">{{ $num(c.transit) }}</b> {{ $t("on the way") }}<br />
                        <span class="tt-muted">{{ $tn(c.orders, "{n} order", "{n} orders") }}</span>
                    </span>
                    <v-switch-trz :model-value="c.is_active" :aria-label="$t('In use')" @update:model-value="quickToggle(c, $event)" />
                    <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(c)" />
                    <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(c)" />
                </div>
            </VueDraggable>
        </section>

        <v-dialog-trz v-model="dialog" max-width="560" :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit courier" : "Add courier") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model.trim="form.name" :label="$t('Name')" :error-messages="errors.name" />
                        <v-input-trz v-model.trim="form.phone" :label="$t('Phone (optional)')" />
                        <v-input-trz
                            v-model.trim="form.tracking_url"
                            class="is-full"
                            :label="$t('Tracking link (optional)')"
                            placeholder="https://steadfast.com.bd/t/{code}"
                            :hint="$t('Write {code} where the tracking code goes. Customers get a Track parcel button.')"
                            persistent-hint
                            :error-messages="errors.tracking_url"
                        />
                        <v-switch-trz v-model="form.is_active" class="is-full" :label="$t('In use')" />
                    </div>
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.name" @click="save">{{ $t("Save") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: deleting?.name || '' })" text="Orders sent with this courier keep working, but lose the courier name and tracking link." :loading="saving" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";

// Common Bangladeshi couriers, to add with one click
const SUGGESTIONS = [
    { name: "Steadfast", tracking_url: "https://steadfast.com.bd/t/{code}" },
    { name: "Pathao", tracking_url: "https://merchant.pathao.com/tracking?consignment_id={code}" },
    { name: "RedX", tracking_url: "https://redx.com.bd/track-parcel/?trackingId={code}" },
    { name: "Sundarban Courier", tracking_url: "" },
    { name: "Own delivery", tracking_url: "" },
];

export default {
    name: "Couriers",
    data: () => ({
        items: [],
        loading: true,
        dialog: false,
        form: {},
        errors: {},
        saving: false,
        deleteDialog: false,
        deleting: null,
        suggestions: SUGGESTIONS,
    }),
    created() {
        this.load();
    },
    methods: {
        async load() {
            try {
                this.items = (await api.get("/admin/couriers")).data;
            } finally {
                this.loading = false;
            }
        },
        openForm(c = null, preset = {}) {
            this.form = c ? { ...c } : { name: "", phone: "", tracking_url: "", is_active: true, ...preset };
            this.errors = {};
            this.dialog = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const body = { name: this.form.name, phone: this.form.phone || null, tracking_url: this.form.tracking_url || null, is_active: this.form.is_active };
                if (this.form.id) await api.put(`/admin/couriers/${this.form.id}`, body, { quiet: true });
                else await api.post("/admin/couriers", body, { quiet: true });
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Courier saved."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        async quickToggle(c, value) {
            c.is_active = value;
            await api.put(`/admin/couriers/${c.id}`, { name: c.name, phone: c.phone, tracking_url: c.tracking_url, is_active: value });
        },
        saveOrder() {
            api.post("/admin/delivery/reorder", { type: "couriers", ids: this.items.map((c) => c.id) });
        },
        askDelete(c) {
            this.deleting = c;
            this.deleteDialog = true;
        },
        async remove() {
            this.saving = true;
            try {
                await api.delete(`/admin/couriers/${this.deleting.id}`);
                this.deleteDialog = false;
                this.$store.dispatch("notify", this.$t("Courier deleted."));
                this.load();
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
