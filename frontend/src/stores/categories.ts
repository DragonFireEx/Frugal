import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiClient } from '../api/client'
import type { Category, CategoryType } from '../types'

export interface CategoryPayload {
  name: string
  type: CategoryType
  color: string
  icon: string | null
}

export const useCategoriesStore = defineStore('categories', () => {
  const list = ref<Category[]>([])

  async function fetchAll(): Promise<void> {
    const { data } = await apiClient.get<Category[]>('/categories')
    list.value = data
  }

  async function create(payload: CategoryPayload): Promise<void> {
    const { data } = await apiClient.post<Category>('/categories', payload)
    list.value.push(data)
  }

  async function update(id: number, payload: CategoryPayload): Promise<void> {
    const { data } = await apiClient.put<Category>(`/categories/${id}`, payload)
    const index = list.value.findIndex((category) => category.id === id)
    if (index !== -1) {
      list.value[index] = data
    }
  }

  async function remove(id: number): Promise<void> {
    await apiClient.delete(`/categories/${id}`)
    list.value = list.value.filter((category) => category.id !== id)
  }

  return { list, fetchAll, create, update, remove }
})
