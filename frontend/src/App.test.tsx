import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { fireEvent } from '@testing-library/react'
import { App } from './App'
import type { Booking } from './api/types'
import { toLocalInputValue } from './lib/dates'

interface FakeServer {
  fetchImpl: typeof fetch
  calls: { method: string, path: string, authorization: string | null }[]
  bookings: Booking[]
  failBookingsWith?: number
}

function json(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function createServer(validPassword = 'secret-123'): FakeServer {
  const server: FakeServer = { calls: [], bookings: [], fetchImpl: undefined as unknown as typeof fetch }

  server.fetchImpl = (async (input: RequestInfo | URL, init?: RequestInit) => {
    const path = String(input)
    const method = init?.method ?? 'GET'
    const headers = (init?.headers ?? {}) as Record<string, string>
    server.calls.push({ method, path, authorization: headers.Authorization ?? null })

    if (path === '/api/auth/login') {
      const body = JSON.parse(String(init?.body)) as { password: string }

      return body.password === validPassword
        ? json(200, { data: { token: 'tok-1', token_type: 'Bearer', expires_at: new Date(Date.now() + 3_600_000).toISOString() } })
        : json(401, { message: 'Invalid credentials.', code: 'invalid_credentials' })
    }
    if (server.failBookingsWith && path === '/api/bookings' && method === 'GET') {
      return json(server.failBookingsWith, { message: 'x', code: 'unauthenticated' })
    }
    if (path === '/api/resources') {
      return json(200, { data: [{ id: 'r1', name: 'Apartament 101' }] })
    }
    if (path === '/api/bookings' && method === 'GET') {
      return json(200, { data: server.bookings })
    }
    if (path === '/api/bookings' && method === 'POST') {
      const body = JSON.parse(String(init?.body)) as Omit<Booking, 'id' | 'resource_name' | 'created_at'>
      const created: Booking = { ...body, id: 'b-new', resource_name: 'Apartament 101', created_at: new Date().toISOString() }
      server.bookings.push(created)

      return json(201, { data: created })
    }

    return json(404, { message: 'Not found', code: 'not_found' })
  }) as typeof fetch

  return server
}

async function logIn(password = 'secret-123') {
  await userEvent.type(screen.getByLabelText('E-mail'), 'jan@example.com')
  await userEvent.type(screen.getByLabelText('Hasło'), password)
  await userEvent.click(screen.getByRole('button', { name: 'Zaloguj się' }))
}

describe('App', () => {
  it('shows the login form when not authenticated and does not call the data API', () => {
    const server = createServer()
    render(<App fetchImpl={server.fetchImpl} />)

    expect(screen.getByRole('form', { name: 'Logowanie' })).toBeInTheDocument()
    expect(server.calls).toHaveLength(0)
  })

  it('logs in, loads data with the bearer token and shows the dashboard', async () => {
    const server = createServer()
    render(<App fetchImpl={server.fetchImpl} />)

    await logIn()

    expect(await screen.findByRole('form', { name: 'Nowa rezerwacja' })).toBeInTheDocument()
    const dataCalls = server.calls.filter(c => c.path !== '/api/auth/login')
    expect(dataCalls.map(c => c.path).sort()).toEqual(['/api/bookings', '/api/resources'])
    expect(dataCalls.every(c => c.authorization === 'Bearer tok-1')).toBe(true)
  })

  it('shows an error for wrong credentials and stays on the login form', async () => {
    const server = createServer()
    render(<App fetchImpl={server.fetchImpl} />)

    await logIn('wrong')

    expect(await screen.findByRole('alert')).toHaveTextContent('Nieprawidłowy e-mail lub hasło.')
    expect(screen.getByRole('form', { name: 'Logowanie' })).toBeInTheDocument()
  })

  it('creates a booking end to end and refreshes the list and the calendar', async () => {
    const server = createServer()
    render(<App fetchImpl={server.fetchImpl} />)
    await logIn()
    await screen.findByRole('form', { name: 'Nowa rezerwacja' })

    const start = new Date(Date.now() + 10 * 24 * 3_600_000)
    start.setHours(10, 0, 0, 0)
    const end = new Date(start.getTime() + 2 * 24 * 3_600_000)

    fireEvent.change(screen.getByLabelText('Apartament'), { target: { value: 'r1' } })
    fireEvent.change(screen.getByLabelText('Początek'), { target: { value: toLocalInputValue(start) } })
    fireEvent.change(screen.getByLabelText('Koniec'), { target: { value: toLocalInputValue(end) } })
    fireEvent.change(screen.getByLabelText('Imię i nazwisko'), { target: { value: 'Anna Nowak' } })
    await userEvent.click(screen.getByRole('button', { name: 'Zarezerwuj' }))

    expect(await screen.findByRole('status')).toHaveTextContent('Rezerwacja zapisana dla Anna Nowak.')

    const post = server.calls.find(c => c.method === 'POST' && c.path === '/api/bookings')
    expect(post?.authorization).toBe('Bearer tok-1')

    const list = screen.getByRole('region', { name: 'Lista rezerwacji' })
    await waitFor(() => expect(within(list).getByText('Anna Nowak')).toBeInTheDocument())
  })

  it('returns to the login form when the API rejects the token', async () => {
    const server = createServer()
    server.failBookingsWith = 401
    render(<App fetchImpl={server.fetchImpl} />)

    await logIn()

    expect(await screen.findByRole('form', { name: 'Logowanie' })).toBeInTheDocument()
  })

  it('logs out', async () => {
    const server = createServer()
    render(<App fetchImpl={server.fetchImpl} />)
    await logIn()
    await screen.findByRole('form', { name: 'Nowa rezerwacja' })

    await userEvent.click(screen.getByRole('button', { name: 'Wyloguj' }))

    expect(screen.getByRole('form', { name: 'Logowanie' })).toBeInTheDocument()
    expect(sessionStorage.getItem('reservation.auth')).toBeNull()
  })

  it('restores a still-valid session from sessionStorage', async () => {
    sessionStorage.setItem(
      'reservation.auth',
      JSON.stringify({ token: 'stored', expiresAt: new Date(Date.now() + 60_000).toISOString() }),
    )
    const server = createServer()
    render(<App fetchImpl={server.fetchImpl} />)

    expect(await screen.findByRole('form', { name: 'Nowa rezerwacja' })).toBeInTheDocument()
    expect(server.calls[0]?.authorization).toBe('Bearer stored')
  })

  it('ignores an expired stored session', () => {
    sessionStorage.setItem(
      'reservation.auth',
      JSON.stringify({ token: 'stale', expiresAt: new Date(Date.now() - 1000).toISOString() }),
    )
    const server = createServer()
    render(<App fetchImpl={server.fetchImpl} />)

    expect(screen.getByRole('form', { name: 'Logowanie' })).toBeInTheDocument()
    expect(server.calls).toHaveLength(0)
  })
})
