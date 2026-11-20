import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vuetify from "vite-plugin-vuetify";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/js/app.js"],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Only the Vuetify components a page uses end up in its bundle.
        vuetify({ autoImport: true }),
    ],
    resolve: {
        alias: {
            "@": "/resources/js",
        },
    },
    build: {
        rollupOptions: {
            output: {
                // Charts are only downloaded by pages that draw charts.
                manualChunks(id) {
                    if (id.includes("apexcharts")) return "charts";
                    if (id.includes("node_modules/vuetify")) return "vuetify";
                },
            },
        },
    },
});
