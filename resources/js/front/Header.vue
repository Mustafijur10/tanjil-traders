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

    <v-toolbar class="v-trz-toolbar-header-2 bg-primary header-front">

            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Camera </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>

            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Desktop </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in desktopCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Laptop </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in laptopCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Component </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Monitor </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> UPS </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Phone </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Tablet </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Office Equipment </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Security </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Networking </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Software </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Server & Storage </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Accessories </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Gadget </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Gaming </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> TV </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
            </v-menu>
            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-3 text-decoration-none text-white text-subtitle-2 nav-item"> Appliance </router-link>
                </template>                
                <v-list class="rounded-l pa-2 bg-primary toolbar-header mt-2">
                    <v-list-item v-for="(category, index) in cameraCategories" :key="index">
                        <v-list-item-title class="d-flex align-center">
                            <router-link :to="`/camera/${category.slug}`" class="ml-3 text-white text-subtitle-2 text-decoration-none">
                                {{ category.name }} <span class="mdi mdi-chevron-right text-right"></span>
                            </router-link>
                        </v-list-item-title>
                    </v-list-item>            
                </v-list>
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

                cameraCategories: [
                    { name: "Action Camera", slug: "action-camera" },
                    { name: "Camera Lenses", slug: "camera-lenses" },
                    { name: "Digital Camera", slug: "digital-camera" },
                    { name: "DSLR", slug: "dslr" },
                    { name: "Handycam", slug: "handycam" },
                    { name: "Mirrorless Camera", slug: "mirrorless-camera" },
                    { name: "Dash Camera", slug: "dash-camera" },
                    { name: "Video Camera", slug: "video-camera" },
                    { name: "Instant Camera", slug: "instant-camera" },
                    { name: "Camera Accessories", slug: "camera-accessories" },
                    { name: "Camera Tripod", slug: "camera-tripod" },
                    { name: "Show all Camera", slug: "all-cameras" }
                ],

                desktopCategories: [
                    { name: "Desktop Offer", slug: "special-pc" },
                    { name: "Star PC", slug: "star-pc" },
                    { name: "Gaming PC", slug: "gaming-pc" },
                    { name: "Brand PC", slug: "brand-pc" },
                    { name: "All-in-One-PC", slug: "all-in-one-pc" },
                    { name: "Apple MAC Mini", slug: "apple-mac-mini" },
                    { name: "Apple iMac", slug: "apple-imac" },
                    { name: "Apple Mac Studio", slug: "apple-mac-studio" },
                    { name: "Apple Mac Pro", slug: "apple-mac-pro" },
                    { name: "Show All Desktop", slug: "show-all-desktop" },                   
                ],

                laptopCategories: [
                    { name: "All Laptop", slug: "all-laptop" },
                    { name: "Gaming Laptop", slug: "gaming-laptop" },
                    { name: "Premium Ultrabook", slug: "premium-ultrabook" },
                    { name: "Laptop Bag", slug: "laptop-bag" },
                    { name: "Laptop Accessories", slug: "laptop-accessories" },
                    { name: "Show All Laptop", slug: "show-all-laptop" },                                  
                ],
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