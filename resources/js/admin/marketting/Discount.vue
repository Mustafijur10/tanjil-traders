<template>
    <v-slide-x-transition appear>
        <div class="tt-promo">
            <v-window v-model="tab">
                <!-- ═════════════════════════ Promotions list ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">Coupons &amp; Discounts</h1>
                            <div class="tt-sub">{{ promos.length }} promotions · {{ activeCount }} live · {{ codeCount }} codes · {{ autoCount }} automatic</div>
                        </div>
                        <v-spacer />
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-add-btn" prepend-icon="mdi-plus" @click="openAdd()">
                            Create Promotion
                        </v-btn>
                    </div>

                    <v-alert v-if="usingDemo && !demoHidden" variant="tonal" color="#e25311" density="compact" class="tt-demo mt-4" icon="mdi-information-outline" closable @click:close="demoHidden = true">
                        Showing sample promotions because <b>/api/promotions</b> returned nothing. Saving will call that endpoint once it exists.
                    </v-alert>

                    <!-- Summary cards -->
                    <v-row dense class="mt-2">
                        <v-col v-for="s in statCards" :key="s.label" cols="12" sm="6" lg="3">
                            <v-card flat class="tt-card tt-stat">
                                <div class="d-flex align-center ga-3">
                                    <div class="tt-stat-icon" :style="{ background: s.tint }"><v-icon size="20" :color="s.color">{{ s.icon }}</v-icon></div>
                                    <div style="min-width: 0">
                                        <div class="tt-stat-label">{{ s.label }}</div>
                                        <div class="tt-stat-value">{{ s.value }}</div>
                                    </div>
                                </div>
                                <div class="tt-sub mt-3">{{ s.note }}</div>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-row dense class="mt-1">
                        <v-col cols="12" lg="8">
                            <v-card flat class="tt-card">
                                <div class="tt-head tt-head-wrap">
                                    <span class="tt-dot"></span><span class="tt-card-title">All Promotions</span>
                                    <v-spacer />
                                    <v-btn-toggle v-model="methodFilter" mandatory density="comfortable" variant="outlined" divided class="mr-2 tt-seg-sm">
                                        <v-btn value="all" class="text-none">All</v-btn>
                                        <v-btn value="code" class="text-none">Codes</v-btn>
                                        <v-btn value="automatic" class="text-none">Automatic</v-btn>
                                    </v-btn-toggle>
                                    <v-select v-model="statusFilter" :items="statusFilters" variant="outlined" density="compact" hide-details style="max-width: 140px" class="mr-2" />
                                    <v-text-field v-model="searchValue" placeholder="Search…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details style="max-width: 200px" @click:clear="searchValue = ''" />
                                </div>

                                <v-card-text class="tt-body">
                                    <EasyDataTable
                                        :headers="headers"
                                        :items="tableItems"
                                        table-class-name="customize-table"
                                        buttons-pagination
                                        :rows-per-page="10"
                                        :fixedIndex="true"
                                        :loading="loading"
                                        :search-value="searchValue"
                                    >
                                        <template #item-title="item">
                                            <v-list-item class="tt-name-item" density="compact" min-height="52" @click="editItem(item)">
                                                <template #prepend>
                                                    <span class="tt-type-badge" :style="{ background: item.color }"><v-icon size="17" color="white">{{ item.icon }}</v-icon></span>
                                                </template>
                                                <v-list-item-title class="tt-name">{{ item.title }}</v-list-item-title>
                                                <v-list-item-subtitle class="text-caption">
                                                    <span v-if="item.method === 'code'" class="tt-code" @click.stop="copyCode(item.code)" title="Click to copy">{{ item.code }}<v-icon size="11">mdi-content-copy</v-icon></span>
                                                    <span v-else class="tt-auto-tag"><v-icon size="11">mdi-lightning-bolt</v-icon>Automatic</span>
                                                    <span class="ml-2">{{ item.audienceText }}</span>
                                                </v-list-item-subtitle>
                                            </v-list-item>
                                        </template>

                                        <template #item-valueText="item">
                                            <div class="font-weight-bold">{{ item.valueText }}</div>
                                            <div class="tt-sub">{{ item.scopeText }}</div>
                                        </template>

                                        <template #item-used="item">
                                            <div class="tt-usage">
                                                <div class="d-flex justify-space-between"><b>{{ item.used }}</b><span class="tt-sub">{{ item.usageLimit ? "of " + item.usageLimit : "unlimited" }}</span></div>
                                                <div v-if="item.usageLimit" class="tt-progress mt-1"><div class="tt-progress-fill" :style="{ width: Math.min(100, (item.used / item.usageLimit) * 100) + '%', background: item.used / item.usageLimit > 0.85 ? '#d97706' : '#0f9d6b' }"></div></div>
                                            </div>
                                        </template>

                                        <template #item-validityText="item">
                                            <div class="font-weight-medium">{{ item.validityText }}</div>
                                            <div class="tt-sub">{{ item.validityNote }}</div>
                                        </template>

                                        <template #item-statusLabel="item">
                                            <v-chip size="small" :color="statusColor(item.statusLabel)" variant="tonal" label>{{ item.statusLabel }}</v-chip>
                                        </template>

                                        <template #item-operation="item">
                                            <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click.prevent="editItem(item)" />
                                            <v-btn :icon="item.status === 'Active' ? 'mdi-pause-circle-outline' : 'mdi-play-circle-outline'" size="small" variant="text" :disabled="item.status === 'Draft'" @click="toggleStatus(item)" />
                                            <v-btn icon="mdi-content-copy" size="small" variant="text" @click="duplicatePromo(item)" />
                                            <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="removeOption(item)" />
                                        </template>

                                        <template #empty-message>
                                            <div class="tt-empty">No promotions match. Try another filter or create one.</div>
                                        </template>
                                    </EasyDataTable>

                                    <div class="tt-add-new" @click="openAdd()"><v-icon size="16">mdi-plus</v-icon> Create New Promotion</div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" lg="4">
                            <!-- Templates -->
                            <v-card flat class="tt-card mb-4">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Quick start</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-sub mb-3">Start from a ready-made offer for an electronics store, then adjust.</div>
                                    <div class="tt-tpl-grid">
                                        <button v-for="t in templates" :key="t.name" type="button" class="tt-tpl" @click="openAdd(t)">
                                            <span class="tt-tpl-ico" :style="{ background: t.tint }"><v-icon size="18" :color="t.color">{{ t.icon }}</v-icon></span>
                                            <span class="tt-tpl-name">{{ t.name }}</span>
                                            <span class="tt-sub">{{ t.desc }}</span>
                                        </button>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Expiring soon -->
                            <v-card flat class="tt-card mb-4">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Ending soon</span><v-spacer /><span v-if="expiringSoon.length" class="tt-tag warn">{{ expiringSoon.length }}</span></div>
                                <v-card-text class="tt-body">
                                    <div v-for="p in expiringSoon" :key="p.id" class="tt-mini-row" @click="editItem(p)">
                                        <span class="tt-dot-sm" :style="{ background: p.color }"></span>
                                        <span class="flex-grow-1 text-truncate">{{ p.title }}</span>
                                        <span class="tt-sub">{{ p.daysLeft }}</span>
                                    </div>
                                    <div v-if="!expiringSoon.length" class="tt-sub">Nothing ends in the next 7 days.</div>
                                </v-card-text>
                            </v-card>

                            <!-- Top performers -->
                            <v-card flat class="tt-card">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Top performers</span></div>
                                <v-card-text class="tt-body">
                                    <div v-for="p in topPromos" :key="p.id" class="tt-top-row" @click="editItem(p)">
                                        <div class="d-flex justify-space-between mb-1">
                                            <span class="font-weight-medium text-truncate">{{ p.title }}</span>
                                            <span class="tt-sub">{{ money(p.revenue) }}</span>
                                        </div>
                                        <div class="tt-progress"><div class="tt-progress-fill" :style="{ width: (p.revenue / topMax) * 100 + '%', background: p.color }"></div></div>
                                        <div class="tt-sub mt-1">{{ p.used }} uses · {{ p.roi }}× return on discount</div>
                                    </div>
                                    <div v-if="!topPromos.length" class="tt-sub">Results appear after your first redemptions.</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>

                <!-- ═════════════════════════ Add / Edit promotion (embedded) ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">{{ form.id ? "Edit promotion" : "Create promotion" }}</h1>
                            <div class="tt-sub">{{ form.id ? "Update “" + form.title + "”" : "Set the offer, who can use it and when it runs" }}</div>
                        </div>
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-ghost-btn" prepend-icon="mdi-arrow-left" @click="closeAdd">Back</v-btn>
                        <v-btn variant="outlined" class="text-none" prepend-icon="mdi-content-save-outline" :loading="savingDraft" @click="savePromo('Draft')">Save as Draft</v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-submit-btn" prepend-icon="mdi-check" :loading="saving" @click="savePromo(form.status)">
                            {{ form.id ? "Save changes" : "Create Promotion" }}
                        </v-btn>
                    </div>

                    <v-row dense class="mt-1">
                        <v-col cols="12" md="8">
                            <!-- Basic -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Basic Info</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">How is it applied?</div>
                                    <v-row dense class="mb-4">
                                        <v-col v-for="m in methods" :key="m.value" cols="12" sm="6">
                                            <div class="tt-pick" :class="{ 'is-selected': form.method === m.value }" @click="form.method = m.value">
                                                <v-icon size="22" :color="form.method === m.value ? '#0f9d6b' : '#78716c'">{{ m.icon }}</v-icon>
                                                <div>
                                                    <div class="tt-pick-name">{{ m.label }}</div>
                                                    <div class="tt-sub">{{ m.hint }}</div>
                                                </div>
                                            </div>
                                        </v-col>
                                    </v-row>

                                    <div class="tt-field-label">Promotion name <span class="tt-required">*</span></div>
                                    <v-text-field v-model="form.title" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Eid Gadget Sale" autofocus />

                                    <template v-if="form.method === 'code'">
                                        <div class="tt-field-label">Discount code <span class="tt-required">*</span></div>
                                        <div class="d-flex ga-2 mb-1">
                                            <v-text-field v-model="form.code" variant="outlined" density="comfortable" hide-details placeholder="e.g. EID25" class="tt-code-input" @update:model-value="cleanCode" />
                                            <v-btn variant="outlined" class="text-none" prepend-icon="mdi-auto-fix" style="height: 48px" @click="generateCode">Generate</v-btn>
                                        </div>
                                        <div class="tt-sub mb-3" :class="{ 'tt-bad': codeTaken }">
                                            <template v-if="codeTaken"><v-icon size="13" color="#dc2626">mdi-alert-circle-outline</v-icon> This code is already used by “{{ codeTakenBy }}”.</template>
                                            <template v-else>Letters, numbers and dashes only. Customers type this at checkout.</template>
                                        </div>
                                        <div class="tt-toggle-row">
                                            <div>
                                                <div class="font-weight-medium">Suggest at checkout</div>
                                                <div class="tt-sub">Show this code as a one-tap suggestion when the cart qualifies</div>
                                            </div>
                                            <v-switch v-model="form.autoApply" color="#0f9d6b" density="compact" inset hide-details />
                                        </div>
                                    </template>

                                    <div class="tt-field-label mt-4">Description</div>
                                    <v-textarea v-model="form.description" variant="outlined" density="comfortable" rows="2" hide-details placeholder="Internal note or short customer-facing text…" />
                                </v-card-text>
                            </v-card>

                            <!-- Discount -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Discount</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">Discount type</div>
                                    <v-row dense class="mb-3">
                                        <v-col v-for="t in types" :key="t.value" cols="6" sm="3">
                                            <div class="tt-rate-card" :class="{ 'is-selected': form.type === t.value }" @click="form.type = t.value">
                                                <v-icon size="20" :color="form.type === t.value ? '#0f9d6b' : '#78716c'">{{ t.icon }}</v-icon>
                                                <div class="tt-rate-name">{{ t.label }}</div>
                                                <div class="tt-sub">{{ t.hint }}</div>
                                            </div>
                                        </v-col>
                                    </v-row>

                                    <v-row v-if="form.type === 'percent'" dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Percentage off</div>
                                            <v-text-field v-model.number="form.value" type="number" min="0" max="100" variant="outlined" density="comfortable" hide-details suffix="%" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Maximum discount</div>
                                            <v-text-field v-model.number="form.maxDiscount" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="No cap" />
                                        </v-col>
                                    </v-row>
                                    <v-row v-else-if="form.type === 'fixed'" dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Amount off</div>
                                            <v-text-field v-model.number="form.value" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" />
                                        </v-col>
                                    </v-row>
                                    <v-row v-else-if="form.type === 'bogo'" dense>
                                        <v-col cols="4">
                                            <div class="tt-field-label">Customer buys</div>
                                            <v-text-field v-model.number="form.buyQty" type="number" min="1" variant="outlined" density="comfortable" hide-details suffix="items" />
                                        </v-col>
                                        <v-col cols="4">
                                            <div class="tt-field-label">And gets</div>
                                            <v-text-field v-model.number="form.getQty" type="number" min="1" variant="outlined" density="comfortable" hide-details suffix="items" />
                                        </v-col>
                                        <v-col cols="4">
                                            <div class="tt-field-label">Off the extra</div>
                                            <v-text-field v-model.number="form.getPercent" type="number" min="1" max="100" variant="outlined" density="comfortable" hide-details suffix="%" />
                                        </v-col>
                                    </v-row>
                                    <div v-else class="tt-note"><v-icon size="16" color="#0f9d6b">mdi-truck-check-outline</v-icon>Shipping becomes free for qualifying orders.</div>

                                    <v-divider class="my-5" />

                                    <div class="tt-field-label">Applies to</div>
                                    <v-btn-toggle v-model="form.scope" mandatory density="comfortable" variant="outlined" divided class="mb-3 tt-seg">
                                        <v-btn v-for="s in scopes" :key="s.value" :value="s.value" class="text-none">{{ s.label }}</v-btn>
                                    </v-btn-toggle>

                                    <v-autocomplete v-if="form.scope === 'categories'" v-model="form.categories" :items="categoryOptions" multiple chips closable-chips variant="outlined" density="comfortable" hide-details placeholder="Choose categories" />
                                    <v-autocomplete v-else-if="form.scope === 'brands'" v-model="form.brands" :items="brandOptions" multiple chips closable-chips variant="outlined" density="comfortable" hide-details placeholder="Choose brands" />
                                    <v-combobox v-else-if="form.scope === 'products'" v-model="form.products" multiple chips closable-chips variant="outlined" density="comfortable" hide-details placeholder="Type a product name or SKU and press Enter" />
                                    <div v-else class="tt-sub">Applies to everything in the cart.</div>

                                    <div class="tt-toggle-row mt-3">
                                        <div>
                                            <div class="font-weight-medium">Exclude items already on sale</div>
                                            <div class="tt-sub">Products with a sale price will not get this discount</div>
                                        </div>
                                        <v-switch v-model="form.excludeSale" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Requirements -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Requirements &amp; Eligibility</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense class="mb-2">
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Minimum order amount</div>
                                            <v-text-field v-model.number="form.minOrder" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="None" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Minimum items in cart</div>
                                            <v-text-field v-model.number="form.minQty" type="number" min="0" variant="outlined" density="comfortable" hide-details placeholder="None" />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-field-label mt-3">Who can use it</div>
                                    <div v-for="a in audiences" :key="a.value" class="tt-radio-box" :class="{ 'is-selected': form.audience === a.value }" @click="form.audience = a.value">
                                        <span class="tt-radio-dot" :class="{ 'is-on': form.audience === a.value }"></span>
                                        <span>{{ a.label }}<span class="tt-sub d-block">{{ a.hint }}</span></span>
                                    </div>
                                    <div v-if="form.audience === 'groups'" class="tt-pill-row mb-2">
                                        <button v-for="g in groupOptions" :key="g" type="button" class="tt-pill" :class="{ 'is-on': form.groups.includes(g) }" @click="toggleIn('groups', g)"><v-icon size="14">{{ form.groups.includes(g) ? "mdi-check" : "mdi-plus" }}</v-icon>{{ g }}</button>
                                    </div>

                                    <div class="tt-field-label mt-4">Only with these payment methods <span class="tt-sub">(none selected = all)</span></div>
                                    <div class="tt-pill-row">
                                        <button v-for="p in paymentOptions" :key="p" type="button" class="tt-pill" :class="{ 'is-on': form.payments.includes(p) }" @click="toggleIn('payments', p)"><v-icon size="14">{{ form.payments.includes(p) ? "mdi-check" : "mdi-plus" }}</v-icon>{{ p }}</button>
                                    </div>

                                    <div class="tt-field-label mt-4">Only on these channels <span class="tt-sub">(none selected = all)</span></div>
                                    <div class="tt-pill-row">
                                        <button v-for="c in channelOptions" :key="c" type="button" class="tt-pill" :class="{ 'is-on': form.channels.includes(c) }" @click="toggleIn('channels', c)"><v-icon size="14">{{ form.channels.includes(c) ? "mdi-check" : "mdi-plus" }}</v-icon>{{ c }}</button>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Usage -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Usage Limits</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Total uses</div>
                                            <v-text-field v-model.number="form.usageLimit" type="number" min="0" variant="outlined" density="comfortable" hide-details placeholder="Unlimited" />
                                            <div class="tt-sub mt-1">Stops working once this many orders have used it.</div>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Uses per customer</div>
                                            <v-text-field v-model.number="form.perCustomer" type="number" min="0" variant="outlined" density="comfortable" hide-details placeholder="Unlimited" />
                                        </v-col>
                                    </v-row>
                                    <div class="tt-toggle-row mt-3">
                                        <div>
                                            <div class="font-weight-medium">Can be combined with other promotions</div>
                                            <div class="tt-sub">If off, customers get the single best offer</div>
                                        </div>
                                        <v-switch v-model="form.stackable" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Schedule -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Schedule</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Starts</div>
                                            <v-text-field v-model="form.startAt" type="datetime-local" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Ends</div>
                                            <v-text-field v-model="form.endAt" type="datetime-local" variant="outlined" density="comfortable" hide-details :disabled="form.noEnd" />
                                        </v-col>
                                    </v-row>
                                    <div class="tt-toggle-row mt-2">
                                        <div class="font-weight-medium">No end date</div>
                                        <v-switch v-model="form.noEnd" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>

                                    <div class="tt-field-label mt-3">Active on <span class="tt-sub">(all days if none selected)</span></div>
                                    <div class="tt-pill-row">
                                        <button v-for="d in dayOptions" :key="d.value" type="button" class="tt-pill tt-pill-day" :class="{ 'is-on': form.days.includes(d.value) }" @click="toggleDay(d.value)">{{ d.label }}</button>
                                    </div>

                                    <div class="tt-toggle-row mt-3">
                                        <div>
                                            <div class="font-weight-medium">Limit to a time window</div>
                                            <div class="tt-sub">Good for flash sales and “happy hours”</div>
                                        </div>
                                        <v-switch v-model="form.useWindow" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                    <v-row v-if="form.useWindow" dense class="mt-1">
                                        <v-col cols="6">
                                            <div class="tt-field-label">From</div>
                                            <v-text-field v-model="form.timeFrom" type="time" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                        <v-col cols="6">
                                            <div class="tt-field-label">Until</div>
                                            <v-text-field v-model="form.timeTo" type="time" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Display -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Display</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-toggle-row">
                                        <div>
                                            <div class="font-weight-medium">Show a badge on product cards</div>
                                            <div class="tt-sub">A small tag such as “−10%” on matching products</div>
                                        </div>
                                        <v-switch v-model="form.showBadge" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                    <v-row v-if="form.showBadge" dense class="mt-1">
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Badge text</div>
                                            <v-text-field v-model="form.badgeText" variant="outlined" density="comfortable" hide-details :placeholder="autoBadge" />
                                        </v-col>
                                    </v-row>
                                    <div class="tt-field-label mt-4">Promotion colour</div>
                                    <div class="tt-color-row">
                                        <button v-for="c in colorOptions" :key="c" type="button" class="tt-color-swatch" :class="{ 'is-selected': form.color === c }" :style="{ background: c }" :aria-label="'Colour ' + c" @click="form.color = c" />
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="4">
                            <div class="tt-sticky">
                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Status</span></div>
                                    <v-card-text class="tt-body">
                                        <div v-for="opt in statusOptions" :key="opt.value" class="tt-radio-box" :class="{ 'is-selected': form.status === opt.value }" @click="form.status = opt.value">
                                            <span class="tt-radio-dot" :class="{ 'is-on': form.status === opt.value }"></span>
                                            <span>{{ opt.label }}<span class="tt-sub d-block">{{ opt.hint }}</span></span>
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <!-- Live preview -->
                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Preview</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-ticket">
                                            <div class="tt-ticket-left" :style="{ background: form.color }">
                                                <div class="tt-ticket-big">{{ preview.big }}</div>
                                                <div class="tt-ticket-small">{{ preview.small }}</div>
                                            </div>
                                            <div class="tt-ticket-right">
                                                <div class="tt-ticket-title">{{ form.title || "Promotion name" }}</div>
                                                <div v-if="form.method === 'code'" class="tt-ticket-code">{{ form.code || "CODE" }}</div>
                                                <div v-else class="tt-auto-tag"><v-icon size="11">mdi-lightning-bolt</v-icon>Applied automatically</div>
                                                <div class="tt-sub mt-1">{{ preview.rules }}</div>
                                            </div>
                                        </div>
                                        <div v-for="r in preview.rows" :key="r.label" class="tt-option-row">
                                            <span class="tt-muted">{{ r.label }}</span><span class="font-weight-medium text-right">{{ r.value }}</span>
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <!-- Impact simulator -->
                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Try it on a cart</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-field-label">Cart total</div>
                                        <v-text-field v-model.number="sim.total" type="number" min="0" step="500" variant="outlined" density="comfortable" hide-details prefix="৳" class="mb-3" />
                                        <div class="tt-sim" :class="sim.result.ok ? 'ok' : 'no'">
                                            <template v-if="sim.result.ok">
                                                <div class="tt-sub">Customer saves</div>
                                                <div class="tt-sim-save">{{ sim.result.text }}</div>
                                                <div v-if="sim.result.pays !== null" class="tt-sub">Customer pays {{ money(sim.result.pays) }}</div>
                                            </template>
                                            <template v-else>
                                                <v-icon size="18">mdi-alert-circle-outline</v-icon><span>{{ sim.result.text }}</span>
                                            </template>
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-alert variant="tonal" color="#0f9d6b" class="tt-ai-alert mb-5" icon="mdi-creation">
                                    <div class="font-weight-medium mb-1">AI Suggestion</div>
                                    <div class="tt-sub">{{ aiSuggestion }}</div>
                                </v-alert>
                            </div>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="400">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Delete promotion</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="pa-4">Delete <b>{{ deletedItem ? deletedItem.title : "this promotion" }}</b>? Customers will no longer be able to use it. Past orders keep their discount.</v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" @click="cancelDelete">Cancel</v-btn>
                        <v-btn color="error" variant="flat" @click="confirmDelete">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" color="#0f9d6b" timeout="4000">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
