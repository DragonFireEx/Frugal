import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiClient } from '../api/client'
import type { Budget } from '../types'

export interface BudgetPayload {
  categoryId: number
  monthlyLimit: string
}

export const useBudgetsStore = defineStore('budgets', () => {
  const list = ref<Budget[]>([])

  async function fetchAll(): Promise<void> {
    const { data } = await apiClient.get<Budget[]>('/budgets')
    list.value = data
  }

  async function create(payload: BudgetPayload): Promise<void> {
    const { data } = await apiClient.post<Budget>('/budgets', payload)
    list.value.push(data)
  }

  async function update(id: number, payload: BudgetPayload): Promise<void> {
    const { data } = await apiClient.put<Budget>(`/budgets/${id}`, payload)
    const index = list.value.findIndex((budget) => budget.id === id)
    if (index !== -1) {
      list.value[index] = data
    }
  }

  return { list, fetchAll, create, update }
})
