/*
 * Admin sidebar, in labelled sections.
 *   { section: "Sales" }                         section label
 *   { title, icon, to }                          single link
 *   { title, icon, children: [{ title, to }] }   group
 * `ready: false` shows the item greyed out with "Soon" until its page is built.
 * `admin: true` hides the item from staff accounts.
 * Page headers take their icon and breadcrumb from here, so keep titles in sync with the pages.
 */
export default [
    { section: "Overview" },
    { title: "Dashboard", icon: "mdi-view-dashboard-outline", to: "/admin", exact: true, ready: true },

    { section: "Store" },
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
            { title: "Product Q&A", to: "/admin/questions", ready: false },
            { title: "Import / Export", to: "/admin/products/import", ready: false },
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

    { section: "Sales" },
    {
        title: "Orders",
        icon: "mdi-receipt-text-outline",
        children: [
            { title: "All Orders", to: "/admin/orders", exact: true, ready: false },
            { title: "Create Order", to: "/admin/orders/new", ready: false },
            { title: "Returns & Refunds", to: "/admin/returns", ready: false },
            { title: "Couriers", to: "/admin/couriers", ready: false },
        ],
    },
    { title: "Customers", icon: "mdi-account-group-outline", to: "/admin/customers", ready: false },
    {
        title: "Promotions",
        icon: "mdi-ticket-percent-outline",
        children: [
            { title: "Coupons", to: "/admin/coupons", ready: false },
            { title: "Flash Sales", to: "/admin/flash-sales", ready: false },
        ],
    },

    { section: "Operations" },
    {
        title: "Purchasing",
        icon: "mdi-truck-delivery-outline",
        children: [
            { title: "Suppliers", to: "/admin/suppliers", ready: false },
            { title: "Purchase Orders", to: "/admin/purchases", ready: false },
        ],
    },
    { title: "Warranty & Service", icon: "mdi-shield-check-outline", to: "/admin/warranty", ready: false },
    {
        title: "Finance",
        icon: "mdi-cash-multiple",
        admin: true,
        children: [
            { title: "Expenses", to: "/admin/expenses", ready: false },
            { title: "Profit & Loss", to: "/admin/profit-loss", ready: false },
        ],
    },

    { section: "Website" },
    {
        title: "Content",
        icon: "mdi-web",
        children: [
            { title: "Carousel & Banners", to: "/admin/banners", ready: false },
            { title: "Announcement Bar", to: "/admin/announcements", ready: false },
            { title: "Homepage Sections", to: "/admin/home-sections", ready: false },
            { title: "Pages", to: "/admin/pages", ready: false },
            { title: "Contact Messages", to: "/admin/messages", ready: false },
        ],
    },

    { section: "Insights" },
    {
        title: "Analytics",
        icon: "mdi-chart-box-outline",
        children: [
            { title: "Sales", to: "/admin/analytics/sales", ready: false },
            { title: "Products", to: "/admin/analytics/products", ready: false },
            { title: "Customers", to: "/admin/analytics/customers", ready: false },
            { title: "Inventory", to: "/admin/analytics/inventory", ready: false },
            { title: "Traffic", to: "/admin/analytics/traffic", ready: false },
            { title: "Search & Wishlist", to: "/admin/analytics/search", ready: false },
            { title: "SEO", to: "/admin/analytics/seo", ready: false },
            { title: "Live Performance", to: "/admin/analytics/live", ready: false },
        ],
    },

    { section: "System" },
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
            { title: "SMS & Notifications", to: "/admin/settings/sms", ready: false, admin: true },
            { title: "Integrations & SEO", to: "/admin/settings/integrations", ready: true, admin: true },
            { title: "Staff & Roles", to: "/admin/settings/staff", ready: false, admin: true },
            { title: "My Profile", to: "/admin/profile", ready: false },
        ],
    },
];

/** The menu entry for a path: { group, item } — used for page header icons and breadcrumbs. */
export function findMenuEntry(path, list) {
    const all = list
        .filter((e) => !e.section)
        .flatMap((e) => (e.children ? e.children.map((c) => ({ group: e, item: c })) : [{ group: null, item: e }]));

    const longest = (matches) => matches.sort((a, b) => b.item.to.length - a.item.to.length)[0] || null;
    const exact = longest(all.filter((c) => (c.item.exact ? path === c.item.to : path === c.item.to || path.startsWith(c.item.to + "/"))));
    // Pages not in the menu (e.g. /admin/products/12) fall back to their nearest parent ("All Products").
    return exact || longest(all.filter((c) => c.item.to !== "/admin" && path.startsWith(c.item.to + "/")));
}
