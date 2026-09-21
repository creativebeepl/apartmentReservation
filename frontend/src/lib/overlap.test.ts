import { intervalsOverlap } from './overlap'

describe('intervalsOverlap', () => {
  it.each([
    ['identical', 10, 14, 10, 14, true],
    ['contained', 10, 14, 11, 12, true],
    ['containing', 10, 14, 9, 15, true],
    ['overlapping start', 10, 14, 9, 11, true],
    ['overlapping end', 10, 14, 13, 18, true],
    ['touching at end', 10, 14, 14, 18, false],
    ['touching at start', 10, 14, 6, 10, false],
    ['before', 10, 14, 1, 2, false],
    ['after', 10, 14, 20, 30, false],
  ])('%s', (_name, aStart, aEnd, bStart, bEnd, expected) => {
    expect(intervalsOverlap(aStart, aEnd, bStart, bEnd)).toBe(expected)
    expect(intervalsOverlap(bStart, bEnd, aStart, aEnd)).toBe(expected)
  })
})
