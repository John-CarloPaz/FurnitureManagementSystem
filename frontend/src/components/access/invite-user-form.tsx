import { useState } from 'react'
import { Mail, Copy, Check } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useCreateInvitation } from '@/hooks/use-invitations'
import { apiError } from '@/lib/api-error'
import { prettyRole, type Role } from '@/lib/roles-api'
import type { CreatedInvitation } from '@/lib/invitations-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

function CopyLink({ url }: { url: string }) {
  const [copied, setCopied] = useState(false)
  const copy = async () => {
    try {
      await navigator.clipboard.writeText(url)
      setCopied(true)
      setTimeout(() => setCopied(false), 1500)
    } catch {
      /* clipboard blocked — the link is still visible to copy manually */
    }
  }
  return (
    <Button size="sm" variant="secondary" onClick={copy}>
      {copied ? <Check size={14} /> : <Copy size={14} />} {copied ? 'Copied' : 'Copy invite link'}
    </Button>
  )
}

export function InviteUserForm({ roles, onDone }: { roles: Role[]; onDone: () => void }) {
  const create = useCreateInvitation()
  const [email, setEmail] = useState('')
  const [role, setRole] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [result, setResult] = useState<CreatedInvitation | null>(null)

  const submit = () => {
    setError(null)
    create.mutate(
      { email, role },
      {
        onSuccess: (res) => { setResult(res); setEmail('') },
        onError: (e) => setError(apiError(e)),
      },
    )
  }

  return (
    <div className="space-y-4">
      <div className="grid gap-3 sm:grid-cols-[1fr_200px_auto]">
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="name@company.com"
          className={inputCls}
        />
        <select value={role} onChange={(e) => setRole(e.target.value)} className={inputCls}>
          <option value="">Select role…</option>
          {roles.map((r) => (
            <option key={r.id} value={r.name}>{prettyRole(r.name)}</option>
          ))}
        </select>
        <Button disabled={create.isPending || !email || !role} onClick={submit}>
          <Mail size={15} /> {create.isPending ? 'Sending…' : 'Send invite'}
        </Button>
      </div>

      {error && <p className="text-sm text-[var(--status-danger)]">{error}</p>}

      {result && (
        <div className="rounded-[var(--radius-sm)] border border-border bg-surface-2 p-3 text-sm">
          <p className="text-fg">
            {result.email_sent
              ? `Invitation emailed to ${result.invitation.email}.`
              : `Invite created for ${result.invitation.email} — email isn't configured, so share the link:`}
          </p>
          <div className="mt-2 flex flex-wrap items-center gap-2">
            <CopyLink url={result.invitation.accept_url} />
            <span className="truncate text-xs text-muted">{result.invitation.accept_url}</span>
          </div>
        </div>
      )}

      <button onClick={onDone} className="text-sm text-muted hover:text-fg">Done</button>
    </div>
  )
}
