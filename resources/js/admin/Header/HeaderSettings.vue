<template>
    <!-- ═════════ Quick settings (gear popover in the tools cluster) ═════════ -->
    <v-menu :close-on-content-click="false" location="bottom end" offset="10" transition="scale-transition">
        <template #activator="{ props }">
            <button v-bind="props" type="button" class="tt-tool-btn" aria-label="Quick settings">
                <v-icon size="20" class="tt-gear">mdi-cog-outline</v-icon>
                <v-tooltip activator="parent" location="bottom">Quick settings</v-tooltip>
            </button>
        </template>
        <v-card class="tt-panel tt-settings" :class="{ 'is-dark': hdr.isDark }" width="348" flat>
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
                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': !hdr.isDark }" @click="hdr.setTheme('light')"><v-icon size="15">mdi-weather-sunny</v-icon>Light</button>
                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': hdr.isDark }" @click="hdr.setTheme('dark')"><v-icon size="15">mdi-weather-night</v-icon>Dark</button>
                </div>
            </div>
            <div class="tt-set-row">
                <div class="tt-set-label"><span>Language</span><small>Dates, digits, time and prayer names</small></div>
                <div class="tt-seg">
                    <button v-for="o in hdr.languages" :key="o.value" type="button" class="tt-seg-btn" :class="{ 'is-on': s.dateLang === o.value }" :title="o.name" @click="hdr.setDateLang(o.value)"><span class="tt-flag" v-html="hdr.flagSvg[o.value]"></span></button>
                </div>
            </div>
            <div class="tt-set-row">
                <div class="tt-set-label"><span>Time format</span></div>
                <div class="tt-seg">
                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': s.hour12 }" @click="hdr.setHour12(true)">12h</button>
                    <button type="button" class="tt-seg-btn" :class="{ 'is-on': !s.hour12 }" @click="hdr.setHour12(false)">24h</button>
                </div>
            </div>
            <div class="tt-set-row">
                <div class="tt-set-label"><span>Compact header</span><small>Tighter spacing, more room for content</small></div>
                <label class="tt-switch"><input v-model="s.compact" type="checkbox" aria-label="Compact header" /><span></span></label>
            </div>

            <div class="px-2 pt-1">
                <button type="button" class="tt-menu-item" @click="hdr.openDrawer('header')">
                    <v-icon size="18">mdi-view-dashboard-edit-outline</v-icon><span class="flex-grow-1 text-left">Choose what shows in the header</span><v-icon size="14" class="tt-muted">mdi-arrow-right</v-icon>
                </button>
            </div>

            <div class="tt-set-foot">
                <button type="button" class="tt-set-reset" @click="hdr.resetSettings()"><v-icon size="14">mdi-backup-restore</v-icon>Reset</button>
                <v-spacer />
                <button type="button" class="tt-set-all" @click="hdr.openDrawer()">All settings <v-icon size="14">mdi-arrow-right</v-icon></button>
            </div>
        </v-card>
    </v-menu>

    <!-- ═════════ Right settings drawer: fixed to the right edge, full viewport height, slides in from the right ═════════ -->
    <Teleport to="body">
        <div class="tt-drawer-root" :class="{ 'is-open': hdr.drawerOpen }">
            <div class="tt-drawer-backdrop" aria-hidden="true" @click="hdr.closeDrawer()"></div>

            <aside class="tt-drawer" :class="{ 'is-dark': hdr.isDark }" role="dialog" aria-modal="true" aria-labelledby="tt-drawer-title" :aria-hidden="!hdr.drawerOpen" :inert="!hdr.drawerOpen">
                <div class="tt-drawer-head">
                    <span class="tt-set-ico"><v-icon size="18" color="var(--h-green)">mdi-tune-variant</v-icon></span>
                    <div>
                        <div id="tt-drawer-title" class="tt-panel-title">Settings</div>
                        <div class="tt-sub">Saved on this device</div>
                    </div>
                    <v-spacer />
                    <v-btn ref="closeBtn" icon variant="text" size="small" class="tt-icon-btn" aria-label="Close settings" @click="hdr.closeDrawer()"><v-icon size="20">mdi-close</v-icon></v-btn>
                </div>

                <div class="tt-tabs" role="tablist">
                    <button v-for="t in drawerTabs" :key="t.value" type="button" role="tab" class="tt-tab" :class="{ 'is-on': hdr.drawerTab === t.value }" :aria-selected="hdr.drawerTab === t.value" @click="hdr.drawerTab = t.value">
                        <v-icon size="16">{{ t.icon }}</v-icon>{{ t.label }}
                    </button>
                </div>

                <div class="tt-drawer-body">
                    <!-- Header: what shows where -->
                    <template v-if="hdr.drawerTab === 'header'">
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
                    <template v-else-if="hdr.drawerTab === 'display'">
                        <div class="tt-set-group">Look</div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Appearance</span></div>
                            <div class="tt-seg">
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': !hdr.isDark }" @click="hdr.setTheme('light')"><v-icon size="15">mdi-weather-sunny</v-icon>Light</button>
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': hdr.isDark }" @click="hdr.setTheme('dark')"><v-icon size="15">mdi-weather-night</v-icon>Dark</button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Accent colour</span><small>Buttons, highlights and active states</small></div>
                            <div class="tt-swatches">
                                <button v-for="a in hdr.accentOptions" :key="a.value" type="button" class="tt-swatch" :class="{ 'is-on': s.accent === a.value }" :style="{ background: a.hex }" :title="a.label" :aria-label="a.label" @click="s.accent = a.value"></button>
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
                                <button v-for="o in hdr.languages" :key="o.value" type="button" class="tt-seg-btn" :class="{ 'is-on': s.dateLang === o.value }" :title="o.name" @click="hdr.setDateLang(o.value)"><span class="tt-flag" v-html="hdr.flagSvg[o.value]"></span></button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Time format</span></div>
                            <div class="tt-seg">
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': s.hour12 }" @click="hdr.setHour12(true)">12h</button>
                                <button type="button" class="tt-seg-btn" :class="{ 'is-on': !s.hour12 }" @click="hdr.setHour12(false)">24h</button>
                            </div>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Show seconds</span><small>In the header clock</small></div>
                            <label class="tt-switch"><input v-model="s.showSeconds" type="checkbox" aria-label="Show seconds" /><span></span></label>
                        </div>
                    </template>

                    <!-- Time: calendar + prayer -->
                    <template v-else-if="hdr.drawerTab === 'time'">
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
                                    <option v-for="c in hdr.prayerCities" :key="c.name" :value="c.name">{{ c.name }}</option>
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
                            <button v-for="o in hdr.stores" :key="o.value" type="button" class="tt-date-opt" :class="{ 'is-on': s.store === o.value }" @click="hdr.setStore(o.value)">
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
                            <button v-for="o in hdr.periodOptions" :key="o.value" type="button" class="tt-chip" :class="{ 'is-on': s.period === o.value }" :title="o.hint" @click="hdr.setPeriod(o.value)">{{ o.label }}</button>
                        </div>
                        <div class="tt-set-row">
                            <div class="tt-set-label"><span>Compare with previous period</span><small>Show change next to your numbers</small></div>
                            <label class="tt-switch"><input v-model="s.comparePrev" type="checkbox" aria-label="Compare with previous period" /><span></span></label>
                        </div>

                        <div class="tt-set-group">Share &amp; export</div>
                        <div class="px-2">
                            <button type="button" class="tt-menu-item" @click="hdr.exportReport('csv')"><span class="tt-tile sm" style="background: #dff5ec"><v-icon size="16" color="#0f9d6b">mdi-file-delimited-outline</v-icon></span><span class="flex-grow-1 text-left">Export report (CSV)</span></button>
                            <button type="button" class="tt-menu-item" @click="hdr.exportReport('pdf')"><span class="tt-tile sm" style="background: #fde8e8"><v-icon size="16" color="#dc2626">mdi-file-pdf-box</v-icon></span><span class="flex-grow-1 text-left">Export report (PDF)</span></button>
                            <button type="button" class="tt-menu-item" @click="hdr.emailReport()"><span class="tt-tile sm" style="background: #fff1d1"><v-icon size="16" color="#d97706">mdi-email-fast-outline</v-icon></span><span class="flex-grow-1 text-left">Email this report</span></button>
                            <button type="button" class="tt-menu-item" @click="hdr.printPage()"><span class="tt-tile sm" style="background: #e6edff"><v-icon size="16" color="#2f5be7">mdi-printer-outline</v-icon></span><span class="flex-grow-1 text-left">Print this page</span></button>
                            <button type="button" class="tt-menu-item" @click="hdr.copyLink()"><span class="tt-tile sm" style="background: #efe8ff"><v-icon size="16" color="#7c3aed">mdi-link-variant</v-icon></span><span class="flex-grow-1 text-left">Copy link to this view</span></button>
                            <button type="button" class="tt-menu-item" @click="hdr.resetView()"><span class="tt-tile sm" style="background: #eef2f1"><v-icon size="16" color="#6b7f7b">mdi-backup-restore</v-icon></span><span class="flex-grow-1 text-left">Reset period and store</span></button>
                        </div>
                    </template>
                </div>

                <div class="tt-set-foot">
                    <button type="button" class="tt-set-reset" @click="hdr.resetSettings()"><v-icon size="14">mdi-backup-restore</v-icon>Reset all</button>
                    <v-spacer />
                    <button type="button" class="tt-set-all" @click="openAccount">Account settings <v-icon size="14">mdi-arrow-right</v-icon></button>
                </div>
            </aside>
        </div>
    </Teleport>

    <!-- Edge handle: standard left-chevron tab on the right side of the page -->
    <Teleport to="body">
        <button v-if="s.showHandle" type="button" class="tt-handle" :class="{ 'is-dark': hdr.isDark, 'is-hidden': hdr.drawerOpen }" aria-label="Open settings sidebar" title="Settings (Ctrl ,)" :tabindex="hdr.drawerOpen ? -1 : 0" @click="hdr.openDrawer()">
            <v-icon size="18" class="tt-handle-gear">mdi-cog-outline</v-icon>
        </button>
    </Teleport>
