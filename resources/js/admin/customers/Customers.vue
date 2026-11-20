<template>
    <v-slide-x-transition appear>
        <div class="tt-cust">
            <v-window v-model="tab">
                <!-- ═════════════════════════ Customer list ═════════════════════════ -->
                <v-window-item value="list" lazy>
                    <!-- Hero -->
                    <header class="tt-hero">
                        <div class="d-flex flex-wrap align-end ga-4">
                            <div>
                                <h1 class="tt-h1">Customers</h1>
                                <div class="tt-hero-sub">Everyone who buys from your store, with their orders, spend and loyalty tier</div>
                            </div>
                            <v-spacer />
                            <div class="d-flex flex-wrap align-center ga-3">
                                <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-download" @click="exportCsv">Export CSV</v-btn>
                                <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-plus" @click="openAdd">Add customer</v-btn>
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

                    <!-- Table card -->
                    <v-card flat class="tt-card mt-5">
                        <div class="tt-head tt-head-wrap">
                            <div class="tt-head-ic"><v-icon size="16">mdi-account-group-outline</v-icon></div>
                            <div>
                                <div class="tt-card-title">All customers</div>
                                <div class="tt-sub">{{ filteredItems.length }} of {{ items.length }} shown</div>
                            </div>
                            <v-spacer />
                            <v-select
                                v-model="cityFilter"
                                :items="cityOptions"
                                prepend-inner-icon="mdi-map-marker-outline"
                                variant="outlined"
                                density="compact"
                                hide-details
                                class="tt-filter mr-2"
                            />
                            <v-text-field
                                v-model="searchValue"
                                placeholder="Search name, phone, email…"
                                prepend-inner-icon="mdi-magnify"
                                clearable
                                variant="outlined"
                                density="compact"
                                hide-details
                                class="tt-search"
                                @click:clear="searchValue = ''"
                            />
                        </div>

                        <!-- Segment tabs -->
                        <div class="tt-segments" role="tablist" aria-label="Customer segments">
                            <button
                                v-for="s in segments"
                                :key="s.value"
                                type="button"
                                role="tab"
                                class="tt-segment"
                                :class="{ on: segment === s.value }"
                                :aria-selected="segment === s.value"
                                @click="segment = s.value"
                            >
                                {{ s.label }}<span class="tt-segment-count">{{ s.count }}</span>
                            </button>
                        </div>

                        <v-card-text class="tt-body pa-0">
                            <EasyDataTable
                                :headers="headers"
                                :items="filteredItems"
                                table-class-name="customize-table"
                                buttons-pagination
                                :rows-per-page="15"
                                :rows-items="[10, 15, 25, 50]"
                                :fixedIndex="true"
                                :search-value="searchValue"
                                :loading="loading"
                                sort-by="spent"
                                sort-type="desc"
                                empty-message="No customers match these filters"
                            >
                                <template #item-name="item">
                                    <div class="tt-person" @click="openDetail(item)">
                                        <v-avatar size="38" class="tt-person-avatar" :style="{ background: avatarColor(item.name) }">
                                            <span>{{ initials(item.name) }}</span>
                                        </v-avatar>
                                        <div class="tt-person-text">
                                            <div class="tt-name">{{ item.name }}</div>
                                            <div class="tt-sub">{{ item.email || item.company || "No email on file" }}</div>
                                        </div>
                                    </div>
                                </template>

                                <template #item-phone="item">
                                    <span v-if="item.phone">{{ item.phone }}</span>
                                    <span v-else class="tt-muted">—</span>
                                </template>

                                <template #item-city="item">
                                    <span v-if="item.city">{{ item.city }}</span>
                                    <span v-else class="tt-muted">—</span>
                                </template>

                                <template #item-orders="item">
                                    <span class="tt-strong">{{ item.orders }}</span>
                                </template>

                                <template #item-spent="item">
                                    <span class="tt-strong">{{ money(item.spent) }}</span>
                                </template>

                                <template #item-lastOrder="item">
                                    <span v-if="item.lastOrder">{{ fmtDate(item.lastOrder) }}</span>
                                    <span v-else class="tt-muted">No orders yet</span>
                                </template>

                                <template #item-tier="item">
                                    <span class="tt-chip" :class="'tone-' + tierTone(tierOf(item))">{{ tierOf(item) }}</span>
                                </template>

                                <template #item-status="item">
                                    <span class="tt-chip" :class="'tone-' + statusTone(item.status)">{{ item.status || "Active" }}</span>
                                </template>

                                <template #item-operation="item">
                                    <div class="tt-actions">
                                        <v-btn icon="mdi-eye-outline" size="small" variant="text" color="#0a5548" aria-label="View customer" @click="openDetail(item)" />
                                        <v-btn icon="mdi-pencil-outline" size="small" variant="text" color="primary" aria-label="Edit customer" @click.prevent="editItem(item)" />
                                        <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" aria-label="Delete customer" @click="removeItem(item)" />
                                    </div>
                                </template>
                            </EasyDataTable>
                        </v-card-text>
                    </v-card>
                </v-window-item>

                <!-- ═════════════════════════ Add / Edit customer ═════════════════════════ -->
                <v-window-item value="add" lazy>
                    <header class="tt-hero tt-hero-slim">
                        <div class="d-flex flex-wrap align-center ga-3">
                            <div>
                                <h1 class="tt-h1">{{ form.id ? "Edit customer" : "Add customer" }}</h1>
                                <div class="tt-hero-sub">
                                    {{ form.id ? "Update the details for " + form.name : "Create a customer profile for walk-in, phone or wholesale buyers" }}
                                </div>
                            </div>
                            <v-spacer />
                            <v-btn variant="flat" class="text-none tt-btn-ghost" prepend-icon="mdi-arrow-left" @click="closeAdd">Back to customers</v-btn>
                            <v-btn variant="flat" class="text-none tt-btn-primary" prepend-icon="mdi-check" :loading="saving" @click="saveCustomer">
                                {{ form.id ? "Save changes" : "Create customer" }}
                            </v-btn>
                        </div>
                    </header>

                    <v-row dense class="mt-3">
                        <v-col cols="12" md="8">
                            <!-- Personal -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-account-outline</v-icon></div><span class="tt-card-title">Personal details</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Full name <span class="tt-required">*</span></div>
                                            <v-text-field v-model="form.name" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Rahim Uddin" autofocus />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Customer type</div>
                                            <v-select v-model="form.type" :items="typeOptions" variant="outlined" density="comfortable" hide-details class="mb-4" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Phone <span class="tt-required">*</span></div>
                                            <v-text-field v-model="form.phone" variant="outlined" density="comfortable" hide-details class="mb-4" prepend-inner-icon="mdi-phone-outline" placeholder="01XXX-XXXXXX" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Alternative phone</div>
                                            <v-text-field v-model="form.altPhone" variant="outlined" density="comfortable" hide-details class="mb-4" prepend-inner-icon="mdi-phone-plus-outline" placeholder="Optional" />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Email</div>
                                            <v-text-field v-model="form.email" type="email" variant="outlined" density="comfortable" hide-details prepend-inner-icon="mdi-email-outline" placeholder="name@example.com" />
                                        </v-col>
                                        <v-col v-if="form.type !== 'Individual'" cols="12" sm="6">
                                            <div class="tt-field-label">Shop or company name</div>
                                            <v-text-field v-model="form.company" variant="outlined" density="comfortable" hide-details prepend-inner-icon="mdi-store-outline" placeholder="e.g. Dhaka Mobile Hub" />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Address -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-map-marker-outline</v-icon></div><span class="tt-card-title">Delivery address</span></div>
                                <v-card-text class="tt-body">
                                    <v-row dense>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">Division</div>
                                            <v-select v-model="form.division" :items="divisions" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="Select division" clearable />
                                        </v-col>
                                        <v-col cols="12" sm="6">
                                            <div class="tt-field-label">City or district</div>
                                            <v-combobox v-model="form.city" :items="cityList" variant="outlined" density="comfortable" hide-details class="mb-4" placeholder="e.g. Dhaka" />
                                        </v-col>
                                        <v-col cols="12" sm="8">
                                            <div class="tt-field-label">Street address</div>
                                            <v-text-field v-model="form.address" variant="outlined" density="comfortable" hide-details placeholder="House, road, area" />
                                        </v-col>
                                        <v-col cols="12" sm="4">
                                            <div class="tt-field-label">Postal code</div>
                                            <v-text-field v-model="form.postalCode" variant="outlined" density="comfortable" hide-details placeholder="1207" />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Notes -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-note-text-outline</v-icon></div><span class="tt-card-title">Notes and tags</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-field-label">Tags</div>
                                    <v-combobox v-model="form.tags" :items="tagSuggestions" multiple chips closable-chips variant="outlined" density="comfortable" hide-details class="mb-2" placeholder="Type a tag and press Enter" />
                                    <div class="tt-sub mb-4">Use tags like Wholesale, Gamer or Repeat buyer to find groups of customers quickly.</div>

                                    <div class="tt-field-label">Internal note</div>
                                    <v-textarea v-model="form.notes" variant="outlined" density="comfortable" rows="3" hide-details placeholder="Only your team can see this. For example: prefers bKash, calls before delivery." />
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="4">
                            <!-- Status -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-toggle-switch-outline</v-icon></div><span class="tt-card-title">Account status</span></div>
                                <v-card-text class="tt-body">
                                    <div v-for="opt in statusOptions" :key="opt.value" class="tt-radio-box" :class="{ 'is-selected': form.status === opt.value }" role="radio" :aria-checked="form.status === opt.value" tabindex="0" @click="form.status = opt.value" @keydown.enter.prevent="form.status = opt.value" @keydown.space.prevent="form.status = opt.value">
                                        <span class="tt-radio-dot" :class="{ 'is-on': form.status === opt.value }"></span>
                                        <div>
                                            <div>{{ opt.label }}</div>
                                            <div class="tt-sub">{{ opt.hint }}</div>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Loyalty -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-star-circle-outline</v-icon></div><span class="tt-card-title">Loyalty tier</span></div>
                                <v-card-text class="tt-body">
                                    <v-select v-model="form.tier" :items="tierChoices" variant="outlined" density="comfortable" hide-details />
                                    <div class="tt-sub mt-2">Automatic sets the tier from lifetime spend: VIP from ৳ 1,50,000 and Regular from ৳ 60,000.</div>
                                </v-card-text>
                            </v-card>

                            <!-- Consent -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-bell-outline</v-icon></div><span class="tt-card-title">Communication</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-option-row"><span>Order updates by SMS</span><v-checkbox-btn v-model="form.smsConsent" color="#0f9d6b" /></div>
                                    <div class="tt-option-row"><span>Offers and news by email</span><v-checkbox-btn v-model="form.marketingConsent" color="#0f9d6b" /></div>
                                    <div class="tt-option-row"><span>Allow cash on delivery</span><v-checkbox-btn v-model="form.allowCod" color="#0f9d6b" /></div>
                                </v-card-text>
                            </v-card>

                            <!-- Preview -->
                            <v-card flat class="tt-card mb-5">
                                <div class="tt-head"><div class="tt-head-ic"><v-icon size="16">mdi-card-account-details-outline</v-icon></div><span class="tt-card-title">Profile preview</span></div>
                                <v-card-text class="tt-body">
                                    <div class="tt-preview">
                                        <v-avatar size="56" class="tt-person-avatar" :style="{ background: avatarColor(form.name || 'Customer') }">
                                            <span class="tt-preview-initials">{{ initials(form.name || "Customer") }}</span>
                                        </v-avatar>
                                        <div class="tt-name mt-2">{{ form.name || "Customer name" }}</div>
                                        <div class="tt-sub">{{ form.email || form.phone || "Contact details appear here" }}</div>
                                        <div class="d-flex flex-wrap ga-1 mt-3">
                                            <span class="tt-chip tone-teal">{{ form.type }}</span>
                                            <span class="tt-chip" :class="'tone-' + tierTone(form.tier === 'Automatic' ? tierFromSpent(form.spent) : form.tier)">{{ form.tier === "Automatic" ? tierFromSpent(form.spent) : form.tier }}</span>
                                            <span class="tt-chip" :class="'tone-' + statusTone(form.status)">{{ form.status }}</span>
                                        </div>
                                        <div v-if="form.city || form.division" class="tt-sub mt-3"><v-icon size="13">mdi-map-marker-outline</v-icon> {{ [form.city, form.division].filter(Boolean).join(", ") }}</div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- ═════════════════════════ Detail drawer ═════════════════════════ -->
            <v-navigation-drawer v-model="drawer" location="right" temporary width="420" class="tt-drawer">
                <template v-if="selected">
                    <div class="tt-drawer-hero">
                        <div class="d-flex align-start">
                            <v-avatar size="56" class="tt-person-avatar" :style="{ background: avatarColor(selected.name) }"><span class="tt-preview-initials">{{ initials(selected.name) }}</span></v-avatar>
                            <v-spacer />
                            <v-btn icon="mdi-close" size="small" variant="text" color="white" aria-label="Close" @click="drawer = false" />
                        </div>
                        <div class="tt-drawer-name">{{ selected.name }}</div>
                        <div class="tt-drawer-sub">{{ selected.company || selected.type || "Individual" }} · Customer since {{ fmtDate(selected.joined) }}</div>
                        <div class="d-flex flex-wrap ga-2 mt-3">
                            <span class="tt-chip tone-light">{{ tierOf(selected) }}</span>
                            <span class="tt-chip tone-light">{{ selected.status || "Active" }}</span>
                        </div>
                    </div>

                    <div class="tt-drawer-stats">
                        <div class="tt-stat"><small>Orders</small><b>{{ selected.orders }}</b></div>
                        <div class="tt-stat"><small>Total spent</small><b>{{ money(selected.spent) }}</b></div>
                        <div class="tt-stat"><small>Avg order</small><b>{{ money(avgOrder(selected)) }}</b></div>
                    </div>

                    <div class="tt-drawer-body">
                        <div class="tt-drawer-t">Contact</div>
                        <div class="tt-line"><v-icon size="16">mdi-phone-outline</v-icon><span>{{ selected.phone || "No phone" }}</span></div>
                        <div class="tt-line"><v-icon size="16">mdi-email-outline</v-icon><span>{{ selected.email || "No email" }}</span></div>
                        <div class="tt-line"><v-icon size="16">mdi-map-marker-outline</v-icon><span>{{ addressLine(selected) || "No address" }}</span></div>

                        <div v-if="selected.tags && selected.tags.length" class="tt-drawer-t mt-5">Tags</div>
                        <div v-if="selected.tags && selected.tags.length" class="d-flex flex-wrap ga-1">
                            <span v-for="t in selected.tags" :key="t" class="tt-chip tone-teal">{{ t }}</span>
                        </div>

                        <div class="tt-drawer-t mt-5">Recent orders</div>
                        <div v-if="ordersLoading" class="tt-sub">Loading orders…</div>
                        <div v-else-if="!recentOrders.length" class="tt-empty">No orders to show for this customer yet.</div>
                        <div v-for="o in recentOrders" :key="o.id" class="tt-order-row">
                            <div>
                                <div class="tt-strong">{{ o.id }}</div>
                                <div class="tt-sub">{{ fmtDate(o.date) }} · {{ o.payment }}</div>
                            </div>
                            <v-spacer />
                            <div class="text-right">
                                <div class="tt-strong">{{ money(o.total) }}</div>
                                <span class="tt-chip tone-sm" :class="'tone-' + orderTone(o.status)">{{ o.status }}</span>
                            </div>
                        </div>

                        <div v-if="selected.notes" class="tt-drawer-t mt-5">Internal note</div>
                        <p v-if="selected.notes" class="tt-note">{{ selected.notes }}</p>
                    </div>

                    <div class="tt-drawer-foot">
                        <v-btn variant="outlined" class="text-none flex-grow-1" prepend-icon="mdi-phone-outline" :href="selected.phone ? 'tel:' + selected.phone : undefined" :disabled="!selected.phone">Call</v-btn>
                        <v-btn variant="flat" class="text-none flex-grow-1 tt-btn-solid" prepend-icon="mdi-pencil-outline" @click="editFromDrawer">Edit profile</v-btn>
                    </div>
                </template>
            </v-navigation-drawer>

            <!-- Delete confirm dialog -->
            <v-dialog v-model="deleteDialog" max-width="420">
                <v-card class="tt-dialog">
                    <v-card-title class="text-subtitle-1 font-weight-bold pt-5 px-5">Delete customer</v-card-title>
                    <v-card-text class="px-5">Delete <b>{{ deletedName }}</b>? Their order history stays in reports, but the profile can't be restored.</v-card-text>
                    <v-card-actions class="pb-4 px-5">
                        <v-spacer />
                        <v-btn variant="text" class="text-none" @click="cancel">Keep customer</v-btn>
                        <v-btn color="error" variant="flat" class="text-none" :loading="deleting" @click="agree">Delete</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="bottom right">{{ snackbarText }}</v-snackbar>
        </div>
    </v-slide-x-transition>
