/**
 * Cedarside Holding Corp. mark — a spearhead centred in a viewfinder frame
 * (the "monitoring" brackets). Line-art; inherits color via `currentColor`.
 */
export function Logo({ className, strokeWidth = 7 }: { className?: string; strokeWidth?: number }) {
  return (
    <svg
      viewBox="0 0 100 100"
      fill="none"
      stroke="currentColor"
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
      aria-hidden="true"
    >
      {/* viewfinder corner brackets */}
      <path d="M20 34 V20 H34" />
      <path d="M66 20 H80 V34" />
      <path d="M80 66 V80 H66" />
      <path d="M34 80 H20 V66" />
      {/* spearhead */}
      <path d="M50 21 C 61 38 66 50 60 61 C 56 68 52 67 50 74 C 48 67 44 68 40 61 C 34 50 39 38 50 21 Z" />
      <path d="M50 74 V83" />
    </svg>
  )
}
