import { NavLink, useNavigate } from 'react-router-dom'
import {
  LayoutDashboard,
  Package,
  ClipboardList,
  Factory,
  Truck,
  BarChart3,
  Users,
  LogOut,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { Logo } from '@/components/brand/logo'
import { useAuth, useLogout } from '@/hooks/use-auth'

type NavItem = {
  to: string
  label: string
  icon: typeof LayoutDashboard
  end?: boolean
  anyOf?: string[]
}

const NAV: NavItem[] = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard, end: true },
  { to: '/catalog', label: 'Catalog', icon: Package, anyOf: ['products.browse', 'products.viewAny'] },
  { to: '/orders', label: 'Orders', icon: ClipboardList, anyOf: ['orders.viewAny', 'orders.view.own'] },
  { to: '/shop-floor', label: 'Shop Floor', icon: Factory, anyOf: ['manufacturing.view'] },
  { to: '/deliveries', label: 'Deliveries', icon: Truck, anyOf: ['delivery.view'] },
  { to: '/kpi', label: 'Analytics', icon: BarChart3, anyOf: ['kpi.view', 'kpi.view.shopfloor', 'kpi.view.delivery'] },
  { to: '/users', label: 'Users', icon: Users, anyOf: ['users.view'] },
]

export function Sidebar() {
  const navigate = useNavigate()
  const { user, hasAny } = useAuth()
  const logout = useLogout()

  const items = NAV.filter((item) => !item.anyOf || hasAny(item.anyOf))
  const initials =
    user?.name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase() ?? 'U'

  const onLogout = () =>
    logout.mutate(undefined, { onSuccess: () => navigate('/login', { replace: true }) })

  return (
    <aside className="flex w-60 shrink-0 flex-col border-r border-border bg-surface">
      <div className="flex h-16 items-center gap-2.5 px-5">
        <span className="flex h-9 w-9 items-center justify-center rounded-[var(--radius-sm)] bg-walnut text-walnut-foreground">
          <Logo className="h-5 w-5" />
        </span>
        <div className="flex flex-col leading-none">
          <span className="font-display text-lg text-fg">Cedarside</span>
          <span className="text-[10px] uppercase tracking-wider text-muted">Holding Corp.</span>
        </div>
      </div>

      <nav className="flex-1 space-y-1 px-3 py-2">
        {items.map(({ to, label, icon: Icon, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            className={({ isActive }) =>
              cn(
                'relative flex items-center gap-3 rounded-[var(--radius-sm)] px-3 py-2 text-sm transition-colors',
                isActive
                  ? 'bg-amber-soft font-medium text-fg'
                  : 'text-muted hover:bg-surface-2 hover:text-fg',
              )
            }
          >
            {({ isActive }) => (
              <>
                {isActive && (
                  <span className="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-r bg-walnut" />
                )}
                <Icon size={18} />
                {label}
              </>
            )}
          </NavLink>
        ))}
      </nav>

      <div className="flex items-center gap-3 border-t border-border p-4">
        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-surface-2 text-sm font-medium text-fg">
          {initials}
        </span>
        <div className="min-w-0 flex-1">
          <p className="truncate text-sm font-medium text-fg">{user?.name ?? 'User'}</p>
          <p className="truncate text-xs text-muted">{user?.email ?? ''}</p>
        </div>
        <button
          onClick={onLogout}
          aria-label="Sign out"
          className="rounded-[var(--radius-sm)] p-1.5 text-muted transition-colors hover:bg-surface-2 hover:text-fg"
        >
          <LogOut size={16} />
        </button>
      </div>
    </aside>
  )
}
