<template>
    <v-defaults-provider :defaults="fieldDefaults">
        <div class="tt-page">
            <!-- ═════════ Hero (same family as Dashboard and Inbox) ═════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="tt-crumbs">
                            <router-link to="/admin/posts">Posts</router-link>
                            <v-icon size="13">mdi-chevron-right</v-icon>
                            <span>Create post</span>
                        </div>
                        <div class="d-flex flex-wrap align-center ga-3 mt-1">
                            <h1 class="tt-h1">Create post</h1>
                            <span class="tt-pill" :class="{ 'tt-pill-warn': dirty }">
                                <v-icon size="13">{{ dirty ? "mdi-circle-edit-outline" : "mdi-check-circle-outline" }}</v-icon>{{ dirty ? "Unsaved draft" : "Nothing to save yet" }}
                            </span>
                        </div>
                        <div class="tt-hero-sub">Write once, preview on every network, and publish or schedule in one go</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <v-btn variant="text" class="text-none tt-btn-text" @click="$router.push('/admin/posts')">Cancel</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-content-save-outline" :loading="saving" @click="saveDraft">Save draft</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-primary" :disabled="!canPublish" :loading="publishing" :prepend-icon="publishMode === 'now' ? 'mdi-send' : 'mdi-calendar-clock'" @click="publish">
                            {{ publishLabel }}
                        </v-btn>
                    </div>
                </div>

                <div class="tt-hero-strip">
                    <div v-for="h in heroTiles" :key="h.label" class="tt-hero-item" :style="{ '--hue': h.color }">
                        <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                        <div class="tt-hero-txt">
                            <div class="tt-hero-value">{{ h.value }}</div>
                            <div class="tt-hero-label">{{ h.label }}</div>
                        </div>
                        <span class="tt-live" :class="{ todo: !h.ok }">{{ h.tag }}</span>
                    </div>
                </div>
            </header>

            <div class="tt-layout">
                <!-- ═════════════ MAIN ═════════════ -->
                <div class="tt-main">
                    <!-- Channels -->
                    <section class="tt-card a-blue">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-share-variant-outline</v-icon></div>
                            <div><div class="tt-title">Where to post</div><div class="tt-subt">Pick the networks that should get this post</div></div>
                            <v-spacer />
                            <span class="tt-tag">{{ selectedKeys.length }} selected</span>
                        </div>
                        <div class="tt-body">
                            <div class="ch-grid">
                                <button v-for="(c, key) in channels" :key="key" type="button" class="ch" :class="{ on: c.selected && c.connected, off: !c.connected }" :style="{ '--c': c.color }" :aria-pressed="c.selected && c.connected" @click="toggleChannel(key)">
                                    <span class="ch-ic" :style="{ background: c.color }"><v-icon size="19" color="white">{{ c.icon }}</v-icon></span>
                                    <span class="ch-txt"><b>{{ c.label }}</b><small>{{ c.connected ? c.account : "Not connected" }}</small></span>
                                    <span class="ch-check"><v-icon size="13" color="white">mdi-check</v-icon></span>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Caption -->
                    <section class="tt-card a-teal">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-text-box-edit-outline</v-icon></div>
                            <div><div class="tt-title">Caption</div><div class="tt-subt">What do you want your customers to know?</div></div>
                            <v-spacer />
                            <span class="tt-tag">{{ caption.length }} characters</span>
                        </div>
                        <div class="tt-body">
                            <v-textarea v-model="caption" rows="5" auto-grow hide-details placeholder="Share a new arrival, an offer or a tip…" />

                            <div class="tt-toolbar">
                                <v-menu :close-on-content-click="false" content-class="tt-overlay">
                                    <template #activator="{ props }">
                                        <v-btn v-bind="props" size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-emoticon-happy-outline">Emoji</v-btn>
                                    </template>
                                    <div class="tt-emoji-pop">
                                        <button v-for="e in emojis" :key="e" @click="caption += e">{{ e }}</button>
                                    </div>
                                </v-menu>
                                <v-menu content-class="tt-overlay">
                                    <template #activator="{ props }">
                                        <v-btn v-bind="props" size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-text-box-outline">Templates</v-btn>
                                    </template>
                                    <v-list density="compact" max-width="360">
                                        <v-list-item v-for="t in captionTemplates" :key="t.label" :title="t.label" :subtitle="t.text" @click="caption = t.text" />
                                    </v-list>
                                </v-menu>
                                <v-btn size="small" variant="text" class="text-none tt-tool tt-tool-ai" prepend-icon="mdi-creation" :disabled="!taggedProducts.length" @click="draftFromProducts">Draft from tagged products</v-btn>
                                <v-spacer />
                                <v-btn size="small" variant="text" class="text-none tt-tool" prepend-icon="mdi-eraser" :disabled="!caption" @click="caption = ''">Clear</v-btn>
                            </div>

                            <div v-if="selectedKeys.length" class="tt-limits">
                                <span v-for="k in selectedKeys" :key="k" class="tt-limit" :class="{ over: captionLength(k) > channels[k].limit }" :style="{ '--c': channels[k].color }">
                                    <v-icon size="13">{{ channels[k].icon }}</v-icon>
                                    <i><u :style="{ width: Math.min(100, (captionLength(k) / channels[k].limit) * 100) + '%' }"></u></i>
                                    {{ captionLength(k) }}/{{ channels[k].limit }}
                                </span>
                            </div>

                            <v-row dense class="mt-3">
                                <v-col cols="12">
                                    <v-combobox v-model="hashtags" :items="suggestedTags" label="Hashtags" multiple chips closable-chips prepend-inner-icon="mdi-pound" hide-details :menu-props="overlayMenu" />
                                </v-col>
                                <v-col cols="12" md="7">
                                    <v-text-field v-model="link" label="Link (optional)" prepend-inner-icon="mdi-link-variant" placeholder="https://tanjiltraders.com/…" hide-details />
                                </v-col>
                                <v-col cols="12" md="5">
                                    <v-select v-model="cta" :items="ctaOptions" label="Button (Facebook)" prepend-inner-icon="mdi-gesture-tap-button" hide-details :menu-props="overlayMenu" />
                                </v-col>
                            </v-row>

                            <div class="tt-switch-row">
                                <div><b>Write a different caption per network</b><span>Shorter for fast feeds, longer for Facebook and YouTube</span></div>
                                <v-switch v-model="customize" color="#0f9d6b" density="compact" hide-details inset />
                            </div>
                            <v-expand-transition>
                                <div v-if="customize && selectedKeys.length" class="tt-custom">
                                    <div v-for="k in selectedKeys" :key="k" class="tt-custom-item">
                                        <div class="tt-custom-label" :style="{ color: channels[k].color }"><v-icon size="15" :color="channels[k].color">{{ channels[k].icon }}</v-icon>{{ channels[k].label }}</div>
                                        <v-textarea v-model="overrides[k]" rows="2" auto-grow hide-details placeholder="Leave empty to use the main caption" />
                                    </div>
                                </div>
                            </v-expand-transition>
                        </div>
                    </section>

                    <!-- Media -->
                    <section class="tt-card a-violet">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-image-multiple-outline</v-icon></div>
                            <div><div class="tt-title">Photos &amp; video</div><div class="tt-subt">The first file becomes the cover</div></div>
                            <v-spacer />
                            <span class="tt-tag">{{ media.length }}/10</span>
                        </div>
                        <div class="tt-body">
                            <label class="tt-drop" :class="{ drag: dragging }" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
                                <span class="tt-drop-ic"><v-icon size="24" color="#7c3aed">mdi-cloud-upload-outline</v-icon></span>
                                <b>Drop photos or a video here, or click to browse</b>
                                <small>JPG, PNG, WebP up to 8MB · MP4 / MOV up to 500MB · up to 10 files</small>
                                <input type="file" accept="image/*,video/*" multiple hidden @change="onFiles" />
                            </label>

                            <div v-if="media.length" class="tt-media-grid">
                                <div v-for="(m, i) in media" :key="m.id" class="tt-media" :class="{ cover: i === 0 }">
                                    <video v-if="m.type === 'video'" :src="m.url" muted />
                                    <img v-else :src="m.url" :alt="m.alt || m.name" />
                                    <span v-if="i === 0" class="tt-media-badge">Cover</span>
                                    <span v-if="m.type === 'video'" class="tt-media-play"><v-icon size="22" color="white">mdi-play</v-icon></span>
                                    <div class="tt-media-acts">
                                        <button type="button" :disabled="i === 0" aria-label="Move earlier" @click="moveMedia(i, -1)"><v-icon size="14" color="white">mdi-arrow-left</v-icon></button>
                                        <button type="button" :disabled="i === media.length - 1" aria-label="Move later" @click="moveMedia(i, 1)"><v-icon size="14" color="white">mdi-arrow-right</v-icon></button>
                                        <button type="button" aria-label="Remove" @click="media.splice(i, 1)"><v-icon size="14" color="white">mdi-delete-outline</v-icon></button>
                                    </div>
                                </div>
                            </div>
                            <v-text-field v-if="media.length && media[0].type === 'image'" v-model="media[0].alt" class="mt-3" label="Alt text for the cover image" prepend-inner-icon="mdi-eye-outline" hint="Helps accessibility and search" persistent-hint />
                        </div>
                    </section>

                    <!-- Products -->
                    <section class="tt-card a-coral">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-package-variant-closed</v-icon></div>
                            <div><div class="tt-title">Tag products</div><div class="tt-subt">Trace orders back to the post that sold them</div></div>
                            <v-spacer />
                            <span v-if="taggedProducts.length" class="tt-tag">{{ taggedProducts.length }} tagged</span>
                        </div>
                        <div class="tt-body">
                            <v-autocomplete v-model="taggedProducts" :items="products" item-title="name" item-value="slug" label="Products featured in this post" multiple chips closable-chips return-object hide-details prepend-inner-icon="mdi-package-variant-closed" :menu-props="overlayMenu" />
                            <div class="tt-switch-row">
                                <div><b>Add price and link to the caption</b><span>Each tagged product is listed with its price and store link</span></div>
                                <v-switch v-model="autoProductLines" color="#0f9d6b" density="compact" hide-details inset />
                            </div>
                            <div class="tt-switch-row">
                                <div><b>Track clicks with UTM tags</b><span>Adds utm_source and utm_campaign so orders show which post sold them</span></div>
                                <v-switch v-model="utm" color="#0f9d6b" density="compact" hide-details inset />
                            </div>
                            <v-expand-transition>
                                <v-text-field v-if="utm" v-model="campaign" class="mt-2" label="Campaign name" prepend-inner-icon="mdi-bullhorn-outline" hide-details />
                            </v-expand-transition>
                        </div>
                    </section>

                    <!-- Schedule -->
                    <section class="tt-card a-amber">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-calendar-clock</v-icon></div>
                            <div><div class="tt-title">When to publish</div><div class="tt-subt">{{ whenSummary }}</div></div>
                        </div>
                        <div class="tt-body">
                            <div class="tt-modes" role="radiogroup" aria-label="Publish time">
                                <button v-for="m in modes" :key="m.value" type="button" role="radio" :aria-checked="publishMode === m.value" :class="{ on: publishMode === m.value }" @click="publishMode = m.value">
                                    <span class="tt-mode-ic"><v-icon size="18">{{ m.icon }}</v-icon></span>
                                    <span><b>{{ m.label }}</b><small>{{ m.hint }}</small></span>
                                </button>
                            </div>

                            <v-expand-transition>
                                <div v-if="publishMode === 'schedule'" class="mt-4">
                                    <v-row dense>
                                        <v-col cols="12" sm="5">
                                            <v-menu v-model="dateMenu" :close-on-content-click="false" content-class="tt-overlay">
                                                <template #activator="{ props }">
                                                    <v-text-field v-bind="props" :model-value="dateLabel" label="Date" prepend-inner-icon="mdi-calendar-outline" readonly hide-details />
                                                </template>
                                                <v-date-picker v-model="scheduleDate" color="#0f9d6b" @update:model-value="dateMenu = false" />
                                            </v-menu>
                                        </v-col>
                                        <v-col cols="6" sm="3">
                                            <v-text-field v-model="scheduleTime" type="time" label="Time" hide-details />
                                        </v-col>
                                        <v-col cols="6" sm="4">
                                            <v-select v-model="timezone" :items="timezones" label="Time zone" hide-details :menu-props="overlayMenu" />
                                        </v-col>
                                    </v-row>
                                    <div class="tt-best">
                                        <span class="tt-subt"><v-icon size="14">mdi-lightning-bolt-outline</v-icon> Popular times for your audience</span>
                                        <button v-for="b in bestTimes" :key="b.label" type="button" @click="applyBest(b)">{{ b.label }}</button>
                                    </div>
                                    <div v-if="schedulePast" class="tt-warn"><v-icon size="15" color="#b4381a">mdi-alert-circle-outline</v-icon> That time has already passed. Choose a future time.</div>
                                </div>
                            </v-expand-transition>
                        </div>
                    </section>

                    <!-- Engagement -->
                    <section class="tt-card a-rose">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-comment-multiple-outline</v-icon></div>
                            <div><div class="tt-title">Comments &amp; replies</div><div class="tt-subt">Decide who answers the people who respond</div></div>
                        </div>
                        <div class="tt-body">
                            <div class="tt-switch-row first">
                                <div><b>Allow comments</b><span>Turn off to hide the comment box where the network supports it</span></div>
                                <v-switch v-model="allowComments" color="#0f9d6b" density="compact" hide-details inset />
                            </div>
                            <div class="tt-switch-row">
                                <div><b>Send comments to the Message Center</b><span>Customers who comment appear in your inbox so your team can reply</span></div>
                                <v-switch v-model="routeToInbox" color="#0f9d6b" density="compact" hide-details inset />
                            </div>
                            <v-expand-transition>
                                <v-select v-if="routeToInbox" v-model="replyOwner" :items="staff" label="Assign replies to" class="mt-2" prepend-inner-icon="mdi-account-outline" hide-details :menu-props="overlayMenu" />
                            </v-expand-transition>
                            <v-text-field v-if="channels.instagram.selected && channels.instagram.connected" v-model="firstComment" class="mt-3" label="First comment (Instagram)" prepend-inner-icon="mdi-comment-text-outline" placeholder="Hashtags or extra details posted as the first comment" hide-details />
                        </div>
                    </section>
                </div>

                <!-- ═════════════ SIDE: preview + checklist ═════════════ -->
                <aside class="tt-side">
                    <div class="tt-sticky">
                        <section class="tt-card a-blue">
                            <div class="tt-head">
                                <div class="tt-head-ic"><v-icon size="16">mdi-eye-outline</v-icon></div>
                                <div><div class="tt-title">Live preview</div><div class="tt-subt">{{ selectedKeys.length ? "How it looks on " + channels[previewKey].label : "Updates as you type" }}</div></div>
                            </div>
                            <div class="tt-body">
                                <div v-if="selectedKeys.length" class="tt-prev-tabs" role="tablist" aria-label="Preview network">
                                    <button v-for="k in selectedKeys" :key="k" type="button" role="tab" :aria-selected="previewKey === k" :class="{ on: previewKey === k }" :style="{ '--c': channels[k].color }" @click="previewKey = k">
                                        <v-icon size="15" :color="previewKey === k ? 'white' : channels[k].color">{{ channels[k].icon }}</v-icon>{{ channels[k].short }}
                                    </button>
                                </div>

                                <div class="tt-stage">
                                    <div v-if="!selectedKeys.length" class="tt-prev-empty"><v-icon size="26" color="#9db3ae">mdi-eye-off-outline</v-icon><span>Pick at least one network to see a preview</span></div>

                                    <!-- Instagram -->
                                    <div v-else-if="previewKey === 'instagram'" class="pv">
                                        <div class="pv-head"><span class="pv-av">T</span><b>tanjiltraders</b><v-icon size="18" class="ml-auto">mdi-dots-horizontal</v-icon></div>
                                        <div class="pv-media sq"><template v-if="media.length"><video v-if="media[0].type === 'video'" :src="media[0].url" muted /><img v-else :src="media[0].url" alt="" /></template><div v-else class="pv-ph"><v-icon size="30">mdi-image-outline</v-icon><span>Instagram needs a photo or video</span></div><span v-if="media.length > 1" class="pv-count">1/{{ media.length }}</span></div>
                                        <div class="pv-ig-acts"><v-icon size="22">mdi-heart-outline</v-icon><v-icon size="22">mdi-comment-outline</v-icon><v-icon size="22">mdi-send-outline</v-icon><v-icon size="22" class="ml-auto">mdi-bookmark-outline</v-icon></div>
                                        <div class="pv-cap"><b>tanjiltraders</b> {{ previewCaption || "Your caption appears here…" }}</div>
                                    </div>

                                    <!-- Telegram -->
                                    <div v-else-if="previewKey === 'telegram'" class="pv pv-tg">
                                        <div class="pv-tg-bubble">
                                            <div v-if="media.length" class="pv-media"><video v-if="media[0].type === 'video'" :src="media[0].url" muted /><img v-else :src="media[0].url" alt="" /></div>
                                            <div class="pv-tg-text">{{ previewCaption || "Your message appears here…" }}</div>
                                            <div v-if="link" class="pv-tg-link">{{ link }}</div>
                                            <div class="pv-tg-time">now</div>
                                        </div>
                                    </div>

                                    <!-- Facebook / LinkedIn / TikTok / YouTube -->
                                    <div v-else class="pv">
                                        <div class="pv-head"><span class="pv-av" :style="{ background: channels[previewKey].color }">T</span><div><b>Tanjil Traders</b><small>Just now · {{ channels[previewKey].label }}</small></div></div>
                                        <div class="pv-cap pad">{{ previewCaption || "Your caption appears here…" }}</div>
                                        <div v-if="media.length" class="pv-media"><video v-if="media[0].type === 'video'" :src="media[0].url" muted /><img v-else :src="media[0].url" alt="" /><span v-if="media.length > 1" class="pv-count">1/{{ media.length }}</span></div>
                                        <div v-else-if="channels[previewKey].needsVideo" class="pv-media"><div class="pv-ph"><v-icon size="30">mdi-video-outline</v-icon><span>{{ channels[previewKey].label }} needs a video</span></div></div>
                                        <div v-if="link && previewKey === 'facebook'" class="pv-link"><small>tanjiltraders.com</small><b>{{ link }}</b><span v-if="cta !== 'None'" class="pv-cta">{{ cta }}</span></div>
                                        <div class="pv-fb-acts"><span><v-icon size="16">mdi-thumb-up-outline</v-icon>Like</span><span><v-icon size="16">mdi-comment-outline</v-icon>Comment</span><span><v-icon size="16">mdi-share-outline</v-icon>Share</span></div>
                                    </div>
                                </div>

                                <div v-if="selectedKeys.length && taggedProducts.length" class="pv-products">
                                    <span v-for="p in taggedProducts" :key="p.slug"><v-icon size="13" color="#0f9d6b">mdi-tag-outline</v-icon>{{ p.name }} · {{ money(p.price) }}</span>
                                </div>
                            </div>
                        </section>

                        <section class="tt-card a-teal">
                            <div class="tt-head">
                                <div class="tt-head-ic"><v-icon size="16">mdi-clipboard-check-outline</v-icon></div>
                                <div><div class="tt-title">Ready to publish?</div><div class="tt-subt">{{ readyDone }} of {{ readyTotal }} required checks passed</div></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-meter"><span :style="{ width: readyPct + '%' }"></span></div>
                                <div v-for="c in checklist" :key="c.text" class="tt-check" :class="{ ok: c.ok, bad: !c.ok && c.required }">
                                    <v-icon size="16" :color="c.ok ? '#0f9d6b' : c.required ? '#dc2626' : '#d97706'">{{ c.ok ? "mdi-check-circle" : c.required ? "mdi-close-circle" : "mdi-alert-circle" }}</v-icon>
                                    <span>{{ c.text }}</span>
                                </div>
                                <v-btn block variant="flat" color="#0f9d6b" class="text-none tt-publish" :disabled="!canPublish" :loading="publishing" :prepend-icon="publishMode === 'now' ? 'mdi-send' : 'mdi-calendar-clock'" @click="publish">{{ publishLabel }}</v-btn>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>

            <v-snackbar v-model="snackbar" color="#0a5548" timeout="3500">{{ snackbarText }}</v-snackbar>
        </div>
    </v-defaults-provider>
