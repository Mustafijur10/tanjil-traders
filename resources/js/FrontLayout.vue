<template>
  <v-app :theme="theme" id="main" :class="{ collapsed: collapsed }">
      <v-header>
          <Header></Header>
      </v-header>
      <v-main>
        <div class="bg-for_marquee text-subtitle-2">
            <marquee-text :duration="15" :repeat="2">
                Eid Mubarak! Due to the Eid Holiday, our online activities and some outlets are out of operation. For Eid holiday schedule please visit our contact page. For more details please call. We will be open after vacation.&nbsp;
            </marquee-text>
        </div>
        <v-container fluid>
        
            <router-view></router-view>
        </v-container>
      </v-main>
      <Footer> </Footer>
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
    created() 
    {            
      
    },
    computed: 
    {        
      
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
#main.collapsed, #main.onmobile {
    padding-left: 65px;
}

.v-footer {
    flex: initial;
}
</style>