<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-center ga-2 mb-2">
                <span class="tt-pill"><v-icon size="12">mdi-creation</v-icon> AI insights refreshed {{ aiRefreshedAgo }}</span>
                <span class="tt-pill tt-pill-live"><span class="tt-pill-dot"></span> Live · {{ domain }}</span>
            </div>

            <div class="d-flex flex-wrap align-start ga-3">
                <div>
                    <h1 class="tt-h1">SEO analytics</h1>
                    <div class="tt-hero-sub">Rankings · Organic traffic · Technical health · Backlinks · Competitors</div>
                </div>
                <v-spacer />
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-btn variant="outlined" class="text-none tt-ghost-btn" prepend-icon="mdi-download" @click="exportData">Export</v-btn>
                    <v-btn color="white" variant="flat" class="text-none tt-add" prepend-icon="mdi-creation" :loading="generating" @click="generateReport">Generate AI report</v-btn>
                    <span class="tt-date-pill"><v-icon size="14">mdi-clock</v-icon> As on {{ today }}</span>
                </div>
            </div>

            <div class="tt-hero-strip">
                <div v-for="h in heroStats" :key="h.label" class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                    <div class="flex-grow-1">
                        <div class="tt-hero-value">{{ h.value }}</div>
                        <div class="tt-hero-label">{{ h.label }}</div>
                        <div class="tt-hero-note">{{ h.note }}</div>
                    </div>
                    <span class="tt-live" :class="{ warn: h.warn }">{{ h.tag }}</span>
                </div>
            </div>
            <div class="tt-hero-line"></div>
        </header>

        <!-- ═════════ Search performance KPIs ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-chart-box-outline</v-icon><span>Search performance</span></div>
        <v-row dense>
            <v-col v-for="k in kpis" :key="k.label" cols="12" sm="6" md="4">
                <v-card flat class="tt-card tt-kpi">
                    <div class="d-flex align-center">
                        <div class="tt-kpi-icon" :style="{ background: k.tint }"><v-icon :icon="k.icon" size="20" :color="k.color" /></div>
                        <div class="tt-kpi-label ml-3">{{ k.label }}</div>
                    </div>
                    <div class="tt-kpi-box mt-3">
                        <div class="d-flex align-center justify-space-between">
                            <div class="tt-kpi-value" :style="{ color: k.color }">{{ k.value }}</div>
                            <apexchart type="area" width="110" height="42" :options="sparkOptions(k.color)" :series="[{ data: k.spark }]" />
                        </div>
                    </div>
                    <span class="tt-delta mt-3" :class="(k.change >= 0) !== !!k.inverse ? 'up' : 'down'">
                        <v-icon size="13">{{ k.change >= 0 ? "mdi-arrow-up" : "mdi-arrow-down" }}</v-icon>
                        {{ Math.abs(k.change) }}{{ k.unit || "%" }} vs last period
                    </span>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Site health & ranking movement ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-heart-pulse</v-icon><span>Site health &amp; ranking movement</span></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="3">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Site snapshot</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Last crawl {{ lastCrawl }}</div>
                        <div v-for="s in snapshot" :key="s.label" class="tt-snap-row">
                            <span class="tt-snap-icon" :style="{ background: s.tint }"><v-icon size="16" :color="s.color">{{ s.icon }}</v-icon></span>
                            <div class="flex-grow-1">
                                <div class="tt-sub">{{ s.label }}</div>
                                <div class="tt-snap-value">{{ s.value }}</div>
                            </div>
                            <span class="tt-delta-sm up">{{ s.delta }}</span>
                        </div>
                        <div class="tt-note mt-3"><v-icon size="14">mdi-creation</v-icon>{{ snapshotNote }}</div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">SEO health breakdown</span><v-spacer /><span class="tt-tag">{{ healthLabel }}</span></div>
                    <div class="tt-body">
                        <div class="tt-sub">Composite score across all tracked signals</div>
                        <div class="d-flex align-end ga-2 mt-2">
                            <span class="tt-score">{{ healthScore }}</span><span class="tt-score-of">/ 100</span>
                            <v-spacer />
                            <span class="tt-delta up"><v-icon size="13">mdi-arrow-up</v-icon>+4.2 MoM</span>
                        </div>
                        <div class="tt-seg mt-3"><span class="tt-seg-marker" :style="{ left: healthScore + '%' }"></span></div>
                        <div class="tt-seg-scale"><span>0</span><span>50</span><span>75</span><span>100</span></div>
                        <div class="tt-health-tiles mt-3">
                            <div v-for="t in healthTiles" :key="t.label" class="tt-health-tile">
                                <div class="tt-sub"><span class="tt-dot-sm" :style="{ background: t.color }"></span>{{ t.label }}</div>
                                <div class="tt-health-value">{{ t.value }}</div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="5">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Weekly ranking movement</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Best → worst SERP position reached per day</div>
                        <apexchart type="rangeBar" height="290" :options="movementOptions" :series="[{ name: 'Position range', data: weeklyMovement }]" />
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ AI intelligence ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-creation</v-icon><span>AI SEO intelligence</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">AI SEO intelligence center</span><v-spacer /><span class="tt-sub">Synthesized from rankings, crawl, backlink &amp; competitor signals</span></div>
                    <div class="tt-body">
                        <div class="tt-rec-grid">
                            <div v-for="r in recs" :key="r.tag" class="tt-rec">
                                <div class="d-flex align-center justify-space-between mb-2">
                                    <span class="tt-ai-tag" :class="r.cls"><span class="tt-dot-sm"></span>{{ r.tag }}</span>
                                    <span class="tt-sub">{{ r.metric }}</span>
                                </div>
                                <p class="tt-rec-text">{{ r.text }}</p>
                            </div>
                        </div>
                        <v-btn block color="#0f9d6b" variant="flat" class="text-none tt-submit-btn mt-3" prepend-icon="mdi-creation" :loading="generating" @click="generateReport">
                            Generate full AI report
                        </v-btn>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">90-day growth forecast</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">AI-projected organic sessions if current recommendations are actioned</div>
                        <apexchart type="line" height="210" :options="forecastOptions" :series="forecastSeries" />
                        <div class="tt-mini-tiles mt-2">
                            <div class="tt-mini-tile"><div class="tt-sub">Projected sessions</div><div class="tt-mini-value">{{ forecastEnd }}K/mo</div></div>
                            <div class="tt-mini-tile"><div class="tt-sub">Confidence</div><div class="tt-mini-value">High</div></div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Traffic & search intent ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-chart-timeline-variant</v-icon><span>Organic traffic &amp; search intent</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Organic traffic intelligence</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Sessions by channel (thousands), last 6 months</div>
                        <apexchart type="bar" height="320" :options="trafficOptions" :series="trafficSeries" />
                        <div class="tt-note mt-2"><v-icon size="14">mdi-creation</v-icon>Organic search now drives {{ organicShare }}% of sessions — Facebook and social still send a large share, so protecting organic rankings reduces ad dependence.</div>
                    </div>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Search intent funnel</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-3">{{ keywordsTracked }} keywords ranked, grouped by intent</div>
                        <template v-for="(f, i) in intent" :key="f.label">
                            <div class="tt-funnel-block" :class="f.cls" :style="{ width: 100 - i * 14 + '%' }">{{ f.label }} · {{ f.pct }}% · {{ f.count.toLocaleString("en-IN") }}</div>
                            <div v-if="i < intent.length - 1" class="text-center"><v-icon size="16" color="#9db3ae">mdi-chevron-down</v-icon></div>
                        </template>
                        <div class="tt-note mt-3"><v-icon size="14">mdi-creation</v-icon>Transactional intent grew +3.2 pts WoW — your closest-to-purchase segment.</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ SERP, technical, CWV ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-speedometer</v-icon><span>SERP visibility &amp; technical health</span></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">SERP visibility</span></div>
                    <div class="tt-body">
                        <div class="tt-chip-list">
                            <span v-for="f in serpFeatures" :key="f.label" class="tt-serp-chip" :style="{ color: f.color, background: f.color + '14', borderColor: f.color + '40' }">
                                <v-icon size="13">{{ f.icon }}</v-icon>{{ f.label }} · {{ f.count }}
                            </span>
                        </div>
                        <div class="tt-field-label mt-4 mb-1">Feature placement trend</div>
                        <apexchart type="area" height="130" :options="featureTrendOptions" :series="[{ name: 'Rich features', data: featureTrend }]" />
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Technical SEO health</span><v-spacer /><span class="tt-tag warn">{{ techTotal }} issues</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Latest crawl · 14,860 pages scanned</div>
                        <div v-for="t in techIssues" :key="t.label" class="tt-tech-row">
                            <span class="tt-tech-icon" :style="{ background: sevColor[t.sev].tint }"><v-icon size="16" :color="sevColor[t.sev].color">{{ t.icon }}</v-icon></span>
                            <div class="flex-grow-1">
                                <div class="tt-tech-label">{{ t.label }}</div>
                                <div class="tt-op-bar"><div class="tt-op-bar-fill" :style="{ width: (t.count / techMax) * 100 + '%', background: sevColor[t.sev].color }"></div></div>
                            </div>
                            <span class="font-weight-medium">{{ t.count }}</span>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Core Web Vitals</span><v-spacer />
                        <v-btn-toggle v-model="device" mandatory density="compact" variant="outlined" divided>
                            <v-btn value="mobile" size="x-small" class="text-none">Mobile</v-btn>
                            <v-btn value="desktop" size="x-small" class="text-none">Desktop</v-btn>
                        </v-btn-toggle>
                    </div>
                    <div class="tt-body">
                        <div class="tt-cwv-grid">
                            <div v-for="m in cwvList" :key="m.label" class="tt-cwv">
                                <div class="tt-ring" :style="{ background: `conic-gradient(${statusColor[m.status]} ${m.pct}%, #eaf0ee 0)` }">
                                    <div class="tt-ring-inner">{{ m.value }}</div>
                                </div>
                                <div class="tt-cwv-label">{{ m.label }}</div>
                                <span class="tt-cwv-status" :style="{ color: statusColor[m.status] }"><span class="tt-dot-sm" :style="{ background: statusColor[m.status] }"></span>{{ m.status }}</span>
                            </div>
                        </div>
                        <div class="tt-note mt-3"><v-icon size="14">mdi-cellphone</v-icon>Most of your shoppers browse on mobile — prioritise the mobile column.</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Gadget search demand, schema, local ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-cellphone-link</v-icon><span>Gadget search demand, rich results &amp; local presence</span></div>
        <v-row dense>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Search demand by brand</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-2">Monthly searches vs. clicks captured by your site</div>
                        <apexchart type="bar" height="300" :options="brandOptions" :series="brandSeries" />
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" md="7" lg="4">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Product rich results</span><v-spacer /><span class="tt-sub">Structured data coverage</span></div>
                    <EasyDataTable :headers="schemaHeaders" :items="schemaRows" table-class-name="customize-table" hide-footer :rows-per-page="10">
                        <template #item-type="r"><span class="font-weight-medium">{{ r.type }}</span></template>
                        <template #item-warnings="r"><span :class="r.warnings ? 'tt-warn-text' : 'tt-muted'">{{ r.warnings }}</span></template>
                        <template #item-errors="r"><span :class="r.errors ? 'tt-err-text' : 'tt-muted'">{{ r.errors }}</span></template>
                    </EasyDataTable>
                </v-card>
            </v-col>

            <v-col cols="12" md="5" lg="4">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Local &amp; showroom presence</span></div>
                    <div class="tt-body">
                        <div class="tt-sub mb-3">Google Business Profile · walk-in &amp; store pickup demand</div>
                        <div class="tt-local-grid">
                            <div v-for="l in local" :key="l.label" class="tt-local-tile">
                                <v-icon size="18" color="#d97706">{{ l.icon }}</v-icon>
                                <div class="tt-health-value">{{ l.value }}</div>
                                <div class="tt-sub">{{ l.label }}</div>
                                <span class="tt-delta-sm up">{{ l.delta }}</span>
                            </div>
                        </div>
                        <div class="tt-rating-row mt-3">
                            <v-icon v-for="n in 5" :key="n" size="16" :color="n <= Math.round(localRating.score) ? '#f59e0b' : '#e8e0cf'">mdi-star</v-icon>
                            <span class="font-weight-medium ml-1">{{ localRating.score }}</span>
                            <span class="tt-sub">({{ localRating.reviews.toLocaleString("en-IN") }} reviews)</span>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Backlinks & competitors ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-link-variant</v-icon><span>Backlinks &amp; competitors</span></div>
        <v-row dense>
            <v-col cols="12" lg="5">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Backlink intelligence</span><v-spacer /><span class="tt-tag warn">{{ toxicLinks }} toxic</span></div>
                    <div class="tt-body">
                        <div class="tt-mini-tiles tt-mini-tiles--3">
                            <div v-for="b in backlinkTiles" :key="b.label" class="tt-mini-tile">
                                <div class="tt-sub">{{ b.label }}</div>
                                <div class="tt-mini-value" :class="b.cls">{{ b.value }}</div>
                            </div>
                        </div>
                        <div class="tt-sub mt-3">Referring domains, last 6 months · {{ refDomains[refDomains.length - 1].toLocaleString("en-IN") }} total</div>
                        <apexchart type="area" height="150" :options="refDomainOptions" :series="[{ name: 'Referring domains', data: refDomains }]" />
                        <div class="tt-field-label mt-3 mb-2">Anchor text distribution</div>
                        <div v-for="a in anchors" :key="a.label" class="tt-anchor-row">
                            <span class="tt-anchor-label"><span class="tt-dot-sm" :style="{ background: a.color }"></span>{{ a.label }}</span>
                            <div class="tt-op-bar flex-grow-1"><div class="tt-op-bar-fill" :style="{ width: a.pct + '%', background: a.color }"></div></div>
                            <span class="tt-anchor-pct">{{ a.pct }}%</span>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="7">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Competitor intelligence</span><v-spacer /><span class="tt-sub">{{ domain }} vs. top organic competitors</span></div>
                    <EasyDataTable :headers="competitorHeaders" :items="competitors" table-class-name="customize-table tt-table-teal" hide-footer :rows-per-page="10" :body-row-class-name="competitorRowClass">
                        <template #item-domain="c"><span class="font-weight-medium">{{ c.domain }}</span><span v-if="c.own" class="tt-you-tag">You</span></template>
                        <template #item-visibility="c">{{ c.visibility }}%</template>
                        <template #item-keywords="c">{{ c.keywords.toLocaleString("en-IN") }}</template>
                        <template #item-traffic="c">{{ kfmt(c.traffic) }}/mo</template>
                        <template #item-backlinks="c">{{ kfmt(c.backlinks) }}</template>
                    </EasyDataTable>
                    <div class="tt-body"><div class="tt-note"><v-icon size="14">mdi-creation</v-icon>{{ competitorInsight }}</div></div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Landing pages & opportunities ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-file-chart-outline</v-icon><span>Landing pages &amp; content opportunities</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Top organic landing pages</span><v-spacer /><v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/catalog">View catalog</v-btn></div>
                    <EasyDataTable :headers="landingHeaders" :items="landing" table-class-name="customize-table" buttons-pagination :rows-per-page="6">
                        <template #item-page="p"><span class="font-weight-medium">{{ p.page }}</span></template>
                        <template #item-sessions="p">{{ p.sessions.toLocaleString("en-IN") }}</template>
                        <template #item-bounce="p"><span :style="{ color: p.bounce < 35 ? '#0f7a4d' : p.bounce < 45 ? '#8a5a00' : '#b3261e', fontWeight: 600 }">{{ p.bounce }}%</span></template>
                        <template #item-revenue="p"><span class="font-weight-medium">{{ money(p.revenue) }}</span></template>
                    </EasyDataTable>
                </v-card>
            </v-col>
            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Content opportunities</span><v-spacer /><span class="tt-tag">Opportunity score</span></div>
                    <div class="tt-body">
                        <div v-for="(o, i) in opportunities" :key="o.kw" class="tt-opp-row">
                            <div class="d-flex align-center ga-2">
                                <span class="tt-opp-rank">{{ i + 1 }}</span>
                                <span class="font-weight-medium tt-opp-kw">{{ o.kw }}</span>
                                <v-spacer />
                                <span class="font-weight-bold">{{ o.score }}</span>
                            </div>
                            <div class="tt-op-bar mt-1"><div class="tt-op-bar-fill" :style="{ width: o.score + '%', background: o.score >= 85 ? GREEN : BLUE }"></div></div>
                            <div class="tt-sub">Vol. {{ o.vol.toLocaleString("en-IN") }} · Difficulty {{ o.diff }} · Content gap</div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Alerts, timeline, tasks ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-bell-alert-outline</v-icon><span>Alerts, activity &amp; tasks</span></div>
        <v-row dense>
            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">AI SEO alerts</span><v-spacer /><span class="tt-tag warn">{{ criticalCount }} critical</span></div>
                    <div class="tt-body">
                        <div v-for="a in alerts" :key="a.title" class="tt-alert" :class="a.level">
                            <div class="font-weight-medium">{{ a.title }}</div>
                            <div class="tt-sub">{{ a.meta }}</div>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" md="6" lg="4">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">SEO activity timeline</span></div>
                    <div class="tt-body">
                        <div v-for="e in timeline" :key="e.time" class="tt-tl-item">
                            <span class="tt-tl-dot" :style="{ background: tlColor[e.type] }"></span>
                            <div>
                                <div class="tt-sub">{{ e.time }}</div>
                                <div class="tt-tl-text">{{ e.text }}</div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">SEO tasks</span><v-spacer /><span class="tt-tag">{{ doneTasks }} / {{ tasks.length }} done</span></div>
                    <div class="tt-body">
                        <div v-for="t in tasks" :key="t.label" class="tt-task-row">
                            <v-checkbox-btn v-model="t.done" color="#0f9d6b" density="compact" />
                            <span :class="{ 'tt-task-done': t.done }">{{ t.label }}</span>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Keyword rankings ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-table</v-icon><span>Keyword rankings</span></div>
        <v-row dense>
            <v-col cols="12">
                <v-card flat class="tt-card tt-blue">
                    <div class="tt-head tt-head-wrap">
                        <span class="tt-dot"></span><span class="tt-card-title">{{ filteredKeywords.length }} tracked keywords</span>
                        <v-spacer />
                        <v-select v-model="intentFilter" :items="intentOptions" density="compact" variant="outlined" hide-details style="max-width: 170px" class="mr-2" />
                        <v-text-field v-model="kwSearch" density="compact" variant="outlined" hide-details placeholder="Search keyword or URL" prepend-inner-icon="mdi-magnify" style="max-width: 250px" />
                    </div>
                    <EasyDataTable :headers="keywordHeaders" :items="filteredKeywords" table-class-name="customize-table" buttons-pagination :rows-per-page="8" :search-value="kwSearch" empty-message="No keywords match your filters.">
                        <template #item-keyword="k"><span class="font-weight-medium">{{ k.keyword }}</span></template>
                        <template #item-volume="k">{{ k.volume.toLocaleString("en-IN") }}</template>
                        <template #item-difficulty="k">
                            <span class="tt-diff"><span class="tt-dot-sm" :style="{ background: k.difficulty < 40 ? GREEN : k.difficulty < 65 ? AMBER : RED }"></span>{{ k.difficulty }}</span>
                        </template>
                        <template #item-intent="k"><span class="tt-intent" :class="k.intent.toLowerCase()">{{ k.intent }}</span></template>
                        <template #item-rank="k"><span class="font-weight-bold">{{ k.rank }}</span></template>
                        <template #item-delta="k">
                            <span v-if="k.delta > 0" class="tt-growth-up"><v-icon size="12">mdi-arrow-up</v-icon>{{ k.delta }}</span>
                            <span v-else-if="k.delta < 0" class="tt-growth-down"><v-icon size="12">mdi-arrow-down</v-icon>{{ Math.abs(k.delta) }}</span>
                            <span v-else class="tt-muted">— 0</span>
                        </template>
                        <template #item-url="k"><span class="tt-muted">{{ k.url }}</span></template>
                    </EasyDataTable>
                </v-card>
            </v-col>
        </v-row>

        <v-snackbar v-model="snackbar" color="#0f9d6b" timeout="4000">{{ snackbarText }}</v-snackbar>
    </div>
