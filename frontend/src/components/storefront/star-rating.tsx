import { Star } from 'lucide-react'

interface Props {
  value: number
  size?: number
  onChange?: (value: number) => void
}

/** Five stars filled to `value`. Pass `onChange` to make it an input. */
export function StarRating({ value, size = 16, onChange }: Props) {
  const interactive = !!onChange
  return (
    <span className="inline-flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((n) => {
        const filled = n <= Math.round(value)
        const star = (
          <Star
            size={size}
            className={filled ? 'text-[var(--amber)]' : 'text-border'}
            fill={filled ? 'currentColor' : 'none'}
          />
        )
        return interactive ? (
          <button key={n} type="button" onClick={() => onChange(n)} aria-label={`${n} star${n > 1 ? 's' : ''}`} className="rounded p-0.5 hover:scale-110">
            {star}
          </button>
        ) : (
          <span key={n}>{star}</span>
        )
      })}
    </span>
  )
}
