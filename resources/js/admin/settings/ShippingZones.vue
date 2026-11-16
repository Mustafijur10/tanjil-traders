<template>
    <v-slide-x-transition appear>
        <div class="tt-zone">
            <v-window v-model="tab">
                <!-- ═════════════════════════ Zone list ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">Shipping Zones</h1>
                            <div class="tt-sub">{{ zones.length }} zones · {{ activeZones }} active · {{ coveredCount }} of {{ allDistricts.length }} districts covered</div>
                        </div>
                        <v-spacer />
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-add-btn" prepend-icon="mdi-plus" @click="openAdd">
                            Add Zone
                        </v-btn>
                    </div>

                    <v-alert v-if="usingDemo && !demoHidden" variant="tonal" color="#e25311" density="compact" class="tt-demo mt-4" icon="mdi-information-outline" closable @click:close="demoHidden = true">
                        Showing sample zones because no zones were returned from <b>/api/shipping-zones</b>. Saving a zone will call that endpoint.
                    </v-alert>

                    <!-- Summary cards -->
                    <v-row dense class="mt-2">
                        <v-col v-for="s in statCards" :key="s.label" cols="12" sm="6" lg="3">
                            <v-card flat class="tt-card tt-stat" :class="{ 'is-click': s.click }" @click="s.click && s.click()">
                                <div class="d-flex align-center ga-3">
                                    <div class="tt-stat-icon" :style="{ background: s.tint }"><v-icon size="20" :color="s.color">{{ s.icon }}</v-icon></div>
                                    <div style="min-width: 0">
                                        <div class="tt-stat-label">{{ s.label }}</div>
                                        <div class="tt-stat-value">{{ s.value }}</div>
                                    </div>
                                </div>
                                <div v-if="s.progress !== undefined" class="tt-progress mt-3"><div class="tt-progress-fill" :style="{ width: s.progress + '%', background: s.color }"></div></div>
                                <div class="tt-sub mt-2">{{ s.note }}</div>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-row dense class="mt-1">
                        <v-col cols="12" lg="8">
                            <v-card flat class="tt-card">
                                <div class="tt-head tt-head-wrap">
                                    <span class="tt-dot"></span><span class="tt-card-title">All Zones</span>
                                    <v-spacer />
                                    <v-select v-model="statusFilter" :items="['All', 'Active', 'Draft', 'Inactive']" variant="outlined" density="compact" hide-details style="max-width: 130px" class="mr-2" />
                                    <v-text-field v-model="searchValue" placeholder="Search zones…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details style="max-width: 220px" @click:clear="searchValue = ''" />
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
                                            <v-list-item class="tt-name-item" density="compact" min-height="48" @click="editItem(item)">
                                                <template #prepend>
                                                    <span class="tt-zone-badge" :style="{ background: item.color }"><v-icon size="16" color="white">mdi-map-marker-radius-outline</v-icon></span>
                                                </template>
                                                <v-list-item-title class="tt-name">{{ item.title }}</v-list-item-title>
                                                <v-list-item-subtitle class="text-caption">{{ item.areas }}</v-list-item-subtitle>
                                            </v-list-item>
                                        </template>

                                        <template #item-districtCount="item">
                                            <v-chip size="small" variant="tonal" label>{{ item.districtCount }} districts</v-chip>
                                        </template>

                                        <template #item-rateText="item">
                                            <span class="font-weight-medium">{{ item.rateText }}</span>
                                            <div class="tt-sub">{{ item.rateType }}</div>
                                        </template>

                                        <template #item-codText="item">
                                            <v-chip size="small" :color="item.cod ? 'success' : 'default'" variant="tonal" label>{{ item.codText }}</v-chip>
                                        </template>

                                        <template #item-status="item">
                                            <v-chip size="small" :color="statusColor(item.status)" variant="tonal" label>{{ item.status }}</v-chip>
                                        </template>

                                        <template #item-operation="item">
                                            <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click.prevent="editItem(item)" />
                                            <v-btn icon="mdi-content-copy" size="small" variant="text" @click="duplicateZone(item)" />
                                            <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="removeOption(item)" />
                                        </template>

                                        <template #empty-message>
                                            <div class="tt-empty">No zones yet. Use “Add Zone” to create your first one.</div>
                                        </template>
                                    </EasyDataTable>

                                    <div class="tt-add-new" @click="openAdd"><v-icon size="16">mdi-plus</v-icon> Add New Zone</div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" lg="4">
                            <!-- Rate checker -->
                            <v-card flat class="tt-card mb-4">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Rate checker</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-sub mb-3">Test what a customer would pay. Uses your active zones.</div>
                                    <div class="tt-field-label">District</div>
                                    <v-autocomplete v-model="checker.district" :items="districtItems" item-title="label" item-value="name" variant="outlined" density="comfortable" hide-details clearable placeholder="e.g. Chattogram" class="mb-3" />
                                    <v-row dense>
                                        <v-col cols="6">
                                            <div class="tt-field-label">Weight (kg)</div>
                                            <v-text-field v-model.number="checker.weight" type="number" min="0" step="0.5" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                        <v-col cols="6">
                                            <div class="tt-field-label">Order total (৳)</div>
                                            <v-text-field v-model.number="checker.total" type="number" min="0" step="500" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                    </v-row>

                                    <div class="tt-check-result" :class="checkResult.state">
                                        <template v-if="checkResult.state === 'idle'">
                                            <v-icon size="18">mdi-map-search-outline</v-icon><span>Pick a district to see the shipping charge.</span>
                                        </template>
                                        <template v-else-if="checkResult.state === 'none'">
                                            <v-icon size="18">mdi-alert-circle-outline</v-icon><span><b>{{ checker.district }}</b> is not covered by any active zone.</span>
                                        </template>
                                        <template v-else>
                                            <div class="d-flex align-center justify-space-between">
                                                <span class="tt-check-zone"><span class="tt-dot-sm" :style="{ background: checkResult.zone.color }"></span>{{ checkResult.zone.title }}</span>
                                                <span class="tt-check-price">{{ checkResult.free ? "Free" : money(checkResult.charge) }}</span>
                                            </div>
                                            <div class="tt-sub mt-1">Delivery in {{ etaText(checkResult.zone) }} · COD {{ checkResult.zone.codEnabled ? "available" : "not available" }}<template v-if="checkResult.free"> · free-shipping threshold reached</template></div>
                                        </template>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Coverage by division -->
                            <v-card flat class="tt-card">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Coverage by division</span></div>
                                <v-card-text class="tt-body">
                                    <div v-for="d in divisionCoverage" :key="d.name" class="tt-cov-row">
                                        <div class="d-flex justify-space-between mb-1">
                                            <span class="font-weight-medium">{{ d.name }}</span>
                                            <span class="tt-sub">{{ d.covered }} / {{ d.total }}</span>
                                        </div>
                                        <div class="tt-progress"><div class="tt-progress-fill" :style="{ width: (d.covered / d.total) * 100 + '%', background: d.covered === d.total ? '#0f9d6b' : '#f59e0b' }"></div></div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>

                <!-- ═════════════════════════ Add / Edit zone (embedded) ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <div class="tt-page-head">
                        <div>
                            <h1 class="tt-h1">{{ form.id ? "Edit zone" : "Add zone" }}</h1>
                            <div class="tt-sub">{{ form.id ? "Update “" + form.title + "”" : "Group districts and set how much and how fast you ship there" }}</div>
                        </div>
                        <v-spacer />
                        <v-btn variant="outlined" class="text-none tt-ghost-btn" prepend-icon="mdi-arrow-left" @click="closeAdd">Back</v-btn>
                        <v-btn variant="outlined" class="text-none" prepend-icon="mdi-content-save-outline" :loading="savingDraft" @click="saveZone('Draft')">Save as Draft</v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none tt-submit-btn" prepend-icon="mdi-check" :loading="saving" @click="saveZone(form.status)">
                            {{ form.id ? "Save changes" : "Create Zone" }}
                        </v-btn>
                    </div>

                    <v-row dense class="mt-1">
                        <v-col cols="12" md="8">
                            <!-- Basic info -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Basic Info</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="8">
                                            <div class="tt-field-label">Zone name <span class="tt-required">*</span></div>
                                            <v-text-field v-model="form.title" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Dhaka City" autofocus />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <div class="tt-field-label">Priority</div>
                                            <v-text-field v-model.number="form.priority" type="number" min="0" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="0" />
                                        </v-col>
                                    </v-row>
                                    <div class="tt-field-label">Description</div>
                                    <v-textarea v-model="form.description" variant="outlined" density="comfortable" rows="2" hide-details class="mb-4" placeholder="Internal note, e.g. inside Dhaka city corporation, same-day eligible…" />
                                    <div class="tt-field-label">Zone colour</div>
                                    <div class="tt-color-row">
                                        <button v-for="c in colorOptions" :key="c" type="button" class="tt-color-swatch" :class="{ 'is-selected': form.color === c }" :style="{ background: c }" :aria-label="'Colour ' + c" @click="form.color = c" />
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Coverage -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head tt-head-wrap">
                                    <span class="tt-dot"></span><span class="tt-card-title">Coverage</span>
                                    <v-spacer />
                                    <v-chip size="small" :color="form.districts.length ? 'success' : 'default'" variant="tonal" label>{{ form.districts.length }} of {{ allDistricts.length }} districts</v-chip>
                                </div>
                                <v-card-text class="tt-body">
                                    <div class="d-flex flex-wrap align-center ga-2 mb-3">
                                        <v-text-field v-model="districtSearch" placeholder="Search districts…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details style="max-width: 260px" />
                                        <v-spacer />
                                        <v-btn variant="outlined" size="small" class="text-none" prepend-icon="mdi-check-all" @click="selectAllAvailable">Select all available</v-btn>
                                        <v-btn variant="text" size="small" class="text-none" :disabled="!form.districts.length" @click="form.districts = []">Clear</v-btn>
                                    </div>

                                    <div v-for="d in pickerDivisions" :key="d.name" class="tt-div">
                                        <div class="tt-div-head" @click="toggleOpen(d.name)">
                                            <v-checkbox-btn :model-value="d.state.all" :indeterminate="d.state.some && !d.state.all" :disabled="!d.state.available" color="#0f9d6b" density="compact" @click.stop @update:model-value="toggleDivision(d)" />
                                            <span class="tt-div-name">{{ d.name }}</span>
                                            <span class="tt-sub ml-2">{{ d.state.selected }} / {{ d.state.available }} selected</span>
                                            <v-spacer />
                                            <v-icon size="18">{{ isOpen(d.name) ? "mdi-chevron-up" : "mdi-chevron-down" }}</v-icon>
                                        </div>
                                        <div v-show="isOpen(d.name)" class="tt-div-body">
                                            <button
                                                v-for="x in d.districts"
                                                :key="x.name"
                                                type="button"
                                                class="tt-dchip"
                                                :class="{ 'is-on': x.selected, 'is-taken': !!x.taken }"
                                                :disabled="!!x.taken"
                                                :title="x.taken ? 'Already in “' + x.taken + '”' : ''"
                                                @click="toggleDistrict(x.name)"
                                            >
                                                <v-icon size="14">{{ x.taken ? "mdi-lock-outline" : x.selected ? "mdi-check" : "mdi-plus" }}</v-icon>{{ x.name }}
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="!pickerDivisions.length" class="tt-empty">No district matches “{{ districtSearch }}”.</div>
                                    <div class="tt-sub mt-3"><v-icon size="14">mdi-lock-outline</v-icon> A district can belong to only one zone. Locked districts are already used by another zone.</div>
                                </v-card-text>
                            </v-card>

                            <!-- Rates -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Shipping Rate</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">How do you charge?</div>
                                    <v-row dense class="mb-3">
                                        <v-col v-for="r in rateTypes" :key="r.value" cols="6" sm="3">
                                            <div class="tt-rate-card" :class="{ 'is-selected': form.rateType === r.value }" @click="form.rateType = r.value">
                                                <v-icon size="20" :color="form.rateType === r.value ? '#0f9d6b' : '#78716c'">{{ r.icon }}</v-icon>
                                                <div class="tt-rate-name">{{ r.label }}</div>
                                                <div class="tt-sub">{{ r.hint }}</div>
                                            </div>
                                        </v-col>
                                    </v-row>

                                    <!-- flat -->
                                    <div v-if="form.rateType === 'flat'">
                                        <div class="tt-field-label">Flat rate (৳)</div>
                                        <v-text-field v-model.number="form.baseRate" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" style="max-width: 240px" />
                                    </div>

                                    <!-- weight -->
                                    <div v-else-if="form.rateType === 'weight'">
                                        <div class="tt-field-label">Weight tiers</div>
                                        <div v-for="(t, i) in form.weightTiers" :key="t.key" class="tt-tier">
                                            <span class="tt-tier-text">Up to</span>
                                            <v-text-field v-model.number="t.upTo" type="number" min="0" step="0.5" variant="outlined" density="compact" hide-details suffix="kg" style="max-width: 140px" />
                                            <span class="tt-tier-text">costs</span>
                                            <v-text-field v-model.number="t.price" type="number" min="0" variant="outlined" density="compact" hide-details prefix="৳" style="max-width: 140px" />
                                            <v-btn icon size="small" variant="text" color="error" :disabled="form.weightTiers.length <= 1" @click="removeTier('weightTiers', i)"><v-icon size="16">mdi-close</v-icon></v-btn>
                                        </div>
                                        <v-btn variant="outlined" size="small" class="text-none mt-1" prepend-icon="mdi-plus" @click="addTier('weightTiers')">Add tier</v-btn>
                                        <div class="tt-field-label mt-4">Each extra kg above the last tier</div>
                                        <v-text-field v-model.number="form.extraKgRate" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" style="max-width: 240px" />
                                    </div>

                                    <!-- value -->
                                    <div v-else-if="form.rateType === 'value'">
                                        <div class="tt-field-label">Order value tiers</div>
                                        <div v-for="(t, i) in form.valueTiers" :key="t.key" class="tt-tier">
                                            <span class="tt-tier-text">Orders up to</span>
                                            <v-text-field v-model.number="t.upTo" type="number" min="0" step="500" variant="outlined" density="compact" hide-details prefix="৳" style="max-width: 150px" />
                                            <span class="tt-tier-text">cost</span>
                                            <v-text-field v-model.number="t.price" type="number" min="0" variant="outlined" density="compact" hide-details prefix="৳" style="max-width: 130px" />
                                            <v-btn icon size="small" variant="text" color="error" :disabled="form.valueTiers.length <= 1" @click="removeTier('valueTiers', i)"><v-icon size="16">mdi-close</v-icon></v-btn>
                                        </div>
                                        <v-btn variant="outlined" size="small" class="text-none mt-1" prepend-icon="mdi-plus" @click="addTier('valueTiers')">Add tier</v-btn>
                                        <div class="tt-field-label mt-4">Orders above the last tier</div>
                                        <v-text-field v-model.number="form.valueAbovePrice" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" style="max-width: 240px" />
                                    </div>

                                    <div v-else class="tt-note"><v-icon size="16" color="#0f9d6b">mdi-gift-outline</v-icon>Shipping is free for every order in this zone.</div>

                                    <v-divider class="my-5" />

                                    <v-row dense>
                                        <v-col cols="12" sm="6" md="3">
                                            <div class="tt-field-label">Free shipping over</div>
                                            <v-text-field v-model.number="form.freeOver" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="Off" />
                                        </v-col>
                                        <v-col cols="12" sm="6" md="3">
                                            <div class="tt-field-label">Minimum order</div>
                                            <v-text-field v-model.number="form.minOrder" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="None" />
                                        </v-col>
                                        <v-col cols="12" sm="6" md="3">
                                            <div class="tt-field-label">Handling fee</div>
                                            <v-text-field v-model.number="form.handlingFee" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="0" />
                                        </v-col>
                                        <v-col cols="12" sm="6" md="3">
                                            <div class="tt-field-label">Max parcel weight</div>
                                            <v-text-field v-model.number="form.maxWeight" type="number" min="0" variant="outlined" density="comfortable" hide-details suffix="kg" placeholder="No limit" />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Delivery -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Delivery Time</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="6" sm="3">
                                            <div class="tt-field-label">Fastest (days)</div>
                                            <v-text-field v-model.number="form.etaMin" type="number" min="0" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                        <v-col cols="6" sm="3">
                                            <div class="tt-field-label">Slowest (days)</div>
                                            <v-text-field v-model.number="form.etaMax" type="number" min="0" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Order cut-off time</div>
                                            <v-text-field v-model="form.cutoff" type="time" variant="outlined" density="comfortable" hide-details />
                                            <div class="tt-sub mt-1">Orders after this time ship the next working day.</div>
                                        </v-col>
                                    </v-row>

                                    <div class="tt-toggle-row mt-4">
                                        <div>
                                            <div class="font-weight-medium">Express delivery</div>
                                            <div class="tt-sub">Offer a faster option for an extra fee</div>
                                        </div>
                                        <v-switch v-model="form.expressEnabled" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                    <v-row v-if="form.expressEnabled" dense class="mt-1">
                                        <v-col cols="6" sm="4">
                                            <div class="tt-field-label">Express fee</div>
                                            <v-text-field v-model.number="form.expressFee" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" />
                                        </v-col>
                                        <v-col cols="6" sm="4">
                                            <div class="tt-field-label">Delivery in</div>
                                            <v-select v-model="form.expressDays" :items="expressOptions" item-title="label" item-value="value" variant="outlined" density="comfortable" hide-details />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Payment -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Payment &amp; Cash on Delivery</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-toggle-row">
                                        <div>
                                            <div class="font-weight-medium">Cash on delivery</div>
                                            <div class="tt-sub">Let customers in this zone pay when the parcel arrives</div>
                                        </div>
                                        <v-switch v-model="form.codEnabled" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                    <v-row v-if="form.codEnabled" dense class="mt-1">
                                        <v-col cols="12" sm="4">
                                            <div class="tt-field-label">COD fee type</div>
                                            <v-btn-toggle v-model="form.codFeeType" mandatory density="comfortable" variant="outlined" divided class="tt-seg">
                                                <v-btn value="flat" class="text-none">Flat ৳</v-btn>
                                                <v-btn value="percent" class="text-none">Percent %</v-btn>
                                            </v-btn-toggle>
                                        </v-col>
                                        <v-col cols="6" sm="4">
                                            <div class="tt-field-label">COD fee</div>
                                            <v-text-field v-model.number="form.codFee" type="number" min="0" step="0.5" variant="outlined" density="comfortable" hide-details :prefix="form.codFeeType === 'flat' ? '৳' : ''" :suffix="form.codFeeType === 'percent' ? '%' : ''" />
                                        </v-col>
                                        <v-col cols="6" sm="4">
                                            <div class="tt-field-label">Max COD order</div>
                                            <v-text-field v-model.number="form.codMax" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="No limit" />
                                        </v-col>
                                    </v-row>

                                    <v-divider class="my-4" />
                                    <div class="tt-field-label">Require advance payment above</div>
                                    <v-text-field v-model.number="form.advanceAbove" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="Off" style="max-width: 260px" />
                                    <div class="tt-sub mt-1">Useful for high-value gadgets: orders above this amount must be paid online first.</div>
                                </v-card-text>
                            </v-card>

                            <!-- Handling -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Electronics Handling</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-toggle-row">
                                        <div>
                                            <div class="font-weight-medium">Ship batteries &amp; power banks</div>
                                            <div class="tt-sub">Turn off if your couriers refuse lithium-battery parcels in this zone</div>
                                        </div>
                                        <v-switch v-model="form.allowBatteries" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                    <div class="tt-toggle-row">
                                        <div>
                                            <div class="font-weight-medium">OTP or signature on delivery</div>
                                            <div class="tt-sub">Reduces “delivered but not received” disputes</div>
                                        </div>
                                        <v-switch v-model="form.otpRequired" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                    <div class="tt-toggle-row">
                                        <div>
                                            <div class="font-weight-medium">Insure high-value orders</div>
                                            <div class="tt-sub">Add courier insurance when the order is large</div>
                                        </div>
                                        <v-switch v-model="form.insuranceEnabled" color="#0f9d6b" density="compact" inset hide-details />
                                    </div>
                                    <v-row v-if="form.insuranceEnabled" dense class="mt-1">
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Insure orders above</div>
                                            <v-text-field v-model.number="form.insureOver" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Insurance fee</div>
                                            <v-text-field v-model.number="form.insuranceFee" type="number" min="0" step="0.5" variant="outlined" density="comfortable" hide-details suffix="% of order" />
                                        </v-col>
                                    </v-row>
                                    <v-divider class="my-4" />
                                    <div class="tt-field-label">Fragile item surcharge</div>
                                    <v-text-field v-model.number="form.fragileFee" type="number" min="0" variant="outlined" density="comfortable" hide-details prefix="৳" placeholder="0" style="max-width: 240px" />
                                    <div class="tt-sub mt-1">Added once per order when it contains items marked “fragile”.</div>
                                </v-card-text>
                            </v-card>

                            <!-- Couriers -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Couriers</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-courier-grid">
                                        <button v-for="c in courierOptions" :key="c.value" type="button" class="tt-courier" :class="{ 'is-on': form.couriers.includes(c.value) }" @click="toggleCourier(c.value)">
                                            <span class="tt-courier-ico"><v-icon size="18">{{ c.icon }}</v-icon></span>
                                            <span class="tt-courier-name">{{ c.label }}</span>
                                            <v-icon v-if="form.couriers.includes(c.value)" size="16" color="#0f9d6b">mdi-check-circle</v-icon>
                                        </button>
                                    </div>
                                    <div class="tt-field-label mt-4">Default courier</div>
                                    <v-select v-model="form.defaultCourier" :items="selectedCouriers" item-title="label" item-value="value" variant="outlined" density="comfortable" hide-details clearable placeholder="Pick from the couriers above" :disabled="!selectedCouriers.length" style="max-width: 300px" />
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

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Summary</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="d-flex align-center ga-3 mb-3">
                                            <span class="tt-zone-badge lg" :style="{ background: form.color }"><v-icon size="20" color="white">mdi-map-marker-radius-outline</v-icon></span>
                                            <div style="min-width: 0">
                                                <div class="font-weight-bold text-truncate">{{ form.title || "Untitled zone" }}</div>
                                                <div class="tt-sub">{{ summary.areas }}</div>
                                            </div>
                                        </div>
                                        <div v-for="r in summary.rows" :key="r.label" class="tt-option-row">
                                            <span class="tt-muted">{{ r.label }}</span><span class="font-weight-medium text-right">{{ r.value }}</span>
                                        </div>
                                    </v-card-text>
                                </v-card>

                                <v-card flat class="tt-card mb-5">
                                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Customer Experience</span></div>
                                    <v-card-text class="tt-body">
                                        <div class="tt-option-row"><span>Show delivery estimate on product page</span><v-checkbox-btn v-model="form.showEta" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>SMS customer when parcel is dispatched</span><v-checkbox-btn v-model="form.smsOnDispatch" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Auto-assign the default courier</span><v-checkbox-btn v-model="form.autoAssign" color="#0f9d6b" /></div>
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
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Delete zone</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="pa-4">Delete <b>{{ deletedItem ? deletedItem.title : "this zone" }}</b>? Its districts will become uncovered and customers there will not be able to check out until you cover them again.</v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" @click="cancelDelete">Cancel</v-btn>
                        <v-btn color="error" variant="flat" @click="confirmDelete">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Uncovered districts dialog -->
            <v-dialog v-model="uncoveredDialog" max-width="560">
                <v-card>
                    <v-toolbar color="secondary" flat height="48">
                        <v-toolbar-title class="text-subtitle-1 font-weight-bold">Districts without shipping</v-toolbar-title>
                        <v-spacer />
                        <v-btn icon="mdi-close" variant="text" size="small" @click="uncoveredDialog = false" />
                    </v-toolbar>
                    <v-card-text class="pa-5">
                        <div class="tt-sub mb-3">{{ uncoveredDistricts.length }} districts are not in any active zone. Customers there cannot receive an order.</div>
                        <div v-for="g in uncoveredByDivision" :key="g.name" class="mb-3">
                            <div class="tt-field-label">{{ g.name }}</div>
                            <v-chip v-for="n in g.names" :key="n" size="small" variant="tonal" color="warning" class="mr-1 mb-1" label>{{ n }}</v-chip>
                        </div>
                    </v-card-text>
                    <v-card-actions class="pb-4 px-4">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="uncoveredDialog = false">Close</v-btn>
                        <v-btn color="#0f9d6b" variant="flat" class="text-none" prepend-icon="mdi-plus" @click="addForUncovered">Create zone for these</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" color="#0f9d6b" timeout="4000">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
