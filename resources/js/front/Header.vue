<template>
  <header class="tt-header">
    <!-- ============ ANNOUNCEMENT ============ -->
    <div class="announce">
      <div class="wrap">
        <p class="announce__msg">
          Free delivery over <b>৳5,000</b> · 0% EMI up to 12 months · Same-day in Dhaka
        </p>
        <nav class="announce__nav">
          <router-link to="/track-order">Track order</router-link>
          <router-link to="/emi">EMI guide</router-link>
          <router-link to="/showrooms">Showrooms</router-link>
          <router-link to="/support">Support</router-link>
        </nav>
      </div>
    </div>

    <!-- ============ MAIN BAR ============ -->
    <div class="mainbar">
      <div class="wrap">
        <v-btn icon="mdi-menu" variant="text" class="mainbar__burger d-lg-none" @click="drawer = true" />

        <router-link to="/" class="logo">
          <span class="logo__mark"><v-icon icon="mdi-flash" size="18" /></span>
          <span class="logo__text">
            Tanjil<em>Traders</em>
            <small>Electronics &amp; accessories</small>
          </span>
        </router-link>

        <form class="search" @submit.prevent="doSearch">
          <select v-model="searchScope" class="search__scope">
            <option value="">All categories</option>
            <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
          </select>
          <input
            v-model="query"
            class="search__input"
            type="search"
            placeholder="Search products — try “65W GaN charger”"
            @input="suggest"
          />
          <button class="search__btn" type="submit">
            <v-icon icon="mdi-magnify" size="18" />
            <span class="d-none d-md-inline">Search</span>
          </button>

          <ul v-if="suggestions.length" class="search__drop">
            <li v-for="s in suggestions" :key="s.id">
              <a :href="'/p/' + s.slug">
                <img :src="s.image" :alt="s.name" />
                <span>
                  <b>{{ s.name }}</b>
                  <em>৳{{ money(s.price) }}</em>
                </span>
              </a>
            </li>
          </ul>
        </form>

        <div class="hacts">
          <router-link to="/account" class="hact">
            <v-icon icon="mdi-account-outline" size="22" />
            <span class="hact__label">
              <small>Account</small>
              <strong>{{ user ? user.name : 'Sign in' }}</strong>
            </span>
          </router-link>

          <router-link to="/wishlist" class="hact">
            <v-icon icon="mdi-heart-outline" size="22" />
            <em v-if="wishlistCount" class="hact__dot">{{ wishlistCount }}</em>
            <span class="hact__label">
              <small>Saved</small>
              <strong>Wishlist</strong>
            </span>
          </router-link>

          <router-link to="/cart" class="hact hact--accent">
            <v-icon icon="mdi-cart-outline" size="22" />
            <em v-if="cartCount" class="hact__dot">{{ cartCount }}</em>
            <span class="hact__label">
              <small>৳{{ money(cartTotal) }}</small>
              <strong>Cart</strong>
            </span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- ============ NAV BAR ============ -->
    <nav class="navbar d-none d-lg-block">
      <div class="wrap">
        <div class="mega-host" @mouseleave="megaOpen = false">
          <button class="allcat" @mouseenter="megaOpen = true" @click="megaOpen = !megaOpen">
            <v-icon icon="mdi-view-grid-outline" size="17" />
            All categories
            <v-icon icon="mdi-chevron-down" size="16" />
          </button>

          <div v-show="megaOpen" class="mega">
            <div class="mega__list">
              <a
                v-for="c in categories"
                :key="c.slug"
                :href="'/c/' + c.slug"
                class="mega__listitem"
                :class="{ 'mega__listitem--on': activeCat === c.slug }"
                @mouseenter="activeCat = c.slug"
              >
                <v-icon :icon="c.icon" size="17" />
                {{ c.name }}
              </a>
            </div>

            <div class="mega__panel">
              <div v-for="col in currentCat.columns" :key="col.title" class="mega__col">
                <h4>{{ col.title }}</h4>
                <a v-for="l in col.links" :key="l.label" :href="l.href">{{ l.label }}</a>
              </div>
            </div>
          </div>
        </div>

        <router-link
          v-for="l in navLinks"
          :key="l.to"
          :to="l.to"
          class="navlink"
          :class="{ navlink__sale: l.sale }"
        >
          {{ l.label }}
        </router-link>

        <div class="navright">
          <v-icon icon="mdi-headset" size="17" />
          Support <b>10611</b>
        </div>
      </div>
    </nav>

    <!-- ============ MOBILE DRAWER ============ -->
    <v-navigation-drawer v-model="drawer" temporary width="290" class="drawer">
      <div class="drawer__head">
        <span class="logo__mark"><v-icon icon="mdi-flash" size="16" /></span>
        <b>Tanjil Traders</b>
        <v-btn icon="mdi-close" variant="text" size="small" @click="drawer = false" />
      </div>

      <v-list density="compact" nav>
        <v-list-item v-for="l in navLinks" :key="l.to" :to="l.to" :title="l.label" @click="drawer = false" />
        <v-divider class="my-2" />
        <v-list-group v-for="c in categories" :key="c.slug" :value="c.slug">
          <template #activator="{ props }">
            <v-list-item v-bind="props" :prepend-icon="c.icon" :title="c.name" />
          </template>
          <v-list-item v-for="l in flatLinks(c)" :key="l.label" :title="l.label" :href="l.href" />
        </v-list-group>
      </v-list>
    </v-navigation-drawer>
  </header>
