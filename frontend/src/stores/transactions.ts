import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiClient } from '../api/client'
import { getCurrentMonth } from '../composables/useDateFormat'
import type { Transaction } from '../types'

export interface TransactionPayload {
  categoryId: number
  amount: string
  description: string | null
  date: string
}

export const useTransactionsStore = defineStore('transactions', () => {
  const list = ref<Transaction[]>([])
  const currentMonth = ref(getCurrentMonth())

  async function fetchByMonth(month: string, categoryId?: number): Promise<void> {
    currentMonth.value = month

    const { data } = await apiClient.get<Transaction[]>('/transactions', {
      params: { month, categoryId },
    })
    list.value = data
  }

  async function create(payload: TransactionPayload): Promise<void> {
    const { data } = await apiClient.post<Transaction>('/transactions', payload)
    list.value.push(data)
  }

  async function update(id: number, payload: TransactionPayload): Promise<void> {
    const { data } = await apiClient.put<Transaction>(`/transactions/${id}`, payload)
    const index = list.value.findIndex((transaction) => transaction.id === id)
    if (index !== -1) {
      list.value[index] = data
    }
  }

  async function remove(id: number): Promise<void> {
    await apiClient.delete(`/transactions/${id}`)
    list.value = list.value.filter((transaction) => transaction.id !== id)
  }

  return { list, currentMonth, fetchByMonth, create, update, remove }
})