// Set to false once your API returns real zones, so sample data is never shown
const USE_DEMO_WHEN_EMPTY = true;

let rowSeq = 0;
const key = () => ++rowSeq;

const DIVISIONS = [
    { name: "Dhaka", districts: ["Dhaka", "Faridpur", "Gazipur", "Gopalganj", "Kishoreganj", "Madaripur", "Manikganj", "Munshiganj", "Narayanganj", "Narsingdi", "Rajbari", "Shariatpur", "Tangail"] },
    { name: "Chattogram", districts: ["Bandarban", "Brahmanbaria", "Chandpur", "Chattogram", "Cumilla", "Cox's Bazar", "Feni", "Khagrachhari", "Lakshmipur", "Noakhali", "Rangamati"] },
    { name: "Rajshahi", districts: ["Bogura", "Chapainawabganj", "Joypurhat", "Naogaon", "Natore", "Pabna", "Rajshahi", "Sirajganj"] },
    { name: "Khulna", districts: ["Bagerhat", "Chuadanga", "Jashore", "Jhenaidah", "Khulna", "Kushtia", "Magura", "Meherpur", "Narail", "Satkhira"] },
    { name: "Barishal", districts: ["Barguna", "Barishal", "Bhola", "Jhalokati", "Patuakhali", "Pirojpur"] },
    { name: "Sylhet", districts: ["Habiganj", "Moulvibazar", "Sunamganj", "Sylhet"] },
    { name: "Rangpur", districts: ["Dinajpur", "Gaibandha", "Kurigram", "Lalmonirhat", "Nilphamari", "Panchagarh", "Rangpur", "Thakurgaon"] },
    { name: "Mymensingh", districts: ["Jamalpur", "Mymensingh", "Netrokona", "Sherpur"] },
];
const ALL_DISTRICTS = DIVISIONS.flatMap((d) => d.districts.map((n) => ({ name: n, division: d.name })));
const COLORS = ["#e25311", "#0f9d6b", "#2f5be7", "#7c3aed", "#f59e0b", "#ec4899", "#0f766e"];
const COURIERS = [
    { value: "pathao", label: "Pathao", icon: "mdi-moped-outline" },
    { value: "steadfast", label: "Steadfast", icon: "mdi-truck-fast-outline" },
    { value: "redx", label: "RedX", icon: "mdi-package-variant-closed" },
    { value: "paperfly", label: "Paperfly", icon: "mdi-airplane" },
    { value: "ecourier", label: "eCourier", icon: "mdi-truck-delivery-outline" },
    { value: "sa", label: "SA Paribahan", icon: "mdi-bus" },
    { value: "sundarban", label: "Sundarban", icon: "mdi-ferry" },
    { value: "pickup", label: "Store pickup", icon: "mdi-storefront-outline" },
];

