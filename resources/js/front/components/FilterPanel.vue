<template>
    <div class="tt-filters">
        <!-- Subcategories -->
        <section v-if="facets.subcategories?.length" class="tt-fgroup">
            <h3>{{ $t("Categories") }}</h3>
            <router-link v-for="c in facets.subcategories" :key="c.slug" :to="{ path: `/category/${c.slug}`, query: keepQuery }" class="tt-fsub">
                <span>{{ $t(c.name) }}</span><small>{{ $num(c.count) }}</small>
            </router-link>
        </section>

        <!-- Price -->
        <section v-if="priceMax > priceMin" class="tt-fgroup">
            <button type="button" class="tt-fgroup-head" :aria-expanded="isOpen('price')" @click="toggle('price')">
                <h3>{{ $t("Price") }}</h3><v-icon size="18">{{ isOpen("price") ? "mdi-chevron-up" : "mdi-chevron-down" }}</v-icon>
            </button>
            <div v-show="isOpen('price')">
                <v-range-slider
                    v-model="range"
                    :min="priceMin"
                    :max="priceMax"
                    :step="priceStep"
                    color="primary"
                    track-color="surface-variant"
                    hide-details
                    strict
                    class="tt-frange"
                    @end="applyPrice"
                />
                <div class="tt-fprice">
                    <v-input-trz v-model.number="range[0]" type="number" density="compact" :prefix="currency" :aria-label="$t('Lowest price')" @change="applyPrice" />
                    <span>–</span>
                    <v-input-trz v-model.number="range[1]" type="number" density="compact" :prefix="currency" :aria-label="$t('Highest price')" @change="applyPrice" />
                </div>
            </div>
        </section>

        <!-- Availability and deals -->
        <section class="tt-fgroup">
            <button type="button" class="tt-fgroup-head" :aria-expanded="isOpen('avail')" @click="toggle('avail')">
                <h3>{{ $t("Availability") }}</h3><v-icon size="18">{{ isOpen("avail") ? "mdi-chevron-up" : "mdi-chevron-down" }}</v-icon>
            </button>
            <div v-show="isOpen('avail')" class="tt-fopts">
                <label class="tt-fopt">
                    <input type="checkbox" :checked="!!query.stock" @change="set('stock', $event.target.checked ? 1 : null)" />
                    <span>{{ $t("In stock") }}</span><small>{{ $num(facets.in_stock || 0) }}</small>
                </label>
                <label v-if="!hideSale" class="tt-fopt">
                    <input type="checkbox" :checked="!!query.sale" @change="set('sale', $event.target.checked ? 1 : null)" />
                    <span>{{ $t("On sale") }}</span><small>{{ $num(facets.on_sale || 0) }}</small>
                </label>
            </div>
        </section>

        <!-- Brands -->
        <section v-if="!hideBrand && facets.brands?.length" class="tt-fgroup">
            <button type="button" class="tt-fgroup-head" :aria-expanded="isOpen('brand')" @click="toggle('brand')">
                <h3>{{ $t("Brand") }}</h3><v-icon size="18">{{ isOpen("brand") ? "mdi-chevron-up" : "mdi-chevron-down" }}</v-icon>
            </button>
            <div v-show="isOpen('brand')" class="tt-fopts">
                <label v-for="b in shown('brand', facets.brands)" :key="b.slug" class="tt-fopt">
                    <input type="checkbox" :checked="selected('brand').includes(b.slug)" @change="toggleValue('brand', b.slug)" />
                    <span>{{ b.name }}</span><small>{{ $num(b.count) }}</small>
                </label>
                <button v-if="facets.brands.length > LIMIT" type="button" class="tt-fmore" @click="toggleMore('brand')">
                    {{ more.brand ? $t("Show less") : $tn(facets.brands.length - LIMIT, "Show {n} more", "Show {n} more") }}
                </button>
            </div>
        </section>

        <!-- Specs: RAM, Storage, Colour ... -->
        <section v-for="spec in facets.specs || []" :key="spec.slug" class="tt-fgroup">
            <button type="button" class="tt-fgroup-head" :aria-expanded="isOpen(spec.slug)" @click="toggle(spec.slug)">
                <h3>{{ $t(spec.name) }}</h3><v-icon size="18">{{ isOpen(spec.slug) ? "mdi-chevron-up" : "mdi-chevron-down" }}</v-icon>
            </button>
            <div v-show="isOpen(spec.slug)" :class="hasSwatches(spec) ? 'tt-fswatches' : 'tt-fopts'">
                <template v-if="hasSwatches(spec)">
                    <button
                        v-for="v in spec.values"
                        :key="v.slug"
                        type="button"
                        class="tt-fswatch"
                        :class="{ 'is-on': selected(spec.slug).includes(v.slug), 'is-zero': !v.count }"
                        :aria-pressed="selected(spec.slug).includes(v.slug)"
                        :title="`${v.value} (${v.count})`"
                        @click="toggleValue(spec.slug, v.slug)"
                    >
                        <i :style="{ '--swatch': v.swatch || '#ccc' }"></i>{{ v.value }}
                    </button>
                </template>
                <template v-else>
                    <label v-for="v in shown(spec.slug, spec.values)" :key="v.slug" class="tt-fopt" :class="{ 'is-zero': !v.count }">
                        <input type="checkbox" :checked="selected(spec.slug).includes(v.slug)" @change="toggleValue(spec.slug, v.slug)" />
                        <span>{{ v.value }}</span><small>{{ $num(v.count) }}</small>
                    </label>
                    <button v-if="spec.values.length > LIMIT" type="button" class="tt-fmore" @click="toggleMore(spec.slug)">
                        {{ more[spec.slug] ? $t("Show less") : $tn(spec.values.length - LIMIT, "Show {n} more", "Show {n} more") }}
                    </button>
                </template>
            </div>
        </section>

        <!-- Rating -->
        <section v-if="facets.ratings && (facets.ratings[4] || facets.ratings[3])" class="tt-fgroup">
            <button type="button" class="tt-fgroup-head" :aria-expanded="isOpen('rating')" @click="toggle('rating')">
                <h3>{{ $t("Customer rating") }}</h3><v-icon size="18">{{ isOpen("rating") ? "mdi-chevron-up" : "mdi-chevron-down" }}</v-icon>
            </button>
            <div v-show="isOpen('rating')" class="tt-fopts">
                <label v-for="r in [4, 3]" :key="r" class="tt-fopt">
                    <input type="radio" name="tt-rating" :checked="Number(query.rating) === r" @change="set('rating', r)" @click="Number(query.rating) === r && set('rating', null)" />
                    <span class="tt-fstars"><v-icon v-for="n in 5" :key="n" size="15" :class="{ 'is-on': n <= r }">mdi-star</v-icon> {{ $t("& up") }}</span>
                    <small>{{ $num(facets.ratings[r] || 0) }}</small>
                </label>
            </div>
        </section>
    </div>
