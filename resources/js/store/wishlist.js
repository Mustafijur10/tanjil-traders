/*
 * Saved products. Signed-in customers keep them on their account;
 * guests keep them in this browser, and they move to the account after signing in (sync).
 */
import api from "@/api";
import { storage } from "./storage";
import { t } from "@/plugins/i18n";

const KEY = "tt_wishlist";

export default {
    namespaced: true,

    state: () => ({
        ids: storage.get(KEY, []),
    }),

    getters: {
        has: (s) => (id) => s.ids.includes(id),
        count: (s) => s.ids.length,
    },

    mutations: {
        set(state, ids) {
            state.ids = ids;
        },
    },

    actions: {
        async toggle({ state, commit, rootGetters, dispatch }, productId) {
            const saved = !state.ids.includes(productId);
            if (rootGetters["auth/isCustomer"]) {
                const { data } = await api.post(`/me/wishlist/${productId}`);
                commit("set", data.ids);
            } else {
                const ids = saved ? [productId, ...state.ids] : state.ids.filter((i) => i !== productId);
                commit("set", ids);
                storage.set(KEY, ids);
            }
            dispatch("notify", t(saved ? "Saved to your wishlist." : "Removed from your wishlist."), { root: true });
        },
        /** After sign-in, or on page load for a signed-in customer. */
        async sync({ commit, rootGetters }) {
            if (!rootGetters["auth/isCustomer"]) return;
            const local = storage.get(KEY, []);
            try {
                const { data } = local.length ? await api.post("/me/wishlist/sync", { ids: local }, { quiet: true }) : await api.get("/me/wishlist", { quiet: true });
                storage.remove(KEY);
                commit("set", data.ids);
            } catch (e) {
                /* keep the local list */
            }
        },
        /** After signing out, show this browser's list again. */
        reset({ commit }) {
            commit("set", storage.get(KEY, []));
        },
    },
};
