<template>
    <div>
        <PageHeader title="Flash Sales" subtitle="Put a group of products on sale for a set time. Prices change by themselves when it starts and ends, with a countdown on the website.">
            <v-btn-secondary prepend-icon="mdi-open-in-new" href="/deals" target="_blank">{{ $t("Deals page") }}</v-btn-secondary>
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("New flash sale") }}</v-btn-primary>
        </PageHeader>

        <div v-if="loading" class="tt-box tt-box-body tt-skel-group"><span v-for="n in 3" :key="n" class="tt-skel"></span></div>
        <section v-else-if="!sales.length" class="tt-box">
            <EmptyState icon="mdi-lightning-bolt-outline" title="No flash sales yet" text="Pick products, a discount and a time. Scheduled sales start on their own.">
                <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t("New flash sale") }}</v-btn-primary>
            </EmptyState>
        </section>

        <div v-else class="tt-flash-grid">
            <article v-for="s in sales" :key="s.id" class="tt-flash-card" :class="'is-' + s.state">
                <div class="tt-flash-top">
                    <StatusChip :label="$t(states[s.state].label)" :tone="states[s.state].tone" />
                    <span class="tt-flash-off">{{ s.discount_type === "percent" ? `−${$num(s.discount_value)}%` : `−${$money(s.discount_value)}` }}</span>
                </div>
                <h3>{{ s.name }}</h3>
                <p class="tt-muted tt-small"><v-icon size="15">mdi-calendar-range</v-icon> {{ range(s) }}</p>
                <p v-if="s.state === 'live'" class="tt-flash-left"><v-icon size="16">mdi-timer-outline</v-icon>{{ $t("Ends in {time}", { time: left(s.ends_at) }) }}</p>
                <p v-else-if="s.state === 'scheduled'" class="tt-flash-left is-soon"><v-icon size="16">mdi-clock-start</v-icon>{{ $t("Starts in {time}", { time: left(s.starts_at) }) }}</p>
                <div class="tt-flash-stats">
                    <div><small>{{ $t("Products") }}</small><b>{{ $num(s.products_count) }}</b></div>
                    <div><small>{{ $t("Units sold") }}</small><b>{{ $num(s.units) }}</b></div>
                    <div><small>{{ $t("Sales") }}</small><b>{{ $money(s.revenue) }}</b></div>
                </div>
                <div class="tt-flash-actions">
                    <v-btn-secondary size="small" prepend-icon="mdi-pencil-outline" @click="openForm(s)">{{ $t("Edit") }}</v-btn-secondary>
                    <v-btn-tertiary size="small" icon="mdi-trash-can-outline" :aria-label="$t('Delete')" @click="askDelete(s)" />
                </div>
            </article>
        </div>

        <v-dialog-trz v-model="dialog" max-width="760" :persistent="saving" scrollable>
            <v-card>
                <div class="tt-box-head">
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit flash sale" : "New flash sale") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <v-input-trz v-model.trim="form.name" class="is-full" :label="$t('Name (for you)')" placeholder="Eid flash sale" :error-messages="errors.name" />
                        <div class="tt-segments is-full" role="tablist">
                            <button type="button" role="tab" :class="{ 'is-on': form.discount_type === 'percent' }" @click="form.discount_type = 'percent'">{{ $t("Percent off") }}</button>
                            <button type="button" role="tab" :class="{ 'is-on': form.discount_type === 'fixed' }" @click="form.discount_type = 'fixed'">{{ $t("Amount off") }}</button>
                        </div>
                        <v-input-trz
                            v-model.number="form.discount_value"
                            type="number"
                            min="0"
                            :label="$t('Discount')"
                            :suffix="form.discount_type === 'percent' ? '%' : undefined"
                            :prefix="form.discount_type === 'fixed' ? currency : undefined"
                            :error-messages="errors.discount_value"
                        />
                        <v-switch-trz v-model="form.is_active" :label="$t('Turned on')" />
                        <v-input-trz v-model="form.starts_at" type="datetime-local" :label="$t('Starts')" :error-messages="errors.starts_at" />
                        <v-input-trz v-model="form.ends_at" type="datetime-local" :label="$t('Ends')" :error-messages="errors.ends_at" />
                    </div>

                    <h3 class="tt-card-title mt-6">{{ $tn(picked.length, "{n} product", "{n} products") }}</h3>
                    <v-autocomplete-trz
                        v-model="pickedNow"
                        v-model:search="search"
                        :items="results"
                        item-title="name"
                        return-object
                        :loading="searching"
                        no-filter
                        clearable
                        prepend-inner-icon="mdi-magnify"
                        :placeholder="$t('Add products…')"
                        :error-messages="errors.product_ids"
                        @update:model-value="add"
                    />
                    <ul v-if="picked.length" class="tt-flash-products">
                        <li v-for="(p, i) in picked" :key="p.id">
                            <span class="tt-thumb"><img v-if="p.image" :src="url(p.image)" alt="" /><v-icon v-else size="16">mdi-image-outline</v-icon></span>
                            <span class="tt-ellipsis">{{ p.name }}<small v-if="p.variants" class="tt-muted"> · {{ $tn(p.variants, "{n} option", "{n} options") }}</small></span>
                            <span class="tt-num tt-muted"><s>{{ $money(p.price) }}</s></span>
                            <b class="tt-num">{{ $money(salePrice(p.price)) }}</b>
                            <v-btn-tertiary icon="mdi-close" size="x-small" :aria-label="$t('Remove')" @click="picked.splice(i, 1)" />
                        </li>
                    </ul>
                    <p v-if="picked.some((p) => p.variants)" class="tt-muted tt-small mt-2">{{ $t("Products with options get the same discount on every option.") }}</p>
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.name || !form.discount_value || !picked.length" @click="save">{{ $t("Save flash sale") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" :title="$t('Delete “{name}”?', { name: deleting?.name || '' })" text="Its products go back to their normal price straight away." :loading="saving" @confirm="remove" />
    </div>
