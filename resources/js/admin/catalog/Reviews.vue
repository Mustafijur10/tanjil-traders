<template>
    <div>
        <PageHeader title="Reviews" subtitle="Customer reviews appear on the product page after you approve them. Reply to show you care." />

        <section class="tt-box">
            <div class="tt-toolbar">
                <div class="tt-segments" role="tablist" :aria-label="$t('Filter reviews')">
                    <button v-for="t in tabs" :key="t.value" type="button" role="tab" :class="{ 'is-on': status === t.value }" :aria-selected="status === t.value" @click="setStatus(t.value)">
                        {{ $t(t.label) }}<span class="tt-count tt-num">{{ $num(counts[t.value || "all"] ?? 0) }}</span>
                    </button>
                </div>
                <div class="tt-toolbar-grow"></div>
                <v-select-trz v-model="rating" density="compact" :items="ratingOptions" :aria-label="$t('Rating')" />
                <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search reviews or products')" clearable :aria-label="$t('Search')" />
            </div>

            <div v-if="loading && !items.length" class="tt-box-body tt-skel-group"><span v-for="n in 5" :key="n" class="tt-skel"></span></div>

            <EmptyState
                v-else-if="!items.length"
                icon="mdi-star-outline"
                :title="status === 'pending' ? 'No reviews waiting' : 'No reviews yet'"
                :text="status === 'pending' ? 'New reviews from customers show up here for you to approve.' : 'Reviews appear here when customers rate products they bought.'"
            />

            <article v-for="r in items" :key="r.id" class="tt-review" :class="{ 'is-pending': !r.is_approved }">
                <div class="tt-review-product">
                    <span class="tt-thumb"><img v-if="r.product?.images?.[0]" :src="url(r.product.images[0].path)" alt="" loading="lazy" /><v-icon v-else size="18">mdi-image-off-outline</v-icon></span>
                    <router-link v-if="r.product" :to="`/admin/products/${r.product.id}`" class="tt-ellipsis">{{ r.product.name }}</router-link>
                    <span v-else class="tt-muted">{{ $t("Deleted product") }}</span>
                </div>
                <div class="tt-review-body">
                    <div class="tt-review-meta">
                        <span class="tt-rating-stars" :aria-label="$t('{n} out of 5 stars', { n: r.rating })">
                            <v-icon v-for="n in 5" :key="n" size="16" :class="n <= r.rating ? 'is-on' : ''">mdi-star</v-icon>
                        </span>
                        <b>{{ r.name }}</b>
                        <span class="tt-muted tt-small">{{ $date(r.created_at) }}</span>
                        <StatusChip :label="r.is_approved ? 'Published' : 'Waiting for approval'" :tone="r.is_approved ? 'green' : 'amber'" />
                    </div>
                    <p v-if="r.comment" class="tt-review-text">{{ r.comment }}</p>
                    <p v-else class="tt-muted tt-small">{{ $t("Rating only, no comment.") }}</p>
                    <div v-if="r.reply" class="tt-review-reply">
                        <b>{{ $t("Your reply") }}</b>
                        <p>{{ r.reply }}</p>
                    </div>
                </div>
                <div class="tt-review-actions">
                    <v-btn-primary v-if="!r.is_approved" size="small" :loading="busyId === r.id" @click="update(r, { is_approved: true })">{{ $t("Approve") }}</v-btn-primary>
                    <v-btn-secondary v-else size="small" :loading="busyId === r.id" @click="update(r, { is_approved: false })">{{ $t("Hide") }}</v-btn-secondary>
                    <v-btn-tertiary size="small" prepend-icon="mdi-reply-outline" @click="openReply(r)">{{ $t(r.reply ? "Edit reply" : "Reply") }}</v-btn-tertiary>
                    <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="askDelete(r)" />
                </div>
            </article>

            <div v-if="lastPage > 1" class="tt-box-foot">
                <v-pagination v-model="page" :length="lastPage" density="comfortable" total-visible="7" />
            </div>
        </section>

        <v-dialog-trz v-model="replyDialog" max-width="560">
            <v-card>
                <div class="tt-box-head">
                    <div class="flex-grow-1">
                        <h2>{{ $t("Reply to {name}", { name: replying?.name || "" }) }}</h2>
                        <p>{{ $t("Your reply is shown under the review on the product page.") }}</p>
                    </div>
                </div>
                <v-card-text class="pa-5">
                    <blockquote v-if="replying?.comment" class="tt-quote">{{ replying.comment }}</blockquote>
                    <v-textarea-trz v-model="replyText" :label="$t('Your reply')" rows="4" autofocus counter="2000" />
                </v-card-text>
                <div class="tt-box-foot">
                    <v-btn-tertiary @click="replyDialog = false">{{ $t("Cancel") }}</v-btn-tertiary>
                    <v-btn-primary :loading="busyId === replying?.id" @click="saveReply">{{ $t("Save reply") }}</v-btn-primary>
                </div>
            </v-card>
        </v-dialog-trz>

        <ConfirmDialog v-model="deleteDialog" title="Delete this review?" text="It is removed from the product page and the rating is recalculated." :loading="!!busyId" @confirm="remove" />
    </div>
