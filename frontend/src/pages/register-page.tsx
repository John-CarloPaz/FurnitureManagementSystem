import { useState, type FormEvent } from 'react'
import { useNavigate, useSearchParams, Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Logo } from '@/components/brand/logo'
import { useRegister } from '@/hooks/use-auth'
import { apiError } from '@/lib/api-error'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function RegisterPage() {
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const reg = useRegister()
  const [form, setForm] = useState({ name: '', username: '', email: '', password: '', password_confirmation: '' })
  const [error, setError] = useState<string | null>(null)
  const set = (k: keyof typeof form, v: string) => setForm((f) => ({ ...f, [k]: v }))

  const onSubmit = (e: FormEvent) => {
    e.preventDefault()
    setError(null)
    reg.mutate(form, {
      onSuccess: () => navigate(params.get('next') || '/shop', { replace: true }),
      onError: (er) => setError(apiError(er, 'Could not create your account.')),
    })
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-bg p-4">
      <form onSubmit={onSubmit} className="w-full max-w-sm space-y-6">
        <div className="flex flex-col items-center gap-3 text-center">
          <span className="flex h-14 w-14 items-center justify-center rounded-[var(--radius-md)] bg-walnut text-walnut-foreground">
            <Logo className="h-8 w-8" />
          </span>
          <div>
            <h1 className="font-display text-2xl text-fg">Create your account</h1>
            <p className="text-sm text-muted">Shop Cedarside and track your orders</p>
          </div>
        </div>

        <div className="space-y-4 rounded-[var(--radius-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-md)]">
          {error && (
            <div className="rounded-[var(--radius-sm)] px-3 py-2 text-sm" style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}>
              {error}
            </div>
          )}
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="name">Full name</label>
            <input id="name" value={form.name} onChange={(e) => set('name', e.target.value)} className={inputCls} required />
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="username">Username</label>
            <input id="username" value={form.username} onChange={(e) => set('username', e.target.value)} placeholder="username" className={inputCls} required />
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="email">Email</label>
            <input id="email" type="email" value={form.email} onChange={(e) => set('email', e.target.value)} className={inputCls} required />
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="password">Password</label>
            <input id="password" type="password" value={form.password} onChange={(e) => set('password', e.target.value)} placeholder="••••••••" className={inputCls} required />
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-fg" htmlFor="confirm">Confirm password</label>
            <input id="confirm" type="password" value={form.password_confirmation} onChange={(e) => set('password_confirmation', e.target.value)} placeholder="••••••••" className={inputCls} required />
          </div>
          <Button type="submit" className="w-full" size="pill" disabled={reg.isPending}>
            {reg.isPending ? 'Creating account…' : 'Create account'}
          </Button>
        </div>

        <p className="text-center text-sm text-muted">
          Already have an account? <Link to="/login" className="text-walnut hover:underline">Sign in</Link>
        </p>
      </form>
    </div>
  )
}
