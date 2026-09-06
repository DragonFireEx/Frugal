export type RecurringTransactionFrequency = 'weekly' | 'monthly'

export interface RecurringTransaction {
  id: number
  categoryId: number
  amount: string
  description: string | null
  frequency: RecurringTransactionFrequency
  nextRunDate: string
  active: boolean
}
