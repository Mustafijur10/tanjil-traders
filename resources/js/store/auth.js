import api from "@/api";
import { storage } from "./storage";

const TOKEN_KEY = "tt_token";
const USER_KEY = "tt_user";

export default {
    namespaced: true,

    state: () => ({
        token: storage.get(TOKEN_KEY),
        user: storage.get(USER_KEY),
    }),

    getters: {
        isLoggedIn: (s) => !!s.token,
        isStaff: (s) => !!s.user && ["admin", "staff"].includes(s.user.role),
        isAdmin: (s) => s.user?.role === "admin",
        isCustomer: (s) => !!s.token && s.user?.role === "customer",
        user: (s) => s.user,
    },

    mutations: {
        set(state, { token, user }) {
            state.token = token;
            state.user = user;
            storage.set(TOKEN_KEY, token);
            storage.set(USER_KEY, user);
        },
        setUser(state, user) {
            state.user = user;
            storage.set(USER_KEY, user);
        },
        clear(state) {
            state.token = null;
            state.user = null;
            storage.remove(TOKEN_KEY);
            storage.remove(USER_KEY);
        },
    },

    actions: {
        /** { login, password, admin } -> signs in and applies the user's saved theme and language. */
        async login({ commit, dispatch }, credentials) {
            const { data } = await api.post("/login", credentials, { quiet: true });
            commit("set", data);
            dispatch("prefs/applyUserPreferences", data.user, { root: true });
            return data.user;
        },

        async register({ commit }, form) {
            const { data } = await api.post("/register", form, { quiet: true });
            commit("set", data);
            return data.user;
        },

        async logout({ commit }) {
            try {
                await api.post("/logout", null, { quiet: true });
            } finally {
                commit("clear");
            }
        },

        /** Refresh the user from the server (role or name may have changed). */
        async fetchMe({ commit, state }) {
            if (!state.token) return null;
            const { data } = await api.get("/me", { quiet: true });
            commit("setUser", data);
            return data;
        },
    },
};
