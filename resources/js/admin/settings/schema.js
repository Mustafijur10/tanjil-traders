/*
 * Settings pages, described as panels of fields. SettingsPage.vue draws them.
 * Each `key` must exist in config/store.php.
 *
 * Field types: text, textarea, number, switch, select, image, secret, swatches
 */
export const ACCENTS = [
    { value: "green", label: "Emerald", hex: "#0f9d6b" },
    { value: "teal", label: "Teal", hex: "#0f766e" },
    { value: "blue", label: "Blue", hex: "#2f5be7" },
    { value: "violet", label: "Violet", hex: "#7c3aed" },
    { value: "rose", label: "Rose", hex: "#e11d48" },
    { value: "amber", label: "Amber", hex: "#d97706" },
];

export default {
    store: {
        title: "Store Information",
        subtitle: "Your shop's name, contact details and logo. Shown in the header, footer, invoices and search results.",
        panels: [
            {
                title: "Store details",
                fields: [
                    { key: "store.name", label: "Store name", type: "text", required: true },
                    { key: "store.tagline", label: "Tagline", type: "text", hint: "A short line under the name, e.g. Electronics, gadgets and gear." },
                    { key: "store.phone", label: "Phone", type: "text", hint: "Shown in the header and on invoices." },
                    { key: "store.email", label: "Email", type: "text" },
                    { key: "store.address", label: "Address", type: "textarea" },
                    { key: "store.hours", label: "Opening hours", type: "text", hint: "e.g. Sat–Thu, 10am–8pm" },
                    {
                        key: "store.map_location",
                        label: "Map location",
                        type: "textarea",
                        hint: "Shown on the Contact page. Paste a Google Maps link or the “Embed a map” code, or leave empty to use the address above.",
                    },
                ],
            },
            {
                title: "Logo and icon",
                fields: [
                    { key: "store.logo", label: "Logo", type: "image", wide: true, hint: "Used on light backgrounds. A transparent PNG or SVG works best." },
                    { key: "store.logo_dark", label: "Logo for dark mode", type: "image", wide: true, hint: "Optional. A light version of the logo for dark mode." },
                    { key: "store.favicon", label: "Browser tab icon", type: "image", hint: "A square image, at least 64 × 64 pixels." },
                ],
            },
            {
                title: "Social links",
                subtitle: "Full links to your pages. Empty links are hidden on the website.",
                fields: [
                    { key: "social.facebook", label: "Facebook", type: "text", icon: "mdi-facebook" },
                    { key: "social.instagram", label: "Instagram", type: "text", icon: "mdi-instagram" },
                    { key: "social.youtube", label: "YouTube", type: "text", icon: "mdi-youtube" },
                    { key: "social.tiktok", label: "TikTok", type: "text", icon: "mdi-music-note" },
                    { key: "social.whatsapp", label: "WhatsApp number", type: "text", icon: "mdi-whatsapp", hint: "With country code, e.g. 8801XXXXXXXXX" },
                ],
            },
            {
                title: "Footer",
                fields: [{ key: "store.footer_text", label: "Footer text", type: "textarea", hint: "A short paragraph about your shop." }],
            },
        ],
    },

    appearance: {
        title: "Appearance & Language",
        subtitle: "What visitors see the first time they open your website. Each visitor can still switch for themselves.",
        panels: [
            {
                title: "Theme",
                fields: [
                    {
                        key: "appearance.default_theme",
                        label: "Default theme",
                        type: "select",
                        options: [
                            { value: "light", title: "Light" },
                            { value: "dark", title: "Dark" },
                        ],
                    },
                    { key: "appearance.allow_theme_switch", label: "Let visitors switch between light and dark", type: "switch" },
                    { key: "appearance.accent", label: "Website accent colour", type: "swatches", options: ACCENTS, hint: "Used for buttons, links and highlights on the website." },
                ],
            },
            {
                title: "Language",
                fields: [
                    {
                        key: "language.default",
                        label: "Default language",
                        type: "select",
                        options: [
                            { value: "en", title: "English" },
                            { value: "bn", title: "বাংলা (Bangla)" },
                        ],
                    },
                    { key: "language.allow_switch", label: "Let visitors switch between English and Bangla", type: "switch" },
                ],
            },
        ],
    },

    payments: {
        title: "Payments",
        subtitle: "How customers can pay at checkout.",
        panels: [
            {
                title: "Payment methods",
                fields: [
                    { key: "checkout.cod_enabled", label: "Cash on delivery", type: "switch" },
                    { key: "checkout.bkash_enabled", label: "bKash (send money)", type: "switch", hint: "Customers send money to your number and enter the transaction ID at checkout." },
                    { key: "checkout.bkash_number", label: "bKash number", type: "text", showIf: "checkout.bkash_enabled" },
                    { key: "checkout.nagad_enabled", label: "Nagad (send money)", type: "switch" },
                    { key: "checkout.nagad_number", label: "Nagad number", type: "text", showIf: "checkout.nagad_enabled" },
                ],
            },
            {
                title: "Checkout",
                fields: [
                    { key: "checkout.guest_checkout", label: "Allow ordering without an account", type: "switch", hint: "Customers only need a name, phone and address." },
                    { key: "checkout.return_days", label: "Online return requests allowed for", type: "number", suffix: "days", hint: "Days after delivery a customer can ask for a return from their account. 0 turns it off." },
                    { key: "inventory.low_stock_threshold", label: "Low stock warning at", type: "number", suffix: "units", hint: "Products at or below this stock appear in Low Stock." },
                ],
            },
        ],
    },

    display: {
        title: "Header & Display",
        subtitle: "What product cards and pages show on the website. Your own admin header (clock, prayer times, layout) is set in the header settings sidebar.",
        panels: [
            {
                title: "Product lists",
                fields: [
                    { key: "display.products_per_page", label: "Products per page", type: "select", options: [12, 24, 36, 48].map((n) => ({ value: n, title: String(n) })) },
                    { key: "display.stock_left_under", label: "Show “Only N left” at", type: "number", suffix: "units or fewer", hint: "Creates urgency when stock is low. 0 turns it off." },
                    { key: "display.show_ratings", label: "Star ratings on product cards", type: "switch" },
                ],
            },
            {
                title: "Buttons",
                fields: [
                    { key: "display.show_wishlist", label: "Wishlist (heart) button", type: "switch" },
                    { key: "display.show_compare", label: "Compare button", type: "switch" },
                    { key: "display.show_whatsapp", label: "“Ask on WhatsApp” on product pages", type: "switch", hint: "Needs your WhatsApp number in Store Information." },
                ],
            },
        ],
    },

    integrations: {
        title: "Integrations & SEO",
        subtitle: "Search engine defaults and Google services.",
        panels: [
            {
                title: "Search engines",
                subtitle: "Used on pages that do not have their own title and description.",
                fields: [
                    { key: "seo.meta_title", label: "Default page title", type: "text", counter: 60 },
                    { key: "seo.meta_description", label: "Default description", type: "textarea", counter: 160 },
                ],
            },
            {
                title: "Google services",
                subtitle: "Keys are stored on the server and never shown to visitors.",
                fields: [
                    { key: "integrations.google_translate_key", label: "Google Translate API key", type: "secret", hint: "Fills missing Bangla text automatically in Language & Translations." },
                    { key: "integrations.pagespeed_key", label: "PageSpeed Insights API key", type: "secret", hint: "Optional. Raises the daily limit for speed tests in Live Performance." },
                    { key: "integrations.search_console_site", label: "Search Console property", type: "text", hint: "e.g. sc-domain:yourshop.com or https://yourshop.com/" },
                    { key: "integrations.search_console_json", label: "Search Console service account (JSON)", type: "secret", multiline: true, hint: "Paste the JSON key of a service account that has access to the property." },
                ],
            },
        ],
    },
};
