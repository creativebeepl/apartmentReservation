import { ApiError, createApiClient } from './client'

function jsonResponse(status: number, body: unknown): Response {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function setup(fetchImpl: typeof fetch, token: string | null = 'secret-token') {
  const onUnauthorized = vi.fn()
  const client = createApiClient({ getToken: () => token, onUnauthorized, fetchImpl })

  return { client, onUnauthorized }
}

describe('api client', () => {
  it('sends the bearer token and unwraps "data"', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse(200, { data: [{ id: 'r1', name: 'A' }] }))
    const { client } = setup(fetchImpl)

    await expect(client.listResources()).resolves.toEqual([{ id: 'r1', name: 'A' }])

    const [url, init] = fetchImpl.mock.calls[0] as [string, RequestInit]
    expect(url).toBe('/api/resources')
    expect((init.headers as Record<string, string>).Authorization).toBe('Bearer secret-token')
  })

  it('does not send an Authorization header without a token', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse(200, { data: { token: 't', expires_at: 'x' } }))
    const { client } = setup(fetchImpl, null)

    await client.login('a@b.pl', 'pw')

    const [url, init] = fetchImpl.mock.calls[0] as [string, RequestInit]
    expect(url).toBe('/api/auth/login')
    expect(init.method).toBe('POST')
    expect(init.body).toBe(JSON.stringify({ email: 'a@b.pl', password: 'pw' }))
    expect((init.headers as Record<string, string>).Authorization).toBeUndefined()
  })

  it('posts a booking as JSON', async () => {
    const fetchImpl = vi.fn().mockResolvedValue(jsonResponse(201, { data: { id: 'b1' } }))
    const { client } = setup(fetchImpl)
    const booking = { resource_id: 'r1', start_at: 's', end_at: 'e', customer_name: 'Jan' }

    await client.createBooking(booking)

    const [url, init] = fetchImpl.mock.calls[0] as [string, RequestInit]
    expect(url).toBe('/api/bookings')
    expect(init.method).toBe('POST')
    expect(init.body).toBe(JSON.stringify(booking))
    expect((init.headers as Record<string, string>)['Content-Type']).toBe('application/json')
  })

  it('turns error responses into ApiError with code and field errors', async () => {
    const body = { message: 'Validation failed.', code: 'validation_failed', errors: { end_at: ['bad'] } }
    const { client } = setup(vi.fn().mockResolvedValue(jsonResponse(422, body)))

    const error = await client.listBookings().catch((e: unknown) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({ status: 422, code: 'validation_failed', fieldErrors: { end_at: ['bad'] } })
  })

  it('reports 409 conflicts with their code', async () => {
    const { client } = setup(vi.fn().mockResolvedValue(jsonResponse(409, { message: 'x', code: 'slot_conflict' })))

    await expect(client.createBooking({ resource_id: 'r', start_at: 's', end_at: 'e', customer_name: 'n' }))
      .rejects.toMatchObject({ status: 409, code: 'slot_conflict' })
  })

  it('calls onUnauthorized when a protected endpoint returns 401', async () => {
    const { client, onUnauthorized } = setup(vi.fn().mockResolvedValue(jsonResponse(401, { message: 'x', code: 'unauthenticated' })))

    await expect(client.listBookings()).rejects.toMatchObject({ status: 401 })
    expect(onUnauthorized).toHaveBeenCalledOnce()
  })

  it('does not treat a failed login as an expired session', async () => {
    const { client, onUnauthorized } = setup(
      vi.fn().mockResolvedValue(jsonResponse(401, { message: 'x', code: 'invalid_credentials' })),
      null,
    )

    await expect(client.login('a@b.pl', 'bad')).rejects.toMatchObject({ code: 'invalid_credentials' })
    expect(onUnauthorized).not.toHaveBeenCalled()
  })

  it('maps network failures to a network_error', async () => {
    const { client } = setup(vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    await expect(client.listResources()).rejects.toMatchObject({ status: 0, code: 'network_error' })
  })

  it('survives a non-JSON error body', async () => {
    const { client } = setup(vi.fn().mockResolvedValue(new Response('<html>Bad gateway</html>', { status: 502 })))

    await expect(client.listResources()).rejects.toMatchObject({ status: 502, code: 'unknown_error' })
  })
})
