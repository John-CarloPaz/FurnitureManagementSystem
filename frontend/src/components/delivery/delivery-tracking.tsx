import { Camera } from 'lucide-react'
import { fileUrl } from '@/lib/api'
import type { OrderDelivery } from '@/lib/orders-api'

/** Shopee/Lazada-style delivery timeline + proof — shown to staff, driver, and customer. */
export function DeliveryTracking({ delivery }: { delivery: OrderDelivery }) {
  const events = [...(delivery.events ?? [])].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
  )

  return (
    <div className="space-y-3">
      {events.length > 0 ? (
        <ol>
          {events.map((ev, i) => (
            <li key={ev.id} className="flex gap-3">
              <div className="flex flex-col items-center">
                <span
                  className="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                  style={{ backgroundColor: i === 0 ? 'var(--status-success)' : 'var(--border)' }}
                />
                {i < events.length - 1 && <span className="w-px flex-1 bg-border" />}
              </div>
              <div className="pb-4">
                <p className="text-sm text-fg">
                  {ev.type_label}
                  {ev.manual_location ? ` · ${ev.manual_location}` : ''}
                </p>
                {ev.note && <p className="text-xs text-muted">{ev.note}</p>}
                <p className="text-xs text-muted">{new Date(ev.created_at).toLocaleString()}</p>
              </div>
            </li>
          ))}
        </ol>
      ) : (
        <p className="text-sm text-muted">No tracking updates yet — you'll see each step here.</p>
      )}

      {delivery.proof && (
        <a
          href={fileUrl(delivery.proof.photo_url)}
          target="_blank"
          rel="noreferrer"
          className="inline-flex items-center gap-1.5 text-sm text-walnut hover:underline"
        >
          <Camera size={15} /> Proof of delivery
          {delivery.proof.recipient_name ? ` · received by ${delivery.proof.recipient_name}` : ''}
        </a>
      )}
    </div>
  )
}
