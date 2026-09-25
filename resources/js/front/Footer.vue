<template>
  <footer class="tt-footer">
    <div class="footer-top">
      <div class="wrap">
        <v-row>
          <!-- brand -->
          <v-col cols="12" md="3">
            <div class="footer-brand">
              <span class="footer-brand__mark"><v-icon icon="mdi-flash" size="18" /></span>
              <span class="footer-brand__text">Tanjil<em>Traders</em></span>
            </div>
            <p class="footer-lead">
              Your trusted source for electronics accessories in Bangladesh —
              cables, chargers, audio and more, all under official warranty.
            </p>
            <div class="footer-socials">
              <a v-for="s in socials" :key="s.icon" :href="s.href" class="social-btn">
                <v-icon :icon="s.icon" size="17" />
              </a>
            </div>
          </v-col>

          <!-- quick links -->
          <v-col cols="6" md="3">
            <div class="footer-head">Quick links</div>
            <a v-for="l in quickLinks" :key="l.label" :href="l.href" class="footer-link">{{ l.label }}</a>
          </v-col>

          <!-- categories -->
          <v-col cols="6" md="3">
            <div class="footer-head">Categories</div>
            <a v-for="c in categories" :key="c.label" :href="c.href" class="footer-link">{{ c.label }}</a>
          </v-col>

          <!-- contact -->
          <v-col cols="12" md="3">
            <div class="footer-head">Contact</div>
            <div class="footer-row">
              <v-icon icon="mdi-map-marker-outline" size="17" class="footer-row__ic" />
              <span>{{ address }}</span>
            </div>
            <div class="footer-row">
              <v-icon icon="mdi-phone-outline" size="17" class="footer-row__ic" />
              <a :href="'tel:' + phone" class="footer-link footer-link--inline">{{ phone }}</a>
            </div>
            <div class="footer-row">
              <v-icon icon="mdi-email-outline" size="17" class="footer-row__ic" />
              <a :href="'mailto:' + email" class="footer-link footer-link--inline">{{ email }}</a>
            </div>
          </v-col>
        </v-row>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="wrap footer-bottom__row">
        <span>© {{ year }} Tanjil Traders. All rights reserved.</span>
        <span class="footer-bottom__legal">
          <a href="/privacy">Privacy</a>
          <a href="/terms">Terms</a>
          <a href="/returns">Returns policy</a>
        </span>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'SiteFooter',

  data() {
    return {
      year: new Date().getFullYear(),
      phone: '01711 000 000',
      email: 'support@example.com',
      address: 'House 32, Road 8/A, Dhanmondi, Dhaka',

      socials: [
        { icon: 'mdi-facebook', href: '#' },
        { icon: 'mdi-instagram', href: '#' },
        { icon: 'mdi-youtube', href: '#' },
        { icon: 'mdi-linkedin', href: '#' },
      ],

      quickLinks: [
        { label: 'Home', href: '/' },
        { label: 'New arrivals', href: '/new' },
        { label: 'Best sellers', href: '/best-sellers' },
        { label: 'Track order', href: '/track-order' },
        { label: 'EMI guide', href: '/emi' },
        { label: 'Support', href: '/support' },
      ],

      categories: [
        { label: 'Cables & adapters', href: '/c/cables' },
        { label: 'Chargers & power', href: '/c/chargers' },
        { label: 'Audio & headphones', href: '/c/audio' },
        { label: 'Keyboard & mouse', href: '/c/keyboard-mouse' },
        { label: 'Storage & drives', href: '/c/storage' },
        { label: 'Networking', href: '/c/networking' },
      ],
    }
  },
}
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-footer {
  --deep: #053d35;
  --deep-2: #0a5548;
  --deep-3: #0b5f50;
  --green: #0f9d6b;
  --line: rgba(255, 255, 255, 0.12);

  font-family: Poppins, "Segoe UI", sans-serif;
  font-size: 14px;
}

.tt-footer a { text-decoration: none; }
.wrap { max-width: 1340px; margin: 0 auto; padding: 0 20px; }

.footer-top {
  background: linear-gradient(135deg, var(--deep) 0%, var(--deep-2) 55%, var(--deep-3) 100%);
  color: #fff;
  padding: 40px 0 26px;
}

.footer-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.footer-brand__mark {
  width: 32px; height: 32px; border-radius: 9px;
  background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22);
  display: grid; place-items: center; color: #6ee7b7; flex: none;
}
.footer-brand__text { font-weight: 700; font-size: 19px; letter-spacing: -0.02em; }
.footer-brand__text em { font-style: normal; font-weight: 400; color: #8fe3c6; }

.footer-lead { font-size: 12.8px; line-height: 1.7; color: rgba(255, 255, 255, 0.65); margin: 0 0 16px; }

.footer-socials { display: flex; gap: 8px; }
.social-btn {
  width: 32px; height: 32px; border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: grid; place-items: center; color: rgba(255, 255, 255, 0.75);
}
.social-btn:hover { background: var(--green); border-color: var(--green); color: #fff; }

.footer-head { font-size: 12px; font-weight: 600; letter-spacing: 0.3px; text-transform: uppercase; color: #8fe3c6; margin-bottom: 12px; }

.footer-link { display: block; font-size: 13.2px; line-height: 2.15; color: rgba(255, 255, 255, 0.7); }
.footer-link:hover { color: #fff; }
.footer-link--inline { display: inline; line-height: 1.5; }

.footer-row { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 11px; font-size: 12.8px; color: rgba(255, 255, 255, 0.7); line-height: 1.55; }
.footer-row__ic { color: #6ee7b7; margin-top: 1px; flex: none; }

.footer-bottom { background: #032a24; color: rgba(255, 255, 255, 0.55); padding: 14px 0; font-size: 12.5px; }
.footer-bottom__row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.footer-bottom__legal { display: flex; gap: 18px; }
.footer-bottom__legal a { color: rgba(255, 255, 255, 0.55); }
.footer-bottom__legal a:hover { color: #fff; }

@media (max-width: 640px) {
  .footer-bottom__row { flex-direction: column; text-align: center; }
}
</style>