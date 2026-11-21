<template>
    <div class="tt-front">
        <Header v-if="!hideChrome" />
        <v-main>
            <router-view />
        </v-main>
        <Footer v-if="!hideChrome" />
        <CompareBar />
        <CartDrawer />
    </div>
</template>

<script>
import Header from "@/front/Header.vue";
import Footer from "@/front/Footer.vue";
import CompareBar from "@/front/components/CompareBar.vue";
import CartDrawer from "@/front/components/CartDrawer.vue";

export default {
    name: "FrontLayout",
    components: { Header, Footer, CompareBar, CartDrawer },
    computed: {
        hideChrome() {
            return !!this.$route.meta.hideChrome;
        },
    },
    created() {
        // A signed-in customer's wishlist lives on their account
        this.$store.dispatch("wishlist/sync");
    },
};
</script>
