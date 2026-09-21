import type { Booking, NewBooking } from '../api/types'
import { parseLocalInput, toApiInstant } from './dates'
import { intervalsOverlap } from './overlap'

export const MAX_ADVANCE_YEARS = 1

export interface BookingFormValues {
  resourceId: string
  start: string
  end: string
  customerName: string
}

export type FieldName = 'resource' | 'start' | 'end' | 'customerName'
export type FieldErrors = Partial<Record<FieldName, string>>

export type ValidationResult =
  | { ok: true, value: NewBooking }
  | { ok: false, errors: FieldErrors }

/** Koniec okna rezerwacji: teraz + rok (te same reguły egzekwuje backend). */
export function bookingWindowEnd(now: Date): Date {
  const limit = new Date(now)
  limit.setFullYear(limit.getFullYear() + MAX_ADVANCE_YEARS)

  return limit
}

/**
 * Szybka walidacja po stronie klienta. Backend i tak jest źródłem prawdy (reguły + kolizje),
 * ale użytkownik od razu widzi oczywiste błędy bez zapytania do serwera.
 */
export function validateBookingForm(
  values: BookingFormValues,
  existing: Booking[],
  now: Date,
): ValidationResult {
  const errors: FieldErrors = {}

  if (!values.resourceId) {
    errors.resource = 'Wybierz apartament.'
  }

  const name = values.customerName.trim()
  if (!name) {
    errors.customerName = 'Podaj imię i nazwisko.'
  } else if (name.length > 255) {
    errors.customerName = 'Maksymalnie 255 znaków.'
  }

  const start = parseLocalInput(values.start)
  const end = parseLocalInput(values.end)

  if (!start) {
    errors.start = 'Podaj początek rezerwacji.'
  } else if (start < now) {
    errors.start = 'Rezerwacja nie może zaczynać się w przeszłości.'
  }

  if (!end) {
    errors.end = 'Podaj koniec rezerwacji.'
  } else if (start && end <= start) {
    errors.end = 'Koniec musi być późniejszy niż początek.'
  } else if (end > bookingWindowEnd(now)) {
    errors.end = `Rezerwować można najwyżej ${MAX_ADVANCE_YEARS} rok do przodu.`
  }

  if (start && end && !errors.start && !errors.end && values.resourceId) {
    const collides = existing.some(
      b =>
        b.resource_id === values.resourceId
        && intervalsOverlap(start.getTime(), end.getTime(), Date.parse(b.start_at), Date.parse(b.end_at)),
    )
    if (collides) {
      errors.start = 'Ten termin koliduje z istniejącą rezerwacją tego apartamentu.'
    }
  }

  if (Object.keys(errors).length > 0 || !start || !end) {
    return { ok: false, errors }
  }

  return {
    ok: true,
    value: {
      resource_id: values.resourceId,
      start_at: toApiInstant(start),
      end_at: toApiInstant(end),
      customer_name: name,
    },
  }
}
