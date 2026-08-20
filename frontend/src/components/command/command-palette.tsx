import { useEffect, useState } from 'react'
import { Search, Package, ClipboardList, Factory, Truck, BarChart3 } from 'lucide-react'
import { useNavigate } from 'react-router-dom'

const COMMANDS = [
  { label: 'Go to Catalog', to: '/catalog', icon: Package },
  { label: 'Go to Orders', to: '/orders', icon: ClipboardList },
  { label: 'Go to Shop Floor', to: '/shop-floor', icon: Factory },
  { label: 'Go to Deliveries', to: '/deliveries', icon: Truck },
  { label: 'Go to Analytics', to: '/kpi', icon: BarChart3 },
]

export function CommandPalette({ open, onClose }: { open: boolean; onClose: () => void }) {
  const [query, setQuery] = useState('')
  const navigate = useNavigate()

  useEffect(() => {
    if (!open) setQuery('')
  }, [open])

  if (!open) return null
  const results = COMMANDS.filter((c) => c.label.toLowerCase().includes(query.toLowerCase()))

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center bg-black/40 pt-[15vh]"
      onClick={onClose}
    >
      <div
        className="w-full max-w-lg overflow-hidden rounded-[var(--radius-lg)] border border-border bg-surface shadow-[var(--shadow-lg)]"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center gap-2 border-b border-border px-4">
          <Search size={16} className="text-muted" />
          <input
            autoFocus
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Type a command or search…"
            className="w-full bg-transparent py-3.5 text-sm text-fg outline-none placeholder:text-muted"
          />
        </div>
        <ul className="max-h-72 overflow-y-auto p-2">
          {results.map(({ label, to, icon: Icon }) => (
            <li key={to}>
              <button
                onClick={() => {
                  navigate(to)
                  onClose()
                }}
                className="flex w-full items-center gap-3 rounded-[var(--radius-sm)] px-3 py-2 text-left text-sm text-fg hover:bg-surface-2"
              >
                <Icon size={16} className="text-muted" />
                {label}
              </button>
            </li>
          ))}
          {results.length === 0 && (
            <li className="px-3 py-6 text-center text-sm text-muted">No results</li>
          )}
        </ul>
      </div>
    </div>
  )
}