</template>

<script>
let mediaSeq = 0;
const C = { teal: "#0f9d6b", blue: "#2f5be7", violet: "#7c3aed", amber: "#f59e0b" };

export default {
    name: "CreatePost",

    data() {
        return {
            fieldDefaults: {
                VTextField: { variant: "outlined", density: "compact" },
                VSelect: { variant: "outlined", density: "compact" },
                VAutocomplete: { variant: "outlined", density: "compact" },
                VCombobox: { variant: "outlined", density: "compact" },
                VTextarea: { variant: "outlined", density: "compact" },
            },
            overlayMenu: { contentClass: "tt-overlay" },

            saving: false,
            publishing: false,
            dragging: false,
            dateMenu: false,
            snackbar: false,
            snackbarText: "",

            // limits are the networks' published caption limits: confirm against each API before launch
            channels: {
                facebook: { label: "Facebook Page", short: "Facebook", icon: "mdi-facebook", color: "#1877f2", account: "Tanjil Traders", connected: true, selected: true, limit: 63206 },
                instagram: { label: "Instagram", short: "Instagram", icon: "mdi-instagram", color: "#e1306c", account: "@tanjiltraders", connected: true, selected: true, limit: 2200, needsMedia: true },
                tiktok: { label: "TikTok", short: "TikTok", icon: "mdi-music-note", color: "#111111", account: "@tanjiltraders", connected: true, selected: false, limit: 2200, needsVideo: true },
                youtube: { label: "YouTube", short: "YouTube", icon: "mdi-youtube", color: "#ff0000", account: "Tanjil Traders", connected: false, selected: false, limit: 5000, needsVideo: true },
                telegram: { label: "Telegram", short: "Telegram", icon: "mdi-telegram", color: "#229ed9", account: "@tanjiltraders_bd", connected: true, selected: false, limit: 4096 },
                linkedin: { label: "LinkedIn", short: "LinkedIn", icon: "mdi-linkedin", color: "#0a66c2", account: "Tanjil Traders", connected: false, selected: false, limit: 3000 },
            },
            previewKey: "facebook",

            caption: "",
            hashtags: [],
            link: "",
            cta: "None",
            customize: false,
            overrides: {},
            media: [],

            taggedProducts: [],
            autoProductLines: true,
            utm: true,
            campaign: "eid-offer",

            publishMode: "now",
            scheduleDate: null,
            scheduleTime: "20:00",
            timezone: "Asia/Dhaka (GMT+6)",

            allowComments: true,
            routeToInbox: true,
            replyOwner: "Mitu Akter",
            firstComment: "",

            ctaOptions: ["None", "Shop now", "Learn more", "Send message", "Call now", "Order now"],
            suggestedTags: ["TanjilTraders", "Bangladesh", "Electronics", "PowerBank", "GamingGear", "EidOffer", "SmartWatch", "Headphones"],
            timezones: ["Asia/Dhaka (GMT+6)", "Asia/Kolkata (GMT+5:30)", "UTC"],
            staff: ["Tanjil Ahmed", "Mitu Akter", "Shakil Rana", "Imran Hossain"],
            emojis: ["😀", "😍", "🔥", "✅", "🎉", "❤️", "⭐", "🛒", "📦", "🚚", "💳", "🎧", "🔋", "📱", "⌚", "💻", "🎮", "📢", "👉", "💯", "🙏", "👍", "⚡", "🎁"],
            modes: [
                { value: "now", label: "Publish now", hint: "Goes live right away", icon: "mdi-send-outline" },
                { value: "schedule", label: "Schedule", hint: "Pick a date and time", icon: "mdi-calendar-clock" },
                { value: "queue", label: "Add to queue", hint: "Next open slot", icon: "mdi-playlist-plus" },
            ],
            bestTimes: [
                { label: "Today 8:00 PM", days: 0, time: "20:00" },
                { label: "Tomorrow 12:30 PM", days: 1, time: "12:30" },
                { label: "Tomorrow 9:00 PM", days: 1, time: "21:00" },
            ],
            captionTemplates: [
                { label: "New arrival", text: "🆕 Just landed at Tanjil Traders!\n\nOfficial warranty · Same-day delivery in Dhaka · bKash, Nagad & COD available.\n\nMessage us to reserve yours 👇" },
                { label: "Offer", text: "🔥 Limited-time offer!\n\nGrab it before stock runs out. Offer ends this Sunday.\n\nOrder now or visit our Dhanmondi showroom." },
                { label: "Back in stock", text: "✅ Back in stock!\n\nYou asked, we restocked. Limited units available. Message us to order." },
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
        };
    },

    computed: {
        selectedKeys() {
            return Object.keys(this.channels).filter((k) => this.channels[k].selected && this.channels[k].connected);
        },
        dirty() {
            return !!(this.caption || this.media.length || this.taggedProducts.length);
        },
        hashtagText() {
            return this.hashtags.map((h) => "#" + String(h).replace(/^#/, "").replace(/\s+/g, "")).join(" ");
        },
        productLines() {
            if (!this.autoProductLines || !this.taggedProducts.length) return "";
            return this.taggedProducts.map((p) => `${p.name} — ${this.money(p.price)}\n${this.productUrl(p)}`).join("\n");
        },
        previewCaption() {
            return this.finalCaption(this.previewKey);
        },
        scheduledAt() {
            if (this.publishMode !== "schedule" || !this.scheduleDate) return null;
            const d = new Date(this.scheduleDate);
            const [h, m] = (this.scheduleTime || "00:00").split(":");
            d.setHours(Number(h), Number(m), 0, 0);
            return d;
        },
        schedulePast() {
            return !!this.scheduledAt && this.scheduledAt.getTime() < Date.now();
        },
        dateLabel() {
            return this.scheduleDate ? new Date(this.scheduleDate).toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short", year: "numeric" }) : "";
        },
        whenSummary() {
            if (this.publishMode === "now") return "Goes live as soon as you publish";
            if (this.publishMode === "queue") return "Posts in the next open slot in your queue";
            return this.scheduledAt ? `${this.dateLabel} at ${this.scheduleTime} · ${this.timezone}` : "Choose a date and time";
        },
        publishLabel() {
            if (this.publishMode === "schedule") return this.scheduledAt ? "Schedule post" : "Pick a date";
            return this.publishMode === "queue" ? "Add to queue" : "Publish now";
        },
        mediaRequired() {
            return this.selectedKeys.some((k) => this.channels[k].needsMedia || this.channels[k].needsVideo);
        },
        // four status tiles in the hero double as a quick read on how complete the post is
        heroTiles() {
            const n = this.selectedKeys.length;
            const hasCaption = !!this.caption.trim();
            const mediaOk = this.mediaRequired ? this.media.length > 0 : true;
            const shortDate = this.scheduledAt ? this.scheduledAt.toLocaleDateString("en-GB", { day: "numeric", month: "short" }) : "Pick date";
            const when = { now: "Now", queue: "Queue", schedule: shortDate }[this.publishMode];
            const whenOk = this.publishMode !== "schedule" || (!!this.scheduledAt && !this.schedulePast);
            return [
                { icon: "mdi-share-variant-outline", color: C.teal, value: n, label: n === 1 ? "Network selected" : "Networks selected", tag: n ? "Ready" : "Choose one", ok: n > 0 },
                { icon: "mdi-text-box-outline", color: C.blue, value: this.caption.length, label: "Caption characters", tag: hasCaption ? "Ready" : "Empty", ok: hasCaption || this.media.length > 0 },
                { icon: "mdi-image-multiple-outline", color: C.violet, value: this.media.length + "/10", label: "Photos & video", tag: this.media.length ? "Added" : this.mediaRequired ? "Needed" : "Optional", ok: mediaOk },
                { icon: "mdi-calendar-clock", color: C.amber, value: when, label: "Publish time", tag: whenOk ? "Set" : "Fix time", ok: whenOk },
            ];
        },
        checklist() {
            const list = [];
            list.push({ required: true, ok: this.selectedKeys.length > 0, text: this.selectedKeys.length ? `Posting to ${this.selectedKeys.length} network${this.selectedKeys.length > 1 ? "s" : ""}` : "Choose at least one connected network" });
            list.push({ required: true, ok: !!(this.caption.trim() || this.media.length), text: "Caption or media added" });
            this.selectedKeys.forEach((k) => {
                const c = this.channels[k];
                if (c.needsMedia) list.push({ required: true, ok: this.media.length > 0, text: `${c.label} needs a photo or video` });
                if (c.needsVideo) list.push({ required: true, ok: this.media.some((m) => m.type === "video"), text: `${c.label} needs a video` });
                const len = this.captionLength(k);
                if (len > c.limit) list.push({ required: true, ok: false, text: `${c.label} caption is ${len - c.limit} characters too long` });
            });
            if (this.publishMode === "schedule") list.push({ required: true, ok: !!this.scheduledAt && !this.schedulePast, text: this.schedulePast ? "Scheduled time is in the past" : "Date and time chosen" });
            list.push({ required: false, ok: this.taggedProducts.length > 0, text: this.taggedProducts.length ? `${this.taggedProducts.length} product(s) tagged` : "Tip: tag a product so orders can be traced to this post" });
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
        canPublish() {
            return this.checklist.every((c) => c.ok || !c.required);
        },
    },

    watch: {
        selectedKeys(keys) {
            if (!keys.includes(this.previewKey)) this.previewKey = keys[0] || "facebook";
        },
    },

    methods: {
        money: (v) => "৳ " + Number(v || 0).toLocaleString("en-IN"),
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
        productUrl(p) {
            const base = `https://tanjiltraders.com/p/${p.slug}`;
            return this.utm ? `${base}?utm_source=social&utm_campaign=${encodeURIComponent(this.campaign || "post")}` : base;
        },
        finalCaption(key) {
            const own = this.customize && (this.overrides[key] || "").trim();
            const parts = [own || this.caption.trim()];
            if (this.productLines) parts.push(this.productLines);
            if (this.link) parts.push(this.link);
            if (this.hashtagText && key !== "telegram") parts.push(this.hashtagText);
            return parts.filter(Boolean).join("\n\n");
        },
        captionLength(key) {
            return this.finalCaption(key).length;
        },
        // builds a starting caption from the tagged products; the editor can change it freely afterwards
        draftFromProducts() {
            const names = this.taggedProducts.map((p) => p.name);
            if (!names.length) return;
            const list = names.length > 1 ? names.slice(0, -1).join(", ") + " and " + names[names.length - 1] : names[0];
            this.caption = `🆕 ${list} ${names.length > 1 ? "are" : "is"} now at Tanjil Traders!\n\nOfficial warranty · Same-day delivery in Dhaka · bKash, Nagad & COD available.\n\nMessage us to reserve yours 👇`;
        },

        toggleChannel(key) {
            const c = this.channels[key];
            if (!c.connected) {
                this.notify(`${c.label} isn't connected yet. Connect it from Message Center → Manage channels.`);
                return;
            }
            c.selected = !c.selected;
        },

        addFiles(files) {
            Array.from(files || []).forEach((f) => {
                if (this.media.length >= 10) return;
                const type = f.type.startsWith("video/") ? "video" : f.type.startsWith("image/") ? "image" : null;
                if (!type) return;
                this.media.push({ id: ++mediaSeq, name: f.name, type, url: URL.createObjectURL(f), file: f, alt: "" });
            });
        },
        onFiles(e) {
            this.addFiles(e.target.files);
            e.target.value = "";
        },
        onDrop(e) {
            this.dragging = false;
            this.addFiles(e.dataTransfer.files);
        },
        moveMedia(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.media.length) return;
            const [m] = this.media.splice(i, 1);
            this.media.splice(j, 0, m);
        },

        applyBest(b) {
            const d = new Date();
            d.setDate(d.getDate() + b.days);
            this.scheduleDate = d;
            this.scheduleTime = b.time;
        },

        buildPayload(status) {
            const form = new FormData();
            const data = {
                status,
                channels: this.selectedKeys,
                caption: this.caption,
                captions: Object.fromEntries(this.selectedKeys.map((k) => [k, this.finalCaption(k)])),
                hashtags: this.hashtags,
                link: this.link,
                cta: this.cta,
                products: this.taggedProducts.map((p) => p.slug),
                utm: this.utm ? this.campaign : null,
                publishMode: this.publishMode,
                scheduledAt: this.scheduledAt ? this.scheduledAt.toISOString() : null,
                timezone: this.timezone,
                allowComments: this.allowComments,
                routeToInbox: this.routeToInbox,
                replyOwner: this.routeToInbox ? this.replyOwner : null,
                firstComment: this.firstComment,
                alt: this.media[0] ? this.media[0].alt : "",
            };
            form.append("data", JSON.stringify(data));
            this.media.forEach((m, i) => form.append(`media[${i}]`, m.file));
            return form;
        },

        async saveDraft() {
            this.saving = true;
            try {
                const response = await this.axios.post("/api/admin/posts", this.buildPayload("draft"));
                if (response.data.success !== false) this.ok("Draft saved.");
                else this.fail(response.data.message || "Save failed.");
            } catch (error) {
                console.error(error);
                this.fail("An error occurred while saving the draft.");
            } finally {
                this.saving = false;
            }
        },

        async publish() {
            if (!this.canPublish) return;
            this.publishing = true;
            try {
                const status = this.publishMode === "now" ? "publishing" : this.publishMode === "queue" ? "queued" : "scheduled";
                const response = await this.axios.post("/api/admin/posts", this.buildPayload(status));
                if (response.data.success !== false) {
                    this.ok(this.publishMode === "now" ? "Post is being published." : "Post scheduled.");
                    this.$router.push("/admin/posts");
                } else {
                    this.fail(response.data.message || "Publishing failed.");
                }
            } catch (error) {
                console.error(error);
                this.fail("An error occurred while publishing.");
            } finally {
                this.publishing = false;
            }
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

/* Same tokens as the Dashboard and Inbox. Vuetify colour tokens are pinned so inputs and icons stay dark on white. */
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

/* ───────── Hero ───────── */
.tt-hero {
    position: relative; overflow: hidden; color: #fff; padding: 18px 22px; border-radius: 18px;
    background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%);
    box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95);
}
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-crumbs { display: flex; align-items: center; gap: 3px; font-size: 12px; color: rgba(255, 255, 255, 0.65); }
.tt-crumbs a { color: rgba(255, 255, 255, 0.8); text-decoration: none; }
.tt-crumbs a:hover { color: #fff; text-decoration: underline; }
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.15; letter-spacing: -0.5px; color: #fff; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.22); color: #6ee7b7; }
.tt-pill-warn { color: #fcd34d; border-color: rgba(252, 211, 77, 0.5); }
.tt-btn-text { height: 38px !important; color: rgba(255, 255, 255, 0.85) !important; font-weight: 500; }
.tt-btn-ghost { height: 38px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 38px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-btn-primary.v-btn--disabled { opacity: 0.55; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 14px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 13px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.22; filter: blur(10px); }
.tt-hero-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-txt { flex: 1; min-width: 0; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.3px; white-space: nowrap; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.82); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tt-live { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(110, 231, 183, 0.5); white-space: nowrap; }
.tt-live.todo { color: #fcd34d; background: rgba(245, 158, 11, 0.16); border-color: rgba(252, 211, 77, 0.5); }
@media (max-width: 1199px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Layout ───────── */
.tt-layout { display: grid; grid-template-columns: minmax(0, 1fr) 400px; gap: 16px; align-items: start; margin-top: 16px; }
.tt-main { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.tt-sticky { position: sticky; top: 12px; display: flex; flex-direction: column; gap: 16px; }

/* ───────── Cards (same recipe as the Dashboard) ───────── */
.tt-card { --accent: #2f5be7; --tint: #e6edff; position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35); overflow: hidden; color: var(--ink); }
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, var(--accent), transparent 85%); }
.a-blue { --accent: #2f5be7; --tint: #e6edff; }
.a-teal { --accent: #0f9d6b; --tint: #dff5ec; }
.a-violet { --accent: #7c3aed; --tint: #ebe5ff; }
.a-coral { --accent: #ef6a45; --tint: #fce1d8; }
.a-amber { --accent: #f59e0b; --tint: #fdecc8; }
.a-rose { --accent: #ec4899; --tint: #fbe1ec; }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 6px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-title { font-size: 14px; font-weight: 600; line-height: 1.3; }
.tt-tag { white-space: nowrap; font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; color: var(--accent); background: var(--tint); }
.tt-body { padding: 10px 18px 18px; }

/* ───────── Channel tiles ───────── */
.ch-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
.ch { position: relative; display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1.5px solid var(--line); border-radius: 13px; background: #fff; font: inherit; color: var(--ink); text-align: left; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.ch:hover { border-color: var(--c); }
.ch:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.ch.on { border-color: var(--c); background: color-mix(in srgb, var(--c) 7%, white); }
.ch.off { opacity: 0.55; background: var(--bg2); border-style: dashed; }
.ch-ic { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex: none; box-shadow: 0 8px 14px -8px rgba(15, 42, 38, 0.5); }
.ch-txt { min-width: 0; display: flex; flex-direction: column; line-height: 1.3; }
.ch-txt b { font-size: 13px; font-weight: 600; }
.ch-txt small { font-size: 11px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ch-check { position: absolute; top: 8px; right: 8px; width: 18px; height: 18px; border-radius: 50%; background: var(--c); display: grid; place-items: center; opacity: 0; transform: scale(0.6); transition: 0.15s; }
.ch.on .ch-check { opacity: 1; transform: none; }

/* ───────── Caption ───────── */
.tt-toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; padding-top: 6px; }
.tt-tool { color: #4d6561 !important; }
.tt-tool-ai { color: #5b3fd1 !important; }
.tt-limits { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.tt-limit { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: var(--c); background: color-mix(in srgb, var(--c) 9%, white); padding: 3px 10px; border-radius: 20px; }
.tt-limit i { width: 38px; height: 4px; border-radius: 4px; background: color-mix(in srgb, var(--c) 18%, white); overflow: hidden; }
.tt-limit u { display: block; height: 100%; background: var(--c); }
.tt-limit.over { color: #b4381a; background: #fce1d8; }
.tt-limit.over i { background: #f5c2b3; }
.tt-limit.over u { background: #dc2626; }
.tt-switch-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 0; border-top: 1px dashed #d5dfdc; margin-top: 12px; }
.tt-switch-row.first { border-top: 0; margin-top: 0; padding-top: 0; }
.tt-switch-row b { font-size: 12.8px; font-weight: 600; display: block; }
.tt-switch-row span { font-size: 11.5px; color: var(--muted); }
.tt-custom { display: flex; flex-direction: column; gap: 10px; padding-top: 4px; }
.tt-custom-label { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; margin-bottom: 4px; }

/* ───────── Media ───────── */
.tt-drop { display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center; padding: 22px 16px; border: 1.5px dashed #cdbff5; border-radius: 14px; background: #faf8ff; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tt-drop:hover, .tt-drop.drag { border-color: #7c3aed; background: #f3eeff; }
.tt-drop-ic { width: 46px; height: 46px; border-radius: 14px; background: #ebe5ff; display: grid; place-items: center; margin-bottom: 4px; }
.tt-drop b { font-size: 13.5px; }
.tt-drop small { font-size: 11.5px; color: var(--muted); }
.tt-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin-top: 14px; }
.tt-media { position: relative; aspect-ratio: 1; border-radius: 12px; overflow: hidden; border: 1px solid var(--line); background: #0f2a26; }
.tt-media.cover { outline: 2px solid #0f9d6b; outline-offset: -2px; }
.tt-media img, .tt-media video { width: 100%; height: 100%; object-fit: cover; display: block; }
.tt-media-badge { position: absolute; top: 6px; left: 6px; background: #0f9d6b; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.tt-media-play { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; }
.tt-media-acts { position: absolute; left: 0; right: 0; bottom: 0; display: flex; justify-content: center; gap: 4px; padding: 6px; background: linear-gradient(transparent, rgba(5, 42, 38, 0.85)); opacity: 0; transition: opacity 0.15s; }
.tt-media:hover .tt-media-acts, .tt-media:focus-within .tt-media-acts { opacity: 1; }
@media (hover: none) { .tt-media-acts { opacity: 1; } }
.tt-media-acts button { width: 26px; height: 26px; border: 0; border-radius: 7px; background: rgba(255, 255, 255, 0.2); display: grid; place-items: center; cursor: pointer; }
.tt-media-acts button:hover:not(:disabled) { background: #0f9d6b; }
.tt-media-acts button:disabled { opacity: 0.35; cursor: default; }

/* ───────── Schedule ───────── */
.tt-modes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.tt-modes button { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1.5px solid var(--line); border-radius: 13px; background: #fff; font: inherit; color: var(--ink); text-align: left; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tt-modes button:hover { border-color: #f3d78a; }
.tt-modes button:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-modes button.on { border-color: #f59e0b; background: #fffaf0; }
.tt-mode-ic { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #f3f7f6; color: #4d6561; flex: none; }
.tt-modes button.on .tt-mode-ic { background: #f59e0b; color: #fff; box-shadow: 0 8px 14px -8px #f59e0b; }
.tt-modes b { display: block; font-size: 13px; font-weight: 600; }
.tt-modes small { font-size: 11px; color: var(--muted); }
.tt-best { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
.tt-best button { font: inherit; font-size: 12px; font-weight: 600; color: #8a5a00; background: #fdecc8; border: 1px solid #f3d78a; border-radius: 14px; padding: 2px 12px; cursor: pointer; }
.tt-best button:hover { background: #fbe2a8; }
.tt-warn { display: flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 12.5px; color: #b4381a; }

/* ───────── Preview ───────── */
.tt-prev-tabs { display: flex; gap: 6px; margin-bottom: 12px; overflow-x: auto; scrollbar-width: none; padding-bottom: 2px; }
.tt-prev-tabs::-webkit-scrollbar { display: none; }
.tt-prev-tabs button { flex: none; display: inline-flex; align-items: center; gap: 6px; font: inherit; font-size: 12px; font-weight: 600; color: var(--ink); padding: 5px 12px 5px 9px; border-radius: 20px; border: 1px solid var(--line); background: #fff; cursor: pointer; }
.tt-prev-tabs button:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-prev-tabs button.on { background: var(--c); border-color: var(--c); color: #fff; }
.tt-stage { border-radius: 14px; padding: 14px; background-color: #f0f5f3; background-image: radial-gradient(#dbe6e2 1px, transparent 1px); background-size: 16px 16px; }
.tt-prev-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 30px 10px; font-size: 12.5px; color: var(--muted); text-align: center; }
.pv { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; background: #fff; font-size: 13px; box-shadow: 0 8px 20px -14px rgba(15, 42, 38, 0.5); }
.pv-head { display: flex; align-items: center; gap: 9px; padding: 10px 12px; }
.pv-head b { font-size: 13px; display: block; line-height: 1.2; }
.pv-head small { font-size: 11px; color: var(--muted); }
.pv-av { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #0a5548, #0f9d6b); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 13px; flex: none; }
.pv-cap { padding: 0 12px 10px; white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.5; max-height: 190px; overflow: hidden; }
.pv-cap.pad { padding-top: 2px; }
.pv-media { position: relative; background: #0f2a26; }
.pv-media img, .pv-media video { width: 100%; display: block; max-height: 340px; object-fit: cover; }
.pv-media.sq img, .pv-media.sq video { aspect-ratio: 1; max-height: none; }
.pv-ph { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; min-height: 150px; color: #9db3ae; font-size: 12px; text-align: center; padding: 14px; }
.pv-count { position: absolute; top: 8px; right: 8px; background: rgba(0, 0, 0, 0.6); color: #fff; font-size: 11px; padding: 1px 8px; border-radius: 10px; }
.pv-fb-acts { display: flex; justify-content: space-around; border-top: 1px solid var(--line); padding: 8px 4px; color: #5c726e; font-size: 12px; font-weight: 600; }
.pv-fb-acts span { display: inline-flex; align-items: center; gap: 4px; }
.pv-link { display: flex; flex-direction: column; gap: 2px; padding: 8px 12px; background: #f3f6f9; border-top: 1px solid var(--line); }
.pv-link small { color: var(--muted); font-size: 10.5px; }
.pv-link b { font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pv-cta { align-self: flex-start; margin-top: 4px; font-size: 11.5px; font-weight: 600; background: #e4e6eb; border-radius: 6px; padding: 3px 12px; }
.pv-ig-acts { display: flex; gap: 12px; padding: 9px 12px 4px; }
.pv-tg { background: #cfe3ee; padding: 14px; border: 0; box-shadow: none; }
.pv-tg-bubble { background: #fff; border-radius: 12px 12px 12px 4px; overflow: hidden; max-width: 100%; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1); }
.pv-tg-text { padding: 8px 12px 2px; white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.45; }
.pv-tg-link { padding: 0 12px; color: #229ed9; font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pv-tg-time { text-align: right; font-size: 10.5px; color: #8ea0ac; padding: 0 10px 6px; }
.pv-products { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
.pv-products span { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: #0a5548; background: #dff5ec; border-radius: 20px; padding: 3px 10px; }

/* ───────── Checklist ───────── */
.tt-meter { height: 6px; border-radius: 6px; background: #e8eeec; overflow: hidden; margin: 4px 0 10px; }
.tt-meter span { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0f9d6b, #6ee7b7); transition: width 0.3s; }
.tt-check { display: flex; align-items: flex-start; gap: 8px; padding: 5px 0; font-size: 12.5px; line-height: 1.45; }
.tt-check.bad { color: #b4381a; }
.tt-publish { margin-top: 12px; height: 42px !important; color: #fff !important; font-weight: 600; border-radius: 12px !important; box-shadow: 0 12px 20px -14px #0f9d6b; }

@media (max-width: 1180px) {
    .tt-layout { grid-template-columns: minmax(0, 1fr); }
    .tt-sticky { position: static; }
}
@media (max-width: 640px) {
    .tt-modes { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) { .ch, .ch-check, .tt-meter span, .tt-drop { transition: none; } }
</style>

<!-- Menus and pickers open outside the page, so they need their own pinned colours -->
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
</style>