</template>

<script>
/*
 * Shop filters. Reads the current filters from `query` (the page URL) and emits a new query on every change.
 * Spec filters use the spec's own slug as the query key: /category/laptops?ram=16gb&brand=asus
 */
const LIMIT = 6;

export default {
    name: "FilterPanel",
    props: {
        facets: { type: Object, required: true },
        query: { type: Object, required: true },
        hideBrand: { type: Boolean, default: false },
        hideSale: { type: Boolean, default: false },
    },
    emits: ["update"],
    data() {
        return { range: [0, 0], closed: {}, more: {}, LIMIT };
    },
    computed: {
        currency() {
            return this.$store.state.prefs.settings["store.currency_symbol"] || "৳";
        },
        priceMin() {
            return this.facets.price?.min || 0;
        },
        priceMax() {
            return this.facets.price?.max || 0;
        },
        priceStep() {
            const span = this.priceMax - this.priceMin;
            return span > 100000 ? 1000 : span > 10000 ? 500 : 100;
        },
        // Moving to a subcategory keeps the other filters
        keepQuery() {
            const { page, ...rest } = this.query;
            return rest;
        },
    },
    watch: {
        facets: { immediate: true, handler: "syncRange" },
        "query.min": "syncRange",
        "query.max": "syncRange",
    },
    methods: {
        syncRange() {
            const lo = this.query.min !== undefined ? Number(this.query.min) : this.priceMin;
            const hi = this.query.max !== undefined ? Number(this.query.max) : this.priceMax;
            this.range = [Math.max(this.priceMin, Math.min(lo, this.priceMax)), Math.min(this.priceMax, Math.max(hi, this.priceMin))];
        },
        isOpen(key) {
            return !this.closed[key];
        },
        toggle(key) {
            this.closed = { ...this.closed, [key]: !this.closed[key] };
        },
        toggleMore(key) {
            this.more = { ...this.more, [key]: !this.more[key] };
        },
        shown(key, list) {
            if (this.more[key] || list.length <= LIMIT + 2) return list;
            // Always keep ticked values visible
            const picked = this.selected(key);
            return list.filter((v, i) => i < LIMIT || picked.includes(v.slug));
        },
        hasSwatches(spec) {
            return spec.values.some((v) => v.swatch);
        },
        selected(key) {
            return String(this.query[key] || "").split(",").filter(Boolean);
        },
        toggleValue(key, slug) {
            const now = this.selected(key);
            const next = now.includes(slug) ? now.filter((s) => s !== slug) : [...now, slug];
            this.set(key, next.length ? next.join(",") : null);
        },
        set(key, value) {
            const q = { ...this.query };
            delete q.page;
            if (value === null || value === undefined || value === "") delete q[key];
            else q[key] = String(value);
            this.$emit("update", q);
        },
        applyPrice() {
            let [lo, hi] = this.range.map((v) => Number(v) || 0);
            if (lo > hi) [lo, hi] = [hi, lo];
            const q = { ...this.query };
            delete q.page;
            if (lo > this.priceMin) q.min = String(lo);
            else delete q.min;
            if (hi < this.priceMax) q.max = String(hi);
            else delete q.max;
            this.$emit("update", q);
        },
    },
};
</script>
