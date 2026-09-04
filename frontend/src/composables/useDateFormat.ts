const dateFormatter = new Intl.DateTimeFormat('pl-PL', { year: 'numeric', month: '2-digit', day: '2-digit' })

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
