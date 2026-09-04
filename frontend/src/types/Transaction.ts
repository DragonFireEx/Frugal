export interface Transaction {
  id: number
  categoryId: number
  amount: string
  description: string | null
  date: string
  createdAt: string
}
