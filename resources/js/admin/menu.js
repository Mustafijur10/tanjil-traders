/*
 * Admin sidebar, in labelled sections.
 *   { section: "Sales" }                         section label
 *   { title, icon, to }                          single link
 *   { title, icon, children: [{ title, to }] }   group
 * `ready: false` shows the item greyed out with "Soon" until its page is built.
 * `admin: true` hides the item from staff accounts.
 * `area` (on a group or item) hides it from staff whose account does not allow that area (Settings › Staff & Roles).
 * Page headers take their icon and breadcrumb from here, so keep titles in sync with the pages.
 */
export default [
    { section: "Overview" },
    { title: "Dashboard", icon: "mdi-view-dashboard-outline", to: "/admin", exact: true, ready: true },

    { section: "Store" },
    {
        title: "Catalog",
        area: "catalog",
        icon: "mdi-package-variant-closed",
        children: [
            { title: "All Products", to: "/admin/products", exact: true, ready: true },
            { title: "Add Product", to: "/admin/products/new", ready: true },
            { title: "Categories", to: "/admin/categories", ready: true },
            { title: "Brands", to: "/admin/brands", ready: true },
            { title: "Attributes & Specs", to: "/admin/attributes", ready: true },
            { title: "Reviews", to: "/admin/reviews", ready: true },
            { title: "Product Q&A", to: "/admin/questions", ready: false },
            { title: "Import / Export", to: "/admin/products/import", ready: true },
        ],
    },
    {
        title: "Inventory",
        area: "inventory",
        icon: "mdi-warehouse",
        children: [
            { title: "Stock Overview", to: "/admin/inventory", exact: true, ready: true },
            { title: "Stock Movements", to: "/admin/inventory/movements", ready: true },
        ],
    },

    { section: "Sales" },
    {
        title: "Orders",
        area: "orders",
        icon: "mdi-receipt-text-outline",
        children: [
            { title: "All Orders", to: "/admin/orders", exact: true, ready: true },
            { title: "Create Order", to: "/admin/orders/new", ready: true },
            { title: "Returns & Refunds", to: "/admin/returns", ready: true },
            { title: "Couriers", to: "/admin/couriers", ready: true },
        ],
    },
    { title: "Customers", icon: "mdi-account-group-outline", to: "/admin/customers", area: "customers", ready: true },
    {
        title: "Promotions",
        area: "promotions",
        icon: "mdi-ticket-percent-outline",
        children: [
            { title: "Coupons", to: "/admin/coupons", ready: true },
            { title: "Flash Sales", to: "/admin/flash-sales", ready: true },
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
        area: "content",
        icon: "mdi-web",
        children: [
            { title: "Carousel & Banners", to: "/admin/banners", ready: true },
            { title: "Announcement Bar", to: "/admin/announcements", ready: true },
            { title: "Homepage Sections", to: "/admin/home-sections", ready: true },
            { title: "Pages", to: "/admin/pages", ready: true },
            { title: "Contact Messages", to: "/admin/messages", ready: true },
        ],
    },

    { section: "Insights" },
    {
        title: "Analytics",
        area: "analytics",
        icon: "mdi-chart-box-outline",
        children: [
            { title: "Sales", to: "/admin/analytics/sales", ready: true },
            { title: "Products", to: "/admin/analytics/products", ready: true },
            { title: "Customers", to: "/admin/analytics/customers", ready: true },
            { title: "Inventory", to: "/admin/analytics/inventory", ready: true },
            { title: "Traffic", to: "/admin/analytics/traffic", ready: true },
            { title: "Search & Wishlist", to: "/admin/analytics/search", ready: true },
            { title: "SEO", to: "/admin/analytics/seo", ready: true },
            { title: "Live Performance", to: "/admin/analytics/live", ready: true },
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
            { title: "Header & Display", to: "/admin/settings/header", ready: true, admin: true },
            { title: "Shipping", to: "/admin/settings/shipping", ready: true, admin: true },
            { title: "Payments", to: "/admin/settings/payments", ready: true, admin: true },
            { title: "SMS & Notifications", to: "/admin/settings/sms", ready: true, admin: true },
            { title: "Integrations & SEO", to: "/admin/settings/integrations", ready: true, admin: true },
            { title: "Staff & Roles", to: "/admin/settings/staff", ready: true, admin: true },
            { title: "My Profile", to: "/admin/profile", ready: true },
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
