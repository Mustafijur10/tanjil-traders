<template>
    <div>
        <PageHeader title="Carousel & Banners" subtitle="The sliding images at the top of the home page and the promo banners between sections. Drag to change their order.">
            <v-btn-secondary prepend-icon="mdi-open-in-new" href="/" target="_blank">{{ $t("View home page") }}</v-btn-secondary>
            <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t(type === "hero" ? "Add slide" : "Add banner") }}</v-btn-primary>
        </PageHeader>

        <div class="tt-toolbar tt-toolbar-bare">
            <div class="tt-segments" role="tablist" :aria-label="$t('Banner type')">
                <button v-for="t in types" :key="t.value" type="button" role="tab" :class="{ 'is-on': type === t.value }" :aria-selected="type === t.value" @click="setType(t.value)">
                    <v-icon size="16">{{ t.icon }}</v-icon>{{ $t(t.label) }}
                </button>
            </div>
            <span class="tt-muted tt-small">{{ $t(typeInfo.hint) }}</span>
        </div>

        <div v-if="loading" class="tt-banner-grid">
            <div v-for="n in 3" :key="n" class="tt-banner-card tt-skel-group"><span class="tt-skel"></span><span class="tt-skel"></span></div>
        </div>

        <section v-else-if="!banners.length" class="tt-box">
            <EmptyState :icon="typeInfo.icon" :title="type === 'hero' ? 'No slides yet' : 'No promo banners yet'" :text="typeInfo.empty">
                <v-btn-primary prepend-icon="mdi-plus" @click="openForm()">{{ $t(type === "hero" ? "Add slide" : "Add banner") }}</v-btn-primary>
            </EmptyState>
        </section>

        <VueDraggable v-else v-model="banners" handle=".tt-banner-img" :animation="150" class="tt-banner-grid" @end="saveOrder">
            <article v-for="(b, i) in banners" :key="b.id" class="tt-banner-card" :class="{ 'is-off': state(b) !== 'live' }">
                <div class="tt-banner-img" :class="'is-' + type" :title="$t('Drag to reorder')">
                    <img :src="url(b.image)" :alt="b.title || ''" loading="lazy" />
                    <span class="tt-banner-pos">{{ $num(i + 1) }}</span>
                    <StatusChip class="tt-banner-state" :label="schedule[state(b)].label" :tone="schedule[state(b)].tone" />
                </div>
                <div class="tt-banner-body">
                    <b class="tt-ellipsis">{{ b.title || $t("No title") }}</b>
                    <span class="tt-muted tt-small tt-ellipsis">{{ b.subtitle || "" }}</span>
                    <span class="tt-banner-meta">
                        <span v-if="b.link" class="tt-ellipsis"><v-icon size="14">mdi-link-variant</v-icon>{{ b.link }}</span>
                        <span v-if="b.starts_at || b.ends_at"><v-icon size="14">mdi-calendar-range</v-icon>{{ range(b) }}</span>
                    </span>
                </div>
                <div class="tt-banner-foot">
                    <v-switch-trz :model-value="b.is_active" :aria-label="$t('Show on the website')" @update:model-value="quickToggle(b, $event)" />
                    <v-btn-tertiary size="small" prepend-icon="mdi-pencil-outline" @click="openForm(b)">{{ $t("Edit") }}</v-btn-tertiary>
                    <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(b)" />
                </div>
            </article>
        </VueDraggable>

        <v-dialog-trz v-model="dialog" max-width="820" scrollable :persistent="saving">
            <v-card>
                <div class="tt-box-head">
                    <span class="tt-box-ico"><v-icon size="18">{{ typeInfo.icon }}</v-icon></span>
                    <h2 class="flex-grow-1">{{ $t(form.id ? "Edit banner" : type === "hero" ? "Add slide" : "Add banner") }}</h2>
                    <v-btn-tertiary icon="mdi-close" size="small" :aria-label="$t('Close')" @click="dialog = false" />
                </div>
                <v-card-text class="pa-5">
                    <div class="tt-form-grid">
                        <div class="is-full">
                            <ImageUpload v-model="form.image" folder="banners" :label="type === 'hero' ? 'Slide image (desktop)' : 'Banner image'" :hint="typeInfo.size" wide />
                            <p v-if="errors.image" class="tt-error-text">{{ errors.image }}</p>
                        </div>
                        <div v-if="type === 'hero'" class="is-full">
                            <ImageUpload v-model="form.mobile_image" folder="banners" label="Slide image for phones (optional)" hint="Square or tall, e.g. 800 × 800. Without it, the desktop image is used." />
                        </div>
                        <v-input-trz v-model="form.title" :label="$t('Heading (optional)')" :hint="$t('Shown on the image. Leave empty if the image already has text.')" persistent-hint />
                        <v-input-trz v-model="form.subtitle" :label="$t('Small text (optional)')" />
                        <v-input-trz v-model="form.button_text" :label="$t('Button text (optional)')" :placeholder="$t('e.g. Shop now')" />
                        <v-input-trz
                            v-model="form.link"
                            :label="$t('Link')"
                            :placeholder="'/category/laptops'"
                            :hint="$t('Where a click goes: a page on your site (/category/laptops) or a full https:// link.')"
                            persistent-hint
                            :error-messages="errors.link"
                        />
                        <v-input-trz v-model="form.starts_at" type="datetime-local" :label="$t('Show from (optional)')" :error-messages="errors.starts_at" />
                        <v-input-trz v-model="form.ends_at" type="datetime-local" :label="$t('Show until (optional)')" :error-messages="errors.ends_at" />
                        <v-switch-trz v-model="form.is_active" class="is-full" :label="$t('Show on the website')" />
                    </div>
                </v-card-text>
                <v-divider />
                <div class="tt-box-foot">
                    <v-btn-tertiary :disabled="saving" @click="dialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="saving" :disabled="!form.image" @click="save">{{ $t(form.id ? "Save changes" : "Add") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" title="Delete this banner?" text="It is removed from the website. The image file stays in storage." :loading="removing" @confirm="remove" />
    </div>
