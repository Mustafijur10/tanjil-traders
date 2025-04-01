<template>

<!-- <sidebar-menu
      :menu="menu"
      @update:collapsed="onToggleCollapse"
      v-model:collapsed="collapsed"
      :theme="menutheme"
      :show-one-child="true"
  >
      <template v-slot:header>
          <v-container
              class="align-self-center side-header"
              color="white"
              contain
          >
              <router-link
                  to="/"
                  tag="span"
                  style="cursor: pointer; text-align: center"
              >
                  <v-img
                      src="/images/logo.png"
                      max-width="150"
                      class="mx-auto"
                  ></v-img>
              </router-link>
          </v-container>
      </template>
  </sidebar-menu> -->
  <v-app :theme="theme" id="main" :class="{ collapsed: collapsed }">
      <v-header>
          <Header></Header>
      </v-header>
      <v-main>
          <v-container fluid>
              <router-view></router-view>
          </v-container>
      </v-main>
      <v-footer> </v-footer>
  </v-app>
  {{ checkUrl }}
</template>

<script setup>
  import Header from '@/front/Header.vue';
  import Footer from '@/front/Footer.vue';  
</script>

<script>

  export default {
    mounted(){

    },
    data() {
      return {
        collapsed: false,
            menutheme: "white-theme",

            isOnMobile: false,

            showsidebar: false,

            menu: [
                {
                    hiddenOnCollapse: true,
                },
                {
                    title: "Nav Item 1",
                    icon: "fa fa-edit",
                    child: [
                        {
                            href: "/dash-1",
                            title: "Nav Sub-Item 1",
                        },
                        {
                            href: "/dash-2",
                            title: "List",
                        },
                        {
                            href: "/dash-3",
                            title: "List Form",
                        },
                  
                    ],
                },
                {
                    href: "/dash-4",
                    title: "Nav Item 2",
                    icon: "fa fa-clipboard",
                },
                {
                    title: "Nav Item 3",
                    icon: "fa fa-bar-chart",
                    child: [
                        {
                            href: "/dash-5",
                            title: "Nav Sub-Item 1",
                        },
                        
                        
                    ],
                },
                {
                    title: "Nav Item 4",
                    icon: "fa fa-file",
                    href: "/dash-6",
                },
                {
                    href: "/dash-7",
                    title: "Nav Item 5",
                    icon: "fa fa-calculator",
                },
                {
                    href: "/dash-8",
                    title: "Nav Item 6",
                    icon: "fa fa-file",
                }
            
            ],
      };
    },
    methods:{
      
      themeSwitch() {
            theme.value = theme.value === "light" ? "dark" : "light";

            this.menutheme =
                this.menutheme === "white-theme" ? "" : "white-theme";

            window.localStorage.setItem("theme_mode", theme.value);
        },
        onToggleCollapse(collapsed) {
            this.collapsed = collapsed;
        },
    },
    created() {            
      
      

    },
    computed: {
      

    }
  };
</script>
<style scoped>
#main {
    /* padding-left: 290px; */
    -webkit-transition: 0.3s ease;
    transition: 0.3s ease;
    background-color: rgb(245, 245, 245);
}
#main.collapsed,
#main.onmobile {
    padding-left: 65px;
}

.v-footer {
    flex: initial;
}
</style>