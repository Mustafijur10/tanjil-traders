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
    ],
];
