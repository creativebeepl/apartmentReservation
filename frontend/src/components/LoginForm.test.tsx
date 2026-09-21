import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ApiError } from '../api/client'
import { LoginForm } from './LoginForm'

describe('LoginForm', () => {
  it('submits trimmed e-mail and the password', async () => {
    const onLogin = vi.fn().mockResolvedValue(undefined)
    render(<LoginForm onLogin={onLogin} />)

    await userEvent.type(screen.getByLabelText('E-mail'), '  jan@example.com ')
    await userEvent.type(screen.getByLabelText('Hasło'), 'secret-123')
    await userEvent.click(screen.getByRole('button', { name: 'Zaloguj się' }))

    expect(onLogin).toHaveBeenCalledWith('jan@example.com', 'secret-123')
  })

  it('does not call the API with empty fields', async () => {
    const onLogin = vi.fn()
    render(<LoginForm onLogin={onLogin} />)

    await userEvent.click(screen.getByRole('button', { name: 'Zaloguj się' }))

    expect(screen.getByRole('alert')).toHaveTextContent('Podaj e-mail i hasło.')
    expect(onLogin).not.toHaveBeenCalled()
  })

  it('shows a translated message for invalid credentials', async () => {
    const onLogin = vi.fn().mockRejectedValue(new ApiError(401, 'invalid_credentials', 'Invalid credentials.'))
    render(<LoginForm onLogin={onLogin} />)

    await userEvent.type(screen.getByLabelText('E-mail'), 'jan@example.com')
    await userEvent.type(screen.getByLabelText('Hasło'), 'wrong')
    await userEvent.click(screen.getByRole('button', { name: 'Zaloguj się' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Nieprawidłowy e-mail lub hasło.')
  })

  it('shows a rate limit message', async () => {
    const onLogin = vi.fn().mockRejectedValue(new ApiError(429, 'too_many_requests', 'Too many login attempts.'))
    render(<LoginForm onLogin={onLogin} />)

    await userEvent.type(screen.getByLabelText('E-mail'), 'jan@example.com')
    await userEvent.type(screen.getByLabelText('Hasło'), 'x')
    await userEvent.click(screen.getByRole('button', { name: 'Zaloguj się' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Zbyt wiele prób')
  })
})
