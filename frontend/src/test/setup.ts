// Stała strefa czasowa: testy dat (dni kalendarza, wartości datetime-local) muszą być deterministyczne.
process.env.TZ = 'Europe/Warsaw'

import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterEach } from 'vitest'

afterEach(() => {
  cleanup()
  sessionStorage.clear()
})
