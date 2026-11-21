<template>
    <div class="tt-wrap tt-page">
        <div class="tt-cauth">
            <section class="tt-page-card tt-cauth-card">
                <div class="tt-segments tt-cauth-tabs" role="tablist">
                    <button type="button" role="tab" :class="{ 'is-on': isLogin }" :aria-selected="isLogin" @click="go('/login')">{{ $t("Sign in") }}</button>
                    <button type="button" role="tab" :class="{ 'is-on': !isLogin }" :aria-selected="!isLogin" @click="go('/register')">{{ $t("Create account") }}</button>
                </div>

                <h1>{{ $t(isLogin ? "Welcome back" : "Create your account") }}</h1>
                <p class="tt-muted">{{ $t(isLogin ? "Sign in with your phone number or email." : "Track orders, save products and check out faster.") }}</p>

                <v-form v-if="isLogin" @submit.prevent="login">
                    <v-input-trz v-model.trim="form.login" :label="$t('Phone or email')" prepend-inner-icon="mdi-account-outline" autocomplete="username" autofocus :error-messages="errors.login" class="mb-3" @update:model-value="errors.login = null" />
                    <v-input-trz
                        v-model="form.password"
                        :label="$t('Password')"
                        prepend-inner-icon="mdi-lock-outline"
                        :type="show ? 'text' : 'password'"
                        :append-inner-icon="show ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                        autocomplete="current-password"
                        :error-messages="errors.password"
                        @click:append-inner="show = !show"
                        @update:model-value="errors.password = null"
                    />
                    <div class="tt-auth-row">
                        <span></span>
                        <router-link to="/forgot-password">{{ $t("Forgot password?") }}</router-link>
                    </div>
                    <v-btn-primary type="submit" block size="large" :loading="loading" :disabled="!form.login || !form.password">{{ $t("Sign in") }}</v-btn-primary>
                </v-form>

                <v-form v-else @submit.prevent="register">
                    <v-input-trz v-model.trim="form.name" :label="$t('Full name')" prepend-inner-icon="mdi-account-outline" autocomplete="name" autofocus :error-messages="errors.name" class="mb-3" @update:model-value="errors.name = null" />
                    <v-input-trz v-model.trim="form.phone" :label="$t('Mobile number')" type="tel" inputmode="tel" placeholder="01XXXXXXXXX" prepend-inner-icon="mdi-phone-outline" autocomplete="tel" :error-messages="errors.phone" class="mb-3" @update:model-value="errors.phone = null" />
                    <v-input-trz v-model.trim="form.email" :label="$t('Email (optional)')" type="email" prepend-inner-icon="mdi-email-outline" autocomplete="email" :error-messages="errors.email" class="mb-3" @update:model-value="errors.email = null" />
                    <v-input-trz
                        v-model="form.password"
                        :label="$t('Password')"
                        prepend-inner-icon="mdi-lock-outline"
                        :type="show ? 'text' : 'password'"
                        :append-inner-icon="show ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                        autocomplete="new-password"
                        :hint="$t('At least 8 characters')"
                        :error-messages="errors.password"
                        class="mb-3"
                        @click:append-inner="show = !show"
                        @update:model-value="errors.password = null"
                    />
                    <v-input-trz v-model="form.password_confirmation" :label="$t('Confirm password')" prepend-inner-icon="mdi-lock-check-outline" :type="show ? 'text' : 'password'" autocomplete="new-password" class="mb-4" />
                    <v-btn-primary type="submit" block size="large" :loading="loading" :disabled="!form.name || !form.phone || !form.password">{{ $t("Create account") }}</v-btn-primary>
                </v-form>
            </section>

            <aside class="tt-cauth-side">
                <h2>{{ $t("Why create an account?") }}</h2>
                <ul>
                    <li><v-icon size="22">mdi-map-marker-path</v-icon><span><b>{{ $t("Track every order") }}</b>{{ $t("See where your parcel is, any time.") }}</span></li>
                    <li><v-icon size="22">mdi-heart-outline</v-icon><span><b>{{ $t("Save products") }}</b>{{ $t("Keep a wishlist on all your devices.") }}</span></li>
                    <li><v-icon size="22">mdi-lightning-bolt-outline</v-icon><span><b>{{ $t("Faster checkout") }}</b>{{ $t("Your address is filled in for you.") }}</span></li>
                    <li><v-icon size="22">mdi-keyboard-return</v-icon><span><b>{{ $t("Easy returns") }}</b>{{ $t("Ask for a return from your order page.") }}</span></li>
                </ul>
            </aside>
        </div>
    </div>
</template>

<script>
import { fieldErrors } from "@/api";

export default {
    name: "CustomerAuth",
    data: () => ({
        form: { login: "", password: "", name: "", phone: "", email: "", password_confirmation: "" },
        errors: {},
        loading: false,
        show: false,
    }),
    computed: {
        isLogin() {
            return this.$route.path === "/login";
        },
    },
    methods: {
        go(path) {
            this.errors = {};
            this.$router.replace({ path, query: this.$route.query });
        },
        async login() {
            await this.run(() => this.$store.dispatch("auth/login", { login: this.form.login, password: this.form.password }));
        },
        async register() {
            if (this.form.password !== this.form.password_confirmation) {
                this.errors = { password: this.$t("The two passwords do not match.") };
                return;
            }
            const { name, phone, email, password, password_confirmation } = this.form;
            await this.run(() => this.$store.dispatch("auth/register", { name, phone, email: email || null, password, password_confirmation }));
        },
        async run(action) {
            this.loading = true;
            this.errors = {};
            try {
                const user = await action();
                await this.$store.dispatch("wishlist/sync");
                const target = this.$route.query.redirect;
                const staff = ["admin", "staff"].includes(user.role);
                this.$router.replace(typeof target === "string" && target.startsWith("/") && !target.startsWith("//") ? target : staff ? "/admin" : "/account");
            } catch (e) {
                this.errors = fieldErrors(e);
                if (!Object.keys(this.errors).length) this.$store.dispatch("notify", { text: this.$t(e.response?.data?.message || "Something went wrong. Please try again."), type: "error" });
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
