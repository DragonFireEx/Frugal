import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiClient } from '../api/client'
import type { MonthlyStats, YearlyStats } from '../types'

export const useStatsStore = defineStore('stats', () => {
  const monthly = ref<MonthlyStats | null>(null)
  const yearly = ref<YearlyStats | null>(null)

  async function fetchMonthly(month: string): Promise<void> {
    const { data } = await apiClient.get<MonthlyStats>('/stats/monthly', { params: { month } })
    monthly.value = data
  }

  async function fetchYearly(year: string): Promise<void> {
    const { data } = await apiClient.get<YearlyStats>('/stats/yearly', { params: { year } })
    yearly.value = data
  }

  return { monthly, yearly, fetchMonthly, fetchYearly }
})
