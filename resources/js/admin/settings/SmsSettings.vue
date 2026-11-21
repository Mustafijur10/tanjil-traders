<template>
    <div>
        <PageHeader title="SMS & Notifications" subtitle="Text customers about their order, and get told when a new order comes in.">
            <v-btn-primary prepend-icon="mdi-content-save-outline" :loading="saving" :disabled="!changed" @click="save">{{ $t("Save changes") }}</v-btn-primary>
        </PageHeader>

        <div v-if="!form" class="tt-box tt-box-body tt-skel-group"><span v-for="n in 5" :key="n" class="tt-skel"></span></div>

        <div v-else class="tt-detail">
            <div class="tt-detail-main">
                <!-- Gateway -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-message-processing-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("SMS gateway") }}</h2><p>{{ $t("The company that sends your texts. Messages are paid to them per SMS.") }}</p></div>
                        <v-switch-trz v-model="form['sms.enabled']" :aria-label="$t('Send SMS')" />
                    </div>
                    <div class="tt-box-body tt-form-grid">
                        <div class="tt-segments is-full" role="tablist">
                            <button type="button" :class="{ 'is-on': form['sms.provider'] === 'bulksmsbd' }" @click="form['sms.provider'] = 'bulksmsbd'">BulkSMSBD</button>
                            <button type="button" :class="{ 'is-on': form['sms.provider'] === 'custom' }" @click="form['sms.provider'] = 'custom'">{{ $t("Other gateway") }}</button>
                        </div>
                        <v-input-trz v-model.trim="form['sms.api_key']" :label="$t('API key')" type="password" autocomplete="off" :placeholder="form['sms.api_key'] === '__set__' ? $t('Saved. Type to replace') : ''" @focus="form['sms.api_key'] === '__set__' && (form['sms.api_key'] = '')" />
                        <v-input-trz v-model.trim="form['sms.sender_id']" :label="$t('Sender ID')" :hint="$t('Approved by the gateway, e.g. your shop name or number.')" persistent-hint />
                        <v-input-trz
                            v-if="form['sms.provider'] === 'custom'"
                            v-model.trim="form['sms.api_url']"
                            class="is-full"
                            :label="$t('Gateway link')"
                            placeholder="https://api.example.com/send?key={key}&to={phone}&from={sender}&text={message}"
                            :hint="$t('Write {phone}, {message}, {key} and {sender} where they go.')"
                            persistent-hint
                        />
                    </div>
                    <div class="tt-box-foot tt-sms-test">
                        <v-input-trz v-model.trim="testPhone" density="compact" :placeholder="$t('Your mobile number')" :aria-label="$t('Test number')" />
                        <v-btn-secondary prepend-icon="mdi-send-outline" :loading="testing" :disabled="!testPhone || changed" @click="test">{{ $t("Send test SMS") }}</v-btn-secondary>
                        <small v-if="changed" class="tt-muted">{{ $t("Save first, then test.") }}</small>
                    </div>
                </section>

                <!-- Messages -->
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-text-box-edit-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Messages to customers") }}</h2><p>{{ $t("Turn each message on or off and edit the words. Keep them short: one SMS is 160 English or 70 Bangla characters.") }}</p></div>
                    </div>
                    <div class="tt-box-body tt-sms-events">
                        <div v-for="e in events" :key="e.key" class="tt-sms-event" :class="{ 'is-off': !form[`sms.on_${e.key}`] }">
                            <div class="tt-sms-event-head">
                                <v-icon size="18">{{ e.icon }}</v-icon>
                                <b>{{ $t(e.label) }}</b>
                                <v-switch-trz v-model="form[`sms.on_${e.key}`]" :aria-label="$t(e.label)" />
                            </div>
                            <v-textarea-trz v-model="form[`sms.tpl_${e.key}`]" rows="2" :disabled="!form[`sms.on_${e.key}`]" :counter="160" />
                            <p class="tt-sms-sample">{{ sample(form[`sms.tpl_${e.key}`]) }}</p>
                        </div>
                        <p class="tt-muted tt-small">
                            {{ $t("You can use:") }}
                            <code v-for="p in placeholders" :key="p">{{ p }}</code>
                        </p>
                    </div>
                </section>
            </div>

            <aside class="tt-detail-side">
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-bell-ring-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("New order alerts") }}</h2><p>{{ $t("For orders from the website.") }}</p></div>
                    </div>
                    <div class="tt-box-body tt-vstack">
                        <v-input-trz v-model.trim="form['notify.order_sms']" :label="$t('SMS to this number')" placeholder="01XXXXXXXXX" :hint="$t('Uses the SMS gateway.')" persistent-hint />
                        <v-input-trz v-model.trim="form['notify.order_email']" type="email" :label="$t('Email to this address')" :hint="$t('Needs email (MAIL_) settings in the server .env file.')" persistent-hint />
                    </div>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-history</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Recent SMS") }}</h2><p>{{ $t("{s} sent, {f} failed in 30 days", { s: $num(logCounts.sent || 0), f: $num(logCounts.failed || 0) }) }}</p></div>
                    </div>
                    <ul v-if="logs.length" class="tt-sms-log">
                        <li v-for="l in logs" :key="l.id" :class="'is-' + l.status">
                            <v-icon size="16">{{ l.status === "sent" ? "mdi-check-circle" : "mdi-alert-circle" }}</v-icon>
                            <span>
                                <b>{{ l.phone }}</b> <small class="tt-muted">{{ l.order_number || $t(l.event === "test" ? "Test" : "") }}</small>
                                <small class="tt-ellipsis-2">{{ l.message }}</small>
                                <small v-if="l.status === 'failed'" class="tt-danger-text tt-ellipsis">{{ l.response }}</small>
                            </span>
                            <small class="tt-muted">{{ $date(l.created_at, { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }) }}</small>
                        </li>
                    </ul>
                    <div v-else class="tt-box-body"><p class="tt-muted tt-small">{{ $t("No messages sent yet.") }}</p></div>
                </section>
            </aside>
        </div>
    </div>
