import type { CategoryType } from './Category'

export interface MonthlyStatsCategory {
  categoryId: number
  categoryName: string
  type: CategoryType
  total: string
  budgetLimit?: string
  budgetExceeded?: boolean
}

export interface MonthlyStats {
  month: string
  income: string
  expense: string
  balance: string
  byCategory: MonthlyStatsCategory[]
}
