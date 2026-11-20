<template>
    <div class="tt-auth">
        <aside class="tt-auth-side">
            <router-link to="/" class="tt-auth-brand">
                <span class="tt-sidebar-mark">{{ storeName.charAt(0) }}</span>
                {{ storeName }}
            </router-link>
            <div class="tt-auth-pitch">
                <h2>{{ $t("Run the whole shop from one place.") }}</h2>
                <p>{{ $t("Products, orders, stock, content and reports for your electronics store, in English and Bangla.") }}</p>
            </div>
            <div class="tt-auth-facts">
                <div><b>{{ $t("Orders") }}</b><span>{{ $t("from checkout to delivery") }}</span></div>
                <div><b>{{ $t("Stock") }}</b><span>{{ $t("updated with every sale") }}</span></div>
                <div><b>{{ $t("Content") }}</b><span>{{ $t("banners, pages, translations") }}</span></div>
            </div>
        </aside>

        <main class="tt-auth-main">
            <div class="tt-auth-tools"><PrefToggles always /></div>
            <div class="tt-auth-card">
                <h1>{{ $t(title) }}</h1>
                <p class="tt-auth-lead">{{ $t(lead) }}</p>
                <slot />
            </div>
        </main>
    </div>
</template>

<script>
/* Two-column frame used by the sign-in, forgot password and reset password pages. */
export default {
    name: "AuthShell",
    props: {
        title: { type: String, required: true },
        lead: { type: String, default: "" },
    },
    computed: {
        storeName() {
            return this.$store.state.prefs.settings["store.name"] || "Tanjil Traders";
        },
    },
};
</script>
