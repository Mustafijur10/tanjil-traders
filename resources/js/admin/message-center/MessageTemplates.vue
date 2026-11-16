<template>
    <v-defaults-provider :defaults="fieldDefaults">
        <div class="tt-page">
            <!-- ═════════ Hero ═════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="d-flex flex-wrap align-center ga-3">
                            <h1 class="tt-h1">Templates</h1>
                            <span class="tt-pill"><v-icon size="13">mdi-file-document-multiple-outline</v-icon>{{ templates.length }} saved messages</span>
                        </div>
                        <div class="tt-hero-sub">Save the messages you send often, then reuse them in campaigns and automations</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-email-fast-outline" to="/admin/campaigns">Open campaigns</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="openNew()">New template</v-btn>
                    </div>
                </div>

                <div class="tt-hero-strip">
                    <div v-for="h in heroTiles" :key="h.label" class="tt-hero-item" :style="{ '--hue': h.color }">
                        <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                        <div class="tt-hero-txt">
                            <div class="tt-hero-value">{{ h.value }}</div>
                            <div class="tt-hero-label">{{ h.label }}</div>
                        </div>
                        <span class="tt-live" :class="{ todo: h.todo }">{{ h.tag }}</span>
                    </div>
                </div>
            </header>

            <!-- ═════════ Library ═════════ -->
            <section class="tt-card a-violet mt-4">
                <div class="tt-head tt-head-wrap">
                    <div class="tt-head-ic"><v-icon size="16">mdi-view-grid-outline</v-icon></div>
                    <div><div class="tt-title">Template library</div><div class="tt-subt">{{ filtered.length }} of {{ templates.length }} templates</div></div>
                    <v-spacer />
                    <v-text-field v-model="search" class="tt-search" hide-details clearable prepend-inner-icon="mdi-magnify" placeholder="Search templates" />
                    <v-select v-model="categoryFilter" class="tt-cat-select" :items="['All categories', ...categories]" hide-details :menu-props="overlayMenu" aria-label="Category" />
                    <div class="tt-seg tt-seg-light" role="tablist" aria-label="Channel filter">
                        <button v-for="f in channelFilters" :key="f.value" type="button" role="tab" :aria-selected="channelFilter === f.value" :class="{ on: channelFilter === f.value }" @click="channelFilter = f.value">{{ f.label }}</button>
                    </div>
                </div>

                <div class="tt-body">
                    <div v-if="filtered.length" class="tpl-grid">
                        <article v-for="t in filtered" :key="t.id" class="tpl" :style="{ '--c': meta[t.channel].color }">
                            <div class="tpl-top">
                                <span class="tpl-ic"><v-icon size="16" color="white">{{ meta[t.channel].icon }}</v-icon></span>
                                <div class="tpl-name">
                                    <b>{{ t.name }}</b>
                                    <small>{{ meta[t.channel].label }} · {{ t.category }}</small>
                                </div>
                                <v-menu content-class="tt-overlay">
                                    <template #activator="{ props }">
                                        <v-btn v-bind="props" icon variant="text" size="small" :aria-label="'More actions for ' + t.name"><v-icon size="18" color="#5c726e">mdi-dots-vertical</v-icon></v-btn>
                                    </template>
                                    <v-list density="compact" min-width="190">
                                        <v-list-item prepend-icon="mdi-pencil-outline" title="Edit" @click="openEdit(t)" />
                                        <v-list-item prepend-icon="mdi-content-copy" title="Duplicate" @click="duplicate(t)" />
                                        <v-divider />
                                        <v-list-item prepend-icon="mdi-delete-outline" title="Delete" base-color="error" @click="deleting = t" />
                                    </v-list>
                                </v-menu>
                            </div>

                            <div class="tpl-body">
                                <div v-if="t.channel === 'email'" class="tpl-subject">{{ t.subject }}</div>
                                <p class="tpl-text">{{ t.body }}</p>
                            </div>

                            <div class="tpl-meta">
                                <span v-if="t.channel === 'whatsapp'" class="tt-chip" :class="'tone-' + waTones[t.waStatus]">{{ t.waStatus }}</span>
                                <span v-else-if="t.channel === 'sms'" class="tt-chip tone-gray">{{ smsInfo(t.body).parts }} SMS · {{ smsInfo(t.body).chars }} characters</span>
                                <span v-else class="tt-chip tone-gray">{{ t.body.length }} characters</span>
                                <span class="tt-subt ml-auto">Used {{ t.used }} times</span>
                            </div>
                            <div v-if="t.channel === 'whatsapp' && t.waStatus === 'Rejected'" class="tpl-reject"><v-icon size="14" color="#b4381a">mdi-alert-circle-outline</v-icon>{{ t.reason }}</div>

                            <div class="tpl-foot">
                                <span class="tt-subt">Edited {{ t.edited }}</span>
                                <v-spacer />
                                <v-btn variant="text" size="small" class="text-none tt-link" @click="openEdit(t)">Edit</v-btn>
                                <v-btn variant="flat" size="small" color="#0f9d6b" class="text-none tt-send-btn" :disabled="t.channel === 'whatsapp' && t.waStatus !== 'Approved'" @click="useTemplate(t)">Use</v-btn>
                            </div>
                        </article>
                    </div>

                    <div v-else class="tt-empty">
                        <div class="tt-empty-ic"><v-icon size="26" color="#0f9d6b">mdi-file-search-outline</v-icon></div>
                        <div class="tt-strong mt-3">No templates match</div>
                        <div class="tt-subt">Clear the search or pick another channel, or create a new template.</div>
                        <v-btn variant="flat" color="#0f9d6b" class="text-none mt-3 tt-send-btn" prepend-icon="mdi-plus" @click="openNew()">New template</v-btn>
                    </div>
                </div>
            </section>

            <!-- ═════════ Editor ═════════ -->
            <v-dialog v-model="editorOpen" max-width="960" scrollable content-class="tt-overlay">
                <div class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg"><v-icon size="20">mdi-file-edit-outline</v-icon></div>
                        <div class="ml-3">
                            <div class="tt-info-title">{{ form.id ? "Edit template" : "New template" }}</div>
                            <div class="tt-subt">Use the insert buttons so each customer sees their own details</div>
                        </div>
                        <v-spacer />
                        <v-btn icon size="small" variant="text" aria-label="Close" @click="editorOpen = false"><v-icon size="18">mdi-close</v-icon></v-btn>
                    </div>

                    <div class="ed-grid">
                        <div class="ed-form">
                            <div class="ed-ch">
                                <button v-for="(m, key) in meta" :key="key" type="button" class="ed-ch-btn" :class="{ on: form.channel === key }" :style="{ '--c': m.color }" :aria-pressed="form.channel === key" @click="form.channel = key">
                                    <v-icon size="16">{{ m.icon }}</v-icon>{{ m.label }}
                                </button>
                            </div>

                            <v-row dense class="mt-3">
                                <v-col cols="12" sm="7"><v-text-field v-model="form.name" label="Template name" prepend-inner-icon="mdi-tag-outline" hide-details="auto" :error-messages="touched && !form.name.trim() ? 'Give the template a name' : ''" /></v-col>
                                <v-col cols="12" sm="5"><v-select v-model="form.category" :items="categories" label="Category" hide-details :menu-props="overlayMenu" /></v-col>
                            </v-row>

                            <v-text-field v-if="form.channel === 'email'" v-model="form.subject" class="mt-3" label="Subject line" prepend-inner-icon="mdi-format-title" hide-details="auto" :error-messages="touched && !form.subject.trim() ? 'Emails need a subject' : ''" />

                            <v-textarea v-model="form.body" class="mt-3" label="Message" rows="6" auto-grow hide-details="auto" :error-messages="touched && !form.body.trim() ? 'Write the message' : ''" />

                            <div class="tags-row">
                                <span class="tt-subt">Insert:</span>
                                <button v-for="m in mergeTags" :key="m.tag" type="button" @click="form.body += m.tag">{{ m.label }}</button>
                            </div>

                            <div v-if="form.channel === 'sms'" class="ed-note" :class="{ warn: formSms.parts > 3 }">
                                <v-icon size="15">mdi-message-text-outline</v-icon>
                                {{ formSms.chars }} characters, {{ formSms.parts }} SMS per customer.
                                {{ formSms.unicode ? "Bangla or emoji makes each SMS hold only " + formSms.per + " characters." : "English only, so each SMS holds " + formSms.per + " characters." }}
                            </div>
                            <div v-if="form.channel === 'whatsapp'" class="ed-note">
                                <v-icon size="15">mdi-shield-check-outline</v-icon>
                                It will be sent to WhatsApp as <b>{{ waName }}</b>. Meta reviews every template, so you can use it only after it is approved.
                            </div>
                        </div>

                        <div class="ed-prev">
                            <div class="tt-subt mb-2">Preview with sample details</div>
                            <div class="tt-stage">
                                <div v-if="form.channel === 'email'" class="pv">
                                    <div class="pv-mail-brand"><v-icon size="16" color="white">mdi-flash</v-icon>Tanjil Traders</div>
                                    <div class="pv-mail-body">
                                        <div class="pv-subject">{{ fill(form.subject) || "Your subject line" }}</div>
                                        <p v-for="(p, i) in paragraphs" :key="i">{{ p }}</p>
                                        <p v-if="!paragraphs.length" class="pv-empty">Your message appears here…</p>
                                    </div>
                                </div>
                                <div v-else class="pv pv-chat" :class="{ wa: form.channel === 'whatsapp' }">
                                    <div class="pv-bubble">{{ fill(form.body) || "Your message appears here…" }}<small>now</small></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tt-dialog-foot">
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-icon-btn" @click="editorOpen = false">Cancel</v-btn>
                        <v-btn variant="flat" color="#0f9d6b" class="text-none tt-send-btn" :loading="saving" prepend-icon="mdi-content-save-outline" @click="save">{{ form.channel === "whatsapp" ? "Save and send for approval" : "Save template" }}</v-btn>
                    </div>
                </div>
            </v-dialog>

            <!-- ═════════ Delete confirm ═════════ -->
            <v-dialog :model-value="!!deleting" max-width="420" content-class="tt-overlay" @update:model-value="deleting = null">
                <div v-if="deleting" class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg tt-head-ic--red"><v-icon size="20">mdi-delete-outline</v-icon></div>
                        <div class="ml-3"><div class="tt-info-title">Delete "{{ deleting.name }}"?</div><div class="tt-subt">Automations that use it will stop sending until you pick another template.</div></div>
                    </div>
                    <div class="tt-dialog-foot">
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-icon-btn" @click="deleting = null">Keep it</v-btn>
                        <v-btn variant="flat" color="#dc2626" class="text-none tt-send-btn" @click="confirmDelete">Delete template</v-btn>
                    </div>
                </div>
            </v-dialog>

            <v-snackbar v-model="snackbar" color="#0a5548" timeout="3500">{{ snackbarText }}</v-snackbar>
        </div>
    </v-defaults-provider>
