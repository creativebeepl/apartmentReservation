import { fireEvent, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ApiError } from '../api/client'
import type { Booking } from '../api/types'
import { BookingForm } from './BookingForm'

const NOW = new Date(2026, 5, 1, 12, 0, 0)
const resources = [
  { id: 'r1', name: 'Apartament 101' },
  { id: 'r2', name: 'Apartament 102' },
]

function fill(values: { resource?: string, start?: string, end?: string, name?: string }) {
  if (values.resource) {
    fireEvent.change(screen.getByLabelText('Apartament'), { target: { value: values.resource } })
  }
  if (values.start) {
    fireEvent.change(screen.getByLabelText('Początek'), { target: { value: values.start } })
  }
  if (values.end) {
    fireEvent.change(screen.getByLabelText('Koniec'), { target: { value: values.end } })
  }
  if (values.name) {
    fireEvent.change(screen.getByLabelText('Imię i nazwisko'), { target: { value: values.name } })
  }
}

const submit = () => userEvent.click(screen.getByRole('button', { name: 'Zarezerwuj' }))

function renderForm(onSubmit = vi.fn().mockResolvedValue(undefined), bookings: Booking[] = []) {
  render(<BookingForm resources={resources} bookings={bookings} onSubmit={onSubmit} now={() => NOW} />)

  return onSubmit
}

describe('BookingForm', () => {
  it('lists the resources', () => {
    renderForm()

    expect(screen.getByRole('option', { name: 'Apartament 101' })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: 'Apartament 102' })).toBeInTheDocument()
  })

  it('limits the pickers to the allowed booking window', () => {
    renderForm()

    expect(screen.getByLabelText('Początek')).toHaveAttribute('min', '2026-06-01T12:00')
    expect(screen.getByLabelText('Początek')).toHaveAttribute('max', '2027-06-01T12:00')
  })

  it('submits a valid booking with UTC instants', async () => {
    const onSubmit = renderForm()

    fill({ resource: 'r2', start: '2026-07-01T10:00', end: '2026-07-03T10:00', name: 'Jan Kowalski' })
    await submit()

    expect(onSubmit).toHaveBeenCalledWith({
      resource_id: 'r2',
      start_at: '2026-07-01T08:00:00.000Z',
      end_at: '2026-07-03T08:00:00.000Z',
      customer_name: 'Jan Kowalski',
    })
    expect(await screen.findByRole('status')).toHaveTextContent('Rezerwacja zapisana dla Jan Kowalski.')
    expect(screen.getByLabelText('Imię i nazwisko')).toHaveValue('')
  })

  it('shows field errors and does not call the API for an empty form', async () => {
    const onSubmit = renderForm()

    await submit()

    expect(onSubmit).not.toHaveBeenCalled()
    expect(screen.getByText('Wybierz apartament.')).toBeInTheDocument()
    expect(screen.getByText('Podaj początek rezerwacji.')).toBeInTheDocument()
    expect(screen.getByText('Podaj koniec rezerwacji.')).toBeInTheDocument()
    expect(screen.getByText('Podaj imię i nazwisko.')).toBeInTheDocument()
  })

  it('rejects a booking that starts in the past', async () => {
    const onSubmit = renderForm()

    fill({ resource: 'r1', start: '2026-05-01T10:00', end: '2026-05-03T10:00', name: 'Jan' })
    await submit()

    expect(onSubmit).not.toHaveBeenCalled()
    expect(screen.getByText('Rezerwacja nie może zaczynać się w przeszłości.')).toBeInTheDocument()
  })

  it('rejects a booking beyond one year ahead', async () => {
    const onSubmit = renderForm()

    fill({ resource: 'r1', start: '2027-05-30T10:00', end: '2027-07-01T10:00', name: 'Jan' })
    await submit()

    expect(onSubmit).not.toHaveBeenCalled()
    expect(screen.getByText(/najwyżej 1 rok do przodu/)).toBeInTheDocument()
  })

  it('rejects a booking that collides with an existing one', async () => {
    const existing: Booking = {
      id: 'b1',
      resource_id: 'r1',
      resource_name: 'Apartament 101',
      start_at: new Date(2026, 6, 1, 10, 0).toISOString(),
      end_at: new Date(2026, 6, 5, 10, 0).toISOString(),
      customer_name: 'Ktoś',
      created_at: '2026-06-01T00:00:00Z',
    }
    const onSubmit = renderForm(undefined, [existing])

    fill({ resource: 'r1', start: '2026-07-03T10:00', end: '2026-07-06T10:00', name: 'Jan' })
    await submit()

    expect(onSubmit).not.toHaveBeenCalled()
    expect(screen.getByText(/koliduje z istniejącą rezerwacją/)).toBeInTheDocument()
  })

  it('shows a translated message when the server reports a conflict', async () => {
    const onSubmit = vi.fn().mockRejectedValue(new ApiError(409, 'slot_conflict', 'The selected period overlaps...'))
    renderForm(onSubmit)

    fill({ resource: 'r1', start: '2026-07-01T10:00', end: '2026-07-03T10:00', name: 'Jan' })
    await submit()

    expect(await screen.findByRole('alert')).toHaveTextContent('Wybrany termin koliduje z istniejącą rezerwacją')
    expect(screen.queryByRole('status')).not.toBeInTheDocument()
  })

  it('maps server validation errors onto the fields', async () => {
    const onSubmit = vi.fn().mockRejectedValue(
      new ApiError(422, 'validation_failed', 'Validation failed.', { customer_name: ['Too long.'] }),
    )
    renderForm(onSubmit)

    fill({ resource: 'r1', start: '2026-07-01T10:00', end: '2026-07-03T10:00', name: 'Jan' })
    await submit()

    expect(await screen.findByText('Too long.')).toBeInTheDocument()
    expect(screen.getByRole('alert')).toHaveTextContent('Sprawdź poprawność wprowadzonych danych.')
  })

  it('disables submitting when there are no resources', () => {
    render(<BookingForm resources={[]} bookings={[]} onSubmit={vi.fn()} now={() => NOW} />)

    expect(screen.getByRole('button', { name: 'Zarezerwuj' })).toBeDisabled()
  })
})
