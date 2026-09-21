import type { Booking } from '../api/types'
import { intervalsOverlap } from './overlap'

export function daysInMonth(year: number, monthIndex: number): number {
  return new Date(year, monthIndex + 1, 0).getDate()
}

/** Rezerwacje zasobu zajmujące (choćby częściowo) dany dzień kalendarzowy w czasie lokalnym. */
export function bookingsOnDay(bookings: Booking[], resourceId: string, day: Date): Booking[] {
  const dayStart = new Date(day.getFullYear(), day.getMonth(), day.getDate()).getTime()
  const nextDayStart = new Date(day.getFullYear(), day.getMonth(), day.getDate() + 1).getTime()

  return bookings.filter(
    b =>
      b.resource_id === resourceId
      && intervalsOverlap(Date.parse(b.start_at), Date.parse(b.end_at), dayStart, nextDayStart),
  )
}

export function formatMonthTitle(year: number, monthIndex: number): string {
  return new Intl.DateTimeFormat('pl-PL', { month: 'long', year: 'numeric' }).format(new Date(year, monthIndex, 1))
}
