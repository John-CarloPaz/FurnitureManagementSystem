import { create } from 'zustand'

/** Minimal product shape the cart needs — satisfied by both the CRM Product and the storefront product. */
export interface CartProduct {
  id: number
  name: string
  base_price: string
}

export interface CartVariation {
  id: number
  name: string
  color_hex: string
}

export interface CartItem {
  product: CartProduct
  variation?: CartVariation
  quantity: number
}

/** A product + chosen variation make one line; the same product in two finishes is two lines. */
const keyFor = (productId: number, variationId?: number) => `${productId}:${variationId ?? 0}`
export const cartItemKey = (i: CartItem) => keyFor(i.product.id, i.variation?.id)

interface CartState {
  items: CartItem[]
  add: (product: CartProduct, variation?: CartVariation) => void
  setQty: (key: string, quantity: number) => void
  remove: (key: string) => void
  clear: () => void
  total: () => number
  count: () => number
}

export const useCart = create<CartState>((set, get) => ({
  items: [],
  add: (product, variation) =>
    set((s) => {
      const key = keyFor(product.id, variation?.id)
      if (s.items.some((i) => cartItemKey(i) === key)) {
        return { items: s.items.map((i) => (cartItemKey(i) === key ? { ...i, quantity: i.quantity + 1 } : i)) }
      }
      return { items: [...s.items, { product, variation, quantity: 1 }] }
    }),
  setQty: (key, quantity) =>
    set((s) => ({
      items: s.items.map((i) => (cartItemKey(i) === key ? { ...i, quantity: Math.max(1, quantity) } : i)),
    })),
  remove: (key) => set((s) => ({ items: s.items.filter((i) => cartItemKey(i) !== key) })),
  clear: () => set({ items: [] }),
  total: () => get().items.reduce((sum, i) => sum + parseFloat(i.product.base_price) * i.quantity, 0),
  count: () => get().items.reduce((sum, i) => sum + i.quantity, 0),
}))
