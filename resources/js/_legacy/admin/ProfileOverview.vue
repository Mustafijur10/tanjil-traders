<template>
    <div class="tt-dash">
        <!-- ═════════ Hero header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-center ga-2 mb-3">
                <span class="tt-pill">STORE ADMIN PROFILE</span>
                <span class="tt-pill tt-pill-live"><span class="tt-pill-dot"></span> Profile active</span>
            </div>

            <div class="d-flex flex-wrap align-center ga-4">
                <div class="tt-avatar">
                    <img v-if="profile.avatar" :src="profile.avatar" :alt="displayName" />
                    <span v-else>{{ initials }}</span>
                </div>
                <div>
                    <h1 class="tt-h1">{{ displayName }}</h1>
                    <div class="tt-hero-sub">{{ profile.designation || profile.role || "Store Administrator" }}<span v-if="profile.department"> · {{ profile.department }}</span></div>
                    <div class="tt-hero-sub mt-1"><v-icon size="14">mdi-map-marker-outline</v-icon> {{ locationLabel }}</div>
                </div>
                <v-spacer />
                <v-btn color="white" variant="flat" class="text-none tt-add" prepend-icon="mdi-pencil-outline" to="/admin/profile/edit">Edit profile</v-btn>
            </div>

            <div class="tt-hero-strip">
                <div v-for="h in heroStats" :key="h.label" class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon :icon="h.icon" size="18" color="white" /></div>
                    <div class="flex-grow-1">
                        <div class="tt-hero-value">{{ h.value }}</div>
                        <div class="tt-hero-label">{{ h.label }}</div>
                        <div class="tt-hero-note">{{ h.note }}</div>
                    </div>
                </div>
            </div>
            <div class="tt-hero-line"></div>
        </header>

        <!-- ═════════ Anchor tabs ═════════ -->
        <nav class="tt-tabs" aria-label="Profile sections">
            <a class="is-active" href="#overview">Overview</a>
            <a href="#activity">Activity</a>
            <a href="#projects">Store projects</a>
            <a href="#documents">Store tools</a>
        </nav>

        <!-- ═════════ Profile health ═════════ -->
        <div id="overview" class="tt-section"><v-icon size="16">mdi-heart-pulse</v-icon><span>Profile health</span></div>
        <v-card flat class="tt-card tt-green">
            <div class="tt-head">
                <span class="tt-dot"></span><span class="tt-card-title">Complete your profile</span><v-spacer />
                <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/profile/edit" append-icon="mdi-arrow-right">Complete profile</v-btn>
            </div>
            <div class="tt-body">
                <div class="d-flex align-center ga-3">
                    <div class="tt-bar flex-grow-1"><div class="tt-bar-fill" :style="{ width: completion + '%' }"></div></div>
                    <span class="tt-bar-value">{{ completion }}%</span>
                </div>
                <div class="tt-sub mt-2">A complete profile helps your team identify the right store contact quickly.</div>
            </div>
        </v-card>

        <!-- ═════════ Account & presence ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-account-outline</v-icon><span>Account &amp; online presence</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Personal information</span><v-spacer />
                        <v-btn icon size="small" variant="text" to="/admin/profile/edit" aria-label="Edit personal information"><v-icon size="16">mdi-pencil-outline</v-icon></v-btn>
                    </div>
                    <div class="tt-body">
                        <div v-for="row in infoRows" :key="row.label" class="tt-info-row">
                            <span class="tt-sub">{{ row.label }}</span>
                            <span class="tt-info-value">{{ row.value }}</span>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Store portfolio</span><v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/profile/edit">Manage</v-btn>
                    </div>
                    <div class="tt-body">
                        <div class="tt-sub mb-1">Useful links for customers and your store team.</div>
                        <a v-for="(link, index) in visibleLinks" :key="`${link.label}-${index}`" class="tt-link-row" :href="link.url || '#'" target="_blank" rel="noopener noreferrer">
                            <span class="tt-icon-tile tt-icon-tile--purple"><i :class="link.icon || 'mdi mdi-link-variant'"></i></span>
                            <span class="tt-link-copy"><strong>{{ link.label || "Store link" }}</strong><small>{{ link.url || "Add a link in profile settings" }}</small></span>
                            <v-icon size="16" color="#9db3ae">mdi-open-in-new</v-icon>
                        </a>
                        <div v-if="!visibleLinks.length" class="tt-empty">No links added yet.</div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Expertise & about ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-star-outline</v-icon><span>Expertise &amp; about</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-teal h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Skills &amp; responsibilities</span><v-spacer />
                        <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/profile/edit">Edit</v-btn>
                    </div>
                    <div class="tt-body">
                        <div class="d-flex flex-wrap ga-2">
                            <span v-for="skill in profile.skills" :key="skill" class="tt-chip">{{ skill }}</span>
                            <span v-if="!profile.skills.length" class="tt-empty">Add skills from Edit profile.</span>
                        </div>
                        <div class="tt-role-row">
                            <span class="tt-icon-tile tt-icon-tile--green"><v-icon size="18" color="#0f9d6b">mdi-shield-check-outline</v-icon></span>
                            <span class="flex-grow-1"><strong class="tt-strong">{{ profile.role || "Store team member" }}</strong><span class="tt-sub d-block">Assigned admin role</span></span>
                            <v-chip size="small" color="success" variant="tonal" label>Active</v-chip>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-amber h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">About {{ firstName }}</span><v-spacer />
                        <v-btn icon size="small" variant="text" to="/admin/profile/edit" aria-label="Edit about"><v-icon size="16">mdi-pencil-outline</v-icon></v-btn>
                    </div>
                    <div class="tt-body">
                        <p class="tt-about">{{ profile.description || "Add a short introduction about your role, areas of expertise, and how you support the Tanjil Traders team." }}</p>
                        <div class="tt-about-meta">
                            <div><span class="tt-sub">Designation</span><strong class="tt-strong d-block">{{ profile.designation || profile.role || "Store Administrator" }}</strong></div>
                            <div><span class="tt-sub">Website</span><strong class="tt-strong d-block">{{ profile.website || "Add your store website" }}</strong></div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Activity & tools ═════════ -->
        <div id="activity" class="tt-section"><v-icon size="16">mdi-history</v-icon><span>Activity &amp; store tools</span></div>
        <v-row dense>
            <v-col cols="12" lg="6">
                <v-card flat class="tt-card tt-green h-100">
                    <div class="tt-head">
                        <span class="tt-dot"></span><span class="tt-card-title">Recent activity</span><v-spacer />
                        <v-btn-toggle v-model="activePeriod" mandatory density="compact" variant="outlined" divided>
                            <v-btn v-for="period in periods" :key="period" :value="period" size="x-small" class="text-none">{{ period }}</v-btn>
                        </v-btn-toggle>
                    </div>
                    <div class="tt-body">
                        <template v-if="profile.activity && profile.activity.length">
                            <div v-for="(item, index) in profile.activity" :key="index" class="tt-activity-item">
                                <span class="tt-icon-tile tt-icon-tile--green"><i :class="item.icon || 'mdi mdi-check'"></i></span>
                                <div><strong class="tt-strong">{{ item.title }}</strong><div class="tt-sub">{{ item.description }}</div><small class="tt-muted">{{ item.time }}</small></div>
                            </div>
                        </template>
                        <div v-else class="tt-empty-state">
                            <span class="tt-icon-tile tt-icon-tile--green"><v-icon size="18" color="#0f9d6b">mdi-text-box-check-outline</v-icon></span>
                            <strong class="tt-strong mt-2">No profile activity yet</strong>
                            <div class="tt-sub">Store actions will appear here when activity logging is connected.</div>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col id="documents" cols="12" lg="6">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Store tools</span></div>
                    <div class="tt-body">
                        <router-link v-for="tool in storeTools" :key="tool.title" :to="tool.to" class="tt-link-row">
                            <span class="tt-icon-tile tt-icon-tile--blue"><i :class="tool.icon"></i></span>
                            <span class="tt-link-copy"><strong>{{ tool.title }}</strong><small>{{ tool.note }}</small></span>
                            <v-icon size="18" color="#9db3ae">mdi-chevron-right</v-icon>
                        </router-link>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Store operations ═════════ -->
        <div class="tt-section"><v-icon size="16">mdi-store-cog-outline</v-icon><span>Website &amp; store settings</span></div>
        <v-card flat class="tt-card tt-green">
            <div class="tt-head">
                <span class="tt-dot"></span><span class="tt-card-title">{{ storeSettings.store.name || "Store operations" }}</span><v-spacer />
                <v-btn variant="text" size="small" class="text-none" color="#0b5a4a" to="/admin/profile/edit" append-icon="mdi-arrow-right">Manage settings</v-btn>
            </div>
            <div class="tt-body">
                <div class="tt-sub mb-3">{{ storeSettings.store.tagline || "Store identity, customer policies and fulfillment preferences." }}</div>
                <div class="tt-ops-grid">
                    <div v-for="op in operations" :key="op.label" class="tt-ops-item">
                        <span class="tt-icon-tile tt-icon-tile--green"><v-icon size="17" color="#0f766e">{{ op.icon }}</v-icon></span>
                        <span><small class="tt-sub d-block">{{ op.label }}</small><strong class="tt-strong">{{ op.value }}</strong></span>
                    </div>
                </div>
                <div class="tt-note mt-3"><v-icon size="14">mdi-information-outline</v-icon>Browser-saved settings preview; production behavior requires a connected admin settings API.</div>
            </div>
        </v-card>

        <!-- ═════════ Projects & next steps ═════════ -->
        <div id="projects" class="tt-section"><v-icon size="16">mdi-clipboard-check-outline</v-icon><span>Store projects &amp; next steps</span></div>
        <v-row dense>
            <v-col cols="12" lg="8">
                <v-card flat class="tt-card tt-blue h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Store projects</span><v-spacer /><span class="tt-tag">Example board</span></div>
                    <div class="tt-body">
                        <div class="tt-project-grid">
                            <article v-for="project in projects" :key="project.name" class="tt-project" :style="{ '--project-accent': project.color }">
                                <div class="d-flex align-center justify-space-between">
                                    <v-icon size="18" :color="project.color">{{ project.icon }}</v-icon>
                                    <span class="tt-project-status" :class="`status-${project.statusKey}`">{{ project.status }}</span>
                                </div>
                                <h3 class="tt-project-name">{{ project.name }}</h3>
                                <p class="tt-sub">{{ project.note }}</p>
                                <div class="tt-bar tt-bar--sm"><div class="tt-bar-fill" :style="{ width: project.progress + '%', background: project.color }"></div></div>
                                <div class="tt-sub mt-1">{{ project.progress }}% complete · Store team</div>
                            </article>
                        </div>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <v-card flat class="tt-card tt-purple h-100">
                    <div class="tt-head"><span class="tt-dot"></span><span class="tt-card-title">Suggested next steps</span></div>
                    <div class="tt-body">
                        <router-link v-for="task in tasks" :key="task.title" :to="task.to" class="tt-link-row">
                            <span class="tt-icon-tile tt-icon-tile--purple"><v-icon size="16" color="#7c3aed">mdi-arrow-top-right</v-icon></span>
                            <span class="tt-link-copy"><strong>{{ task.title }}</strong><small>{{ task.note }}</small></span>
                            <v-icon size="18" color="#9db3ae">mdi-chevron-right</v-icon>
                        </router-link>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <p class="tt-footnote">Profile information is shared with authorized store administrators only.</p>
    </div>