</template>

<script>
export default {
  name: 'SiteHeader',

  data() {
    return {
      drawer: false,
      megaOpen: false,
      activeCat: 'chargers',
      query: '',
      searchScope: '',
      suggestions: [],
      suggestTimer: null,

      user: null,
      cartCount: 3,
      cartTotal: 12340,
      wishlistCount: 5,

      navLinks: [
        { label: 'Home', to: '/' },
        { label: 'New arrivals', to: '/new' },
        { label: 'Best sellers', to: '/best-sellers' },
        { label: 'Brands', to: '/brands' },
        { label: 'Buying guides', to: '/guides' },
        { label: 'Clearance', to: '/clearance', sale: true },
      ],

      categories: [
        {
          slug: 'chargers', name: 'Chargers & power', icon: 'mdi-flash-outline',
          columns: [
            { title: 'Wall chargers', links: [
              { label: 'GaN chargers', href: '/c/chargers/gan' },
              { label: '20W – 45W', href: '/c/chargers/20-45w' },
              { label: '65W and above', href: '/c/chargers/65w' },
              { label: 'Multi-port', href: '/c/chargers/multi-port' },
            ]},
            { title: 'Portable', links: [
              { label: 'Power banks', href: '/c/power-banks' },
              { label: 'Magnetic packs', href: '/c/chargers/magsafe' },
              { label: 'Car chargers', href: '/c/chargers/car' },
            ]},
            { title: 'Home power', links: [
              { label: 'Extension sockets', href: '/c/chargers/extension' },
              { label: 'Surge protectors', href: '/c/chargers/surge' },
              { label: 'UPS', href: '/c/chargers/ups' },
            ]},
          ],
        },
        {
          slug: 'cables', name: 'Cables & adapters', icon: 'mdi-usb-port',
          columns: [
            { title: 'By connector', links: [
              { label: 'USB-C to USB-C', href: '/c/cables/usb-c' },
              { label: 'USB-C to Lightning', href: '/c/cables/lightning' },
              { label: 'HDMI 2.1', href: '/c/cables/hdmi' },
              { label: 'DisplayPort', href: '/c/cables/displayport' },
              { label: 'Ethernet Cat6', href: '/c/cables/ethernet' },
            ]},
            { title: 'By use', links: [
              { label: 'Fast charging', href: '/c/cables/fast-charging' },
              { label: 'Data transfer', href: '/c/cables/data' },
              { label: 'Monitor & display', href: '/c/cables/display' },
            ]},
            { title: 'Popular brands', links: [
              { label: 'Anker', href: '/b/anker' },
              { label: 'UGREEN', href: '/b/ugreen' },
              { label: 'Baseus', href: '/b/baseus' },
            ]},
          ],
        },
        {
          slug: 'audio', name: 'Audio & headphones', icon: 'mdi-headphones',
          columns: [
            { title: 'Headphones', links: [
              { label: 'True wireless', href: '/c/audio/tws' },
              { label: 'Over-ear', href: '/c/audio/over-ear' },
              { label: 'Noise cancelling', href: '/c/audio/anc' },
            ]},
            { title: 'Speakers', links: [
              { label: 'Bluetooth', href: '/c/audio/bt-speakers' },
              { label: 'Bookshelf', href: '/c/audio/bookshelf' },
              { label: 'Soundbars', href: '/c/audio/soundbar' },
            ]},
            { title: 'Studio', links: [
              { label: 'Microphones', href: '/c/audio/mics' },
              { label: 'Audio interfaces', href: '/c/audio/interfaces' },
            ]},
          ],
        },
        {
          slug: 'keyboard-mouse', name: 'Keyboard & mouse', icon: 'mdi-keyboard-outline',
          columns: [
            { title: 'Keyboards', links: [
              { label: 'Mechanical', href: '/c/keyboards/mechanical' },
              { label: 'Wireless', href: '/c/keyboards/wireless' },
              { label: 'Compact 65% / 75%', href: '/c/keyboards/compact' },
            ]},
            { title: 'Mice', links: [
              { label: 'Gaming', href: '/c/mice/gaming' },
              { label: 'Ergonomic', href: '/c/mice/ergonomic' },
              { label: 'Silent', href: '/c/mice/silent' },
            ]},
            { title: 'Desk', links: [
              { label: 'Mouse pads', href: '/c/desk/pads' },
              { label: 'Wrist rests', href: '/c/desk/wrist' },
            ]},
          ],
        },
        {
          slug: 'storage', name: 'Storage & drives', icon: 'mdi-harddisk',
          columns: [
            { title: 'Internal', links: [
              { label: 'NVMe SSD', href: '/c/storage/nvme' },
              { label: 'SATA SSD', href: '/c/storage/sata' },
              { label: 'Hard drives', href: '/c/storage/hdd' },
            ]},
            { title: 'Portable', links: [
              { label: 'External SSD', href: '/c/storage/external-ssd' },
              { label: 'Pen drives', href: '/c/storage/usb' },
              { label: 'Memory cards', href: '/c/storage/cards' },
            ]},
            { title: 'Enclosures', links: [
              { label: 'M.2 enclosures', href: '/c/storage/m2-case' },
              { label: 'Docking stations', href: '/c/storage/docks' },
            ]},
          ],
        },
        {
          slug: 'networking', name: 'Networking', icon: 'mdi-router-wireless',
          columns: [
            { title: 'Routers', links: [
              { label: 'Wi-Fi 6', href: '/c/networking/wifi6' },
              { label: 'Mesh systems', href: '/c/networking/mesh' },
              { label: 'Range extenders', href: '/c/networking/extenders' },
            ]},
            { title: 'Wired', links: [
              { label: 'Switches', href: '/c/networking/switches' },
              { label: 'Network cards', href: '/c/networking/nic' },
            ]},
            { title: 'Accessories', links: [
              { label: 'Cat6 cables', href: '/c/cables/ethernet' },
              { label: 'PoE injectors', href: '/c/networking/poe' },
            ]},
          ],
        },
      ],
    }
  },

  computed: {
    currentCat() {
      return this.categories.find((c) => c.slug === this.activeCat) || this.categories[0]
    },
  },

  created() {
    // this.fetchHeaderState()
  },

  methods: {
    money(v) {
      return Number(v || 0).toLocaleString('en-BD')
    },
    flatLinks(cat) {
      return cat.columns.flatMap((c) => c.links)
    },
    doSearch() {
      if (!this.query.trim()) return
      this.suggestions = []
      this.$router.push({ path: '/search', query: { q: this.query, category: this.searchScope || undefined } })
    },
    suggest() {
      clearTimeout(this.suggestTimer)
      if (this.query.trim().length < 2) {
        this.suggestions = []
        return
      }
      this.suggestTimer = setTimeout(async () => {
        try {
          const { data } = await this.axios.get('/api/search/suggest', { params: { q: this.query, category: this.searchScope } })
          this.suggestions = data.slice(0, 6)
        } catch (e) {
          this.suggestions = []
        }
      }, 250)
    },
    async fetchHeaderState() {
      const { data } = await this.axios.get('/api/header')
      this.user = data.user
      this.cartCount = data.cart.count
      this.cartTotal = data.cart.total
      this.wishlistCount = data.wishlist
      this.categories = data.categories
      this.activeCat = this.categories[0].slug
    },
  },
}
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-header {
  --deep: #053d35;
  --deep-2: #0a5548;
  --deep-3: #0b5f50;
  --accent: #0f9d6b;
  --accent-dk: #0b7a53;
  --accent-tint: #e3f6ee;
  --ink: #0f2a26;
  --ink-2: #3d5450;
  --muted: #6b7f7b;
  --line: #e3eae8;
  --surface: #ffffff;
  --wash: #f3f7f6;

  font-family: Poppins, "Segoe UI", sans-serif;
  font-size: 14px;
}

