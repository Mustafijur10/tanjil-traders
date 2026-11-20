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
                    href: "/admin",
                    title: "Dashboard",
                    icon: "fa fa-clipboard",
                },                
                {
                    title: "Catalog",
                    icon: "fa fa-th-list",
                    child: [
                        { href: "/admin/catalog", title: "All Products" },
                        { href: "/admin/product", title: "Add New Product" },
                        { href: "/admin/category", title: "Categories" },
                        { href: "/admin/brand", title: "Brands" },
                        { href: "/admin/attributes", title: "Attributes & Specifications" },
                        { href: "/admin/variants", title: "Product Variants" },
                        { href: "/admin/reviews", title: "Product Reviews" },
                        { href: "/admin/product-collections", title: "Product Collections" },
                        { href: "/admin/product-bundles", title: "Product Bundles" },
                        { href: "/admin/frequently-bought-together", title: "Frequently Bought Together" },
                        { href: "/admin/price-history", title: "Price History" },

                        { href: "/admin/product-recommendations", title: "Product Recommendations" },
                        { href: "/admin/product-comparison", title: "Product Comparison Settings" },
                        { href: "/admin/product-qa", title: "Product Q&A" },
                        { href: "/admin/product-tags", title: "Product Tags" },
                        { href: "/admin/product-import-export", title: "Product Import / Export" },
                        { href: "/admin/catalog/search-configuration", title: "Search Configuration" },
                    ],
                },
                {
                    title: "Orders",
                    icon: "fa fa-shopping-cart",
                    child: [
                        { href: "/admin/orders", title: "All Orders" },
                        { href: "/admin/create-order", title: "Create Order" },
                        { href: "/admin/pre-orders", title: "Pre-Orders" },
                        { href: "/admin/pending", title: "Pending" },
                        { href: "/admin/confirmed", title: "Confirmed" },
                        { href: "/admin/orders/processing", title: "Processing" },
                        { href: "/admin/orders/Shipped", title: "Shipped" },
                        { href: "/admin/orders/Delivered", title: "Delivered" },
                        { href: "/admin/orders/cancelled", title: "Cancelled" },
                        { href: "/admin/orders/returns", title: "Returns & Refunds" },

                        { href: "/admin/orders/on-hold", title: "On-Hold Orders" },
                        { href: "/admin/orders/order-verification", title: "Order Verification" },
                        { href: "/admin/orders/order-history", title: "Order Notes & History" },
                    ],
                },
                {
                    title: "Fulfillment & Delivery",
                    icon: "fa fa-truck",
                    child: [
                        { href: "/admin/fulfillment", title: "Fulfillment Overview" },
                        { href: "/admin/fulfillment/warehouse", title: "Warehouse Fulfillment" },
                        { href: "/admin/fulfillment/supplier", title: "Supplier Fulfillment" },
                        { href: "/admin/fulfillment/ready-to-pack", title: "Ready to Pack" },
                        { href: "/admin/fulfillment/ready-to-dispatch", title: "Ready to Dispatch" },
                        { href: "/admin/fulfillment/shipments", title: "Shipments" },
                        { href: "/admin/fulfillment/tracking", title: "Delivery Tracking" },
                        { href: "/admin/fulfillment/failed-deliveries", title: "Failed Deliveries" },
                        { href: "/admin/fulfillment/delivery-exceptions", title: "Delivery Exceptions" },
                        { href: "/admin/fulfillment/shipping-labels", title: "Shipping Labels" },
                        { href: "/admin/fulfillment/couriers", title: "Courier Management" },
                    ],
                },
                {
                    title: "Inventory",
                    icon: "fa fa-cubes",
                    child: [
                        { href: "/admin/inventory/stock-overview", title: "Stock Overview" },
                        { href: "/admin/inventory/stock-movements", title: "Stock Movements" },
                        { href: "/admin/inventory/low-stock", title: "Low Stock Alerts" },
                        { href: "/admin/inventory/warehouses", title: "Warehouses" },
                        { href: "/admin/inventory/warehouses", title: "Warehouses" },
                        { href: "/admin/inventory/stock-adjustments", title: "Stock Adjustments" },
                        { href: "/admin/inventory/serial-numbers", title: "Serial Numbers" },
                        { href: "/admin/inventory/stock-transfers", title: "Stock Transfers" },
                        { href: "/admin/inventory/back-in-stock Alerts", title: "Back in Stock Alerts" },

                        { href: "/admin/inventory/out-of-stock", title: "Out of Stock Products" },
                        { href: "/admin/inventory/warehouse-locations", title: "Warehouse Locations" },
                        { href: "/admin/inventory/stock-reservations", title: "Stock Reservations" },
                        { href: "/admin/inventory/damaged-stock", title: "Damaged & Defective Stock" },
                        { href: "/admin/inventory/valuation", title: "Inventory Valuation" },
                        { href: "/admin/inventory/forecasting", title: "Stock Forecasting" },
                    ],
                },
                {
                    title: "Purchasing",
                    icon: "fa fa-cubes",
                    child: [
                        { href: "/admin/purchasing/suppliers", title: "Suppliers" },
                        { href: "/admin/purchasing/purchase-orders", title: "Purchase Orders" },
                        { href: "/admin/purchasing/goods-receiving", title: "Goods Receiving" },
                        { href: "/admin/purchasing/import-costing", title: "Import Costing" },
                        { href: "/admin/purchasing/supplier-payments", title: "Supplier Payments" },   
                        
                        { href: "/admin/purchasing/supplier-products", title: "Supplier Products" },
                        { href: "/admin/purchasing/price-lists", title: "Supplier Price Lists" },
                        { href: "/admin/purchasing/create-purchase-order", title: "Create Purchase Order" },
                        { href: "/admin/purchasing/pending-purchase-orders", title: "Pending Purchase Orders" },
                        { href: "/admin/purchasing/supplier-returns", title: "Supplier Returns" },
                        { href: "/admin/purchasing/history", title: "Purchase History" },
                        { href: "/admin/purchasing/supplier-performance", title: "Supplier Performance" },
                    ],
                },
                {
                    title: "Customers",
                    icon: "fa fa-users",
                    child: [
                        { href: "/admin/customers", title: "All Customers" },
                        { href: "/admin/customers-groups", title: "Customer Groups" },
                        { href: "/admin/customers-details", title: "Customer Details" },
                        { href: "/admin/reviews", title: "Reviews & Ratings" },
                        { href: "/admin/whishlist-activity", title: "Wishlist Activity" },
                        { href: "/admin/recently-viewed", title: "Recently Viewed" },
                        { href: "/admin/loyalty-points", title: "Loyalty Points" },

                        { href: "/admin/customers/activity", title: "Customer Activity" },
                        { href: "/admin/customers/segments", title: "Customer Segments" },
                        { href: "/admin/customers/support-history", title: "Customer Support History" },
                        { href: "/admin/customers/questions", title: "Customer Questions" },
                        { href: "/admin/customers/comparison-activity", title: "Product Comparison Activity" },
                    ],
                }, 
                {
                    title: "Marketing",
                    icon: "fa fa-bullhorn",
                    child: [
                        { href: "/admin/marketing/campaigns", title: "Campaigns" },
                        { href: "/admin/marketing/discount", title: "Coupons & Discounts" },
                        { href: "/admin/marketing/promotions", title: "Promotions" },
                        { href: "/admin/marketing/banners", title: "Banners & Promotions" },
                        { href: "/admin/marketing/content-calendar", title: "Content Calendar" },
                        { href: "/admin/marketing/email-sms", title: "Email & SMS" },
                        { href: "/admin/marketing/audiences", title: "Audiences" },
                        { href: "/admin/marketing/automations", title: "Automations" },
                        { href: "/admin/marketing/flash-sales", title: "Flash Sales" },
                        { href: "/admin/marketing/gift-cards", title: "Gift Cards" },

                        { href: "/admin/marketing/abandoned-cart", title: "Abandoned Cart Campaigns" },
                        { href: "/admin/marketing/referrals", title: "Referral Program" },
                        { href: "/admin/marketing/free-shipping", title: "Free Shipping Campaigns" },
                        { href: "/admin/marketing/product-offers", title: "Product Offers" },
                    ],
                }, 
                {
                    title: "Communications",
                    icon: "fa fa-comments",
                    child: [
                        { href: "/admin/inbox", title: "Inbox", icon: "fa fa-inbox", badge: { text: "12" } },
                        { href: "/admin/publish",          title: "Contact Messages",     icon: "fa fa-edit" },
                        { href: "/admin/notifications",        title: "Notifications",       icon: "fa fa-address-book" },
                        { href: "/admin/message-templates",title: "MessageTemplates",       icon: "fa fa-file-alt" },    
                        
                        { href: "/admin/communications/email", title: "Email Center" },
                        { href: "/admin/communications/sms", title: "SMS Center" },
                        { href: "/admin/communications/channels", title: "Communication Channels" },
                        { href: "/admin/communications/workflows", title: "Automation Workflows" },
                        { href: "/admin/communications/scheduled", title: "Scheduled Messages" },
                        { href: "/admin/communications/delivery-logs", title: "Message Delivery Logs" },
                        { href: "/admin/communications/reports", title: "Communication Reports" },
                    ],
                },   
                { 
                    title: "After-Sales & Support", icon: "fa fa-shield-alt", 
                        child: [
                        { href: "/admin/warranty", title: "Warranty Registry" },
                        { href: "/admin/warranty-claims", title: "Warranty Claims" },
                        { href: "/admin/service-tickets", title: "Service Tickets" },
                        { href: "/admin/returns-and-refunds", title: "Returns & Refunds" },
                        { href: "/admin/trade-in", title: "Trade-in / Exchange" },

                        { href: "/admin/after-sales/overview", title: "After-Sales Overview" },
                        { href: "/admin/after-sales/warranty-policies", title: "Warranty Policies" },
                        { href: "/admin/after-sales/repair-tracking", title: "Repair Status Tracking" },
                        { href: "/admin/after-sales/replacements", title: "Product Replacements" },
                        { href: "/admin/after-sales/service-centers", title: "Service Centers" },
                        { href: "/admin/after-sales/reports", title: "After-Sales Reports" },
                    ]
                },                                                         
                { 
                    title: "Finance", icon: "fa fa-shield-alt", 
                        child: [
                        { href: "/admin/transactions", title: "Transactions" },
                        { href: "/admin/payment-management", title: "Payment Management" },
                        { href: "/admin/cod-settlements", title: "COD Settlements" },
                        { href: "/admin/Expenses", title: "Expenses" },
                        { href: "/admin/supplier-payments", title: "Supplier Payments" },
                        { href: "/admin/profit-loss", title: "Profit & Loss" },
                        { href: "/admin/profit-loss", title: "Profit & Loss" },
                        { href: "/admin/financial-reconciliation", title: "Financial Reconciliation" },

                        { href: "/admin/finance/receivables", title: "Customer Receivables" },
                        { href: "/admin/finance/revenue", title: "Revenue" },
                        { href: "/admin/finance/expense-categories", title: "Expense Categories" },
                        { href: "/admin/finance/accounts", title: "Chart of Accounts" },
                        { href: "/admin/finance/journal-entries", title: "Journal Entries" },
                        { href: "/admin/finance/balance-sheet", title: "Balance Sheet" },
                        { href: "/admin/finance/cash-flow", title: "Cash Flow" },
                    ]
                },                                                         
                {
                    title: "Website & CMS",
                    icon: "fa fa-file-alt",
                    child: [
                        { href: "/admin/pages", title: "Pages" },
                        { href: "/admin/pages/new", title: "Add New Page" },
                        { href: "/admin/pages/menus-and-navigation", title: "Menus & Navigation" },
                        { href: "/admin/pages/homepage-builder", title: "Homepage Builder" },
                        { href: "/admin/pages/landing-pages", title: "Landing Pages" },
                        { href: "/admin/pages/blog-articles", title: "Blog Articles" },
                        { href: "/admin/pages/seo-management", title: "SEO Management" },
                        { href: "/admin/pages/media-library", title: "Media Library" },

                        { href: "/admin/pages/revisions", title: "Page Revisions" },
                        { href: "/admin/pages/banners", title: "Banners & Sliders" },
                        { href: "/admin/pages/homepage-sections", title: "Homepage Sections" },
                        { href: "/admin/pages/footer-builder", title: "Footer Builder" },
                        { href: "/admin/pages/blog-categories", title: "Blog Categories" },
                        { href: "/admin/pages/redirects", title: "Redirect Management" },
                    ],
                },      
                {
                    title: "Analytics & Reports",
                    icon: "fa fa-line-chart",
                    child: [
                        { href: "/admin/business-overview", title: "Business Overview" },
                        { href: "/admin/sales-analytics", title: "Sales Analytics" },
                        { href: "/admin/seo-analytics", title: "Seo Analytics" },     
                        { href: "/admin/product-performance", title: "Product Performance" },                             
                        { href: "/admin/inventory-report", title: "Inventory Report" },
                        { href: "/admin/customer-report", title: "Customer Report" },     
                        { href: "/admin/marketing-reports", title: "Marketing Reports" },     
                        { href: "/admin/sales-report", title: "Sales Report" },              
                        { href: "/admin/financial-reports", title: "Financial Reports" },       
                        
                        
                        { href: "/admin/analytics/conversion", title: "Conversion Analytics" },
                        { href: "/admin/analytics/traffic", title: "Traffic Analytics" },
                        { href: "/admin/analytics/customer", title: "Customer Analytics" },
                        { href: "/admin/analytics/fulfillment", title: "Fulfillment Analytics" },
                        { href: "/admin/analytics/purchase", title: "Purchase Analytics" },
                        { href: "/admin/analytics/inventory", title: "Inventory Analytics" },
                        { href: "/admin/analytics/marketing", title: "Marketing Analytics" },
                        { href: "/admin/reports/order-report", title: "Order Report" },
                        { href: "/admin/reports/purchase-report", title: "Purchase Report" },
                        { href: "/admin/reports/supplier-report", title: "Supplier Report" },
                        { href: "/admin/reports/payment-report", title: "Payment Report" },
                        { href: "/admin/reports/shipping-report", title: "Shipping Report" },
                        { href: "/admin/reports/returns-report", title: "Returns & Refunds Report" },
                        { href: "/admin/reports/tax-report", title: "Tax Report" },
                        { href: "/admin/reports/custom", title: "Custom Reports" },
                    ],
                },                        
                {
                    title: "Settings",
                    icon: "fa fa-cog",
                    child: [
                        { href: "/admin/general-settings", title: "General" },
                        { href: "/admin/storefront", title: "Store Information" },
                        { href: "/admin/settings-payment", title: "Payment Methods" },
                        { href: "/admin/shipping", title: "Shipping & Delivery" },
                        { href: "/admin/tax-configuration", title: "Tax Configuration" },
                        { href: "/admin/roles", title: "Staff & Roles" },
                        { href: "/admin/notification-settings", title: "Notification Settings" },
                        { href: "/admin/integrations-api", title: "Integrations & API" },
                        { href: "/admin/security-audit-logs", title: "Security & Audit Logs" },
                        { href: "/admin/roles", title: "Staff & Roles" },

                        { href: "/admin/settings/currency-localization", title: "Currency & Localization" },
                        { href: "/admin/settings/shipping-zones", title: "Shipping Zones" },
                        { href: "/admin/settings/shipping-methods", title: "Shipping Methods" },
                        { href: "/admin/settings/shipping-rules", title: "Dynamic Shipping Rules" },
                        { href: "/admin/settings/courier-integrations", title: "Courier Integrations" },
                        { href: "/admin/settings/email-sms", title: "Email & SMS Configuration" },
                        { href: "/admin/settings/permissions", title: "Roles & Permissions" },
                        { href: "/admin/settings/webhooks", title: "Webhooks" },
                        { href: "/admin/settings/system", title: "System Configuration" },
                        { href: "/admin/settings/cache", title: "Cache Management" },
                        { href: "/admin/settings/backup", title: "Backup & Recovery" },
                        { href: "/admin/settings/analytics", title: "Analytics & Tracking" },
                        { href: "/admin/settings/seo", title: "SEO Settings" },
                        { href: "/admin/settings/legal", title: "Legal & Compliance" },
                        { href: "/admin/settings/notifications", title: "Notification Templates" },
                        { href: "/admin/settings/maintenance", title: "Maintenance Mode" },
                        { href: "/admin/settings/branding", title: "Branding & Customization" },
                        { href: "/admin/settings/third-party", title: "Third-Party Integrations" },
                        { href: "/admin/settings/advanced", title: "Advanced Settings" },   
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
