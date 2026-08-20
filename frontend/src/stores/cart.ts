import { create } from 'zustand'
import type { Product } from '@/lib/products-api'

export interface CartItem {
  product: Product
  quantity: number
}

interface CartState {
  items: CartItem[]
  add: (product: Product) => void
  setQty: (productId: number, quantity: number) => void
  remove: (productId: number) => void
  clear: () => void
  total: () => number
  count: () => number
}

export const useCart = create<CartState>((set, get) => ({
  items: [],
  add: (product) =>
    set((s) => {
      const existing = s.items.find((i) => i.product.id === product.id)
      if (existing) {
        return { items: s.items.map((i) => (i.product.id === product.id ? { ...i, quantity: i.quantity + 1 } : i)) }
      }
      return { items: [...s.items, { product, quantity: 1 }] }
    }),
  setQty: (productId, quantity) =>
    set((s) => ({
      items: s.items.map((i) => (i.product.id === productId ? { ...i, quantity: Math.max(1, quantity) } : i)),
    })),
  remove: (productId) => set((s) => ({ items: s.items.filter((i) => i.product.id !== productId) })),
  clear: () => set({ items: [] }),
  total: () => get().items.reduce((sum, i) => sum + parseFloat(i.product.base_price) * i.quantity, 0),
  count: () => get().items.reduce((sum, i) => sum + i.quantity, 0),
}))