</template>

<script>
import api, { fieldErrors, fileUrl } from "@/api";
import { toLocalInput, fromLocalInput, scheduleState, SCHEDULE_LABELS } from "@/utils/datetime";

const TYPES = [
    { value: "hero", label: "Home carousel", icon: "mdi-view-carousel-outline", size: "Wide image, 1600 × 560 pixels works best.", hint: "Slides rotate every few seconds at the top of the home page.", empty: "Add a few slides to welcome visitors with your best offers." },
    { value: "promo", label: "Promo banners", icon: "mdi-image-area", size: "800 × 400 pixels works best.", hint: "Shown where you add a “Promo banners” section on the home page.", empty: "Promo banners link to a category, brand or sale. Add a “Promo banners” section in Homepage Sections to show them." },
];
const blank = (type) => ({ id: null, type, image: null, mobile_image: null, title: "", subtitle: "", button_text: "", link: "", starts_at: null, ends_at: null, is_active: true });

export default {
    name: "Banners",
    data() {
        return {
            type: this.$route.query.type === "promo" ? "promo" : "hero",
            banners: [],
            loading: true,
            dialog: false,
            form: blank("hero"),
            errors: {},
            saving: false,
            deleteDialog: false,
            deleting: null,
            removing: false,
        };
    },
    computed: {
        types: () => TYPES,
        typeInfo() {
            return TYPES.find((t) => t.value === this.type);
        },
        schedule: () => SCHEDULE_LABELS,
    },
    created() {
        this.load();
    },
    methods: {
        url: fileUrl,
        state: (b) => scheduleState(b),
        range(b) {
            const f = (d) => (d ? this.$date(d, { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }) : "…");
            return `${f(b.starts_at)} – ${f(b.ends_at)}`;
        },
        setType(t) {
            this.type = t;
            this.$router.replace({ query: t === "promo" ? { type: t } : {} });
            this.load();
        },
        async load() {
            this.loading = true;
            try {
                this.banners = (await api.get("/admin/banners", { params: { type: this.type } })).data;
            } finally {
                this.loading = false;
            }
        },
        openForm(b = null) {
            this.errors = {};
            this.form = b ? { ...blank(this.type), ...b, starts_at: toLocalInput(b.starts_at), ends_at: toLocalInput(b.ends_at) } : blank(this.type);
            this.dialog = true;
        },
        body(form) {
            return { ...form, type: this.type, starts_at: fromLocalInput(form.starts_at), ends_at: fromLocalInput(form.ends_at) };
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                if (this.form.id) await api.put(`/admin/banners/${this.form.id}`, this.body(this.form));
                else await api.post("/admin/banners", this.body(this.form));
                this.dialog = false;
                this.$store.dispatch("notify", this.$t("Saved."));
                this.load();
            } catch (e) {
                this.errors = fieldErrors(e);
            } finally {
                this.saving = false;
            }
        },
        async quickToggle(b, value) {
            b.is_active = value;
            try {
                await api.put(`/admin/banners/${b.id}`, { ...b });
            } catch (e) {
                b.is_active = !value;
            }
        },
        async saveOrder() {
            try {
                await api.post("/admin/banners/reorder", { ids: this.banners.map((b) => b.id) });
                this.$store.dispatch("notify", this.$t("Order saved."));
            } catch (e) {
                this.load();
            }
        },
        askDelete(b) {
            this.deleting = b;
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