const blankForm = () => ({
    id: null,
    title: "",
    description: "",
    status: "Active",
    priority: 0,
    color: COLORS[0],
    districts: [],

    rateType: "flat",
    baseRate: 60,
    weightTiers: [
        { key: key(), upTo: 1, price: 60 },
        { key: key(), upTo: 3, price: 90 },
        { key: key(), upTo: 5, price: 130 },
    ],
    extraKgRate: 30,
    valueTiers: [
        { key: key(), upTo: 5000, price: 120 },
        { key: key(), upTo: 15000, price: 80 },
    ],
    valueAbovePrice: 0,
    freeOver: null,
    minOrder: null,
    handlingFee: 0,
    maxWeight: null,

    etaMin: 1,
    etaMax: 3,
    cutoff: "16:00",
    expressEnabled: false,
    expressFee: 100,
    expressDays: 0,

    codEnabled: true,
    codFeeType: "percent",
    codFee: 1,
    codMax: null,
    advanceAbove: null,

    allowBatteries: true,
    otpRequired: false,
    insuranceEnabled: false,
    insureOver: 20000,
    insuranceFee: 1,
    fragileFee: 0,

    couriers: [],
    defaultCourier: null,
    showEta: true,
    smsOnDispatch: true,
    autoAssign: false,
});

