import { useState } from 'react'
import { Card, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { DeliveryCard } from '@/components/delivery/delivery-card'
import { useAssignDelivery, useDeliveries, useDrivers, useUnassignedOrders } from '@/hooks/use-delivery'
import { useAuth } from '@/hooks/use-auth'
import { peso } from '@/lib/status'
import type { Order } from '@/lib/orders-api'
import type { Driver } from '@/lib/delivery-api'

const inputCls =
  'rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

function AssignRow({
  order,
  drivers,
  pending,
  onAssign,
}: {
  order: Order
  drivers: Driver[]
  pending: boolean
  onAssign: (driverId?: number) => void
}) {
  const [driverId, setDriverId] = useState('')
  return (
    <div className="flex flex-wrap items-center gap-3 border-t border-border pt-3 first:border-0 first:pt-0">
      <div className="flex-1">
        <p className="font-mono text-xs text-muted">{order.order_number}</p>
        <p className="text-sm text-fg">{order.customer?.name ?? '—'} · {peso(order.total)}</p>
      </div>
      <select value={driverId} onChange={(e) => setDriverId(e.target.value)} className={inputCls}>
        <option value="">Select driver…</option>
        {drivers.map((d) => (
          <option key={d.id} value={d.id}>{d.name}</option>
        ))}
      </select>
      <Button size="sm" disabled={pending} onClick={() => onAssign(driverId ? Number(driverId) : undefined)}>
        Assign
      </Button>
    </div>
  )
}

export function DeliveriesPage() {
  const { has } = useAuth()
  const canAssign = has('delivery.assign')
  const isDriver = has('delivery.update')
  const deliveries = useDeliveries()
  const unassigned = useUnassignedOrders(canAssign)
  const drivers = useDrivers(canAssign)
  const assign = useAssignDelivery()

  return (
    <div className="mx-auto max-w-6xl space-y-6">
      <div>
        <h1 className="font-display text-3xl text-fg">Deliveries</h1>
        <p className="mt-1 text-muted">
          {isDriver ? 'Your assigned deliveries — pick up, log location, capture proof.' : 'Assign drivers and track deliveries.'}
        </p>
      </div>

      {canAssign && unassigned.data && unassigned.data.length > 0 && (
        <Card className="space-y-3">
          <CardTitle>Ready for delivery — needs a driver</CardTitle>
          {unassigned.data.map((o) => (
            <AssignRow
              key={o.id}
              order={o}
              drivers={drivers.data ?? []}
              pending={assign.isPending}
              onAssign={(driverId) => assign.mutate({ orderId: o.id, driver_id: driverId })}
            />
          ))}
        </Card>
      )}

      {deliveries.isLoading ? (
        <p className="p-8 text-center text-muted">Loading deliveries…</p>
      ) : !deliveries.data?.length ? (
        <Card className="text-center text-muted">No deliveries yet.</Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2">
          {deliveries.data.map((a) => (
            <DeliveryCard key={a.id} assignment={a} isDriver={isDriver} />
          ))}
        </div>
      )}
    </div>
  )
}
