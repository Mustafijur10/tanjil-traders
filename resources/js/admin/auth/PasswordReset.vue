<template>
    <AuthShell title="Choose a new password" lead="Use at least 8 characters. After saving, sign in with the new password.">
        <v-alert v-if="!token" type="warning" variant="tonal" class="mb-5" :text="$t('This reset link is incomplete. Please request a new one.')" />

        <template v-else-if="done">
            <v-alert type="success" variant="tonal" class="mb-5" :text="$t('Your password has been changed.')" />
            <v-btn-primary block size="large" to="/admin/login">{{ $t("Sign in") }}</v-btn-primary>
        </template>

        <v-form v-else @submit.prevent="submit">
            <v-input-trz :model-value="email" :label="$t('Email')" prepend-inner-icon="mdi-email-outline" readonly class="mb-4" />
            <v-input-trz
                v-model="password"
                :label="$t('New password')"
                prepend-inner-icon="mdi-lock-outline"
                type="password"
                autocomplete="new-password"
                :error-messages="errors.password || errors.email"
                class="mb-4"
            />
            <v-input-trz
                v-model="confirmation"
                :label="$t('Repeat new password')"
                prepend-inner-icon="mdi-lock-check-outline"
                type="password"
                autocomplete="new-password"
                :error-messages="mismatch ? $t('The two passwords do not match.') : null"
                class="mb-5"
            />
            <v-btn-primary type="submit" block size="large" :loading="loading" :disabled="password.length < 8 || mismatch">
                {{ $t("Save new password") }}
            </v-btn-primary>
        </v-form>

        <div class="tt-auth-foot">
            <router-link to="/admin/forgot-password">{{ $t("Request a new link") }}</router-link>
        </div>
    </AuthShell>
</template>

<script>
import AuthShell from "@/components/AuthShell.vue";
import api, { fieldErrors } from "@/api";

export default {
    name: "PasswordReset",
    components: { AuthShell },
    data() {
        return {
            token: this.$route.query.token || "",
            email: this.$route.query.email || "",
            password: "",
            confirmation: "",
            errors: {},
            loading: false,
            done: false,
        };
    },
    computed: {
        mismatch() {
            return this.confirmation.length > 0 && this.password !== this.confirmation;
        },
    },
    methods: {
        async submit() {
            this.loading = true;
            this.errors = {};
            try {
                await api.post(
                    "/password/reset",
                    { token: this.token, email: this.email, password: this.password, password_confirmation: this.confirmation },
                    { quiet: true }
                );
                this.done = true;
            } catch (e) {
                this.errors = fieldErrors(e);
                if (!Object.keys(this.errors).length) this.errors = { password: this.$t("Could not reset the password. The link may have expired.") };
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
