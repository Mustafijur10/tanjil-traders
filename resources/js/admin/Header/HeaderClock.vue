<template>
    <!-- Date + time + waqt: opens the clock / calendar / prayer / world-clock panel -->
    <v-menu v-if="s.showClock" v-model="calOpen" :close-on-content-click="false" location="bottom start" offset="8" transition="scale-transition">
        <template #activator="{ props }">
            <button v-bind="props" type="button" class="tt-time" aria-label="Open clock, calendar and prayer times">
                <span class="tt-time-ico"><v-icon size="18" color="var(--h-green)">mdi-clock-outline</v-icon></span>
                <span class="tt-time-text">
                    <span class="tt-time-main">
                        <span class="tt-time-clock" :class="'is-' + dateLang"><span class="tt-time-full">{{ timeFull }}</span><span class="tt-time-short">{{ timeShort }}</span></span>
                        <span v-if="s.showDateInChip" class="tt-time-date" :class="'is-' + dateLang" :lang="activeDate.lang">
                            <i class="tt-date-dot" :style="{ '--c': activeDate.color }"></i>
                            <span class="tt-d-long">{{ activeDate.text }}</span><span class="tt-d-short">{{ activeDate.short }}</span>
                        </span>
                    </span>
                    <span v-if="s.showWaqt" class="tt-time-next">
                        <v-icon size="12" :color="currentWaqt.type === 'avoid' ? '#dc2626' : 'var(--h-green)'">mdi-mosque</v-icon>
                        <b :class="'is-' + dateLang">{{ currentWaqt.name }}</b><em>{{ currentWaqt.till }}</em>
                        <span class="tt-time-nextwide">· {{ ui.nextChip }} <b>{{ nextPrayer.name }}</b> {{ nextPrayer.timeText }}</span>
                        <span v-if="showFastChip" class="tt-time-fast"><v-icon size="11" color="#7c3aed">mdi-moon-waning-crescent</v-icon><b>{{ fastInfo.nextShort }}</b></span>
                    </span>
                </span>
                <v-icon size="16" class="tt-chev">mdi-chevron-down</v-icon>
                <v-tooltip activator="parent" location="bottom">{{ ui.current }}: {{ currentWaqt.name }} {{ currentWaqt.till }} · {{ ui.next }}: {{ nextPrayer.name }} {{ nextPrayer.timeText }}</v-tooltip>
            </button>
        </template>

        <v-card class="tt-panel tt-cal" :class="{ 'is-dark': isDark }" width="372" flat>
            <!-- Clock -->
            <div class="tt-clockbox">
                <div>
                    <div class="tt-clock-time" :class="'is-' + dateLang" dir="ltr">{{ timeFull }}</div>
                    <div class="tt-clock-tz"><v-icon size="13">mdi-map-marker-outline</v-icon>{{ hdr.tzLabel }}</div>
                </div>
                <button type="button" class="tt-fmt-toggle" :aria-label="s.hour12 ? 'Switch to 24-hour time' : 'Switch to 12-hour time'" @click="hdr.setHour12(!s.hour12)">
                    <span :class="{ on: s.hour12 }">12h</span><span :class="{ on: !s.hour12 }">24h</span>
                </button>
            </div>

            <div class="tt-tabs" role="tablist">
                <button v-for="t in calTabs" :key="t.value" type="button" role="tab" class="tt-tab" :class="{ 'is-on': calTab === t.value }" :aria-selected="calTab === t.value" @click="calTab = t.value">
                    <v-icon size="16">{{ t.icon }}</v-icon>{{ t.label }}
                </button>
            </div>

            <!-- ═════ Calendar ═════ -->
            <div v-if="calTab === 'calendar'" class="tt-pane">
                <div class="tt-cal-top">
                    <div class="tt-seg" role="tablist" aria-label="Date language">
                        <button v-for="o in hdr.languages" :key="o.value" type="button" role="tab" class="tt-seg-btn" :class="{ 'is-on': dateLang === o.value }" :aria-selected="dateLang === o.value" :title="o.name" @click="hdr.setDateLang(o.value)"><span class="tt-flag" v-html="hdr.flagSvg[o.value]"></span></button>
                    </div>
                    <v-spacer />
                    <button type="button" class="tt-cal-today" @click="goToday"><v-icon size="14">mdi-calendar-today</v-icon>Today</button>
                </div>

                <div class="tt-cal-nav">
                    <button type="button" class="tt-cal-arrow" aria-label="Previous month" @click="shiftMonth(-1)"><v-icon size="18">mdi-chevron-left</v-icon></button>
                    <div class="tt-cal-title" :lang="activeDate.lang" :dir="activeDate.dir">
                        <div class="tt-cal-month" :class="'is-' + dateLang">{{ calTitle }}</div>
                        <div v-if="calSubtitle" class="tt-cal-sub" :class="'is-' + dateLang">{{ calSubtitle }}</div>
                    </div>
                    <button type="button" class="tt-cal-arrow" aria-label="Next month" @click="shiftMonth(1)"><v-icon size="18">mdi-chevron-right</v-icon></button>
                </div>

                <div class="tt-cal-grid tt-cal-week">
                    <span v-for="w in weekdayLabels" :key="w.i" :class="{ 'is-weekend': w.weekend, ['is-' + dateLang]: true }">{{ w.label }}</span>
                </div>
                <div class="tt-cal-grid">
                    <button
                        v-for="c in calendarCells"
                        :key="c.key"
                        type="button"
                        class="tt-cal-cell"
                        :class="{ 'is-out': !c.inMonth, 'is-today': c.isToday, 'is-selected': c.isSelected, 'is-weekend': c.weekend, 'is-bij': c.bij, 'is-sunnah': c.sunnah, 'is-ramadan': c.ramadan }"
                        :aria-label="c.key"
                        @click="selectDate(c.date)"
                    >
                        <span v-if="c.bij || c.sunnah || c.ramadan" class="tt-cal-moons">
                            <v-icon v-if="c.ramadan" size="10" class="moon-ram">mdi-moon-waning-crescent</v-icon>
                            <v-icon v-if="c.bij" size="10" class="moon-bij">mdi-moon-waning-crescent</v-icon>
                            <v-icon v-if="c.sunnah" size="10" class="moon-sunnah">mdi-moon-waning-crescent</v-icon>
                        </span>
                        <span class="tt-cal-num" :class="'is-' + dateLang">{{ c.primary }}</span>
                        <span v-if="c.secondary" class="tt-cal-sec" :class="'is-' + dateLang">{{ c.secondary }}</span>
                        <i v-if="c.event" class="tt-cal-event"></i>
                    </button>
                </div>

                <div class="tt-cal-legend">
                    <span><i class="lg today"></i>Today</span>
                    <span v-if="s.showEvents"><i class="lg event"></i>Sales event</span>
                    <span v-if="s.showBij"><v-icon size="12" color="#0284c7">mdi-moon-waning-crescent</v-icon>{{ bijName }}</span>
                    <span v-if="s.showSunnah"><v-icon size="12" color="#d97706">mdi-moon-waning-crescent</v-icon>{{ sunnahName }}</span>
                    <span v-if="hasRamadan"><v-icon size="12" color="#7c3aed">mdi-moon-waning-crescent</v-icon>{{ ui.ramadan }}</span>
                </div>
                <div v-if="bijRanges.length" class="tt-bij-line"><v-icon size="14" color="#0284c7">mdi-moon-waning-crescent</v-icon><span>{{ bijName }}: <b>{{ bijRanges.join("  ·  ") }}</b></span></div>

                <div class="tt-cal-detail">
                    <div v-for="o in selectedOptions" :key="o.value" class="tt-cal-row">
                        <span class="tt-flag" v-html="hdr.flagSvg[o.value]"></span>
                        <span class="tt-date-opt-text" :lang="o.lang" :dir="o.dir" :class="'is-' + o.value">{{ o.text }}</span>
                    </div>
                    <div v-if="selectedBij || selectedSunnah || selectedEvent || (s.showFastCard && selectedFast)" class="tt-chips">
                        <span v-if="selectedBij" class="tt-chip-bij"><v-icon size="13">mdi-moon-waning-crescent</v-icon>{{ bijName }} · {{ selectedBij }}</span>
                        <span v-if="s.showFastCard && selectedFast" class="tt-chip-fast"><v-icon size="13">mdi-weather-night-partly-cloudy</v-icon>{{ selectedFast.reasonText }} · {{ ui.sehri }} {{ selectedFast.sehriText }} · {{ ui.iftar }} {{ selectedFast.iftarText }}</span>
                        <span v-if="selectedSunnah" class="tt-chip-sunnah"><v-icon size="13">mdi-moon-waning-crescent</v-icon>{{ sunnahName }} · {{ selectedSunnah }}</span>
                        <span v-if="selectedEvent" class="tt-cal-evchip"><span class="tt-cal-event static"></span>{{ selectedEvent }}</span>
                    </div>
                </div>
            </div>

            <!-- ═════ Prayer times ═════ -->
            <div v-else-if="calTab === 'prayer'" class="tt-pane">
                <div class="tt-next">
                    <div class="d-flex align-center justify-space-between">
                        <span class="tt-next-label"><v-icon size="14">mdi-mosque</v-icon>{{ ui.current }}</span>
                        <span class="tt-next-city"><v-icon size="12">mdi-map-marker-outline</v-icon>{{ s.prayerCity }}</span>
                    </div>
                    <div class="d-flex align-end justify-space-between mt-1" style="gap: 8px">
                        <div style="min-width: 0">
                            <div class="tt-next-name" :class="'is-' + dateLang">{{ currentWaqt.name }}</div>
                            <div class="tt-next-in">{{ ui.started }} {{ currentWaqt.startText }} · {{ ui.ends }} {{ currentWaqt.endText }}</div>
                        </div>
                        <div class="text-right" style="flex: none">
                            <div class="tt-next-time" :class="'is-' + dateLang">{{ currentWaqt.left }}</div>
                            <div class="tt-next-in">{{ ui.left }}</div>
                        </div>
                    </div>
                    <div class="tt-next-bar"><div class="tt-next-bar-fill" :style="{ width: currentWaqt.progress * 100 + '%' }"></div></div>
                    <div class="tt-next-row">
                        <span class="d-flex align-center ga-1"><v-icon size="15">mdi-arrow-right-circle-outline</v-icon>{{ ui.next }}</span>
                        <b :class="'is-' + dateLang">{{ nextPrayer.name }} · {{ nextPrayer.timeText }}<small v-if="nextPrayer.isTomorrow"> ({{ ui.tomorrow }})</small></b>
                        <span>{{ nextPrayer.inPhrase }}</span>
                    </div>
                </div>

                <div v-if="s.showFastCard" class="tt-fast" :class="{ 'is-on': fastInfo.isFasting }">
                    <div class="tt-fast-head">
                        <span class="tt-fast-title"><v-icon size="15">mdi-moon-waning-crescent</v-icon>{{ fastInfo.title }}</span>
                        <span v-if="fastInfo.reasonText" class="tt-fast-tag">{{ fastInfo.reasonText }}</span>
                    </div>
                    <div class="tt-fast-grid">
                        <div class="tt-fast-box" :class="{ 'is-next': fastInfo.nextKey === 'sehri' }">
                            <span class="tt-fast-ico sehri"><v-icon size="17">mdi-weather-night-partly-cloudy</v-icon></span>
                            <span class="text-left">
                                <span class="tt-fast-label d-block">{{ ui.sehri }}</span>
                                <span class="tt-fast-time" :class="'is-' + dateLang">{{ fastInfo.sehriText }}</span>
                            </span>
                        </div>
                        <div class="tt-fast-box" :class="{ 'is-next': fastInfo.nextKey === 'iftar' }">
                            <span class="tt-fast-ico iftar"><v-icon size="17">mdi-weather-sunset-down</v-icon></span>
                            <span class="text-left">
                                <span class="tt-fast-label d-block">{{ ui.iftar }}</span>
                                <span class="tt-fast-time" :class="'is-' + dateLang">{{ fastInfo.iftarText }}</span>
                            </span>
                        </div>
                    </div>
                    <div v-if="fastInfo.progress !== null" class="tt-fast-bar"><div class="tt-fast-bar-fill" :style="{ width: fastInfo.progress * 100 + '%' }"></div></div>
                    <div class="tt-fast-next"><v-icon size="13">mdi-timer-sand</v-icon>{{ fastInfo.nextLabel }} · {{ fastInfo.inPhrase }}</div>
                </div>

                <div class="tt-prayers">
                    <div v-for="r in prayerRows" :key="r.key" class="tt-prayer" :class="['is-' + r.status, 'type-' + r.type, { 'is-current': r.isCurrent, 'is-next': r.isNext }]">
                        <span class="tt-prayer-ico"><v-icon size="16">{{ r.icon }}</v-icon></span>
                        <span class="flex-grow-1 text-left" style="min-width: 0">
                            <span class="tt-prayer-name" :class="'is-' + dateLang">{{ r.name }}<em v-if="r.type !== 'fard'" class="tt-tag-mini" :class="r.type">{{ r.type === "nafl" ? ui.nafl : ui.avoid }}</em></span>
                            <span class="tt-sub d-block">{{ r.sub }}</span>
                        </span>
                        <span class="tt-prayer-time" :class="'is-' + dateLang">{{ r.timeText }}</span>
                        <span class="tt-prayer-state">
                            <v-icon v-if="r.status === 'past'" size="16" color="#9db3ad">mdi-check-circle-outline</v-icon>
                            <span v-else-if="r.isCurrent" class="tt-now-chip">{{ ui.now }}</span>
                            <span v-else-if="r.isNext" class="tt-next-chip">{{ ui.nextChip }}</span>
                        </span>
                    </div>
                </div>

                <div class="tt-prayer-foot">
                    <label class="tt-city">
                        <v-icon size="14">mdi-map-marker-outline</v-icon>
                        <select v-model="s.prayerCity" aria-label="Prayer time city">
                            <option v-for="c in hdr.prayerCities" :key="c.name" :value="c.name">{{ c.name }}</option>
                        </select>
                    </label>
                    <span class="tt-sub">Karachi method · Hanafi Asr</span>
                </div>
                <div class="tt-note">Calculated times. Ishraq starts 15 min after sunrise, Chasht at a quarter of the morning, Zawal 6 min before Dhuhr, Awabin 20 min after Maghrib, and Tahajjud in the last third of the night. Sehri ends at Fajr and Iftar is at Maghrib. Fine-tune each time in Settings → Time, and confirm with your local mosque.</div>
            </div>

            <!-- ═════ World clock ═════ -->
            <div v-else class="tt-pane">
                <div class="tt-chip-row tt-wchips">
                    <button v-for="g in worldGroups" :key="g.value" type="button" class="tt-chip" :class="{ 'is-on': worldGroup === g.value }" @click="worldGroup = g.value">{{ g.label }}</button>
                </div>
                <div class="tt-wlist">
                    <div v-for="w in worldClocks" :key="w.key" class="tt-wrow">
                        <span class="tt-wrow-ico"><v-icon size="17" :color="w.isDay ? '#d97706' : '#7c3aed'">{{ w.isDay ? "mdi-weather-sunny" : "mdi-weather-night" }}</v-icon></span>
                        <span class="flex-grow-1 text-left" style="min-width: 0">
                            <span class="tt-wrow-city">{{ w.city }}<em v-if="w.local">You</em></span>
                            <span class="tt-sub d-block">{{ w.country }} · {{ w.dateText }}</span>
                        </span>
                        <span class="text-right" style="flex: none">
                            <span class="tt-wrow-time d-block">{{ w.time }}</span>
                            <span class="tt-sub d-block">{{ w.offset }}<template v-if="w.diff"> · {{ w.diff }}</template></span>
                        </span>
                    </div>
                </div>
                <div class="tt-note">Jeddah, Makkah and Madinah share Arabia Standard Time (UTC+3, no daylight saving). USA and Europe follow daylight saving automatically. “Ahead / behind” is measured from your own time zone.</div>
            </div>
        </v-card>
    </v-menu>
