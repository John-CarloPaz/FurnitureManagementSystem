/** Order FULFILLMENT states → label + color token. Mirrors docs/design/FSM.md §2. */
export type OrderState =
  | 'PLACED'
  | 'CONFIRMED'
  | 'IN_PRODUCTION'
  | 'QUALITY_CHECK'
  | 'REWORK'
  | 'READY_FOR_DELIVERY'
  | 'OUT_FOR_DELIVERY'
  | 'DELIVERED'
  | 'COMPLETED'
  | 'CANCELLED'

export const STATUS_META: Record<OrderState, { label: string; color: string }> = {
  PLACED: { label: 'Placed', color: 'var(--status-neutral)' },
  CONFIRMED: { label: 'Confirmed', color: 'var(--status-warning)' },
  IN_PRODUCTION: { label: 'In Production', color: 'var(--status-active)' },
  QUALITY_CHECK: { label: 'Quality Check', color: 'var(--status-info)' },
  REWORK: { label: 'Rework', color: 'var(--status-alert)' },
  READY_FOR_DELIVERY: { label: 'Ready for Delivery', color: 'var(--status-progress)' },
  OUT_FOR_DELIVERY: { label: 'Out for Delivery', color: 'var(--status-progress)' },
  DELIVERED: { label: 'Delivered', color: 'var(--status-done)' },
  COMPLETED: { label: 'Completed', color: 'var(--status-success)' },
  CANCELLED: { label: 'Cancelled', color: 'var(--status-danger)' },
}

/** Product catalog statuses. Mirrors docs/design/FSM.md §1. */
export type ProductStatus = 'DRAFT' | 'PUBLISHED' | 'ARCHIVED'

export const PRODUCT_STATUS_META: Record<ProductStatus, { label: string; color: string }> = {
  DRAFT: { label: 'Draft', color: 'var(--status-neutral)' },
  PUBLISHED: { label: 'Published', color: 'var(--status-success)' },
  ARCHIVED: { label: 'Archived', color: 'var(--status-neutral)' },
}

/** Peso formatting for prices/totals. */
export function peso(value: string | number): string {
  const n = typeof value === 'string' ? parseFloat(value) : value
  return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
