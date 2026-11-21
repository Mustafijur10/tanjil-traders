/*
 * Shared ApexCharts settings for Analytics pages.
 * Colours: a validated categorical palette (colour-blind safe, ≥3:1 on the surface) in a fixed order,
 * with separate steps for dark mode. Series beyond five fold into grey "Other".
 * One y-axis per chart: revenue and order counts get separate charts, never two scales.
 */
import store from "@/store";
import { money, num, date } from "@/plugins/i18n";

const SERIES = {
    light: ["#0f9d6b", "#2a78d6", "#eb6834", "#7c5cd6", "#d55181"],
    dark: ["#14a873", "#3987e5", "#d95926", "#8b6ee8", "#d55181"],
};
const OTHER = { light: "#94a3a0", dark: "#5f706c" };

/** Traffic source names (orders.traffic_source, visits.source). */
export const TRAFFIC_SOURCES = { direct: "Direct", search: "Search engines", social: "Social media", referral: "Other websites", email: "Email / SMS", ads: "Ads", unknown: "Not known" };

/** For text placed inside custom tooltip HTML. */
export const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

export const isDark = () => store.getters["prefs/isDark"];
export const palette = () => SERIES[isDark() ? "dark" : "light"];

/** Colour per item, in fixed order; "Other" is always grey. */
export function colorsFor(names) {
    const p = palette();
    let i = 0;
    return names.map((n) => (n === "Other" ? OTHER[isDark() ? "dark" : "light"] : p[i++ % p.length]));
}

function ink() {
    return isDark() ? { text: "#c3d3ce", muted: "#8fa9a2", grid: "rgba(255,255,255,0.07)" } : { text: "#3b4a46", muted: "#6b7f7b", grid: "#e9efed" };
}

/** Label for a bucket key ("2026-11-21" or "2026-11"). */
export function bucketLabel(key, bucket = "day") {
    if (bucket === "month") return date(`${key}-01`, { month: "short", year: "2-digit" });
    return date(key, { day: "numeric", month: "short" });
}

export const fmt = {
    money: (v) => money(v || 0),
    // Short money for axes: ৳1.2L, ৳45K
    moneyShort(v) {
        const n = Math.abs(v || 0);
        const sym = store.state.prefs.settings["store.currency_symbol"] || "৳";
        if (n >= 1e7) return `${sym}${num(+(v / 1e7).toFixed(1))}Cr`;
        if (n >= 1e5) return `${sym}${num(+(v / 1e5).toFixed(1))}L`;
        if (n >= 1e3) return `${sym}${num(+(v / 1e3).toFixed(0))}K`;
        return `${sym}${num(v || 0)}`;
    },
    count: (v) => num(Math.round(v || 0)),
    pct: (v) => `${num(+(v || 0).toFixed(1))}%`,
};

/** Base options every chart starts from. */
export function base(extra = {}) {
    const c = ink();
    return deepMerge(
        {
            chart: { toolbar: { show: false }, zoom: { enabled: false }, fontFamily: "Poppins, 'Noto Sans Bengali', sans-serif", foreColor: c.muted, background: "transparent", animations: { speed: 350 } },
            theme: { mode: isDark() ? "dark" : "light" },
            grid: { borderColor: c.grid, strokeDashArray: 0, padding: { left: 6, right: 8 }, xaxis: { lines: { show: false } } },
            dataLabels: { enabled: false },
            legend: { position: "top", horizontalAlign: "left", fontSize: "13px", labels: { colors: c.text }, markers: { size: 6, shape: "circle" }, itemMargin: { horizontal: 10 } },
            tooltip: { theme: isDark() ? "dark" : "light", style: { fontSize: "13px" } },
            xaxis: { axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: "12px" }, hideOverlappingLabels: true, rotate: 0 }, crosshairs: { stroke: { color: c.muted, dashArray: 3 } } },
            yaxis: { labels: { style: { fontSize: "12px" } } },
            states: { hover: { filter: { type: "darken", value: 0.9 } } },
        },
        extra
    );
}

/** Line/area over time. series: [{ name, data: [] }], keys: bucket keys */
export function timeChart({ keys, bucket, series, kind = "area", format = fmt.count, colors }) {
    return base({
        chart: { type: kind },
        colors: colors || palette(),
        stroke: { curve: "smooth", width: 2 },
        fill: kind === "area" ? { type: "gradient", gradient: { opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 95] } } : { opacity: 1 },
        markers: { size: 0, hover: { size: 5 } },
        xaxis: { categories: keys.map((k) => bucketLabel(k, bucket)), tickAmount: Math.min(keys.length, 8), tooltip: { enabled: false } },
        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: format === fmt.money ? fmt.moneyShort : format } },
        tooltip: { shared: true, intersect: false, y: { formatter: format } },
        legend: { show: series.length > 1 },
        plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: "end", columnWidth: keys.length > 20 ? "70%" : "48%" } },
    });
}

/** Horizontal bars for rankings (names on the left). */
export function rankChart({ names, format = fmt.count, color }) {
    return base({
        chart: { type: "bar" },
        colors: [color || palette()[0]],
        plotOptions: { bar: { horizontal: true, borderRadius: 4, borderRadiusApplication: "end", barHeight: "58%" } },
        xaxis: { categories: names, labels: { formatter: format === fmt.money ? fmt.moneyShort : format } },
        yaxis: { labels: { maxWidth: 160, style: { fontSize: "12.5px" } } },
        grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        tooltip: { y: { formatter: format } },
        legend: { show: false },
    });
}

/** Donut for shares (≤ 6 slices; the page folds the rest into Other). */
export function donutChart({ names, format = fmt.count, total }) {
    const c = ink();
    return base({
        chart: { type: "donut" },
        labels: names,
        colors: colorsFor(names),
        stroke: { width: 2, colors: [isDark() ? "#111e1b" : "#ffffff"] },
        legend: { position: "bottom", horizontalAlign: "center" },
        plotOptions: {
            pie: {
                donut: {
                    size: "68%",
                    labels: {
                        show: true,
                        name: { fontSize: "13px", color: c.muted },
                        value: { fontSize: "20px", fontWeight: 700, color: c.text, formatter: (v) => format(Number(v)) },
                        total: { show: true, label: total || "Total", color: c.muted, formatter: (w) => format(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
                    },
                },
            },
        },
        tooltip: { y: { formatter: format } },
    });
}

function deepMerge(a, b) {
    const out = { ...a };
    for (const [k, v] of Object.entries(b)) {
        out[k] = v && typeof v === "object" && !Array.isArray(v) && a[k] && typeof a[k] === "object" ? deepMerge(a[k], v) : v;
    }
    return out;
}