</template>

<script>
import api, { fileUrl } from "@/api";

export default {
    name: "Reviews",
    data() {
        return {
            items: [],
            counts: {},
            loading: true,
            status: this.$route.query.status || "pending",
            rating: null,
            search: "",
            page: 1,
            lastPage: 1,
            busyId: null,
            replyDialog: false,
            replying: null,
            replyText: "",
            deleteDialog: false,
            deleting: null,
            timer: null,
        };
    },
    computed: {
        tabs: () => [
            { value: "pending", label: "Waiting" },
            { value: "approved", label: "Published" },
            { value: "", label: "All" },
        ],
        ratingOptions() {
            return [{ value: null, title: this.$t("Any rating") }, ...[5, 4, 3, 2, 1].map((n) => ({ value: n, title: this.$t("{n} stars", { n }) }))];
        },
    },
    watch: {
        page: "load",
        rating() {
            this.page = 1;
            this.load();
        },
        search() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.page = 1;
                this.load();
            }, 300);
        },
    },
    created() {
        this.load();
    },
    methods: {
        url: fileUrl,
        setStatus(v) {
            this.status = v;
            this.page = 1;
            this.$router.replace({ query: v ? { status: v } : {} });
            this.load();
        },
        async load() {
            this.loading = true;
            try {
                const { data } = await api.get("/admin/reviews", {
                    params: { page: this.page, status: this.status || undefined, rating: this.rating || undefined, search: this.search || undefined },
                });
                this.items = data.items.data;
                this.lastPage = data.items.last_page;
                this.counts = data.counts;
            } finally {
                this.loading = false;
            }
        },
        async update(review, change) {
            this.busyId = review.id;
            try {
                await api.put(`/admin/reviews/${review.id}`, change);
                Object.assign(review, change);
                this.$store.dispatch("notify", this.$t(change.is_approved === true ? "Review published." : change.is_approved === false ? "Review hidden." : "Reply saved."));
                const { data } = await api.get("/admin/reviews", { params: { page: 1, status: "pending" } });
                this.counts = data.counts;
            } finally {
                this.busyId = null;
            }
        },
        openReply(review) {
            this.replying = review;
            this.replyText = review.reply || "";
            this.replyDialog = true;
        },
        async saveReply() {
            await this.update(this.replying, { reply: this.replyText.trim() || null });
            this.replyDialog = false;
        },
        askDelete(review) {
            this.deleting = review;
            this.deleteDialog = true;
        },
        async remove() {
            this.busyId = this.deleting.id;
            try {
                await api.delete(`/admin/reviews/${this.deleting.id}`);
                this.deleteDialog = false;
                this.$store.dispatch("notify", this.$t("Review deleted."));
                this.load();
            } finally {
                this.busyId = null;
            }
        },
    },
};
</script>
