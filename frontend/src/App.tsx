import { AuthProvider, useAuth } from './auth/AuthContext'
import { Dashboard } from './components/Dashboard'
import { LoginForm } from './components/LoginForm'

function Shell() {
  const { isAuthenticated, login, logout } = useAuth()

  return (
    <main>
      <header className="app-header">
        <h1>Rezerwacja apartamentów</h1>
        {isAuthenticated && <button type="button" className="secondary" onClick={logout}>Wyloguj</button>}
      </header>
      {isAuthenticated ? <Dashboard /> : <LoginForm onLogin={login} />}
    </main>
  )
}

export function App({ fetchImpl }: { fetchImpl?: typeof fetch }) {
  return (
    <AuthProvider fetchImpl={fetchImpl}>
      <Shell />
    </AuthProvider>
  )
}
