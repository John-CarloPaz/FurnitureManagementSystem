import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ArrowLeft, ShoppingBag, Check } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { ModelViewer } from '@/components/orders/model-viewer'
import { useShopProduct } from '@/hooks/use-storefront'
import { useCart } from '@/stores/cart'
import { fileUrl } from '@/lib/api'
import { peso } from '@/lib/status'

export function StorefrontProduct() {
  const { id } = useParams()
  const { data: product, isLoading } = useShopProduct(Number(id))
  const add = useCart((s) => s.add)
  const [added, setAdded] = useState(false)

  if (isLoading) return <p className="py-16 text-center text-muted">Loading…</p>
  if (!product) {
    return <p className="py-16 text-center text-muted">Product not found. <Link to="/shop" className="text-walnut">Back to shop</Link></p>
  }

  const onAdd = () => {
    add({ id: product.id, name: product.name, base_price: product.base_price })
    setAdded(true)
    setTimeout(() => setAdded(false), 1500)
  }

  const specs: [string, string | null][] = [
    ['Category', product.category],
    ['Material', product.material],
    ['Wood type', product.wood_type],
    ['Finish', product.finish],
    ['Dimensions', product.dimensions_label],
    ['Lead time', product.lead_time_days ? `${product.lead_time_days} days` : null],
  ]

  return (
    <div className="space-y-6">
      <Link to="/shop" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> Back to shop
      </Link>

      <div className="grid gap-8 lg:grid-cols-2">
        <div>
          {product.model_url ? (
            <ModelViewer url={fileUrl(product.model_url)} format={product.model_format ?? 'glb'} />
          ) : product.image_url ? (
            <img src={product.image_url} alt={product.name} className="aspect-square w-full rounded-[var(--radius-md)] object-cover" />
          ) : (
            <div className="flex aspect-square items-center justify-center rounded-[var(--radius-md)] bg-surface-2 text-muted">No preview</div>
          )}
        </div>

        <div className="space-y-5">
          {product.category && <p className="text-xs uppercase tracking-wide text-muted">{product.category}</p>}
          <h1 className="font-display text-3xl text-fg">{product.name}</h1>
          <p className="font-display text-3xl text-walnut">{peso(product.base_price)}</p>
          {product.description && <p className="text-muted">{product.description}</p>}

          <dl className="grid grid-cols-1 gap-y-2 border-t border-border pt-4 text-sm sm:grid-cols-2 sm:gap-x-8">
            {specs.filter(([, v]) => v).map(([k, v]) => (
              <div key={k} className="flex justify-between border-b border-border pb-2">
                <dt className="text-muted">{k}</dt>
                <dd className="text-fg">{v}</dd>
              </div>
            ))}
          </dl>

          <Button size="pill" className="w-full" onClick={onAdd}>
            {added ? <><Check size={16} /> Added to cart</> : <><ShoppingBag size={16} /> Add to cart</>}
          </Button>
        </div>
      </div>
    </div>
  )
}
