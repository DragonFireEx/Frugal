import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiClient } from '../api/client'
import type { RecurringTransaction, RecurringTransactionFrequency } from '../types'

export interface RecurringTransactionPayload {
  categoryId: number
  amount: string
  description: string | null
  frequency: RecurringTransactionFrequency
  nextRunDate: string
  active: boolean
}

export const useRecurringTransactionsStore = defineStore('recurringTransactions', () => {
  const list = ref<RecurringTransaction[]>([])

  async function fetchAll(): Promise<void> {
    const { data } = await apiClient.get<RecurringTransaction[]>('/recurring-transactions')
    list.value = data
  }

  async function create(payload: RecurringTransactionPayload): Promise<void> {
    const { data } = await apiClient.post<RecurringTransaction>('/recurring-transactions', payload)
    list.value.push(data)
  }

  async function update(id: number, payload: RecurringTransactionPayload): Promise<void> {
    const { data } = await apiClient.put<RecurringTransaction>(`/recurring-transactions/${id}`, payload)
    const index = list.value.findIndex((item) => item.id === id)
    if (index !== -1) {
      list.value[index] = data
    }
  }

  async function remove(id: number): Promise<void> {
    await apiClient.delete(`/recurring-transactions/${id}`)
    list.value = list.value.filter((item) => item.id !== id)
  }

  return { list, fetchAll, create, update, remove }
})
