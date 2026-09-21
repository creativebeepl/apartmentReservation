import { createContext, useCallback, useContext, useMemo, useRef, useState, type ReactNode } from 'react'
import { createApiClient, type ApiClient } from '../api/client'

const STORAGE_KEY = 'reservation.auth'

interface StoredAuth {
  token: string
  expiresAt: string
}

/** Token trzymamy w sessionStorage (znika z zamknięciem karty) i odrzucamy po wygaśnięciu. */
function loadToken(): string | null {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return null
    }
    const stored = JSON.parse(raw) as StoredAuth
    if (Date.parse(stored.expiresAt) <= Date.now()) {
      sessionStorage.removeItem(STORAGE_KEY)

      return null
    }

    return stored.token
  } catch {
    return null
  }
}

interface AuthContextValue {
  isAuthenticated: boolean
  api: ApiClient
  login: (email: string, password: string) => Promise<void>
  logout: () => void
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children, fetchImpl }: { children: ReactNode, fetchImpl?: typeof fetch }) {
  const [token, setToken] = useState<string | null>(loadToken)
  // Klient czyta token z refa, więc jego tożsamość jest stabilna i nie wywołuje ponownych pobrań danych.
  const tokenRef = useRef(token)
  tokenRef.current = token

  const logout = useCallback(() => {
    sessionStorage.removeItem(STORAGE_KEY)
    setToken(null)
  }, [])

  const api = useMemo(
    () => createApiClient({ getToken: () => tokenRef.current, onUnauthorized: logout, fetchImpl }),
    [logout, fetchImpl],
  )

  const login = useCallback(
    async (email: string, password: string) => {
      const result = await api.login(email, password)
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ token: result.token, expiresAt: result.expires_at }))
      tokenRef.current = result.token
      setToken(result.token)
    },
    [api],
  )

  const value = useMemo(
    () => ({ isAuthenticated: token !== null, api, login, logout }),
    [token, api, login, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used inside <AuthProvider>.')
  }

  return context
}
