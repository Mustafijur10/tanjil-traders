<template>
    <div class="tt-gen">
        <!-- ═════════ Hero Header ═════════ -->
        <header class="tt-hero">
            <div class="d-flex flex-wrap align-center ga-2 mb-2">
                <span class="tt-pill tt-pill-live">
                    <span class="tt-pill-dot"></span> System Operational · Asia/Dhaka (UTC+6)
                </span>
                <span class="tt-pill tt-pill-tag">
                    <v-icon size="12">mdi-shield-check</v-icon> BIN / VAT Registered
                </span>
                <span v-if="form.security.maintenanceMode" class="tt-pill tt-pill-warn">
                    <v-icon size="12">mdi-power</v-icon> Maintenance Mode Active
                </span>
                <v-chip v-if="isDirty" size="x-small" color="warning" variant="flat" class="font-weight-bold ml-1">
                    Unsaved changes
                </v-chip>
            </div>

            <div class="d-flex flex-wrap align-start justify-space-between ga-4">
                <div>
                    <div class="d-flex align-center ga-3">
                        <div class="tt-hero-logo">
                            <v-icon size="28" color="white">mdi-tune-vertical-variant</v-icon>
                        </div>
                        <div>
                            <h1 class="tt-h1">General Store Settings</h1>
                            <div class="tt-hero-sub">
                                Business, Warranty, IMEI & Checkout Rules
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-center ga-2 flex-wrap">
                    <v-btn
                        variant="outlined"
                        class="text-none tt-ghost-btn"
                        prepend-icon="mdi-restore"
                        :disabled="!isDirty || saving"
                        @click="resetForm"
                    >
                        Discard
                    </v-btn>
                    <v-btn
                        color="white"
                        variant="flat"
                        class="text-none tt-add font-weight-bold"
                        prepend-icon="mdi-content-save-check"
                        :loading="saving"
                        @click="saveAll"
                    >
                        Save all changes
                    </v-btn>
                </div>
            </div>

            <!-- Hero Quick Stats Strip -->
            <div class="tt-hero-strip">
                <div class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon size="18" color="white">mdi-cellphone-link</v-icon></div>
                    <div>
                        <div class="tt-hero-value">{{ form.hardware.imeiTracking ? "Enforced" : "Disabled" }}</div>
                        <div class="tt-hero-label">IMEI / Serial Traceability</div>
                        <div class="tt-hero-note">Barcode scan required on dispatch</div>
                    </div>
                </div>
                <div class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon size="18" color="white">mdi-shield-star</v-icon></div>
                    <div>
                        <div class="tt-hero-value">{{ form.warranty.brandWarrantyMonths }} Mo. / {{ form.warranty.replacementWindowDays }} Days</div>
                        <div class="tt-hero-label">Warranty &amp; Replacement</div>
                        <div class="tt-hero-note">Unboxing video clause enabled</div>
                    </div>
                </div>
                <div class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon size="18" color="white">mdi-cash-lock</v-icon></div>
                    <div>
                        <div class="tt-hero-value">৳ {{ Number(form.checkout.maxCodLimit).toLocaleString("en-IN") }}</div>
                        <div class="tt-hero-label">Max Cash on Delivery</div>
                        <div class="tt-hero-note">{{ form.checkout.codSmsOtp ? "SMS OTP verification on" : "Direct COD" }}</div>
                    </div>
                </div>
                <div class="tt-hero-item">
                    <div class="tt-hero-icon"><v-icon size="18" color="white">mdi-truck-fast-outline</v-icon></div>
                    <div>
                        <div class="tt-hero-value">{{ form.shipping.integratedCouriers.length }} Couriers</div>
                        <div class="tt-hero-label">Logistics Integrations</div>
                        <div class="tt-hero-note">Free shipping over ৳{{ Number(form.shipping.freeShippingOver).toLocaleString("en-IN") }}</div>
                    </div>
                </div>
            </div>
            <div class="tt-hero-line"></div>
        </header>

        <!-- ═════════ Visible Command Grid Tabs (NO SCROLLBAR) ═════════ -->
        <div class="tt-tabs-grid">
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                class="tt-tab-card"
                :class="{ active: currentTab === t.key }"
                @click="currentTab = t.key"
            >
                <div class="d-flex align-center justify-space-between mb-1">
                    <div class="tt-tab-icon">
                        <v-icon size="19">{{ t.icon }}</v-icon>
                    </div>
                    <span v-if="t.badge" class="tt-tab-badge" :class="t.badgeClass || ''">{{ t.badge }}</span>
                </div>
                <div class="tt-tab-title">{{ t.label }}</div>
                <div class="tt-tab-sub">{{ t.desc }}</div>
            </button>
        </div>

        <v-form ref="formRef" @submit.prevent="saveAll">
            <!-- ═════════ TAB 1: STORE & BRANDING ═════════ -->
            <div v-show="currentTab === 'store'">
                <v-row dense>
                    <v-col cols="12" lg="8">
                        <v-card flat class="tt-card tt-green mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Business Profile &amp; Regulatory Licensing</span>
                                <v-spacer />
                                <span class="tt-tag">Public Facing</span>
                            </div>
                            <div class="tt-body">
                                <v-row dense>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.store.name"
                                            label="Public Store Name *"
                                            placeholder="Tanjil Traders"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-storefront"
                                            :rules="[v => !!v || 'Store name is required']"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.store.legalEntity"
                                            label="Registered Business Entity *"
                                            placeholder="Tanjil Traders International Ltd."
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-domain"
                                            :rules="[v => !!v || 'Entity name is required']"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.store.tagline"
                                            label="Store Slogan / Tagline"
                                            placeholder="Authentic Gadgets & Official Warranty"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-bullhorn-outline"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.store.binNumber"
                                            label="Trade BIN / VAT Registration No."
                                            placeholder="002349182-0101"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-file-certificate-outline"
                                            hint="Printed on official customer invoice slips"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.store.supportEmail"
                                            label="Official Support Email *"
                                            type="email"
                                            placeholder="support@tanjiltraders.com"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-email-outline"
                                            :rules="[v => !!v || 'Email is required']"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.store.hotline"
                                            label="Customer Hotline *"
                                            placeholder="+880 1711 000 000"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-phone-in-talk"
                                            :rules="[v => !!v || 'Phone is required']"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.store.whatsapp"
                                            label="WhatsApp Business Desk"
                                            placeholder="+880 1811 000 000"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-whatsapp"
                                        />
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="form.store.hqAddress"
                                            label="Central Showroom & Corporate Hub Address"
                                            rows="2"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-map-marker-outline"
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="4">
                        <v-card flat class="tt-card tt-blue h-100">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Brand Logos &amp; Badges</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-label mb-2">Main Storefront Logo</div>
                                <div class="tt-uploader-box mb-3">
                                    <div class="d-flex align-center ga-3">
                                        <div class="tt-logo-preview">
                                            <v-icon size="30" color="#0a5548">mdi-shield-crown</v-icon>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="font-weight-medium text-body-2">store_logo.svg</div>
                                            <div class="tt-muted tt-small">240×60px SVG or PNG</div>
                                            <v-btn size="x-small" variant="text" color="primary" class="pa-0 text-none">Change Logo</v-btn>
                                        </div>
                                    </div>
                                </div>

                                <div class="tt-label mb-2">Favicon Browser Icon</div>
                                <div class="tt-uploader-box mb-4">
                                    <div class="d-flex align-center ga-3">
                                        <div class="tt-fav-preview">
                                            <v-icon size="18" color="#0a5548">mdi-cellphone-charging</v-icon>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="font-weight-medium text-body-2">favicon.ico</div>
                                            <div class="tt-muted tt-small">32×32px PNG or ICO</div>
                                            <v-btn size="x-small" variant="text" color="primary" class="pa-0 text-none">Change Icon</v-btn>
                                        </div>
                                    </div>
                                </div>

                                <div class="tt-switch-box">
                                    <v-switch
                                        v-model="form.store.genuineWatermark"
                                        color="success"
                                        density="compact"
                                        hide-details
                                        label="Print '100% Genuine' stamp on customer invoices"
                                    />
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- ═════════ TAB 2: HARDWARE, IMEI & BARCODES ═════════ -->
            <div v-show="currentTab === 'hardware'">
                <v-row dense>
                    <v-col cols="12" lg="7">
                        <v-card flat class="tt-card tt-amber mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Serial / IMEI Traceability Engine</span>
                                <v-spacer />
                                <span class="tt-tag warn">Anti-Theft &amp; RMA</span>
                            </div>
                            <div class="tt-body">
                                <p class="tt-para">
                                    Enforce hardware identification during receiving, packaging, and dispatch to prevent fraud and manage accurate warranty claims.
                                </p>

                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.hardware.imeiTracking"
                                        color="warning"
                                        density="compact"
                                        hide-details
                                        label="Enforce IMEI / Serial capture before order dispatch"
                                    />
                                    <div class="tt-muted tt-small pl-9 pb-2">
                                        Warehouse packing slips cannot be marked as "Dispatched" without scanning the unique barcode of phones, tablets, earbuds, and smartwatches.
                                    </div>
                                </div>

                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.hardware.bundleSerials"
                                        color="warning"
                                        density="compact"
                                        hide-details
                                        label="Capture multi-serials for bundled items"
                                    />
                                    <div class="tt-muted tt-small pl-9 pb-2">
                                        E.g. records separate serials for left earbud, right earbud, and charging case.
                                    </div>
                                </div>

                                <div class="tt-switch-box mb-4">
                                    <v-switch
                                        v-model="form.hardware.autoGenBarcodes"
                                        color="warning"
                                        density="compact"
                                        hide-details
                                        label="Auto-generate internal barcodes for loose accessories"
                                    />
                                    <div class="tt-muted tt-small pl-9 pb-2">
                                        Creates TT-EAN13 barcodes automatically for bulk cables, adapters, and screen protectors without factory barcodes.
                                    </div>
                                </div>

                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="form.hardware.barcodeFormat"
                                            :items="['Code 128 (Standard)', 'QR Code (Compact)', 'EAN-13 (Retail Standard)', 'UPC-A']"
                                            label="Barcode Print Standard"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="form.hardware.labelPrinterSize"
                                            :items="['50mm × 25mm (Standard)', '40mm × 30mm (Compact)', '100mm × 50mm (Large Box)']"
                                            label="Thermal Label Size"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="5">
                        <v-card flat class="tt-card tt-teal h-100">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Barcode &amp; Thermal Sticker Preview</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-barcode-preview text-center">
                                    <div class="font-weight-bold text-caption text-uppercase">Tanjil Traders Official</div>
                                    <div class="tt-barcode-bars my-2">||||| | |||| |||| | ||||| || |||</div>
                                    <div class="font-family-monospace font-weight-bold text-caption">SN: TT-88029-A7492</div>
                                    <div class="tt-muted text-caption mt-1">Anker 65W GaN Charger · 12M Warranty</div>
                                </div>
                                <p class="tt-para mt-3 text-center">
                                    This sticker is automatically queued on your network thermal printer whenever a product is scanned at the packaging desk.
                                </p>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- ═════════ TAB 3: WARRANTY & RMA ═════════ -->
            <div v-show="currentTab === 'warranty'">
                <v-row dense>
                    <v-col cols="12" lg="7">
                        <v-card flat class="tt-card tt-teal mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Warranty Coverage &amp; Dead-on-Arrival (DOA) Rules</span>
                            </div>
                            <div class="tt-body">
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.warranty.brandWarrantyMonths"
                                            label="Default Brand Warranty"
                                            type="number"
                                            suffix="Months"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="Official brand warranty duration"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.warranty.shopWarrantyMonths"
                                            label="Default Shop Warranty"
                                            type="number"
                                            suffix="Months"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="For imported / global version units"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6" class="mt-2">
                                        <v-text-field
                                            v-model="form.warranty.replacementWindowDays"
                                            label="Dead-on-Arrival (DOA) Replacement"
                                            type="number"
                                            suffix="Days"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="Instant hassle-free swap window"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6" class="mt-2">
                                        <v-text-field
                                            v-model="form.warranty.serviceEscalationEmail"
                                            label="Warranty Claims Desk Email"
                                            type="email"
                                            variant="outlined"
                                            density="comfortable"
                                            prepend-inner-icon="mdi-wrench-clock"
                                        />
                                    </v-col>
                                    <v-col cols="12" class="mt-2">
                                        <v-textarea
                                            v-model="form.warranty.termsSummary"
                                            label="Warranty Terms Summary"
                                            rows="3"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="Prints on the invoice and warranty card"
                                            persistent-hint
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="5">
                        <v-card flat class="tt-card tt-amber h-100">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Transit Claims &amp; Customer Verification</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.warranty.requireUnboxingVideo"
                                        color="warning"
                                        density="compact"
                                        hide-details
                                        label="Mandatory Unboxing Video Clause"
                                    />
                                    <div class="tt-muted tt-small pl-9 pb-2">
                                        Prints clear advisory: "Shopper must film unbroken seal opening video to claim physical damage or missing accessories in transit."
                                    </div>
                                </div>

                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.warranty.publicLookupPortal"
                                        color="warning"
                                        density="compact"
                                        hide-details
                                        label="Enable Public Serial & Warranty Lookup Portal"
                                    />
                                    <div class="tt-muted tt-small pl-9 pb-2">
                                        Shoppers can visit <span class="font-weight-bold">/check-warranty</span> and enter IMEI or Order ID to verify claim eligibility.
                                    </div>
                                </div>

                                <div class="tt-switch-box">
                                    <v-switch
                                        v-model="form.warranty.offerExtendedWarranty"
                                        color="warning"
                                        density="compact"
                                        hide-details
                                        label="Enable Extended Warranty Upsell (+6 / +12 Mo.) at Checkout"
                                    />
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- ═════════ TAB 4: CHECKOUT & FRAUD SHIELD ═════════ -->
            <div v-show="currentTab === 'checkout'">
                <v-row dense>
                    <v-col cols="12" lg="6">
                        <v-card flat class="tt-card tt-purple mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">High-Value Gadget Cash on Delivery (COD) Rules</span>
                                <v-spacer />
                                <span class="tt-tag">Fraud Protection</span>
                            </div>
                            <div class="tt-body">
                                <p class="tt-para">
                                    Prevent bogus orders and delivery refusal costs on premium tech products (e.g. flagship phones, gimbal stabilizers, drones).
                                </p>

                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.checkout.maxCodLimit"
                                            label="Maximum Allowed COD Order (৳)"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="Orders exceeding this require online payment"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.checkout.minCartValue"
                                            label="Minimum Cart Checkout (৳)"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                </v-row>

                                <div class="tt-switch-box mt-4 mb-3">
                                    <v-switch
                                        v-model="form.checkout.codSmsOtp"
                                        color="purple"
                                        density="compact"
                                        hide-details
                                        label="Require SMS OTP Verification on COD Orders"
                                    />
                                    <div class="tt-muted tt-small pl-9 pb-2">
                                        Buyer must enter 4-digit SMS OTP sent to their mobile number before their order is logged.
                                    </div>
                                </div>

                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.checkout.advanceDeliveryFeeRequired"
                                        color="purple"
                                        density="compact"
                                        hide-details
                                        label="Require Advance Delivery Fee (৳ 150) for Outside-Dhaka COD"
                                    />
                                    <div class="tt-muted tt-small pl-9 pb-2">
                                        Locks order commitment and reduces return-to-origin (RTO) courier losses.
                                    </div>
                                </div>

                                <div class="tt-switch-box">
                                    <v-switch
                                        v-model="form.checkout.autoBlockHighRto"
                                        color="purple"
                                        density="compact"
                                        hide-details
                                        label="Auto-block phone numbers with &gt;2 past courier return refusals"
                                    />
                                </div>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="6">
                        <v-card flat class="tt-card tt-blue mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Cart Holding &amp; Pre-Order Bookings</span>
                            </div>
                            <div class="tt-body">
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.checkout.paymentSessionTimeoutMins"
                                            label="Digital Payment Holding Session"
                                            type="number"
                                            suffix="Minutes"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="Time allotted to complete bKash/Nagad/Cards"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.checkout.customerSelfCancelMins"
                                            label="Customer Self-Cancel Window"
                                            type="number"
                                            suffix="Minutes"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="Before parcel moves to packaging stage"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6" class="mt-2">
                                        <v-select
                                            v-model="form.checkout.preOrderAdvanceType"
                                            :items="['Percentage (%)', 'Fixed Advance (৳)', 'Full Advance Only']"
                                            label="Pre-Order Deposit Rule"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6" class="mt-2">
                                        <v-text-field
                                            v-model="form.checkout.preOrderAdvanceValue"
                                            label="Advance Value"
                                            type="number"
                                            :suffix="form.checkout.preOrderAdvanceType.includes('%') ? '%' : '৳'"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                </v-row>

                                <div class="tt-switch-box mt-4">
                                    <v-switch
                                        v-model="form.checkout.guestCheckout"
                                        color="primary"
                                        density="compact"
                                        hide-details
                                        label="Enable 1-Click Guest Checkout (No password creation required)"
                                    />
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- ═════════ TAB 5: INVENTORY & WAREHOUSES ═════════ -->
            <div v-show="currentTab === 'inventory'">
                <v-row dense>
                    <v-col cols="12" lg="7">
                        <v-card flat class="tt-card tt-green mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Stock Thresholds &amp; Out-of-Stock Behaviors</span>
                            </div>
                            <div class="tt-body">
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.inventory.globalLowStockThreshold"
                                            label="Global Low Stock Alert Level"
                                            type="number"
                                            suffix="Units"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="Triggers dashboard warning badge"
                                            persistent-hint
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="form.inventory.outOfStockAction"
                                            :items="['Show Out of Stock Badge (Default)', 'Hide Product Completely', 'Convert to Pre-Order Booking']"
                                            label="Action When Stock Reaches 0"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" class="mt-2">
                                        <v-select
                                            v-model="form.inventory.primaryFulfillmentHub"
                                            :items="['Central Logistics Warehouse (Multiplan)', 'Jamuna Future Park Showroom', 'Chattogram Regional Hub']"
                                            label="Primary Stock Deduction Priority"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                </v-row>

                                <div class="tt-switch-box mt-3 mb-2">
                                    <v-switch
                                        v-model="form.inventory.blockNegativeStock"
                                        color="success"
                                        density="compact"
                                        hide-details
                                        label="Strict Zero-Stock Prevention (Block negative inventory orders)"
                                    />
                                </div>
                                <div class="tt-switch-box">
                                    <v-switch
                                        v-model="form.inventory.emailDigestToWarehouse"
                                        color="success"
                                        density="compact"
                                        hide-details
                                        label="Send daily 9:00 AM low-stock replenishment email to store managers"
                                    />
                                </div>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="5">
                        <v-card flat class="tt-card tt-amber h-100">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Stock Health Status</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-stock-stat mb-3">
                                    <div class="d-flex align-center justify-space-between">
                                        <span class="tt-muted">Healthy Inventory Availability</span>
                                        <span class="font-weight-bold text-success">94.2%</span>
                                    </div>
                                    <v-progress-linear model-value="94" color="success" height="6" rounded class="mt-1" />
                                </div>
                                <div class="tt-stock-stat mb-3">
                                    <div class="d-flex align-center justify-space-between">
                                        <span class="tt-muted">Items Nearing Reorder Point</span>
                                        <span class="font-weight-bold text-warning">18 SKUs</span>
                                    </div>
                                </div>
                                <div class="tt-stock-stat">
                                    <div class="d-flex align-center justify-space-between">
                                        <span class="tt-muted">Out-of-Stock SKUs</span>
                                        <span class="font-weight-bold text-error">4 SKUs</span>
                                    </div>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- ═════════ TAB 6: SHIPPING & LOGISTICS ═════════ -->
            <div v-show="currentTab === 'shipping'">
                <v-row dense>
                    <v-col cols="12" lg="7">
                        <v-card flat class="tt-card tt-blue mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Courier Integrations &amp; Regional Rates</span>
                            </div>
                            <div class="tt-body">
                                <v-select
                                    v-model="form.shipping.integratedCouriers"
                                    :items="['Pathao Courier', 'Steadfast Courier', 'RedX Logistics', 'Paperfly', 'Sundarban Courier']"
                                    multiple
                                    chips
                                    label="Active Couriers with API Auto-Consignment"
                                    variant="outlined"
                                    density="comfortable"
                                    class="mb-3"
                                />

                                <v-row dense>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.shipping.insideDhakaRate"
                                            label="Inside Dhaka Delivery (৳)"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.shipping.subDhakaRate"
                                            label="Dhaka Suburbs (৳)"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="4">
                                        <v-text-field
                                            v-model="form.shipping.outsideDhakaRate"
                                            label="Outside Dhaka (৳)"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.shipping.freeShippingOver"
                                            label="Free Delivery on Orders Over (৳)"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.shipping.fragilePackagingFee"
                                            label="Fragile Air-Column Packaging Fee (৳)"
                                            type="number"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="For monitors, cameras & tempered glass"
                                            persistent-hint
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="5">
                        <v-card flat class="tt-card tt-teal h-100">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Same-Day Express Policy</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.shipping.sameDayDhaka"
                                        color="teal"
                                        density="compact"
                                        hide-details
                                        label="Offer Same-Day Express Delivery in Dhaka"
                                    />
                                </div>
                                <v-text-field
                                    v-model="form.shipping.sameDayCutoff"
                                    label="Same-Day Order Cutoff Time"
                                    placeholder="02:00 PM"
                                    variant="outlined"
                                    density="comfortable"
                                    class="mt-2"
                                />
                                <div class="tt-muted tt-small">
                                    Orders placed after this cutoff time are automatically queued for next-morning courier handover.
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- ═════════ TAB 7: NOTIFICATIONS & SMS ═════════ -->
            <div v-show="currentTab === 'notifications'">
                <v-row dense>
                    <v-col cols="12" lg="7">
                        <v-card flat class="tt-card tt-purple mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">SMS Gateway &amp; Masking Configuration</span>
                            </div>
                            <div class="tt-body">
                                <v-row dense>
                                    <v-col cols="12" sm="6">
                                        <v-select
                                            v-model="form.notifications.smsProvider"
                                            :items="['SSL Wireless BD', 'Greenweb Bangladesh', 'BulkSMS BD', 'Twilio SMS']"
                                            label="Active SMS Gateway"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.notifications.smsSenderId"
                                            label="Approved Masking Sender ID"
                                            placeholder="TANJIL"
                                            variant="outlined"
                                            density="comfortable"
                                            hint="BTRC approved alphanumeric sender title"
                                            persistent-hint
                                        />
                                    </v-col>
                                </v-row>

                                <div class="tt-label mt-4 mb-2">Automated SMS Triggers to Buyers</div>
                                <div class="tt-switch-box mb-2">
                                    <v-switch
                                        v-model="form.notifications.smsOnOrderPlace"
                                        color="purple"
                                        density="compact"
                                        hide-details
                                        label="Instant Order Confirmation SMS with Order ID & Total"
                                    />
                                </div>
                                <div class="tt-switch-box mb-2">
                                    <v-switch
                                        v-model="form.notifications.smsOnDispatch"
                                        color="purple"
                                        density="compact"
                                        hide-details
                                        label="Dispatch SMS with Courier Consignment Tracking URL"
                                    />
                                </div>
                                <div class="tt-switch-box">
                                    <v-switch
                                        v-model="form.notifications.smsOnDelivered"
                                        color="purple"
                                        density="compact"
                                        hide-details
                                        label="Delivery Completion &amp; Digital Warranty Registration SMS"
                                    />
                                </div>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="5">
                        <v-card flat class="tt-card tt-green h-100">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">WhatsApp Live Chat Widget</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.notifications.whatsappWidgetEnabled"
                                        color="success"
                                        density="compact"
                                        hide-details
                                        label="Show Floating WhatsApp Button on Storefront"
                                    />
                                </div>
                                <v-text-field
                                    v-model="form.notifications.whatsappWidgetNumber"
                                    label="WhatsApp Number (with country code)"
                                    placeholder="+8801711000000"
                                    variant="outlined"
                                    density="comfortable"
                                />
                                <v-text-field
                                    v-model="form.notifications.whatsappDefaultMsg"
                                    label="Pre-filled Starter Message"
                                    placeholder="Hi Tanjil Traders, I need product consultation."
                                    variant="outlined"
                                    density="comfortable"
                                />
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>

            <!-- ═════════ TAB 8: SECURITY & BACKUP ═════════ -->
            <div v-show="currentTab === 'security'">
                <v-row dense>
                    <v-col cols="12" lg="7">
                        <v-card flat class="tt-card tt-green mb-4">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Staff Access &amp; Administrative Security</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.security.twoFactorMandatory"
                                        color="success"
                                        density="compact"
                                        hide-details
                                        label="Mandatory Two-Factor Authentication (2FA) for all staff"
                                    />
                                </div>
                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.security.ipWhitelistEnabled"
                                        color="success"
                                        density="compact"
                                        hide-details
                                        label="Restrict POS and Admin logins to whitelisted office static IPs"
                                    />
                                </div>

                                <v-row dense class="mt-2">
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.security.sessionTimeoutMins"
                                            label="Session Inactivity Timeout"
                                            type="number"
                                            suffix="Minutes"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="6">
                                        <v-text-field
                                            v-model="form.security.maxFailedLogins"
                                            label="Lockout After Failed Logins"
                                            type="number"
                                            suffix="Attempts"
                                            variant="outlined"
                                            density="comfortable"
                                        />
                                    </v-col>
                                </v-row>
                            </div>
                        </v-card>
                    </v-col>

                    <v-col cols="12" lg="5">
                        <v-card flat class="tt-card tt-amber h-100">
                            <div class="tt-head">
                                <span class="tt-dot"></span>
                                <span class="tt-card-title">Maintenance Mode &amp; Database Backup</span>
                            </div>
                            <div class="tt-body">
                                <div class="tt-switch-box mb-3">
                                    <v-switch
                                        v-model="form.security.maintenanceMode"
                                        color="error"
                                        density="compact"
                                        hide-details
                                        label="Enable Storefront Maintenance Mode"
                                    />
                                </div>

                                <div class="tt-label mt-3 mb-1">Admin Secret Bypass Link</div>
                                <v-text-field
                                    :model-value="bypassLink"
                                    readonly
                                    variant="outlined"
                                    density="comfortable"
                                    append-inner-icon="mdi-content-copy"
                                    @click:append-inner="copyBypassLink"
                                />

                                <v-btn
                                    variant="outlined"
                                    color="#0a5548"
                                    block
                                    class="text-none mt-3 font-weight-bold"
                                    prepend-icon="mdi-database-arrow-down"
                                    @click="downloadBackup"
                                >
                                    Download Full SQL Database Snapshot
                                </v-btn>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
            </div>
        </v-form>

        <!-- Feedback Notification Snackbar -->
        <v-snackbar v-model="toast.show" :color="toast.color" timeout="2800" location="bottom right">
            {{ toast.text }}
        </v-snackbar>
    </div>
