import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { PermissionMatrix } from '@/components/access/permission-matrix'
import type { PermissionGroup } from '@/lib/roles-api'

const inputCls =
  'w-full rounded-[var(--radius-sm)] border border-border bg-bg px-3 py-2 text-sm text-fg outline-none focus:ring-2 focus:ring-[var(--amber)]'

export function RoleForm({
  catalog,
  initial,
  submitting,
  error,
  onSubmit,
  onCancel,
}: {
  catalog: PermissionGroup[]
  initial?: { name: string; permissions: string[] }
  submitting: boolean
  error: string | null
  onSubmit: (payload: { name: string; permissions: string[] }) => void
  onCancel: () => void
}) {
  const [name, setName] = useState(initial?.name ?? '')
  const [selected, setSelected] = useState<Set<string>>(() => new Set(initial?.permissions ?? []))

  const toggle = (permission: string) =>
    setSelected((prev) => {
      const next = new Set(prev)
      if (next.has(permission)) next.delete(permission)
      else next.add(permission)
      return next
    })

  return (
    <div className="space-y-5">
      <div className="space-y-1.5">
        <label className="text-sm font-medium text-fg" htmlFor="role-name">Role name</label>
        <input
          id="role-name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          placeholder="e.g. Delivery Rider"
          className={inputCls}
        />
      </div>

      <PermissionMatrix groups={catalog} selected={selected} onToggle={toggle} disabled={submitting} />

      {error && <p className="text-sm text-[var(--status-danger)]">{error}</p>}

      <div className="flex gap-2">
        <Button
          disabled={submitting || !name.trim()}
          onClick={() => onSubmit({ name: name.trim(), permissions: [...selected] })}
        >
          {submitting ? 'Saving…' : 'Save role'}
        </Button>
        <Button variant="secondary" onClick={onCancel} disabled={submitting}>
          Cancel
        </Button>
      </div>
    </div>
  )
}
