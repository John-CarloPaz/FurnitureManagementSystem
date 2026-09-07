import { Check } from 'lucide-react'
import { cn } from '@/lib/utils'
import type { CrudAction, PermissionGroup } from '@/lib/roles-api'

const CRUD_COLUMNS: { action: CrudAction; label: string }[] = [
  { action: 'create', label: 'Create' },
  { action: 'read', label: 'Read' },
  { action: 'update', label: 'Update' },
  { action: 'delete', label: 'Delete' },
]

function Checkbox({ checked, onClick, disabled, label }: { checked: boolean; onClick: () => void; disabled?: boolean; label: string }) {
  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      aria-pressed={checked}
      aria-label={label}
      className={cn(
        'inline-flex h-5 w-5 items-center justify-center rounded border transition-colors',
        checked ? 'border-walnut bg-walnut text-walnut-foreground' : 'border-border bg-bg hover:border-walnut',
        disabled && 'cursor-not-allowed opacity-60 hover:border-border',
      )}
    >
      {checked && <Check size={13} />}
    </button>
  )
}

function Chip({ checked, onClick, disabled, label }: { checked: boolean; onClick: () => void; disabled?: boolean; label: string }) {
  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      aria-pressed={checked}
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors',
        checked ? 'border-walnut bg-amber-soft text-fg' : 'border-border bg-bg text-muted hover:border-walnut hover:text-fg',
        disabled && 'cursor-not-allowed opacity-60',
      )}
    >
      {checked && <Check size={12} />}
      {label}
    </button>
  )
}

/**
 * The role builder's permission picker: core models render as a Create/Read/Update/
 * Delete matrix, workflow areas as toggle chips. Fully controlled — `selected` is a
 * Set of permission names; `onToggle` flips one.
 */
export function PermissionMatrix({
  groups,
  selected,
  onToggle,
  disabled,
}: {
  groups: PermissionGroup[]
  selected: Set<string>
  onToggle: (name: string) => void
  disabled?: boolean
}) {
  const crudGroups = groups.filter((g) => g.abilities.some((a) => a.type === 'crud'))
  const actionGroups = groups
    .map((g) => ({ ...g, actions: g.abilities.filter((a) => a.type === 'action') }))
    .filter((g) => g.actions.length > 0)

  return (
    <div className="space-y-6">
      <div>
        <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted">Data access (CRUD)</p>
        <div className="overflow-x-auto rounded-[var(--radius-sm)] border border-border">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
                <th className="px-4 py-2.5 text-left font-medium">Resource</th>
                {CRUD_COLUMNS.map((c) => (
                  <th key={c.action} className="px-3 py-2.5 text-center font-medium">{c.label}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {crudGroups.map((g) => (
                <tr key={g.key} className="border-b border-border last:border-0">
                  <td className="whitespace-nowrap px-4 py-2.5 text-fg">{g.label}</td>
                  {CRUD_COLUMNS.map((c) => {
                    const ability = g.abilities.find((a) => a.type === 'crud' && a.action === c.action)
                    return (
                      <td key={c.action} className="px-3 py-2.5 text-center">
                        {ability ? (
                          <Checkbox
                            checked={selected.has(ability.name)}
                            onClick={() => onToggle(ability.name)}
                            disabled={disabled}
                            label={`${g.label} — ${ability.label}`}
                          />
                        ) : (
                          <span className="text-border">·</span>
                        )}
                      </td>
                    )
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <p className="mb-2 text-xs font-medium uppercase tracking-wide text-muted">Capabilities</p>
        <div className="space-y-3">
          {actionGroups.map((g) => (
            <div key={g.key}>
              <p className="mb-1.5 text-sm font-medium text-fg">{g.label}</p>
              <div className="flex flex-wrap gap-2">
                {g.actions.map((a) => (
                  <Chip
                    key={a.name}
                    label={a.label}
                    checked={selected.has(a.name)}
                    onClick={() => onToggle(a.name)}
                    disabled={disabled}
                  />
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
