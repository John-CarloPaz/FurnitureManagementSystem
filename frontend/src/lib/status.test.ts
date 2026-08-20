import { describe, it, expect } from 'vitest'
import { STATUS_META, PRODUCT_STATUS_META, peso } from './status'

describe('status metadata', () => {
  it('labels order states', () => {
    expect(STATUS_META.IN_PRODUCTION.label).toBe('In Production')
    expect(STATUS_META.OUT_FOR_DELIVERY.label).toBe('Out for Delivery')
    expect(STATUS_META.COMPLETED.label).toBe('Completed')
  })

  it('labels product statuses', () => {
    expect(PRODUCT_STATUS_META.PUBLISHED.label).toBe('Published')
  })
})

describe('peso', () => {
  it('formats numbers with the peso sign and 2 decimals', () => {
    expect(peso(1000)).toBe('₱1,000.00')
  })

  it('parses string amounts', () => {
    expect(peso('2500.5')).toBe('₱2,500.50')
  })
})
