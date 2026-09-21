import react from '@vitejs/plugin-react'
import { defineConfig } from 'vitest/config'

// Przeglądarka rozmawia tylko z serwerem Vite; ścieżki /api są proxy-owane do backendu,
// więc nie potrzeba CORS ani osobnej konfiguracji adresu API po stronie klienta.
const apiTarget = process.env.API_PROXY_TARGET ?? 'http://backend:8000'

export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 3000,
    proxy: {
      '/api': { target: apiTarget, changeOrigin: true },
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    css: false,
  },
})
