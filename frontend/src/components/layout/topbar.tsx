import { Search, Sun, Moon } from 'lucide-react'
import { useTheme } from '@/components/theme/theme-provider'
import { Button } from '@/components/ui/button'
import { NotificationBell } from '@/components/notifications/notification-bell'

export function Topbar({ onOpenCommand }: { onOpenCommand: () => void }) {
  const { theme, toggle } = useTheme()
  return (
    <header className="flex h-16 shrink-0 items-center gap-4 border-b border-border bg-surface px-6">
      <button
        onClick={onOpenCommand}
        className="flex flex-1 items-center gap-2 rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-muted transition-colors hover:border-walnut max-w-md"
      >
        <Search size={16} />
        <span>Search orders, customers…</span>
        <kbd className="ml-auto rounded border border-border px-1.5 py-0.5 font-mono text-xs">⌘K</kbd>
      </button>

      <div className="ml-auto flex items-center gap-1">
        <NotificationBell />
        <Button variant="ghost" size="icon" onClick={toggle} aria-label="Toggle theme">
          {theme === 'light' ? <Moon size={18} /> : <Sun size={18} />}
        </Button>
      </div>
    </header>
  )
}
