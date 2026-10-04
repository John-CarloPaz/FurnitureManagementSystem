import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Logo } from '@/components/brand/logo'
import { forgotPassword } from '@/lib/auth-api'
import { apiError } from '@/lib/api-error'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [pending, setPending] = useState(false)

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault()
    setError(null)
    setPending(true)
    try {
      setMessage(await forgotPassword(email))
    } catch (err) {
      setError(apiError(err, 'Could not send the reset link.'))
    } finally {
      setPending(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-bg p-4">
      <form onSubmit={onSubmit} className="w-full max-w-sm space-y-6">
        <div className="flex flex-col items-center gap-3 text-center">
          <span className="flex h-14 w-14 items-center justify-center rounded-[var(--radius-md)] bg-walnut text-walnut-foreground">
            <Logo className="h-8 w-8" />
          </span>
          <div>
            <h1 className="font-display text-2xl text-fg">Forgot your password?</h1>
            <p className="text-sm text-muted">We'll email you a link to reset it.</p>
          </div>
        </div>

        <div className="space-y-4 rounded-[var(--radius-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-md)]">
          {message ? (
            <p className="rounded-[var(--radius-sm)] px-3 py-2 text-sm" style={{ backgroundColor: 'color-mix(in srgb, var(--status-success) 12%, transparent)', color: 'var(--status-success)' }}>
              {message}
            </p>
          ) : (
            <>
              {error && (
                <div className="rounded-[var(--radius-sm)] px-3 py-2 text-sm" style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}>
                  {error}
                </div>
              )}
              <div className="space-y-1.5">
                <label className="text-sm font-medium text-fg" htmlFor="email">Email</label>
                <input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} className={inputCls} required />
              </div>
              <Button type="submit" className="w-full" size="pill" disabled={pending}>
                {pending ? 'Sending…' : 'Send reset link'}
              </Button>
            </>
          )}
        </div>

        <p className="text-center text-sm text-muted">
          <Link to="/login" className="text-walnut hover:underline">Back to sign in</Link>
        </p>
      </form>
    </div>
  )
}
