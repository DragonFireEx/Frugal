import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiClient } from '../api/client'
import type { MonthlyStats } from '../types'

export const useStatsStore = defineStore('stats', () => {
  const monthly = ref<MonthlyStats | null>(null)

  async function fetchMonthly(month: string): Promise<void> {
    const { data } = await apiClient.get<MonthlyStats>('/stats/monthly', { params: { month } })
    monthly.value = data
  }

  return { monthly, fetchMonthly }
})
