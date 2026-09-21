import type { Booking, Resource } from '../api/types'
import { bookingsOnDay, daysInMonth, formatMonthTitle } from '../lib/calendar'
import { formatDateTime } from '../lib/dates'

interface Props {
  resources: Resource[]
  bookings: Booking[]
  year: number
  monthIndex: number
  onMonthChange: (delta: number) => void
  today?: Date
}

export function OccupancyCalendar({ resources, bookings, year, monthIndex, onMonthChange, today = new Date() }: Props) {
  const days = Array.from({ length: daysInMonth(year, monthIndex) }, (_, i) => new Date(year, monthIndex, i + 1))
  const isToday = (d: Date) =>
    d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth() && d.getDate() === today.getDate()

  return (
    <section className="card" aria-label="Kalendarz zajętości">
      <div className="calendar-header">
        <button type="button" onClick={() => onMonthChange(-1)} aria-label="Poprzedni miesiąc">‹</button>
        <h2>{formatMonthTitle(year, monthIndex)}</h2>
        <button type="button" onClick={() => onMonthChange(1)} aria-label="Następny miesiąc">›</button>
      </div>

      <div className="calendar-scroll">
        <table className="calendar">
          <thead>
            <tr>
              <th scope="col" className="sticky">Apartament</th>
              {days.map(d => (
                <th key={d.getDate()} scope="col" className={isToday(d) ? 'today' : undefined}>{d.getDate()}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {resources.map(resource => (
              <tr key={resource.id}>
                <th scope="row" className="sticky">{resource.name}</th>
                {days.map(day => {
                  const list = bookingsOnDay(bookings, resource.id, day)
                  const busy = list.length > 0
                  const details = list.map(b => `${formatDateTime(b.start_at)} – ${formatDateTime(b.end_at)}`).join('\n')

                  return (
                    <td
                      key={day.getDate()}
                      className={[busy ? 'busy' : 'free', isToday(day) ? 'today' : ''].join(' ').trim()}
                      title={details}
                      aria-label={busy ? `Zajęte: ${resource.name}, ${day.getDate()}` : `Wolne: ${resource.name}, ${day.getDate()}`}
                    />
                  )
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
