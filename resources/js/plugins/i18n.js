/*
 * English / Bangla text.
 *
 *   $t("Add to cart")                 -> "কার্টে যোগ করুন" in Bangla, the English text otherwise
 *   $t("{count} items", { count: 3 })  -> placeholders in {braces} are filled in after translating
 *   $money(24500)                      -> "৳24,500" / "৳২৪,৫০০"
 *   $num(1200)                         -> "1,200" / "১,২০০"
 *   $date(value, { month: "short" })   -> date in the current language
 *
 * The English sentence is the key. Bangla lines come from Admin > Settings > Language & Translations.
 * Texts without a Bangla line fall back to English, so nothing ever shows up empty.
 */
import store from "@/store";
import api from "@/api";

const fill = (text, params) =>
    params ? text.replace(/\{(\w+)\}/g, (m, k) => (params[k] !== undefined ? params[k] : m)) : text;

/*
 * When a staff member browses in Bangla, any text still showing in English is reported once,
 * so it appears under "Missing" in Language & Translations without anyone hunting for it.
 */
const reported = new Set();
let queue = [];
let flushTimer = null;

function reportMissing(text) {
    if (reported.has(text) || !/[A-Za-z]/.test(text) || !store.getters["auth/isStaff"] || !store.state.prefs.ready) return;
    reported.add(text);
    queue.push(text);
    clearTimeout(flushTimer);
    flushTimer = setTimeout(() => {
        while (queue.length) {
            const texts = queue.splice(0, 200);
            api.post("/admin/translations/register", { texts }, { quiet: true }).catch(() => {});
        }
    }, 3000);
}

export function t(text, params) {
    if (text === null || text === undefined || text === "") return "";
    const s = String(text);
    const { locale, dictionary } = store.state.prefs;
    if (locale !== "bn") return fill(s, params);

    const translated = dictionary[s];
    if (!translated) reportMissing(s);
    return fill(translated || s, params);
}

/**
 * Count-aware text: $tn(1, "{n} product", "{n} products") -> "1 product", "3 products".
 * Both forms go to the translation list; Bangla usually uses the same word for both.
 */
export function tn(count, singular, plural) {
    return t(Number(count) === 1 ? singular : plural, { n: num(count) });
}

// Bangladeshi grouping in both languages: 12,49,000 (en-IN) and ১২,৪৯,০০০ (bn-BD)
const numberLocale = () => (store.state.prefs.locale === "bn" ? "bn-BD" : "en-IN");

export function num(value, options = {}) {
    const n = Number(value || 0);
    return new Intl.NumberFormat(numberLocale(), { maximumFractionDigits: 2, ...options }).format(n);
}

export function money(value) {
    const symbol = store.state.prefs.settings["store.currency_symbol"] || "৳";
    return symbol + num(Math.round(Number(value || 0)));
}

export function date(value, options = { day: "numeric", month: "short", year: "numeric" }) {
    if (!value) return "";
    const d = value instanceof Date ? value : new Date(value);
    return new Intl.DateTimeFormat(store.state.prefs.locale === "bn" ? "bn-BD" : "en-GB", options).format(d);
}

export default {
    install(app) {
        app.config.globalProperties.$t = t;
        app.config.globalProperties.$tn = tn;
        app.config.globalProperties.$num = num;
        app.config.globalProperties.$money = money;
        app.config.globalProperties.$date = date;
    },
};
