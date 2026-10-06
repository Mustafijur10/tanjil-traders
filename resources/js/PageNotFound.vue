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

<style scoped>
/*
  Optional: add once in index.html / app blade for the exact typeface
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;800&display=swap" rel="stylesheet">
*/
.nf {
  --nf-bg: #060914;
  --nf-text: #f4f6fa;
  --nf-muted: #8a909c;
  --nf-border: rgba(255, 255, 255, 0.12);
  --nf-teal: #6fc2a8;
  --nf-teal-deep: #4f9d87;
  --nf-amber: #f59e0b;

  /* Pinned to the viewport so it always fills the window and never scrolls the page */
  position: fixed;
  inset: 0;
  z-index: 10;
  width: 100%;
  height: 100vh;
  height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 20px;
  overflow: hidden;
  box-sizing: border-box;
  background: var(--nf-bg);
  color: var(--nf-text);
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  text-align: center;
}

/* ---------- Background ---------- */
.nf__glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  pointer-events: none;
}
.nf__glow--teal {
  top: -15%;
  left: -8%;
  width: 60vw;
  height: 130%;
  background: radial-gradient(closest-side, rgba(22, 105, 90, 0.85), rgba(22, 105, 90, 0));
}
.nf__glow--amber {
  right: -10%;
  top: 5%;
  width: 50vw;
  height: 100%;
  background: radial-gradient(closest-side, rgba(135, 105, 35, 0.75), rgba(135, 105, 35, 0));
}
/* Square grid pattern over the whole background */
.nf__grid {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 0;
  pointer-events: none;
  opacity: 1;
  background-color: transparent;
  background-image:
    linear-gradient(to bottom, rgba(255, 255, 255, 0.055) 0, rgba(255, 255, 255, 0.055) 1px, transparent 1px),
    linear-gradient(to right, rgba(255, 255, 255, 0.055) 0, rgba(255, 255, 255, 0.055) 1px, transparent 1px);
  background-size: 48px 48px, 48px 48px;
  background-repeat: repeat, repeat;
  background-position: 0 0, 0 0;

  /* Gradient fade: grid is clear in the center and dissolves toward the edges */
  -webkit-mask-image: radial-gradient(ellipse 50% 45% at 50% 50%, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.3) 50%, transparent 100%);
  mask-image: radial-gradient(ellipse 50% 45% at 50% 50%, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.3) 50%, transparent 100%);
}

/* ---------- Content ---------- */
.nf__content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 520px;
}

.nf__badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 28px;
  padding: 8px 16px;
  border: 1px solid var(--nf-border);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.04);
  color: #b4b9c4;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}
.nf__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--nf-amber);
  box-shadow: 0 0 8px rgba(245, 158, 11, 0.7);
}

.nf__code {
  margin: 0;
  font-size: clamp(88px, 15vw, 204px);
  font-weight: 800;
  line-height: 0.95;
  letter-spacing: -0.03em;
  background: linear-gradient(180deg, var(--nf-teal) 0%, var(--nf-teal-deep) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  color: transparent;
}

.nf__text {
  max-width: 30em;
  margin: 20px 0 28px;
  color: var(--nf-muted);
  font-size: 15px;
  line-height: 1.65;
}

/* ---------- Buttons ---------- */
.nf__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}
.nf__btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 42px;
  padding: 0 26px;
  border-radius: 4px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  line-height: 1;
  text-decoration: none;
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}
.nf__btn--primary {
  border: 1px solid #fff;
  background: #fff;
  color: #0b1020;
}
.nf__btn--primary:hover {
  background: #e8ecf2;
}
.nf__btn--ghost {
  border: 1px solid var(--nf-border);
  background: transparent;
  color: var(--nf-text);
}
.nf__btn--ghost:hover {
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.05);
}
.nf__btn:active {
  transform: translateY(1px);
}
.nf__icon {
  width: 16px;
  height: 16px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

/* ---------- Help links ---------- */
.nf__help {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin: 32px 0 0;
  font-size: 13.5px;
}
.nf__help-label {
  color: #6b717d;
}
.nf__link {
  color: var(--nf-text);
  font-weight: 600;
  text-decoration: none;
  transition: color 0.2s ease;
}
.nf__link:hover {
  color: var(--nf-teal);
}
.nf__sep {
  width: 1px;
  height: 16px;
  background: var(--nf-border);
}

/* ---------- Keyboard focus ---------- */
.nf__btn:focus-visible,
.nf__link:focus-visible {
  outline: 2px solid var(--nf-teal);
  outline-offset: 3px;
  border-radius: 4px;
}

/* ---------- Small screens ---------- */
@media (max-width: 599px) {
  .nf {
    overflow-y: auto;
  }
  .nf__actions {
    flex-direction: column;
    width: 100%;
  }
  .nf__btn {
    justify-content: center;
    width: 100%;
    height: 46px;
  }
  .nf__help {
    margin-top: 24px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .nf__btn,
  .nf__link {
    transition: none;
  }
}
</style>