<template>
  <main class="profile-edit">
    <div class="page-heading">
      <div><p class="eyebrow">ADMIN ACCOUNT</p><h1>Edit profile</h1><p class="heading-subtitle">Manage your store-team profile, contact details and account preferences.</p></div>
      <router-link to="/admin/profile" class="back-link"><i class="mdi mdi-arrow-left"></i> Back to profile</router-link>
    </div>

    <section class="cover-card">
      <div class="cover-preview" :style="coverStyle">
        <label class="upload-button cover-upload"><i class="mdi mdi-image-edit-outline"></i> Change cover<input type="file" accept="image/*" @change="onImageChange($event, 'cover')"></label>
        <div class="cover-caption">A clear profile helps your team know who to contact.</div>
      </div>
      <div class="identity-preview">
        <div class="avatar-upload-wrap">
          <img v-if="form.avatar" :src="form.avatar" alt="Profile photo preview" class="avatar-preview">
          <span v-else class="avatar-fallback">{{ initials }}</span>
          <label class="avatar-camera" title="Change profile photo"><i class="mdi mdi-camera-outline"></i><input type="file" accept="image/*" @change="onImageChange($event, 'avatar')"></label>
        </div>
        <div class="identity-copy"><strong>{{ fullNamePreview }}</strong><span>{{ form.designation || form.role || 'Store Administrator' }}</span></div>
        <div class="identity-actions"><router-link to="/admin/profile" class="quiet-button">Cancel</router-link><button class="primary-button" @click="saveProfile"><i class="mdi mdi-content-save-outline"></i> Save changes</button></div>
      </div>
    </section>

    <section class="panel completion-panel">
      <div class="section-heading"><div><p class="eyebrow">PROFILE HEALTH</p><h2>Complete your profile</h2></div><span class="completion-badge">{{ completion }}%</span></div>
      <div class="progress-track"><span :style="{ width: completion + '%' }"></span></div>
      <p class="helper">Add contact, location and role information to help the Tanjil Traders team work together.</p>
    </section>

    <section class="panel portfolio-panel">
      <div class="section-heading"><div><p class="eyebrow">ONLINE PRESENCE</p><h2>Store portfolio</h2><p class="section-subtitle">Add useful store and professional links.</p></div><button class="outline-button" @click="addPortfolio"><i class="mdi mdi-plus"></i> Add link</button></div>
      <div class="portfolio-list">
        <div v-for="(link, index) in form.portfolio" :key="index" class="portfolio-row">
          <span class="social-mark"><i :class="link.icon || 'mdi mdi-link-variant'"></i></span>
          <label class="field-block"><span>Link name</span><input v-model.trim="link.label" type="text" placeholder="Store website"></label>
          <label class="field-block"><span>URL or handle</span><input v-model.trim="link.url" type="text" placeholder="https://example.com"></label>
          <button class="remove-button" aria-label="Remove link" @click="removePortfolio(index)"><i class="mdi mdi-close"></i></button>
        </div>
        <div v-if="!form.portfolio.length" class="empty-links">No links yet. Select “Add link” to add your store website or social profile.</div>
      </div>
    </section>

    <section class="panel editor-panel">
      <div class="editor-tabs" role="tablist" aria-label="Profile settings">
        <button v-for="tab in tabs" :key="tab.id" role="tab" :aria-selected="activeTab === tab.id" :class="{ active: activeTab === tab.id }" @click="activeTab = tab.id"><i :class="tab.icon"></i>{{ tab.label }}</button>
      </div>

      <form v-if="activeTab === 'personal'" class="edit-form" @submit.prevent="saveProfile">
        <div class="form-intro"><div><h2>Personal details</h2><p>These details are used for your admin profile and team contact information.</p></div><span class="required-note">* Required</span></div>
        <div class="form-grid">
          <label class="field-block"><span>First name <b>*</b></span><input v-model.trim="form.firstName" required autocomplete="given-name" placeholder="First name"></label>
          <label class="field-block"><span>Last name</span><input v-model.trim="form.lastName" autocomplete="family-name" placeholder="Last name"></label>
          <label class="field-block"><span>Phone number</span><input v-model.trim="form.phone" type="tel" autocomplete="tel" placeholder="+880 1XXXXXXXXX"></label>
          <label class="field-block"><span>Email address <b>*</b></span><input v-model.trim="form.email" type="email" required autocomplete="email" placeholder="you@tanjiltraders.com"></label>
          <label class="field-block"><span>Designation</span><input v-model.trim="form.designation" placeholder="e.g. Store Manager"></label>
          <label class="field-block"><span>Department</span><input v-model.trim="form.department" placeholder="e.g. Store Operations"></label>
          <label class="field-block"><span>Website</span><input v-model.trim="form.website" type="url" placeholder="https://tanjiltraders.com"></label>
          <label class="field-block"><span>City</span><input v-model.trim="form.city" placeholder="Dhaka"></label>
          <label class="field-block"><span>Country</span><input v-model.trim="form.country" placeholder="Bangladesh"></label>
          <label class="field-block"><span>Postal code</span><input v-model.trim="form.postalCode" placeholder="1205"></label>
          <label class="field-block field-span-2"><span>Skills & responsibilities</span><div class="skill-editor"><span v-for="(skill, index) in form.skills" :key="`${skill}-${index}`" class="skill-token">{{ skill }}<button type="button" :aria-label="`Remove ${skill}`" @click="removeSkill(index)"><i class="mdi mdi-close"></i></button></span><input v-model.trim="newSkill" placeholder="Type a skill and press Enter" @keydown.enter.prevent="addSkill"></div></label>
          <label class="field-block field-span-2"><span>About you</span><textarea v-model.trim="form.description" rows="4" maxlength="500" placeholder="Briefly describe your role, expertise and how you support the store team."></textarea><small class="field-hint">{{ form.description.length }}/500 characters</small></label>
        </div>
        <div class="form-actions"><span v-if="notice" class="save-notice" role="status"><i class="mdi mdi-check-circle-outline"></i>{{ notice }}</span><button type="button" class="quiet-button" @click="resetForm">Reset</button><button type="submit" class="primary-button"><i class="mdi mdi-content-save-outline"></i> Update profile</button></div>
      </form>

      <form v-else-if="activeTab === 'password'" class="edit-form" @submit.prevent="submitPassword">
        <div class="form-intro"><div><h2>Change password</h2><p>Choose a strong password and do not reuse one from another account.</p></div></div>
        <div class="security-notice"><i class="mdi mdi-shield-lock-outline"></i><span>Password changes must be saved by a secure server-side endpoint. This component deliberately does not store passwords in browser storage.</span></div>
        <div class="password-fields"><label class="field-block"><span>Current password</span><input v-model="password.current" type="password" autocomplete="current-password" placeholder="Enter current password"></label><label class="field-block"><span>New password</span><input v-model="password.next" type="password" autocomplete="new-password" placeholder="At least 8 characters"></label><label class="field-block"><span>Confirm new password</span><input v-model="password.confirm" type="password" autocomplete="new-password" placeholder="Re-enter new password"></label></div>
        <p v-if="passwordMessage" class="form-message" role="status">{{ passwordMessage }}</p>
        <div class="form-actions"><span></span><button type="submit" class="primary-button"><i class="mdi mdi-lock-reset"></i> Validate password</button></div>
      </form>

      <form v-else-if="activeTab === 'experience'" class="edit-form" @submit.prevent="saveExperience">
        <div class="form-intro"><div><h2>Experience</h2><p>Keep your store-team role and work history current.</p></div><button type="button" class="outline-button" @click="addExperience"><i class="mdi mdi-plus"></i> Add experience</button></div>
        <div v-if="form.experience.length" class="experience-list"><article v-for="(job, index) in form.experience" :key="index" class="experience-card"><div class="experience-icon"><i class="mdi mdi-store-outline"></i></div><div class="experience-fields"><label class="field-block"><span>Role / position</span><input v-model.trim="job.title" placeholder="Store manager"></label><label class="field-block"><span>Company / branch</span><input v-model.trim="job.company" placeholder="Tanjil Traders · Dhaka"></label><label class="field-block"><span>From</span><input v-model="job.from" type="month"></label><label class="field-block"><span>To</span><input v-model="job.to" type="month" :disabled="job.current"></label><label class="current-role"><input v-model="job.current" type="checkbox"><span>I currently work here</span></label><label class="field-block field-span-2"><span>Responsibilities</span><textarea v-model.trim="job.description" rows="2" placeholder="Describe key responsibilities"></textarea></label></div><button type="button" class="remove-button" aria-label="Remove experience" @click="removeExperience(index)"><i class="mdi mdi-trash-can-outline"></i></button></article></div>
        <div v-else class="empty-links">No experience added yet. Add your current store role or previous work history.</div>
        <div class="form-actions"><span v-if="notice" class="save-notice" role="status"><i class="mdi mdi-check-circle-outline"></i>{{ notice }}</span><button type="submit" class="primary-button"><i class="mdi mdi-content-save-outline"></i> Save experience</button></div>
      </form>

      <form v-else-if="activeTab === 'store-settings'" class="edit-form store-settings-form" @submit.prevent="saveStoreSettings">
        <div class="form-intro"><div><h2>Store & website settings</h2><p>Manage the public business profile and the electronics-store policies shown to customers and staff.</p></div><span class="local-settings-badge"><i class="mdi mdi-content-save-outline"></i> Browser saved</span></div>
        <div class="store-setting-groups">
          <section class="setting-group setting-group--green">
            <div class="setting-group-head"><span class="setting-group-icon"><i class="mdi mdi-storefront-outline"></i></span><div><h3>Business profile & public contacts</h3><p>Store identity, customer service details and invoice information.</p></div></div>
            <div class="form-grid">
              <label class="field-block"><span>Public store name <b>*</b></span><input v-model.trim="storeSettings.store.name" required placeholder="Tanjil Traders"></label>
              <label class="field-block"><span>Registered business entity</span><input v-model.trim="storeSettings.store.legalEntity" placeholder="Registered company name"></label>
              <label class="field-block"><span>Store tagline</span><input v-model.trim="storeSettings.store.tagline" placeholder="Authentic Gadgets, Official Warranty"></label>
              <label class="field-block"><span>Trade BIN / VAT number</span><input v-model.trim="storeSettings.store.binNumber" placeholder="BIN / VAT registration"></label>
              <label class="field-block"><span>Customer support email</span><input v-model.trim="storeSettings.store.supportEmail" type="email" placeholder="support@example.com"></label>
              <label class="field-block"><span>Customer hotline</span><input v-model.trim="storeSettings.store.hotline" type="tel" placeholder="+880 1XXX-XXXXXX"></label>
              <label class="field-block"><span>WhatsApp Business number</span><input v-model.trim="storeSettings.store.whatsapp" type="tel" placeholder="+880 1XXX-XXXXXX"></label>
              <label class="field-block field-span-2"><span>Showroom / business address</span><textarea v-model.trim="storeSettings.store.hqAddress" rows="2" placeholder="Showroom address"></textarea></label>
            </div>
            <div class="brand-upload-grid">
              <label class="brand-upload-card"><span class="brand-preview"> <img v-if="storeSettings.store.logoData" :src="storeSettings.store.logoData" alt="Store logo preview"><i v-else class="mdi mdi-storefront-outline"></i></span><span class="brand-upload-copy"><strong>Storefront logo</strong><small>PNG, JPG or SVG · up to 1.5 MB</small><input type="file" accept="image/*,.svg" @change="onStoreAssetChange($event, 'logoData')"></span></label>
              <label class="brand-upload-card"><span class="favicon-preview"><img v-if="storeSettings.store.faviconData" :src="storeSettings.store.faviconData" alt="Favicon preview"><i v-else class="mdi mdi-cellphone-charging"></i></span><span class="brand-upload-copy"><strong>Browser favicon</strong><small>Square icon · up to 1.5 MB</small><input type="file" accept="image/*,.ico" @change="onStoreAssetChange($event, 'faviconData')"></span></label>
            </div>
            <div class="setting-toggles"><label class="setting-toggle"><input v-model="storeSettings.store.genuineWatermark" type="checkbox"><span><strong>Print “100% Genuine” on customer invoices</strong><small>Show the authenticity statement on order documents.</small></span></label></div>
          </section>

          <section class="setting-group setting-group--teal">
            <div class="setting-group-head"><span class="setting-group-icon"><i class="mdi mdi-shield-check-outline"></i></span><div><h3>Warranty, returns & device traceability</h3><p>Relevant to phones, laptops, accessories and serialized gadgets.</p></div></div>
            <div class="form-grid">
              <label class="field-block"><span>Brand warranty (months)</span><input v-model.number="storeSettings.warranty.brandWarrantyMonths" type="number" min="0"></label>
              <label class="field-block"><span>Store warranty (months)</span><input v-model.number="storeSettings.warranty.shopWarrantyMonths" type="number" min="0"></label>
              <label class="field-block"><span>DOA replacement window (days)</span><input v-model.number="storeSettings.warranty.replacementWindowDays" type="number" min="0"></label>
              <label class="field-block"><span>Warranty claims email</span><input v-model.trim="storeSettings.warranty.serviceEscalationEmail" type="email" placeholder="rma@example.com"></label>
              <label class="field-block field-span-2"><span>Warranty terms summary</span><textarea v-model.trim="storeSettings.warranty.termsSummary" rows="3" placeholder="Customer-facing warranty and replacement policy"></textarea></label>
              <label class="field-block"><span>Barcode format</span><select v-model="storeSettings.hardware.barcodeFormat"><option>Code 128 (Standard)</option><option>QR Code (Compact)</option><option>EAN-13 (Retail Standard)</option><option>UPC-A</option></select></label>
              <label class="field-block"><span>Thermal label size</span><select v-model="storeSettings.hardware.labelPrinterSize"><option>50mm × 25mm (Standard)</option><option>40mm × 30mm (Compact)</option><option>100mm × 50mm (Large Box)</option></select></label>
            </div>
            <div class="setting-toggles">
              <label class="setting-toggle"><input v-model="storeSettings.hardware.imeiTracking" type="checkbox"><span><strong>Require IMEI / serial capture before dispatch</strong><small>Trace phones, tablets, earbuds and smartwatches through fulfillment.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.hardware.bundleSerials" type="checkbox"><span><strong>Capture separate serials for bundled devices</strong><small>Useful for multi-part kits and left/right earbuds.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.hardware.autoGenBarcodes" type="checkbox"><span><strong>Auto-generate internal barcodes</strong><small>For loose accessories without manufacturer barcodes.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.warranty.requireUnboxingVideo" type="checkbox"><span><strong>Require unboxing video for transit-damage claims</strong><small>Display the condition clearly in customer policy text.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.warranty.publicLookupPortal" type="checkbox"><span><strong>Enable public serial / warranty lookup</strong><small>Allow customer access to the warranty-check workflow.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.warranty.offerExtendedWarranty" type="checkbox"><span><strong>Offer extended warranty at checkout</strong><small>Show optional extended coverage where configured.</small></span></label>
            </div>
          </section>

          <section class="setting-group setting-group--blue">
            <div class="setting-group-head"><span class="setting-group-icon"><i class="mdi mdi-cart-check"></i></span><div><h3>Checkout & cash on delivery</h3><p>Set customer checkout limits and order verification preferences.</p></div></div>
            <div class="form-grid">
              <label class="field-block"><span>Maximum COD order (৳)</span><input v-model.number="storeSettings.checkout.maxCodLimit" type="number" min="0"></label>
              <label class="field-block"><span>Minimum cart value (৳)</span><input v-model.number="storeSettings.checkout.minCartValue" type="number" min="0"></label>
              <label class="field-block"><span>Digital payment hold (minutes)</span><input v-model.number="storeSettings.checkout.paymentSessionTimeoutMins" type="number" min="1"></label>
              <label class="field-block"><span>Customer self-cancel window (minutes)</span><input v-model.number="storeSettings.checkout.customerSelfCancelMins" type="number" min="0"></label>
              <label class="field-block"><span>Pre-order advance rule</span><select v-model="storeSettings.checkout.preOrderAdvanceType"><option>Percentage (%)</option><option>Fixed Advance (৳)</option><option>Full Advance Only</option></select></label>
              <label class="field-block"><span>Pre-order advance value</span><input v-model.number="storeSettings.checkout.preOrderAdvanceValue" type="number" min="0"></label>
            </div>
            <div class="setting-toggles">
              <label class="setting-toggle"><input v-model="storeSettings.checkout.codSmsOtp" type="checkbox"><span><strong>Verify COD orders by SMS OTP</strong><small>Help confirm delivery contact details before fulfillment.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.checkout.advanceDeliveryFeeRequired" type="checkbox"><span><strong>Require delivery-fee advance for outside-Dhaka COD</strong><small>Reduce uncollected courier return costs.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.checkout.autoBlockHighRto" type="checkbox"><span><strong>Flag repeat return-to-origin phone numbers</strong><small>Review numbers with repeated courier refusals.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.checkout.guestCheckout" type="checkbox"><span><strong>Allow guest checkout</strong><small>Let customers place orders without creating an account.</small></span></label>
            </div>
          </section>

          <section class="setting-group setting-group--amber">
            <div class="setting-group-head"><span class="setting-group-icon"><i class="mdi mdi-warehouse"></i></span><div><h3>Inventory & delivery</h3><p>Customer-facing stock rules, regional rates and courier choices.</p></div></div>
            <div class="form-grid">
              <label class="field-block"><span>Low-stock alert threshold (units)</span><input v-model.number="storeSettings.inventory.globalLowStockThreshold" type="number" min="0"></label>
              <label class="field-block"><span>Out-of-stock product behavior</span><select v-model="storeSettings.inventory.outOfStockAction"><option>Show Out of Stock Badge (Default)</option><option>Hide Product Completely</option><option>Convert to Pre-Order Booking</option></select></label>
              <label class="field-block field-span-2"><span>Primary fulfillment location</span><select v-model="storeSettings.inventory.primaryFulfillmentHub"><option>Central Logistics Warehouse (Multiplan)</option><option>Jamuna Future Park Showroom</option><option>Chattogram Regional Hub</option></select></label>
              <label class="field-block"><span>Inside Dhaka delivery (৳)</span><input v-model.number="storeSettings.shipping.insideDhakaRate" type="number" min="0"></label>
              <label class="field-block"><span>Dhaka suburbs delivery (৳)</span><input v-model.number="storeSettings.shipping.subDhakaRate" type="number" min="0"></label>
              <label class="field-block"><span>Outside Dhaka delivery (৳)</span><input v-model.number="storeSettings.shipping.outsideDhakaRate" type="number" min="0"></label>
              <label class="field-block"><span>Free delivery above (৳)</span><input v-model.number="storeSettings.shipping.freeShippingOver" type="number" min="0"></label>
              <label class="field-block"><span>Fragile packaging fee (৳)</span><input v-model.number="storeSettings.shipping.fragilePackagingFee" type="number" min="0"></label>
              <label class="field-block"><span>Same-day dispatch cutoff</span><input v-model="storeSettings.shipping.sameDayCutoff" type="time"></label>
              <label class="field-block field-span-2"><span>Active delivery couriers</span><select v-model="storeSettings.shipping.integratedCouriers" multiple size="5"><option v-for="courier in courierOptions" :key="courier" :value="courier">{{ courier }}</option></select><small class="field-hint">Use Ctrl (Windows) or Command (Mac) to select multiple couriers.</small></label>
            </div>
            <div class="setting-toggles">
              <label class="setting-toggle"><input v-model="storeSettings.inventory.blockNegativeStock" type="checkbox"><span><strong>Prevent checkout when stock is zero</strong><small>Blocks negative inventory orders.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.inventory.emailDigestToWarehouse" type="checkbox"><span><strong>Enable daily low-stock reminder</strong><small>Reminder preference for store/warehouse managers.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.shipping.sameDayDhaka" type="checkbox"><span><strong>Offer same-day delivery in Dhaka</strong><small>Show only if your courier and operations support it.</small></span></label>
            </div>
          </section>

          <section class="setting-group setting-group--purple">
            <div class="setting-group-head"><span class="setting-group-icon"><i class="mdi mdi-message-badge-outline"></i></span><div><h3>Customer messages & storefront status</h3><p>Order notifications, WhatsApp contact, and optional maintenance mode.</p></div></div>
            <div class="form-grid">
              <label class="field-block"><span>SMS service provider</span><input v-model.trim="storeSettings.notifications.smsProvider" placeholder="SMS provider"></label>
              <label class="field-block"><span>Approved SMS sender ID</span><input v-model.trim="storeSettings.notifications.smsSenderId" placeholder="TANJIL"></label>
              <label class="field-block"><span>WhatsApp contact number</span><input v-model.trim="storeSettings.notifications.whatsappWidgetNumber" type="tel" placeholder="+8801XXXXXXXXX"></label>
              <label class="field-block field-span-2"><span>WhatsApp starter message</span><textarea v-model.trim="storeSettings.notifications.whatsappDefaultMsg" rows="2" placeholder="Customer support greeting"></textarea></label>
            </div>
            <div class="setting-toggles">
              <label class="setting-toggle"><input v-model="storeSettings.notifications.smsOnOrderPlace" type="checkbox"><span><strong>Notify customer when order is placed</strong><small>Preference for the connected SMS service.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.notifications.smsOnDispatch" type="checkbox"><span><strong>Notify customer when order is dispatched</strong><small>Include courier tracking when available.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.notifications.smsOnDelivered" type="checkbox"><span><strong>Notify customer on delivery completion</strong><small>Delivery message and warranty registration reminder.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.notifications.whatsappWidgetEnabled" type="checkbox"><span><strong>Show WhatsApp support button on storefront</strong><small>Offers customers a direct contact option.</small></span></label>
              <label class="setting-toggle setting-toggle--warning"><input v-model="storeSettings.security.maintenanceMode" type="checkbox"><span><strong>Storefront maintenance mode</strong><small>Preference only: connect to server-side maintenance handling before it affects customers.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.security.twoFactorMandatory" type="checkbox"><span><strong>Require two-factor authentication for admin staff</strong><small>Preference only until a server-side authentication policy is connected.</small></span></label>
              <label class="setting-toggle"><input v-model="storeSettings.security.ipWhitelistEnabled" type="checkbox"><span><strong>Restrict admin sign-in by office IP</strong><small>Requires verified IP allowlist enforcement on the server.</small></span></label>
            </div>
            <div class="form-grid security-fields"><label class="field-block"><span>Admin idle timeout (minutes)</span><input v-model.number="storeSettings.security.sessionTimeoutMins" type="number" min="5"></label><label class="field-block"><span>Failed sign-in lockout attempts</span><input v-model.number="storeSettings.security.maxFailedLogins" type="number" min="1"></label></div>
          </section>
        </div>
        <div class="form-actions"><span v-if="settingsNotice" class="save-notice" role="status"><i class="mdi mdi-check-circle-outline"></i>{{ settingsNotice }}</span><button type="button" class="quiet-button" @click="resetStoreSettings">Reset store settings</button><button type="submit" class="primary-button"><i class="mdi mdi-content-save-outline"></i> Save store settings</button></div>
        <p class="settings-disclaimer"><i class="mdi mdi-information-outline"></i>These preferences are stored in this browser using the existing General Settings key. For multi-user persistence, connect them to a protected Laravel settings API.</p>
      </form>

      <form v-else class="edit-form" @submit.prevent="saveProfile">
        <div class="form-intro"><div><h2>Privacy preferences</h2><p>Choose which profile details are visible to other authorized store-team members.</p></div></div>
        <div class="privacy-list">
          <label class="privacy-row"><span class="privacy-icon"><i class="mdi mdi-email-outline"></i></span><span class="privacy-copy"><strong>Show email on profile</strong><small>Let authenticated store staff see your work email.</small></span><input v-model="form.privacy.showEmail" type="checkbox" class="toggle-control"></label>
          <label class="privacy-row"><span class="privacy-icon"><i class="mdi mdi-phone-outline"></i></span><span class="privacy-copy"><strong>Show phone on profile</strong><small>Let authenticated store staff see your contact number.</small></span><input v-model="form.privacy.showPhone" type="checkbox" class="toggle-control"></label>
          <label class="privacy-row"><span class="privacy-icon"><i class="mdi mdi-account-eye-outline"></i></span><span class="privacy-copy"><strong>Show profile in team directory</strong><small>Allow your profile to appear in the internal admin directory.</small></span><input v-model="form.privacy.listInDirectory" type="checkbox" class="toggle-control"></label>
        </div>
        <div class="privacy-footnote"><i class="mdi mdi-information-outline"></i>These preferences are stored locally in this UI until the profile privacy API is connected.</div>
        <div class="form-actions"><span v-if="notice" class="save-notice" role="status"><i class="mdi mdi-check-circle-outline"></i>{{ notice }}</span><button type="submit" class="primary-button"><i class="mdi mdi-content-save-outline"></i> Save preferences</button></div>
      </form>
    </section>
    <p class="local-note">Profile changes on this prototype are stored in this browser. Connect the forms to the account API to synchronize across devices.</p>
  </main>
