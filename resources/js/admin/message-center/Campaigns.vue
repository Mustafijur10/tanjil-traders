<template>
    <v-defaults-provider :defaults="fieldDefaults">
        <div class="tt-page">
            <!-- ═════════ Hero ═════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="d-flex flex-wrap align-center ga-3">
                            <h1 class="tt-h1">Email &amp; SMS</h1>
                            <span class="tt-pill"><v-icon size="13">mdi-message-badge-outline</v-icon>Sender ID: {{ senderId }}</span>
                            <span class="tt-pill" :class="smsLow ? 'tt-pill-warn' : 'tt-pill-live'"><span class="tt-pulse"></span>{{ smsBalance.toLocaleString("en-IN") }} SMS credits left</span>
                        </div>
                        <div class="tt-hero-sub">Tell customers about new gadgets, offers and orders by email or SMS</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <div class="tt-seg" role="tablist" aria-label="Page section">
                            <button v-for="v in views" :key="v.value" type="button" role="tab" :aria-selected="view === v.value" :class="{ on: view === v.value }" @click="view = v.value">{{ v.label }}</button>
                        </div>
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-wallet-plus-outline" @click="notify('Open your SMS gateway to recharge credits.')">Buy SMS credits</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="newCampaign">New campaign</v-btn>
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
                        <div v-if="h.progress != null" class="tt-hero-progress"><span :style="{ width: h.progress + '%' }"></span></div>
                    </div>
                </div>
            </header>

            <!-- ═════════════════ COMPOSE ═════════════════ -->
            <div v-if="view === 'compose'" class="tt-layout">
                <div class="tt-main">
                    <!-- Channel -->
                    <section class="tt-card a-blue">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-send-outline</v-icon></div>
                            <div><div class="tt-title">How do you want to send it?</div><div class="tt-subt">Email fits photos and details. SMS gets read within minutes.</div></div>
                        </div>
                        <div class="tt-body">
                            <div class="ch-grid">
                                <button v-for="c in channelOptions" :key="c.value" type="button" class="ch" :class="{ on: channel === c.value }" :style="{ '--c': c.color }" :aria-pressed="channel === c.value" @click="channel = c.value">
                                    <span class="ch-ic" :style="{ background: c.color }"><v-icon size="19" color="white">{{ c.icon }}</v-icon></span>
                                    <span class="ch-txt"><b>{{ c.label }}</b><small>{{ c.hint }}</small></span>
                                    <span class="ch-check"><v-icon size="13" color="white">mdi-check</v-icon></span>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Audience -->
                    <section class="tt-card a-teal">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-account-group-outline</v-icon></div>
                            <div><div class="tt-title">Who gets it</div><div class="tt-subt">Only customers who agreed to receive {{ channel === "email" ? "emails" : "SMS" }}</div></div>
                            <v-spacer />
                            <span class="tt-tag">{{ recipients.toLocaleString("en-IN") }} people</span>
                        </div>
                        <div class="tt-body">
                            <div class="seg-grid">
                                <button v-for="s in segments" :key="s.value" type="button" class="seg" :class="{ on: segment === s.value }" :aria-pressed="segment === s.value" @click="segment = s.value">
                                    <b>{{ s.label }}</b>
                                    <small>{{ s.hint }}</small>
                                    <span>{{ (channel === "email" ? s.email : s.sms).toLocaleString("en-IN") }}</span>
                                </button>
                            </div>
                            <div class="tt-switch-row">
                                <div><b>Skip people who got a message in the last 24 hours</b><span>Avoids sending too much to the same customer</span></div>
                                <v-switch v-model="frequencyCap" color="#0f9d6b" density="compact" hide-details inset />
                            </div>
                        </div>
                    </section>

                    <!-- Email message -->
                    <section v-if="channel === 'email'" class="tt-card a-violet">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-email-edit-outline</v-icon></div>
                            <div><div class="tt-title">Email</div><div class="tt-subt">Choose a starting point, then edit the words</div></div>
                        </div>
                        <div class="tt-body">
                            <div class="tpl-grid">
                                <button v-for="t in emailTemplates" :key="t.key" type="button" class="tpl" :class="{ on: template === t.key }" :aria-pressed="template === t.key" @click="applyTemplate(t)">
                                    <span class="tpl-ic"><v-icon size="18">{{ t.icon }}</v-icon></span>
                                    <b>{{ t.label }}</b>
                                </button>
                            </div>

                            <v-text-field v-model="subject" class="mt-4" label="Subject line" prepend-inner-icon="mdi-format-title" :hint="subject.length + ' characters. Under 50 reads well on phones.'" persistent-hint />
                            <v-text-field v-model="preheader" class="mt-4" label="Preview text" prepend-inner-icon="mdi-text-short" hint="The grey line shown after the subject in the inbox" persistent-hint />
                            <v-textarea v-model="emailBody" class="mt-4" label="Message" rows="6" auto-grow hide-details />

                            <div class="tags-row">
                                <span class="tt-subt">Insert:</span>
                                <button v-for="m in mergeTags" :key="m.tag" type="button" @click="emailBody += m.tag">{{ m.label }}</button>
                            </div>

                            <v-row dense class="mt-2">
                                <v-col cols="12" md="5"><v-text-field v-model="ctaLabel" label="Button text" prepend-inner-icon="mdi-gesture-tap-button" hide-details /></v-col>
                                <v-col cols="12" md="7"><v-text-field v-model="ctaLink" label="Button link" prepend-inner-icon="mdi-link-variant" placeholder="https://tanjiltraders.com/…" hide-details /></v-col>
                            </v-row>

                            <v-autocomplete v-model="taggedProducts" class="mt-4" :items="products" item-title="name" item-value="slug" label="Show products in the email" multiple chips closable-chips return-object hide-details prepend-inner-icon="mdi-package-variant-closed" :menu-props="overlayMenu" />
                        </div>
                    </section>

                    <!-- SMS message -->
                    <section v-else class="tt-card a-violet">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-message-text-outline</v-icon></div>
                            <div><div class="tt-title">SMS</div><div class="tt-subt">Keep it short. Bangla text is counted differently.</div></div>
                            <v-spacer />
                            <span class="tt-tag" :class="{ 'tt-tag-warn': smsInfo.parts > 3 }">{{ smsInfo.chars }} characters · {{ smsInfo.parts }} SMS</span>
                        </div>
                        <div class="tt-body">
                            <v-textarea v-model="smsText" rows="4" auto-grow hide-details placeholder="Write your message…" />

                            <div class="tt-toolbar">
                                <v-menu content-class="tt-overlay">
                                    <template #activator="{ props }">
                                        <v-btn v-bind="props" size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-text-box-outline">Templates</v-btn>
                                    </template>
                                    <v-list density="compact" max-width="380">
                                        <v-list-item v-for="t in smsTemplates" :key="t.label" :title="t.label" :subtitle="t.text" @click="smsText = t.text" />
                                    </v-list>
                                </v-menu>
                                <v-menu :close-on-content-click="false" content-class="tt-overlay">
                                    <template #activator="{ props }">
                                        <v-btn v-bind="props" size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-emoticon-happy-outline">Emoji</v-btn>
                                    </template>
                                    <div class="tt-emoji-pop"><button v-for="e in emojis" :key="e" @click="smsText += e">{{ e }}</button></div>
                                </v-menu>
                                <v-spacer />
                                <v-btn size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-eraser" :disabled="!smsText" @click="smsText = ''">Clear</v-btn>
                            </div>

                            <div class="tags-row">
                                <span class="tt-subt">Insert:</span>
                                <button v-for="m in mergeTags" :key="m.tag" type="button" @click="smsText += m.tag">{{ m.label }}</button>
                            </div>

                            <div class="sms-meter" :class="{ warn: smsInfo.parts > 3 }">
                                <div class="tt-meter"><span :style="{ width: Math.min(100, (smsInfo.parts / 3) * 100) + '%' }"></span></div>
                                <small>{{ smsInfo.unicode ? "Contains Bangla or emoji, so each SMS holds " + smsInfo.per + " characters." : "English only, so each SMS holds " + smsInfo.per + " characters." }}</small>
                            </div>

                            <v-row dense class="mt-2">
                                <v-col cols="12" md="6"><v-select v-model="senderId" :items="senderIds" label="Sender ID" prepend-inner-icon="mdi-card-account-details-outline" hide-details :menu-props="overlayMenu" /></v-col>
                                <v-col cols="12" md="6"><v-text-field v-model="ctaLink" label="Link in the message (optional)" prepend-inner-icon="mdi-link-variant" hide-details /></v-col>
                            </v-row>
                            <div class="tt-switch-row">
                                <div><b>Shorten the link</b><span>Saves characters, so the SMS costs less</span></div>
                                <v-switch v-model="shortenLink" color="#0f9d6b" density="compact" hide-details inset />
                            </div>
                        </div>
                    </section>

                    <!-- Schedule -->
                    <section class="tt-card a-amber">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-calendar-clock</v-icon></div>
                            <div><div class="tt-title">When to send</div><div class="tt-subt">{{ whenSummary }}</div></div>
                        </div>
                        <div class="tt-body">
                            <div class="tt-modes" role="radiogroup" aria-label="Send time">
                                <button v-for="m in modes" :key="m.value" type="button" role="radio" :aria-checked="sendMode === m.value" :class="{ on: sendMode === m.value }" @click="sendMode = m.value">
                                    <span class="tt-mode-ic"><v-icon size="18">{{ m.icon }}</v-icon></span>
                                    <span><b>{{ m.label }}</b><small>{{ m.hint }}</small></span>
                                </button>
                            </div>
                            <v-expand-transition>
                                <div v-if="sendMode === 'schedule'" class="mt-4">
                                    <v-row dense>
                                        <v-col cols="12" sm="6"><v-text-field v-model="scheduleDate" type="date" :min="todayKey" label="Date" hide-details /></v-col>
                                        <v-col cols="12" sm="6"><v-text-field v-model="scheduleTime" type="time" label="Time (Dhaka)" hide-details /></v-col>
                                    </v-row>
                                    <div class="tt-best">
                                        <span class="tt-subt"><v-icon size="14">mdi-lightning-bolt-outline</v-icon> Customers open most at</span>
                                        <button v-for="b in bestTimes" :key="b.label" type="button" @click="applyBest(b)">{{ b.label }}</button>
                                    </div>
                                    <div v-if="schedulePast" class="tt-warn"><v-icon size="15" color="#b4381a">mdi-alert-circle-outline</v-icon> That time has passed. Choose a future time.</div>
                                </div>
                            </v-expand-transition>
                        </div>
                    </section>
                </div>

                <!-- ═════════ SIDE ═════════ -->
                <aside class="tt-side">
                    <div class="tt-sticky">
                        <section class="tt-card a-blue">
                            <div class="tt-head">
                                <div class="tt-head-ic"><v-icon size="16">mdi-eye-outline</v-icon></div>
                                <div><div class="tt-title">Live preview</div><div class="tt-subt">{{ channel === "email" ? "How it looks in the inbox" : "How it looks on a phone" }}</div></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-stage">
                                    <!-- Email -->
                                    <div v-if="channel === 'email'" class="pv">
                                        <div class="pv-inbox"><b>Tanjil Traders</b><span>{{ subject || "Your subject line" }}</span><small>{{ preheader || "Preview text appears here" }}</small></div>
                                        <div class="pv-mail">
                                            <div class="pv-mail-brand"><v-icon size="16" color="white">mdi-flash</v-icon>Tanjil Traders</div>
                                            <div class="pv-mail-body">
                                                <p v-for="(p, i) in previewParagraphs" :key="i">{{ p }}</p>
                                                <p v-if="!previewParagraphs.length" class="pv-empty">Your message appears here…</p>
                                                <div v-for="p in taggedProducts" :key="p.slug" class="pv-prod"><span><v-icon size="18" color="#0f9d6b">mdi-package-variant-closed</v-icon></span><div><b>{{ p.name }}</b><small>{{ money(p.price) }}</small></div></div>
                                                <span v-if="ctaLabel" class="pv-btn">{{ ctaLabel }}</span>
                                            </div>
                                            <div class="pv-mail-foot">House 32, Road 8/A, Dhanmondi, Dhaka · Unsubscribe</div>
                                        </div>
                                    </div>
                                    <!-- SMS -->
                                    <div v-else class="pv pv-phone">
                                        <div class="pv-phone-head"><span class="pv-av">T</span><b>{{ senderId }}</b></div>
                                        <div class="pv-sms"><div>{{ previewSms || "Your message appears here…" }}</div><small>now</small></div>
                                    </div>
                                </div>
                                <div class="tt-subt mt-2">Names and links use sample values here. Each customer gets their own.</div>
                            </div>
                        </section>

                        <section class="tt-card a-teal">
                            <div class="tt-head">
                                <div class="tt-head-ic"><v-icon size="16">mdi-clipboard-check-outline</v-icon></div>
                                <div><div class="tt-title">Ready to send?</div><div class="tt-subt">{{ readyDone }} of {{ readyTotal }} required checks passed</div></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-meter"><span :style="{ width: readyPct + '%' }"></span></div>
                                <div v-for="c in checklist" :key="c.text" class="tt-check" :class="{ bad: !c.ok && c.required }">
                                    <v-icon size="16" :color="c.ok ? '#0f9d6b' : c.required ? '#dc2626' : '#d97706'">{{ c.ok ? "mdi-check-circle" : c.required ? "mdi-close-circle" : "mdi-alert-circle" }}</v-icon>
                                    <span>{{ c.text }}</span>
                                </div>

                                <div class="cost-box">
                                    <div><small>Recipients</small><b>{{ recipients.toLocaleString("en-IN") }}</b></div>
                                    <div v-if="channel === 'sms'"><small>SMS credits used</small><b>{{ creditsNeeded.toLocaleString("en-IN") }}</b></div>
                                    <div v-if="channel === 'sms'"><small>Estimated cost</small><b>{{ money(smsCost) }}</b></div>
                                    <div v-else><small>Cost</small><b>Included</b></div>
                                </div>

                                <div class="d-flex ga-2 mt-3">
                                    <v-menu :close-on-content-click="false" content-class="tt-overlay">
                                        <template #activator="{ props }">
                                            <v-btn v-bind="props" variant="outlined" class="text-none tt-test" prepend-icon="mdi-test-tube">Send test</v-btn>
                                        </template>
                                        <div class="test-pop">
                                            <v-text-field v-model="testTarget" :label="channel === 'email' ? 'Your email' : 'Your phone number'" hide-details />
                                            <v-btn block variant="flat" color="#0f9d6b" class="text-none mt-2" :disabled="!testTarget" @click="sendTest">Send test</v-btn>
                                        </div>
                                    </v-menu>
                                    <v-btn variant="outlined" class="text-none tt-test" :loading="saving" @click="saveDraft">Save draft</v-btn>
                                </div>
                                <v-btn block variant="flat" color="#0f9d6b" class="text-none tt-publish" :disabled="!canSend" :loading="sending" :prepend-icon="sendMode === 'now' ? 'mdi-send' : 'mdi-calendar-clock'" @click="send">{{ sendLabel }}</v-btn>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>

            <!-- ═════════════════ CAMPAIGNS ═════════════════ -->
            <section v-else-if="view === 'campaigns'" class="tt-card a-blue mt-4">
                <div class="tt-head tt-head-wrap">
                    <div class="tt-head-ic"><v-icon size="16">mdi-bullhorn-outline</v-icon></div>
                    <div><div class="tt-title">All campaigns</div><div class="tt-subt">{{ filteredCampaigns.length }} campaigns</div></div>
                    <v-spacer />
                    <v-text-field v-model="search" class="tt-search" hide-details clearable prepend-inner-icon="mdi-magnify" placeholder="Search campaigns" @update:model-value="page = 1" />
                    <div class="tt-seg tt-seg-light" role="tablist" aria-label="Channel filter">
                        <button v-for="f in channelFilters" :key="f.value" type="button" role="tab" :aria-selected="channelFilter === f.value" :class="{ on: channelFilter === f.value }" @click="setChannelFilter(f.value)">{{ f.label }}</button>
                    </div>
                </div>
                <v-table class="tt-table">
                    <thead>
                        <tr><th>Campaign</th><th>Sent on</th><th class="text-right">Recipients</th><th>Delivered</th><th class="text-right">Opened</th><th class="text-right">Clicked</th><th class="text-right">Revenue</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in pagedCampaigns" :key="c.id">
                            <td>
                                <div class="camp-name">
                                    <span class="camp-ic" :style="{ background: c.channel === 'email' ? '#7c3aed' : '#0f9d6b' }"><v-icon size="15" color="white">{{ c.channel === "email" ? "mdi-email-outline" : "mdi-message-text-outline" }}</v-icon></span>
                                    <div><div class="tt-strong">{{ c.name }}</div><div class="tt-subt">{{ c.segment }}</div></div>
                                </div>
                            </td>
                            <td class="tt-muted">{{ c.date }}</td>
                            <td class="text-right">{{ c.sent ? c.sent.toLocaleString("en-IN") : "-" }}</td>
                            <td><div v-if="c.sent" class="tt-minibar"><span class="tt-minibar-track"><span :style="{ width: c.delivered + '%' }"></span></span><b>{{ c.delivered }}%</b></div><span v-else class="tt-muted">-</span></td>
                            <td class="text-right">{{ c.channel === "email" && c.sent ? c.opened + "%" : "-" }}</td>
                            <td class="text-right">{{ c.sent ? c.clicked + "%" : "-" }}</td>
                            <td class="text-right tt-strong">{{ c.revenue ? money(c.revenue) : "-" }}</td>
                            <td><span class="tt-chip" :class="'tone-' + (tones[c.status] || 'gray')">{{ c.status }}</span></td>
                            <td class="text-right"><v-btn icon variant="text" size="small" aria-label="Duplicate campaign" @click="duplicate(c)"><v-icon size="18" color="#4d6561">mdi-content-copy</v-icon></v-btn></td>
                        </tr>
                        <tr v-if="!pagedCampaigns.length"><td colspan="9" class="text-center tt-muted py-6">No campaigns match. Clear the search or pick another channel.</td></tr>
                    </tbody>
                </v-table>
                <div class="tt-foot">
                    <span class="tt-subt">{{ showing }}</span>
                    <v-spacer />
                    <v-pagination v-model="page" :length="pageCount" :total-visible="5" density="comfortable" size="small" rounded="circle" active-color="#0a5548" />
                </div>
            </section>

            <!-- ═════════════════ AUTOMATIONS ═════════════════ -->
            <div v-else class="mt-4">
                <div class="auto-intro">
                    <div class="tt-strong">Messages that send themselves</div>
                    <div class="tt-subt">Switch on a flow and it runs for every customer who matches the trigger.</div>
                </div>
                <div class="auto-grid">
                    <section v-for="(a, i) in automations" :key="a.name" class="tt-card auto" :class="accents[i % accents.length]">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">{{ a.icon }}</v-icon></div>
                            <div><div class="tt-title">{{ a.name }}</div><div class="tt-subt">{{ a.trigger }}</div></div>
                            <v-spacer />
                            <v-switch v-model="a.on" color="#0f9d6b" density="compact" hide-details inset :aria-label="'Turn ' + a.name + ' on or off'" @update:model-value="toggleAuto(a)" />
                        </div>
                        <div class="tt-body">
                            <p class="auto-desc">{{ a.desc }}</p>
                            <div class="auto-meta">
                                <span class="tt-tag"><v-icon size="12">{{ a.channel === "SMS" ? "mdi-message-text-outline" : "mdi-email-outline" }}</v-icon> {{ a.channel }}</span>
                                <span class="tt-subt">{{ a.sent.toLocaleString("en-IN") }} sent this month</span>
                                <span class="tt-strong auto-conv">{{ a.conv }}% order rate</span>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <v-snackbar v-model="snackbar" color="#0a5548" timeout="3500">{{ snackbarText }}</v-snackbar>
        </div>
    </v-defaults-provider>
