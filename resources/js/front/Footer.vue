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
