/** Przedziały półotwarte [start, end): stykające się końcami nie kolidują (jak w API). */
export function intervalsOverlap(aStart: number, aEnd: number, bStart: number, bEnd: number): boolean {
  return aStart < bEnd && aEnd > bStart
}
