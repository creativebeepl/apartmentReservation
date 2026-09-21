import type { Booking, LoginResult, NewBooking, Resource } from './types'

/** Błąd zwrócony przez API (lub brak połączenia: status 0, kod "network_error"). */
export class ApiError extends Error {
  readonly status: number
  readonly code: string
  readonly fieldErrors: Record<string, string[]>

  constructor(status: number, code: string, message: string, fieldErrors: Record<string, string[]> = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.fieldErrors = fieldErrors
  }
}

export interface ApiClientOptions {
  getToken: () => string | null
  /** Wywoływane, gdy API odrzuci token (np. wygasł) — zwykle wylogowanie. */
  onUnauthorized: () => void
  fetchImpl?: typeof fetch
}

export interface ApiClient {
  login: (email: string, password: string) => Promise<LoginResult>
  listResources: () => Promise<Resource[]>
  listBookings: () => Promise<Booking[]>
  createBooking: (booking: NewBooking) => Promise<Booking>
}

const LOGIN_PATH = '/api/auth/login'

interface ErrorBody {
  message?: string
  code?: string
  errors?: Record<string, string[]>
}

export function createApiClient(options: ApiClientOptions): ApiClient {
  const doFetch = options.fetchImpl ?? ((...args: Parameters<typeof fetch>) => fetch(...args))

  async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
    const headers: Record<string, string> = { Accept: 'application/json' }
    if (body !== undefined) {
      headers['Content-Type'] = 'application/json'
    }
    const token = options.getToken()
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    let response: Response
    try {
      response = await doFetch(path, {
        method,
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
      })
    } catch {
      throw new ApiError(0, 'network_error', 'Nie można połączyć się z serwerem.')
    }

    const payload: unknown = await response.json().catch(() => null)

    if (!response.ok) {
      const error = (payload ?? {}) as ErrorBody
      if (response.status === 401 && path !== LOGIN_PATH) {
        options.onUnauthorized()
      }
      throw new ApiError(
        response.status,
        error.code ?? 'unknown_error',
        error.message ?? `Błąd HTTP ${response.status}`,
        error.errors ?? {},
      )
    }

    return (payload as { data: T }).data
  }

  return {
    login: (email, password) => request<LoginResult>('POST', LOGIN_PATH, { email, password }),
    listResources: () => request<Resource[]>('GET', '/api/resources'),
    listBookings: () => request<Booking[]>('GET', '/api/bookings'),
    createBooking: (booking) => request<Booking>('POST', '/api/bookings', booking),
  }
}
