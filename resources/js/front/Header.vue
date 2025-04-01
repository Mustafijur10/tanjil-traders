<template>
    <v-toolbar class="v-trz-toolbar-header bg-primary header-front">
        <v-menu bottom left offset-y origin="top right" transition="scale-transition">
            <template v-slot:activator="{ attrs, on }">

                <router-link to="/" style="cursor: pointer; text-align: left">
                    <v-img src="/images/logo.png" max-width="240" class="header-logo ml-3"></v-img>
                </router-link>

                <v-text-field :loading="loading" append-inner-icon="mdi-magnify" density="compact" label="Search..."
                    variant="outlined" hide-details single-line @click:append-inner="onClick" class="ml-5">
                </v-text-field>
 
            </template>

            <template>
                
            </template>
        </v-menu>

        <!-- <span class="mx-auto">

            <v-menu open-on-hover >
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-5 text-decoration-none text-link_text text-subtitle-2"> Camera </router-link>
                </template>
                <v-list class="rounded-xl mt-7 pa-2">
                    <v-list-item class="mt-3 mb-2">
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cv-maker.webp" width="120" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Action Camera</span>
                        </v-list-item-title> 
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cover-letter-builder.webp" width="120" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Camera Lenses</span>
                        </v-list-item-title> 
                       
                        <VBtnDropdown class="font-weight-bold text-primary" block append-icon="mdi-arrow-right" >Make My CV Now</VBtnDropdown>             
                    </v-list-item>
                    
                </v-list>
            </v-menu>

            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-5 text-decoration-none text-link_text text-subtitle-2"> Templates </router-link>
                </template>
                <v-list class="rounded-xl mt-7 pa-3">
                    <v-list-item>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cv-templates.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">CV Templates</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cover-letter-template.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Cover Letter Templates</span>
                        </v-list-item-title> 
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/resignation-letter.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Resignation Letter Templates</span>
                        </v-list-item-title> 
                                                         
                    </v-list-item>
                    <VBtnDropdown class="font-weight-bold text-primary" append-icon="mdi-arrow-right" block >Make My CV Now</VBtnDropdown>
                    
                </v-list>
            </v-menu>

            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-5 text-decoration-none text-link_text text-subtitle-2"> Examples </router-link>
                </template>
                <v-list class="rounded-xl mt-7 pa-3">
                    <v-list-item>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cv-templates.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">CV Examples</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cover-letter-examples.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Cover Letter Examples</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cv-personal-profile-examples.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">CV Personal Profile Templates</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cv-summary-examples.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">CV Summary Templates</span>
                        </v-list-item-title>
                        
                        
                    </v-list-item>
                    <VBtnDropdown class="font-weight-bold text-primary" append-icon="mdi-arrow-right" block>Make My CV Now</VBtnDropdown>            
                    
                </v-list>
            </v-menu>

            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-5 text-decoration-none text-link_text text-subtitle-2"> CV Writing </router-link>
                </template>

                <v-list class="rounded-xl mt-7 pa-3">
                    <v-list-item>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/how-to-write-a-cv.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">How to Write a CV</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/how-to-lay-out-a-cv.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Best CV Format for 2024</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/top-skills-for-your-cv.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Top Skills for Your CV</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/23-personal-profile-examples.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">CV Personal Statement Examples</span>
                        </v-list-item-title>
                        
                        
                    </v-list-item>
                    <VBtnDropdown class="font-weight-bold text-primary" append-icon="mdi-arrow-right" block>Make My CV Now</VBtnDropdown>            
                    
                </v-list>
            </v-menu>

            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-5 text-decoration-none text-link_text text-subtitle-2"> Cover Letter Writting </router-link>
                </template>

                <v-list class="rounded-xl mt-7 pa-3">
                    <v-list-item>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/how-to-write-a-cover-letter.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">How to Write a Cover Letter</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/how-to-start-a-cover-letter.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Starting a Cover Letter</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/how-to-format-a-cover-letter.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Cover Letter Formatting</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/what-is-a-cover-letter.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">What Is a Cover Letter</span>
                        </v-list-item-title>
                        
                             
                    </v-list-item>
                    <VBtnDropdown class="font-weight-bold text-primary" append-icon="mdi-arrow-right" block>Make My CV Now</VBtnDropdown>       
                    
                </v-list>
            </v-menu>

            <v-menu open-on-hover>
                <template v-slot:activator="{ props }">
                    <router-link v-bind="props" to="/" class="ml-5 text-decoration-none text-link_text text-subtitle-2"> Resources </router-link>
                </template>

                <v-list class="rounded-xl mt-7 pa-3">
                    <v-list-item>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/blog.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Blog</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cv-help.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">CV Help</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/cover-letter-help.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Cover Letter Help</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/Interview-help.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Interview Help</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/career-help.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">Career Advice</span>
                        </v-list-item-title>
                        <v-list-item-title class="d-flex align-center mb-5"> 
                            <img src="/images/faqs.svg" width="25" class="rounded-lg" />
                            <span class="ml-3 text-subtitle-2">FAQ's</span>
                        </v-list-item-title>
                        
                             
                    </v-list-item>
                    <VBtnDropdown class="font-weight-bold text-primary" append-icon="mdi-arrow-right" block>Make My CV Now</VBtnDropdown>      
                    
                </v-list>
            </v-menu>
           
        </span> -->
        
        <!-- <v-divider class="ml-5 divider-height my-auto" vertical></v-divider> -->

        <v-spacer />

        <!-- <v-menu bottom left offset-y origin="top right" transition="scale-transition">
            <template v-slot:activator="{ attrs, on }">
                <v-btn @click="themeSwitch" class="ml-2" min-width="0" color="primary" v-on="on">
                    <v-icon>
                        <template v-if="theme === 'light'" >mdi-weather-sunny</template>
                        <template v-else>mdi-weather-night</template>
                    </v-icon>
                </v-btn>
            </template>
        </v-menu> -->
          <v-menu open-on-hover>
            <template v-slot:activator="{ props }">
               <v-btn color="primary" v-bind="props" class="ma-0">
                    <v-icon color="orange darken-2">mdi-account</v-icon>
                </v-btn> 
            </template>
            <v-list>
                <v-list-item prepend-icon="mdi-account" title="Account Settings" link @click="$router.push('/admin/account/settings')"></v-list-item>
                <v-list-item prepend-icon="mdi-logout" @click="logout" title="Logout"></v-list-item>
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