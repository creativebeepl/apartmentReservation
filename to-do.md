# Do zrobienia: rezerwacja apartamentów

Technologia: 
+ Symfony 7.4 (API) + React 19 (Vite, TypeScript)
+ Docker, 
+ domyślnie **SQLite**.
## API

Wszystko poza logowaniem wymaga nagłówka `Authorization: Bearer <token>`. Odpowiedzi sukcesu mają kształt `{"data": ...}`.

| Metoda |     Ścieżka       |
|--------|-------------------|
| POST   | `/api/auth/login` |
| GET    | `/api/resources`  |
| GET    | `/api/bookings`   |
| POST   | `/api/bookings`   |

Czasy w odpowiedziach są zawsze w UTC

### Błędy

| HTTP |         `code`          |                              Kiedy                                             |
|------|-------------------------|--------------------------------------------------------------------------------|
| 401  | `unauthenticated`       | brak, błędny lub wygasły token                                                 |
| 401  | `invalid_credentials`   | złe dane logowania (identyczna odpowiedź dla nieznanego e-maila i złego hasła) |
| 422  | `validation_failed`     | brakujące/niepoprawne pola; `errors` mapuje pole → komunikaty                  |
| 422  | `booking_in_past`       | rezerwacja zaczyna się w przeszłości                                           |
| 422  | `booking_too_far_ahead` | rezerwacja kończy się później niż za rok                                       |
| 422  | `invalid_period`        | koniec nie jest po początku (po normalizacji do sekund)                        |
| 404  | `resource_not_found`    | `resource_id` poprawny, ale taki apartament nie istnieje                       |
| 409  | `slot_conflict`         | termin nakłada się na istniejącą rezerwację tego apartamentu                   |
| 429  | `too_many_requests`     | zbyt wiele prób logowania (nagłówek `Retry-After`)                             |

## Reguły biznesowe i bezpieczeństwo

- **Uwierzytelnianie**: logowanie e-mail + hasło zwraca losowy token (256 bit), ważny domyślnie 12 h (`API_TOKEN_TTL`). W bazie jest tylko jego skrót SHA-256. Hasła są haszowane algorytmem `auto` Symfony.
- **Limit prób logowania**: 5/min na parę IP+e-mail i 30/min na samo IP (sliding window).
- **Nie w przeszłości**: `start_at` nie może być wcześniejszy niż „teraz”.
- **Najwyżej rok do przodu**: `end_at` nie może być późniejszy niż „teraz + `BOOKING_MAX_ADVANCE`” (domyślnie `P1Y`).
- **Kolizje**: przedziały są półotwarte `[start, end)` — rezerwacja kończąca się o 12:00 nie blokuje kolejnej zaczynającej się o 12:00. Kolizje liczą się tylko w obrębie jednego apartamentu.
- **Strefy czasowe**: API wymaga jawnej strefy w datach (bez niej, jak i np. `tomorrow`, jest 422). Wszystko jest normalizowane do UTC i pełnych sekund, więc porównywane są chwile, a nie godziny na zegarze.

## Architektura (SOLID)

SOLID

## Testy i statyczna analiza

Backend
Frontend

Testy backendu używają osobnej bazy (`var/data_test.db`), więc nie ruszają danych deweloperskich.