</template>

<script>
import api from "@/api";

const KEYS = [
    "sms.enabled", "sms.provider", "sms.api_key", "sms.sender_id", "sms.api_url",
    "sms.on_placed", "sms.on_confirmed", "sms.on_shipped", "sms.on_delivered", "sms.on_cancelled",
    "sms.tpl_placed", "sms.tpl_confirmed", "sms.tpl_shipped", "sms.tpl_delivered", "sms.tpl_cancelled",
    "notify.order_sms", "notify.order_email",
];

export default {
    name: "SmsSettings",
    data: () => ({ form: null, original: "", saving: false, testPhone: "", testing: false, logs: [], logCounts: {} }),
    computed: {
        events: () => [
            { key: "placed", label: "Order placed", icon: "mdi-cart-check" },
            { key: "confirmed", label: "Order confirmed", icon: "mdi-check-circle-outline" },
            { key: "shipped", label: "Handed to courier", icon: "mdi-truck-fast-outline" },
            { key: "delivered", label: "Delivered", icon: "mdi-home-check-outline" },
            { key: "cancelled", label: "Cancelled", icon: "mdi-close-circle-outline" },
        ],
        placeholders: () => ["{name}", "{order}", "{total}", "{courier}", "{tracking}", "{link}", "{store}", "{phone}"],
        changed() {
            return this.form && JSON.stringify(this.form) !== this.original;
        },
    },
    async created() {
        const { data } = await api.get("/admin/settings");
        this.form = Object.fromEntries(KEYS.map((k) => [k, data[k]]));
        this.original = JSON.stringify(this.form);
        this.loadLogs();
    },
    methods: {
        sample(tpl) {
            const s = this.$store.state.prefs.settings;
            return String(tpl || "")
                .replaceAll("{name}", "Rahim")
                .replaceAll("{order}", "TT-2611-0042")
                .replaceAll("{total}", "36,069")
                .replaceAll("{courier}", "Steadfast")
                .replaceAll("{tracking}", "SF12345")
                .replaceAll("{link}", `${location.origin}/track-order?number=TT-2611-0042`)
                .replaceAll("{store}", s["store.name"] || "")
                .replaceAll("{phone}", s["store.phone"] || "");
        },
        async loadLogs() {
            const { data } = await api.get("/admin/sms/logs");
            this.logs = data.items.data.slice(0, 12);
            this.logCounts = data.counts;
        },
        async save() {
            this.saving = true;
            try {
                await api.put("/admin/settings", { values: this.form });
                this.original = JSON.stringify(this.form);
                this.$store.dispatch("notify", this.$t("Saved."));
            } finally {
                this.saving = false;
            }
        },
        async test() {
            this.testing = true;
            try {
                await api.post("/admin/sms/test", { phone: this.testPhone });
                this.$store.dispatch("notify", this.$t("Test SMS sent. Check your phone."));
            } catch (e) {
                /* the message from the gateway is shown by the error handler */
            } finally {
                this.testing = false;
                this.loadLogs();
            }
        },
    },
};
</script>
