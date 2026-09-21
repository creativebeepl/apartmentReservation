import type { Booking } from '../api/types'
import { formatDateTime } from '../lib/dates'

export function BookingList({ bookings }: { bookings: Booking[] }) {
  return (
    <section className="card" aria-label="Lista rezerwacji">
      <h2>Rezerwacje</h2>
      {bookings.length === 0
        ? <p className="muted">Brak rezerwacji.</p>
        : (
            <table className="list">
              <thead>
                <tr>
                  <th scope="col">Apartament</th>
                  <th scope="col">Od</th>
                  <th scope="col">Do</th>
                  <th scope="col">Klient</th>
                </tr>
              </thead>
              <tbody>
                {bookings.map(b => (
                  <tr key={b.id}>
                    <td>{b.resource_name}</td>
                    <td>{formatDateTime(b.start_at)}</td>
                    <td>{formatDateTime(b.end_at)}</td>
                    <td>{b.customer_name}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
    </section>
  )
}
