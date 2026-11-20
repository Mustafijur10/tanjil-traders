/*
 * Theme and language for the whole site.
 * Order of choice: the signed-in user's saved preference > this browser's last choice > store default (Admin > Settings).
 */
import api from "@/api";
import vuetify from "@/plugins/vuetify";
import { storage } from "./storage";

const THEME_KEY = "tt_theme";
const LOCALE_KEY = "tt_locale";
const THEMES = ["light", "dark"];
const LOCALES = ["en", "bn"];

let saveTimer = null;
let pending = {};

function applyTheme(name) {
    vuetify.theme.global.name.value = name;
    document.documentElement.setAttribute("data-theme", name);
    document.documentElement.style.colorScheme = name;
}

function applyLocale(lang) {
    document.documentElement.setAttribute("lang", lang);
}

export default {
    namespaced: true,

    state: () => ({
        theme: "light",
        locale: "en",
        settings: {},
        dictionary: {},
        ready: false,
    }),

    getters: {
        isDark: (s) => s.theme === "dark",
        setting: (s) => (key, fallback = null) => (s.settings[key] ?? fallback),
        canSwitchTheme: (s) => s.settings["appearance.allow_theme_switch"] !== false,
        canSwitchLanguage: (s) => s.settings["language.allow_switch"] !== false,
    },

    mutations: {
        setTheme(state, theme) {
            state.theme = theme;
        },
        setLocale(state, locale) {
            state.locale = locale;
        },
        setSettings(state, settings) {
            state.settings = settings || {};
        },
        setDictionary(state, dict) {
            state.dictionary = dict || {};
        },
        setReady(state) {
            state.ready = true;
        },
    },

    actions: {
        /** Runs once when the app starts. */
        async boot({ commit, dispatch, rootState }) {
            // Apply the last known choice at once, so the page does not flash in the wrong theme.
            const user = rootState.auth.user;
            const theme = user?.preferences?.theme || storage.get(THEME_KEY);
            const locale = user?.preferences?.locale || storage.get(LOCALE_KEY);
            if (THEMES.includes(theme)) dispatch("setTheme", { value: theme, save: false });
            if (LOCALES.includes(locale)) dispatch("setLocale", { value: locale, save: false });

            try {
                const { data } = await api.get("/bootstrap", { quiet: true });
                commit("setSettings", data.settings);
                commit("setDictionary", data.translations?.bn);

                if (!THEMES.includes(theme)) dispatch("setTheme", { value: data.settings["appearance.default_theme"] || "light", save: false });
                if (!LOCALES.includes(locale)) dispatch("setLocale", { value: data.settings["language.default"] || "en", save: false });
            } catch (e) {
                /* the site still works in English with the default theme */
            }
            commit("setReady");
        },

        /** Reload the Bangla dictionary after translations are edited. */
        async refreshDictionary({ commit }) {
            const { data } = await api.get("/bootstrap", { quiet: true });
            commit("setDictionary", data.translations?.bn);
            commit("setSettings", data.settings);
        },

        setTheme({ commit, dispatch }, payload) {
            const { value, save = true } = typeof payload === "string" ? { value: payload } : payload;
            const theme = THEMES.includes(value) ? value : "light";
            commit("setTheme", theme);
            applyTheme(theme);
            if (save) {
                storage.set(THEME_KEY, theme);
                dispatch("savePreferences", { theme });
            }
        },

        toggleTheme({ state, dispatch }) {
            dispatch("setTheme", state.theme === "dark" ? "light" : "dark");
        },

        setLocale({ commit, dispatch }, payload) {
            const { value, save = true } = typeof payload === "string" ? { value: payload } : payload;
            const locale = LOCALES.includes(value) ? value : "en";
            commit("setLocale", locale);
            applyLocale(locale);
            // Only a visitor's own choice is remembered, so later changes to the store default still reach them.
            if (save) {
                storage.set(LOCALE_KEY, locale);
                dispatch("savePreferences", { locale });
            }
        },

        /** After login: switch to the theme and language saved on the account. */
        applyUserPreferences({ dispatch }, user) {
            const p = user?.preferences || {};
            if (THEMES.includes(p.theme)) dispatch("setTheme", { value: p.theme, save: false });
            if (LOCALES.includes(p.locale)) dispatch("setLocale", { value: p.locale, save: false });
        },

        /** Save to the signed-in account, batched so quick changes make one request. */
        savePreferences({ rootState, commit }, partial) {
            if (!rootState.auth.token) return;
            pending = { ...pending, ...partial };
            clearTimeout(saveTimer);
            saveTimer = setTimeout(async () => {
                const body = pending;
                pending = {};
                try {
                    const { data } = await api.put("/me/preferences", { preferences: body }, { quiet: true });
                    commit("auth/setUser", data, { root: true });
                } catch (e) {
                    /* kept in this browser anyway */
                }
            }, 800);
        },
    },
};
