<template>
    <v-navigation-drawer
        v-model="drawer"
        class="tt-sidebar"
        :rail="rail && !mobile"
        :temporary="mobile"
        :permanent="!mobile"
        width="264"
        rail-width="72"
        :expand-on-hover="rail && !mobile"
    >
        <router-link to="/admin" class="tt-sidebar-brand">
            <img v-if="logo" :src="logo" :alt="storeName" />
            <template v-else>
                <span class="tt-sidebar-mark">{{ storeName.charAt(0) }}</span>
                <span class="tt-sidebar-name">{{ storeName }}<small>{{ $t("Admin panel") }}</small></span>
            </template>
        </router-link>

        <v-list v-model:opened="opened" nav density="compact" color="primary">
            <template v-for="item in menu" :key="item.title">
                <v-list-group v-if="item.children" :value="item.title">
                    <template #activator="{ props }">
                        <v-list-item v-bind="props" :prepend-icon="item.icon" :title="$t(item.title)" />
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

                <v-list-item
                    v-else
                    :to="item.ready ? item.to : undefined"
                    :exact="item.exact"
                    :disabled="!item.ready"
                    :prepend-icon="item.icon"
                    :title="$t(item.title)"
                >
                    <template v-if="!item.ready" #append><span class="tt-sidebar-soon">{{ $t("Soon") }}</span></template>
                </v-list-item>
            </template>
        </v-list>
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
import menu from "@/admin/menu.js";
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
        isAdmin() {
            return this.$store.getters["auth/isAdmin"];
        },
        // Staff do not see admin-only pages.
        menu() {
            const visible = (i) => !i.admin || this.isAdmin;
            return menu
                .filter(visible)
                .map((i) => (i.children ? { ...i, children: i.children.filter(visible) } : i))
                .filter((i) => !i.children || i.children.length);
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
                const group = menu.find((i) => i.children && i.children.some((c) => path.startsWith(c.to)));
                if (group && !this.opened.includes(group.title)) this.opened = [group.title];
                if (this.mobile) this.drawer = false;
            },
        },
    },
    methods: {
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
