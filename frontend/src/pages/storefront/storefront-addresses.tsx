import { useState } from 'react'
import { Link } from 'react-router-dom'
import { MapPin, Plus, Pencil, Trash2, Star } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { AddressForm } from '@/components/storefront/address-form'
import { useAuth } from '@/hooks/use-auth'
import { useAddresses, useCreateAddress, useDeleteAddress, useUpdateAddress } from '@/hooks/use-addresses'
import type { AddressInput, DeliveryAddress } from '@/lib/addresses-api'

export function StorefrontAddresses() {
  const { isAuthenticated } = useAuth()
  const { data: addresses = [], isLoading } = useAddresses()
  const create = useCreateAddress()
  const update = useUpdateAddress()
  const remove = useDeleteAddress()
  const [editing, setEditing] = useState<DeliveryAddress | 'new' | null>(null)

  if (!isAuthenticated) {
    return (
      <div className="mx-auto max-w-2xl">
        <Card className="space-y-3 text-center text-muted">
          <p>Sign in to manage your saved delivery addresses.</p>
          <Link to="/login?next=/shop/addresses" className="inline-block rounded-full bg-walnut px-4 py-1.5 text-sm font-medium text-walnut-foreground hover:bg-walnut-hover">Sign in</Link>
        </Card>
      </div>
    )
  }

  const onCreate = (payload: AddressInput) =>
    create.mutate(payload, { onSuccess: () => setEditing(null) })

  const onUpdate = (id: number, payload: AddressInput) =>
    update.mutate({ id, payload }, { onSuccess: () => setEditing(null) })

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="font-display text-3xl text-fg">My addresses</h1>
        {editing === null && (
          <Button size="pill" onClick={() => setEditing('new')}>
            <Plus size={16} /> Add address
          </Button>
        )}
      </div>

      {editing === 'new' && (
        <Card className="space-y-4">
          <h2 className="font-display text-lg text-fg">New address</h2>
          <AddressForm submitting={create.isPending} onSubmit={onCreate} onCancel={() => setEditing(null)} />
        </Card>
      )}

      {isLoading ? (
        <p className="py-10 text-center text-muted">Loading…</p>
      ) : !addresses.length && editing !== 'new' ? (
        <Card className="text-center text-muted">No saved addresses yet. Add one to reuse it on your next order.</Card>
      ) : (
        <div className="space-y-3">
          {addresses.map((a) =>
            editing !== 'new' && typeof editing === 'object' && editing?.id === a.id ? (
              <Card key={a.id} className="space-y-4">
                <h2 className="font-display text-lg text-fg">Edit address</h2>
                <AddressForm
                  initial={a}
                  submitLabel="Save changes"
                  submitting={update.isPending}
                  onSubmit={(payload) => onUpdate(a.id, payload)}
                  onCancel={() => setEditing(null)}
                />
              </Card>
            ) : (
              <Card key={a.id} className="flex items-start justify-between gap-4">
                <div className="flex gap-3">
                  <MapPin size={18} className="mt-0.5 shrink-0 text-walnut" />
                  <div className="space-y-1">
                    <p className="flex items-center gap-2 font-medium text-fg">
                      {a.label || 'Address'}
                      {a.is_default && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-[var(--amber-soft)] px-2 py-0.5 text-[10px] font-medium text-walnut">
                          <Star size={10} /> Default
                        </span>
                      )}
                    </p>
                    <p className="text-sm text-muted">{a.formatted}</p>
                    {a.notes && <p className="text-xs text-muted">Note: {a.notes}</p>}
                  </div>
                </div>
                <div className="flex shrink-0 gap-1">
                  <button onClick={() => setEditing(a)} aria-label="Edit" className="rounded-full p-2 text-muted hover:bg-surface-2 hover:text-fg"><Pencil size={15} /></button>
                  <button onClick={() => remove.mutate(a.id)} aria-label="Delete" className="rounded-full p-2 text-muted hover:bg-surface-2 hover:text-[var(--status-danger)]"><Trash2 size={15} /></button>
                </div>
              </Card>
            ),
          )}
        </div>
      )}
    </div>
  )
}