</template>

<script>
const VIP_FROM = 150000;
const REGULAR_FROM = 60000;

const fullMoney = (v) => "৳ " + Number(v || 0).toLocaleString("en-IN");

const emptyForm = () => ({
    id: null,
    name: "",
    type: "Individual",
    phone: "",
    altPhone: "",
    email: "",
    company: "",
    division: null,
    city: "",
    address: "",
    postalCode: "",
    tags: [],
    notes: "",
    status: "Active",
    tier: "Automatic",
    smsConsent: true,
    marketingConsent: false,
    allowCod: true,
    spent: 0,
    orders: 0,
});

/* Sample rows so the page looks right before your API is connected.
   Set to false once /api/customers returns real data. */
const USE_SAMPLE_DATA_ON_ERROR = true;
const makeSample = () => {
    let s = 77;
    const r = () => ((s = (s * 9301 + 49297) % 233280) / 233280);
    const day = 864e5;
    const names = ["Rahim Traders", "Sabbir Hossain", "Dhaka Mobile Hub", "Nusrat Jahan", "Gadget Point", "Tanvir Ahmed", "Mim Electronics", "Farhana Akter", "Techland BD", "Arif Hasan", "Shanta Islam", "Star Gadgets", "Imran Khan", "Sumaiya Rahman", "Nazmul Haque", "Ridoy Store", "Mahmudul Karim", "Tasnim Sultana", "Jahid Hasan", "Lamia Chowdhury", "Rafiq Mobile Corner", "Sadia Afrin", "Kamrul Islam", "Priya Das"];
    const cities = [["Dhaka", "Dhaka"], ["Dhaka", "Dhaka"], ["Chattogram", "Chattogram"], ["Sylhet", "Sylhet"], ["Khulna", "Khulna"], ["Rajshahi", "Rajshahi"], ["Barishal", "Barishal"], ["Rangpur", "Rangpur"], ["Cumilla", "Chattogram"]];
    return names.map((name, i) => {
        const orders = i % 9 === 8 ? 0 : Math.round(1 + r() * 38);
        const spent = orders * Math.round(1800 + r() * 4200);
        const [city, division] = cities[Math.floor(r() * cities.length)];
        const business = /Traders|Hub|Point|Electronics|BD|Gadgets|Store|Corner/.test(name);
        return {
            id: i + 1,
            name,
            type: business ? "Wholesale" : "Individual",
            company: business ? name : "",
            phone: "01" + (3 + Math.floor(r() * 7)) + String(Math.floor(r() * 1e8)).padStart(8, "0").replace(/^(\d{4})(\d{4})$/, "$1-$2").replace("-", ""),
            email: business ? "" : name.toLowerCase().replace(/[^a-z]+/g, ".") + "@example.com",
            city,
            division,
            address: "Road " + (1 + Math.floor(r() * 20)) + ", House " + (1 + Math.floor(r() * 90)),
            orders,
            spent,
            lastOrder: orders ? new Date(Date.now() - Math.floor(r() * 75) * day).toISOString() : null,
            joined: new Date(Date.now() - (30 + Math.floor(r() * 400)) * day).toISOString(),
            status: i % 11 === 10 ? "Blocked" : orders === 0 || (i % 7 === 6) ? "Inactive" : "Active",
            tier: "Automatic",
            tags: business ? ["Wholesale"] : i % 4 === 0 ? ["Gamer"] : [],
            notes: i % 5 === 0 ? "Prefers bKash. Call before delivery." : "",
            smsConsent: true,
            marketingConsent: i % 3 === 0,
            allowCod: true,
        };
    });
};

