/*
 * The one HTTP client for the app. Adds the login token, and turns errors into
 * messages people can read (shown in the global snackbar).
 *
 *   import api from "@/api";
 *   const { data } = await api.get("/admin/translations", { params: { status: "missing" } });
 *
 * Pass { quiet: true } in the request config to handle an error yourself without the snackbar.
 */
import axios from "axios";
import store from "@/store";
import router from "@/router";
import { t } from "@/plugins/i18n";

const api = axios.create({
    baseURL: "/api",
    headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
});

api.interceptors.request.use((config) => {
    const token = store.state.auth.token;
    if (token) config.headers.Authorization = `Bearer ${token}`;
    config.headers["Accept-Language"] = store.state.prefs.locale;
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const quiet = error.config?.quiet;

        if (status === 401) {
            store.commit("auth/clear");
            const inAdmin = router.currentRoute.value.path.startsWith("/admin");
            const target = inAdmin ? "/admin/login" : "/login";
            if (router.currentRoute.value.path !== target) {
                router.push({ path: target, query: { redirect: router.currentRoute.value.fullPath } });
            }
        } else if (!quiet) {
            let message = error.response?.data?.message;
            if (status === 422 && error.response.data.errors) {
                message = Object.values(error.response.data.errors).flat()[0];
            } else if (status === 403) {
                message = message || t("You do not have permission to do this.");
            } else if (!error.response) {
                message = t("Could not reach the server. Check your connection.");
            } else if (status >= 500) {
                message = t("Something went wrong on our side. Please try again.");
            }
            store.dispatch("notify", { text: t(message || "Request failed."), type: "error" });
        }

        return Promise.reject(error);
    }
);

/** Validation errors as { field: "first message" }, for showing under each input. */
export function fieldErrors(error) {
    const errors = error?.response?.data?.errors || {};
    return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
}

/** Full URL for a stored file path ("products/a.jpg" -> "/storage/products/a.jpg"). */
export function fileUrl(path) {
    if (!path) return "";
    if (/^(https?:)?\/\//.test(path) || path.startsWith("/")) return path;
    return `/storage/${path}`;
}

export default api;