</template>

<script>
const STORAGE_KEY = "tt_full_general_settings";

const defaultSettings = () => ({
    store: {
        name: "Tanjil Traders",
        legalEntity: "Tanjil Traders International Ltd.",
        tagline: "Authentic Gadgets, Official Warranty",
        binNumber: "002349182-0101",
        supportEmail: "support@tanjiltraders.com",
        hotline: "+880 1711 000 000",
        whatsapp: "+880 1811 000 000",
        hqAddress: "Suite 412, Level 4, Multiplan Center, Elephant Road, Dhaka-1205",
        genuineWatermark: true,
    },
    hardware: {
        imeiTracking: true,
        bundleSerials: true,
        autoGenBarcodes: true,
        barcodeFormat: "Code 128 (Standard)",
        labelPrinterSize: "50mm × 25mm (Standard)",
    },
    warranty: {
        brandWarrantyMonths: 12,
        shopWarrantyMonths: 6,
        replacementWindowDays: 7,
        serviceEscalationEmail: "rma@tanjiltraders.com",
        termsSummary: "Brand warranties serviced through official distribution hubs. Hardware defective units qualify for direct 7-day replacement with intact packaging, invoice, and unboxing video verification.",
        requireUnboxingVideo: true,
        publicLookupPortal: true,
        offerExtendedWarranty: true,
    },
    checkout: {
        maxCodLimit: 25000,
        minCartValue: 200,
        codSmsOtp: true,
        advanceDeliveryFeeRequired: true,
        autoBlockHighRto: true,
        paymentSessionTimeoutMins: 15,
        customerSelfCancelMins: 30,
        preOrderAdvanceType: "Percentage (%)",
        preOrderAdvanceValue: 20,
        guestCheckout: true,
    },
    inventory: {
        globalLowStockThreshold: 5,
        outOfStockAction: "Show Out of Stock Badge (Default)",
        primaryFulfillmentHub: "Central Logistics Warehouse (Multiplan)",
        blockNegativeStock: true,
        emailDigestToWarehouse: true,
    },
    shipping: {
        integratedCouriers: ["Pathao Courier", "Steadfast Courier", "RedX Logistics"],
        insideDhakaRate: 70,
        subDhakaRate: 100,
        outsideDhakaRate: 130,
        freeShippingOver: 5000,
        fragilePackagingFee: 50,
        sameDayDhaka: true,
        sameDayCutoff: "02:00 PM",
    },
    notifications: {
        smsProvider: "SSL Wireless BD",
        smsSenderId: "TANJIL",
        smsOnOrderPlace: true,
        smsOnDispatch: true,
        smsOnDelivered: true,
        whatsappWidgetEnabled: true,
        whatsappWidgetNumber: "+8801811000000",
        whatsappDefaultMsg: "Hello Tanjil Traders, I would like to inquire about gadget availability.",
    },
    security: {
        twoFactorMandatory: true,
        ipWhitelistEnabled: false,
        sessionTimeoutMins: 60,
        maxFailedLogins: 5,
        maintenanceMode: false,
    },
});