</template>

<script>
const PROFILE_KEY = "tanjil_admin_profile";

function readJson(key, fallback = {}) {
    try {
        return JSON.parse(window.localStorage.getItem(key) || "null") || fallback;
    } catch (_) {
        return fallback;
    }
}

function readGeneralSettings() {
    const defaults = {
        store: { name: "Tanjil Traders", tagline: "Authentic Gadgets, Official Warranty" },
        hardware: { imeiTracking: true },
        warranty: { brandWarrantyMonths: 12, replacementWindowDays: 7 },
        checkout: { maxCodLimit: 25000, codSmsOtp: true },
        shipping: { freeShippingOver: 5000, integratedCouriers: ["Pathao Courier", "Steadfast Courier", "RedX Logistics"] },
        inventory: { globalLowStockThreshold: 5, blockNegativeStock: true },
        security: { maintenanceMode: false },
    };
    try {
        const saved = JSON.parse(window.localStorage.getItem("tt_full_general_settings") || "null") || {};
        return Object.fromEntries(Object.keys(defaults).map((section) => [section, { ...defaults[section], ...(saved[section] || {}) }]));
    } catch (_) {
        return defaults;
    }
}

export default {
    name: "ProfileOverview",

    data() {
        const authUser = readJson("user", {});
        const saved = readJson(PROFILE_KEY, {});
        const name = saved.fullName || authUser.name || "";
        return {
            storeSettings: readGeneralSettings(),
            activePeriod: "Today",
            periods: ["Today", "Weekly", "Monthly"],
            profile: {
                ...saved,
                fullName: name,
                email: saved.email || authUser.email || "",
                role: saved.role || authUser.role || "Store Administrator",
                skills: saved.skills || ["Catalog management", "Inventory operations", "Customer support"],
                portfolio: saved.portfolio || [
                    { label: "Store website", url: saved.website || "", icon: "mdi mdi-web" },
                    { label: "Admin workspace", url: "/admin", icon: "mdi mdi-view-dashboard-outline" },
                ],
                activity: saved.activity || [],
            },
            storeTools: [
                { title: "Product catalog", note: "Review listings and product details", to: "/admin/catalog", icon: "mdi mdi-package-variant-closed" },
                { title: "Orders", note: "Manage customer purchases", to: "/admin/orders", icon: "mdi mdi-cart-outline" },
                { title: "Inventory report", note: "Check stock levels and availability", to: "/admin/inventory-report", icon: "mdi mdi-clipboard-list-outline" },
                { title: "SEO analytics", note: "Track rankings, traffic and technical health", to: "/admin/seo", icon: "mdi mdi-magnify-expand" },
            ],
            tasks: [
                { title: "Review low-stock products", note: "Keep best-selling gadgets available", to: "/admin/inventory-report" },
                { title: "Update store profile details", note: "Add contact and location information", to: "/admin/profile/edit" },
                { title: "Check today’s orders", note: "Follow up on new customer purchases", to: "/admin/orders" },
            ],
            projects: [
                { name: "Catalog quality refresh", note: "Improve product specifications and photos", status: "In progress", statusKey: "progress", progress: 68, color: "#2f5be7", icon: "mdi-image-edit-outline" },
                { name: "Stock accuracy review", note: "Reconcile inventory for popular devices", status: "Planned", statusKey: "planned", progress: 32, color: "#0f9d6b", icon: "mdi-clipboard-check-outline" },
                { name: "Warranty information update", note: "Make support details easier to find", status: "In review", statusKey: "review", progress: 84, color: "#7c3aed", icon: "mdi-shield-check-outline" },
            ],
        };
    },

    computed: {
        displayName() {
            return this.profile.fullName || "Store Administrator";
        },
        firstName() {
            return this.displayName.split(" ")[0];
        },
        initials() {
            return this.displayName.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join("").toUpperCase() || "TT";
        },
        locationLabel() {
            return [this.profile.city, this.profile.country].filter(Boolean).join(", ") || "Add your store location";
        },
        visibleLinks() {
            return (this.profile.portfolio || []).filter((link) => link.label || link.url).slice(0, 4);
        },
        completion() {
            const fields = [this.profile.fullName, this.profile.email, this.profile.phone, this.profile.designation, this.profile.city, this.profile.description, this.profile.avatar];
            return Math.round((fields.filter(Boolean).length / fields.length) * 100);
        },
        heroStats() {
            return [
                { icon: "mdi-cart-outline", value: this.profile.ordersManaged || "—", label: "Orders managed", note: "All time" },
                { icon: "mdi-package-variant-closed", value: this.profile.productsManaged || "—", label: "Products managed", note: "In catalog" },
                { icon: "mdi-heart-pulse", value: this.completion + "%", label: "Profile health", note: this.completion >= 80 ? "Looking complete" : "Add more details" },
            ];
        },
        infoRows() {
            return [
                { label: "Full name", value: this.displayName },
                { label: "Phone", value: this.profile.phone || "Add a phone number" },
                { label: "Email", value: this.profile.email || "Add an email address" },
                { label: "Location", value: this.locationLabel },
                { label: "Joined", value: this.profile.joinedAt || "Not provided" },
                { label: "Department", value: this.profile.department || "Store operations" },
            ];
        },
        operations() {
            const s = this.storeSettings;
            return [
                { icon: "mdi-shield-check-outline", label: "Warranty", value: `${s.warranty.brandWarrantyMonths} mo brand · ${s.warranty.replacementWindowDays} day DOA` },
                { icon: "mdi-cash-multiple", label: "Cash on delivery", value: `Up to ৳${Number(s.checkout.maxCodLimit || 0).toLocaleString()} · OTP ${s.checkout.codSmsOtp ? "on" : "off"}` },
                { icon: "mdi-truck-fast-outline", label: "Delivery", value: `Free over ৳${Number(s.shipping.freeShippingOver || 0).toLocaleString()} · ${s.shipping.integratedCouriers.length} couriers` },
                { icon: "mdi-warehouse", label: "Inventory", value: `Alert at ${s.inventory.globalLowStockThreshold} units · zero-stock block ${s.inventory.blockNegativeStock ? "on" : "off"}` },
                { icon: "mdi-cellphone-link", label: "Device traceability", value: `IMEI / serial ${s.hardware.imeiTracking ? "required" : "not required"}` },
                { icon: "mdi-store-clock-outline", label: "Storefront status", value: s.security.maintenanceMode ? "Maintenance preference enabled" : "Normal operating preference" },
            ];
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
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.75); margin-top: 4px; }
.tt-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; letter-spacing: 0.3px; padding: 3px 10px; border-radius: 20px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
.tt-pill-live { color: #6ee7b7; }
.tt-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }
.tt-add { color: #0a5548 !important; font-weight: 600; }
.tt-avatar { width: 72px; height: 72px; border-radius: 50%; display: grid; place-items: center; flex: none; background: #fff; color: #053d35; font-size: 22px; font-weight: 700; border: 3px solid rgba(255, 255, 255, 0.85); overflow: hidden; }
.tt-avatar img { width: 100%; height: 100%; object-fit: cover; }
.tt-hero-strip { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 22px; }
.tt-hero-item { display: flex; align-items: flex-start; gap: 12px; padding: 4px 22px; border-left: 1px solid rgba(255, 255, 255, 0.14); }
.tt-hero-item:first-child { border-left: 0; padding-left: 0; }
.tt-hero-icon { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.2); flex: none; }
.tt-hero-value { font-size: 20px; font-weight: 700; line-height: 1.2; }
.tt-hero-label { font-size: 12px; font-weight: 500; color: rgba(255, 255, 255, 0.85); }
.tt-hero-note { font-size: 11px; color: rgba(255, 255, 255, 0.55); }
.tt-hero-line { position: absolute; left: 0; right: 0; bottom: 0; height: 2px; background: linear-gradient(90deg, #ec4899, rgba(236, 72, 153, 0) 70%); }
@media (max-width: 959px) { .tt-hero-strip { grid-template-columns: 1fr; gap: 14px; } .tt-hero-item { border-left: 0; padding-left: 0; } }

/* ───────── Anchor tabs ───────── */
.tt-tabs { display: flex; gap: 4px; margin-top: 14px; padding: 0 12px; background: #fff; border: 1px solid var(--line); border-radius: 10px; overflow-x: auto; }
.tt-tabs a { display: inline-flex; align-items: center; height: 46px; padding: 0 14px; font-size: 13px; font-weight: 600; color: var(--muted); text-decoration: none; border-bottom: 2px solid transparent; white-space: nowrap; }
.tt-tabs a:hover, .tt-tabs a.is-active { color: #0a5548; border-bottom-color: #0f9d6b; }

/* ───────── Sections & cards ───────── */
.tt-section { display: flex; align-items: center; gap: 8px; margin: 26px 0 12px; font-size: 12px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; color: #3d5450; }
.tt-card { --accent: #2f5be7; --tint: #eaf0ff; background: #fff; border: 1px solid var(--line); border-radius: 10px !important; box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04); overflow: hidden; }
.tt-blue { --accent: #2f5be7; --tint: #e9efff; }
.tt-green { --accent: #0f9d6b; --tint: #e3f6ee; }
.tt-purple { --accent: #7c3aed; --tint: #f0e9ff; }
.tt-teal { --accent: #0f766e; --tint: #ddf3f0; }
.tt-amber { --accent: #d97706; --tint: #fff2d6; }
.tt-head { display: flex; align-items: center; gap: 8px; padding: 11px 16px; background: linear-gradient(90deg, var(--tint), #fff); border-bottom: 1px solid var(--line); border-left: 3px solid var(--accent); }
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14px; font-weight: 600; color: var(--ink); }
.tt-tag { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 6px; color: var(--accent); background: #fff; border: 1px solid var(--accent); }
.tt-body { padding: 14px 18px 16px; }
.tt-sub { font-size: 12.5px; color: var(--muted); }
.tt-muted { color: var(--muted); font-size: 11.5px; }
.tt-strong { font-size: 13px; font-weight: 600; color: var(--ink); }
.tt-empty { font-size: 12.5px; color: var(--muted); }
.tt-note { display: flex; align-items: flex-start; gap: 6px; font-size: 12.5px; line-height: 1.5; color: #0f5a40; background: #f0faf5; border: 1px solid #cfeadd; border-left: 3px solid #0f9d6b; border-radius: 8px; padding: 9px 12px; }
.tt-note .v-icon { margin-top: 2px; color: #0f9d6b; }
.tt-footnote { margin: 18px 4px 0; text-align: center; font-size: 11.5px; color: var(--muted); }

/* ───────── Progress bars ───────── */
.tt-bar { height: 8px; border-radius: 99px; background: #eef2f1; overflow: hidden; }
.tt-bar--sm { height: 5px; }
.tt-bar-fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, #0f9d6b, #0f766e); transition: width 0.25s; }
.tt-bar-value { min-width: 38px; font-size: 13px; font-weight: 700; color: #0a5548; }

/* ───────── Info / links / icon tiles ───────── */
.tt-info-row { display: grid; grid-template-columns: minmax(110px, 34%) 1fr; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); }
.tt-info-row:last-child { border-bottom: 0; padding-bottom: 0; }
.tt-info-value { font-size: 13.5px; overflow-wrap: anywhere; }
.tt-link-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); color: inherit; text-decoration: none; }
.tt-link-row:last-child { border-bottom: 0; padding-bottom: 0; }
.tt-link-row:hover strong { color: #0f9d6b; }
.tt-link-copy { display: flex; min-width: 0; flex: 1; flex-direction: column; gap: 2px; }
.tt-link-copy strong { font-size: 13px; font-weight: 600; }
.tt-link-copy small { font-size: 12px; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tt-icon-tile { display: grid; width: 34px; height: 34px; flex: none; place-items: center; border-radius: 9px; font-size: 17px; }
.tt-icon-tile--green { background: #dff5ec; color: #0f9d6b; }
.tt-icon-tile--blue { background: #e6edff; color: #2f5be7; }
.tt-icon-tile--purple { background: #efe8ff; color: #7c3aed; }

/* ───────── Skills / about ───────── */
.tt-chip { padding: 5px 11px; border-radius: 8px; background: #ddf3f0; border: 1px solid #bfe6e1; color: #0f766e; font-size: 12px; font-weight: 600; }
.tt-role-row { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--line); }
.tt-about { margin: 0; font-size: 13.5px; line-height: 1.75; color: #3d5450; }
.tt-about-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--line); }
.tt-about-meta strong { overflow-wrap: anywhere; }

/* ───────── Activity ───────── */
.tt-activity-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); }
.tt-activity-item:last-child { border-bottom: 0; padding-bottom: 0; }
.tt-empty-state { display: flex; min-height: 130px; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 2px; }

/* ───────── Store operations ───────── */
.tt-ops-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.tt-ops-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 8px; background: #fbfdfc; min-width: 0; }
.tt-ops-item strong { overflow-wrap: anywhere; line-height: 1.35; }
@media (max-width: 959px) { .tt-ops-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 599px) { .tt-ops-grid { grid-template-columns: 1fr; } .tt-about-meta { grid-template-columns: 1fr; } }

/* ───────── Projects ───────── */
.tt-project-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.tt-project { min-width: 0; padding: 14px; border: 1px solid var(--line); border-top: 3px solid var(--project-accent); border-radius: 8px; background: #fff; }
.tt-project-name { margin: 10px 0 4px; font-size: 13.5px; font-weight: 600; line-height: 1.4; }
.tt-project p { min-height: 34px; margin: 0 0 10px; }
.tt-project-status { padding: 3px 8px; border-radius: 6px; font-size: 10.5px; font-weight: 700; white-space: nowrap; }
.status-progress { background: #e6edff; color: #1d4ed8; }
.status-planned { background: #fdf1cf; color: #8a5a00; }
.status-review { background: #efe8ff; color: #6d28d9; }
@media (max-width: 959px) { .tt-project-grid { grid-template-columns: 1fr; } .tt-project p { min-height: 0; } }
</style>