</template>

<script>
const PROFILE_KEY = 'tanjil_admin_profile';
function readJson(key, fallback = {}) { try { return JSON.parse(window.localStorage.getItem(key) || 'null') || fallback; } catch (_) { return fallback; } }
function defaultProfile() {
  const authUser = readJson('user', {});
  const saved = readJson(PROFILE_KEY, {});
  const nameParts = String(saved.fullName || authUser.name || '').trim().split(/\s+/).filter(Boolean);
  return {
    ...saved,
    firstName: saved.firstName || nameParts.shift() || '',
    lastName: saved.lastName || nameParts.join(' '),
    email: saved.email || authUser.email || '',
    phone: saved.phone || authUser.phone || '',
    role: saved.role || authUser.role || 'Store Administrator',
    designation: saved.designation || '', department: saved.department || '',
    website: saved.website || '', city: saved.city || '', country: saved.country || 'Bangladesh', postalCode: saved.postalCode || '',
    description: saved.description || '', avatar: saved.avatar || '', cover: saved.cover || '',
    skills: Array.isArray(saved.skills) ? saved.skills : ['Catalog management', 'Inventory operations', 'Customer support'],
    portfolio: Array.isArray(saved.portfolio) ? saved.portfolio : [{ label: 'Store website', url: saved.website || '', icon: 'mdi mdi-web' }, { label: 'LinkedIn', url: '', icon: 'mdi mdi-linkedin' }],
    experience: Array.isArray(saved.experience) ? saved.experience : [],
    privacy: { showEmail: true, showPhone: false, listInDirectory: true, ...(saved.privacy || {}) },
  };
}
const GENERAL_SETTINGS_KEY = 'tt_full_general_settings';
const defaultStoreSettings = () => ({
  store: { name: 'Tanjil Traders', legalEntity: 'Tanjil Traders International Ltd.', tagline: 'Authentic Gadgets, Official Warranty', binNumber: '', supportEmail: 'support@tanjiltraders.com', hotline: '+880 1711 000 000', whatsapp: '+880 1811 000 000', hqAddress: 'Dhaka, Bangladesh', genuineWatermark: true, logoData: '', faviconData: '' },
  hardware: { imeiTracking: true, bundleSerials: true, autoGenBarcodes: true, barcodeFormat: 'Code 128 (Standard)', labelPrinterSize: '50mm × 25mm (Standard)' },
  warranty: { brandWarrantyMonths: 12, shopWarrantyMonths: 6, replacementWindowDays: 7, serviceEscalationEmail: 'rma@tanjiltraders.com', termsSummary: 'Official brand warranty applies. DOA replacement is subject to product inspection, invoice and stated policy.', requireUnboxingVideo: true, publicLookupPortal: true, offerExtendedWarranty: true },
  checkout: { maxCodLimit: 25000, minCartValue: 200, codSmsOtp: true, advanceDeliveryFeeRequired: true, autoBlockHighRto: false, paymentSessionTimeoutMins: 15, customerSelfCancelMins: 30, preOrderAdvanceType: 'Percentage (%)', preOrderAdvanceValue: 20, guestCheckout: true },
  inventory: { globalLowStockThreshold: 5, outOfStockAction: 'Show Out of Stock Badge (Default)', primaryFulfillmentHub: 'Central Logistics Warehouse (Multiplan)', blockNegativeStock: true, emailDigestToWarehouse: true },
  shipping: { integratedCouriers: ['Pathao Courier', 'Steadfast Courier', 'RedX Logistics'], insideDhakaRate: 70, subDhakaRate: 100, outsideDhakaRate: 130, freeShippingOver: 5000, fragilePackagingFee: 50, sameDayDhaka: true, sameDayCutoff: '14:00' },
  notifications: { smsProvider: 'SSL Wireless BD', smsSenderId: 'TANJIL', smsOnOrderPlace: true, smsOnDispatch: true, smsOnDelivered: true, whatsappWidgetEnabled: true, whatsappWidgetNumber: '+8801811000000', whatsappDefaultMsg: 'Hello Tanjil Traders, I would like to inquire about gadget availability.' },
  security: { maintenanceMode: false, twoFactorMandatory: true, ipWhitelistEnabled: false, sessionTimeoutMins: 60, maxFailedLogins: 5 },
});
function readStoreSettings() {
  try {
    const saved = JSON.parse(window.localStorage.getItem(GENERAL_SETTINGS_KEY) || 'null') || {};
    const defaults = defaultStoreSettings();
    return Object.fromEntries(Object.keys(defaults).map(section => [section, { ...defaults[section], ...(saved[section] || {}) }]));
  } catch (_) { return defaultStoreSettings(); }
}
// localStorage holds ~5 MB; base64 photos can exceed it, so writes must never throw.
function safeWrite(key, value) {
  try { window.localStorage.setItem(key, JSON.stringify(value)); return true; } catch (_) { return false; }
}