</template>

<script>
/*
 * HeaderSettings.vue: everything about preferences.
 * The gear popover (display basics), the right-side settings drawer (4 tabs) and the edge handle.
 * It owns no setting values: it only renders switches and buttons that read and write the single
 * settings object in Header.vue, so each setting exists exactly once.
 * The module on/off switches live only in the drawer's Header tab.
 */
export default {
    name: "HeaderSettings",
    inject: ["hdr"],

    data() {
        return {
            returnFocusEl: null,
            drawerTabs: [
                { value: "header", label: "Header", icon: "mdi-view-dashboard-edit-outline" },
                { value: "display", label: "Display", icon: "mdi-palette-outline" },
                { value: "time", label: "Time", icon: "mdi-clock-edit-outline" },
                { value: "data", label: "Data", icon: "mdi-database-cog-outline" },
            ],
            weekStartOptions: [
                { value: 6, label: "Sat" },
                { value: 0, label: "Sun" },
                { value: 1, label: "Mon" },
            ],

            // ── Which header modules exist. Add a key here (and in makeDefaults in Header.vue) to add a new toggle. ──
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
        };
    },

    computed: {
        s() {
            return this.hdr.s;
        },
    },

    watch: {
        // Lock page scroll while the drawer is open, move focus in, and give it back on close
        "hdr.drawerOpen"(open) {
            document.body.classList.toggle("tt-drawer-lock", open);
            if (open) {
                this.returnFocusEl = document.activeElement;
                this.$nextTick(() => {
                    const btn = this.$refs.closeBtn;
                    if (btn && btn.$el && btn.$el.focus) btn.$el.focus({ preventScroll: true });
                });
            } else if (this.returnFocusEl && this.returnFocusEl.focus) {
                this.returnFocusEl.focus({ preventScroll: true });
                this.returnFocusEl = null;
            }
        },
    },

    beforeUnmount() {
        document.body.classList.remove("tt-drawer-lock");
    },

    methods: {
        nudge(key, step) {
            this.s.adjust[key] = Math.max(-30, Math.min(30, (this.s.adjust[key] || 0) + step));
        },
        openAccount() {
            this.hdr.closeDrawer();
            this.hdr.go(this.hdr.accountRoute);
        },
    },
};
</script>
