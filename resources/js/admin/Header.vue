<template>
    <header class="tt-header" :class="{ 'is-dark': isDark, 'is-compact': s.compact }" role="banner">
        <div class="tt-inner">
            <!-- ═════════ Left: sidebar toggle + title + date/time + section tabs ═════════ -->
            <div class="tt-left">
                <v-btn v-if="showSidebarToggle" icon variant="text" class="tt-icon-btn mr-1" aria-label="Toggle sidebar" @click="$emit('toggle-sidebar')">
                    <v-icon size="20">mdi-menu</v-icon>
                </v-btn>

                <div class="tt-context">
                    <span class="tt-page-tile" aria-hidden="true"><v-icon size="22" color="white">{{ pageIcon }}</v-icon></span>
                    <div class="tt-title-row">
                        <template v-for="c in parentCrumbs" :key="c.to">
                            <router-link :to="c.to" class="tt-title-parent">{{ c.label }}</router-link>
                            <span class="tt-title-parent">/</span>
                        </template>
                        <h1 class="tt-page-title">{{ pageTitle }}</h1>
                    </div>
                </div>

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
                                <div class="tt-clock-tz"><v-icon size="13">mdi-map-marker-outline</v-icon>{{ tzLabel }}</div>
                            </div>
                            <button type="button" class="tt-fmt-toggle" :aria-label="s.hour12 ? 'Switch to 24-hour time' : 'Switch to 12-hour time'" @click="setHour12(!s.hour12)">
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
                                    <button v-for="o in languages" :key="o.value" type="button" role="tab" class="tt-seg-btn" :class="{ 'is-on': dateLang === o.value }" :aria-selected="dateLang === o.value" :title="o.name" @click="setDateLang(o.value)"><span class="tt-flag" v-html="flagSvg[o.value]"></span></button>
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
                                    <span class="tt-flag" v-html="flagSvg[o.value]"></span>
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
                                        <option v-for="c in prayerCities" :key="c.name" :value="c.name">{{ c.name }}</option>
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

                <!-- Section tabs + page actions + counters (or live mini stats) -->
                <div v-if="s.showTabs" class="tt-section">
                    <nav v-if="currentTabs.length" class="tt-stabs" aria-label="Section">
                        <router-link v-for="t in currentTabs" :key="t.to" :to="t.to" class="tt-stab" :class="{ 'is-on': activeTabTo === t.to }" :title="t.label">
                            <v-icon size="16">{{ t.icon }}</v-icon><span class="tt-stab-label">{{ t.label }}</span>
                        </router-link>
                    </nav>
                    <div v-else class="tt-metrics">
                        <span v-for="m in miniMetrics" :key="m.label" class="tt-mini">
                            <span class="tt-mini-ico" :style="{ background: m.tint }"><v-icon size="15" :color="m.color">{{ m.icon }}</v-icon></span>
                            <span class="tt-mini-text"><span class="tt-mini-val">{{ m.value }}</span><span class="tt-mini-lbl">{{ m.label }}</span></span>
                            <span class="tt-delta" :class="m.good ? 'up' : 'down'"><v-icon size="10">{{ m.delta >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(m.delta) }}%</span>
                        </span>
                    </div>

                    <div v-if="s.showSectionActions && (currentSection.stats.length || currentSection.actions.length)" class="tt-sec-end">
                        <div v-if="currentSection.stats.length" class="tt-sec-stats">
                            <span v-for="x in currentSection.stats" :key="x.label" class="tt-sstat">
                                <span class="tt-sstat-ico" :style="{ background: x.tint }"><v-icon size="14" :color="x.color">{{ x.icon }}</v-icon></span>
                                <b>{{ x.value }}</b><em>{{ x.label }}</em>
                            </span>
                        </div>
                        <div v-if="currentSection.actions.length" class="tt-sec-actions">
                            <button v-for="a in currentSection.actions" :key="a.label" type="button" class="tt-sact" :class="{ 'is-primary': a.primary }" :title="a.label" @click="runSectionAction(a)">
                                <v-icon size="16">{{ a.icon }}</v-icon><span class="tt-sact-label">{{ a.label }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════════ Right: search, today, create, tools, user ═════════ -->
            <div class="tt-right">
                <button v-if="s.showSearch" type="button" class="tt-search" aria-label="Open search" @click="openPalette">
                    <v-icon size="18" class="tt-search-icon">mdi-magnify</v-icon>
                    <span class="tt-search-text">Search <transition name="tt-fade" mode="out-in"><b :key="hintIndex" class="tt-hint">{{ searchHints[hintIndex] }}</b></transition>…</span>
                    <v-tooltip activator="parent" location="bottom">Search · Ctrl K</v-tooltip>
                </button>

                <!-- Today at a glance -->
                <v-menu v-if="s.showToday" :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
                    <template #activator="{ props }">
                        <button v-bind="props" type="button" class="tt-today" aria-label="Today's sales snapshot">
                            <span class="tt-ring" :style="{ background: `conic-gradient(var(--h-green) ${glance.targetPct * 3.6}deg, var(--h-line) 0deg)` }">
                                <span class="tt-ring-in"><v-icon size="14" color="var(--h-green)">mdi-trending-up</v-icon></span>
                            </span>
                            <span class="tt-today-text">
                                <span class="tt-today-label">Today's sales</span>
                                <span class="tt-today-value">{{ shortMoney(glance.revenue) }}<span class="tt-delta up"><v-icon size="10">mdi-arrow-up</v-icon>{{ glance.revenueDelta }}%</span></span>
                            </span>
                            <v-tooltip activator="parent" location="bottom">Today's sales so far · the ring shows {{ glance.targetPct }}% of this month's target</v-tooltip>
                        </button>
                    </template>
                    <v-card class="tt-panel" :class="{ 'is-dark': isDark }" width="392" flat>
                        <div class="tt-glance-head">
                            <div>
                                <div class="tt-panel-title">Today at a glance</div>
                                <div class="tt-sub">{{ dateLabel }} · updated {{ timeLabel }}</div>
                            </div>
                            <v-spacer />
                            <span class="tt-pill-live"><span class="tt-pill-dot"></span>Live</span>
                        </div>

                        <div class="tt-glance-grid">
                            <div v-for="t in glance.tiles" :key="t.label" class="tt-gtile">
                                <div class="d-flex align-center justify-space-between">
                                    <span class="tt-tile sm" :style="{ background: t.tint }"><v-icon size="15" :color="t.color">{{ t.icon }}</v-icon></span>
                                    <span class="tt-delta" :class="t.good ? 'up' : 'down'"><v-icon size="10">{{ t.delta >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(t.delta) }}%</span>
                                </div>
                                <div class="tt-gvalue">{{ t.value }}</div>
                                <div class="tt-sub">{{ t.label }}</div>
                            </div>
                        </div>

                        <div class="tt-glance-section">
                            <div class="d-flex justify-space-between align-center mb-1">
                                <span class="tt-glabel">Monthly target</span>
                                <span class="tt-gpct">{{ glance.targetPct }}%</span>
                            </div>
                            <div class="tt-bar"><div class="tt-bar-fill" :style="{ width: glance.targetPct + '%' }"></div></div>
                            <div class="tt-sub mt-1">{{ shortMoney(glance.monthRevenue) }} of {{ shortMoney(glance.monthTarget) }} · {{ daysLeft }} days left</div>
                        </div>

                        <div class="tt-glance-section">
                            <div class="d-flex justify-space-between align-center mb-2">
                                <span class="tt-glabel">Orders by hour</span>
                                <span class="tt-sub">Peak {{ glance.peakHour }}</span>
                            </div>
                            <div class="tt-bars">
                                <span v-for="(v, i) in glance.hourly" :key="i" class="tt-bar-col" :class="{ 'is-peak': v === hourlyMax }" :style="{ height: (v / hourlyMax) * 100 + '%' }" :title="v + ' orders'"></span>
                            </div>
                        </div>

                        <div class="tt-glance-section">
                            <div class="tt-glabel mb-2">Needs attention</div>
                            <button v-for="a in glance.attention" :key="a.label" type="button" class="tt-attn-row" @click="go(a.to)">
                                <span class="tt-tile sm" :style="{ background: a.tint }"><v-icon size="15" :color="a.color">{{ a.icon }}</v-icon></span>
                                <span class="flex-grow-1 text-left">{{ a.label }}</span>
                                <span class="tt-attn-count" :style="{ color: a.color, background: a.tint }">{{ a.count }}</span>
                            </button>
                        </div>

                        <button type="button" class="tt-panel-foot" @click="go('/admin/sales-analytics')">Open sales analytics <v-icon size="14">mdi-arrow-right</v-icon></button>
                    </v-card>
                </v-menu>

                <!-- Quick create -->
                <v-menu v-if="s.showCreate" location="bottom end" offset="10" transition="scale-transition">
                    <template #activator="{ props }">
                        <v-btn v-bind="props" variant="flat" class="text-none tt-create-btn" prepend-icon="mdi-plus">
                            <span class="tt-create-label">Create</span>
                            <v-tooltip activator="parent" location="bottom">Create new order, product, brand…</v-tooltip>
                        </v-btn>
                    </template>
                    <v-card class="tt-panel" :class="{ 'is-dark': isDark }" width="248" flat>
                        <div class="tt-panel-label">Quick create</div>
                        <div class="px-2 pb-2">
                            <button v-for="q in quickCreate" :key="q.label" type="button" class="tt-menu-item" @click="go(q.to)">
                                <span class="tt-tile" :style="{ background: q.tint }"><v-icon size="16" :color="q.color">{{ q.icon }}</v-icon></span>
                                <span class="flex-grow-1 text-left">{{ q.label }}</span>
                                <v-icon size="14" class="tt-muted">mdi-arrow-top-right</v-icon>
                            </button>
                        </div>
                    </v-card>
                </v-menu>

                <!-- Tools cluster: fullscreen, info center, quick settings, notifications -->
                <div class="tt-actions">
                    <v-btn v-if="s.showFullscreen" icon variant="text" class="tt-icon-btn tt-hide-xs" :aria-label="isFullscreen ? 'Exit full screen' : 'Enter full screen'" @click="toggleFullscreen">
                        <v-icon size="20">{{ isFullscreen ? "mdi-fullscreen-exit" : "mdi-fullscreen" }}</v-icon>
                        <v-tooltip activator="parent" location="bottom">{{ isFullscreen ? "Exit full screen" : "Full screen" }}</v-tooltip>
                    </v-btn>

                    <!-- Info center -->
                    <v-menu v-if="s.showInfo" :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
                        <template #activator="{ props }">
                            <v-btn v-bind="props" icon variant="text" class="tt-icon-btn" aria-label="Info center">
                                <v-badge v-if="infoWarnings" dot color="#d97706"><v-icon size="20">mdi-information-outline</v-icon></v-badge>
                                <v-icon v-else size="20">mdi-information-outline</v-icon>
                                <v-tooltip activator="parent" location="bottom">Info center</v-tooltip>
                            </v-btn>
                        </template>
                        <v-card class="tt-panel tt-info" :class="{ 'is-dark': isDark }" width="360" flat>
                            <div class="tt-panel-head">
                                <span class="tt-set-ico"><v-icon size="18" color="var(--h-green)">mdi-information-outline</v-icon></span>
                                <div>
                                    <div class="tt-panel-title">Info center</div>
                                    <div class="tt-sub">Status and quick reference</div>
                                </div>
                            </div>

                            <div v-if="s.info.status" class="tt-info-block">
                                <div class="tt-glabel mb-1">System status</div>
                                <div v-for="r in infoStatus" :key="r.label" class="tt-info-row">
                                    <span class="tt-info-ico" :class="'tone-' + r.tone"><v-icon size="15">{{ r.icon }}</v-icon></span>
                                    <span class="flex-grow-1 text-left">{{ r.label }}</span>
                                    <span class="tt-info-val" :class="'tone-' + r.tone">{{ r.value }}</span>
                                </div>
                            </div>

                            <div v-if="s.info.rates" class="tt-info-block">
                                <div class="d-flex justify-space-between align-center mb-2"><span class="tt-glabel">Exchange rates</span><span class="tt-sub">to ৳ BDT · sample</span></div>
                                <div class="tt-rates">
                                    <span v-for="r in infoRates" :key="r.code" class="tt-rate"><b>{{ r.code }}</b>{{ r.value }}</span>
                                </div>
                            </div>

                            <div v-if="s.info.session" class="tt-info-block">
                                <div class="tt-glabel mb-1">This session</div>
                                <div class="tt-kv"><span>Signed in as</span><b>{{ user.name }} · {{ user.role }}</b></div>
                                <div class="tt-kv"><span>Time zone</span><b>{{ tzLabel }}</b></div>
                                <div class="tt-kv"><span>Store</span><b>{{ storeLabel }}</b></div>
                                <div class="tt-kv"><span>Date range</span><b>{{ periodLabel }}</b></div>
                            </div>

                            <div v-if="s.info.shortcuts" class="tt-info-block">
                                <div class="tt-glabel mb-2">Shortcuts</div>
                                <div class="tt-kv"><span>Search</span><span><kbd class="tt-kbd">Ctrl</kbd><kbd class="tt-kbd">K</kbd></span></div>
                                <div class="tt-kv"><span>Settings sidebar</span><span><kbd class="tt-kbd">Ctrl</kbd><kbd class="tt-kbd">,</kbd></span></div>
                            </div>

                            <button type="button" class="tt-panel-foot" @click="openDrawer('header')">Choose what shows here <v-icon size="14">mdi-arrow-right</v-icon></button>
                        </v-card>
                    </v-menu>

                    <!-- Quick settings -->
                    <v-menu :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
                        <template #activator="{ props }">
                            <button v-bind="props" type="button" class="tt-tool-btn" aria-label="Quick settings">
                                <v-icon size="20" class="tt-gear">mdi-cog-outline</v-icon>
                                <v-tooltip activator="parent" location="bottom">Quick settings</v-tooltip>
                            </button>
                        </template>
                        <v-card class="tt-panel tt-settings" :class="{ 'is-dark': isDark }" width="348" flat>
                            <div class="tt-panel-head">
                                <span class="tt-set-ico"><v-icon size="18" color="var(--h-green)">mdi-cog-outline</v-icon></span>
                                <div>
                                    <div class="tt-panel-title">Quick settings</div>
                                    <div class="tt-sub">Saved on this device</div>
                                </div>
                            </div>

                            <div class="tt-set-group">Display</div>
                            <div class="tt-set-row">
                                <div class="tt-set-label"><span>Appearance</span></div>
                                <div class="tt-seg">
                                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': !isDark }" @click="setTheme('light')"><v-icon size="15">mdi-weather-sunny</v-icon>Light</button>
                                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': isDark }" @click="setTheme('dark')"><v-icon size="15">mdi-weather-night</v-icon>Dark</button>
                                </div>
                            </div>
                            <div class="tt-set-row">
                                <div class="tt-set-label"><span>Language</span><small>Dates, digits, time and prayer names</small></div>
                                <div class="tt-seg">
                                    <button v-for="o in languages" :key="o.value" type="button" class="tt-seg-btn" :class="{ 'is-on': dateLang === o.value }" :title="o.name" @click="setDateLang(o.value)"><span class="tt-flag" v-html="flagSvg[o.value]"></span></button>
                                </div>
                            </div>
                            <div class="tt-set-row">
                                <div class="tt-set-label"><span>Time format</span></div>
                                <div class="tt-seg">
                                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': s.hour12 }" @click="setHour12(true)">12h</button>
                                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': !s.hour12 }" @click="setHour12(false)">24h</button>
                                </div>
                            </div>
                            <div class="tt-set-row">
                                <div class="tt-set-label"><span>Compact header</span><small>Tighter spacing, more room for content</small></div>
                                <label class="tt-switch"><input v-model="s.compact" type="checkbox" aria-label="Compact header" /><span></span></label>
                            </div>

                            <template v-for="g in moduleGroups" :key="g.title">
                                <div class="tt-set-group">{{ g.title }}</div>
                                <div v-for="it in g.items" :key="it.key" class="tt-set-row" :class="{ 'is-off': it.needs && !s[it.needs] }">
                                    <div class="tt-set-label"><span>{{ it.label }}</span><small>{{ it.hint }}</small></div>
                                    <label class="tt-switch"><input v-model="s[it.key]" type="checkbox" :aria-label="it.label" /><span></span></label>
                                </div>
                            </template>

                            <div class="tt-set-foot">
                                <button type="button" class="tt-set-reset" @click="resetSettings"><v-icon size="14">mdi-backup-restore</v-icon>Reset</button>
                                <v-spacer />
                                <button type="button" class="tt-set-all" @click="openDrawer()">All settings <v-icon size="14">mdi-arrow-right</v-icon></button>
                            </div>
                        </v-card>
                    </v-menu>

                    <!-- Notifications -->
                    <v-menu v-if="s.showNotif" :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
                        <template #activator="{ props }">
                            <v-btn v-bind="props" icon variant="text" class="tt-icon-btn" aria-label="Notifications">
                                <v-badge v-if="unreadCount" :content="unreadCount" color="#ec4899" floating class="tt-badge">
                                    <v-icon size="20">mdi-bell-outline</v-icon>
                                </v-badge>
                                <v-icon v-else size="20">mdi-bell-outline</v-icon>
                            </v-btn>
                        </template>
                        <v-card class="tt-panel" :class="{ 'is-dark': isDark }" width="392" flat>
                            <div class="tt-panel-head">
                                <div>
                                    <div class="tt-panel-title">Notifications</div>
                                    <div class="tt-sub">{{ unreadCount ? unreadCount + " unread" : "You're all caught up" }}</div>
                                </div>
                                <v-spacer />
                                <v-btn variant="text" size="small" class="text-none tt-link-btn" :disabled="!unreadCount" @click="markAllRead">Mark all read</v-btn>
                            </div>

                            <div class="tt-chip-row">
                                <button v-for="t in notifTabs" :key="t.value" type="button" class="tt-chip" :class="{ 'is-on': notifTab === t.value }" @click="notifTab = t.value">{{ t.label }}</button>
                            </div>

                            <div class="tt-notif-list">
                                <button v-for="n in filteredNotifications" :key="n.id" type="button" class="tt-notif" :class="{ 'is-unread': !n.read }" @click="openNotification(n)">
                                    <span class="tt-tile" :style="{ background: n.tint }"><v-icon size="17" :color="n.color">{{ n.icon }}</v-icon></span>
                                    <span class="flex-grow-1 text-left">
                                        <span class="tt-notif-title">{{ n.title }}</span>
                                        <span class="tt-sub d-block">{{ n.desc }}</span>
                                    </span>
                                    <span class="tt-notif-time">{{ n.time }}<span v-if="!n.read" class="tt-unread-dot"></span></span>
                                </button>
                                <div v-if="!filteredNotifications.length" class="tt-empty">
                                    <v-icon size="28" color="#9db3ad">mdi-bell-check-outline</v-icon>
                                    <div>Nothing here yet</div>
                                </div>
                            </div>

                            <button type="button" class="tt-panel-foot" @click="go('/admin/notifications')">View all notifications <v-icon size="14">mdi-arrow-right</v-icon></button>
                        </v-card>
                    </v-menu>
                </div>

                <div class="tt-divider"></div>

                <!-- User -->
                <v-menu location="bottom end" offset="10" transition="scale-transition">
                    <template #activator="{ props }">
                        <button v-bind="props" type="button" class="tt-user" aria-label="Account menu">
                            <span class="tt-avatar">{{ initials }}<span class="tt-avatar-dot"></span></span>
                            <span class="tt-user-text tt-hide-md">
                                <span class="tt-user-name">{{ user.name }}</span>
                                <span class="tt-user-role">{{ user.role }}</span>
                            </span>
                            <v-icon size="16" class="tt-muted tt-hide-md">mdi-chevron-down</v-icon>
                        </button>
                    </template>
                    <v-card class="tt-panel" :class="{ 'is-dark': isDark }" width="272" flat>
                        <div class="tt-user-card">
                            <span class="tt-avatar lg">{{ initials }}</span>
                            <div style="min-width: 0">
                                <div class="tt-uc-greet">{{ greeting }}</div>
                                <div class="tt-uc-name text-truncate">{{ user.name }}</div>
                                <div class="tt-uc-mail text-truncate">{{ user.email || user.role }}</div>
                            </div>
                        </div>
                        <div class="pa-2">
                            <button type="button" class="tt-menu-item" @click="go('/admin/account/settings')">
                                <v-icon size="18">mdi-account-cog-outline</v-icon><span class="flex-grow-1 text-left">Account settings</span>
                            </button>
                            <button type="button" class="tt-menu-item" @click="openDrawer()">
                                <v-icon size="18">mdi-tune-variant</v-icon><span class="flex-grow-1 text-left">Header &amp; display settings</span>
                            </button>
                            <button type="button" class="tt-menu-item" @click="themeSwitch">
                                <v-icon size="18">{{ isDark ? "mdi-weather-sunny" : "mdi-weather-night" }}</v-icon><span class="flex-grow-1 text-left">{{ isDark ? "Switch to light mode" : "Switch to dark mode" }}</span>
                            </button>
                            <button type="button" class="tt-menu-item" @click="go('/admin/help')">
                                <v-icon size="18">mdi-lifebuoy</v-icon><span class="flex-grow-1 text-left">Help &amp; support</span>
                            </button>
                            <div class="tt-menu-sep"></div>
                            <button type="button" class="tt-menu-item danger" :disabled="loggingOut" @click="logout">
                                <v-progress-circular v-if="loggingOut" indeterminate size="16" width="2" />
                                <v-icon v-else size="18">mdi-logout</v-icon>
                                <span class="flex-grow-1 text-left">Log out</span>
                            </button>
                        </div>
                    </v-card>
                </v-menu>
            </div>

            <!-- ═════════ Optional second bar (Settings → Header → Second bar) ═════════ -->
            <div v-if="s.showSecondBar && (s.showQuick || s.showStore || s.showPeriod)" class="tt-row2">
                <nav v-if="s.showQuick" class="tt-quick" aria-label="Quick links">
                    <router-link v-for="q in quickLinks" :key="q.label" :to="q.to" class="tt-qchip" :class="{ 'is-on': isActiveLink(q.to) }" :title="q.label">
                        <span class="tt-qico" :style="{ background: q.tint }"><v-icon size="15" :color="q.color">{{ q.icon }}</v-icon></span>
                        <span class="tt-qlabel">{{ q.label }}</span>
                        <em v-if="q.badge" class="tt-qbadge" :class="q.tone">{{ q.badge }}</em>
                    </router-link>
                </nav>

                <div class="tt-tools-end">
                    <v-menu v-if="s.showStore" location="bottom end" offset="8" transition="scale-transition">
                        <template #activator="{ props }">
                            <button v-bind="props" type="button" class="tt-period" aria-label="Choose store">
                                <v-icon size="18" color="#2f5be7">mdi-storefront-outline</v-icon>
                                <span>{{ storeLabel }}</span>
                                <v-icon size="16" class="tt-chev">mdi-chevron-down</v-icon>
                            </button>
                        </template>
                        <v-card class="tt-panel" :class="{ 'is-dark': isDark }" width="268" flat>
                            <div class="tt-panel-label">Store / warehouse</div>
                            <div class="px-2 pb-2">
                                <button v-for="o in stores" :key="o.value" type="button" class="tt-date-opt" :class="{ 'is-on': s.store === o.value }" @click="setStore(o.value)">
                                    <span class="tt-tile sm" style="background: #e6edff"><v-icon size="16" color="#2f5be7">{{ o.icon }}</v-icon></span>
                                    <span class="flex-grow-1 text-left" style="min-width: 0">
                                        <span class="tt-date-opt-text">{{ o.label }}</span>
                                        <span class="tt-sub d-block">{{ o.hint }}</span>
                                    </span>
                                    <v-icon v-if="s.store === o.value" size="18" color="var(--h-green)">mdi-check-circle</v-icon>
                                </button>
                            </div>
                        </v-card>
                    </v-menu>

                    <v-menu v-if="s.showPeriod" location="bottom end" offset="8" transition="scale-transition">
                        <template #activator="{ props }">
                            <button v-bind="props" type="button" class="tt-period" aria-label="Choose date range">
                                <v-icon size="18" color="var(--h-green)">mdi-calendar-range</v-icon>
                                <span>{{ periodLabel }}</span>
                                <v-icon size="16" class="tt-chev">mdi-chevron-down</v-icon>
                            </button>
                        </template>
                        <v-card class="tt-panel" :class="{ 'is-dark': isDark }" width="264" flat>
                            <div class="tt-panel-label">Date range</div>
                            <div class="px-2 pb-2">
                                <button v-for="o in periodOptions" :key="o.value" type="button" class="tt-date-opt" :class="{ 'is-on': s.period === o.value }" @click="setPeriod(o.value)">
                                    <span class="flex-grow-1 text-left" style="min-width: 0">
                                        <span class="tt-date-opt-text" :lang="dateLang" :class="'is-' + dateLang">{{ o.label }}</span>
                                        <span class="tt-sub d-block">{{ o.hint }}</span>
                                    </span>
                                    <v-icon v-if="s.period === o.value" size="18" color="var(--h-green)">mdi-check-circle</v-icon>
                                </button>
                            </div>
                        </v-card>
                    </v-menu>
                </div>
            </div>
        </div>
        <div class="tt-header-line"></div>
        <v-snackbar v-model="toastOpen" color="#0f9d6b" timeout="2400" location="bottom">{{ toastText }}</v-snackbar>

        <!-- ═════════ Command palette ═════════ -->
        <v-dialog v-model="paletteOpen" max-width="640" content-class="tt-cmd-dialog" scrim="#053d35" @after-enter="focusInput">
            <v-card class="tt-cmd" :class="{ 'is-dark': isDark }" flat>
                <div class="tt-cmd-input">
                    <v-icon size="20" class="tt-muted">mdi-magnify</v-icon>
                    <input ref="cmdInput" v-model="query" type="text" placeholder="Search pages, actions, orders, customers…" aria-label="Search" autocomplete="off" @keydown="onCmdKey" />
                    <button v-if="query" type="button" class="tt-clear" aria-label="Clear search" @click="query = ''"><v-icon size="16">mdi-close</v-icon></button>
                    <kbd class="tt-kbd">Esc</kbd>
                </div>

                <div ref="cmdList" class="tt-cmd-list">
                    <template v-for="g in paletteGroups" :key="g.title">
                        <div class="tt-cmd-group">{{ g.title }}</div>
                        <button
                            v-for="it in g.items"
                            :key="it.id"
                            type="button"
                            class="tt-cmd-item"
                            :class="{ 'is-active': flatItems[activeIndex] && flatItems[activeIndex].id === it.id }"
                            @mouseenter="activeIndex = flatItems.indexOf(it)"
                            @click="runItem(it)"
                        >
                            <span class="tt-tile" :style="{ background: it.tint || '#e3f6ee' }"><v-icon size="16" :color="it.color || 'var(--h-green)'">{{ it.icon }}</v-icon></span>
                            <span class="flex-grow-1 text-left">
                                <span class="tt-cmd-label">{{ it.label }}</span>
                                <span v-if="it.hint" class="tt-sub d-block">{{ it.hint }}</span>
                            </span>
                            <v-icon v-if="flatItems[activeIndex] && flatItems[activeIndex].id === it.id" size="16" class="tt-muted">mdi-subdirectory-arrow-left</v-icon>
                        </button>
                    </template>
                </div>

                <div class="tt-cmd-foot">
                    <span><kbd class="tt-kbd">↑</kbd><kbd class="tt-kbd">↓</kbd> navigate</span>
                    <span><kbd class="tt-kbd">↵</kbd> select</span>
                    <span><kbd class="tt-kbd">Esc</kbd> close</span>
                    <v-spacer />
                    <span class="tt-muted">{{ flatItems.length }} results</span>
                </div>
            </v-card>
        </v-dialog>

        <!-- ═════════ Right settings sidebar (slides in from the right) ═════════ -->
        <v-dialog v-model="drawerOpen" content-class="tt-drawer-dialog" transition="slide-x-reverse-transition" scrim="#053d35">
            <v-card class="tt-drawer" :class="{ 'is-dark': isDark }" flat>
                <div class="tt-drawer-head">
                    <span class="tt-set-ico"><v-icon size="18" color="var(--h-green)">mdi-tune-variant</v-icon></span>
                    <div>
                        <div class="tt-panel-title">Settings</div>
                        <div class="tt-sub">Saved on this device</div>
                    </div>
                    <v-spacer />
                    <v-btn icon variant="text" size="small" class="tt-icon-btn" aria-label="Close settings" @click="drawerOpen = false"><v-icon size="20">mdi-close</v-icon></v-btn>
                </div>

                <div class="tt-tabs" role="tablist">
                    <button v-for="t in drawerTabs" :key="t.value" type="button" role="tab" class="tt-tab" :class="{ 'is-on': drawerTab === t.value }" :aria-selected="drawerTab === t.value" @click="drawerTab = t.value">
                        <v-icon size="16">{{ t.icon }}</v-icon>{{ t.label }}
                    </button>
                </div>

                <div class="tt-drawer-body">
                    <!-- Header: what shows where -->
                    <template v-if="drawerTab === 'header'">
                        <template v-for="g in moduleGroups" :key="g.title">
                            <div class="tt-set-group">{{ g.title }}</div>
                            <div v-for="it in g.items" :key="it.key" class="tt-set-row" :class="{ 'is-off': it.needs && !s[it.needs] }">
                                <div class="tt-set-label"><span>{{ it.label }}</span><small>{{ it.hint }}</small></div>
                                <label class="tt-switch"><input v-model="s[it.key]" type="checkbox" :aria-label="it.label" /><span></span></label>
                            </div>
                        </template>
                        <div class="tt-set-group">Info center contents</div>
                        <div v-for="b in infoBlocks" :key="b.key" class="tt-set-row" :class="{ 'is-off': !s.showInfo }">
                            <div class="tt-set-label"><span>{{ b.label }}</span><small>{{ b.hint }}</small></div>
                            <label class="tt-switch"><input v-model="s.info[b.key]" type="checkbox" :aria-label="b.label" /><span></span></label>
                        </div>
                    </template>

                    <!-- Display -->
                    <template v-else-if="drawerTab === 'display'">
                        <div class="tt-set-group">Look</div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Appearance</span></div>
                            <div class="tt-seg">
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': !isDark }" @click="setTheme('light')"><v-icon size="15">mdi-weather-sunny</v-icon>Light</button>
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': isDark }" @click="setTheme('dark')"><v-icon size="15">mdi-weather-night</v-icon>Dark</button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Accent colour</span><small>Buttons, highlights and active states</small></div>
                            <div class="tt-swatches">
                                <button v-for="a in accentOptions" :key="a.value" type="button" class="tt-swatch" :class="{ 'is-on': s.accent === a.value }" :style="{ background: a.hex }" :title="a.label" :aria-label="a.label" @click="s.accent = a.value"></button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Compact header</span><small>Tighter spacing, more room for content</small></div>
                            <label class="tt-switch"><input v-model="s.compact" type="checkbox" aria-label="Compact header" /><span></span></label>
                        </div>

                        <div class="tt-set-group">Language &amp; time</div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Language</span><small>Dates, digits, time and prayer names</small></div>
                            <div class="tt-seg">
                                <button v-for="o in languages" :key="o.value" type="button" class="tt-seg-btn" :class="{ 'is-on': dateLang === o.value }" :title="o.name" @click="setDateLang(o.value)"><span class="tt-flag" v-html="flagSvg[o.value]"></span></button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Time format</span></div>
                            <div class="tt-seg">
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': s.hour12 }" @click="setHour12(true)">12h</button>
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': !s.hour12 }" @click="setHour12(false)">24h</button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Show seconds</span><small>In the header clock</small></div>
                            <label class="tt-switch"><input v-model="s.showSeconds" type="checkbox" aria-label="Show seconds" /><span></span></label>
                        </div>
                    </template>

                    <!-- Time: calendar + prayer -->
                    <template v-else-if="drawerTab === 'time'">
                        <div class="tt-set-group">Calendar</div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Week starts on</span></div>
                            <div class="tt-seg">
                                <button v-for="w in weekStartOptions" :key="w.value" type="button" class="tt-seg-btn" :class="{ 'is-on': s.weekStart === w.value }" @click="s.weekStart = w.value">{{ w.label }}</button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Hijri date</span><small>Match your local moon sighting</small></div>
                            <div class="tt-seg">
                                <button v-for="h in [-1, 0, 1]" :key="h" type="button" class="tt-seg-btn" :class="{ 'is-on': s.hijriShift === h }" @click="s.hijriShift = h">{{ h > 0 ? "+" + h : h }}</button>
                            </div>
                        </div>
                        <div v-for="it in calendarToggles" :key="it.key" class="tt-set-row">
                            <div class="tt-set-label"><span>{{ it.label }}</span><small>{{ it.hint }}</small></div>
                            <label class="tt-switch"><input v-model="s[it.key]" type="checkbox" :aria-label="it.label" /><span></span></label>
                        </div>

                        <div class="tt-set-group">Prayer times</div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>City</span><small>Karachi method · Hanafi Asr</small></div>
                            <label class="tt-city">
                                <v-icon size="14">mdi-map-marker-outline</v-icon>
                                <select v-model="s.prayerCity" aria-label="Prayer time city">
                                    <option v-for="c in prayerCities" :key="c.name" :value="c.name">{{ c.name }}</option>
                                </select>
                            </label>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Nafl &amp; avoid times</span><small>Ishraq, Chasht, Zawal, Awabin, Tahajjud</small></div>
                            <label class="tt-switch"><input v-model="s.showNafl" type="checkbox" aria-label="Show nafl and avoid times" /><span></span></label>
                        </div>

                        <div class="tt-set-group">Match your timetable (minutes)</div>
                        <div v-for="a in adjustKeys" :key="a.key" class="tt-set-row">
                            <div class="tt-set-label"><span>{{ a.label }}</span></div>
                            <div class="tt-step">
                                <button type="button" :aria-label="'Earlier ' + a.label" @click="nudge(a.key, -1)">−</button>
                                <b>{{ s.adjust[a.key] > 0 ? "+" + s.adjust[a.key] : s.adjust[a.key] }}</b>
                                <button type="button" :aria-label="'Later ' + a.label" @click="nudge(a.key, 1)">+</button>
                            </div>
                        </div>
                    </template>

                    <!-- Data: store, range, export -->
                    <template v-else>
                        <div class="tt-set-group">Store / warehouse</div>
                        <div class="px-2">
                            <button v-for="o in stores" :key="o.value" type="button" class="tt-date-opt" :class="{ 'is-on': s.store === o.value }" @click="setStore(o.value)">
                                <span class="tt-tile sm" style="background: #e6edff"><v-icon size="16" color="#2f5be7">{{ o.icon }}</v-icon></span>
                                <span class="flex-grow-1 text-left" style="min-width: 0">
                                    <span class="tt-date-opt-text">{{ o.label }}</span>
                                    <span class="tt-sub d-block">{{ o.hint }}</span>
                                </span>
                                <v-icon v-if="s.store === o.value" size="18" color="var(--h-green)">mdi-check-circle</v-icon>
                            </button>
                        </div>

                        <div class="tt-set-group">Date range</div>
                        <div class="tt-chip-row tt-wchips" style="flex-wrap: wrap">
                            <button v-for="o in periodOptions" :key="o.value" type="button" class="tt-chip" :class="{ 'is-on': s.period === o.value }" :title="o.hint" @click="setPeriod(o.value)">{{ o.label }}</button>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Compare with previous period</span><small>Show change next to your numbers</small></div>
                            <label class="tt-switch"><input v-model="s.comparePrev" type="checkbox" aria-label="Compare with previous period" /><span></span></label>
                        </div>

                        <div class="tt-set-group">Share &amp; export</div>
                        <div class="px-2">
                            <button type="button" class="tt-menu-item" @click="exportReport('csv')"><span class="tt-tile sm" style="background: #dff5ec"><v-icon size="16" color="#0f9d6b">mdi-file-delimited-outline</v-icon></span><span class="flex-grow-1 text-left">Export report (CSV)</span></button>
                            <button type="button" class="tt-menu-item" @click="exportReport('pdf')"><span class="tt-tile sm" style="background: #fde8e8"><v-icon size="16" color="#dc2626">mdi-file-pdf-box</v-icon></span><span class="flex-grow-1 text-left">Export report (PDF)</span></button>
                            <button type="button" class="tt-menu-item" @click="emailReport"><span class="tt-tile sm" style="background: #fff1d1"><v-icon size="16" color="#d97706">mdi-email-fast-outline</v-icon></span><span class="flex-grow-1 text-left">Email this report</span></button>
                            <button type="button" class="tt-menu-item" @click="printPage"><span class="tt-tile sm" style="background: #e6edff"><v-icon size="16" color="#2f5be7">mdi-printer-outline</v-icon></span><span class="flex-grow-1 text-left">Print this page</span></button>
                            <button type="button" class="tt-menu-item" @click="copyLink"><span class="tt-tile sm" style="background: #efe8ff"><v-icon size="16" color="#7c3aed">mdi-link-variant</v-icon></span><span class="flex-grow-1 text-left">Copy link to this view</span></button>
                            <button type="button" class="tt-menu-item" @click="resetView"><span class="tt-tile sm" style="background: #eef2f1"><v-icon size="16" color="#6b7f7b">mdi-backup-restore</v-icon></span><span class="flex-grow-1 text-left">Reset period and store</span></button>
                        </div>
                    </template>
                </div>

                <div class="tt-set-foot">
                    <button type="button" class="tt-set-reset" @click="resetSettings"><v-icon size="14">mdi-backup-restore</v-icon>Reset all</button>
                    <v-spacer />
                    <button type="button" class="tt-set-all" @click="go('/admin/account/settings')">Account settings <v-icon size="14">mdi-arrow-right</v-icon></button>
                </div>
            </v-card>
        </v-dialog>

        <!-- Edge handle: standard left-chevron tab on the right side of the page -->
        <Teleport to="body">
            <button v-if="s.showHandle" type="button" class="tt-handle" :class="{ 'is-dark': isDark, 'is-hidden': drawerOpen }" aria-label="Open settings sidebar" title="Settings (Ctrl ,)" @click="openDrawer()">
                <v-icon size="18" class="tt-handle-gear">mdi-cog-outline</v-icon>
                <v-icon size="18">mdi-chevron-left</v-icon>
            </button>
        </Teleport>
    </header>
</template>

<script>
import { useTheme } from "vuetify";
import Auth from "@/auth.js";

const RECENT_KEY = "tt_recent_searches";
const SETTINGS_KEY = "tt_header_settings_v2";
const ACCENTS = [
    { value: "green", label: "Emerald", hex: "#0f9d6b" },
    { value: "blue", label: "Blue", hex: "#2f5be7" },
    { value: "violet", label: "Violet", hex: "#7c3aed" },
    { value: "rose", label: "Rose", hex: "#e11d48" },
    { value: "amber", label: "Amber", hex: "#d97706" },
    { value: "teal", label: "Teal", hex: "#0f766e" },
];
const BN_MONTHS = ["বৈশাখ", "জ্যৈষ্ঠ", "আষাঢ়", "শ্রাবণ", "ভাদ্র", "আশ্বিন", "কার্তিক", "অগ্রহায়ণ", "পৌষ", "মাঘ", "ফাল্গুন", "চৈত্র"];
const toDigits = (n, lang) => {
    const set = lang === "bn" ? "০১২৩৪৫৬৭৮৯" : lang === "ar" ? "٠١٢٣٤٥٦٧٨٩" : null;
    return set ? String(n).replace(/\d/g, (d) => set[d]) : String(n);
};
const isoKey = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

// Every user setting lives in one object, so it is saved, restored and reset in one place.
const makeDefaults = (weekStart, hijriShift) => ({
    dateLang: "en",
    hour12: true,
    showSeconds: true,
    compact: false,
    accent: "green",
    // header modules
    showClock: true,
    showDateInChip: true,
    showWaqt: true,
    showFastChip: true,
    showTabs: true,
    showSectionActions: true,
    showSearch: true,
    showToday: true,
    showCreate: true,
    showFullscreen: true,
    showInfo: true,
    showNotif: true,
    showSecondBar: false,
    showQuick: true,
    showStore: true,
    showPeriod: true,
    showHandle: true,
    // calendar
    weekStart,
    hijriShift,
    showBij: true,
    showSunnah: true,
    showFastCard: true,
    showEvents: true,
    showAltDay: true,
    // prayer: minutes to add/subtract so times match your local mosque / Islamic Foundation timetable
    prayerCity: "Dhaka",
    showNafl: true,
    adjust: { sehri: 0, iftar: 0, fajr: 0, sunrise: 0, ishraq: 0, chasht: 0, zawal: 0, dhuhr: 0, asr: 0, maghrib: 0, awabin: 0, isha: 0, tahajjud: 0 },
    // data view
    store: "all",
    period: "7d",
    comparePrev: false,
    // info center blocks
    info: { status: true, rates: true, session: true, shortcuts: true },
});

// ── Prayer times (astronomical calculation, no network needed) ──
// Karachi method (Fajr 18°, Isha 18°) with Hanafi Asr, the common choice in Bangladesh.
const PERIOD_LABELS = {
    en: { today: "Today", yesterday: "Yesterday", "7d": "Last 7 days", "30d": "Last 30 days", month: "This month", year: "This year" },
    bn: { today: "আজ", yesterday: "গতকাল", "7d": "গত ৭ দিন", "30d": "গত ৩০ দিন", month: "এই মাস", year: "এই বছর" },
    ar: { today: "اليوم", yesterday: "أمس", "7d": "آخر 7 أيام", "30d": "آخر 30 يومًا", month: "هذا الشهر", year: "هذه السنة" },
};
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
    name: "AppHeader",

    props: {
        // Show a hamburger in the header (useful on mobile). Listen with @toggle-sidebar.
        showSidebarToggle: { type: Boolean, default: false },
        // Hijri date follows Umm al-Qura. Use -1 if local moon sighting runs a day behind (common in Bangladesh).
        hijriOffset: { type: Number, default: 0 },
        // First day of the calendar week (0 = Sunday … 6 = Saturday) and which weekdays are tinted as weekend.
        weekStart: { type: Number, default: 6 },
        weekendDays: { type: Array, default: () => [5] },
    },
    emits: ["toggle-sidebar", "theme-change", "date-select", "language-change", "period-change", "store-change", "compare-change", "export"],

    setup() {
        // Uses Vuetify's theme if "light" / "dark" themes are registered; otherwise only the stored flag is used.
        const vTheme = useTheme();
        return { vTheme };
    },

    data() {
        return {
            theme: "light",
            s: makeDefaults(this.weekStart, this.hijriOffset),
            clock: new Date(),
            now: new Date(),
            calOpen: false,
            calTab: "calendar",
            calTabs: [
                { value: "calendar", label: "Calendar", icon: "mdi-calendar-month-outline" },
                { value: "prayer", label: "Prayer", icon: "mdi-mosque" },
                { value: "world", label: "World clock", icon: "mdi-earth" },
            ],
            drawerOpen: false,
            drawerTab: "header",
            drawerTabs: [
                { value: "header", label: "Header", icon: "mdi-view-dashboard-edit-outline" },
                { value: "display", label: "Display", icon: "mdi-palette-outline" },
                { value: "time", label: "Time", icon: "mdi-clock-edit-outline" },
                { value: "data", label: "Data", icon: "mdi-database-cog-outline" },
            ],
            toastOpen: false,
            toastText: "",
            hintIndex: 0,
            searchHints: ["orders", "customers", "products", "brands", "invoices", "coupons"],
            timers: [],
            isFullscreen: false,
            loggingOut: false,

            accentOptions: ACCENTS,
            weekStartOptions: [
                { value: 6, label: "Sat" },
                { value: 0, label: "Sun" },
                { value: 1, label: "Mon" },
            ],
            languages: [
                { value: "en", name: "English" },
                { value: "bn", name: "বাংলা" },
                { value: "ar", name: "العربية" },
            ],
            // Country flags used for the language switcher (English: UK, Bangla: Bangladesh, Arabic: Saudi Arabia)
            flagSvg: {
                en: '<svg viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect width="60" height="40" fill="#012169"/><path d="M0 0L60 40M60 0L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0L60 40M60 0L0 40" stroke="#C8102E" stroke-width="3"/><path d="M30 0V40M0 20H60" stroke="#fff" stroke-width="13"/><path d="M30 0V40M0 20H60" stroke="#C8102E" stroke-width="8"/></svg>',
                bn: '<svg viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect width="60" height="40" fill="#006a4e"/><circle cx="27" cy="20" r="12" fill="#f42a41"/></svg>',
                ar: '<svg viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect width="60" height="40" fill="#006c35"/><rect x="14" y="11" width="32" height="3.2" rx="1.6" fill="#fff"/><rect x="19" y="17" width="22" height="2.4" rx="1.2" fill="#fff"/><rect x="12" y="28" width="36" height="2.2" rx="1.1" fill="#fff"/><circle cx="14" cy="29" r="1.6" fill="#fff"/></svg>',
            },

            // ── Which header modules exist. Add a key here (and in makeDefaults) to add a new toggle. ──
            moduleGroups: [
                {
                    title: "Left side",
                    items: [
                        { key: "showClock", label: "Date & time", hint: "Clock chip beside the page title" },
                        { key: "showDateInChip", label: "Date in clock chip", hint: "English, Bangla or Hijri date", needs: "showClock" },
                        { key: "showWaqt", label: "Prayer line", hint: "Current and next waqt under the clock", needs: "showClock" },
                        { key: "showFastChip", label: "Sehri / Iftar chip", hint: "Only on fasting days", needs: "showWaqt" },
                        { key: "showTabs", label: "Section tabs & mini stats", hint: "Next to the clock" },
                        { key: "showSectionActions", label: "Page actions & counters", hint: "Add, import, export beside the tabs", needs: "showTabs" },
                    ],
                },
                {
                    title: "Right side",
                    items: [
                        { key: "showSearch", label: "Search", hint: "Command palette (Ctrl K)" },
                        { key: "showToday", label: "Today's sales", hint: "Ring with today's revenue" },
                        { key: "showCreate", label: "Create button", hint: "Order, product, brand and more" },
                        { key: "showFullscreen", label: "Full screen", hint: "Toggle button" },
                        { key: "showInfo", label: "Info center", hint: "Status, rates, session and shortcuts" },
                        { key: "showNotif", label: "Notifications", hint: "Bell with unread count" },
                    ],
                },
                {
                    title: "Second bar & sidebar",
                    items: [
                        { key: "showSecondBar", label: "Second bar", hint: "Quick links, store and date range under the header" },
                        { key: "showQuick", label: "Quick links", hint: "Orders, Products, Customers, Reports", needs: "showSecondBar" },
                        { key: "showStore", label: "Store selector", hint: "Switch between branches", needs: "showSecondBar" },
                        { key: "showPeriod", label: "Date range", hint: "Period for reports and charts", needs: "showSecondBar" },
                        { key: "showHandle", label: "Right sidebar", hint: "Edge handle that opens all settings (Ctrl ,)" },
                    ],
                },
            ],
            infoBlocks: [
                { key: "status", label: "System status", hint: "Storefront, payments, courier, backup" },
                { key: "rates", label: "Exchange rates", hint: "USD, SAR, AED, CNY, GBP, EUR" },
                { key: "session", label: "This session", hint: "User, time zone, store, date range" },
                { key: "shortcuts", label: "Shortcuts", hint: "Keyboard reference" },
            ],
            calendarToggles: [
                { key: "showBij", label: "Ayyam al-Bid roja", hint: "13th, 14th, 15th Hijri" },
                { key: "showSunnah", label: "Monday & Thursday roja", hint: "Weekly sunnah fasts" },
                { key: "showFastCard", label: "Sehri & Iftar", hint: "Show times in the prayer tab and header" },
                { key: "showEvents", label: "Sales events", hint: "Mark campaign days on the calendar" },
                { key: "showAltDay", label: "Second date under each day", hint: "Bangla or Hijri day number" },
            ],
            adjustKeys: [
                { key: "sehri", label: "Sehri ends" },
                { key: "fajr", label: "Fajr" },
                { key: "dhuhr", label: "Dhuhr" },
                { key: "asr", label: "Asr" },
                { key: "maghrib", label: "Maghrib" },
                { key: "iftar", label: "Iftar" },
                { key: "isha", label: "Isha" },
            ],

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
            prayerCities: [
                { name: "Dhaka", lat: 23.8103, lng: 90.4125 },
                { name: "Chattogram", lat: 22.3569, lng: 91.7832 },
                { name: "Sylhet", lat: 24.8949, lng: 91.8687 },
                { name: "Khulna", lat: 22.8456, lng: 89.5403 },
                { name: "Rajshahi", lat: 24.3745, lng: 88.6042 },
                { name: "Barishal", lat: 22.701, lng: 90.3535 },
                { name: "Rangpur", lat: 25.7439, lng: 89.2752 },
                { name: "Mymensingh", lat: 24.7471, lng: 90.4203 },
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

            stores: [
                { value: "all", label: "All stores", hint: "Combined view", icon: "mdi-store-outline" },
                { value: "dhaka", label: "Dhaka showroom", hint: "Main branch", icon: "mdi-storefront-outline" },
                { value: "ctg", label: "Chattogram branch", hint: "Port city outlet", icon: "mdi-storefront-outline" },
                { value: "online", label: "Online store", hint: "Website and marketplaces", icon: "mdi-web" },
            ],

            // ── Next to the clock: tabs for the current area, plus counters and actions. Routes match your sidebar. Stats are sample numbers: replace with API values. ──
            sectionTabs: [
                {
                    match: ["/admin/catalog", "/admin/product", "/admin/brand", "/admin/category"],
                    tabs: [
                        { label: "Products", to: "/admin/catalog", icon: "mdi-package-variant-closed" },
                        { label: "Brands", to: "/admin/brand", icon: "mdi-tag-outline" },
                        { label: "Categories", to: "/admin/category", icon: "mdi-shape-outline" },
                    ],
                    stats: [
                        { label: "Products", value: "2,146", icon: "mdi-package-variant-closed", color: "#2f5be7", tint: "#e6edff" },
                        { label: "Low stock", value: "18", icon: "mdi-archive-alert-outline", color: "#d97706", tint: "#fff1d1" },
                        { label: "Out of stock", value: "14", icon: "mdi-alert-circle-outline", color: "#dc2626", tint: "#fde8e8" },
                    ],
                    actions: [
                        { label: "Add product", icon: "mdi-plus", to: "/admin/product", primary: true },
                        { label: "Import", icon: "mdi-tray-arrow-down", to: "/admin/products/import" },
                        { label: "Export", icon: "mdi-tray-arrow-up", fn: "exportCsv" },
                    ],
                },
                {
                    match: ["/admin/orders", "/admin/create-order", "/admin/pending"],
                    tabs: [
                        { label: "All orders", to: "/admin/orders", icon: "mdi-receipt-text-outline" },
                        { label: "Pending", to: "/admin/pending", icon: "mdi-clock-outline" },
                        { label: "Processing", to: "/admin/orders/processing", icon: "mdi-progress-clock" },
                        { label: "Returns", to: "/admin/orders/returns", icon: "mdi-backup-restore" },
                    ],
                    stats: [
                        { label: "Orders today", value: "17", icon: "mdi-receipt-text-outline", color: "#2f5be7", tint: "#e6edff" },
                        { label: "To confirm", value: "6", icon: "mdi-clock-alert-outline", color: "#d97706", tint: "#fff1d1" },
                        { label: "Unpaid", value: "4", icon: "mdi-cash-clock", color: "#7c3aed", tint: "#efe8ff" },
                    ],
                    actions: [
                        { label: "New order", icon: "mdi-plus", to: "/admin/create-order", primary: true },
                        { label: "Export", icon: "mdi-tray-arrow-up", fn: "exportCsv" },
                    ],
                },
                {
                    match: ["/admin/sales-analytics", "/admin/seo-analytics", "/admin/analytics", "/admin/sales-report", "/admin/inventory-report", "/admin/customer-report"],
                    tabs: [
                        { label: "Sales analytics", to: "/admin/sales-analytics", icon: "mdi-chart-line" },
                        { label: "SEO analytics", to: "/admin/seo-analytics", icon: "mdi-magnify-scan" },
                        { label: "Sales report", to: "/admin/sales-report", icon: "mdi-file-chart-outline" },
                        { label: "Customer report", to: "/admin/customer-report", icon: "mdi-account-group-outline" },
                    ],
                    stats: [
                        { label: "Revenue today", value: "৳ 48.2K", icon: "mdi-cash-multiple", color: "#0f9d6b", tint: "#dff5ec" },
                        { label: "Conversion", value: "4.4%", icon: "mdi-percent-outline", color: "#ec4899", tint: "#fde6f1" },
                    ],
                    actions: [
                        { label: "Export report", icon: "mdi-tray-arrow-up", fn: "exportCsv", primary: true },
                        { label: "Print", icon: "mdi-printer-outline", fn: "printPage" },
                    ],
                },
                {
                    match: ["/admin/customers", "/admin/reviews"],
                    tabs: [
                        { label: "All customers", to: "/admin/customers", icon: "mdi-account-group-outline" },
                        { label: "Groups", to: "/admin/customers/groups", icon: "mdi-account-multiple-outline" },
                        { label: "Reviews", to: "/admin/reviews", icon: "mdi-star-outline" },
                    ],
                    stats: [
                        { label: "Customers", value: "1,208", icon: "mdi-account-group-outline", color: "#7c3aed", tint: "#efe8ff" },
                        { label: "New today", value: "9", icon: "mdi-account-plus-outline", color: "#0f9d6b", tint: "#dff5ec" },
                    ],
                    actions: [
                        { label: "Add customer", icon: "mdi-plus", to: "/admin/customers", primary: true },
                        { label: "Export", icon: "mdi-tray-arrow-up", fn: "exportCsv" },
                    ],
                },
                {
                    match: ["/admin/inventory"],
                    tabs: [
                        { label: "Stock levels", to: "/admin/inventory", icon: "mdi-warehouse" },
                        { label: "Low stock", to: "/admin/inventory/low-stock", icon: "mdi-archive-alert-outline" },
                        { label: "Suppliers", to: "/admin/inventory/suppliers", icon: "mdi-truck-outline" },
                    ],
                    stats: [
                        { label: "Low stock", value: "18", icon: "mdi-archive-alert-outline", color: "#d97706", tint: "#fff1d1" },
                        { label: "Out of stock", value: "14", icon: "mdi-alert-circle-outline", color: "#dc2626", tint: "#fde8e8" },
                    ],
                    actions: [{ label: "Export stock", icon: "mdi-tray-arrow-up", fn: "exportCsv", primary: true }],
                },
                {
                    match: ["/admin/coupons", "/admin/banners", "/admin/notifications"],
                    tabs: [
                        { label: "Coupons", to: "/admin/coupons", icon: "mdi-ticket-percent-outline" },
                        { label: "Banners", to: "/admin/banners", icon: "mdi-image-multiple-outline" },
                        { label: "Notifications", to: "/admin/notifications", icon: "mdi-bell-outline" },
                    ],
                    stats: [{ label: "Active coupons", value: "7", icon: "mdi-ticket-percent-outline", color: "#7c3aed", tint: "#efe8ff" }],
                    actions: [{ label: "New coupon", icon: "mdi-plus", to: "/admin/coupons", primary: true }],
                },
                {
                    match: ["/admin/shipping"],
                    tabs: [
                        { label: "Zones", to: "/admin/shipping", icon: "mdi-map-marker-radius-outline" },
                        { label: "Methods", to: "/admin/shipping/methods", icon: "mdi-truck-fast-outline" },
                    ],
                    stats: [
                        { label: "On-time", value: "94%", icon: "mdi-truck-check-outline", color: "#0f9d6b", tint: "#dff5ec" },
                        { label: "Delayed", value: "14", icon: "mdi-truck-alert-outline", color: "#dc2626", tint: "#fde8e8" },
                    ],
                    actions: [{ label: "Add method", icon: "mdi-plus", to: "/admin/shipping/methods", primary: true }],
                },
                {
                    match: ["/admin/general-settings", "/admin/storefront", "/admin/settings", "/admin/roles"],
                    tabs: [
                        { label: "General", to: "/admin/general-settings", icon: "mdi-cog-outline" },
                        { label: "Storefront", to: "/admin/storefront", icon: "mdi-storefront-outline" },
                        { label: "Payments", to: "/admin/settings/payment", icon: "mdi-credit-card-outline" },
                        { label: "Staff & roles", to: "/admin/roles", icon: "mdi-shield-account-outline" },
                    ],
                    stats: [],
                    actions: [{ label: "Help", icon: "mdi-lifebuoy", to: "/admin/help" }],
                },
            ],

            // ── Sample snapshot: replace with GET /api/admin/dashboard/today ──
            glance: {
                revenue: 48200,
                revenueDelta: 12.4,
                monthRevenue: 960000,
                monthTarget: 1200000,
                targetPct: 80,
                peakHour: "8–9 PM",
                hourly: [2, 1, 1, 3, 5, 8, 6, 9, 7, 10, 12, 9, 14, 11, 8, 13, 16, 12],
                tiles: [
                    { label: "Orders today", value: "17", delta: 8.2, good: true, icon: "mdi-receipt-text-outline", color: "#2f5be7", tint: "#e6edff" },
                    { label: "Avg. order value", value: "৳ 2,835", delta: 3.4, good: true, icon: "mdi-basket-outline", color: "#d97706", tint: "#fff1d1" },
                    { label: "Conversion rate", value: "4.4%", delta: 0.6, good: true, icon: "mdi-percent-outline", color: "#ec4899", tint: "#fde6f1" },
                    { label: "New customers", value: "9", delta: -2.1, good: false, icon: "mdi-account-plus-outline", color: "#7c3aed", tint: "#efe8ff" },
                ],
                attention: [
                    { label: "Orders awaiting confirmation", count: 6, icon: "mdi-clock-alert-outline", color: "#d97706", tint: "#fff1d1", to: "/admin/orders" },
                    { label: "Low-stock products", count: 18, icon: "mdi-archive-alert-outline", color: "#dc2626", tint: "#fde8e8", to: "/admin/catalog" },
                    { label: "Pending payments", count: 4, icon: "mdi-cash-clock", color: "#2f5be7", tint: "#e6edff", to: "/admin/orders" },
                ],
            },

            // ── Sample info center data: replace with GET /api/admin/system-status and a rates endpoint ──
            infoStatus: [
                { label: "Storefront", value: "Online", tone: "ok", icon: "mdi-storefront-outline" },
                { label: "Payments (bKash, Nagad, cards)", value: "All working", tone: "ok", icon: "mdi-credit-card-check-outline" },
                { label: "Courier API", value: "Slow", tone: "warn", icon: "mdi-truck-alert-outline" },
                { label: "Last backup", value: "6h ago", tone: "ok", icon: "mdi-database-check-outline" },
            ],
            infoRates: [
                { code: "USD", value: "122.40" },
                { code: "SAR", value: "32.64" },
                { code: "AED", value: "33.33" },
                { code: "CNY", value: "16.90" },
                { code: "GBP", value: "158.10" },
                { code: "EUR", value: "135.20" },
            ],

            paletteOpen: false,
            query: "",
            activeIndex: 0,
            recent: [],

            notifTab: "all",
            notifTabs: [
                { label: "All", value: "all" },
                { label: "Orders", value: "orders" },
                { label: "Stock", value: "stock" },
                { label: "System", value: "system" },
            ],
            // ── Sample notifications: replace with GET /api/notifications ──
            notifications: [
                { id: 1, type: "orders", title: "New order #TT-10482", desc: "Rahim Traders · ৳ 48,500 · Paid via bKash", time: "2m", icon: "mdi-receipt-text-outline", color: "#0f9d6b", tint: "#dff5ec", read: false, to: "/admin/orders" },
                { id: 2, type: "stock", title: "Low stock: Anker PowerCore 20000", desc: "Only 3 units left across all warehouses", time: "18m", icon: "mdi-alert-outline", color: "#d97706", tint: "#fff1d1", read: false, to: "/admin/catalog" },
                { id: 3, type: "orders", title: "Delivery delayed — Khulna", desc: "14 parcels stuck for more than 4 days", time: "1h", icon: "mdi-truck-alert-outline", color: "#dc2626", tint: "#fde8e8", read: false, to: "/admin/orders" },
                { id: 4, type: "orders", title: "Payment received", desc: "Nusrat Jahan · ৳ 12,900 via Nagad", time: "3h", icon: "mdi-cash-check", color: "#2f5be7", tint: "#e6edff", read: true, to: "/admin/orders" },
                { id: 5, type: "system", title: "Nightly backup completed", desc: "Database snapshot saved successfully", time: "6h", icon: "mdi-database-check-outline", color: "#7c3aed", tint: "#efe8ff", read: true, to: null },
            ],

            // ── Update these routes to match your router ──
            quickCreate: [
                { label: "New order", icon: "mdi-receipt-text-plus-outline", color: "#0f9d6b", tint: "#dff5ec", to: "/admin/orders" },
                { label: "Add product", icon: "mdi-package-variant-plus", color: "#2f5be7", tint: "#e6edff", to: "/admin/product" },
                { label: "Add brand", icon: "mdi-tag-plus-outline", color: "#d97706", tint: "#fff1d1", to: "/admin/brand" },
                { label: "Add category", icon: "mdi-shape-plus-outline", color: "#7c3aed", tint: "#efe8ff", to: "/admin/category" },
                { label: "Add customer", icon: "mdi-account-plus-outline", color: "#0f766e", tint: "#dcf3f0", to: "/admin/customers" },
            ],
            pages: [
                { label: "Dashboard", to: "/admin/dashboard", icon: "mdi-view-dashboard-outline", hint: "Overview of your store", words: "home overview kpi" },
                { label: "Sales analytics", to: "/admin/sales-analytics", icon: "mdi-chart-line", hint: "Revenue, funnel, forecast", words: "revenue reports funnel" },
                { label: "Orders", to: "/admin/orders", icon: "mdi-receipt-text-outline", hint: "Manage customer orders", words: "sales invoices" },
                { label: "Catalog", to: "/admin/catalog", icon: "mdi-package-variant-closed", hint: "Products and stock", words: "products inventory stock" },
                { label: "Brands", to: "/admin/brand", icon: "mdi-tag-outline", hint: "Manage brands", words: "samsung anker xiaomi" },
                { label: "Categories", to: "/admin/category", icon: "mdi-shape-outline", hint: "Catalog structure", words: "subcategory" },
                { label: "Customers", to: "/admin/customers", icon: "mdi-account-group-outline", hint: "Customer list and tiers", words: "clients buyers" },
                { label: "Account settings", to: "/admin/account/settings", icon: "mdi-account-cog-outline", hint: "Profile and password", words: "profile password" },
            ],
        };
    },

    computed: {
        isDark() {
            return this.theme === "dark";
        },
        dateLang() {
            return this.s.dateLang;
        },
        hour12() {
            return this.s.hour12;
        },
        hijriShift() {
            return this.s.hijriShift;
        },
        ui() {
            return UI[this.s.dateLang];
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
            return this.s.showEvents ? this.calEvents[isoKey(this.selectedDate)] || "" : "";
        },
        showFastChip() {
            return this.s.showFastChip && this.s.showFastCard && this.fastInfo.isFasting;
        },
        hourlyMax() {
            return Math.max(...this.glance.hourly);
        },
        infoWarnings() {
            return this.s.info.status ? this.infoStatus.filter((r) => r.tone !== "ok").length : 0;
        },

        // ── Time ──
        greeting() {
            const h = this.now.getHours();
            return h < 12 ? "Good morning" : h < 17 ? "Good afternoon" : "Good evening";
        },
        dateLabel() {
            return this.now.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" });
        },
        timeLabel() {
            return this.fmtTime(this.clock, { locale: "en-US" });
        },
        timeShort() {
            return this.fmtTime(this.clock);
        },
        timeFull() {
            return this.fmtTime(this.clock, { seconds: this.s.showSeconds });
        },
        localTz() {
            try {
                return Intl.DateTimeFormat().resolvedOptions().timeZone || "Asia/Dhaka";
            } catch (e) {
                return "Asia/Dhaka";
            }
        },
        tzLabel() {
            try {
                const part = new Intl.DateTimeFormat("en", { timeZoneName: "shortOffset" }).formatToParts(this.clock).find((x) => x.type === "timeZoneName");
                return `${this.localTz.replace(/_/g, " ")}${part ? " · " + part.value : ""}`;
            } catch (e) {
                return "";
            }
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
                    key: isoKey(date),
                    date,
                    inMonth: date.getMonth() === this.calMonth,
                    isToday: sameDay(date, today),
                    isSelected: sameDay(date, this.selectedDate),
                    weekend: this.weekendDays.includes(date.getDay()),
                    primary: toDigits(date.getDate(), lang),
                    secondary,
                    event: this.s.showEvents && !!this.calEvents[isoKey(date)],
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
            return this.prayerCities.find((c) => c.name === this.s.prayerCity) || this.prayerCities[0];
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

        // ── Section area next to the clock ──
        currentSection() {
            const path = (this.$route && this.$route.path) || "";
            const sec = this.sectionTabs.find((x) => x.match.some((m) => path.startsWith(m)));
            return { tabs: (sec && sec.tabs) || [], stats: (sec && sec.stats) || [], actions: (sec && sec.actions) || [] };
        },
        currentTabs() {
            return this.currentSection.tabs;
        },
        // the tab whose route is the longest match for the current path (so "All orders" is not lit on /orders/processing)
        activeTabTo() {
            const path = (this.$route && this.$route.path) || "";
            let best = "";
            this.currentTabs.forEach((t) => {
                const base = t.to.split("?")[0];
                if ((path === base || path.startsWith(base + "/")) && base.length > best.length) best = t.to;
            });
            return best;
        },
        miniMetrics() {
            return this.glance.tiles.slice(0, 3);
        },

        // ── Second bar ──
        storeLabel() {
            return (this.stores.find((x) => x.value === this.s.store) || this.stores[0]).label;
        },
        quickLinks() {
            const a = this.glance.attention;
            return [
                { label: "Orders", icon: "mdi-receipt-text-outline", color: "#2f5be7", tint: "#e6edff", to: "/admin/orders", badge: a[0].count, tone: "" },
                { label: "Products", icon: "mdi-package-variant-closed", color: "#0f9d6b", tint: "#dff5ec", to: "/admin/catalog", badge: a[1].count, tone: "warn" },
                { label: "Customers", icon: "mdi-account-group-outline", color: "#7c3aed", tint: "#efe8ff", to: "/admin/customers" },
                { label: "Reports", icon: "mdi-chart-line", color: "#d97706", tint: "#fff1d1", to: "/admin/sales-analytics" },
            ];
        },
        periodRanges() {
            const d = new Date(this.now.getFullYear(), this.now.getMonth(), this.now.getDate());
            const back = (n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() - n);
            return {
                today: [d, d],
                yesterday: [back(1), back(1)],
                "7d": [back(6), d],
                "30d": [back(29), d],
                month: [new Date(d.getFullYear(), d.getMonth(), 1), d],
                year: [new Date(d.getFullYear(), 0, 1), d],
            };
        },
        periodOptions() {
            const fmt = new Intl.DateTimeFormat("en-GB", { day: "numeric", month: "short" });
            return Object.keys(this.periodRanges).map((value) => {
                const [from, to] = this.periodRanges[value];
                return { value, label: PERIOD_LABELS[this.dateLang][value], hint: sameDay(from, to) ? fmt.format(from) : `${fmt.format(from)} – ${fmt.format(to)}` };
            });
        },
        periodLabel() {
            return PERIOD_LABELS[this.dateLang][this.s.period];
        },
        daysLeft() {
            const d = this.now;
            return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate() - d.getDate();
        },

        // ── Page context ──
        crumbs() {
            const path = (this.$route && this.$route.path) || "";
            const parts = path.split("/").filter(Boolean);
            const trimmed = parts[0] === "admin" ? parts.slice(1) : parts;
            let acc = parts[0] === "admin" ? "/admin" : "";
            return trimmed.map((p) => {
                acc += "/" + p;
                return { label: p.replace(/[-_]/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()), to: acc };
            });
        },
        parentCrumbs() {
            return this.crumbs.slice(0, -1);
        },
        pageTitle() {
            const r = this.$route || {};
            const pretty = (x) => String(x).replace(/([a-z0-9])([A-Z])/g, "$1 $2").replace(/[-_]/g, " ").replace(/\s+/g, " ").trim();
            return pretty((r.meta && r.meta.title) || r.name || (this.crumbs.length ? this.crumbs[this.crumbs.length - 1].label : "Dashboard"));
        },
        pageIcon() {
            const path = ((this.$route && this.$route.path) || "").toLowerCase();
            const hit = this.pages.find((p) => path.startsWith(p.to.toLowerCase()));
            if (hit) return hit.icon;
            const map = [["analytic", "mdi-chart-line"], ["order", "mdi-receipt-text-outline"], ["product", "mdi-package-variant-closed"], ["catalog", "mdi-package-variant-closed"], ["brand", "mdi-tag-outline"], ["categor", "mdi-shape-outline"], ["customer", "mdi-account-group-outline"], ["setting", "mdi-cog-outline"]];
            const m = map.find(([k]) => path.includes(k));
            return m ? m[1] : "mdi-view-dashboard-outline";
        },

        // ── User ──
        user() {
            let u = {};
            try {
                const raw = JSON.parse(window.localStorage.getItem("user") || "{}") || {};
                u = raw.user || raw;
            } catch (e) {
                u = {};
            }
            return { name: u.name || u.username || "Admin", email: u.email || "", role: u.role || u.role_name || "Administrator" };
        },
        initials() {
            return this.user.name
                .split(" ")
                .filter(Boolean)
                .map((w) => w[0])
                .slice(0, 2)
                .join("")
                .toUpperCase();
        },

        // ── Notifications ──
        unreadCount() {
            return this.notifications.filter((n) => !n.read).length;
        },
        filteredNotifications() {
            return this.notifTab === "all" ? this.notifications : this.notifications.filter((n) => n.type === this.notifTab);
        },

        // ── Command palette ──
        paletteGroups() {
            const q = this.query.trim().toLowerCase();
            const groups = [];
            const actions = [
                ...this.quickCreate.map((c, i) => ({ id: "qc" + i, label: c.label, hint: "Quick action", icon: c.icon, color: c.color, tint: c.tint, to: c.to })),
                { id: "a-settings", label: "Open settings sidebar", hint: "Header, display, time and data", icon: "mdi-tune-variant", color: "#0f9d6b", tint: "#dff5ec", action: "openDrawer" },
                { id: "a-theme", label: this.isDark ? "Switch to light mode" : "Switch to dark mode", hint: "Appearance", icon: this.isDark ? "mdi-weather-sunny" : "mdi-weather-night", color: "#d97706", tint: "#fff1d1", action: "themeSwitch" },
                { id: "a-fs", label: "Toggle full screen", hint: "Display", icon: "mdi-fullscreen", color: "#2f5be7", tint: "#e6edff", action: "toggleFullscreen" },
                { id: "a-logout", label: "Log out", hint: "End your session", icon: "mdi-logout", color: "#dc2626", tint: "#fde8e8", action: "logout" },
            ];
            const pages = this.pages.map((p, i) => ({ id: "pg" + i, label: p.label, hint: p.hint, icon: p.icon, to: p.to, words: p.words }));
            const match = (it) => !q || (it.label + " " + (it.hint || "") + " " + (it.words || "")).toLowerCase().includes(q);

            if (!q && this.recent.length) {
                groups.push({ title: "Recent searches", items: this.recent.map((t, i) => ({ id: "rc" + i, label: t, icon: "mdi-history", color: "#6b7f7b", tint: "#eef2f1", fill: t })) });
            }
            const a = actions.filter(match);
            if (a.length) groups.push({ title: "Quick actions", items: q ? a : a.slice(0, 6) });
            const p = pages.filter(match);
            if (p.length) groups.push({ title: "Go to", items: p });

            if (q) {
                const enc = encodeURIComponent(this.query.trim());
                groups.push({
                    title: `Search for “${this.query.trim()}”`,
                    items: [
                        { id: "s-orders", label: "Orders", hint: "Find in all orders", icon: "mdi-receipt-text-outline", color: "#0f9d6b", tint: "#dff5ec", to: `/admin/orders?search=${enc}`, remember: true },
                        { id: "s-products", label: "Products", hint: "Find in catalog", icon: "mdi-package-variant-closed", color: "#2f5be7", tint: "#e6edff", to: `/admin/catalog?search=${enc}`, remember: true },
                        { id: "s-customers", label: "Customers", hint: "Find by name, phone or email", icon: "mdi-account-group-outline", color: "#7c3aed", tint: "#efe8ff", to: `/admin/customers?search=${enc}`, remember: true },
                    ],
                });
            }
            return groups;
        },
        flatItems() {
            return this.paletteGroups.flatMap((g) => g.items);
        },
    },

    watch: {
        // One deep watcher saves every setting
        s: {
            deep: true,
            handler() {
                this.saveSettings();
            },
        },
        "s.accent"(v) {
            this.applyAccent(v);
        },
        "s.comparePrev"(v) {
            this.$emit("compare-change", v);
        },
        query() {
            this.activeIndex = 0;
        },
        paletteOpen(v) {
            if (v) {
                this.query = "";
                this.activeIndex = 0;
                this.loadRecent();
            }
        },
        activeIndex() {
            this.$nextTick(() => {
                const el = this.$refs.cmdList && this.$refs.cmdList.querySelector(".tt-cmd-item.is-active");
                if (el) el.scrollIntoView({ block: "nearest" });
            });
        },
    },

    mounted() {
        this.theme = window.localStorage.getItem("theme_mode") === "dark" ? "dark" : "light";
        this.applyTheme(this.theme);
        this.loadSettings();
        this.applyAccent(this.s.accent);
        this.loadRecent();

        window.addEventListener("keydown", this.onGlobalKey);
        document.addEventListener("fullscreenchange", this.onFullscreenChange);

        this.timers.push(
            setInterval(() => {
                this.clock = new Date();
                if (this.clock.getMinutes() !== this.now.getMinutes()) this.now = this.clock;
            }, 1000)
        );
        this.timers.push(setInterval(() => (this.hintIndex = (this.hintIndex + 1) % this.searchHints.length), 3200));
    },
    beforeUnmount() {
        this.timers.forEach(clearInterval);
        window.removeEventListener("keydown", this.onGlobalKey);
        document.removeEventListener("fullscreenchange", this.onFullscreenChange);
    },

    methods: {
        // ── Formatting ──
        shortMoney(v) {
            const f = (n) => String(Math.round(n * 10) / 10);
            if (v >= 10000000) return "৳ " + f(v / 10000000) + "Cr";
            if (v >= 100000) return "৳ " + f(v / 100000) + "L";
            if (v >= 1000) return "৳ " + f(v / 1000) + "K";
            return "৳ " + v;
        },
        fmtTime(d, { seconds = false, tz = "", locale = "" } = {}) {
            const loc = locale || { en: "en-US", bn: "bn-BD", ar: "ar-EG" }[this.dateLang];
            const opt = { hour: this.hour12 ? "numeric" : "2-digit", minute: "2-digit", hour12: this.hour12 };
            if (seconds) opt.second = "2-digit";
            if (tz) opt.timeZone = tz;
            try {
                return new Intl.DateTimeFormat(loc, opt).format(d);
            } catch (e) {
                return d.toLocaleTimeString();
            }
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
        toast(msg) {
            this.toastText = msg;
            this.toastOpen = true;
        },

        // ── Settings ──
        loadSettings() {
            try {
                const o = JSON.parse(window.localStorage.getItem(SETTINGS_KEY) || "{}");
                const d = this.s;
                Object.keys(d).forEach((k) => {
                    if (!(k in o)) return;
                    if (k === "adjust" || k === "info") d[k] = { ...d[k], ...o[k] };
                    else if (typeof o[k] === typeof d[k]) d[k] = o[k];
                });
                if (!this.languages.some((l) => l.value === d.dateLang)) d.dateLang = "en";
                if (![0, 1, 6].includes(d.weekStart)) d.weekStart = this.weekStart;
                if (![-1, 0, 1].includes(d.hijriShift)) d.hijriShift = this.hijriOffset;
                if (!this.stores.some((x) => x.value === d.store)) d.store = "all";
                if (!PERIOD_LABELS.en[d.period]) d.period = "7d";
                if (!this.prayerCities.some((c) => c.name === d.prayerCity)) d.prayerCity = "Dhaka";
                if (!ACCENTS.some((a) => a.value === d.accent)) d.accent = "green";
            } catch (e) {
                /* ignore broken settings */
            }
        },
        saveSettings() {
            try {
                window.localStorage.setItem(SETTINGS_KEY, JSON.stringify(this.s));
            } catch (e) {
                /* storage unavailable */
            }
        },
        resetSettings() {
            Object.assign(this.s, makeDefaults(this.weekStart, this.hijriOffset));
            this.toast("Settings reset");
        },
        applyAccent(name) {
            const a = ACCENTS.find((x) => x.value === name) || ACCENTS[0];
            document.documentElement.style.setProperty("--tt-accent", a.hex);
        },
        nudge(key, step) {
            this.s.adjust[key] = Math.max(-30, Math.min(30, (this.s.adjust[key] || 0) + step));
        },
        openDrawer(tab) {
            if (typeof tab === "string") this.drawerTab = tab;
            this.drawerOpen = true;
        },
        setHour12(v) {
            this.s.hour12 = v;
        },
        setDateLang(v) {
            this.s.dateLang = v;
            this.$emit("language-change", v);
        },
        setStore(v) {
            this.s.store = v;
            this.$emit("store-change", v);
        },
        setPeriod(v) {
            this.s.period = v;
            const [from, to] = this.periodRanges[v];
            this.$emit("period-change", { value: v, from: isoKey(from), to: isoKey(to) });
        },
        resetView() {
            this.setPeriod("7d");
            this.setStore("all");
            this.s.comparePrev = false;
            this.toast("Period and store reset");
        },

        // ── Section actions (buttons beside the tabs) ──
        runSectionAction(a) {
            if (a.fn && typeof this[a.fn] === "function") this[a.fn]();
            else if (a.to) this.go(a.to);
        },
        exportCsv() {
            this.exportReport("csv");
        },
        exportReport(format) {
            const [from, to] = this.periodRanges[this.s.period];
            this.$emit("export", { format, period: this.s.period, from: isoKey(from), to: isoKey(to), store: this.s.store, compare: this.s.comparePrev });
            this.toast(format === "email" ? "Report will be emailed to you" : `Preparing ${format.toUpperCase()} report…`);
        },
        emailReport() {
            this.exportReport("email");
        },
        printPage() {
            this.drawerOpen = false;
            setTimeout(() => window.print(), 250);
        },
        copyLink() {
            const u = new URL(window.location.href);
            u.searchParams.set("period", this.s.period);
            u.searchParams.set("store", this.s.store);
            const done = () => this.toast("Link copied");
            if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(u.toString()).then(done, done);
            else done();
        },
        isActiveLink(to) {
            const path = (this.$route && this.$route.path) || "";
            return path === to || path.startsWith(to + "/");
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
            this.$emit("date-select", isoKey(d));
        },
        goToday() {
            this.selectDate(new Date());
        },

        // ── Theme ──
        setTheme(name) {
            if (this.theme !== name) this.themeSwitch();
        },
        themeSwitch() {
            this.theme = this.theme === "light" ? "dark" : "light";
            window.localStorage.setItem("theme_mode", this.theme);
            this.applyTheme(this.theme);
            this.$emit("theme-change", this.theme);
        },
        applyTheme(name) {
            document.documentElement.setAttribute("data-theme", name);
            try {
                if (this.vTheme && this.vTheme.themes && this.vTheme.themes.value[name]) this.vTheme.global.name.value = name;
            } catch (e) {
                /* theme not registered, ignore */
            }
        },

        // ── Navigation ──
        go(to) {
            if (to) this.$router.push(to);
        },
        openNotification(n) {
            n.read = true;
            if (n.to) this.go(n.to);
        },
        markAllRead() {
            this.notifications.forEach((n) => (n.read = true));
        },

        // ── Fullscreen ──
        toggleFullscreen() {
            if (!document.fullscreenElement) document.documentElement.requestFullscreen && document.documentElement.requestFullscreen();
            else document.exitFullscreen && document.exitFullscreen();
        },
        onFullscreenChange() {
            this.isFullscreen = !!document.fullscreenElement;
        },

        // ── Command palette ──
        openPalette() {
            this.paletteOpen = true;
        },
        focusInput() {
            this.$nextTick(() => this.$refs.cmdInput && this.$refs.cmdInput.focus());
        },
        // Ctrl / Cmd + K toggles search, Ctrl / Cmd + , opens the settings sidebar
        onGlobalKey(e) {
            if (!(e.metaKey || e.ctrlKey) || !e.key) return;
            const k = e.key.toLowerCase();
            if (k === "k") {
                e.preventDefault();
                this.paletteOpen = !this.paletteOpen;
            } else if (k === ",") {
                e.preventDefault();
                this.drawerOpen = !this.drawerOpen;
            }
        },
        onCmdKey(e) {
            const n = this.flatItems.length;
            if (e.key === "ArrowDown") {
                e.preventDefault();
                this.activeIndex = n ? (this.activeIndex + 1) % n : 0;
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                this.activeIndex = n ? (this.activeIndex - 1 + n) % n : 0;
            } else if (e.key === "Enter") {
                e.preventDefault();
                const it = this.flatItems[this.activeIndex];
                if (it) this.runItem(it);
            }
        },
        runItem(it) {
            if (it.fill) {
                this.query = it.fill;
                this.focusInput();
                return;
            }
            if (it.remember) this.addRecent(this.query.trim());
            this.paletteOpen = false;
            if (it.to) this.go(it.to);
            else if (it.action && typeof this[it.action] === "function") this[it.action]();
        },
        loadRecent() {
            try {
                this.recent = JSON.parse(window.localStorage.getItem(RECENT_KEY) || "[]").slice(0, 4);
            } catch (e) {
                this.recent = [];
            }
        },
        addRecent(term) {
            if (!term) return;
            const next = [term, ...this.recent.filter((t) => t !== term)].slice(0, 5);
            this.recent = next;
            try {
                window.localStorage.setItem(RECENT_KEY, JSON.stringify(next));
            } catch (e) {
                /* storage unavailable */
            }
        },

        // ── Auth ──
        logout() {
            if (this.loggingOut) return;
            this.loggingOut = true;
            this.axios
                .post("/api/logout", window.localStorage.getItem("user"))
                .then(() => {
                    Auth.logout();
                    window.location.href = "/admin/login";
                })
                .catch(() => {
                    this.loggingOut = false;
                });
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600&family=Noto+Naskh+Arabic:wght@400;500;600&display=swap");

/* ───────── Tokens (shared by header, teleported panels, dialogs and the edge handle) ─────────
   --tt-accent is set on <html> from Settings → Display → Accent colour; every tint below derives from it. */
.tt-header,
.tt-panel,
.tt-cmd,
.tt-drawer,
.tt-handle {
    --h-bg: #ffffff;
    --h-ink: #0f2a26;
    --h-muted: #6b7f7b;
    --h-line: #e3eae8;
    --h-field: #f1f6f5;
    --h-green: var(--tt-accent, #0f9d6b);
    --h-green2: color-mix(in srgb, var(--h-green) 78%, #fff);
    --h-hover: color-mix(in srgb, var(--h-green) 10%, var(--h-bg));
    --h-soft: color-mix(in srgb, var(--h-green) 16%, var(--h-bg));
    --h-glow: color-mix(in srgb, var(--h-green) 16%, transparent);
    --h-edge: color-mix(in srgb, var(--h-green) 45%, transparent);
    --h-deep: #053d35;
    --h-mint: #6ee7b7;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--h-ink);
}
.tt-header.is-dark,
.tt-panel.is-dark,
.tt-cmd.is-dark,
.tt-drawer.is-dark,
.tt-handle.is-dark {
    --h-bg: #0c1f1b;
    --h-ink: #e6f2ef;
    --h-muted: #8fb0a8;
    --h-line: rgba(255, 255, 255, 0.1);
    --h-field: rgba(255, 255, 255, 0.06);
}
.tt-panel,
.tt-cmd,
.tt-drawer {
    background: var(--h-bg) !important;
}
.tt-sub { font-size: 12px; color: var(--h-muted); line-height: 1.4; }
.tt-muted { color: var(--h-muted); }

/* ───────── Bar ───────── */
.tt-header {
    container: tth / inline-size;
    isolation: isolate;
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 0 20px;
    background-color: color-mix(in srgb, var(--h-bg) 90%, transparent);
    backdrop-filter: saturate(180%) blur(14px);
    -webkit-backdrop-filter: saturate(180%) blur(14px);
    border-bottom: 1px solid var(--h-line);
    box-shadow: 0 10px 24px -20px rgba(5, 61, 53, 0.45);
}
.tt-header::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background-image:
        radial-gradient(560px 150px at 0% 0%, var(--h-glow), transparent 72%),
        radial-gradient(440px 130px at 100% 0%, rgba(236, 72, 153, 0.06), transparent 72%),
        linear-gradient(100deg, color-mix(in srgb, var(--h-green) 6%, var(--h-bg)) 0%, var(--h-bg) 42%);
}
.tt-inner { position: relative; z-index: 1; display: flex; flex-wrap: wrap; align-items: center; column-gap: 16px; }
.tt-header-line { position: absolute; left: 0; right: 0; bottom: -1px; height: 2px; pointer-events: none; background: linear-gradient(90deg, var(--h-green), transparent 38%); opacity: 0.85; }
.tt-left { display: flex; align-items: center; gap: 14px; min-width: 0; min-height: 64px; flex: 1 1 0; }
.tt-right { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; min-height: 64px; justify-content: flex-end; }

.tt-header.is-compact .tt-left, .tt-header.is-compact .tt-right { min-height: 52px; }
.tt-header.is-compact .tt-page-tile { width: 34px; height: 34px; border-radius: 10px; }
.tt-header.is-compact .tt-time, .tt-header.is-compact .tt-today, .tt-header.is-compact .tt-actions, .tt-header.is-compact .tt-user, .tt-header.is-compact .tt-search { height: 38px; }
.tt-header.is-compact .tt-create-btn { height: 36px !important; }
.tt-header.is-compact .tt-row2 { margin-bottom: 8px; }

/* ───────── Title ───────── */
.tt-context { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 0 1 auto; max-width: 260px; }
.tt-page-tile { flex: none; width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: linear-gradient(135deg, var(--h-green), color-mix(in srgb, var(--h-green) 55%, #000)); box-shadow: 0 8px 16px -8px var(--h-green), inset 0 1px 0 rgba(255, 255, 255, 0.25); }
.tt-title-row { display: flex; align-items: center; gap: 6px; min-width: 0; }
.tt-title-parent { font-size: 13px; font-weight: 500; color: var(--h-muted); text-decoration: none; white-space: nowrap; }
a.tt-title-parent:hover { color: var(--h-green); }
.tt-page-title { font-size: 18px; font-weight: 700; letter-spacing: -0.2px; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--h-ink); }

/* ───────── Date + time chip (left, next to the title) ───────── */
.tt-time { flex: none; display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 10px 0 6px; border-radius: 12px; border: 1px solid var(--h-line); background: linear-gradient(135deg, var(--h-glow), transparent 65%), var(--h-bg); font-family: inherit; color: var(--h-ink); cursor: pointer; text-align: left; transition: border-color 0.15s, box-shadow 0.15s; }
.tt-time:hover { border-color: var(--h-green); box-shadow: 0 0 0 4px var(--h-glow); }
.tt-time:focus-visible, .tt-today:focus-visible, .tt-search:focus-visible, .tt-user:focus-visible, .tt-period:focus-visible { outline: 2px solid var(--h-green); outline-offset: 2px; }
.tt-time-ico { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; background: var(--h-soft); flex: none; }
.tt-time-text { display: flex; flex-direction: column; line-height: 1.25; }
.tt-time-main { display: flex; align-items: baseline; gap: 9px; white-space: nowrap; }
.tt-time-clock { font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; }
.tt-time-clock.is-bn, .tt-time-date.is-bn { font-family: "Noto Sans Bengali", Poppins, sans-serif; }
.tt-time-clock.is-ar, .tt-time-date.is-ar { font-family: "Noto Naskh Arabic", Poppins, sans-serif; }
.tt-time-date { display: inline-flex; align-items: center; gap: 5px; padding-left: 9px; border-left: 1px solid var(--h-line); font-size: 11.5px; font-weight: 500; color: var(--h-muted); }
.tt-date-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--c); box-shadow: 0 0 0 3px color-mix(in srgb, var(--c) 18%, transparent); flex: none; }
.tt-chev { color: var(--h-muted); flex: none; }
.tt-d-short, .tt-time-short { display: none; }
.tt-time-next { display: flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 500; color: var(--h-muted); white-space: nowrap; }
.tt-time-next b { font-weight: 600; color: var(--h-ink); }
.tt-time-next b.is-bn { font-family: "Noto Sans Bengali", Poppins, sans-serif; }
.tt-time-next b.is-ar { font-family: "Noto Naskh Arabic", Poppins, sans-serif; }
.tt-time-next em { font-style: normal; font-weight: 600; color: var(--h-green); }
.tt-time-fast { display: inline-flex; align-items: center; gap: 3px; margin-left: 4px; padding: 0 7px; border-radius: 9px; background: #ede9fe; color: #6d28d9; }
.tt-time-fast b { color: inherit; font-weight: 700; }
.tt-header.is-dark .tt-time-fast { background: rgba(124, 58, 237, 0.25); color: #c4b5fd; }

/* ───────── Section area ───────── */
.tt-section { flex: 1 1 0; min-width: 0; padding-left: 16px; border-left: 1px solid var(--h-line); display: flex; align-items: center; gap: 12px; height: 36px; }
.tt-stabs { display: flex; align-items: center; gap: 4px; min-width: 0; flex: 0 1 auto; overflow-x: auto; scrollbar-width: none; }
.tt-stabs::-webkit-scrollbar { display: none; }
.tt-stab { flex: none; display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 12px; border-radius: 10px; border: 1px solid transparent; font-size: 13px; font-weight: 600; color: var(--h-muted); text-decoration: none; white-space: nowrap; transition: background 0.15s, color 0.15s; }
.tt-stab:hover { background: var(--h-hover); color: var(--h-ink); }
.tt-stab.is-on { background: var(--h-hover); color: var(--h-green); border-color: var(--h-edge); }
.tt-metrics { display: flex; align-items: center; gap: 8px; min-width: 0; overflow: hidden; }
.tt-mini { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 10px 0 6px; border-radius: 12px; border: 1px solid var(--h-line); background: var(--h-bg); white-space: nowrap; }
.tt-mini-ico { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.tt-mini-text { display: flex; flex-direction: column; line-height: 1.2; }
.tt-mini-val { font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums; }
.tt-mini-lbl { font-size: 10.5px; color: var(--h-muted); }
.tt-sec-end { margin-left: auto; flex: none; display: flex; align-items: center; gap: 10px; }
.tt-sec-stats, .tt-sec-actions { display: flex; align-items: center; gap: 8px; }
.tt-sstat { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 11px 0 5px; border-radius: 10px; border: 1px solid var(--h-line); background: var(--h-bg); font-size: 12px; white-space: nowrap; }
.tt-sstat-ico { width: 24px; height: 24px; border-radius: 7px; display: grid; place-items: center; flex: none; }
.tt-sstat b { font-weight: 700; font-variant-numeric: tabular-nums; }
.tt-sstat em { font-style: normal; color: var(--h-muted); }
.tt-sact { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 34px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--h-line); background: var(--h-bg); font-family: inherit; font-size: 12.5px; font-weight: 600; color: var(--h-ink); white-space: nowrap; cursor: pointer; transition: border-color 0.15s, color 0.15s, filter 0.15s; }
.tt-sact:hover { border-color: var(--h-green); color: var(--h-green); }
.tt-sact:focus-visible { outline: 2px solid var(--h-green); outline-offset: 2px; }
.tt-sact.is-primary { border-color: transparent; color: #fff; background: linear-gradient(135deg, var(--h-green), var(--h-green2)); box-shadow: 0 6px 14px -8px var(--h-green); }
.tt-sact.is-primary:hover { color: #fff; filter: brightness(1.06); }

/* ───────── Clock panel ───────── */
.tt-cal { max-height: calc(100vh - 88px); overflow-y: auto; }
.tt-clockbox { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px 18px; color: #fff; background: linear-gradient(135deg, #053d35 0%, #0a5548 60%, #0b5f50 100%); }
.tt-clockbox::after, .tt-user-card::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: linear-gradient(90deg, #ec4899, rgba(236, 72, 153, 0) 70%); }
.tt-clock-time { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; line-height: 1.1; font-variant-numeric: tabular-nums; white-space: nowrap; }
.tt-clock-time.is-bn { font-family: "Noto Sans Bengali", Poppins, sans-serif; }
.tt-clock-time.is-ar { font-family: "Noto Naskh Arabic", Poppins, sans-serif; }
.tt-clock-tz { display: flex; align-items: center; gap: 4px; margin-top: 5px; font-size: 11.5px; color: rgba(255, 255, 255, 0.72); }
.tt-fmt-toggle { display: inline-flex; padding: 3px; gap: 2px; border-radius: 9px; background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); font-family: inherit; cursor: pointer; }
.tt-fmt-toggle span { padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 600; color: rgba(255, 255, 255, 0.7); transition: all 0.15s; }
.tt-fmt-toggle span.on { background: #fff; color: #0a5548; }
.tt-tabs { display: flex; gap: 4px; padding: 10px 12px 0; border-bottom: 1px solid var(--h-line); }
.tt-tab { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 38px; border: 0; border-bottom: 2px solid transparent; background: transparent; font-family: inherit; font-size: 12.5px; font-weight: 600; color: var(--h-muted); cursor: pointer; transition: color 0.15s, border-color 0.15s; }
.tt-tab:hover { color: var(--h-ink); }
.tt-tab.is-on { color: var(--h-green); border-bottom-color: var(--h-green); }
.tt-pane { padding: 14px 14px 16px; }
.tt-note { margin-top: 10px; font-size: 11px; line-height: 1.5; color: var(--h-muted); }

/* ───────── Calendar ───────── */
.tt-cal-top { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.tt-seg { display: inline-flex; padding: 3px; gap: 2px; border-radius: 10px; background: var(--h-field); border: 1px solid var(--h-line); }
.tt-seg-btn { display: inline-flex; align-items: center; justify-content: center; gap: 4px; min-width: 40px; height: 28px; padding: 0 10px; border: 0; border-radius: 7px; background: transparent; font-family: "Noto Sans Bengali", "Noto Naskh Arabic", Poppins, sans-serif; font-size: 12px; font-weight: 600; color: var(--h-muted); cursor: pointer; transition: all 0.15s; }
.tt-seg-btn:hover { color: var(--h-ink); }
.tt-seg-btn:not(.is-on) .tt-flag { opacity: 0.55; filter: saturate(0.7); }
.tt-seg-btn.is-on { background: var(--h-bg); color: var(--h-green); box-shadow: 0 1px 4px rgba(5, 61, 53, 0.18); }
.tt-flag { display: inline-block; flex: none; width: 24px; height: 16px; border-radius: 3px; overflow: hidden; box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.15); line-height: 0; }
.tt-flag :deep(svg) { display: block; width: 100%; height: 100%; }
.tt-cal-today { display: inline-flex; align-items: center; gap: 5px; height: 28px; padding: 0 12px 0 8px; border-radius: 8px; border: 1px solid var(--h-edge); background: transparent; font-family: inherit; font-size: 12px; font-weight: 600; color: var(--h-green); cursor: pointer; }
.tt-cal-today:hover { background: var(--h-hover); }
.tt-cal-nav { display: flex; align-items: center; justify-content: space-between; padding: 4px 0 8px; }
.tt-cal-title { text-align: center; line-height: 1.3; }
.tt-cal-month { font-size: 16px; font-weight: 700; color: var(--h-ink); }
.tt-cal-sub { font-size: 11.5px; color: var(--h-muted); margin-top: 1px; }
.tt-cal-month.is-bn, .tt-cal-sub.is-bn, .tt-cal-num.is-bn, .tt-cal-sec.is-bn, .tt-cal-week .is-bn, .tt-date-opt-text.is-bn { font-family: "Noto Sans Bengali", Poppins, sans-serif; }
.tt-cal-month.is-ar, .tt-cal-sub.is-ar, .tt-cal-num.is-ar, .tt-cal-sec.is-ar, .tt-cal-week .is-ar, .tt-date-opt-text.is-ar { font-family: "Noto Naskh Arabic", Poppins, sans-serif; }
.tt-cal-arrow { width: 32px; height: 32px; display: grid; place-items: center; border: 0; border-radius: 50%; background: var(--h-field); color: var(--h-muted); cursor: pointer; transition: background 0.12s, color 0.12s; }
.tt-cal-arrow:hover { background: var(--h-hover); color: var(--h-green); }
.tt-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
.tt-cal-week span { padding: 4px 0 6px; text-align: center; font-size: 10.5px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: var(--h-muted); }
.tt-cal-week span.is-weekend { color: #e11d48; opacity: 0.75; }
.tt-cal-cell { position: relative; height: 42px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1px; border: 0; border-radius: 12px; background: transparent; font-family: inherit; color: var(--h-ink); cursor: pointer; transition: background 0.12s; }
.tt-cal-cell:hover { background: var(--h-hover); }
.tt-cal-cell:focus-visible { outline: 2px solid var(--h-green); outline-offset: -2px; }
.tt-cal-num { font-size: 13.5px; font-weight: 600; line-height: 1.15; }
.tt-cal-sec { font-size: 9.5px; line-height: 1.1; color: var(--h-muted); }
.tt-cal-cell.is-out { opacity: 0.28; }
.tt-cal-cell.is-weekend .tt-cal-num { color: #e11d48; opacity: 0.85; }
.tt-cal-cell.is-ramadan { background: #f3edff; }
.tt-cal-cell.is-sunnah { background: #fff4df; }
.tt-cal-cell.is-bij { background: #e8f3fb; }
.tt-panel.is-dark .tt-cal-cell.is-ramadan { background: rgba(124, 58, 237, 0.2); }
.tt-panel.is-dark .tt-cal-cell.is-sunnah { background: rgba(217, 119, 6, 0.16); }
.tt-panel.is-dark .tt-cal-cell.is-bij { background: rgba(2, 132, 199, 0.18); }
.tt-cal-moons { position: absolute; top: 4px; left: 5px; display: flex; gap: 1px; }
.tt-cal-moons .moon-ram { color: #7c3aed; }
.tt-cal-moons .moon-bij { color: #0284c7; }
.tt-cal-moons .moon-sunnah { color: #d97706; }
.tt-cal-cell.is-today { background: var(--h-soft); box-shadow: inset 0 0 0 1.5px var(--h-green); }
.tt-cal-cell.is-today .tt-cal-num { color: var(--h-green); opacity: 1; }
.tt-cal-cell.is-selected { background: linear-gradient(135deg, var(--h-green), var(--h-green2)); box-shadow: 0 8px 16px -8px var(--h-green); }
.tt-cal-cell.is-selected .tt-cal-num { color: #fff; opacity: 1; }
.tt-cal-cell.is-selected .tt-cal-sec { color: rgba(255, 255, 255, 0.85); }
.tt-cal-cell.is-selected .tt-cal-moons .v-icon { color: #fff; }
.tt-cal-event { position: absolute; top: 5px; right: 7px; width: 6px; height: 6px; border-radius: 50%; background: #ec4899; }
.tt-cal-cell.is-selected .tt-cal-event { background: #fff; }
.tt-cal-event.static { position: static; flex: none; }
.tt-cal-legend { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 14px; margin-top: 12px; font-size: 11.5px; color: var(--h-muted); }
.tt-cal-legend span { display: inline-flex; align-items: center; gap: 5px; }
.tt-cal-legend .lg { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.tt-cal-legend .lg.today { background: var(--h-soft); box-shadow: inset 0 0 0 1.5px var(--h-green); }
.tt-cal-legend .lg.event { background: #ec4899; width: 7px; height: 7px; }
.tt-bij-line { display: flex; align-items: center; gap: 8px; margin-top: 10px; padding: 8px 12px; border-radius: 10px; background: #e8f3fb; font-size: 12px; color: #075985; }
.tt-panel.is-dark .tt-bij-line { background: rgba(2, 132, 199, 0.16); color: #7dd3fc; }
.tt-cal-detail { margin-top: 12px; padding: 10px 12px; border-radius: 12px; background: var(--h-field); border: 1px solid var(--h-line); }
.tt-cal-row { display: flex; align-items: center; gap: 10px; padding: 3px 0; }
.tt-date-opt-text { display: block; font-size: 13.5px; font-weight: 600; unicode-bidi: isolate; }
.tt-cal-row .tt-date-opt-text { font-size: 13px; }
.tt-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.tt-cal-evchip, .tt-chip-bij, .tt-chip-sunnah, .tt-chip-fast { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 14px; font-size: 11.5px; font-weight: 600; }
.tt-cal-evchip { color: #be185d; background: #fde6f1; }
.tt-chip-bij { color: #075985; background: #dbeefb; }
.tt-chip-sunnah { color: #92400e; background: #fde9c4; }
.tt-chip-fast { color: #6d28d9; background: #ede9fe; }
.tt-panel.is-dark .tt-cal-evchip { color: #f9a8d4; background: rgba(236, 72, 153, 0.15); }
.tt-panel.is-dark .tt-chip-bij { color: #7dd3fc; background: rgba(2, 132, 199, 0.2); }
.tt-panel.is-dark .tt-chip-sunnah { color: #fcd34d; background: rgba(217, 119, 6, 0.22); }
.tt-panel.is-dark .tt-chip-fast { color: #c4b5fd; background: rgba(124, 58, 237, 0.25); }

/* ───────── Prayer times ───────── */
.tt-next { padding: 14px 16px 16px; border-radius: 14px; color: #fff; background: linear-gradient(135deg, #0a5548, var(--h-green)); box-shadow: 0 12px 24px -16px var(--h-green); }
.tt-next-label { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255, 255, 255, 0.85); }
.tt-next-city { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: rgba(255, 255, 255, 0.8); }
.tt-next-name { font-size: 24px; font-weight: 700; line-height: 1.15; }
.tt-next-in { margin-top: 2px; font-size: 12.5px; color: rgba(255, 255, 255, 0.85); font-variant-numeric: tabular-nums; }
.tt-next-time { font-size: 26px; font-weight: 700; letter-spacing: -0.5px; font-variant-numeric: tabular-nums; }
.tt-next-name.is-bn, .tt-next-time.is-bn, .tt-prayer-name.is-bn, .tt-prayer-time.is-bn, .tt-next-row b.is-bn, .tt-fast-time.is-bn { font-family: "Noto Sans Bengali", Poppins, sans-serif; }
.tt-next-name.is-ar, .tt-next-time.is-ar, .tt-prayer-name.is-ar, .tt-prayer-time.is-ar, .tt-next-row b.is-ar, .tt-fast-time.is-ar { font-family: "Noto Naskh Arabic", Poppins, sans-serif; }
.tt-next-bar { height: 5px; margin-top: 12px; border-radius: 4px; background: rgba(255, 255, 255, 0.22); overflow: hidden; }
.tt-next-bar-fill { height: 100%; border-radius: 4px; background: #fff; transition: width 1s linear; }
.tt-next-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 12px; padding: 8px 10px; border-radius: 10px; background: rgba(255, 255, 255, 0.16); font-size: 12px; color: rgba(255, 255, 255, 0.92); }
.tt-next-row b { font-weight: 700; color: #fff; }
.tt-next-row b small { font-weight: 500; opacity: 0.8; }

.tt-fast { margin-top: 12px; padding: 12px; border-radius: 14px; border: 1px solid var(--h-line); background: var(--h-field); }
.tt-fast.is-on { border-color: rgba(124, 58, 237, 0.35); background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(217, 119, 6, 0.1)); }
.tt-fast-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
.tt-fast-title { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; color: var(--h-ink); }
.tt-fast-tag { padding: 2px 9px; border-radius: 10px; font-size: 10.5px; font-weight: 700; color: #6d28d9; background: #ede9fe; }
.tt-panel.is-dark .tt-fast-tag { color: #c4b5fd; background: rgba(124, 58, 237, 0.25); }
.tt-fast-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.tt-fast-box { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 12px; border: 1px solid var(--h-line); background: var(--h-bg); }
.tt-fast-box.is-next { border-color: var(--h-green); box-shadow: 0 0 0 3px var(--h-glow); }
.tt-fast-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: none; }
.tt-fast-ico.sehri { background: #ede9fe; color: #7c3aed; }
.tt-fast-ico.iftar { background: #fff1d1; color: #d97706; }
.tt-panel.is-dark .tt-fast-ico.sehri { background: rgba(124, 58, 237, 0.25); }
.tt-panel.is-dark .tt-fast-ico.iftar { background: rgba(217, 119, 6, 0.22); }
.tt-fast-label { font-size: 11px; font-weight: 500; color: var(--h-muted); }
.tt-fast-time { font-size: 15px; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--h-ink); }
.tt-fast-bar { height: 5px; margin-top: 10px; border-radius: 4px; background: var(--h-line); overflow: hidden; }
.tt-fast-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #7c3aed, #d97706); transition: width 1s linear; }
.tt-fast-next { display: flex; align-items: center; gap: 5px; margin-top: 8px; font-size: 11.5px; font-weight: 600; color: var(--h-muted); font-variant-numeric: tabular-nums; }

.tt-prayers { margin-top: 12px; max-height: 330px; overflow-y: auto; padding-right: 2px; }
.tt-prayer { display: flex; align-items: center; gap: 12px; padding: 9px 10px; margin-bottom: 4px; border-radius: 11px; border: 1px solid transparent; }
.tt-prayer-ico { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; background: var(--h-field); color: var(--h-muted); flex: none; }
.tt-prayer-name { font-size: 13.5px; font-weight: 600; }
.tt-prayer-time { font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums; }
.tt-prayer-state { width: 48px; display: flex; justify-content: flex-end; }
.tt-prayer.type-avoid .tt-prayer-ico { background: #fde8e8; color: #dc2626; }
.tt-prayer.type-nafl .tt-prayer-ico { background: #fff1d1; color: #d97706; }
.tt-panel.is-dark .tt-prayer.type-avoid .tt-prayer-ico { background: rgba(220, 38, 38, 0.18); }
.tt-panel.is-dark .tt-prayer.type-nafl .tt-prayer-ico { background: rgba(217, 119, 6, 0.18); }
.tt-prayer.is-past .tt-prayer-name, .tt-prayer.is-past .tt-prayer-time { color: var(--h-muted); font-weight: 500; }
.tt-prayer.is-active:not(.is-current) { background: var(--h-field); }
.tt-prayer.is-next { border-style: dashed; border-color: var(--h-edge); }
.tt-prayer.is-next .tt-prayer-ico { color: var(--h-green); }
.tt-prayer.is-current { background: var(--h-hover); border-color: var(--h-edge); }
.tt-prayer.is-current .tt-prayer-ico { background: var(--h-green) !important; color: #fff !important; }
.tt-tag-mini { margin-left: 6px; padding: 1px 7px; border-radius: 8px; font-style: normal; font-size: 10px; font-weight: 700; vertical-align: middle; }
.tt-tag-mini.nafl { color: #92400e; background: #fde9c4; }
.tt-tag-mini.avoid { color: #b3261e; background: #fde8e8; }
.tt-now-chip, .tt-next-chip { padding: 2px 8px; border-radius: 10px; font-size: 10.5px; font-weight: 700; color: #fff; }
.tt-now-chip { background: #10b981; }
.tt-next-chip { background: var(--h-green); }
.tt-prayer-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 10px; }
.tt-city { display: inline-flex; align-items: center; gap: 4px; height: 30px; padding: 0 8px; border-radius: 8px; border: 1px solid var(--h-line); background: var(--h-field); color: var(--h-muted); }
.tt-city select { border: 0; outline: none; background: transparent; font-family: inherit; font-size: 12.5px; font-weight: 600; color: var(--h-ink); cursor: pointer; }
.tt-city select option { color: #0f2a26; }

/* ───────── World clock ───────── */
.tt-wchips { padding: 0 0 10px; }
.tt-wlist { max-height: 360px; overflow-y: auto; padding-right: 2px; }
.tt-wrow { display: flex; align-items: center; gap: 12px; padding: 10px; margin-bottom: 4px; border-radius: 11px; border: 1px solid var(--h-line); background: var(--h-field); }
.tt-wrow-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: var(--h-bg); flex: none; }
.tt-wrow-city { display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 600; }
.tt-wrow-city em { font-style: normal; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 8px; color: #fff; background: var(--h-green); }
.tt-wrow-time { font-size: 15px; font-weight: 700; font-variant-numeric: tabular-nums; }

/* ───────── Today button + panel ───────── */
.tt-today { display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 14px 0 6px; border-radius: 12px; border: 1px solid var(--h-line); background: linear-gradient(135deg, var(--h-glow), transparent 70%), var(--h-bg); font-family: inherit; color: var(--h-ink); cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s; }
.tt-today:hover { border-color: var(--h-green); box-shadow: 0 0 0 4px var(--h-glow); }
.tt-ring { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; flex: none; }
.tt-ring-in { width: 26px; height: 26px; border-radius: 50%; background: var(--h-bg); display: grid; place-items: center; }
.tt-today-text { display: flex; flex-direction: column; text-align: left; line-height: 1.2; }
.tt-today-label { font-size: 11px; font-weight: 500; color: var(--h-muted); white-space: nowrap; }
.tt-today-value { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 700; }
.tt-delta { display: inline-flex; align-items: center; gap: 1px; font-size: 10.5px; font-weight: 700; padding: 1px 6px 1px 3px; border-radius: 10px; }
.tt-delta.up { color: #0f7a4d; background: #e0f5ea; }
.tt-delta.down { color: #b3261e; background: #fde9e7; }
.tt-header.is-dark .tt-delta.up, .tt-panel.is-dark .tt-delta.up { color: var(--h-mint); background: rgba(110, 231, 183, 0.12); }
.tt-glance-head { display: flex; align-items: center; gap: 8px; padding: 14px 16px 10px; background: linear-gradient(90deg, var(--h-glow), transparent 75%); border-bottom: 1px solid var(--h-line); }
.tt-pill-live { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; color: #0f7a4d; background: #e0f5ea; border: 1px solid #b7e6cd; }
.tt-panel.is-dark .tt-pill-live { color: var(--h-mint); background: rgba(110, 231, 183, 0.1); border-color: rgba(110, 231, 183, 0.3); }
.tt-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25); animation: tt-pulse 2s infinite; }
@keyframes tt-pulse { 0%, 100% { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25); } 50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } }
.tt-glance-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 14px 16px 6px; }
.tt-gtile { padding: 10px 12px; border-radius: 10px; border: 1px solid var(--h-line); background: var(--h-field); }
.tt-tile { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-tile.sm { width: 28px; height: 28px; border-radius: 8px; }
.tt-gvalue { font-size: 19px; font-weight: 700; letter-spacing: -0.3px; margin-top: 6px; }
.tt-glance-section { padding: 12px 16px; border-top: 1px solid var(--h-line); margin-top: 8px; }
.tt-glabel { font-size: 11px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--h-muted); }
.tt-gpct { font-size: 13px; font-weight: 700; color: var(--h-green); }
.tt-bar { height: 7px; border-radius: 5px; background: var(--h-line); overflow: hidden; }
.tt-bar-fill { height: 100%; border-radius: 5px; background: linear-gradient(90deg, var(--h-green), var(--h-mint)); }
.tt-bars { display: flex; align-items: flex-end; gap: 4px; height: 46px; }
.tt-bar-col { flex: 1; min-height: 3px; border-radius: 3px 3px 0 0; background: var(--h-edge); transition: background 0.15s; }
.tt-bar-col:hover, .tt-bar-col.is-peak { background: var(--h-green); }
.tt-attn-row { display: flex; align-items: center; gap: 10px; width: 100%; padding: 6px 8px; margin-bottom: 2px; border: 0; border-radius: 9px; background: transparent; font-family: inherit; font-size: 13px; font-weight: 500; color: var(--h-ink); cursor: pointer; }
.tt-attn-row:hover { background: var(--h-hover); }
.tt-attn-count { min-width: 26px; height: 22px; padding: 0 8px; border-radius: 11px; display: grid; place-items: center; font-size: 11.5px; font-weight: 700; }

/* ───────── Info center ───────── */
.tt-info { max-height: calc(100vh - 100px); overflow-y: auto; }
.tt-info .tt-panel-head { border-bottom: 1px solid var(--h-line); background: linear-gradient(90deg, var(--h-glow), transparent 75%); }
.tt-info-block { padding: 12px 16px; border-bottom: 1px solid var(--h-line); }
.tt-info-row { display: flex; align-items: center; gap: 10px; padding: 5px 0; font-size: 13px; }
.tt-info-ico { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.tt-info-val { font-size: 12px; font-weight: 700; white-space: nowrap; }
.tone-ok { color: #0f7a4d; }
.tone-warn { color: #b45309; }
.tone-bad { color: #b3261e; }
.tt-info-ico.tone-ok { background: #e0f5ea; }
.tt-info-ico.tone-warn { background: #fff1d1; }
.tt-info-ico.tone-bad { background: #fde8e8; }
.tt-panel.is-dark .tone-ok { color: var(--h-mint); }
.tt-panel.is-dark .tone-warn { color: #fcd34d; }
.tt-panel.is-dark .tone-bad { color: #fca5a5; }
.tt-panel.is-dark .tt-info-ico.tone-ok { background: rgba(110, 231, 183, 0.12); }
.tt-panel.is-dark .tt-info-ico.tone-warn { background: rgba(217, 119, 6, 0.2); }
.tt-panel.is-dark .tt-info-ico.tone-bad { background: rgba(220, 38, 38, 0.2); }
.tt-rates { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
.tt-rate { display: flex; flex-direction: column; padding: 7px 10px; border-radius: 10px; border: 1px solid var(--h-line); background: var(--h-field); font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; }
.tt-rate b { font-size: 10.5px; font-weight: 600; color: var(--h-muted); }
.tt-kv { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 4px 0; font-size: 12.5px; color: var(--h-muted); }
.tt-kv b { font-weight: 600; color: var(--h-ink); text-align: right; }

/* ───────── Right cluster ───────── */
.tt-search { display: flex; align-items: center; gap: 8px; width: 190px; height: 40px; padding: 0 12px; border-radius: 12px; border: 1px solid var(--h-line); background: var(--h-field); color: var(--h-muted); font-family: inherit; font-size: 13px; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s, background 0.15s; }
.tt-search:hover { border-color: var(--h-green); background: var(--h-bg); box-shadow: 0 0 0 4px var(--h-glow); }
.tt-search-icon { color: var(--h-green); flex: none; }
.tt-search-text { flex: 1; min-width: 0; text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-hint { display: inline-block; font-weight: 600; color: var(--h-ink); }
.tt-fade-enter-active, .tt-fade-leave-active { transition: opacity 0.22s ease, transform 0.22s ease; }
.tt-fade-enter-from { opacity: 0; transform: translateY(6px); }
.tt-fade-leave-to { opacity: 0; transform: translateY(-6px); }
.tt-create-btn { height: 40px !important; border-radius: 11px !important; font-weight: 600; letter-spacing: 0; color: #fff !important; background: linear-gradient(135deg, var(--h-green), var(--h-green2)) !important; box-shadow: 0 8px 16px -8px var(--h-green), inset 0 1px 0 rgba(255, 255, 255, 0.25) !important; }
.tt-create-btn:hover { filter: brightness(1.06); }
.tt-actions { display: flex; align-items: center; gap: 2px; height: 44px; padding: 0 4px; border-radius: 13px; background: var(--h-field); border: 1px solid var(--h-line); }
.tt-actions .tt-icon-btn { width: 36px !important; height: 36px !important; border-radius: 9px !important; }
.tt-actions .tt-icon-btn:hover { background: var(--h-bg) !important; box-shadow: 0 1px 4px rgba(5, 61, 53, 0.12); }
.tt-tool-btn { width: 36px; height: 36px; display: grid; place-items: center; border: 0; border-radius: 9px; background: transparent; color: var(--h-muted); cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-tool-btn:hover { background: var(--h-bg); color: var(--h-green); box-shadow: 0 1px 4px rgba(5, 61, 53, 0.12); }
.tt-tool-btn:focus-visible { outline: 2px solid var(--h-green); outline-offset: 1px; }
.tt-gear { transition: transform 0.4s ease; }
.tt-tool-btn:hover .tt-gear { transform: rotate(75deg); }
.tt-divider { width: 1px; height: 24px; margin: 0 4px; background: var(--h-line); }
.tt-icon-btn { width: 40px !important; height: 40px !important; border-radius: 10px !important; color: var(--h-muted) !important; transition: background 0.15s, color 0.15s; }
.tt-icon-btn:hover { background: var(--h-hover) !important; color: var(--h-green) !important; }
.tt-badge :deep(.v-badge__badge) { font-size: 10px; font-weight: 700; min-width: 17px; height: 17px; padding: 0 4px; inset-block-start: -6px !important; }
.tt-user { display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 8px 0 4px; border-radius: 12px; border: 1px solid transparent; background: transparent; cursor: pointer; font-family: inherit; color: var(--h-ink); transition: background 0.15s, border-color 0.15s; }
.tt-user:hover { background: var(--h-hover); border-color: var(--h-line); }
.tt-user-text { display: flex; flex-direction: column; text-align: left; line-height: 1.25; }
.tt-user-name { font-size: 13px; font-weight: 600; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-user-role { font-size: 11px; color: var(--h-muted); }
.tt-avatar { position: relative; flex: none; width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; font-size: 12.5px; font-weight: 700; color: var(--h-deep); background: linear-gradient(135deg, #a7f3d0, #6ee7b7); box-shadow: 0 0 0 2px var(--h-bg), 0 0 0 3.5px var(--h-edge); }
.tt-avatar.lg { width: 46px; height: 46px; font-size: 15px; box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.25); }
.tt-avatar-dot { position: absolute; right: -1px; bottom: -1px; width: 10px; height: 10px; border-radius: 50%; background: #10b981; border: 2px solid var(--h-bg); }

/* ───────── Optional second bar ───────── */
.tt-row2 { flex: 1 1 100%; display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin: 0 0 12px; }
.tt-quick { display: flex; align-items: center; gap: 6px; padding: 2px; }
.tt-qchip { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 12px 0 5px; border-radius: 12px; border: 1px solid var(--h-line); background: var(--h-bg); font-size: 12.5px; font-weight: 600; color: var(--h-ink); text-decoration: none; white-space: nowrap; transition: border-color 0.15s, color 0.15s, background 0.15s; }
.tt-qchip:hover { border-color: var(--h-green); color: var(--h-green); }
.tt-qchip.is-on { background: var(--h-hover); border-color: var(--h-edge); color: var(--h-green); }
.tt-qico { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.tt-qbadge { min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px; display: grid; place-items: center; font-style: normal; font-size: 10.5px; font-weight: 700; color: #fff; background: #2f5be7; }
.tt-qbadge.warn { background: #d97706; }
.tt-tools-end { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.tt-period { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 12px; border-radius: 12px; border: 1px solid var(--h-line); background: var(--h-bg); font-family: inherit; font-size: 12.5px; font-weight: 600; color: var(--h-ink); cursor: pointer; white-space: nowrap; transition: border-color 0.15s, box-shadow 0.15s; }
.tt-period:hover { border-color: var(--h-green); box-shadow: 0 0 0 4px var(--h-glow); }

/* ───────── Panels ───────── */
.tt-panel { border: 1px solid var(--h-line) !important; border-radius: 14px !important; box-shadow: 0 24px 48px -20px rgba(5, 61, 53, 0.35), 0 4px 12px rgba(5, 61, 53, 0.06) !important; overflow: hidden; }
.tt-panel-label { padding: 12px 16px 6px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--h-muted); }
.tt-panel-head { display: flex; align-items: center; gap: 10px; padding: 14px 16px 10px; }
.tt-panel-title { font-size: 15px; font-weight: 700; color: var(--h-ink); }
.tt-panel-foot { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; height: 44px; border: 0; border-top: 1px solid var(--h-line); background: transparent; font-family: inherit; font-size: 13px; font-weight: 600; color: var(--h-green); cursor: pointer; }
.tt-panel-foot:hover { background: var(--h-hover); }
.tt-link-btn { color: var(--h-green) !important; font-weight: 600; }
.tt-date-opt { display: flex; align-items: center; gap: 12px; width: 100%; padding: 8px 10px; margin-bottom: 4px; border: 1px solid transparent; border-radius: 10px; background: transparent; font-family: inherit; color: var(--h-ink); cursor: pointer; transition: background 0.12s; }
.tt-date-opt:hover { background: var(--h-hover); }
.tt-date-opt.is-on { border-color: var(--h-edge); background: var(--h-hover); }
.tt-menu-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 8px 10px; border: 0; border-radius: 10px; background: transparent; font-family: inherit; font-size: 13.5px; font-weight: 500; color: var(--h-ink); cursor: pointer; transition: background 0.12s; }
.tt-menu-item:hover { background: var(--h-hover); }
.tt-menu-item:focus-visible { outline: 2px solid var(--h-green); outline-offset: -2px; }
.tt-menu-item.danger { color: #dc2626; }
.tt-menu-item.danger:hover { background: rgba(220, 38, 38, 0.08); }
.tt-menu-item:disabled { opacity: 0.6; cursor: wait; }
.tt-menu-sep { height: 1px; margin: 6px 4px; background: var(--h-line); }

/* settings rows (quick settings popover + sidebar share these) */
.tt-settings { max-height: calc(100vh - 100px); overflow-y: auto; }
.tt-settings .tt-panel-head { border-bottom: 1px solid var(--h-line); background: linear-gradient(90deg, var(--h-glow), transparent 75%); }
.tt-set-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: var(--h-soft); flex: none; }
.tt-set-group { padding: 14px 16px 4px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--h-muted); }
.tt-set-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 16px; transition: opacity 0.15s; }
.tt-set-row.is-off { opacity: 0.45; }
.tt-set-label { display: flex; flex-direction: column; min-width: 0; font-size: 13px; font-weight: 600; color: var(--h-ink); }
.tt-set-label small { margin-top: 1px; font-size: 11px; font-weight: 400; color: var(--h-muted); }
.tt-set-foot { display: flex; align-items: center; margin-top: 8px; padding: 10px 16px; border-top: 1px solid var(--h-line); }
.tt-set-reset, .tt-set-all { display: inline-flex; align-items: center; gap: 5px; height: 30px; padding: 0 10px; border: 0; border-radius: 8px; background: transparent; font-family: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; }
.tt-set-reset { color: var(--h-muted); }
.tt-set-reset:hover { background: var(--h-hover); color: var(--h-ink); }
.tt-set-all { color: var(--h-green); }
.tt-set-all:hover { background: var(--h-hover); }
.tt-switch { position: relative; display: inline-block; width: 40px; height: 22px; flex: none; }
.tt-switch input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; z-index: 1; }
.tt-switch span { position: absolute; inset: 0; border-radius: 12px; background: var(--h-line); transition: background 0.18s; }
.tt-switch span::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3); transition: transform 0.18s; }
.tt-switch input:checked + span { background: var(--h-green); }
.tt-switch input:checked + span::after { transform: translateX(18px); }
.tt-switch input:focus-visible + span { outline: 2px solid var(--h-green); outline-offset: 2px; }
.tt-swatches { display: flex; gap: 6px; }
.tt-swatch { width: 22px; height: 22px; border: 0; border-radius: 50%; cursor: pointer; box-shadow: 0 0 0 2px var(--h-bg), 0 0 0 3px var(--h-line); transition: transform 0.15s; }
.tt-swatch:hover { transform: scale(1.12); }
.tt-swatch.is-on { box-shadow: 0 0 0 2px var(--h-bg), 0 0 0 4px var(--h-ink); }
.tt-step { display: inline-flex; align-items: center; gap: 2px; padding: 3px; border-radius: 10px; background: var(--h-field); border: 1px solid var(--h-line); }
.tt-step button { width: 26px; height: 26px; border: 0; border-radius: 7px; background: transparent; font-family: inherit; font-size: 15px; font-weight: 600; color: var(--h-muted); cursor: pointer; }
.tt-step button:hover { background: var(--h-bg); color: var(--h-green); }
.tt-step b { min-width: 34px; text-align: center; font-size: 12.5px; font-weight: 700; font-variant-numeric: tabular-nums; }

.tt-user-card { display: flex; align-items: center; gap: 12px; padding: 16px; color: #fff; background: linear-gradient(135deg, #053d35 0%, #0a5548 60%, #0b5f50 100%); position: relative; }
.tt-uc-greet { font-size: 11px; color: var(--h-mint); font-weight: 500; letter-spacing: 0.02em; }
.tt-uc-name { font-size: 14px; font-weight: 600; }
.tt-uc-mail { font-size: 11.5px; color: rgba(255, 255, 255, 0.7); }

/* notifications + chips */
.tt-chip-row { display: flex; gap: 6px; padding: 0 16px 10px; }
.tt-chip { height: 28px; padding: 0 12px; border-radius: 16px; border: 1px solid var(--h-line); background: transparent; font-family: inherit; font-size: 12px; font-weight: 500; color: var(--h-muted); cursor: pointer; transition: all 0.12s; }
.tt-chip:hover { border-color: var(--h-green); color: var(--h-green); }
.tt-chip.is-on { background: var(--h-green); border-color: var(--h-green); color: #fff; }
.tt-notif-list { max-height: 340px; overflow-y: auto; border-top: 1px solid var(--h-line); }
.tt-notif { display: flex; align-items: flex-start; gap: 12px; width: 100%; padding: 12px 16px; border: 0; border-bottom: 1px solid var(--h-line); background: transparent; font-family: inherit; cursor: pointer; color: var(--h-ink); transition: background 0.12s; }
.tt-notif:hover { background: var(--h-hover); }
.tt-notif.is-unread { background: linear-gradient(90deg, var(--h-glow), transparent 70%); }
.tt-notif-title { display: block; font-size: 13px; font-weight: 600; line-height: 1.35; }
.tt-notif-time { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--h-muted); white-space: nowrap; padding-top: 2px; }
.tt-unread-dot { width: 8px; height: 8px; border-radius: 50%; background: #ec4899; }
.tt-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 36px 16px; font-size: 13px; color: var(--h-muted); }

/* ───────── Command palette ───────── */
.tt-cmd { border-radius: 16px !important; border: 1px solid var(--h-line); overflow: hidden; box-shadow: 0 32px 64px -24px rgba(5, 61, 53, 0.55) !important; }
.tt-cmd-input { display: flex; align-items: center; gap: 12px; height: 58px; padding: 0 16px; border-bottom: 1px solid var(--h-line); }
.tt-cmd-input input { flex: 1; min-width: 0; height: 100%; border: 0; outline: none; background: transparent; font-family: inherit; font-size: 15px; color: var(--h-ink); }
.tt-cmd-input input::placeholder { color: var(--h-muted); }
.tt-clear { width: 24px; height: 24px; display: grid; place-items: center; border: 0; border-radius: 50%; background: var(--h-field); color: var(--h-muted); cursor: pointer; }
.tt-clear:hover { color: var(--h-ink); }
.tt-cmd-list { max-height: min(56vh, 420px); overflow-y: auto; padding: 6px 8px 10px; }
.tt-cmd-group { padding: 12px 10px 6px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--h-muted); }
.tt-cmd-item { display: flex; align-items: center; gap: 12px; width: 100%; padding: 8px 10px; border: 0; border-radius: 10px; background: transparent; font-family: inherit; color: var(--h-ink); cursor: pointer; }
.tt-cmd-item.is-active { background: var(--h-hover); box-shadow: inset 3px 0 0 var(--h-green); }
.tt-cmd-label { font-size: 13.5px; font-weight: 600; }
.tt-cmd-foot { display: flex; align-items: center; gap: 16px; height: 42px; padding: 0 16px; border-top: 1px solid var(--h-line); font-size: 11.5px; color: var(--h-muted); background: var(--h-field); }
.tt-kbd { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; padding: 0 6px; margin: 0 2px; font-family: inherit; font-size: 10.5px; font-weight: 600; color: var(--h-muted); background: var(--h-bg); border: 1px solid var(--h-line); border-bottom-width: 2px; border-radius: 6px; }

/* ───────── Right settings sidebar + edge handle ───────── */
.tt-drawer { height: 100%; display: flex; flex-direction: column; border-radius: 0 !important; border-left: 1px solid var(--h-line) !important; box-shadow: -24px 0 48px -24px rgba(5, 61, 53, 0.45) !important; }
.tt-drawer-head { display: flex; align-items: center; gap: 10px; padding: 14px 12px 12px 16px; background: linear-gradient(90deg, var(--h-glow), transparent 75%); }
.tt-drawer .tt-tabs { padding: 0 8px; }
.tt-drawer .tt-tab { font-size: 12px; gap: 5px; }
.tt-drawer-body { flex: 1; min-height: 0; overflow-y: auto; padding-bottom: 8px; }
.tt-drawer .tt-set-foot { margin-top: 0; }
.tt-handle { position: fixed; right: 0; top: 50%; transform: translateY(-50%); z-index: 120; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; width: 30px; height: 68px; padding: 0; border: 1px solid var(--h-line); border-right: 0; border-radius: 14px 0 0 14px; background: var(--h-bg); color: var(--h-muted); box-shadow: -6px 8px 20px -10px rgba(5, 61, 53, 0.45); cursor: pointer; transition: width 0.18s, color 0.15s, transform 0.22s, opacity 0.22s; }
.tt-handle:hover { width: 38px; color: var(--h-green); border-color: var(--h-edge); }
.tt-handle:focus-visible { outline: 2px solid var(--h-green); outline-offset: 2px; }
.tt-handle-gear { transition: transform 0.4s ease; }
.tt-handle:hover .tt-handle-gear { transform: rotate(75deg); }
.tt-handle.is-hidden { opacity: 0; pointer-events: none; transform: translate(100%, -50%); }

/* ───────── Responsive ─────────
   Container queries use the header's own width, so an open app sidebar is accounted for.
   The right side sheds detail first, then the section area, so the title and clock stay readable. */
@container tth (max-width: 2299px) { .tt-sec-stats { display: none; } }
@container tth (max-width: 1899px) {
    .tt-sact-label { display: none; }
    .tt-sact { padding: 0 10px; }
}
@container tth (max-width: 1799px) {
    .tt-sact:not(.is-primary) { display: none; }
    .tt-time-nextwide, .tt-time-fast { display: none; }
}
@container tth (max-width: 1699px) {
    .tt-hide-md { display: none !important; }
    .tt-today .tt-delta { display: none; }
    .tt-search { width: 40px; padding: 0; justify-content: center; }
    .tt-search-text { display: none; }
}
@container tth (max-width: 1499px) {
    .tt-time-full, .tt-d-long { display: none; }
    .tt-time-short, .tt-d-short { display: inline; }
    .tt-stab:not(.is-on) .tt-stab-label { display: none; }
    .tt-stab:not(.is-on) { padding: 0 9px; }
}
@container tth (max-width: 1349px) {
    .tt-stab .tt-stab-label { display: none; }
    .tt-stab { padding: 0 10px; }
    .tt-today-label, .tt-create-label { display: none; }
    .tt-create-btn { min-width: 0 !important; padding: 0 12px !important; }
}
@container tth (max-width: 1199px) { .tt-section { display: none; } }
@container tth (max-width: 979px) {
    .tt-today { display: none; }
    .tt-time-next, .tt-title-parent { display: none !important; }
}
@container tth (max-width: 679px) {
    .tt-hide-xs { display: none !important; }
    .tt-time-date { display: none; }
    .tt-page-tile { width: 36px; height: 36px; border-radius: 10px; }
    .tt-actions { padding: 0 2px; }
    .tt-time { padding: 0 8px 0 6px; }
    .tt-context { max-width: 150px; }
}

@media (prefers-reduced-motion: reduce) {
    .tt-search, .tt-icon-btn, .tt-menu-item, .tt-sact, .tt-qchip, .tt-handle, .tt-gear, .tt-handle-gear { transition: none; }
    .tt-pill-dot { animation: none; }
}
</style>

<style>
/* Dialog wrappers live in a teleport, so their position needs unscoped rules */
.tt-cmd-dialog { align-self: flex-start; margin-top: 11vh !important; width: 100%; }
.v-dialog > .v-overlay__content.tt-drawer-dialog { align-self: stretch; margin: 0 0 0 auto !important; width: 420px !important; max-width: 100vw !important; height: 100% !important; max-height: 100% !important; }
.v-dialog > .v-overlay__content.tt-drawer-dialog > .v-card { height: 100%; }
.v-overlay__scrim { backdrop-filter: blur(3px); }
</style>