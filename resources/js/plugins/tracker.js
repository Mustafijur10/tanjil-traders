/*
 * Storefront analytics: one page view per route, then Core Web Vitals and a heartbeat for that view.
 * Ids are random strings kept in this browser (visitor) and this tab (session); nothing personal is sent.
 * Admin pages and staff accounts are never tracked.
 */
import { onLCP, onINP, onCLS, onTTFB } from "web-vitals";
import api from "@/api";
import store from "@/store";
import { storage } from "@/store/storage";

const HEARTBEAT_MS = 30000;
const random = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), (b) => b.toString(16).padStart(2, "0")).join("");

export function visitorId() {
    let id = storage.get("tt_vid");
    if (!id || !/^[a-z0-9]{16,32}$/.test(id)) {
        id = random();
        storage.set("tt_vid", id);
    }
    return id;
}

function sessionId() {
    try {
        let id = sessionStorage.getItem("tt_sid");
        if (!id) {
            id = random();
            sessionStorage.setItem("tt_sid", id);
        }
        return id;
    } catch (e) {
        return visitorId();
    }
}

const PAGE_TYPE = [
    [/^\/$/, "home"],
    [/^\/product\//, "product"],
    [/^\/category\//, "category"],
    [/^\/brand\//, "brand"],
    [/^\/search/, "search"],
    [/^\/deals/, "deals"],
    [/^\/(shop|brands|compare)/, "shop"],
    [/^\/cart/, "cart"],
    [/^\/checkout/, "checkout"],
    [/^\/(order-placed|track-order)/, "order"],
    [/^\/(account|wishlist|login|register)/, "account"],
    [/^\/(contact|[a-z0-9-]+$)/, "page"],
];

let current = null; // { id, vitals }
let firstView = true;
let timer = null;

function send(final = false) {
    if (!current?.id) return;
    const body = JSON.stringify({ visitor_id: visitorId(), ...current.vitals });
    const url = `/api/track/${current.id}`;
    if (final && navigator.sendBeacon) navigator.sendBeacon(url, new Blob([body], { type: "application/json" }));
    else fetch(url, { method: "POST", body, headers: { "Content-Type": "application/json", Accept: "application/json" }, keepalive: true }).catch(() => {});
}

// Vitals are measured once per page load (the first view); later SPA views only send heartbeats
function watchVitals() {
    const put = (key) => (m) => {
        if (current && current.measured) current.vitals[key] = key === "cls" ? Math.round(m.value * 1000) / 1000 : Math.round(m.value);
    };
    onLCP(put("lcp"));
    onINP(put("inp"));
    onCLS(put("cls"));
    onTTFB(put("ttfb"));
}

async function view(to) {
    if (to.path.startsWith("/admin") || store.getters["auth/isStaff"]) return;
    send(true); // close the previous view
    const q = to.query;
    try {
        const { data } = await api.post(
            "/track",
            {
                visitor_id: visitorId(),
                session_id: sessionId(),
                path: to.fullPath.slice(0, 500),
                page_type: (PAGE_TYPE.find(([re]) => re.test(to.path)) || [, "other"])[1],
                referrer: firstView ? document.referrer || null : null,
                utm_source: typeof q.utm_source === "string" ? q.utm_source : null,
                utm_medium: typeof q.utm_medium === "string" ? q.utm_medium : null,
            },
            { quiet: true }
        );
        current = { id: data.id, vitals: {}, measured: firstView };
    } catch (e) {
        current = null;
    }
    firstView = false;
}

export default {
    install(app, { router }) {
        if (typeof window === "undefined") return;
        visitorId(); // make sure the id exists before the first API call
        watchVitals();
        router.afterEach((to, from) => {
            // Only real page changes (not filter/query tweaks on the same page)
            if (from.matched.length && to.path === from.path) return;
            view(to);
        });
        document.addEventListener("visibilitychange", () => {
            if (document.visibilityState === "hidden") send(true);
        });
        clearInterval(timer);
        timer = setInterval(() => document.visibilityState === "visible" && send(), HEARTBEAT_MS);
    },
};
