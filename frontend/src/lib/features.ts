/**
 * Feature flags for work that's built but not yet enabled in the UI.
 * Flip to `true` to switch a feature back on.
 */

// Customer-facing payment (checkout method picker + "Pay now" panel). Hidden for now
// — orders still default to Cash on Delivery on the backend.
export const PAYMENTS_ENABLED = false