export default {
  name: 'ProfileEdit',
  data() {
    return {
      activeTab: 'personal',
      tabs: [
        { id: 'personal', label: 'Personal details', icon: 'mdi mdi-account-outline' },
        { id: 'password', label: 'Change password', icon: 'mdi mdi-lock-outline' },
        { id: 'experience', label: 'Experience', icon: 'mdi mdi-briefcase-outline' },
        { id: 'store-settings', label: 'Store & website', icon: 'mdi mdi-store-cog-outline' },
        { id: 'privacy', label: 'Privacy policy', icon: 'mdi mdi-shield-account-outline' },
      ],
      form: defaultProfile(), newSkill: '', notice: '',
      storeSettings: readStoreSettings(), settingsNotice: '', courierOptions: ['Pathao Courier', 'Steadfast Courier', 'RedX Logistics', 'Paperfly', 'Sundarban Courier'],
      password: { current: '', next: '', confirm: '' }, passwordMessage: '',
    };
  },
  computed: {
    fullNamePreview() { return [this.form.firstName, this.form.lastName].filter(Boolean).join(' ') || 'Store Administrator'; },
    initials() { return this.fullNamePreview.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase() || 'TT'; },
    completion() {
      const fields = [this.form.firstName, this.form.lastName, this.form.email, this.form.phone, this.form.designation, this.form.city, this.form.country, this.form.description, this.form.avatar];
      return Math.round(fields.filter(Boolean).length / fields.length * 100);
    },
    coverStyle() { return this.form.cover ? { backgroundImage: `linear-gradient(90deg, rgba(5,12,46,.42), rgba(24,38,93,.25)), url("${this.form.cover}")` } : {}; },
  },
  methods: {
    onStoreAssetChange(event, field) {
      const file = event.target.files && event.target.files[0];
      if (!file) return;
      if (file.size > 1.5 * 1024 * 1024) { this.settingsNotice = 'Please choose an image smaller than 1.5 MB.'; event.target.value = ''; return; }
      const reader = new FileReader();
      reader.onload = () => { this.storeSettings.store[field] = reader.result; this.settingsNotice = 'Asset preview updated. Save store settings to keep it in this browser.'; };
      reader.readAsDataURL(file);
    },
    saveStoreSettings() {
      if (!safeWrite(GENERAL_SETTINGS_KEY, this.storeSettings)) {
        this.settingsNotice = 'Could not save: the logo/favicon is too large for browser storage. Use smaller images.';
        return;
      }
      this.settingsNotice = 'Website/store settings saved in this browser.';
      window.setTimeout(() => { this.settingsNotice = ''; }, 4000);
    },
    resetStoreSettings() {
      this.storeSettings = defaultStoreSettings();
      this.settingsNotice = 'Store settings reset to the supplied defaults. Save to keep them.';
    },
    persistProfile(message = 'Profile saved in this browser.') {
      this.form.fullName = this.fullNamePreview;
      if (!safeWrite(PROFILE_KEY, this.form)) {
        this.notice = 'Could not save: the photo/cover is too large for browser storage. Use smaller images.';
        return;
      }
      this.notice = message;
      window.setTimeout(() => { this.notice = ''; }, 3500);
    },
    saveProfile() { this.persistProfile(); },
    resetForm() { this.form = defaultProfile(); this.notice = 'Unsaved changes were reset.'; },
    addPortfolio() { this.form.portfolio.push({ label: '', url: '', icon: 'mdi mdi-link-variant' }); },
    removePortfolio(index) { this.form.portfolio.splice(index, 1); },
    addSkill() { const value = this.newSkill.trim(); if (value && !this.form.skills.includes(value)) this.form.skills.push(value); this.newSkill = ''; },
    removeSkill(index) { this.form.skills.splice(index, 1); },
    addExperience() { this.form.experience.push({ title: '', company: 'Tanjil Traders', from: '', to: '', current: true, description: '' }); },
    removeExperience(index) { this.form.experience.splice(index, 1); },
    saveExperience() { this.persistProfile('Experience saved in this browser.'); },
    onImageChange(event, kind) {
      const file = event.target.files && event.target.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) { this.notice = 'Please choose an image file.'; return; }
      if (file.size > 2 * 1024 * 1024) { this.notice = 'Choose an image smaller than 2 MB.'; event.target.value = ''; return; }
      const reader = new FileReader();
      reader.onload = () => { this.form[kind] = reader.result; this.notice = kind === 'avatar' ? 'Profile photo preview updated.' : 'Cover preview updated.'; };
      reader.readAsDataURL(file);
    },
    submitPassword() {
      this.passwordMessage = '';
      if (!this.password.current || !this.password.next || !this.password.confirm) { this.passwordMessage = 'Complete all three password fields first.'; return; }
      if (this.password.next.length < 8) { this.passwordMessage = 'Use at least 8 characters for the new password.'; return; }
      if (this.password.next !== this.password.confirm) { this.passwordMessage = 'The new password and confirmation do not match.'; return; }
      this.passwordMessage = 'Password fields validated. Connect a secure server-side password endpoint to complete the change.';
      this.password = { current: '', next: '', confirm: '' };
    },
  },
};
</script>

