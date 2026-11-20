/*
 * Vuetify setup: the store's light and dark themes, component defaults and aliases.
 * Colours come from the admin dashboard design. Change them here only.
 */
import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";
import { createVuetify } from "vuetify";
// Components are imported automatically per page by vite-plugin-vuetify; only alias targets are listed here.
import { VBtn, VTextField, VSelect, VTextarea, VAutocomplete, VCard, VSwitch, VCheckbox, VDialog } from "vuetify/components";
import { VDataTable } from "vuetify/components/VDataTable";

const light = {
    dark: false,
    colors: {
        background: "#f3f6f5",
        surface: "#ffffff",
        "surface-variant": "#e8eeec",
        "on-surface-variant": "#3b4a46",
        primary: "#0f9d6b",
        "primary-darken-1": "#0a5548",
        secondary: "#053d35",
        accent: "#f59e0b",
        error: "#d64545",
        info: "#2f6fe0",
        success: "#0f9d6b",
        warning: "#d97706",
        mint: "#dff5ec",
        ink: "#10231f",
    },
    variables: {
        "border-color": "#0f2a24",
        "border-opacity": 0.1,
        "high-emphasis-opacity": 0.92,
        "medium-emphasis-opacity": 0.66,
    },
};

const dark = {
    dark: true,
    colors: {
        background: "#0b1513",
        surface: "#111e1b",
        "surface-variant": "#1a2b27",
        "on-surface-variant": "#b9c9c4",
        primary: "#2fbf88",
        "primary-darken-1": "#1f9a6c",
        secondary: "#6ee7b7",
        accent: "#fbbf24",
        error: "#f07272",
        info: "#6b9cff",
        success: "#2fbf88",
        warning: "#fbbf24",
        mint: "#163a2f",
        ink: "#e6f0ed",
    },
    variables: {
        "border-color": "#cfe3dc",
        "border-opacity": 0.12,
        "high-emphasis-opacity": 0.92,
        "medium-emphasis-opacity": 0.68,
    },
};

const field = { variant: "outlined", density: "comfortable", color: "primary", hideDetails: "auto" };

/*
 * Aliases: use these in every page instead of the plain Vuetify tags, so one change here restyles the whole app.
 *
 *   Buttons   <v-btn-primary>    main action (Save, Sign in)
 *             <v-btn-secondary>  second action (Scan, Upload, Export)
 *             <v-btn-tertiary>   quiet action (Cancel, Remove, icon buttons)
 *             <v-btn-danger>     destructive action (Delete)
 *   Inputs    <v-input-trz> <v-select-trz> <v-textarea-trz> <v-autocomplete-trz>
 *             <v-switch-trz> <v-checkbox-trz>
 *   Layout    <v-panel>  bordered card     <v-dialog-trz>  dialog
 *   Data      <v-table-trz>  data table with sorting and paging
 */
export default createVuetify({
    theme: { defaultTheme: "light", themes: { light, dark } },
    aliases: {
        VBtnPrimary: VBtn,
        VBtnSecondary: VBtn,
        VBtnTertiary: VBtn,
        VBtnDanger: VBtn,
        VInputTrz: VTextField,
        VSelectTrz: VSelect,
        VTextareaTrz: VTextarea,
        VAutocompleteTrz: VAutocomplete,
        VSwitchTrz: VSwitch,
        VCheckboxTrz: VCheckbox,
        VPanel: VCard,
        VDialogTrz: VDialog,
        VTableTrz: VDataTable,
    },
    defaults: {
        VBtn: { rounded: "lg" },
        VBtnPrimary: { color: "primary", variant: "flat", rounded: "lg" },
        VBtnSecondary: { variant: "outlined", rounded: "lg" },
        VBtnTertiary: { variant: "text", rounded: "lg" },
        VBtnDanger: { color: "error", variant: "flat", rounded: "lg" },

        VInputTrz: field,
        VSelectTrz: field,
        VTextareaTrz: { ...field, rows: 3, autoGrow: true },
        VAutocompleteTrz: field,
        VSwitchTrz: { color: "primary", inset: true, hideDetails: "auto", density: "compact" },
        VCheckboxTrz: { color: "primary", hideDetails: "auto", density: "compact" },

        VPanel: { flat: true, rounded: "lg", border: true },
        VDialogTrz: { maxWidth: 560 },
        VTableTrz: { density: "comfortable", hover: true, itemsPerPage: 25 },

        // Plain components keep sensible defaults too (used inside the header and by Vuetify internally).
        VTextField: field,
        VSelect: field,
        VTextarea: { ...field, rows: 3, autoGrow: true },
        VAutocomplete: field,
        VCard: { rounded: "lg" },
        VChip: { size: "small", rounded: "md" },
        VTooltip: { location: "top" },
        VMenu: { transition: "slide-y-transition" },
    },
});
