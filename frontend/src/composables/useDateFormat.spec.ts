import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { getCurrentMonth, useDateFormat } from './useDateFormat'

describe('useDateFormat', () => {
  const { formatDate } = useDateFormat()

  it('formats a date-only string as DD.MM.YYYY', () => {
    expect(formatDate('2026-09-04')).toBe('04.09.2026')
  })

  it('does not shift the date at a year boundary (UTC midnight parsing)', () => {
    expect(formatDate('2026-01-01')).toBe('01.01.2026')
    expect(formatDate('2025-12-31')).toBe('31.12.2025')
  })
})

describe('getCurrentMonth', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('returns the current year and month as YYYY-MM', () => {
    vi.setSystemTime(new Date(2026, 8, 15))
    expect(getCurrentMonth()).toBe('2026-09')
  })

  it('pads single-digit months', () => {
    vi.setSystemTime(new Date(2026, 0, 5))
    expect(getCurrentMonth()).toBe('2026-01')
  })
})
