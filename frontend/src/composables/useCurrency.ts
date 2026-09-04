const formatter = new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' })

export function useCurrency() {
  function formatCurrency(amount: string | number): string {
    const value = typeof amount === 'string' ? Number(amount) : amount
    return formatter.format(value)
  }

  return { formatCurrency }
}
