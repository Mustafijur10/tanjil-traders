<template>
    <v-slide-x-transition appear>
        <div class="tt-grp">
            <v-window v-model="tab">
                <!-- ═════════════════════════ Group list ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <!-- Hero -->
                    <header class="tt-hero">
                        <div class="d-flex flex-wrap align-end ga-4">
                            <div>
                                <h1 class="tt-h1">Customer groups</h1>
                                <div class="tt-hero-sub">Sort buyers into groups, then give each group its own discount, delivery and support perks</div>
                            </div>
                            <v-spacer />
                            <div class="d-flex flex-wrap align-center ga-3">
                                <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-download" @click="exportCsv">Export CSV</v-btn>
                                <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="openAdd">Create group</v-btn>
                            </div>
                        </div>

                        <div class="tt-hero-strip">
                            <div v-for="s in heroStats" :key="s.label" class="tt-hero-item" :style="{ '--hue': s.color }">
                                <div class="tt-hero-icon"><v-icon :icon="s.icon" size="20" color="white" /></div>
                                <div class="flex-grow-1">
                                    <div class="tt-hero-value">{{ s.value }}</div>
                                    <div class="tt-hero-label">{{ s.label }}</div>
                                    <div class="tt-hero-note">{{ s.note }}</div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Insight row -->
                    <v-row dense class="mt-3">
                        <v-col cols="12" md="7">
                            <v-card flat class="tt-card h-100">
                                <div class="tt-head">
                                    <div class="tt-head-ic"><v-icon size="16">mdi-chart-box-outline</v-icon></div>
                                    <div>
                                        <div class="tt-card-title">Lifetime sales by group</div>
                                        <div class="tt-sub">A customer can sit in more than one group, so shares can overlap</div>
                                    </div>
                                </div>
                                <div class="tt-body">
                                    <div v-if="!revenueShare.length" class="tt-empty">Create a group to see how much each one sells.</div>
                                    <button v-for="g in revenueShare" :key="g.id" type="button" class="tt-share" :style="{ '--c': g.color }" @click="openDetail(g)">
                                        <span class="tt-share-ic"><v-icon size="15" color="white">{{ g.icon }}</v-icon></span>
                                        <span class="tt-share-name">{{ g.name }}</span>
                                        <span class="tt-share-track"><span :style="{ width: g.pct + '%' }"></span></span>
                                        <span class="tt-share-val">{{ money(g.revenue) }}</span>
                                    </button>
                                </div>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="5">
                            <v-card flat class="tt-card h-100">
                                <div class="tt-head">
                                    <div class="tt-head-ic"><v-icon size="16">mdi-lightbulb-on-outline</v-icon></div>
                                    <div>
                                        <div class="tt-card-title">Worth a look</div>
                                        <div class="tt-sub">Quick wins from your current groups</div>
                                    </div>
                                </div>
                                <div class="tt-body">
                                    <div v-for="a in attention" :key="a.title" class="tt-attn" :style="{ '--c': a.color }">
                                        <div class="tt-attn-ic"><v-icon size="18" color="white">{{ a.icon }}</v-icon></div>
                                        <div class="flex-grow-1">
                                            <div class="tt-strong">{{ a.title }}</div>
                                            <div class="tt-sub">{{ a.text }}</div>
                                        </div>
                                        <v-btn variant="outlined" size="small" class="text-none tt-attn-btn" :disabled="!a.count" @click="a.apply">{{ a.label }}</v-btn>
                                    </div>
                                </div>
                            </v-card>
                        </v-col>
                    </v-row>

                    <!-- Toolbar -->
                    <v-card flat class="tt-card mt-5">
                        <div class="tt-head tt-head-wrap">
                            <div class="tt-head-ic"><v-icon size="16">mdi-account-multiple-outline</v-icon></div>
                            <div>
                                <div class="tt-card-title">All groups</div>
                                <div class="tt-sub">{{ visibleGroups.length }} of {{ groupsView.length }} shown</div>
                            </div>
                            <v-spacer />
                            <v-select v-model="sortBy" :items="sortOptions" prepend-inner-icon="mdi-sort" variant="outlined" density="compact" hide-details class="tt-filter mr-2" />
                            <v-text-field v-model="searchValue" placeholder="Search groups…" prepend-inner-icon="mdi-magnify" clearable variant="outlined" density="compact" hide-details class="tt-search" @click:clear="searchValue = ''" />
                        </div>

                        <div class="tt-segments" role="tablist" aria-label="Group type">
                            <button v-for="s in segments" :key="s.value" type="button" role="tab" class="tt-segment" :class="{ on: segment === s.value }" :aria-selected="segment === s.value" @click="segment = s.value">
                                {{ s.label }}<span class="tt-segment-count">{{ s.count }}</span>
                            </button>
                        </div>

                        <div class="tt-body pt-4">
                            <div v-if="loading" class="tt-empty">Loading groups…</div>
                            <div v-else-if="!visibleGroups.length" class="tt-empty tt-empty-lg">
                                <v-icon size="30" color="#0f9d6b">mdi-account-multiple-plus-outline</v-icon>
                                <div class="tt-strong mt-2">{{ groupsView.length ? "No groups match these filters" : "You haven't created a group yet" }}</div>
                                <div class="tt-sub mb-3">{{ groupsView.length ? "Try a different search or switch tabs." : "Start with VIP buyers, wholesale shops or gamers." }}</div>
                                <v-btn v-if="!groupsView.length" variant="flat" class="text-none tt-btn-solid" prepend-icon="mdi-plus" @click="openAdd">Create group</v-btn>
                            </div>

                            <v-row v-else dense>
                                <v-col v-for="g in visibleGroups" :key="g.id" cols="12" md="6" xl="4">
                                    <div class="tt-group" :class="{ paused: g.status === 'Paused' }" :style="{ '--c': g.color, '--t': g.tint }">
                                        <div class="tt-group-top" @click="openDetail(g)">
                                            <div class="tt-group-ic"><v-icon size="22" color="white">{{ g.icon }}</v-icon></div>
                                            <div class="tt-group-title">
                                                <div class="tt-name tt-clamp-1">{{ g.name }}</div>
                                                <div class="tt-sub tt-clamp-1">{{ ruleSummary(g) }}</div>
                                            </div>
                                            <span class="tt-chip" :class="g.status === 'Active' ? 'tone-green' : 'tone-amber'">{{ g.status }}</span>
                                        </div>

                                        <p class="tt-group-desc">{{ g.description || "No description yet." }}</p>

                                        <div class="tt-group-stats">
                                            <div><small>Customers</small><b>{{ g.members.length }}</b></div>
                                            <div><small>Lifetime sales</small><b>{{ shortMoney(g.revenue) }}</b></div>
                                            <div><small>Avg order</small><b>{{ money(g.avgOrder) }}</b></div>
                                        </div>

                                        <div class="tt-perks">
                                            <span v-if="g.discount" class="tt-perk"><v-icon size="13">mdi-tag-outline</v-icon>{{ g.discount }}% off</span>
                                            <span v-if="g.freeDelivery" class="tt-perk"><v-icon size="13">mdi-truck-fast-outline</v-icon>Free delivery</span>
                                            <span v-if="g.prioritySupport" class="tt-perk"><v-icon size="13">mdi-headset</v-icon>Priority support</span>
                                            <span v-if="!g.discount && !g.freeDelivery && !g.prioritySupport" class="tt-perk none">No perks set</span>
                                        </div>

                                        <div class="tt-group-foot">
                                            <span class="tt-chip" :class="g.type === 'Automatic' ? 'tone-teal' : 'tone-gray'">
                                                <v-icon size="12">{{ g.type === "Automatic" ? "mdi-autorenew" : "mdi-hand-back-right-outline" }}</v-icon> {{ g.type }}
                                            </span>
                                            <v-spacer />
                                            <v-btn icon="mdi-eye-outline" size="small" variant="text" color="#0a5548" aria-label="View group" @click="openDetail(g)" />
                                            <v-btn icon="mdi-pencil-outline" size="small" variant="text" color="primary" aria-label="Edit group" @click="editItem(g)" />
                                            <v-btn icon="mdi-content-copy" size="small" variant="text" color="#6b7f7b" aria-label="Duplicate group" @click="duplicate(g)" />
                                            <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" aria-label="Delete group" @click="removeItem(g)" />
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>
                        </div>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ Add / Edit group ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <header class="tt-hero tt-hero-slim">
                        <div class="d-flex flex-wrap align-center ga-3">
                            <div>
                                <h1 class="tt-h1">{{ form.id ? "Edit group" : "Create group" }}</h1>
                                <div class="tt-hero-sub">{{ form.id ? "Update the rules and perks for " + form.name : "Choose who belongs in the group and what they get" }}</div>
                            </div>
                            <v-spacer />
                            <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-arrow-left" @click="closeAdd">Back to groups</v-btn>
                            <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-check" :loading="saving" @click="saveGroup">{{ form.id ? "Save changes" : "Create group" }}</v-btn>
                        </div>
                    </header>

                    <v-row dense class="mt-3">
                        <v-col cols="12" md="8">
                            <!-- Basics -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-card-text-outline</v-icon></div><span class="tt-card-title">Group details</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">Group name <span class="tt-required">*</span></div>
                                    <v-text-field v-model="form.name" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. VIP buyers" autofocus />

                                    <div class="tt-field-label">Description</div>
                                    <v-textarea v-model="form.description" variant="outlined" density="comfortable" rows="2" hide-details class="mb-4" placeholder="Who is in this group and why it exists" />

                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Colour</div>
                                            <div class="tt-swatches">
                                                <button v-for="c in colorChoices" :key="c" type="button" class="tt-swatch" :class="{ on: form.color === c }" :style="{ background: c }" :aria-label="'Colour ' + c" :aria-pressed="form.color === c" @click="form.color = c"></button>
                                            </div>
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Icon</div>
                                            <div class="tt-swatches">
                                                <button v-for="i in iconChoices" :key="i" type="button" class="tt-icopick" :class="{ on: form.icon === i }" :aria-label="'Icon ' + i" :aria-pressed="form.icon === i" @click="form.icon = i">
                                                    <v-icon size="18">{{ i }}</v-icon>
                                                </button>
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Membership -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-account-filter-outline</v-icon></div><span class="tt-card-title">Who belongs here</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-choice-grid">
                                        <div v-for="o in typeOptions" :key="o.value" class="tt-radio-box" :class="{ 'is-selected': form.type === o.value }" role="radio" :aria-checked="form.type === o.value" tabindex="0" @click="form.type = o.value" @keydown.enter.prevent="form.type = o.value" @keydown.space.prevent="form.type = o.value">
                                            <span class="tt-radio-dot" :class="{ 'is-on': form.type === o.value }"></span>
                                            <div>
                                                <div>{{ o.label }}</div>
                                                <div class="tt-sub">{{ o.hint }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Automatic rules -->
                                    <template v-if="form.type === 'Automatic'">
                                        <div class="d-flex align-center flex-wrap ga-2 mt-5 mb-3">
                                            <span class="tt-field-label mb-0">Customers who match</span>
                                            <v-btn-toggle v-model="form.match" mandatory density="compact" variant="outlined" divided class="tt-match">
                                                <v-btn value="all" class="text-none" size="small">all rules</v-btn>
                                                <v-btn value="any" class="text-none" size="small">any rule</v-btn>
                                            </v-btn-toggle>
                                        </div>

                                        <div v-for="(r, i) in form.rules" :key="i" class="tt-rule">
                                            <v-select v-model="r.field" :items="fieldItems" variant="outlined" density="compact" hide-details class="tt-rule-field" @update:model-value="onFieldChange(r)" />
                                            <v-select v-model="r.op" :items="opsFor(r.field)" variant="outlined" density="compact" hide-details class="tt-rule-op" />
                                            <v-text-field v-model="r.value" :type="fieldType(r.field) === 'number' ? 'number' : 'text'" variant="outlined" density="compact" hide-details class="tt-rule-val" :placeholder="fieldHint(r.field)" />
                                            <v-btn icon="mdi-close" size="small" variant="text" color="#6b7f7b" aria-label="Remove rule" :disabled="form.rules.length === 1" @click="form.rules.splice(i, 1)" />
                                        </div>
                                        <v-btn variant="text" size="small" class="text-none tt-link mt-1" prepend-icon="mdi-plus" @click="addRule">Add rule</v-btn>
                                        <div class="tt-sub mt-2">Customers move in and out of this group on their own as their orders and spend change.</div>
                                    </template>

                                    <!-- Manual members -->
                                    <template v-else>
                                        <div class="tt-field-label mt-5">Customers in this group</div>
                                        <v-autocomplete v-model="form.memberIds" :items="customerItems" multiple chips closable-chips variant="outlined" density="comfortable" hide-details placeholder="Search customers to add" />
                                        <div class="tt-sub mt-2">You choose each customer yourself. Nobody is added or removed automatically.</div>
                                    </template>
                                </v-card-text>
                            </v-card>

                            <!-- Perks -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-gift-outline</v-icon></div><span class="tt-card-title">Group perks</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Discount on every order (%)</div>
                                            <v-text-field v-model.number="form.discount" type="number" min="0" max="100" variant="outlined" density="comfortable" hide-details prepend-inner-icon="mdi-percent-outline" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Minimum order for perks (৳)</div>
                                            <v-text-field v-model.number="form.minOrder" type="number" min="0" variant="outlined" density="comfortable" hide-details prepend-inner-icon="mdi-cash" />
                                        </v-col>
                                    </v-row>
                                    <div class="mt-3">
                                        <div class="tt-option-row"><span>Free delivery</span><v-checkbox-btn v-model="form.freeDelivery" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Priority support</span><v-checkbox-btn v-model="form.prioritySupport" color="#0f9d6b" /></div>
                                        <div class="tt-option-row"><span>Allow cash on delivery</span><v-checkbox-btn v-model="form.allowCod" color="#0f9d6b" /></div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="4">
                            <!-- Status -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-toggle-switch-outline</v-icon></div><span class="tt-card-title">Status</span></div>
                                <v-card-text class="tt-body">
                                    <div v-for="o in statusOptions" :key="o.value" class="tt-radio-box" :class="{ 'is-selected': form.status === o.value }" role="radio" :aria-checked="form.status === o.value" tabindex="0" @click="form.status = o.value" @keydown.enter.prevent="form.status = o.value" @keydown.space.prevent="form.status = o.value">
                                        <span class="tt-radio-dot" :class="{ 'is-on': form.status === o.value }"></span>
                                        <div>
                                            <div>{{ o.label }}</div>
                                            <div class="tt-sub">{{ o.hint }}</div>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Live preview -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-account-search-outline</v-icon></div><span class="tt-card-title">Who fits today</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-preview" :style="{ '--c': form.color }">
                                        <div class="d-flex align-center ga-3">
                                            <div class="tt-group-ic"><v-icon size="22" color="white">{{ form.icon }}</v-icon></div>
                                            <div class="tt-group-title">
                                                <div class="tt-name tt-clamp-1">{{ form.name || "Group name" }}</div>
                                                <div class="tt-sub">{{ previewMembers.length }} {{ previewMembers.length === 1 ? "customer" : "customers" }} · {{ money(previewRevenue) }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <div v-for="m in previewMembers.slice(0, 5)" :key="m.id" class="tt-mini">
                                                <v-avatar size="26" :style="{ background: avatarColor(m.name) }" class="tt-person-avatar"><span class="tt-mini-in">{{ initials(m.name) }}</span></v-avatar>
                                                <span class="tt-mini-name">{{ m.name }}</span>
                                                <span class="tt-sub">{{ shortMoney(m.spent) }}</span>
                                            </div>
                                            <div v-if="!previewMembers.length" class="tt-empty">No customers fit yet. Loosen a rule or pick people by hand.</div>
                                            <div v-else-if="previewMembers.length > 5" class="tt-sub mt-1">and {{ previewMembers.length - 5 }} more</div>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- ═════════════════════════ Detail drawer ═════════════════════════ -->
            <v-navigation-drawer v-model="drawer" location="right" temporary width="440" class="tt-drawer">
                <template v-if="current">
                    <div class="tt-drawer-hero" :style="{ '--c': current.color }">
                        <div class="d-flex align-start">
                            <div class="tt-group-ic tt-group-ic-lg"><v-icon size="26" color="white">{{ current.icon }}</v-icon></div>
                            <v-spacer />
                            <v-btn icon="mdi-close" size="small" variant="text" color="white" aria-label="Close" @click="drawer = false" />
                        </div>
                        <div class="tt-drawer-name">{{ current.name }}</div>
                        <div class="tt-drawer-sub">{{ current.description || ruleSummary(current) }}</div>
                        <div class="d-flex flex-wrap ga-2 mt-3">
                            <span class="tt-chip tone-light">{{ current.type }}</span>
                            <span class="tt-chip tone-light">{{ current.status }}</span>
                        </div>
                    </div>

                    <div class="tt-drawer-stats">
                        <div class="tt-stat"><small>Customers</small><b>{{ current.members.length }}</b></div>
                        <div class="tt-stat"><small>Lifetime sales</small><b>{{ shortMoney(current.revenue) }}</b></div>
                        <div class="tt-stat"><small>Avg order</small><b>{{ money(current.avgOrder) }}</b></div>
                    </div>

                    <div class="tt-drawer-body">
                        <div class="tt-drawer-t">How customers get in</div>
                        <p class="tt-note">{{ ruleSummary(current, true) }}</p>

                        <div class="tt-drawer-t mt-5">Perks</div>
                        <div class="tt-line"><v-icon size="16">mdi-tag-outline</v-icon><span>{{ current.discount ? current.discount + "% off every order" : "No discount" }}</span></div>
                        <div class="tt-line"><v-icon size="16">mdi-truck-fast-outline</v-icon><span>{{ current.freeDelivery ? "Free delivery" : "Standard delivery charge" }}</span></div>
                        <div class="tt-line"><v-icon size="16">mdi-headset</v-icon><span>{{ current.prioritySupport ? "Priority support" : "Standard support" }}</span></div>
                        <div class="tt-line"><v-icon size="16">mdi-cash</v-icon><span>{{ current.allowCod ? "Cash on delivery allowed" : "Online payment only" }}{{ current.minOrder ? " · perks from " + money(current.minOrder) : "" }}</span></div>

                        <div class="tt-drawer-t mt-5">Customers ({{ current.members.length }})</div>
                        <div v-if="!current.members.length" class="tt-empty">Nobody is in this group right now.</div>
                        <div v-for="m in topMembers" :key="m.id" class="tt-member">
                            <v-avatar size="34" class="tt-person-avatar" :style="{ background: avatarColor(m.name) }"><span class="tt-mini-in">{{ initials(m.name) }}</span></v-avatar>
                            <div class="tt-person-text">
                                <div class="tt-name tt-clamp-1">{{ m.name }}</div>
                                <div class="tt-sub">{{ m.city || "No city" }} · {{ m.orders }} orders</div>
                            </div>
                            <v-spacer />
                            <span class="tt-strong">{{ money(m.spent) }}</span>
                        </div>
                        <div v-if="current.members.length > topMembers.length" class="tt-sub mt-2">Showing the top {{ topMembers.length }} by spend. See everyone on the Customers page.</div>
                    </div>

                    <div class="tt-drawer-foot">
                        <v-btn variant="outlined" class="text-none flex-grow-1" prepend-icon="mdi-send-outline" :disabled="!current.members.length" @click="sendOffer(current)">Send offer</v-btn>
                        <v-btn variant="flat" class="text-none flex-grow-1 tt-btn-solid" prepend-icon="mdi-pencil-outline" @click="editFromDrawer">Edit group</v-btn>
                    </div>
                </template>
            </v-navigation-drawer>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="420">
                <v-card class="tt-dialog">
                    <v-card-title class="text-subtitle-1 font-weight-bold pt-5 px-5">Delete group</v-card-title>
                    <v-card-text class="px-5">Delete <b>{{ deletedName }}</b>? The customers stay in your store, but they lose this group's perks straight away.</v-card-text>
                    <v-card-actions class="pb-4 px-5">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="cancel">Keep group</v-btn>
                        <v-btn color="error" variant="flat" class="text-none" :loading="deleting" @click="agree">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
const DAY = 864e5;
const fullMoney = (v) => "৳ " + Number(v || 0).toLocaleString("en-IN");
const shortMoney = (v) => {
    v = Number(v || 0);
    return "৳ " + (v >= 10000000 ? (v / 10000000).toFixed(1).replace(".0", "") + "Cr" : v >= 100000 ? (v / 100000).toFixed(1).replace(".0", "") + "L" : v >= 1000 ? Math.round(v / 1000) + "k" : v);
};

const COLORS = ["#0f9d6b", "#2f5be7", "#7c3aed", "#ec4899", "#f59e0b", "#ef6a45", "#0f766e"];
const ICONS = ["mdi-crown-outline", "mdi-store-outline", "mdi-controller-classic-outline", "mdi-account-plus-outline", "mdi-repeat", "mdi-sleep", "mdi-briefcase-outline", "mdi-heart-outline"];
const tintOf = (hex) => ({ "#0f9d6b": "#dff5ec", "#2f5be7": "#e6edff", "#7c3aed": "#ebe5ff", "#ec4899": "#fbe1ec", "#f59e0b": "#fdecc8", "#ef6a45": "#fce1d8", "#0f766e": "#d9f1e6" }[hex] || "#e3f6ee");

/* Rule building blocks: field → label, type, hint */
const FIELDS = {
    spent: { label: "Total spent (৳)", type: "number", hint: "e.g. 150000" },
    orders: { label: "Number of orders", type: "number", hint: "e.g. 5" },
    lastOrderDays: { label: "Days since last order", type: "number", hint: "e.g. 60" },
    joinedDays: { label: "Days since joined", type: "number", hint: "e.g. 30" },
    city: { label: "City", type: "text", hint: "e.g. Dhaka" },
    tag: { label: "Tag", type: "text", hint: "e.g. Gamer" },
    custType: { label: "Customer type", type: "text", hint: "e.g. Wholesale" },
};
const OPS = { number: [">=", "<=", "="], text: ["is", "is not", "contains"] };

const emptyForm = () => ({
    id: null,
    name: "",
    description: "",
    color: COLORS[0],
    icon: ICONS[0],
    type: "Automatic",
    match: "all",
    rules: [{ field: "spent", op: ">=", value: "" }],
    memberIds: [],
    discount: 0,
    minOrder: 0,
    freeDelivery: false,
    prioritySupport: false,
    allowCod: true,
    status: "Active",
});

/* Sample data so the page looks right before your API is connected.
   Set to false once /api/customer-groups and /api/customers return real data. */
const USE_SAMPLE_DATA_ON_ERROR = true;

const makeCustomers = () => {
    let s = 77;
    const r = () => ((s = (s * 9301 + 49297) % 233280) / 233280);
    const names = ["Rahim Traders", "Sabbir Hossain", "Dhaka Mobile Hub", "Nusrat Jahan", "Gadget Point", "Tanvir Ahmed", "Mim Electronics", "Farhana Akter", "Techland BD", "Arif Hasan", "Shanta Islam", "Star Gadgets", "Imran Khan", "Sumaiya Rahman", "Nazmul Haque", "Ridoy Store", "Mahmudul Karim", "Tasnim Sultana", "Jahid Hasan", "Lamia Chowdhury", "Rafiq Mobile Corner", "Sadia Afrin", "Kamrul Islam", "Priya Das", "Shakil Ahmed", "Maruf Hossain", "Nabila Rahman", "Rubel Mia", "Tithi Akter", "Fahim Reza"];
    const cities = ["Dhaka", "Dhaka", "Dhaka", "Chattogram", "Sylhet", "Khulna", "Rajshahi", "Barishal", "Rangpur", "Cumilla"];
    return names.map((name, i) => {
        const business = /Traders|Hub|Point|Electronics|BD|Gadgets|Store|Corner/.test(name);
        const orders = i % 9 === 8 ? 0 : Math.round(1 + r() * (business ? 40 : 18));
        return {
            id: i + 1,
            name,
            city: cities[Math.floor(r() * cities.length)],
            orders,
            spent: orders * Math.round(1800 + r() * (business ? 6000 : 3600)),
            lastOrder: orders ? new Date(Date.now() - Math.floor(r() * 110) * DAY).toISOString() : null,
            joined: new Date(Date.now() - (6 + Math.floor(r() * 420)) * DAY).toISOString(),
            type: business ? "Wholesale" : "Individual",
            tags: business ? ["Wholesale"] : i % 4 === 0 ? ["Gamer"] : [],
        };
    });
};

const makeGroups = () => [
    { id: 1, name: "VIP buyers", description: "Our biggest spenders. They get the best prices and first access to stock.", color: "#7c3aed", icon: "mdi-crown-outline", type: "Automatic", match: "all", rules: [{ field: "spent", op: ">=", value: 150000 }], memberIds: [], discount: 5, minOrder: 0, freeDelivery: true, prioritySupport: true, allowCod: true, status: "Active" },
    { id: 2, name: "Wholesale and resellers", description: "Shops that buy in bulk for resale.", color: "#0f9d6b", icon: "mdi-store-outline", type: "Automatic", match: "all", rules: [{ field: "custType", op: "is", value: "Wholesale" }], memberIds: [], discount: 8, minOrder: 20000, freeDelivery: false, prioritySupport: false, allowCod: false, status: "Active" },
    { id: 3, name: "Gamers", description: "Buyers of mice, keyboards and headsets. Good audience for gaming launches.", color: "#2f5be7", icon: "mdi-controller-classic-outline", type: "Automatic", match: "all", rules: [{ field: "tag", op: "is", value: "Gamer" }], memberIds: [], discount: 3, minOrder: 0, freeDelivery: false, prioritySupport: false, allowCod: true, status: "Active" },
    { id: 4, name: "New this month", description: "Joined in the last 30 days. Help them place a second order.", color: "#f59e0b", icon: "mdi-account-plus-outline", type: "Automatic", match: "all", rules: [{ field: "joinedDays", op: "<=", value: 30 }], memberIds: [], discount: 5, minOrder: 1500, freeDelivery: false, prioritySupport: false, allowCod: true, status: "Active" },
    { id: 5, name: "Slipping away", description: "Ordered before but quiet for two months or more.", color: "#ef6a45", icon: "mdi-sleep", type: "Automatic", match: "all", rules: [{ field: "lastOrderDays", op: ">=", value: 60 }, { field: "orders", op: ">=", value: 1 }], memberIds: [], discount: 10, minOrder: 0, freeDelivery: true, prioritySupport: false, allowCod: true, status: "Paused" },
    { id: 6, name: "Dhaka repeat buyers", description: "Five or more orders and based in Dhaka, so same-day delivery works.", color: "#ec4899", icon: "mdi-repeat", type: "Automatic", match: "all", rules: [{ field: "city", op: "is", value: "Dhaka" }, { field: "orders", op: ">=", value: 5 }], memberIds: [], discount: 0, minOrder: 0, freeDelivery: true, prioritySupport: false, allowCod: true, status: "Active" },
    { id: 7, name: "Corporate accounts", description: "Hand-picked offices and companies on invoice terms.", color: "#0f766e", icon: "mdi-briefcase-outline", type: "Manual", match: "all", rules: [], memberIds: [1, 3, 5, 9], discount: 6, minOrder: 30000, freeDelivery: true, prioritySupport: true, allowCod: false, status: "Active" },
];

export default {
    name: "CustomerGroups",

    data() {
        return {
            tab: "list",
            searchValue: "",
            segment: "all",
            sortBy: "Most customers",
            loading: false,

            groups: [],
            customers: [],

            form: emptyForm(),
            saving: false,
            deleting: false,
            deleteDialog: false,
            deletedId: -1,
            deletedName: "",

            drawer: false,
            current: null,

            snackbar: false,
            snackbarText: "",
            snackbarColor: "#0f9d6b",

            colorChoices: COLORS,
            iconChoices: ICONS,
            sortOptions: ["Most customers", "Highest sales", "Name A to Z", "Newest first"],
            typeOptions: [
                { value: "Automatic", label: "Automatic", hint: "Set rules and the group fills itself" },
                { value: "Manual", label: "Manual", hint: "Pick each customer yourself" },
            ],
            statusOptions: [
                { value: "Active", label: "Active", hint: "Perks apply at checkout" },
                { value: "Paused", label: "Paused", hint: "Keeps its members but perks are off" },
            ],
        };
    },

    computed: {
        fieldItems() {
            return Object.entries(FIELDS).map(([value, f]) => ({ value, title: f.label }));
        },
        customerItems() {
            return this.customers.map((c) => ({ value: c.id, title: c.name + (c.city ? " · " + c.city : "") }));
        },

        /* groups with their live members and numbers */
        groupsView() {
            return this.groups.map((g) => {
                const members = this.membersOf(g);
                const revenue = members.reduce((a, c) => a + c.spent, 0);
                const orders = members.reduce((a, c) => a + c.orders, 0);
                return { ...g, members, revenue, avgOrder: orders ? Math.round(revenue / orders) : 0, tint: tintOf(g.color) };
            });
        },
        segments() {
            const n = (fn) => this.groupsView.filter(fn).length;
            return [
                { value: "all", label: "All", count: this.groupsView.length },
                { value: "Automatic", label: "Automatic", count: n((g) => g.type === "Automatic") },
                { value: "Manual", label: "Manual", count: n((g) => g.type === "Manual") },
                { value: "Paused", label: "Paused", count: n((g) => g.status === "Paused") },
            ];
        },
        visibleGroups() {
            const q = (this.searchValue || "").trim().toLowerCase();
            const rows = this.groupsView.filter((g) => {
                if (this.segment === "Paused" && g.status !== "Paused") return false;
                if ((this.segment === "Automatic" || this.segment === "Manual") && g.type !== this.segment) return false;
                return !q || (g.name + " " + g.description).toLowerCase().includes(q);
            });
            const by = {
                "Most customers": (a, b) => b.members.length - a.members.length,
                "Highest sales": (a, b) => b.revenue - a.revenue,
                "Name A to Z": (a, b) => a.name.localeCompare(b.name),
                "Newest first": (a, b) => b.id - a.id,
            }[this.sortBy];
            return [...rows].sort(by);
        },
        groupedIds() {
            const set = new Set();
            this.groupsView.forEach((g) => g.members.forEach((m) => set.add(m.id)));
            return set;
        },
        heroStats() {
            const active = this.groupsView.filter((g) => g.status === "Active");
            const covered = this.customers.length ? Math.round((this.groupedIds.size / this.customers.length) * 100) : 0;
            const top = [...this.groupsView].sort((a, b) => b.revenue - a.revenue)[0];
            return [
                { icon: "mdi-account-multiple-outline", color: "#0f9d6b", value: String(this.groupsView.length), label: "Customer groups", note: active.length + " active right now" },
                { icon: "mdi-account-check-outline", color: "#2f5be7", value: this.groupedIds.size.toLocaleString("en-IN"), label: "Customers in a group", note: covered + "% of all customers" },
                { icon: "mdi-autorenew", color: "#7c3aed", value: String(this.groupsView.filter((g) => g.type === "Automatic").length), label: "Self-updating groups", note: "Rules keep them current" },
                { icon: "mdi-trophy-outline", color: "#f59e0b", value: top ? top.name : "—", label: "Top group by sales", note: top ? fullMoney(top.revenue) + " lifetime" : "No groups yet" },
            ];
        },
        revenueShare() {
            const rows = [...this.groupsView].sort((a, b) => b.revenue - a.revenue).slice(0, 6);
            const max = Math.max(1, ...rows.map((g) => g.revenue));
            return rows.map((g) => ({ ...g, pct: Math.max(4, Math.round((g.revenue / max) * 100)) }));
        },
        attention() {
            const ungrouped = this.customers.filter((c) => !this.groupedIds.has(c.id)).length;
            const paused = this.groupsView.filter((g) => g.status === "Paused" && g.members.length);
            const bare = this.groupsView.filter((g) => g.status === "Active" && !g.discount && !g.freeDelivery && !g.prioritySupport);
            return [
                {
                    icon: "mdi-account-question-outline", color: "#ef6a45", count: ungrouped, label: "Create group",
                    title: ungrouped + " customers aren't in any group",
                    text: "They get no perks. A broad group like “Everyone else” can fix that.",
                    apply: () => this.openAdd(),
                },
                {
                    icon: "mdi-pause-circle-outline", color: "#f59e0b", count: paused.length, label: "Review",
                    title: paused.length + (paused.length === 1 ? " paused group has" : " paused groups have") + " customers",
                    text: paused.length ? paused[0].name + " isn't giving perks while paused." : "All your groups are running.",
                    apply: () => { this.segment = "Paused"; },
                },
                {
                    icon: "mdi-gift-off-outline", color: "#7c3aed", count: bare.length, label: "Add perks",
                    title: bare.length + (bare.length === 1 ? " active group has" : " active groups have") + " no perks",
                    text: "A group with no discount, delivery or support benefit changes nothing at checkout.",
                    apply: () => this.editItem(bare[0]),
                },
            ];
        },

        topMembers() {
            return this.current ? [...this.current.members].sort((a, b) => b.spent - a.spent).slice(0, 8) : [];
        },

        /* live preview in the form */
        previewMembers() {
            return this.membersOf(this.form);
        },
        previewRevenue() {
            return this.previewMembers.reduce((a, c) => a + c.spent, 0);
        },
    },

    methods: {
        money: fullMoney,
        shortMoney,

        /* ── helpers ── */
        initials(name) {
            const p = (name || "?").trim().split(/\s+/);
            return ((p[0] || "")[0] + (p.length > 1 ? p[p.length - 1][0] : "")).toUpperCase();
        },
        avatarColor(name) {
            let h = 0;
            for (const ch of name || "") h = (h * 31 + ch.charCodeAt(0)) >>> 0;
            return COLORS[h % COLORS.length];
        },
        fieldType(f) { return (FIELDS[f] || FIELDS.spent).type; },
        fieldHint(f) { return (FIELDS[f] || FIELDS.spent).hint; },
        opsFor(f) { return OPS[this.fieldType(f)]; },
        onFieldChange(r) {
            r.op = this.opsFor(r.field)[0];
            r.value = "";
        },
        addRule() {
            this.form.rules.push({ field: "orders", op: ">=", value: "" });
        },

        /* ── membership engine ── */
        valueOf(c, field) {
            const now = Date.now();
            switch (field) {
                case "spent": return Number(c.spent) || 0;
                case "orders": return Number(c.orders) || 0;
                case "lastOrderDays": return c.lastOrder ? Math.floor((now - new Date(c.lastOrder).getTime()) / DAY) : 99999;
                case "joinedDays": return c.joined ? Math.floor((now - new Date(c.joined).getTime()) / DAY) : 99999;
                case "city": return c.city || "";
                case "custType": return c.type || "";
                case "tag": return c.tags || [];
                default: return "";
            }
        },
        ruleHolds(c, r) {
            if (r.value === "" || r.value === null || r.value === undefined) return true; // unfinished rules don't filter yet
            const v = this.valueOf(c, r.field);
            if (this.fieldType(r.field) === "number") {
                const n = Number(r.value);
                return r.op === ">=" ? v >= n : r.op === "<=" ? v <= n : v === n;
            }
            const want = String(r.value).trim().toLowerCase();
            const have = (Array.isArray(v) ? v : [v]).map((x) => String(x).toLowerCase());
            if (r.op === "is") return have.includes(want);
            if (r.op === "is not") return !have.includes(want);
            return have.some((x) => x.includes(want));
        },
        membersOf(g) {
            if (g.type === "Manual") return this.customers.filter((c) => (g.memberIds || []).includes(c.id));
            const rules = (g.rules || []).filter((r) => r.value !== "" && r.value !== null && r.value !== undefined);
            if (!rules.length) return [];
            return this.customers.filter((c) => (g.match === "any" ? rules.some((r) => this.ruleHolds(c, r)) : rules.every((r) => this.ruleHolds(c, r))));
        },
        ruleText(r) {
            const f = FIELDS[r.field] || { label: r.field };
            return f.label.replace(" (৳)", "") + " " + r.op + " " + (r.field === "spent" ? fullMoney(r.value) : r.value);
        },
        ruleSummary(g, long = false) {
            if (g.type === "Manual") return long ? "Picked by hand. " + (g.memberIds || []).length + " customers were added by your team." : "Picked by hand";
            const rules = (g.rules || []).filter((r) => r.value !== "" && r.value !== null);
            if (!rules.length) return "No rules yet";
            const joiner = g.match === "any" ? " or " : " and ";
            const text = rules.map((r) => this.ruleText(r).toLowerCase()).join(joiner);
            return long ? "Customers join automatically when " + text + "." : text.charAt(0).toUpperCase() + text.slice(1);
        },

        /* ── data ── */
        normalize(g) {
            return {
                ...g,
                description: g.description ?? "",
                color: g.color || COLORS[0],
                icon: g.icon || ICONS[0],
                type: g.type || "Automatic",
                match: g.match || "all",
                rules: Array.isArray(g.rules) ? g.rules : [],
                memberIds: g.member_ids ?? g.memberIds ?? [],
                discount: Number(g.discount ?? 0),
                minOrder: Number(g.min_order ?? g.minOrder ?? 0),
                freeDelivery: Boolean(g.free_delivery ?? g.freeDelivery ?? false),
                prioritySupport: Boolean(g.priority_support ?? g.prioritySupport ?? false),
                allowCod: g.allow_cod !== undefined ? Boolean(g.allow_cod) : (g.allowCod ?? true),
                status: g.status || "Active",
            };
        },
        normalizeCustomer(c) {
            return {
                id: c.id,
                name: c.name,
                city: c.city ?? "",
                orders: Number(c.orders_count ?? c.orders ?? 0),
                spent: Number(c.total_spent ?? c.spent ?? 0),
                lastOrder: c.last_order ?? c.lastOrder ?? null,
                joined: c.created_at ?? c.joined ?? null,
                type: c.type || "Individual",
                tags: Array.isArray(c.tags) ? c.tags : [],
            };
        },

        async loadAll() {
            this.loading = true;
            try {
                const [g, c] = await Promise.all([this.axios.get("/api/customer-groups"), this.axios.get("/api/customers")]);
                if (g.data && g.data.success && c.data && c.data.success) {
                    this.groups = g.data.data.map(this.normalize);
                    this.customers = c.data.data.map(this.normalizeCustomer);
                } else if (USE_SAMPLE_DATA_ON_ERROR) this.useSample();
            } catch (error) {
                console.error("Failed to load customer groups:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) this.useSample();
                else this.showError("Couldn't load customer groups. Check your connection and refresh.");
            } finally {
                this.loading = false;
            }
        },
        useSample() {
            this.customers = makeCustomers();
            this.groups = makeGroups().map(this.normalize);
        },

        /* ── navigation ── */
        openAdd() {
            this.form = emptyForm();
            this.drawer = false;
            this.tab = "add";
        },
        editItem(g) {
            if (!g) return;
            this.form = { ...emptyForm(), ...g, rules: (g.rules || []).map((r) => ({ ...r })), memberIds: [...(g.memberIds || [])] };
            if (!this.form.rules.length) this.form.rules = [{ field: "spent", op: ">=", value: "" }];
            this.tab = "add";
        },
        editFromDrawer() {
            const g = this.current;
            this.drawer = false;
            this.editItem(g);
        },
        closeAdd() {
            this.tab = "list";
            this.form = emptyForm();
        },
        openDetail(g) {
            this.current = this.groupsView.find((x) => x.id === g.id) || g;
            this.drawer = true;
        },

        /* ── save ── */
        validate() {
            const f = this.form;
            if (!f.name || !f.name.trim()) return "Enter a group name.";
            if (this.groups.some((g) => g.id !== f.id && g.name.trim().toLowerCase() === f.name.trim().toLowerCase())) return "A group with this name already exists. Pick a different name.";
            if (f.type === "Automatic" && !f.rules.some((r) => r.value !== "" && r.value !== null)) return "Add at least one rule with a value, or switch to a manual group.";
            if (f.type === "Manual" && !f.memberIds.length) return "Pick at least one customer for a manual group.";
            if (f.discount < 0 || f.discount > 100) return "Discount must be between 0 and 100.";
            return null;
        },
        async saveGroup() {
            const err = this.validate();
            if (err) return this.showError(err);

            this.saving = true;
            const wasEdit = Boolean(this.form.id);
            const payload = { ...this.form, name: this.form.name.trim(), discount: Number(this.form.discount) || 0, minOrder: Number(this.form.minOrder) || 0 };
            try {
                const res = wasEdit ? await this.axios.put(`/api/customer-groups/${payload.id}`, payload) : await this.axios.post("/api/customer-groups", payload);
                if (res.data && res.data.success !== false) {
                    this.showSuccess(wasEdit ? "Group updated." : "Group created.");
                    await this.loadAll();
                    this.closeAdd();
                } else {
                    this.showError(res.data?.message || "Couldn't save the group. Try again.");
                }
            } catch (error) {
                console.error("Save error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    const row = this.normalize({ ...payload, id: payload.id || Date.now() });
                    this.groups = wasEdit ? this.groups.map((g) => (g.id === row.id ? row : g)) : [row, ...this.groups];
                    this.showSuccess((wasEdit ? "Group updated" : "Group created") + " (demo data).");
                    this.closeAdd();
                } else {
                    this.showError("Couldn't save the group. Check your connection and try again.");
                }
            } finally {
                this.saving = false;
            }
        },
        async duplicate(g) {
            const copy = { ...g, id: null, name: g.name + " copy", status: "Paused", rules: g.rules.map((r) => ({ ...r })), memberIds: [...g.memberIds] };
            delete copy.members; delete copy.revenue; delete copy.avgOrder; delete copy.tint;
            try {
                const res = await this.axios.post("/api/customer-groups", copy);
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                await this.loadAll();
                this.showSuccess("Group duplicated. The copy is paused.");
            } catch (error) {
                console.error("Duplicate error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    this.groups = [this.normalize({ ...copy, id: Date.now() }), ...this.groups];
                    this.showSuccess("Group duplicated (demo data). The copy is paused.");
                } else this.showError("Couldn't duplicate the group. Try again.");
            }
        },

        /* ── delete ── */
        removeItem(g) {
            this.deletedId = g.id;
            this.deletedName = g.name;
            this.deleteDialog = true;
        },
        cancel() {
            this.deleteDialog = false;
            this.deletedId = -1;
            this.deletedName = "";
        },
        async agree() {
            this.deleting = true;
            const id = this.deletedId;
            const drop = () => {
                this.groups = this.groups.filter((g) => g.id !== id);
                if (this.current && this.current.id === id) this.drawer = false;
            };
            try {
                const res = await this.axios.delete(`/api/customer-groups/${id}`);
                if (res.data && res.data.success === false) throw new Error(res.data.message || "failed");
                drop();
                this.showSuccess("Group deleted.");
            } catch (error) {
                console.error("Delete error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    drop();
                    this.showSuccess("Group deleted (demo data).");
                } else this.showError("Couldn't delete the group. Try again.");
            } finally {
                this.deleting = false;
                this.cancel();
            }
        },

        /* ── extras ── */
        sendOffer(g) {
            // Hook this up to your campaign or SMS endpoint, e.g. POST /api/campaigns { groupId }
            this.showSuccess("Offer draft started for " + g.members.length + " customers in " + g.name + ".");
        },
        exportCsv() {
            const rows = this.visibleGroups;
            if (!rows.length) return this.showError("There are no groups to export.");
            const head = ["Group", "Type", "Status", "Customers", "Lifetime sales", "Discount %", "Free delivery", "Priority support", "Rule"];
            const esc = (v) => '"' + String(v ?? "").replace(/"/g, '""') + '"';
            const lines = rows.map((g) => [g.name, g.type, g.status, g.members.length, g.revenue, g.discount, g.freeDelivery ? "Yes" : "No", g.prioritySupport ? "Yes" : "No", this.ruleSummary(g)].map(esc).join(","));
            const blob = new Blob(["\ufeff" + [head.map(esc).join(","), ...lines].join("\n")], { type: "text/csv;charset=utf-8" });
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = "customer-groups.csv";
            a.click();
            URL.revokeObjectURL(a.href);
            this.showSuccess("Exported " + rows.length + " groups.");
        },
        showSuccess(msg) {
            this.snackbarText = msg;
            this.snackbarColor = "#0f9d6b";
            this.snackbar = true;
        },
        showError(msg) {
            this.snackbarText = msg;
            this.snackbarColor = "#b3261e";
            this.snackbar = true;
        },
    },

    created() {
        document.title = "Customer groups";
        this.loadAll();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-grp {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg2: #f7faf9;
    --accent: #0f9d6b;
    --tint: #e3f6ee;
    font-family: Poppins, "Segoe UI", sans-serif;
    color: var(--ink);
    padding-bottom: 36px;
    font-variant-numeric: tabular-nums;
    -webkit-font-smoothing: antialiased;
}

/* ───────── Hero ───────── */
.tt-hero {
    position: relative; overflow: hidden; color: #fff; padding: 24px 26px 26px; border-radius: 18px;
    background: radial-gradient(800px 280px at 100% 0%, rgba(110, 231, 183, 0.2), transparent 60%), linear-gradient(135deg, #053d35 0%, #0a5548 55%, #0b6150 100%);
    box-shadow: 0 26px 44px -30px rgba(5, 61, 53, 0.95);
}
.tt-hero::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, #ec4899, #f59e0b 45%, rgba(236, 72, 153, 0) 88%); }
.tt-hero-slim { padding: 20px 26px 22px; }
.tt-h1 { font-size: 30px; font-weight: 700; line-height: 1.15; letter-spacing: -0.6px; color: #fff; }
.tt-hero-slim .tt-h1 { font-size: 24px; }
.tt-hero-sub { font-size: 13.5px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; max-width: 600px; }
.tt-btn-ghost { height: 40px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 40px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-btn-solid { background: #0a5548 !important; color: #fff !important; font-weight: 600; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 22px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: flex-start; gap: 14px; padding: 15px 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); min-width: 0; }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -40px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.25; filter: blur(10px); }
.tt-hero-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-item > .flex-grow-1 { min-width: 0; }
.tt-hero-value { font-size: 22px; font-weight: 700; line-height: 1.2; letter-spacing: -0.4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-hero-label { font-size: 12.5px; font-weight: 500; color: rgba(255, 255, 255, 0.9); }
.tt-hero-note { font-size: 11.5px; color: rgba(255, 255, 255, 0.58); }
@media (max-width: 1279px) { .tt-hero-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-hero-strip { grid-template-columns: 1fr; } }

/* ───────── Cards ───────── */
.tt-card {
    position: relative; background: #fff; border: 1px solid var(--line) !important; border-radius: 16px !important;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.05), 0 10px 24px -18px rgba(15, 42, 38, 0.35) !important; overflow: hidden;
}
.tt-card::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, #0f9d6b, rgba(15, 157, 107, 0) 85%); }
.tt-head { display: flex; align-items: center; gap: 10px; padding: 16px 18px 8px; }
.tt-head-wrap { flex-wrap: wrap; row-gap: 10px; }
.tt-head-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--tint); color: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; line-height: 1.3; color: var(--ink); }
.tt-sub { font-size: 12px; color: var(--muted); margin-top: 1px; }
.tt-body { padding: 8px 18px 18px; }
.tt-strong { font-weight: 600; color: var(--ink); }
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-link { color: #0a5548 !important; font-weight: 600; }
.tt-field-label { font-size: 12.5px; font-weight: 600; color: #3a4a47; margin-bottom: 8px; }
.tt-required { color: #dc2626; }
.tt-search { max-width: 280px; min-width: 200px; }
.tt-filter { max-width: 210px; min-width: 160px; }
.tt-search :deep(.v-field), .tt-filter :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
.tt-clamp-1 { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-empty { font-size: 13px; color: var(--muted); background: var(--bg2); border: 1px dashed #c8d4d0; border-radius: 10px; padding: 14px; }
.tt-empty-lg { text-align: center; padding: 34px 14px; }

/* ───────── Sales share ───────── */
.tt-share { --c: #0f9d6b; display: flex; align-items: center; gap: 12px; width: 100%; border: 0; background: transparent; font: inherit; text-align: left; padding: 7px 8px; border-radius: 10px; cursor: pointer; transition: background 0.15s; }
.tt-share:hover { background: var(--bg2); }
.tt-share:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-share-ic { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; background: var(--c); flex: none; }
.tt-share-name { flex: 0 0 150px; font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-share-track { flex: 1; height: 8px; border-radius: 8px; background: #e8eeec; overflow: hidden; }
.tt-share-track span { display: block; height: 100%; border-radius: 8px; background: var(--c); }
.tt-share-val { flex: 0 0 104px; text-align: right; font-size: 12.5px; font-weight: 600; }
@media (max-width: 599px) { .tt-share-name { flex-basis: 96px; } .tt-share-val { flex-basis: 80px; } }

/* ───────── Attention ───────── */
.tt-attn { --c: #ef6a45; display: flex; align-items: center; gap: 14px; padding: 12px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--line); margin-bottom: 10px; }
.tt-attn:last-child { margin-bottom: 0; }
.tt-attn-ic { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; background: var(--c); flex: none; box-shadow: 0 8px 14px -8px var(--c); }
.tt-attn-btn { border-color: #0a5548 !important; color: #0a5548 !important; font-weight: 600; flex: none; }
@media (max-width: 599px) { .tt-attn { flex-wrap: wrap; } .tt-attn-btn { width: 100%; } }

/* ───────── Segments ───────── */
.tt-segments { display: flex; gap: 4px; padding: 6px 14px 10px; overflow-x: auto; scrollbar-width: none; border-bottom: 1px solid var(--line); }
.tt-segments::-webkit-scrollbar { display: none; }
.tt-segment { flex: none; display: inline-flex; align-items: center; gap: 8px; border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: var(--muted); padding: 7px 14px; border-radius: 11px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-segment:hover { color: var(--ink); background: #eaf2ef; }
.tt-segment.on { background: #0a5548; color: #fff; font-weight: 600; }
.tt-segment:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-segment-count { font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 10px; background: #e3eae8; color: #3a4a47; }
.tt-segment.on .tt-segment-count { background: rgba(255, 255, 255, 0.22); color: #fff; }

/* ───────── Group cards ───────── */
.tt-group { --c: #0f9d6b; --t: #dff5ec; display: flex; flex-direction: column; height: 100%; padding: 16px; border-radius: 14px; border: 1px solid var(--line); background: #fff; border-top: 3px solid var(--c); transition: box-shadow 0.2s ease; }
.tt-group:hover { box-shadow: 0 16px 28px -20px rgba(15, 42, 38, 0.5); }
.tt-group.paused { background: var(--bg2); }
.tt-group.paused .tt-group-ic { opacity: 0.55; }
.tt-group-top { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.tt-group-ic { width: 44px; height: 44px; border-radius: 13px; display: grid; place-items: center; background: var(--c); flex: none; box-shadow: 0 8px 14px -8px var(--c); }
.tt-group-ic-lg { width: 52px; height: 52px; border-radius: 15px; background: rgba(255, 255, 255, 0.16); border: 1px solid rgba(255, 255, 255, 0.28); box-shadow: none; }
.tt-group-title { flex: 1; min-width: 0; }
.tt-group-top:hover .tt-name { color: #0a5548; text-decoration: underline; text-underline-offset: 3px; }
.tt-group-desc { font-size: 12.5px; line-height: 1.55; color: #3a4a47; margin: 12px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 38px; }
.tt-group-stats { display: grid; grid-template-columns: repeat(3, 1fr); border-radius: 10px; background: var(--t); }
.tt-group-stats > div { padding: 8px 10px; min-width: 0; }
.tt-group-stats small { display: block; font-size: 10.5px; color: #3a4a47; opacity: 0.8; }
.tt-group-stats b { display: block; font-size: 13.5px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-perks { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0 14px; min-height: 26px; }
.tt-perk { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 500; padding: 3px 10px; border-radius: 20px; border: 1px solid var(--line); background: #fff; color: #3a4a47; }
.tt-perk.none { color: var(--muted); border-style: dashed; }
.tt-group-foot { display: flex; align-items: center; gap: 0; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--line); }

.tt-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; white-space: nowrap; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-teal { background: #d3f1f2; color: #0b6b72; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.tone-light { background: rgba(255, 255, 255, 0.16); color: #fff; border: 1px solid rgba(255, 255, 255, 0.28); }

/* ───────── Form ───────── */
.tt-choice-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media (max-width: 599px) { .tt-choice-grid { grid-template-columns: 1fr; } }
.tt-radio-box { display: flex; align-items: center; gap: 12px; font-size: 13.5px; border: 1px solid var(--line); border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; background: var(--bg2); cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tt-choice-grid .tt-radio-box { margin-bottom: 0; }
.tt-radio-box:last-child { margin-bottom: 0; }
.tt-radio-box:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-radio-box.is-selected { border-color: #0f9d6b; background: #eaf8f1; font-weight: 600; }
.tt-radio-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #c8d4d0; display: inline-block; position: relative; flex: none; }
.tt-radio-dot.is-on { border-color: #0f9d6b; }
.tt-radio-dot.is-on::after { content: ""; position: absolute; inset: 3px; border-radius: 50%; background: #0f9d6b; }
.tt-option-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; font-size: 13.5px; border-bottom: 1px solid var(--line); }
.tt-option-row:last-child { border-bottom: 0; }

.tt-swatches { display: flex; flex-wrap: wrap; gap: 8px; }
.tt-swatch { width: 28px; height: 28px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 1px var(--line); cursor: pointer; transition: box-shadow 0.15s; }
.tt-swatch.on { box-shadow: 0 0 0 2px #0a5548; }
.tt-swatch:focus-visible, .tt-icopick:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-icopick { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; border: 1px solid var(--line); background: var(--bg2); color: var(--muted); cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-icopick:hover { color: var(--ink); }
.tt-icopick.on { background: #0a5548; border-color: #0a5548; color: #fff; }

.tt-match :deep(.v-btn) { font-size: 12.5px; }
.tt-match :deep(.v-btn--active) { background: #0a5548 !important; color: #fff !important; }
.tt-rule { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.tt-rule-field { flex: 1.3; min-width: 0; }
.tt-rule-op { flex: 0 0 120px; }
.tt-rule-val { flex: 1; min-width: 0; }
.tt-rule :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }
@media (max-width: 599px) { .tt-rule { flex-wrap: wrap; } .tt-rule-field, .tt-rule-op, .tt-rule-val { flex: 1 1 100%; } }

.tt-preview { --c: #0f9d6b; border: 1px solid var(--line); border-top: 3px solid var(--c); border-radius: 12px; padding: 14px; background: var(--bg2); }
.tt-preview .tt-group-ic { background: var(--c); }
.tt-mini { display: flex; align-items: center; gap: 10px; padding: 5px 0; font-size: 12.5px; }
.tt-mini-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
.tt-mini-in { font-size: 10.5px; color: #fff; font-weight: 600; }
.tt-person-avatar { flex: none; color: #fff; font-size: 12px; font-weight: 600; }
.tt-person-text { min-width: 0; }

/* ───────── Drawer ───────── */
.tt-drawer { font-family: Poppins, "Segoe UI", sans-serif; }
.tt-drawer-hero { --c: #0f9d6b; color: #fff; padding: 18px 20px 20px; background: radial-gradient(420px 200px at 100% 0%, rgba(110, 231, 183, 0.22), transparent 62%), linear-gradient(135deg, #053d35 0%, #0a5548 60%, #0b6150 100%); border-bottom: 3px solid var(--c); }
.tt-drawer-name { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; margin-top: 12px; }
.tt-drawer-sub { font-size: 12.5px; color: rgba(255, 255, 255, 0.72); margin-top: 3px; line-height: 1.5; }
.tt-drawer-stats { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid var(--line); background: #f4faf7; }
.tt-stat { padding: 12px 14px; border-left: 1px solid var(--line); min-width: 0; }
.tt-stat:first-child { border-left: 0; }
.tt-stat small { display: block; font-size: 11px; color: var(--muted); }
.tt-stat b { display: block; font-size: 14px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-drawer-body { padding: 18px 20px 96px; }
.tt-drawer-t { font-size: 12.5px; font-weight: 600; margin-bottom: 8px; }
.tt-note { font-size: 13px; line-height: 1.6; background: var(--bg2); border: 1px solid var(--line); border-radius: 10px; padding: 12px; margin: 0; }
.tt-line { display: flex; align-items: center; gap: 10px; font-size: 13px; padding: 5px 0; }
.tt-line .v-icon { color: var(--accent); }
.tt-member { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line); font-size: 13px; }
.tt-member:last-of-type { border-bottom: 0; }
.tt-drawer-foot { position: absolute; left: 0; right: 0; bottom: 0; display: flex; gap: 10px; padding: 14px 20px; background: #fff; border-top: 1px solid var(--line); }
.tt-dialog { border-radius: 16px !important; }

@media (prefers-reduced-motion: reduce) { .tt-group, .tt-segment, .tt-share { transition: none; } }
</style>