import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { ShoppingBag, LogOut } from 'lucide-react'
import { Logo } from '@/components/brand/logo'
import { useAuth, useLogout } from '@/hooks/use-auth'
import { useCart } from '@/stores/cart'

export function StorefrontLayout() {
  const { user, isAuthenticated } = useAuth()
  const logout = useLogout()
  const navigate = useNavigate()
  const count = useCart((s) => s.count())

  const navLink = ({ isActive }: { isActive: boolean }) =>
    isActive ? 'text-sm font-medium text-fg' : 'text-sm text-muted transition-colors hover:text-fg'

  return (
    <div className="min-h-screen bg-bg">
      <header className="sticky top-0 z-40 border-b border-border bg-surface/85 backdrop-blur">
        <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4">
          <Link to="/shop" className="flex items-center gap-2.5">
            <span className="flex h-9 w-9 items-center justify-center rounded-[var(--radius-sm)] bg-walnut text-walnut-foreground">
              <Logo className="h-5 w-5" />
            </span>
            <div className="flex flex-col leading-none">
              <span className="font-display text-lg text-fg">Cedarside</span>
              <span className="text-[10px] uppercase tracking-wider text-muted">Furniture</span>
            </div>
          </Link>

          <nav className="hidden items-center gap-6 sm:flex">
            <NavLink to="/shop" end className={navLink}>Shop</NavLink>
            {isAuthenticated && <NavLink to="/shop/orders" className={navLink}>My Orders</NavLink>}
          </nav>

          <div className="flex items-center gap-2">
            <Link to="/shop/cart" className="relative rounded-full p-2 text-fg transition-colors hover:bg-surface-2" aria-label="Cart">
              <ShoppingBag size={20} />
              {count > 0 && (
                <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-walnut px-1 text-[10px] font-bold text-walnut-foreground">
                  {count}
                </span>
              )}
            </Link>
            {isAuthenticated ? (
              <div className="flex items-center gap-2 pl-1">
                <span className="hidden text-sm text-muted sm:inline">{user?.username ?? user?.name}</span>
                <button
                  onClick={() => logout.mutate(undefined, { onSuccess: () => navigate('/shop') })}
                  aria-label="Sign out"
                  className="rounded-full p-2 text-muted transition-colors hover:bg-surface-2 hover:text-fg"
                >
                  <LogOut size={18} />
                </button>
              </div>
            ) : (
              <div className="flex items-center gap-2 pl-1">
                <Link to="/login" className="text-sm text-muted hover:text-fg">Sign in</Link>
                <Link to="/register" className="rounded-full bg-walnut px-4 py-1.5 text-sm font-medium text-walnut-foreground transition-colors hover:bg-walnut-hover">
                  Sign up
                </Link>
              </div>
            )}
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-4 py-8">
        <Outlet />
      </main>

      <footer className="border-t border-border py-8 text-center text-xs text-muted">
        Cedarside Holding Corp. · Handcrafted furniture, seen in 3D
      </footer>
    </div>
  )
}
