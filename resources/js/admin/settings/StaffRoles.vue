<template>
    <div class="ts-page">
        <!-- ═════════ Hero ═════════ -->
        <header class="ts-hero">
            <div class="d-flex flex-wrap align-center ga-4">
                <div class="ts-hero-logo"><v-icon size="26" color="white">mdi-shield-account-outline</v-icon></div>
                <div>
                    <div class="d-flex align-center ga-3 flex-wrap">
                        <h1 class="ts-h1">Staff &amp; Roles</h1>
                        <v-chip v-if="isDirty" size="x-small" color="warning" variant="flat" class="font-weight-bold">Unsaved changes</v-chip>
                    </div>
                    <div class="ts-hero-sub">Invite your team and control what each role can do</div>
                </div>
                <v-spacer />
                <div class="d-flex ga-2 flex-wrap">
                    <v-btn variant="outlined" class="text-none ts-btn-ghost" prepend-icon="mdi-restore" :disabled="!isDirty || saving" @click="discard">Discard</v-btn>
                    <v-btn variant="flat" class="text-none ts-btn-solid" prepend-icon="mdi-content-save-check" :loading="saving" :disabled="!isDirty" @click="saveAll">Save changes</v-btn>
                </div>
            </div>
        </header>

        <!-- ═════════ Stats ═════════ -->
        <div class="ts-stats">
            <div v-for="s in stats" :key="s.label" class="ts-stat">
                <span class="ts-stat-icon" :style="{ background: s.color }"><v-icon size="20" color="white">{{ s.icon }}</v-icon></span>
                <div>
                    <div class="ts-stat-value">{{ s.value }}</div>
                    <div class="ts-stat-label">{{ s.label }}</div>
                </div>
            </div>
        </div>

        <!-- ═════════ Tabs ═════════ -->
        <div class="ts-tabs" role="tablist">
            <button type="button" role="tab" class="ts-tab" :class="{ 'is-on': tab === 'staff' }" :aria-selected="tab === 'staff'" @click="tab = 'staff'">
                <v-icon size="18">mdi-account-group-outline</v-icon>Staff<span class="ts-tab-count">{{ staff.length }}</span>
            </button>
            <button type="button" role="tab" class="ts-tab" :class="{ 'is-on': tab === 'roles' }" :aria-selected="tab === 'roles'" @click="tab = 'roles'">
                <v-icon size="18">mdi-key-outline</v-icon>Roles &amp; permissions<span class="ts-tab-count">{{ roles.length }}</span>
            </button>
        </div>

        <!-- ═════════ TAB: Staff ═════════ -->
        <v-card v-if="tab === 'staff'" flat class="ts-card">
            <div class="ts-toolbar">
                <v-text-field v-model="q" class="ts-search" placeholder="Search name or email" prepend-inner-icon="mdi-magnify" variant="outlined" density="compact" hide-details clearable />
                <v-select v-model="roleFilter" :items="roleFilterItems" variant="outlined" density="compact" hide-details style="max-width: 190px" aria-label="Filter by role" />
                <button v-for="s in statusFilters" :key="s.value" type="button" class="ts-chip" :class="{ 'is-on': statusFilter === s.value }" @click="statusFilter = s.value">{{ s.label }}</button>
                <v-spacer />
                <v-btn color="#0a5548" variant="flat" class="text-none" prepend-icon="mdi-account-plus-outline" @click="openStaff()">Invite staff</v-btn>
            </div>

            <div class="ts-table-wrap">
                <div class="ts-table">
                    <div class="ts-tr ts-th"><span>Member</span><span>Role</span><span>2FA</span><span>Status</span><span>Last active</span><span></span></div>

                    <div v-for="u in filteredStaff" :key="u.id" class="ts-tr">
                        <div class="ts-person">
                            <span class="ts-avatar" :style="{ background: avatarColor(u.name) }">{{ initials(u.name) }}</span>
                            <span class="ts-person-text"><span class="ts-person-name">{{ u.name }}</span><span class="ts-muted ts-small">{{ u.email }}</span></span>
                        </div>
                        <div><span class="ts-role-chip" :style="{ '--c': roleOf(u.role).color }">{{ roleOf(u.role).name }}</span></div>
                        <div>
                            <v-icon size="20" :color="u.twoFactor ? '#0f9d6b' : '#9db3ad'">{{ u.twoFactor ? "mdi-shield-check" : "mdi-shield-off-outline" }}</v-icon>
                            <v-tooltip activator="parent" location="top">{{ u.twoFactor ? "Two-factor on" : "Two-factor off" }}</v-tooltip>
                        </div>
                        <div><span class="ts-status" :class="u.status">{{ statusText[u.status] }}</span></div>
                        <div class="ts-muted ts-small">{{ u.lastActive }}</div>
                        <div>
                            <v-menu location="bottom end">
                                <template #activator="{ props }">
                                    <v-btn v-bind="props" icon="mdi-dots-vertical" variant="text" size="small" :aria-label="'Actions for ' + u.name" />
                                </template>
                                <v-card class="ts-menu" flat>
                                    <button type="button" class="ts-menu-item" @click="openStaff(u)"><v-icon size="18">mdi-pencil-outline</v-icon>Edit</button>
                                    <button v-if="u.status === 'invited'" type="button" class="ts-menu-item" @click="notify('Invite sent again to ' + u.email, 'info')"><v-icon size="18">mdi-email-fast-outline</v-icon>Resend invite</button>
                                    <button v-else type="button" class="ts-menu-item" @click="notify('Password reset email sent to ' + u.email, 'info')"><v-icon size="18">mdi-lock-reset</v-icon>Reset password</button>
                                    <button v-if="u.status !== 'invited'" type="button" class="ts-menu-item" @click="toggleSuspend(u)"><v-icon size="18">{{ u.status === "suspended" ? "mdi-account-check-outline" : "mdi-account-lock-outline" }}</v-icon>{{ u.status === "suspended" ? "Reactivate" : "Suspend" }}</button>
                                    <button type="button" class="ts-menu-item danger" @click="askRemove(u)"><v-icon size="18">mdi-delete-outline</v-icon>Remove</button>
                                </v-card>
                            </v-menu>
                        </div>
                    </div>

                    <div v-if="!filteredStaff.length" class="ts-empty">
                        <v-icon size="30" color="#9db3ad">mdi-account-search-outline</v-icon>
                        <div>No staff match these filters.</div>
                    </div>
                </div>
            </div>
        </v-card>

        <!-- ═════════ TAB: Roles ═════════ -->
        <v-row v-else dense>
            <v-col cols="12" md="4" lg="3">
                <v-card flat class="ts-card">
                    <div class="ts-card-head">
                        <div class="ts-card-title">Roles</div>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" prepend-icon="mdi-plus" @click="openRole()">New role</v-btn>
                    </div>
                    <div class="pa-2">
                        <button v-for="r in roles" :key="r.key" type="button" class="ts-role-item" :class="{ 'is-on': r.key === activeRoleKey }" :style="{ '--c': r.color }" @click="activeRoleKey = r.key">
                            <span class="ts-role-dot"><v-icon size="18" color="white">{{ r.locked ? "mdi-shield-crown-outline" : "mdi-account-key-outline" }}</v-icon></span>
                            <span class="ts-role-name">{{ r.name }}</span>
                            <span class="ts-role-count">{{ staffCount(r.key) }} {{ staffCount(r.key) === 1 ? "member" : "members" }}</span>
                        </button>
                    </div>
                </v-card>
            </v-col>

            <v-col cols="12" md="8" lg="9">
                <v-card v-if="activeRole" flat class="ts-card">
                    <div class="ts-card-head">
                        <span class="ts-dialog-icon" :style="{ background: activeRole.color }"><v-icon size="20" color="white">mdi-key-outline</v-icon></span>
                        <div>
                            <div class="ts-card-title">{{ activeRole.name }}</div>
                            <div class="ts-card-sub">{{ activeRole.desc || "No description" }} · {{ grantedCount(activeRole) }} of {{ totalPerms }} permissions</div>
                        </div>
                        <v-spacer />
                        <v-btn variant="text" size="small" class="text-none" prepend-icon="mdi-content-copy" @click="openRole(null, activeRole)">Duplicate</v-btn>
                        <v-btn variant="text" size="small" class="text-none" prepend-icon="mdi-pencil-outline" :disabled="activeRole.locked" @click="openRole(activeRole)">Edit</v-btn>
                        <v-btn variant="text" size="small" color="error" class="text-none" prepend-icon="mdi-delete-outline" :disabled="activeRole.locked" @click="removeRole(activeRole)">Delete</v-btn>
                    </div>

                    <div v-if="activeRole.locked" class="ts-body pb-0">
                        <div class="ts-note"><v-icon size="16" color="#8a5a00">mdi-lock-outline</v-icon>Super Admin always has full access and cannot be changed.</div>
                    </div>

                    <div class="ts-matrix-wrap mt-3">
                        <div class="ts-matrix">
                            <div class="ts-mrow ts-mhead" style="padding-bottom: 0">
                                <span>Module</span>
                                <span v-for="a in actions" :key="a.key" class="ts-mcell">{{ a.label }}</span>
                                <span class="ts-mcell">All</span>
                            </div>
                            <div class="ts-mrow ts-mhead" style="padding-top: 0">
                                <span class="ts-small" style="text-transform: none; letter-spacing: 0">Select a whole column</span>
                                <span v-for="a in actions" :key="a.key" class="ts-mcell">
                                    <v-checkbox-btn :model-value="colAll(activeRole, a.key)" :disabled="activeRole.locked" density="compact" color="success" :aria-label="'All ' + a.label" @update:model-value="setCol(activeRole, a.key, $event)" />
                                </span>
                                <span class="ts-mcell"></span>
                            </div>

                            <div v-for="m in modules" :key="m.key" class="ts-mrow">
                                <span class="ts-mmod"><v-icon size="18">{{ m.icon }}</v-icon>{{ m.label }}</span>
                                <span v-for="a in actions" :key="a.key" class="ts-mcell">
                                    <v-checkbox-btn :model-value="has(activeRole, m.key, a.key)" :disabled="activeRole.locked" density="compact" color="success" :aria-label="a.label + ' ' + m.label" @update:model-value="setPerm(activeRole, m.key, a.key, $event)" />
                                </span>
                                <span class="ts-mcell">
                                    <v-checkbox-btn :model-value="rowAll(activeRole, m.key)" :disabled="activeRole.locked" density="compact" color="success" :aria-label="'All for ' + m.label" @update:model-value="setRow(activeRole, m.key, $event)" />
                                </span>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- ═════════ Staff dialog (invite / edit) ═════════ -->
        <v-dialog v-model="staffDialog" max-width="520" scrollable>
            <v-card class="ts-dialog">
                <div class="ts-dialog-head">
                    <span class="ts-dialog-icon" style="background: #167a5e"><v-icon size="20" color="white">{{ editingId ? "mdi-account-edit-outline" : "mdi-account-plus-outline" }}</v-icon></span>
                    <div>
                        <div class="ts-card-title">{{ editingId ? "Edit staff member" : "Invite staff" }}</div>
                        <div class="ts-card-sub">{{ editingId ? "Update details, role or status" : "They get an email to set their password" }}</div>
                    </div>
                    <v-spacer />
                    <v-btn icon="mdi-close" variant="text" size="small" aria-label="Close" @click="staffDialog = false" />
                </div>
                <v-card-text class="pt-4">
                    <v-form ref="staffForm" @submit.prevent="saveStaff">
                        <v-text-field v-model="sDraft.name" label="Full name *" variant="outlined" density="comfortable" class="mb-1" :rules="[(v) => !!String(v || '').trim() || 'Name is required']" />
                        <v-text-field v-model="sDraft.email" label="Email *" type="email" variant="outlined" density="comfortable" class="mb-1" :rules="emailRules" />
                        <v-text-field v-model="sDraft.phone" label="Phone" variant="outlined" density="comfortable" class="mb-1" />
                        <v-select v-model="sDraft.role" :items="roles.map((r) => ({ title: r.name, value: r.key }))" label="Role *" variant="outlined" density="comfortable" class="mb-1" :rules="[(v) => !!v || 'Choose a role']" />
                        <v-select v-if="editingId && sDraft.status !== 'invited'" v-model="sDraft.status" :items="[{ title: 'Active', value: 'active' }, { title: 'Suspended', value: 'suspended' }]" label="Status" variant="outlined" density="comfortable" class="mb-1" />
                        <div class="ts-switch-box">
                            <v-switch v-model="sDraft.twoFactor" color="success" density="compact" hide-details label="Require two-factor sign-in" />
                        </div>
                    </v-form>
                </v-card-text>
                <v-card-actions class="ts-dialog-foot">
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="staffDialog = false">Cancel</v-btn>
                    <v-btn color="#0a5548" variant="flat" class="text-none" @click="saveStaff">{{ editingId ? "Apply" : "Send invite" }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- ═════════ Role dialog (new / edit / duplicate) ═════════ -->
        <v-dialog v-model="roleDialog" max-width="480">
            <v-card class="ts-dialog">
                <div class="ts-dialog-head">
                    <span class="ts-dialog-icon" :style="{ background: rDraft.color }"><v-icon size="20" color="white">mdi-key-outline</v-icon></span>
                    <div>
                        <div class="ts-card-title">{{ roleEditingKey ? "Edit role" : "New role" }}</div>
                        <div class="ts-card-sub">{{ roleEditingKey ? "Rename or recolour this role" : rDraft.copyFrom ? "Starts with the permissions you copied" : "Starts with no permissions" }}</div>
                    </div>
                    <v-spacer />
                    <v-btn icon="mdi-close" variant="text" size="small" aria-label="Close" @click="roleDialog = false" />
                </div>
                <v-card-text class="pt-4">
                    <v-form ref="roleForm" @submit.prevent="saveRole">
                        <v-text-field v-model="rDraft.name" label="Role name *" variant="outlined" density="comfortable" class="mb-1" :rules="roleNameRules" />
                        <v-textarea v-model="rDraft.desc" label="Description" rows="2" auto-grow variant="outlined" density="comfortable" class="mb-1" />
                        <v-select v-if="!roleEditingKey" v-model="rDraft.copyFrom" :items="[{ title: 'Start empty', value: '' }, ...roles.map((r) => ({ title: 'Copy from ' + r.name, value: r.key }))]" label="Starting permissions" variant="outlined" density="comfortable" class="mb-2" />
                        <div class="ts-label">Colour</div>
                        <div class="ts-swatches">
                            <button v-for="c in palette" :key="c" type="button" class="ts-swatch" :class="{ 'is-on': rDraft.color === c }" :style="{ background: c }" :aria-label="'Colour ' + c" @click="rDraft.color = c"></button>
                        </div>
                    </v-form>
                </v-card-text>
                <v-card-actions class="ts-dialog-foot">
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="roleDialog = false">Cancel</v-btn>
                    <v-btn color="#0a5548" variant="flat" class="text-none" @click="saveRole">{{ roleEditingKey ? "Apply" : "Create role" }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- ═════════ Remove confirmation ═════════ -->
        <v-dialog v-model="confirm.show" max-width="420">
            <v-card class="ts-dialog">
                <v-card-title class="text-h6 pt-5">Remove {{ confirm.name }}?</v-card-title>
                <v-card-text>They lose access to the admin panel as soon as you save. This does not delete their past activity.</v-card-text>
                <v-card-actions class="ts-dialog-foot">
                    <v-spacer />
                    <v-btn variant="text" class="text-none" @click="confirm.show = false">Keep</v-btn>
                    <v-btn color="error" variant="flat" class="text-none" @click="doRemove">Remove</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="toast.show" :color="toast.color" timeout="2800" location="bottom right">{{ toast.text }}</v-snackbar>
    </div>
</template>

<script>
import "./settings.scss";

const STORAGE_KEY = "tt_staff_roles";

const MODULES = [
    { key: "orders", label: "Orders", icon: "mdi-receipt-text-outline" },
    { key: "products", label: "Products & catalog", icon: "mdi-package-variant-closed" },
    { key: "inventory", label: "Inventory", icon: "mdi-warehouse" },
    { key: "customers", label: "Customers", icon: "mdi-account-group-outline" },
    { key: "coupons", label: "Coupons & banners", icon: "mdi-ticket-percent-outline" },
    { key: "reports", label: "Reports & analytics", icon: "mdi-chart-line" },
    { key: "payments", label: "Payments", icon: "mdi-credit-card-outline" },
    { key: "staff", label: "Staff & roles", icon: "mdi-shield-account-outline" },
    { key: "settings", label: "Store settings", icon: "mdi-cog-outline" },
];
const ACTIONS = [
    { key: "view", label: "View" },
    { key: "create", label: "Create" },
    { key: "edit", label: "Edit" },
    { key: "delete", label: "Delete" },
    { key: "export", label: "Export" },
];
const ALL = ACTIONS.map((a) => a.key);
const RW = ["view", "create", "edit"];
const RO = ["view"];
const PALETTE = ["#167a5e", "#2f5be7", "#7c3aed", "#d97706", "#e11d48", "#0f766e", "#2d5f8f", "#7a808e"];
const AVATARS = ["#167a5e", "#2f5be7", "#7c3aed", "#c58a1e", "#c0392b", "#0f766e", "#2d5f8f"];

// Role permissions: module -> list of allowed actions
const defaultRoles = () => [
    { key: "admin", name: "Super Admin", desc: "Full access to everything", color: "#0a5548", locked: true, perms: Object.fromEntries(MODULES.map((m) => [m.key, [...ALL]])) },
    { key: "manager", name: "Store Manager", desc: "Runs daily operations, no billing or role changes", color: "#2f5be7",
      perms: { orders: [...ALL], products: [...ALL], inventory: [...ALL], customers: [...RW, "export"], coupons: [...ALL], reports: ["view", "export"], payments: [...RO], staff: [...RO], settings: [...RO] } },
    { key: "sales", name: "Sales Executive", desc: "Creates and edits orders and customers", color: "#d97706",
      perms: { orders: [...RW], products: [...RO], inventory: [...RO], customers: [...RW], coupons: [...RO], reports: [...RO] } },
    { key: "warehouse", name: "Warehouse", desc: "Packs, scans IMEI and manages stock", color: "#0f766e",
      perms: { orders: ["view", "edit"], products: [...RO], inventory: [...RW, "export"] } },
    { key: "support", name: "Customer Support", desc: "Answers customers and handles returns", color: "#7c3aed",
      perms: { orders: ["view", "edit"], products: [...RO], customers: ["view", "edit"] } },
    { key: "accountant", name: "Accountant", desc: "Views payments and exports financial reports", color: "#e11d48",
      perms: { orders: ["view", "export"], customers: [...RO], reports: ["view", "export"], payments: ["view", "export"] } },
];

// Sample team. Replace with: axios.get("/api/admin/staff")
const defaultStaff = () => [
    { id: "s1", name: "Tanjil Ahmed", email: "tanjil@tanjiltraders.com", phone: "+880 1711 000 001", role: "admin", status: "active", twoFactor: true, lastActive: "2 min ago" },
    { id: "s2", name: "Nusrat Jahan", email: "nusrat@tanjiltraders.com", phone: "+880 1711 000 002", role: "manager", status: "active", twoFactor: true, lastActive: "1 hour ago" },
    { id: "s3", name: "Rahim Uddin", email: "rahim@tanjiltraders.com", phone: "+880 1711 000 003", role: "warehouse", status: "active", twoFactor: false, lastActive: "Today, 9:40 AM" },
    { id: "s4", name: "Sadia Islam", email: "sadia@tanjiltraders.com", phone: "+880 1711 000 004", role: "sales", status: "active", twoFactor: true, lastActive: "Yesterday" },
    { id: "s5", name: "Imran Hossain", email: "imran@tanjiltraders.com", phone: "+880 1711 000 005", role: "support", status: "suspended", twoFactor: false, lastActive: "3 weeks ago" },
    { id: "s6", name: "Farhana Akter", email: "farhana@tanjiltraders.com", phone: "", role: "accountant", status: "invited", twoFactor: true, lastActive: "Never" },
];

export default {
    name: "StaffRoles",

    data() {
        return {
            modules: MODULES,
            actions: ACTIONS,
            palette: PALETTE,
            roles: defaultRoles(),
            staff: defaultStaff(),
            snapshot: "",
            saving: false,
            tab: "staff",
            activeRoleKey: "manager",

            q: "",
            roleFilter: "all",
            statusFilter: "all",
            statusFilters: [
                { value: "all", label: "All" },
                { value: "active", label: "Active" },
                { value: "invited", label: "Invited" },
                { value: "suspended", label: "Suspended" },
            ],
            statusText: { active: "Active", invited: "Invited", suspended: "Suspended" },

            staffDialog: false,
            editingId: null,
            sDraft: {},
            roleDialog: false,
            roleEditingKey: null,
            rDraft: { name: "", desc: "", color: PALETTE[0], copyFrom: "" },
            confirm: { show: false, id: null, name: "" },
            toast: { show: false, text: "", color: "success" },
        };
    },

    computed: {
        isDirty() {
            return this.serialize() !== this.snapshot;
        },
        activeRole() {
            return this.roles.find((r) => r.key === this.activeRoleKey) || this.roles[0] || null;
        },
        totalPerms() {
            return MODULES.length * ACTIONS.length;
        },
        roleFilterItems() {
            return [{ title: "All roles", value: "all" }, ...this.roles.map((r) => ({ title: r.name, value: r.key }))];
        },
        filteredStaff() {
            const q = (this.q || "").trim().toLowerCase();
            return this.staff.filter((u) => (this.roleFilter === "all" || u.role === this.roleFilter) && (this.statusFilter === "all" || u.status === this.statusFilter) && (!q || (u.name + " " + u.email).toLowerCase().includes(q)));
        },
        stats() {
            const c = (s) => this.staff.filter((u) => u.status === s).length;
            return [
                { label: "Team members", value: this.staff.length, icon: "mdi-account-group-outline", color: "#167a5e" },
                { label: "Active now", value: c("active"), icon: "mdi-account-check-outline", color: "#2d5f8f" },
                { label: "Pending invites", value: c("invited"), icon: "mdi-email-fast-outline", color: "#c58a1e" },
                { label: "Without two-factor", value: this.staff.filter((u) => !u.twoFactor && u.status !== "suspended").length, icon: "mdi-shield-alert-outline", color: "#c0392b" },
            ];
        },
        emailRules() {
            return [
                (v) => !!String(v || "").trim() || "Email is required",
                (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || "Enter a valid email",
                (v) => !this.staff.some((u) => u.id !== this.editingId && u.email.toLowerCase() === String(v).trim().toLowerCase()) || "This email is already on the team",
            ];
        },
        roleNameRules() {
            return [
                (v) => !!String(v || "").trim() || "Role name is required",
                (v) => !this.roles.some((r) => r.key !== this.roleEditingKey && r.name.toLowerCase() === String(v).trim().toLowerCase()) || "A role with this name already exists",
            ];
        },
    },

    created() {
        // Load saved values. Swap for: axios.get("/api/admin/staff") and axios.get("/api/admin/roles")
        try {
            const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
            if (raw && Array.isArray(raw.roles) && Array.isArray(raw.staff)) {
                // Super Admin is always rebuilt so it can never lose permissions
                const admin = defaultRoles()[0];
                this.roles = [admin, ...raw.roles.filter((r) => r.key !== "admin")];
                this.staff = raw.staff;
            }
        } catch (e) {
            /* ignore corrupt storage */
        }
        this.snapshot = this.serialize();
    },

    methods: {
        serialize() {
            return JSON.stringify({ roles: this.roles, staff: this.staff });
        },
        roleOf(key) {
            return this.roles.find((r) => r.key === key) || { name: "Unassigned", color: "#7a808e" };
        },
        staffCount(key) {
            return this.staff.filter((u) => u.role === key).length;
        },
        initials(name) {
            return String(name).split(" ").filter(Boolean).map((w) => w[0]).slice(0, 2).join("").toUpperCase();
        },
        avatarColor(name) {
            let h = 0;
            for (const ch of String(name)) h = (h * 31 + ch.charCodeAt(0)) % 997;
            return AVATARS[h % AVATARS.length];
        },
        isLastAdmin(u) {
            return u.role === "admin" && u.status === "active" && this.staff.filter((x) => x.role === "admin" && x.status === "active").length <= 1;
        },

        // ── Permission matrix ──
        has(role, mod, act) {
            return (role.perms[mod] || []).includes(act);
        },
        setPerm(role, mod, act, on) {
            if (role.locked) return;
            const cur = new Set(role.perms[mod] || []);
            on ? cur.add(act) : cur.delete(act);
            role.perms[mod] = ALL.filter((a) => cur.has(a));
        },
        rowAll(role, mod) {
            return ALL.every((a) => this.has(role, mod, a));
        },
        setRow(role, mod, on) {
            if (!role.locked) role.perms[mod] = on ? [...ALL] : [];
        },
        colAll(role, act) {
            return MODULES.every((m) => this.has(role, m.key, act));
        },
        setCol(role, act, on) {
            if (role.locked) return;
            MODULES.forEach((m) => this.setPerm(role, m.key, act, on));
        },
        grantedCount(role) {
            return MODULES.reduce((n, m) => n + (role.perms[m.key] || []).length, 0);
        },

        // ── Staff ──
        openStaff(u) {
            this.editingId = u ? u.id : null;
            this.sDraft = u ? { ...u } : { name: "", email: "", phone: "", role: "sales", status: "invited", twoFactor: true };
            this.staffDialog = true;
            this.$nextTick(() => this.$refs.staffForm && this.$refs.staffForm.resetValidation());
        },
        async saveStaff() {
            const { valid } = await this.$refs.staffForm.validate();
            if (!valid) return;
            const d = { ...this.sDraft, name: this.sDraft.name.trim(), email: this.sDraft.email.trim() };
            if (this.editingId) {
                const cur = this.staff.find((u) => u.id === this.editingId);
                if (this.isLastAdmin(cur) && (d.role !== "admin" || d.status !== "active")) return this.notify("Keep at least one active Super Admin", "error");
                Object.assign(cur, d);
                this.notify(`${d.name} updated. Save changes to publish`, "info");
            } else {
                // Swap for: await axios.post("/api/admin/staff/invite", d);
                this.staff.unshift({ ...d, id: "s" + Date.now(), status: "invited", lastActive: "Never" });
                this.notify(`Invite ready for ${d.email}. Save changes to send it`, "info");
            }
            this.staffDialog = false;
        },
        toggleSuspend(u) {
            if (u.status !== "suspended" && this.isLastAdmin(u)) return this.notify("Keep at least one active Super Admin", "error");
            u.status = u.status === "suspended" ? "active" : "suspended";
            this.notify(`${u.name} ${u.status === "suspended" ? "suspended" : "reactivated"}. Save changes to publish`, "info");
        },
        askRemove(u) {
            if (this.isLastAdmin(u)) return this.notify("You cannot remove the last Super Admin", "error");
            this.confirm = { show: true, id: u.id, name: u.name };
        },
        doRemove() {
            this.staff = this.staff.filter((u) => u.id !== this.confirm.id);
            this.confirm.show = false;
            this.notify(`${this.confirm.name} removed. Save changes to publish`, "info");
        },

        // ── Roles ──
        openRole(role, copySource) {
            this.roleEditingKey = role ? role.key : null;
            this.rDraft = role
                ? { name: role.name, desc: role.desc, color: role.color, copyFrom: "" }
                : { name: copySource ? copySource.name + " copy" : "", desc: copySource ? copySource.desc : "", color: copySource ? copySource.color : PALETTE[1], copyFrom: copySource ? copySource.key : "" };
            this.roleDialog = true;
            this.$nextTick(() => this.$refs.roleForm && this.$refs.roleForm.resetValidation());
        },
        async saveRole() {
            const { valid } = await this.$refs.roleForm.validate();
            if (!valid) return;
            const d = this.rDraft;
            if (this.roleEditingKey) {
                Object.assign(this.roles.find((r) => r.key === this.roleEditingKey), { name: d.name.trim(), desc: d.desc, color: d.color });
            } else {
                const src = this.roles.find((r) => r.key === d.copyFrom);
                const perms = src ? JSON.parse(JSON.stringify(src.perms)) : {};
                const key = "r" + Date.now();
                this.roles.push({ key, name: d.name.trim(), desc: d.desc, color: d.color, perms });
                this.activeRoleKey = key;
            }
            this.roleDialog = false;
            this.notify("Role updated. Save changes to publish", "info");
        },
        removeRole(role) {
            const n = this.staffCount(role.key);
            if (n) return this.notify(`Move the ${n} member${n === 1 ? "" : "s"} in ${role.name} to another role first`, "error");
            this.roles = this.roles.filter((r) => r.key !== role.key);
            this.activeRoleKey = this.roles[0].key;
            this.notify(`${role.name} deleted. Save changes to publish`, "info");
        },

        // ── Save / discard ──
        async saveAll() {
            this.saving = true;
            try {
                // Swap for: await axios.put("/api/admin/roles", this.roles); await axios.put("/api/admin/staff", this.staff);
                await new Promise((r) => setTimeout(r, 400));
                localStorage.setItem(STORAGE_KEY, this.serialize());
                this.snapshot = this.serialize();
                this.notify("Staff and roles saved");
            } catch (e) {
                this.notify("Could not save. Please try again.", "error");
            } finally {
                this.saving = false;
            }
        },
        discard() {
            const o = JSON.parse(this.snapshot);
            this.roles = o.roles;
            this.staff = o.staff;
            if (!this.roles.some((r) => r.key === this.activeRoleKey)) this.activeRoleKey = this.roles[0].key;
            this.notify("Changes discarded", "info");
        },
        notify(text, color = "success") {
            this.toast = { show: true, text, color };
        },
    },
};
</script>