.tt-header a { text-decoration: none; }
.tt-header .announce__nav a { color: #8fe3c6; }

.wrap { max-width: 1340px; margin: 0 auto; padding: 0 20px; }

/* ---------- announcement ---------- */
.announce {
  background: var(--deep);
  color: #c9ded9;
  font-size: 12.5px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.announce .wrap { display: flex; justify-content: space-between; align-items: center; height: 38px; gap: 16px; }
.announce__msg { margin: 0; }
.announce__msg b { color: #8fe3c6; }
.announce__nav { display: flex; gap: 20px; }
.announce__nav a:hover { color: #fff; }

/* ---------- main bar ---------- */
.mainbar { background: linear-gradient(135deg, var(--deep) 0%, var(--deep-2) 55%, var(--deep-3) 100%); }
.mainbar .wrap { display: flex; align-items: center; gap: 24px; height: 78px; }
.mainbar__burger { color: #fff; margin-left: -8px; }

.logo { display: flex; align-items: center; gap: 10px; color: #fff; flex: none; }
.logo__mark {
  width: 34px; height: 34px; border-radius: 9px;
  background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22);
  display: grid; place-items: center; flex: none; color: #6ee7b7;
}
.logo__text { font-weight: 700; font-size: 21px; letter-spacing: -0.02em; line-height: 1.05; }
.logo__text em { font-style: normal; font-weight: 400; color: #8fe3c6; }
.logo__text small { display: block; font-size: 10.5px; font-weight: 500; color: rgba(255,255,255,0.55); letter-spacing: 0.02em; }

/* ---------- search ---------- */
.search { flex: 1; max-width: 660px; display: flex; background: #fff; border-radius: 9px; position: relative; }
.search__scope {
  border: 0; background: transparent; padding: 0 14px; font: inherit; color: var(--muted);
  border-right: 1px solid var(--line); outline: none; border-radius: 9px 0 0 9px;
}
.search__input { flex: 1; border: 0; padding: 14px; font: inherit; outline: none; color: var(--ink); min-width: 0; }
.search__input::placeholder { color: var(--muted); }
.search__btn {
  border: 0; background: var(--accent); color: #fff; padding: 0 22px; font: 600 13.5px inherit;
  cursor: pointer; display: flex; align-items: center; gap: 7px; border-radius: 0 9px 9px 0;
}
.search__btn:hover { background: var(--accent-dk); }

.search__drop {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: var(--surface); border: 1px solid var(--line); border-radius: 9px;
  box-shadow: 0 20px 40px rgba(5, 30, 25, 0.18); list-style: none; padding: 6px; margin: 0; z-index: 70;
}
.search__drop a { display: flex; align-items: center; gap: 11px; padding: 8px 10px; border-radius: 7px; }
.search__drop a:hover { background: var(--wash); }
.search__drop img { width: 36px; height: 36px; object-fit: contain; }
.search__drop b { display: block; font-size: 12.8px; font-weight: 600; color: var(--ink); }
.search__drop em { font-style: normal; font-size: 12px; color: var(--accent); font-weight: 700; }

/* ---------- header actions ---------- */
.hacts { display: flex; gap: 4px; margin-left: auto; }
.hact { display: flex; align-items: center; gap: 10px; padding: 8px 11px; border-radius: 9px; color: #fff; position: relative; }
.hact:hover { background: rgba(255, 255, 255, 0.09); }
.hact--accent :deep(.v-icon) { color: #6ee7b7; }
.hact__label small { display: block; font-size: 11px; color: rgba(255,255,255,0.55); line-height: 1.25; }
.hact__label strong { display: block; font-size: 13px; font-weight: 600; line-height: 1.25; }
.hact__dot {
  position: absolute; top: 3px; left: 26px; background: var(--accent); color: #fff; border-radius: 99px;
  font-size: 10px; font-weight: 700; font-style: normal; padding: 0 5px; line-height: 15px;
}

/* ---------- nav bar ---------- */
.navbar { background: #072e28; position: sticky; top: 0; z-index: 50; }
.navbar .wrap { display: flex; align-items: stretch; gap: 4px; height: 48px; }
.allcat {
  display: flex; align-items: center; gap: 9px; background: var(--accent); color: #fff;
  padding: 13.4px 18px; font: 650 13.5px inherit; border: 0; cursor: pointer;
}
.allcat:hover { background: var(--accent-dk); }
.navlink { display: flex; align-items: center; padding: 0 14px; color: #b7ccc7; font-size: 13.2px; font-weight: 500; }
.navlink:hover, .navlink.router-link-active { color: #fff; box-shadow: inset 0 -3px 0 var(--accent); }
.navlink__sale { color: #ff9a6e; font-weight: 650; }
.navright { margin-left: auto; display: flex; align-items: center; gap: 8px; color: #b7ccc7; font-size: 12.8px; }
.navright b { color: #fff; }

/* ---------- mega menu ---------- */
.mega-host { position: relative; }
.mega {
  position: absolute; top: 100%; left: 0; width: 860px; background: var(--surface);
  border: 1px solid var(--line); border-radius: 0 0 12px 12px;
  box-shadow: 0 22px 44px rgba(5, 30, 25, 0.18); display: grid; grid-template-columns: 232px 1fr;
  overflow: hidden; z-index: 60;
}
.mega__list { background: var(--wash); border-right: 1px solid var(--line); padding: 8px; }
.mega__listitem { display: flex; align-items: center; gap: 10px; padding: 9px 11px; border-radius: 7px; font-size: 13px; font-weight: 500; color: var(--ink-2); }
.mega__listitem--on, .mega__listitem:hover { background: var(--surface); color: var(--accent-dk); box-shadow: 0 1px 3px rgba(5, 30, 25, 0.08); }
.mega__panel { padding: 18px 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.mega__col h4 { font-size: 12.5px; font-weight: 700; margin: 0 0 9px; color: var(--ink); }
.mega__col a { display: block; font-size: 12.6px; color: var(--muted); padding: 4px 0; }
.mega__col a:hover { color: var(--accent-dk); }

/* ---------- drawer ---------- */
.drawer__head { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-bottom: 1px solid var(--line); font-size: 17px; letter-spacing: -0.02em; }
.drawer__head .v-btn { margin-left: auto; }

@media (max-width: 1279px) { .mega { width: 640px; } }
@media (max-width: 960px) {
  .announce__nav { display: none; }
  .mainbar .wrap { height: 66px; gap: 12px; }
  .logo__text small { display: none; }
  .search__scope { display: none; }
  .hact__label { display: none; }
}
</style>