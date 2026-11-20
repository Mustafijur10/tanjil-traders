<template>
  <main class="nf" role="main">
    <!-- Decorative background -->
    <div class="nf__glow nf__glow--teal" aria-hidden="true"></div>
    <div class="nf__glow nf__glow--amber" aria-hidden="true"></div>
    <div class="nf__grid" aria-hidden="true"></div>

    <div class="nf__content">
      <p class="nf__badge">
        <span class="nf__dot" aria-hidden="true"></span>
        404 · Route not found
      </p>

      <h1 class="nf__code" aria-label="Error 404">404</h1>

      <p class="nf__text">
        Nothing lives at this address anymore. It may have been renamed,
        moved, or never existed at all.
      </p>

      <div class="nf__actions">
        <router-link :to="homeRoute" class="nf__btn nf__btn--primary">
          <svg class="nf__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 10.5 12 3l9 7.5" />
            <path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5" />
          </svg>
          Back to Dashboard
        </router-link>

        <button type="button" class="nf__btn nf__btn--ghost" @click="goBack">
          <svg class="nf__icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M19 12H5" />
            <path d="m12 19-7-7 7-7" />
          </svg>
          Go Back
        </button>
      </div>

      <p class="nf__help">
        <span class="nf__help-label">Still stuck?</span>
        <router-link :to="helpRoute" class="nf__link">Help Center</router-link>
        <span class="nf__sep" aria-hidden="true"></span>
        <router-link :to="supportRoute" class="nf__link">Contact Support</router-link>
      </p>
    </div>
  </main>
</template>

<script>
export default {
  name: 'PageNotFound',

  props: {
    homeRoute: { type: [String, Object], default: '/admin' },
    helpRoute: { type: [String, Object], default: '/admin/settings' },
    supportRoute: { type: [String, Object], default: '/admin/settings' }
  },

  methods: {
    goBack() {
      if (window.history.length > 1) {
        this.$router.back()
      } else {
        this.$router.push(this.homeRoute)
      }
    }
  }
}
</script>
