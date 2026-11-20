<template>
    <v-slide-x-transition appear>
        <div class="tt-rev">
            <!-- ═════════════════════════ Hero ═════════════════════════ -->
            <header class="tt-hero">
                <div class="d-flex flex-wrap align-end ga-4">
                    <div>
                        <h1 class="tt-h1">Reviews</h1>
                        <div class="tt-hero-sub">Approve what customers say about your products and reply to them in one place</div>
                    </div>
                    <v-spacer />
                    <div class="d-flex flex-wrap align-center ga-3">
                        <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-download" @click="exportCsv">Export CSV</v-btn>
                        <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-clock-check-outline" :disabled="!counts.Pending" @click="segment = 'Pending'">
                            Review {{ counts.Pending }} pending
                        </v-btn>
                    </div>
                </div>

                <div class="tt-hero-strip">
                    <div v-for="s in heroStats" :key="s.label" class="tt-hero-item" :style="{ '--hue': s.color }">
                        <div class="tt-hero-icon"><v-icon :icon="s.icon" size="20" color="white" /></div>
                        <div class="flex-grow-1">
                            <div class="tt-hero-value">{{ s.value }}</div>
                            <div class="tt-hero-label">{{ s.label }}</div>
                            <div class="tt-hero-note">{{ s.note }}</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ═════════════════════════ Insight row ═════════════════════════ -->
            <v-row dense class="mt-3">
                <v-col cols="12" md="5">
                    <v-card flat class="tt-card h-100">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-star-half-full</v-icon></div>
                            <div>
                                <div class="tt-card-title">Rating breakdown</div>
                                <div class="tt-sub">Select a row to filter the table</div>
                            </div>
                            <v-spacer />
                            <v-btn v-if="ratingFilter" variant="text" size="small" class="text-none tt-link" @click="ratingFilter = 0">Clear filter</v-btn>
                        </div>
                        <div class="tt-body">
                            <button
                                v-for="r in distribution"
                                :key="r.stars"
                                type="button"
                                class="tt-dist"
                                :class="{ on: ratingFilter === r.stars }"
                                :aria-pressed="ratingFilter === r.stars"
                                @click="ratingFilter = ratingFilter === r.stars ? 0 : r.stars"
                            >
                                <span class="tt-dist-label">{{ r.stars }} <v-icon size="13" color="#f5a524">mdi-star</v-icon></span>
                                <span class="tt-dist-track"><span :style="{ width: r.pct + '%', background: r.color }"></span></span>
                                <span class="tt-dist-count">{{ r.count }}</span>
                            </button>
                        </div>
                    </v-card>
                </v-col>

                <v-col cols="12" md="7">
                    <v-card flat class="tt-card h-100">
                        <div class="tt-head">
                            <div class="tt-head-ic"><v-icon size="16">mdi-lightbulb-on-outline</v-icon></div>
                            <div>
                                <div class="tt-card-title">Needs your attention</div>
                                <div class="tt-sub">The reviews that affect trust most</div>
                            </div>
                        </div>
                        <div class="tt-body">
                            <div v-for="a in attention" :key="a.label" class="tt-attn" :style="{ '--c': a.color }">
                                <div class="tt-attn-ic"><v-icon size="18" color="white">{{ a.icon }}</v-icon></div>
                                <div class="flex-grow-1">
                                    <div class="tt-strong">{{ a.title }}</div>
                                    <div class="tt-sub">{{ a.text }}</div>
                                </div>
                                <v-btn variant="outlined" size="small" class="text-none tt-attn-btn" :disabled="!a.count" @click="a.apply">{{ a.label }}</v-btn>
                            </div>
                        </div>
                    </v-card>
                </v-col>
            </v-row>

            <!-- ═════════════════════════ Table ═════════════════════════ -->
            <v-card flat class="tt-card mt-5">
                <div class="tt-head tt-head-wrap">
                    <div class="tt-head-ic"><v-icon size="16">mdi-comment-star-outline</v-icon></div>
                    <div>
                        <div class="tt-card-title">All reviews</div>
                        <div class="tt-sub">{{ filteredItems.length }} of {{ items.length }} shown</div>
                    </div>
                    <v-spacer />
                    <v-select v-model="productFilter" :items="productOptions" prepend-inner-icon="mdi-package-variant-closed" variant="outlined" density="compact" hide-details class="tt-filter mr-2" />
                    <v-text-field v-model="searchValue" placeholder="Search reviews…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details class="tt-search" @click:clear="searchValue = ''" />
                </div>

                <div class="tt-segments" role="tablist" aria-label="Review status">
                    <button v-for="s in segments" :key="s.value" type="button" role="tab" class="tt-segment" :class="{ on: segment === s.value }" :aria-selected="segment === s.value" @click="segment = s.value">
                        {{ s.label }}<span class="tt-segment-count">{{ s.count }}</span>
                    </button>
                </div>

                <!-- Bulk action bar -->
                <div v-if="selected.length" class="tt-bulk" role="status">
                    <span class="tt-strong">{{ selected.length }} selected</span>
                    <v-spacer />
                    <v-btn size="small" variant="flat" class="text-none tt-bulk-ok" prepend-icon="mdi-check" :loading="bulkBusy" @click="bulkSet('Approved')">Approve</v-btn>
                    <v-btn size="small" variant="outlined" class="text-none" prepend-icon="mdi-close" :disabled="bulkBusy" @click="bulkSet('Rejected')">Reject</v-btn>
                    <v-btn size="small" variant="text" class="text-none" :disabled="bulkBusy" @click="selected = []">Clear</v-btn>
                </div>

                <v-card-text class="tt-body pa-0">
                    <EasyDataTable
                        v-model:items-selected="selected"
                        :headers="headers"
                        :items="filteredItems"
                        table-class-name="customize-table"
                        buttons-pagination
                        :rows-per-page="12"
                        :rows-items="[10, 12, 25, 50]"
                        :fixedIndex="true"
                        :search-value="searchValue"
                        :loading="loading"
                        sort-by="date"
                        sort-type="desc"
                        empty-message="No reviews match these filters"
                    >
                        <template #item-customer="item">
                            <div class="tt-person" @click="openDetail(item)">
                                <v-avatar size="36" class="tt-person-avatar" :style="{ background: avatarColor(item.customer) }">
                                    <span>{{ initials(item.customer) }}</span>
                                </v-avatar>
                                <div class="tt-person-text">
                                    <div class="tt-name">{{ item.customer }}</div>
                                    <div v-if="item.verified" class="tt-verified"><v-icon size="12">mdi-check-decagram</v-icon>Verified buyer</div>
                                    <div v-else class="tt-sub">Not a verified buyer</div>
                                </div>
                            </div>
                        </template>

                        <template #item-product="item">
                            <span class="tt-strong tt-clamp-1">{{ item.product }}</span>
                        </template>

                        <template #item-rating="item">
                            <span class="tt-stars" :aria-label="item.rating + ' out of 5 stars'">
                                <v-icon v-for="n in 5" :key="n" size="15" :color="n <= item.rating ? '#f5a524' : '#d5dfdc'">{{ n <= item.rating ? "mdi-star" : "mdi-star-outline" }}</v-icon>
                            </span>
                        </template>

                        <template #item-title="item">
                            <div class="tt-review-cell" @click="openDetail(item)">
                                <div class="tt-strong tt-clamp-1">{{ item.title || "No title" }}</div>
                                <div class="tt-sub tt-clamp-2">{{ item.body }}</div>
                                <span v-if="item.reply" class="tt-replied"><v-icon size="12">mdi-reply</v-icon>Store replied</span>
                            </div>
                        </template>

                        <template #item-date="item">
                            <span class="tt-muted">{{ fmtDate(item.date) }}</span>
                        </template>

                        <template #item-status="item">
                            <span class="tt-chip" :class="'tone-' + statusTone(item.status)">{{ item.status }}</span>
                        </template>

                        <template #item-operation="item">
                            <div class="tt-actions">
                                <v-btn v-if="item.status !== 'Approved'" icon="mdi-check" size="small" variant="text" color="#0f7a4d" aria-label="Approve review" @click="setOne(item, 'Approved')" />
                                <v-btn v-if="item.status !== 'Rejected'" icon="mdi-close" size="small" variant="text" color="#b4381a" aria-label="Reject review" @click="setOne(item, 'Rejected')" />
                                <v-btn icon="mdi-eye-outline" size="small" variant="text" color="#0a5548" aria-label="Open review" @click="openDetail(item)" />
                                <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" aria-label="Delete review" @click="removeItem(item)" />
                            </div>
                        </template>
                    </EasyDataTable>
                </v-card-text>
            </v-card>

            <!-- ═════════════════════════ Detail drawer ═════════════════════════ -->
            <v-navigation-drawer v-model="drawer" location="right" temporary width="440" class="tt-drawer">
                <template v-if="current">
                    <div class="tt-drawer-hero">
                        <div class="d-flex align-start">
                            <span class="tt-chip tone-light">{{ current.status }}</span>
                            <v-spacer />
                            <v-btn icon="mdi-close" size="small" variant="text" color="white" aria-label="Close" @click="drawer = false" />
                        </div>
                        <div class="tt-drawer-stars">
                            <v-icon v-for="n in 5" :key="n" size="22" :color="n <= current.rating ? '#fbbf24' : 'rgba(255,255,255,0.3)'">{{ n <= current.rating ? "mdi-star" : "mdi-star-outline" }}</v-icon>
                        </div>
                        <div class="tt-drawer-name">{{ current.title || "No title" }}</div>
                        <div class="tt-drawer-sub">On {{ current.product }}</div>
                    </div>

                    <div class="tt-drawer-body">
                        <div class="d-flex align-center ga-3 mb-4">
                            <v-avatar size="40" class="tt-person-avatar" :style="{ background: avatarColor(current.customer) }"><span>{{ initials(current.customer) }}</span></v-avatar>
                            <div>
                                <div class="tt-name">{{ current.customer }}</div>
                                <div class="tt-sub">{{ fmtDate(current.date) }}<template v-if="current.verified"> · <span class="tt-verified-inline">Verified buyer</span></template></div>
                            </div>
                        </div>

                        <p class="tt-quote">{{ current.body }}</p>

                        <div class="tt-sub mt-3"><v-icon size="14">mdi-thumb-up-outline</v-icon> {{ current.helpful || 0 }} people found this helpful</div>

                        <div class="tt-drawer-t mt-6">Your reply</div>
                        <div class="d-flex flex-wrap ga-2 mb-3">
                            <button v-for="t in replyTemplates" :key="t.label" type="button" class="tt-template" @click="replyText = t.text.replace('{name}', firstName(current.customer))">{{ t.label }}</button>
                        </div>
                        <v-textarea v-model="replyText" variant="outlined" density="comfortable" rows="4" hide-details placeholder="Write a public reply. Customers will see it under their review." />
                        <div class="d-flex align-center mt-3">
                            <span v-if="current.reply" class="tt-sub">Last replied {{ fmtDate(current.repliedAt) }}</span>
                            <v-spacer />
                            <v-btn variant="flat" size="small" class="text-none tt-btn-solid" :loading="replying" :disabled="!replyText.trim()" @click="saveReply">{{ current.reply ? "Update reply" : "Post reply" }}</v-btn>
                        </div>
                    </div>

                    <div class="tt-drawer-foot">
                        <v-btn variant="outlined" class="text-none flex-grow-1" prepend-icon="mdi-flag-outline" :disabled="current.status === 'Flagged'" @click="setOne(current, 'Flagged')">Flag</v-btn>
                        <v-btn variant="outlined" class="text-none flex-grow-1" prepend-icon="mdi-close" :disabled="current.status === 'Rejected'" @click="setOne(current, 'Rejected')">Reject</v-btn>
                        <v-btn variant="flat" class="text-none flex-grow-1 tt-btn-solid" prepend-icon="mdi-check" :disabled="current.status === 'Approved'" @click="setOne(current, 'Approved')">Approve</v-btn>
                    </div>
                </template>
            </v-navigation-drawer>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="420">
                <v-card class="tt-dialog">
                    <v-card-title class="text-subtitle-1 font-weight-bold pt-5 px-5">Delete review</v-card-title>
                    <v-card-text class="px-5">Delete the review by <b>{{ deletedName }}</b>? This removes it from the product page and can't be undone. To hide it instead, use Reject.</v-card-text>
                    <v-card-actions class="pb-4 px-5">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="cancel">Keep review</v-btn>
                        <v-btn color="error" variant="flat" class="text-none" :loading="deleting" @click="agree">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
