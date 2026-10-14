import { Link } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { useTerms } from '@/hooks/use-storefront'

export function StorefrontTerms() {
  const { data: terms = '', isLoading } = useTerms()
  const paragraphs = terms.split(/\n{2,}/).map((p) => p.trim()).filter(Boolean)

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <Link to="/shop" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-fg">
        <ArrowLeft size={15} /> Back to shop
      </Link>
      <h1 className="font-display text-3xl text-fg">Terms &amp; Conditions</h1>

      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : paragraphs.length === 0 ? (
        <Card className="text-center text-muted">Our terms &amp; conditions are being finalized. Please check back soon.</Card>
      ) : (
        <Card className="space-y-4 leading-relaxed text-fg">
          {paragraphs.map((p, i) => (
            <p key={i} className="whitespace-pre-line text-[15px]">{p}</p>
          ))}
        </Card>
      )}
    </div>
  )
}