// Fills defaults for anything the API did not send, and gives every tier a stable key
const normalize = (raw, i = 0) => {
    const f = blankForm();
    const z = { ...f, ...raw };
    z.id = raw.id ?? raw.id === 0 ? raw.id : null;
    z.color = raw.color || COLORS[i % COLORS.length];
    z.districts = Array.isArray(raw.districts) ? [...raw.districts] : [];
    z.couriers = Array.isArray(raw.couriers) ? [...raw.couriers] : [];
    z.weightTiers = (raw.weightTiers && raw.weightTiers.length ? raw.weightTiers : f.weightTiers).map((t) => ({ key: key(), upTo: t.upTo, price: t.price }));
    z.valueTiers = (raw.valueTiers && raw.valueTiers.length ? raw.valueTiers : f.valueTiers).map((t) => ({ key: key(), upTo: t.upTo, price: t.price }));
    return z;
};

const DEMO = () => {
    const used = ["Dhaka", "Gazipur", "Narayanganj", "Narsingdi", "Munshiganj", "Manikganj", "Chattogram", "Sylhet", "Rajshahi", "Khulna", "Barishal", "Rangpur", "Mymensingh", "Cumilla", "Bogura", "Bandarban", "Rangamati", "Khagrachhari"];
    const rest = ALL_DISTRICTS.map((d) => d.name).filter((n) => !used.includes(n));
    return [
        { id: 1, title: "Dhaka City", description: "Inside Dhaka, same-day eligible", status: "Active", priority: 1, color: "#e25311", districts: ["Dhaka"], rateType: "flat", baseRate: 60, freeOver: 5000, etaMin: 1, etaMax: 2, expressEnabled: true, expressFee: 100, expressDays: 0, codEnabled: true, codFeeType: "percent", codFee: 1, couriers: ["pathao", "steadfast", "redx"], defaultCourier: "pathao", autoAssign: true },
        { id: 2, title: "Dhaka Suburbs", description: "Around Dhaka", status: "Active", priority: 2, color: "#0f9d6b", districts: ["Gazipur", "Narayanganj", "Narsingdi", "Munshiganj", "Manikganj"], rateType: "flat", baseRate: 90, freeOver: 8000, etaMin: 2, etaMax: 3, codEnabled: true, codFeeType: "percent", codFee: 1.5, couriers: ["steadfast", "redx"], defaultCourier: "steadfast" },
        { id: 3, title: "Chattogram Metro", description: "Port city", status: "Active", priority: 3, color: "#2f5be7", districts: ["Chattogram"], rateType: "flat", baseRate: 110, freeOver: 10000, etaMin: 2, etaMax: 3, codEnabled: true, codFeeType: "percent", codFee: 1.5, couriers: ["steadfast", "paperfly"], defaultCourier: "steadfast" },
        { id: 4, title: "Major Cities", description: "Divisional headquarters and big towns", status: "Active", priority: 4, color: "#7c3aed", districts: ["Sylhet", "Rajshahi", "Khulna", "Barishal", "Rangpur", "Mymensingh", "Cumilla", "Bogura"], rateType: "weight", weightTiers: [{ upTo: 1, price: 120 }, { upTo: 3, price: 160 }, { upTo: 5, price: 220 }], extraKgRate: 30, freeOver: 15000, etaMin: 3, etaMax: 4, codEnabled: true, codFeeType: "percent", codFee: 2, codMax: 50000, couriers: ["steadfast", "sa", "paperfly"], defaultCourier: "steadfast", otpRequired: true },
        { id: 5, title: "Rest of Bangladesh", description: "All remaining districts", status: "Active", priority: 5, color: "#f59e0b", districts: rest, rateType: "weight", weightTiers: [{ upTo: 1, price: 140 }, { upTo: 3, price: 190 }, { upTo: 5, price: 260 }], extraKgRate: 40, etaMin: 4, etaMax: 6, codEnabled: true, codFeeType: "percent", codFee: 2.5, codMax: 25000, couriers: ["sa", "sundarban"], defaultCourier: "sa", advanceAbove: 30000, insuranceEnabled: true },
        { id: 6, title: "Hill Tracts", description: "Waiting for a courier partner", status: "Draft", priority: 6, color: "#ec4899", districts: ["Bandarban", "Rangamati", "Khagrachhari"], rateType: "weight", weightTiers: [{ upTo: 1, price: 180 }, { upTo: 3, price: 240 }], extraKgRate: 50, etaMin: 5, etaMax: 8, codEnabled: false, couriers: [] },
    ];
};

