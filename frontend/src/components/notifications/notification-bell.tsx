import { useState } from 'react'
import { Bell } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { useMarkAllRead, useMarkRead, useNotifications } from '@/hooks/use-notifications'
import type { AppNotification } from '@/lib/notifications-api'

export function NotificationBell() {
  const [open, setOpen] = useState(false)
  const { data } = useNotifications()
  const markRead = useMarkRead()
  const markAll = useMarkAllRead()
  const navigate = useNavigate()

  const unread = data?.meta.unread_count ?? 0
  const items = data?.data ?? []

  const onItem = (n: AppNotification) => {
    if (!n.read_at) markRead.mutate(n.id)
    setOpen(false)
    const orderId = n.data?.order_id
    if (typeof orderId === 'number') navigate(`/orders/${orderId}`)
  }

  return (
    <div className="relative">
      <Button variant="ghost" size="icon" onClick={() => setOpen((o) => !o)} aria-label="Notifications">
        <Bell size={18} />
        {unread > 0 && (
          <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--status-danger)] px-1 text-[10px] font-semibold text-white">
            {unread > 9 ? '9+' : unread}
          </span>
        )}
      </Button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-[var(--radius-md)] border border-border bg-surface shadow-[var(--shadow-lg)]">
            <div className="flex items-center justify-between border-b border-border px-4 py-3">
              <span className="font-display text-sm text-fg">Notifications</span>
              {unread > 0 && (
                <button onClick={() => markAll.mutate()} className="text-xs text-walnut hover:underline">
                  Mark all read
                </button>
              )}
            </div>
            <ul className="max-h-96 overflow-y-auto">
              {items.length === 0 ? (
                <li className="px-4 py-6 text-center text-sm text-muted">You're all caught up.</li>
              ) : (
                items.map((n) => (
                  <li key={n.id}>
                    <button
                      onClick={() => onItem(n)}
                      className="flex w-full flex-col gap-0.5 border-b border-border px-4 py-3 text-left transition-colors hover:bg-surface-2"
                      style={{ backgroundColor: !n.read_at ? 'color-mix(in srgb, var(--amber) 8%, transparent)' : undefined }}
                    >
                      <span className="text-sm text-fg">{n.message}</span>
                      <span className="text-xs text-muted">{new Date(n.created_at).toLocaleString()}</span>
                    </button>
                  </li>
                ))
              )}
            </ul>
          </div>
        </>
      )}
    </div>
  )
}
