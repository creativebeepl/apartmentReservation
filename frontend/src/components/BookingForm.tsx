import { useState, type FormEvent } from 'react'
import type { Booking, NewBooking, Resource } from '../api/types'
import { bookingWindowEnd, validateBookingForm, type FieldErrors } from '../lib/bookingRules'
import { toLocalInputValue } from '../lib/dates'
import { fieldErrorsFromApi, messageForError } from '../lib/errorMessages'

interface Props {
  resources: Resource[]
  bookings: Booking[]
  onSubmit: (booking: NewBooking) => Promise<void>
  /** Wstrzykiwany zegar (testy); domyślnie prawdziwy czas. */
  now?: () => Date
}

export function BookingForm({ resources, bookings, onSubmit, now = () => new Date() }: Props) {
  const [resourceId, setResourceId] = useState('')
  const [start, setStart] = useState('')
  const [end, setEnd] = useState('')
  const [customerName, setCustomerName] = useState('')
  const [errors, setErrors] = useState<FieldErrors>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  const current = now()
  const minValue = toLocalInputValue(current)
  const maxValue = toLocalInputValue(bookingWindowEnd(current))

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setSuccess(null)
    setFormError(null)

    const result = validateBookingForm({ resourceId, start, end, customerName }, bookings, now())
    if (!result.ok) {
      setErrors(result.errors)

      return
    }
    setErrors({})
    setSubmitting(true)
    try {
      await onSubmit(result.value)
      setSuccess(`Rezerwacja zapisana dla ${result.value.customer_name}.`)
      setCustomerName('')
    } catch (e) {
      const apiFieldErrors = fieldErrorsFromApi(e)
      if (Object.keys(apiFieldErrors).length > 0) {
        setErrors(apiFieldErrors)
      }
      setFormError(messageForError(e, 'Nie udało się zapisać rezerwacji.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form className="card" onSubmit={handleSubmit} noValidate aria-label="Nowa rezerwacja">
      <h2>Nowa rezerwacja</h2>

      <label>
        Apartament
        <select value={resourceId} onChange={e => setResourceId(e.target.value)} aria-invalid={!!errors.resource}>
          <option value="">— wybierz —</option>
          {resources.map(r => (
            <option key={r.id} value={r.id}>{r.name}</option>
          ))}
        </select>
        {errors.resource && <span className="field-error">{errors.resource}</span>}
      </label>

      <div className="row">
        <label>
          Początek
          <input
            type="datetime-local"
            value={start}
            min={minValue}
            max={maxValue}
            onChange={e => setStart(e.target.value)}
            aria-invalid={!!errors.start}
          />
          {errors.start && <span className="field-error">{errors.start}</span>}
        </label>
        <label>
          Koniec
          <input
            type="datetime-local"
            value={end}
            min={minValue}
            max={maxValue}
            onChange={e => setEnd(e.target.value)}
            aria-invalid={!!errors.end}
          />
          {errors.end && <span className="field-error">{errors.end}</span>}
        </label>
      </div>

      <label>
        Imię i nazwisko
        <input
          type="text"
          value={customerName}
          maxLength={255}
          onChange={e => setCustomerName(e.target.value)}
          aria-invalid={!!errors.customerName}
        />
        {errors.customerName && <span className="field-error">{errors.customerName}</span>}
      </label>

      {formError && <p role="alert" className="error">{formError}</p>}
      {success && <p role="status" className="success">{success}</p>}

      <button type="submit" disabled={submitting || resources.length === 0}>
        {submitting ? 'Zapisywanie…' : 'Zarezerwuj'}
      </button>
    </form>
  )
}
