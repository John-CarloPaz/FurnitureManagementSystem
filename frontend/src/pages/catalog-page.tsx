import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { Plus, ShoppingCart } from 'lucide-react'
import { Card, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ProductCard } from '@/components/catalog/product-card'
import { ProductForm } from '@/components/catalog/product-form'
import { useCreateProduct, useProducts } from '@/hooks/use-products'
import { useAuth } from '@/hooks/use-auth'
import { useCart } from '@/stores/cart'

export function CatalogPage() {
  const navigate = useNavigate()
  const { has } = useAuth()
  const { data, isLoading } = useProducts()
  const create = useCreateProduct()
  const cartCount = useCart((s) => s.count())
  const [open, setOpen] = useState(false)
  const canManage = has('products.create')

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="font-display text-3xl text-fg">Catalog</h1>
          <p className="mt-1 text-muted">
            {canManage ? 'Manage and publish your product catalog.' : 'Browse our furniture collection.'}
          </p>
        </div>
        <div className="flex items-center gap-2">
          {cartCount > 0 && (
            <Button variant="secondary" size="pill" onClick={() => navigate('/cart')}>
              <ShoppingCart size={16} /> Order ({cartCount})
            </Button>
          )}
          {canManage && (
            <Button size="pill" onClick={() => setOpen((o) => !o)}>
              <Plus size={16} /> New Product
            </Button>
          )}
        </div>
      </div>

      {open && canManage && (
        <Card className="space-y-4">
          <CardTitle>New product</CardTitle>
          <ProductForm
            submitLabel="Create product"
            submitting={create.isPending}
            onCancel={() => setOpen(false)}
            onSubmit={(payload) => create.mutate(payload, { onSuccess: (p) => navigate(`/catalog/${p.id}`) })}
          />
        </Card>
      )}

      {isLoading ? (
        <p className="p-8 text-center text-muted">Loading catalog…</p>
      ) : !data?.data.length ? (
        <Card className="text-center text-muted">
          No products yet.{' '}
          {canManage && <Link to="#" onClick={() => setOpen(true)} className="text-walnut">Create one</Link>}
        </Card>
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {data.data.map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      )}
    </div>
  )
}
