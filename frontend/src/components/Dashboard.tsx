import { useCallback, useEffect, useState } from 'react'
import type { Booking, NewBooking, Resource } from '../api/types'
import { useAuth } from '../auth/AuthContext'
import { messageForError } from '../lib/errorMessages'
import { BookingForm } from './BookingForm'
import { BookingList } from './BookingList'
import { OccupancyCalendar } from './OccupancyCalendar'

export function Dashboard() {
  const { api } = useAuth()
  const [resources, setResources] = useState<Resource[]>([])
  const [bookings, setBookings] = useState<Booking[]>([])
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [view, setView] = useState(() => {
    const now = new Date()

    return { year: now.getFullYear(), monthIndex: now.getMonth() }
  })

  const load = useCallback(async () => {
    setLoadError(null)
    try {
      const [loadedResources, loadedBookings] = await Promise.all([api.listResources(), api.listBookings()])
      setResources(loadedResources)
      setBookings(loadedBookings)
    } catch (e) {
      setLoadError(messageForError(e, 'Nie udało się wczytać danych.'))
    } finally {
      setLoading(false)
    }
  }, [api])

  useEffect(() => {
    void load()
  }, [load])

  async function createBooking(booking: NewBooking) {
    await api.createBooking(booking)
    await load()
  }

  function shiftMonth(delta: number) {
    setView((v) => {
      const next = new Date(v.year, v.monthIndex + delta, 1)

      return { year: next.getFullYear(), monthIndex: next.getMonth() }
    })
  }

  if (loading) {
    return <p className="muted">Ładowanie…</p>
  }

  return (
    <>
      {loadError && (
        <p role="alert" className="error">
          {loadError} <button type="button" onClick={() => void load()}>Spróbuj ponownie</button>
        </p>
      )}
      <BookingForm resources={resources} bookings={bookings} onSubmit={createBooking} />
      <OccupancyCalendar
        resources={resources}
        bookings={bookings}
        year={view.year}
        monthIndex={view.monthIndex}
        onMonthChange={shiftMonth}
      />
      <BookingList bookings={bookings} />
    </>
  )
}