// Set to false once /api/promotions returns real data, so sample promotions are never shown
const USE_DEMO_WHEN_EMPTY = true;

const COLORS = ["#e25311", "#0f9d6b", "#2f5be7", "#7c3aed", "#f59e0b", "#ec4899", "#0f766e"];
const CATEGORIES = ["Power banks & chargers", "Earbuds & headphones", "Smart watches", "Cables & adapters", "Gaming gear", "Networking", "Speakers", "Mobile phones", "Laptops & accessories", "Cameras"];
const BRANDS = ["Anker", "Baseus", "Xiaomi", "JBL", "Ugreen", "Realme", "Havit", "TP-Link", "Logitech", "Samsung", "Remax"];
const PAYMENTS = ["bKash", "Nagad", "Rocket", "Card", "Cash on delivery", "Bank transfer"];
const CHANNELS = ["Website", "Mobile app", "Facebook Shop", "Marketplace", "Retail store"];
const GROUPS = ["VIP", "Resellers", "Corporate", "Students"];
const TYPE_ICON = { percent: "mdi-percent-outline", fixed: "mdi-cash", shipping: "mdi-truck-check-outline", bogo: "mdi-gift-outline" };

const pad = (n) => String(n).padStart(2, "0");
// <input type="datetime-local"> wants local time as YYYY-MM-DDTHH:mm
const toInput = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
const inDays = (n, hour = 0) => {
    const d = new Date();
    d.setDate(d.getDate() + n);
    d.setHours(hour, 0, 0, 0);
    return toInput(d);
};
const fmtDate = (iso) => (iso ? new Date(iso).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" }) : "");
const moneyFmt = (v) => "৳ " + Number(v || 0).toLocaleString("en-IN");

const blankForm = () => ({
    id: null,
    title: "",
    method: "code",
    code: "",
    autoApply: false,
    description: "",
    status: "Active",
    color: COLORS[1],

    type: "percent",
    value: 10,
    maxDiscount: null,
    buyQty: 2,
    getQty: 1,
    getPercent: 100,
    scope: "all",
    categories: [],
    brands: [],
    products: [],
    excludeSale: true,

    minOrder: null,
    minQty: null,
    audience: "everyone",
    groups: [],
    payments: [],
    channels: [],

    usageLimit: null,
    perCustomer: 1,
    stackable: false,

    startAt: inDays(0, new Date().getHours()),
    endAt: inDays(30, 23),
    noEnd: false,
    days: [],
    useWindow: false,
    timeFrom: "20:00",
    timeTo: "23:00",

    showBadge: true,
    badgeText: "",

    used: 0,
    revenue: 0,
    discountGiven: 0,
});

const normalize = (raw, i = 0) => {
    const z = { ...blankForm(), ...raw };
    z.color = raw.color || COLORS[i % COLORS.length];
    ["categories", "brands", "products", "groups", "payments", "channels", "days"].forEach((k) => (z[k] = Array.isArray(raw[k]) ? [...raw[k]] : []));
    return z;
};

const DEMO = () => [
    { id: 1, title: "Welcome offer", method: "code", code: "WELCOME10", status: "Active", color: "#0f9d6b", type: "percent", value: 10, maxDiscount: 1500, minOrder: 2000, audience: "new", perCustomer: 1, startAt: inDays(-50), endAt: inDays(40, 23), used: 689, revenue: 312400, discountGiven: 31240, description: "10% off the first order" },
    { id: 2, title: "Free shipping over ৳3,000", method: "code", code: "FREESHIP", status: "Active", color: "#2f5be7", type: "shipping", minOrder: 3000, perCustomer: 0, startAt: inDays(-30), noEnd: true, used: 534, revenue: 241800, discountGiven: 40600 },
    { id: 3, title: "Cables bundle", method: "code", code: "BUNDLE15", status: "Active", color: "#7c3aed", type: "percent", value: 15, scope: "categories", categories: ["Cables & adapters", "Power banks & chargers"], minQty: 2, perCustomer: 0, startAt: inDays(-20), endAt: inDays(5, 23), used: 148, revenue: 128900, discountGiven: 19300 },
    { id: 4, title: "Flash sale", method: "code", code: "FLASH30", status: "Active", color: "#e25311", type: "percent", value: 30, maxDiscount: 2500, scope: "brands", brands: ["Baseus", "Remax"], usageLimit: 100, perCustomer: 1, useWindow: true, timeFrom: "20:00", timeTo: "23:00", startAt: inDays(-2), endAt: inDays(1, 23), used: 96, revenue: 64200, discountGiven: 27500 },
    { id: 5, title: "Buy 2 cables, get 1 free", method: "automatic", status: "Active", color: "#f59e0b", type: "bogo", buyQty: 2, getQty: 1, getPercent: 100, scope: "categories", categories: ["Cables & adapters"], perCustomer: 0, stackable: true, startAt: inDays(-15), noEnd: true, used: 233, revenue: 98100, discountGiven: 14300 },
    { id: 6, title: "Black Friday gadget week", method: "automatic", status: "Active", color: "#ec4899", type: "percent", value: 20, excludeSale: true, perCustomer: 0, startAt: inDays(12), endAt: inDays(19, 23), used: 0, revenue: 0, discountGiven: 0 },
    { id: 7, title: "Eid sale", method: "code", code: "EID25", status: "Active", color: "#0f766e", type: "percent", value: 25, maxDiscount: 3000, perCustomer: 1, startAt: inDays(-45), endAt: inDays(-20, 23), used: 412, revenue: 486000, discountGiven: 48600 },
    { id: 8, title: "Reseller price", method: "automatic", status: "Draft", color: "#2f5be7", type: "percent", value: 8, audience: "groups", groups: ["Resellers"], perCustomer: 0, startAt: inDays(0), noEnd: true, used: 0, revenue: 0, discountGiven: 0 },
];

export default {
    name: "CouponsAndDiscount",

    data() {
        return {
            tab: "list",
            loading: false,
            usingDemo: false,
            demoHidden: false,
            searchValue: "",
            methodFilter: "all",
            statusFilter: "All",

            promos: [],
            form: blankForm(),
            sim: { total: 10000 },

            saving: false,
            savingDraft: false,
            deleteDialog: false,
            deletedItem: null,

            colorOptions: COLORS,
            categoryOptions: CATEGORIES,
            brandOptions: BRANDS,
            paymentOptions: PAYMENTS,
            channelOptions: CHANNELS,
            groupOptions: GROUPS,
            statusFilters: ["All", "Active", "Scheduled", "Paused", "Used up", "Expired", "Draft"],
            methods: [
                { value: "code", label: "Discount code", hint: "Customer types it at checkout", icon: "mdi-ticket-percent-outline" },
                { value: "automatic", label: "Automatic discount", hint: "Applied when the cart qualifies", icon: "mdi-lightning-bolt-outline" },
            ],
            types: [
                { value: "percent", label: "Percentage", hint: "e.g. 10% off", icon: "mdi-percent-outline" },
                { value: "fixed", label: "Fixed amount", hint: "e.g. ৳500 off", icon: "mdi-cash" },
                { value: "shipping", label: "Free shipping", hint: "Waive delivery fee", icon: "mdi-truck-check-outline" },
                { value: "bogo", label: "Buy X get Y", hint: "e.g. buy 2 get 1", icon: "mdi-gift-outline" },
            ],
            scopes: [
                { value: "all", label: "Entire order" },
                { value: "categories", label: "Categories" },
                { value: "brands", label: "Brands" },
                { value: "products", label: "Products" },
            ],
            audiences: [
                { value: "everyone", label: "Everyone", hint: "Any customer, including guests" },
                { value: "new", label: "New customers", hint: "Only on their first order" },
                { value: "returning", label: "Returning customers", hint: "Customers who ordered before" },
                { value: "groups", label: "Specific customer groups", hint: "VIP, resellers, corporate…" },
            ],
            dayOptions: [
                { value: 6, label: "Sat" },
                { value: 0, label: "Sun" },
                { value: 1, label: "Mon" },
                { value: 2, label: "Tue" },
                { value: 3, label: "Wed" },
                { value: 4, label: "Thu" },
                { value: 5, label: "Fri" },
            ],
            statusOptions: [
                { label: "Active", value: "Active", hint: "Works as soon as the start time arrives" },
                { label: "Draft", value: "Draft", hint: "Saved, not live" },
                { label: "Inactive", value: "Inactive", hint: "Paused. Keep the settings" },
            ],
            templates: [
                { name: "Welcome offer", desc: "10% off a first order", icon: "mdi-hand-wave-outline", color: "#0f9d6b", tint: "#dff5ec", patch: { title: "Welcome offer", method: "code", code: "WELCOME10", type: "percent", value: 10, maxDiscount: 1500, audience: "new", perCustomer: 1, minOrder: 2000 } },
                { name: "Free shipping", desc: "Waive delivery over ৳3,000", icon: "mdi-truck-check-outline", color: "#2f5be7", tint: "#e6edff", patch: { title: "Free shipping", method: "code", code: "FREESHIP", type: "shipping", minOrder: 3000, perCustomer: 0 } },
                { name: "Flash sale", desc: "30% off for a few hours", icon: "mdi-flash-outline", color: "#e25311", tint: "#fdece3", patch: { title: "Flash sale", method: "code", code: "FLASH30", type: "percent", value: 30, maxDiscount: 2500, usageLimit: 100, useWindow: true, timeFrom: "20:00", timeTo: "23:00", color: "#e25311" } },
                { name: "Buy 2 get 1", desc: "Automatic bundle on cables", icon: "mdi-gift-outline", color: "#f59e0b", tint: "#fff1d1", patch: { title: "Buy 2, get 1 free", method: "automatic", type: "bogo", buyQty: 2, getQty: 1, getPercent: 100, scope: "categories", categories: ["Cables & adapters"], perCustomer: 0, stackable: true, color: "#f59e0b" } },
                { name: "Festival sale", desc: "25% off, scheduled", icon: "mdi-party-popper", color: "#ec4899", tint: "#fde6f1", patch: { title: "Festival sale", method: "automatic", type: "percent", value: 25, perCustomer: 0, excludeSale: true, color: "#ec4899" } },
                { name: "Reseller price", desc: "8% for resellers", icon: "mdi-account-tie-outline", color: "#7c3aed", tint: "#efe8ff", patch: { title: "Reseller price", method: "automatic", type: "percent", value: 8, audience: "groups", groups: ["Resellers"], perCustomer: 0, color: "#7c3aed" } },
            ],

            headers: [
                { text: "Promotion", value: "title", sortable: true, width: 300 },
                { text: "Discount", value: "valueText", sortable: true, width: 150 },
                { text: "Usage", value: "used", sortable: true, width: 130 },
                { text: "Validity", value: "validityText", sortable: true, width: 170 },
                { text: "Status", value: "statusLabel", sortable: true, width: 110 },
                { text: "Operation", value: "operation", width: 150 },
            ],

            snackbar: false,
            snackbarText: "",
        };
    },

    computed: {
        activeCount() {
            return this.promos.filter((p) => this.statusOf(p) === "Active").length;
        },
        codeCount() {
            return this.promos.filter((p) => p.method === "code").length;
        },
        autoCount() {
            return this.promos.filter((p) => p.method === "automatic").length;
        },
        totals() {
            const used = this.promos.reduce((a, p) => a + (Number(p.used) || 0), 0);
            const given = this.promos.reduce((a, p) => a + (Number(p.discountGiven) || 0), 0);
            const revenue = this.promos.reduce((a, p) => a + (Number(p.revenue) || 0), 0);
            return { used, given, revenue, roi: given ? Math.round((revenue / given) * 10) / 10 : 0 };
        },
        statCards() {
            const t = this.totals;
            return [
                { label: "Live promotions", value: this.activeCount, icon: "mdi-ticket-percent-outline", color: "#0f9d6b", tint: "#dff5ec", note: `${this.promos.length - this.activeCount} scheduled, paused, draft or ended` },
                { label: "Redemptions", value: t.used.toLocaleString("en-IN"), icon: "mdi-cart-check", color: "#2f5be7", tint: "#e6edff", note: "Orders that used a promotion" },
                { label: "Discount given", value: moneyFmt(t.given), icon: "mdi-cash-minus", color: "#e25311", tint: "#fdece3", note: "Total value handed back to customers" },
                { label: "Revenue from promos", value: moneyFmt(t.revenue), icon: "mdi-cash-multiple", color: "#7c3aed", tint: "#efe8ff", note: t.roi ? `${t.roi}× return on every ৳1 discounted` : "No redemptions yet" },
            ];
        },

        // ── Table ──
        tableItems() {
            return this.promos
                .filter((p) => this.methodFilter === "all" || p.method === this.methodFilter)
                .map((p) => ({ ...p, statusLabel: this.statusOf(p) }))
                .filter((p) => this.statusFilter === "All" || p.statusLabel === this.statusFilter)
                .map((p) => ({
                    ...p,
                    icon: TYPE_ICON[p.type],
                    valueText: this.valueText(p),
                    scopeText: this.scopeText(p),
                    audienceText: this.audienceText(p),
                    validityText: this.validityText(p),
                    validityNote: this.validityNote(p),
                }));
        },
        expiringSoon() {
            const now = Date.now();
            return this.promos
                .filter((p) => this.statusOf(p) === "Active" && !p.noEnd && p.endAt)
                .map((p) => ({ ...p, left: new Date(p.endAt).getTime() - now }))
                .filter((p) => p.left > 0 && p.left <= 7 * 86400000)
                .sort((a, b) => a.left - b.left)
                .map((p) => ({ ...p, daysLeft: p.left < 86400000 ? "today" : `in ${Math.ceil(p.left / 86400000)} days` }));
        },
        topPromos() {
            return this.promos
                .filter((p) => p.revenue > 0)
                .sort((a, b) => b.revenue - a.revenue)
                .slice(0, 3)
                .map((p) => ({ ...p, roi: p.discountGiven ? Math.round((p.revenue / p.discountGiven) * 10) / 10 : 0 }));
        },
        topMax() {
            return this.topPromos.length ? this.topPromos[0].revenue : 1;
        },

        // ── Add / edit page ──
        codeClash() {
            if (this.form.method !== "code" || !this.form.code) return null;
            const c = this.form.code.trim().toUpperCase();
            return this.promos.find((p) => p.method === "code" && p.id !== this.form.id && (p.code || "").toUpperCase() === c) || null;
        },
        codeTaken() {
            return !!this.codeClash;
        },
        codeTakenBy() {
            return this.codeClash ? this.codeClash.title : "";
        },
        autoBadge() {
            return this.badgeFor(this.form);
        },
        preview() {
            const f = this.form;
            const rules = [];
            if (f.minOrder) rules.push(`Min. order ${moneyFmt(f.minOrder)}`);
            if (f.minQty) rules.push(`${f.minQty}+ items`);
            if (f.scope !== "all") rules.push(this.scopeText(f));
            if (f.audience !== "everyone") rules.push(this.audienceText(f));
            const big = this.badgeFor(f).replace(/^−/, "");
            return {
                big: f.type === "shipping" ? "FREE" : f.type === "bogo" ? `${f.buyQty}+${f.getQty}` : big,
                small: f.type === "shipping" ? "SHIPPING" : f.type === "bogo" ? "BUNDLE" : "OFF",
                rules: rules.length ? rules.join(" · ") : "No conditions",
                rows: [
                    { label: "Applies to", value: f.scope === "all" ? "Entire order" : this.scopeText(f) },
                    { label: "Valid", value: f.noEnd ? `From ${fmtDate(f.startAt) || "now"}` : `${fmtDate(f.startAt) || "now"} → ${fmtDate(f.endAt) || "?"}` },
                    { label: "Limits", value: f.usageLimit ? `${f.usageLimit} total · ${f.perCustomer || "∞"} each` : f.perCustomer ? `${f.perCustomer} per customer` : "Unlimited" },
                    { label: "Combine", value: f.stackable ? "Allowed" : "Best offer only" },
                ],
            };
        },
        simResult() {
            return this.simulate(this.form, Number(this.sim.total) || 0);
        },
        aiSuggestion() {
            const f = this.form;
            if (!f.title) return "Give the promotion a clear name your team will recognise in reports.";
            if (f.type === "percent" && Number(f.value) >= 30 && !f.maxDiscount) return "Big percentage with no cap can get expensive on laptops and phones. Add a maximum discount.";
            if (f.method === "code" && !f.usageLimit && Number(f.value) >= 25) return "Consider a total-use limit so a leaked code cannot be used without end.";
            if (!f.noEnd && f.startAt && f.endAt && new Date(f.endAt) <= new Date(f.startAt)) return "The end time is before the start time. Fix the schedule.";
            if (f.type !== "shipping" && !f.minOrder) return "A minimum order slightly above your average order value lifts basket size.";
            if (f.stackable && Number(f.value) >= 20) return "Stacking a 20%+ offer with others can push margins below zero. Double-check.";
            return "Looks good. Check the preview, then create the promotion.";
        },
    },

    watch: {
        // keep the cart simulator result reactive without recomputing in the template
        simResult: {
            immediate: true,
            handler(v) {
                this.sim.result = v;
            },
        },
    },

    methods: {
        money: moneyFmt,

        // ── Status, wording ──
        statusOf(p) {
            if (p.status === "Draft") return "Draft";
            if (p.status === "Inactive") return "Paused";
            const now = Date.now();
            if (!p.noEnd && p.endAt && new Date(p.endAt).getTime() < now) return "Expired";
            if (p.startAt && new Date(p.startAt).getTime() > now) return "Scheduled";
            if (p.usageLimit && p.used >= p.usageLimit) return "Used up";
            return "Active";
        },
        statusColor(s) {
            return { Active: "success", Scheduled: "info", Paused: "warning", "Used up": "warning", Expired: "default", Draft: "default" }[s] || "default";
        },
        badgeFor(f) {
            if (f.badgeText) return f.badgeText;
            if (f.type === "percent") return `−${Number(f.value) || 0}%`;
            if (f.type === "fixed") return `−${moneyFmt(f.value)}`;
            if (f.type === "shipping") return "Free shipping";
            return `Buy ${f.buyQty} get ${f.getQty}`;
        },
        valueText(p) {
            if (p.type === "percent") return `${Number(p.value) || 0}% off`;
            if (p.type === "fixed") return `${moneyFmt(p.value)} off`;
            if (p.type === "shipping") return "Free shipping";
            const pct = Number(p.getPercent);
            return `Buy ${p.buyQty}, get ${p.getQty}${pct && pct < 100 ? " at " + pct + "% off" : " free"}`;
        },
        scopeText(p) {
            const list = p.scope === "categories" ? p.categories : p.scope === "brands" ? p.brands : p.scope === "products" ? p.products : null;
            if (!list) return "Entire order";
            if (!list.length) return `No ${p.scope} chosen`;
            return list.length > 2 ? `${list.slice(0, 2).join(", ")} +${list.length - 2}` : list.join(", ");
        },
        audienceText(p) {
            if (p.audience === "new") return "New customers";
            if (p.audience === "returning") return "Returning customers";
            if (p.audience === "groups") return p.groups.length ? p.groups.join(", ") : "Customer groups";
            return "Everyone";
        },
        validityText(p) {
            const a = fmtDate(p.startAt);
            if (p.noEnd) return a ? `From ${a}` : "Always on";
            return `${a || "Now"} → ${fmtDate(p.endAt) || "?"}`;
        },
        validityNote(p) {
            const bits = [];
            if (p.days && p.days.length && p.days.length < 7) bits.push(this.dayOptions.filter((d) => p.days.includes(d.value)).map((d) => d.label).join(", "));
            if (p.useWindow) bits.push(`${p.timeFrom}–${p.timeTo}`);
            return bits.length ? bits.join(" · ") : p.noEnd ? "No end date" : "All day";
        },

        // what a cart of `total` ৳ would get from this promotion (rough, for the simulator)
        simulate(f, total) {
            if (f.minOrder && total < f.minOrder) return { ok: false, text: `Cart is below the minimum order of ${moneyFmt(f.minOrder)}.` };
            if (f.type === "shipping") return { ok: true, text: "Free shipping", pays: null };
            let save = 0;
            if (f.type === "percent") {
                save = (total * (Number(f.value) || 0)) / 100;
                if (f.maxDiscount) save = Math.min(save, Number(f.maxDiscount));
            } else if (f.type === "fixed") save = Math.min(Number(f.value) || 0, total);
            else {
                // assume items average ৳1,500 to show roughly what a bundle is worth
                const price = 1500;
                const qty = Math.floor(total / price);
                const sets = Math.floor(qty / ((Number(f.buyQty) || 1) + (Number(f.getQty) || 1)));
                save = sets * (Number(f.getQty) || 1) * price * ((Number(f.getPercent) || 100) / 100);
                if (!save) return { ok: false, text: "Cart is too small to earn the free items." };
            }
            save = Math.round(save);
            return { ok: true, text: moneyFmt(save), pays: Math.max(0, total - save) };
        },

        // ── Load ──
        loadPromos() {
            this.loading = true;
            this.axios
                .get("/api/promotions")
                .then((response) => {
                    const data = response.data && response.data.success ? response.data.data : null;
                    if (data && data.length) {
                        this.promos = data.map((p, i) => normalize(p, i));
                        this.usingDemo = false;
                    } else this.useDemo();
                })
                .catch((error) => {
                    console.error(error);
                    this.useDemo();
                })
                .finally(() => (this.loading = false));
        },
        useDemo() {
            if (USE_DEMO_WHEN_EMPTY) {
                this.promos = DEMO().map((p, i) => normalize(p, i));
                this.usingDemo = true;
            } else this.promos = [];
        },

        // ── Open add / edit ──
        openAdd(template) {
            this.form = blankForm();
            this.form.color = COLORS[this.promos.length % COLORS.length];
            if (template) Object.assign(this.form, JSON.parse(JSON.stringify(template.patch)));
            if (this.form.method === "code" && !this.form.code) this.generateCode();
            this.tab = "add";
        },
        editItem(item) {
            const p = this.promos.find((x) => x.id === item.id) || item;
            this.form = normalize(JSON.parse(JSON.stringify(p)));
            this.form.id = p.id;
            this.tab = "add";
        },
        duplicatePromo(item) {
            const p = this.promos.find((x) => x.id === item.id) || item;
            this.form = normalize(JSON.parse(JSON.stringify(p)));
            this.form.id = null;
            this.form.title = p.title + " (copy)";
            this.form.status = "Draft";
            this.form.used = 0;
            this.form.revenue = 0;
            this.form.discountGiven = 0;
            this.form.code = "";
            if (this.form.method === "code") this.generateCode();
            this.tab = "add";
            this.showSuccess("Copied as a draft with a new code.");
        },
        closeAdd() {
            this.tab = "list";
        },

        // ── Form helpers ──
        cleanCode(v) {
            this.form.code = (v || "").toUpperCase().replace(/[^A-Z0-9-]/g, "").slice(0, 20);
        },
        generateCode() {
            const f = this.form;
            const base = (f.title || "SAVE").toUpperCase().replace(/[^A-Z0-9]/g, "").slice(0, 6) || "SAVE";
            const num = f.type === "percent" && f.value ? String(Math.round(f.value)) : String(Math.floor(10 + Math.random() * 90));
            let code = base + num;
            let n = 0;
            while (this.promos.some((p) => (p.code || "").toUpperCase() === code && p.id !== f.id) && n < 20) code = base + String(Math.floor(10 + Math.random() * 90)), n++;
            f.code = code;
        },
        toggleIn(listName, value) {
            const list = this.form[listName];
            this.form[listName] = list.includes(value) ? list.filter((x) => x !== value) : [...list, value];
        },
        toggleDay(v) {
            this.toggleIn("days", v);
        },
        copyCode(code) {
            if (!code) return;
            const done = () => this.showSuccess(`Copied ${code}`);
            if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(code).then(done, done);
            else done();
        },

        // ── Validate & save ──
        validate() {
            const f = this.form;
            if (!f.title || !f.title.trim()) return "Promotion name is required.";
            if (f.method === "code") {
                if (!f.code || f.code.length < 3) return "Enter a code of at least 3 characters.";
                if (this.codeTaken) return `The code ${f.code} is already used by “${this.codeTakenBy}”.`;
            }
            if (f.type === "percent" && !(Number(f.value) > 0 && Number(f.value) <= 100)) return "Percentage must be between 1 and 100.";
            if (f.type === "fixed" && !(Number(f.value) > 0)) return "Enter the amount to take off.";
            if (f.type === "bogo" && !(Number(f.buyQty) >= 1 && Number(f.getQty) >= 1 && Number(f.getPercent) > 0 && Number(f.getPercent) <= 100)) return "Check the Buy X get Y numbers.";
            if (f.scope === "categories" && !f.categories.length) return "Choose at least one category.";
            if (f.scope === "brands" && !f.brands.length) return "Choose at least one brand.";
            if (f.scope === "products" && !f.products.length) return "Add at least one product.";
            if (f.audience === "groups" && !f.groups.length) return "Choose at least one customer group.";
            if (!f.noEnd && f.startAt && f.endAt && new Date(f.endAt) <= new Date(f.startAt)) return "The end time must be after the start time.";
            if (f.useWindow && f.timeFrom === f.timeTo) return "The time window starts and ends at the same time.";
            return null;
        },
        async savePromo(status) {
            const err = this.validate();
            if (err) return this.showError(err);

            const flag = status === "Draft" ? "savingDraft" : "saving";
            this[flag] = true;
            const payload = { ...JSON.parse(JSON.stringify(this.form)), status: status || this.form.status, title: this.form.title.trim() };
            if (payload.method === "automatic") payload.code = "";
            try {
                let saved = payload;
                // REST-style endpoints: POST /api/promotions, PUT /api/promotions/:id
                if (!this.usingDemo) {
                    const response = payload.id ? await this.axios.put(`/api/promotions/${payload.id}`, payload) : await this.axios.post("/api/promotions", payload);
                    if (response.data && response.data.success === false) {
                        this.showError(response.data.message || "Save failed.");
                        return;
                    }
                    if (response.data && response.data.data) saved = { ...payload, ...response.data.data };
                } else if (!payload.id) saved = { ...payload, id: Date.now() };

                const promo = normalize(saved, this.promos.length);
                const idx = this.promos.findIndex((p) => p.id === promo.id);
                if (idx >= 0) this.promos.splice(idx, 1, promo);
                else this.promos.unshift(promo);

                this.showSuccess(payload.id ? "Promotion updated." : "Promotion created.");
                this.tab = "list";
            } catch (error) {
                console.error(error);
                this.showError("An error occurred while saving.");
            } finally {
                this[flag] = false;
            }
        },

        // ── Pause / resume ──
        async toggleStatus(item) {
            const p = this.promos.find((x) => x.id === item.id);
            if (!p) return;
            const next = p.status === "Active" ? "Inactive" : "Active";
            try {
                if (!this.usingDemo) {
                    const response = await this.axios.put(`/api/promotions/${p.id}`, { ...p, status: next });
                    if (response.data && response.data.success === false) return this.showError(response.data.message || "Update failed.");
                }
                p.status = next;
                this.showSuccess(next === "Active" ? `${p.title} resumed.` : `${p.title} paused.`);
            } catch (error) {
                console.error(error);
                this.showError("An error occurred while updating.");
            }
        },

        // ── Delete ──
        removeOption(item) {
            this.deletedItem = item;
            this.deleteDialog = true;
        },
        cancelDelete() {
            this.deleteDialog = false;
            this.deletedItem = null;
        },
        confirmDelete() {
            const id = this.deletedItem && this.deletedItem.id;
            const done = () => {
                this.promos = this.promos.filter((p) => p.id !== id);
                this.showSuccess("Promotion deleted.");
            };
            if (this.usingDemo) {
                done();
                this.cancelDelete();
                return;
            }
            this.axios
                .delete(`/api/promotions/${id}`)
                .then((response) => {
                    if (response.data && response.data.success === false) this.showError(response.data.message || "Delete failed.");
                    else done();
                })
                .catch((error) => {
                    console.error(error);
                    this.showError("An error occurred while deleting.");
                })
                .finally(() => this.cancelDelete());
        },

        showSuccess(msg) {
            this.snackbarText = msg;
            this.snackbar = true;
        },
        showError(msg) {
            this.snackbarText = msg;
            this.snackbar = true;
        },
    },

    created() {
        document.title = "Coupons & Discounts";
        this.loadPromos();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-promo {
    --ink: #1c1917;
    --muted: #78716c;
    --line: #e7e2dd;
    --accent: #e25311;
    --tint: #fdece3;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    padding-bottom: 32px;
}

/* ───────── Page header ───────── */
.tt-page-head { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 10px; }
.tt-h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.2px; }
.tt-sub { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
.tt-muted { color: var(--muted); }
.tt-bad { color: #dc2626; }
.tt-ghost-btn { color: var(--accent) !important; border-color: var(--accent) !important; }
.tt-add-btn, .tt-submit-btn { color: #fff !important; font-weight: 600; }
.tt-demo { border: 1px solid #f5c9b4 !important; }

/* ───────── Cards ───────── */
.tt-card {
    background: #fff;
    border: 1px solid var(--line) !important;
    border-radius: 10px !important;
    box-shadow: 0 1px 2px rgba(28, 25, 23, 0.04) !important;
    overflow: hidden;
}
.tt-head {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 16px;
    background: linear-gradient(90deg, var(--tint), #fff);
    border-bottom: 1px solid var(--line);
    border-left: 3px solid var(--accent);
}
.tt-head-wrap { flex-wrap: wrap; row-gap: 8px; }
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-dot-sm { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-tag { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; border: 1px solid var(--accent); color: var(--accent); background: #fff; }
.tt-tag.warn { color: #8a5a00; background: #fdf1cf; border-color: #f3d78a; }
.tt-body { padding: 16px 18px 18px; }
.tt-field-label { font-size: 11.5px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #57534e; margin-bottom: 8px; }
.tt-field-label .tt-sub { text-transform: none; letter-spacing: 0; font-weight: 400; }
.tt-required { color: #dc2626; }
.tt-sticky { position: sticky; top: 16px; }
.tt-empty { padding: 28px 16px; text-align: center; color: var(--muted); font-size: 13px; }
.tt-seg-sm :deep(.v-btn) { height: 40px !important; font-size: 12.5px; }
.tt-seg { height: 48px; }

/* ───────── Summary cards ───────── */
.tt-stat { padding: 16px; }
.tt-stat-icon { width: 42px; height: 42px; border-radius: 11px; display: grid; place-items: center; flex: none; }
.tt-stat-label { font-size: 12px; font-weight: 500; color: var(--muted); }
.tt-stat-value { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; white-space: nowrap; }
.tt-progress { height: 6px; border-radius: 4px; background: #efebe7; overflow: hidden; }
.tt-progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }

/* ───────── Table cells ───────── */
.tt-name-item { padding-inline: 4px !important; cursor: pointer; border-radius: 6px; transition: transform 0.15s ease, background-color 0.15s ease; }
.tt-name-item:hover { background-color: var(--tint); transform: translateX(3px); }
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-type-badge { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; flex: none; margin-right: 12px; }
.tt-code { display: inline-flex; align-items: center; gap: 4px; padding: 1px 8px; border-radius: 6px; border: 1px dashed #0f9d6b; background: #f3fbf7; color: #0f7a4d; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; cursor: copy; }
.tt-code:hover { background: #e3f6ee; }
.tt-auto-tag { display: inline-flex; align-items: center; gap: 3px; padding: 1px 8px; border-radius: 6px; background: #fff1d1; color: #8a5a00; font-size: 11px; font-weight: 600; }
.tt-usage { min-width: 96px; font-size: 13px; }
.tt-add-new {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    margin: 12px -18px -18px; padding: 14px; font-size: 13px; font-weight: 600; color: var(--accent); cursor: pointer;
    border-top: 1px solid var(--line);
}
.tt-add-new:hover { background: var(--tint); }

/* ───────── Side cards ───────── */
.tt-tpl-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.tt-tpl { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 10px; background: #fbfafa; font-family: inherit; text-align: left; cursor: pointer; transition: border-color 0.15s, background 0.15s, transform 0.15s; }
.tt-tpl:hover { border-color: var(--accent); background: var(--tint); transform: translateY(-2px); }
.tt-tpl-ico { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; }
.tt-tpl-name { font-size: 13px; font-weight: 600; color: var(--ink); }
.tt-mini-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 13px; cursor: pointer; }
.tt-mini-row:last-child { border-bottom: 0; }
.tt-mini-row:hover { color: var(--accent); }
.tt-top-row { padding: 8px 0 10px; border-bottom: 1px solid var(--line); font-size: 13px; cursor: pointer; }
.tt-top-row:last-child { border-bottom: 0; }

/* ───────── Form pieces ───────── */
.tt-pick { display: flex; align-items: center; gap: 12px; height: 100%; padding: 12px 14px; border: 1px solid var(--line); border-radius: 10px; background: #fbfafa; cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tt-pick:hover { border-color: #0f9d6b; }
.tt-pick.is-selected { border-color: #0f9d6b; background: #eaf8f1; }
.tt-pick-name { font-size: 13.5px; font-weight: 600; }
.tt-rate-card { height: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 12px; cursor: pointer; background: #fbfafa; transition: border-color 0.15s, background 0.15s; }
.tt-rate-card:hover { border-color: #0f9d6b; }
.tt-rate-card.is-selected { border-color: #0f9d6b; background: #eaf8f1; }
.tt-rate-name { font-size: 13px; font-weight: 600; margin-top: 6px; }
.tt-note { display: flex; align-items: center; gap: 8px; padding: 12px 14px; border-radius: 10px; background: #eaf8f1; font-size: 13px; }
.tt-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 8px 0; font-size: 13.5px; }
.tt-code-input :deep(input) { text-transform: uppercase; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; letter-spacing: 1px; }
.tt-pill-row { display: flex; flex-wrap: wrap; gap: 8px; }
.tt-pill { display: inline-flex; align-items: center; gap: 5px; height: 32px; padding: 0 13px 0 9px; border-radius: 16px; border: 1px solid var(--line); background: #fff; font-family: inherit; font-size: 12.5px; font-weight: 500; color: var(--ink); cursor: pointer; transition: border-color 0.15s, background 0.15s, color 0.15s; }
.tt-pill:hover { border-color: #0f9d6b; color: #0f9d6b; }
.tt-pill.is-on { background: #eaf8f1; border-color: #0f9d6b; color: #0f7a4d; font-weight: 600; }
.tt-pill-day { padding: 0 14px; min-width: 52px; justify-content: center; }
.tt-color-row { display: flex; gap: 10px; align-items: center; padding-top: 4px; }
.tt-color-swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08); }
.tt-color-swatch.is-selected { border-color: var(--ink); transform: scale(1.1); }

/* ───────── Sidebar blocks ───────── */
.tt-radio-box {
    display: flex; align-items: center; gap: 10px; font-size: 13.5px;
    border: 1px solid var(--line); border-radius: 8px; padding: 10px 14px; margin-bottom: 10px;
    background: #fbfafa; cursor: pointer; transition: border-color 0.15s, background 0.15s;
}
.tt-radio-box:last-child { margin-bottom: 0; }
.tt-radio-box.is-selected { border-color: #0f9d6b; background: #eaf8f1; font-weight: 600; }
.tt-radio-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #d8cfc7; display: inline-block; position: relative; flex: none; }
.tt-radio-dot.is-on { border-color: #0f9d6b; }
.tt-radio-dot.is-on::after { content: ""; position: absolute; inset: 3px; border-radius: 50%; background: #0f9d6b; }
.tt-option-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 7px 0; font-size: 13.5px; border-bottom: 1px solid var(--line); }
.tt-option-row:last-child { border-bottom: 0; }
.tt-ai-alert { border: 1px solid #b7e6cd !important; }

/* coupon ticket preview */
.tt-ticket { display: flex; border-radius: 12px; overflow: hidden; border: 1px solid var(--line); margin-bottom: 12px; background: #fff; box-shadow: 0 6px 16px -10px rgba(28, 25, 23, 0.35); }
.tt-ticket-left { flex: none; width: 104px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 14px 8px; color: #fff; text-align: center; position: relative; }
.tt-ticket-left::after { content: ""; position: absolute; right: -6px; top: 0; bottom: 0; width: 12px; background: radial-gradient(circle at 6px 8px, #fff 4px, transparent 4.5px) 0 0 / 12px 16px repeat-y; }
.tt-ticket-big { font-size: 22px; font-weight: 700; line-height: 1.1; letter-spacing: -0.3px; }
.tt-ticket-small { font-size: 10.5px; font-weight: 600; letter-spacing: 0.1em; opacity: 0.9; }
.tt-ticket-right { padding: 12px 14px 12px 18px; min-width: 0; }
.tt-ticket-title { font-size: 13.5px; font-weight: 600; margin-bottom: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-ticket-code { display: inline-block; padding: 2px 10px; border-radius: 6px; border: 1px dashed #0f9d6b; background: #f3fbf7; color: #0f7a4d; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; font-weight: 700; letter-spacing: 1px; }

/* cart simulator */
.tt-sim { padding: 12px 14px; border-radius: 10px; font-size: 13px; }
.tt-sim.ok { background: #eaf8f1; border: 1px solid #b7e6cd; }
.tt-sim.no { display: flex; align-items: center; gap: 8px; background: #fff6e6; border: 1px solid #f3d78a; color: #8a5a00; }
.tt-sim-save { font-size: 22px; font-weight: 700; color: #0f7a4d; }

/* ───────── EasyDataTable theme ───────── */
:deep(.customize-table) {
    --easy-table-border: 1px solid #e7e2dd;
    --easy-table-row-border: 1px solid #e7e2dd;
    --easy-table-header-font-size: 12px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #57534e;
    --easy-table-header-background-color: #f5f1ec;
    --easy-table-body-row-height: 68px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #fdece3;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
</style>