export default {
    name: "CustomerList",

    data() {
        return {
            tab: "list",
            searchValue: "",
            segment: "all",
            cityFilter: "All cities",
            loading: false,

            items: [],

            form: emptyForm(),
            saving: false,
            deleting: false,
            deleteDialog: false,
            deletedId: -1,
            deletedName: "",

            drawer: false,
            selected: null,
            recentOrders: [],
            ordersLoading: false,

            snackbar: false,
            snackbarText: "",
            snackbarColor: "#0f9d6b",

            headers: [
                { text: "Customer", value: "name", sortable: true, width: 280 },
                { text: "Phone", value: "phone", width: 140 },
                { text: "City", value: "city", sortable: true, width: 120 },
                { text: "Orders", value: "orders", sortable: true, width: 90 },
                { text: "Total spent", value: "spent", sortable: true, width: 130 },
                { text: "Last order", value: "lastOrder", sortable: true, width: 130 },
                { text: "Tier", value: "tier", width: 100 },
                { text: "Status", value: "status", sortable: true, width: 110 },
                { text: "Actions", value: "operation", width: 130 },
            ],

            typeOptions: ["Individual", "Wholesale", "Reseller", "Corporate"],
            tierChoices: ["Automatic", "VIP", "Regular", "New"],
            statusOptions: [
                { label: "Active", value: "Active", hint: "Can place orders" },
                { label: "Inactive", value: "Inactive", hint: "Hasn't ordered in a while" },
                { label: "Blocked", value: "Blocked", hint: "Can't place new orders" },
            ],
            tagSuggestions: ["Wholesale", "Gamer", "Repeat buyer", "Corporate", "Reseller", "Needs follow-up"],
            divisions: ["Dhaka", "Chattogram", "Rajshahi", "Khulna", "Barishal", "Sylhet", "Rangpur", "Mymensingh"],
            cityList: ["Dhaka", "Chattogram", "Sylhet", "Khulna", "Rajshahi", "Barishal", "Rangpur", "Mymensingh", "Cumilla", "Gazipur", "Narayanganj", "Bogura", "Cox's Bazar"],
        };
    },

    computed: {
        cityOptions() {
            const cities = [...new Set(this.items.map((c) => c.city).filter(Boolean))].sort();
            return ["All cities", ...cities];
        },
        segments() {
            const count = (fn) => this.items.filter(fn).length;
            return [
                { value: "all", label: "All", count: this.items.length },
                { value: "VIP", label: "VIP", count: count((c) => this.tierOf(c) === "VIP") },
                { value: "Regular", label: "Regular", count: count((c) => this.tierOf(c) === "Regular") },
                { value: "New", label: "New", count: count((c) => this.tierOf(c) === "New") },
                { value: "Inactive", label: "Inactive", count: count((c) => (c.status || "Active") === "Inactive") },
                { value: "Blocked", label: "Blocked", count: count((c) => c.status === "Blocked") },
            ];
        },
        filteredItems() {
            return this.items
                .filter((c) => {
                    if (this.segment === "all") return true;
                    if (this.segment === "Inactive" || this.segment === "Blocked") return (c.status || "Active") === this.segment;
                    return this.tierOf(c) === this.segment;
                })
                .filter((c) => this.cityFilter === "All cities" || c.city === this.cityFilter)
                .map((c) => ({ ...c, tier: this.tierOf(c) }));
        },
        heroStats() {
            const total = this.items.length;
            const monthAgo = Date.now() - 30 * 864e5;
            const fresh = this.items.filter((c) => c.joined && new Date(c.joined).getTime() >= monthAgo).length;
            const vip = this.items.filter((c) => this.tierOf(c) === "VIP").length;
            const ltv = this.items.reduce((a, c) => a + (Number(c.spent) || 0), 0);
            const ordered = this.items.filter((c) => c.orders > 0);
            const repeat = ordered.length ? Math.round((ordered.filter((c) => c.orders > 1).length / ordered.length) * 100) : 0;
            return [
                { icon: "mdi-account-group-outline", color: "#0f9d6b", value: total.toLocaleString("en-IN"), label: "Total customers", note: fresh + " joined in the last 30 days" },
                { icon: "mdi-crown-outline", color: "#7c3aed", value: String(vip), label: "VIP customers", note: "Spent over " + fullMoney(VIP_FROM) },
                { icon: "mdi-cash-multiple", color: "#f59e0b", value: fullMoney(ltv), label: "Lifetime sales", note: "Across all customers" },
                { icon: "mdi-repeat", color: "#ec4899", value: repeat + "%", label: "Repeat buyers", note: "Ordered more than once" },
            ];
        },
    },

    methods: {
        money: fullMoney,

        /* ── formatting helpers ── */
        fmtDate(d) {
            if (!d) return "—";
            const dt = new Date(d);
            return isNaN(dt) ? "—" : dt.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
        },
        initials(name) {
            const p = (name || "?").trim().split(/\s+/);
            return ((p[0] || "")[0] + (p.length > 1 ? p[p.length - 1][0] : "")).toUpperCase();
        },
        avatarColor(name) {
            const colors = ["#0f9d6b", "#2f5be7", "#7c3aed", "#0f766e", "#f59e0b", "#ec4899", "#ef6a45"];
            let h = 0;
            for (const ch of name || "") h = (h * 31 + ch.charCodeAt(0)) >>> 0;
            return colors[h % colors.length];
        },
        addressLine(c) {
            return [c.address, c.city, c.division && c.division !== c.city ? c.division : ""].filter(Boolean).join(", ");
        },
        avgOrder(c) {
            return c.orders ? Math.round(c.spent / c.orders) : 0;
        },

        /* ── tiers and tones ── */
        tierFromSpent(spent) {
            const v = Number(spent) || 0;
            return v >= VIP_FROM ? "VIP" : v >= REGULAR_FROM ? "Regular" : "New";
        },
        tierOf(c) {
            return c.tier && c.tier !== "Automatic" ? c.tier : this.tierFromSpent(c.spent);
        },
        tierTone(t) {
            return { VIP: "violet", Regular: "blue", New: "teal" }[t] || "gray";
        },
        statusTone(s) {
            return { Active: "green", Inactive: "amber", Blocked: "red" }[s || "Active"] || "gray";
        },
        orderTone(s) {
            return { Delivered: "green", Processing: "blue", Pending: "amber", Cancelled: "red" }[s] || "gray";
        },

        /* ── data ── */
        normalize(c) {
            return {
                ...c,
                company: c.company ?? "",
                altPhone: c.alt_phone ?? c.altPhone ?? "",
                postalCode: c.postal_code ?? c.postalCode ?? "",
                lastOrder: c.last_order ?? c.lastOrder ?? null,
                joined: c.created_at ?? c.joined ?? null,
                orders: Number(c.orders_count ?? c.orders ?? 0),
                spent: Number(c.total_spent ?? c.spent ?? 0),
                tier: c.tier || "Automatic",
                status: c.status || "Active",
                tags: Array.isArray(c.tags) ? c.tags : [],
                smsConsent: Boolean(c.sms_consent ?? c.smsConsent ?? true),
                marketingConsent: Boolean(c.marketing_consent ?? c.marketingConsent ?? false),
                allowCod: c.allow_cod !== undefined ? Boolean(c.allow_cod) : (c.allowCod ?? true),
            };
        },

        async allItem() {
            this.loading = true;
            try {
                const res = await this.axios.get("/api/customers");
                if (res.data && res.data.success) {
                    this.items = res.data.data.map(this.normalize);
                } else if (USE_SAMPLE_DATA_ON_ERROR) {
                    this.items = makeSample();
                }
            } catch (error) {
                console.error("Failed to load customers:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) this.items = makeSample();
                else this.showError("Couldn't load customers. Check your connection and refresh.");
            } finally {
                this.loading = false;
            }
        },

        async loadOrders(c) {
            this.recentOrders = [];
            this.ordersLoading = true;
            try {
                const res = await this.axios.get(`/api/customers/${c.id}/orders?limit=5`);
                if (res.data && res.data.success) {
                    this.recentOrders = res.data.data.map((o) => ({
                        id: o.order_number || o.id,
                        date: o.created_at || o.date,
                        payment: o.payment_method || o.payment || "—",
                        status: o.status || "Pending",
                        total: Number(o.total || 0),
                    }));
                }
            } catch {
                /* order history is optional; the drawer shows an empty state */
            } finally {
                this.ordersLoading = false;
            }
        },

        /* ── navigation ── */
        openAdd() {
            this.form = emptyForm();
            this.tab = "add";
        },
        editItem(item) {
            const base = emptyForm();
            this.form = { ...base, ...item, tier: item.tier || "Automatic", tags: [...(item.tags || [])] };
            this.tab = "add";
        },
        editFromDrawer() {
            const c = this.selected;
            this.drawer = false;
            if (c) this.editItem(c);
        },
        closeAdd() {
            this.tab = "list";
            this.form = emptyForm();
        },
        openDetail(item) {
            this.selected = this.items.find((c) => c.id === item.id) || item;
            this.drawer = true;
            this.loadOrders(this.selected);
        },

        /* ── save ── */
        validate() {
            const f = this.form;
            if (!f.name || !f.name.trim()) return "Enter the customer's name.";
            const phone = (f.phone || "").replace(/[\s-]/g, "");
            if (!phone) return "Enter a phone number.";
            if (!/^(\+?880|0)1[3-9]\d{8}$/.test(phone)) return "Enter a valid Bangladeshi mobile number, like 01712-345678.";
            if (f.email && !/^\S+@\S+\.\S+$/.test(f.email)) return "Enter a valid email address.";
            return null;
        },

        async saveCustomer() {
            const err = this.validate();
            if (err) return this.showError(err);

            this.saving = true;
            const wasEdit = Boolean(this.form.id);
            try {
                const payload = { ...this.form, tags: this.form.tags || [] };
                const res = wasEdit
                    ? await this.axios.put(`/api/customers/${this.form.id}`, payload)
                    : await this.axios.post("/api/customers", payload);

                if (res.data && res.data.success !== false) {
                    this.showSuccess(wasEdit ? "Customer updated." : "Customer created.");
                    await this.allItem();
                    this.closeAdd();
                } else {
                    this.showError(res.data?.message || "Couldn't save the customer. Try again.");
                }
            } catch (error) {
                console.error("Save error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    // demo mode: keep the change locally so the UI can be tested without a backend
                    const row = this.normalize({ ...this.form, id: this.form.id || Date.now(), joined: this.form.joined || new Date().toISOString() });
                    this.items = wasEdit ? this.items.map((c) => (c.id === row.id ? row : c)) : [row, ...this.items];
                    this.showSuccess((wasEdit ? "Customer updated" : "Customer created") + " (demo data).");
                    this.closeAdd();
                } else {
                    this.showError("Couldn't save the customer. Check your connection and try again.");
                }
            } finally {
                this.saving = false;
            }
        },

        /* ── delete ── */
        removeItem(item) {
            this.deletedId = item.id;
            this.deletedName = item.name;
            this.deleteDialog = true;
        },
        cancel() {
            this.deleteDialog = false;
            this.deletedId = -1;
            this.deletedName = "";
        },
        async agree() {
            this.deleting = true;
            try {
                const res = await this.axios.delete(`/api/customers/${this.deletedId}`);
                if (res.data && res.data.success !== false) {
                    this.items = this.items.filter((c) => c.id !== this.deletedId);
                    this.showSuccess("Customer deleted.");
                } else {
                    this.showError(res.data?.message || "Couldn't delete the customer.");
                }
            } catch (error) {
                console.error("Delete error:", error);
                if (USE_SAMPLE_DATA_ON_ERROR) {
                    this.items = this.items.filter((c) => c.id !== this.deletedId);
                    this.showSuccess("Customer deleted (demo data).");
                } else {
                    this.showError("Couldn't delete the customer. Try again.");
                }
            } finally {
                this.deleting = false;
                this.cancel();
            }
        },

        /* ── export ── */
        exportCsv() {
            const rows = this.filteredItems;
            if (!rows.length) return this.showError("There are no customers to export.");
            const head = ["Name", "Phone", "Email", "City", "Orders", "Total spent", "Last order", "Tier", "Status"];
            const esc = (v) => '"' + String(v ?? "").replace(/"/g, '""') + '"';
            const lines = rows.map((c) => [c.name, c.phone, c.email, c.city, c.orders, c.spent, c.lastOrder ? this.fmtDate(c.lastOrder) : "", this.tierOf(c), c.status].map(esc).join(","));
            const blob = new Blob(["\ufeff" + [head.map(esc).join(","), ...lines].join("\n")], { type: "text/csv;charset=utf-8" });
            const a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = "customers.csv";
            a.click();
            URL.revokeObjectURL(a.href);
            this.showSuccess("Exported " + rows.length + " customers.");
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
        document.title = "Customers";
        this.allItem();
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-cust {
    --ink: #0f2a26;
    --muted: #6b7f7b;
    --line: #e3eae8;
    --bg: #f3f7f6;
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
.tt-hero-sub { font-size: 13.5px; color: rgba(255, 255, 255, 0.72); margin-top: 4px; max-width: 560px; }

.tt-btn-ghost { height: 40px !important; background: transparent !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.4); font-weight: 500; }
.tt-btn-primary { height: 40px !important; background: #fff !important; color: #0a5548 !important; font-weight: 600; box-shadow: 0 8px 18px -10px rgba(0, 0, 0, 0.5); }
.tt-btn-solid { background: #0a5548 !important; color: #fff !important; font-weight: 600; }

.tt-hero-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 22px; }
.tt-hero-item { --hue: #0f9d6b; position: relative; overflow: hidden; display: flex; align-items: flex-start; gap: 14px; padding: 15px 16px; border-radius: 14px; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.15); }
.tt-hero-item::after { content: ""; position: absolute; right: -40px; top: -40px; width: 120px; height: 120px; border-radius: 50%; background: var(--hue); opacity: 0.25; filter: blur(10px); }
.tt-hero-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--hue); flex: none; box-shadow: 0 8px 16px -8px var(--hue); }
.tt-hero-value { font-size: 22px; font-weight: 700; line-height: 1.2; letter-spacing: -0.4px; }
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
.tt-muted { color: var(--muted); }
.tt-strong { font-weight: 600; color: var(--ink); }
.tt-field-label { font-size: 12.5px; font-weight: 600; color: #3a4a47; margin-bottom: 8px; }
.tt-required { color: #dc2626; }
.tt-search { max-width: 280px; min-width: 200px; }
.tt-filter { max-width: 190px; min-width: 150px; }
.tt-search :deep(.v-field), .tt-filter :deep(.v-field) { font-size: 13px; border-radius: 10px; background: var(--bg2); }

/* ───────── Segments ───────── */
.tt-segments { display: flex; gap: 4px; padding: 6px 14px 10px; overflow-x: auto; scrollbar-width: none; border-bottom: 1px solid var(--line); }
.tt-segments::-webkit-scrollbar { display: none; }
.tt-segment { flex: none; display: inline-flex; align-items: center; gap: 8px; border: 0; background: transparent; font: inherit; font-size: 13px; font-weight: 500; color: var(--muted); padding: 7px 14px; border-radius: 11px; cursor: pointer; transition: background 0.15s, color 0.15s; }
.tt-segment:hover { color: var(--ink); background: #eaf2ef; }
.tt-segment.on { background: #0a5548; color: #fff; font-weight: 600; }
.tt-segment:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-segment-count { font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 10px; background: #e3eae8; color: #3a4a47; }
.tt-segment.on .tt-segment-count { background: rgba(255, 255, 255, 0.22); color: #fff; }

/* ───────── Table cells ───────── */
.tt-person { display: flex; align-items: center; gap: 12px; padding: 6px 0; cursor: pointer; min-width: 0; }
.tt-person:hover .tt-name { color: #0a5548; text-decoration: underline; text-underline-offset: 3px; }
.tt-person-avatar { flex: none; color: #fff; font-size: 13px; font-weight: 600; }
.tt-person-text { min-width: 0; }
.tt-name { font-weight: 600; font-size: 13.5px; color: var(--ink); }
.tt-person-text .tt-sub { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 220px; }
.tt-actions { display: flex; gap: 2px; }
.tt-preview-initials { font-size: 18px; }

.tt-chip { display: inline-block; font-size: 11.5px; font-weight: 600; padding: 3px 11px; border-radius: 20px; white-space: nowrap; }
.tt-chip.tone-sm { font-size: 10.5px; padding: 2px 9px; margin-top: 2px; }
.tone-green { background: #dff5ec; color: #0f7a4d; }
.tone-blue { background: #e6edff; color: #1f4e9c; }
.tone-amber { background: #fdecc8; color: #8a5a00; }
.tone-red { background: #fce1d8; color: #b4381a; }
.tone-violet { background: #ebe5ff; color: #5b3fd1; }
.tone-teal { background: #d3f1f2; color: #0b6b72; }
.tone-gray { background: #e8ecea; color: #5b6275; }
.tone-light { background: rgba(255, 255, 255, 0.16); color: #fff; border: 1px solid rgba(255, 255, 255, 0.28); }

/* ───────── Form ───────── */
.tt-radio-box { display: flex; align-items: center; gap: 12px; font-size: 13.5px; border: 1px solid var(--line); border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; background: var(--bg2); cursor: pointer; transition: border-color 0.15s, background 0.15s; }
.tt-radio-box:last-child { margin-bottom: 0; }
.tt-radio-box:focus-visible { outline: 2px solid #0f9d6b; outline-offset: 2px; }
.tt-radio-box.is-selected { border-color: #0f9d6b; background: #eaf8f1; font-weight: 600; }
.tt-radio-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #c8d4d0; display: inline-block; position: relative; flex: none; }
.tt-radio-dot.is-on { border-color: #0f9d6b; }
.tt-radio-dot.is-on::after { content: ""; position: absolute; inset: 3px; border-radius: 50%; background: #0f9d6b; }
.tt-option-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; font-size: 13.5px; border-bottom: 1px solid var(--line); }
.tt-option-row:last-child { border-bottom: 0; }
.tt-preview { border: 1px solid var(--line); border-radius: 12px; padding: 16px; background: var(--bg2); }

/* ───────── Detail drawer ───────── */
.tt-drawer { font-family: Poppins, "Segoe UI", sans-serif; }
.tt-drawer-hero { color: #fff; padding: 18px 20px 20px; background: radial-gradient(420px 200px at 100% 0%, rgba(110, 231, 183, 0.22), transparent 62%), linear-gradient(135deg, #053d35 0%, #0a5548 60%, #0b6150 100%); }
.tt-drawer-name { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; margin-top: 10px; }
.tt-drawer-sub { font-size: 12.5px; color: rgba(255, 255, 255, 0.72); margin-top: 2px; }
.tt-drawer-stats { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid var(--line); background: #f4faf7; }
.tt-stat { padding: 12px 14px; border-left: 1px solid var(--line); min-width: 0; }
.tt-stat:first-child { border-left: 0; }
.tt-stat small { display: block; font-size: 11px; color: var(--muted); }
.tt-stat b { display: block; font-size: 14px; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-drawer-body { padding: 18px 20px 90px; }
.tt-drawer-t { font-size: 12.5px; font-weight: 600; margin-bottom: 8px; }
.tt-line { display: flex; align-items: center; gap: 10px; font-size: 13px; padding: 5px 0; color: var(--ink); }
.tt-line .v-icon { color: var(--accent); }
.tt-order-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 13px; }
.tt-order-row:last-of-type { border-bottom: 0; }
.tt-empty { font-size: 13px; color: var(--muted); background: var(--bg2); border: 1px dashed #c8d4d0; border-radius: 10px; padding: 14px; }
.tt-note { font-size: 13px; line-height: 1.6; background: var(--bg2); border: 1px solid var(--line); border-radius: 10px; padding: 12px; margin: 0; }
.tt-drawer-foot { position: absolute; left: 0; right: 0; bottom: 0; display: flex; gap: 10px; padding: 14px 20px; background: #fff; border-top: 1px solid var(--line); }
.tt-dialog { border-radius: 16px !important; }

/* ───────── EasyDataTable theme ───────── */
:deep(.customize-table) {
    --easy-table-border: 0;
    --easy-table-row-border: 1px solid #eef3f1;
    --easy-table-header-font-size: 12px;
    --easy-table-header-height: 44px;
    --easy-table-header-font-color: #e6f4ef;
    --easy-table-header-background-color: #0a5548;
    --easy-table-body-row-height: 62px;
    --easy-table-body-row-font-size: 13.5px;
    --easy-table-body-row-hover-background-color: #f4f9f7;
    --easy-table-body-item-padding: 6px 14px;
    --easy-table-footer-background-color: #f7faf9;
    --easy-table-footer-font-color: #3a4a47;
    --easy-table-footer-height: 52px;
    --easy-table-loading-mask-background-color: #ffffff;
    --easy-table-scrollbar-track-color: #eef3f1;
    --easy-table-scrollbar-color: #c8d4d0;
    --easy-table-buttons-pagination-border: 1px solid #d5dfdc;
    font-family: Poppins, "Segoe UI", sans-serif;
}
:deep(.customize-table .pagination__rows-per-page) { color: #6b7f7b; }
:deep(.customize-table .buttons-pagination .item.button.active) { background: #0a5548; border-color: #0a5548; color: #fff; }
:deep(.customize-table .vue3-easy-data-table__header th) { font-weight: 600; letter-spacing: 0.2px; }
</style>