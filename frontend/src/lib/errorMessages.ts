import { ApiError } from '../api/client'
import type { FieldErrors } from './bookingRules'

const MESSAGES: Record<string, string> = {
  slot_conflict: 'Wybrany termin koliduje z istniejącą rezerwacją tego apartamentu.',
  booking_in_past: 'Rezerwacja nie może zaczynać się w przeszłości.',
  booking_too_far_ahead: 'Rezerwować można najwyżej rok do przodu.',
  invalid_period: 'Koniec rezerwacji musi być późniejszy niż początek.',
  resource_not_found: 'Wybrany apartament już nie istnieje. Odśwież stronę.',
  invalid_credentials: 'Nieprawidłowy e-mail lub hasło.',
  too_many_requests: 'Zbyt wiele prób. Odczekaj chwilę i spróbuj ponownie.',
  validation_failed: 'Sprawdź poprawność wprowadzonych danych.',
  network_error: 'Nie można połączyć się z serwerem. Spróbuj ponownie.',
  unauthenticated: 'Sesja wygasła. Zaloguj się ponownie.',
}

export function messageForError(error: unknown, fallback = 'Wystąpił nieoczekiwany błąd.'): string {
  if (error instanceof ApiError) {
    return MESSAGES[error.code] ?? fallback
  }

  return fallback
}

const FIELD_MAP: Record<string, keyof FieldErrors> = {
  resource_id: 'resource',
  start_at: 'start',
  end_at: 'end',
  customer_name: 'customerName',
}

/** Błędy pól z odpowiedzi 422 (nazwy pól API) -> pola formularza. */
export function fieldErrorsFromApi(error: unknown): FieldErrors {
  const result: FieldErrors = {}
  if (!(error instanceof ApiError)) {
    return result
  }
  for (const [apiField, messages] of Object.entries(error.fieldErrors)) {
    const field = FIELD_MAP[apiField]
    if (field && messages[0]) {
      result[field] = messages[0]
    }
  }

  return result
}
