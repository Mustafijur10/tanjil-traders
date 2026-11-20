<template>
    <div class="tt-dash">
        <!-- ═════════ Header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-center ga-2 mb-3">
                <span class="tt-pill tt-pill-ai"><v-icon size="13">mdi-creation</v-icon>AI insights refreshed {{ aiRefreshedAgo }}</span>
                <span class="tt-pill tt-pill-live"><span class="tt-pulse"></span>Live data</span>
            </div>

            <div class="d-flex flex-wrap align-end ga-4">
                <div>
                    <h1 class="tt-h1">Dashboard</h1>
                    <div class="tt-hero-sub">Sales, orders, customers and inventory at a glance</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <div class="tt-seg" role="tablist" aria-label="Date range">
                        <button v-for="r in ranges" :key="r.value" type="button" role="tab" :aria-selected="range === r.value" :class="{ on: range === r.value }" @click="range = r.value">{{ r.label }}</button>
                    </div>
                    <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-download" @click="exportReport">Export report</v-btn>
                    <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" to="/admin/product">Add product</v-btn>
                </div>
            </div>

            <div class="d-flex flex-wrap align-center justify-space-between mt-4 ga-2">
                <div class="tt-viewers">
                    <span v-for="(v, i) in viewers" :key="v" class="tt-avatar" :style="{ zIndex: viewers.length - i, background: avatarColors[i % avatarColors.length] }">{{ v }}</span>
                    <span class="tt-avatar tt-avatar-extra">+{{ extraViewers }}</span>
                    <span class="tt-viewers-label">viewing this dashboard</span>
                </div>
                <span class="tt-date-pill"><v-icon size="14">mdi-calendar-clock</v-icon>As on {{ today }}</span>
            </div>

            <div class="tt-hero-strip">
                <div v-for="h in heroStats" :key="h.label" class="tt-hero-item" :style="{ '--hue': h.color }">
                    <div class="tt-hero-icon"><v-icon :icon="h.icon" size="20" color="white" /></div>
                    <div class="flex-grow-1">
                        <div class="tt-hero-value">{{ h.value }}</div>
                        <div class="tt-hero-label">{{ h.label }}</div>
                        <div class="tt-hero-note">{{ h.note }}</div>
                        <div v-if="h.progress" class="tt-hero-progress"><span :style="{ width: h.progress + '%' }"></span></div>
                    </div>
                    <span class="tt-live">{{ h.tag }}</span>
                </div>
            </div>
        </header>

        <!-- Chapter rail: sticky quick navigation with scroll tracking -->
        <nav class="tt-rail" aria-label="Dashboard sections">
            <div class="tt-rail-in">
                <button v-for="n in navItems" :key="n.id" type="button" class="tt-rail-btn" :class="{ on: activeChapter === n.id }" @click="goTo(n.id)">{{ n.label }}</button>
            </div>
        </nav>

        <!-- ═════════ Key metrics ═════════ -->
        <div class="tt-chapter" id="ch-overview"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.overview.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.overview.title }}</div><div class="tt-ch-insight">{{ chapters.overview.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.overview.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col v-for="k in kpis" :key="k.label" cols="12" sm="6" lg="4">
                <v-card flat class="tt-card tt-kpi" :style="{ '--accent': k.color, '--tint': k.tint }">
                    <div class="d-flex align-center">
                        <div class="tt-kpi-icon"><v-icon :icon="k.icon" size="20" /></div>
                        <div class="tt-kpi-label ml-3">{{ k.label }}</div>
                        <v-spacer />
                        <span class="tt-delta" :class="(k.change >= 0) !== !!k.inverse ? 'up' : 'down'">
                            <v-icon size="13">{{ k.change >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(k.change) }}%
                        </span>
                    </div>
                    <div class="tt-kpi-value">{{ k.value }}</div>
                    <div class="tt-kpi-vs">vs previous period</div>
                    <apexchart class="tt-kpi-spark" type="area" width="100%" height="64" :options="sparkOptions(k.color)" :series="[{ data: k.spark }]" />
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Health ═════════ -->
        <div class="tt-chapter" id="ch-health"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.health.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.health.title }}</div><div class="tt-ch-insight">{{ chapters.health.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.health.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" lg="12">
                <v-card flat class="tt-card tt-a-teal h-100">
                    <div class="tt-head">
                        <div class="tt-head-ic"><v-icon size="16">mdi-shield-check-outline</v-icon></div>
                        <div><div class="tt-title">Customer and inventory health</div><div class="tt-subt">Summary generated from live store data</div></div>
                    </div>
                    <div class="tt-body">
                        <p class="tt-health-text">{{ healthSummary.text }}</p>
                        <div class="tt-health-stats">
                            <div v-for="(s, i) in healthSummary.stats" :key="s.label" class="tt-health-stat" :style="{ '--c': PALETTE[i] }">
                                <div class="tt-health-stat-value">{{ s.value }}</div>
                                <div class="tt-health-stat-label">{{ s.label }}</div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card flat class="tt-card tt-a-amber tt-op-card h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-trending-up</v-icon></div><div class="tt-title">Product profit</div></div>
                    <div class="tt-body">
                        <div class="tt-op-value">{{ productProfit.margin }}%</div>
                        <div class="tt-op-bar"><div class="tt-op-bar-fill" :style="{ width: productProfit.margin + '%' }"></div></div>
                        <div class="tt-op-row"><span class="tt-muted">Gross margin</span><span>{{ productProfit.margin }}%</span></div>
                        <div class="tt-op-row"><span class="tt-muted">Best category</span><span class="tt-strong">{{ productProfit.bestCategory }}</span></div>
                        <div class="tt-op-row"><span class="tt-muted">Profit per order</span><span class="tt-strong">{{ productProfit.perOrder }}</span></div>
                        <div class="tt-op-row"><span class="tt-muted">Return cost</span><span class="tt-strong">{{ productProfit.returnCost }}</span></div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card flat class="tt-card tt-a-blue tt-op-card h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-truck-fast-outline</v-icon></div><div class="tt-title">Fulfillment speed</div></div>
                    <div class="tt-body">
                        <div class="tt-op-value">{{ fulfillment.days }} days</div>
                        <div class="tt-op-row"><span class="tt-muted">On-time delivery</span><span>{{ fulfillment.onTime }}%</span></div>
                        <div class="tt-op-bar"><div class="tt-op-bar-fill" :style="{ width: fulfillment.onTime + '%' }"></div></div>
                        <div class="tt-op-row mt-2"><span class="tt-muted">Same-day dispatch</span><span>{{ fulfillment.sameDay }}%</span></div>
                        <div class="tt-op-bar"><div class="tt-op-bar-fill soft" :style="{ width: fulfillment.sameDay + '%' }"></div></div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card flat class="tt-card tt-a-violet tt-op-card h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-emoticon-happy-outline</v-icon></div><div class="tt-title">Satisfaction</div></div>
                    <div class="tt-body">
                        <div class="tt-op-value">{{ satisfaction.score }} / 5</div>
                        <div class="tt-stars">
                            <v-icon v-for="n in 5" :key="n" size="16" :class="n <= Math.round(satisfaction.score) ? 'tt-star-on' : 'tt-star-off'">mdi-star</v-icon>
                        </div>
                        <div class="tt-op-row mt-2"><span class="tt-muted">NPS score</span><span class="tt-strong">{{ satisfaction.nps }}</span></div>
                        <div class="tt-op-row"><span class="tt-muted">Support reply</span><span class="tt-strong">{{ satisfaction.responseMins }} min</span></div>
                        <div class="tt-op-row"><span class="tt-muted">5-star reviews</span><span class="tt-strong">{{ satisfaction.fiveStar }}%</span></div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Revenue ═════════ -->
        <div class="tt-chapter" id="ch-revenue"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.revenue.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.revenue.title }}</div><div class="tt-ch-insight">{{ chapters.revenue.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.revenue.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-a-blue h-100">
                    <div class="tt-head">
                        <div class="tt-head-ic"><v-icon size="16">mdi-chart-areaspline</v-icon></div>
                        <div><div class="tt-title">Revenue and orders</div><div class="tt-subt">Total {{ money(totalRevenue) }} in this period</div></div>
                        <v-spacer /><span class="tt-tag">Live</span>
                    </div>
                    <div class="tt-body"><apexchart type="line" height="330" :options="revenueOptions" :series="revenueSeries" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-a-coral h-100">
                    <div class="tt-head">
                        <div class="tt-head-ic"><v-icon size="16">mdi-chart-donut</v-icon></div>
                        <div><div class="tt-title">Sales by category</div><div class="tt-subt">Share of total revenue</div></div>
                    </div>
                    <div class="tt-body"><apexchart type="donut" height="340" :options="categoryOptions" :series="categoryData.map((c) => c.value)" /></div>
                </v-card>
            </v-col>
        </v-row>

        <v-row dense class="mt-1">
            <v-col cols="12">
                <v-card flat class="tt-card tt-a-blue">
                    <div class="tt-head">
                        <div class="tt-head-ic"><v-icon size="16">mdi-shape-outline</v-icon></div>
                        <div><div class="tt-title">Category revenue distribution</div><div class="tt-subt">Revenue share by category</div></div>
                    </div>
                    <div class="tt-body">
                        <div class="tt-cat-grid">
                            <div v-for="(c, i) in categoryDistribution" :key="c.name" class="tt-cat-card" :style="{ '--c': PALETTE[i % PALETTE.length] }">
                                <div class="tt-cat-icon"><v-icon size="19" color="white">{{ c.icon }}</v-icon></div>
                                <div class="flex-grow-1">
                                    <div class="tt-cat-name">{{ c.name }}</div>
                                    <div class="d-flex align-baseline ga-2"><span class="tt-cat-pct">{{ c.pct }}%</span><span class="tt-cat-value">{{ money(c.value) }}</span></div>
                                    <div class="tt-cat-bar"><span :style="{ width: c.pct + '%' }"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Orders ═════════ -->
        <div class="tt-chapter" id="ch-orders"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.orders.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.orders.title }}</div><div class="tt-ch-insight">{{ chapters.orders.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.orders.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-a-blue h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-calendar-week</v-icon></div><div><div class="tt-title">Orders by weekday</div><div class="tt-subt">Busiest day is highlighted</div></div></div>
                    <div class="tt-body"><apexchart type="bar" height="270" :options="weekdayOptions" :series="[{ name: 'Orders', data: weekdayData }]" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-a-teal h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-progress-check</v-icon></div><div><div class="tt-title">Order status</div><div class="tt-subt">Share of all orders</div></div></div>
                    <div class="tt-body"><apexchart type="radialBar" height="270" :options="statusOptions" :series="statusData.map((s) => s.value)" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-a-violet h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-credit-card-outline</v-icon></div><div><div class="tt-title">Payment methods</div><div class="tt-subt">How customers pay</div></div></div>
                    <div class="tt-body"><apexchart type="pie" height="270" :options="paymentOptions" :series="paymentData.map((p) => p.value)" /></div>
                </v-card>
            </v-col>
        </v-row>

        <v-row dense class="mt-1">
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-teal h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-clipboard-list-outline</v-icon></div><div class="tt-title">Order status overview</div></div>
                    <div class="tt-body">
                        <div class="tt-order-stat-grid">
                            <div v-for="o in orderStatusCounts" :key="o.label" class="tt-order-stat" :style="{ '--c': o.color }">
                                <div class="tt-order-ic"><v-icon size="18" color="white">{{ o.icon }}</v-icon></div>
                                <div class="tt-order-stat-value">{{ o.count }}</div>
                                <div class="tt-order-stat-label">{{ o.label }} · {{ o.pct }}%</div>
                            </div>
                        </div>
                        <div class="tt-stack"><span v-for="o in orderStatusCounts" :key="o.label" :style="{ width: o.pct + '%', background: o.color }"></span></div>
                        <div class="tt-subt mt-2">Distribution of {{ totalOrders }} orders in this period</div>
                        <div class="tt-reasons">
                            <div class="tt-reasons-t">Why orders get cancelled</div>
                            <div v-for="r in cancelReasons" :key="r.k" class="tt-reason"><span>{{ r.k }}</span><div class="tt-reason-bar"><i :style="{ width: r.v + '%' }"></i></div><b>{{ r.v }}%</b></div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-violet h-100">
                    <div class="tt-head">
                        <div class="tt-head-ic"><v-icon size="16">mdi-wallet-outline</v-icon></div><div class="tt-title">Payment analytics</div>
                        <v-spacer /><span class="tt-tag">{{ totalTransactions }} transactions</span>
                    </div>
                    <div class="tt-body pa-0">
                        <div v-for="p in paymentDetails" :key="p.name" class="tt-payment-item">
                            <div class="tt-payment-icon" :style="{ background: p.tint }"><v-icon size="17" :color="p.color">{{ p.icon }}</v-icon></div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-center justify-space-between">
                                    <span class="tt-strong">{{ p.name }} leads with {{ p.share }}% share</span>
                                    <span v-if="p.live" class="tt-tag">Live</span>
                                    <span v-else class="tt-subt">{{ p.time }}</span>
                                </div>
                                <div class="tt-subt">{{ p.desc }}</div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Brands + customers ═════════ -->
        <div class="tt-chapter" id="ch-people"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.people.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.people.title }}</div><div class="tt-ch-insight">{{ chapters.people.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.people.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-amber h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-tag-multiple-outline</v-icon></div><div><div class="tt-title">Top brands by revenue</div><div class="tt-subt">Last 12 months</div></div></div>
                    <div class="tt-body"><apexchart type="bar" height="310" :options="brandOptions" :series="[{ name: 'Revenue', data: brandData.map((b) => b.value) }]" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-violet h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-account-plus-outline</v-icon></div><div><div class="tt-title">Customer growth</div><div class="tt-subt">New and returning customers per month</div></div></div>
                    <div class="tt-body"><apexchart type="area" height="310" :options="customerOptions" :series="customerSeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Marketing ═════════ -->
        <div class="tt-chapter" id="ch-marketing"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.marketing.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.marketing.title }}</div><div class="tt-ch-insight">{{ chapters.marketing.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.marketing.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col v-for="(c, i) in campaigns" :key="c.name" cols="12" sm="6" lg="3">
                <v-card flat class="tt-card h-100" :class="campaignAccent[i]">
                    <div class="tt-head">
                        <div class="tt-head-ic"><v-icon size="16">{{ c.icon }}</v-icon></div><div class="tt-title">{{ c.name }}</div>
                        <v-spacer /><span class="tt-tag">{{ c.roi }}% ROI</span>
                    </div>
                    <div class="tt-body">
                        <div class="tt-campaign-grid">
                            <div class="tt-campaign-stat"><div class="tt-muted">Revenue</div><div class="tt-strong">{{ money(c.revenue) }}</div></div>
                            <div class="tt-campaign-stat"><div class="tt-muted">Budget</div><div class="tt-strong">{{ money(c.budget) }}</div></div>
                            <div class="tt-campaign-stat"><div class="tt-muted">Conversion</div><div class="tt-strong">{{ c.conversion }}%</div></div>
                            <div class="tt-campaign-stat"><div class="tt-muted">CAC</div><div class="tt-strong">{{ moneyDec(c.cac) }}</div></div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Heatmap ═════════ -->
        <div class="tt-chapter" id="ch-hours"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.hours.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.hours.title }}</div><div class="tt-ch-insight">{{ chapters.hours.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.hours.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-a-coral">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-fire</v-icon></div><div><div class="tt-title">Order intensity</div><div class="tt-subt">Orders by day and time of day</div></div></div>
                    <div class="tt-body"><apexchart type="heatmap" height="290" :options="heatOptions" :series="heatSeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Targets ═════════ -->
        <div class="tt-chapter" id="ch-targets"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.targets.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.targets.title }}</div><div class="tt-ch-insight">{{ chapters.targets.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.targets.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-a-teal h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-flag-checkered</v-icon></div><div><div class="tt-title">Monthly sales target</div><div class="tt-subt">{{ money(targetSold) }} of {{ money(targetGoal) }}</div></div></div>
                    <div class="tt-body"><apexchart type="radialBar" height="280" :options="gaugeOptions" :series="[Math.round((targetSold / targetGoal) * 100)]" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-a-blue h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-radar</v-icon></div><div><div class="tt-title">Category performance</div><div class="tt-subt">This month against last month</div></div></div>
                    <div class="tt-body"><apexchart type="radar" height="300" :options="radarOptions" :series="radarSeries" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-a-rose h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-web</v-icon></div><div><div class="tt-title">Traffic sources</div><div class="tt-subt">Where visitors come from</div></div></div>
                    <div class="tt-body"><apexchart type="polarArea" height="310" :options="trafficOptions" :series="trafficData.map((t) => t.value)" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Inventory & channels ═════════ -->
        <div class="tt-chapter" id="ch-inventory"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.inventory.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.inventory.title }}</div><div class="tt-ch-insight">{{ chapters.inventory.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.inventory.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-amber h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-package-variant</v-icon></div><div><div class="tt-title">Stock overview</div><div class="tt-subt">Inventory health across all warehouses</div></div></div>
                    <div class="tt-body">
                        <div class="tt-stock-ov-grid mb-3">
                            <div v-for="s in stockCards" :key="s.label" class="tt-stock-ov-item" :style="{ '--c': s.color }">
                                <span class="tt-dot-sm"></span>{{ s.label }}
                                <div class="tt-stock-ov-value">{{ s.value }}%</div>
                            </div>
                        </div>
                        <div v-for="w in warehouses" :key="w.name" class="tt-warehouse-row">
                            <span>{{ w.name }}</span>
                            <div class="tt-wh-bar"><span :style="{ width: w.health + '%', background: w.health >= 70 ? C.teal : C.amber }"></span></div>
                            <span class="tt-strong">{{ w.health }}% healthy</span>
                        </div>
                        <div class="tt-reasons">
                            <div class="tt-reasons-t">Next to run out</div>
                            <div v-for="p in runOut" :key="p.name" class="tt-reason"><span>{{ p.name }}</span><div class="tt-reason-bar warn"><i :style="{ width: p.cover + '%' }"></i></div><b>{{ p.days === 0 ? "Out" : p.days + " days" }}</b></div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-blue h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-compare</v-icon></div><div class="tt-title">Channel performance comparison</div></div>
                    <div class="tt-body pa-0">
                        <v-table class="tt-table">
                            <thead>
                                <tr>
                                    <th>Channel</th>
                                    <th v-for="m in channelMetrics" :key="m" class="text-right">{{ m }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="c in channelPerformance" :key="c.channel">
                                    <td class="tt-strong">{{ c.channel }}</td>
                                    <td v-for="m in channelMetrics" :key="m" class="text-right">
                                        <span class="tt-score" :class="scoreClass(c[m.toLowerCase()])">{{ c[m.toLowerCase()] }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                        <div class="tt-best-strip">
                            <div v-for="b in bestOf" :key="b.label" class="tt-best-item">
                                <div class="tt-muted">{{ b.label }}</div>
                                <div class="tt-strong">{{ b.winner }}</div>
                            </div>
                        </div>
                        <div class="tt-spend">
                            <div class="tt-reasons-t">Ad spend share</div>
                            <div class="tt-stack"><span v-for="c in spendShare" :key="c.name" :style="{ width: c.pct + '%', background: c.color }"></span></div>
                            <div class="tt-spend-legend"><span v-for="c in spendShare" :key="c.name"><i :style="{ background: c.color }"></i>{{ c.name }} <b>{{ c.pct }}%</b></span></div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-row dense class="mt-1">
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-a-teal h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-store-outline</v-icon></div><div><div class="tt-title">Monthly sales by channel</div><div class="tt-subt">Where orders come from, in taka</div></div></div>
                    <div class="tt-body"><apexchart type="bar" height="320" :options="channelOptions" :series="channelData" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-a-violet h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-percent-outline</v-icon></div><div><div class="tt-title">Profit margin change</div><div class="tt-subt">Compared with last month</div></div></div>
                    <div class="tt-body"><apexchart type="bar" height="320" :options="marginOptions" :series="marginSeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <v-row dense class="mt-1">
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-coral h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-backup-restore</v-icon></div><div><div class="tt-title">Returns and return rate</div><div class="tt-subt">Monthly count with the rate as a line</div></div></div>
                    <div class="tt-body"><apexchart type="line" height="300" :options="returnsOptions" :series="returnsSeries" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-amber h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-warehouse</v-icon></div><div><div class="tt-title">Warehouse stock level</div><div class="tt-subt">Units in stock against the reorder point</div></div></div>
                    <div class="tt-body"><apexchart type="line" height="300" :options="stockOptions" :series="stockSeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Ratings & revenue ═════════ -->
        <div class="tt-chapter" id="ch-ratings"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.ratings.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.ratings.title }}</div><div class="tt-ch-insight">{{ chapters.ratings.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.ratings.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-blue h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-chart-scatter-plot</v-icon></div><div><div class="tt-title">Rating vs. review volume</div><div class="tt-subt">Each dot is a product, grouped by category</div></div></div>
                    <div class="tt-body"><apexchart type="scatter" height="330" :options="scatterOptions" :series="scatterSeries" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-teal h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-view-dashboard-variant-outline</v-icon></div><div><div class="tt-title">Revenue by product</div><div class="tt-subt">Bigger block means more revenue</div></div></div>
                    <div class="tt-body"><apexchart type="treemap" height="330" :options="treemapOptions" :series="treemapSeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Pricing & delivery ═════════ -->
        <div class="tt-chapter" id="ch-pricing"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.pricing.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.pricing.title }}</div><div class="tt-ch-insight">{{ chapters.pricing.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.pricing.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-violet h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-chart-bubble</v-icon></div><div><div class="tt-title">Price vs. units sold</div><div class="tt-subt">Bubble size shows revenue</div></div></div>
                    <div class="tt-body"><apexchart type="bubble" height="340" :options="bubbleOptions" :series="bubbleSeries" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-teal h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-map-marker-distance</v-icon></div><div><div class="tt-title">Delivery time by region</div><div class="tt-subt">Fastest to slowest, in days</div></div></div>
                    <div class="tt-body"><apexchart type="rangeBar" height="340" :options="deliveryOptions" :series="deliverySeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Outcome & weekly value ═════════ -->
        <div class="tt-chapter" id="ch-outcome"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.outcome.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.outcome.title }}</div><div class="tt-ch-insight">{{ chapters.outcome.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.outcome.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-blue h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-city-variant-outline</v-icon></div><div><div class="tt-title">Order outcome by city</div><div class="tt-subt">Share of delivered, processing and cancelled</div></div></div>
                    <div class="tt-body"><apexchart type="bar" height="310" :options="cityOptions" :series="citySeries" /></div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-a-coral h-100">
                    <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-chart-waterfall</v-icon></div><div><div class="tt-title">Weekly order value range</div><div class="tt-subt">Open, high, low and close of average order value</div></div></div>
                    <div class="tt-body"><apexchart type="candlestick" height="310" :options="candleOptions" :series="candleSeries" /></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Data tables (config driven, paginated) ═════════ -->
        <template v-for="(sec, si) in tableSections" :key="sec.title">
            <div class="tt-chapter" :id="'ch-tables-' + si"><div class="tt-ch-ic"><v-icon size="22">{{ sec.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ sec.title }}</div><div class="tt-ch-insight">{{ sec.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in sec.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
            <v-row dense>
                <v-col v-for="t in sec.items" :key="t.id" cols="12" :lg="t.span">
                    <v-card flat class="tt-card h-100 d-flex flex-column" :class="'tt-a-' + t.accent">
                        <div class="tt-head tt-head-wrap">
                            <div class="tt-head-ic"><v-icon size="16">{{ t.icon }}</v-icon></div>
                            <div><div class="tt-title">{{ t.title }}</div><div class="tt-subt">{{ t.rows.length }} records</div></div>
                            <v-spacer />
                            <v-text-field
                                v-if="t.span === 12"
                                v-model="queries[t.id]"
                                class="tt-search" density="compact" variant="outlined" hide-details clearable
                                prepend-inner-icon="mdi-magnify" placeholder="Search"
                                @update:model-value="pages[t.id] = 1"
                            />
                            <v-btn v-if="t.link" variant="text" size="small" class="text-none tt-link" :to="t.link.to">{{ t.link.label }}</v-btn>
                        </div>

                        <v-table class="tt-table tt-grow">
                            <thead>
                                <tr><th v-for="c in t.cols" :key="c.key" :class="{ 'text-right': c.align === 'right' }">{{ c.label }}</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, ri) in pageRows(t)" :key="ri">
                                    <td v-for="c in t.cols" :key="c.key" :class="{ 'text-right': c.align === 'right', 'tt-muted': c.type === 'muted', 'tt-strong': c.type === 'strong' }">
                                        <span v-if="c.type === 'chip'" class="tt-chip" :class="'tone-' + (tones[row[c.key]] || 'gray')">{{ row[c.key] }}</span>
                                        <template v-else-if="c.type === 'product'">
                                            <div class="tt-strong">{{ row.name }}</div>
                                            <div class="tt-rating"><v-icon size="12" color="#f5a524">mdi-star</v-icon>{{ row.rating }} · {{ row.reviews }} reviews</div>
                                        </template>
                                        <span v-else-if="c.type === 'growth'" :class="row[c.key] >= 0 ? 'tt-up' : 'tt-down'">
                                            <v-icon size="12">{{ row[c.key] >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(row[c.key]) }}%
                                        </span>
                                        <span v-else-if="c.type === 'stock'" class="tt-chip" :class="row[c.key] === 0 ? 'tone-red' : 'tone-amber'">{{ row[c.key] === 0 ? "Out" : row[c.key] + " left" }}</span>
                                        <span v-else-if="c.type === 'stars'" class="tt-starline">{{ stars(row[c.key]) }}</span>
                                        <div v-else-if="c.type === 'bar'" class="tt-minibar"><span class="tt-minibar-track"><span :style="{ width: row[c.key] + '%' }"></span></span><b>{{ row[c.key] }}%</b></div>
                                        <template v-else>{{ cell(c, row[c.key]) }}</template>
                                    </td>
                                </tr>
                                <tr v-if="!pageRows(t).length"><td :colspan="t.cols.length" class="text-center tt-muted py-4">No results match your search</td></tr>
                                <tr v-for="n in blankCount(t)" :key="'blank' + n" class="tt-blank"><td :colspan="t.cols.length">&nbsp;</td></tr>
                            </tbody>
                        </v-table>

                        <div v-if="t.stats" class="tt-stats">
                            <div v-for="x in tableStats(t)" :key="x.k" class="tt-stat"><small>{{ x.k }}</small><b>{{ x.v }}</b></div>
                        </div>

                        <div class="tt-foot">
                            <span class="tt-subt">{{ showing(t) }}</span>
                            <v-spacer />
                            <v-pagination v-model="pages[t.id]" :length="pageCount(t)" :total-visible="t.span === 12 ? 7 : 3" density="comfortable" size="small" rounded="circle" active-color="#0a5548" />
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </template>

        <!-- ═════════ AI recommendations ═════════ -->
        <div class="tt-chapter" id="ch-ai"><div class="tt-ch-ic"><v-icon size="22">{{ chapters.ai.icon }}</v-icon></div><div class="tt-ch-main"><div class="tt-ch-title">{{ chapters.ai.title }}</div><div class="tt-ch-insight">{{ chapters.ai.insight }}</div></div><div class="tt-ch-chips"><span v-for="c in chapters.ai.chips" :key="c.k" class="tt-ch-chip"><small>{{ c.k }}</small><b>{{ c.v }}</b><em v-if="c.sub" :class="c.tone">{{ c.sub }}</em></span></div></div>
        <v-row dense>
            <v-col v-for="r in aiRecommendations" :key="r.title" cols="12" md="4">
                <v-card flat class="tt-card h-100" :style="{ '--accent': r.color, '--tint': r.tint }">
                    <div class="tt-body">
                        <span class="tt-ai-tag"><v-icon size="13">{{ r.icon }}</v-icon>{{ r.tag }}</span>
                        <p class="tt-ai-desc">{{ r.text }}</p>
                        <v-btn variant="outlined" size="small" class="text-none tt-ai-btn" :color="r.color" block>{{ r.action }}</v-btn>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";

/* ───────────────── Design tokens ─────────────────
   Identity: deep navy + warm cream, with a disciplined set of colourful accents. */
const C = {
    navy9: "#053d35", navy8: "#0a5548", navy7: "#0b6150",
    blue: "#2f5be7", sky: "#6ee7b7", teal: "#0f9d6b", mint: "#a7f3d0", green: "#0f766e",
    amber: "#f59e0b", coral: "#ef6a45", rose: "#ec4899", violet: "#7c3aed",
    ink: "#0f2a26", muted: "#6b7f7b",
};
const PALETTE = [C.teal, C.blue, C.violet, C.green, C.amber, C.rose]; // categorical, on light cards // categorical, on cream
const FONT = "Poppins, Segoe UI, sans-serif";

/* shared chart building blocks so every chart looks like it belongs to one family */
const chartBase = (dark = false, extra = {}) => ({
    toolbar: { show: false },
    fontFamily: FONT,
    foreColor: dark ? "#cfe8e0" : "#5d667c",
    parentHeightOffset: 0,
    animations: { enabled: true, speed: 600 },
    ...extra,
});
const gridOf = (dark = false) => ({ borderColor: dark ? "rgba(255,255,255,0.1)" : "#e8eeec", strokeDashArray: 4, padding: { left: 6, right: 6 } });
const legendOf = (dark = false, position = "top") => ({
    position,
    horizontalAlign: position === "top" ? "left" : "center",
    fontSize: "12px",
    fontWeight: 500,
    markers: { width: 8, height: 8, radius: 8 },
    itemMargin: { horizontal: 10, vertical: 4 },
    labels: { colors: dark ? "#e1f1ec" : "#3a4660" },
});
const axisClean = { axisBorder: { show: false }, axisTicks: { show: false } };

// short axis labels: 250000 -> 2.5L, 45000 -> 45k
const shortMoney = (v) => (v >= 100000 ? (v / 100000).toFixed(1).replace(".0", "") + "L" : v >= 1000 ? Math.round(v / 1000) + "k" : v);
const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");
const decMoney = (v) => "৳ " + Number(v).toFixed(2);

// small deterministic pseudo-random so sample data looks natural but stays stable
const seeded = (seed) => {
    let s = seed;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
};
const makeSeries = (n, base, spread, seed) => {
    const r = seeded(seed);
    return Array.from({ length: n }, (_, i) => Math.round(base * (1 + i * 0.02) + (r() - 0.5) * spread));
};

const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
const rnd = seeded(2026);
const pick = (a) => a[Math.floor(rnd() * a.length)];
const fmtDate = (back) => new Date(Date.now() - back * 864e5).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });

/* ───────────────── Sample table data (replace with API calls) ───────────────── */
// name, brand, category, price, stock, units sold (30d), rating, reviews
const PRODUCTS = [
    ["Anker PowerCore 20000", "Anker", "Power banks", 4200, 3, 84, 4.8, 1200],
    ["Baseus Bowie E9 Earbuds", "Baseus", "Earbuds", 1490, 5, 210, 4.6, 890],
    ["Xiaomi Smart Band 8", "Xiaomi", "Smart watches", 3150, 2, 75, 4.3, 410],
    ["TP-Link Archer C6 Router", "TP-Link", "Networking", 3200, 0, 48, 4.4, 152],
    ["JBL Go 4 Speaker", "JBL", "Speakers", 4990, 4, 44, 4.7, 663],
    ["Ugreen 65W GaN Charger", "Ugreen", "Chargers", 2850, 38, 70, 4.5, 298],
    ["Realme Buds T300", "Realme", "Earbuds", 2790, 26, 60, 4.2, 330],
    ["Logitech G102 Mouse", "Logitech", "Gaming", 1650, 45, 140, 4.6, 540],
    ["Hoco Type-C Cable 1m", "Hoco", "Cables", 420, 210, 520, 4.1, 760],
    ["SanDisk 128GB Pendrive", "SanDisk", "Storage", 1350, 9, 90, 4.5, 410],
    ["Havit H2002d Headphone", "Havit", "Headphones", 2450, 7, 38, 4.0, 190],
    ["Amazfit Bip 5", "Amazfit", "Smart watches", 6900, 14, 30, 4.4, 220],
    ["Anker 313 Power Bank 10000", "Anker", "Power banks", 2800, 52, 120, 4.7, 980],
    ["Baseus 100W Charging Cable", "Baseus", "Cables", 690, 160, 260, 4.5, 610],
    ["Redragon K552 Keyboard", "Redragon", "Gaming", 3990, 12, 55, 4.6, 310],
    ["Xiaomi Redmi Buds 5", "Xiaomi", "Earbuds", 2190, 31, 66, 4.3, 275],
    ["D-Link DIR-615 Router", "D-Link", "Networking", 1850, 8, 41, 4.0, 140],
    ["Samsung 25W Adapter", "Samsung", "Chargers", 1450, 6, 58, 4.4, 360],
];
const CUSTOMERS = ["Rahim Traders", "Sabbir Hossain", "Dhaka Mobile Hub", "Nusrat Jahan", "Gadget Point", "Tanvir Ahmed", "Mim Electronics", "Farhana Akter", "Techland BD", "Arif Hasan", "Shanta Islam", "Star Gadgets", "Imran Khan", "Sumaiya Rahman", "Nazmul Haque", "Ridoy Store"];
const CITIES = ["Dhaka", "Dhaka", "Chattogram", "Sylhet", "Khulna", "Rajshahi", "Barishal", "Rangpur", "Cumilla"];

const INVENTORY = PRODUCTS.map(([name, brand, category, price, stock, sold], i) => ({
    sku: brand.slice(0, 2).toUpperCase() + "-" + (1001 + i), name, brand, category, price, stock, sold,
    status: stock === 0 ? "Out of stock" : stock <= 10 ? "Low stock" : "In stock",
}));
const TOP = [...PRODUCTS]
    .sort((a, b) => b[3] * b[5] - a[3] * a[5])
    .slice(0, 14)
    .map(([name, , , price, stock, sold, rating, reviews]) => ({
        name, rating, reviews, units: sold, revenue: price * sold, profit: Math.round(price * sold * 0.3),
        growth: Math.round((rnd() * 36 - 6) * 10) / 10, stock: stock === 0 ? "Out of stock" : stock <= 10 ? "Low stock" : "In stock",
    }));
const LOW = INVENTORY.filter((p) => p.stock <= 10).sort((a, b) => a.stock - b.stock);
const ORDERS = Array.from({ length: 30 }, (_, i) => ({
    id: "#TT-" + (10482 - i), customer: pick(CUSTOMERS), city: pick(CITIES), payment: pick(["bKash", "Cash on delivery", "Nagad", "Card", "bKash"]),
    date: fmtDate(Math.floor(i / 2)), status: pick(["Delivered", "Delivered", "Delivered", "Processing", "Pending", "Delivered", "Cancelled"]),
    total: pick(PRODUCTS)[3] * (1 + Math.floor(rnd() * 3)),
}));
const TOP_CUSTOMERS = CUSTOMERS.slice(0, 14).map((name) => {
    const orders = Math.round(3 + rnd() * 40);
    const spent = orders * Math.round(2000 + rnd() * 4000);
    return { name, orders, spent, last: fmtDate(Math.floor(rnd() * 20)), tier: spent >= 150000 ? "VIP" : spent >= 60000 ? "Regular" : "New" };
}).sort((a, b) => b.spent - a.spent);
const REVIEWS = Array.from({ length: 16 }, (_, i) => ({ product: pick(PRODUCTS)[0], customer: pick(CUSTOMERS), rating: Math.max(1, Math.min(5, Math.round(3.2 + rnd() * 2.2))), date: fmtDate(i) }));
const RETURNS = Array.from({ length: 14 }, (_, i) => ({
    id: "#RT-" + (2041 - i), product: pick(PRODUCTS)[0], reason: pick(["Defective unit", "Wrong item", "Not as described", "Battery issue", "Warranty repair"]), status: pick(["Open", "In review", "Resolved", "Refunded", "Resolved"]),
}));
const COUPONS = [
    ["NEWUSER10", "10%", 286, 684000, "Active"], ["EID26", "15%", 412, 1240000, "Expired"], ["POWER300", "৳ 300", 148, 392000, "Active"], ["AUDIO20", "20%", 96, 251000, "Active"],
    ["FREESHIP", "Free delivery", 530, 1580000, "Active"], ["WATCH500", "৳ 500", 64, 198000, "Scheduled"], ["GAMER12", "12%", 73, 264000, "Active"],
    ["BKASH5", "5% cashback", 620, 1910000, "Active"], ["WINTER26", "8%", 0, 0, "Scheduled"], ["FLASH24", "25%", 188, 540000, "Expired"],
].map(([code, discount, used, revenue, status]) => ({ code, discount, used, revenue, status }));
const COURIERS = [["Pathao Courier", 1240, 94, 1.9], ["Steadfast", 1120, 93, 2.2], ["RedX", 860, 92, 2.4], ["Sundarban Courier", 540, 88, 3.0], ["Paperfly", 420, 89, 2.6], ["eCourier", 310, 90, 2.1], ["SA Paribahan", 190, 85, 3.4]]
    .map(([name, deliveries, ontime, returns]) => ({ name, deliveries, ontime, returns }));
const SEARCHES = [["power bank", 2140, 48, 7.2], ["earbuds", 1890, 64, 6.1], ["smart watch", 1640, 37, 4.8], ["type c cable", 1310, 52, 8.9], ["gaming mouse", 980, 29, 5.5], ["wifi router", 870, 21, 4.2], ["airpods pro", 760, 0, 0], ["charger 65w", 690, 18, 7.8], ["bluetooth speaker", 640, 26, 5.0], ["ssd 512gb", 520, 15, 3.6]]
    .map(([term, searches, results, conv]) => ({ term, searches, results, conv }));
const PURCHASES = Array.from({ length: 16 }, (_, i) => {
    const units = 20 + Math.floor(rnd() * 380);
    return { id: "PO-" + (3100 - i), supplier: pick(["Anker Bangladesh", "Baseus Official BD", "Xiaomi BD Distributor", "Ryans Wholesale", "Global Brand", "Smart Technologies"]), units, expected: fmtDate(-(1 + i)), amount: units * Math.round(800 + rnd() * 2500), status: pick(["Ordered", "Shipped", "Received", "Received", "Delayed"]) };
});

// status → colour tone for the pill component
const TONES = {
    Pending: "amber", Processing: "blue", Delivered: "green", Cancelled: "red",
    "In stock": "green", "Low stock": "amber", "Out of stock": "red",
    Open: "amber", "In review": "blue", Resolved: "green", Refunded: "violet",
    Active: "green", Expired: "gray", Scheduled: "blue",
    Ordered: "blue", Shipped: "violet", Received: "green", Delayed: "red",
    VIP: "violet", Regular: "blue", New: "teal",
};

// column: key, label, type (strong | muted | money | num | pct | chip | stock | stars | bar | product | growth), align
const col = (key, label, type = "", align = "") => ({ key, label, type, align });
const TABLES = {
    orders: { title: "Recent orders", icon: "mdi-receipt-text-outline", accent: "blue", span: 12, perPage: 6, link: { to: "/admin/orders", label: "View all orders" }, rows: ORDERS,
        cols: [col("id", "Order", "strong"), col("customer", "Customer"), col("city", "City", "muted"), col("payment", "Payment", "muted"), col("date", "Date", "muted"), col("status", "Status", "chip"), col("total", "Total", "money", "right")] },
    inventory: { title: "Product inventory", icon: "mdi-package-variant-closed", accent: "teal", span: 12, perPage: 6, link: { to: "/admin/products", label: "Manage products" }, rows: INVENTORY,
        cols: [col("sku", "SKU", "muted"), col("name", "Product", "strong"), col("brand", "Brand"), col("category", "Category", "muted"), col("price", "Price", "money", "right"), col("stock", "Stock", "num", "right"), col("sold", "Sold (30d)", "num", "right"), col("status", "Status", "chip")] },
    top: { title: "Top performing products", icon: "mdi-trophy-outline", accent: "violet", span: 7, link: { to: "/admin/catalog", label: "View catalog" }, rows: TOP,
        cols: [col("name", "Product", "product"), col("units", "Sales", "num", "right"), col("revenue", "Revenue", "money", "right"), col("profit", "Profit", "money", "right"), col("growth", "Growth", "growth", "right"), col("stock", "Stock", "chip")] },
    restock: { title: "Needs restocking", icon: "mdi-alert-outline", accent: "amber", span: 5, rows: LOW, cols: [col("name", "Product", "strong"), col("brand", "Brand", "muted"), col("stock", "Stock", "stock", "right")] },
    customers: { title: "Top customers", icon: "mdi-account-star-outline", accent: "coral", span: 7, link: { to: "/admin/customers", label: "View all customers" }, rows: TOP_CUSTOMERS,
        cols: [col("name", "Customer", "strong"), col("orders", "Orders", "num", "right"), col("spent", "Total spent", "money", "right"), col("last", "Last order", "muted"), col("tier", "Tier", "chip")] },
    reviews: { title: "Latest product reviews", icon: "mdi-comment-star-outline", accent: "amber", span: 5, rows: REVIEWS, cols: [col("product", "Product", "strong"), col("customer", "Customer", "muted"), col("rating", "Rating", "stars"), col("date", "Date", "muted")] },
    returns: { title: "Warranty and return requests", icon: "mdi-backup-restore", accent: "rose", rows: RETURNS, cols: [col("id", "Ticket", "strong"), col("product", "Product"), col("reason", "Reason", "muted"), col("status", "Status", "chip")] },
    coupons: { title: "Coupons and campaigns", icon: "mdi-ticket-percent-outline", accent: "teal", rows: COUPONS, cols: [col("code", "Code", "strong"), col("discount", "Discount", "muted"), col("used", "Used", "num", "right"), col("revenue", "Revenue", "money", "right"), col("status", "Status", "chip")] },
    couriers: { title: "Courier performance", icon: "mdi-truck-fast-outline", accent: "blue", rows: COURIERS, cols: [col("name", "Courier", "strong"), col("deliveries", "Deliveries", "num", "right"), col("ontime", "On-time", "bar"), col("returns", "Returns", "pct", "right")] },
    search: { title: "Top search terms", icon: "mdi-text-search", accent: "violet", rows: SEARCHES, cols: [col("term", "Search term", "strong"), col("searches", "Searches", "num", "right"), col("results", "Results", "num", "right"), col("conv", "Conversion", "pct", "right")] },
    purchase: { title: "Purchase orders", icon: "mdi-cart-arrow-down", accent: "coral", span: 12, perPage: 6, rows: PURCHASES,
        cols: [col("id", "PO", "strong"), col("supplier", "Supplier"), col("units", "Units", "num", "right"), col("expected", "Expected", "muted"), col("amount", "Amount", "money", "right"), col("status", "Status", "chip")] },
};
for (const [k, t] of Object.entries(TABLES)) { t.id = k; t.span = t.span || 6; t.perPage = t.perPage || 5; }

const sum = (rows, f) => rows.reduce((a, r) => a + f(r), 0);
const avg = (rows, f) => (rows.length ? sum(rows, f) / rows.length : 0);
TABLES.orders.stats = (r) => [{ k: "Orders", v: r.length }, { k: "Delivered", v: r.filter((x) => x.status === "Delivered").length }, { k: "Order value", v: fullMoney(sum(r.filter((x) => x.status !== "Cancelled"), (x) => x.total)) }];
TABLES.inventory.stats = (r) => [{ k: "Products", v: r.length }, { k: "Units in stock", v: sum(r, (x) => x.stock).toLocaleString("en-IN") }, { k: "Stock value", v: fullMoney(sum(r, (x) => x.price * x.stock)) }];
TABLES.top.stats = (r) => [{ k: "Revenue", v: fullMoney(sum(r, (x) => x.revenue)) }, { k: "Profit", v: fullMoney(sum(r, (x) => x.profit)) }, { k: "Avg growth", v: avg(r, (x) => x.growth).toFixed(1) + "%" }];
TABLES.restock.stats = (r) => [{ k: "Out of stock", v: r.filter((x) => x.stock === 0).length }, { k: "Running low", v: r.filter((x) => x.stock > 0).length }, { k: "Units to reorder", v: sum(r, (x) => Math.max(0, 30 - x.stock)) }];
TABLES.customers.stats = (r) => [{ k: "Total spent", v: fullMoney(sum(r, (x) => x.spent)) }, { k: "Avg orders", v: avg(r, (x) => x.orders).toFixed(0) }, { k: "VIP customers", v: r.filter((x) => x.tier === "VIP").length }];
TABLES.reviews.stats = (r) => [{ k: "Average rating", v: avg(r, (x) => x.rating).toFixed(1) + " ★" }, { k: "5-star reviews", v: r.filter((x) => x.rating === 5).length }, { k: "Below 3 stars", v: r.filter((x) => x.rating <= 2).length }];
TABLES.returns.stats = (r) => [{ k: "Open tickets", v: r.filter((x) => x.status === "Open" || x.status === "In review").length }, { k: "Resolved", v: r.filter((x) => x.status === "Resolved" || x.status === "Refunded").length }, { k: "Defective units", v: r.filter((x) => x.reason === "Defective unit").length }];
TABLES.coupons.stats = (r) => [{ k: "Active codes", v: r.filter((x) => x.status === "Active").length }, { k: "Redemptions", v: sum(r, (x) => x.used).toLocaleString("en-IN") }, { k: "Revenue", v: fullMoney(sum(r, (x) => x.revenue)) }];
TABLES.couriers.stats = (r) => [{ k: "Deliveries", v: sum(r, (x) => x.deliveries).toLocaleString("en-IN") }, { k: "Avg on-time", v: avg(r, (x) => x.ontime).toFixed(1) + "%" }, { k: "Best courier", v: r.length ? [...r].sort((a, b) => b.ontime - a.ontime)[0].name : "-" }];
TABLES.search.stats = (r) => [{ k: "Searches", v: sum(r, (x) => x.searches).toLocaleString("en-IN") }, { k: "Zero-result terms", v: r.filter((x) => x.results === 0).length }, { k: "Avg conversion", v: avg(r, (x) => x.conv).toFixed(1) + "%" }];
TABLES.purchase.stats = (r) => [{ k: "Open orders", v: r.filter((x) => x.status !== "Received").length }, { k: "Total value", v: fullMoney(sum(r, (x) => x.amount)) }, { k: "Delayed", v: r.filter((x) => x.status === "Delayed").length }];

const TABLE_SECTIONS = [
    { title: "Orders and inventory", icon: "mdi-clipboard-list-outline", ids: ["orders", "inventory"],
        insight: `${ORDERS.length} recent orders worth ${fullMoney(sum(ORDERS.filter((o) => o.status !== "Cancelled"), (o) => o.total))}, and ${INVENTORY.length} products with ${LOW.length} running low.` },
    { title: "Best sellers and stock alerts", icon: "mdi-trophy-outline", ids: ["top", "restock"],
        insight: `${TOP[0].name} leads sales. ${LOW.length} products need restocking and ${LOW.filter((p) => p.stock === 0).length} is already out of stock.` },
    { title: "Customers and feedback", icon: "mdi-account-star-outline", ids: ["customers", "reviews"],
        insight: `${TOP_CUSTOMERS[0].name} is the top customer. Recent reviews average ${avg(REVIEWS, (x) => x.rating).toFixed(1)} stars.` },
    { title: "After-sales and marketing", icon: "mdi-lifebuoy", ids: ["returns", "coupons"],
        insight: `${RETURNS.filter((r) => r.status === "Open" || r.status === "In review").length} return tickets are open and ${COUPONS.filter((c) => c.status === "Active").length} coupon codes are live.` },
    { title: "Fulfilment and site search", icon: "mdi-truck-fast-outline", ids: ["couriers", "search"],
        insight: `${[...COURIERS].sort((a, b) => b.ontime - a.ontime)[0].name} has the best on-time rate. "${SEARCHES[0].term}" is the most searched term.` },
    { title: "Purchasing", icon: "mdi-cart-arrow-down", ids: ["purchase"],
        insight: `${PURCHASES.filter((x) => x.status !== "Received").length} purchase orders are still open and ${PURCHASES.filter((x) => x.status === "Delayed").length} are delayed.` },
].map((sec) => {
    const items = sec.ids.map((id) => TABLES[id]);
    const per = items.length === 1 ? 3 : 2;
    const chips = items.flatMap((t) => t.stats(t.rows).slice(0, per).map((x) => ({ k: x.k, v: x.v, sub: t.title })));
    if (chips.length < 4) chips.push({ k: "Records", v: items.reduce((a, t) => a + t.rows.length, 0), sub: "All tables" });
    return { ...sec, items, chips };
});

// stepline: warehouse stock
const STOCK_LEVEL = (() => { const r = seeded(15); let st = 120; return Array.from({ length: 14 }, () => { st -= Math.round(8 + r() * 14); if (st < 25) st += 90; return st; }); })();

let chapterObserver = null;

export default {
    name: "AdminDashboard",
    components: { apexchart: VueApexCharts },

    data() {
        const days30 = Array.from({ length: 30 }, (_, i) => `${i + 1} Oct`);
        const days7 = ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"];

        return {
            range: "12",
            activeChapter: "overview",
            navItems: [
                { id: "overview", label: "Overview" }, { id: "health", label: "Health" }, { id: "revenue", label: "Revenue" }, { id: "orders", label: "Orders" },
                { id: "people", label: "Customers" }, { id: "marketing", label: "Marketing" }, { id: "hours", label: "Busy hours" }, { id: "targets", label: "Targets" },
                { id: "inventory", label: "Inventory" }, { id: "ratings", label: "Products" }, { id: "pricing", label: "Delivery" }, { id: "outcome", label: "Outcomes" },
                { id: "tables-0", label: "Tables" }, { id: "ai", label: "AI advice" },
            ],
            ranges: [{ value: "7", label: "7 days" }, { value: "30", label: "30 days" }, { value: "12", label: "12 months" }],
            C,
            PALETTE,

            // ── Header extras ──
            aiRefreshedAgo: "6m ago",
            viewers: ["A", "R", "S"],
            avatarColors: [C.blue, C.teal, C.coral],
            extraViewers: 6,

            // ── Sample data: replace with API calls (e.g. GET /api/admin/dashboard) ──
            datasets: {
                12: { labels: MONTHS, revenue: makeSeries(12, 520000, 160000, 7), orders: makeSeries(12, 190, 60, 11) },
                30: { labels: days30, revenue: makeSeries(30, 28000, 12000, 21), orders: makeSeries(30, 11, 6, 5) },
                7: { labels: days7, revenue: makeSeries(7, 31000, 14000, 3), orders: makeSeries(7, 12, 6, 9) },
            },

            kpis: [
                { label: "Revenue this month", value: "৳ 9,60,000", change: 6.1, icon: "mdi-cash-multiple", color: C.teal, tint: "#dff5ec", spark: [31, 40, 35, 50, 49, 60, 70, 91, 85] },
                { label: "Orders", value: "318", change: 12.4, icon: "mdi-receipt-text-outline", color: C.blue, tint: "#e6edff", spark: [10, 22, 18, 30, 26, 41, 38, 52, 60] },
                { label: "Products in catalog", value: "2,146", change: 1.8, icon: "mdi-package-variant-closed", color: C.violet, tint: "#ebe5ff", spark: [50, 52, 54, 55, 57, 58, 60, 61, 62] },
                { label: "Customers", value: "1,208", change: -0.9, icon: "mdi-account-group-outline", color: C.rose, tint: "#fbe1ec", spark: [44, 50, 47, 52, 49, 46, 48, 45, 44] },
                { label: "Average order value", value: "৳ 3,020", change: 3.4, icon: "mdi-basket-outline", color: C.amber, tint: "#fdecc8", spark: [28, 30, 29, 32, 31, 33, 34, 33, 36] },
                { label: "Conversion rate", value: "5.2%", change: 0.6, icon: "mdi-percent-outline", color: C.coral, tint: "#fce1d8", spark: [4.1, 4.4, 4.3, 4.7, 4.6, 4.9, 5.0, 5.1, 5.2] },
                { label: "Pending orders", value: "34", change: -8.1, inverse: true, icon: "mdi-clock-outline", color: "#0a8fd1", tint: "#d8eefa", spark: [52, 48, 50, 45, 43, 41, 39, 36, 34] },
                { label: "Return rate", value: "2.1%", change: -0.4, inverse: true, icon: "mdi-backup-restore", color: "#c2410c", tint: "#fde4d3", spark: [3.2, 3.0, 2.8, 2.9, 2.6, 2.4, 2.3, 2.2, 2.1] },
                { label: "Low-stock items", value: "18", change: 5.9, inverse: true, icon: "mdi-alert-outline", color: C.green, tint: "#d9f1e6", spark: [10, 11, 12, 12, 14, 15, 16, 17, 18] },
            ],

            healthSummary: {
                text:
                    "The customer base has grown to 184K shoppers, with a 68.4% repeat purchase rate showing strong loyalty. Visitor conversion sits at 4.82%, with 61.3% of carts completing checkout. Inventory remains healthy at 92% availability, though 14 SKUs are running low and may need restocking soon.",
                stats: [
                    { label: "Customers", value: "184K" },
                    { label: "Conversion", value: "4.82%" },
                    { label: "Stock health", value: "92%" },
                    { label: "Retention", value: "68.4%" },
                ],
            },
            productProfit: { margin: 34.8, bestCategory: "Electronics", perOrder: "৳ 1,052", returnCost: "৳ 18,400" },
            fulfillment: { days: 2.3, onTime: 94.2, sameDay: 68 },
            satisfaction: { score: 4.7, nps: 62, responseMins: 8, fiveStar: 78 },

            categoryData: [
                { name: "Power banks and chargers", value: 2840000 },
                { name: "Earbuds and headphones", value: 1960000 },
                { name: "Smart watches", value: 1410000 },
                { name: "Cables and adapters", value: 980000 },
                { name: "Gaming gear", value: 720000 },
                { name: "Others", value: 420000 },
            ],
            categoryIcons: {
                "Power banks and chargers": "mdi-battery-charging-high",
                "Earbuds and headphones": "mdi-headphones",
                "Smart watches": "mdi-watch-variant",
                "Cables and adapters": "mdi-cable-data",
                "Gaming gear": "mdi-controller-classic-outline",
                Others: "mdi-dots-horizontal-circle-outline",
            },

            weekdayData: [58, 44, 39, 41, 47, 63, 71],

            statusData: [
                { name: "Delivered", value: 68 },
                { name: "Processing", value: 17 },
                { name: "Pending", value: 10 },
                { name: "Cancelled", value: 5 },
            ],
            cancelReasons: [
                { k: "Customer changed mind", v: 38 },
                { k: "Payment failed", v: 27 },
                { k: "Item out of stock", v: 21 },
                { k: "Address issue", v: 14 },
            ],
            orderStatusCounts: [
                { label: "Pending", count: 32, pct: 10, icon: "mdi-clock-outline", color: C.amber },
                { label: "Processing", count: 54, pct: 17, icon: "mdi-progress-clock", color: C.blue },
                { label: "Delivered", count: 216, pct: 68, icon: "mdi-check-circle-outline", color: C.teal },
                { label: "Cancelled", count: 16, pct: 5, icon: "mdi-close-circle-outline", color: C.coral },
            ],

            paymentData: [
                { name: "bKash", value: 44 },
                { name: "Cash on delivery", value: 31 },
                { name: "Nagad", value: 14 },
                { name: "Card", value: 11 },
            ],
            totalTransactions: "48.9K",
            paymentDetails: [
                { name: "bKash", share: 44, icon: "mdi-cellphone", color: C.rose, tint: "#fbe1ec", desc: "Still the most trusted checkout option", live: true },
                { name: "Cash on delivery", share: 31, icon: "mdi-cash", color: C.amber, tint: "#fdecc8", desc: "Average checkout time is higher outside Dhaka", time: "2h ago" },
                { name: "Nagad", share: 14, icon: "mdi-wallet-outline", color: C.coral, tint: "#fce1d8", desc: "Conversions up this week with the fastest checkout time", time: "5h ago" },
                { name: "Card", share: 11, icon: "mdi-credit-card-outline", color: C.violet, tint: "#ebe5ff", desc: "Used for larger orders, mostly B2B accounts", time: "1d ago" },
            ],

            brandData: [
                { name: "Anker", value: 1250000 },
                { name: "Baseus", value: 980000 },
                { name: "Xiaomi", value: 870000 },
                { name: "JBL", value: 640000 },
                { name: "Ugreen", value: 510000 },
                { name: "Realme", value: 430000 },
            ],
            customerSeries: [
                { name: "New customers", data: [42, 51, 63, 58, 72, 80, 94, 101, 96, 118, 130, 141] },
                { name: "Returning customers", data: [30, 34, 41, 47, 55, 61, 70, 78, 86, 95, 108, 119] },
            ],

            campaignAccent: ["tt-a-blue", "tt-a-teal", "tt-a-amber", "tt-a-violet"],
            campaigns: [
                { name: "Google Ads", icon: "mdi-google", roi: 345, revenue: 214600, budget: 48200, conversion: 3.9, cac: 18.4 },
                { name: "Facebook Ads", icon: "mdi-facebook", roi: 290, revenue: 142300, budget: 36500, conversion: 2.8, cac: 22.1 },
                { name: "Email Campaign", icon: "mdi-email-outline", roi: 1335, revenue: 68900, budget: 4800, conversion: 6.1, cac: 3.9 },
                { name: "Affiliate", icon: "mdi-link-variant", roi: 278, revenue: 79400, budget: 21000, conversion: 2.2, cac: 26.7 },
            ],

            targetSold: 960000,
            targetGoal: 1200000,

            radarCategories: ["Power", "Audio", "Watches", "Cables", "Gaming", "Network"],
            radarThis: [80, 72, 58, 62, 47, 36],
            radarLast: [66, 63, 60, 49, 41, 39],

            trafficData: [
                { name: "Facebook", value: 34 },
                { name: "Google search", value: 28 },
                { name: "Direct", value: 16 },
                { name: "Instagram", value: 10 },
                { name: "YouTube", value: 7 },
                { name: "WhatsApp", value: 5 },
            ],

            stockCards: [
                { label: "Healthy stock", value: 70, color: C.teal },
                { label: "Low stock", value: 17, color: C.amber },
                { label: "Out of stock", value: 6, color: C.coral },
                { label: "Overstock", value: 7, color: C.blue },
            ],
            warehouses: [
                { name: "Warehouse A", health: 68 },
                { name: "Warehouse B", health: 71 },
                { name: "Warehouse C", health: 65 },
            ],

            channelMetrics: ["Reach", "Engagement", "Conversion", "ROI", "Retention"],
            channelPerformance: [
                { channel: "Google Ads", reach: 88, engagement: 74, conversion: 79, roi: 92, retention: 70 },
                { channel: "Facebook Ads", reach: 92, engagement: 88, conversion: 62, roi: 78, retention: 65 },
                { channel: "Email Campaign", reach: 52, engagement: 60, conversion: 91, roi: 96, retention: 88 },
            ],
            bestOf: [
                { label: "Best reach", winner: "Facebook" },
                { label: "Best conversion", winner: "Email" },
                { label: "Best ROI", winner: "Email" },
                { label: "Best retention", winner: "Email" },
            ],

            channelData: [
                { name: "Website", data: makeSeries(12, 230000, 60000, 31) },
                { name: "Mobile app", data: makeSeries(12, 150000, 50000, 32) },
                { name: "Facebook shop", data: makeSeries(12, 90000, 40000, 33) },
                { name: "Marketplace", data: makeSeries(12, 60000, 30000, 34) },
            ],
            marginData: [
                { name: "Power banks", value: 4.2 }, { name: "Earbuds", value: 2.8 }, { name: "Smart watches", value: -1.4 },
                { name: "Cables", value: 0.9 }, { name: "Gaming", value: -2.3 }, { name: "Networking", value: 1.6 },
            ],
            returnsSeries: [
                { name: "Returns", type: "column", data: [14, 12, 15, 18, 16, 19, 21, 17, 15, 14, 16, 12] },
                { name: "Return rate", type: "line", data: [3.4, 3.1, 3.0, 3.3, 2.9, 3.0, 3.2, 2.7, 2.5, 2.4, 2.3, 2.1] },
            ],
            stockDays: Array.from({ length: 14 }, (_, i) => `${15 + i} Oct`),
            stockSeries: [
                { name: "Units in stock", data: STOCK_LEVEL },
                { name: "Reorder point", data: STOCK_LEVEL.map(() => 40) },
            ],

            // rating (x) vs number of reviews (y), grouped by category: scatter chart
            ratingData: [
                { name: "Power banks", data: [[4.6, 420], [4.3, 310], [4.1, 260]] },
                { name: "Earbuds", data: [[4.7, 610], [4.2, 380], [3.9, 190]] },
                { name: "Smart watches", data: [[4.5, 340], [4.0, 210]] },
                { name: "Cables", data: [[4.4, 280], [4.1, 150]] },
                { name: "Gaming", data: [[4.6, 260], [4.2, 175]] },
            ],
            productRevenue: [
                { x: "Anker PowerCore 20000", y: 352800 },
                { x: "Baseus Bowie E9", y: 312900 },
                { x: "Xiaomi Smart Band 8", y: 236250 },
                { x: "JBL Go 4", y: 219450 },
                { x: "Ugreen 65W Charger", y: 201000 },
                { x: "Logitech G102", y: 231000 },
                { x: "Hoco cable 1m", y: 218400 },
                { x: "Realme Buds T300", y: 167400 },
                { x: "SanDisk 128GB", y: 121500 },
                { x: "Havit H2002d", y: 94500 },
            ],
            // [price, units sold, revenue]
            bubbleData: [
                { name: "Power banks", data: [[4200, 84, 352800], [2800, 120, 336000], [5900, 40, 236000]] },
                { name: "Earbuds", data: [[1490, 210, 312900], [2790, 90, 251100], [990, 300, 297000]] },
                { name: "Smart watches", data: [[3150, 75, 236250], [6900, 30, 207000]] },
                { name: "Cables", data: [[420, 520, 218400], [690, 260, 179400]] },
                { name: "Gaming", data: [[1650, 140, 231000], [3990, 55, 219450]] },
            ],
            delivery: [
                { x: "Dhaka", y: [0, 1] },
                { x: "Chattogram", y: [1, 2] },
                { x: "Sylhet", y: [1, 3] },
                { x: "Khulna", y: [2, 3] },
                { x: "Rajshahi", y: [2, 4] },
                { x: "Barishal", y: [2, 4] },
                { x: "Rangpur", y: [3, 5] },
            ],
            cityLabels: ["Dhaka", "Chattogram", "Sylhet", "Khulna", "Rajshahi"],
            cityData: [
                { name: "Delivered", data: [210, 64, 28, 22, 19] },
                { name: "Processing", data: [40, 14, 6, 5, 4] },
                { name: "Cancelled", data: [12, 5, 2, 2, 1] },
            ],
            // weekly average order value: [open, high, low, close]
            candleData: [
                { x: "Wk 1", y: [2850, 3400, 2600, 3150] },
                { x: "Wk 2", y: [3150, 3600, 2950, 3020] },
                { x: "Wk 3", y: [3020, 3300, 2780, 3210] },
                { x: "Wk 4", y: [3210, 3750, 3100, 3480] },
                { x: "Wk 5", y: [3480, 3520, 2990, 3060] },
                { x: "Wk 6", y: [3060, 3410, 2900, 3340] },
                { x: "Wk 7", y: [3340, 3690, 3200, 3610] },
                { x: "Wk 8", y: [3610, 3820, 3350, 3020] },
            ],

            // ── Tables ──
            tableSections: TABLE_SECTIONS,
            tones: TONES,
            pages: Object.fromEntries(Object.keys(TABLES).map((k) => [k, 1])),
            queries: Object.fromEntries(Object.keys(TABLES).map((k) => [k, ""])),
            lowStock: LOW,

            aiRecommendations: [
                { title: "impact", tag: "High impact", icon: "mdi-rocket-launch-outline", color: C.teal, tint: "#dff5ec", text: "Mobile accessories revenue increased 24% this month. Consider increasing ad spend on this category.", action: "Boost campaign" },
                { title: "trend", tag: "Positive trend", icon: "mdi-trending-up", color: C.blue, tint: "#e6edff", text: "Customer retention improved 6.2 points after the loyalty campaign launched in June. Repeat purchases are now trending above target.", action: "View campaign" },
                { title: "action", tag: "Action needed", icon: "mdi-alert-circle-outline", color: C.amber, tint: "#fdecc8", text: "12 products may need inventory replenishment within the next 2 weeks based on current sell-through rates.", action: "Review stock" },
            ],
        };
    },

    computed: {
        today() {
            return new Date().toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },
        current() {
            return this.datasets[this.range];
        },
        totalRevenue() {
            return this.current.revenue.reduce((a, b) => a + b, 0);
        },
        runOut() {
            return this.lowStock.slice(0, 4).map((p) => {
                const days = p.stock === 0 ? 0 : Math.max(1, Math.round(p.stock / Math.max(1, p.sold / 30)));
                return { name: p.name, days, cover: Math.max(6, Math.min(100, days * 9)) };
            });
        },
        spendShare() {
            const total = this.campaigns.reduce((a, c) => a + c.budget, 0);
            const colors = [C.blue, C.violet, C.teal, C.amber];
            return this.campaigns.map((c, i) => ({ name: c.name.replace(" Campaign", "").replace(" Ads", ""), pct: Math.round((c.budget / total) * 100), color: colors[i] }));
        },
        totalOrders() {
            return this.orderStatusCounts.reduce((a, o) => a + o.count, 0);
        },

        /* Three headline tiles in the header */
        heroStats() {
            const rangeText = { 7: "Last 7 days", 30: "Last 30 days", 12: "Last 12 months" }[this.range];
            const pct = Math.round((this.targetSold / this.targetGoal) * 100);
            return [
                { icon: "mdi-cash-multiple", color: C.teal, value: this.money(this.totalRevenue), label: "Revenue", note: rangeText, tag: "Live" },
                { icon: "mdi-target", color: C.amber, value: this.money(this.targetSold), label: "Monthly sales target", note: `Goal ${this.money(this.targetGoal)}`, tag: pct + "%", progress: pct },
                { icon: "mdi-alert-outline", color: C.coral, value: String(this.lowStock.length), label: "Needs restocking", note: "Low-stock products", tag: "Check" },
            ];
        },

        peakSlot() {
            let best = { v: -1 };
            this.heatSeries.forEach((r) => r.data.forEach((d) => { if (d.y > best.v) best = { v: d.y, day: r.name, slot: d.x }; }));
            return best;
        },

        /* Section banners: icon, a one-line summary and four headline chips (value + note), all derived from the data above */
        chapters() {
            const rangeText = { 7: "last 7 days", 30: "last 30 days", 12: "last 12 months" }[this.range];
            const kpi = (label) => this.kpis.find((x) => x.label === label);
            const trend = (n) => (n >= 0 ? "up " : "down ") + Math.abs(n) + "%";
            const kc = (label, name) => {
                const x = kpi(label);
                return { k: name || label, v: x.value, sub: (x.change >= 0 ? "▲ " : "▼ ") + Math.abs(x.change) + "%", tone: (x.change >= 0) !== !!x.inverse ? "up" : "down" };
            };
            const days = ["Saturday", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
            const fullDay = { Mon: "Monday", Tue: "Tuesday", Wed: "Wednesday", Thu: "Thursday", Fri: "Friday", Sat: "Saturday", Sun: "Sunday" };
            const k1 = (v) => (v / 1000).toFixed(1);
            const wMax = Math.max(...this.weekdayData);
            const wMin = Math.min(...this.weekdayData);
            const topCat = this.categoryDistribution[0];
            const topPay = this.paymentData[0];
            const topBrand = this.brandData[0];
            const bestRoi = this.bestOf.find((b) => b.label === "Best ROI");
            const pct = Math.round((this.targetSold / this.targetGoal) * 100);
            const gap = this.targetGoal - this.targetSold;
            const pk = this.peakSlot;
            const grow = this.radarCategories.map((c, i) => ({ c, d: this.radarThis[i] - this.radarLast[i] })).sort((a, b) => b.d - a.d)[0];
            const aov = this.candleData.flatMap((c) => [c.y[1], c.y[2]]);
            const wk = this.candleData.map((c) => c.y[3]);
            const wkChange = wk[wk.length - 1] - wk[wk.length - 2];
            const groups = this.ratingData.map((g) => ({ name: g.name, avg: g.data.reduce((a, d) => a + d[0], 0) / g.data.length, reviews: g.data.reduce((a, d) => a + d[1], 0) }));
            const rated = [...groups].sort((a, b) => b.avg - a.avg)[0];
            const reviewed = [...groups].sort((a, b) => b.reviews - a.reviews)[0];
            const allReviews = groups.reduce((a, g) => a + g.reviews, 0);
            const pts = this.bubbleData.flatMap((g) => g.data.map((d) => ({ cat: g.name, price: d[0], units: d[1] })));
            const topPrice = [...pts].sort((a, b) => b.price - a.price)[0];
            const topUnits = [...pts].sort((a, b) => b.units - a.units)[0];
            const slow = this.delivery[this.delivery.length - 1];
            const fast = this.delivery[0];
            const cityTotal = this.cityData.reduce((a, d) => a + d.data.reduce((x, y) => x + y, 0), 0);
            const cancelled = this.cityData[2].data.reduce((a, b) => a + b, 0);
            const dhaka = this.cityData.map((d) => d.data[0]);
            const dhakaShare = Math.round((dhaka[0] / dhaka.reduce((a, b) => a + b, 0)) * 100);
            const spend = this.campaigns.reduce((a, c) => a + c.budget, 0);
            const earned = this.campaigns.reduce((a, c) => a + c.revenue, 0);
            const newest = this.customerSeries[0].data.slice(-1)[0];
            const returning = this.customerSeries[1].data.slice(-1)[0];
            const oc = this.orderStatusCounts;
            const periodOrders = this.current.orders.reduce((a, b) => a + b, 0);
            return {
                overview: {
                    icon: "mdi-view-dashboard-outline", title: "Key metrics",
                    insight: `Orders are ${trend(kpi("Orders").change)} and the customer count is ${trend(kpi("Customers").change)}. Pending orders are ${trend(kpi("Pending orders").change)}.`,
                    chips: [kc("Revenue this month", "Revenue"), kc("Orders"), kc("Customers"), kc("Conversion rate", "Conversion")],
                },
                health: {
                    icon: "mdi-heart-pulse", title: "Customer, inventory and operational health",
                    insight: `Stock health is ${this.healthSummary.stats[2].value} and customer satisfaction is ${this.satisfaction.score} out of 5. Delivery takes ${this.fulfillment.days} days on average.`,
                    chips: [{ k: "NPS score", v: this.satisfaction.nps, sub: "Net promoter score" }, { k: "On-time", v: this.fulfillment.onTime + "%", sub: "Delivered as promised" }, { k: "Stock health", v: this.healthSummary.stats[2].value, sub: "14 SKUs running low" }, { k: "Retention", v: this.healthSummary.stats[3].value, sub: "Repeat customers" }],
                },
                revenue: {
                    icon: "mdi-cash-multiple", title: "Revenue and category sales",
                    insight: `${this.money(this.totalRevenue)} earned over the ${rangeText}. ${topCat.name} leads with ${topCat.pct}% of category sales.`,
                    chips: [{ k: "Total revenue", v: "৳ " + shortMoney(this.totalRevenue), sub: rangeText }, { k: "Top category", v: topCat.pct + "%", sub: topCat.name.split(" and ")[0] }, kc("Average order value", "Avg order"), { k: "Orders placed", v: periodOrders.toLocaleString("en-IN"), sub: rangeText }],
                },
                orders: {
                    icon: "mdi-clipboard-check-outline", title: "Orders and payment status",
                    insight: `${this.statusData[0].value}% of orders are delivered. ${topPay.name} is the most used payment method at ${topPay.value}%.`,
                    chips: [{ k: "Delivered", v: oc[2].count, sub: oc[2].pct + "% of orders", tone: "up" }, { k: "Processing", v: oc[1].count, sub: oc[1].pct + "% of orders" }, { k: "Pending", v: oc[0].count, sub: oc[0].pct + "% of orders" }, { k: "Cancelled", v: oc[3].count, sub: oc[3].pct + "% of orders", tone: "down" }],
                },
                people: {
                    icon: "mdi-account-group-outline", title: "Brands and customers",
                    insight: `${topBrand.name} leads brand revenue at ৳ ${shortMoney(topBrand.value)}. New customers reached ${newest} in the latest month.`,
                    chips: [{ k: "Top brand", v: topBrand.name, sub: "৳ " + shortMoney(topBrand.value) }, { k: "New customers", v: newest, sub: "Latest month", tone: "up" }, { k: "Returning", v: returning, sub: "Latest month" }, kc("Customers", "All customers")],
                },
                marketing: {
                    icon: "mdi-bullhorn-outline", title: "Marketing and campaigns",
                    insight: `${bestRoi.winner} gives the best return on investment, while ${this.bestOf[0].winner} reaches the most people.`,
                    chips: [{ k: "Ad spend", v: "৳ " + shortMoney(spend), sub: this.campaigns.length + " channels" }, { k: "Ad revenue", v: "৳ " + shortMoney(earned), sub: "Overall ROI " + Math.round(((earned - spend) / spend) * 100) + "%", tone: "up" }, { k: "Best ROI", v: bestRoi.winner, sub: "Highest return" }, { k: "Best reach", v: this.bestOf[0].winner, sub: "Most people" }],
                },
                hours: {
                    icon: "mdi-clock-outline", title: "Busiest hours",
                    insight: `${fullDay[pk.day] || pk.day} between ${pk.slot} is the busiest window with ${pk.v} orders. ${days[this.weekdayData.indexOf(wMax)]} is the busiest day overall.`,
                    chips: [{ k: "Peak window", v: pk.day + " " + pk.slot, sub: "Highest demand" }, { k: "Peak orders", v: pk.v, sub: "In one time slot" }, { k: "Busiest day", v: days[this.weekdayData.indexOf(wMax)], sub: wMax + " orders", tone: "up" }, { k: "Quietest day", v: days[this.weekdayData.indexOf(wMin)], sub: wMin + " orders" }],
                },
                targets: {
                    icon: "mdi-target", title: "Targets and insights",
                    insight: `${pct}% of the monthly target is reached, with ${this.money(gap)} still to go. ${grow.c} is the fastest growing category.`,
                    chips: [{ k: "Target reached", v: pct + "%", sub: this.money(this.targetSold), tone: "up" }, { k: "Remaining", v: "৳ " + shortMoney(gap), sub: "To reach the goal" }, { k: "Monthly goal", v: "৳ " + shortMoney(this.targetGoal), sub: "Sales target" }, { k: "Fastest growing", v: grow.c, sub: "▲ " + grow.d + " points", tone: "up" }],
                },
                inventory: {
                    icon: "mdi-warehouse", title: "Inventory and channel performance",
                    insight: `${this.stockCards[0].value}% of stock is healthy and ${this.stockCards[2].value}% is out of stock. ${bestRoi.winner} is the best performing channel on ROI.`,
                    chips: this.stockCards.map((c, i) => ({ k: c.label, v: c.value + "%", sub: "Of all products", tone: i === 0 ? "up" : i === 2 ? "down" : "" })),
                },
                ratings: {
                    icon: "mdi-star-outline", title: "Product ratings and revenue",
                    insight: `${rated.name} is the best rated category at ${rated.avg.toFixed(1)} stars, and ${reviewed.name} has the most reviews.`,
                    chips: [{ k: "Best rated", v: rated.name, sub: rated.avg.toFixed(1) + " ★ average", tone: "up" }, { k: "Most reviewed", v: reviewed.name, sub: reviewed.reviews + " reviews" }, { k: "Total reviews", v: allReviews.toLocaleString("en-IN"), sub: "All categories" }, { k: "Top revenue item", v: this.productRevenue[0].x.split(" ").slice(0, 2).join(" "), sub: "৳ " + shortMoney(this.productRevenue[0].y) }],
                },
                pricing: {
                    icon: "mdi-truck-delivery-outline", title: "Pricing and delivery",
                    insight: `${fast.x} delivers within a day, while ${slow.x} can take up to ${slow.y[1]} days. ${topUnits.cat} sell the most units.`,
                    chips: [{ k: "Fastest region", v: fast.x, sub: "Within " + fast.y[1] + " day", tone: "up" }, { k: "Slowest region", v: slow.x, sub: "Up to " + slow.y[1] + " days", tone: "down" }, { k: "Highest price", v: this.money(topPrice.price), sub: topPrice.cat }, { k: "Most units", v: topUnits.units, sub: topUnits.cat }],
                },
                outcome: {
                    icon: "mdi-chart-bar-stacked", title: "Order outcome and weekly order value",
                    insight: `Dhaka completes ${dhakaShare}% of its orders. Average order value moves between ৳ ${k1(Math.min(...aov))}k and ৳ ${k1(Math.max(...aov))}k each week.`,
                    chips: [{ k: "Dhaka delivered", v: dhakaShare + "%", sub: "Of Dhaka orders", tone: "up" }, { k: "Cancelled", v: ((cancelled / cityTotal) * 100).toFixed(1) + "%", sub: "All cities", tone: "down" }, { k: "Weekly AOV", v: "৳ " + k1(Math.min(...aov)) + "–" + k1(Math.max(...aov)) + "k", sub: "Low to high" }, { k: "Latest week", v: this.money(wk[wk.length - 1]), sub: (wkChange >= 0 ? "▲ " : "▼ ") + Math.abs(wkChange) + " vs prior week", tone: wkChange >= 0 ? "up" : "down" }],
                },
                ai: {
                    icon: "mdi-creation", title: "AI business recommendations",
                    insight: "Suggestions generated from this period's sales, retention and stock data, ranked by expected impact.",
                    chips: [{ k: "Suggestions", v: this.aiRecommendations.length, sub: "This period" }, { k: "Last refreshed", v: this.aiRefreshedAgo, sub: "AI analysis" }, { k: "High impact", v: this.aiRecommendations[0].action, sub: "Best opportunity", tone: "up" }, { k: "Needs action", v: this.aiRecommendations[2].action, sub: "Time sensitive", tone: "down" }],
                },
            };
        },

        categoryDistribution() {
            const total = this.categoryData.reduce((a, c) => a + c.value, 0);
            return this.categoryData.map((c) => ({ ...c, pct: Math.round((c.value / total) * 1000) / 10, icon: this.categoryIcons[c.name] || "mdi-shape-outline" }));
        },

        /* ── Revenue (area) + orders (line), navy card ── */
        revenueSeries() {
            return [
                { name: "Revenue", type: "area", data: this.current.revenue },
                { name: "Orders", type: "line", data: this.current.orders },
            ];
        },
        revenueOptions() {
            return {
                chart: chartBase(false, { zoom: { enabled: false } }),
                colors: [C.teal, C.blue],
                stroke: { width: [3, 3], curve: "smooth" },
                fill: { type: ["gradient", "solid"], gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.02, stops: [0, 95, 100] } },
                markers: { size: 0, hover: { size: 5 } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.current.labels, tickAmount: this.range === "30" ? 10 : undefined, ...axisClean },
                yaxis: [
                    { title: { text: "Revenue" }, labels: { formatter: shortMoney } },
                    { opposite: true, title: { text: "Orders" } },
                ],
                grid: gridOf(false),
                legend: legendOf(false),
                tooltip: { shared: true, y: [{ formatter: fullMoney }, { formatter: (v) => v + " orders" }] },
            };
        },

        /* ── Donut ── */
        categoryOptions() {
            return {
                chart: chartBase(false),
                labels: this.categoryData.map((c) => c.name),
                colors: PALETTE,
                legend: legendOf(false, "bottom"),
                dataLabels: { enabled: false },
                stroke: { width: 3, colors: ["#ffffff"] },
                plotOptions: {
                    pie: {
                        donut: {
                            size: "70%",
                            labels: {
                                show: true,
                                name: { fontSize: "12px" },
                                value: { fontSize: "18px", fontWeight: 600, color: C.ink, formatter: fullMoney },
                                total: { show: true, label: "Total sales", fontSize: "12px", formatter: (w) => fullMoney(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
                            },
                        },
                    },
                },
            };
        },

        /* ── Weekday columns ── */
        weekdayOptions() {
            const max = Math.max(...this.weekdayData);
            return {
                chart: chartBase(false),
                plotOptions: { bar: { borderRadius: 7, borderRadiusApplication: "end", columnWidth: "52%", distributed: true, dataLabels: { position: "top" } } },
                legend: { show: false },
                dataLabels: { enabled: true, offsetY: -22, style: { fontSize: "11.5px", fontWeight: 600, colors: [C.ink] } },
                xaxis: { categories: ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"], ...axisClean },
                yaxis: { show: false },
                grid: { ...gridOf(false), padding: { top: 18 } },
                colors: this.weekdayData.map((v) => (v === max ? C.teal : "#bfe3d3")),
                tooltip: { y: { formatter: (v) => v + " orders" } },
            };
        },

        /* ── Order status radial ── */
        statusOptions() {
            return {
                chart: chartBase(false),
                labels: this.statusData.map((s) => s.name),
                colors: [C.teal, C.blue, C.amber, C.coral],
                plotOptions: {
                    radialBar: {
                        hollow: { size: "36%" },
                        track: { background: "#e8eeec", margin: 4 },
                        dataLabels: {
                            name: { fontSize: "13px" },
                            value: { fontSize: "20px", fontWeight: 600, color: C.ink, formatter: (v) => v + "%" },
                            total: { show: true, label: "Delivered", formatter: () => this.statusData[0].value + "%" },
                        },
                    },
                },
                stroke: { lineCap: "round" },
                legend: legendOf(false, "bottom"),
            };
        },

        /* ── Payment pie ── */
        paymentOptions() {
            return {
                chart: chartBase(false),
                labels: this.paymentData.map((p) => p.name),
                colors: [C.rose, C.amber, C.coral, C.violet],
                legend: legendOf(false, "bottom"),
                stroke: { width: 3, colors: ["#ffffff"] },
                dataLabels: { formatter: (v) => Math.round(v) + "%", dropShadow: { enabled: false }, style: { fontSize: "12px", fontWeight: 600 } },
                tooltip: { y: { formatter: (v) => v + "%" } },
            };
        },

        /* ── Brands horizontal bars ── */
        brandOptions() {
            return {
                chart: chartBase(false),
                plotOptions: { bar: { horizontal: true, borderRadius: 7, borderRadiusApplication: "end", barHeight: "58%", distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: true, formatter: shortMoney, style: { fontSize: "12px", fontWeight: 600 } },
                xaxis: { categories: this.brandData.map((b) => b.name), labels: { formatter: shortMoney }, ...axisClean },
                grid: gridOf(false),
                colors: PALETTE,
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        /* ── Customers stacked area, navy card ── */
        customerOptions() {
            return {
                chart: chartBase(false, { stacked: true }),
                colors: [C.violet, C.teal],
                stroke: { curve: "smooth", width: 2.5 },
                fill: { type: "gradient", gradient: { opacityFrom: 0.55, opacityTo: 0.05 } },
                dataLabels: { enabled: false },
                xaxis: { categories: MONTHS, ...axisClean },
                grid: gridOf(false),
                legend: legendOf(false),
                tooltip: {},
            };
        },

        /* ── Gauge, navy card ── */
        gaugeOptions() {
            return {
                chart: chartBase(false, { offsetY: -10 }),
                colors: [C.teal],
                plotOptions: {
                    radialBar: {
                        startAngle: -90,
                        endAngle: 90,
                        hollow: { size: "60%" },
                        track: { background: "#e8eeec", strokeWidth: "100%" },
                        dataLabels: {
                            name: { show: true, offsetY: 24, fontSize: "12.5px", color: C.muted },
                            value: { offsetY: -22, fontSize: "34px", fontWeight: 600, color: C.ink, formatter: (v) => v + "%" },
                        },
                    },
                },
                fill: { type: "gradient", gradient: { shade: "dark", type: "horizontal", gradientToColors: ["#6ee7b7"], stops: [0, 100] } },
                stroke: { lineCap: "round" },
                labels: ["Target reached"],
            };
        },

        /* ── Radar ── */
        radarSeries() {
            return [
                { name: "This month", data: this.radarThis },
                { name: "Last month", data: this.radarLast },
            ];
        },
        radarOptions() {
            return {
                chart: chartBase(false),
                colors: [C.teal, C.amber],
                xaxis: { categories: this.radarCategories },
                yaxis: { show: false },
                stroke: { width: 2 },
                fill: { opacity: 0.2 },
                markers: { size: 4, strokeWidth: 0 },
                legend: legendOf(false, "bottom"),
                plotOptions: { radar: { polygons: { strokeColors: "#e2e9e7", connectorColors: "#e2e9e7" } } },
            };
        },

        /* ── Polar area ── */
        trafficOptions() {
            return {
                chart: chartBase(false),
                labels: this.trafficData.map((t) => t.name),
                colors: PALETTE,
                stroke: { colors: ["#ffffff"], width: 2 },
                fill: { opacity: 0.9 },
                yaxis: { show: false },
                legend: legendOf(false, "bottom"),
                plotOptions: { polarArea: { rings: { strokeColor: "#e2e9e7" }, spokes: { connectorColors: "#e2e9e7" } } },
            };
        },

        /* ── Stacked columns by channel ── */
        channelOptions() {
            return {
                chart: chartBase(false, { stacked: true }),
                colors: [C.blue, C.teal, C.amber, C.violet],
                plotOptions: { bar: { columnWidth: "52%", borderRadius: 5, borderRadiusApplication: "end" } },
                dataLabels: { enabled: false },
                xaxis: { categories: MONTHS, ...axisClean },
                yaxis: { labels: { formatter: shortMoney } },
                grid: gridOf(false),
                legend: legendOf(false),
                tooltip: { shared: true, intersect: false, y: { formatter: fullMoney } },
            };
        },

        /* ── Diverging bars: margin change ── */
        marginSeries() {
            return [{ name: "Margin change", data: this.marginData.map((m) => m.value) }];
        },
        marginOptions() {
            return {
                chart: chartBase(false),
                plotOptions: { bar: { horizontal: true, barHeight: "55%", borderRadius: 5, colors: { ranges: [{ from: -100, to: -0.01, color: C.coral }, { from: 0, to: 100, color: C.teal }] } } },
                dataLabels: { enabled: true, formatter: (v) => (v > 0 ? "+" : "") + v + "%", style: { fontSize: "12px", fontWeight: 600 } },
                xaxis: { categories: this.marginData.map((m) => m.name), labels: { formatter: (v) => v + "%" }, ...axisClean },
                grid: gridOf(false),
                legend: { show: false },
                tooltip: { y: { formatter: (v) => v + "%" } },
            };
        },

        /* ── Column + line: returns ── */
        returnsOptions() {
            return {
                chart: chartBase(false),
                colors: ["#bfe3d3", C.coral],
                stroke: { width: [0, 3], curve: "smooth" },
                plotOptions: { bar: { columnWidth: "48%", borderRadius: 5, borderRadiusApplication: "end" } },
                markers: { size: [0, 4], strokeWidth: 2, strokeColors: "#ffffff" },
                dataLabels: { enabled: false },
                xaxis: { categories: MONTHS, ...axisClean },
                yaxis: [{ title: { text: "Returns" } }, { opposite: true, title: { text: "Return rate" }, labels: { formatter: (v) => v + "%" } }],
                grid: gridOf(false),
                legend: legendOf(false),
                tooltip: { shared: true },
            };
        },

        /* ── Stepline: stock level, navy card ── */
        stockOptions() {
            return {
                chart: chartBase(false, { zoom: { enabled: false } }),
                colors: [C.blue, C.coral],
                stroke: { curve: "stepline", width: [3, 2], dashArray: [0, 6] },
                markers: { size: 0 },
                dataLabels: { enabled: false },
                xaxis: { categories: this.stockDays, tickAmount: 7, ...axisClean },
                yaxis: { title: { text: "Units" }, min: 0 },
                grid: gridOf(false),
                legend: legendOf(false),
                tooltip: {},
            };
        },

        /* ── Scatter: rating vs review volume, navy card ── */
        scatterSeries() {
            return this.ratingData;
        },
        scatterOptions() {
            return {
                chart: chartBase(false, { zoom: { enabled: false } }),
                colors: PALETTE,
                markers: { size: 8, strokeWidth: 2, strokeColors: "#ffffff", hover: { sizeOffset: 3 } },
                xaxis: { type: "numeric", title: { text: "Average rating" }, min: 3.5, max: 5, tickAmount: 6, labels: { formatter: (v) => Number(v).toFixed(1) } },
                yaxis: { title: { text: "Number of reviews" } },
                grid: gridOf(false),
                legend: legendOf(false),
                tooltip: { y: { formatter: (v) => v + " reviews" } },
            };
        },

        /* ── Treemap ── */
        treemapSeries() {
            return [{ data: this.productRevenue }];
        },
        treemapOptions() {
            return {
                chart: chartBase(false),
                colors: PALETTE,
                plotOptions: { treemap: { distributed: true, enableShades: false, borderRadius: 6 } },
                stroke: { width: 3, colors: ["#ffffff"] },
                legend: { show: false },
                dataLabels: { style: { fontSize: "12px", fontWeight: 600 }, formatter: (text, op) => [text, shortMoney(op.value)] },
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        /* ── Bubble ── */
        bubbleSeries() {
            return this.bubbleData;
        },
        bubbleOptions() {
            return {
                chart: chartBase(false, { zoom: { enabled: false } }),
                colors: PALETTE,
                dataLabels: { enabled: false },
                fill: { opacity: 0.78 },
                stroke: { width: 2, colors: ["#ffffff"] },
                plotOptions: { bubble: { minBubbleRadius: 8, maxBubbleRadius: 42 } },
                xaxis: { type: "numeric", min: 0, max: 7500, tickAmount: 7, title: { text: "Price (৳)" }, labels: { formatter: (v) => Math.round(v) } },
                yaxis: { title: { text: "Units sold" } },
                grid: gridOf(false),
                legend: legendOf(false),
                tooltip: {
                    custom: ({ seriesIndex, dataPointIndex, w }) => {
                        const [price, units, revenue] = w.config.series[seriesIndex].data[dataPointIndex];
                        return `<div style="padding:10px 12px;font-size:12px;line-height:1.6"><b>${w.config.series[seriesIndex].name}</b><br/>Price: ${fullMoney(price)}<br/>Units: ${units}<br/>Revenue: ${fullMoney(revenue)}</div>`;
                    },
                },
            };
        },

        /* ── Range bar (delivery days), navy card ── */
        deliverySeries() {
            return [{ name: "Delivery time", data: this.delivery }];
        },
        deliveryOptions() {
            return {
                chart: chartBase(false),
                colors: [C.teal],
                plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: "52%", rangeBarGroupRows: true } },
                fill: { type: "gradient", gradient: { shade: "light", type: "horizontal", gradientToColors: [C.blue], stops: [0, 100] } },
                xaxis: { min: 0, max: 5, tickAmount: 5, title: { text: "Days" } },
                grid: gridOf(false),
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (v) => v + " day" + (v === 1 ? "" : "s") } },
            };
        },

        /* ── 100% stacked bar ── */
        citySeries() {
            return this.cityData;
        },
        cityOptions() {
            return {
                chart: chartBase(false, { stacked: true, stackType: "100%" }),
                colors: [C.teal, C.blue, C.coral],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: "58%" } },
                stroke: { width: 2, colors: ["#ffffff"] },
                xaxis: { categories: this.cityLabels, ...axisClean },
                grid: gridOf(false),
                legend: legendOf(false),
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (v) => v + " orders" } },
            };
        },

        /* ── Candlestick, navy card ── */
        candleSeries() {
            return [{ name: "Average order value", data: this.candleData }];
        },
        candleOptions() {
            return {
                chart: chartBase(false),
                plotOptions: { candlestick: { colors: { upward: C.teal, downward: C.coral }, wick: { useFillColor: true } } },
                xaxis: { ...axisClean },
                yaxis: { labels: { formatter: shortMoney }, tooltip: { enabled: true } },
                grid: gridOf(false),
                tooltip: {},
            };
        },

        /* ── Heatmap ── */
        heatSeries() {
            const days = ["Fri", "Thu", "Wed", "Tue", "Mon", "Sun", "Sat"];
            const slots = ["9–12", "12–15", "15–18", "18–21", "21–24"];
            const r = seeded(42);
            return days.map((d) => ({
                name: d,
                data: slots.map((s, si) => ({ x: s, y: Math.round(4 + r() * 12 + (si === 3 ? 10 : 0) + (d === "Fri" || d === "Sat" ? 6 : 0)) })),
            }));
        },
        heatOptions() {
            return {
                chart: chartBase(false),
                dataLabels: {
                    enabled: true,
                    // dark text on pale cells, white text on dark cells
                    style: { fontSize: "12px", fontWeight: 600, colors: [({ seriesIndex, dataPointIndex, w }) => (w.globals.series[seriesIndex][dataPointIndex] >= 15 ? "#ffffff" : C.ink)] },
                },
                fill: { opacity: 1 },
                colors: [C.teal],
                stroke: { width: 3, colors: ["#ffffff"] },
                plotOptions: {
                    heatmap: {
                        radius: 7,
                        colorScale: {
                            ranges: [
                                { from: 0, to: 8, color: "#d6f3e6", name: "Low" },
                                { from: 9, to: 14, color: "#7fd8b2", name: "Medium" },
                                { from: 15, to: 22, color: "#1fae7c", name: "High" },
                                { from: 23, to: 60, color: "#0b5a4a", name: "Peak" },
                            ],
                        },
                    },
                },
                legend: legendOf(false),
            };
        },
    },

    mounted() {
        if (typeof IntersectionObserver === "undefined") return;
        this.$nextTick(() => {
            chapterObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((e) => {
                        if (!e.isIntersecting) return;
                        const id = e.target.id.replace("ch-", "");
                        this.activeChapter = id.startsWith("tables-") ? "tables-0" : id;
                    });
                },
                { rootMargin: "-18% 0px -72% 0px" }
            );
            this.$el.querySelectorAll(".tt-chapter[id]").forEach((el) => chapterObserver.observe(el));
        });
    },

    beforeUnmount() {
        if (chapterObserver) chapterObserver.disconnect();
        chapterObserver = null;
    },

    methods: {
        money: fullMoney,

        goTo(id) {
            const el = document.getElementById("ch-" + id);
            if (!el) return;
            const calm = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            el.scrollIntoView({ behavior: calm ? "auto" : "smooth", block: "start" });
        },
        moneyDec: decMoney,

        scoreClass(v) {
            return v >= 85 ? "hi" : v >= 70 ? "mid" : "lo";
        },

        sparkOptions(color) {
            return {
                chart: { sparkline: { enabled: true } },
                grid: { padding: { left: 0, right: 0, top: 4, bottom: 0 } },
                stroke: { curve: "smooth", width: 2.5 },
                colors: [color],
                fill: { type: "gradient", gradient: { opacityFrom: 0.38, opacityTo: 0.02 } },
                tooltip: { enabled: false },
            };
        },

        /* table helpers: search + pagination */
        rowsOf(t) {
            const q = (this.queries[t.id] || "").trim().toLowerCase();
            return q ? t.rows.filter((r) => t.cols.some((c) => String(r[c.key]).toLowerCase().includes(q))) : t.rows;
        },
        pageRows(t) {
            const p = this.pages[t.id] || 1;
            return this.rowsOf(t).slice((p - 1) * t.perPage, p * t.perPage);
        },
        blankCount(t) {
            return Math.max(0, t.perPage - this.pageRows(t).length - (this.rowsOf(t).length ? 0 : 1));
        },
        tableStats(t) {
            return t.stats ? t.stats(this.rowsOf(t)) : [];
        },
        pageCount(t) {
            return Math.max(1, Math.ceil(this.rowsOf(t).length / t.perPage));
        },
        showing(t) {
            const n = this.rowsOf(t).length;
            if (!n) return "No results";
            const p = this.pages[t.id] || 1;
            return `Showing ${(p - 1) * t.perPage + 1}–${Math.min(p * t.perPage, n)} of ${n}`;
        },
        cell(c, v) {
            if (c.type === "money") return fullMoney(v);
            if (c.type === "pct") return v + "%";
            if (c.type === "num") return Number(v).toLocaleString("en-IN");
            return v;
        },
        stars(n) {
            return "★".repeat(n) + "☆".repeat(5 - n);
        },

        exportReport() {
            // Hook this up to your real export endpoint, e.g. GET /api/admin/dashboard/export
            // eslint-disable-next-line no-console
            console.log("Export dashboard report", { range: this.range });
        },
    },
};
</script>
