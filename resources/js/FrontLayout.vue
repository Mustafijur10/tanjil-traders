<template>
    <div class="tt-front">
        <Header v-if="!hideChrome" />
        <v-main>
            <router-view />
        </v-main>
        <Footer v-if="!hideChrome" />
        <CompareBar />
    </div>
</template>

<script>
import Header from "@/front/Header.vue";
import Footer from "@/front/Footer.vue";
import CompareBar from "@/front/components/CompareBar.vue";

export default {
    name: "FrontLayout",
    components: { Header, Footer, CompareBar },
    computed: {
        hideChrome() {
            return !!this.$route.meta.hideChrome;
        },
    },
};
</script>