export default {
    name: "GeneralSettings",

    data() {
        return {
            currentTab: "store",
            saving: false,
            initialSnapshot: "",
            form: defaultSettings(),

            tabs: [
                { key: "store", label: "Store & Brand", desc: "Profile, Legal & BIN", icon: "mdi-storefront-outline", badge: "Public", badgeClass: "green" },
                { key: "hardware", label: "IMEI & Barcodes", desc: "Serialization & Print", icon: "mdi-barcode-scan", badge: "Core", badgeClass: "amber" },
                { key: "warranty", label: "Warranty & Claims", desc: "Replacement & RMA", icon: "mdi-shield-check-outline", badge: "7-Day DOA", badgeClass: "teal" },
                { key: "checkout", label: "Checkout & COD", desc: "Fraud Shield & OTP", icon: "mdi-cart-check", badge: "Protected", badgeClass: "purple" },
                { key: "inventory", label: "Stock & Warehouses", desc: "Alerts & Reorders", icon: "mdi-warehouse", badge: "Multi-Hub", badgeClass: "green" },
                { key: "shipping", label: "Shipping & Couriers", desc: "Pathao, Rates & Express", icon: "mdi-truck-fast-outline" },
                { key: "notifications", label: "SMS & WhatsApp", desc: "Gateways & Alerts", icon: "mdi-message-badge-outline" },
                { key: "security", label: "Security & Backup", desc: "2FA, Access & DB", icon: "mdi-lock-outline" },
            ],

            toast: { show: false, text: "", color: "success" },
        };
    },

    computed: {
        isDirty() {
            return JSON.stringify(this.form) !== this.initialSnapshot;
        },
        bypassLink() {
            return `https://tanjiltraders.com/?bypass=tt_adm_${btoa("tanjil_admin_secret").slice(0, 8)}`;
        },
    },

    created() {
        this.loadSettings();
    },

    methods: {
        loadSettings() {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    const parsed = JSON.parse(stored);
                    this.form = Object.assign(defaultSettings(), parsed);
                }
            } catch (e) {
                this.form = defaultSettings();
            }
            this.initialSnapshot = JSON.stringify(this.form);
        },

        async saveAll() {
            const isValid = this.$refs.formRef ? (await this.$refs.formRef.validate()).valid : true;
            if (!isValid) {
                this.notify("Please review form and complete required fields", "error");
                return;
            }

            this.saving = true;
            try {
                // Hook to API: await axios.put('/api/admin/settings/general', this.form);
                await new Promise((res) => setTimeout(res, 400));
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.form));
                this.initialSnapshot = JSON.stringify(this.form);
                this.notify("All settings updated successfully");
            } catch (err) {
                this.notify("Could not save settings. Please retry.", "error");
            } finally {
                this.saving = false;
            }
        },

        resetForm() {
            this.form = JSON.parse(this.initialSnapshot);
            this.notify("Unsaved modifications reverted", "info");
        },

        copyBypassLink() {
            navigator.clipboard?.writeText(this.bypassLink);
            this.notify("Bypass link copied to clipboard");
        },

        downloadBackup() {
            this.notify("SQL snapshot generated & download initiated", "success");
        },

        notify(text, color = "success") {
            this.toast = { show: true, text, color };
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap");

.tt-gen {
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
.tt-hero-logo {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
}
.tt-h1 { font-size: 24px; font-weight: 700; line-height: 1.2; letter-spacing: -0.3px; }
.tt-hero-sub { font-size: 13px; color: rgba(255, 255, 255, 0.75); margin-top: 3px; }

.tt-pill {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
    background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);
}
.tt-pill-live { color: #6ee7b7; }
.tt-pill-tag { color: #fed7aa; }
.tt-pill-warn { color: #fca5a5; }
.tt-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.25); }

.tt-ghost-btn { color: #fff !important; border-color: rgba(255, 255, 255, 0.35) !important; }
.tt-add { color: #0a5548 !important; }

.tt-hero-strip {
    display: grid; grid-template-columns: repeat(4, 1fr);
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.14);
}
.tt-hero-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 0 18px;
    border-left: 1px solid rgba(255, 255, 255, 0.14);
}
.tt-hero-item:first-child { border-left: 0; padding-left: 0; }
.tt-hero-icon {
    width: 34px; height: 34px; border-radius: 8px;
    display: grid; place-items: center;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.2);
    flex: none;
}
.tt-hero-value { font-size: 17px; font-weight: 700; line-height: 1.2; }
.tt-hero-label { font-size: 11.5px; font-weight: 500; color: rgba(255, 255, 255, 0.85); }
.tt-hero-note { font-size: 10.5px; color: rgba(255, 255, 255, 0.6); }
.tt-hero-line {
    position: absolute; left: 0; right: 0; bottom: 0; height: 2px;
    background: linear-gradient(90deg, #10b981, rgba(16, 185, 129, 0) 70%);
}

@media (max-width: 1050px) {
    .tt-hero-strip { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .tt-hero-item { border-left: 0; padding-left: 0; }
}

/* ───────── VISIBLE COMMAND GRID TABS (NO SCROLLBAR) ───────── */
.tt-tabs-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-top: 20px;
    margin-bottom: 24px;
}
@media (max-width: 1100px) {
    .tt-tabs-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .tt-tabs-grid { grid-template-columns: 1fr; }
}

.tt-tab-card {
    display: flex; flex-direction: column; text-align: left;
    background: #fff; border: 1px solid var(--line);
    border-radius: 10px; padding: 14px 16px;
    cursor: pointer; transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04);
}
.tt-tab-card:hover {
    border-color: #0f9d6b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 42, 38, 0.08);
}
.tt-tab-card.active {
    background: linear-gradient(135deg, #07473c 0%, #0a5548 100%);
    color: #fff;
    border-color: #07473c;
    box-shadow: 0 4px 14px rgba(10, 85, 72, 0.25);
}
.tt-tab-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: grid; place-items: center;
    background: #eaf3f0; color: #0a5548;
}
.tt-tab-card.active .tt-tab-icon {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
}
.tt-tab-badge {
    font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px;
    background: #f1f5f4; color: #4b635e;
}
.tt-tab-badge.green { background: #dff5ec; color: #0f7a4d; }
.tt-tab-badge.amber { background: #fef3c7; color: #b45309; }
.tt-tab-badge.teal  { background: #ccfbf1; color: #0f766e; }
.tt-tab-badge.purple { background: #f3e8ff; color: #7e22ce; }
.tt-tab-card.active .tt-tab-badge { background: rgba(255, 255, 255, 0.25); color: #fff; }

.tt-tab-title { font-size: 13.5px; font-weight: 600; margin-top: 4px; }
.tt-tab-sub { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
.tt-tab-card.active .tt-tab-sub { color: rgba(255, 255, 255, 0.8); }

/* ───────── Cards ───────── */
.tt-card {
    --accent: #2f5be7;
    --tint: #eaf0ff;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 10px !important;
    box-shadow: 0 1px 2px rgba(15, 42, 38, 0.04);
    overflow: hidden;
}
.tt-blue   { --accent: #2f5be7; --tint: #e9efff; }
.tt-green  { --accent: #0f9d6b; --tint: #e3f6ee; }
.tt-purple { --accent: #7c3aed; --tint: #f0e9ff; }
.tt-teal   { --accent: #0f766e; --tint: #ddf3f0; }
.tt-amber  { --accent: #d97706; --tint: #fff2d6; }

.tt-head {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 18px;
    background: linear-gradient(90deg, var(--tint), #fff);
    border-bottom: 1px solid var(--line);
    border-left: 3px solid var(--accent);
}
.tt-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex: none; }
.tt-card-title { font-size: 14.5px; font-weight: 600; color: var(--ink); }
.tt-tag {
    font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 6px;
    color: var(--accent); background: #fff; border: 1px solid var(--accent);
}
.tt-tag.warn { color: #8a5a00; background: #fdf1cf; border-color: #f3d78a; }
.tt-body { padding: 18px 20px; }
.tt-muted { color: var(--muted); }
.tt-small { font-size: 12px; }
.tt-para { font-size: 13px; line-height: 1.6; color: var(--muted); margin-bottom: 16px; }
.tt-label { font-size: 11px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; color: #3d5450; }

.tt-switch-box { background: #f6faf9; border-radius: 8px; padding: 6px 14px; border: 1px solid var(--line); }

/* Uploader & previews */
.tt-uploader-box { border: 1px dashed #b7cac5; border-radius: 8px; padding: 12px; background: #fbfcfa; }
.tt-logo-preview { width: 44px; height: 44px; border-radius: 8px; background: #e3f6ee; display: grid; place-items: center; flex: none; }
.tt-fav-preview { width: 34px; height: 34px; border-radius: 6px; background: #e3f6ee; display: grid; place-items: center; flex: none; }

.tt-barcode-preview {
    border: 2px dashed #b7cac5; border-radius: 8px;
    padding: 18px; background: #fafcfb;
}
.tt-barcode-bars {
    font-size: 24px; letter-spacing: 2px; font-weight: 900; color: #111;
}

.tt-stock-stat {
    border: 1px solid var(--line); border-radius: 8px;
    padding: 10px 14px; background: #fafcfb;
}
</style>