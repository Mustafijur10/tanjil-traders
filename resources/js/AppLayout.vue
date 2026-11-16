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
                        { href: "/admin/seo-analytics", title: "Seo Analytics" },                        
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
                    title: "Message Center",
                    icon: "fa fa-comments",
                    child: [
                        { href: "/admin/inbox",            title: "Inbox",           icon: "fa fa-inbox",       badge: { text: "12" } },
                        { href: "/admin/publish",          title: "Create Post",     icon: "fa fa-edit" },
                        { href: "/admin/content-calendar", title: "Content Calendar",icon: "fa fa-calendar-alt" },
                        { href: "/admin/campaigns",        title: "Email & SMS",     icon: "fa fa-envelope" },
                        { href: "/admin/audiences",        title: "Audiences",       icon: "fa fa-address-book" },
                        { href: "/admin/message-templates",title: "Templates",       icon: "fa fa-file-alt" },
                        { href: "/admin/automations",      title: "Automations",     icon: "fa fa-bolt" },
                        { href: "/admin/channels",         title: "Channels",        icon: "fa fa-plug" },
                        { href: "/admin/channel-reports",  title: "Performance",     icon: "fa fa-chart-line" },
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
                        { href: "/admin/discount", title: "Coupons & Discounts" },
                        { href: "/admin/banners", title: "Banners & Promotions" },
                        { href: "/admin/notifications", title: "Notifications" },
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
                    title: "Pages",
                    icon: "fa fa-file-alt",
                    child: [
                        { href: "/admin/pages", title: "All Pages" },
                        { href: "/admin/pages/new", title: "Add New Page" },
                        { href: "/admin/pages?status=draft", title: "Draft Pages" },
                        { href: "/admin/pages?status=published", title: "Published Pages" },
                    ],
                },
                { title: "After-Sales", icon: "fa fa-shield-alt", child: [
                    { href: "/admin/warranty", title: "Warranty Registry" },
                    { href: "/admin/warranty-claims", title: "Warranty Claims" },
                    { href: "/admin/service-tickets", title: "Repair & Service" },
                    { href: "/admin/trade-in", title: "Trade-in / Exchange" },
                ]},
                { title: "Purchasing", icon: "fa fa-truck-loading", child: [
                    { href: "/admin/purchase-orders", title: "Purchase Orders" },
                    { href: "/admin/import-costing", title: "Import Costing" },
                    { href: "/admin/stock-transfers", title: "Stock Transfers" },
                ]},
                { title: "Finance", icon: "fa fa-money-bill-wave", child: [
                    { href: "/admin/settlements", title: "Payments & COD Settlements" },
                    { href: "/admin/expenses", title: "Expenses" },
                    { href: "/admin/profit-loss", title: "Profit & Loss" },
                ]},
                {
                    title: "Settings",
                    icon: "fa fa-cog",
                    child: [
                        { href: "/admin/general-settings", title: "General" },
                        { href: "/admin/storefront", title: "Storefront" },
                        { href: "/admin/settings-payment", title: "Payment Methods" },
                        { href: "/admin/shipping", title: "Shipping Zones" },
                        { href: "/admin/roles", title: "Staff & Roles" },
                    ],
                },   
                {
                    title: "Profile",
                    icon: "fa fa-cog",
                    child: [
                        { href: "/admin/profile", title: "Page" },
                        { href: "/admin/profile-edit", title: "Settings" },                        
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