</template>

<script>
import api, { fileUrl, fieldErrors } from "@/api";
import { toLocalInput, fromLocalInput } from "@/utils/datetime";

const STATES = {
    live: { label: "Live now", tone: "green" },
    scheduled: { label: "Scheduled", tone: "blue" },
    paused: { label: "Turned off", tone: "grey" },
    ended: { label: "Ended", tone: "red" },
};

export default {
    name: "FlashSales",
    data: () => ({
        sales: [],
        loading: true,
        dialog: false,
        form: {},
        picked: [],
        pickedNow: null,
        search: "",
        results: [],
        searching: false,
        timer: null,
        errors: {},
        saving: false,
        deleteDialog: false,
        deleting: null,
        states: STATES,
        now: Date.now(),
        clock: null,
    }),
    computed: {
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
    },
    watch: {
        search(q) {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.find(q), 250);
        },
    },
    created() {
        this.load();
        this.clock = setInterval(() => (this.now = Date.now()), 30000);
    },
    beforeUnmount() {
        clearInterval(this.clock);
    },
    methods: {
        url: fileUrl,
        async load() {
            try {
                this.sales = (await api.get("/admin/flash-sales")).data;
            } finally {
                this.loading = false;
            }
        },
        range(s) {
            const f = (d) => this.$date(d, { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" });
            return `${f(s.starts_at)} – ${f(s.ends_at)}`;
        },
        left(to) {
            const mins = Math.max(0, Math.round((new Date(to).getTime() - this.now) / 60000));
            const d = Math.floor(mins / 1440);
            const h = Math.floor((mins % 1440) / 60);
            const m = mins % 60;
            return [d && this.$tn(d, "{n} day", "{n} days"), h && `${this.$num(h)}h`, !d && `${this.$num(m)}m`].filter(Boolean).join(" ");
        },
        salePrice(price) {
            const v = Number(this.form.discount_value) || 0;
            const p = this.form.discount_type === "percent" ? price * (1 - v / 100) : price - v;
            return Math.max(0, Math.round(p));
        },
        async find(q) {
            this.searching = true;
            try {
                const { data } = await api.get("/admin/orders/products", { params: { q: q || undefined } });
                this.results = data.map((p) => ({ id: p.id, name: p.name, image: p.image, price: p.variants.length ? Math.min(...p.variants.map((v) => v.price)) : p.price, variants: p.variants.length }));
            } finally {
                this.searching = false;
            }
        },
        add(p) {
            if (p && !this.picked.some((x) => x.id === p.id)) this.picked.push(p);
            this.errors.product_ids = null;
            this.$nextTick(() => {
                this.pickedNow = null;
                this.search = "";
            });
        },
        async openForm(s = null) {
            this.errors = {};
            this.picked = [];
            if (s) {
                this.form = { ...s, starts_at: toLocalInput(s.starts_at), ends_at: toLocalInput(s.ends_at) };
                const { data } = await api.get(`/admin/flash-sales/${s.id}`);
                this.picked = data.products.map((p) => ({ id: p.id, name: p.name, image: p.images?.[0]?.path, price: p.price }));
            } else {
                const start = new Date(Date.now() + 3600e3);
                start.setMinutes(0, 0, 0);
                this.form = { name: "", discount_type: "percent", discount_value: 10, starts_at: toLocalInput(start.toISOString()), ends_at: toLocalInput(new Date(start.getTime() + 2 * 86400e3).toISOString()), is_active: true };
            }
            this.find("");
            this.dialog = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            const f = this.form;
            const body = {
                name: f.name, discount_type: f.discount_type, discount_value: f.discount_value, is_active: f.is_active,
                starts_at: fromLocalInput(f.starts_at), ends_at: fromLocalInput(f.ends_at), product_ids: this.picked.map((p) => p.id),
            };
            try {
                if (f.id) await api.put(`/admin/flash-sales/${f.id}`, body, { quiet: true });
                else await api.post("/admin/flash-sales", body, { quiet: true });
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Flash sale saved. Prices are updated."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        askDelete(s) {
            this.deleting = s;
            this.deleteDialog = true;
        },
        async remove() {
            this.saving = true;
            try {
                await api.delete(`/admin/flash-sales/${this.deleting.id}`);
                this.deleteDialog = false;
                this.load();
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
