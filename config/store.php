<?php

/*
 * Default values for every store setting. Admins change them in Settings;
 * saved values live in the `settings` table and override these.
 * Keys starting with a prefix in Setting::PUBLIC_PREFIXES are sent to the storefront.
 */
return [
    'settings' => [
        // Store information
        'store.name'            => 'Tanjil Traders',
        'store.tagline'         => 'Electronics, gadgets and gear',
        'store.logo'            => null,
        'store.logo_dark'       => null,
        'store.favicon'         => null,
        'store.phone'           => '',
        'store.email'           => '',
        'store.address'         => '',
        'store.hours'           => '',
        'store.map_location'    => '',   // Google Maps link/embed code, or empty to use the address
        'store.footer_text'     => '',
        'store.currency_symbol' => '৳',

        'social.facebook'  => '',
        'social.instagram' => '',
        'social.youtube'   => '',
        'social.tiktok'    => '',
        'social.whatsapp'  => '',

        // Appearance
        'appearance.default_theme'      => 'light',  // light | dark
        'appearance.accent'             => 'green',
        'appearance.allow_theme_switch' => true,

        // Language
        'language.default'      => 'en',             // en | bn
        'language.allow_switch' => true,

        // Checkout and payments
        'checkout.guest_checkout' => true,
        'checkout.cod_enabled'    => true,
        'checkout.bkash_enabled'  => false,
        'checkout.bkash_number'   => '',
        'checkout.nagad_enabled'  => false,
        'checkout.nagad_number'   => '',
        'checkout.return_days'    => 7,      // days after delivery a customer can ask for a return (0 = off)

        // Storefront display
        'display.products_per_page' => 24,
        'display.stock_left_under'  => 3,     // show "Only 3 left" at or below this; 0 = never
        'display.show_ratings'      => true,
        'display.show_compare'      => true,
        'display.show_wishlist'     => true,
        'display.show_whatsapp'     => true,

        // SMS to customers (private)
        'sms.enabled'       => false,
        'sms.provider'      => 'bulksmsbd',  // bulksmsbd | custom
        'sms.api_key'       => '',
        'sms.sender_id'     => '',
        'sms.api_url'       => '',           // custom gateway link with {phone} {message} {key} {sender}
        'sms.on_placed'     => true,
        'sms.on_confirmed'  => true,
        'sms.on_shipped'    => true,
        'sms.on_delivered'  => false,
        'sms.on_cancelled'  => true,
        'sms.tpl_placed'    => 'Dear {name}, we received your order {order} ({total} Tk). We will call you to confirm. - {store}',
        'sms.tpl_confirmed' => 'Your order {order} is confirmed and will be packed soon. - {store}',
        'sms.tpl_shipped'   => 'Your order {order} is on the way with {courier}. Tracking: {tracking}. Track: {link}',
        'sms.tpl_delivered' => 'Your order {order} was delivered. Thank you for shopping with {store}!',
        'sms.tpl_cancelled' => 'Your order {order} was cancelled. Questions? Call {phone}. - {store}',

        // Alerts to the shop (private)
        'notify.order_email' => '',
        'notify.order_sms'   => '',

        // SEO defaults
        'seo.meta_title'       => 'Tanjil Traders - Electronics, gadgets and gear',
        'seo.meta_description' => '',

        // Inventory
        'inventory.low_stock_threshold' => 5,

        // Integrations (private, never sent to the storefront)
        'integrations.google_translate_key' => '',
        'integrations.pagespeed_key'        => '',
        'integrations.search_console_site'  => '',
        'integrations.search_console_json'  => '',
    ],

    // Keys whose values are secrets: shown masked in the admin.
    'secret_settings' => [
        'integrations.google_translate_key',
        'integrations.pagespeed_key',
        'integrations.search_console_json',
        'sms.api_key',
    ],
];