const STAR_COLORS = { 5: "#0f9d6b", 4: "#6ee7b7", 3: "#f59e0b", 2: "#ef6a45", 1: "#dc2626" };

/* Sample rows so the page looks right before your API is connected.
   Set to false once /api/reviews returns real data. */
const USE_SAMPLE_DATA_ON_ERROR = true;
const makeSample = () => {
    let s = 31;
    const r = () => ((s = (s * 9301 + 49297) % 233280) / 233280);
    const day = 864e5;
    const products = ["Anker PowerCore 20000", "Baseus Bowie E9 Earbuds", "Xiaomi Smart Band 8", "TP-Link Archer C6 Router", "JBL Go 4 Speaker", "Ugreen 65W GaN Charger", "Logitech G102 Mouse", "Hoco Type-C Cable 1m", "Redragon K552 Keyboard", "SanDisk 128GB Pendrive"];
    const people = ["Sabbir Hossain", "Nusrat Jahan", "Tanvir Ahmed", "Farhana Akter", "Arif Hasan", "Shanta Islam", "Imran Khan", "Sumaiya Rahman", "Nazmul Haque", "Mahmudul Karim", "Tasnim Sultana", "Jahid Hasan", "Lamia Chowdhury", "Sadia Afrin", "Kamrul Islam", "Priya Das"];
    const good = [
        ["Works exactly as described", "Charges my phone three times over and still has power left. Delivery to Dhaka took two days."],
        ["Great sound for the price", "Bass is punchy and the battery easily lasts a full day of calls and music."],
        ["Solid build quality", "Feels premium, no heat while charging, and the cable is long enough to reach the bed."],
        ["Fast delivery, genuine product", "Checked the serial number on the brand site and it is original. Packaging was safe."],
    ];
    const mid = [
        ["Good, but not perfect", "Does the job, though the app is confusing at first. Support helped me set it up."],
        ["Okay for everyday use", "Nothing special. Fine for the price but I expected a slightly better finish."],
    ];
    const bad = [
        ["Stopped working after two weeks", "Battery drains fast and now it will not turn on. Waiting on the warranty claim."],
        ["Box was damaged on arrival", "The product works, but the packaging was torn and the cable was loose inside."],
    ];
    return Array.from({ length: 34 }, (_, i) => {
        const rating = r() < 0.55 ? 5 : r() < 0.6 ? 4 : r() < 0.5 ? 3 : r() < 0.6 ? 2 : 1;
        const pool = rating >= 4 ? good : rating === 3 ? mid : bad;
        const [title, body] = pool[Math.floor(r() * pool.length)];
        const status = i % 13 === 12 ? "Flagged" : i % 9 === 8 ? "Rejected" : i < 7 ? "Pending" : "Approved";
        const replied = status === "Approved" && (rating <= 3 ? r() < 0.4 : r() < 0.25);
        return {
            id: i + 1,
            customer: people[i % people.length],
            product: products[Math.floor(r() * products.length)],
            rating,
            title,
            body,
            date: new Date(Date.now() - Math.floor(i * 1.6 * day + r() * day)).toISOString(),
            status,
            verified: r() < 0.82,
            helpful: Math.floor(r() * 24),
            reply: replied ? "Thank you for the feedback. We're always here if you need anything." : "",
            repliedAt: replied ? new Date(Date.now() - i * day).toISOString() : null,
        };
    });
};

