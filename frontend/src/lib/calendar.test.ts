import type { Booking } from '../api/types'
import { bookingsOnDay, daysInMonth } from './calendar'

function booking(start: string, end: string, resourceId = 'r1'): Booking {
  return {
    id: `${start}-${end}`,
    resource_id: resourceId,
    resource_name: 'Apartament',
    start_at: start,
    end_at: end,
    customer_name: 'Jan',
    created_at: '2026-06-01T00:00:00Z',
  }
}

describe('daysInMonth', () => {
  it.each([
    [2026, 1, 28],
    [2028, 1, 29],
    [2026, 5, 30],
    [2026, 6, 31],
  ])('%i/%i has %i days', (year, month, expected) => {
    expect(daysInMonth(year, month)).toBe(expected)
  })
})

describe('bookingsOnDay', () => {
  // 10:00–10:00 czasu warszawskiego (lato: UTC+2), 10-12 lipca.
  const stay = booking('2026-07-10T08:00:00Z', '2026-07-12T08:00:00Z')

  it.each([
    [9, false],
    [10, true],
    [11, true],
    [12, true],
    [13, false],
  ])('day %i occupied=%s', (day, occupied) => {
    expect(bookingsOnDay([stay], 'r1', new Date(2026, 6, day)).length > 0).toBe(occupied)
  })

  it('does not occupy the day when the booking ends exactly at midnight', () => {
    // Koniec 12 lipca 00:00 czasu lokalnego = 11 lipca 22:00 UTC.
    const endsAtMidnight = booking('2026-07-10T08:00:00Z', '2026-07-11T22:00:00Z')

    expect(bookingsOnDay([endsAtMidnight], 'r1', new Date(2026, 6, 11))).toHaveLength(1)
    expect(bookingsOnDay([endsAtMidnight], 'r1', new Date(2026, 6, 12))).toHaveLength(0)
  })

  it('only returns bookings of the requested resource', () => {
    expect(bookingsOnDay([stay], 'other', new Date(2026, 6, 10))).toHaveLength(0)
  })

  it('returns every booking on a shared day', () => {
    const morning = booking('2026-07-15T05:00:00Z', '2026-07-15T08:00:00Z')
    const evening = booking('2026-07-15T12:00:00Z', '2026-07-15T16:00:00Z')

    expect(bookingsOnDay([morning, evening], 'r1', new Date(2026, 6, 15))).toHaveLength(2)
  })
})
