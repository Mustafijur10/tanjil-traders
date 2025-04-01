import "./bootstrap";
import { createApp } from "vue";

import axios from "axios";
import VueAxios from "vue-axios";

import VueSidebarMenu from "vue-sidebar-menu";
import "vue-sidebar-menu/dist/vue-sidebar-menu.css";
import "@fortawesome/fontawesome-free/css/all.css";

import VueSweetalert2 from "vue-sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

// Vuetify
import "vuetify/styles";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import "@mdi/font/css/materialdesignicons.css";
import { VBtn, VTextField, VSelect, VTextarea } from 'vuetify/components';

const vuetify = createVuetify({
    aliases: {
        VBtnPrimary: VBtn,
        VBtnSecondary: VBtn,
        VBtnTertiary: VBtn,

        VInputTrz: VTextField,
        VSelectTrz: VSelect,
        VTextareaTrz: VTextarea,
    },
    theme: {
        themes: {
            light: {
                variables: {
                    
                }, 
                colors: {
                    // primary: "rgba(255, 110, 44, 1)",
                    primary: "#050c2e",
                    primary_lite: "rgba(255, 236, 227, 1)",
                    secondary: "rgba(51, 69, 122, 1)",
                    secondary_lite: "rgba(248, 249, 255, 1)",
                    white: "#ffffff",
                    grey: "rgb(245, 245, 245)",
                    greyheader: "rgba(168, 168, 168, 1)",
                    greymain: "#525252",
                    error: '#FF0000',
                    info: '#2196F3',
                    success: '#00A560',
                    success_lite: '#E5F6EF',
                    warning: '#FB8C00',
                    orange:'#FF6E2C',
                    customer_btn: '#AAC7FE',
                    purple: 'rgba(170, 178, 254, 1)',
                    purple_lite: '#F8F9FF',

                    header_blue: "#050c2e",
                    
                },
                variations: {
                    colors: ['primary', 'secondary'],
                    lighten: 1,
                    darken: 2,
                },
            },
        },
    },
    
    defaults: {
        VBtnPrimary: { color: 'primary', variant: 'flat', },
        VBtnSecondary: { color: 'secondary', variant: 'flat', },
        VBtnTertiary: { color: 'secondary', rounded: true, variant: 'plain', },

        VInputTrz: {density: "compact", variant: "outlined", },
        VSelectTrz: { density: "compact", variant: "outlined", },
        VTextareaTrz: { density: "compact", variant: "outlined", rows: "3",}
    },
    components,
    directives,
});

import Vue3EasyDataTable from "vue3-easy-data-table";
import "vue3-easy-data-table/dist/style.css";

import App from "@/app.vue";
import router from "@/router";
import store from "@/store";

const app = createApp(App);

app.use(vuetify);
app.use(VueAxios, axios);
app.use(store).use(router);
app.use(VueSidebarMenu);
app.use(VueSweetalert2);
app.component('EasyDataTable', Vue3EasyDataTable);

app.mount("#app");

if (axios.interceptors) {
    axios.interceptors.response.use(
        function (response) {
            return response;
        },
        function (error) {
            if (error.response.status == 401) {
                //location.reload();
                router.push("/admin/login");
            } else if (error.response.status == 422) {
                Swal.fire({
                    position: "bottom-end",
                    icon: "warning",
                    title: Object.values(error.response.data.errors),
                    showConfirmButton: false,
                    timer: 2000,
                });
                //Swal.fire('Please fill all required inputs', '', 'warning');
            } else if (error.response.status == 500) {
                Swal.fire({
                    position: "bottom-end",
                    icon: "error",
                    title: "Server error.",
                    showConfirmButton: false,
                    timer: 2000,
                });
            } else if (
                error.response.status == 419 ||
                error.response.status == 401
            )
                location.reload();
        }
    );
}

window.Rules = {
    required: (value) => !!value || "This field is required",
    email: (value) => /.+@.+\..+/.test(value) || "Must be a valid email",
    integer: (value) =>
        Number.isInteger(Number(value)) || "Must be a valid number",
    max(value, max) {
        return (
            (value || "").length <= max ||
            `This field is too long (maximum is ${max} characters).`
        );
    },
    min(value, min) {
        return (
            (value || "").length >= min ||
            `This field is too short (minimum is ${min} characters).`
        );
    },
};