const moneyFmt = (v) => "৳ " + Number(v || 0).toLocaleString("en-IN");

export default {
    name: "ShippingZones",

    data() {
        return {
            tab: "list",
            loading: false,
            usingDemo: false,
            demoHidden: false,
            searchValue: "",
            statusFilter: "All",

            zones: [],
            form: blankForm(),
            districtSearch: "",
            openDivs: ["Dhaka"],

            saving: false,
            savingDraft: false,
            deleteDialog: false,
            deletedItem: null,
            uncoveredDialog: false,

            checker: { district: null, weight: 1, total: 5000 },

            allDistricts: ALL_DISTRICTS,
            colorOptions: COLORS,
            courierOptions: COURIERS,
            statusOptions: [
                { label: "Active", value: "Active", hint: "Customers can choose this zone at checkout" },
                { label: "Draft", value: "Draft", hint: "Saved, but hidden from checkout" },
                { label: "Inactive", value: "Inactive", hint: "Paused. Keep the settings for later" },
            ],
            rateTypes: [
                { value: "flat", label: "Flat rate", hint: "One price", icon: "mdi-cash" },
                { value: "weight", label: "By weight", hint: "Heavier costs more", icon: "mdi-weight-kilogram" },
                { value: "value", label: "By order value", hint: "Bigger orders pay less", icon: "mdi-receipt-text-outline" },
                { value: "free", label: "Free", hint: "No charge", icon: "mdi-gift-outline" },
            ],
            expressOptions: [
                { label: "Same day", value: 0 },
                { label: "Next day", value: 1 },
                { label: "Within 2 days", value: 2 },
            ],

            headers: [
                { text: "Zone", value: "title", sortable: true, width: 280 },
                { text: "Coverage", value: "districtCount", sortable: true, width: 120 },
                { text: "Delivery", value: "etaText", sortable: true, width: 110 },
                { text: "Rate", value: "rateText", sortable: true, width: 130 },
                { text: "Free over", value: "freeText", sortable: true, width: 100 },
                { text: "COD", value: "codText", sortable: true, width: 100 },
                { text: "Status", value: "status", sortable: true, width: 100 },
                { text: "Operation", value: "operation", width: 120 },
            ],

            snackbar: false,
            snackbarText: "",
        };
    },

    computed: {
        activeZones() {
            return this.zones.filter((z) => z.status === "Active").length;
        },

        // ── Coverage across active zones ──
        coveredSet() {
            const s = new Set();
            this.zones.filter((z) => z.status === "Active").forEach((z) => z.districts.forEach((n) => s.add(n)));
            return s;
        },
        coveredCount() {
            return this.coveredSet.size;
        },
        uncoveredDistricts() {
            return this.allDistricts.filter((d) => !this.coveredSet.has(d.name));
        },
        uncoveredByDivision() {
            return DIVISIONS.map((d) => ({ name: d.name, names: this.uncoveredDistricts.filter((x) => x.division === d.name).map((x) => x.name) })).filter((g) => g.names.length);
        },
        divisionCoverage() {
            return DIVISIONS.map((d) => ({ name: d.name, total: d.districts.length, covered: d.districts.filter((n) => this.coveredSet.has(n)).length }));
        },
        avgEta() {
            const act = this.zones.filter((z) => z.status === "Active");
            if (!act.length) return "–";
            const avg = act.reduce((a, z) => a + (Number(z.etaMin) + Number(z.etaMax)) / 2, 0) / act.length;
            return (Math.round(avg * 10) / 10).toString().replace(/\.0$/, "") + " days";
        },
        rateRange() {
            const act = this.zones.filter((z) => z.status === "Active" && z.rateType !== "free");
            if (!act.length) return "–";
            const rates = act.map((z) => this.startingRate(z));
            const lo = Math.min(...rates);
            const hi = Math.max(...rates);
            return lo === hi ? moneyFmt(lo) : `${moneyFmt(lo)} – ${moneyFmt(hi)}`;
        },
        statCards() {
            const unc = this.uncoveredDistricts.length;
            return [
                { label: "Total zones", value: this.zones.length, icon: "mdi-map-marker-radius-outline", color: "#e25311", tint: "#fdece3", note: `${this.activeZones} active · ${this.zones.length - this.activeZones} not live` },
                { label: "Districts covered", value: `${this.coveredCount} / ${this.allDistricts.length}`, icon: "mdi-map-check-outline", color: unc ? "#d97706" : "#0f9d6b", tint: unc ? "#fff1d1" : "#dff5ec", progress: Math.round((this.coveredCount / this.allDistricts.length) * 100), note: unc ? `${unc} uncovered · click to see` : "Every district is covered", click: unc ? () => (this.uncoveredDialog = true) : null },
                { label: "Average delivery", value: this.avgEta, icon: "mdi-clock-fast", color: "#2f5be7", tint: "#e6edff", note: "Across active zones" },
                { label: "Starting rates", value: this.rateRange, icon: "mdi-cash-multiple", color: "#7c3aed", tint: "#efe8ff", note: "Lowest to highest base charge" },
            ];
        },

        // ── Table ──
        tableItems() {
            return this.zones
                .filter((z) => this.statusFilter === "All" || z.status === this.statusFilter)
                .map((z) => ({
                    ...z,
                    areas: this.areasText(z),
                    districtCount: z.districts.length,
                    etaText: this.etaText(z),
                    rateText: this.rateText(z),
                    rateType: this.rateTypes.find((r) => r.value === z.rateType).label,
                    freeText: z.rateType === "free" ? "Always" : z.freeOver ? moneyFmt(z.freeOver) : "–",
                    cod: z.codEnabled,
                    codText: z.codEnabled ? (z.codMax ? "Up to " + this.shortMoney(z.codMax) : "Yes") : "No",
                }));
        },

        // ── Rate checker ──
        districtItems() {
            return this.allDistricts.map((d) => ({ name: d.name, label: `${d.name} · ${d.division}` }));
        },
        checkResult() {
            const d = this.checker.district;
            if (!d) return { state: "idle" };
            const zone = this.zones.filter((z) => z.status === "Active").sort((a, b) => a.priority - b.priority).find((z) => z.districts.includes(d));
            if (!zone) return { state: "none" };
            const r = this.calcCharge(zone, Number(this.checker.weight) || 0, Number(this.checker.total) || 0);
            return { state: "ok", zone, ...r };
        },

        // ── Add / edit page ──
        // district → name of the other zone that already uses it
        takenMap() {
            const m = {};
            this.zones.forEach((z) => {
                if (z.id !== null && z.id === this.form.id) return;
                z.districts.forEach((n) => (m[n] = z.title));
            });
            return m;
        },
        pickerDivisions() {
            const q = (this.districtSearch || "").trim().toLowerCase();
            return DIVISIONS.map((d) => {
                const all = d.districts.map((n) => ({ name: n, selected: this.form.districts.includes(n), taken: this.takenMap[n] || "" }));
                const available = all.filter((x) => !x.taken);
                const selected = available.filter((x) => x.selected).length;
                return {
                    name: d.name,
                    districts: q ? all.filter((x) => x.name.toLowerCase().includes(q) || d.name.toLowerCase().includes(q)) : all,
                    state: { available: available.length, selected, all: available.length > 0 && selected === available.length, some: selected > 0 },
                };
            }).filter((d) => d.districts.length);
        },
        selectedCouriers() {
            return this.courierOptions.filter((c) => this.form.couriers.includes(c.value));
        },
        summary() {
            const f = this.form;
            const divs = new Set(this.allDistricts.filter((d) => f.districts.includes(d.name)).map((d) => d.division));
            const rows = [
                { label: "Rate", value: this.rateText(f) },
                { label: "Free shipping", value: f.rateType === "free" ? "Always" : f.freeOver ? "Over " + moneyFmt(f.freeOver) : "Off" },
                { label: "Delivery", value: this.etaText(f) },
                { label: "Express", value: f.expressEnabled ? `${moneyFmt(f.expressFee)} · ${this.expressOptions.find((o) => o.value === f.expressDays).label}` : "Off" },
                { label: "Cash on delivery", value: f.codEnabled ? (f.codFeeType === "percent" ? `${f.codFee || 0}% fee` : `${moneyFmt(f.codFee)} fee`) : "Off" },
                { label: "Couriers", value: f.couriers.length ? f.couriers.length + " selected" : "None yet" },
            ];
            return { areas: f.districts.length ? `${f.districts.length} districts · ${divs.size} division${divs.size === 1 ? "" : "s"}` : "No districts yet", rows };
        },
        aiSuggestion() {
            const f = this.form;
            if (!f.districts.length) return "Start by picking districts. Zones with a clear, small area are easier to price.";
            if (!f.couriers.length) return "Pick at least one courier so orders in this zone can be booked automatically.";
            if (f.rateType !== "free" && !f.freeOver) return "A free-shipping threshold slightly above your average order value usually lifts basket size.";
            if (f.codEnabled && !f.codMax && !f.advanceAbove) return "Set a COD limit or an advance-payment threshold. High-value gadgets are the riskiest COD orders.";
            return "This zone looks complete. Check the summary, then create it.";
        },
    },

    methods: {
        money: moneyFmt,
        shortMoney(v) {
            return v >= 1000 ? "৳ " + Math.round(v / 100) / 10 + "K" : "৳ " + v;
        },
        statusColor(s) {
            return { Active: "success", Draft: "default", Inactive: "error" }[s] || "default";
        },
        etaText(z) {
            const a = Number(z.etaMin);
            const b = Number(z.etaMax);
            if (!b && !a) return "Same day";
            return a === b ? `${a} day${a === 1 ? "" : "s"}` : `${a}–${b} days`;
        },
        // the lowest charge a customer can see in this zone, used for the cards
        startingRate(z) {
            if (z.rateType === "flat") return Number(z.baseRate) || 0;
            if (z.rateType === "weight") return Math.min(...z.weightTiers.map((t) => Number(t.price) || 0));
            if (z.rateType === "value") return Math.min(...z.valueTiers.map((t) => Number(t.price) || 0), Number(z.valueAbovePrice) || 0);
            return 0;
        },
        rateText(z) {
            if (z.rateType === "free") return "Free";
            if (z.rateType === "flat") return moneyFmt(z.baseRate);
            if (z.rateType === "weight") return "From " + moneyFmt(this.startingRate(z));
            return moneyFmt(this.startingRate(z)) + " +";
        },
        areasText(z) {
            if (!z.districts.length) return "No districts";
            const first = z.districts.slice(0, 3).join(", ");
            return z.districts.length > 3 ? `${first} +${z.districts.length - 3} more` : first;
        },

        // charge for one order: tier lookup, free-shipping threshold, then handling fee
        calcCharge(z, weight, total) {
            if (z.rateType === "free" || (z.freeOver && total >= z.freeOver)) return { charge: 0, free: true };
            let c = 0;
            if (z.rateType === "flat") c = Number(z.baseRate) || 0;
            else if (z.rateType === "weight") {
                const tiers = [...z.weightTiers].sort((a, b) => a.upTo - b.upTo);
                const hit = tiers.find((t) => weight <= t.upTo);
                if (hit) c = Number(hit.price) || 0;
                else {
                    const last = tiers[tiers.length - 1];
                    c = (Number(last.price) || 0) + Math.ceil(weight - last.upTo) * (Number(z.extraKgRate) || 0);
                }
            } else {
                const tiers = [...z.valueTiers].sort((a, b) => a.upTo - b.upTo);
                const hit = tiers.find((t) => total <= t.upTo);
                c = hit ? Number(hit.price) || 0 : Number(z.valueAbovePrice) || 0;
            }
            return { charge: c + (Number(z.handlingFee) || 0), free: false };
        },

        // ── Load ──
        loadZones() {
            this.loading = true;
            this.axios
                .get("/api/shipping-zones")
                .then((response) => {
                    const data = response.data && response.data.success ? response.data.data : null;
                    if (data && data.length) {
                        this.zones = data.map((z, i) => normalize(z, i));
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
                this.zones = DEMO().map((z, i) => normalize(z, i));
                this.usingDemo = true;
            } else this.zones = [];
        },

        // ── Open add / edit ──
        openAdd() {
            this.form = blankForm();
            this.form.color = COLORS[this.zones.length % COLORS.length];
            this.form.priority = this.zones.length + 1;
            this.districtSearch = "";
            this.openDivs = ["Dhaka"];
            this.tab = "add";
        },
        addForUncovered() {
            this.uncoveredDialog = false;
            this.openAdd();
            this.form.title = "New coverage zone";
            this.form.districts = this.uncoveredDistricts.map((d) => d.name);
            this.openDivs = DIVISIONS.map((d) => d.name);
        },
        editItem(item) {
            const z = this.zones.find((x) => x.id === item.id) || item;
            this.form = normalize(JSON.parse(JSON.stringify(z)));
            this.form.id = z.id;
            this.districtSearch = "";
            this.openDivs = DIVISIONS.filter((d) => d.districts.some((n) => this.form.districts.includes(n))).map((d) => d.name);
            this.tab = "add";
        },
        duplicateZone(item) {
            const z = this.zones.find((x) => x.id === item.id) || item;
            this.form = normalize(JSON.parse(JSON.stringify(z)));
            this.form.id = null;
            this.form.title = z.title + " (copy)";
            this.form.districts = []; // districts can only live in one zone
            this.form.status = "Draft";
            this.openDivs = ["Dhaka"];
            this.tab = "add";
            this.showSuccess("Copied. Pick the districts for the new zone.");
        },
        closeAdd() {
            this.tab = "list";
        },

        // ── District picker ──
        isOpen(name) {
            return !!this.districtSearch || this.openDivs.includes(name);
        },
        toggleOpen(name) {
            this.openDivs = this.openDivs.includes(name) ? this.openDivs.filter((n) => n !== name) : [...this.openDivs, name];
        },
        toggleDistrict(name) {
            if (this.takenMap[name]) return;
            this.form.districts = this.form.districts.includes(name) ? this.form.districts.filter((n) => n !== name) : [...this.form.districts, name];
        },
        toggleDivision(d) {
            const free = DIVISIONS.find((x) => x.name === d.name).districts.filter((n) => !this.takenMap[n]);
            if (d.state.all) this.form.districts = this.form.districts.filter((n) => !free.includes(n));
            else this.form.districts = Array.from(new Set([...this.form.districts, ...free]));
        },
        selectAllAvailable() {
            this.form.districts = this.allDistricts.map((d) => d.name).filter((n) => !this.takenMap[n]);
        },

        // ── Tiers & couriers ──
        addTier(listName) {
            const list = this.form[listName];
            const last = list[list.length - 1];
            list.push({ key: key(), upTo: last ? Number(last.upTo) + (listName === "weightTiers" ? 2 : 5000) : 1, price: last ? Number(last.price) : 0 });
        },
        removeTier(listName, i) {
            if (this.form[listName].length > 1) this.form[listName].splice(i, 1);
        },
        toggleCourier(v) {
            const f = this.form;
            f.couriers = f.couriers.includes(v) ? f.couriers.filter((c) => c !== v) : [...f.couriers, v];
            if (f.defaultCourier && !f.couriers.includes(f.defaultCourier)) f.defaultCourier = null;
            if (!f.defaultCourier && f.couriers.length === 1) f.defaultCourier = f.couriers[0];
        },

        // ── Validate & save ──
        validate() {
            const f = this.form;
            if (!f.title || !f.title.trim()) return "Zone name is required.";
            if (this.zones.some((z) => z.id !== f.id && z.title.trim().toLowerCase() === f.title.trim().toLowerCase())) return "Another zone already uses this name.";
            if (!f.districts.length) return "Pick at least one district for this zone.";
            const clash = f.districts.find((n) => this.takenMap[n]);
            if (clash) return `${clash} already belongs to “${this.takenMap[clash]}”.`;
            if (Number(f.etaMin) > Number(f.etaMax)) return "Fastest delivery cannot be slower than the slowest.";
            if (f.rateType === "weight" || f.rateType === "value") {
                const list = f.rateType === "weight" ? f.weightTiers : f.valueTiers;
                const ups = list.map((t) => Number(t.upTo));
                if (ups.some((u) => !(u > 0))) return "Every tier needs a limit above zero.";
                if (new Set(ups).size !== ups.length) return "Two tiers have the same limit.";
            }
            if (f.codEnabled && f.codFeeType === "percent" && Number(f.codFee) > 100) return "COD fee cannot be more than 100%.";
            return null;
        },
        buildPayload(status) {
            const f = this.form;
            const clean = (list) => [...list].sort((a, b) => a.upTo - b.upTo).map((t) => ({ upTo: Number(t.upTo), price: Number(t.price) || 0 }));
            return {
                ...f,
                status: status || f.status,
                title: f.title.trim(),
                weightTiers: clean(f.weightTiers),
                valueTiers: clean(f.valueTiers),
            };
        },
        async saveZone(status) {
            const err = this.validate();
            if (err) return this.showError(err);

            const flag = status === "Draft" ? "savingDraft" : "saving";
            this[flag] = true;
            const payload = this.buildPayload(status);
            try {
                let saved = payload;
                // REST-style endpoints: POST /api/shipping-zones, PUT /api/shipping-zones/:id
                if (!this.usingDemo) {
                    const response = payload.id ? await this.axios.put(`/api/shipping-zones/${payload.id}`, payload) : await this.axios.post("/api/shipping-zones", payload);
                    if (response.data && response.data.success === false) {
                        this.showError(response.data.message || "Save failed.");
                        return;
                    }
                    if (response.data && response.data.data) saved = { ...payload, ...response.data.data };
                } else if (!payload.id) {
                    saved = { ...payload, id: Date.now() };
                }
                const zone = normalize(saved, this.zones.length);
                const idx = this.zones.findIndex((z) => z.id === zone.id);
                if (idx >= 0) this.zones.splice(idx, 1, zone);
                else this.zones.push(zone);

                this.showSuccess(payload.id ? "Zone updated." : "Zone created.");
                this.tab = "list";
            } catch (error) {
                console.error(error);
                this.showError("An error occurred while saving.");
            } finally {
                this[flag] = false;
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
                this.zones = this.zones.filter((z) => z.id !== id);
                this.showSuccess("Zone deleted.");
            };
            if (this.usingDemo) {
                done();
                this.cancelDelete();
                return;
            }
            this.axios
                .delete(`/api/shipping-zones/${id}`)
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
        document.title = "Shipping Zones";
        this.loadZones();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-zone {
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
.tt-dot-sm { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-body { padding: 16px 18px 18px; }
.tt-field-label { font-size: 11.5px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #57534e; margin-bottom: 8px; }
.tt-required { color: #dc2626; }
.tt-sticky { position: sticky; top: 16px; }
.tt-empty { padding: 28px 16px; text-align: center; color: var(--muted); font-size: 13px; }

/* ───────── Summary cards ───────── */
.tt-stat { padding: 16px; }
.tt-stat.is-click { cursor: pointer; transition: border-color 0.15s, transform 0.15s; }
.tt-stat.is-click:hover { border-color: #f59e0b !important; transform: translateY(-2px); }
.tt-stat-icon { width: 42px; height: 42px; border-radius: 11px; display: grid; place-items: center; flex: none; }
.tt-stat-label { font-size: 12px; font-weight: 500; color: var(--muted); }
.tt-stat-value { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; white-space: nowrap; }
.tt-progress { height: 6px; border-radius: 4px; background: #efebe7; overflow: hidden; }
.tt-progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }

/* ───────── Table cells ───────── */
.tt-name-item { padding-inline: 4px !important; cursor: pointer; border-radius: 6px; transition: transform 0.15s ease, background-color 0.15s ease; }
.tt-name-item:hover { background-color: var(--tint); transform: translateX(3px); }
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-zone-badge { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; flex: none; margin-right: 12px; }
.tt-zone-badge.lg { width: 44px; height: 44px; border-radius: 12px; margin-right: 0; }
.tt-add-new {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    margin: 12px -18px -18px; padding: 14px; font-size: 13px; font-weight: 600; color: var(--accent); cursor: pointer;
    border-top: 1px solid var(--line);
}
.tt-add-new:hover { background: var(--tint); }

/* ───────── Rate checker ───────── */
.tt-check-result { margin-top: 14px; padding: 12px 14px; border-radius: 10px; border: 1px dashed var(--line); background: #fbfafa; font-size: 13px; display: flex; flex-direction: column; gap: 2px; }
.tt-check-result.idle, .tt-check-result.none { flex-direction: row; align-items: center; gap: 8px; color: var(--muted); }
.tt-check-result.none { background: #fff6e6; border-color: #f3d78a; color: #8a5a00; }
.tt-check-result.ok { border-style: solid; border-color: #b7e6cd; background: #eaf8f1; }
.tt-check-zone { font-weight: 600; display: inline-flex; align-items: center; }
.tt-check-price { font-size: 20px; font-weight: 700; color: #0f7a4d; }
.tt-cov-row { margin-bottom: 12px; font-size: 13px; }
.tt-cov-row:last-child { margin-bottom: 0; }

/* ───────── District picker ───────── */
.tt-div { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 8px; overflow: hidden; }
.tt-div-head { display: flex; align-items: center; gap: 4px; padding: 4px 12px 4px 6px; background: #fbfafa; cursor: pointer; }
.tt-div-head:hover { background: var(--tint); }
.tt-div-name { font-size: 13.5px; font-weight: 600; }
.tt-div-body { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px; border-top: 1px solid var(--line); }
.tt-dchip {
    display: inline-flex; align-items: center; gap: 5px; height: 30px; padding: 0 12px 0 8px;
    border-radius: 16px; border: 1px solid var(--line); background: #fff; font-family: inherit; font-size: 12.5px; font-weight: 500; color: var(--ink);
    cursor: pointer; transition: border-color 0.15s, background 0.15s, color 0.15s;
}
.tt-dchip:hover:not(:disabled) { border-color: #0f9d6b; color: #0f9d6b; }
.tt-dchip.is-on { background: #eaf8f1; border-color: #0f9d6b; color: #0f7a4d; font-weight: 600; }
.tt-dchip.is-taken { background: #f5f3f0; color: #a8a29e; cursor: not-allowed; text-decoration: line-through; }

/* ───────── Rate type cards, tiers, toggles ───────── */
.tt-rate-card { height: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 12px; cursor: pointer; background: #fbfafa; transition: border-color 0.15s, background 0.15s; }
.tt-rate-card:hover { border-color: #0f9d6b; }
.tt-rate-card.is-selected { border-color: #0f9d6b; background: #eaf8f1; }
.tt-rate-name { font-size: 13px; font-weight: 600; margin-top: 6px; }
.tt-tier { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; }
.tt-tier-text { font-size: 13px; color: var(--muted); min-width: 52px; }
.tt-note { display: flex; align-items: center; gap: 8px; padding: 12px 14px; border-radius: 10px; background: #eaf8f1; font-size: 13px; }
.tt-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 13.5px; }
.tt-toggle-row:last-of-type { border-bottom: 0; }
.tt-seg { height: 48px; }

/* ───────── Couriers ───────── */
.tt-courier-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
.tt-courier { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 10px; background: #fbfafa; font-family: inherit; font-size: 13px; font-weight: 500; color: var(--ink); cursor: pointer; text-align: left; transition: border-color 0.15s, background 0.15s; }
.tt-courier:hover { border-color: #0f9d6b; }
.tt-courier.is-on { border-color: #0f9d6b; background: #eaf8f1; }
.tt-courier-ico { width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; background: #fff; border: 1px solid var(--line); flex: none; }
.tt-courier-name { flex: 1; }

/* ───────── Colour swatches ───────── */
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

/* ───────── EasyDataTable theme ───────── */
:deep(.customize-table) {
    --easy-table-border: 1px solid #e7e2dd;
    --easy-table-row-border: 1px solid #e7e2dd;
    --easy-table-header-font-size: 12px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #57534e;
    --easy-table-header-background-color: #f5f1ec;
    --easy-table-body-row-height: 64px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #fdece3;
    --easy-table-footer-background-color: #ffffff;
    font-family: Poppins, "Segoe UI", sans-serif;
}
</style>