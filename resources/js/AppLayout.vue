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
                // {
                //     title: "Create New",
                //     icon: "fa fa-edit",
                //     child: [
                //         {
                //             href: "/admin/order",
                //             title: "Order",
                //         },
                        
                //     ],
                // },
                {
                    href: "/admin",
                    title: "Dashboard",
                    icon: "fa fa-clipboard",
                },
                {
                    title: "Analytics",
                    icon: "fa fa-th-list",
                    child: [
                        { href: "/admin/sales-analytics", title: "Sales Analytics" },                        
                    ],
                },
                {
                    title: "Catalog",
                    icon: "fa fa-th-list",
                    child: [
                        { href: "/admin/catalog", title: "All Products" },
                        { href: "/admin/category", title: "Categories" },
                        { href: "/admin/brand", title: "Brands" },
                    ],
                },
                {
                    title: "Orders",
                    icon: "fa fa-shopping-cart",
                    child: [
                        { href: "/admin/orders", title: "All Orders" },
                        { href: "/admin/create-order", title: "Create Order" },
                        { href: "/admin/pending", title: "Pending" },
                        { href: "/admin/orders/processing", title: "Processing" },
                        { href: "/admin/orders/cancelled", title: "Cancelled" },
                        { href: "/admin/orders/returns", title: "Returns & Refunds" },
                    ],
                },
                {
                    title: "Customers",
                    icon: "fa fa-users",
                    child: [
                        { href: "/admin/customers", title: "All Customers" },
                        { href: "/admin/customers/groups", title: "Customer Groups" },
                        { href: "/admin/reviews", title: "Reviews & Ratings" },
                    ],
                },
                {
                    title: "Inventory",
                    icon: "fa fa-cubes",
                    child: [
                        { href: "/admin/inventory", title: "Stock Levels" },
                        { href: "/admin/inventory/low-stock", title: "Low Stock" },
                        { href: "/admin/inventory/suppliers", title: "Suppliers" },
                    ],
                },
                {
                    title: "Marketing",
                    icon: "fa fa-bullhorn",
                    child: [
                        { href: "/admin/coupons", title: "Coupons & Discounts" },
                        { href: "/admin/banners", title: "Banners & Promotions" },
                        { href: "/admin/notifications", title: "Notifications" },
                    ],
                },
                {
                    title: "Shipping",
                    icon: "fa fa-truck",
                    child: [
                        { href: "/admin/shipping", title: "Shipping Zones" },
                        { href: "/admin/shipping/methods", title: "Shipping Methods" },
                    ],
                },
                {
                    title: "Reports",
                    icon: "fa fa-line-chart",
                    child: [
                        { href: "/admin/sales-report", title: "Sales Report" },
                        { href: "/admin/inventory-report", title: "Inventory Report" },
                        { href: "/admin/customer-report", title: "Customer Report" },
                    ],
                },
                {
                    title: "Settings",
                    icon: "fa fa-cog",
                    child: [
                        { href: "/admin/general-settings", title: "General" },
                        { href: "/admin/storefront", title: "Storefront" },
                        { href: "/admin/settings/payment", title: "Payment Methods" },
                        { href: "/admin/roles", title: "Staff & Roles" },
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
</style>
