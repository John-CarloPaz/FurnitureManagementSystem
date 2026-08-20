import { lazy, Suspense } from 'react'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { AppShell } from '@/components/layout/app-shell'
import { DashboardPage } from '@/pages/dashboard-page'
import { LoginPage } from '@/pages/login-page'
import { CatalogPage } from '@/pages/catalog-page'
import { CartPage } from '@/pages/cart-page'
import { OrdersPage } from '@/pages/orders-page'
import { OrderDetailPage } from '@/pages/order-detail-page'
import { ShopFloorPage } from '@/pages/shop-floor-page'
import { DeliveriesPage } from '@/pages/deliveries-page'
import { AnalyticsPage } from '@/pages/analytics-page'
import { ProtectedRoute } from '@/components/auth/protected-route'

// Code-split: the product detail page pulls in Three.js (3D viewer).
const ProductDetailPage = lazy(() =>
  import('@/pages/product-detail-page').then((m) => ({ default: m.ProductDetailPage })),
)

function Placeholder({ title }: { title: string }) {
  return (
    <div className="mx-auto max-w-6xl">
      <h1 className="font-display text-3xl text-fg">{title}</h1>
      <p className="mt-2 text-muted">
        This module lands in a later milestone — see <code>docs/ROADMAP.md</code>.
      </p>
    </div>
  )
}

const Loading = () => <div className="p-8 text-center text-muted">Loading…</div>

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route element={<ProtectedRoute />}>
          <Route path="/" element={<AppShell />}>
            <Route index element={<DashboardPage />} />
            <Route path="catalog" element={<CatalogPage />} />
            <Route
              path="catalog/:id"
              element={<Suspense fallback={<Loading />}><ProductDetailPage /></Suspense>}
            />
            <Route path="cart" element={<CartPage />} />
            <Route path="orders" element={<OrdersPage />} />
            <Route path="orders/:id" element={<OrderDetailPage />} />
            <Route path="shop-floor" element={<ShopFloorPage />} />
            <Route path="deliveries" element={<DeliveriesPage />} />
            <Route path="kpi" element={<AnalyticsPage />} />
            <Route path="users" element={<Placeholder title="Users" />} />
          </Route>
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