</template>

<script>
const C = { teal: "#0f9d6b", blue: "#2f5be7", violet: "#7c3aed", amber: "#f59e0b" };
const blank = () => ({ id: null, name: "", channel: "email", category: "Offers", subject: "", body: "", waStatus: "Pending", reason: "", used: 0 });

export default {
    name: "MessageTemplates",

    data() {
        return {
            fieldDefaults: {
                VTextField: { variant: "outlined", density: "compact" },
                VSelect: { variant: "outlined", density: "compact" },
                VTextarea: { variant: "outlined", density: "compact" },
            },
            overlayMenu: { contentClass: "tt-overlay" },
            snackbar: false,
            snackbarText: "",

            search: "",
            categoryFilter: "All categories",
            channelFilter: "all",
            channelFilters: [{ value: "all", label: "All" }, { value: "email", label: "Email" }, { value: "sms", label: "SMS" }, { value: "whatsapp", label: "WhatsApp" }],
            meta: {
                email: { label: "Email", icon: "mdi-email-outline", color: "#7c3aed" },
                sms: { label: "SMS", icon: "mdi-message-text-outline", color: "#0f9d6b" },
                whatsapp: { label: "WhatsApp", icon: "mdi-whatsapp", color: "#25d366" },
            },
            waTones: { Approved: "green", Pending: "amber", Rejected: "red" },
            categories: ["Offers", "Order updates", "Reminders", "Welcome", "Support"],
            mergeTags: [
                { label: "Customer name", tag: "{name}" },
                { label: "Order number", tag: "{order_id}" },
                { label: "Coupon code", tag: "{coupon}" },
            ],

            editorOpen: false,
            touched: false,
            saving: false,
            form: blank(),
            deleting: null,
            nextId: 100,

            // replace with GET /api/admin/message-templates
            templates: [
                { id: 1, name: "Offer announcement", channel: "email", category: "Offers", subject: "Our offer is on: up to 30% off chargers and power banks", body: "Hi {name},\n\nOur offer is on. Get up to 30% off power banks, chargers and earbuds, all with official warranty.\n\nUse code {coupon} at checkout.", used: 14, edited: "12 Nov 2026" },
                { id: 2, name: "Welcome and first order coupon", channel: "email", category: "Welcome", subject: "Welcome to Tanjil Traders, {name}", body: "Hi {name},\n\nThanks for joining us. Here is a coupon for your first order: {coupon}.\n\nEvery gadget we sell comes with official warranty.", used: 141, edited: "2 Nov 2026" },
                { id: 3, name: "Cart reminder", channel: "email", category: "Reminders", subject: "You left something in your cart", body: "Hi {name},\n\nYour cart is waiting. Cash on delivery, bKash and Nagad are all available at checkout.\n\nNeed help choosing? Just reply to this email.", used: 214, edited: "28 Oct 2026" },
                { id: 4, name: "Review request", channel: "email", category: "Reminders", subject: "How is your new gadget, {name}?", body: "Hi {name},\n\nYour order {order_id} was delivered a week ago. Would you share a quick review? It helps other customers choose.", used: 188, edited: "20 Oct 2026" },
                { id: 5, name: "Order confirmed", channel: "sms", category: "Order updates", subject: "", body: "Hi {name}, your order {order_id} is confirmed. We will dispatch it within 24 hours. Tanjil Traders", used: 318, edited: "30 Oct 2026" },
                { id: 6, name: "Out for delivery", channel: "sms", category: "Order updates", subject: "", body: "Hi {name}, your order {order_id} is out for delivery today. Please keep your phone on. Tanjil Traders", used: 296, edited: "30 Oct 2026" },
                { id: 7, name: "Flash sale", channel: "sms", category: "Offers", subject: "", body: "Tanjil Traders: Flash sale today only! Up to 25% off. Code {coupon}. Ends tonight.", used: 22, edited: "10 Nov 2026" },
                { id: 8, name: "বাংলা অফার", channel: "sms", category: "Offers", subject: "", body: "প্রিয় {name}, তানজিল ট্রেডার্সে অফার চলছে। পাওয়ার ব্যাংক ও চার্জারে ৩০% পর্যন্ত ছাড়। কোড: {coupon}", used: 9, edited: "6 Nov 2026" },
                { id: 9, name: "Back in stock", channel: "sms", category: "Reminders", subject: "", body: "Good news {name}! The item you asked about is back in stock at Tanjil Traders. Order now before it sells out.", used: 63, edited: "3 Nov 2026" },
                { id: 10, name: "enquiry_followup", channel: "whatsapp", category: "Support", subject: "", body: "Hello {name}! This is Tanjil Traders following up on your recent enquiry. Reply here and we will be happy to help.", used: 77, edited: "15 Oct 2026", waStatus: "Approved" },
                { id: 11, name: "quotation_followup", channel: "whatsapp", category: "Support", subject: "", body: "Hi {name}! We have sent the quotation you requested. Would you like us to adjust quantities or delivery?", used: 31, edited: "9 Nov 2026", waStatus: "Approved" },
                { id: 12, name: "offer_broadcast", channel: "whatsapp", category: "Offers", subject: "", body: "Hi {name}! Our offer is live with up to 30% off. Use code {coupon} at checkout.", used: 0, edited: "14 Nov 2026", waStatus: "Rejected", reason: "Meta asked for a clearer opt-out line. Add one and send it again." },
                { id: 13, name: "warranty_reminder", channel: "whatsapp", category: "Reminders", subject: "", body: "Hi {name}, the warranty on your order {order_id} ends soon. Reply here to book a free check-up.", used: 0, edited: "16 Nov 2026", waStatus: "Pending" },
            ],
        };
    },

    computed: {
        filtered() {
            const q = (this.search || "").trim().toLowerCase();
            return this.templates.filter(
                (t) =>
                    (this.channelFilter === "all" || t.channel === this.channelFilter) &&
                    (this.categoryFilter === "All categories" || t.category === this.categoryFilter) &&
                    (!q || t.name.toLowerCase().includes(q) || t.body.toLowerCase().includes(q) || (t.subject || "").toLowerCase().includes(q))
            );
        },
        heroTiles() {
            const n = (ch) => this.templates.filter((t) => t.channel === ch).length;
            const pending = this.templates.filter((t) => t.channel === "whatsapp" && t.waStatus === "Pending").length;
            const rejected = this.templates.filter((t) => t.channel === "whatsapp" && t.waStatus === "Rejected").length;
            const top = [...this.templates].sort((a, b) => b.used - a.used)[0];
            return [
                { icon: "mdi-email-outline", color: C.violet, value: n("email"), label: "Email templates", tag: "Ready" },
                { icon: "mdi-message-text-outline", color: C.teal, value: n("sms"), label: "SMS templates", tag: "Ready" },
                { icon: "mdi-whatsapp", color: C.blue, value: n("whatsapp"), label: "WhatsApp templates", tag: rejected ? rejected + " rejected" : pending ? pending + " pending" : "Approved", todo: rejected + pending > 0 },
                { icon: "mdi-trophy-outline", color: C.amber, value: top ? top.used : 0, label: "Uses of " + (top ? top.name : "-"), tag: "Most used" },
            ];
        },
        formSms() {
            return this.smsInfo(this.form.body);
        },
        waName() {
            return (this.form.name || "").toLowerCase().trim().replace(/[^a-z0-9]+/g, "_").replace(/^_|_$/g, "") || "template_name";
        },
        paragraphs() {
            return this.fill(this.form.body).split("\n").filter((l) => l.trim());
        },
    },

    methods: {
        notify(text) {
            this.snackbarText = text;
            this.snackbar = true;
        },
        fill(text) {
            return (text || "").replace(/\{name\}/g, "Nusrat").replace(/\{order_id\}/g, "#TT-10482").replace(/\{coupon\}/g, "EID26");
        },
        smsInfo(text) {
            const chars = (text || "").length;
            const unicode = /[^\x00-\x7F]/.test(text || "");
            const per = unicode ? (chars <= 70 ? 70 : 67) : chars <= 160 ? 160 : 153;
            return { chars, unicode, per, parts: chars ? Math.ceil(chars / per) : 0 };
        },
        today() {
            return new Date().toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },

        openNew() {
            this.form = blank();
            this.touched = false;
            this.editorOpen = true;
        },
        openEdit(t) {
            this.form = { ...blank(), ...t };
            this.touched = false;
            this.editorOpen = true;
        },
        async save() {
            this.touched = true;
            const f = this.form;
            if (!f.name.trim() || !f.body.trim() || (f.channel === "email" && !f.subject.trim())) return;
            this.saving = true;
            try {
                // POST /api/admin/message-templates (or PUT /:id). WhatsApp templates are then sent to Meta for review.
                const payload = { ...f, name: f.channel === "whatsapp" ? this.waName : f.name.trim() };
                if (f.channel === "whatsapp") payload.waStatus = "Pending";
                if (f.id) {
                    const i = this.templates.findIndex((t) => t.id === f.id);
                    this.templates.splice(i, 1, { ...this.templates[i], ...payload, edited: this.today() });
                } else {
                    this.templates.unshift({ ...payload, id: ++this.nextId, used: 0, edited: this.today() });
                }
                this.editorOpen = false;
                this.notify(f.channel === "whatsapp" ? "Template sent to WhatsApp for approval." : "Template saved.");
            } finally {
                this.saving = false;
            }
        },
        duplicate(t) {
            this.templates.unshift({ ...t, id: ++this.nextId, name: t.name + (t.channel === "whatsapp" ? "_copy" : " (copy)"), used: 0, edited: this.today(), waStatus: t.channel === "whatsapp" ? "Pending" : t.waStatus });
            this.notify("Copy created.");
        },
        confirmDelete() {
            this.templates = this.templates.filter((t) => t.id !== this.deleting.id); // DELETE /api/admin/message-templates/:id
            this.deleting = null;
            this.notify("Template deleted.");
        },
        useTemplate(t) {
            // Email & SMS can read this.$route.query.template to pre-fill the message
            this.$router.push({ path: "/admin/campaigns", query: { template: t.id } });
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

/* Same tokens as the Dashboard, Inbox and Create Post pages. Vuetify colour tokens are pinned so inputs stay dark on white. */
.tt-page {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
    --bg2: #f7faf9;
    --v-theme-on-surface: 15, 42, 38;
    --v-theme-on-background: 15, 42, 38;
    --v-theme-surface: 255, 255, 255;
    --v-theme-background: 243, 247, 246;
    --v-theme-primary: 15, 157, 107;
    --v-theme-on-primary: 255, 255, 255;
    --v-border-color: 15, 42, 38;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    background: var(--bg);
    padding-bottom: 32px;
    font-variant-numeric: tabular-nums;
    -webkit-font-smoothing: antialiased;
}
.tt-page :deep(.v-btn) { letter-spacing: 0; }
.tt-subt { font-size: 12px; color: var(--muted); }
.tt-strong { font-weight: 600; color: var(--ink); }

/* ───────── Hero ───────── */
.tt-hero { position: relative; overflow: hidden; color: #fff; padding: 18px 22px; border-radius: 18px; background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%); box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95); }
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.15; letter-spacing: -0.5px; color: #fff; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.22); color: #fff; }
.tt-btn-ghost { height: 38px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 38px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 13px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.22; filter: blur(10px); }
.tt-hero-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-txt { flex: 1; min-width: 0; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.3px; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.82); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tt-live { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(110, 231, 183, 0.5); white-space: nowrap; }
.tt-live.todo { color: #fcd34d; background: rgba(245, 158, 11, 0.16); border-color: rgba(252, 211, 77, 0.5); }
@media (max-width: 1199px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Cards ───────── */
.tt-card { --accent: #2f5be7; --tint: #e6edff; position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35); overflow: hidden; color: var(--ink); }
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, var(--accent), transparent 85%); }
.a-violet { --accent: #7c3aed; --tint: #ebe5ff; }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 6px; }
.tt-head-wrap { flex-wrap: wrap; padding-bottom: 12px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-head-ic--lg { width: 42px; height: 42px; border-radius: 12px; background: #dff5ec; color: #0f9d6b; }
.tt-head-ic--red { background: #fce1d8; color: #b4381a; }
.tt-title { font-size: 14px; font-weight: 600; line-height: 1.3; }
.tt-body { padding: 10px 18px 18px; }
.tt-seg { display: inline-flex; padding: 3px; border-radius: 12px; background: #eef3f1; border: 1px solid var(--line); }
.tt-seg button { border: 0; background: transparent; font: inherit; font-size: 12.5px; font-weight: 500; color: var(--muted); padding: 5px 14px; border-radius: 9px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-seg button:hover { color: var(--ink); }
.tt-seg button.on { background: #fff; color: #0a5548; font-weight: 600; box-shadow: 0 1px 3px rgba(15, 42, 38, 0.14); }
.tt-seg button:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-search { max-width: 250px; min-width: 170px; }
.tt-search :deep(.v-field), .tt-cat-select :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-cat-select { max-width: 190px; min-width: 150px; }
.tt-icon-btn { border-color: var(--line) !important; color: #3d5450 !important; border-radius: 10px !important; font-weight: 600; }
.tt-send-btn { color: #fff !important; font-weight: 600; border-radius: 10px !important; }
.tt-link { color: #0a5548 !important; font-weight: 600; }
.tt-chip { display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 20px; white-space: nowrap; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-red { background: #fce1d8; color: #b4381a; }
.tone-gray { background: #e8ecea; color: #5b6275; }

/* ───────── Template cards ───────── */
.tpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); gap: 14px; }
.tpl { --c: #7c3aed; display: flex; flex-direction: column; border: 1px solid var(--line); border-top: 3px solid var(--c); border-radius: 14px; background: #fff; padding: 12px 14px 10px; min-width: 0; }
.tpl:hover { box-shadow: 0 14px 26px -20px rgba(15, 42, 38, 0.55); }
.tpl-top { display: flex; align-items: center; gap: 10px; }
.tpl-ic { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; background: var(--c); flex: none; box-shadow: 0 8px 14px -8px var(--c); }
.tpl-name { flex: 1; min-width: 0; line-height: 1.3; }
.tpl-name b { display: block; font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tpl-name small { font-size: 11.5px; color: var(--muted); }
.tpl-body { margin: 10px 0; padding: 10px 12px; border-radius: 11px; background: var(--bg2); border: 1px solid var(--line); flex: 1; }
.tpl-subject { font-size: 12.5px; font-weight: 600; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tpl-text { margin: 0; font-size: 12.5px; line-height: 1.55; color: #3d5450; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; white-space: pre-line; }
.tpl-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.tpl-reject { display: flex; align-items: flex-start; gap: 6px; margin-top: 8px; font-size: 12px; line-height: 1.45; color: #b4381a; }
.tpl-foot { display: flex; align-items: center; gap: 4px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #d5dfdc; }
.tt-empty { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 44px 20px; }
.tt-empty-ic { width: 56px; height: 56px; border-radius: 50%; background: #e3f6ee; display: grid; place-items: center; }

/* ───────── Editor dialog ───────── */
.tt-dialog { position: relative; background: #fff; color: var(--ink); border-radius: 16px; overflow: hidden; font-family: Poppins, "Segoe UI", sans-serif; display: flex; flex-direction: column; max-height: 92vh; }
.tt-dialog::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-dialog-head { display: flex; align-items: center; padding: 20px 20px 14px; border-bottom: 1px solid var(--line); }
.tt-info-title { font-size: 15px; font-weight: 600; }
.tt-dialog-foot { display: flex; align-items: center; gap: 8px; padding: 12px 20px 16px; border-top: 1px solid var(--line); }
.ed-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; padding: 16px 20px; overflow-y: auto; }
.ed-ch { display: flex; gap: 8px; flex-wrap: wrap; }
.ed-ch-btn { --c: #7c3aed; display: inline-flex; align-items: center; gap: 6px; font: inherit; font-size: 12.5px; font-weight: 600; padding: 6px 14px; border: 1.5px solid var(--line); border-radius: 20px; background: #fff; color: var(--ink); cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.ed-ch-btn:hover { border-color: var(--c); }
.ed-ch-btn.on { border-color: var(--c); background: color-mix(in srgb, var(--c) 9%, white); color: var(--c); }
.ed-ch-btn:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tags-row { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.tags-row button { font: inherit; font-size: 12px; font-weight: 600; color: #5b3fd1; background: #ebe5ff; border: 1px solid #d6caff; border-radius: 14px; padding: 2px 12px; cursor: pointer; }
.tags-row button:hover { background: #e0d6ff; }
.ed-note { display: flex; align-items: flex-start; gap: 8px; margin-top: 12px; padding: 9px 12px; border-radius: 10px; background: #f0faf5; border: 1px solid #c6ebd9; font-size: 12px; line-height: 1.5; color: #2f4f49; }
.ed-note.warn { background: #fff8e8; border-color: #f3e3b8; color: #7a4f00; }
.tt-stage { border-radius: 14px; padding: 14px; background-color: #f0f5f3; background-image: radial-gradient(#dbe6e2 1px, transparent 1px); background-size: 16px 16px; }
.pv { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; background: #fff; font-size: 13px; box-shadow: 0 8px 20px -14px rgba(15, 42, 38, 0.5); }
.pv-mail-brand { display: flex; align-items: center; gap: 6px; padding: 10px 14px; background: linear-gradient(135deg, #053d35, #0a5548); color: #fff; font-weight: 600; font-size: 13px; }
.pv-mail-body { padding: 14px; }
.pv-subject { font-weight: 600; margin-bottom: 8px; }
.pv-mail-body p { margin: 0 0 10px; line-height: 1.55; overflow-wrap: anywhere; }
.pv-empty { color: var(--muted); }
.pv-chat { background: #e7eef1; border: 0; padding: 18px 12px 22px; }
.pv-chat.wa { background: #e6ddd4; }
.pv-bubble { display: inline-block; max-width: 92%; background: #fff; border-radius: 14px 14px 14px 4px; padding: 9px 13px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; box-shadow: 0 1px 1px rgba(15, 42, 38, 0.08); }
.pv-chat.wa .pv-bubble { background: #d9fdd3; }
.pv-bubble small { display: block; text-align: right; margin-top: 2px; font-size: 10.5px; color: var(--muted); }

@media (max-width: 860px) { .ed-grid { grid-template-columns: minmax(0, 1fr); } }
@media (prefers-reduced-motion: reduce) { .ed-ch-btn, .tt-seg button { transition: none; } }
</style>

<!-- Menus and dialogs open outside the page, so they need their own pinned colours -->
<style>
.tt-overlay {
    --v-theme-on-surface: 15, 42, 38;
    --v-theme-surface: 255, 255, 255;
    --v-theme-primary: 15, 157, 107;
    --v-border-color: 15, 42, 38;
    color: #0f2a26;
    font-family: Poppins, "Segoe UI", sans-serif;
}
.tt-overlay .v-list { background: #fff; color: #0f2a26; border-radius: 12px; }
.tt-overlay .v-list-item--active { color: #0a5548; background: #eaf8f1; }
</style>
