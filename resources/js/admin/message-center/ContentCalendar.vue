<template>
    <v-defaults-provider :defaults="fieldDefaults">
        <div class="tt-page">
            <!-- ═════════ Hero ═════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-center ga-3">
                    <div>
                        <div class="d-flex flex-wrap align-center ga-3">
                            <h1 class="tt-h1">Content calendar</h1>
                            <span class="tt-pill"><v-icon size="13">mdi-calendar-month-outline</v-icon>{{ monthPosts.length }} posts in {{ monthName }}</span>
                        </div>
                        <div class="tt-hero-sub">See every post on every network. Drag a post to another day to reschedule it.</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <div class="tt-seg" role="tablist" aria-label="Calendar view">
                            <button v-for="v in views" :key="v.value" type="button" role="tab" :aria-selected="view === v.value" :class="{ on: view === v.value }" @click="view = v.value">{{ v.label }}</button>
                        </div>
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-format-list-bulleted" to="/admin/posts">All posts</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="createOn('')">Create post</v-btn>
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

            <div class="tt-layout">
                <!-- ═════════════ CALENDAR ═════════════ -->
                <section class="tt-card a-blue">
                    <div class="tt-head tt-head-wrap">
                        <div class="cal-nav">
                            <v-btn icon variant="outlined" size="small" class="tt-icon-btn" aria-label="Previous" @click="step(-1)"><v-icon size="18">mdi-chevron-left</v-icon></v-btn>
                            <v-btn icon variant="outlined" size="small" class="tt-icon-btn" aria-label="Next" @click="step(1)"><v-icon size="18">mdi-chevron-right</v-icon></v-btn>
                        </div>
                        <div class="tt-title cal-title">{{ rangeTitle }}</div>
                        <v-btn variant="outlined" size="small" class="text-none tt-icon-btn" @click="anchor = new Date()">Today</v-btn>
                        <v-spacer />
                        <div class="tt-seg tt-seg-light" role="tablist" aria-label="Post status">
                            <button v-for="s in statusTabs" :key="s.value" type="button" role="tab" :aria-selected="statusFilter === s.value" :class="{ on: statusFilter === s.value }" @click="statusFilter = s.value">{{ s.label }}</button>
                        </div>
                    </div>

                    <!-- Month / week grid -->
                    <div v-if="view !== 'list'" class="tt-body cal-body">
                        <div class="cal-scroll">
                            <div class="cal-dow"><span v-for="(d, i) in dayNames" :key="d" :class="{ fri: i === 6 }">{{ d }}</span></div>
                            <div class="cal-grid" :class="{ week: view === 'week' }">
                                <div
                                    v-for="cell in cells"
                                    :key="cell.key"
                                    class="cal-cell"
                                    :class="{ out: view === 'month' && !cell.inMonth, today: cell.isToday, past: cell.isPast, fri: cell.isFri, over: dragOver === cell.key }"
                                    @dragover.prevent="dragOver = cell.key"
                                    @dragleave="dragOver === cell.key && (dragOver = '')"
                                    @drop.prevent="onDrop(cell)"
                                >
                                    <div class="cal-cell-top">
                                        <span class="cal-day">{{ cell.day }}</span>
                                        <span v-if="cell.occasion" class="cal-occ" :title="cell.occasion.idea"><v-icon size="11">mdi-star-four-points</v-icon>{{ cell.occasion.name }}</span>
                                        <v-spacer />
                                        <button v-if="!cell.isPast" type="button" class="cal-add" :aria-label="'Create a post on ' + dayLabel(cell.key)" @click="createOn(cell.key)"><v-icon size="14">mdi-plus</v-icon></button>
                                    </div>

                                    <div
                                        v-for="p in shown(cell)"
                                        :key="p.id"
                                        class="cal-chip"
                                        :class="'st-' + p.status"
                                        role="button"
                                        tabindex="0"
                                        :draggable="p.status !== 'published'"
                                        :title="p.title"
                                        @click="openPost(p)"
                                        @keydown.enter="openPost(p)"
                                        @dragstart="onDragStart($event, p)"
                                        @dragend="dragId = null; dragOver = ''"
                                    >
                                        <span class="cal-chip-dots"><i v-for="k in p.channels" :key="k" :style="{ background: channels[k].color }"></i></span>
                                        <span class="cal-chip-time">{{ time12(p.time) }}</span>
                                        <span class="cal-chip-title">{{ p.title }}</span>
                                        <span v-if="view === 'week'" class="cal-chip-cap">{{ p.caption }}</span>
                                    </div>
                                    <button v-if="more(cell) > 0" type="button" class="cal-more" @click="focusWeek(cell)">+{{ more(cell) }} more</button>
                                </div>
                            </div>
                        </div>

                        <div class="cal-legend">
                            <span><i class="lg lg-scheduled"></i>Scheduled</span>
                            <span><i class="lg lg-published"></i>Published</span>
                            <span><i class="lg lg-draft"></i>Draft</span>
                            <span class="tt-subt ml-auto">Fridays are shaded. Published posts can't be moved.</span>
                        </div>
                    </div>

                    <!-- List view -->
                    <div v-else class="tt-body pa-0">
                        <div v-for="g in agenda" :key="g.key" class="ag-day">
                            <div class="ag-date" :class="{ today: g.key === todayKey }"><b>{{ g.day }}</b><span>{{ g.dow }}</span></div>
                            <div class="ag-posts">
                                <div v-for="p in g.posts" :key="p.id" class="ag-row" role="button" tabindex="0" @click="openPost(p)" @keydown.enter="openPost(p)">
                                    <span class="ag-time">{{ time12(p.time) }}</span>
                                    <div class="ag-main">
                                        <div class="tt-strong">{{ p.title }}</div>
                                        <div class="tt-subt ag-cap">{{ p.caption }}</div>
                                    </div>
                                    <span class="cal-chip-dots"><i v-for="k in p.channels" :key="k" :style="{ background: channels[k].color }"></i></span>
                                    <span class="tt-chip" :class="'tone-' + tones[p.status]">{{ statusText[p.status] }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="!agenda.length" class="ag-empty">
                            <div class="ag-empty-ic"><v-icon size="26" color="#0f9d6b">mdi-calendar-blank-outline</v-icon></div>
                            <div class="tt-strong mt-3">No posts in {{ monthName }}</div>
                            <div class="tt-subt">Clear a filter, or create a post to fill the month.</div>
                            <v-btn variant="flat" color="#0f9d6b" class="text-none mt-3 tt-send-btn" prepend-icon="mdi-plus" @click="createOn('')">Create post</v-btn>
                        </div>
                    </div>
                </section>

                <!-- ═════════════ SIDE ═════════════ -->
                <aside class="tt-side">
                    <section class="tt-card a-teal">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-share-variant-outline</v-icon></div>
                            <div><div class="tt-title">Networks</div><div class="tt-subt">Show or hide posts by network</div></div>
                        </div>
                        <div class="tt-body">
                            <button v-for="(c, key) in channels" :key="key" type="button" class="net" :class="{ off: !activeChannels[key] }" :aria-pressed="activeChannels[key]" @click="activeChannels[key] = !activeChannels[key]">
                                <span class="net-ic" :style="{ background: c.color }"><v-icon size="15" color="white">{{ c.icon }}</v-icon></span>
                                <b>{{ c.label }}</b>
                                <span class="net-count">{{ channelCounts[key] }}</span>
                                <v-icon size="18" :color="activeChannels[key] ? '#0f9d6b' : '#b8c6c2'">{{ activeChannels[key] ? "mdi-eye-outline" : "mdi-eye-off-outline" }}</v-icon>
                            </button>
                        </div>
                    </section>

                    <section class="tt-card a-amber">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-clock-outline</v-icon></div>
                            <div><div class="tt-title">Up next</div><div class="tt-subt">Your next scheduled posts</div></div>
                        </div>
                        <div class="tt-body">
                            <div v-for="p in upNext" :key="p.id" class="up" role="button" tabindex="0" @click="openPost(p)" @keydown.enter="openPost(p)">
                                <div class="up-when"><b>{{ dayLabel(p.date) }}</b><span>{{ time12(p.time) }}</span></div>
                                <div class="up-main"><div class="tt-strong up-title">{{ p.title }}</div><span class="cal-chip-dots"><i v-for="k in p.channels" :key="k" :style="{ background: channels[k].color }"></i></span></div>
                            </div>
                            <div v-if="!upNext.length" class="tt-subt">Nothing is scheduled. Create a post to get started.</div>
                        </div>
                    </section>

                    <section class="tt-card a-coral">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-star-four-points-outline</v-icon></div>
                            <div><div class="tt-title">Dates to plan around</div><div class="tt-subt">Shopping moments for gadget stores in Bangladesh</div></div>
                        </div>
                        <div class="tt-body">
                            <div v-for="o in upcomingOccasions" :key="o.date" class="occ">
                                <div class="occ-top">
                                    <b>{{ o.name }}</b>
                                    <span class="tt-tag">{{ o.away === 0 ? "Today" : o.away === 1 ? "Tomorrow" : "In " + o.away + " days" }}</span>
                                </div>
                                <div class="tt-subt">{{ o.idea }}</div>
                                <button type="button" class="occ-btn" @click="createOn(o.date)">Plan a post</button>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <!-- ═════════ Post details ═════════ -->
            <v-dialog v-model="dialog" max-width="560" content-class="tt-overlay">
                <div v-if="sel" class="tt-dialog">
                    <div class="tt-dialog-head">
                        <div class="tt-head-ic tt-head-ic--lg"><v-icon size="20">mdi-post-outline</v-icon></div>
                        <div class="ml-3 tt-dialog-title">
                            <div class="tt-info-title">{{ sel.title }}</div>
                            <div class="tt-subt">{{ dayLabel(sel.date) }} at {{ time12(sel.time) }}</div>
                        </div>
                        <v-spacer />
                        <span class="tt-chip" :class="'tone-' + tones[sel.status]">{{ statusText[sel.status] }}</span>
                        <v-btn icon size="small" variant="text" aria-label="Close" class="ml-1" @click="dialog = false"><v-icon size="18">mdi-close</v-icon></v-btn>
                    </div>
                    <div class="tt-dialog-body">
                        <div class="dl-chans">
                            <span v-for="k in sel.channels" :key="k" class="dl-chan" :style="{ '--c': channels[k].color }"><v-icon size="14">{{ channels[k].icon }}</v-icon>{{ channels[k].label }}</span>
                        </div>
                        <div class="dl-cap">{{ sel.caption }}</div>

                        <template v-if="sel.status !== 'published'">
                            <div class="tt-field-label">Change the time</div>
                            <v-row dense>
                                <v-col cols="12" sm="5"><v-text-field v-model="editDate" type="date" :min="todayKey" label="Date" hide-details /></v-col>
                                <v-col cols="6" sm="3"><v-text-field v-model="editTime" type="time" label="Time" hide-details /></v-col>
                                <v-col cols="6" sm="4"><v-btn block variant="flat" color="#0f9d6b" class="text-none tt-send-btn dl-save" :disabled="!editDate || !editTime" @click="saveTime">Update time</v-btn></v-col>
                            </v-row>
                        </template>
                        <div v-else class="tt-subt mt-3">This post is already live, so its time can't be changed.</div>
                    </div>
                    <div class="tt-dialog-foot">
                        <v-btn variant="text" class="text-none" :color="confirmDelete ? '#dc2626' : '#5c726e'" :prepend-icon="confirmDelete ? 'mdi-alert-outline' : 'mdi-delete-outline'" @click="removePost">{{ confirmDelete ? "Click again to delete" : "Delete" }}</v-btn>
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-icon-btn" prepend-icon="mdi-content-copy" @click="duplicatePost">Duplicate</v-btn>
                        <v-btn variant="flat" color="#0f9d6b" class="text-none tt-send-btn" prepend-icon="mdi-pencil-outline" @click="editPost">Edit post</v-btn>
                    </div>
                </div>
            </v-dialog>

            <v-snackbar v-model="snackbar" color="#0a5548" timeout="3500">{{ snackbarText }}</v-snackbar>
        </div>
    </v-defaults-provider>
</template>

<script>
const C = { teal: "#0f9d6b", blue: "#2f5be7", amber: "#f59e0b", coral: "#ef6a45" };
const pad = (n) => String(n).padStart(2, "0");
const keyOf = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const fromKey = (k) => {
    const [y, m, d] = k.split("-").map(Number);
    return new Date(y, m - 1, d);
};
const addDays = (d, n) => {
    const x = new Date(d);
    x.setDate(x.getDate() + n);
    return x;
};
// the week starts on Saturday in Bangladesh
const weekStartOf = (d) => addDays(d, -((d.getDay() + 1) % 7));

export default {
    name: "ContentCalendar",

    data() {
        const base = new Date();
        const at = (n) => keyOf(addDays(base, n));
        return {
            fieldDefaults: {
                VTextField: { variant: "outlined", density: "compact" },
            },

            anchor: new Date(),
            view: "month",
            views: [{ value: "month", label: "Month" }, { value: "week", label: "Week" }, { value: "list", label: "List" }],
            statusFilter: "all",
            statusTabs: [{ value: "all", label: "All" }, { value: "scheduled", label: "Scheduled" }, { value: "published", label: "Published" }, { value: "draft", label: "Drafts" }],
            tones: { scheduled: "blue", published: "green", draft: "gray" },
            statusText: { scheduled: "Scheduled", published: "Published", draft: "Draft" },
            dayNames: ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"],

            channels: {
                facebook: { label: "Facebook", icon: "mdi-facebook", color: "#1877f2" },
                instagram: { label: "Instagram", icon: "mdi-instagram", color: "#e1306c" },
                tiktok: { label: "TikTok", icon: "mdi-music-note", color: "#111111" },
                youtube: { label: "YouTube", icon: "mdi-youtube", color: "#ff0000" },
                telegram: { label: "Telegram", icon: "mdi-telegram", color: "#229ed9" },
            },
            activeChannels: { facebook: true, instagram: true, tiktok: true, youtube: true, telegram: true },

            dragId: null,
            dragOver: "",
            dialog: false,
            sel: null,
            editDate: "",
            editTime: "",
            confirmDelete: false,
            nextId: 100,
            snackbar: false,
            snackbarText: "",

            // replace with GET /api/admin/posts?from=&to=  (dates as YYYY-MM-DD, time as HH:mm, Dhaka time)
            posts: [
                { id: 1, date: at(-12), time: "19:00", channels: ["facebook", "instagram"], status: "published", title: "Anker PowerCore 20000 is back in stock", caption: "Back in stock! Limited units with official warranty. Message us to order." },
                { id: 2, date: at(-10), time: "12:30", channels: ["facebook", "telegram"], status: "published", title: "Weekend deal: Baseus Bowie E9 earbuds", caption: "This weekend only: ৳ 2,350 for the Baseus Bowie E9. Cash on delivery available." },
                { id: 3, date: at(-8), time: "21:00", channels: ["instagram", "tiktok"], status: "published", title: "Unboxing: Xiaomi Smart Band 8", caption: "Is the Smart Band 8 worth ৳ 3,150? We unbox it and test the battery." },
                { id: 4, date: at(-6), time: "20:00", channels: ["youtube"], status: "published", title: "SSD vs HDD: which one should you buy?", caption: "A simple guide to choosing storage for your laptop or PC." },
                { id: 5, date: at(-4), time: "13:00", channels: ["facebook", "instagram"], status: "published", title: "Customer review: TP-Link Archer AX55", caption: "Thank you to our customer in Uttara for the kind words about the AX55 router." },
                { id: 6, date: at(-2), time: "20:00", channels: ["instagram", "facebook", "telegram"], status: "published", title: "Same-day delivery in Dhaka", caption: "Order before 3 PM and get your gadget today. bKash, Nagad and COD accepted." },
                { id: 7, date: at(-1), time: "12:30", channels: ["telegram"], status: "published", title: "Price drop on 65W GaN chargers", caption: "Baseus 65W GaN charger is now ৳ 3,290. Charges a laptop and a phone together." },
                { id: 8, date: at(0), time: "20:00", channels: ["facebook", "instagram"], status: "scheduled", title: "Gaming mouse buying guide", caption: "DPI, weight, sensor: what actually matters when you pick a gaming mouse." },
                { id: 9, date: at(1), time: "12:30", channels: ["tiktok"], status: "scheduled", title: "Fast charging myths in 30 seconds", caption: "Does fast charging damage your battery? Short answer inside." },
                { id: 10, date: at(2), time: "21:00", channels: ["facebook"], status: "draft", title: "Winter gadget gift guide", caption: "Gift ideas for every budget: smart bands, speakers, power banks and more." },
                { id: 11, date: at(3), time: "20:00", channels: ["instagram", "facebook"], status: "scheduled", title: "Power bank sizes explained", caption: "10000mAh or 20000mAh? Here is how many charges each one gives." },
                { id: 12, date: at(3), time: "12:30", channels: ["telegram"], status: "scheduled", title: "Warranty tip: check the serial number", caption: "Always check the serial number on the box matches the device before you pay." },
                { id: 13, date: at(5), time: "19:00", channels: ["youtube"], status: "draft", title: "Top 5 earbuds under ৳ 3,000", caption: "We compare sound, battery and call quality across five popular earbuds." },
                { id: 14, date: at(8), time: "20:00", channels: ["facebook", "instagram", "telegram"], status: "scheduled", title: "Black Friday teaser", caption: "Something big is coming this Friday. Turn on notifications so you don't miss it." },
                { id: 15, date: at(10), time: "10:00", channels: ["facebook", "instagram", "telegram", "tiktok"], status: "scheduled", title: "Black Friday: deals are live", caption: "Our biggest sale of the year starts now. Chargers, earbuds, watches and more." },
                { id: 16, date: at(10), time: "14:00", channels: ["tiktok"], status: "scheduled", title: "Black Friday: top 3 picks", caption: "The three deals we would buy first." },
                { id: 17, date: at(10), time: "17:00", channels: ["telegram"], status: "scheduled", title: "Black Friday: flash price list", caption: "Full price list for today's sale items." },
                { id: 18, date: at(10), time: "21:00", channels: ["instagram"], status: "scheduled", title: "Black Friday: last hours", caption: "The sale ends at midnight. Message us to order." },
                { id: 19, date: at(14), time: "20:00", channels: ["instagram"], status: "draft", title: "Year-end gadget picks", caption: "The gadgets our customers loved most this year." },
            ],

            // shopping moments: replace or extend as needed. Eid dates depend on moon sighting, so confirm them closer to the day.
            occasions: [
                { date: "2026-11-27", name: "Black Friday", idea: "Post a countdown, then a price list. Tag your best-selling chargers and earbuds." },
                { date: "2026-12-16", name: "Victory Day", idea: "Share opening hours and a short thank-you to customers." },
                { date: "2026-12-31", name: "Year-end sale", idea: "Clear old stock with a one-day offer and a 'best of the year' post." },
                { date: "2027-01-01", name: "New Year", idea: "Suggest tech upgrades for the new year: laptops, routers and storage." },
                { date: "2027-02-14", name: "Valentine's Day", idea: "Gift picks under ৳ 5,000: smart bands, earbuds and speakers." },
                { date: "2027-03-10", name: "Eid-ul-Fitr (approx.)", idea: "Start offer posts two weeks early. Confirm the date after moon sighting." },
                { date: "2027-04-14", name: "Pohela Boishakh", idea: "A festive offer with free delivery in Dhaka." },
            ],
        };
    },

    computed: {
        todayKey() {
            return keyOf(new Date());
        },
        monthName() {
            return this.anchor.toLocaleDateString("en-GB", { month: "long", year: "numeric" });
        },
        weekStart() {
            return weekStartOf(this.anchor);
        },
        rangeTitle() {
            if (this.view !== "week") return this.monthName;
            const a = this.weekStart;
            const b = addDays(a, 6);
            const f = (d, y) => d.toLocaleDateString("en-GB", { day: "numeric", month: "short", ...(y ? { year: "numeric" } : {}) });
            return `${f(a)} – ${f(b, true)}`;
        },
        cells() {
            const y = this.anchor.getFullYear();
            const m = this.anchor.getMonth();
            let start;
            let count;
            if (this.view === "week") {
                start = this.weekStart;
                count = 7;
            } else {
                const first = new Date(y, m, 1);
                const off = (first.getDay() + 1) % 7;
                start = addDays(first, -off);
                count = Math.ceil((off + new Date(y, m + 1, 0).getDate()) / 7) * 7;
            }
            return Array.from({ length: count }, (_, i) => {
                const date = addDays(start, i);
                const key = keyOf(date);
                return { key, date, day: date.getDate(), inMonth: date.getMonth() === m, isToday: key === this.todayKey, isPast: key < this.todayKey, isFri: date.getDay() === 5, occasion: this.occasions.find((o) => o.date === key) || null };
            });
        },
        visiblePosts() {
            return this.posts.filter((p) => (this.statusFilter === "all" || p.status === this.statusFilter) && p.channels.some((c) => this.activeChannels[c]));
        },
        postsByDay() {
            const map = {};
            this.visiblePosts.forEach((p) => (map[p.date] = (map[p.date] || []).concat(p)));
            Object.keys(map).forEach((k) => map[k].sort((a, b) => a.time.localeCompare(b.time)));
            return map;
        },
        monthPrefix() {
            return `${this.anchor.getFullYear()}-${pad(this.anchor.getMonth() + 1)}`;
        },
        monthPosts() {
            return this.posts.filter((p) => p.date.startsWith(this.monthPrefix));
        },
        emptyDays() {
            const y = this.anchor.getFullYear();
            const m = this.anchor.getMonth();
            const days = new Date(y, m + 1, 0).getDate();
            let n = 0;
            for (let d = 1; d <= days; d++) {
                const key = `${this.monthPrefix}-${pad(d)}`;
                if (key >= this.todayKey && !this.posts.some((p) => p.date === key)) n++;
            }
            return n;
        },
        heroTiles() {
            const by = (s) => this.monthPosts.filter((p) => p.status === s).length;
            const drafts = by("draft");
            return [
                { icon: "mdi-calendar-clock", color: C.blue, value: by("scheduled"), label: "Scheduled", tag: "Ready to go" },
                { icon: "mdi-check-circle-outline", color: C.teal, value: by("published"), label: "Published", tag: "Live" },
                { icon: "mdi-pencil-outline", color: C.amber, value: drafts, label: "Drafts", tag: drafts ? "Finish them" : "None", todo: drafts > 0 },
                { icon: "mdi-calendar-blank-outline", color: C.coral, value: this.emptyDays, label: "Days left with no post", tag: this.emptyDays > 6 ? "Plan more" : "On track", todo: this.emptyDays > 6 },
            ];
        },
        channelCounts() {
            const out = {};
            Object.keys(this.channels).forEach((k) => (out[k] = this.monthPosts.filter((p) => p.channels.includes(k)).length));
            return out;
        },
        agenda() {
            const days = {};
            this.visiblePosts.filter((p) => p.date.startsWith(this.monthPrefix)).forEach((p) => (days[p.date] = (days[p.date] || []).concat(p)));
            return Object.keys(days)
                .sort()
                .map((key) => {
                    const d = fromKey(key);
                    return { key, day: d.getDate(), dow: d.toLocaleDateString("en-GB", { weekday: "short" }), posts: days[key].sort((a, b) => a.time.localeCompare(b.time)) };
                });
        },
        upNext() {
            const now = new Date();
            const stamp = `${keyOf(now)} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
            return this.posts
                .filter((p) => p.status === "scheduled" && `${p.date} ${p.time}` >= stamp)
                .sort((a, b) => `${a.date} ${a.time}`.localeCompare(`${b.date} ${b.time}`))
                .slice(0, 5);
        },
        upcomingOccasions() {
            const today = fromKey(this.todayKey);
            return this.occasions
                .filter((o) => o.date >= this.todayKey)
                .slice(0, 4)
                .map((o) => ({ ...o, away: Math.round((fromKey(o.date) - today) / 86400000) }));
        },
    },

    watch: {
        dialog(v) {
            if (!v) this.confirmDelete = false;
        },
    },

    mounted() {
        // the month grid is too tight on phones, so start in the list view there
        if (typeof window !== "undefined" && window.innerWidth < 760) this.view = "list";
    },

    methods: {
        notify(text) {
            this.snackbarText = text;
            this.snackbar = true;
        },
        time12(t) {
            const [h, m] = (t || "00:00").split(":").map(Number);
            return `${h % 12 || 12}:${pad(m)} ${h >= 12 ? "PM" : "AM"}`;
        },
        dayLabel(key) {
            if (key === this.todayKey) return "Today";
            if (key === keyOf(addDays(new Date(), 1))) return "Tomorrow";
            return fromKey(key).toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" });
        },

        step(dir) {
            const a = this.anchor;
            this.anchor = this.view === "week" ? addDays(a, dir * 7) : new Date(a.getFullYear(), a.getMonth() + dir, 1);
        },
        shown(cell) {
            const list = this.postsByDay[cell.key] || [];
            return this.view === "week" ? list : list.slice(0, 3);
        },
        more(cell) {
            return this.view === "week" ? 0 : Math.max(0, (this.postsByDay[cell.key] || []).length - 3);
        },
        focusWeek(cell) {
            this.anchor = cell.date;
            this.view = "week";
        },

        createOn(key) {
            if (key && key < this.todayKey) {
                this.notify("Pick today or a later day.");
                return;
            }
            // CreatePost can read this.$route.query.date to pre-fill the schedule
            this.$router.push({ path: "/admin/posts/create", query: key ? { date: key } : {} });
        },

        // drag and drop reschedule
        onDragStart(e, p) {
            this.dragId = p.id;
            e.dataTransfer.effectAllowed = "move";
            e.dataTransfer.setData("text/plain", String(p.id));
        },
        onDrop(cell) {
            const p = this.posts.find((x) => x.id === this.dragId);
            this.dragId = null;
            this.dragOver = "";
            if (!p || p.date === cell.key) return;
            if (cell.key < this.todayKey) {
                this.notify("Posts can't be moved to a day that has passed.");
                return;
            }
            p.date = cell.key; // PATCH /api/admin/posts/:id { date }
            this.notify(`"${p.title}" moved to ${this.dayLabel(cell.key)}.`);
        },

        openPost(p) {
            this.sel = p;
            this.editDate = p.date;
            this.editTime = p.time;
            this.confirmDelete = false;
            this.dialog = true;
        },
        saveTime() {
            const p = this.sel;
            if (!p) return;
            const now = new Date();
            const stamp = `${keyOf(now)} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
            if (p.status === "scheduled" && `${this.editDate} ${this.editTime}` < stamp) {
                this.notify("That time has passed. Choose a future time.");
                return;
            }
            p.date = this.editDate; // PATCH /api/admin/posts/:id { date, time }
            p.time = this.editTime;
            this.notify("Time updated.");
        },
        duplicatePost() {
            const p = this.sel;
            if (!p) return;
            this.posts.push({ ...p, id: ++this.nextId, status: "draft", date: p.date < this.todayKey ? this.todayKey : p.date, title: p.title + " (copy)", channels: [...p.channels] }); // POST /api/admin/posts/:id/duplicate
            this.dialog = false;
            this.notify("Copy saved as a draft.");
        },
        removePost() {
            if (!this.confirmDelete) {
                this.confirmDelete = true;
                return;
            }
            this.posts = this.posts.filter((p) => p.id !== this.sel.id); // DELETE /api/admin/posts/:id
            this.dialog = false;
            this.notify("Post deleted.");
        },
        editPost() {
            this.$router.push(`/admin/posts/${this.sel.id}/edit`);
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
.tt-seg { display: inline-flex; padding: 3px; border-radius: 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-seg button { border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: rgba(255, 255, 255, 0.75); padding: 6px 15px; border-radius: 9px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-seg button:hover { color: #fff; }
.tt-seg button.on { background: rgba(255, 255, 255, 0.22); color: #fff; font-weight: 600; }
.tt-seg button:focus-visible { outline: 2px solid #6ee7b7; outline-offset: 2px; }
.tt-seg-light { background: #eef3f1; border-color: var(--line); }
.tt-seg-light button { color: var(--muted); padding: 5px 13px; font-size: 12.5px; }
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
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.3px; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.82); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tt-live { position: relative; z-index: 1; font-size: 10.5px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(110, 231, 183, 0.5); white-space: nowrap; }
.tt-live.todo { color: #fcd34d; background: rgba(245, 158, 11, 0.16); border-color: rgba(252, 211, 77, 0.5); }
@media (max-width: 1199px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Layout & cards ───────── */
.tt-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; margin-top: 16px; }
.tt-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.tt-card { --accent: #2f5be7; --tint: #e6edff; position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35); overflow: hidden; color: var(--ink); min-width: 0; }
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, var(--accent), transparent 85%); }
.a-blue { --accent: #2f5be7; --tint: #e6edff; }
.a-teal { --accent: #0f9d6b; --tint: #dff5ec; }
.a-coral { --accent: #ef6a45; --tint: #fce1d8; }
.a-amber { --accent: #f59e0b; --tint: #fdecc8; }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 6px; }
.tt-head-wrap { flex-wrap: wrap; padding-bottom: 12px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-head-ic--lg { width: 42px; height: 42px; border-radius: 12px; }
.tt-title { font-size: 14px; font-weight: 600; line-height: 1.3; }
.tt-tag { white-space: nowrap; font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; color: var(--accent); background: var(--tint); }
.tt-body { padding: 10px 18px 18px; }
.tt-icon-btn { border-color: var(--line) !important; color: #3d5450 !important; border-radius: 10px !important; font-weight: 600; }
.tt-send-btn { color: #fff !important; font-weight: 600; border-radius: 10px !important; }
.tt-chip { display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 20px; white-space: nowrap; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-blue { background: #e6edff; color: #1f4e9c; }
.tone-gray { background: #e8ecea; color: #5b6275; }

/* ───────── Calendar ───────── */
.cal-nav { display: flex; gap: 6px; }
.cal-title { font-size: 16px; min-width: 150px; }
.cal-body { padding-top: 2px; }
.cal-scroll { overflow-x: auto; }
.cal-dow, .cal-grid { min-width: 720px; }
.cal-dow { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); padding-bottom: 6px; }
.cal-dow span { font-size: 12px; font-weight: 600; color: var(--muted); padding: 0 8px; }
.cal-dow span.fri { color: #b4381a; }
.cal-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
.cal-cell { min-height: 124px; padding: 6px; background: #fff; display: flex; flex-direction: column; gap: 3px; transition: background 0.12s; min-width: 0; }
.cal-grid.week .cal-cell { min-height: 360px; }
.cal-cell.fri { background: #fbf9f8; }
.cal-cell.out { background: var(--bg2); }
.cal-cell.out .cal-day { color: #aebdb9; }
.cal-cell.past:not(.today) { background: #f9fbfa; }
.cal-cell.over { background: #eaf8f1; box-shadow: inset 0 0 0 2px #0f9d6b; }
.cal-cell-top { display: flex; align-items: center; gap: 5px; min-height: 22px; }
.cal-day { font-size: 12.5px; font-weight: 600; width: 22px; height: 22px; display: grid; place-items: center; border-radius: 50%; flex: none; }
.cal-cell.today .cal-day { background: #0a5548; color: #fff; }
.cal-occ { display: inline-flex; align-items: center; gap: 3px; min-width: 0; font-size: 10px; font-weight: 600; color: #8a5a00; background: #fdecc8; border-radius: 10px; padding: 1px 7px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.cal-add { width: 22px; height: 22px; border: 0; border-radius: 7px; background: transparent; color: #0f9d6b; display: grid; place-items: center; cursor: pointer; opacity: 0; transition: opacity 0.12s, background 0.12s; }
.cal-cell:hover .cal-add, .cal-add:focus-visible { opacity: 1; }
.cal-add:hover { background: #dff5ec; }
.cal-add:focus-visible { outline: 2px solid #0f9d6b; }
@media (hover: none) { .cal-add { opacity: 1; } }

.cal-chip { display: flex; flex-wrap: wrap; align-items: center; gap: 2px 6px; padding: 4px 7px; border-radius: 8px; font-size: 11.5px; line-height: 1.35; cursor: pointer; border: 1px solid var(--line); border-left: 3px solid #2f5be7; background: #f3f6ff; outline: none; min-width: 0; }
.cal-chip[draggable="true"] { cursor: grab; }
.cal-chip:focus-visible { box-shadow: 0 0 0 2px #0f9d6b; }
.cal-chip:hover { box-shadow: 0 4px 10px -6px rgba(15, 42, 38, 0.5); }
.cal-chip.st-published { border-left-color: #0f9d6b; background: #f0faf5; color: #4d6561; }
.cal-chip.st-draft { border-left-color: #9aa8a5; border-style: dashed; border-left-style: solid; background: #fff; }
.cal-chip-dots { display: inline-flex; gap: 2px; flex: none; }
.cal-chip-dots i { width: 7px; height: 7px; border-radius: 50%; display: block; }
.cal-chip-time { font-weight: 700; font-size: 10.5px; color: var(--muted); flex: none; }
.cal-chip-title { flex: 1 1 0; min-width: 60px; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cal-chip-cap { flex-basis: 100%; font-size: 11px; color: var(--muted); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.cal-grid.week .cal-chip-title { white-space: normal; }
.cal-more { align-self: flex-start; border: 0; background: transparent; font: inherit; font-size: 11.5px; font-weight: 600; color: #0a5548; cursor: pointer; padding: 1px 4px; border-radius: 6px; }
.cal-more:hover { background: #dff5ec; }
.cal-more:focus-visible { outline: 2px solid #0f9d6b; }

.cal-legend { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 16px; margin-top: 12px; font-size: 12px; color: var(--muted); }
.cal-legend span { display: inline-flex; align-items: center; gap: 6px; }
.lg { width: 10px; height: 10px; border-radius: 3px; display: inline-block; border-left: 3px solid; }
.lg-scheduled { border-color: #2f5be7; background: #f3f6ff; }
.lg-published { border-color: #0f9d6b; background: #f0faf5; }
.lg-draft { border-color: #9aa8a5; background: #fff; box-shadow: 0 0 0 1px var(--line); }

/* list view */
.ag-day { display: flex; gap: 16px; padding: 14px 18px; border-bottom: 1px solid #eef3f1; }
.ag-date { width: 52px; flex: none; text-align: center; padding: 6px 0; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); align-self: flex-start; }
.ag-date b { display: block; font-size: 18px; line-height: 1.1; }
.ag-date span { font-size: 11px; color: var(--muted); }
.ag-date.today { background: #0a5548; border-color: #0a5548; color: #fff; }
.ag-date.today span { color: rgba(255, 255, 255, 0.75); }
.ag-posts { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.ag-row { display: flex; align-items: center; gap: 12px; padding: 8px 10px; border-radius: 10px; cursor: pointer; outline: none; }
.ag-row:hover { background: #f4f9f7; }
.ag-row:focus-visible { box-shadow: 0 0 0 2px #0f9d6b; }
.ag-time { width: 66px; flex: none; font-size: 12px; font-weight: 600; color: var(--muted); }
.ag-main { flex: 1; min-width: 0; }
.ag-cap { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ag-empty { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 48px 20px; }
.ag-empty-ic { width: 56px; height: 56px; border-radius: 50%; background: #e3f6ee; display: grid; place-items: center; }

/* ───────── Side cards ───────── */
.net { width: 100%; display: flex; align-items: center; gap: 10px; padding: 7px 4px; border: 0; background: transparent; font: inherit; color: var(--ink); text-align: left; cursor: pointer; border-radius: 10px; transition: opacity 0.15s; }
.net:hover { background: #f4f9f7; }
.net:focus-visible { outline: 2px solid #0f9d6b; }
.net.off { opacity: 0.5; }
.net-ic { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.net b { flex: 1; font-size: 13px; font-weight: 600; }
.net-count { font-size: 11px; font-weight: 700; color: #3d5450; background: #e8eeec; border-radius: 10px; padding: 0 8px; }
.up { display: flex; gap: 12px; padding: 8px 6px; border-radius: 10px; cursor: pointer; outline: none; }
.up + .up { border-top: 1px solid #eef3f1; }
.up:hover { background: #f4f9f7; }
.up:focus-visible { box-shadow: 0 0 0 2px #0f9d6b; }
.up-when { width: 82px; flex: none; line-height: 1.35; }
.up-when b { display: block; font-size: 12.5px; }
.up-when span { font-size: 11.5px; color: var(--muted); }
.up-main { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 4px; }
.up-title { font-size: 12.5px; line-height: 1.4; }
.occ { padding: 10px 0; border-bottom: 1px dashed #d5dfdc; }
.occ:first-child { padding-top: 0; }
.occ:last-child { border-bottom: 0; padding-bottom: 0; }
.occ-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 3px; }
.occ-top b { font-size: 13px; }
.occ .tt-subt { line-height: 1.5; }
.occ-btn { margin-top: 6px; font: inherit; font-size: 12px; font-weight: 600; color: #b4381a; background: #fce1d8; border: 1px solid #f5c2b3; border-radius: 14px; padding: 2px 12px; cursor: pointer; }
.occ-btn:hover { background: #f9d3c6; }
.occ-btn:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }

/* ───────── Dialog ───────── */
.tt-dialog { position: relative; background: #fff; color: var(--ink); border-radius: 16px; overflow: hidden; font-family: Poppins, "Segoe UI", sans-serif; }
.tt-dialog::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-dialog-head { display: flex; align-items: center; padding: 20px 20px 14px; border-bottom: 1px solid var(--line); }
.tt-dialog-head .tt-head-ic { background: #dff5ec; color: #0f9d6b; }
.tt-dialog-title { min-width: 0; }
.tt-info-title { font-size: 14.5px; font-weight: 600; line-height: 1.35; }
.tt-dialog-body { padding: 16px 20px 6px; }
.tt-dialog-foot { display: flex; align-items: center; gap: 8px; padding: 12px 20px 16px; }
.dl-chans { display: flex; flex-wrap: wrap; gap: 6px; }
.dl-chan { --c: #0f9d6b; display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: var(--c); background: color-mix(in srgb, var(--c) 9%, white); border-radius: 20px; padding: 3px 10px; }
.dl-cap { margin-top: 12px; padding: 12px 14px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); font-size: 13.5px; line-height: 1.6; white-space: pre-wrap; overflow-wrap: anywhere; }
.tt-field-label { font-size: 12px; font-weight: 600; margin: 16px 0 8px; padding-top: 14px; border-top: 1px dashed #d5dfdc; }
.dl-save { height: 40px !important; }

@media (max-width: 1180px) { .tt-layout { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 599px) { .ag-day { padding: 12px; gap: 10px; } .ag-row { flex-wrap: wrap; } .ag-time { width: auto; } }
@media (prefers-reduced-motion: reduce) { .cal-cell, .cal-add, .net, .tt-seg button { transition: none; } }
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
</style>
