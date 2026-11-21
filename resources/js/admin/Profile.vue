<template>
    <div>
        <PageHeader title="My Profile" subtitle="Your name, sign-in details and password." />

        <div class="tt-detail">
            <div class="tt-detail-main">
                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-account-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Your details") }}</h2><p>{{ $t("You can sign in with either the email or the phone number.") }}</p></div>
                    </div>
                    <form class="tt-box-body tt-form-grid" @submit.prevent="save">
                        <v-input-trz v-model.trim="form.name" class="is-full" :label="$t('Name')" :error-messages="errors.name" />
                        <v-input-trz v-model.trim="form.email" type="email" :label="$t('Email')" :error-messages="errors.email" />
                        <v-input-trz v-model.trim="form.phone" :label="$t('Phone')" :error-messages="errors.phone" />
                        <div class="is-full"><v-btn-primary type="submit" :loading="saving === 'details'">{{ $t("Save details") }}</v-btn-primary></div>
                    </form>
                </section>

                <section class="tt-box">
                    <div class="tt-box-head">
                        <span class="tt-box-icon"><v-icon size="20">mdi-lock-outline</v-icon></span>
                        <div class="flex-grow-1"><h2>{{ $t("Password") }}</h2><p>{{ $t("Use at least 8 characters. Other devices stay signed in.") }}</p></div>
                    </div>
                    <form class="tt-box-body tt-form-grid" @submit.prevent="savePassword">
                        <v-input-trz v-model="pw.current_password" class="is-full" type="password" autocomplete="current-password" :label="$t('Current password')" :error-messages="errors.current_password" />
                        <v-input-trz v-model="pw.password" type="password" autocomplete="new-password" :label="$t('New password')" :error-messages="errors.password" />
                        <v-input-trz v-model="pw.password_confirmation" type="password" autocomplete="new-password" :label="$t('Confirm new password')" />
                        <div class="is-full"><v-btn-primary type="submit" :loading="saving === 'password'" :disabled="!pw.current_password || !pw.password">{{ $t("Change password") }}</v-btn-primary></div>
                    </form>
                </section>
            </div>
            <aside class="tt-detail-side">
                <section class="tt-box">
                    <div class="tt-box-body tt-profile-card">
                        <span class="tt-initials is-large">{{ initials }}</span>
                        <b>{{ user?.name }}</b>
                        <StatusChip :label="$t(user?.role === 'admin' ? 'Admin' : 'Staff')" :tone="user?.role === 'admin' ? 'green' : 'blue'" />
                        <small class="tt-muted">{{ $t("Member since {date}", { date: $date(user?.created_at) }) }}</small>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

<script>
import api, { fieldErrors } from "@/api";

export default {
    name: "Profile",
    data() {
        const u = this.$store.state.auth.user || {};
        return {
            form: { name: u.name || "", email: u.email || "", phone: u.phone || "" },
            pw: { current_password: "", password: "", password_confirmation: "" },
            errors: {},
            saving: null,
        };
    },
    computed: {
        user() {
            return this.$store.state.auth.user;
        },
        initials() {
            return (this.user?.name || "?").split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase();
        },
    },
    methods: {
        async send(body, key, message) {
            this.saving = key;
            this.errors = {};
            try {
                const { data } = await api.put("/me", body, { quiet: true });
                this.$store.commit("auth/setUser", data);
                this.$store.dispatch("notify", this.$t(message));
                return true;
            } catch (e) {
                this.errors = fieldErrors(e);
                return false;
            } finally {
                this.saving = null;
            }
        },
        save() {
            this.send({ ...this.form, email: this.form.email || null, phone: this.form.phone || null }, "details", "Your details are saved.");
        },
        async savePassword() {
            if (await this.send({ ...this.form, email: this.form.email || null, phone: this.form.phone || null, ...this.pw }, "password", "Password changed.")) {
                this.pw = { current_password: "", password: "", password_confirmation: "" };
            }
        },
    },
};
</script>