export default {
    name: "ReviewList",

    data() {
        return {
            searchValue: "",
            segment: "all",
            ratingFilter: 0,
            productFilter: "All products",
            loading: false,

            items: [],
            selected: [],
            bulkBusy: false,

            drawer: false,
            current: null,
            replyText: "",
            replying: false,

            deleteDialog: false,
            deletedId: -1,
            deletedName: "",
            deleting: false,

            snackbar: false,
            snackbarText: "",
            snackbarColor: "#0f9d6b",

            headers: [
                { text: "Customer", value: "customer", sortable: true, width: 210 },
                { text: "Product", value: "product", sortable: true, width: 190 },
                { text: "Rating", value: "rating", sortable: true, width: 130 },
                { text: "Review", value: "title", width: 320 },
                { text: "Date", value: "date", sortable: true, width: 120 },
                { text: "Status", value: "status", sortable: true, width: 110 },
                { text: "Actions", value: "operation", width: 150 },
            ],

            replyTemplates: [
                { label: "Say thanks", text: "Hi {name}, thank you for taking the time to review this product. We're glad you like it." },
                { label: "Apologise and help", text: "Hi {name}, we're sorry this wasn't the experience you expected. Please message our support team with your order number and we'll make it right." },
            ],
        };
    },

    computed: {
        counts() {
            const c = { Pending: 0, Approved: 0, Rejected: 0, Flagged: 0 };
            this.items.forEach((r) => { c[r.status] = (c[r.status] || 0) + 1; });
            return c;
        },
        segments() {
            return [
                { value: "all", label: "All", count: this.items.length },
                { value: "Pending", label: "Pending", count: this.counts.Pending },
                { value: "Approved", label: "Approved", count: this.counts.Approved },
                { value: "Flagged", label: "Flagged", count: this.counts.Flagged },
                { value: "Rejected", label: "Rejected", count: this.counts.Rejected },
            ];
        },
        productOptions() {
            return ["All products", ...[...new Set(this.items.map((r) => r.product))].sort()];
        },
        filteredItems() {
            return this.items.filter((r) =>
                (this.segment === "all" || r.status === this.segment) &&
                (!this.ratingFilter || r.rating === this.ratingFilter) &&
                (this.productFilter === "All products" || r.product === this.productFilter)
            );
        },
        publicReviews() {
            return this.items.filter((r) => r.status === "Approved");
        },
        average() {
            const rows = this.publicReviews;
            return rows.length ? rows.reduce((a, r) => a + r.rating, 0) / rows.length : 0;
        },
        distribution() {
            const rows = this.items;
            const max = Math.max(1, ...[5, 4, 3, 2, 1].map((n) => rows.filter((r) => r.rating === n).length));
            return [5, 4, 3, 2, 1].map((n) => {
                const count = rows.filter((r) => r.rating === n).length;
                return { stars: n, count, pct: Math.round((count / max) * 100), color: STAR_COLORS[n] };
            });
        },
        lowUnanswered() {
            return this.items.filter((r) => r.rating <= 3 && r.status === "Approved" && !r.reply);
        },
        heroStats() {
            const approved = this.publicReviews.length;
            const replied = this.publicReviews.filter((r) => r.reply).length;
            const verified = this.items.length ? Math.round((this.items.filter((r) => r.verified).length / this.items.length) * 100) : 0;
            return [
                { icon: "mdi-comment-text-multiple-outline", color: "#0f9d6b", value: this.items.length.toLocaleString("en-IN"), label: "Total reviews", note: approved + " visible on your store" },
                { icon: "mdi-star-outline", color: "#f59e0b", value: this.average ? this.average.toFixed(1) + " / 5" : "—", label: "Average rating", note: "From approved reviews" },
                { icon: "mdi-clock-outline", color: "#ef6a45", value: String(this.counts.Pending), label: "Waiting for approval", note: this.counts.Flagged + " flagged for a second look" },
                { icon: "mdi-reply-outline", color: "#7c3aed", value: approved ? Math.round((replied / approved) * 100) + "%" : "0%", label: "Reply rate", note: verified + "% from verified buyers" },
            ];
        },
        attention() {
            return [
                {
                    icon: "mdi-clock-alert-outline", color: "#ef6a45", count: this.counts.Pending, label: "Moderate",
                    title: this.counts.Pending + " reviews waiting for approval",
                    text: "Customers can't see these on your product pages yet.",
                    apply: () => { this.segment = "Pending"; this.ratingFilter = 0; },
                },
                {
                    icon: "mdi-emoticon-sad-outline", color: "#f59e0b", count: this.lowUnanswered.length, label: "Reply now",
                    title: this.lowUnanswered.length + " low ratings with no reply",
                    text: "A quick, polite reply often turns a 2-star review into a returning customer.",
                    apply: () => { this.segment = "Approved"; this.ratingFilter = 0; this.searchValue = ""; this.openDetail(this.lowUnanswered[0]); },
                },
                {
                    icon: "mdi-flag-outline", color: "#7c3aed", count: this.counts.Flagged, label: "Check",
                    title: this.counts.Flagged + " flagged reviews",
                    text: "Reported as spam or inappropriate. Approve or reject each one.",
                    apply: () => { this.segment = "Flagged"; this.ratingFilter = 0; },
                },
            ];
        },
    },

    watch: {
        // changing filters clears the selection so bulk actions never hit hidden rows
        segment() { this.selected = []; },
        ratingFilter() { this.selected = []; },
        productFilter() { this.selected = []; },
    },

    methods: {
        /* ── helpers ── */
        fmtDate(d) {
            if (!d) return "—";
            const dt = new Date(d);
            return isNaN(dt) ? "—" : dt.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },
        initials(name) {
            const p = (name || "?").trim().split(/\s+/);
            return ((p[0] || "")[0] + (p.length > 1 ? p[p.length - 1][0] : "")).toUpperCase();
        },
        firstName(name) {
            return (name || "there").trim().split(/\s+/)[0];
        },
        avatarColor(name) {
            const colors = ["#0f9d6b", "#2f5be7", "#7c3aed", "#0f766e", "#f59e0b", "#ec4899", "#ef6a45"];
            let h = 0;
            for (const ch of name || "") h = (h * 31 + ch.charCodeAt(0)) >>> 0;
            return colors[h % colors.length];
        },
        statusTone(s) {
            return { Approved: "green", Pending: "amber", Flagged: "violet", Rejected: "red" }[s] || "gray";
        },

        /* ── data ── */
        normalize(r) {
            return {
                ...r,
                customer: r.customer_name ?? r.customer ?? "Customer",
                product: r.product_name ?? r.product ?? "Product",
                rating: Number(r.rating || 0),
                title: r.title ?? "",
                body: r.body ?? r.comment ?? "",
                date: r.created_at ?? r.date ?? null,
                status: r.status || "Pending",
                verified: Boolean(r.verified_purchase ?? r.verified),
                helpful: Number(r.helpful_count ?? r.helpful ?? 0),
                reply: r.reply ?? r.store_reply ?? "",
                repliedAt: r.replied_at ?? r.repliedAt ?? null,
            };
        },

        async allItem() {
            this.loading = true;
            try {
                const res = await this.axios.get("/api/reviews");
                if (res.data && res.data.success) this.items = res.data.data.map(this.normalize);
                else if (USE_SAMPLE_DATA_ON_ERROR) this.items = makeSample();
            } catch (error) {
                console.error("Failed to load reviews:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) this.items = makeSample();
                else this.showError("Couldn't load reviews. Check your connection and refresh.");
            } finally {
                this.loading = false;
            }
        },

        patchLocal(ids, changes) {
            this.items = this.items.map((r) => (ids.includes(r.id) ? { ...r, ...changes } : r));
            if (this.current && ids.includes(this.current.id)) this.current = this.items.find((r) => r.id === this.current.id);
        },

        /* ── moderation ── */
        async setStatus(ids, status) {
            try {
                const res = await this.axios.post("/api/reviews/status", { ids, status });
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                this.patchLocal(ids, { status });
                return true;
            } catch (error) {
                console.error("Status error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    this.patchLocal(ids, { status });
                    return true;
                }
                this.showError("Couldn't update the review. Try again.");
                return false;
            }
        },
        async setOne(item, status) {
            if (await this.setStatus([item.id], status)) this.showSuccess("Review " + status.toLowerCase() + ".");
        },
        async bulkSet(status) {
            const ids = this.selected.map((r) => r.id);
            if (!ids.length) return;
            this.bulkBusy = true;
            const ok = await this.setStatus(ids, status);
            this.bulkBusy = false;
            if (ok) {
                this.showSuccess(ids.length + " reviews " + status.toLowerCase() + ".");
                this.selected = [];
            }
        },

        /* ── drawer + reply ── */
        openDetail(item) {
            if (!item) return;
            this.current = this.items.find((r) => r.id === item.id) || item;
            this.replyText = this.current.reply || "";
            this.drawer = true;
        },
        async saveReply() {
            const text = this.replyText.trim();
            if (!text || !this.current) return;
            this.replying = true;
            const id = this.current.id;
            const changes = { reply: text, repliedAt: new Date().toISOString() };
            try {
                const res = await this.axios.post(`/api/reviews/${id}/reply`, { reply: text });
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                this.patchLocal([id], changes);
                this.showSuccess("Reply posted.");
            } catch (error) {
                console.error("Reply error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    this.patchLocal([id], changes);
                    this.showSuccess("Reply posted (demo data).");
                } else {
                    this.showError("Couldn't post your reply. Try again.");
                }
            } finally {
                this.replying = false;
            }
        },

        /* ── delete ── */
        removeItem(item) {
            this.deletedId = item.id;
            this.deletedName = item.customer;
            this.deleteDialog = true;
        },
        cancel() {
            this.deleteDialog = false;
            this.deletedId = -1;
            this.deletedName = "";
        },
        async agree() {
            this.deleting = true;
            const id = this.deletedId;
            const drop = () => {
                this.items = this.items.filter((r) => r.id !== id);
                this.selected = this.selected.filter((r) => r.id !== id);
                if (this.current && this.current.id === id) this.drawer = false;
            };
            try {
                const res = await this.axios.delete(`/api/reviews/${id}`);
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                drop();
                this.showSuccess("Review deleted.");
            } catch (error) {
                console.error("Delete error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    drop();
                    this.showSuccess("Review deleted (demo data).");
                } else {
                    this.showError("Couldn't delete the review. Try again.");
                }
            } finally {
                this.deleting = false;
                this.cancel();
            }
        },

        /* ── export ── */
        exportCsv() {
            const rows = this.filteredItems;
            if (!rows.length) return this.showError("There are no reviews to export.");
            const head = ["Customer", "Product", "Rating", "Title", "Review", "Date", "Status", "Verified buyer", "Store reply"];
            const esc = (v) => '"' + String(v ?? "").replace(/"/g, '""') + '"';
            const lines = rows.map((r) => [r.customer, r.product, r.rating, r.title, r.body, this.fmtDate(r.date), r.status, r.verified ? "Yes" : "No", r.reply].map(esc).join(","));
            const blob = new Blob(["\ufeff" + [head.map(esc).join(","), ...lines].join("\n")], { type: "text/csv;charset=utf-8" });
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = "reviews.csv";
            a.click();
            URL.revokeObjectURL(a.href);
            this.showSuccess("Exported " + rows.length + " reviews.");
        },

        showSuccess(msg) {
            this.snackbarText = msg;
            this.snackbarColor = "#0f9d6b";
            this.snackbar = true;
        },
        showError(msg) {
            this.snackbarText = msg;
            this.snackbarColor = "#b3261e";
            this.snackbar = true;
        },
    },

    created() {
        document.title = "Reviews";
        this.allItem();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-rev {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg2: #f7faf9;
    --accent: #0f9d6b;
    --tint: #e3f6ee;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    padding-bottom: 36px;
    font-variant-numeric: tabular-nums;
    -webkit-font-smoothing: antialiased;
}

/* ───────── Hero ───────── */
.tt-hero {
    position: relative; overflow: hidden; color: #fff; padding: 24px 26px 26px; border-radius: 18px;
    background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%);
    box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95);
}
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-h1 { font-size: 30px; font-weight: 700; line-height: 1.15; letter-spacing: -0.6px; color: #fff; }
.tt-hero-sub { font-size: 13.5px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; max-width: 560px; }
.tt-btn-ghost { height: 40px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 40px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-btn-primary.v-btn--disabled { opacity: 0.55; }
.tt-btn-solid { background: #0a5548 !important; color: #fff !important; font-weight: 600; }
.tt-btn-solid.v-btn--disabled { opacity: 0.45; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 22px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: flex-start; gap: 14px; padding: 15px 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -40px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.25; filter: blur(10px); }
.tt-hero-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-value { font-size: 22px; font-weight: 700; line-height: 1.2; letter-spacing: -0.4px; }
.tt-hero-label { font-size: 12.5px; font-weight: 500; color: rgba(255, 255, 255, 0.9); }
.tt-hero-note { font-size: 11.5px; color: rgba(255, 255, 255, 0.58); }
@media (max-width: 1279px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Cards ───────── */
.tt-card {
    position: relative; background: #fff; border: 1px solid var(--line) !important; border-radius: 16px !important;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35) !important; overflow: hidden;
}
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 8px; }
.tt-head-wrap { flex-wrap: wrap; row-gap: 10px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; line-height: 1.3; color: var(--ink); }
.tt-sub { font-size: 12px; color: var(--muted); margin-top: 1px; }
.tt-body { padding: 8px 18px 18px; }
.tt-muted { color: var(--muted); }
.tt-strong { font-weight: 600; color: var(--ink); }
.tt-link { color: #0a5548 !important; font-weight: 600; }
.tt-search { max-width: 280px; min-width: 200px; }
.tt-filter { max-width: 240px; min-width: 170px; }
.tt-search :deep(.v-field), .tt-filter :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-clamp-1 { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.45; }

/* ───────── Rating breakdown ───────── */
.tt-dist { display: flex; align-items: center; gap: 12px; width: 100%; border: 0; background: transparent; font: inherit; text-align: left; padding: 7px 10px; border-radius: 10px; cursor: pointer; transition: background 0.15s; }
.tt-dist:hover { background: var(--bg2); }
.tt-dist.on { background: var(--tint); }
.tt-dist:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-dist-label { flex: 0 0 38px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; }
.tt-dist-track { flex: 1; height: 8px; border-radius: 8px; background: #e8eeec; overflow: hidden; }
.tt-dist-track span { display: block; height: 100%; border-radius: 8px; }
.tt-dist-count { flex: 0 0 34px; text-align: right; font-size: 12.5px; font-weight: 600; color: var(--muted); }

/* ───────── Attention list ───────── */
.tt-attn { --c: #ef6a45; display: flex; align-items: center; gap: 14px; padding: 12px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); margin-bottom: 10px; }
.tt-attn:last-child { margin-bottom: 0; }
.tt-attn-ic { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; background: var(--c); flex: none; box-shadow: 0 8px 14px -8px var(--c); }
.tt-attn-btn { border-color: #0a5548 !important; color: #0a5548 !important; font-weight: 600; flex: none; }
@media (max-width: 599px) { .tt-attn { flex-wrap: wrap; } .tt-attn-btn { width: 100%; } }

/* ───────── Segments + bulk bar ───────── */
.tt-segments { display: flex; gap: 4px; padding: 6px 14px 10px; overflow-x: auto; scrollbar-width: none; border-bottom: 1px solid var(--line); }
.tt-segments::-webkit-scrollbar { display: none; }
.tt-segment { flex: none; display: inline-flex; align-items: center; gap: 8px; border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: var(--muted); padding: 7px 14px; border-radius: 11px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-segment:hover { color: var(--ink); background: #eaf2ef; }
.tt-segment.on { background: #0a5548; color: #fff; font-weight: 600; }
.tt-segment:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-segment-count { font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 10px; background: #e3eae8; color: #3a4a47; }
.tt-segment.on .tt-segment-count { background: rgba(255, 255, 255, 0.22); color: #fff; }
.tt-bulk { display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: #eaf8f1; border-bottom: 1px solid #b7e6cd; font-size: 13px; }
.tt-bulk-ok { background: #0f9d6b !important; color: #fff !important; font-weight: 600; }

/* ───────── Table cells ───────── */
.tt-person { display: flex; align-items: center; gap: 12px; padding: 6px 0; cursor: pointer; min-width: 0; }
.tt-person-avatar { flex: none; color: #fff; font-size: 13px; font-weight: 600; }
.tt-person-text { min-width: 0; }
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-verified { display: inline-flex; align-items: center; gap: 3px; font-size: 11.5px; color: #0f7a4d; font-weight: 500; }
.tt-verified-inline { color: #0f7a4d; font-weight: 500; }
.tt-stars { display: inline-flex; gap: 1px; white-space: nowrap; }
.tt-review-cell { padding: 6px 0; cursor: pointer; max-width: 320px; }
.tt-review-cell:hover .tt-strong { color: #0a5548; }
.tt-replied { display: inline-flex; align-items: center; gap: 3px; margin-top: 3px; font-size: 11px; font-weight: 600; color: #5b3fd1; }
.tt-actions { display: flex; gap: 0; }

.tt-chip { display: inline-block; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; white-space: nowrap; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-red { background: #fce1d8; color: #b4381a; }
.tone-violet { background: #ebe5ff; color: #5b3fd1; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.tone-light { background: rgba(255, 255, 255, 0.16); color: #fff; border: 1px solid rgba(255, 255, 255, 0.28); }

/* ───────── Drawer ───────── */
.tt-drawer { font-family: Poppins, "Segoe UI", sans-serif; }
.tt-drawer-hero { color: #fff; padding: 18px 20px 20px; background: radial-gradient(420px 200px at 100% 0%, rgba(110, 231, 183, 0.22), transparent 62%), linear-gradient(135deg, #053d35 0%, #0a5548 60%, #0b6150 100%); }
.tt-drawer-stars { display: flex; gap: 2px; margin-top: 12px; }
.tt-drawer-name { font-size: 19px; font-weight: 700; letter-spacing: -0.3px; margin-top: 8px; line-height: 1.3; }
.tt-drawer-sub { font-size: 12.5px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; }
.tt-drawer-body { padding: 18px 20px 96px; }
.tt-drawer-t { font-size: 12.5px; font-weight: 600; margin-bottom: 8px; }
.tt-quote { font-size: 14px; line-height: 1.7; margin: 0; padding: 14px 16px; background: var(--bg2); border: 1px solid var(--line); border-left: 3px solid #0f9d6b; border-radius: 10px; }
.tt-template { border: 1px solid var(--line); background: #fff; font: inherit; font-size: 12px; font-weight: 500; color: #0a5548; padding: 5px 12px; border-radius: 20px; cursor: pointer; transition: background 0.15s, border-color 0.15s; }
.tt-template:hover { background: var(--tint); border-color: #0f9d6b; }
.tt-template:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-drawer-foot { position: absolute; left: 0; right: 0; bottom: 0; display: flex; gap: 10px; padding: 14px 20px; background: #fff; border-top: 1px solid var(--line); }
.tt-dialog { border-radius: 16px !important; }

/* ───────── EasyDataTable theme ───────── */
:deep(.customize-table) {
    --easy-table-border: 0;
    --easy-table-row-border: 1px solid #eef3f1;
    --easy-table-header-font-size: 12px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #e6f4ef;
    --easy-table-header-background-color: #0a5548;
    --easy-table-body-row-height: 68px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #f4f9f7;
    --easy-table-body-item-padding: 6px 14px;
    --easy-table-footer-background-color: #f7faf9;
    --easy-table-footer-font-color: #3a4a47;
    --easy-table-footer-height: 52px;
    --easy-table-loading-mask-background-color: #ffffff;
    --easy-table-scrollbar-track-color: #eef3f1;
    --easy-table-scrollbar-color: #c8d4d0;
    --easy-table-buttons-pagination-border: 1px solid #d5dfdc;
    font-family: Poppins, "Segoe UI", sans-serif;
}
:deep(.customize-table .buttons-pagination .item.button.active) { background: #0a5548; border-color: #0a5548; color: #fff; }
:deep(.customize-table .vue3-easy-data-table__header th) { font-weight: 600; letter-spacing: 0.2px; }
:deep(.customize-table .easy-checkbox input:checked + label::before) { background: #0f9d6b; border-color: #0f9d6b; }
</style>