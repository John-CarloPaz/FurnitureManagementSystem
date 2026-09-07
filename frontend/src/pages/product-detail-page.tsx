import { useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { ArrowLeft, Plus, ShoppingCart, Pencil } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ProductModelPanel } from '@/components/catalog/product-model-panel'
import { ProductForm } from '@/components/catalog/product-form'
import { useChangeProductStatus, useProduct, useUpdateProduct } from '@/hooks/use-products'
import { useAuth } from '@/hooks/use-auth'
import { useCart } from '@/stores/cart'
import { PRODUCT_STATUS_META, peso, type ProductStatus } from '@/lib/status'

const STATUS_ACTION: Record<ProductStatus, 'publish' | 'unpublish' | 'archive'> = {
  PUBLISHED: 'publish',
  DRAFT: 'unpublish',
  ARCHIVED: 'archive',
}

export function ProductDetailPage() {
  const { id } = useParams()
  const productId = Number(id)
  const navigate = useNavigate()
  const { data: product, isLoading } = useProduct(productId)
  const changeStatus = useChangeProductStatus(productId)
  const update = useUpdateProduct(productId)
  const add = useCart((s) => s.add)
  const cartCount = useCart((s) => s.count())
  const { has } = useAuth()
  const [editing, setEditing] = useState(false)

  if (isLoading) return <div className="p-8 text-center text-muted">Loading…</div>
  if (!product) return <div className="p-8 text-center text-muted">Product not found.</div>

  const meta = PRODUCT_STATUS_META[product.status]
  const canManage = has('products.update')
  const canPublish = has('products.publish')
  const canOrder = has('orders.place') && product.status === 'PUBLISHED'

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div className="flex items-center justify-between">
        <Link to="/catalog" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
          <ArrowLeft size={15} /> Back to catalog
        </Link>
        {cartCount > 0 && (
          <Button variant="secondary" size="sm" onClick={() => navigate('/cart')}>
            <ShoppingCart size={15} /> Order ({cartCount})
          </Button>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-5">
        <div className="lg:col-span-3">
          <Card>
            <h2 className="mb-4 font-display text-lg text-fg">3D Model</h2>
            <ProductModelPanel product={product} />
          </Card>
        </div>

        <div className="space-y-6 lg:col-span-2">
          <Card className="space-y-3">
            {editing ? (
              <ProductForm
                initial={product}
                submitLabel="Save changes"
                submitting={update.isPending}
                onCancel={() => setEditing(false)}
                onSubmit={(payload) => update.mutate(payload, { onSuccess: () => setEditing(false) })}
              />
            ) : (
              <>
                <div className="flex items-start justify-between gap-2">
                  <h1 className="font-display text-2xl text-fg">{product.name}</h1>
                  {has('products.viewAny') && (
                    <span
                      className="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                      style={{ backgroundColor: `color-mix(in srgb, ${meta.color} 14%, transparent)`, color: meta.color }}
                    >
                      {meta.label}
                    </span>
                  )}
                </div>
                <p className="font-display text-2xl text-walnut">{peso(product.base_price)}</p>
                {product.description && <p className="text-sm text-muted">{product.description}</p>}
                <dl className="space-y-1 text-sm">
                  {[
                    ['Category', product.category],
                    ['Material', product.material],
                    ['Wood type', product.wood_type],
                    ['Finish', product.finish],
                    ['Dimensions', product.dimensions_label],
                    ['Weight', product.weight_kg ? `${product.weight_kg} kg` : null],
                    ['Lead time', product.lead_time_days != null ? `${product.lead_time_days} days` : null],
                  ]
                    .filter(([, val]) => val)
                    .map(([label, val]) => (
                      <div key={label} className="flex justify-between gap-4">
                        <dt className="text-muted">{label}</dt>
                        <dd className="text-right text-fg">{val}</dd>
                      </div>
                    ))}
                </dl>

                <div className="flex gap-2 pt-1">
                  {canOrder && (
                    <Button className="flex-1" size="pill" onClick={() => add(product)}>
                      <Plus size={16} /> Add to order
                    </Button>
                  )}
                  {canManage && (
                    <Button variant="secondary" size="pill" onClick={() => setEditing(true)}>
                      <Pencil size={15} /> Edit
                    </Button>
                  )}
                </div>
              </>
            )}
          </Card>

          {(canManage || canPublish) && (
            <Card className="space-y-2">
              <p className="text-xs font-medium uppercase tracking-wide text-muted">Catalog actions</p>
              <div className="flex flex-wrap gap-2">
                {canPublish &&
                  product.allowed_status_transitions.map((to) => (
                    <Button
                      key={to}
                      size="sm"
                      variant={to === 'PUBLISHED' ? 'primary' : 'secondary'}
                      disabled={changeStatus.isPending}
                      onClick={() => changeStatus.mutate(STATUS_ACTION[to])}
                    >
                      {to === 'PUBLISHED' ? 'Publish' : to === 'DRAFT' ? 'Unpublish' : 'Archive'}
                    </Button>
                  ))}
              </div>
              {changeStatus.isError && (
                <p className="text-sm text-[var(--status-danger)]">
                  Can't publish yet — a product needs a 3D model and a price above 0.
                </p>
              )}
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}
