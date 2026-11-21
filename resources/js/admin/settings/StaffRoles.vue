<template>
    <div>
        <PageHeader title="Staff & Roles" subtitle="Who can sign in to the admin, and which parts each staff member can open. Admins can open everything, including settings.">
            <v-btn-primary prepend-icon="mdi-account-plus-outline" @click="openForm()">{{ $t("Add staff") }}</v-btn-primary>
        </PageHeader>

        <section class="tt-box">
            <div v-if="loading" class="tt-box-body tt-skel-group"><span v-for="n in 3" :key="n" class="tt-skel"></span></div>
            <ul v-else class="tt-staff-list">
                <li v-for="u in items" :key="u.id" :class="{ 'is-off': !u.is_active }">
                    <span class="tt-initials">{{ initials(u.name) }}</span>
                    <div class="tt-staff-main">
                        <b>{{ u.name }} <small v-if="u.id === me" class="tt-muted">({{ $t("you") }})</small></b>
                        <span class="tt-muted tt-small">{{ [u.email, u.phone].filter(Boolean).join(" · ") }}</span>
                        <div class="tt-staff-areas">
                            <template v-if="u.role === 'admin'"><span class="tt-area-chip is-all">{{ $t("Everything, including settings") }}</span></template>
                            <template v-else-if="u.permissions === null"><span class="tt-area-chip is-all">{{ $t("All areas except settings") }}</span></template>
                            <template v-else-if="!u.permissions.length"><span class="tt-area-chip is-none">{{ $t("No areas yet") }}</span></template>
                            <span v-for="a in u.permissions || []" v-else :key="a" class="tt-area-chip">{{ $t(areaInfo[a]?.label || a) }}</span>
                        </div>
                    </div>
                    <div class="tt-staff-side">
                        <StatusChip :label="$t(u.role === 'admin' ? 'Admin' : 'Staff')" :tone="u.role === 'admin' ? 'green' : 'blue'" />
                        <StatusChip v-if="!u.is_active" :label="$t('Disabled')" tone="red" />
                        <small class="tt-muted">{{ u.last_seen_at ? $t("Active {date}", { date: $date(u.last_seen_at, { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }) }) : $t("Never signed in") }}</small>
                    </div>
                    <div class="tt-staff-actions">
                        <v-btn-tertiary icon="mdi-pencil-outline" size="small" :aria-label="$t('Edit')" @click="openForm(u)" />
                        <v-btn-tertiary v-if="u.id !== me" icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(u)" />
                    </div>
                </li>
            </ul>
        </section>

        <v-dialog-trz v-model="dialog" max-width="680" :persistent="saving" scrollable>
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit staff member" : "Add staff member") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model.trim="form.name" class="is-full" :label="$t('Name')" :error-messages="errors.name" />
                        <v-input-trz v-model.trim="form.email" type="email" :label="$t('Email (to sign in)')" :error-messages="errors.email" />
                        <v-input-trz v-model.trim="form.phone" :label="$t('Phone (can also sign in)')" :error-messages="errors.phone" />
                        <v-input-trz
                            v-model="form.password"
                            class="is-full"
                            type="password"
                            autocomplete="new-password"
                            :label="$t(form.id ? 'New password (leave empty to keep)' : 'Password')"
                            :hint="$t('At least 8 characters. Share it with them privately.')"
                            persistent-hint
                            :error-messages="errors.password"
                        />
                    </div>

                    <h3 class="tt-card-title mt-6">{{ $t("Role") }}</h3>
                    <div class="tt-choice-list">
                        <label class="tt-choice" :class="{ 'is-on': form.role === 'staff' }">
                            <input v-model="form.role" type="radio" value="staff" :disabled="form.id === me" />
                            <span class="tt-choice-body"><b>{{ $t("Staff") }}</b><small>{{ $t("Only the areas you tick below. Cannot change settings.") }}</small></span>
                        </label>
                        <label class="tt-choice" :class="{ 'is-on': form.role === 'admin' }">
                            <input v-model="form.role" type="radio" value="admin" />
                            <span class="tt-choice-body"><b>{{ $t("Admin") }}</b><small>{{ $t("Everything, including settings, payments and staff.") }}</small></span>
                        </label>
                    </div>
                    <p v-if="errors.role" class="tt-field-error">{{ errors.role }}</p>

                    <template v-if="form.role === 'staff'">
                        <h3 class="tt-card-title mt-6">{{ $t("Can open") }}</h3>
                        <div class="tt-area-grid">
                            <label v-for="a in areas" :key="a" class="tt-area-option" :class="{ 'is-on': form.permissions.includes(a) }">
                                <input v-model="form.permissions" type="checkbox" :value="a" />
                                <v-icon size="20">{{ areaInfo[a].icon }}</v-icon>
                                <span><b>{{ $t(areaInfo[a].label) }}</b><small>{{ $t(areaInfo[a].hint) }}</small></span>
                            </label>
                        </div>
                        <p class="tt-muted tt-small mt-3">{{ $t("Every staff member can see the dashboard and help with Bangla translations.") }}</p>
                    </template>

                    <v-switch-trz v-if="form.id !== me" v-model="form.is_active" class="mt-4" :label="$t('Can sign in')" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.name || !form.email" @click="save">{{ $t("Save") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete {name}?', { name: deleting?.name || '' })" text="They are signed out and can no longer open the admin. Orders and notes they made stay." :loading="saving" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";

