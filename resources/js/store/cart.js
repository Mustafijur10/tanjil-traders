/*
 * Shopping cart, kept in this browser so it survives reloads and works for guests.
 * Prices here are refreshed from the server (refresh) on the cart and checkout pages,
 * and the server prices the order itself, so nothing in the browser changes what is charged.
 *
 * Line: { product_id, variant_id, qty, name, variant_label, slug, image, price, regular_price, stock, available }
 */
import api from "@/api";
import { storage } from "./storage";
import { t } from "@/plugins/i18n";

const KEY = "tt_cart";
export const keyOf = (l) => `${l.product_id}:${l.variant_id || 0}`;

export default {
    namespaced: true,

    state: () => ({
        lines: storage.get(KEY, []),
        drawer: false,
        issues: [],
    }),

    getters: {
        count: (s) => s.lines.reduce((n, l) => n + l.qty, 0),
        subtotal: (s) => s.lines.filter((l) => l.available !== false).reduce((n, l) => n + l.price * l.qty, 0),
        items: (s) => s.lines.filter((l) => l.available !== false).map((l) => ({ product_id: l.product_id, variant_id: l.variant_id || null, qty: l.qty })),
        hasProblems: (s) => s.lines.some((l) => l.available === false),
    },

    mutations: {
        set(state, lines) {
            state.lines = lines;
            storage.set(KEY, lines);
        },
        drawer(state, open) {
            state.drawer = open;
        },
        issues(state, issues) {
            state.issues = issues;
        },
    },

    actions: {
        /** Adds `line.qty` to what is already in the cart (never above stock). */
        add({ state, commit, dispatch }, { line, openDrawer = true }) {
            const key = keyOf(line);
            const existing = state.lines.find((l) => keyOf(l) === key);
            const max = Math.min(line.stock ?? 99, 99);

            if (existing && existing.qty >= max) {
                dispatch("notify", { text: t("You already have all {n} in your cart.", { n: max }), type: "warning" }, { root: true });
                return false;
            }
            const qty = Math.min((existing?.qty || 0) + (line.qty || 1), max);
            const next = existing ? state.lines.map((l) => (keyOf(l) === key ? { ...l, ...line, qty, available: true } : l)) : [...state.lines, { ...line, qty, available: true }];
            commit("set", next);
            if (openDrawer) commit("drawer", true);
            return true;
        },
        setQty({ state, commit }, { key, qty }) {
            commit("set", state.lines.map((l) => (keyOf(l) === key ? { ...l, qty: Math.max(1, Math.min(qty, l.stock ?? 99, 99)) } : l)));
        },
        remove({ state, commit }, key) {
            commit("set", state.lines.filter((l) => keyOf(l) !== key));
            commit("issues", state.issues.filter((i) => i.key !== key));
        },
        clear({ commit }) {
            commit("set", []);
            commit("issues", []);
        },
        /** Re-check prices and stock with the server. */
        async refresh({ state, commit }) {
            if (!state.lines.length) return;
            const items = state.lines.map((l) => ({ product_id: l.product_id, variant_id: l.variant_id || null, qty: l.qty }));
            const { data } = await api.post("/cart", { items }, { quiet: true });
            const fresh = Object.fromEntries(data.lines.map((l) => [l.key, l]));
            commit(
                "set",
                state.lines.map((l) => {
                    const f = fresh[keyOf(l)];
                    if (!f) return l;
                    if (!f.available) return { ...l, available: false, stock: f.stock };
                    const { name, variant_label, slug, image, price, regular_price, stock, qty } = f;
                    return { ...l, name, variant_label, slug, image, price, regular_price, stock, qty, available: true };
                })
            );
            commit("issues", data.issues);
        },
    },
};
