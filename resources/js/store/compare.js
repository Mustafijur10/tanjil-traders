/*
 * Products picked for comparison (up to 3), kept in this browser.
 * Each item keeps just what the floating compare bar shows.
 */
import { storage } from "./storage";
import { t } from "@/plugins/i18n";

const KEY = "tt_compare";
export const COMPARE_MAX = 3;

export default {
    namespaced: true,

    state: () => ({
        items: storage.get(KEY, []),
    }),

    getters: {
        ids: (s) => s.items.map((i) => i.id),
        has: (s) => (id) => s.items.some((i) => i.id === id),
        count: (s) => s.items.length,
    },

    mutations: {
        set(state, items) {
            state.items = items;
            storage.set(KEY, items);
        },
    },

    actions: {
        /** Add or remove; p is a product card ({ id, name, slug, image, category? }). */
        toggle({ state, commit, dispatch }, p) {
            if (state.items.some((i) => i.id === p.id)) {
                commit("set", state.items.filter((i) => i.id !== p.id));
                return;
            }
            if (state.items.length >= COMPARE_MAX) {
                dispatch("notify", { text: t("You can compare up to {n} products. Remove one first.", { n: COMPARE_MAX }), type: "warning" }, { root: true });
                return;
            }
            commit("set", [...state.items, { id: p.id, name: p.name, slug: p.slug, image: p.image || null }]);
        },
        remove({ state, commit }, id) {
            commit("set", state.items.filter((i) => i.id !== id));
        },
        clear({ commit }) {
            commit("set", []);
        },
    },
};
