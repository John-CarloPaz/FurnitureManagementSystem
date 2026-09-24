import { useState } from 'react'
import { Search, Boxes } from 'lucide-react'
import { ShopProductCard } from '@/components/storefront/shop-product-card'
import { useShopProducts } from '@/hooks/use-storefront'

export function StorefrontHome() {
  const [q, setQ] = useState('')
  const { data, isLoading } = useShopProducts(q.trim() ? { q: q.trim() } : undefined)
  const products = data?.data ?? []

  return (
    <div className="space-y-10">
      <section className="overflow-hidden rounded-[var(--radius-lg)] border border-border bg-gradient-to-br from-[var(--amber-soft)] to-[var(--surface)] px-6 py-12 text-center sm:px-12 sm:py-16">
        <p className="mb-2 inline-flex items-center gap-1.5 rounded-full bg-surface px-3 py-1 text-xs font-medium text-walnut">
          <Boxes size={13} /> View every piece in interactive 3D
        </p>
        <h1 className="mx-auto max-w-2xl font-display text-4xl leading-tight text-fg sm:text-5xl">
          Furniture you can walk around before it's built.
        </h1>
        <p className="mx-auto mt-3 max-w-xl text-muted">
          Handcrafted by Cedarside. Browse the collection, spin each model in 3D, and track your order from the workshop to your door.
        </p>
      </section>

      <section className="space-y-5">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h2 className="font-display text-2xl text-fg">The collection</h2>
          <div className="relative sm:w-72">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder="Search furniture…"
              className="w-full rounded-full border border-border bg-surface py-2 pl-9 pr-3 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]"
            />
          </div>
        </div>

        {isLoading ? (
          <p className="py-16 text-center text-muted">Loading the collection…</p>
        ) : !products.length ? (
          <p className="py-16 text-center text-muted">No products found{q ? ` for “${q}”` : ' yet'}.</p>
        ) : (
          <div className="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            {products.map((p) => <ShopProductCard key={p.id} product={p} />)}
          </div>
        )}
      </section>
    </div>
  )
}
