<template>
    <AuthShell title="Sign in to the admin panel" lead="Use the email or phone number of your admin or staff account.">
        <v-form @submit.prevent="submit">
            <v-input-trz
                v-model.trim="form.login"
                :label="$t('Email or phone')"
                prepend-inner-icon="mdi-account-outline"
                autocomplete="username"
                autofocus
                :error-messages="errors.login"
                class="mb-4"
                @update:model-value="errors.login = null"
            />
            <v-input-trz
                v-model="form.password"
                :label="$t('Password')"
                prepend-inner-icon="mdi-lock-outline"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                :error-messages="errors.password"
                @click:append-inner="showPassword = !showPassword"
                @update:model-value="errors.password = null"
            />
            <div class="tt-auth-row">
                <span></span>
                <router-link to="/admin/forgot-password">{{ $t("Forgot password?") }}</router-link>
            </div>
            <v-btn-primary type="submit" block size="large" :loading="loading" :disabled="!form.login || !form.password">
                {{ $t("Sign in") }}
            </v-btn-primary>
        </v-form>
        <div class="tt-auth-foot">
            <router-link to="/">{{ $t("Back to the store") }}</router-link>
        </div>
    </AuthShell>
</template>

<script>
import AuthShell from "@/components/AuthShell.vue";
import { fieldErrors } from "@/api";

export default {
    name: "AdminLogin",
    components: { AuthShell },
    data: () => ({
        form: { login: "", password: "" },
        errors: {},
        loading: false,
        showPassword: false,
    }),
    methods: {
        async submit() {
            this.loading = true;
            this.errors = {};
            try {
                await this.$store.dispatch("auth/login", { ...this.form, admin: true });
                const target = this.$route.query.redirect;
                this.$router.replace(typeof target === "string" && target.startsWith("/admin") ? target : "/admin");
            } catch (e) {
                this.errors = fieldErrors(e);
                if (!Object.keys(this.errors).length) {
                    this.errors = { login: this.$t("Could not sign in. Please try again.") };
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