</template>

<script>
import VueApexCharts from "vue3-apexcharts";

// ── Palette (same as the rest of the admin) ──
const BLUE = "#2f5be7";
const GREEN = "#0f9d6b";
const PURPLE = "#7c3aed";
const TEAL = "#0f766e";
const AMBER = "#f59e0b";
const PINK = "#ec4899";
const RED = "#dc2626";
const FONT = "Poppins, Segoe UI, sans-serif";

const fullMoney = (v) => "৳ " + Number(v).toLocaleString("en-IN");
const kfmt = (v) => (v >= 1e6 ? (v / 1e6).toFixed(2).replace(/\.?0+$/, "") + "M" : v >= 1000 ? (v / 1000).toFixed(1).replace(/\.0$/, "") + "K" : String(v));

export default {
    name: "SeoAnalytics",
    components: { apexchart: VueApexCharts },

    data() {
        const months6 = ["Apr", "May", "Jun", "Jul", "Aug", "Sep"];
        const months9 = ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        return {
            GREEN, BLUE, AMBER, RED,
            domain: "tanjiltraders.com",
            aiRefreshedAgo: "6m ago",
            lastCrawl: "2 hours ago",
            generating: false,
            snackbar: false,
            snackbarText: "",
            device: "mobile",
            kwSearch: "",
            intentFilter: "All intents",
            months6,
            months9,

            // ── Sample data: replace with Search Console / crawler / backlink API calls ──
            kpis: [
                { label: "Organic clicks", value: "48.2K", change: 12.4, icon: "mdi-cursor-default-click-outline", color: GREEN, tint: "#dff5ec", spark: [31, 35, 33, 40, 44, 47, 52, 58, 61] },
                { label: "Impressions", value: "1.42M", change: 9.1, icon: "mdi-eye-outline", color: BLUE, tint: "#e6edff", spark: [40, 42, 41, 46, 48, 51, 50, 55, 58] },
                { label: "Average CTR", value: "3.4%", change: 0.3, unit: " pts", icon: "mdi-percent-outline", color: PURPLE, tint: "#efe8ff", spark: [2.8, 2.9, 3.0, 3.0, 3.1, 3.2, 3.2, 3.3, 3.4] },
                { label: "Average position", value: "11.8", change: -1.6, unit: "", inverse: true, icon: "mdi-chart-line-variant", color: TEAL, tint: "#dcf3f0", spark: [15, 14.6, 14, 13.4, 13, 12.6, 12.3, 12, 11.8] },
                { label: "Organic orders", value: "1,284", change: 14.2, icon: "mdi-cart-outline", color: "#d97706", tint: "#fff1d1", spark: [18, 22, 21, 27, 30, 33, 36, 41, 44] },
                { label: "Organic revenue", value: "৳ 41,20,000", change: 16.8, icon: "mdi-cash-multiple", color: PINK, tint: "#fde6f1", spark: [22, 26, 25, 31, 35, 38, 43, 47, 52] },
            ],

            keywordsTracked: "21,870",
            snapshot: [
                { icon: "mdi-key-outline", label: "Keywords tracked", value: "21,870", delta: "+1,286", color: BLUE, tint: "#e6edff" },
                { icon: "mdi-file-search-outline", label: "Indexed pages", value: "14,860", delta: "+312", color: GREEN, tint: "#dff5ec" },
                { icon: "mdi-link-variant", label: "Referring domains", value: "3,180", delta: "+214", color: PURPLE, tint: "#efe8ff" },
                { icon: "mdi-chart-line-variant", label: "Avg. position", value: "11.8", delta: "−1.6", color: TEAL, tint: "#dcf3f0" },
            ],

            healthTiles: [
                { label: "Keywords", value: 90, color: GREEN },
                { label: "Content", value: 84, color: BLUE },
                { label: "Backlinks", value: 78, color: AMBER },
                { label: "Technical", value: 92, color: TEAL },
            ],

            weeklyMovement: [
                { x: "Mon", y: [4, 13] },
                { x: "Tue", y: [3, 8] },
                { x: "Wed", y: [3, 17] },
                { x: "Thu", y: [4, 12] },
                { x: "Fri", y: [6, 9] },
                { x: "Sat", y: [8, 18] },
                { x: "Sun", y: [5, 11] },
            ],

            recs: [
                { tag: "High impact", cls: "impact", metric: "Est. +8% traffic", text: "Add Product + FAQ schema to /category/smartphones — 4 competitors show price and stock rich results on overlapping queries." },
                { tag: "Action needed", cls: "action", metric: "CLS 0.12", text: "Fix render-blocking CSS and image sizing on /category/laptops — layout shift is suppressing 5 keywords from the top 5." },
                { tag: "Opportunity", cls: "trend", metric: "Vol. 2,400", text: "Publish a “samsung vs xiaomi phone comparison” guide — low competition with strong commercial intent for your phone category." },
                { tag: "Risk", cls: "risk", metric: "14 links", text: "Disavow 14 toxic backlinks from low-quality directory and spam sites flagged in this audit." },
            ],

            // organic sessions (thousands) per channel, last 6 months
            trafficSeries: [
                { name: "Organic", data: [168, 181, 196, 214, 231, 248] },
                { name: "Social", data: [74, 78, 81, 85, 88, 90] },
                { name: "Direct", data: [52, 55, 57, 60, 62, 66] },
                { name: "Referral", data: [18, 19, 21, 22, 24, 26] },
            ],
            forecastActual: [168, 181, 196, 214, 231, 248, null, null, null],
            forecastPredicted: [null, null, null, null, null, 248, 266, 283, 301],

            intent: [
                { label: "Informational", pct: 38, count: 8310, cls: "info" },
                { label: "Commercial", pct: 31, count: 6780, cls: "comm" },
                { label: "Transactional", pct: 22, count: 4810, cls: "trans" },
                { label: "Navigational", pct: 9, count: 1970, cls: "nav" },
            ],

            serpFeatures: [
                { icon: "mdi-tag-outline", label: "Product rich results", count: 412, color: GREEN },
                { icon: "mdi-star-outline", label: "Review stars", count: 286, color: "#d97706" },
                { icon: "mdi-help-circle-outline", label: "People Also Ask", count: 214, color: BLUE },
                { icon: "mdi-image-outline", label: "Image pack", count: 96, color: PURPLE },
                { icon: "mdi-play-circle-outline", label: "Video result", count: 38, color: RED },
                { icon: "mdi-map-marker-outline", label: "Local pack", count: 14, color: TEAL },
                { icon: "mdi-link-variant", label: "Sitelinks", count: 142, color: PINK },
            ],
            featureTrend: [212, 228, 241, 256, 270, 288],

            techIssues: [
                { icon: "mdi-link-variant-off", label: "Broken internal links", count: 21, sev: "high" },
                { icon: "mdi-package-variant-closed-remove", label: "Out-of-stock pages returning 404", count: 14, sev: "high" },
                { icon: "mdi-code-json", label: "Missing or invalid product schema", count: 17, sev: "med" },
                { icon: "mdi-image-off-outline", label: "Product images missing alt text", count: 38, sev: "med" },
                { icon: "mdi-content-copy", label: "Duplicate title tags (variants)", count: 12, sev: "low" },
                { icon: "mdi-speedometer-slow", label: "Slow pages (>3s on mobile)", count: 9, sev: "low" },
            ],
            sevColor: {
                high: { color: RED, tint: "#fde8e8" },
                med: { color: "#d97706", tint: "#fff2d6" },
                low: { color: TEAL, tint: "#dcf3f0" },
            },

            statusColor: { Good: GREEN, "Needs work": AMBER, Poor: RED },
            cwv: {
                mobile: [
                    { label: "LCP", value: "2.8s", status: "Needs work", pct: 62 },
                    { label: "INP", value: "190ms", status: "Good", pct: 88 },
                    { label: "CLS", value: "0.12", status: "Needs work", pct: 64 },
                    { label: "FCP", value: "1.7s", status: "Good", pct: 90 },
                    { label: "TTFB", value: "640ms", status: "Good", pct: 84 },
                    { label: "Speed Index", value: "3.9s", status: "Needs work", pct: 60 },
                ],
                desktop: [
                    { label: "LCP", value: "1.6s", status: "Good", pct: 94 },
                    { label: "INP", value: "90ms", status: "Good", pct: 96 },
                    { label: "CLS", value: "0.04", status: "Good", pct: 95 },
                    { label: "FCP", value: "1.1s", status: "Good", pct: 96 },
                    { label: "TTFB", value: "380ms", status: "Good", pct: 93 },
                    { label: "Speed Index", value: "2.2s", status: "Good", pct: 92 },
                ],
            },

            brandDemand: [
                { brand: "Samsung", searches: 18400, clicks: 2100 },
                { brand: "Xiaomi", searches: 16200, clicks: 2480 },
                { brand: "Apple", searches: 14900, clicks: 1320 },
                { brand: "Realme", searches: 11300, clicks: 1650 },
                { brand: "Oppo", searches: 8700, clicks: 740 },
                { brand: "Anker", searches: 4100, clicks: 690 },
            ],

            schemaRows: [
                { type: "Product", valid: 2104, warnings: 38, errors: 6 },
                { type: "Offer (price & stock)", valid: 2051, warnings: 62, errors: 11 },
                { type: "Review / Rating", valid: 1342, warnings: 24, errors: 0 },
                { type: "Breadcrumb", valid: 2146, warnings: 0, errors: 0 },
                { type: "FAQ", valid: 64, warnings: 3, errors: 0 },
                { type: "LocalBusiness", valid: 1, warnings: 0, errors: 0 },
            ],
            schemaHeaders: [
                { text: "Schema type", value: "type", sortable: true },
                { text: "Valid", value: "valid", sortable: true },
                { text: "Warn", value: "warnings", sortable: true },
                { text: "Errors", value: "errors", sortable: true },
            ],

            local: [
                { icon: "mdi-eye-outline", label: "Profile views", value: "18.4K", delta: "+9%" },
                { icon: "mdi-phone-outline", label: "Calls", value: "642", delta: "+6%" },
                { icon: "mdi-directions", label: "Direction requests", value: "1,128", delta: "+14%" },
                { icon: "mdi-web", label: "Website clicks", value: "2,940", delta: "+11%" },
            ],
            localRating: { score: 4.7, reviews: 1204 },

            toxicLinks: 14,
            backlinkTiles: [
                { label: "Total backlinks", value: "24,860" },
                { label: "New (30d)", value: "+1,420", cls: "up" },
                { label: "Lost (30d)", value: "−186", cls: "down" },
            ],
            refDomains: [2410, 2580, 2740, 2880, 3010, 3180],
            anchors: [
                { label: "Branded", pct: 41, color: GREEN },
                { label: "Exact match", pct: 17, color: BLUE },
                { label: "Partial match", pct: 14, color: PURPLE },
                { label: "Generic (“click here”)", pct: 18, color: AMBER },
                { label: "Naked URL", pct: 10, color: TEAL },
            ],

            // sample competitor figures — replace with your real tracked competitors
            competitors: [
                { domain: "tanjiltraders.com", visibility: 61.4, keywords: 21870, traffic: 248000, dr: 58, backlinks: 24860, own: true },
                { domain: "competitor-a.com.bd", visibility: 72.8, keywords: 29410, traffic: 341000, dr: 71, backlinks: 61200 },
                { domain: "competitor-b.com.bd", visibility: 55.3, keywords: 14280, traffic: 163000, dr: 52, backlinks: 19400 },
                { domain: "competitor-c.com.bd", visibility: 48.9, keywords: 11020, traffic: 118000, dr: 49, backlinks: 15200 },
                { domain: "competitor-d.com.bd", visibility: 37.2, keywords: 8140, traffic: 72000, dr: 41, backlinks: 9800 },
            ],
            competitorHeaders: [
                { text: "Domain", value: "domain", sortable: true, width: 190 },
                { text: "Visibility", value: "visibility", sortable: true },
                { text: "Keywords", value: "keywords", sortable: true },
                { text: "Est. traffic", value: "traffic", sortable: true },
                { text: "DR", value: "dr", sortable: true },
                { text: "Backlinks", value: "backlinks", sortable: true },
            ],

            landing: [
                { page: "/category/smartphones", sessions: 41200, bounce: 33.4, time: "3m 12s", orders: 312, revenue: 1248000 },
                { page: "/category/power-banks", sessions: 28900, bounce: 27.1, time: "2m 48s", orders: 268, revenue: 402000 },
                { page: "/category/wireless-earbuds", sessions: 24600, bounce: 30.8, time: "2m 30s", orders: 241, revenue: 361500 },
                { page: "/category/laptops", sessions: 19800, bounce: 38.2, time: "3m 40s", orders: 96, revenue: 1344000 },
                { page: "/blog/best-smartwatch-under-10000", sessions: 17200, bounce: 52.6, time: "1m 54s", orders: 38, revenue: 114000 },
                { page: "/product/anker-powercore-20000", sessions: 12400, bounce: 24.9, time: "2m 05s", orders: 187, revenue: 785400 },
            ],
            landingHeaders: [
                { text: "Page", value: "page", sortable: true, width: 230 },
                { text: "Sessions", value: "sessions", sortable: true },
                { text: "Bounce", value: "bounce", sortable: true },
                { text: "Avg. time", value: "time", sortable: false },
                { text: "Orders", value: "orders", sortable: true },
                { text: "Revenue", value: "revenue", sortable: true },
            ],

            opportunities: [
                { kw: "best smartphone under 30000 taka", score: 92, vol: 3800, diff: 22 },
                { kw: "wireless earbuds with anc under 5000", score: 86, vol: 2900, diff: 29 },
                { kw: "original power bank 20000mah price", score: 81, vol: 2100, diff: 16 },
                { kw: "smartwatch for kids with gps", score: 74, vol: 1700, diff: 19 },
            ],

            alerts: [
                { level: "critical", title: "Out-of-stock product pages returning 404", meta: "Flagged 2 hours ago · 14 URLs affected" },
                { level: "critical", title: "Mobile usability errors spiked", meta: "Flagged 5 hours ago · 29 pages" },
                { level: "warning", title: "Indexing coverage drop — 54 pages excluded", meta: "Flagged yesterday · noindex detected" },
                { level: "info", title: "New competitor entered top 5 for “smart watch price in bangladesh”", meta: "Flagged 2 days ago" },
            ],

            tlColor: { up: GREEN, down: RED, win: PURPLE, volatile: AMBER },
            timeline: [
                { time: "Oct 11 · 18:20", type: "up", text: "“wireless earbuds price in bangladesh” jumped 14 → 3" },
                { time: "Oct 10 · 09:12", type: "down", text: "“smart watch price in bangladesh” dropped 21 → 29" },
                { time: "Oct 9 · 14:30", type: "win", text: "Product rich result won for “power bank 20000mah price in bd”" },
                { time: "Oct 7 · 11:05", type: "volatile", text: "“laptop price in bangladesh 2026” volatile — swinging 8 → 15" },
            ],

            tasks: [
                { label: "Fix 404s on out-of-stock product pages", done: true },
                { label: "Add alt text to product images", done: true },
                { label: "Submit updated sitemap.xml", done: true },
                { label: "Compress hero banners on /category/smartphones", done: true },
                { label: "Consolidate duplicate title tags on variants", done: true },
                { label: "Add Product + Offer schema to remaining categories", done: false },
                { label: "Fix render-blocking CSS on /category/laptops", done: false },
                { label: "Request reviews for the top 10 best-sellers", done: false },
            ],

            intentOptions: ["All intents", "Informational", "Commercial", "Transactional", "Navigational"],
            keywords: [
                { keyword: "wireless earbuds price in bangladesh", volume: 22200, difficulty: 62, intent: "Commercial", rank: 3, delta: 11, url: "/category/wireless-earbuds" },
                { keyword: "power bank 20000mah price in bd", volume: 14800, difficulty: 48, intent: "Transactional", rank: 2, delta: 7, url: "/category/power-banks" },
                { keyword: "smart watch price in bangladesh", volume: 12100, difficulty: 74, intent: "Transactional", rank: 29, delta: -8, url: "/category/smart-watches" },
                { keyword: "best smartphone under 30000 taka", volume: 9900, difficulty: 68, intent: "Commercial", rank: 8, delta: 16, url: "/category/smartphones" },
                { keyword: "how to check imei number", volume: 18000, difficulty: 21, intent: "Informational", rank: 15, delta: 3, url: "/blog/check-imei" },
                { keyword: "gaming mouse price in bd", volume: 5400, difficulty: 38, intent: "Transactional", rank: 5, delta: 0, url: "/category/gaming" },
                { keyword: "samsung vs xiaomi phone comparison", volume: 6600, difficulty: 55, intent: "Commercial", rank: 21, delta: 19, url: "/blog/samsung-vs-xiaomi" },
                { keyword: "laptop price in bangladesh 2026", volume: 16200, difficulty: 81, intent: "Transactional", rank: 12, delta: -3, url: "/category/laptops" },
                { keyword: "tanjil traders", volume: 1900, difficulty: 4, intent: "Navigational", rank: 1, delta: 0, url: "/" },
                { keyword: "bluetooth speaker under 3000", volume: 4300, difficulty: 33, intent: "Commercial", rank: 6, delta: 4, url: "/category/speakers" },
                { keyword: "type c fast charger original", volume: 3100, difficulty: 27, intent: "Commercial", rank: 9, delta: 4, url: "/category/chargers" },
                { keyword: "tp link router price in bd", volume: 7700, difficulty: 44, intent: "Transactional", rank: 4, delta: 2, url: "/category/networking" },
            ],
            keywordHeaders: [
                { text: "Keyword", value: "keyword", sortable: true, width: 260 },
                { text: "Volume", value: "volume", sortable: true },
                { text: "Difficulty", value: "difficulty", sortable: true },
                { text: "Intent", value: "intent", sortable: true },
                { text: "Rank", value: "rank", sortable: true },
                { text: "Δ 7d", value: "delta", sortable: true },
                { text: "URL", value: "url", sortable: false },
            ],
        };
    },

    computed: {
        today() {
            return new Date().toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },

        healthScore() {
            return Math.round(this.healthTiles.reduce((a, t) => a + t.value, 0) / this.healthTiles.length);
        },
        healthLabel() {
            const s = this.healthScore;
            return s >= 90 ? "Excellent" : s >= 75 ? "Good" : s >= 50 ? "Fair" : "Poor";
        },
        criticalCount() {
            return this.alerts.filter((a) => a.level === "critical").length;
        },
        snapshotNote() {
            return this.criticalCount ? `Rankings are trending up, but ${this.criticalCount} critical alerts need attention today.` : "All metrics are trending up — no urgent action needed today.";
        },
        organicShare() {
            const last = this.trafficSeries.map((s) => s.data[s.data.length - 1]);
            return Math.round((last[0] / last.reduce((a, b) => a + b, 0)) * 1000) / 10;
        },
        forecastEnd() {
            return this.forecastPredicted[this.forecastPredicted.length - 1];
        },

        heroStats() {
            const organic = this.trafficSeries[0].data;
            const last = organic[organic.length - 1];
            const prev = organic[organic.length - 2];
            const growth = (((last - prev) / prev) * 100).toFixed(1);
            return [
                { icon: "mdi-magnify", value: last + "K", label: "Organic sessions", note: "Last month", tag: "+" + growth + "%" },
                { icon: "mdi-trophy-outline", value: "1,284", label: "Keywords in top 10", note: "+96 this month", tag: "Live" },
                { icon: "mdi-heart-pulse", value: this.healthScore + " / 100", label: "SEO health score", note: this.healthLabel, tag: this.criticalCount ? this.criticalCount + " alerts" : "Healthy", warn: this.criticalCount > 0 },
            ];
        },

        techTotal() {
            return this.techIssues.reduce((a, t) => a + t.count, 0);
        },
        techMax() {
            return Math.max(...this.techIssues.map((t) => t.count));
        },
        cwvList() {
            return this.cwv[this.device];
        },
        doneTasks() {
            return this.tasks.filter((t) => t.done).length;
        },

        filteredKeywords() {
            return this.keywords.filter((k) => this.intentFilter === "All intents" || k.intent === this.intentFilter);
        },

        competitorInsight() {
            const own = this.competitors.find((c) => c.own);
            const rivals = this.competitors.filter((c) => !c.own);
            const top = [...rivals].sort((a, b) => b.visibility - a.visibility)[0];
            const drLead = rivals.filter((r) => own.dr > r.dr).length;
            return `You trail ${top.domain} by ${(top.visibility - own.visibility).toFixed(1)} pts visibility and ${kfmt(top.backlinks - own.backlinks)} backlinks, but lead ${drLead} of ${rivals.length} rivals on Domain Rating.`;
        },

        /* ── Chart options ── */
        movementOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                colors: [BLUE],
                plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: "45%" } },
                xaxis: { min: 0, max: 30, tickAmount: 6, title: { text: "SERP position (lower is better)" } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (v) => "Position " + v } },
            };
        },
        forecastSeries() {
            return [
                { name: "Actual", data: this.forecastActual },
                { name: "Forecast", data: this.forecastPredicted },
            ];
        },
        forecastOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [GREEN, BLUE],
                stroke: { width: [3, 3], curve: "smooth", dashArray: [0, 6] },
                dataLabels: { enabled: false },
                markers: { size: 0 },
                xaxis: { categories: this.months9, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: (v) => v + "K" } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                annotations: { xaxis: [{ x: "Sep", borderColor: "#9fb6f0", strokeDashArray: 4, label: { text: "Today", style: { color: "#fff", background: BLUE, fontSize: "10px" } } }] },
                tooltip: { y: { formatter: (v) => (v == null ? "" : v + "K sessions") } },
            };
        },
        trafficOptions() {
            return {
                chart: { stacked: true, toolbar: { show: false }, fontFamily: FONT },
                colors: [GREEN, BLUE, PURPLE, AMBER],
                plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: "62%" } },
                stroke: { width: 1, colors: ["#fff"] },
                dataLabels: { enabled: false },
                xaxis: { categories: this.months6, labels: { formatter: (v) => v + "K" } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { y: { formatter: (v) => v + "K sessions" } },
            };
        },
        featureTrendOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [GREEN],
                stroke: { curve: "smooth", width: 2 },
                fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.months6, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
            };
        },
        brandSeries() {
            return [
                { name: "Monthly searches", data: this.brandDemand.map((b) => b.searches) },
                { name: "Clicks to your site", data: this.brandDemand.map((b) => b.clicks) },
            ];
        },
        brandOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT },
                colors: ["#b7c6f5", GREEN],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: "70%" } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.brandDemand.map((b) => b.brand), labels: { formatter: kfmt } },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
                legend: { position: "top", horizontalAlign: "right" },
                tooltip: { y: { formatter: (v) => v.toLocaleString("en-IN") } },
            };
        },
        refDomainOptions() {
            return {
                chart: { toolbar: { show: false }, fontFamily: FONT, zoom: { enabled: false } },
                colors: [AMBER],
                stroke: { curve: "smooth", width: 2 },
                fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
                dataLabels: { enabled: false },
                xaxis: { categories: this.months6, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { show: false },
                grid: { borderColor: "#e8eeec", strokeDashArray: 4 },
            };
        },
    },

    methods: {
        money: fullMoney,
        kfmt,

        sparkOptions(color) {
            return {
                chart: { sparkline: { enabled: true } },
                stroke: { curve: "smooth", width: 2 },
                colors: [color],
                fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                tooltip: { enabled: false },
            };
        },
        competitorRowClass(item) {
            return item.own ? "tt-row-own" : "";
        },

        async generateReport() {
            this.generating = true;
            try {
                // Hook this up to your real report endpoint, e.g. POST /api/admin/seo/report
                await new Promise((resolve) => setTimeout(resolve, 900));
                this.snackbarText = "AI SEO report generated — check your reports inbox.";
                this.snackbar = true;
            } finally {
                this.generating = false;
            }
        },
        exportData() {
            // Hook this up to your real export endpoint, e.g. GET /api/admin/seo/export
            this.snackbarText = "Export started.";
            this.snackbar = true;
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
.tt-hero { position: relative; background: linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b5f50 100%); color: #fff; border-radius: 14px; padding: 24px 24px 22px; overflow: hidden; }
.tt-h1 { font-size: 26px; font-weight: 700; line-height: 1.2; letter-spacing: -0.3px; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-pill-live { color: #6ee7b7; }
.tt-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }
.tt-date-pill { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.22); }
.tt-add { color: #0a5548 !important; font-weight: 600; }
.tt-ghost-btn { color: #fff !important; border-color: rgba(255, 255, 255, 0.4) !important; }
.tt-hero-strip { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 22px; }
.tt-hero-item { display: flex; align-items: flex-start; gap: 12px; padding: 4px 22px; border-left: 1px solid rgba(255, 255, 255, 0.14); }
.tt-hero-item:first-child { border-left: 0; padding-left: 0; }
.tt-hero-icon { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.2); flex: none; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.2; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.85); }
.tt-hero-note { font-size: 11px; color: rgba(255, 255, 255, 0.55); }
.tt-live { font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 12px; color: #6ee7b7; background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(110, 231, 183, 0.5); }
.tt-live.warn { color: #fca5a5; background: rgba(220, 38, 38, 0.14); border-color: rgba(252, 165, 165, 0.5); }
.tt-hero-line { position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: linear-gradient(90deg, #ec4899, rgba(236, 72, 153, 0) 70%); }
@media (max-width: 959px) { .tt-hero-strip { grid-template-columns: 1fr; gap: 14px; } .tt-hero-item { border-left: 0; padding-left: 0; } }

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
.tt-dot-sm { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-tag { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; color: var(--accent); background: #fff; border: 1px solid var(--accent); }
.tt-tag.warn { color: #8a5a00; background: #fdf1cf; border-color: #f3d78a; }
.tt-body { padding: 14px 18px 16px; }
.tt-sub { font-size: 12.5px; color: var(--muted); }
.tt-muted { color: var(--muted); }
.tt-field-label { font-size: 11.5px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #3d5450; }
.tt-warn-text { color: #8a5a00; font-weight: 600; }
.tt-err-text { color: #b3261e; font-weight: 600; }
.tt-note { display: flex; align-items: flex-start; gap: 6px; font-size: 12.5px; line-height: 1.5; color: #0f5a40; background: #f0faf5; border: 1px solid #cfeadd; border-left: 3px solid #0f9d6b; border-radius: 8px; padding: 9px 12px; }
.tt-note .v-icon { margin-top: 2px; color: #0f9d6b; }

/* ───────── KPI cards ───────── */
.tt-kpi { padding: 16px; }
.tt-kpi-icon { width: 36px; height: 36px; border-radius: 9px; display: grid; place-items: center; flex: none; }
.tt-kpi-label { font-size: 12.5px; font-weight: 500; color: var(--muted); }
.tt-kpi-box { border: 1px solid var(--line); border-radius: 8px; padding: 8px 12px; background: #fff; }
.tt-kpi-value { font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
.tt-delta { font-size: 11px; font-weight: 600; padding: 2px 10px 2px 6px; border-radius: 20px; display: inline-flex; align-items: center; gap: 2px; }
.tt-delta.up { background: #e0f5ea; color: #0f7a4d; border: 1px solid #b7e6cd; }
.tt-delta.down { background: #fde9e7; color: #b3261e; border: 1px solid #f5c2bd; }
.tt-delta-sm { font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 10px; }
.tt-delta-sm.up { background: #e0f5ea; color: #0f7a4d; }

/* ───────── Snapshot & health ───────── */
.tt-snap-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--line); }
.tt-snap-row:last-of-type { border-bottom: 0; }
.tt-snap-icon { width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.tt-snap-value { font-size: 16px; font-weight: 700; }
.tt-score { font-size: 46px; font-weight: 700; line-height: 1; letter-spacing: -1px; }
.tt-score-of { font-size: 14px; color: var(--muted); padding-bottom: 6px; }
.tt-seg { position: relative; height: 10px; border-radius: 6px; background: linear-gradient(90deg, #dc2626 0 25%, #f59e0b 25% 50%, #84cc16 50% 75%, #0f9d6b 75% 100%); }
.tt-seg-marker { position: absolute; top: 50%; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 3px solid var(--ink); transform: translate(-50%, -50%); }
.tt-seg-scale { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); margin-top: 4px; }
.tt-health-tiles { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.tt-health-tile { border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; background: #fbfdfc; }
.tt-health-value { font-size: 20px; font-weight: 700; }

/* ───────── AI recommendations ───────── */
.tt-rec-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.tt-rec { border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px; background: #fbfdfc; }
.tt-rec-text { font-size: 13px; line-height: 1.55; margin: 0; }
.tt-ai-tag { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.tt-ai-tag .tt-dot-sm { background: currentColor; }
.tt-ai-tag.impact { color: #0f7a4d; background: #e0f5ea; }
.tt-ai-tag.trend { color: #1d4ed8; background: #e6edff; }
.tt-ai-tag.action { color: #8a5a00; background: #fdf1cf; }
.tt-ai-tag.risk { color: #b3261e; background: #fde9e7; }
.tt-submit-btn { color: #fff !important; font-weight: 600; }
.tt-mini-tiles { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.tt-mini-tiles--3 { grid-template-columns: repeat(3, 1fr); }
.tt-mini-tile { border: 1px solid var(--line); border-radius: 8px; padding: 8px 12px; background: #fbfdfc; }
.tt-mini-value { font-size: 17px; font-weight: 700; }
.tt-mini-value.up { color: #0f7a4d; }
.tt-mini-value.down { color: #b3261e; }
@media (max-width: 767px) { .tt-rec-grid { grid-template-columns: 1fr; } }

/* ───────── Intent funnel ───────── */
.tt-funnel-block { margin: 0 auto; text-align: center; font-size: 12.5px; font-weight: 600; padding: 10px 8px; border-radius: 8px; border: 1px solid transparent; }
.tt-funnel-block.info { background: #e9efff; color: #1d4ed8; border-color: #cfdcff; }
.tt-funnel-block.comm { background: #ddf3f0; color: #0f766e; border-color: #bfe6e1; }
.tt-funnel-block.trans { background: #e0f5ea; color: #0f7a4d; border-color: #b7e6cd; }
.tt-funnel-block.nav { background: #fff2d6; color: #8a5a00; border-color: #f3d78a; }

/* ───────── SERP / technical / CWV ───────── */
.tt-chip-list { display: flex; flex-wrap: wrap; gap: 8px; }
.tt-serp-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 8px; border: 1px solid; }
.tt-tech-row { display: flex; align-items: center; gap: 10px; padding: 7px 0; }
.tt-tech-icon { width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; flex: none; }
.tt-tech-label { font-size: 12.5px; font-weight: 500; margin-bottom: 4px; }
.tt-op-bar { height: 6px; border-radius: 4px; background: #eef2f1; overflow: hidden; }
.tt-op-bar-fill { height: 100%; border-radius: 4px; }
.tt-cwv-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px 8px; }
.tt-cwv { text-align: center; }
.tt-ring { width: 68px; height: 68px; border-radius: 50%; margin: 0 auto; display: grid; place-items: center; }
.tt-ring-inner { width: 54px; height: 54px; border-radius: 50%; background: #fff; display: grid; place-items: center; font-size: 12px; font-weight: 700; }
.tt-cwv-label { font-size: 12px; font-weight: 600; margin-top: 6px; }
.tt-cwv-status { display: inline-flex; align-items: center; font-size: 10.5px; font-weight: 600; }

/* ───────── Local presence ───────── */
.tt-local-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.tt-local-tile { border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; background: #fbfdfc; display: flex; flex-direction: column; gap: 2px; align-items: flex-start; }
.tt-rating-row { display: flex; align-items: center; gap: 2px; border-top: 1px solid var(--line); padding-top: 12px; }

/* ───────── Backlinks ───────── */
.tt-anchor-row { display: flex; align-items: center; gap: 10px; padding: 4px 0; font-size: 12.5px; }
.tt-anchor-label { width: 150px; display: inline-flex; align-items: center; flex: none; }
.tt-anchor-pct { width: 36px; text-align: right; font-weight: 600; }
.tt-you-tag { margin-left: 8px; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 6px; background: #e0f5ea; color: #0f7a4d; }

/* ───────── Opportunities / alerts / timeline / tasks ───────── */
.tt-opp-row { padding: 10px 0; border-bottom: 1px solid var(--line); }
.tt-opp-row:last-child { border-bottom: 0; }
.tt-opp-rank { width: 22px; height: 22px; border-radius: 50%; background: #e3f6ee; color: #0f7a4d; font-size: 11px; font-weight: 700; display: grid; place-items: center; flex: none; }
.tt-opp-kw { font-size: 13px; }
.tt-alert { border-left: 3px solid; border-radius: 8px; padding: 9px 12px; margin-bottom: 10px; }
.tt-alert:last-child { margin-bottom: 0; }
.tt-alert.critical { border-color: #dc2626; background: #fdf0ee; }
.tt-alert.warning { border-color: #f59e0b; background: #fff8e8; }
.tt-alert.info { border-color: #2f5be7; background: #eff3ff; }
.tt-tl-item { display: flex; gap: 12px; padding: 8px 0; }
.tt-tl-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; flex: none; }
.tt-tl-text { font-size: 13px; line-height: 1.45; }
.tt-task-row { display: flex; align-items: center; gap: 6px; font-size: 13px; padding: 1px 0; }
.tt-task-done { text-decoration: line-through; color: var(--muted); }

/* ───────── Keyword table chips ───────── */
.tt-diff { display: inline-flex; align-items: center; font-weight: 600; }
.tt-intent { font-size: 11px; font-weight: 600; padding: 2px 9px; border-radius: 10px; }
.tt-intent.informational { background: #e9efff; color: #1d4ed8; }
.tt-intent.commercial { background: #ddf3f0; color: #0f766e; }
.tt-intent.transactional { background: #e0f5ea; color: #0f7a4d; }
.tt-intent.navigational { background: #fff2d6; color: #8a5a00; }
.tt-growth-up { color: #0f7a4d; display: inline-flex; align-items: center; gap: 1px; font-weight: 600; }
.tt-growth-down { color: #b3261e; display: inline-flex; align-items: center; gap: 1px; font-weight: 600; }

/* ───────── Tables (EasyDataTable) ───────── */
:deep(.customize-table) {
    --easy-table-border: 1px solid #e3eae8;
    --easy-table-row-border: 1px solid #e3eae8;
    --easy-table-header-font-size: 11px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #3d5450;
    --easy-table-header-background-color: #e9f0ee;
    --easy-table-body-row-height: 50px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #f6faf9;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
:deep(.customize-table .tt-row-own) { background: #f0faf5; }
</style>