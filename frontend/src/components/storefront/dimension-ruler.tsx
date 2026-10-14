/** Dimension annotation overlaid on a product photo (width along the bottom, height up the side). */
export function DimensionRuler({
  width,
  height,
  depth,
}: {
  width?: string | null
  height?: string | null
  depth?: string | null
}) {
  if (!width && !height && !depth) return null
  const cm = (v: string) => `${parseFloat(v)} cm`
  const pill = 'rounded-full bg-surface/90 px-2 py-0.5 text-[11px] font-medium text-fg shadow-[var(--shadow-sm)] ring-1 ring-black/5'

  return (
    <div className="pointer-events-none absolute inset-0">
      {width && (
        <div className="absolute inset-x-[10%] bottom-3 flex items-center gap-1">
          <span className="h-3 w-px bg-fg/40" />
          <span className="h-px flex-1 bg-fg/30" />
          <span className={pill}>W {cm(width)}</span>
          <span className="h-px flex-1 bg-fg/30" />
          <span className="h-3 w-px bg-fg/40" />
        </div>
      )}
      {height && (
        <div className="absolute inset-y-[10%] left-3 flex flex-col items-center gap-1">
          <span className="h-px w-3 bg-fg/40" />
          <span className="w-px flex-1 bg-fg/30" />
          <span className={`${pill} -rotate-90 whitespace-nowrap`}>H {cm(height)}</span>
          <span className="w-px flex-1 bg-fg/30" />
          <span className="h-px w-3 bg-fg/40" />
        </div>
      )}
      {depth && <span className={`absolute right-3 top-3 ${pill}`}>D {cm(depth)}</span>}
    </div>
  )
}