</template>

<script>
/*
 * HeaderClock.vue: the clock chip and its three-tab panel (Calendar, Prayer times, World clock).
 * Everything here is display logic. Language, 12/24h, city, theme and the clock tick come from Header.vue via `hdr`,
 * so changing them anywhere else in the header updates this panel too.
 */
const BN_MONTHS = ["বৈশাখ", "জ্যৈষ্ঠ", "আষাঢ়", "শ্রাবণ", "ভাদ্র", "আশ্বিন", "কার্তিক", "অগ্রহায়ণ", "পৌষ", "মাঘ", "ফাল্গুন", "চৈত্র"];
const toDigits = (n, lang) => {
    const set = lang === "bn" ? "০১২৩৪৫৬৭৮৯" : lang === "ar" ? "٠١٢٣٤٥٦٧٨٩" : null;
    return set ? String(n).replace(/\d/g, (d) => set[d]) : String(n);
};

// ── Prayer times (astronomical calculation, no network needed) ──
// Karachi method (Fajr 18°, Isha 18°) with Hanafi Asr, the common choice in Bangladesh.
const BD_OFFSET = 6;
const PRAYER_ICONS = {
    fajr: "mdi-weather-sunset-up",
    sunrise: "mdi-weather-sunny",
    ishraq: "mdi-brightness-5",
    chasht: "mdi-brightness-7",
    zawal: "mdi-clock-alert-outline",
    dhuhr: "mdi-white-balance-sunny",
    asr: "mdi-weather-partly-cloudy",
    maghrib: "mdi-weather-sunset-down",
    awabin: "mdi-star-crescent",
    isha: "mdi-weather-night",
    tahajjud: "mdi-moon-waning-crescent",
};
const PRAYER_NAMES = {
    en: { fajr: "Fajr", sunrise: "Sunrise", ishraq: "Ishraq", chasht: "Chasht (Duha)", zawal: "Zawal", dhuhr: "Dhuhr", asr: "Asr", maghrib: "Maghrib", awabin: "Awabin", isha: "Isha", tahajjud: "Tahajjud" },
    bn: { fajr: "ফজর", sunrise: "সূর্যোদয়", ishraq: "ইশরাক", chasht: "চাশত", zawal: "জাওয়াল", dhuhr: "যোহর", asr: "আসর", maghrib: "মাগরিব", awabin: "আউয়াবীন", isha: "এশা", tahajjud: "তাহাজ্জুদ" },
    ar: { fajr: "الفجر", sunrise: "الشروق", ishraq: "الإشراق", chasht: "الضحى", zawal: "الزوال", dhuhr: "الظهر", asr: "العصر", maghrib: "المغرب", awabin: "الأوابين", isha: "العشاء", tahajjud: "التهجد" },
};
const UI = {
    en: { sehriIftar: "Sehri & Iftar", fasting: "Fasting today", fastingTomorrow: "Fasting tomorrow", sehri: "Sehri ends", iftar: "Iftar", ramadan: "Ramadan", bij: "Ayyam al-Bid", sunnah: "Mon & Thu", current: "Current waqt", next: "Next waqt", nextChip: "Next", started: "Started", ends: "ends", left: "left", now: "Now", nafl: "Nafl", avoid: "Avoid", tomorrow: "tomorrow", till: (t) => `till ${t}`, inP: (t) => `in ${t}`, units: ["h", "m", "s"] },
    bn: { sehriIftar: "সেহরি ও ইফতার", fasting: "আজ রোজা", fastingTomorrow: "আগামীকাল রোজা", sehri: "সেহরির শেষ", iftar: "ইফতার", ramadan: "রমজান", bij: "আইয়ামে বীজ", sunnah: "সোম/বৃহস্পতি", current: "বর্তমান ওয়াক্ত", next: "পরবর্তী ওয়াক্ত", nextChip: "পরবর্তী", started: "শুরু", ends: "শেষ", left: "বাকি", now: "এখন", nafl: "নফল", avoid: "নিষিদ্ধ", tomorrow: "আগামীকাল", till: (t) => `${t} পর্যন্ত`, inP: (t) => `${t} পরে`, units: ["ঘ", "মি", "সে"] },
    ar: { sehriIftar: "السحور والإفطار", fasting: "صيام اليوم", fastingTomorrow: "صيام غدًا", sehri: "نهاية السحور", iftar: "الإفطار", ramadan: "رمضان", bij: "الأيام البيض", sunnah: "الاثنين والخميس", current: "الوقت الحالي", next: "الوقت التالي", nextChip: "التالي", started: "بدأ", ends: "ينتهي", left: "متبقي", now: "الآن", nafl: "نافلة", avoid: "مكروه", tomorrow: "غدًا", till: (t) => `حتى ${t}`, inP: (t) => `بعد ${t}`, units: ["س", "د", "ث"] },
};
const D2R = Math.PI / 180;
const dsin = (d) => Math.sin(d * D2R);
const dcos = (d) => Math.cos(d * D2R);
const dtan = (d) => Math.tan(d * D2R);
const darcsin = (x) => Math.asin(x) / D2R;
const darccos = (x) => Math.acos(x) / D2R;
const darctan2 = (y, x) => Math.atan2(y, x) / D2R;
const darccot = (x) => Math.atan(1 / x) / D2R;
const fixAngle = (a) => ((a % 360) + 360) % 360;
const fixHour = (a) => ((a % 24) + 24) % 24;
const julianDay = (y, m, d) => {
    if (m <= 2) {
        y -= 1;
        m += 12;
    }
    const A = Math.floor(y / 100);
    const B = 2 - A + Math.floor(A / 4);
    return Math.floor(365.25 * (y + 4716)) + Math.floor(30.6001 * (m + 1)) + d + B - 1524.5;
};
const sunPosition = (jd) => {
    const D = jd - 2451545.0;
    const g = fixAngle(357.529 + 0.98560028 * D);
    const q = fixAngle(280.459 + 0.98564736 * D);
    const L = fixAngle(q + 1.915 * dsin(g) + 0.02 * dsin(2 * g));
    const e = 23.439 - 0.00000036 * D;
    const RA = darctan2(dcos(e) * dsin(L), dcos(L)) / 15;
    return { decl: darcsin(dsin(e) * dsin(L)), eqt: q / 15 - fixHour(RA) };
};
const prayerHours = (date, lat, lng) => {
    const jd = julianDay(date.getFullYear(), date.getMonth() + 1, date.getDate()) - lng / (15 * 24);
    const midDay = (t) => fixHour(12 - sunPosition(jd + t).eqt);
    const angleTime = (angle, t, ccw) => {
        const { decl } = sunPosition(jd + t);
        const x = (-dsin(angle) - dsin(decl) * dsin(lat)) / (dcos(decl) * dcos(lat));
        if (x < -1 || x > 1) return NaN;
        const dt = darccos(x) / 15;
        return midDay(t) + (ccw ? -dt : dt);
    };
    const asrTime = (factor, t) => {
        const { decl } = sunPosition(jd + t);
        return angleTime(-darccot(factor + dtan(Math.abs(lat - decl))), t, false);
    };
    const adj = BD_OFFSET - lng / 15;
    return {
        fajr: angleTime(18, 5 / 24, true) + adj,
        sunrise: angleTime(0.833, 6 / 24, true) + adj,
        dhuhr: midDay(12 / 24) + adj,
        asr: asrTime(2, 13 / 24) + adj,
        maghrib: angleTime(0.833, 18 / 24, false) + adj,
        isha: angleTime(18, 18 / 24, false) + adj,
    };
};
const hoursToDate = (date, hours) => new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) + (Math.round(hours * 60) / 60 - BD_OFFSET) * 3600000);

