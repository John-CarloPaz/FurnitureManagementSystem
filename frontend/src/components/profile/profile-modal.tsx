import { useEffect, useState, type ReactNode } from 'react'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useAuth, useUpdateProfile } from '@/hooks/use-auth'
import { apiError } from '@/lib/api-error'
import type { ProfileInput } from '@/lib/auth-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="space-y-1.5">
      <label className="text-sm font-medium text-fg">{label}</label>
      {children}
    </div>
  )
}

export function ProfileModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { user } = useAuth()
  const update = useUpdateProfile()

  const [name, setName] = useState('')
  const [username, setUsername] = useState('')
  const [changePw, setChangePw] = useState(false)
  const [current, setCurrent] = useState('')
  const [pw, setPw] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState<string | null>(null)

  // Reset fields to the current user each time the modal opens.
  useEffect(() => {
    if (open) {
      setName(user?.name ?? '')
      setUsername(user?.username ?? '')
      setChangePw(false)
      setCurrent('')
      setPw('')
      setConfirm('')
      setError(null)
    }
  }, [open, user])

  if (!open) return null

  const save = () => {
    setError(null)
    const payload: ProfileInput = { name: name.trim(), username: username.trim() }
    if (changePw) {
      payload.current_password = current
      payload.password = pw
      payload.password_confirmation = confirm
    }
    update.mutate(payload, { onSuccess: onClose, onError: (e) => setError(apiError(e)) })
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div
        className="w-full max-w-md space-y-4 rounded-[var(--radius-lg)] border border-border bg-surface p-6 shadow-[var(--shadow-md)]"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between">
          <div>
            <h2 className="font-display text-lg text-fg">Your profile</h2>
            <p className="text-xs text-muted">{user?.email}</p>
          </div>
          <button onClick={onClose} aria-label="Close" className="text-muted hover:text-fg"><X size={18} /></button>
        </div>

        {error && (
          <div
            className="rounded-[var(--radius-sm)] px-3 py-2 text-sm"
            style={{ backgroundColor: 'color-mix(in srgb, var(--status-danger) 12%, transparent)', color: 'var(--status-danger)' }}
          >
            {error}
          </div>
        )}

        <Field label="Name">
          <input value={name} onChange={(e) => setName(e.target.value)} className={inputCls} />
        </Field>
        <Field label="Username">
          <input value={username} onChange={(e) => setUsername(e.target.value)} placeholder="username" className={inputCls} />
        </Field>

        <label className="flex cursor-pointer items-center gap-2 text-sm text-fg">
          <input type="checkbox" checked={changePw} onChange={(e) => setChangePw(e.target.checked)} />
          Change password
        </label>

        {changePw && (
          <div className="space-y-3 rounded-[var(--radius-sm)] border border-border bg-surface-2 p-3">
            <Field label="Current password">
              <input type="password" value={current} onChange={(e) => setCurrent(e.target.value)} className={inputCls} autoComplete="current-password" />
            </Field>
            <Field label="New password">
              <input type="password" value={pw} onChange={(e) => setPw(e.target.value)} className={inputCls} autoComplete="new-password" />
            </Field>
            <Field label="Confirm new password">
              <input type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} className={inputCls} autoComplete="new-password" />
            </Field>
          </div>
        )}

        <div className="flex gap-2 pt-1">
          <Button
            disabled={update.isPending || !name.trim() || !username.trim() || (changePw && (!current || !pw))}
            onClick={save}
          >
            {update.isPending ? 'Saving…' : 'Save changes'}
          </Button>
          <Button variant="ghost" onClick={onClose} disabled={update.isPending}>Cancel</Button>
        </div>
      </div>
    </div>
  )
}
