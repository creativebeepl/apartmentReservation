import { useState, type FormEvent } from 'react'
import { messageForError } from '../lib/errorMessages'

interface Props {
  onLogin: (email: string, password: string) => Promise<void>
}

export function LoginForm({ onLogin }: Props) {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    if (!email.trim() || !password) {
      setError('Podaj e-mail i hasło.')

      return
    }
    setError(null)
    setSubmitting(true)
    try {
      await onLogin(email.trim(), password)
    } catch (e) {
      setError(messageForError(e, 'Nie udało się zalogować.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form className="card" onSubmit={handleSubmit} noValidate aria-label="Logowanie">
      <h2>Logowanie</h2>
      <label>
        E-mail
        <input type="email" value={email} onChange={e => setEmail(e.target.value)} autoComplete="username" />
      </label>
      <label>
        Hasło
        <input type="password" value={password} onChange={e => setPassword(e.target.value)} autoComplete="current-password" />
      </label>
      {error && <p role="alert" className="error">{error}</p>}
      <button type="submit" disabled={submitting}>{submitting ? 'Logowanie…' : 'Zaloguj się'}</button>
    </form>
  )
}
