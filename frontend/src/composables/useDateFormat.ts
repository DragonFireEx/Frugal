// date-only strings (YYYY-MM-DD) parse as UTC midnight; format in UTC too so the
// calendar date shown always matches the literal string, regardless of viewer timezone.
const dateFormatter = new Intl.DateTimeFormat('pl-PL', {
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  timeZone: 'UTC',
})

export function getCurrentMonth(): string {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

export function useDateFormat() {
  function formatDate(date: string): string {
    return dateFormatter.format(new Date(date))
  }

  return { formatDate }
}