</template>

<script>
const C = { teal: "#0f9d6b", blue: "#2f5be7", violet: "#7c3aed", amber: "#f59e0b" };
const SMS_RATE = 0.35; // cost per SMS in taka: replace with your gateway's rate
const keyOf = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;

export default {
    name: "EmailAndSms",

    data() {
        return {
            fieldDefaults: {
                VTextField: { variant: "outlined", density: "compact" },
                VSelect: { variant: "outlined", density: "compact" },
                VAutocomplete: { variant: "outlined", density: "compact" },
                VTextarea: { variant: "outlined", density: "compact" },
            },
            overlayMenu: { contentClass: "tt-overlay" },
            tones: { Sent: "green", Scheduled: "blue", Draft: "gray", Sending: "amber", Failed: "red" },
            accents: ["a-teal", "a-blue", "a-violet", "a-amber", "a-coral", "a-rose"],

            view: "compose",
            views: [{ value: "compose", label: "Compose" }, { value: "campaigns", label: "Campaigns" }, { value: "automations", label: "Automations" }],

            saving: false,
            sending: false,
            snackbar: false,
            snackbarText: "",

            // replace with GET /api/admin/sms/balance
            smsBalance: 4820,
            senderId: "TANJIL",
            senderIds: ["TANJIL", "TANJILTRD"],

            channel: "email",
            channelOptions: [
                { value: "email", label: "Email", hint: "Photos, product cards and a button", icon: "mdi-email-outline", color: "#7c3aed" },
                { value: "sms", label: "SMS", hint: "Short text to the customer's phone", icon: "mdi-message-text-outline", color: "#0f9d6b" },
            ],

            segment: "all",
            frequencyCap: true,
            // replace with GET /api/admin/segments (email = opted in to email, sms = opted in to SMS)
            segments: [
                { value: "all", label: "All customers", hint: "Everyone who opted in", email: 980, sms: 1150 },
                { value: "vip", label: "VIP customers", hint: "Spent ৳ 1,50,000 or more", email: 88, sms: 96 },
                { value: "new", label: "New this month", hint: "First order in 30 days", email: 120, sms: 141 },
                { value: "cart", label: "Left items in cart", hint: "Last 7 days", email: 54, sms: 63 },
                { value: "dhaka", label: "Dhaka customers", hint: "Same-day delivery area", email: 590, sms: 702 },
                { value: "wholesale", label: "Wholesale buyers", hint: "Shops and resellers", email: 36, sms: 38 },
                { value: "inactive", label: "Not ordered in 90 days", hint: "Good for win-back offers", email: 171, sms: 214 },
            ],

            template: "offer",
            subject: "Eid offer: up to 30% off power banks and chargers",
            preheader: "Official warranty, same-day delivery in Dhaka",
            emailBody: "Hi {name},\n\nOur Eid offer is on. Get up to 30% off power banks, chargers and earbuds, all with official warranty.\n\nUse code {coupon} at checkout. The offer ends this Sunday.",
            ctaLabel: "Shop the offer",
            ctaLink: "https://tanjiltraders.com/offers",
            taggedProducts: [],
            smsText: "Tanjil Traders: Eid offer! Up to 30% off power banks and chargers. Code {coupon}. Ends Sunday.",
            shortenLink: true,

            sendMode: "now",
            scheduleDate: "",
            scheduleTime: "20:00",
            testTarget: "",

            mergeTags: [
                { label: "Customer name", tag: "{name}" },
                { label: "Order number", tag: "{order_id}" },
                { label: "Coupon code", tag: "{coupon}" },
            ],
            emojis: ["😀", "😍", "🔥", "✅", "🎉", "❤️", "⭐", "🛒", "📦", "🚚", "💳", "🎧", "🔋", "📱", "⌚", "🎁"],
            modes: [
                { value: "now", label: "Send now", hint: "Goes out right away", icon: "mdi-send-outline" },
                { value: "schedule", label: "Schedule", hint: "Pick a date and time", icon: "mdi-calendar-clock" },
            ],
            bestTimes: [
                { label: "Today 8:00 PM", days: 0, time: "20:00" },
                { label: "Tomorrow 12:30 PM", days: 1, time: "12:30" },
                { label: "Tomorrow 9:00 PM", days: 1, time: "21:00" },
            ],
            emailTemplates: [
                { key: "offer", label: "Offer", icon: "mdi-sale-outline", subject: "Eid offer: up to 30% off power banks and chargers", preheader: "Official warranty, same-day delivery in Dhaka", cta: "Shop the offer", body: "Hi {name},\n\nOur Eid offer is on. Get up to 30% off power banks, chargers and earbuds, all with official warranty.\n\nUse code {coupon} at checkout. The offer ends this Sunday." },
                { key: "arrival", label: "New arrival", icon: "mdi-new-box", subject: "Just landed: new gadgets at Tanjil Traders", preheader: "Be the first to get them", cta: "See what's new", body: "Hi {name},\n\nNew gadgets are in stock today. Official warranty on every item, and same-day delivery inside Dhaka.\n\nTake a look before they sell out." },
                { key: "restock", label: "Back in stock", icon: "mdi-package-variant-closed-check", subject: "Back in stock: the item you wanted", preheader: "Limited units available", cta: "Order now", body: "Hi {name},\n\nYou asked, we restocked. The item you were looking at is available again, but we only have a few units.\n\nOrder now to reserve yours." },
                { key: "cart", label: "Cart reminder", icon: "mdi-cart-outline", subject: "You left something in your cart", preheader: "Your items are saved for you", cta: "Finish my order", body: "Hi {name},\n\nYour cart is waiting. Cash on delivery, bKash and Nagad are all available at checkout.\n\nNeed help choosing? Just reply to this email." },
                { key: "plain", label: "Blank", icon: "mdi-file-outline", subject: "", preheader: "", cta: "", body: "" },
            ],
            smsTemplates: [
                { label: "Offer", text: "Tanjil Traders: Eid offer! Up to 30% off power banks and chargers. Code {coupon}. Ends Sunday." },
                { label: "Order confirmed", text: "Hi {name}, your order {order_id} is confirmed. We will dispatch it within 24 hours. Tanjil Traders" },
                { label: "Out for delivery", text: "Hi {name}, your order {order_id} is out for delivery today. Please keep your phone on. Tanjil Traders" },
                { label: "Back in stock", text: "Good news {name}! The item you asked about is back in stock at Tanjil Traders. Order now before it sells out." },
                { label: "বাংলা অফার", text: "প্রিয় {name}, তানজিল ট্রেডার্সে ঈদ অফার চলছে। পাওয়ার ব্যাংক ও চার্জারে ৩০% পর্যন্ত ছাড়। কোড: {coupon}" },
            ],
            // replace with GET /api/admin/products?search=
            products: [
                { name: "Anker PowerCore 20000", slug: "anker-powercore-20000", price: 4200 },
                { name: "Baseus Bowie E9 Earbuds", slug: "baseus-e9", price: 2350 },
                { name: "Xiaomi Smart Band 8", slug: "xiaomi-band-8", price: 3150 },
                { name: "Baseus 65W GaN Charger", slug: "baseus-65w", price: 3290 },
                { name: "TP-Link Archer AX55", slug: "archer-ax55", price: 8900 },
                { name: "SanDisk Extreme SSD 1TB", slug: "sandisk-1tb", price: 11500 },
            ],

            // ── Campaigns table: replace with GET /api/admin/campaigns ──
            search: "",
            channelFilter: "all",
            channelFilters: [{ value: "all", label: "All" }, { value: "email", label: "Email" }, { value: "sms", label: "SMS" }],
            page: 1,
            perPage: 6,
            campaigns: [
                { id: 1, name: "Eid offer launch", channel: "email", segment: "All customers", date: "12 Nov 2026", sent: 4120, delivered: 98, opened: 41, clicked: 9.2, revenue: 286400, status: "Sent" },
                { id: 2, name: "Eid offer reminder", channel: "sms", segment: "All customers", date: "13 Nov 2026", sent: 5380, delivered: 96, opened: 0, clicked: 6.4, revenue: 142800, status: "Sent" },
                { id: 3, name: "Cart reminder: power banks", channel: "email", segment: "Left items in cart", date: "11 Nov 2026", sent: 312, delivered: 99, opened: 52, clicked: 18.1, revenue: 94500, status: "Sent" },
                { id: 4, name: "11.11 flash sale", channel: "sms", segment: "Dhaka customers", date: "10 Nov 2026", sent: 2840, delivered: 97, opened: 0, clicked: 7.9, revenue: 168200, status: "Sent" },
                { id: 5, name: "Wholesale price list", channel: "email", segment: "Wholesale buyers", date: "8 Nov 2026", sent: 36, delivered: 100, opened: 72, clicked: 31.4, revenue: 212000, status: "Sent" },
                { id: 6, name: "Black Friday teaser", channel: "email", segment: "VIP customers", date: "25 Nov 2026", sent: 0, delivered: 0, opened: 0, clicked: 0, revenue: 0, status: "Scheduled" },
                { id: 7, name: "Black Friday launch", channel: "sms", segment: "All customers", date: "27 Nov 2026", sent: 0, delivered: 0, opened: 0, clicked: 0, revenue: 0, status: "Scheduled" },
                { id: 8, name: "Win-back: we miss you", channel: "email", segment: "Not ordered in 90 days", date: "5 Nov 2026", sent: 171, delivered: 97, opened: 28, clicked: 4.1, revenue: 38600, status: "Sent" },
                { id: 9, name: "Smart band restock", channel: "sms", segment: "New this month", date: "3 Nov 2026", sent: 141, delivered: 98, opened: 0, clicked: 12.8, revenue: 56700, status: "Sent" },
                { id: 10, name: "December gadget guide", channel: "email", segment: "All customers", date: "-", sent: 0, delivered: 0, opened: 0, clicked: 0, revenue: 0, status: "Draft" },
                { id: 11, name: "Welcome offer", channel: "email", segment: "New this month", date: "1 Nov 2026", sent: 120, delivered: 99, opened: 58, clicked: 14.2, revenue: 41200, status: "Sent" },
                { id: 12, name: "Payment failed notice", channel: "sms", segment: "Left items in cart", date: "30 Oct 2026", sent: 47, delivered: 94, opened: 0, clicked: 21.3, revenue: 18900, status: "Sent" },
            ],

            // ── Automations: replace with GET /api/admin/automations ──
            automations: [
                { name: "Welcome email", icon: "mdi-hand-wave-outline", trigger: "When someone creates an account", desc: "Introduces the store and gives a first-order coupon.", channel: "Email", sent: 141, conv: 18.4, on: true },
                { name: "Order confirmation", icon: "mdi-receipt-text-check-outline", trigger: "When an order is placed", desc: "Sends the order number, items and delivery estimate by SMS.", channel: "SMS", sent: 318, conv: 0, on: true },
                { name: "Out for delivery", icon: "mdi-truck-fast-outline", trigger: "When the courier picks up the parcel", desc: "Tells the customer to keep their phone on for the rider.", channel: "SMS", sent: 296, conv: 0, on: true },
                { name: "Cart reminder", icon: "mdi-cart-arrow-down", trigger: "3 hours after leaving items in the cart", desc: "Reminds the customer with a link back to the cart.", channel: "Email", sent: 214, conv: 16.9, on: true },
                { name: "Back in stock", icon: "mdi-package-variant-closed-check", trigger: "When a waiting product is restocked", desc: "Tells everyone who asked about the item that it is available.", channel: "SMS", sent: 63, conv: 24.2, on: false },
                { name: "Win-back offer", icon: "mdi-heart-outline", trigger: "90 days after the last order", desc: "Offers a small discount to bring quiet customers back.", channel: "Email", sent: 171, conv: 5.8, on: false },
                { name: "Review request", icon: "mdi-star-outline", trigger: "7 days after delivery", desc: "Asks for a product review. Reviews help new customers trust you.", channel: "Email", sent: 188, conv: 0, on: true },
                { name: "Warranty reminder", icon: "mdi-shield-check-outline", trigger: "30 days before warranty ends", desc: "Reminds customers to claim service while still covered.", channel: "SMS", sent: 0, conv: 0, on: false },
            ],
        };
    },

    computed: {
        todayKey() {
            return keyOf(new Date());
        },
        smsLow() {
            return this.smsBalance < 1000;
        },
        activeSegment() {
            return this.segments.find((s) => s.value === this.segment) || this.segments[0];
        },
        recipients() {
            const base = this.channel === "email" ? this.activeSegment.email : this.activeSegment.sms;
            return this.frequencyCap ? Math.round(base * 0.96) : base;
        },
        smsInfo() {
            const text = this.smsText || "";
            const chars = text.length;
            const unicode = /[^\x00-\x7F]/.test(text);
            const per = unicode ? (chars <= 70 ? 70 : 67) : chars <= 160 ? 160 : 153;
            return { chars, unicode, per, parts: chars ? Math.ceil(chars / per) : 0 };
        },
        creditsNeeded() {
            return this.recipients * this.smsInfo.parts;
        },
        smsCost() {
            return this.creditsNeeded * SMS_RATE;
        },
        previewParagraphs() {
            return this.fillTags(this.emailBody).split("\n").filter((l) => l.trim());
        },
        previewSms() {
            const link = this.ctaLink ? (this.shortenLink ? " tj.bd/x7Kq" : " " + this.ctaLink) : "";
            return this.smsText ? this.fillTags(this.smsText) + link : "";
        },
        scheduledAt() {
            if (this.sendMode !== "schedule" || !this.scheduleDate) return null;
            const d = new Date(`${this.scheduleDate}T${this.scheduleTime || "00:00"}`);
            return isNaN(d) ? null : d;
        },
        schedulePast() {
            return !!this.scheduledAt && this.scheduledAt.getTime() < Date.now();
        },
        whenSummary() {
            if (this.sendMode === "now") return "Goes out as soon as you press send";
            return this.scheduledAt ? this.scheduledAt.toLocaleString("en-GB", { weekday: "short", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" }) + " (Dhaka time)" : "Choose a date and time";
        },
        sendLabel() {
            if (this.sendMode === "schedule") return this.scheduledAt ? "Schedule campaign" : "Pick a date";
            return "Send now";
        },
        checklist() {
            const list = [];
            list.push({ required: true, ok: this.recipients > 0, text: this.recipients > 0 ? `${this.recipients.toLocaleString("en-IN")} people will receive it` : "Choose an audience with at least one person" });
            if (this.channel === "email") {
                list.push({ required: true, ok: !!this.subject.trim(), text: "Subject line added" });
                list.push({ required: true, ok: !!this.emailBody.trim(), text: "Message written" });
                list.push({ required: false, ok: !!(this.ctaLabel && this.ctaLink), text: this.ctaLabel && this.ctaLink ? "Button has text and a link" : "Tip: add a button so customers know what to do next" });
            } else {
                list.push({ required: true, ok: !!this.smsText.trim(), text: "Message written" });
                list.push({ required: true, ok: this.creditsNeeded <= this.smsBalance, text: this.creditsNeeded <= this.smsBalance ? "Enough SMS credits" : `You need ${(this.creditsNeeded - this.smsBalance).toLocaleString("en-IN")} more credits` });
                list.push({ required: false, ok: this.smsInfo.parts <= 2, text: this.smsInfo.parts <= 2 ? "Message is short enough" : `This is ${this.smsInfo.parts} SMS per person. Shorter costs less.` });
            }
            if (this.sendMode === "schedule") list.push({ required: true, ok: !!this.scheduledAt && !this.schedulePast, text: this.schedulePast ? "Scheduled time is in the past" : "Date and time chosen" });
            return list;
        },
        readyTotal() {
            return this.checklist.filter((c) => c.required).length;
        },
        readyDone() {
            return this.checklist.filter((c) => c.required && c.ok).length;
        },
        readyPct() {
            return this.readyTotal ? Math.round((this.readyDone / this.readyTotal) * 100) : 0;
        },
        canSend() {
            return this.checklist.every((c) => c.ok || !c.required);
        },

        sentCampaigns() {
            return this.campaigns.filter((c) => c.status === "Sent");
        },
        heroTiles() {
            const em = this.sentCampaigns.filter((c) => c.channel === "email");
            const sm = this.sentCampaigns.filter((c) => c.channel === "sms");
            const emSent = em.reduce((a, c) => a + c.sent, 0);
            const smSent = sm.reduce((a, c) => a + c.sent, 0);
            const open = emSent ? Math.round(em.reduce((a, c) => a + c.opened * c.sent, 0) / emSent) : 0;
            const deliv = smSent ? Math.round(sm.reduce((a, c) => a + c.delivered * c.sent, 0) / smSent) : 0;
            return [
                { icon: "mdi-email-fast-outline", color: C.violet, value: emSent.toLocaleString("en-IN"), label: "Emails sent", tag: em.length + " campaigns" },
                { icon: "mdi-email-open-outline", color: C.blue, value: open + "%", label: "Email open rate", tag: open >= 30 ? "Strong" : "Improve", todo: open < 30, progress: open },
                { icon: "mdi-message-check-outline", color: C.teal, value: smSent.toLocaleString("en-IN"), label: "SMS sent", tag: deliv + "% delivered" },
                { icon: "mdi-wallet-outline", color: C.amber, value: this.smsBalance.toLocaleString("en-IN"), label: "SMS credits left", tag: this.smsLow ? "Recharge" : "Enough", todo: this.smsLow, progress: Math.min(100, Math.round((this.smsBalance / 10000) * 100)) },
            ];
        },
        filteredCampaigns() {
            const q = (this.search || "").trim().toLowerCase();
            return this.campaigns.filter((c) => (this.channelFilter === "all" || c.channel === this.channelFilter) && (!q || c.name.toLowerCase().includes(q) || c.segment.toLowerCase().includes(q)));
        },
        pagedCampaigns() {
            return this.filteredCampaigns.slice((this.page - 1) * this.perPage, this.page * this.perPage);
        },
        pageCount() {
            return Math.max(1, Math.ceil(this.filteredCampaigns.length / this.perPage));
        },
        showing() {
            const n = this.filteredCampaigns.length;
            if (!n) return "No results";
            return `Showing ${(this.page - 1) * this.perPage + 1}–${Math.min(this.page * this.perPage, n)} of ${n}`;
        },
    },

    methods: {
        money: (v) => "৳ " + Number(v || 0).toLocaleString("en-IN", { maximumFractionDigits: 2 }),
        notify(text) {
            this.snackbarText = text;
            this.snackbar = true;
        },
        // use the app's global toasts when they exist, otherwise fall back to this page's snackbar
        ok(text) {
            typeof this.showSuccess === "function" ? this.showSuccess(text) : this.notify(text);
        },
        fail(text) {
            typeof this.showError === "function" ? this.showError(text) : this.notify(text);
        },
        fillTags(text) {
            return (text || "").replace(/\{name\}/g, "Nusrat").replace(/\{order_id\}/g, "#TT-10482").replace(/\{coupon\}/g, "EID26");
        },
        newCampaign() {
            this.view = "compose";
            this.$nextTick(() => window.scrollTo({ top: 0, behavior: "smooth" }));
        },
        applyTemplate(t) {
            this.template = t.key;
            this.subject = t.subject;
            this.preheader = t.preheader;
            this.emailBody = t.body;
            this.ctaLabel = t.cta;
        },
        applyBest(b) {
            const d = new Date();
            d.setDate(d.getDate() + b.days);
            this.scheduleDate = keyOf(d);
            this.scheduleTime = b.time;
        },
        setChannelFilter(v) {
            this.channelFilter = v;
            this.page = 1;
        },
        duplicate(c) {
            this.channel = c.channel;
            this.view = "compose";
            this.notify(`Started a new campaign from "${c.name}". Edit it and send.`);
            this.$nextTick(() => window.scrollTo({ top: 0, behavior: "smooth" }));
        },
        toggleAuto(a) {
            // PATCH /api/admin/automations/:id { on }
            this.notify(`${a.name} is ${a.on ? "on" : "off"}.`);
        },

        buildPayload(status) {
            return {
                status,
                channel: this.channel,
                segment: this.segment,
                skipRecentlyMessaged: this.frequencyCap,
                subject: this.channel === "email" ? this.subject : null,
                preheader: this.channel === "email" ? this.preheader : null,
                body: this.channel === "email" ? this.emailBody : this.smsText,
                cta: this.channel === "email" ? { label: this.ctaLabel, link: this.ctaLink } : null,
                link: this.channel === "sms" ? this.ctaLink : null,
                shortenLink: this.channel === "sms" ? this.shortenLink : null,
                senderId: this.channel === "sms" ? this.senderId : null,
                products: this.taggedProducts.map((p) => p.slug),
                sendMode: this.sendMode,
                scheduledAt: this.scheduledAt ? this.scheduledAt.toISOString() : null,
                timezone: "Asia/Dhaka",
            };
        },
        async saveDraft() {
            this.saving = true;
            try {
                const response = await this.axios.post("/api/admin/campaigns", this.buildPayload("draft"));
                if (response.data.success !== false) this.ok("Draft saved.");
                else this.fail(response.data.message || "Save failed.");
            } catch (error) {
                console.error(error);
                this.fail("An error occurred while saving the draft.");
            } finally {
                this.saving = false;
            }
        },
        async send() {
            if (!this.canSend) return;
            this.sending = true;
            try {
                const response = await this.axios.post("/api/admin/campaigns", this.buildPayload(this.sendMode === "now" ? "sending" : "scheduled"));
                if (response.data.success !== false) {
                    this.ok(this.sendMode === "now" ? "Campaign is sending." : "Campaign scheduled.");
                    this.view = "campaigns";
                } else {
                    this.fail(response.data.message || "Sending failed.");
                }
            } catch (error) {
                console.error(error);
                this.fail("An error occurred while sending.");
            } finally {
                this.sending = false;
            }
        },
        async sendTest() {
            try {
                // POST /api/admin/campaigns/test { channel, to, subject, body }
                await this.axios.post("/api/admin/campaigns/test", { ...this.buildPayload("test"), to: this.testTarget });
                this.ok("Test sent to " + this.testTarget + ".");
            } catch (error) {
                console.error(error);
                this.fail("The test could not be sent.");
            }
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
.tt-muted { color: var(--muted); }
.tt-strong { font-weight: 600; color: var(--ink); }

/* ───────── Hero ───────── */
.tt-hero { position: relative; overflow: hidden; color: #fff; padding: 18px 22px; border-radius: 18px; background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%); box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95); }
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.15; letter-spacing: -0.5px; color: #fff; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.22); color: #fff; }
.tt-pill-live { color: #6ee7b7; }
.tt-pill-warn { color: #fcd34d; border-color: rgba(252, 211, 77, 0.5); }
.tt-pulse { width: 7px; height: 7px; border-radius: 50%; background: currentColor; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }
.tt-seg { display: inline-flex; padding: 3px; border-radius: 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-seg button { border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: rgba(255, 255, 255, 0.75); padding: 6px 15px; border-radius: 9px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-seg button:hover { color: #fff; }
.tt-seg button.on { background: rgba(255, 255, 255, 0.22); color: #fff; font-weight: 600; }
.tt-seg button:focus-visible { outline: 2px solid #6ee7b7; outline-offset: 2px; }
.tt-seg-light { background: #eef3f1; border-color: var(--line); }
.tt-seg-light button { color: var(--muted); padding: 5px 14px; font-size: 12.5px; }
.tt-seg-light button:hover { color: var(--ink); }
.tt-seg-light button.on { background: #fff; color: #0a5548; box-shadow: 0 1px 3px rgba(15, 42, 38, 0.14); }
.tt-seg-light button:focus-visible { outline-color: #0f9d6b; }
.tt-btn-ghost { height: 38px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 38px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 13px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.22; filter: blur(10px); }
.tt-hero-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-txt { flex: 1; min-width: 0; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.3px; white-space: nowrap; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.82); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tt-hero-progress { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: rgba(255, 255, 255, 0.14); }
.tt-hero-progress span { display: block; height: 100%; background: var(--hue); }
.tt-live { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(110, 231, 183, 0.5); white-space: nowrap; }
.tt-live.todo { color: #fcd34d; background: rgba(245, 158, 11, 0.16); border-color: rgba(252, 211, 77, 0.5); }
@media (max-width: 1199px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Layout & cards ───────── */
.tt-layout { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 16px; align-items: start; margin-top: 16px; }
.tt-main { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.tt-sticky { position: sticky; top: 12px; display: flex; flex-direction: column; gap: 16px; }
.tt-card { --accent: #2f5be7; --tint: #e6edff; position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35); overflow: hidden; color: var(--ink); }
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, var(--accent), transparent 85%); }
.a-blue { --accent: #2f5be7; --tint: #e6edff; }
.a-teal { --accent: #0f9d6b; --tint: #dff5ec; }
.a-violet { --accent: #7c3aed; --tint: #ebe5ff; }
.a-coral { --accent: #ef6a45; --tint: #fce1d8; }
.a-amber { --accent: #f59e0b; --tint: #fdecc8; }
.a-rose { --accent: #ec4899; --tint: #fbe1ec; }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 6px; }
.tt-head-wrap { flex-wrap: wrap; padding-bottom: 12px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-title { font-size: 14px; font-weight: 600; line-height: 1.3; }
.tt-tag { display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; color: var(--accent); background: var(--tint); }
.tt-tag-warn { color: #8a5a00; background: #fdecc8; }
.tt-body { padding: 10px 18px 18px; }

/* channel + audience tiles */
.ch-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px; }
.ch { position: relative; display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1.5px solid var(--line); border-radius: 13px; background: #fff; font: inherit; color: var(--ink); text-align: left; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.ch:hover { border-color: var(--c); }
.ch:focus-visible, .seg:focus-visible, .tpl:focus-visible, .tt-modes button:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.ch.on { border-color: var(--c); background: color-mix(in srgb, var(--c) 7%, white); }
.ch-ic { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex: none; box-shadow: 0 8px 14px -8px rgba(15, 42, 38, 0.5); }
.ch-txt { min-width: 0; display: flex; flex-direction: column; line-height: 1.3; }
.ch-txt b { font-size: 13px; font-weight: 600; }
.ch-txt small { font-size: 11px; color: var(--muted); }
.ch-check { position: absolute; top: 8px; right: 8px; width: 18px; height: 18px; border-radius: 50%; background: var(--c); display: grid; place-items: center; opacity: 0; transform: scale(0.6); transition: 0.15s; }
.ch.on .ch-check { opacity: 1; transform: none; }

.seg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
.seg { position: relative; display: flex; flex-direction: column; gap: 2px; text-align: left; padding: 10px 12px; border: 1.5px solid var(--line); border-radius: 12px; background: #fff; font: inherit; color: var(--ink); cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.seg:hover { border-color: #b7e6cd; }
.seg.on { border-color: #0f9d6b; background: #f2fbf7; }
.seg b { font-size: 13px; font-weight: 600; padding-right: 52px; }
.seg small { font-size: 11px; color: var(--muted); }
.seg span { position: absolute; top: 10px; right: 10px; font-size: 11.5px; font-weight: 700; color: #0a5548; background: #dff5ec; border-radius: 12px; padding: 1px 9px; }

.tpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 8px; }
.tpl { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1.5px solid var(--line); border-radius: 11px; background: #fff; font: inherit; font-size: 12.5px; color: var(--ink); cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tpl:hover { border-color: #cdbff5; }
.tpl.on { border-color: #7c3aed; background: #faf8ff; }
.tpl-ic { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; background: #ebe5ff; color: #7c3aed; flex: none; }
.tpl b { font-weight: 600; }

.tags-row { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.tags-row button { font: inherit; font-size: 12px; font-weight: 600; color: #5b3fd1; background: #ebe5ff; border: 1px solid #d6caff; border-radius: 14px; padding: 2px 12px; cursor: pointer; }
.tags-row button:hover { background: #e0d6ff; }

.tt-toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; padding-top: 6px; }
.tt-tool { color: #4d6561 !important; }
.sms-meter { margin-top: 12px; }
.sms-meter small { display: block; margin-top: 4px; font-size: 11.5px; color: var(--muted); }
.sms-meter.warn small { color: #8a5a00; }
.sms-meter.warn .tt-meter span { background: linear-gradient(90deg, #f59e0b, #ef6a45); }
.tt-meter { height: 6px; border-radius: 6px; background: #e8eeec; overflow: hidden; margin: 4px 0 10px; }
.tt-meter span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0f9d6b, #6ee7b7); transition: width 0.3s; }
.sms-meter .tt-meter { margin-bottom: 0; }

.tt-switch-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 0 0; border-top: 1px dashed #d5dfdc; margin-top: 14px; }
.tt-switch-row b { font-size: 12.8px; font-weight: 600; display: block; }
.tt-switch-row span { font-size: 11.5px; color: var(--muted); }

/* schedule */
.tt-modes { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.tt-modes button { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1.5px solid var(--line); border-radius: 13px; background: #fff; font: inherit; color: var(--ink); text-align: left; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tt-modes button:hover { border-color: #f3d78a; }
.tt-modes button.on { border-color: #f59e0b; background: #fffaf0; }
.tt-mode-ic { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #f3f7f6; color: #4d6561; flex: none; }
.tt-modes button.on .tt-mode-ic { background: #f59e0b; color: #fff; box-shadow: 0 8px 14px -8px #f59e0b; }
.tt-modes b { display: block; font-size: 13px; font-weight: 600; }
.tt-modes small { font-size: 11px; color: var(--muted); }
.tt-best { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
.tt-best button { font: inherit; font-size: 12px; font-weight: 600; color: #8a5a00; background: #fdecc8; border: 1px solid #f3d78a; border-radius: 14px; padding: 2px 12px; cursor: pointer; }
.tt-best button:hover { background: #fbe2a8; }
.tt-warn { display: flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 12.5px; color: #b4381a; }

/* preview */
.tt-stage { border-radius: 14px; padding: 14px; background-color: #f0f5f3; background-image: radial-gradient(#dbe6e2 1px, transparent 1px); background-size: 16px 16px; }
.pv { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; background: #fff; font-size: 13px; box-shadow: 0 8px 20px -14px rgba(15, 42, 38, 0.5); }
.pv-inbox { display: flex; flex-direction: column; padding: 10px 14px; border-bottom: 1px solid var(--line); line-height: 1.4; }
.pv-inbox b { font-size: 12.5px; }
.pv-inbox span { font-size: 12.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pv-inbox small { font-size: 11.5px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pv-mail { background: #f3f7f6; }
.pv-mail-brand { display: flex; align-items: center; gap: 6px; padding: 10px 14px; background: linear-gradient(135deg, #053d35, #0a5548); color: #fff; font-weight: 600; font-size: 13px; }
.pv-mail-body { padding: 14px; background: #fff; margin: 10px; border-radius: 10px; }
.pv-mail-body p { margin: 0 0 10px; line-height: 1.55; overflow-wrap: anywhere; }
.pv-empty { color: var(--muted); }
.pv-prod { display: flex; align-items: center; gap: 10px; padding: 8px; border: 1px solid var(--line); border-radius: 10px; margin-bottom: 8px; }
.pv-prod > span { width: 36px; height: 36px; border-radius: 9px; background: #e3f6ee; display: grid; place-items: center; flex: none; }
.pv-prod b { display: block; font-size: 12.5px; }
.pv-prod small { color: #0f9d6b; font-weight: 700; font-size: 12px; }
.pv-btn { display: inline-block; margin-top: 4px; background: #0f9d6b; color: #fff; font-weight: 600; font-size: 12.5px; padding: 8px 18px; border-radius: 9px; }
.pv-mail-foot { padding: 4px 14px 12px; font-size: 10.5px; color: var(--muted); text-align: center; }
.pv-phone { background: #e7eef1; padding: 0; border: 0; }
.pv-phone-head { display: flex; align-items: center; gap: 9px; padding: 10px 14px; background: #fff; border-bottom: 1px solid var(--line); }
.pv-av { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #0a5548, #0f9d6b); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 12px; flex: none; }
.pv-sms { padding: 18px 14px 22px; }
.pv-sms div { display: inline-block; max-width: 88%; background: #fff; border-radius: 16px 16px 16px 4px; padding: 9px 13px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; box-shadow: 0 1px 1px rgba(15, 42, 38, 0.08); }
.pv-sms small { display: block; margin: 3px 6px 0; font-size: 10.5px; color: var(--muted); }

/* checklist + cost */
.tt-check { display: flex; align-items: flex-start; gap: 8px; padding: 5px 0; font-size: 12.5px; line-height: 1.45; }
.tt-check.bad { color: #b4381a; }
.cost-box { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px; margin-top: 12px; padding: 10px 12px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); }
.cost-box small { display: block; font-size: 11px; color: var(--muted); }
.cost-box b { font-size: 14.5px; font-weight: 700; }
.tt-test { flex: 1; height: 38px !important; border-color: var(--line) !important; color: #3d5450 !important; border-radius: 10px !important; font-weight: 600; }
.tt-publish { margin-top: 10px; height: 42px !important; color: #fff !important; font-weight: 600; border-radius: 12px !important; box-shadow: 0 12px 20px -14px #0f9d6b; }

/* campaigns table */
.tt-search { max-width: 260px; min-width: 180px; }
.tt-search :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-table { background: transparent !important; }
.tt-table th { font-size: 12px !important; font-weight: 600 !important; background: #0a5548 !important; color: #e6f4ef !important; white-space: nowrap; border-bottom: 0 !important; height: 42px !important; padding: 0 14px !important; }
.tt-table td { font-size: 13.5px; border-bottom: 1px solid #eef3f1 !important; height: 56px !important; padding: 0 14px !important; }
.tt-table tbody tr:hover { background: #f4f9f7; }
.tt-table td.text-right, .tt-table th.text-right { white-space: nowrap; }
.camp-name { display: flex; align-items: center; gap: 10px; }
.camp-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-minibar { display: flex; align-items: center; gap: 8px; min-width: 110px; }
.tt-minibar-track { flex: 1; height: 6px; border-radius: 6px; background: #e8eeec; overflow: hidden; }
.tt-minibar-track span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0f9d6b, #6ee7b7); }
.tt-minibar b { font-size: 12px; min-width: 36px; }
.tt-chip { display: inline-block; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; white-space: nowrap; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-blue { background: #e6edff; color: #1f4e9c; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-red { background: #fce1d8; color: #b4381a; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.tt-foot { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 6px 14px; border-top: 1px solid var(--line); background: var(--bg2); }

/* automations */
.auto-intro { margin: 0 2px 12px; }
.auto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
.auto-desc { font-size: 13px; line-height: 1.55; margin: 4px 0 12px; min-height: 40px; }
.auto-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 12px; padding-top: 10px; border-top: 1px dashed #d5dfdc; }
.auto-conv { margin-left: auto; font-size: 12.5px; }

@media (max-width: 1180px) { .tt-layout { grid-template-columns: minmax(0, 1fr); } .tt-sticky { position: static; } }
@media (max-width: 640px) { .tt-modes { grid-template-columns: 1fr; } }
@media (prefers-reduced-motion: reduce) { .ch, .ch-check, .seg, .tpl, .tt-meter span { transition: none; } }
</style>

<!-- Menus open outside the page, so they need their own pinned colours -->
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
.tt-emoji-pop { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; padding: 8px; background: #fff; border-radius: 12px; width: 270px; }
.tt-emoji-pop button { font-size: 19px; border: 0; background: transparent; border-radius: 6px; padding: 3px; cursor: pointer; }
.tt-emoji-pop button:hover { background: #eaf8f1; }
.test-pop { width: 290px; padding: 12px; background: #fff; border-radius: 12px; }
</style>
