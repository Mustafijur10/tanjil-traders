<template>
    <div class="tt-wrap tt-page">
        <nav class="tt-crumbs" :aria-label="$t('Breadcrumb')">
            <router-link to="/">{{ $t("Home") }}</router-link><v-icon size="14">mdi-chevron-right</v-icon><span>{{ $t("Contact us") }}</span>
        </nav>
        <h1 class="tt-doc-title">{{ $t("Contact us") }}</h1>
        <p class="tt-muted mb-6">{{ $t("Questions about a product, an order or warranty? Send us a message and we will reply as soon as we can.") }}</p>

        <div class="tt-contact">
            <section class="tt-page-card">
                <v-alert v-if="sent" type="success" variant="tonal" class="mb-4" :text="$t('Thank you. We will get back to you soon.')" />
                <v-form v-else @submit.prevent="send">
                    <div class="tt-form-grid">
                        <v-input-trz v-model="form.name" :label="$t('Your name')" :error-messages="errors.name" autocomplete="name" />
                        <v-input-trz v-model="form.phone" :label="$t('Phone')" type="tel" :error-messages="errors.phone" autocomplete="tel" />
                        <v-input-trz v-model="form.email" :label="$t('Email (optional)')" type="email" :error-messages="errors.email" autocomplete="email" />
                        <v-input-trz v-model="form.subject" :label="$t('Subject (optional)')" />
                        <v-textarea-trz v-model="form.message" class="is-full" :label="$t('Your message')" rows="5" :error-messages="errors.message" />
                        <!-- Hidden from people; bots that fill it are ignored -->
                        <input v-model="form.website" class="tt-visually-hidden" tabindex="-1" autocomplete="off" aria-hidden="true" />
                    </div>
                    <v-btn-primary type="submit" class="mt-4" size="large" prepend-icon="mdi-send-outline" :loading="sending" :disabled="!form.name || !form.message">{{ $t("Send message") }}</v-btn-primary>
                </v-form>
            </section>

            <aside class="tt-page-card tt-contact-info">
                <h2>{{ s["store.name"] }}</h2>
                <ul class="tt-footer-contact">
                    <li v-if="s['store.phone']"><v-icon size="18">mdi-phone-outline</v-icon><a :href="`tel:${s['store.phone']}`">{{ s["store.phone"] }}</a></li>
                    <li v-if="s['social.whatsapp']"><v-icon size="18">mdi-whatsapp</v-icon><a :href="`https://wa.me/${String(s['social.whatsapp']).replace(/\D/g, '')}`" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li v-if="s['store.email']"><v-icon size="18">mdi-email-outline</v-icon><a :href="`mailto:${s['store.email']}`">{{ s["store.email"] }}</a></li>
                    <li v-if="s['store.address']"><v-icon size="18">mdi-map-marker-outline</v-icon><span>{{ $t(s["store.address"]) }}</span></li>
                    <li v-if="s['store.hours']"><v-icon size="18">mdi-clock-outline</v-icon><span>{{ $t(s["store.hours"]) }}</span></li>
                </ul>
                <v-btn-secondary v-if="directions" class="mt-4" block prepend-icon="mdi-directions" :href="directions" target="_blank" rel="noopener">{{ $t("Get directions") }}</v-btn-secondary>
            </aside>
        </div>

        <section v-if="mapUrl" class="tt-map-card" :aria-label="$t('Our location on the map')">
            <iframe :src="mapUrl" :title="$t('Map showing {name}', { name: s['store.name'] || '' })" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </section>
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";
import { mapEmbedUrl, directionsUrl } from "@/utils/map";

export default {
    name: "Contact",
    data: () => ({ form: { name: "", phone: "", email: "", subject: "", message: "", website: "" }, errors: {}, sending: false, sent: false }),
    computed: {
        s() {
            return this.$store.state.prefs.settings;
        },
        mapUrl() {
            return mapEmbedUrl(this.s["store.map_location"], this.s["store.address"]);
        },
        directions() {
            return directionsUrl(this.s["store.map_location"], this.s["store.address"]);
        },
    },
    methods: {
        async send() {
            this.sending = true;
            this.errors = {};
            try {
                await api.post("/contact", this.form, { quiet: true });
                this.sent = true;
            } catch (e) {
                this.errors = fieldErrors(e);
                if (e.response?.status === 429) this.$store.dispatch("notify", { text: this.$t("Too many messages. Please try again in a few minutes."), type: "warning" });
            } finally {
                this.sending = false;
            }
        },
    },
};
</script>