<style scoped>
.profile-edit{--navy:#053d35;--navy-2:#0a5548;--orange:#0f9d6b;--green:#0f9d6b;--ink:#0f2a26;--muted:#6b7f7b;--line:#e3eae8;--wash:#f3f7f6;color:var(--ink);padding:8px 4px 28px;font-family:Poppins,"Segoe UI",sans-serif}.page-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:2px 2px 17px}.eyebrow{margin:0 0 5px;color:#8b94a6;font-size:9px;font-weight:800;letter-spacing:.14em}.page-heading h1{margin:0;color:var(--navy);font-size:23px;font-weight:800}.heading-subtitle{margin:5px 0 0;color:var(--muted);font-size:11px}.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--navy-2);text-decoration:none;font-size:11px;font-weight:700}.back-link:hover{color:var(--orange)}
.cover-card,.panel{overflow:hidden;border:1px solid var(--line);border-radius:10px;background:#fff;box-shadow:0 3px 12px rgba(5,12,46,.035)}.cover-preview{position:relative;display:flex;min-height:160px;align-items:flex-start;justify-content:flex-end;padding:14px 16px;background:linear-gradient(135deg,#053d35 0%,#0a5548 55%,#0b5f50 100%);background-position:center;background-size:cover;color:#fff}.cover-preview:after{position:absolute;inset:0;content:"";background:linear-gradient(90deg,rgba(5,12,46,.08),rgba(5,12,46,.2));pointer-events:none}.upload-button{position:relative;z-index:1;display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(255,255,255,.55);border-radius:6px;background:rgba(255,255,255,.95);color:var(--navy);padding:8px 10px;font-size:10px;font-weight:800;cursor:pointer}.upload-button input,.avatar-camera input{position:absolute;width:1px;height:1px;overflow:hidden;opacity:0}.cover-caption{position:absolute;z-index:1;right:18px;bottom:13px;color:rgba(255,255,255,.84);font-size:10px}.identity-preview{display:flex;align-items:center;gap:13px;padding:0 20px 16px}.avatar-upload-wrap{position:relative;display:grid;width:78px;height:78px;flex:none;place-items:center;margin-top:-36px;border:4px solid white;border-radius:50%;background:#ffb44e;color:var(--navy);box-shadow:0 2px 8px rgba(0,0,0,.15)}.avatar-preview{width:100%;height:100%;border-radius:50%;object-fit:cover}.avatar-fallback{font-size:22px;font-weight:900}.avatar-camera{position:absolute;right:-1px;bottom:0;display:grid;width:25px;height:25px;place-items:center;border:2px solid white;border-radius:50%;background:var(--navy-2);color:white;font-size:12px;cursor:pointer}.identity-copy{display:flex;min-width:0;flex:1;flex-direction:column;gap:3px}.identity-copy strong{color:var(--navy);font-size:14px}.identity-copy span{color:var(--muted);font-size:10px}.identity-actions{display:flex;align-items:center;gap:8px}.primary-button,.quiet-button,.outline-button{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:34px;padding:0 12px;border:1px solid transparent;border-radius:6px;font-family:Poppins,"Segoe UI",sans-serif;font-size:10px;font-weight:800;text-decoration:none;cursor:pointer;transition:.15s}.primary-button{background:var(--navy-2);color:white}.primary-button:hover{background:#263a7c}.quiet-button{border-color:var(--line);background:#fff;color:#70798a}.quiet-button:hover{border-color:#c9cfdb;color:var(--navy)}.outline-button{border-color:#dce1ed;background:#fff;color:var(--navy-2)}.outline-button:hover{border-color:#edb29b;color:var(--orange)}
.panel{margin-top:14px;padding:20px}.section-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.section-heading h2,.form-intro h2{margin:0;color:var(--ink);font-size:15px;font-weight:800}.section-subtitle,.form-intro p{margin:4px 0 0;color:var(--muted);font-size:10px;line-height:1.55}.completion-badge{padding:5px 8px;border-radius:6px;background:#eef1fb;color:var(--navy-2);font-size:10px;font-weight:900}.progress-track{height:8px;overflow:hidden;border-radius:99px;background:#edf0f5}.progress-track span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--orange),#f28b62);transition:width .2s}.helper{margin:8px 0 0;color:var(--muted);font-size:10px}.portfolio-list{display:grid;gap:9px}.portfolio-row{display:grid;grid-template-columns:32px minmax(120px,.75fr) minmax(220px,1.25fr) 30px;align-items:end;gap:10px}.social-mark{display:grid;width:30px;height:30px;place-items:center;border-radius:50%;background:#eef1fb;color:var(--navy-2);font-size:15px}.field-block{display:flex;min-width:0;flex-direction:column;gap:6px}.field-block>span{color:#424d61;font-size:10px;font-weight:700}.field-block b{color:var(--orange)}.field-block input,.field-block textarea{width:100%;min-height:36px;box-sizing:border-box;border:1px solid #dfe3eb;border-radius:6px;outline:0;background:#fff;padding:8px 10px;color:#283246;font:inherit;font-size:11px;transition:border .15s,box-shadow .15s}.field-block textarea{min-height:84px;resize:vertical;line-height:1.55}.field-block input:focus,.field-block textarea:focus{border-color:#8794bc;box-shadow:0 0 0 3px rgba(24,38,93,.08)}.field-block input::placeholder,.field-block textarea::placeholder{color:#a4aab6}.remove-button{display:grid;width:30px;height:30px;place-items:center;border:1px solid var(--line);border-radius:6px;background:#fff;color:#8b94a6;cursor:pointer}.remove-button:hover{border-color:#f3b9a3;background:#fff7f3;color:var(--orange)}.empty-links{padding:15px;border:1px dashed #dfe3eb;border-radius:7px;color:var(--muted);text-align:center;font-size:10px;line-height:1.6}
.editor-panel{padding:0}.editor-tabs{display:flex;gap:2px;overflow-x:auto;padding:0 20px;border-bottom:1px solid var(--line)}.editor-tabs button{display:inline-flex;flex:none;align-items:center;gap:7px;min-height:49px;padding:0 11px;border:0;border-bottom:2px solid transparent;background:transparent;color:#697286;font:inherit;font-size:10px;font-weight:700;white-space:nowrap;cursor:pointer}.editor-tabs button i{font-size:15px}.editor-tabs button.active{border-bottom-color:var(--orange);color:var(--navy-2)}.edit-form{padding:20px}.form-intro{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px}.required-note{color:#8d95a4;font-size:9px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px 18px}.field-span-2{grid-column:span 2}.field-hint{align-self:flex-end;color:#979eac;font-size:9px}.skill-editor{display:flex;min-height:38px;flex-wrap:wrap;align-items:center;gap:6px;padding:5px;border:1px solid #dfe3eb;border-radius:6px}.skill-editor:focus-within{border-color:#8794bc;box-shadow:0 0 0 3px rgba(24,38,93,.08)}.skill-editor input{min-width:130px;flex:1;border:0;box-shadow:none!important}.skill-token{display:inline-flex;align-items:center;gap:4px;padding:5px 7px;border-radius:5px;background:#eef1fb;color:#3f4f7d;font-size:9px;font-weight:700}.skill-token button{display:grid;width:14px;height:14px;place-items:center;border:0;background:transparent;color:inherit;font-size:11px;cursor:pointer}.form-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:20px;padding-top:15px;border-top:1px solid #edf0f5}.save-notice{display:inline-flex;align-items:center;gap:5px;margin-right:auto;color:#148359;font-size:10px}.password-fields{display:grid;max-width:600px;gap:13px}.security-notice,.privacy-footnote{display:flex;align-items:flex-start;gap:9px;margin:0 0 17px;padding:11px 12px;border:1px solid #e1e6f2;border-radius:7px;background:#f6f7fb;color:#52617f;font-size:10px;line-height:1.55}.security-notice i,.privacy-footnote i{color:var(--navy-2);font-size:16px}.form-message{margin:12px 0 0;color:#54617b;font-size:10px}.experience-list{display:grid;gap:12px}.experience-card{display:grid;grid-template-columns:34px 1fr 30px;align-items:start;gap:12px;padding:14px;border:1px solid var(--line);border-radius:8px}.experience-icon{display:grid;width:32px;height:32px;place-items:center;border-radius:8px;background:#fff2ec;color:var(--orange);font-size:17px}.experience-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.current-role{display:flex;align-items:center;gap:7px;color:#566176;font-size:10px}.current-role input,.toggle-control{accent-color:var(--navy-2)}.privacy-list{display:grid;margin:4px 0 17px}.privacy-row{display:flex;align-items:center;gap:11px;padding:14px 4px;border-bottom:1px solid #edf0f5;cursor:pointer}.privacy-row:last-child{border-bottom:0}.privacy-icon{display:grid;width:32px;height:32px;flex:none;place-items:center;border-radius:8px;background:#eef1fb;color:var(--navy-2);font-size:16px}.privacy-copy{display:flex;flex:1;flex-direction:column;gap:3px}.privacy-copy strong{color:#354056;font-size:11px}.privacy-copy small{color:var(--muted);font-size:9px;line-height:1.45}.toggle-control{width:16px;height:16px;flex:none}.local-note{margin:12px 4px 0;color:#929aaa;text-align:center;font-size:9px}

.brand-upload-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;padding:0 15px 13px}.brand-upload-card{display:flex;align-items:center;gap:10px;min-width:0;padding:10px;border:1px dashed #b7cac5;border-radius:7px;background:#fbfdfc;cursor:pointer}.brand-preview,.favicon-preview{display:grid;width:42px;height:42px;flex:none;place-items:center;border-radius:8px;background:#e3f6ee;color:#0a5548;font-size:22px;overflow:hidden}.favicon-preview{width:34px;height:34px;border-radius:7px}.brand-preview img,.favicon-preview img{width:100%;height:100%;object-fit:contain}.brand-upload-copy{display:flex;min-width:0;flex-direction:column;gap:3px}.brand-upload-copy strong{color:#0f2a26;font-size:9px}.brand-upload-copy small{color:#6b7f7b;font-size:8px}.brand-upload-copy input{max-width:100%;font-size:8px;color:#6b7f7b}.security-fields{padding:0 15px 14px}
.local-settings-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:99px;background:#e3f6ee;color:#0f7a4d;font-size:9px;font-weight:800;white-space:nowrap}.store-setting-groups{display:grid;gap:14px}.setting-group{--group-accent:#0f9d6b;overflow:hidden;border:1px solid var(--line);border-radius:9px;background:#fff}.setting-group--green{--group-accent:#0f9d6b}.setting-group--teal{--group-accent:#0f766e}.setting-group--blue{--group-accent:#2f5be7}.setting-group--amber{--group-accent:#d97706}.setting-group--purple{--group-accent:#7c3aed}.setting-group-head{display:flex;align-items:center;gap:10px;padding:12px 15px;border-bottom:1px solid var(--line);border-left:3px solid var(--group-accent);background:linear-gradient(90deg,color-mix(in srgb,var(--group-accent) 8%,white),#fff)}.setting-group-icon{display:grid;width:31px;height:31px;flex:none;place-items:center;border-radius:8px;background:color-mix(in srgb,var(--group-accent) 12%,white);color:var(--group-accent);font-size:16px}.setting-group-head h3{margin:0;color:#0f2a26;font-size:12px;font-weight:700}.setting-group-head p{margin:3px 0 0;color:#6b7f7b;font-size:9px;line-height:1.45}.setting-group>.form-grid,.setting-group>.setting-toggles{padding:14px 15px}.setting-group>.setting-toggles{padding-top:0}.setting-group .field-block select{width:100%;min-height:36px;box-sizing:border-box;border:1px solid #dfe3eb;border-radius:6px;outline:0;background:white;padding:8px 10px;color:#283246;font:inherit;font-size:11px}.setting-group .field-block select:focus{border-color:#8794bc;box-shadow:0 0 0 3px rgba(15,42,38,.08)}.setting-group .field-block select[multiple]{height:auto;min-height:112px}.setting-toggles{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.setting-toggle{display:flex;align-items:flex-start;gap:9px;min-width:0;padding:10px;border:1px solid #e3eae8;border-radius:7px;background:#f6faf9;cursor:pointer}.setting-toggle input{width:15px;height:15px;flex:none;margin:2px 0 0;accent-color:#0f9d6b}.setting-toggle span{display:flex;min-width:0;flex-direction:column;gap:3px}.setting-toggle strong{color:#0f2a26;font-size:9px;line-height:1.4}.setting-toggle small{color:#6b7f7b;font-size:8px;line-height:1.4}.setting-toggle--warning{border-color:#f1d9b2;background:#fffaf0}.setting-toggle--warning input{accent-color:#d97706}.settings-disclaimer{display:flex;align-items:flex-start;gap:6px;margin:13px 0 0;color:#6b7f7b;font-size:9px;line-height:1.5}.settings-disclaimer i{flex:none;color:#0f766e;font-size:13px}

@media(max-width:760px){.portfolio-row{grid-template-columns:30px 1fr 30px}.portfolio-row .field-block:nth-child(3){grid-column:2;grid-row:2}.portfolio-row .remove-button{grid-column:3;grid-row:1}.identity-preview{flex-wrap:wrap;padding:0 15px 14px}.identity-actions{width:100%;justify-content:flex-end}.cover-preview{min-height:135px}.experience-fields{grid-template-columns:1fr}.experience-fields .field-span-2{grid-column:span 1}}
@media(max-width:540px){.brand-upload-grid{grid-template-columns:1fr}.profile-edit{padding:0 0 22px}.page-heading{align-items:flex-start;flex-direction:column}.page-heading h1{font-size:20px}.cover-preview{min-height:120px}.identity-preview{gap:10px}.avatar-upload-wrap{width:64px;height:64px;margin-top:-28px}.identity-actions{margin-top:3px}.identity-actions .primary-button,.identity-actions .quiet-button{flex:1}.panel{padding:15px}.editor-panel{padding:0}.editor-tabs{padding:0 8px}.editor-tabs button{padding:0 9px;font-size:9px}.edit-form{padding:15px}.form-grid{grid-template-columns:1fr;gap:13px}.field-span-2{grid-column:span 1}.section-heading{align-items:flex-start}.section-heading>.outline-button{min-height:30px;padding:0 8px;font-size:9px}.form-actions{flex-wrap:wrap}.save-notice{width:100%;margin-bottom:6px}.experience-card{grid-template-columns:28px 1fr 28px;padding:10px;gap:8px}.cover-caption{left:13px;right:auto;max-width:68%;font-size:9px}}
</style>