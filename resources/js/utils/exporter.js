/*
 * Downloads for every report and list, plus Excel templates and reading uploaded Excel files.
 *
 * A report is described once and both formats are made from it:
 *   {
 *     title: "Sales", subtitle: "21 Oct – 21 Nov 2026", filename: "sales",
 *     kpis:   [{ label: "Sales", value: 128500, type: "money" }],
 *     tables: [{ title: "Top cities", columns: [{ text: "City", value: "name" }, { text: "Sales", value: "revenue", type: "money" }], rows: [...] }]
 *   }
 * Column types: text (default), money, number, percent, date, datetime.
 *
 * Excel: real .xlsx (ExcelJS) with numbers kept as numbers, a styled header, frozen first row and filters.
 * PDF: drawn by the browser from a clean A4 layout (html2pdf), so Bangla text is shaped correctly,
 *      which PDF libraries cannot do on their own.
 * Both libraries load only when someone downloads, so pages stay light.
 */
import store from "@/store";
import { t, money, num, date } from "@/plugins/i18n";
import { fileUrl } from "@/api";

const GREEN = "FF0A5548";
const CURRENCY = () => store.state.prefs.settings["store.currency_symbol"] || "৳";

const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

/** Text for a cell, as people read it (PDF, previews). */
export function formatCell(value, type) {
    if (value === null || value === undefined || value === "") return "—";
    switch (type) {
        case "money":
            return money(value);
        case "number":
            return num(value);
        case "percent":
            return `${num(Number(value).toFixed(1))}%`;
        case "date":
            return date(value, { day: "numeric", month: "short", year: "numeric" });
        case "datetime":
            return date(value, { day: "numeric", month: "short", year: "numeric", hour: "numeric", minute: "2-digit" });
        default:
            return String(value);
    }
}

/** "21 Oct 2026 – 21 Nov 2026" for a report's period ({ from, to }). */
export function periodLabel(p) {
    if (!p) return "";
    const f = (d) => date(d, { day: "numeric", month: "short", year: "numeric" });
    return `${f(p.from)} – ${f(p.to)}`;
}

function stamp() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function fileName(spec, ext) {
    const base = String(spec.filename || spec.title || "report").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
    return `${base}-${stamp()}.${ext}`;
}

function saveBlob(blob, name) {
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
        URL.revokeObjectURL(a.href);
        a.remove();
    }, 1000);
}

// ───────────────────────── Excel ─────────────────────────
function numFmt(type) {
    return { money: `"${CURRENCY()}"#,##0`, number: "#,##0.##", percent: '0.0"%"', date: "dd mmm yyyy", datetime: "dd mmm yyyy hh:mm" }[type];
}

function excelValue(value, type) {
    if (value === null || value === undefined || value === "") return null;
    if (["money", "number", "percent"].includes(type)) return Number(value);
    if (type === "date" || type === "datetime") return new Date(value);
    return typeof value === "object" ? JSON.stringify(value) : String(value);
}

function styleHeader(row) {
    row.font = { bold: true, color: { argb: "FFFFFFFF" } };
    row.fill = { type: "pattern", pattern: "solid", fgColor: { argb: GREEN } };
    row.alignment = { vertical: "middle", wrapText: true };
    row.height = 22;
}

function addTableSheet(wb, table, used) {
    let name = String(t(table.title) || "Sheet").replace(/[\\/?*[\]:]/g, " ").slice(0, 28) || "Sheet";
    while (used.has(name)) name = name.slice(0, 26) + " " + used.size;
    used.add(name);

    const ws = wb.addWorksheet(name, { views: [{ state: "frozen", ySplit: 1 }] });
    ws.columns = table.columns.map((c) => ({ header: t(c.text), key: c.value, width: Math.min(60, Math.max(12, t(c.text).length + 4, c.width || 0)) }));
    styleHeader(ws.getRow(1));
    for (const r of table.rows) {
        ws.addRow(Object.fromEntries(table.columns.map((c) => [c.value, excelValue(typeof c.get === "function" ? c.get(r) : r[c.value], c.type)])));
    }
    table.columns.forEach((c, i) => {
        const col = ws.getColumn(i + 1);
        if (numFmt(c.type)) col.numFmt = numFmt(c.type);
        // Fit the widest text, within limits
        const longest = table.rows.reduce((m, r) => Math.max(m, String((typeof c.get === "function" ? c.get(r) : r[c.value]) ?? "").length), 0);
        col.width = Math.min(60, Math.max(col.width, longest + 2));
    });
    if (table.rows.length) ws.autoFilter = { from: { row: 1, column: 1 }, to: { row: 1, column: table.columns.length } };
    return ws;
}

