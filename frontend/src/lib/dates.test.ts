import { parseLocalInput, toApiInstant, toLocalInputValue } from './dates'

describe('dates', () => {
  it('parses datetime-local values as local time', () => {
    const parsed = parseLocalInput('2026-07-01T10:00')

    expect(parsed).toEqual(new Date(2026, 6, 1, 10, 0))
  })

  it.each(['', 'garbage', '2026-13-45T10:00'])('returns null for %j', (value) => {
    expect(parseLocalInput(value)).toBeNull()
  })

  it('formats an instant as UTC ISO 8601 with an explicit zone', () => {
    // 10:00 w Warszawie (lato, UTC+2) to 08:00 UTC.
    expect(toApiInstant(new Date(2026, 6, 1, 10, 0))).toBe('2026-07-01T08:00:00.000Z')
  })

  it('round-trips through the datetime-local format', () => {
    const date = new Date(2026, 0, 5, 7, 3)

    expect(toLocalInputValue(date)).toBe('2026-01-05T07:03')
    expect(parseLocalInput(toLocalInputValue(date))).toEqual(date)
  })
})
