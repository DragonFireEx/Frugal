import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiClient } from '../api/client'
import type { Tag } from '../types'

export interface TagPayload {
  name: string
}

export const useTagsStore = defineStore('tags', () => {
  const list = ref<Tag[]>([])

  async function fetchAll(): Promise<void> {
    const { data } = await apiClient.get<Tag[]>('/tags')
    list.value = data
  }

  async function create(payload: TagPayload): Promise<void> {
    const { data } = await apiClient.post<Tag>('/tags', payload)
    list.value.push(data)
  }

  async function remove(id: number): Promise<void> {
    await apiClient.delete(`/tags/${id}`)
    list.value = list.value.filter((tag) => tag.id !== id)
  }

  return { list, fetchAll, create, remove }
})
