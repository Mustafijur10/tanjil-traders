<template>
    <v-toolbar class="v-trz-toolbar-header bg-primary header-front">
        <v-menu bottom left offset-y origin="top right" transition="scale-transition">
            <template v-slot:activator="{ attrs, on }">

                <router-link to="/" style="cursor: pointer; text-align: left">
                    <v-img src="/images/logo.png" max-width="240" class="header-logo ml-3"></v-img>
                </router-link>

                <v-text-field :loading="loading" append-inner-icon="mdi-magnify" density="compact" label="Search Here..."
                    variant="outlined" hide-details single-line @click:append-inner="onClick" class="ml-5">
                </v-text-field>
 
            </template>

            <template>
                
            </template>
        </v-menu>

        <v-spacer />

        <v-menu open-on-hover>
            <template v-slot:activator="{ props }">
                <v-btn color="white" v-bind="props" class="ma-0 text-none text-left pl-1 pr-1 mr-2">
                    <v-icon color="white darken-2 header-icon">mdi-account-outline</v-icon>
                    <span class="pl-2"> 
                        <small style="font-size: 10px; color:#696973;">Welcome</small>
                        <br>
                        <span style="font-size: 12px;">Sign In/ Register</span>
                    </span>
                    
                </v-btn> 
                
            </template>            
        </v-menu>
        <v-menu open-on-hover>
            <template v-slot:activator="{ props }">
                <v-btn color="white" v-bind="props" class="ma-0 text-none text-left pl-1 pr-1 mr-2">                    
                    <v-icon color="white darken-2 header-icon">mdi-bell-badge-outline</v-icon>
                    <span class="pl-2"> 
                        <small style="font-size: 10px; color:#696973;">0 Notifications</small>
                        <br>
                        <span style="font-size: 12px;">Check Notifications</span>
                    </span>
                </v-btn>
            </template>            
        </v-menu>
        <v-menu open-on-hover>
            <template v-slot:activator="{ props }">
               <v-btn color="white" v-bind="props" class="ma-0 text-none text-left pl-1 pr-1">
                    <span class="pr-2"> 
                        <small style="font-size: 10px; color:#696973;">0 item(s)</small>
                        <br>
                        <span style="font-size: 12px;">Total: 0৳</span>
                    </span>
                    <v-icon color="white darken-2 header-icon">mdi-cart-variant</v-icon>
                </v-btn> 
            </template>            
        </v-menu>
    </v-toolbar>

</template>

<script setup>
    import Auth from "@/auth.js";

    import { ref } from 'vue'

  const loaded = ref(false)
  const loading = ref(false)

  function onClick() {
        loading.value = true
        setTimeout(() => {
        loading.value = false
        loaded.value = true
        }, 2000)
    }
</script>
<script>
    import { ref } from "vue";
    const theme = ref("light");
    export default {
        mounted() {
            theme.value = window.localStorage.getItem("theme_mode");
            if (theme.value == "light") this.menutheme = "white-theme";
            else this.menutheme = "";

            this.collapsed = window.localStorage.getItem("collapsed");
        },

        data() {
            return {
                menutheme: "",
                isOnMobile: false,
                showForm: false,
            };
        },
        methods: {
            themeSwitch() {
                theme.value = theme.value === "light" ? "dark" : "light";
                this.menutheme = this.menutheme === "white-theme" ? "" : "white-theme";
                this.$emit("update:counter", this.childCounter);
                window.localStorage.setItem("theme_mode", theme.value);
            },

            logout() {
                let userData = window.localStorage.getItem("user");

                this.axios
                    .post("/api/logout", userData)
                    .then(({ data }) => {
                        Auth.logout();
                        window.location.href = "/admin/login";
                    })
                    .catch((error) => {});
            },
            accountSettings() {
                this.showForm = true;
            },           
        },
    };
</script>

<style>

    .v-trz-toolbar-header .header-logo .v-img__img{
        position: relative !important;
    }

</style>