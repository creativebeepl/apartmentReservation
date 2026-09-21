import type { Booking } from '../api/types'
import { bookingWindowEnd, validateBookingForm, type BookingFormValues } from './bookingRules'

const NOW = new Date(2026, 5, 1, 12, 0, 0) // 1 czerwca 2026, 12:00 czasu lokalnego

const valid: BookingFormValues = {
  resourceId: 'r1',
  start: '2026-07-01T10:00',
  end: '2026-07-03T10:00',
  customerName: 'Jan Kowalski',
}

function booking(overrides: Partial<Booking> = {}): Booking {
  return {
    id: 'b1',
    resource_id: 'r1',
    resource_name: 'Apartament 101',
    start_at: new Date(2026, 6, 10, 10, 0).toISOString(),
    end_at: new Date(2026, 6, 12, 10, 0).toISOString(),
    customer_name: 'Ktoś',
    created_at: '2026-06-01T00:00:00Z',
    ...overrides,
  }
}

function errorsFor(values: Partial<BookingFormValues>, existing: Booking[] = []) {
  const result = validateBookingForm({ ...valid, ...values }, existing, NOW)
  if (result.ok) {
    throw new Error('Expected validation to fail.')
  }

  return result.errors
}

describe('validateBookingForm', () => {
  it('accepts a valid booking and produces an API payload in UTC', () => {
    const result = validateBookingForm(valid, [], NOW)

    expect(result).toEqual({
      ok: true,
      value: {
        resource_id: 'r1',
        start_at: '2026-07-01T08:00:00.000Z',
        end_at: '2026-07-03T08:00:00.000Z',
        customer_name: 'Jan Kowalski',
      },
    })
  })

  it('trims the customer name', () => {
    const result = validateBookingForm({ ...valid, customerName: '  Jan  ' }, [], NOW)

    expect(result.ok && result.value.customer_name).toBe('Jan')
  })

  it('requires every field', () => {
    const errors = errorsFor({ resourceId: '', start: '', end: '', customerName: '   ' })

    expect(Object.keys(errors).sort()).toEqual(['customerName', 'end', 'resource', 'start'])
  })

  it('rejects a start in the past', () => {
    expect(errorsFor({ start: '2026-05-31T10:00', end: '2026-06-02T10:00' }).start).toMatch(/przeszłości/)
  })

  it('allows a start exactly now', () => {
    const result = validateBookingForm({ ...valid, start: '2026-06-01T12:00' }, [], NOW)

    expect(result.ok).toBe(true)
  })

  it('rejects an end that is not after the start', () => {
    expect(errorsFor({ end: valid.start }).end).toMatch(/późniejszy/)
    expect(errorsFor({ end: '2026-06-30T10:00' }).end).toMatch(/późniejszy/)
  })

  it('rejects bookings ending more than a year ahead', () => {
    expect(errorsFor({ start: '2027-05-30T10:00', end: '2027-06-02T10:00' }).end).toMatch(/rok do przodu/)
  })

  it('accepts a booking ending exactly at the end of the window', () => {
    const result = validateBookingForm({ ...valid, start: '2027-05-30T12:00', end: '2027-06-01T12:00' }, [], NOW)

    expect(result.ok).toBe(true)
  })

  it('exposes the end of the booking window', () => {
    expect(bookingWindowEnd(NOW)).toEqual(new Date(2027, 5, 1, 12, 0, 0))
  })

  it('rejects overlap with an existing booking of the same resource', () => {
    const errors = errorsFor({ start: '2026-07-11T10:00', end: '2026-07-13T10:00' }, [booking()])

    expect(errors.start).toMatch(/koliduje/)
  })

  it('ignores bookings of other resources', () => {
    const result = validateBookingForm(
      { ...valid, start: '2026-07-11T10:00', end: '2026-07-13T10:00' },
      [booking({ resource_id: 'other' })],
      NOW,
    )

    expect(result.ok).toBe(true)
  })

  it('allows back-to-back bookings', () => {
    const result = validateBookingForm({ ...valid, start: '2026-07-12T10:00', end: '2026-07-14T10:00' }, [booking()], NOW)

    expect(result.ok).toBe(true)
  })
})