// Cached Hijri formatter (used for every calendar cell)
let HIJRI_FMT = null;
const getHijriFmt = () => HIJRI_FMT || (HIJRI_FMT = new Intl.DateTimeFormat("en-u-ca-islamic-umalqura", { day: "numeric", month: "numeric" }));

export default {
    name: "HeaderClock",
    inject: ["hdr"],
    emits: ["date-select"],

    data() {
        return {
            calOpen: false,
            calTab: "calendar",
            calTabs: [
                { value: "calendar", label: "Calendar", icon: "mdi-calendar-month-outline" },
                { value: "prayer", label: "Prayer", icon: "mdi-mosque" },
                { value: "world", label: "World clock", icon: "mdi-earth" },
            ],
            calYear: new Date().getFullYear(),
            calMonth: new Date().getMonth(),
            selectedDate: new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate()),
            // ── Marked days in the calendar: replace with your own campaigns (or load from an API) ──
            calEvents: {
                "2026-11-11": "11.11 Mega Sale",
                "2026-11-27": "Black Friday Gadget Week",
                "2026-12-16": "Victory Day Deals",
                "2026-12-26": "Year-end Clearance",
            },

            worldGroup: "all",
            worldGroups: [
                { value: "all", label: "All" },
                { value: "saudi", label: "Saudi" },
                { value: "usa", label: "USA" },
                { value: "europe", label: "Europe" },
                { value: "asia", label: "Asia" },
            ],
            // Cities in the World clock tab. Jeddah, Makkah and Madinah share one zone but are listed separately on purpose.
            clockZones: [
                { key: "jed", city: "Jeddah", country: "Saudi Arabia", tz: "Asia/Riyadh", group: "saudi" },
                { key: "mak", city: "Makkah", country: "Saudi Arabia", tz: "Asia/Riyadh", group: "saudi" },
                { key: "med", city: "Madinah", country: "Saudi Arabia", tz: "Asia/Riyadh", group: "saudi" },
                { key: "cal", city: "California", country: "USA · Pacific", tz: "America/Los_Angeles", group: "usa" },
                { key: "mic", city: "Michigan", country: "USA · Eastern", tz: "America/Detroit", group: "usa" },
                { key: "nyc", city: "New York", country: "USA · Eastern", tz: "America/New_York", group: "usa" },
                { key: "ger", city: "Germany", country: "Berlin", tz: "Europe/Berlin", group: "europe" },
                { key: "uk", city: "United Kingdom", country: "London", tz: "Europe/London", group: "europe" },
                { key: "dxb", city: "Dubai", country: "UAE", tz: "Asia/Dubai", group: "asia" },
                { key: "szx", city: "Shenzhen", country: "China", tz: "Asia/Shanghai", group: "asia" },
            ],
        };
    },

    computed: {
        // ── Shared state, read from Header.vue ──
        s() {
            return this.hdr.s;
        },
        isDark() {
            return this.hdr.isDark;
        },
        dateLang() {
            return this.hdr.s.dateLang;
        },
        hijriShift() {
            return this.hdr.s.hijriShift;
        },
        clock() {
            return this.hdr.clock;
        },
        now() {
            return this.hdr.now;
        },
        localTz() {
            return this.hdr.localTz;
        },
        weekendDays() {
            return this.hdr.weekendDays;
        },

        ui() {
            return UI[this.dateLang];
        },
        dateOptions() {
            return this.optionsFor(this.now);
        },
        selectedOptions() {
            return this.optionsFor(this.selectedDate);
        },
        activeDate() {
            return this.dateOptions.find((o) => o.value === this.dateLang) || this.dateOptions[0];
        },
        selectedEvent() {
            return this.s.showEvents ? this.calEvents[this.hdr.isoKey(this.selectedDate)] || "" : "";
        },
        showFastChip() {
            return this.s.showFastChip && this.s.showFastCard && this.fastInfo.isFasting;
        },

        // ── Time ──
        timeShort() {
            return this.hdr.fmtTime(this.clock);
        },
        timeFull() {
            return this.hdr.fmtTime(this.clock, { seconds: this.s.showSeconds });
        },
        worldClocks() {
            const localMin = this.tzMinutes(this.localTz);
            const local = { key: "local", city: this.localTz.split("/").pop().replace(/_/g, " "), country: "Your location", tz: this.localTz, local: true };
            const zones = [local, ...this.clockZones.filter((z) => z.tz !== this.localTz && (this.worldGroup === "all" || z.group === this.worldGroup))];
            return zones.map((z) => {
                let isDay = true;
                let offset = "";
                let dateText = "";
                let diff = "";
                try {
                    const h = Number(new Intl.DateTimeFormat("en-GB", { hour: "2-digit", hour12: false, timeZone: z.tz }).format(this.clock));
                    isDay = h >= 6 && h < 18;
                    const part = new Intl.DateTimeFormat("en", { timeZoneName: "shortOffset", timeZone: z.tz }).formatToParts(this.clock).find((x) => x.type === "timeZoneName");
                    offset = part ? part.value.replace("GMT", "UTC") : "";
                    dateText = new Intl.DateTimeFormat("en-GB", { weekday: "short", day: "numeric", month: "short", timeZone: z.tz }).format(this.clock);
                    diff = z.local ? "" : this.fmtDiff(this.tzMinutes(z.tz) - localMin);
                } catch (e) {
                    /* unsupported zone, show what we have */
                }
                return { ...z, time: this.fmtTime(this.clock, { tz: z.tz }), isDay, offset, dateText, diff };
            });
        },

        // ── Calendar ──
        calLocale() {
            return { en: "en-GB", bn: "bn-BD", ar: "ar-EG-u-ca-gregory" }[this.dateLang];
        },
        calTitle() {
            try {
                return new Intl.DateTimeFormat(this.calLocale, { month: "long", year: "numeric" }).format(new Date(this.calYear, this.calMonth, 1));
            } catch (e) {
                return "";
            }
        },
        calSubtitle() {
            const first = new Date(this.calYear, this.calMonth, 1);
            const last = new Date(this.calYear, this.calMonth + 1, 0);
            if (this.dateLang === "bn") {
                const a = this.bnParts(first);
                const b = this.bnParts(last);
                if (a.month === b.month) return `${BN_MONTHS[a.month]} ${toDigits(a.year, "bn")}`;
                const left = a.year === b.year ? BN_MONTHS[a.month] : `${BN_MONTHS[a.month]} ${toDigits(a.year, "bn")}`;
                return `${left} – ${BN_MONTHS[b.month]} ${toDigits(b.year, "bn")}`;
            }
            if (this.dateLang === "ar") {
                try {
                    const f = new Intl.DateTimeFormat("ar-SA-u-ca-islamic-umalqura", { month: "long", year: "numeric" });
                    const off = this.hijriShift * 86400000;
                    const a = f.format(new Date(first.getTime() + off));
                    const b = f.format(new Date(last.getTime() + off));
                    return a === b ? a : `${a} – ${b}`;
                } catch (e) {
                    return "";
                }
            }
            return "";
        },
        weekdayLabels() {
            const loc = { en: "en-GB", bn: "bn-BD", ar: "ar-EG" }[this.dateLang];
            let fmt;
            try {
                fmt = new Intl.DateTimeFormat(loc, { weekday: "short" });
            } catch (e) {
                fmt = new Intl.DateTimeFormat("en-GB", { weekday: "short" });
            }
            return Array.from({ length: 7 }, (_, i) => {
                const dow = (this.s.weekStart + i) % 7;
                return { i, label: fmt.format(new Date(2024, 0, 7 + dow)), weekend: this.weekendDays.includes(dow) };
            });
        },
        calendarCells() {
            const first = new Date(this.calYear, this.calMonth, 1);
            const offset = (first.getDay() - this.s.weekStart + 7) % 7;
            const today = new Date();
            const lang = this.dateLang;
            return Array.from({ length: 42 }, (_, i) => {
                const date = new Date(this.calYear, this.calMonth, 1 - offset + i);
                const hp = this.hijriParts(date);
                const hijri = hp ? hp.day : null;
                let secondary = "";
                if (this.s.showAltDay) {
                    if (lang === "bn") secondary = toDigits(this.bnParts(date).day, "bn");
                    else if (lang === "ar") secondary = hijri ? toDigits(hijri, "ar") : "";
                }
                return {
                    key: this.hdr.isoKey(date),
                    date,
                    inMonth: date.getMonth() === this.calMonth,
                    isToday: this.hdr.sameDay(date, today),
                    isSelected: this.hdr.sameDay(date, this.selectedDate),
                    weekend: this.weekendDays.includes(date.getDay()),
                    primary: toDigits(date.getDate(), lang),
                    secondary,
                    event: this.s.showEvents && !!this.calEvents[this.hdr.isoKey(date)],
                    ramadan: !!hp && hp.month === 9,
                    bij: this.s.showBij && hijri >= 13 && hijri <= 15,
                    sunnah: this.s.showSunnah && (date.getDay() === 1 || date.getDay() === 4),
                };
            });
        },
        hasRamadan() {
            return this.calendarCells.some((c) => c.inMonth && c.ramadan);
        },
        bijName() {
            return { en: "Ayyam al-Bid fasting (Bij roja)", bn: "আইয়ামে বীজের রোজা", ar: "صيام الأيام البيض" }[this.dateLang];
        },
        sunnahName() {
            return { en: "Mon & Thu sunnah roja", bn: "সোম-বৃহস্পতিবারের নফল রোজা", ar: "صيام الاثنين والخميس" }[this.dateLang];
        },
        selectedSunnah() {
            const d = this.selectedDate.getDay();
            if (!this.s.showSunnah || (d !== 1 && d !== 4)) return "";
            try {
                return new Intl.DateTimeFormat(this.calLocale, { weekday: "long" }).format(this.selectedDate);
            } catch (e) {
                return d === 1 ? "Monday" : "Thursday";
            }
        },
        selectedBij() {
            if (!this.s.showBij) return "";
            const h = this.hijriParts(this.selectedDate);
            return h && h.day >= 13 && h.day <= 15 ? `${h.day - 12} / 3` : "";
        },
        bijRanges() {
            const cells = this.calendarCells.filter((c) => c.inMonth && c.bij);
            if (!cells.length) return [];
            let fmt;
            try {
                fmt = new Intl.DateTimeFormat(this.calLocale, { day: "numeric", month: "short" });
            } catch (e) {
                fmt = new Intl.DateTimeFormat("en-GB", { day: "numeric", month: "short" });
            }
            const out = [];
            let start = cells[0];
            let prev = cells[0];
            for (let i = 1; i <= cells.length; i++) {
                const c = cells[i];
                if (c && Math.round((c.date - prev.date) / 86400000) === 1) {
                    prev = c;
                    continue;
                }
                out.push(start === prev ? fmt.format(start.date) : `${fmt.format(start.date)} – ${fmt.format(prev.date)}`);
                start = c;
                prev = c;
            }
            return out;
        },

        // ── Sehri & Iftar for today (or tomorrow once today's Iftar has passed) ──
        fastInfo() {
            const t = this.clock.getTime();
            const u = UI[this.dateLang];
            const today = new Date(this.now.getFullYear(), this.now.getMonth(), this.now.getDate());
            let day = today;
            let isTomorrow = false;
            let ft = this.fastTimes(today);
            if (t >= ft.iftar.getTime()) {
                day = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
                isTomorrow = true;
                ft = this.fastTimes(day);
            }
            const reason = this.fastReason(day);
            const sehriText = this.fmtTime(ft.sehri, { tz: "Asia/Dhaka" });
            const iftarText = this.fmtTime(ft.iftar, { tz: "Asia/Dhaka" });
            let nextKey = "iftar";
            let ms = ft.iftar.getTime() - t;
            let progress = Math.min(1, Math.max(0, (t - ft.sehri.getTime()) / Math.max(1, ft.iftar.getTime() - ft.sehri.getTime())));
            if (t < ft.sehri.getTime()) {
                nextKey = "sehri";
                ms = ft.sehri.getTime() - t;
                progress = null;
            }
            const nextName = nextKey === "sehri" ? u.sehri : u.iftar;
            return {
                isFasting: !!reason,
                reasonText: reason ? u[reason] : "",
                title: reason ? (isTomorrow ? u.fastingTomorrow : u.fasting) : isTomorrow ? `${u.sehriIftar} · ${u.tomorrow}` : u.sehriIftar,
                sehriText,
                iftarText,
                nextKey,
                nextLabel: nextName,
                nextShort: `${nextName} ${nextKey === "sehri" ? sehriText : iftarText}`,
                inPhrase: u.inP(this.dur(Math.max(0, ms), true)),
                progress,
            };
        },
        selectedFast() {
            const reason = this.fastReason(this.selectedDate);
            if (!reason) return null;
            const ft = this.fastTimes(this.selectedDate);
            return {
                reasonText: UI[this.dateLang][reason],
                sehriText: this.fmtTime(ft.sehri, { tz: "Asia/Dhaka" }),
                iftarText: this.fmtTime(ft.iftar, { tz: "Asia/Dhaka" }),
            };
        },

        // ── Prayer ──
        prayerLoc() {
            return this.hdr.prayerCities.find((c) => c.name === this.s.prayerCity) || this.hdr.prayerCities[0];
        },
        // yesterday, today, tomorrow and the day after (needed for the end of tomorrow's night)
        prayerDays() {
            return [-1, 0, 1, 2].map((off) => {
                const d = new Date(this.now.getFullYear(), this.now.getMonth(), this.now.getDate() + off);
                const h = prayerHours(d, this.prayerLoc.lat, this.prayerLoc.lng);
                const out = {};
                ["fajr", "sunrise", "dhuhr", "asr", "maghrib", "isha"].forEach((k) => {
                    out[k] = hoursToDate(d, h[k] + (this.s.adjust[k] || 0) / 60);
                });
                return out;
            });
        },
        // every waqt window (fard, nafl and "avoid" times) for yesterday, today and tomorrow
        waqtWindows() {
            const min = 60000;
            const adj = this.s.adjust;
            const list = [];
            for (let i = 0; i < 3; i++) {
                const D = this.prayerDays[i];
                const N = this.prayerDays[i + 1];
                const day = i - 1;
                const ishraq = new Date(D.sunrise.getTime() + (15 + (adj.ishraq || 0)) * min);
                const chasht = new Date(D.sunrise.getTime() + (D.dhuhr - D.sunrise) / 4 + (adj.chasht || 0) * min);
                const zawal = new Date(D.dhuhr.getTime() + (-6 + (adj.zawal || 0)) * min);
                const awabin = new Date(D.maghrib.getTime() + (20 + (adj.awabin || 0)) * min);
                const tahajjud = new Date(D.maghrib.getTime() + ((N.fajr - D.maghrib) * 2) / 3 + (adj.tahajjud || 0) * min);
                list.push(
                    { key: "fajr", type: "fard", day, start: D.fajr, end: D.sunrise },
                    { key: "sunrise", type: "avoid", day, start: D.sunrise, end: ishraq },
                    { key: "ishraq", type: "nafl", day, start: ishraq, end: chasht },
                    { key: "chasht", type: "nafl", day, start: chasht, end: zawal },
                    { key: "zawal", type: "avoid", day, start: zawal, end: D.dhuhr },
                    { key: "dhuhr", type: "fard", day, start: D.dhuhr, end: D.asr },
                    { key: "asr", type: "fard", day, start: D.asr, end: D.maghrib },
                    { key: "maghrib", type: "fard", day, start: D.maghrib, end: D.isha },
                    { key: "awabin", type: "nafl", day, start: awabin, end: D.isha },
                    { key: "isha", type: "fard", day, start: D.isha, end: N.fajr },
                    { key: "tahajjud", type: "nafl", day, start: tahajjud, end: N.fajr }
                );
            }
            return list;
        },
        // the waqt we are in right now, with the time it ends
        currentWaqt() {
            const t = this.clock.getTime();
            const L = this.dateLang;
            const active = this.waqtWindows.filter((w) => w.start.getTime() <= t && t < w.end.getTime());
            const w = active.find((x) => x.type === "fard") || active.find((x) => x.key !== "awabin" && x.key !== "tahajjud") || active[0] || this.waqtWindows[0];
            const total = Math.max(1, w.end.getTime() - w.start.getTime());
            const endText = this.fmtTime(w.end, { tz: "Asia/Dhaka" });
            return {
                key: w.key,
                type: w.type,
                name: PRAYER_NAMES[L][w.key],
                startText: this.fmtTime(w.start, { tz: "Asia/Dhaka" }),
                endText,
                till: UI[L].till(endText),
                left: this.dur(Math.max(0, w.end.getTime() - t)),
                progress: Math.min(1, Math.max(0, (t - w.start.getTime()) / total)),
            };
        },
        // the next fard prayer and when it starts
        nextPrayer() {
            const t = this.clock.getTime();
            const L = this.dateLang;
            const fard = this.waqtWindows.filter((w) => w.type === "fard");
            const next = fard.find((w) => w.start.getTime() > t) || fard[fard.length - 1];
            return {
                key: next.key,
                name: PRAYER_NAMES[L][next.key],
                timeText: this.fmtTime(next.start, { tz: "Asia/Dhaka" }),
                inPhrase: UI[L].inP(this.dur(Math.max(0, next.start.getTime() - t), true)),
                isTomorrow: next.day === 1,
            };
        },
        prayerRows() {
            const t = this.clock.getTime();
            const L = this.dateLang;
            const beforeFajr = t < this.prayerDays[1].fajr.getTime();
            const keys = ["fajr", "sunrise", "ishraq", "chasht", "zawal", "dhuhr", "asr", "maghrib", "awabin", "isha", "tahajjud"];
            const rows = keys.map((key) => {
                // Isha and Tahajjud belong to last night until today's Fajr arrives
                const day = (key === "isha" || key === "tahajjud") && beforeFajr ? -1 : 0;
                const w = this.waqtWindows.find((x) => x.day === day && x.key === key);
                const status = w.end.getTime() <= t ? "past" : w.start.getTime() <= t ? "active" : "upcoming";
                const till = UI[L].till(this.fmtTime(w.end, { tz: "Asia/Dhaka" }));
                return {
                    key,
                    type: w.type,
                    name: PRAYER_NAMES[L][key],
                    icon: PRAYER_ICONS[key],
                    timeText: this.fmtTime(w.start, { tz: "Asia/Dhaka" }),
                    sub: L === "en" ? till : `${PRAYER_NAMES.en[key]} · ${till}`,
                    status,
                    isCurrent: key === this.currentWaqt.key && status === "active",
                    isNext: key === this.nextPrayer.key && !this.nextPrayer.isTomorrow && status === "upcoming",
                };
            });
            return this.s.showNafl ? rows : rows.filter((r) => r.type === "fard" || r.key === "sunrise");
        },
    },

    methods: {
        fmtTime(d, opts) {
            return this.hdr.fmtTime(d, opts);
        },
        // "1h 32m" / "1h 32m 10s", with localized digits and units
        dur(ms, withSec = false) {
            const L = this.dateLang;
            const u = UI[L].units;
            const h = Math.floor(ms / 3600000);
            const m = Math.floor((ms % 3600000) / 60000);
            const sec = Math.floor((ms % 60000) / 1000);
            const parts = [];
            if (h) parts.push(toDigits(h, L) + u[0]);
            parts.push(toDigits(m, L) + u[1]);
            if (withSec) parts.push(toDigits(sec, L) + u[2]);
            return parts.join(" ");
        },
        // UTC offset of a time zone in minutes, read from Intl so daylight saving is always right
        tzMinutes(tz) {
            try {
                const p = new Intl.DateTimeFormat("en", { timeZone: tz, timeZoneName: "shortOffset" }).formatToParts(this.now).find((x) => x.type === "timeZoneName");
                const m = p && /GMT([+-])(\d{1,2})(?::(\d{2}))?/.exec(p.value);
                return m ? (m[1] === "-" ? -1 : 1) * (Number(m[2]) * 60 + Number(m[3] || 0)) : 0;
            } catch (e) {
                return 0;
            }
        },
        fmtDiff(min) {
            if (!min) return "Same time";
            const a = Math.abs(min);
            const text = `${Math.floor(a / 60)}h${a % 60 ? " " + (a % 60) + "m" : ""}`;
            return `${text} ${min > 0 ? "ahead" : "behind"}`;
        },

        // ── Calendar helpers ──
        bnParts(date) {
            const leap = (y) => (y % 4 === 0 && y % 100 !== 0) || y % 400 === 0;
            const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            // Bangladesh revised calendar: new year on 14 April, first 5 months 31 days, then 30 (Falgun 31 in a leap year)
            const y0 = d >= new Date(d.getFullYear(), 3, 14) ? d.getFullYear() : d.getFullYear() - 1;
            let diff = Math.round((d - new Date(y0, 3, 14)) / 86400000);
            const lens = [31, 31, 31, 31, 31, 30, 30, 30, 30, 30, leap(y0 + 1) ? 31 : 30, 30];
            let m = 0;
            while (m < 11 && diff >= lens[m]) {
                diff -= lens[m];
                m++;
            }
            return { day: diff + 1, month: m, year: y0 - 593 };
        },
        hijriParts(date) {
            try {
                const parts = getHijriFmt().formatToParts(new Date(date.getTime() + this.hijriShift * 86400000));
                const get = (t) => {
                    const x = parts.find((p) => p.type === t);
                    return x ? Number(x.value) : null;
                };
                return { day: get("day"), month: get("month") };
            } catch (e) {
                return null;
            }
        },
        // why this day is a fasting day: "ramadan" | "bij" | "sunnah" | ""
        fastReason(date) {
            const hp = this.hijriParts(date);
            if (hp && hp.month === 9) return "ramadan";
            if (this.s.showBij && hp && hp.day >= 13 && hp.day <= 15) return "bij";
            if (this.s.showSunnah && (date.getDay() === 1 || date.getDay() === 4)) return "sunnah";
            return "";
        },
        // Sehri ends when Fajr starts; Iftar is at Maghrib (sunset)
        fastTimes(date) {
            const h = prayerHours(date, this.prayerLoc.lat, this.prayerLoc.lng);
            const adj = this.s.adjust;
            return { sehri: hoursToDate(date, h.fajr + (adj.sehri || 0) / 60), iftar: hoursToDate(date, h.maghrib + (adj.iftar || 0) / 60) };
        },
        formatBn(d, withWeekday = true) {
            const p = this.bnParts(d);
            let wd = "";
            try {
                if (withWeekday) wd = new Intl.DateTimeFormat("bn-BD", { weekday: "long" }).format(d) + ", ";
            } catch (e) {
                wd = "";
            }
            return `${wd}${toDigits(p.day, "bn")} ${BN_MONTHS[p.month]} ${toDigits(p.year, "bn")}`;
        },
        formatAr(d, withYear = true) {
            try {
                const x = new Date(d.getTime() + this.hijriShift * 86400000);
                return new Intl.DateTimeFormat("ar-SA-u-ca-islamic-umalqura", withYear ? { day: "numeric", month: "long", year: "numeric" } : { day: "numeric", month: "long" }).format(x);
            } catch (e) {
                return "";
            }
        },
        optionsFor(d) {
            const opts = [
                { value: "en", text: d.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short", year: "numeric" }), short: d.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" }), lang: "en", dir: "ltr", color: "#0f9d6b" },
                { value: "bn", text: this.formatBn(d), short: this.formatBn(d, false), lang: "bn", dir: "ltr", color: "#d97706" },
            ];
            const ar = this.formatAr(d);
            if (ar) opts.push({ value: "ar", text: ar, short: this.formatAr(d, false), lang: "ar", dir: "rtl", color: "#7c3aed" });
            return opts;
        },
        shiftMonth(delta) {
            const d = new Date(this.calYear, this.calMonth + delta, 1);
            this.calYear = d.getFullYear();
            this.calMonth = d.getMonth();
        },
        selectDate(d) {
            this.selectedDate = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            this.calYear = d.getFullYear();
            this.calMonth = d.getMonth();
            this.$emit("date-select", this.hdr.isoKey(d));
        },
        goToday() {
            this.selectDate(new Date());
        },
    },
};
</script>
