<template>
    <div class="d-flex align-center ga-1">
        <v-btn-tertiary
            v-if="showLanguage"
            size="small"
            class="px-2"
            :aria-label="$t('Change language')"
            @click="$store.dispatch('prefs/setLocale', locale === 'bn' ? 'en' : 'bn')"
        >
            <v-icon start size="18">mdi-translate</v-icon>
            {{ locale === "bn" ? "English" : "বাংলা" }}
        </v-btn-tertiary>
        <v-btn-tertiary
            v-if="showTheme"
            icon
            size="small"
            :aria-label="$t(isDark ? 'Switch to light mode' : 'Switch to dark mode')"
            @click="$store.dispatch('prefs/toggleTheme')"
        >
            <v-icon size="20">{{ isDark ? "mdi-white-balance-sunny" : "mdi-weather-night" }}</v-icon>
        </v-btn-tertiary>
    </div>
</template>

<script>
/* Language and light/dark buttons. Respect the store's "allow switching" settings unless `always` is set (admin). */
export default {
    name: "PrefToggles",
    props: {
        always: { type: Boolean, default: false },
    },
    computed: {
        locale() {
            return this.$store.state.prefs.locale;
        },
        isDark() {
            return this.$store.getters["prefs/isDark"];
        },
        showTheme() {
            return this.always || this.$store.getters["prefs/canSwitchTheme"];
        },
        showLanguage() {
            return this.always || this.$store.getters["prefs/canSwitchLanguage"];
        },
    },
};
</script>