export async function exportExcel(spec) {
    const { default: ExcelJS } = await import("exceljs");
    const wb = new ExcelJS.Workbook();
    wb.creator = store.state.prefs.settings["store.name"] || "";
    wb.created = new Date();
    const used = new Set();

    // First sheet: what the report is, and its headline numbers
    if (spec.kpis?.length || spec.subtitle) {
        const ws = wb.addWorksheet(t("Summary"));
        used.add(t("Summary"));
        ws.columns = [{ width: 34 }, { width: 24 }];
        ws.addRow([`${store.state.prefs.settings["store.name"] || ""} · ${t(spec.title)}`]).font = { bold: true, size: 14 };
        if (spec.subtitle) ws.addRow([spec.subtitle]).font = { color: { argb: "FF6B7F7B" } };
        ws.addRow([t("Downloaded {date}", { date: date(new Date(), { dateStyle: "medium", timeStyle: "short" }) })]).font = { color: { argb: "FF6B7F7B" } };
        ws.addRow([]);
        if (spec.kpis?.length) {
            styleHeader(ws.addRow([t("Measure"), t("Value")]));
            for (const k of spec.kpis) {
                const row = ws.addRow([t(k.label), excelValue(k.value, k.type)]);
                if (numFmt(k.type)) row.getCell(2).numFmt = numFmt(k.type);
            }
        }
    }
    for (const table of spec.tables || []) addTableSheet(wb, table, used);

    const buffer = await wb.xlsx.writeBuffer();
    saveBlob(new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" }), fileName(spec, "xlsx"));
}

// ───────────────────────── PDF ─────────────────────────
function pdfHtml(spec) {
    const s = store.state.prefs.settings;
    const logo = s["store.logo"] ? `<img src="${esc(fileUrl(s["store.logo"]))}" alt="" crossorigin="anonymous" />` : "";
    const kpis = (spec.kpis || []).map((k) => `<div><small>${esc(t(k.label))}</small><b>${esc(formatCell(k.value, k.type))}</b></div>`).join("");
    const tables = (spec.tables || [])
        .map((tb) => {
            const head = tb.columns.map((c) => `<th class="${["money", "number", "percent"].includes(c.type) ? "is-num" : ""}">${esc(t(c.text))}</th>`).join("");
            const body = tb.rows.length
                ? tb.rows
                      .map((r) => `<tr>${tb.columns.map((c) => `<td class="${["money", "number", "percent"].includes(c.type) ? "is-num" : ""}">${esc(formatCell(typeof c.get === "function" ? c.get(r) : r[c.value], c.type))}</td>`).join("")}</tr>`)
                      .join("")
                : `<tr><td colspan="${tb.columns.length}" class="is-empty">${esc(t("No data for this period yet."))}</td></tr>`;
            return `<section><h2>${esc(t(tb.title))}</h2><table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table></section>`;
        })
        .join("");

    return `
        <header class="tt-xr-head">
            <div class="tt-xr-store">${logo}<div><b>${esc(s["store.name"] || "")}</b><span>${esc([s["store.phone"], s["store.email"]].filter(Boolean).join(" · "))}</span></div></div>
            <div class="tt-xr-title"><h1>${esc(t(spec.title))}</h1>${spec.subtitle ? `<span>${esc(spec.subtitle)}</span>` : ""}</div>
        </header>
        ${kpis ? `<div class="tt-xr-kpis">${kpis}</div>` : ""}
        ${tables}
        <footer class="tt-xr-foot">${esc(t("Downloaded {date}", { date: date(new Date(), { dateStyle: "medium", timeStyle: "short" }) }))}</footer>`;
}

export async function exportPdf(spec) {
    const { default: html2pdf } = await import("html2pdf.js");
    // Tables with many columns get a landscape page
    const wide = (spec.tables || []).some((tb) => tb.columns.length > 6);
    // The holder keeps the layout out of sight; html2pdf copies only the inner report (no off-screen position)
    const holder = document.createElement("div");
    holder.className = wide ? "tt-xr-holder is-wide" : "tt-xr-holder";
    const el = document.createElement("div");
    el.className = wide ? "tt-xr is-wide" : "tt-xr";
    el.setAttribute("lang", store.state.prefs.locale);
    el.innerHTML = pdfHtml(spec);
    holder.appendChild(el);
    document.body.appendChild(holder);
    try {
        await document.fonts?.ready;
        await html2pdf()
            .from(el)
            .set({
                margin: [10, 9, 14, 9],
                filename: fileName(spec, "pdf"),
                image: { type: "jpeg", quality: 0.95 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: "#ffffff" },
                jsPDF: { unit: "mm", format: "a4", orientation: wide ? "landscape" : "portrait" },
                pagebreak: { mode: ["css", "legacy"], avoid: ["tr", ".tt-xr-kpis", "h2"] },
            })
            .toPdf()
            .get("pdf")
            .then((pdf) => {
                // Page numbers
                const pages = pdf.internal.getNumberOfPages();
                const w = pdf.internal.pageSize.getWidth();
                const h = pdf.internal.pageSize.getHeight();
                for (let i = 1; i <= pages; i++) {
                    pdf.setPage(i);
                    pdf.setFontSize(8);
                    pdf.setTextColor(120);
                    pdf.text(`${i} / ${pages}`, w - 12, h - 6, { align: "right" });
                }
            })
            .save();
    } finally {
        holder.remove();
    }
}

// ───────────────────────── Import templates and reading files ─────────────────────────

/** Template: a header row, one example row, and a Help sheet that explains each column. */
export async function downloadTemplate(type, columns, title) {
    const { default: ExcelJS } = await import("exceljs");
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet(t(title), { views: [{ state: "frozen", ySplit: 1 }] });
    ws.columns = columns.map((c) => ({ header: c.label + (c.required ? " *" : ""), key: c.key, width: Math.max(14, c.label.length + 6, String(c.example ?? "").length + 2 > 50 ? 50 : String(c.example ?? "").length + 2) }));
    styleHeader(ws.getRow(1));
    const example = ws.addRow(Object.fromEntries(columns.map((c) => [c.key, c.example ?? ""])));
    example.font = { italic: true, color: { argb: "FF6B7F7B" } };
    example.alignment = { wrapText: true, vertical: "top" };

    const help = wb.addWorksheet(t("How to fill"));
    help.columns = [{ width: 26 }, { width: 12 }, { width: 80 }];
    styleHeader(help.addRow([t("Column"), t("Required"), t("What to write")]));
    for (const c of columns) {
        const r = help.addRow([c.label, c.required ? t("Yes") : "", [c.help, c.example ? `${t("Example")}: ${c.example}` : ""].filter(Boolean).join("\n")]);
        r.alignment = { wrapText: true, vertical: "top" };
    }
    help.addRow([]);
    help.addRow([t("Delete the grey example row before importing. Keep the first row (the column names) as it is.")]).font = { bold: true };

    const buffer = await wb.xlsx.writeBuffer();
    saveBlob(new Blob([buffer]), `${type}-template.xlsx`);
}

/** Existing records in the template's columns, so they can be edited and imported again. */
export async function downloadRows(type, columns, rows, title) {
    await exportExcel({ title, filename: type, tables: [{ title, columns: columns.map((c) => ({ text: c.label + (c.required ? " *" : ""), value: c.key, type: c.type === "number" ? "number" : "text" })), rows }] });
}

/**
 * Read the first sheet of an uploaded .xlsx into rows keyed by column key.
 * Header cells are matched to column labels (case and the * mark ignored), so column order does not matter.
 * Returns { rows, unknown: [header names not recognised], missing: [required labels not found] }.
 */
export async function readSheet(file, columns) {
    const { default: ExcelJS } = await import("exceljs");
    const wb = new ExcelJS.Workbook();
    await wb.xlsx.load(await file.arrayBuffer());
    const ws = wb.worksheets[0];
    if (!ws) throw new Error(t("The file has no sheets."));

    const norm = (s) => String(s ?? "").replace(/\*/g, "").trim().toLowerCase();
    const byLabel = Object.fromEntries(columns.flatMap((c) => [[norm(c.label), c.key], [norm(c.key), c.key]]));
    const header = {};
    const unknown = [];
    ws.getRow(1).eachCell((cell, col) => {
        const key = byLabel[norm(cell.text)];
        if (key) header[col] = key;
        else if (norm(cell.text)) unknown.push(cell.text);
    });
    const found = new Set(Object.values(header));
    const missing = columns.filter((c) => c.required && !found.has(c.key)).map((c) => c.label);

    const rows = [];
    ws.eachRow((row, n) => {
        if (n === 1) return;
        const r = {};
        let filled = false;
        for (const [col, key] of Object.entries(header)) {
            const cell = row.getCell(Number(col));
            let v = cell.value;
            if (v && typeof v === "object") v = v.text ?? v.result ?? (v.richText ? v.richText.map((p) => p.text).join("") : v instanceof Date ? v.toISOString() : cell.text);
            if (typeof v === "string") v = v.trim();
            if (v !== null && v !== undefined && v !== "") filled = true;
            r[key] = v === undefined ? null : v;
        }
        if (filled) rows.push(r);
    });
    return { rows, unknown, missing };
}
