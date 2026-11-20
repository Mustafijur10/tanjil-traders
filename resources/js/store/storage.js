/* localStorage that never throws (private windows, blocked storage). */
export const storage = {
    get(key, fallback = null) {
        try {
            const v = window.localStorage.getItem(key);
            return v === null ? fallback : JSON.parse(v);
        } catch (e) {
            return fallback;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            /* storage unavailable */
        }
    },
    remove(key) {
        try {
            window.localStorage.removeItem(key);
        } catch (e) {
            /* storage unavailable */
        }
    },
};
