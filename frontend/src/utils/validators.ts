export function isBlank(value: string): boolean {
  return value.trim().length === 0
}

export function isPositiveNumber(value: string | number): boolean {
  if (typeof value === 'string' && value.trim() === '') {
    return false
  }
  const n = Number(value)
  return !Number.isNaN(n) && n > 0
}

export function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
}
