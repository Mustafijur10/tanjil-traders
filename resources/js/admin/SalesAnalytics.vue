<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-center ga-2 mb-2">
                <span class="tt-pill tt-pill-ai"><v-icon size="12">mdi-creation</v-icon> AI insights refreshed {{ aiRefreshedAgo }}</span>
                <span class="tt-pill tt-pill-live"><span class="tt-pill-dot"></span> Live</span>
            </div>

            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">Sales Analytics</h1>
                    <div class="tt-hero-sub">Revenue · Funnel · Channels · Products · Forecast</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-btn-toggle v-model="range" mandatory density="comfortable" variant="outlined" divided class="tt-range">
                        <v-btn value="7" class="text-none">7 days</v-btn>
                        <v-btn value="30" class="text-none">30 days</v-btn>
                        <v-btn value="12" class="text-none">12 months</v-btn>
                    </v-btn-toggle>
                    <v-btn variant="outlined" class="text-none tt-ghost-btn" prepend-icon="mdi-download" @click="exportReport">Export report</v-btn>
                    <v-btn color="white" variant="flat" class="text-none tt-add" prepend-icon="mdi-plus" to="/admin/orders">New order</v-btn>
                </div>
            </div>

            <div class="d-flex flex-wrap align-center justify-space-between mt-2">
                <div class="tt-viewers">
                    <span v-for="(v, i) in viewers" :key="v" class="tt-viewer-avatar" :style="{ zIndex: viewers.length - i }">{{ v }}</span>
                    <span class="tt-viewer-avatar tt-viewer-extra">+{{ extraViewers }}</span>
                    <span class="tt-viewers-label ml-2">viewing sales analytics</span>
                </div>
                <span class="tt-date-pill"><v-icon size="14">mdi-clock</v-icon> As on {{ today }}</span>
            </div>

            <div class="tt-hero-strip tt-hero-strip-4">
                <div v-for="h in heroStats" :key="h.label" class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                    <div class="flex-grow-1">
                        <div class="tt-hero-label">{{ h.label }}</div>
                        <div class="tt-hero-value">{{ h.value }}</div>
                        <div class="tt-hero-note">{{ h.note }}</div>
                    </div>
                </div>
            </div>
            <div class="tt-hero-line"></div>
        </header>

        <!-- ═════════ Revenue summary strip ═════════ -->
        <v-card flat class="tt-card tt-green mt-4">
            <div class="tt-revbar">
                <div class="tt-revbar-main">
                    <div class="d-flex align-center ga-2">
                        <span class="tt-eyebrow">Revenue this month</span>
                        <span class="tt-delta up"><v-icon size="12">mdi-arrow-top-right</v-icon>{{ mom }}% MoM</span>
                    </div>
                    <div class="d-flex align-end justify-space-between mt-2">
                        <div class="tt-big">{{ money(mtdRevenue) }}</div>
                        <apexchart type="area" width="110" height="44" :options="sparkOptions(GREEN)" :series="[{ data: mtdSpark }]" />
                    </div>
                    <div class="tt-op-bar mt-2"><div class="tt-op-bar-fill" :style="{ width: Math.min(attainment, 100) + '%', background: GREEN }"></div></div>
                    <div class="d-flex justify-space-between tt-sub"><span>Target: {{ money(mtdTarget) }}</span><span class="font-weight-medium">{{ attainment }}% attained</span></div>
                </div>
                <div v-for="m in velocityTiles" :key="m.label" class="tt-revbar-tile">
                    <div class="tt-kpi-icon" :style="{ background: m.tint }"><v-icon :icon="m.icon" size="20" :color="m.color" /></div>
                    <div>
                        <div class="tt-sub">{{ m.label }}</div>
                        <div class="tt-tile-value">{{ m.value }}</div>
                        <div class="tt-tile-note" :class="m.good ? 'good' : 'bad'">{{ m.note }}</div>
                    </div>
                </div>
            </div>
        </v-card>

        <!-- ═════════ Key metrics ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-chart-box-outline</v-icon><span>Key sales metrics</span></div>
        <v-row dense>
            <v-col v-for="k in kpis" :key="k.label" cols="12" sm="6" md="3">
                <v-card flat class="tt-card tt-kpi">
                    <div class="d-flex align-center">
                        <div class="tt-kpi-icon" :style="{ background: k.tint }"><v-icon :icon="k.icon" size="20" :color="k.color" /></div>
                        <div class="tt-kpi-label ml-3">{{ k.label }}</div>
                    </div>
                    <div class="tt-kpi-box mt-3">
                        <div class="d-flex align-center justify-space-between">
                            <div class="tt-kpi-value" :style="{ color: k.color }">{{ k.value }}</div>
                            <apexchart type="area" width="100" height="40" :options="sparkOptions(k.color)" :series="[{ data: k.spark }]" />
                        </div>
                    </div>
                    <span class="tt-delta mt-3" :class="(k.change >= 0) !== !!k.inverse ? 'up' : 'down'">
                        <v-icon size="13">{{ k.change >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>
                        {{ Math.abs(k.change) }}% vs last period
                    </span>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Revenue trend + channels ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-trending-up</v-icon><span>Revenue trend &amp; channels</span></div>
        <v-row dense>
            <v-col cols="12" lg="7">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Revenue vs target</span><v-spacer /><span class="tt-tag">Live</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Total {{ money(totalRevenue) }} · compared with the previous period</div>
                        <apexchart type="line" height="320" :options="trendOptions" :series="trendSeries" />
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="5">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Sales by channel</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Where your revenue is coming from</div>
                        <apexchart type="bar" height="320" :options="channelOptions" :series="channelSeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Funnel + forecast ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-filter-variant</v-icon><span>Sales funnel &amp; forecast</span></div>
        <v-row dense>
            <v-col cols="12" lg="7">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span>
                        <div>
                            <div class="tt-card-title">Sales funnel — visit to delivery</div>
                            <div class="tt-sub">Rolling 30-day cohort, all channels</div>
                        </div>
                        <v-spacer />
                        <span class="tt-tag">{{ fmtNum(funnelStages[0].value) }} visitors</span>
                    </div>
                    <div class="tt-body">
                        <template v-for="(s, i) in funnel" :key="s.name">
                            <div v-if="i > 0" class="tt-drop"><v-icon size="11">mdi-arrow-down</v-icon>{{ s.drop }}% drop</div>
                            <div class="tt-fn-row">
                                <div class="tt-fn-bar" :style="{ width: s.width + '%', background: s.color }">
                                    <span class="d-flex align-center ga-2"><v-icon size="16" color="white">{{ s.icon }}</v-icon>{{ s.name }}</span>
                                    <b>{{ fmtNum(s.value) }}</b>
                                </div>
                                <span class="tt-fn-pct">{{ s.pct }}%</span>
                            </div>
                        </template>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="5">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span>
                        <div>
                            <div class="tt-card-title">Revenue forecast</div>
                            <div class="tt-sub">AI-projected close vs target, ±confidence band</div>
                        </div>
                    </div>
                    <div class="tt-body">
                        <div class="tt-fc-grid">
                            <div class="tt-fc-box"><div class="tt-sub">Projected close</div><div class="tt-fc-value">{{ money(forecast.projected) }}</div></div>
                            <div class="tt-fc-box"><div class="tt-sub">Confidence</div><div class="tt-fc-value">±{{ forecast.confidence }}%</div></div>
                        </div>
                        <div v-for="r in forecast.rows" :key="r.label" class="tt-fc-row">
                            <span class="d-flex align-center"><span class="tt-dot-sm" :style="{ background: r.color }"></span>{{ r.label }}</span>
                            <span class="font-weight-medium">{{ money(r.value) }}</span>
                        </div>
                        <div class="tt-note mt-3">{{ forecast.note }}</div>
                        <v-btn variant="outlined" class="text-none mt-3" block prepend-icon="mdi-file-chart-outline" color="#7c3aed">View full forecast model</v-btn>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Heatmap + segments ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-clock-outline</v-icon><span>Buying behaviour</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head tt-head-wrap">
                        <span class="tt-dot"></span>
                        <div>
                            <div class="tt-card-title">Order activity heatmap</div>
                            <div class="tt-sub">Orders placed by day and time, last 4 weeks</div>
                        </div>
                        <v-spacer />
                        <div class="tt-legend"><span>Low</span><i v-for="n in 5" :key="n" :class="'lv' + (n - 1)"></i><span>High</span></div>
                    </div>
                    <div class="tt-body">
                        <div class="tt-hm">
                            <div class="tt-hm-corner"></div>
                            <div v-for="h in heatHours" :key="h" class="tt-hm-hour">{{ h }}</div>
                            <template v-for="(row, di) in heatMatrix" :key="heatDays[di]">
                                <div class="tt-hm-day">{{ heatDays[di] }}</div>
                                <div v-for="(v, hi) in row" :key="hi" class="tt-hm-cell" :class="'lv' + heatLevel(v)" :title="heatDays[di] + ' ' + heatHours[hi] + ': ' + v + ' orders'"></div>
                            </template>
                        </div>
                        <div class="tt-hm-stats">
                            <div><div class="tt-sub">Peak window</div><div class="tt-hm-stat">{{ heatStats.peak }}</div></div>
                            <div><div class="tt-sub">Busiest day</div><div class="tt-hm-stat">{{ heatStats.busiestDay }}</div></div>
                            <div><div class="tt-sub">Orders / week</div><div class="tt-hm-stat">{{ heatStats.perWeek }} avg</div></div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Customer segments</span></div>
                    <div class="tt-body">
                        <div class="d-flex align-center ga-2">
                            <div style="width: 150px; flex: none">
                                <apexchart type="donut" height="165" :options="segmentOptions" :series="segments.map((s) => s.share)" />
                            </div>
                            <div class="flex-grow-1">
                                <div v-for="s in segments" :key="s.name" class="tt-seg-row">
                                    <span class="d-flex align-center"><span class="tt-dot-sm" :style="{ background: s.color }"></span>{{ s.name }}</span>
                                    <span class="font-weight-medium">{{ s.share }}%</span>
                                </div>
                            </div>
                        </div>
                        <div class="tt-note mt-3">{{ segmentNote }}</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Category + region ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-shape-outline</v-icon><span>Category &amp; regional performance</span></div>
        <v-row dense>
            <v-col cols="12" lg="7">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Category performance</span></div>
                    <v-table class="tt-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-right">Units</th>
                                <th class="text-right">Revenue</th>
                                <th style="min-width: 120px">Share</th>
                                <th class="text-right">Margin</th>
                                <th class="text-right">Growth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in categoryRows" :key="c.name">
                                <td>
                                    <div class="d-flex align-center ga-2">
                                        <span class="tt-cat-mini" :style="{ background: c.color }"><v-icon size="14" color="white">{{ c.icon }}</v-icon></span>
                                        <span class="font-weight-medium">{{ c.name }}</span>
                                    </div>
                                </td>
                                <td class="text-right">{{ fmtNum(c.units) }}</td>
                                <td class="text-right font-weight-medium">{{ money(c.revenue) }}</td>
                                <td>
                                    <div class="d-flex align-center ga-2">
                                        <div class="tt-op-bar flex-grow-1 mb-0"><div class="tt-op-bar-fill" :style="{ width: c.share + '%', background: c.color }"></div></div>
                                        <span class="tt-sub">{{ c.share }}%</span>
                                    </div>
                                </td>
                                <td class="text-right">{{ c.margin }}%</td>
                                <td class="text-right">
                                    <span :class="c.growth >= 0 ? 'tt-growth-up' : 'tt-growth-down'">
                                        <v-icon size="12">{{ c.growth >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(c.growth) }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>
            <v-col cols="12" lg="5">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Sales by region</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Revenue by delivery city</div>
                        <apexchart type="bar" height="300" :options="regionOptions" :series="regionSeries" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Leaderboard + at risk ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-trophy-outline</v-icon><span>Team &amp; orders needing attention</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Sales leaderboard</span><v-spacer /><span class="tt-tag">This month</span></div>
                    <div class="tt-body">
                        <div class="tt-podium">
                            <div v-for="p in podium" :key="p.name" class="tt-pod">
                                <v-icon v-if="p.rank === 1" size="20" color="#d97706">mdi-crown-outline</v-icon>
                                <div class="tt-avatar" :class="{ big: p.rank === 1 }" :style="{ background: p.color }">
                                    {{ initials(p.name) }}
                                    <span class="tt-rank">{{ p.rank }}</span>
                                </div>
                                <div class="tt-pod-name">{{ p.name }}</div>
                                <div class="tt-sub">{{ p.region }}</div>
                                <div class="tt-pod-amount" :class="{ first: p.rank === 1 }">{{ money(p.revenue) }}</div>
                                <div class="tt-pod-block" :class="'r' + p.rank"></div>
                            </div>
                        </div>
                        <div v-for="(a, i) in leaderboardRest" :key="a.name" class="tt-lb-row">
                            <span class="tt-lb-rank">{{ i + 4 }}</span>
                            <div class="tt-avatar sm" :style="{ background: a.color }">{{ initials(a.name) }}</div>
                            <div class="flex-grow-1">
                                <div class="font-weight-medium">{{ a.name }}</div>
                                <div class="tt-sub">{{ a.region }} · {{ a.orders }} orders closed</div>
                            </div>
                            <div class="font-weight-bold">{{ money(a.revenue) }}</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Orders at risk</span>
                        <v-spacer />
                        <span class="tt-tag warn"><v-icon size="11">mdi-alert-outline</v-icon> {{ atRisk.length }} flagged</span>
                    </div>
                    <div class="tt-body">
                        <div v-for="r in atRisk" :key="r.title" class="tt-risk">
                            <div class="tt-risk-icon" :class="r.level"><v-icon size="18" color="white">{{ r.icon }}</v-icon></div>
                            <div class="flex-grow-1">
                                <div class="font-weight-medium">{{ r.title }}</div>
                                <div class="tt-sub">{{ r.desc }}</div>
                            </div>
                            <span class="tt-risk-chip" :class="r.level"><span class="tt-dot-sm"></span>{{ r.level === "high" ? "High" : "Medium" }}</span>
                        </div>
                        <v-btn variant="outlined" class="text-none mt-2" block prepend-icon="mdi-lifebuoy" color="#7c3aed">Open recovery playbook</v-btn>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Conversion journey ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-routes</v-icon><span>Customer journey</span></div>
        <v-card flat class="tt-card tt-green">
            <div class="tt-head">
                <span class="tt-dot"></span>
                <div>
                    <div class="tt-card-title">Conversion journey</div>
                    <div class="tt-sub">Average path from first visit to repeat purchase</div>
                </div>
                <v-spacer />
                <span class="tt-tag">{{ overallConversion }}% overall conversion</span>
            </div>
            <div class="tt-body">
                <div class="tt-journey">
                    <div v-for="(j, i) in journey" :key="j.name" class="tt-jr" :class="{ first: i === 0 }" :style="{ background: j.bg }">
                        <v-icon size="16" :color="j.color">{{ j.icon }}</v-icon>
                        <div class="tt-jr-name">{{ j.name }}</div>
                        <div class="tt-jr-value">{{ fmtNum(j.value) }}</div>
                        <div class="tt-jr-pct">{{ i === 0 ? "&nbsp;" : j.pctPrior + "% of prior" }}</div>
                    </div>
                </div>
            </div>
        </v-card>

        <!-- ═════════ Orders + coupons ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>Orders, products &amp; discounts</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-blue">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Recent orders</span>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/orders">View all orders</v-btn>
                    </div>
                    <v-table class="tt-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Channel</th>
                                <th>Agent</th>
                                <th class="text-right">Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="o in orders" :key="o.id">
                                <td class="font-weight-medium">{{ o.id }}</td>
                                <td>{{ o.customer }}</td>
                                <td class="tt-muted">{{ o.channel }}</td>
                                <td class="tt-muted">{{ o.agent }}</td>
                                <td class="text-right font-weight-medium">{{ money(o.amount) }}</td>
                                <td class="tt-muted">{{ o.date }}</td>
                                <td><v-chip :color="orderColor[o.status]" size="small" variant="tonal" label>{{ o.status }}</v-chip></td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>
        </v-row>

        <v-row dense class="mt-0">
            <v-col cols="12" lg="7">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Top selling products</span>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/catalog">View catalog</v-btn>
                    </div>
                    <v-table class="tt-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Units</th>
                                <th class="text-right">Revenue</th>
                                <th class="text-right">Margin</th>
                                <th class="text-right">Growth</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in topProducts" :key="p.name">
                                <td>
                                    <div class="font-weight-medium">{{ p.name }}</div>
                                    <div class="tt-sub">{{ p.brand }} · {{ p.category }}</div>
                                </td>
                                <td class="text-right">{{ fmtNum(p.units) }}</td>
                                <td class="text-right font-weight-medium">{{ money(p.revenue) }}</td>
                                <td class="text-right">{{ p.margin }}%</td>
                                <td class="text-right">
                                    <span :class="p.growth >= 0 ? 'tt-growth-up' : 'tt-growth-down'">
                                        <v-icon size="12">{{ p.growth >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>{{ Math.abs(p.growth) }}%
                                    </span>
                                </td>
                                <td><v-chip :color="stockColor[p.stock]" size="small" variant="tonal" label>{{ p.stock }}</v-chip></td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>
            <v-col cols="12" lg="5">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Coupons &amp; discounts</span><v-spacer /><span class="tt-sub">Discount cost {{ money(totalDiscount) }}</span></div>
                    <div class="tt-body pa-0">
                        <div v-for="c in coupons" :key="c.code" class="tt-coupon">
                            <div class="tt-coupon-code">{{ c.code }}</div>
                            <div class="flex-grow-1">
                                <div class="font-weight-medium">{{ money(c.revenue) }} <span class="tt-sub">revenue</span></div>
                                <div class="tt-sub">{{ c.uses }} uses · {{ money(c.discount) }} given away</div>
                            </div>
                            <span class="tt-roi" :class="c.roi >= 5 ? 'good' : 'mid'">{{ c.roi }}x ROI</span>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Revenue streams / opportunities / recommendations ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-layers-outline</v-icon><span>Revenue streams &amp; opportunities</span></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span>
                        <div>
                            <div class="tt-card-title">Revenue streams</div>
                            <div class="tt-sub">New vs repeat vs bulk · {{ money(totalRevenue) }} total</div>
                        </div>
                    </div>
                    <div class="tt-body">
                        <div class="tt-stack mb-3">
                            <div v-for="s in streams" :key="s.name" :style="{ width: s.pct + '%', background: s.color }"></div>
                        </div>
                        <div v-for="s in streams" :key="s.name" class="tt-stream-row">
                            <span class="d-flex align-center"><span class="tt-dot-sm" :style="{ background: s.color }"></span>{{ s.name }}</span>
                            <span class="font-weight-medium">{{ money(s.value) }} · {{ s.pct }}%</span>
                        </div>
                        <div class="tt-note mt-3"><v-icon size="14" class="mr-1">mdi-information-outline</v-icon>{{ streamNote }}</div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Hot opportunities</span><v-spacer /><span class="tt-sub">Bulk &amp; corporate quotes</span></div>
                    <div class="tt-body">
                        <div v-for="o in opportunities" :key="o.name" class="tt-opp">
                            <div class="tt-ring" :style="{ background: `conic-gradient(${o.color} ${o.prob * 3.6}deg, #eef2f1 0deg)` }"><span>{{ o.prob }}%</span></div>
                            <div class="flex-grow-1" style="min-width: 0">
                                <div class="font-weight-medium text-truncate">{{ o.name }}</div>
                                <span class="tt-stage" :style="{ color: o.color }">{{ o.stage }}</span>
                            </div>
                            <div class="font-weight-bold">{{ money(o.value) }}</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Smart recommendations</span></div>
                    <div class="tt-body">
                        <div v-for="r in aiRecommendations" :key="r.tag" class="tt-rec" :class="r.tagClass">
                            <div class="d-flex align-center justify-space-between mb-1">
                                <span class="tt-ai-tag" :class="r.tagClass"><span class="tt-dot-sm"></span>{{ r.tag }}</span>
                                <v-icon size="16" :color="r.color">{{ r.icon }}</v-icon>
                            </div>
                            <div class="tt-rec-text">{{ r.text }}</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Why buy / abandon + events ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-lightbulb-on-outline</v-icon><span>Buying reasons &amp; upcoming events</span></div>
        <v-row dense>
            <v-col cols="12" lg="7">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span>
                        <div>
                            <div class="tt-card-title">Why customers buy — and why they leave</div>
                            <div class="tt-sub">Top cited reasons from checkout surveys and exit feedback</div>
                        </div>
                        <v-spacer />
                        <span class="tt-tag">{{ fmtNum(surveyCount) }} responses</span>
                    </div>
                    <div class="tt-body">
                        <div class="tt-win-grid mb-4">
                            <div v-for="t in winTiles" :key="t.label" class="tt-win-tile"><div class="tt-sub">{{ t.label }}</div><div class="tt-win-value" :style="{ color: t.color }">{{ t.value }}</div></div>
                        </div>
                        <v-row dense>
                            <v-col cols="12" sm="6">
                                <div class="tt-reason-title good"><v-icon size="14">mdi-thumb-up-outline</v-icon> Top reasons they buy</div>
                                <div v-for="r in buyReasons" :key="r.label" class="mb-3">
                                    <div class="d-flex justify-space-between tt-reason-label"><span>{{ r.label }}</span><span class="tt-sub">{{ r.pct }}%</span></div>
                                    <div class="tt-op-bar"><div class="tt-op-bar-fill" :style="{ width: r.pct * 2 + '%', background: GREEN }"></div></div>
                                </div>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <div class="tt-reason-title bad"><v-icon size="14">mdi-thumb-down-outline</v-icon> Top reasons they leave</div>
                                <div v-for="r in leaveReasons" :key="r.label" class="mb-3">
                                    <div class="d-flex justify-space-between tt-reason-label"><span>{{ r.label }}</span><span class="tt-sub">{{ r.pct }}%</span></div>
                                    <div class="tt-op-bar"><div class="tt-op-bar-fill" :style="{ width: r.pct * 2 + '%', background: RED }"></div></div>
                                </div>
                            </v-col>
                        </v-row>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="5">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Upcoming sales events</span><v-spacer /><span class="tt-tag warn">{{ events.filter((e) => e.status === "At Risk").length }} at risk</span></div>
                    <div class="tt-body">
                        <div v-for="e in events" :key="e.name" class="tt-event">
                            <div class="tt-event-date"><div class="tt-event-day">{{ e.day }}</div><div class="tt-event-mon">{{ e.month }}</div></div>
                            <div class="flex-grow-1">
                                <div class="font-weight-medium">{{ e.name }}</div>
                                <div class="tt-sub">Expected {{ money(e.expected) }} · {{ e.uplift }}% uplift</div>
                            </div>
                            <span class="tt-risk-chip" :class="e.status === 'At Risk' ? 'high' : 'ok'"><span class="tt-dot-sm"></span>{{ e.status }}</span>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";

// ── Palette (same as dashboard) ──
const BLUE = "#2f5be7";
const GREEN = "#0f9d6b";
const PURPLE = "#7c3aed";
const TEAL = "#0f766e";
const AMBER = "#f59e0b";
const PINK = "#ec4899";
const RED = "#dc2626";
const COLORS = [BLUE, GREEN, PURPLE, TEAL, AMBER, PINK];
const FONT = "Poppins, Segoe UI, sans-serif";

const shortMoney = (v) => (v >= 100000 ? (v / 100000).toFixed(1).replace(".0", "") + "L" : v >= 1000 ? Math.round(v / 1000) + "k" : v);
const fullMoney = (v) => "৳ " + Number(Math.round(v)).toLocaleString("en-IN");

const seeded = (seed) => {
    let s = seed;
    return () => ((s = (s * 9301 + 49297) % 233280) / 233280);
};
const makeSeries = (n, base, spread, seed) => {
    const r = seeded(seed);
    return Array.from({ length: n }, (_, i) => Math.round(base * (1 + i * 0.02) + (r() - 0.5) * spread));
};
const monthShort = (offset) => new Date(new Date().getFullYear(), new Date().getMonth() + offset, 1).toLocaleDateString("en-US", { month: "short" });

export default {
    name: "SalesAnalytics",
    components: { apexchart: VueApexCharts },

    data() {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const days30 = Array.from({ length: 30 }, (_, i) => `${i + 1} Sep`);
        const days7 = ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"];

        return {
            range: "12",
            GREEN,
            RED,
            aiRefreshedAgo: "6m ago",
            viewers: ["A", "R", "S"],
            extraViewers: 4,

            // ── Sample data: replace with API calls (e.g. GET /api/admin/sales-analytics?range=) ──
            datasets: {
                12: { labels: months, revenue: makeSeries(12, 520000, 160000, 7), prev: makeSeries(12, 450000, 150000, 17) },
                30: { labels: days30, revenue: makeSeries(30, 28000, 12000, 21), prev: makeSeries(30, 25000, 11000, 31) },
                7: { labels: days7, revenue: makeSeries(7, 31000, 14000, 3), prev: makeSeries(7, 28000, 13000, 13) },
            },
            targetPerPoint: { 12: 560000, 30: 31000, 7: 33000 },

            mtdRevenue: 960000,
            mtdTarget: 1200000,
            mtdSpark: [31, 40, 35, 50, 49, 60, 70, 91, 85],
            mom: 6.1,
            prevMonthRevenue: 874000,

            kpis: [
                { label: "Total orders", value: "318", change: 12.4, icon: "mdi-receipt-text-outline", color: BLUE, tint: "#e6edff", spark: [10, 22, 18, 30, 26, 41, 38, 52, 60] },
                { label: "Average order value", value: "৳ 3,020", change: 3.4, icon: "mdi-basket-outline", color: "#d97706", tint: "#fff1d1", spark: [28, 30, 29, 32, 31, 33, 34, 33, 36] },
                { label: "Conversion rate", value: "4.4%", change: 0.6, icon: "mdi-percent-outline", color: PINK, tint: "#fde6f1", spark: [4.1, 4.4, 4.3, 4.7, 4.6, 4.9, 5.0, 5.1, 5.2] },
                { label: "Gross margin", value: "34.8%", change: 1.2, icon: "mdi-chart-donut", color: GREEN, tint: "#dff5ec", spark: [31, 32, 32, 33, 33, 34, 34, 35, 35] },
                { label: "Cart abandonment", value: "71%", change: -2.3, inverse: true, icon: "mdi-cart-remove", color: RED, tint: "#fde8e8", spark: [78, 77, 76, 75, 74, 73, 72, 72, 71] },
                { label: "Repeat purchase rate", value: "68.4%", change: 2.7, icon: "mdi-repeat", color: PURPLE, tint: "#efe8ff", spark: [58, 60, 61, 63, 64, 65, 66, 67, 68] },
                { label: "Return rate", value: "2.1%", change: -0.4, inverse: true, icon: "mdi-backup-restore", color: "#ea580c", tint: "#ffe9dc", spark: [3.2, 3.0, 2.8, 2.9, 2.6, 2.4, 2.3, 2.2, 2.1] },
                { label: "Customer acquisition cost", value: "৳ 412", change: -5.1, inverse: true, icon: "mdi-account-plus-outline", color: TEAL, tint: "#dcf3f0", spark: [520, 500, 490, 470, 460, 440, 430, 420, 412] },
            ],

            // ── Funnel (rolling 30 days) ──
            funnelStages: [
                { name: "Store visitors", value: 184200, icon: "mdi-account-group-outline", color: "#0f766e" },
                { name: "Product views", value: 96400, icon: "mdi-eye-outline", color: "#0f9d6b" },
                { name: "Added to cart", value: 21800, icon: "mdi-cart-plus", color: "#d97706" },
                { name: "Checkout started", value: 11350, icon: "mdi-credit-card-outline", color: "#2f5be7" },
                { name: "Orders placed", value: 8120, icon: "mdi-check-circle-outline", color: "#7c3aed" },
                { name: "Delivered", value: 7690, icon: "mdi-truck-check-outline", color: "#0f9d6b" },
            ],

            // ── Journey ──
            journeyStages: [
                { name: "First visit", value: 184200, icon: "mdi-cursor-default-click-outline", bg: "#e6edff", color: BLUE },
                { name: "Added to cart", value: 21800, icon: "mdi-cart-outline", bg: "#dff3ef", color: TEAL },
                { name: "Checkout", value: 11350, icon: "mdi-credit-card-outline", bg: "#fdf1cf", color: "#b45309" },
                { name: "Paid order", value: 8120, icon: "mdi-cash-check", bg: "#fdf1cf", color: "#b45309" },
                { name: "Delivered", value: 7690, icon: "mdi-package-variant-closed-check", bg: "#cdeedf", color: GREEN },
                { name: "Repeat buyer", value: 2610, icon: "mdi-repeat", bg: "#ece6fb", color: PURPLE },
            ],

            // ── Channels ──
            channelDefs: [
                { name: "Website", share: 0.34, color: GREEN },
                { name: "Facebook Shop", share: 0.24, color: BLUE },
                { name: "Marketplace", share: 0.2, color: AMBER },
                { name: "Retail store", share: 0.14, color: PURPLE },
                { name: "WhatsApp / Phone", share: 0.08, color: PINK },
            ],

            // ── Categories ──
            categoryDefs: [
                { name: "Power banks & chargers", share: 0.31, avgPrice: 2900, margin: 31, growth: 24.5, icon: "mdi-battery-charging-high" },
                { name: "Earbuds & headphones", share: 0.22, avgPrice: 2400, margin: 38, growth: 14.2, icon: "mdi-headphones" },
                { name: "Smart watches", share: 0.16, avgPrice: 4200, margin: 29, growth: -3.1, icon: "mdi-watch-variant" },
                { name: "Cables & adapters", share: 0.12, avgPrice: 520, margin: 46, growth: 9.6, icon: "mdi-cable-data" },
                { name: "Gaming gear", share: 0.1, avgPrice: 3600, margin: 27, growth: 18.8, icon: "mdi-controller-classic-outline" },
                { name: "Networking", share: 0.09, avgPrice: 2700, margin: 25, growth: 4.0, icon: "mdi-router-wireless" },
            ],

            regionDefs: [
                { name: "Dhaka", share: 0.46 },
                { name: "Chattogram", share: 0.18 },
                { name: "Sylhet", share: 0.09 },
                { name: "Khulna", share: 0.08 },
                { name: "Rajshahi", share: 0.07 },
                { name: "Others", share: 0.12 },
            ],

            segments: [
                { name: "Retail", share: 41, rev: 33, color: GREEN },
                { name: "Resellers", share: 27, rev: 29, color: BLUE },
                { name: "Corporate", share: 19, rev: 26, color: AMBER },
                { name: "Marketplace", share: 13, rev: 12, color: "#9ca3af" },
            ],

            heatDays: ["Sat", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri"],
            heatHours: ["10–12", "12–2", "2–4", "4–6", "6–8", "8–10", "10–12a"],

            leaderboard: [
                { name: "Rafiq Ahmed", region: "Dhaka", revenue: 428000, orders: 142, color: GREEN },
                { name: "Nadia Islam", region: "Chattogram", revenue: 386000, orders: 118, color: BLUE },
                { name: "Tanvir Hasan", region: "Sylhet", revenue: 315000, orders: 97, color: AMBER },
                { name: "Sumaiya Akter", region: "Khulna", revenue: 268000, orders: 84, color: PURPLE },
                { name: "Imran Khan", region: "Rajshahi", revenue: 214000, orders: 66, color: PINK },
            ],

            atRisk: [
                { title: "Abandoned carts — ৳ 3.8L potential", desc: "1,240 carts abandoned in the last 24 hours", icon: "mdi-cart-remove", level: "high" },
                { title: "Pending payments — ৳ 1.4L", desc: "18 orders waiting on bKash / Nagad for 3+ days", icon: "mdi-cash-clock", level: "medium" },
                { title: "Delayed shipments — Khulna", desc: "14 parcels stuck for more than 4 days", icon: "mdi-truck-alert-outline", level: "high" },
                { title: "COD refusals — Rangpur", desc: "9 parcels returned this week · 11% refusal rate", icon: "mdi-package-variant-remove", level: "medium" },
                { title: "Pre-orders blocked by stock-out", desc: "6 pre-orders waiting on TP-Link Archer C6", icon: "mdi-archive-alert-outline", level: "medium" },
            ],

            orders: [
                { id: "#TT-10482", customer: "Rahim Traders", channel: "WhatsApp / Phone", agent: "Rafiq Ahmed", amount: 48500, date: "Oct 05, 2026", status: "Paid" },
                { id: "#TT-10481", customer: "Sabbir Hossain", channel: "Website", agent: "Nadia Islam", amount: 12900, date: "Oct 05, 2026", status: "COD pending" },
                { id: "#TT-10480", customer: "Dhaka Mobile Hub", channel: "Retail store", agent: "Rafiq Ahmed", amount: 186000, date: "Oct 04, 2026", status: "Paid" },
                { id: "#TT-10479", customer: "Nusrat Jahan", channel: "Facebook Shop", agent: "Tanvir Hasan", amount: 7400, date: "Oct 03, 2026", status: "Refunded" },
                { id: "#TT-10478", customer: "Gadget Point", channel: "Marketplace", agent: "Sumaiya Akter", amount: 23100, date: "Oct 02, 2026", status: "Payment due" },
            ],
            orderColor: { Paid: "success", "COD pending": "warning", "Payment due": "error", Refunded: "default" },

            topProducts: [
                { name: "Anker PowerCore 20000", brand: "Anker", category: "Power banks", units: 412, revenue: 352800, margin: 35, growth: 28.5, stock: "Low stock" },
                { name: "Baseus Bowie E9 Earbuds", brand: "Baseus", category: "Earbuds", units: 388, revenue: 312900, margin: 32, growth: 14.2, stock: "In stock" },
                { name: "Xiaomi Smart Band 8", brand: "Xiaomi", category: "Smart watches", units: 315, revenue: 236250, margin: 30, growth: -3.1, stock: "Low stock" },
                { name: "JBL Go 4 Speaker", brand: "JBL", category: "Speakers", units: 274, revenue: 219450, margin: 35, growth: 9.6, stock: "In stock" },
                { name: "Ugreen 65W Charger", brand: "Ugreen", category: "Chargers", units: 268, revenue: 201000, margin: 30, growth: -1.8, stock: "In stock" },
                { name: "TP-Link Archer C6", brand: "TP-Link", category: "Networking", units: 96, revenue: 86400, margin: 30, growth: 4.0, stock: "Out of stock" },
            ],
            stockColor: { "In stock": "success", "Low stock": "warning", "Out of stock": "error" },

            coupons: [
                { code: "EID25", uses: 412, revenue: 486000, discount: 48600, roi: 10 },
                { code: "WELCOME10", uses: 689, revenue: 312400, discount: 31240, roi: 10 },
                { code: "FREESHIP", uses: 534, revenue: 241800, discount: 40600, roi: 6 },
                { code: "BUNDLE15", uses: 148, revenue: 128900, discount: 19300, roi: 7 },
                { code: "FLASH30", uses: 96, revenue: 64200, discount: 27500, roi: 2 },
            ],

            opportunities: [
                { name: "Dhaka Mobile Hub — 200 power banks", stage: "Negotiation", prob: 92, value: 326000, color: GREEN },
                { name: "BRAC IT Wing — 40 smart watches", stage: "Quote review", prob: 88, value: 214000, color: GREEN },
                { name: "Pathao Office — 120 earbuds", stage: "Verbal commit", prob: 75, value: 189000, color: AMBER },
                { name: "Chattogram Gadget Zone — mixed", stage: "Sample sent", prob: 64, value: 142000, color: AMBER },
                { name: "Sylhet Digital World — accessories", stage: "Early discovery", prob: 58, value: 97000, color: RED },
            ],

            aiRecommendations: [
                { tag: "High Impact", tagClass: "impact", color: GREEN, icon: "mdi-target", text: "Power banks grew 24% this month — shift 15% more ad spend to this category while stock lasts." },
                { tag: "Action Needed", tagClass: "action", color: AMBER, icon: "mdi-alert-outline", text: "71% of carts are abandoned. Add bKash one-tap checkout and a free-shipping threshold nudge." },
                { tag: "Positive Trend", tagClass: "trend", color: BLUE, icon: "mdi-trending-up", text: "Earbuds buyers who add a case convert 2.4x more often — auto-suggest a bundle at checkout." },
            ],

            surveyCount: 4380,
            winTiles: [
                { label: "Conversion", value: "4.4%", color: GREEN },
                { label: "Cart abandonment", value: "71%", color: RED },
                { label: "Avg. order value", value: "৳ 3,020", color: BLUE },
                { label: "Avg. delivery", value: "2.3 days", color: PURPLE },
            ],
            buyReasons: [
                { label: "Official warranty & genuine products", pct: 38 },
                { label: "Competitive price vs market", pct: 31 },
                { label: "Fast delivery", pct: 17 },
                { label: "Easy returns & support", pct: 14 },
            ],
            leaveReasons: [
                { label: "High shipping cost", pct: 32 },
                { label: "Payment method issues", pct: 24 },
                { label: "Found cheaper elsewhere", pct: 21 },
                { label: "Slow delivery estimate", pct: 14 },
            ],

            events: [
                { name: "11.11 Mega Sale", day: "11", month: "Nov", expected: 420000, uplift: 62, status: "On Track" },
                { name: "Black Friday Gadget Week", day: "27", month: "Nov", expected: 510000, uplift: 78, status: "On Track" },
                { name: "Victory Day Deals", day: "16", month: "Dec", expected: 260000, uplift: 34, status: "At Risk" },
                { name: "Year-end Clearance", day: "26", month: "Dec", expected: 340000, uplift: 41, status: "On Track" },
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
        attainment() {
            return Math.round((this.mtdRevenue / this.mtdTarget) * 100);
        },
        overallConversion() {
            return Math.round((this.funnelStages[4].value / this.funnelStages[0].value) * 1000) / 10;
        },

        heroStats() {
            const rangeText = { 7: "Last 7 days", 30: "Last 30 days", 12: "Last 12 months" }[this.range];
            return [
                { icon: "mdi-cash-multiple", label: "Net revenue", value: this.money(this.totalRevenue), note: rangeText },
                { icon: "mdi-target", label: "Target attained", value: this.attainment + "%", note: `Goal ${this.money(this.mtdTarget)}` },
                { icon: "mdi-percent-outline", label: "Conversion rate", value: this.overallConversion + "%", note: "Visitors to paid orders" },
                { icon: "mdi-cart-outline", label: "Open order value", value: this.money(284000), note: "34 pending orders" },
            ];
        },

        velocityTiles() {
            return [
                { label: "Sales velocity", value: this.money(Math.round(this.mtdRevenue / 20)) + "/day", note: "↗ +12.8% vs last month", good: true, icon: "mdi-speedometer", color: TEAL, tint: "#dcf3f0" },
                { label: "Order-to-delivery", value: "2.3 days", note: "↘ -0.3 days faster", good: true, icon: "mdi-timer-outline", color: "#d97706", tint: "#fff1d1" },
                { label: "Open pipeline", value: this.money(284000), note: "34 pending orders", good: true, icon: "mdi-source-branch", color: PURPLE, tint: "#efe8ff" },
            ];
        },

        // ── Funnel ──
        funnel() {
            const first = this.funnelStages[0].value;
            return this.funnelStages.map((s, i) => {
                const pct = Math.round((s.value / first) * 1000) / 10;
                const prev = i ? this.funnelStages[i - 1].value : s.value;
                return { ...s, pct, drop: Math.round((1 - s.value / prev) * 1000) / 10, width: Math.max(pct, 26) };
            });
        },
        journey() {
            return this.journeyStages.map((s, i) => ({
                ...s,
                pctPrior: i ? Math.round((s.value / this.journeyStages[i - 1].value) * 100) : 100,
            }));
        },

        // ── Forecast ──
        forecast() {
            const projected = Math.round(this.mtdRevenue * 1.196);
            const gap = Math.max(this.mtdTarget - projected, 0);
            const pct = Math.round((projected / this.mtdTarget) * 1000) / 10;
            return {
                projected,
                confidence: 6.2,
                rows: [
                    { label: `${monthShort(-1)} (actual)`, value: this.prevMonthRevenue, color: GREEN },
                    { label: `${monthShort(0)} (projected)`, value: projected, color: GREEN },
                    { label: `${monthShort(1)} (projected)`, value: Math.round(projected * 1.08), color: AMBER },
                ],
                note: `At current velocity, this month lands at ${pct}% of target. Closing the 3 stalled bulk orders (${this.money(210000)} combined) would push it ${gap ? "past" : "further beyond"} target.`,
            };
        },

        // ── Trend (revenue vs target vs last period) ──
        trendSeries() {
            const t = this.targetPerPoint[this.range];
            return [
                { name: "Revenue", type: "area", data: this.current.revenue },
                { name: "Target", type: "line", data: this.current.revenue.map(() => t) },
                { name: "Previous period", type: "line", data: this.current.prev },
            ];
        },
        trendOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [GREEN, AMBER, "#9ca3af"],
                stroke: { width: [3, 2, 2], curve: "smooth", dashArray: [0, 6, 3] },
                fill: { type: ["gradient", "solid", "solid"], gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 95, 100] } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.current.labels, tickAmount: this.range === "30" ? 10 : undefined, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { shared: true, y: { formatter: fullMoney } },
            };
        },

        // ── Channels stacked ──
        channelSeries() {
            const r = seeded(77);
            return this.channelDefs.map((c) => ({
                name: c.name,
                data: this.current.revenue.map((v) => Math.round(v * c.share * (0.9 + r() * 0.2))),
            }));
        },
        channelOptions() {
            return {
                chart: { stacked: true, toolbar: { show: false }, fontFamily: FONT },
                colors: this.channelDefs.map((c) => c.color),
                plotOptions: { bar: { borderRadius: 3, columnWidth: "62%" } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.current.labels, tickAmount: this.range === "30" ? 8 : undefined, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "bottom", fontSize: "12px" },
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        // ── Heatmap ──
        heatMatrix() {
            const r = seeded(42);
            const hourWeight = [0.5, 0.8, 0.9, 1.0, 1.5, 1.9, 0.9];
            const dayWeight = [1.15, 1.0, 0.85, 0.85, 0.95, 1.1, 1.3];
            return this.heatDays.map((_, di) => this.heatHours.map((_, hi) => Math.round(2 + r() * 8 * hourWeight[hi] * dayWeight[di] + (hi === 5 ? 6 : 0))));
        },
        heatStats() {
            let best = { v: -1, d: 0, h: 0 };
            let busiest = { sum: -1, d: 0 };
            this.heatMatrix.forEach((row, di) => {
                const sum = row.reduce((a, b) => a + b, 0);
                if (sum > busiest.sum) busiest = { sum, d: di };
                row.forEach((v, hi) => {
                    if (v > best.v) best = { v, d: di, h: hi };
                });
            });
            const total = this.heatMatrix.flat().reduce((a, b) => a + b, 0);
            return {
                peak: `${this.heatDays[best.d]}, ${this.heatHours[best.h]}`,
                busiestDay: this.heatDays[busiest.d],
                perWeek: Math.round((total * 10) / 4).toLocaleString("en-IN"),
            };
        },

        // ── Segments ──
        segmentOptions() {
            return {
                chart: { fontFamily: FONT },
                labels: this.segments.map((s) => s.name),
                colors: this.segments.map((s) => s.color),
                legend: { show: false },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ["#fff"] },
                tooltip: { y: { formatter: (v) => v + "% of customers" } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: "68%",
                            labels: {
                                show: true,
                                name: { fontSize: "11px" },
                                value: { fontSize: "16px", fontWeight: 700, formatter: (v) => v + "%" },
                                total: { show: true, label: "Customers", fontSize: "11px", formatter: () => "12.4K" },
                            },
                        },
                    },
                },
            };
        },
        segmentNote() {
            const top = [...this.segments].sort((a, b) => b.rev - a.rev)[1];
            const c = this.segments.find((s) => s.name === "Corporate");
            return `${c.name} accounts bring ${c.rev}% of revenue from just ${c.share}% of customers. ${top.name} is the second-biggest revenue source.`;
        },

        // ── Categories ──
        categoryRows() {
            return this.categoryDefs.map((c, i) => {
                const revenue = Math.round(this.totalRevenue * c.share);
                return { ...c, revenue, units: Math.round(revenue / c.avgPrice), share: Math.round(c.share * 100), color: COLORS[i % COLORS.length] };
            });
        },

        // ── Regions ──
        regionSeries() {
            return [{ name: "Revenue", data: this.regionDefs.map((r) => Math.round(this.totalRevenue * r.share)) }];
        },
        regionOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: "62%", distributed: true } },
                legend: { show: false },
                dataLabels: { enabled: true, formatter: shortMoney, style: { fontSize: "12px" } },
                xaxis: { categories: this.regionDefs.map((r) => r.name), labels: { formatter: shortMoney } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                colors: COLORS,
                tooltip: { y: { formatter: fullMoney } },
            };
        },

        // ── Leaderboard ──
        podium() {
            const [a, b, c] = this.leaderboard;
            return [
                { ...b, rank: 2 },
                { ...a, rank: 1 },
                { ...c, rank: 3 },
            ];
        },
        leaderboardRest() {
            return this.leaderboard.slice(3);
        },

        // ── Streams ──
        streams() {
            const parts = [
                { name: "New customers", pct: 54, color: GREEN },
                { name: "Repeat customers", pct: 28, color: BLUE },
                { name: "B2B bulk orders", pct: 18, color: AMBER },
            ];
            return parts.map((p) => ({ ...p, value: Math.round((this.totalRevenue * p.pct) / 100) }));
        },
        streamNote() {
            return "Repeat revenue is expected to grow 24% next month as the 11.11 cohort returns for accessories.";
        },

        totalDiscount() {
            return this.coupons.reduce((a, c) => a + c.discount, 0);
        },
    },

    methods: {
        money: fullMoney,
        fmtNum(v) {
            return Number(v).toLocaleString("en-IN");
        },
        initials(name) {
            return name
                .split(" ")
                .map((w) => w[0])
                .slice(0, 2)
                .join("");
        },
        heatLevel(v) {
            return v >= 20 ? 4 : v >= 15 ? 3 : v >= 10 ? 2 : v >= 6 ? 1 : 0;
        },
        sparkOptions(color) {
            return {
                chart: { sparkline: { enabled: true } },
                stroke: { curve: "smooth", width: 2 },
                colors: [color],
                fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                tooltip: { enabled: false },
            };
        },
        exportReport() {
            // Hook up to your export endpoint, e.g. GET /api/admin/sales-analytics/export
            // eslint-disable-next-line no-console
            console.log("Export sales analytics report", { range: this.range });
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-dash {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    background: var(--bg);
    padding-bottom: 32px;
}

/* ───────── Hero ───────── */
.tt-hero {
    position: relative;
    background: linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b5f50 100%);
    color: #fff;
    border-radius: 14px;
    padding: 24px 24px 22px;
    overflow: hidden;
}
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.2; letter-spacing: -0.3px; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-pill-live { color: #6ee7b7; }
.tt-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }
.tt-viewers { display: flex; align-items: center; }
.tt-viewer-avatar { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; font-size: 11px; font-weight: 700; color: #053d35; background: #fff; border: 2px solid #0a5548; margin-left: -8px; }
.tt-viewer-avatar:first-child { margin-left: 0; }
.tt-viewer-extra { background: rgba(255, 255, 255, 0.18); color: #fff; border-color: rgba(255, 255, 255, 0.35); }
.tt-viewers-label { font-size: 12px; color: rgba(255, 255, 255, 0.7); }
.tt-date-pill { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.22); }
.tt-add { color: #0a5548 !important; font-weight: 600; }
.tt-ghost-btn { color: #fff !important; border-color: rgba(255, 255, 255, 0.4) !important; }
.tt-range { border-color: rgba(255, 255, 255, 0.35) !important; }
.tt-range .v-btn { height: 36px !important; color: #fff !important; font-size: 13px; }
.tt-range .v-btn--active { background: rgba(255, 255, 255, 0.18) !important; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 22px; }
.tt-hero-strip-4 { grid-template-columns: repeat(4, 1fr); }
.tt-hero-item { display: flex; align-items: flex-start; gap: 12px; padding: 4px 22px; border-left: 1px solid rgba(255, 255, 255, 0.14); }
.tt-hero-item:first-child { border-left: 0; padding-left: 0; }
.tt-hero-icon { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.2); flex: none; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.2; }
.tt-hero-label { font-size: 11px; font-weight: 500; letter-spacing: 0.4px; text-transform: uppercase; color: rgba(255, 255, 255, 0.75); }
.tt-hero-note { font-size: 11px; color: rgba(255, 255, 255, 0.55); }
.tt-hero-line { position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: linear-gradient(90deg, #ec4899, rgba(236, 72, 153, 0) 70%); }
@media (max-width: 959px) {
    .tt-hero-strip, .tt-hero-strip-4 { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .tt-hero-item:nth-child(3) { border-left: 0; padding-left: 0; }
}
@media (max-width: 599px) {
    .tt-hero-strip, .tt-hero-strip-4 { grid-template-columns: 1fr; }
    .tt-hero-item { border-left: 0; padding-left: 0; }
}

/* ───────── Sections & cards ───────── */
.tt-section { display: flex; align-items: center; gap: 8px; margin: 26px 0 12px; font-size: 12px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #3d5450; }
.tt-card { --accent: #2f5be7; --tint: #eaf0ff; background: #fff; border: 1px solid var(--line); border-radius: 10px !important; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04); overflow: hidden; }
.tt-blue { --accent: #2f5be7; --tint: #e9efff; }
.tt-green { --accent: #0f9d6b; --tint: #e3f6ee; }
.tt-purple { --accent: #7c3aed; --tint: #f0e9ff; }
.tt-teal { --accent: #0f766e; --tint: #ddf3f0; }
.tt-amber { --accent: #d97706; --tint: #fff2d6; }
.tt-head { display: flex; align-items: center; gap: 8px; padding: 11px 16px; background: linear-gradient(90deg, var(--tint), #fff); border-bottom: 1px solid var(--line); border-left: 3px solid var(--accent); }
.tt-head-wrap { flex-wrap: wrap; row-gap: 8px; }
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-dot-sm { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-tag { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; color: var(--accent); background: #fff; border: 1px solid var(--accent); display: inline-flex; align-items: center; gap: 3px; }
.tt-tag.warn { color: #8a5a00; background: #fdf1cf; border-color: #f3d78a; }
.tt-body { padding: 14px 18px 16px; }
.tt-sub { font-size: 12.5px; color: var(--muted); }
.tt-muted { color: var(--muted); }
.tt-note { font-size: 12.5px; line-height: 1.55; padding: 10px 12px; border-radius: 8px; background: linear-gradient(90deg, var(--tint), #fff); border-left: 3px solid var(--accent); }

/* ───────── Revenue summary strip ───────── */
.tt-revbar { display: grid; grid-template-columns: 1.4fr repeat(3, 1fr); }
.tt-revbar-main { padding: 16px 20px; }
.tt-revbar-tile { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-left: 1px solid var(--line); }
.tt-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; color: var(--muted); }
.tt-big { font-size: 30px; font-weight: 700; letter-spacing: -0.5px; line-height: 1.1; }
.tt-tile-value { font-size: 18px; font-weight: 700; }
.tt-tile-note { font-size: 11px; font-weight: 500; }
.tt-tile-note.good { color: #0f7a4d; }
.tt-tile-note.bad { color: #b3261e; }
@media (max-width: 1099px) { .tt-revbar { grid-template-columns: 1fr 1fr; } .tt-revbar-main { grid-column: 1 / -1; } .tt-revbar-tile { border-top: 1px solid var(--line); } }
@media (max-width: 599px) { .tt-revbar { grid-template-columns: 1fr; } .tt-revbar-tile { border-left: 0; } }

/* ───────── KPI ───────── */
.tt-kpi { padding: 16px; }
.tt-kpi-icon { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-kpi-label { font-size: 12.5px; font-weight: 500; color: var(--muted); }
.tt-kpi-box { border: 1px solid var(--line); border-radius: 8px; padding: 8px 12px; background: #fff; }
.tt-kpi-value { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
.tt-delta { font-size: 11px; font-weight: 600; padding: 2px 10px 2px 6px; border-radius: 20px; display: inline-flex; align-items: center; gap: 2px; }
.tt-delta.up { background: #e0f5ea; color: #0f7a4d; border: 1px solid #b7e6cd; }
.tt-delta.down { background: #fde9e7; color: #b3261e; border: 1px solid #f5c2bd; }
.tt-op-bar { height: 6px; border-radius: 4px; background: #eef2f1; overflow: hidden; margin-bottom: 8px; }
.tt-op-bar-fill { height: 100%; border-radius: 4px; }
.mb-0 { margin-bottom: 0 !important; }

/* ───────── Funnel ───────── */
.tt-fn-row { display: flex; align-items: center; gap: 14px; }
.tt-fn-bar { display: flex; align-items: center; justify-content: space-between; min-height: 40px; padding: 0 14px; border-radius: 6px; color: #fff; font-size: 13.5px; font-weight: 600; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.15); }
.tt-fn-bar b { font-weight: 700; }
.tt-fn-pct { font-size: 12.5px; color: var(--muted); flex: none; }
.tt-drop { display: flex; align-items: center; gap: 3px; font-size: 11px; font-weight: 600; color: #b3261e; padding: 3px 0 3px 8px; }

/* ───────── Forecast ───────── */
.tt-fc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
.tt-fc-box { background: #f1f4f3; border-radius: 8px; padding: 10px 12px; }
.tt-fc-value { font-size: 20px; font-weight: 700; }
.tt-fc-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; font-size: 13px; }

/* ───────── Heatmap ───────── */
.tt-hm { display: grid; grid-template-columns: 36px repeat(7, 1fr); gap: 4px; }
.tt-hm-hour { font-size: 10.5px; color: var(--muted); text-align: center; }
.tt-hm-day { font-size: 11px; color: var(--muted); display: flex; align-items: center; }
.tt-hm-cell { height: 28px; border-radius: 4px; transition: transform 0.12s; }
.tt-hm-cell:hover { transform: scale(1.06); }
.lv0 { background: #d6e8e1; } .lv1 { background: #a9d5c4; } .lv2 { background: #3ea982; } .lv3 { background: #12805f; } .lv4 { background: #0b4f3e; }
.tt-legend { display: flex; align-items: center; gap: 4px; font-size: 11px; color: var(--muted); }
.tt-legend i { width: 11px; height: 11px; border-radius: 50%; display: inline-block; }
.tt-hm-stats { display: flex; flex-wrap: wrap; gap: 28px; margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--line); }
.tt-hm-stat { font-size: 15px; font-weight: 600; }

/* ───────── Segments ───────── */
.tt-seg-row { display: flex; align-items: center; justify-content: space-between; font-size: 13px; padding: 6px 0; }

/* ───────── Category table ───────── */
.tt-cat-mini { width: 26px; height: 26px; border-radius: 7px; display: grid; place-items: center; flex: none; }
.tt-growth-up { color: #0f7a4d; display: inline-flex; align-items: center; gap: 1px; font-weight: 600; }
.tt-growth-down { color: #b3261e; display: inline-flex; align-items: center; gap: 1px; font-weight: 600; }

/* ───────── Leaderboard ───────── */
.tt-podium { display: grid; grid-template-columns: repeat(3, 1fr); align-items: end; gap: 12px; margin-bottom: 16px; }
.tt-pod { display: flex; flex-direction: column; align-items: center; text-align: center; }
.tt-avatar { position: relative; width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center; color: #fff; font-weight: 700; font-size: 15px; border: 3px solid #fff; box-shadow: 0 0 0 2px #f3d78a; }
.tt-avatar.big { width: 64px; height: 64px; font-size: 18px; }
.tt-avatar.sm { width: 36px; height: 36px; font-size: 12px; box-shadow: none; border-width: 2px; }
.tt-rank { position: absolute; right: -4px; bottom: -4px; width: 20px; height: 20px; border-radius: 50%; background: #d97706; color: #fff; font-size: 11px; display: grid; place-items: center; border: 2px solid #fff; }
.tt-pod-name { font-size: 13px; font-weight: 600; margin-top: 8px; }
.tt-pod-amount { font-size: 15px; font-weight: 700; margin: 4px 0 8px; }
.tt-pod-amount.first { color: #d97706; font-size: 17px; }
.tt-pod-block { width: 100%; border-radius: 6px 6px 0 0; background: #f4eddc; }
.tt-pod-block.r1 { height: 62px; background: #fbe9c0; }
.tt-pod-block.r2 { height: 42px; }
.tt-pod-block.r3 { height: 30px; }
.tt-lb-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-top: 1px solid var(--line); font-size: 13.5px; }
.tt-lb-rank { width: 24px; height: 24px; border-radius: 50%; background: #eef2f1; display: grid; place-items: center; font-size: 11px; font-weight: 600; color: var(--muted); flex: none; }

/* ───────── At risk ───────── */
.tt-risk { display: flex; align-items: center; gap: 12px; padding: 10px 12px; margin-bottom: 8px; border-radius: 8px; background: #f4f1fa; font-size: 13.5px; }
.tt-risk-icon { width: 36px; height: 36px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.tt-risk-icon.high { background: #d9534f; }
.tt-risk-icon.medium { background: #e0a43a; }
.tt-risk-chip { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; flex: none; }
.tt-risk-chip .tt-dot-sm { background: currentColor; margin-right: 5px; width: 6px; height: 6px; }
.tt-risk-chip.high { color: #b3261e; background: #fde9e7; }
.tt-risk-chip.medium { color: #8a5a00; background: #fdf1cf; }
.tt-risk-chip.ok { color: #0f7a4d; background: #e0f5ea; }

/* ───────── Journey ───────── */
.tt-journey { display: grid; grid-template-columns: repeat(6, 1fr); }
.tt-jr { padding: 16px 12px 16px 28px; text-align: center; clip-path: polygon(0 0, calc(100% - 16px) 0, 100% 50%, calc(100% - 16px) 100%, 0 100%, 16px 50%); margin-left: -8px; }
.tt-jr.first { clip-path: polygon(0 0, calc(100% - 16px) 0, 100% 50%, calc(100% - 16px) 100%, 0 100%); margin-left: 0; padding-left: 12px; }
.tt-jr-name { font-size: 12.5px; font-weight: 500; margin-top: 4px; }
.tt-jr-value { font-size: 19px; font-weight: 700; }
.tt-jr-pct { font-size: 11px; color: var(--muted); }
@media (max-width: 959px) { .tt-journey { grid-template-columns: repeat(2, 1fr); gap: 8px; } .tt-jr, .tt-jr.first { clip-path: none; margin-left: 0; padding: 14px 10px; border-radius: 8px; } }

/* ───────── Tables ───────── */
.tt-table { background: transparent; }
.tt-table th { font-size: 11px !important; font-weight: 600 !important; letter-spacing: 0.4px; text-transform: uppercase; color: #3d5450 !important; background: #e9f0ee !important; white-space: nowrap; }
.tt-table td { font-size: 13.5px; }
.tt-table tbody tr:hover { background: #f6faf9; }

/* ───────── Coupons ───────── */
.tt-coupon { display: flex; align-items: center; gap: 12px; padding: 11px 18px; border-bottom: 1px solid var(--line); font-size: 13.5px; }
.tt-coupon:last-child { border-bottom: 0; }
.tt-coupon-code { font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 6px; border: 1px dashed #7c3aed; color: #7c3aed; background: #f6f0ff; min-width: 96px; text-align: center; }
.tt-roi { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }
.tt-roi.good { color: #0f7a4d; background: #e0f5ea; }
.tt-roi.mid { color: #8a5a00; background: #fdf1cf; }

/* ───────── Streams ───────── */
.tt-stack { display: flex; height: 10px; border-radius: 6px; overflow: hidden; gap: 2px; }
.tt-stream-row { display: flex; align-items: center; justify-content: space-between; padding: 9px 12px; margin-bottom: 6px; border-radius: 8px; background: #f1f4f3; font-size: 13px; }

/* ───────── Opportunities ───────── */
.tt-opp { display: flex; align-items: center; gap: 12px; padding: 10px 12px; margin-bottom: 8px; border-radius: 8px; background: #f7f3e9; font-size: 13.5px; }
.tt-ring { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; flex: none; position: relative; }
.tt-ring::before { content: ""; position: absolute; inset: 5px; border-radius: 50%; background: #f7f3e9; }
.tt-ring span { position: relative; font-size: 11px; font-weight: 700; }
.tt-stage { font-size: 11px; font-weight: 600; }

/* ───────── Recommendations ───────── */
.tt-rec { padding: 10px 12px; margin-bottom: 10px; border-radius: 8px; border-left: 3px solid var(--accent); background: linear-gradient(90deg, #f1f7f5, #fff); }
.tt-rec:last-child { margin-bottom: 0; }
.tt-rec.impact { border-left-color: #0f9d6b; }
.tt-rec.action { border-left-color: #d97706; }
.tt-rec.trend { border-left-color: #2f5be7; }
.tt-rec-text { font-size: 13px; line-height: 1.5; }
.tt-ai-tag { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.tt-ai-tag .tt-dot-sm { background: currentColor; margin-right: 6px; width: 6px; height: 6px; }
.tt-ai-tag.impact { color: #0f7a4d; background: #e0f5ea; }
.tt-ai-tag.trend { color: #1d4ed8; background: #e6edff; }
.tt-ai-tag.action { color: #8a5a00; background: #fdf1cf; }

/* ───────── Buying reasons ───────── */
.tt-win-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.tt-win-tile { background: #f1f4f3; border-radius: 8px; padding: 10px 12px; }
.tt-win-value { font-size: 20px; font-weight: 700; }
.tt-reason-title { font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
.tt-reason-title.good { color: #0f7a4d; }
.tt-reason-title.bad { color: #b3261e; }
.tt-reason-label { font-size: 13px; margin-bottom: 4px; }
@media (max-width: 599px) { .tt-win-grid { grid-template-columns: repeat(2, 1fr); } }

/* ───────── Events ───────── */
.tt-event { display: flex; align-items: center; gap: 12px; padding: 10px 12px; margin-bottom: 8px; border-radius: 8px; background: #f4f1fa; font-size: 13.5px; }
.tt-event-date { width: 44px; text-align: center; border-radius: 8px; background: #fff; border: 1px solid var(--line); padding: 4px 0; flex: none; }
.tt-event-day { font-size: 16px; font-weight: 700; line-height: 1.1; }
.tt-event-mon { font-size: 10px; font-weight: 600; text-transform: uppercase; color: var(--muted); }
</style>