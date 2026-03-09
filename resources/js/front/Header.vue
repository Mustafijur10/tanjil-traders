<template>
    <!-- Top Header Bar -->
    <v-toolbar class="v-trz-toolbar-header bg-primary header-front">
        <router-link to="/" style="cursor: pointer; text-align: left">
            <v-img src="/images/logo.png" max-width="240" class="header-logo ml-3"></v-img>
        </router-link>

        <v-text-field
            v-model="searchQuery"
            :loading="loading"
            append-inner-icon="mdi-magnify"
            density="compact"
            label="Search Here..."
            variant="outlined"
            hide-details
            single-line
            @click:append-inner="onSearch"
            @keyup.enter="onSearch"
            class="ml-5"
        ></v-text-field>

        <v-spacer />

        <router-link to="/admin/login">
            <v-btn color="white" class="ma-0 text-none text-left pl-1 pr-1 mr-2">
                <v-icon color="white darken-2 header-icon">mdi-account-arrow-right-outline</v-icon>
                <span class="pl-2">
                    <small style="font-size: 10px; color:#696973;">Welcome</small><br>
                    <span style="font-size: 12px;">Sign In/ Register</span>
                </span>
            </v-btn>
        </router-link>

        <v-btn color="white" class="ma-0 text-none text-left pl-1 pr-1 mr-2">
            <v-icon color="white darken-2 header-icon">mdi-gift-outline</v-icon>
            <span class="pl-2">
                <small style="font-size: 10px; color:#696973;">Offers</small><br>
                <span style="font-size: 12px;">Latest Offers</span>
            </span>
        </v-btn>

        <v-btn color="white" class="ma-0 text-none text-left pl-1 pr-1 mr-2">
            <v-icon color="white darken-2 header-icon">mdi-bell-badge-outline</v-icon>
            <span class="pl-2">
                <small style="font-size: 10px; color:#696973;">0 Notifications</small><br>
                <span style="font-size: 12px;">Check Notifications</span>
            </span>
        </v-btn>

        <v-btn color="white" class="ma-0 text-none text-left pl-1 pr-1">
            <span class="pr-2">
                <small style="font-size: 10px; color:#696973;">0 item(s)</small><br>
                <span style="font-size: 12px;">Total: 0৳</span>
            </span>
            <v-icon color="white darken-2 header-icon">mdi-cart-variant</v-icon>
        </v-btn>
    </v-toolbar>

    <v-divider class="border-opacity-100" color="success"></v-divider>

    <!-- Nav Category Bar -->
    <v-toolbar class="v-trz-toolbar-header-2 bg-primary header-front">

        <!-- Loading skeleton -->
        <template v-if="loadingCategories">
            <v-skeleton-loader
                v-for="n in 8" :key="n"
                type="text"
                width="80"
                class="ml-3 mt-1"
                color="transparent"
            />
        </template>

        <!-- Dynamic category nav items -->
        <template v-else>
            <v-menu
                v-for="category in navCategories"
                :key="category.id"
                open-on-hover
            >
                <template v-slot:activator="{ props }">
                    <router-link
                        v-bind="props"
                        :to="`/category/${category.slug}`"
                        class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"
                    >
                        {{ category.title }}
                    </router-link>
                </template>

                <!-- Dropdown subcategories -->
                <v-list
                    v-if="category.subcategories && category.subcategories.length"
                    class="rounded-l pa-2 bg-primary toolbar-header mt-2"
                >
                    <v-list-item
                        v-for="sub in category.subcategories"
                        :key="sub.id"
                        class="hover-red"
                    >
                        <v-list-item-title class="d-flex align-center">
                            <router-link
                                :to="`/category/${category.slug}/${sub.slug || sub.id}`"
                                class="ml-3 text-white text-subtitle-2 text-decoration-none"
                            >
                                {{ sub.title }}
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </template>

    </v-toolbar>
</template>

<script>
export default {
    data() {
        return {
            searchQuery: "",
            loading: false,
            loadingCategories: true,

            // Nav categories loaded from API (attribute_id = 47, slug = 'category')
            navCategories: [],
        };
    },

    methods: {

        // ─── SEARCH ──────────────────────────────────────────────────
        onSearch() {
            if (!this.searchQuery.trim()) return;
            this.loading = true;
            // Navigate to search results page
            this.$router.push({ path: "/search", query: { q: this.searchQuery } })
                .finally(() => { this.loading = false; });
        },

        // ─── LOAD NAV CATEGORIES ─────────────────────────────────────
        // Loads top-level categories (attribute_id = 47, parent_attribute_option_id = 0 or null)
        // then loads subcategories for each
        async loadNavCategories() {
            this.loadingCategories = true;
            try {
                // Fetch top-level category options (attribute slug = 'category')
                const response = await this.axios.get("/api/nav-categories");

                if (response.data.success) {
                    this.navCategories = response.data.data;
                }
            } catch (error) {
                console.error("Failed to load nav categories:", error);
            } finally {
                this.loadingCategories = false;
            }
        },
    },

    created() {
        this.loadNavCategories();
    },
};
</script>

<style>
.v-trz-toolbar-header .header-logo .v-img__img {
    position: relative !important;
}
.nav-item {
    white-space: nowrap;
}
.nav-item:hover {
    color: #f0c040 !important;
}
</style>