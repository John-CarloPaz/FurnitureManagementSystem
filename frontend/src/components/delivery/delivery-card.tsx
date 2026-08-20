import { useState } from 'react'
import { Truck, MapPin, Camera, PackageCheck } from 'lucide-react'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useDispatchDelivery, useLogLocation, useRecordProof } from '@/hooks/use-delivery'
import type { DeliveryAssignment } from '@/lib/delivery-api'

const DELIVERY_COLOR: Record<string, string> = {
  assigned: 'var(--status-neutral)',
  out_for_delivery: 'var(--status-progress)',
  delivered: 'var(--status-success)',
}

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function DeliveryCard({ assignment, isDriver }: { assignment: DeliveryAssignment; isDriver: boolean }) {
  const dispatch = useDispatchDelivery()
  const logLocation = useLogLocation()
  const proof = useRecordProof()
  const [location, setLocation] = useState('')
  const [recipient, setRecipient] = useState('')

  const color = DELIVERY_COLOR[assignment.status] ?? 'var(--status-neutral)'
  const canAct = isDriver

  const onProof = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return
    const form = new FormData()
    form.append('photo', file)
    if (recipient) form.append('recipient_name', recipient)
    proof.mutate({ id: assignment.id, form })
    e.target.value = ''
  }

  return (
    <Card className="space-y-3">
      <div className="flex items-start justify-between gap-2">
        <div>
          <p className="font-mono text-xs text-muted">{assignment.order?.order_number}</p>
          <p className="text-sm text-fg">{assignment.order?.customer ?? '—'}</p>
          {assignment.batch_label && <p className="text-xs text-muted">Batch: {assignment.batch_label}</p>}
        </div>
        <span
          className="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
          style={{ backgroundColor: `color-mix(in srgb, ${color} 14%, transparent)`, color }}
        >
          {assignment.status_label}
        </span>
      </div>

      <p className="text-xs text-muted">Driver: <span className="text-fg">{assignment.driver ?? 'Unassigned'}</span></p>

      {/* Event timeline */}
      {assignment.events && assignment.events.length > 0 && (
        <ol className="space-y-1.5 border-l border-border pl-3 text-xs">
          {assignment.events.map((ev) => (
            <li key={ev.id} className="text-muted">
              <span className="text-fg">{ev.type_label}</span>
              {ev.manual_location && ` · ${ev.manual_location}`}
              {' · '}{new Date(ev.created_at).toLocaleString()}
            </li>
          ))}
        </ol>
      )}

      {assignment.proof && (
        <a href={assignment.proof.photo_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1.5 text-sm text-walnut">
          <Camera size={14} /> Proof of delivery{assignment.proof.recipient_name ? ` · ${assignment.proof.recipient_name}` : ''}
        </a>
      )}

      {/* Driver actions */}
      {canAct && assignment.status === 'assigned' && (
        <Button size="sm" disabled={dispatch.isPending} onClick={() => dispatch.mutate({ id: assignment.id, manual_location: location || undefined })}>
          <Truck size={15} /> Mark picked up
        </Button>
      )}

      {canAct && assignment.status === 'out_for_delivery' && (
        <div className="space-y-2">
          <div className="flex gap-2">
            <input value={location} onChange={(e) => setLocation(e.target.value)} placeholder="Current location" className={inputCls} />
            <Button variant="secondary" size="sm" disabled={logLocation.isPending || !location} onClick={() => logLocation.mutate({ id: assignment.id, manual_location: location }, { onSuccess: () => setLocation('') })}>
              <MapPin size={15} /> Log
            </Button>
          </div>
          <input value={recipient} onChange={(e) => setRecipient(e.target.value)} placeholder="Recipient name (optional)" className={inputCls} />
          <label className="flex cursor-pointer items-center justify-center gap-2 rounded-[var(--radius-sm)] bg-walnut px-4 py-2 text-sm font-medium text-walnut-foreground hover:bg-walnut-hover">
            <PackageCheck size={15} /> {proof.isPending ? 'Uploading…' : 'Upload proof & deliver'}
            <input type="file" accept="image/*" onChange={onProof} className="hidden" />
          </label>
        </div>
      )}
    </Card>
  )
}
