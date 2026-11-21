/*
 * Labels, colours and icons for order, payment and return states,
 * shared by the storefront (tracking, account) and the admin.
 * Labels are English; pages pass them through $t().
 */
export const ORDER_STATUS = {
    pending: { label: "Pending", tone: "amber", icon: "mdi-clock-outline", hint: "We received your order and will call you to confirm it." },
    confirmed: { label: "Confirmed", tone: "blue", icon: "mdi-check-circle-outline", hint: "Your order is confirmed." },
    processing: { label: "Packing", tone: "blue", icon: "mdi-package-variant", hint: "We are packing your order." },
    shipped: { label: "Shipped", tone: "blue", icon: "mdi-truck-fast-outline", hint: "Your parcel is on the way." },
    delivered: { label: "Delivered", tone: "green", icon: "mdi-home-check-outline", hint: "Delivered. Enjoy your purchase!" },
    cancelled: { label: "Cancelled", tone: "red", icon: "mdi-close-circle-outline", hint: "This order was cancelled." },
    returned: { label: "Returned", tone: "red", icon: "mdi-keyboard-return", hint: "This order was returned." },
};

/** The normal path, shown as steps on tracking pages. */
export const ORDER_STEPS = ["pending", "confirmed", "processing", "shipped", "delivered"];

export const PAYMENT_METHOD = { cod: "Cash on delivery", bkash: "bKash", nagad: "Nagad" };

export const PAYMENT_STATUS = {
    unpaid: { label: "Unpaid", tone: "amber" },
    paid: { label: "Paid", tone: "green" },
    refunded: { label: "Refunded", tone: "red" },
};

export const RETURN_STATUS = {
    requested: { label: "Requested", tone: "amber", icon: "mdi-email-arrow-left-outline" },
    approved: { label: "Approved", tone: "blue", icon: "mdi-check" },
    received: { label: "Received", tone: "blue", icon: "mdi-package-down" },
    refunded: { label: "Refunded", tone: "green", icon: "mdi-cash-refund" },
    rejected: { label: "Rejected", tone: "red", icon: "mdi-close" },
};

export const RETURN_REASONS = {
    damaged: "Arrived damaged",
    wrong_item: "Wrong item sent",
    not_as_described: "Not as described",
    not_working: "Not working",
    changed_mind: "Changed my mind",
    other: "Other",
};

export const REFUND_METHODS = { cash: "Cash", bkash: "bKash", nagad: "Nagad", bank: "Bank transfer" };

export const MOVEMENT_REASON = {
    sale: { label: "Sale", tone: "blue", icon: "mdi-cart-outline" },
    restock: { label: "Stock in", tone: "green", icon: "mdi-package-down" },
    adjustment: { label: "Adjustment", tone: "amber", icon: "mdi-tune-variant" },
    return: { label: "Return", tone: "green", icon: "mdi-keyboard-return" },
    cancel: { label: "Order cancelled", tone: "grey", icon: "mdi-undo" },
};

/** Valid Bangladeshi mobile number (01XXXXXXXXX, +88 optional). */
export const isBdPhone = (v) => /^(?:\+?88)?01[3-9]\d{8}$/.test(String(v || "").replace(/[\s-]/g, ""));

/** Select items [{ value, title }] from a label map, translated. */
export const options = (map, t) => Object.entries(map).map(([value, v]) => ({ value, title: t(typeof v === "string" ? v : v.label) }));
