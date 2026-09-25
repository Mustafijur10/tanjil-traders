<template>
  <div class="tt-home">
    <!-- ═════════ Hero + rail ═════════ -->
    <div class="wrap">
      <div class="hero-row">
        <aside class="sidemenu">
          <a v-for="c in categories" :key="c.slug" :href="'/c/' + c.slug" class="sidemenu__item">
            <v-icon :icon="c.icon" size="18" />
            <span>{{ c.name }}</span>
            <v-icon v-if="c.children" icon="mdi-chevron-right" size="16" class="sidemenu__chev" />
          </a>
          <a href="/clearance" class="sidemenu__item sidemenu__item--accent">
            <v-icon icon="mdi-tag-multiple-outline" size="18" />
            <span>Clearance</span>
          </a>
        </aside>

        <div class="hero">
          <v-carousel v-model="slide" cycle interval="6000" height="100%" hide-delimiters :show-arrows="false" class="slider">
            <v-carousel-item v-for="(s, i) in slides" :key="i">
              <div class="slide">
                <div class="slide__copy">
                  <span class="slide__eyebrow">{{ s.eyebrow }}</span>
                  <h1 class="slide__title">{{ s.title }}</h1>
                  <p class="slide__text">{{ s.text }}</p>
                  <div>
                    <v-btn flat class="btn text-none" :href="s.href">{{ s.cta }}</v-btn>
                    <v-btn variant="outlined" class="btn btn--ghost text-none" href="/compare">Compare models</v-btn>
                  </div>
                </div>
              </div>
            </v-carousel-item>
          </v-carousel>
          <div class="hero-line"></div>

          <div class="hero-strip">
            <div v-for="h in heroStats" :key="h.label" class="hero-stat">
              <div class="hero-stat__icon"><v-icon :icon="h.icon" size="17" color="white" /></div>
              <div>
                <div class="hero-stat__value">{{ h.value }}</div>
                <div class="hero-stat__label">{{ h.label }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="rail">
          <div class="tt-card tt-green compare">
            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Compare products</span></div>
            <div class="tt-body">
              <div class="tt-sub mb-2">Pick two items to see specs side by side</div>
              <v-autocomplete v-model="compareA" :items="productNames" placeholder="First product" prepend-inner-icon="mdi-magnify" variant="solo" flat density="compact" hide-details hide-no-data class="compare__field" />
              <div class="compare__vs">VS</div>
              <v-autocomplete v-model="compareB" :items="productNames" placeholder="Second product" prepend-inner-icon="mdi-magnify" variant="solo" flat density="compact" hide-details hide-no-data class="compare__field" />
              <v-btn block flat class="compare__btn text-none" :disabled="!compareA || !compareB" @click="goCompare">View comparison</v-btn>
            </div>
          </div>

          <a v-for="t in tools" :key="t.title" :href="t.href" class="tool">
            <span class="tool__ic"><v-icon :icon="t.icon" size="20" /></span>
            <div>
              <b>{{ t.title }}</b>
              <span>{{ t.sub }}</span>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- ═════════ Trust strip ═════════ -->
    <div class="trust">
      <div class="wrap">
        <div class="trust__grid">
          <div v-for="s in services" :key="s.title" class="trust__item">
            <v-icon :icon="s.icon" size="24" />
            <div>
              <b>{{ s.title }}</b>
              <span>{{ s.sub }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="wrap">
      <!-- ═════════ Categories ═════════ -->
      <div class="tt-section"><v-icon size="16">mdi-view-grid-outline</v-icon><span>Shop by category</span></div>
      <v-card flat class="tt-card tt-blue">
        <div class="tt-head">
          <span class="tt-dot"></span><span class="tt-card-title">All categories</span>
          <v-spacer />
          <span class="tt-tag">{{ money(totalProducts) }} products in stock</span>
        </div>
        <div class="tt-body">
          <div class="cats">
            <a v-for="c in categories" :key="c.slug" :href="'/c/' + c.slug" class="cat">
              <span class="cat__ic"><v-icon :icon="c.icon" size="21" /></span>
              <b>{{ c.short }}</b>
              <span class="cat__count">{{ c.count }} items</span>
            </a>
          </div>
        </div>
      </v-card>

      <!-- ═════════ Deals ═════════ -->
      <div class="tt-section"><v-icon size="16">mdi-flash</v-icon><span>Deals of the day</span></div>
      <v-card flat class="tt-card tt-amber">
        <div class="tt-head">
          <span class="tt-dot"></span><span class="tt-card-title">Fresh prices every midnight</span>
          <v-spacer />
          <span class="tt-tag warn">
            <v-icon size="12">mdi-clock-outline</v-icon>
            {{ pad(countdown.h) }}:{{ pad(countdown.m) }}:{{ pad(countdown.s) }}
          </span>
        </div>
        <div class="tt-body">
          <div class="deals-rail">
            <article v-for="p in deals" :key="p.id" class="card">
              <div class="card__media">
                <span class="card__tag">-{{ discount(p) }}%</span>
                <img :src="p.image" :alt="p.name" loading="lazy" />
              </div>
              <div class="card__body">
                <div class="rating"><span class="rating__stars">{{ stars(p.rating) }}</span><span>{{ p.reviews }}</span></div>
                <a :href="p.href" class="card__name">{{ p.name }}</a>
                <div class="card__price">
                  <span class="now">৳{{ money(p.price) }}</span>
                  <span class="was">৳{{ money(p.oldPrice) }}</span>
                </div>
                <div class="bar"><i :style="{ width: p.sold + '%' }"></i></div>
                <div class="bar__label">{{ p.sold }}% claimed · {{ p.stock }} left</div>
              </div>
            </article>
          </div>
        </div>
      </v-card>

      <!-- ═════════ Popular products ═════════ -->
      <div class="tt-section"><v-icon size="16">mdi-star-outline</v-icon><span>Popular right now</span></div>
      <v-card flat class="tt-card tt-green">
        <div class="tt-head">
          <span class="tt-dot"></span><span class="tt-card-title">Best-selling products</span>
          <v-spacer />
          <div class="tabs">
            <button v-for="t in tabs" :key="t" class="tabs__btn" :class="{ 'tabs__btn--on': activeTab === t }" @click="activeTab = t">{{ t }}</button>
          </div>
        </div>
        <div class="tt-body">
          <div class="grid">
            <article v-for="p in visibleProducts" :key="p.id" class="card">
              <div class="card__media">
                <span v-if="p.badge" class="card__tag" :class="'card__tag--' + p.badge.type">{{ p.badge.text }}</span>
                <span v-else-if="p.oldPrice" class="card__tag">-{{ discount(p) }}%</span>
                <img :src="p.image" :alt="p.name" loading="lazy" />
                <div class="quick">
                  <button class="quick__btn" title="Save" @click="toggleWishlist(p)"><v-icon icon="mdi-heart-outline" size="15" /></button>
                  <button class="quick__btn" title="Compare" @click="addCompare(p)"><v-icon icon="mdi-compare-horizontal" size="15" /></button>
                  <button class="quick__btn" title="Quick view" @click="quickView(p)"><v-icon icon="mdi-eye-outline" size="15" /></button>
                </div>
              </div>
              <div class="card__body">
                <div class="rating"><span class="rating__stars">{{ stars(p.rating) }}</span><span>{{ p.reviews }}</span></div>
                <a :href="p.href" class="card__name">{{ p.name }}</a>
                <ul class="specs">
                  <li v-for="(s, i) in p.specs" :key="i">{{ s }}</li>
                </ul>
                <div class="card__price">
                  <span class="now">৳{{ money(p.price) }}</span>
                  <span v-if="p.oldPrice" class="was">৳{{ money(p.oldPrice) }}</span>
                </div>
                <div class="card__note">{{ emiLine(p) }}</div>
                <div class="card__acts">
                  <button class="buy" @click="addToCart(p)">Add to cart</button>
                  <button class="alt" title="Buy now" @click="buyNow(p)"><v-icon icon="mdi-lightning-bolt-outline" size="16" /></button>
                </div>
              </div>
            </article>
          </div>
        </div>
      </v-card>

      <!-- ═════════ Promo banners ═════════ -->
      <div class="promos">
        <a v-for="b in banners" :key="b.title" :href="b.href" class="promo" :style="{ background: b.bg }">
          <small>{{ b.kicker }}</small>
          <b>{{ b.title }}</b>
          <span class="promo__go">{{ b.cta }}</span>
        </a>
      </div>

      <!-- ═════════ Brands ═════════ -->
      <div class="tt-section"><v-icon size="16">mdi-shield-check-outline</v-icon><span>Brands we carry</span></div>
      <v-card flat class="tt-card tt-purple">
        <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Every unit sourced through authorised channels</span></div>
        <div class="tt-body">
          <div class="brands">
            <a v-for="b in brands" :key="b.name" :href="b.href" class="brands__tile">{{ b.name }}</a>
          </div>
        </div>
      </v-card>

      <!-- ═════════ Guides ═════════ -->
      <div class="tt-section"><v-icon size="16">mdi-book-open-variant</v-icon><span>Buying guides</span></div>
      <v-row dense>
        <v-col v-for="p in posts" :key="p.title" cols="12" md="4">
          <v-card flat class="tt-card tt-blue h-100">
            <div class="tt-body">
              <div class="tt-sub mb-2">{{ p.category }} · {{ p.read }} min read</div>
              <a :href="p.href" class="post__title">{{ p.title }}</a>
              <p class="post__excerpt">{{ p.excerpt }}</p>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- ═════════ Showrooms + newsletter ═════════ -->
      <div class="tt-section"><v-icon size="16">mdi-map-marker-outline</v-icon><span>Visit us or stay in the loop</span></div>
      <v-row dense class="mb-8">
        <v-col cols="12" lg="7">
          <v-card flat class="tt-card tt-teal h-100">
            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Showrooms</span></div>
            <div class="tt-body">
              <div class="tt-sub mb-3">Try before you buy, or collect an online order the same day</div>
              <div class="showroom__grid">
                <div v-for="s in showrooms" :key="s.city" class="showroom__card">
                  <b>{{ s.city }}</b>
                  <span>{{ s.address }}<br />{{ s.hours }} · {{ s.phone }}</span>
                </div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" lg="5">
          <v-card flat class="tt-card tt-green h-100 news">
            <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">New arrivals &amp; price drops, weekly</span></div>
            <div class="tt-body">
              <p class="news__lead">No daily blasts. Just stock news worth knowing about.</p>
              <form class="news__form" @submit.prevent="subscribe">
                <v-text-field v-model="email" type="email" placeholder="you@example.com" variant="solo" flat density="comfortable" hide-details bg-color="#fff" />
                <v-btn flat type="submit" class="news__btn text-none" :loading="subscribing">Subscribe</v-btn>
              </form>
              <p class="news__fine">Unsubscribe any time. We never share your address.</p>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HomePage',

  data() {
    return {
      subscribing: false,
      slide: 0,
      compareA: null,
      compareB: null,
      email: '',
      activeTab: 'All',
      timer: null,
      countdown: { h: 0, m: 0, s: 0 },
      totalProducts: 4214,

      slides: [
        { eyebrow: 'Eid offer · ends Sunday', title: 'Charge everything, carry almost nothing.', text: 'GaN chargers, 100W cables and slim power banks — up to 30% off, with official warranty on every unit.', cta: 'Shop the offer', href: '/c/chargers' },
        { eyebrow: 'Audio week', title: 'Headphones that earn their place on your desk.', text: 'Noise cancelling over-ears and true wireless buds from ৳1,890, all with local service support.', cta: 'Shop audio', href: '/c/audio' },
        { eyebrow: 'New in stock', title: 'Wi-Fi 6 routers built for thick walls.', text: 'Mesh kits and high-gain routers for concrete flats, with free setup guidance from our team.', cta: 'Shop networking', href: '/c/networking' },
      ],

      heroStats: [
        { icon: 'mdi-cash-multiple', value: '30% off', label: 'This week only' },
        { icon: 'mdi-truck-fast-outline', value: 'Same-day', label: 'Delivery in Dhaka' },
        { icon: 'mdi-shield-check-outline', value: '12 months', label: '0% EMI available' },
      ],

      categories: [
        { slug: 'cables', name: 'Cables & adapters', short: 'Cables', icon: 'mdi-usb-port', count: 214, children: true },
        { slug: 'chargers', name: 'Chargers & power', short: 'Chargers', icon: 'mdi-flash-outline', count: 168, children: true },
        { slug: 'audio', name: 'Audio & headphones', short: 'Headphones', icon: 'mdi-headphones', count: 96, children: true },
        { slug: 'keyboard-mouse', name: 'Keyboard & mouse', short: 'Keyboards', icon: 'mdi-keyboard-outline', count: 132, children: true },
        { slug: 'storage', name: 'Storage & drives', short: 'Storage', icon: 'mdi-harddisk', count: 74, children: true },
        { slug: 'power-banks', name: 'Power banks', short: 'Power banks', icon: 'mdi-battery-charging-70', count: 58 },
        { slug: 'networking', name: 'Networking', short: 'Networking', icon: 'mdi-router-wireless', count: 89, children: true },
        { slug: 'cooling', name: 'Cooling & fans', short: 'Cooling', icon: 'mdi-fan', count: 41 },
      ],

      tools: [
        { icon: 'mdi-air-conditioner', title: 'AC ton calculator', sub: 'Find the right size for your room', href: '/ac-ton-calculator' },
        { icon: 'mdi-credit-card-outline', title: 'EMI calculator', sub: '0% for 3, 6, 9 and 12 months', href: '/emi' },
      ],

      services: [
        { icon: 'mdi-truck-fast-outline', title: 'Same-day in Dhaka', sub: 'Order before 3pm' },
        { icon: 'mdi-shield-check-outline', title: 'Official warranty', sub: 'Authorised importer' },
        { icon: 'mdi-wallet-outline', title: 'bKash, Nagad & card', sub: 'Or cash on delivery' },
        { icon: 'mdi-headset', title: 'Talk to a human', sub: '10am–8pm, every day' },
      ],

      tabs: ['All', 'Cables', 'Audio', 'Storage', 'Accessories'],

      products: [
        { id: 1, group: 'Audio', name: 'Baseus Bowie E9 True Wireless Earbuds', price: 2350, oldPrice: 2990, rating: 5, reviews: 318, image: '/images/products/earbuds.webp', href: '/p/baseus-e9', specs: ['Bluetooth 5.3', '35 hour total playback', 'IPX5 splash resistant'] },
        { id: 2, group: 'Cables', name: 'UGREEN 100W USB-C Braided Cable, 2m', price: 1190, oldPrice: 1450, rating: 5, reviews: 204, image: '/images/products/usbc-cable.webp', href: '/p/ugreen-100w', specs: ['100W power delivery', '480Mbps data transfer', 'Nylon braided, 2 metre'] },
        { id: 3, group: 'Storage', name: 'SanDisk Extreme Portable SSD 1TB', price: 11500, oldPrice: 12900, rating: 5, reviews: 96, image: '/images/products/ssd.webp', href: '/p/sandisk-1tb', badge: { type: 'gold', text: 'Best seller' }, specs: ['1050MB/s read speed', 'IP65 dust and water rated', '5 year warranty'] },
        { id: 4, group: 'Accessories', name: 'Logitech MX Master 3S Wireless Mouse', price: 12800, oldPrice: null, rating: 5, reviews: 147, image: '/images/products/mouse.webp', href: '/p/mx-master-3s', badge: { type: 'new', text: 'New' }, specs: ['8000 DPI sensor', 'Quiet click switches', 'USB-C fast charge'] },
        { id: 5, group: 'Cables', name: 'Anker HDMI 2.1 Ultra High Speed Cable, 3m', price: 2100, oldPrice: 2600, rating: 4, reviews: 41, image: '/images/products/hdmi.webp', href: '/p/anker-hdmi', specs: ['8K at 60Hz', '48Gbps bandwidth', 'eARC support'] },
        { id: 6, group: 'Accessories', name: 'Xiaomi 20000mAh 22.5W Power Bank', price: 3450, oldPrice: 3900, rating: 4, reviews: 73, image: '/images/products/powerbank.webp', href: '/p/xiaomi-20000', specs: ['20000mAh capacity', '22.5W fast charge', 'Three output ports'] },
        { id: 7, group: 'Audio', name: 'Edifier R1280DB Bookshelf Speakers', price: 14900, oldPrice: 16500, rating: 5, reviews: 52, image: '/images/products/speakers.webp', href: '/p/edifier-r1280db', specs: ['42W RMS output', 'Bluetooth and optical in', 'Remote controlled'] },
        { id: 8, group: 'Storage', name: 'Samsung 990 EVO NVMe SSD 500GB', price: 7900, oldPrice: 8600, rating: 5, reviews: 118, image: '/images/products/nvme.webp', href: '/p/samsung-990-evo', specs: ['5000MB/s read speed', 'PCIe 4.0 x4', 'Nickel coated controller'] },
        { id: 9, group: 'Accessories', name: 'Keychron K2 Pro Mechanical Keyboard', price: 10500, oldPrice: 11900, rating: 5, reviews: 89, image: '/images/products/keyboard.webp', href: '/p/keychron-k2-pro', specs: ['75% hot swap layout', 'QMK and VIA support', 'Bluetooth and wired'] },
        { id: 10, group: 'Cables', name: 'Orico 7-in-1 USB-C Hub with HDMI', price: 3200, oldPrice: 3800, rating: 4, reviews: 66, image: '/images/products/hub.webp', href: '/p/orico-hub', specs: ['4K HDMI output', 'SD and microSD slots', '100W pass-through charging'] },
        { id: 11, group: 'Audio', name: 'Sony WH-CH720N Noise Cancelling Headphones', price: 12400, oldPrice: 13900, rating: 5, reviews: 175, image: '/images/products/headphones.webp', href: '/p/sony-ch720n', specs: ['Active noise cancelling', '35 hour battery', 'Multipoint pairing'] },
        { id: 12, group: 'Storage', name: 'SanDisk Ultra microSDXC 256GB', price: 2250, oldPrice: 2700, rating: 4, reviews: 233, image: '/images/products/microsd.webp', href: '/p/sandisk-256', specs: ['150MB/s read speed', 'A1 app performance', 'Adapter included'] },
      ],

      deals: [
        { id: 101, name: 'Baseus 65W GaN Fast Charger, 3 ports', price: 3290, oldPrice: 4500, sold: 78, stock: 11, rating: 5, reviews: 142, image: '/images/products/charger.webp', href: '/p/baseus-65w' },
        { id: 102, name: 'TP-Link Archer AX55 Wi-Fi 6 Router', price: 8900, oldPrice: 11200, sold: 45, stock: 24, rating: 4, reviews: 63, image: '/images/products/router.webp', href: '/p/archer-ax55' },
        { id: 103, name: 'Havit HV-F2056 Slim Laptop Cooling Pad', price: 1450, oldPrice: 1990, sold: 62, stock: 18, rating: 4, reviews: 88, image: '/images/products/cooler.webp', href: '/p/havit-f2056' },
        { id: 104, name: 'A4Tech Fstyler Wireless Keyboard & Mouse', price: 2650, oldPrice: 3300, sold: 91, stock: 4, rating: 5, reviews: 210, image: '/images/products/combo.webp', href: '/p/a4tech-combo' },
        { id: 105, name: 'Rapoo VT300 Ergonomic Gaming Mouse', price: 3100, oldPrice: 3950, sold: 34, stock: 31, rating: 4, reviews: 57, image: '/images/products/gaming-mouse.webp', href: '/p/rapoo-vt300' },
      ],

      banners: [
        { kicker: 'Build your desk', title: 'Hubs, docks and monitor arms from ৳990', cta: 'Shop workspace', href: '/c/workspace', bg: 'linear-gradient(120deg,#053d35,#0b5f50)' },
        { kicker: 'Audio week', title: 'Up to 30% off headphones', cta: 'Shop audio', href: '/c/audio', bg: 'linear-gradient(135deg,#2f5be7,#5a7ff0)' },
        { kicker: 'Trade in', title: 'Old gear, new credit', cta: 'Get a quote', href: '/trade-in', bg: 'linear-gradient(135deg,#7c3aed,#a06af0)' },
      ],

      brands: [
        { name: 'Anker', href: '/b/anker' },
        { name: 'UGREEN', href: '/b/ugreen' },
        { name: 'Baseus', href: '/b/baseus' },
        { name: 'Logitech', href: '/b/logitech' },
        { name: 'Samsung', href: '/b/samsung' },
        { name: 'SanDisk', href: '/b/sandisk' },
        { name: 'TP-Link', href: '/b/tp-link' },
        { name: 'Xiaomi', href: '/b/xiaomi' },
      ],

      posts: [
        { category: 'Power', read: 6, title: 'GaN vs standard chargers: what actually changes', excerpt: 'Smaller, cooler, and usually cheaper per watt. Here is when the upgrade is worth it.', href: '/guides/gan-chargers' },
        { category: 'Storage', read: 8, title: 'Choosing an external SSD for video work in 2026', excerpt: 'Read speed is the headline, but sustained write and thermals decide the edit.', href: '/guides/external-ssd' },
        { category: 'Networking', read: 5, title: 'Fixing weak Wi-Fi in a concrete Dhaka flat', excerpt: 'Mesh, extenders or powerline — a straight comparison for thick-wall buildings.', href: '/guides/wifi-coverage' },
      ],

      showrooms: [
        { city: 'Dhanmondi', address: 'House 32, Road 8/A', hours: '10am–8pm', phone: '01711 000 000' },
        { city: 'Uttara', address: 'Sector 7, Garib-e-Newaz Ave', hours: '10am–8pm', phone: '01711 000 001' },
        { city: 'Chattogram', address: 'GEC Circle, 2 no. Gate', hours: '10am–8pm', phone: '01711 000 002' },
        { city: 'Sylhet', address: 'Zindabazar, Mirboxtula', hours: '10am–8pm', phone: '01711 000 003' },
      ],
    }
  },

  computed: {
    visibleProducts() {
      if (this.activeTab === 'All') return this.products
      return this.products.filter((p) => p.group === this.activeTab)
    },
    productNames() {
      return this.products.map((p) => p.name)
    },
  },

  created() {
    this.startCountdown()
    // this.fetchHomeData()
  },

  beforeUnmount() {
    clearInterval(this.timer)
  },

  methods: {
    money(v) {
      return Number(v || 0).toLocaleString('en-BD')
    },
    discount(p) {
      if (!p.oldPrice) return 0
      return Math.round(((p.oldPrice - p.price) / p.oldPrice) * 100)
    },
    stars(n) {
      return '★'.repeat(n) + '☆'.repeat(5 - n)
    },
    emiLine(p) {
      if (p.price < 5000) return 'In stock · ships today'
      return `From ৳${this.money(Math.round(p.price / 12))}/mo · 12 months`
    },
    pad(n) {
      return String(n).padStart(2, '0')
    },
    startCountdown() {
      const end = new Date()
      end.setHours(23, 59, 59, 999)
      const tick = () => {
        const diff = Math.max(0, end - new Date())
        this.countdown = { h: Math.floor(diff / 3600000), m: Math.floor((diff % 3600000) / 60000), s: Math.floor((diff % 60000) / 1000) }
      }
      tick()
      this.timer = setInterval(tick, 1000)
    },
    async fetchHomeData() {
      const { data } = await this.axios.get('/api/home')
      this.products = data.products
      this.deals = data.deals
      this.categories = data.categories
      this.totalProducts = data.total
    },
    goCompare() {
      this.$router.push({ path: '/compare', query: { a: this.compareA, b: this.compareB } })
    },
    async addToCart(p) {
      await this.axios.post('/api/cart', { product_id: p.id, qty: 1 })
      this.$swal.fire({ position: 'bottom-end', icon: 'success', title: 'Added to cart', showConfirmButton: false, timer: 1500 })
    },
    buyNow(p) {
      this.$router.push({ path: '/checkout', query: { buy: p.id } })
    },
    addCompare(p) {
      if (!this.compareA) this.compareA = p.name
      else this.compareB = p.name
    },
    async toggleWishlist(p) {
      await this.axios.post('/api/wishlist/toggle', { product_id: p.id })
    },
    quickView(p) {
      this.$router.push(p.href)
    },
    async subscribe() {
      this.subscribing = true
      try {
        await this.axios.post('/api/subscribe', { email: this.email })
        this.email = ''
        this.$swal.fire({ position: 'bottom-end', icon: 'success', title: 'Subscribed', showConfirmButton: false, timer: 1500 })
      } finally {
        this.subscribing = false
      }
    },
  },
}
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-home {
  --deep: #053d35;
  --deep-2: #0a5548;
  --deep-3: #0b5f50;
  --ink: #0f2a26;
  --muted: #6b7f7b;
  --line: #e3eae8;
  --bg: #f3f7f6;
  --blue: #2f5be7;
  --green: #0f9d6b;
  --purple: #7c3aed;
  --teal: #0f766e;
  --amber: #f59e0b;
  --pink: #ec4899;

  font-family: Poppins, "Segoe UI", sans-serif;
  color: var(--ink);
  background: var(--bg);
  font-size: 14px;
  padding-bottom: 12px;
}

.tt-home a { text-decoration: none; color: inherit; }
.tt-home ul { margin: 0; padding: 0; list-style: none; }
.wrap { max-width: 1340px; margin: 0 auto; padding: 0 20px; }

/* ───────── Section headings ───────── */
.tt-section {
  display: flex; align-items: center; gap: 8px;
  margin: 26px 0 12px;
  font-size: 12px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase;
  color: #3d5450;
}

/* ───────── tt-card system (shared with admin) ───────── */
.tt-card {
  --accent: #2f5be7;
  --tint: #eaf0ff;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px !important;
  box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04);
  overflow: hidden;
}
.tt-blue   { --accent: #2f5be7; --tint: #e9efff; }
.tt-green  { --accent: #0f9d6b; --tint: #e3f6ee; }
.tt-purple { --accent: #7c3aed; --tint: #f0e9ff; }
.tt-teal   { --accent: #0f766e; --tint: #ddf3f0; }
.tt-amber  { --accent: #d97706; --tint: #fff2d6; }

.tt-head {
  display: flex; align-items: center; gap: 8px;
  padding: 11px 16px;
  background: linear-gradient(90deg, var(--tint), #fff);
  border-bottom: 1px solid var(--line);
  border-left: 3px solid var(--accent);
}
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 6px;
  color: var(--accent); background: #fff; border: 1px solid var(--accent);
}
.tt-tag.warn { color: #8a5a00; background: #fdf1cf; border-color: #f3d78a; }
.tt-body { padding: 14px 18px 18px; }
.tt-sub { font-size: 12.5px; color: var(--muted); }

/* ───────── Hero row ───────── */
.hero-row { display: grid; grid-template-columns: 236px 1fr 264px; gap: 14px; padding: 18px 0; }

.sidemenu { background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 6px; align-self: start; }
.sidemenu__item { display: flex; align-items: center; gap: 11px; padding: 9.5px 12px; border-radius: 7px; font-size: 13.2px; font-weight: 500; color: var(--ink); }
.sidemenu__item :deep(.v-icon) { color: var(--muted); }
.sidemenu__item:hover { background: #e3f6ee; color: var(--green); }
.sidemenu__item:hover :deep(.v-icon) { color: var(--green); }
.sidemenu__item--accent { color: var(--green); }
.sidemenu__item--accent :deep(.v-icon) { color: var(--green); }
.sidemenu__chev { margin-left: auto; opacity: 0.55; }

.hero { position: relative; border-radius: 10px; overflow: hidden; background: linear-gradient(135deg, var(--deep) 0%, var(--deep-2) 55%, var(--deep-3) 100%); }
.slider { border-radius: 0; }
.slide { height: 300px; display: flex; align-items: center; padding: 0 40px; color: #fff; }
.slide__copy { max-width: 62%; }
.slide__eyebrow { display: inline-flex; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22); color: #8fe3c6; font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 99px; }
.slide__title { font-size: 32px; line-height: 1.15; letter-spacing: -0.02em; font-weight: 700; margin: 14px 0 10px; }
.slide__text { color: rgba(255,255,255,0.72); font-size: 13.5px; line-height: 1.6; margin: 0 0 20px; max-width: 42ch; }
.btn { background: var(--green); color: #fff; font-weight: 600; border-radius: 8px; height: 42px; padding: 0 20px; }
.btn:hover { background: #0b7a53; }
.btn--ghost { background: transparent; border-color: rgba(255,255,255,0.32); color: #fff; margin-left: 8px; }
.hero-line { height: 2px; background: linear-gradient(90deg, var(--pink), rgba(236,72,153,0) 70%); }
.hero-strip { display: grid; grid-template-columns: repeat(3, 1fr); padding: 14px 26px; }
.hero-stat { display: flex; align-items: center; gap: 10px; padding: 4px 18px; border-left: 1px solid rgba(255,255,255,0.14); }
.hero-stat:first-child { border-left: 0; padding-left: 0; }
.hero-stat__icon { width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.2); flex: none; }
.hero-stat__value { font-size: 15px; font-weight: 700; color: #fff; line-height: 1.2; }
.hero-stat__label { font-size: 11px; color: rgba(255,255,255,0.6); }

.rail { display: flex; flex-direction: column; gap: 12px; }
.compare .tt-body { padding: 13px 15px 15px; }
.compare__field :deep(.v-field) { background: var(--bg); border: 1px solid var(--line); border-radius: 7px; font-size: 12.5px; }
.compare__vs { text-align: center; font-size: 10.5px; font-weight: 700; color: var(--muted); letter-spacing: 0.08em; margin: 7px 0; }
.compare__btn { margin-top: 11px; background: var(--green); color: #fff; border-radius: 7px; height: 42px; font-weight: 650; }

.tool { background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 14px; display: flex; gap: 12px; align-items: center; }
.tool:hover { border-color: var(--green); }
.tool__ic { width: 40px; height: 40px; border-radius: 9px; background: #e3f6ee; display: grid; place-items: center; flex: none; }
.tool__ic :deep(.v-icon) { color: var(--green); }
.tool b { font-size: 13px; font-weight: 650; display: block; }
.tool span { font-size: 11.5px; color: var(--muted); }

/* ───────── trust ───────── */
.trust { background: #fff; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin-top: 6px; }
.trust__grid { display: grid; grid-template-columns: repeat(4, 1fr); }
.trust__item { display: flex; gap: 12px; align-items: center; padding: 17px 22px; border-left: 1px solid var(--line); }
.trust__item:first-child { border-left: 0; }
.trust__item :deep(.v-icon) { color: var(--green); }
.trust__item b { font-size: 13.2px; font-weight: 650; display: block; line-height: 1.3; }
.trust__item span { font-size: 11.8px; color: var(--muted); }

/* ───────── categories ───────── */
.cats { display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; }
.cat { background: #fff; border: 1px solid var(--line); border-radius: 9px; padding: 16px 8px 12px; text-align: center; transition: border-color 0.15s, transform 0.15s; }
.cat:hover { border-color: var(--blue); transform: translateY(-2px); }
.cat__ic { width: 42px; height: 42px; margin: 0 auto 8px; border-radius: 10px; background: #e9efff; display: grid; place-items: center; }
.cat__ic :deep(.v-icon) { color: var(--blue); }
.cat b { font-size: 12px; font-weight: 600; display: block; line-height: 1.3; }
.cat__count { font-size: 10.5px; color: var(--muted); }

/* ───────── deals rail ───────── */
.deals-rail { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }

/* ───────── product card ───────── */
.grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
.card { background: #fff; border: 1px solid var(--line); border-radius: 9px; overflow: hidden; display: flex; flex-direction: column; height: 100%; transition: border-color 0.15s, box-shadow 0.15s; }
.card:hover { border-color: var(--green); box-shadow: 0 10px 22px rgba(5, 61, 53, 0.1); }
.card__media { position: relative; aspect-ratio: 1/1; display: grid; place-items: center; padding: 14px; overflow: hidden; background: var(--bg); }
.card__media img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.25s; }
.card:hover .card__media img { transform: scale(1.05); }
.card__tag { position: absolute; top: 8px; left: 8px; background: var(--green); color: #fff; font-size: 10.5px; font-weight: 700; padding: 3px 7px; border-radius: 5px; z-index: 2; }
.card__tag--new { background: var(--blue); }
.card__tag--gold { background: var(--amber); }
.quick { position: absolute; top: 8px; right: 8px; display: flex; flex-direction: column; gap: 6px; opacity: 0; transform: translateX(7px); transition: 0.18s; }
.card:hover .quick { opacity: 1; transform: none; }
.quick__btn { width: 29px; height: 29px; border: 1px solid var(--line); background: #fff; border-radius: 7px; display: grid; place-items: center; cursor: pointer; color: var(--ink); }
.quick__btn:hover { background: var(--green); border-color: var(--green); color: #fff; }
.card__body { padding: 11px; border-top: 1px solid var(--line); display: flex; flex-direction: column; flex: 1; }
.rating { display: flex; align-items: center; gap: 5px; font-size: 10.5px; color: var(--muted); margin-bottom: 6px; }
.rating__stars { color: var(--amber); letter-spacing: 1px; }
.card__name { font-size: 12.5px; font-weight: 600; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 35px; }
.card:hover .card__name { color: var(--green); }
.specs { margin-top: 7px; font-size: 11px; color: var(--muted); line-height: 1.6; }
.specs li::before { content: ''; display: inline-block; width: 3px; height: 3px; border-radius: 99px; background: var(--green); vertical-align: middle; margin-right: 6px; }
.card__price { margin-top: auto; padding-top: 10px; display: flex; align-items: baseline; gap: 7px; }
.now { font-size: 16px; font-weight: 800; color: var(--green); letter-spacing: -0.02em; }
.was { font-size: 11.5px; color: var(--muted); text-decoration: line-through; }
.card__note { font-size: 10.5px; color: var(--muted); margin-top: 3px; }
.card__acts { display: flex; gap: 6px; margin-top: 10px; }
.buy { flex: 1; background: var(--deep); color: #fff; border: 0; border-radius: 7px; padding: 8px; font: 650 12px inherit; cursor: pointer; }
.buy:hover { background: var(--green); }
.alt { width: 34px; border: 1px solid var(--line); background: #fff; border-radius: 7px; display: grid; place-items: center; cursor: pointer; color: var(--ink); }
.alt:hover { border-color: var(--green); color: var(--green); }
.bar { height: 5px; background: var(--line); border-radius: 99px; margin-top: 10px; overflow: hidden; }
.bar i { display: block; height: 100%; background: var(--amber); }
.bar__label { font-size: 10.5px; color: var(--muted); margin-top: 5px; }

/* ───────── tabs ───────── */
.tabs { display: flex; gap: 4px; flex-wrap: wrap; }
.tabs__btn { border: 1px solid var(--line); background: #fff; color: var(--ink); font: 600 11.5px inherit; padding: 6px 12px; border-radius: 99px; cursor: pointer; }
.tabs__btn--on { background: var(--green); color: #fff; border-color: var(--green); }

/* ───────── promos ───────── */
.promos { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px; margin-top: 22px; }
.promo { border-radius: 10px; padding: 24px; min-height: 150px; display: flex; flex-direction: column; justify-content: center; color: #fff; }
.promo small { font-size: 11.5px; opacity: 0.85; }
.promo b { font-size: 18px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.25; margin: 6px 0 14px; max-width: 17ch; }
.promo__go { align-self: flex-start; background: #fff; color: var(--deep); font: 650 12px inherit; padding: 7px 15px; border-radius: 6px; }

/* ───────── brands ───────── */
.brands { display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; }
.brands__tile { border: 1px solid var(--line); border-radius: 9px; height: 56px; display: grid; place-items: center; font-size: 12.5px; font-weight: 600; color: var(--muted); }
.brands__tile:hover { border-color: var(--purple); color: var(--purple); }

/* ───────── guides ───────── */
.post__title { font-size: 14.5px; font-weight: 700; letter-spacing: -0.01em; display: block; margin-bottom: 6px; line-height: 1.35; color: var(--ink); }
.post__title:hover { color: var(--blue); }
.post__excerpt { font-size: 12px; color: var(--muted); line-height: 1.55; margin: 0; }

/* ───────── showroom + newsletter ───────── */
.showroom__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.showroom__card { border: 1px solid var(--line); border-radius: 8px; padding: 12px; }
.showroom__card b { font-size: 13px; font-weight: 650; display: block; }
.showroom__card span { font-size: 11.5px; color: var(--muted); line-height: 1.5; display: block; margin-top: 3px; }

.news .tt-body { display: flex; flex-direction: column; }
.news__lead { font-size: 12.5px; color: var(--muted); margin: 0 0 14px; }
.news__form { display: flex; gap: 8px; }
.news__form :deep(.v-field) { border-radius: 7px; }
.news__btn { background: var(--green); color: #fff; border-radius: 7px; height: 48px; padding: 0 20px; font-weight: 650; }
.news__btn:hover { background: #0b7a53; }
.news__fine { font-size: 11px; color: var(--muted); margin: 12px 0 0; }

/* ───────── responsive ───────── */
@media (max-width: 1180px) {
  .hero-row { grid-template-columns: 1fr 260px; }
  .sidemenu { display: none; }
  .grid { grid-template-columns: repeat(4, 1fr); }
  .deals-rail { grid-template-columns: repeat(3, 1fr); }
  .cats, .brands { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 900px) {
  .hero-row { grid-template-columns: 1fr; }
  .slide { padding: 0 24px; height: 260px; }
  .slide__title { font-size: 25px; }
  .slide__copy { max-width: 100%; }
  .rail { flex-direction: row; }
  .promos, .showroom__grid { grid-template-columns: 1fr; }
  .grid { grid-template-columns: repeat(3, 1fr); }
  .trust__grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
  .grid, .deals-rail { grid-template-columns: repeat(2, 1fr); }
  .cats, .brands { grid-template-columns: repeat(3, 1fr); }
  .trust__grid { grid-template-columns: 1fr; }
  .trust__item { border-left: 0; border-top: 1px solid var(--line); }
  .trust__item:first-child { border-top: 0; }
  .rail { flex-direction: column; }
  .hero-strip { grid-template-columns: 1fr; gap: 10px; }
  .hero-stat { border-left: 0; padding-left: 0; }
}
</style>