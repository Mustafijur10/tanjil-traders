<template>
    <AuthShell title="Reset your password" lead="Enter your account email. We will send you a link to choose a new password.">
        <v-alert v-if="sent" type="success" variant="tonal" class="mb-5" :text="$t('If this email has an account, a reset link has been sent. Check your inbox and spam folder.')" />

        <v-form v-else @submit.prevent="submit">
            <v-input-trz
                v-model.trim="email"
                type="email"
                :label="$t('Email')"
                prepend-inner-icon="mdi-email-outline"
                autocomplete="email"
                autofocus
                :error-messages="error"
                class="mb-5"
                @update:model-value="error = null"
            />
            <v-btn-primary type="submit" block size="large" :loading="loading" :disabled="!email">
                {{ $t("Send reset link") }}
            </v-btn-primary>
        </v-form>

        <div class="tt-auth-foot">
            <router-link to="/admin/login">{{ $t("Back to sign in") }}</router-link>
        </div>
    </AuthShell>
</template>

<script>
import AuthShell from "@/components/AuthShell.vue";
import api, { fieldErrors } from "@/api";

export default {
    name: "ForgotPassword",
    components: { AuthShell },
    data: () => ({ email: "", error: null, loading: false, sent: false }),
    methods: {
        async submit() {
            this.loading = true;
            try {
                await api.post("/password/forgot", { email: this.email }, { quiet: true });
                this.sent = true;
            } catch (e) {
                this.error = fieldErrors(e).email || this.$t("Could not send the link. Please try again.");
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