export const AREAS = {
    orders: { label: "Orders", icon: "mdi-receipt-text-outline", hint: "Orders, returns, couriers" },
    catalog: { label: "Catalog", icon: "mdi-package-variant-closed", hint: "Products, categories, brands, reviews" },
    inventory: { label: "Inventory", icon: "mdi-warehouse", hint: "Stock and adjustments" },
    customers: { label: "Customers", icon: "mdi-account-group-outline", hint: "Customer accounts" },
    promotions: { label: "Promotions", icon: "mdi-ticket-percent-outline", hint: "Coupons and flash sales" },
    content: { label: "Website content", icon: "mdi-web", hint: "Banners, pages, messages" },
    analytics: { label: "Analytics", icon: "mdi-chart-box-outline", hint: "Sales, traffic and reports" },
};

export default {
    name: "StaffRoles",
    data: () => ({ items: [], areas: [], loading: true, dialog: false, form: {}, errors: {}, saving: false, deleteDialog: false, deleting: null, areaInfo: AREAS }),
    computed: {
        me() {
            return this.$store.state.auth.user?.id;
        },
    },
    created() {
        this.load();
    },
    methods: {
        initials: (n) => (n || "?").split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase(),
        async load() {
            try {
                const { data } = await api.get("/admin/staff");
                this.items = data.items;
                this.areas = data.areas;
            } finally {
                this.loading = false;
            }
        },
        openForm(u = null) {
            this.form = u
                ? { ...u, password: "", permissions: u.permissions === null ? [...this.areas] : [...u.permissions] }
                : { name: "", email: "", phone: "", password: "", role: "staff", permissions: ["orders", "catalog", "inventory", "customers"], is_active: true };
            this.errors = {};
            this.dialog = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            const f = this.form;
            const body = { name: f.name, email: f.email, phone: f.phone || null, role: f.role, permissions: f.permissions, is_active: f.is_active, password: f.password || null };
            try {
                if (f.id) await api.put(`/admin/staff/${f.id}`, body, { quiet: true });
                else await api.post("/admin/staff", body, { quiet: true });
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Saved."));
                if (f.id === this.me) this.$store.dispatch("auth/fetchMe");
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        askDelete(u) {
            this.deleting = u;
            this.deleteDialog = true;
        },
        async remove() {
            this.saving = true;
            try {
                await api.delete(`/admin/staff/${this.deleting.id}`);
                this.deleteDialog = false;
                this.load();
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
