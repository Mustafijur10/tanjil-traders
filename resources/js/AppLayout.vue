<template>
    <v-navigation-drawer
        v-model="drawer"
        class="tt-sidebar"
        :rail="rail && !mobile"
        :temporary="mobile"
        :permanent="!mobile"
        width="268"
        rail-width="72"
        :expand-on-hover="rail && !mobile"
    >
        <template #prepend>
            <router-link to="/admin" class="tt-sidebar-brand">
                <img v-if="logo" :src="logo" :alt="storeName" />
                <template v-else>
                    <span class="tt-sidebar-mark">{{ storeName.charAt(0) }}</span>
                    <span class="tt-sidebar-name">{{ storeName }}<small>{{ $t("Admin panel") }}</small></span>
                </template>
            </router-link>
        </template>

        <v-list v-model:opened="opened" nav density="compact" color="primary" class="tt-sidebar-list">
            <template v-for="(item, i) in menu" :key="item.section || item.title">
                <div v-if="item.section" class="tt-sidebar-section" :class="{ 'is-first': i === 0 }">{{ $t(item.section) }}</div>

                <v-list-group v-else-if="item.children" :value="item.title">
                    <template #activator="{ props, isOpen }">
                        <v-list-item v-bind="props" :class="{ 'is-current-group': isCurrentGroup(item) && !isOpen }" :title="$t(item.title)">
                            <template #prepend><span class="tt-sidebar-ico"><v-icon :icon="item.icon" size="19" /></span></template>
                        </v-list-item>
                    </template>
                    <v-list-item
                        v-for="child in item.children"
                        :key="child.to"
                        :to="child.ready ? child.to : undefined"
                        :exact="child.exact"
                        :disabled="!child.ready"
                        :title="$t(child.title)"
                    >
                        <template v-if="!child.ready" #append><span class="tt-sidebar-soon">{{ $t("Soon") }}</span></template>
                    </v-list-item>
                </v-list-group>

                <v-list-item v-else :to="item.ready ? item.to : undefined" :exact="item.exact" :disabled="!item.ready" :title="$t(item.title)">
                    <template #prepend><span class="tt-sidebar-ico"><v-icon :icon="item.icon" size="19" /></span></template>
                    <template v-if="!item.ready" #append><span class="tt-sidebar-soon">{{ $t("Soon") }}</span></template>
                </v-list-item>
            </template>
        </v-list>

        <template #append>
            <div class="tt-sidebar-foot">
                <a href="/" target="_blank" rel="noopener" class="tt-sidebar-store">
                    <v-icon size="18">mdi-storefront-outline</v-icon>
                    <span>{{ $t("View store") }}</span>
                    <v-icon size="15" class="ml-auto">mdi-open-in-new</v-icon>
                </a>
                <div class="tt-sidebar-user">
                    <span class="tt-sidebar-avatar">{{ initials }}</span>
                    <span class="tt-ellipsis">
                        <b class="tt-ellipsis">{{ user?.name }}</b>
                        <small>{{ $t(user?.role === "admin" ? "Administrator" : "Staff") }}</small>
                    </span>
                </div>
            </div>
        </template>
    </v-navigation-drawer>

    <v-main class="tt-shell-main">
        <Header :show-sidebar-toggle="true" @toggle-sidebar="toggleSidebar" />
        <div class="tt-shell-content">
            <router-view />
        </div>
    </v-main>
</template>

<script>
import { useDisplay } from "vuetify";
import Header from "@/admin/Header.vue";
import menu, { findMenuEntry } from "@/admin/menu.js";
import { fileUrl } from "@/api";
import { storage } from "@/store/storage";

const RAIL_KEY = "tt_sidebar_rail";

export default {
    name: "AdminLayout",
    components: { Header },
    setup() {
        const { mdAndDown } = useDisplay();
        return { mobile: mdAndDown };
    },
    data() {
        return {
            drawer: true,
            rail: storage.get(RAIL_KEY, false),
            opened: [],
        };
    },
    computed: {
        user() {
            return this.$store.state.auth.user;
        },
        initials() {
            return (this.user?.name || "A").split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase();
        },
        // Staff do not see admin-only pages; a section label is dropped when nothing under it is visible.
        menu() {
            const isAdmin = this.$store.getters["auth/isAdmin"];
            const can = this.$store.getters["auth/canAccess"];
            const visible = (i) => (!i.admin || isAdmin) && (!i.area || can(i.area));
            const items = menu
                .filter((i) => i.section || visible(i))
                .map((i) => (i.children ? { ...i, children: i.children.filter(visible) } : i))
                .filter((i) => !i.children || i.children.length);
            return items.filter((i, idx) => !i.section || (items[idx + 1] && !items[idx + 1].section));
        },
        storeName() {
            return this.$store.state.prefs.settings["store.name"] || "Tanjil Traders";
        },
        logo() {
            const s = this.$store.state.prefs.settings;
            const path = this.$store.getters["prefs/isDark"] ? s["store.logo_dark"] || s["store.logo"] : s["store.logo"];
            return path ? fileUrl(path) : "";
        },
    },
    watch: {
        mobile: {
            immediate: true,
            handler(isMobile) {
                this.drawer = !isMobile;
            },
        },
        // Open the group that holds the current page.
        "$route.path": {
            immediate: true,
            handler(path) {
                const entry = findMenuEntry(path, menu);
                if (entry?.group && !this.opened.includes(entry.group.title)) this.opened = [entry.group.title];
                if (this.mobile) this.drawer = false;
            },
        },
    },
    methods: {
        isCurrentGroup(group) {
            return findMenuEntry(this.$route.path, menu)?.group?.title === group.title;
        },
        toggleSidebar() {
            if (this.mobile) {
                this.drawer = !this.drawer;
            } else {
                this.rail = !this.rail;
                storage.set(RAIL_KEY, this.rail);
            }
        },
    },
};
</script>
