/*
 * Global state shared by the admin panel and the storefront.
 *   auth   - login token and the signed-in user
 *   prefs  - theme, language, public store settings and the Bangla dictionary
 *   root   - current layout and the snackbar message
 */
import { createStore } from "vuex";
import auth from "./auth";
import prefs from "./prefs";

export default createStore({
    modules: { auth, prefs },

    state: {
        layout: "front",
        snackbar: { open: false, text: "", type: "success" },
    },

    getters: {
        layout: (state) => state.layout,
    },

    mutations: {
        setLayout(state, layout) {
            state.layout = layout;
        },
        showSnackbar(state, { text, type = "success" }) {
            state.snackbar = { open: true, text, type };
        },
        hideSnackbar(state) {
            state.snackbar.open = false;
        },
    },

    actions: {
        /** dispatch("notify", { text: "Saved", type: "success" | "error" | "info" | "warning" }) */
        notify({ commit }, payload) {
            commit("showSnackbar", typeof payload === "string" ? { text: payload } : payload);
        },
    },
});
