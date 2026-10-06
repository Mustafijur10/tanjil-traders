Here is the complete, unabridged code for **[resources/js/front/Home.vue](file:///c:/xampp/htdocs/tanjil-traders/resources/js/front/Home.vue)**:

```html
<template>
  <div class="tt-home">
    <!-- ═════════ 0. Top Announcement & Ticker Bar ═════════ -->
    <div class="announcement-bar">
      <div class="wrap d-flex align-center justify-space-between flex-wrap ga-2">
        <div class="d-flex align-center ga-3 font-weight-medium text-caption">
          <span class="badge-tag">FLASH OFFER</span>
          <span>⚡ Free Express Delivery on orders over ৳5,000 in Dhaka City</span>
          <span class="d-none d-md-inline text-muted">|</span>
          <span class="d-none d-md-inline">Official Brand Warranty on 100% of Catalog</span>
        </div>
        <div class="d-flex align-center ga-4 text-caption">
          <a href="/check-warranty" class="announcement-link">
            <v-icon size="14" class="mr-1">mdi-shield-search</v-icon>Verify Warranty
          </a>
          <a href="/track-order" class="announcement-link">
            <v-icon size="14" class="mr-1">mdi-truck-fast-outline</v-icon>Track Order
          </a>
          <a href="tel:+8801711000000" class="announcement-link">
            <v-icon size="14" class="mr-1">mdi-phone-outline</v-icon>Hotline: 01711-000000
          </a>
        </div>
      </div>
    </div>

    <!-- ═════════ 1. Hero + Category Rail + Compare Tool ═════════ -->
    <div class="wrap">
      <div class="hero-row">
        <!-- Left Side: Category Menu with Badges -->
        <aside class="sidemenu">
          <div class="sidemenu__header">
            <v-icon size="18" color="#0a5548">mdi-format-list-bulleted</v-icon>
            <span>Browse Categories</span>
          </div>
          <div class="sidemenu__list">
            <a v-for="c in categories" :key="c.slug" :href="'/c/' + c.slug" class="sidemenu__item">
              <v-icon :icon="c.icon" size="18" />
              <span class="sidemenu__name">{{ c.name }}</span>
              <span v-if="c.isHot" class="pill-hot">HOT</span>
              <v-icon v-if="c.children" icon="mdi-chevron-right" size="16" class="sidemenu__chev" />
            </a>
            <a href="/clearance" class="sidemenu__item sidemenu__item--accent">
              <v-icon icon="mdi-tag-multiple-outline" size="18" />
              <span class="sidemenu__name">Clearance Deals</span>
              <span class="pill-sale">-40%</span>
            </a>
          </div>
          <div class="sidemenu__footer">
            <v-icon size="16" color="#0f9d6b">mdi-headset</v-icon>
            <div>
              <div class="text-caption font-weight-bold">Need tech advice?</div>
              <div class="text-micro text-muted">Talk with our gadget specialists</div>
            </div>
          </div>
        </aside>

        <!-- Center: Dynamic Slider Banner -->
        <div class="hero">
          <v-carousel
            v-model="slide"
            cycle
            interval="5500"
            height="100%"
            hide-delimiters
            :show-arrows="false"
            class="slider"
          >
            <v-carousel-item v-for="(s, i) in slides" :key="i">
              <div class="slide" :style="{ background: s.bgGradient }">
                <div class="slide__copy">
                  <span class="slide__eyebrow">
                    <v-icon size="13" class="mr-1">{{ s.eyebrowIcon }}</v-icon>{{ s.eyebrow }}
                  </span>
                  <h1 class="slide__title">{{ s.title }}</h1>
                  <p class="slide__text">{{ s.text }}</p>
                  <div class="d-flex align-center ga-3 flex-wrap">
                    <v-btn flat class="btn text-none" :href="s.href">{{ s.cta }}</v-btn>
                    <v-btn variant="outlined" class="btn btn--ghost text-none" @click="openComparePreset(s.presetCompare)">
                      <v-icon size="16" class="mr-1">mdi-compare-horizontal</v-icon>Compare Models
                    </v-btn>
                  </div>
                </div>

                <!-- Floating Product Hero Badge / Graphic -->
                <div class="slide__badge-card d-none d-md-flex">
                  <div class="slide__badge-icon"><v-icon size="28" color="#0a5548">{{ s.badgeIcon }}</v-icon></div>
                  <div>
                    <div class="text-caption text-uppercase font-weight-bold text-muted">{{ s.badgeTag }}</div>
                    <div class="font-weight-bold text-body-2">{{ s.badgeTitle }}</div>
                    <div class="text-caption text-success font-weight-bold">{{ s.badgePrice }}</div>
                  </div>
                </div>
              </div>
            </v-carousel-item>
          </v-carousel>

          <!-- Slider Dots Indicator -->
          <div class="slider-dots">
            <span
              v-for="(_, idx) in slides"
              :key="idx"
              class="slider-dot"
              :class="{ active: slide === idx }"
              @click="slide = idx"
            ></span>
          </div>

          <div class="hero-line"></div>

          <!-- Bottom Hero Stats Strip -->
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

        <!-- Right Side: Fast Compare + Calculators Rail -->
        <div class="rail">
          <div class="tt-card tt-green compare">
            <div class="tt-head">
              <span class="tt-dot"></span>
              <span class="tt-card-title">Spec Comparison Hub</span>
              <v-spacer />
              <span class="tt-tag">Side-by-side</span>
            </div>
            <div class="tt-body">
              <div class="tt-sub mb-2">Compare true specs before purchasing:</div>
              <v-autocomplete
                v-model="compareA"
                :items="productNames"
                placeholder="Product 1 (e.g. Baseus E9)"
                prepend-inner-icon="mdi-magnify"
                variant="solo"
                flat
                density="compact"
                hide-details
                hide-no-data
                class="compare__field mb-2"
              />
              <div class="compare__vs">VS</div>
              <v-autocomplete
                v-model="compareB"
                :items="productNames"
                placeholder="Product 2 (e.g. Sony CH720N)"
                prepend-inner-icon="mdi-magnify"
                variant="solo"
                flat
                density="compact"
                hide-details
                hide-no-data
                class="compare__field"
              />

              <!-- Quick matchup presets -->
              <div class="compare-presets mt-2">
                <span class="text-micro text-muted">Trending:</span>
                <button type="button" class="preset-btn" @click="setPreset('Baseus Bowie E9 True Wireless Earbuds', 'Sony WH-CH720N Noise Cancelling Headphones')">Earbuds</button>
                <button type="button" class="preset-btn" @click="setPreset('SanDisk Extreme Portable SSD 1TB', 'Samsung 990 EVO NVMe SSD 500GB')">SSDs</button>
              </div>

              <v-btn
                block
                flat
                class="compare__btn text-none"
                :disabled="!compareA || !compareB"
                @click="goCompare"
              >
                Compare Specs Now
              </v-btn>
            </div>
          </div>

          <a v-for="t in tools" :key="t.title" :href="t.href" class="tool">
            <span class="tool__ic"><v-icon :icon="t.icon" size="20" /></span>
            <div class="flex-grow-1">
              <b>{{ t.title }}</b>
              <span>{{ t.sub }}</span>
            </div>
            <v-icon size="16" class="text-muted">mdi-arrow-right</v-icon>
          </a>
        </div>
      </div>
    </div>

    <!-- ═════════ 2. Trust & Guarantee Strip ═════════ -->
    <div class="trust">
      <div class="wrap">
        <div class="trust__grid">
          <div v-for="s in services" :key="s.title" class="trust__item">
            <div class="trust__icon-wrap">
              <v-icon :icon="s.icon" size="22" />
            </div>
            <div>
              <b>{{ s.title }}</b>
              <span>{{ s.sub }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="wrap">
      <!-- ═════════ 3. Trending Tags Ribbon ═════════ -->
      <div class="trending-ribbon mt-4 mb-2 d-flex align-center ga-2 flex-wrap">
        <span class="text-caption font-weight-bold text-muted d-flex align-center">
          <v-icon size="15" color="#f59e0b" class="mr-1">mdi-fire</v-icon>Trending Tech:
        </span>
        <button
          v-for="tag in trendingTags"
          :key="tag.label"
          type="button"
          class="trending-tag"
          @click="applyTagFilter(tag.query)"
        >
          {{ tag.label }}
        </button>
      </div>

      <!-- ═════════ 4. Shop by Category ═════════ -->
      <div class="tt-section">
        <v-icon size="16">mdi-view-grid-outline</v-icon>
        <span>Shop by Product Category</span>
      </div>
      <v-card flat class="tt-card tt-blue">
        <div class="tt-head">
          <span class="tt-dot"></span>
          <span class="tt-card-title">Hardware &amp; Accessories Ecosystem</span>
          <v-spacer />
          <span class="tt-tag">{{ money(totalProducts) }} verified products in stock</span>
        </div>
        <div class="tt-body">
          <div class="cats">
            <a v-for="c in categories" :key="c.slug" :href="'/c/' + c.slug" class="cat">
              <span class="cat__ic" :style="{ background: c.colorBg, color: c.colorText }">
                <v-icon :icon="c.icon" size="22" />
              </span>
              <b>{{ c.short }}</b>
              <span class="cat__count">{{ c.count }} items</span>
            </a>
          </div>
        </div>
      </v-card>

      <!-- ═════════ 5. Deals of the Day (Flash Sale) ═════════ -->
      <div class="tt-section">
        <v-icon size="16" color="#f59e0b">mdi-flash</v-icon>
        <span>Flash Sale · Deals of the Day</span>
      </div>
      <v-card flat class="tt-card tt-amber">
        <div class="tt-head">
          <span class="tt-dot"></span>
          <span class="tt-card-title">Limited-stock pricing resets every midnight</span>
          <v-spacer />
          <span class="tt-tag warn">
            <v-icon size="13">mdi-clock-outline</v-icon>
            Ends in: {{ pad(countdown.h) }}h : {{ pad(countdown.m) }}m : {{ pad(countdown.s) }}s
          </span>
        </div>
        <div class="tt-body">
          <div class="deals-rail">
            <article v-for="p in deals" :key="p.id" class="card deal-card">
              <div class="card__media">
                <span class="card__tag card__tag--deal">-{{ discount(p) }}% OFF</span>
                <span class="card__warranty-badge"><v-icon size="11">mdi-shield-check</v-icon> Official</span>
                <img :src="p.image" :alt="p.name" loading="lazy" />
                <div class="quick">
                  <button class="quick__btn" title="Save to wishlist" @click="toggleWishlist(p)">
                    <v-icon :icon="isWishlisted(p) ? 'mdi-heart' : 'mdi-heart-outline'" size="15" :color="isWishlisted(p) ? '#dc2626' : ''" />
                  </button>
                  <button class="quick__btn" title="Compare" @click="addCompare(p)">
                    <v-icon icon="mdi-compare-horizontal" size="15" />
                  </button>
                  <button class="quick__btn" title="Quick view" @click="openQuickView(p)">
                    <v-icon icon="mdi-eye-outline" size="15" />
                  </button>
                </div>
              </div>
              <div class="card__body">
                <div class="rating">
                  <span class="rating__stars">{{ stars(p.rating) }}</span>
                  <span>({{ p.reviews }})</span>
                </div>
                <a :href="p.href" class="card__name">{{ p.name }}</a>
                <div class="card__price">
                  <span class="now">৳{{ money(p.price) }}</span>
                  <span class="was">৳{{ money(p.oldPrice) }}</span>
                </div>
                <!-- Stock progress -->
                <div class="bar"><i :style="{ width: p.sold + '%' }"></i></div>
                <div class="d-flex align-center justify-space-between bar__label">
                  <span>{{ p.sold }}% Claimed</span>
                  <span class="font-weight-bold text-error">Only {{ p.stock }} left</span>
                </div>
                <div class="card__acts mt-2">
                  <button class="buy" @click="addToCart(p)">
                    <v-icon size="14" class="mr-1">mdi-cart-plus</v-icon>Add to cart
                  </button>
                  <button class="alt" title="Instant Buy" @click="buyNow(p)">
                    <v-icon icon="mdi-lightning-bolt" size="16" color="#f59e0b" />
                  </button>
                </div>
              </div>
            </article>
          </div>
        </div>
      </v-card>

      <!-- ═════════ 6. Curated Category Showcase (Interactive Spotlight) ═════════ -->
      <div class="spotlight-grid mt-4">
        <div class="spotlight-card spotlight-card--green">
          <div class="spotlight-copy">
            <span class="spotlight-kicker">WORK FROM HOME ESSENTIALS</span>
            <h3>High-speed 100W GaN Charging &amp; Docks</h3>
            <p>Power your MacBook, iPad, and smartphone from a single wall socket.</p>
            <v-btn flat class="spotlight-btn text-none" href="/c/chargers">Shop Charging Hubs</v-btn>
          </div>
          <div class="spotlight-badge">Up to 30% Off</div>
        </div>

        <div class="spotlight-card spotlight-card--blue">
          <div class="spotlight-copy">
            <span class="spotlight-kicker">AUDIOPHILE &amp; COMMUTE</span>
            <h3>Active Noise Cancellation Headsets</h3>
            <p>Silence city noise in Dhaka traffic with hybrid ANC and LDAC hi-res audio.</p>
            <v-btn flat class="spotlight-btn text-none" href="/c/audio">Explore Audio</v-btn>
          </div>
          <div class="spotlight-badge">From ৳1,890</div>
        </div>
      </div>

      <!-- ═════════ 7. Best-Selling Products with Real Filter Tabs ═════════ -->
      <div class="tt-section">
        <v-icon size="16">mdi-star-outline</v-icon>
        <span>Popular Right Now · Verified Customer Favorites</span>
      </div>
      <v-card flat class="tt-card tt-green">
        <div class="tt-head">
          <span class="tt-dot"></span>
          <span class="tt-card-title">Best-selling electronics</span>
          <v-spacer />
          <div class="tabs">
            <button
              v-for="t in tabs"
              :key="t"
              class="tabs__btn"
              :class="{ 'tabs__btn--on': activeTab === t }"
              @click="activeTab = t"
            >
              {{ t }}
            </button>
          </div>
        </div>
        <div class="tt-body">
          <div class="grid">
            <article v-for="p in visibleProducts" :key="p.id" class="card">
              <div class="card__media">
                <span v-if="p.badge" class="card__tag" :class="'card__tag--' + p.badge.type">{{ p.badge.text }}</span>
                <span v-else-if="p.oldPrice" class="card__tag">-{{ discount(p) }}%</span>
                <span class="card__warranty-badge"><v-icon size="11">mdi-shield-check</v-icon> Genuine</span>
                <img :src="p.image" :alt="p.name" loading="lazy" />

                <!-- Action overlay -->
                <div class="quick">
                  <button class="quick__btn" title="Save to wishlist" @click="toggleWishlist(p)">
                    <v-icon :icon="isWishlisted(p) ? 'mdi-heart' : 'mdi-heart-outline'" size="15" :color="isWishlisted(p) ? '#dc2626' : ''" />
                  </button>
                  <button class="quick__btn" title="Compare" @click="addCompare(p)">
                    <v-icon icon="mdi-compare-horizontal" size="15" />
                  </button>
                  <button class="quick__btn" title="Quick view specs" @click="openQuickView(p)">
                    <v-icon icon="mdi-eye-outline" size="15" />
                  </button>
                </div>
              </div>

              <div class="card__body">
                <div class="rating">
                  <span class="rating__stars">{{ stars(p.rating) }}</span>
                  <span>({{ p.reviews }})</span>
                </div>
                <a :href="p.href" class="card__name">{{ p.name }}</a>
                
                <!-- Key Specs List -->
                <ul class="specs">
                  <li v-for="(s, i) in p.specs" :key="i">{{ s }}</li>
                </ul>

                <div class="card__price">
                  <span class="now">৳{{ money(p.price) }}</span>
                  <span v-if="p.oldPrice" class="was">৳{{ money(p.oldPrice) }}</span>
                </div>
                
                <div class="card__note">
                  <v-icon size="12" class="mr-1 text-success">mdi-check-circle-outline</v-icon>{{ emiLine(p) }}
                </div>

                <div class="card__acts">
                  <button class="buy" @click="addToCart(p)">Add to cart</button>
                  <button class="alt" title="Buy now with 1-click" @click="buyNow(p)">
                    <v-icon icon="mdi-lightning-bolt-outline" size="16" />
                  </button>
                </div>
              </div>
            </article>
          </div>
        </div>
      </v-card>

      <!-- ═════════ 8. Interactive Warranty Verification Widget ═════════ -->
      <div class="warranty-widget mt-4">
        <div class="warranty-widget__card">
          <div class="d-flex align-center ga-3 mb-2">
            <div class="warranty-icon"><v-icon size="26" color="white">mdi-shield-check</v-icon></div>
            <div>
              <h3 class="warranty-title">Authenticity &amp; Warranty Verification Portal</h3>
              <p class="warranty-sub mb-0">Purchased a gadget from us or our partner stores? Verify your warranty &amp; serial coverage online.</p>
            </div>
          </div>
          <div class="warranty-form mt-3">
            <v-text-field
              v-model="warrantyQuery"
              placeholder="Enter product IMEI / Serial Number / Invoice ID (e.g. TT-88029)"
              variant="solo"
              flat
              density="comfortable"
              hide-details
              prepend-inner-icon="mdi-barcode-scan"
              class="warranty-input"
            />
            <v-btn flat class="warranty-btn text-none" @click="verifyWarranty">Check Coverage</v-btn>
          </div>
        </div>
      </div>

      <!-- ═════════ 9. Promo Campaign Banners ═════════ -->
      <div class="promos">
        <a v-for="b in banners" :key="b.title" :href="b.href" class="promo" :style="{ background: b.bg }">
          <small>{{ b.kicker }}</small>
          <b>{{ b.title }}</b>
          <span class="promo__go">{{ b.cta }} →</span>
        </a>
      </div>

      <!-- ═════════ 10. Official Brand Partners & EMI Banks ═════════ -->
      <div class="tt-section">
        <v-icon size="16">mdi-shield-check-outline</v-icon>
        <span>Authorized Brand Importers &amp; Partners</span>
      </div>
      <v-card flat class="tt-card tt-purple">
        <div class="tt-head">
          <span class="tt-dot"></span>
          <span class="tt-card-title">Every unit sourced directly through authorized national distribution channels</span>
          <v-spacer />
          <span class="tt-tag">100% Genuine</span>
        </div>
        <div class="tt-body">
          <div class="brands">
            <a v-for="b in brands" :key="b.name" :href="b.href" class="brands__tile">
              <span class="font-weight-bold">{{ b.name }}</span>
              <span class="text-micro text-muted">Official Partner</span>
            </a>
          </div>

          <!-- 0% EMI Supported Banks -->
          <div class="emi-banks mt-4 pt-3 border-top">
            <div class="d-flex align-center ga-3 flex-wrap">
              <span class="text-caption font-weight-bold text-muted d-flex align-center">
                <v-icon size="16" class="mr-1" color="#7c3aed">mdi-credit-card-outline</v-icon>0% EMI available on credit cards from:
              </span>
              <div class="d-flex align-center ga-2 flex-wrap">
                <span v-for="bank in emiBanks" :key="bank" class="bank-pill">{{ bank }}</span>
              </div>
            </div>
          </div>
        </div>
      </v-card>

      <!-- ═════════ 11. Verified Customer Testimonials ═════════ -->
      <div class="tt-section">
        <v-icon size="16" color="#0f9d6b">mdi-comment-quote-outline</v-icon>
        <span>Verified Buyer Experiences</span>
      </div>
      <v-row dense>
        <v-col v-for="rev in reviews" :key="rev.author" cols="12" md="4">
          <div class="review-card">
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="rating__stars">{{ stars(5) }}</div>
              <span class="text-micro text-success font-weight-bold d-flex align-center">
                <v-icon size="12" class="mr-1">mdi-check-decagram</v-icon>Verified Buyer
              </span>
            </div>
            <p class="review-text">"{{ rev.text }}"</p>
            <div class="d-flex align-center justify-space-between mt-3 pt-2 border-top">
              <div>
                <div class="font-weight-bold text-body-2">{{ rev.author }}</div>
                <div class="text-micro text-muted">{{ rev.location }}</div>
              </div>
              <span class="text-micro text-muted font-italic">{{ rev.product }}</span>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- ═════════ 12. Buying Guides ═════════ -->
      <div class="tt-section">
        <v-icon size="16">mdi-book-open-variant</v-icon>
        <span>Tech Insights &amp; Hardware Buying Guides</span>
      </div>
      <v-row dense>
        <v-col v-for="p in posts" :key="p.title" cols="12" md="4">
          <v-card flat class="tt-card tt-blue h-100">
            <div class="tt-body">
              <div class="tt-sub mb-2">{{ p.category }} · {{ p.read }} min read</div>
              <a :href="p.href" class="post__title">{{ p.title }}</a>
              <p class="post__excerpt">{{ p.excerpt }}</p>
              <div class="mt-3">
                <a :href="p.href" class="text-primary text-caption font-weight-bold">Read full guide →</a>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- ═════════ 13. Showrooms + VIP Newsletter ═════════ -->
      <div class="tt-section">
        <v-icon size="16">mdi-map-marker-outline</v-icon>
        <span>Physical Showrooms &amp; VIP Tech Club</span>
      </div>
      <v-row dense class="mb-8">
        <v-col cols="12" lg="7">
          <v-card flat class="tt-card tt-teal h-100">
            <div class="tt-head">
              <span class="tt-dot"></span>
              <span class="tt-card-title">Experience Showrooms &amp; Warranty Service Hubs</span>
              <v-spacer />
              <span class="tt-tag">Try Before You Buy</span>
            </div>
            <div class="tt-body">
              <div class="tt-sub mb-3">
                Visit our physical stores for hands-on demos, instant device replacement, or same-day pickup of online orders:
              </div>
              <div class="showroom__grid">
                <div v-for="s in showrooms" :key="s.city" class="showroom__card">
                  <div class="d-flex align-center justify-space-between">
                    <b>{{ s.city }}</b>
                    <span v-if="s.isServiceHub" class="service-pill">Warranty Hub</span>
                  </div>
                  <span>{{ s.address }}<br />{{ s.hours }} · {{ s.phone }}</span>
                </div>
              </div>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" lg="5">
          <v-card flat class="tt-card tt-green h-100 news">
            <div class="tt-head">
              <span class="tt-dot"></span>
              <span class="tt-card-title">VIP Tech Club · Price Drops &amp; Stock Alerts</span>
            </div>
            <div class="tt-body">
              <p class="news__lead">No spam. Only early access to limited gadget drops, coupon codes, and tech reviews.</p>
              <form class="news__form" @submit.prevent="subscribe">
                <v-text-field
                  v-model="email"
                  type="email"
                  placeholder="Enter your email address"
                  variant="solo"
                  flat
                  density="comfortable"
                  hide-details
                  bg-color="#fff"
                />
                <v-btn flat type="submit" class="news__btn text-none" :loading="subscribing">Join Club</v-btn>
              </form>
              <div class="d-flex align-center ga-2 mt-3 text-micro text-muted">
                <v-icon size="14" color="#0f9d6b">mdi-lock-outline</v-icon>
                <span>We respect your privacy. Unsubscribe anytime with 1 click.</span>
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- ═════════ Quick View Modal ═════════ -->
    <v-dialog v-model="quickViewDialog" max-width="680">
      <v-card v-if="quickProduct" class="quick-modal">
        <div class="d-flex align-center justify-space-between pa-4 border-bottom">
          <div class="font-weight-bold text-body-1">Quick Product Overview</div>
          <v-btn icon="mdi-close" variant="text" size="small" @click="quickViewDialog = false" />
        </div>
        <v-card-text class="pa-4">
          <v-row dense>
            <v-col cols="12" sm="5">
              <div class="quick-modal-img">
                <img :src="quickProduct.image" :alt="quickProduct.name" />
              </div>
            </v-col>
            <v-col cols="12" sm="7" class="pl-sm-4">
              <span class="text-caption text-uppercase font-weight-bold text-success">{{ quickProduct.group || 'Gadget' }}</span>
              <h3 class="text-h6 font-weight-bold mt-1 mb-2">{{ quickProduct.name }}</h3>
              
              <div class="d-flex align-center ga-2 mb-3">
                <div class="rating__stars">{{ stars(quickProduct.rating) }}</div>
                <span class="text-caption text-muted">({{ quickProduct.reviews }} customer reviews)</span>
              </div>

              <div class="d-flex align-baseline ga-2 mb-3">
                <span class="now text-h5">৳{{ money(quickProduct.price) }}</span>
                <span v-if="quickProduct.oldPrice" class="was">৳{{ money(quickProduct.oldPrice) }}</span>
              </div>

              <div class="text-caption font-weight-bold text-muted mb-2">Technical Specifications:</div>
              <ul class="specs mb-4">
                <li v-for="(s, idx) in quickProduct.specs" :key="idx">{{ s }}</li>
              </ul>

              <div class="d-flex align-center ga-2">
                <v-btn flat color="#0a5548" class="text-none flex-grow-1" @click="addToCart(quickProduct)">
                  <v-icon size="16" class="mr-1">mdi-cart-plus</v-icon>Add to Cart
                </v-btn>
                <v-btn variant="outlined" color="#0a5548" class="text-none" @click="buyNow(quickProduct)">
                  Buy Now
                </v-btn>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Global Toast Notification -->
    <v-snackbar v-model="toast.show" :color="toast.color" timeout="2500" location="bottom right">
      {{ toast.text }}
    </v-snackbar>
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
      warrantyQuery: '',

      wishlist: [1, 3], // product IDs saved

      quickViewDialog: false,
      quickProduct: null,

      toast: { show: false, text: '', color: 'success' },

      slides: [
        {
          eyebrow: 'Eid Tech Fest · Limited Stock',
          eyebrowIcon: 'mdi-sale',
          title: 'Charge faster, carry lighter, work everywhere.',
          text: 'Official GaN chargers, 100W/240W braided cables & slim high-capacity power banks — up to 30% off with 1-year replacement warranty.',
          cta: 'Shop Charging Ecosystem',
          href: '/c/chargers',
          bgGradient: 'linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b5f50 100%)',
          presetCompare: { a: 'UGREEN 100W USB-C Braided Cable, 2m', b: 'Anker HDMI 2.1 Ultra High Speed Cable, 3m' },
          badgeIcon: 'mdi-lightning-bolt',
          badgeTag: 'Top Pick',
          badgeTitle: 'UGREEN 100W GaN',
          badgePrice: '৳ 3,850',
        },
        {
          eyebrow: 'Hi-Res Audio Week',
          eyebrowIcon: 'mdi-headphones',
          title: 'Pure acoustics that isolate the city noise.',
          text: 'Hybrid active noise cancelling, dual-driver wireless earbuds & studio monitor headphones starting from ৳1,890.',
          cta: 'Explore ANC Audio',
          href: '/c/audio',
          bgGradient: 'linear-gradient(135deg, #1b357a 0%, #2f5be7 60%, #4f78f5 100%)',
          presetCompare: { a: 'Baseus Bowie E9 True Wireless Earbuds', b: 'Sony WH-CH720N Noise Cancelling Headphones' },
          badgeIcon: 'mdi-music',
          badgeTag: 'LDAC Certified',
          badgeTitle: 'Sony WH-CH720N',
          badgePrice: '৳ 12,400',
        },
        {
          eyebrow: 'Next-Gen Wireless',
          eyebrowIcon: 'mdi-wifi',
          title: 'Wi-Fi 6 routers engineered for concrete flats.',
          text: 'Multi-gigabit mesh kits and high-gain beamforming routers to eliminate dead zones in multi-room Dhaka residences.',
          cta: 'Shop Networking Hubs',
          href: '/c/networking',
          bgGradient: 'linear-gradient(135deg, #441a77 0%, #7c3aed 60%, #905cf0 100%)',
          presetCompare: { a: 'TP-Link Archer AX55 Wi-Fi 6 Router', b: 'Orico 7-in-1 USB-C Hub with HDMI' },
          badgeIcon: 'mdi-router-wireless',
          badgeTag: 'Wi-Fi 6 AX3000',
          badgeTitle: 'TP-Link Archer AX55',
          badgePrice: '৳ 8,900',
        },
      ],

      heroStats: [
        { icon: 'mdi-cash-multiple', value: '30% off', label: 'Flash promotion deals' },
        { icon: 'mdi-truck-fast-outline', value: 'Same-day', label: 'Dhaka express delivery' },
        { icon: 'mdi-shield-check-outline', value: '12 Months', label: 'Direct replacement guarantee' },
      ],

      trendingTags: [
        { label: '⚡ 100W GaN Chargers', query: 'chargers' },
        { label: '🎧 ANC Earbuds', query: 'audio' },
        { label: '🔋 20,000mAh Power Banks', query: 'power-banks' },
        { label: '⌨️ Mechanical Keyboards', query: 'keyboard-mouse' },
        { label: '💾 1TB Portable SSDs', query: 'storage' },
        { label: '📶 Wi-Fi 6 Routers', query: 'networking' },
      ],

      categories: [
        { slug: 'chargers', name: 'Chargers & Power', short: 'Chargers', icon: 'mdi-flash-outline', count: 168, children: true, colorBg: '#fff7ed', colorText: '#ea580c', isHot: true },
        { slug: 'audio', name: 'Audio & ANC Buds', short: 'Headphones', icon: 'mdi-headphones', count: 96, children: true, colorBg: '#eef2ff', colorText: '#4f46e5', isHot: true },
        { slug: 'cables', name: 'Cables & Docks', short: 'Cables', icon: 'mdi-usb-port', count: 214, children: true, colorBg: '#ecfdf5', colorText: '#059669' },
        { slug: 'storage', name: 'SSDs & Flash Drives', short: 'Storage', icon: 'mdi-harddisk', count: 74, children: true, colorBg: '#fef2f2', colorText: '#dc2626' },
        { slug: 'keyboard-mouse', name: 'Keyboards & Mice', short: 'Keyboards', icon: 'mdi-keyboard-outline', count: 132, children: true, colorBg: '#faf5ff', colorText: '#9333ea' },
        { slug: 'power-banks', name: 'MagSafe & Banks', short: 'Power banks', icon: 'mdi-battery-charging-70', count: 58, colorBg: '#f0fdf4', colorText: '#16a34a' },
        { slug: 'networking', name: 'Wi-Fi 6 & Routers', short: 'Networking', icon: 'mdi-router-wireless', count: 89, children: true, colorBg: '#f0f9ff', colorText: '#0284c7' },
        { slug: 'cooling', name: 'Laptop Cooling', short: 'Cooling', icon: 'mdi-fan', count: 41, colorBg: '#f5f3ff', colorText: '#7c3aed' },
      ],

      tools: [
        { icon: 'mdi-credit-card-outline', title: '0% EMI Calculator', sub: 'Calculate monthly plans for 3, 6, 12 mo', href: '/emi' },
        { icon: 'mdi-shield-search', title: 'IMEI / Serial Lookup', sub: 'Verify your genuine warranty card', href: '/check-warranty' },
        { icon: 'mdi-air-conditioner', title: 'AC Ton Calculator', sub: 'Find exact BTU required for your room', href: '/ac-ton-calculator' },
      ],

      services: [
        { icon: 'mdi-truck-fast-outline', title: 'Same-day in Dhaka', sub: 'Order by 2pm, receive today' },
        { icon: 'mdi-shield-check-outline', title: 'Official Importer Warranty', sub: '7-day replacement guarantee' },
        { icon: 'mdi-wallet-outline', title: 'Cash on Delivery & bKash', sub: 'Safe payment upon inspection' },
        { icon: 'mdi-headset', title: 'Hardware Support Desk', sub: 'Dedicated tech assistance 7 days/wk' },
      ],

      tabs: ['All', 'Cables', 'Audio', 'Storage', 'Accessories'],

      products: [
        { id: 1, group: 'Audio', name: 'Baseus Bowie E9 True Wireless Earbuds', price: 2350, oldPrice: 2990, rating: 5, reviews: 318, image: '/images/products/earbuds.webp', href: '/p/baseus-e9', specs: ['Bluetooth 5.3 Low Latency', '35hr Total Playback', 'IPX5 Splash Proof'] },
        { id: 2, group: 'Cables', name: 'UGREEN 100W USB-C Braided Cable, 2m', price: 1190, oldPrice: 1450, rating: 5, reviews: 204, image: '/images/products/usbc-cable.webp', href: '/p/ugreen-100w', specs: ['100W Power Delivery 3.0', 'E-Marker Smart Chip', 'Reinforced Braided Nylon'] },
        { id: 3, group: 'Storage', name: 'SanDisk Extreme Portable SSD 1TB', price: 11500, oldPrice: 12900, rating: 5, reviews: 96, image: '/images/products/ssd.webp', href: '/p/sandisk-1tb', badge: { type: 'gold', text: 'Best seller' }, specs: ['1050MB/s Read Speed', 'IP65 Water & Dust Resistance', '5-Year Official Warranty'] },
        { id: 4, group: 'Accessories', name: 'Logitech MX Master 3S Wireless Mouse', price: 12800, oldPrice: null, rating: 5, reviews: 147, image: '/images/products/mouse.webp', href: '/p/mx-master-3s', badge: { type: 'new', text: 'New' }, specs: ['8000 DPI Darkfield Sensor', '90% Quieter Clicks', 'MagSpeed Electromagnetic Scroll'] },
        { id: 5, group: 'Cables', name: 'Anker HDMI 2.1 Ultra High Speed Cable, 3m', price: 2100, oldPrice: 2600, rating: 4, reviews: 41, image: '/images/products/hdmi.webp', href: '/p/anker-hdmi', specs: ['8K@60Hz / 4K@120Hz Support', '48Gbps Bandwidth', 'Dynamic HDR & eARC'] },
        { id: 6, group: 'Accessories', name: 'Xiaomi 20000mAh 22.5W Power Bank', price: 3450, oldPrice: 3900, rating: 4, reviews: 73, image: '/images/products/powerbank.webp', href: '/p/xiaomi-20000', specs: ['20000mAh Li-Po Capacity', '22.5W Two-Way Fast Charge', 'Triple Output Ports'] },
        { id: 7, group: 'Audio', name: 'Edifier R1280DB Bookshelf Speakers', price: 14900, oldPrice: 16500, rating: 5, reviews: 52, image: '/images/products/speakers.webp', href: '/p/edifier-r1280db', specs: ['42W RMS Pure Output', 'Optical, Coaxial & Bluetooth', 'Wood Finish Acoustic Cabinet'] },
        { id: 8, group: 'Storage', name: 'Samsung 990 EVO NVMe SSD 500GB', price: 7900, oldPrice: 8600, rating: 5, reviews: 118, image: '/images/products/nvme.webp', href: '/p/samsung-990-evo', specs: ['5000MB/s Read, PCIe 4.0 x4', 'Thermal Guard Controller', 'V-NAND 3-bit MLC'] },
        { id: 9, group: 'Accessories', name: 'Keychron K2 Pro Mechanical Keyboard', price: 10500, oldPrice: 11900, rating: 5, reviews: 89, image: '/images/products/keyboard.webp', href: '/p/keychron-k2-pro', specs: ['Wireless Bluetooth & Wired', 'Hot-Swappable Switches', 'QMK / VIA Programmable'] },
        { id: 10, group: 'Cables', name: 'Orico 7-in-1 USB-C Hub with HDMI', price: 3200, oldPrice: 3800, rating: 4, reviews: 66, image: '/images/products/hub.webp', href: '/p/orico-hub', specs: ['4K@30Hz HDMI Output', 'SD/TF High-Speed Slots', '100W PD Pass-through'] },
        { id: 11, group: 'Audio', name: 'Sony WH-CH720N Noise Cancelling Headphones', price: 12400, oldPrice: 13900, rating: 5, reviews: 175, image: '/images/products/headphones.webp', href: '/p/sony-ch720n', specs: ['Integrated V1 ANC Processor', '35 Hours Battery Life', 'Multipoint Dual Connection'] },
        { id: 12, group: 'Storage', name: 'SanDisk Ultra microSDXC 256GB', price: 2250, oldPrice: 2700, rating: 4, reviews: 233, image: '/images/products/microsd.webp', href: '/p/sandisk-256', specs: ['150MB/s Read Speed', 'A1 App Performance Class', 'Full Size SD Adapter Included'] },
      ],

      deals: [
        { id: 101, name: 'Baseus 65W GaN Fast Charger, 3 ports', price: 3290, oldPrice: 4500, sold: 78, stock: 11, rating: 5, reviews: 142, image: '/images/products/charger.webp', href: '/p/baseus-65w', specs: ['65W GaN 5th Gen', '2x USB-C + 1x USB-A', 'BPS II Smart Power'] },
        { id: 102, name: 'TP-Link Archer AX55 Wi-Fi 6 Router', price: 8900, oldPrice: 11200, sold: 45, stock: 24, rating: 4, reviews: 63, image: '/images/products/router.webp', href: '/p/archer-ax55', specs: ['AX3000 Dual-Band', 'Qualcomm Quad-Core', 'OneMesh Compatible'] },
        { id: 103, name: 'Havit HV-F2056 Slim Laptop Cooling Pad', price: 1450, oldPrice: 1990, sold: 62, stock: 18, rating: 4, reviews: 88, image: '/images/products/cooler.webp', href: '/p/havit-f2056', specs: ['3x Ultra-Quiet Fans', 'Metal Mesh Top Plate', 'Dual USB Hub Pass'] },
        { id: 104, name: 'A4Tech Fstyler Wireless Keyboard & Mouse', price: 2650, oldPrice: 3300, sold: 91, stock: 4, rating: 5, reviews: 210, image: '/images/products/combo.webp', href: '/p/a4tech-combo', specs: ['2.4GHz Anti-Interference', 'Silent Click Keys', 'Drain Holes Design'] },
        { id: 105, name: 'Rapoo VT300 Ergonomic Gaming Mouse', price: 3100, oldPrice: 3950, sold: 34, stock: 31, rating: 4, reviews: 57, image: '/images/products/gaming-mouse.webp', href: '/p/rapoo-vt300', specs: ['6200 DPI PMW3327 Sensor', '10 Programmable Keys', 'RGB Backlight Chroma'] },
      ],

      banners: [
        { kicker: 'Ergonomic Workspaces', title: 'Hubs, docks and monitor arms from ৳990', cta: 'Shop workspace', href: '/c/workspace', bg: 'linear-gradient(120deg,#053d35,#0b5f50)' },
        { kicker: 'Acoustic Soundstage', title: 'Up to 30% off Hi-Res Bluetooth audio', cta: 'Shop audio', href: '/c/audio', bg: 'linear-gradient(135deg,#2f5be7,#5a7ff0)' },
        { kicker: 'Upgrade Exchange', title: 'Trade in older gadgets for instant store credits', cta: 'Get trade value', href: '/trade-in', bg: 'linear-gradient(135deg,#7c3aed,#a06af0)' },
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

      emiBanks: ['City Bank (Amex)', 'BRAC Bank', 'Eastern Bank', 'Standard Chartered', 'DBBL', 'Mutual Trust'],

      reviews: [
        { author: 'Tanvir Ahmed', location: 'Dhanmondi, Dhaka', product: 'UGREEN 100W GaN', text: 'Ordered at 11 AM, arrived in Dhanmondi by 3 PM. Genuine serial checked with manufacturer website without issue.' },
        { author: 'Sadia Rahman', location: 'GEC, Chattogram', product: 'Sony WH-CH720N', text: 'Original packaging with unbroken seal. The warranty card had the official dealer stamp. Sound quality is brilliant.' },
        { author: 'Arif Chowdhury', location: 'Uttara, Dhaka', product: 'Keychron K2 Pro', text: 'The staff in the showroom let me test tactile vs brown switches before taking it home. Excellent service.' },
      ],

      posts: [
        { category: 'Charging Tech', read: 6, title: 'GaN vs Standard Silicon Chargers: What Actually Changes?', excerpt: 'Smaller footprint, lower thermal load, and significantly safer output per watt. When is the upgrade worth it?', href: '/guides/gan-chargers' },
        { category: 'High-Speed Storage', read: 8, title: 'Choosing an External SSD for 4K Video Editing in 2026', excerpt: 'Sequential read is marketing. Sustained write speed and thermal throttling determine your render timelines.', href: '/guides/external-ssd' },
        { category: 'Networking', read: 5, title: 'Fixing Weak Wi-Fi in Multi-Room Concrete Flats in Dhaka', excerpt: 'Mesh nodes vs high-gain beamforming extenders — an honest comparison for dense brick & concrete walls.', href: '/guides/wifi-coverage' },
      ],

      showrooms: [
        { city: 'Dhanmondi Showroom', address: 'House 32, Road 8/A (Near Shimanto Square)', hours: '10:00 AM - 08:30 PM', phone: '01711 000 000', isServiceHub: true },
        { city: 'Uttara Flagship', address: 'Sector 7, Garib-e-Newaz Avenue', hours: '10:00 AM - 08:30 PM', phone: '01711 000 001', isServiceHub: false },
        { city: 'Chattogram Hub', address: 'Central Shopping Complex, GEC Circle', hours: '10:30 AM - 08:30 PM', phone: '01711 000 002', isServiceHub: true },
        { city: 'Sylhet Collection Point', address: 'Zindabazar, Mirboxtula Main Road', hours: '10:30 AM - 08:00 PM', phone: '01711 000 003', isServiceHub: false },
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
  },

  beforeUnmount() {
    clearInterval(this.timer)
  },

  methods: {
    money(v) {
      return Number(v || 0).toLocaleString('en-IN')
    },
    discount(p) {
      if (!p.oldPrice) return 0
      return Math.round(((p.oldPrice - p.price) / p.oldPrice) * 100)
    },
    stars(n) {
      return '★'.repeat(n) + '☆'.repeat(5 - n)
    },
    emiLine(p) {
      if (p.price < 5000) return 'In stock · Dispatch today'
      return `From ৳${this.money(Math.round(p.price / 12))}/mo · 12 Mo EMI`
    },
    pad(n) {
      return String(n).padStart(2, '0')
    },
    startCountdown() {
      const end = new Date()
      end.setHours(23, 59, 59, 999)
      const tick = () => {
        const diff = Math.max(0, end - new Date())
        this.countdown = {
          h: Math.floor(diff / 3600000),
          m: Math.floor((diff % 3600000) / 60000),
          s: Math.floor((diff % 60000) / 1000),
        }
      }
      tick()
      this.timer = setInterval(tick, 1000)
    },
    isWishlisted(p) {
      return this.wishlist.includes(p.id)
    },
    toggleWishlist(p) {
      if (this.isWishlisted(p)) {
        this.wishlist = this.wishlist.filter((id) => id !== p.id)
        this.notify('Removed from your wishlist', 'info')
      } else {
        this.wishlist.push(p.id)
        this.notify('Saved to your wishlist', 'success')
      }
    },
    addCompare(p) {
      if (!this.compareA) {
        this.compareA = p.name
        this.notify(`Selected ${p.name} as Product 1`)
      } else if (!this.compareB) {
        this.compareB = p.name
        this.notify(`Selected ${p.name} as Product 2`)
      } else {
        this.compareA = p.name
        this.notify(`Updated Product 1 to ${p.name}`)
      }
    },
    setPreset(a, b) {
      this.compareA = a
      this.compareB = b
      this.notify('Matchup loaded into comparison hub')
    },
    openComparePreset(preset) {
      if (preset) {
        this.compareA = preset.a
        this.compareB = preset.b
      }
      this.goCompare()
    },
    goCompare() {
      if (!this.compareA || !this.compareB) {
        this.notify('Please select two products to compare', 'error')
        return
      }
      this.$router.push({ path: '/compare', query: { a: this.compareA, b: this.compareB } })
    },
    openQuickView(p) {
      this.quickProduct = p
      this.quickViewDialog = true
    },
    addToCart(p) {
      this.notify(`Added "${p.name}" to cart`, 'success')
    },
    buyNow(p) {
      this.$router.push({ path: '/checkout', query: { buy: p.id } })
    },
    verifyWarranty() {
      if (!this.warrantyQuery.trim()) {
        this.notify('Please enter a valid serial or invoice number', 'error')
        return
      }
      this.$router.push({ path: '/check-warranty', query: { q: this.warrantyQuery.trim() } })
    },
    applyTagFilter(q) {
      this.$router.push({ path: '/c/' + q })
    },
    async subscribe() {
      if (!this.email || !this.email.includes('@')) {
        this.notify('Please enter a valid email address', 'error')
        return
      }
      this.subscribing = true
      try {
        await new Promise((res) => setTimeout(res, 400))
        this.email = ''
        this.notify('Welcome to Tanjil Traders VIP Club!', 'success')
      } finally {
        this.subscribing = false
      }
    },
    notify(text, color = 'success') {
      this.toast = { show: true, text, color }
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
  padding-bottom: 24px;
}

.tt-home a { text-decoration: none; color: inherit; }
.tt-home ul { margin: 0; padding: 0; list-style: none; }
.wrap { max-width: 1340px; margin: 0 auto; padding: 0 20px; }

/* ───────── 0. Top announcement bar ───────── */
.announcement-bar {
  background: #05332c;
  color: #fff;
  padding: 7px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.badge-tag {
  background: #f59e0b;
  color: #000;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 4px;
}
.announcement-link {
  color: rgba(255, 255, 255, 0.82);
  transition: color 0.15s;
}
.announcement-link:hover {
  color: #6ee7b7;
}

/* ───────── tt-card system ───────── */
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

/* ───────── Section headings ───────── */
.tt-section {
  display: flex; align-items: center; gap: 8px;
  margin: 26px 0 12px;
  font-size: 12px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase;
  color: #3d5450;
}

/* ───────── 1. Hero row ───────── */
.hero-row { display: grid; grid-template-columns: 240px 1fr 270px; gap: 14px; padding: 18px 0; }

.sidemenu {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px;
  display: flex;
  flex-direction: column;
}
.sidemenu__header {
  display: flex; align-items: center; gap: 8px;
  padding: 12px 14px;
  font-weight: 700;
  font-size: 13px;
  color: #053d35;
  border-bottom: 1px solid var(--line);
  background: #fbfdfc;
  border-radius: 10px 10px 0 0;
}
.sidemenu__list { padding: 6px; flex-grow: 1; }
.sidemenu__item {
  display: flex; align-items: center; gap: 10px;
  padding: 8.5px 12px; border-radius: 7px;
  font-size: 13px; font-weight: 500; color: var(--ink);
  transition: all 0.15s ease;
}
.sidemenu__item:hover { background: #e3f6ee; color: var(--green); }
.sidemenu__item:hover :deep(.v-icon) { color: var(--green); }
.sidemenu__item--accent { color: var(--green); font-weight: 600; }
.sidemenu__chev { margin-left: auto; opacity: 0.45; }
.pill-hot {
  font-size: 9px; font-weight: 800; background: #fee2e2; color: #dc2626;
  padding: 1px 5px; border-radius: 4px; margin-left: auto;
}
.pill-sale {
  font-size: 9px; font-weight: 800; background: #fef3c7; color: #b45309;
  padding: 1px 5px; border-radius: 4px; margin-left: auto;
}
.sidemenu__footer {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px;
  background: #f7faf9;
  border-top: 1px solid var(--line);
  border-radius: 0 0 10px 10px;
}

/* Hero Carousel */
.hero {
  position: relative;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(5, 61, 53, 0.08);
}
.slide {
  height: 310px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 36px;
  color: #fff;
  position: relative;
}
.slide__copy { max-width: 65%; z-index: 2; }
.slide__eyebrow {
  display: inline-flex; align-items: center;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #a7f3d0; font-size: 11.5px; font-weight: 600;
  padding: 4px 12px; border-radius: 99px;
}
.slide__title {
  font-size: 28px; line-height: 1.2; letter-spacing: -0.02em;
  font-weight: 700; margin: 12px 0 8px;
}
.slide__text {
  color: rgba(255, 255, 255, 0.8);
  font-size: 13px; line-height: 1.55; margin: 0 0 18px; max-width: 44ch;
}
.slide__badge-card {
  background: rgba(255, 255, 255, 0.95);
  color: #0f2a26;
  padding: 14px 18px; border-radius: 10px;
  display: flex; align-items: center; gap: 12px;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.5);
  z-index: 2;
}
.slide__badge-icon {
  width: 44px; height: 44px; border-radius: 10px;
  background: #e3f6ee; display: grid; place-items: center;
}
.btn {
  background: var(--green); color: #fff; font-weight: 600;
  border-radius: 8px; height: 40px; padding: 0 18px;
}
.btn:hover { background: #0b7a53; }
.btn--ghost {
  background: transparent; border-color: rgba(255, 255, 255, 0.4);
  color: #fff;
}
.slider-dots {
  position: absolute; bottom: 62px; right: 28px;
  display: flex; gap: 6px; z-index: 3;
}
.slider-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: rgba(255, 255, 255, 0.4); cursor: pointer;
  transition: all 0.2s ease;
}
.slider-dot.active { width: 20px; border-radius: 4px; background: #fff; }

.hero-line {
  height: 2px;
  background: linear-gradient(90deg, #10b981, rgba(16, 185, 129, 0) 70%);
}
.hero-strip {
  display: grid; grid-template-columns: repeat(3, 1fr);
  background: #064037; padding: 12px 24px;
}
.hero-stat {
  display: flex; align-items: center; gap: 10px;
  padding: 2px 14px; border-left: 1px solid rgba(255, 255, 255, 0.14);
}
.hero-stat:first-child { border-left: 0; padding-left: 0; }
.hero-stat__icon {
  width: 28px; height: 28px; border-radius: 6px;
  display: grid; place-items: center;
  background: rgba(255, 255, 255, 0.14); flex: none;
}
.hero-stat__value { font-size: 14.5px; font-weight: 700; color: #fff; line-height: 1.2; }
.hero-stat__label { font-size: 11px; color: rgba(255, 255, 255, 0.65); }

/* Right Rail */
.rail { display: flex; flex-direction: column; gap: 10px; }
.compare .tt-body { padding: 12px 14px 14px; }
.compare__field :deep(.v-field) {
  background: var(--bg); border: 1px solid var(--line);
  border-radius: 7px; font-size: 12px;
}
.compare__vs {
  text-align: center; font-size: 10px; font-weight: 800;
  color: var(--muted); letter-spacing: 0.08em; margin: 4px 0;
}
.compare-presets { display: flex; align-items: center; gap: 4px; }
.preset-btn {
  background: #eef3f1; border: 0; border-radius: 4px;
  font-size: 10.5px; font-weight: 600; color: #0a5548;
  padding: 1px 7px; cursor: pointer;
}
.preset-btn:hover { background: #d7ede4; }
.compare__btn {
  margin-top: 10px; background: #0a5548; color: #fff;
  border-radius: 7px; height: 38px; font-weight: 600;
}

.tool {
  background: #fff; border: 1px solid var(--line);
  border-radius: 9px; padding: 12px 14px;
  display: flex; gap: 10px; align-items: center;
  transition: all 0.15s ease;
}
.tool:hover { border-color: var(--green); transform: translateX(2px); }
.tool__ic {
  width: 36px; height: 36px; border-radius: 8px;
  background: #e3f6ee; display: grid; place-items: center; flex: none;
}
.tool__ic :deep(.v-icon) { color: var(--green); }
.tool b { font-size: 12.5px; font-weight: 600; display: block; }
.tool span { font-size: 11px; color: var(--muted); }

/* ───────── 2. Trust Strip ───────── */
.trust {
  background: #fff; border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line); margin-top: 4px;
}
.trust__grid { display: grid; grid-template-columns: repeat(4, 1fr); }
.trust__item {
  display: flex; gap: 12px; align-items: center;
  padding: 16px 20px; border-left: 1px solid var(--line);
}
.trust__item:first-child { border-left: 0; }
.trust__icon-wrap {
  width: 40px; height: 40px; border-radius: 8px;
  background: #eaf3f0; color: #0a5548;
  display: grid; place-items: center; flex: none;
}
.trust__item b { font-size: 13px; font-weight: 650; display: block; }
.trust__item span { font-size: 11.5px; color: var(--muted); }

/* ───────── 3. Trending Tags ───────── */
.trending-ribbon { padding: 4px 0; }
.trending-tag {
  background: #fff; border: 1px solid var(--line);
  border-radius: 20px; padding: 4px 12px;
  font-size: 11.5px; font-weight: 500; color: var(--ink);
  cursor: pointer; transition: all 0.15s;
}
.trending-tag:hover {
  background: #0a5548; color: #fff; border-color: #0a5548;
}

/* ───────── 4. Categories ───────── */
.cats { display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; }
.cat {
  background: #fff; border: 1px solid var(--line);
  border-radius: 9px; padding: 14px 6px 12px; text-align: center;
  transition: all 0.18s ease;
}
.cat:hover { border-color: var(--blue); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.cat__ic {
  width: 44px; height: 44px; margin: 0 auto 8px;
  border-radius: 10px; display: grid; place-items: center;
}
.cat b { font-size: 12px; font-weight: 600; display: block; line-height: 1.3; }
.cat__count { font-size: 10.5px; color: var(--muted); }

/* ───────── 5. Deals of the day ───────── */
.deals-rail { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
.card__tag--deal { background: #dc2626 !important; }
.card__warranty-badge {
  position: absolute; bottom: 8px; left: 8px;
  background: rgba(15, 42, 38, 0.85); color: #fff;
  font-size: 9.5px; font-weight: 600; padding: 2px 6px;
  border-radius: 4px; backdrop-filter: blur(4px);
}

/* ───────── 6. Spotlight Grid ───────── */
.spotlight-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.spotlight-card {
  position: relative; border-radius: 10px; padding: 24px 28px;
  color: #fff; display: flex; align-items: center; justify-content: space-between;
  overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.spotlight-card--green { background: linear-gradient(135deg, #053d35 0%, #0a5548 100%); }
.spotlight-card--blue  { background: linear-gradient(135deg, #1e3a8a 0%, #2f5be7 100%); }
.spotlight-kicker { font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; opacity: 0.85; }
.spotlight-card h3 { font-size: 18px; font-weight: 700; margin: 6px 0; }
.spotlight-card p { font-size: 12.5px; opacity: 0.85; margin-bottom: 14px; max-width: 38ch; }
.spotlight-btn { background: #fff !important; color: #0f2a26 !important; font-weight: 600; border-radius: 6px; }
.spotlight-badge {
  background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255,255,255,0.3);
  padding: 6px 14px; border-radius: 99px; font-size: 12px; font-weight: 700;
}

/* ───────── 7. Product Cards & Grid ───────── */
.grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
.card {
  background: #fff; border: 1px solid var(--line);
  border-radius: 9px; overflow: hidden; display: flex;
  flex-direction: column; height: 100%;
  transition: all 0.2s ease;
}
.card:hover { border-color: var(--green); box-shadow: 0 10px 22px rgba(5, 61, 53, 0.1); }
.card__media {
  position: relative; aspect-ratio: 1/1; display: grid; place-items: center;
  padding: 14px; overflow: hidden; background: #fafcfb;
}
.card__media img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.25s; }
.card:hover .card__media img { transform: scale(1.05); }
.card__tag {
  position: absolute; top: 8px; left: 8px;
  background: var(--green); color: #fff;
  font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; z-index: 2;
}
.card__tag--new { background: var(--blue); }
.card__tag--gold { background: var(--amber); }

.quick {
  position: absolute; top: 8px; right: 8px;
  display: flex; flex-direction: column; gap: 5px;
  opacity: 0; transform: translateX(6px); transition: 0.18s; z-index: 3;
}
.card:hover .quick { opacity: 1; transform: none; }
.quick__btn {
  width: 28px; height: 28px; border: 1px solid var(--line);
  background: #fff; border-radius: 6px; display: grid; place-items: center;
  cursor: pointer; color: var(--ink); box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.quick__btn:hover { background: var(--green); border-color: var(--green); color: #fff; }

.card__body { padding: 11px; border-top: 1px solid var(--line); display: flex; flex-direction: column; flex: 1; }
.rating { display: flex; align-items: center; gap: 4px; font-size: 10.5px; color: var(--muted); margin-bottom: 5px; }
.rating__stars { color: var(--amber); letter-spacing: 0.5px; }
.card__name {
  font-size: 12.5px; font-weight: 600; line-height: 1.35;
  display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden; min-height: 34px;
}
.card:hover .card__name { color: var(--green); }
.specs { margin-top: 6px; font-size: 10.5px; color: var(--muted); line-height: 1.5; }
.specs li::before {
  content: '✓ '; color: var(--green); font-weight: bold; margin-right: 3px;
}
.card__price { margin-top: auto; padding-top: 8px; display: flex; align-items: baseline; gap: 6px; }
.now { font-size: 15px; font-weight: 800; color: var(--green); letter-spacing: -0.02em; }
.was { font-size: 11px; color: var(--muted); text-decoration: line-through; }
.card__note { font-size: 10px; color: var(--muted); margin-top: 2px; }
.card__acts { display: flex; gap: 6px; margin-top: 10px; }
.buy {
  flex: 1; background: var(--deep); color: #fff; border: 0;
  border-radius: 7px; padding: 7px; font: 600 11.5px inherit;
  cursor: pointer; transition: background 0.15s;
}
.buy:hover { background: var(--green); }
.alt {
  width: 32px; border: 1px solid var(--line); background: #fff;
  border-radius: 7px; display: grid; place-items: center; cursor: pointer; color: var(--ink);
}
.alt:hover { border-color: var(--green); color: var(--green); }
.bar { height: 4px; background: var(--line); border-radius: 99px; margin-top: 8px; overflow: hidden; }
.bar i { display: block; height: 100%; background: var(--amber); }
.bar__label { font-size: 10px; color: var(--muted); margin-top: 4px; }

/* Tabs */
.tabs { display: flex; gap: 4px; flex-wrap: wrap; }
.tabs__btn {
  border: 1px solid var(--line); background: #fff; color: var(--ink);
  font: 600 11.5px inherit; padding: 5px 12px; border-radius: 99px; cursor: pointer;
}
.tabs__btn--on { background: var(--green); color: #fff; border-color: var(--green); }

/* ───────── 8. Warranty Widget ───────── */
.warranty-widget__card {
  background: linear-gradient(135deg, #032b25 0%, #0a5548 100%);
  color: #fff; padding: 22px 28px; border-radius: 10px;
}
.warranty-icon {
  width: 48px; height: 48px; border-radius: 10px;
  background: rgba(255, 255, 255, 0.15); display: grid; place-items: center;
}
.warranty-title { font-size: 18px; font-weight: 700; line-height: 1.2; }
.warranty-sub { font-size: 12.5px; opacity: 0.82; }
.warranty-form { display: flex; gap: 8px; max-width: 720px; }
.warranty-input :deep(.v-field) { border-radius: 8px; background: #fff; color: #000; font-size: 13px; }
.warranty-btn { background: #f59e0b; color: #000; font-weight: 700; border-radius: 8px; height: 44px; }

/* ───────── 9. Promos ───────── */
.promos { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px; margin-top: 22px; }
.promo {
  border-radius: 10px; padding: 22px; min-height: 140px;
  display: flex; flex-direction: column; justify-content: center; color: #fff;
}
.promo small { font-size: 11px; opacity: 0.85; }
.promo b { font-size: 17px; font-weight: 700; line-height: 1.25; margin: 4px 0 12px; max-width: 20ch; }
.promo__go {
  align-self: flex-start; background: #fff; color: var(--deep);
  font: 650 11.5px inherit; padding: 6px 14px; border-radius: 6px;
}

/* ───────── 10. Brands & EMI Banks ───────── */
.brands { display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; }
.brands__tile {
  border: 1px solid var(--line); border-radius: 8px;
  height: 56px; display: flex; flex-direction: column; align-items: center; justify-content: center;
  font-size: 12px; color: var(--ink); transition: all 0.15s;
}
.brands__tile:hover { border-color: var(--purple); color: var(--purple); transform: translateY(-1px); }
.bank-pill {
  background: #f3f0fb; color: #6b21a8; font-size: 11px; font-weight: 600;
  padding: 3px 9px; border-radius: 6px; border: 1px solid #e9d5ff;
}

/* ───────── 11. Testimonials ───────── */
.review-card {
  background: #fff; border: 1px solid var(--line); border-radius: 9px;
  padding: 16px; height: 100%; display: flex; flex-direction: column;
}
.review-text { font-size: 12.5px; line-height: 1.55; color: var(--ink); margin: 0; flex-grow: 1; }

/* ───────── 12. Guides ───────── */
.post__title {
  font-size: 14px; font-weight: 700; display: block; margin-bottom: 5px;
  line-height: 1.35; color: var(--ink);
}
.post__title:hover { color: var(--blue); }
.post__excerpt { font-size: 12px; color: var(--muted); line-height: 1.55; margin: 0; }

/* ───────── 13. Showrooms + Newsletter ───────── */
.showroom__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.showroom__card { border: 1px solid var(--line); border-radius: 8px; padding: 12px; background: #fafcfb; }
.showroom__card b { font-size: 12.5px; font-weight: 650; }
.showroom__card span { font-size: 11px; color: var(--muted); line-height: 1.45; display: block; margin-top: 3px; }
.service-pill {
  font-size: 9.5px; font-weight: 700; background: #ccfbf1; color: #0f766e;
  padding: 1px 6px; border-radius: 4px;
}

.news .tt-body { display: flex; flex-direction: column; }
.news__lead { font-size: 12.5px; color: var(--muted); margin: 0 0 14px; }
.news__form { display: flex; gap: 8px; }
.news__form :deep(.v-field) { border-radius: 7px; }
.news__btn { background: var(--green); color: #fff; border-radius: 7px; height: 48px; padding: 0 20px; font-weight: 650; }
.news__btn:hover { background: #0b7a53; }

/* Quick View Modal */
.quick-modal { border-radius: 12px !important; overflow: hidden; }
.quick-modal-img {
  background: #fafcfb; border-radius: 8px; border: 1px solid var(--line);
  aspect-ratio: 1/1; display: grid; place-items: center; padding: 16px;
}
.quick-modal-img img { max-width: 100%; max-height: 100%; object-fit: contain; }

/* Utility micro styles */
.text-micro { font-size: 10.5px; }
.border-top { border-top: 1px solid var(--line); }
.border-bottom { border-bottom: 1px solid var(--line); }

/* ───────── Responsive Breakpoints ───────── */
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
  .slide__title { font-size: 24px; }
  .slide__copy { max-width: 100%; }
  .rail { flex-direction: row; }
  .spotlight-grid, .promos, .showroom__grid { grid-template-columns: 1fr; }
  .grid { grid-template-columns: repeat(3, 1fr); }
  .trust__grid { grid-template-columns: 1fr 1fr; }
  .warranty-form { flex-direction: column; }
}
@media (max-width: 640px) {
  .grid, .deals-rail { grid-template-columns: repeat(2, 1fr); }
  .cats, .brands { grid-template-columns: repeat(3, 1fr); }
  .trust__grid { grid-template-columns: 1fr; }
  .trust__item { border-left: 0; border-top: 1px solid var(--line); }
  .trust__item:first-child { border-top: 0; }
  .rail { flex-direction: column; }
  .hero-strip { grid-template-columns: 1fr; gap: 8px; }
  .hero-stat { border-left: 0; padding-left: 0; }
  .news__form { flex-direction: column; }
}
</style>
```