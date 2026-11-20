<template>
    <div class="tt-front">
        <Header v-if="!hideChrome" />
        <v-main>
            <router-view />
        </v-main>
        <Footer v-if="!hideChrome" />
    </div>
</template>

<script>
import Header from "@/front/Header.vue";
import Footer from "@/front/Footer.vue";

export default {
    name: "FrontLayout",
    components: { Header, Footer },
    computed: {
        hideChrome() {
            return !!this.$route.meta.hideChrome;
        },
    },
};
</script>
