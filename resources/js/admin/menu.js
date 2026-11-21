/*
 * Admin sidebar. `ready: false` shows the item greyed out with a "Soon" label until its page is built.
 * `admin: true` hides the item from staff accounts.
 */
export default [
    { title: "Dashboard", icon: "mdi-view-dashboard-outline", to: "/admin", exact: true, ready: true },
    {
        title: "Catalog",
        icon: "mdi-package-variant-closed",
        children: [
            { title: "All Products", to: "/admin/products", exact: true, ready: true },
            { title: "Add Product", to: "/admin/products/new", ready: true },
            { title: "Categories", to: "/admin/categories", ready: true },
            { title: "Brands", to: "/admin/brands", ready: true },
            { title: "Attributes & Specs", to: "/admin/attributes", ready: true },
            { title: "Reviews", to: "/admin/reviews", ready: true },
        ],
    },
    {
        title: "Orders",
        icon: "mdi-receipt-text-outline",
        children: [
            { title: "All Orders", to: "/admin/orders", ready: false },
            { title: "Create Order", to: "/admin/orders/new", ready: false },
        ],
    },
    {
        title: "Inventory",
        icon: "mdi-warehouse",
        children: [
            { title: "Stock Overview", to: "/admin/inventory", exact: true, ready: false },
            { title: "Stock Movements", to: "/admin/inventory/movements", ready: false },
        ],
    },
    { title: "Customers", icon: "mdi-account-group-outline", to: "/admin/customers", ready: false },
    { title: "Coupons", icon: "mdi-ticket-percent-outline", to: "/admin/coupons", ready: false },
    {
        title: "Website",
        icon: "mdi-web",
        children: [
            { title: "Carousel & Banners", to: "/admin/banners", ready: false },
            { title: "Announcement Bar", to: "/admin/announcements", ready: false },
            { title: "Homepage Sections", to: "/admin/home-sections", ready: false },
            { title: "Pages", to: "/admin/pages", ready: false },
            { title: "Contact Messages", to: "/admin/messages", ready: false },
        ],
    },
    {
        title: "Analytics",
        icon: "mdi-chart-box-outline",
        children: [
            { title: "Sales", to: "/admin/analytics/sales", ready: false },
            { title: "Products", to: "/admin/analytics/products", ready: false },
            { title: "Customers", to: "/admin/analytics/customers", ready: false },
            { title: "Inventory", to: "/admin/analytics/inventory", ready: false },
            { title: "Traffic", to: "/admin/analytics/traffic", ready: false },
            { title: "SEO", to: "/admin/analytics/seo", ready: false },
            { title: "Live Performance", to: "/admin/analytics/live", ready: false },
        ],
    },
    {
        title: "Settings",
        icon: "mdi-cog-outline",
        children: [
            { title: "Store Information", to: "/admin/settings/store", ready: true, admin: true },
            { title: "Appearance & Language", to: "/admin/settings/appearance", ready: true, admin: true },
            { title: "Language & Translations", to: "/admin/settings/translations", ready: true },
            { title: "Header & Display", to: "/admin/settings/header", ready: false },
            { title: "Shipping", to: "/admin/settings/shipping", ready: false, admin: true },
            { title: "Payments", to: "/admin/settings/payments", ready: true, admin: true },
            { title: "Integrations & SEO", to: "/admin/settings/integrations", ready: true, admin: true },
            { title: "Staff & Users", to: "/admin/settings/staff", ready: false, admin: true },
            { title: "My Profile", to: "/admin/profile", ready: false },
        ],
    },
];
