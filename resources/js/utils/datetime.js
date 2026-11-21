/*
 * The server sends dates as ISO strings in UTC ("2026-11-21T16:00:00.000000Z").
 * <input type="datetime-local"> needs local "YYYY-MM-DDTHH:mm". These convert both ways,
 * so a sale that ends at 10 PM in Dhaka really ends at 10 PM.
 */
const pad = (n) => String(n).padStart(2, "0");

export function toLocalInput(iso) {
    if (!iso) return null;
    const d = new Date(iso);
    if (isNaN(d)) return null;
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/** Local input value -> ISO string with the browser's offset, which the server stores correctly. */
export function fromLocalInput(value) {
    if (!value) return null;
    const d = new Date(value);
    return isNaN(d) ? null : d.toISOString();
}

/** live | scheduled | ended | off, for anything with is_active / starts_at / ends_at. */
export function scheduleState(item, now = new Date()) {
    if (!item.is_active) return "off";
    if (item.starts_at && new Date(item.starts_at) > now) return "scheduled";
    if (item.ends_at && new Date(item.ends_at) < now) return "ended";
    return "live";
}

export const SCHEDULE_LABELS = {
    live: { label: "Live", tone: "green" },
    scheduled: { label: "Scheduled", tone: "blue" },
    ended: { label: "Ended", tone: "grey" },
    off: { label: "Off", tone: "grey" },
};
