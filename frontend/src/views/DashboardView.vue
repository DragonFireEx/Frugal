<script setup lang="ts">
import Chart from 'chart.js/auto'
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import EmptyState from '../components/EmptyState.vue'
import ErrorAlert from '../components/ErrorAlert.vue'
import LoadingIndicator from '../components/LoadingIndicator.vue'
import { useApiError } from '../composables/useApiError'
import { useCategoriesStore } from '../stores/categories'
import { useStatsStore } from '../stores/stats'
import { useCurrency } from '../composables/useCurrency'
import { getCurrentMonth } from '../composables/useDateFormat'

const categoriesStore = useCategoriesStore()
const statsStore = useStatsStore()
const { formatCurrency } = useCurrency()
const { extractErrorMessage } = useApiError()

const isLoading = ref(true)
const errorMessage = ref('')
const month = ref(getCurrentMonth())
const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

function categoryColor(categoryId: number): string {
  return categoriesStore.list.find((category) => category.id === categoryId)?.color ?? '#9ca3af'
}

function renderChart(): void {
  if (!chartCanvas.value || !statsStore.monthly) {
    return
  }

  const byCategory = statsStore.monthly.byCategory
  const data = {
    labels: byCategory.map((entry) => entry.categoryName),
    datasets: [
      {
        data: byCategory.map((entry) => Number(entry.total)),
        backgroundColor: byCategory.map((entry) => categoryColor(entry.categoryId)),
      },
    ],
  }

  if (chartInstance) {
    chartInstance.data = data
    chartInstance.update()
    return
  }

  chartInstance = new Chart(chartCanvas.value, {
    type: 'pie',
    data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
      },
    },
  })
}

async function loadData(): Promise<void> {
  errorMessage.value = ''

  try {
    if (categoriesStore.list.length === 0) {
      await categoriesStore.fetchAll()
    }
    await statsStore.fetchMonthly(month.value)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się załadować statystyk.')
  } finally {
    isLoading.value = false
  }

  // isLoading must flip before the canvas is mounted (it sits behind a
  // v-if for the loading state), so wait a tick before rendering into it.
  await nextTick()
  renderChart()
}

watch(month, loadData)

onMounted(loadData)

onUnmounted(() => {
  chartInstance?.destroy()
})
</script>

<template>
  <div class="dashboard-view">
    <h1>Dashboard</h1>

    <div class="filters">
      <label>
        Miesiąc
        <input v-model="month" type="month" />
      </label>
    </div>

    <ErrorAlert v-if="errorMessage" :message="errorMessage" />

    <LoadingIndicator v-if="isLoading" />

    <template v-else-if="statsStore.monthly">
      <div class="summary-cards">
        <div class="card">
          <span class="card-label">Przychody</span>
          <span class="card-value income">{{ formatCurrency(statsStore.monthly.income) }}</span>
        </div>
        <div class="card">
          <span class="card-label">Wydatki</span>
          <span class="card-value expense">{{ formatCurrency(statsStore.monthly.expense) }}</span>
        </div>
        <div class="card">
          <span class="card-label">Bilans</span>
          <span class="card-value">{{ formatCurrency(statsStore.monthly.balance) }}</span>
        </div>
      </div>

      <EmptyState v-if="!statsStore.monthly.byCategory.length" message="Brak transakcji w tym miesiącu." />

      <template v-else>
        <div class="chart-container">
          <canvas ref="chartCanvas"></canvas>
        </div>

        <ul class="category-breakdown">
          <li v-for="entry in statsStore.monthly.byCategory" :key="entry.categoryId">
            <span class="color-dot" :style="{ backgroundColor: categoryColor(entry.categoryId) }"></span>
            <span class="category-name">{{ entry.categoryName }}</span>
            <span class="category-total">{{ formatCurrency(entry.total) }}</span>
            <span v-if="entry.budgetExceeded" class="badge-exceeded">
              Przekroczono budżet ({{ formatCurrency(entry.budgetLimit ?? '0') }})
            </span>
          </li>
        </ul>
      </template>
    </template>
  </div>
</template>

<style scoped>
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 20px;
}

.filters label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}

.filters input {
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font: inherit;
}

.summary-cards {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.card {
  flex: 1 1 160px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 8px;
}

.card-label {
  font-size: 14px;
  color: var(--text);
}

.card-value {
  font-size: 24px;
  font-weight: 500;
  color: var(--text-h);
}

.card-value.income {
  color: #16a34a;
}

.card-value.expense {
  color: #dc2626;
}

.chart-container {
  position: relative;
  max-width: 480px;
  height: 320px;
}

.category-breakdown {
  list-style: none;
  padding: 0;
  margin: 24px 0 0;
  max-width: 480px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.category-breakdown li {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 6px 0;
  border-bottom: 1px solid var(--border);
}

.category-name {
  flex: 1 1 auto;
}

.badge-exceeded {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 999px;
  background: #fee2e2;
  color: #dc2626;
}
</style>
