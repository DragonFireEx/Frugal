import { describe, expect, it } from 'vitest'
import { useCurrency } from './useCurrency'

describe('useCurrency', () => {
  const { formatCurrency } = useCurrency()

  it('formats a decimal string as PLN currency', () => {
    expect(formatCurrency('1234.50')).toBe('1234,50 zł')
  })

  it('formats a plain number', () => {
    expect(formatCurrency(0)).toBe('0,00 zł')
  })

  it('formats negative amounts', () => {
    expect(formatCurrency('-50.00')).toBe('-50,00 zł')
  })

  it('rounds to two decimal places', () => {
    expect(formatCurrency('10.005')).toBe('10,01 zł')
  })
})
