<template>
    <sidebar-menu
        :menu="menu"
        @update:collapsed="onToggleCollapse"
        v-model:collapsed="collapsed"
        :theme="menutheme"
        :show-one-child="true"
       
    >
        <template v-slot:header>
            <v-container class="align-self-center side-header" color="white" contain>
                <router-link to="/admin" tag="span" style="cursor: pointer; text-align: center">
                    <v-img src="/images/logo.png" max-width="150" class="mx-auto"></v-img>
                </router-link>
            </v-container>
        </template>
        
    </sidebar-menu>

    <v-app :theme="theme" id="main" :class="{ collapsed: collapsed }">
        <v-header>
            <Header></Header>
        </v-header>
        <v-main>
            <v-container fluid>
                <router-view></router-view>
            </v-container>
        </v-main>
        <v-footer> </v-footer>
    </v-app>
</template>

<script setup>
import Auth from "@/auth.js";
import Header from "@/admin/Header.vue";
import Footer from "@/admin/Footer.vue";
</script>

<script>
import { ref } from "vue";
const theme = ref("light");
export default {
    mounted() {
        theme.value = window.localStorage.getItem("theme_mode");
        if (theme.value == "light") this.menutheme = "white-theme";
        else this.menutheme = "";
    },
    data() {
        return {
            collapsed: false,
            menutheme: "white-theme",

            isOnMobile: false,

            showsidebar: false,

            menu: [
                {
                    hiddenOnCollapse: true,
                },
                {
                    title: "Create New",
                    icon: "fa fa-edit",
                    child: [
                        {
                            href: "/admin/order",
                            title: "Order",
                        },
                        
                    ],
                },
                {
                    href: "/admin",
                    title: "Dashboard",
                    icon: "fa fa-clipboard",
                },
                {
                    title: "Sales",
                    icon: "fa fa-bar-chart",
                    child: [
                        {
                            href: "/admin/sales-orders",
                            title: "Orders",
                        },
                        
                    ],
                },
                 
            ],

            loggedUser: Auth.user,
        };
    },
    methods: {
        themeSwitch() {
            theme.value = theme.value === "light" ? "dark" : "light";

            this.menutheme =
                this.menutheme === "white-theme" ? "" : "white-theme";

            window.localStorage.setItem("theme_mode", theme.value);
        },
        onToggleCollapse(collapsed) {
            this.collapsed = collapsed;
        },
        logout() {
            this.axios
                .post("/api/logout")
                .then(({ data }) => {
                    Auth.logout();
                    window.location.href = "/admin/login";
                })
                .catch((error) => {
                    console.log(error);
                });
        },
    },
};
</script>
<style scoped>
#main {
    padding-left: 290px;
    -webkit-transition: 0.3s ease;
    transition: 0.3s ease;
    background-color: rgb(245, 245, 245);
}
#main.collapsed,
#main.onmobile {
    padding-left: 65px;
}

.v-footer {
    flex: initial;
}

/* .v-sidebar-menu {
    --vsm-primary-color: #FFFFFF ;
    --vsm-item-active-color: #04A9F5 ;
    --vsm-item-hover-color: #04A9F5 ;
    --vsm-item-open-color: #04A9F5 ;
    --vsm-dropdown-bg: #FFFFFF;
    --vsm-item-hover-bg: #E4F5FD;
    --vsm-item-active-bg: #E4F5FD;
    --vsm-icon-bg: #FFFFFF;
    --vsm-icon-active-bg: #E4F5FD;
    --vsm-icon-active-color: #04A9F5;
} */
</style>
