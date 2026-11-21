<template>
    <div>
        <PageHeader title="Contact Messages" subtitle="Messages sent from the contact form on your website." />

        <div class="tt-inbox">
            <section class="tt-box tt-inbox-list">
                <div class="tt-toolbar">
                    <div class="tt-segments" role="tablist" :aria-label="$t('Filter messages')">
                        <button v-for="f in filters" :key="f.value" type="button" role="tab" :class="{ 'is-on': filter === f.value }" :aria-selected="filter === f.value" @click="setFilter(f.value)">
                            {{ $t(f.label) }}<span v-if="f.value === 'unread'" class="tt-count tt-num">{{ $num(unread) }}</span>
                        </button>
                    </div>
                    <v-input-trz v-model="search" density="compact" prepend-inner-icon="mdi-magnify" :placeholder="$t('Search')" clearable :aria-label="$t('Search')" />
                </div>

                <div v-if="loading && !items.length" class="tt-box-body tt-skel-group"><span v-for="n in 5" :key="n" class="tt-skel"></span></div>
                <EmptyState v-else-if="!items.length" icon="mdi-email-open-outline" :title="filter === 'unread' ? 'All caught up' : 'No messages yet'" :text="filter === 'unread' ? 'There are no unread messages.' : 'Messages from your contact page will appear here.'" />

                <button
                    v-for="m in items"
                    :key="m.id"
                    type="button"
                    class="tt-inbox-item"
                    :class="{ 'is-unread': !m.is_read, 'is-on': current?.id === m.id }"
                    @click="open(m)"
                >
                    <span class="tt-sidebar-avatar">{{ initials(m.name) }}</span>
                    <span class="tt-inbox-text">
                        <span class="tt-inbox-top"><b class="tt-ellipsis">{{ m.name }}</b><small>{{ $date(m.created_at, { day: "numeric", month: "short" }) }}</small></span>
                        <span class="tt-ellipsis tt-inbox-subject">{{ m.subject || $t("No subject") }}</span>
                        <span class="tt-ellipsis tt-muted tt-small">{{ m.message }}</span>
                    </span>
                </button>

                <div v-if="lastPage > 1" class="tt-box-foot"><v-pagination v-model="page" :length="lastPage" density="comfortable" total-visible="5" /></div>
            </section>

            <section class="tt-box tt-inbox-read">
                <EmptyState v-if="!current" icon="mdi-email-outline" title="Select a message" text="Pick a message on the left to read it and reply." />
                <template v-else>
                    <div class="tt-box-head">
                        <span class="tt-sidebar-avatar">{{ initials(current.name) }}</span>
                        <div class="flex-grow-1 tt-ellipsis">
                            <h2 class="tt-ellipsis">{{ current.subject || $t("No subject") }}</h2>
                            <p>{{ current.name }} · {{ $date(current.created_at, { day: "numeric", month: "short", year: "numeric", hour: "numeric", minute: "2-digit" }) }}</p>
                        </div>
                        <v-btn-tertiary size="small" :prepend-icon="current.is_read ? 'mdi-email-outline' : 'mdi-email-open-outline'" @click="markRead(current, !current.is_read)">
                            {{ $t(current.is_read ? "Mark unread" : "Mark read") }}
                        </v-btn-tertiary>
                        <v-btn-tertiary icon="mdi-trash-can-outline" size="small" :aria-label="$t('Delete')" @click="deleteDialog = true" />
                    </div>
                    <div class="tt-box-body">
                        <p class="tt-inbox-message">{{ current.message }}</p>
                        <div class="tt-inbox-reply">
                            <span class="tt-muted tt-small">{{ $t("Reply by") }}</span>
                            <v-btn-primary v-if="current.phone" size="small" prepend-icon="mdi-whatsapp" :href="whatsapp(current)" target="_blank">WhatsApp</v-btn-primary>
                            <v-btn-secondary v-if="current.phone" size="small" prepend-icon="mdi-phone-outline" :href="`tel:${current.phone}`">{{ current.phone }}</v-btn-secondary>
                            <v-btn-secondary v-if="current.email" size="small" prepend-icon="mdi-email-outline" :href="mailto(current)">{{ current.email }}</v-btn-secondary>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        <ConfirmDialog v-model="deleteDialog" title="Delete this message?" :loading="removing" @confirm="remove" />
    </div>
</template>

<script>
import api from "@/api";

export default {
    name: "Messages",
    data: () => ({
        items: [],
        unread: 0,
        loading: true,
        filter: "",
        search: "",
        page: 1,
        lastPage: 1,
        current: null,
        timer: null,
        deleteDialog: false,
        removing: false,
    }),
    computed: {
        filters: () => [
            { value: "", label: "All" },
            { value: "unread", label: "Unread" },
        ],
    },
    watch: {
        page: "load",
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
        initials(name) {
            return (name || "?").split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase();
        },
        whatsapp(m) {
            const digits = m.phone.replace(/\D/g, "").replace(/^0/, "880");
            return `https://wa.me/${digits}?text=${encodeURIComponent(this.$t("Hello {name}, thanks for contacting us.", { name: m.name }))}`;
        },
        mailto(m) {
            return `mailto:${m.email}?subject=${encodeURIComponent("Re: " + (m.subject || this.$t("Your message")))}`;
        },
        setFilter(v) {
            this.filter = v;
            this.page = 1;
            this.load();
        },
        async load() {
            this.loading = true;
            try {
                const { data } = await api.get("/admin/messages", { params: { page: this.page, filter: this.filter || undefined, search: this.search || undefined } });
                this.items = data.items.data;
                this.lastPage = data.items.last_page;
                this.unread = data.unread;
            } finally {
                this.loading = false;
            }
        },
        // Opening a message marks it as read.
        open(m) {
            this.current = m;
            if (!m.is_read) this.markRead(m, true, true);
        },
        async markRead(m, value, quiet = false) {
            m.is_read = value;
            this.unread += value ? -1 : 1;
            try {
                await api.put(`/admin/messages/${m.id}`, { is_read: value });
                if (!quiet) this.$store.dispatch("notify", this.$t(value ? "Marked as read." : "Marked as unread."));
            } catch (e) {
                m.is_read = !value;
            }
        },
        async remove() {
            this.removing = true;
            try {
                await api.delete(`/admin/messages/${this.current.id}`);
                this.deleteDialog = false;
                this.current = null;
                this.load();
            } finally {
                this.removing = false;
            }
        },
    },
};
</script>
