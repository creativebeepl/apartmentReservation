import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { Booking } from '../api/types'
import { OccupancyCalendar } from './OccupancyCalendar'

const resources = [
  { id: 'r1', name: 'Apartament 101' },
  { id: 'r2', name: 'Apartament 102' },
]

const bookings: Booking[] = [
  {
    id: 'b1',
    resource_id: 'r1',
    resource_name: 'Apartament 101',
    start_at: '2026-07-10T08:00:00Z',
    end_at: '2026-07-12T08:00:00Z',
    customer_name: 'Jan',
    created_at: '2026-06-01T00:00:00Z',
  },
]

function renderCalendar(onMonthChange = vi.fn(), monthIndex = 6) {
  render(
    <OccupancyCalendar
      resources={resources}
      bookings={bookings}
      year={2026}
      monthIndex={monthIndex}
      onMonthChange={onMonthChange}
      today={new Date(2026, 6, 15)}
    />,
  )

  return onMonthChange
}

describe('OccupancyCalendar', () => {
  it('renders a row per resource and a column per day of the month', () => {
    renderCalendar()

    expect(screen.getByRole('heading', { name: /lipiec 2026/i })).toBeInTheDocument()
    expect(screen.getByRole('rowheader', { name: 'Apartament 101' })).toBeInTheDocument()
    expect(screen.getByRole('rowheader', { name: 'Apartament 102' })).toBeInTheDocument()
    expect(screen.getAllByRole('columnheader')).toHaveLength(1 + 31)
  })

  it('marks exactly the booked days of the booked resource as busy', () => {
    renderCalendar()

    const busy = screen.getAllByLabelText(/^Zajęte:/)
    expect(busy.map(cell => cell.getAttribute('aria-label'))).toEqual([
      'Zajęte: Apartament 101, 10',
      'Zajęte: Apartament 101, 11',
      'Zajęte: Apartament 101, 12',
    ])
    expect(screen.queryAllByLabelText(/^Zajęte: Apartament 102/)).toHaveLength(0)
  })

  it('shows the booking period in the tooltip', () => {
    renderCalendar()

    const row = screen.getByRole('rowheader', { name: 'Apartament 101' }).closest('tr') as HTMLElement
    const cell = within(row).getByLabelText('Zajęte: Apartament 101, 11')

    expect(cell.getAttribute('title')).toContain('–')
  })

  it('highlights today', () => {
    renderCalendar()

    expect(screen.getByRole('columnheader', { name: '15' })).toHaveClass('today')
  })

  it('asks to change the month', async () => {
    const onMonthChange = renderCalendar()

    await userEvent.click(screen.getByRole('button', { name: 'Następny miesiąc' }))
    await userEvent.click(screen.getByRole('button', { name: 'Poprzedni miesiąc' }))

    expect(onMonthChange).toHaveBeenNthCalledWith(1, 1)
    expect(onMonthChange).toHaveBeenNthCalledWith(2, -1)
  })

  it('shows every resource as free in a month without bookings', () => {
    renderCalendar(vi.fn(), 7)

    expect(screen.queryAllByLabelText(/^Zajęte:/)).toHaveLength(0)
    expect(screen.getAllByLabelText(/^Wolne:/)).toHaveLength(2 * 31)
  })
})
