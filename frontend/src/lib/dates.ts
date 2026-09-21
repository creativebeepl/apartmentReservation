const dateTimeFormat = new Intl.DateTimeFormat('pl-PL', { dateStyle: 'short', timeStyle: 'short' })

export function formatDateTime(iso: string): string {
  return dateTimeFormat.format(new Date(iso))
}

/** Wartość pola datetime-local ("2026-07-01T10:00", czas lokalny) -> Date albo null. */
export function parseLocalInput(value: string): Date | null {
  if (!value) {
    return null
  }
  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? null : date
}

/** Chwila w formacie ISO 8601 z jawną strefą (UTC) — tego wymaga API. */
export function toApiInstant(date: Date): string {
  return date.toISOString()
}

function pad(n: number): string {
  return String(n).padStart(2, '0')
}

/** Date -> wartość pola datetime-local (czas lokalny, z dokładnością do minuty). */
export function toLocalInputValue(date: Date): string {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}
