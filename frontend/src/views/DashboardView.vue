<script setup lang="ts">
import Chart from 'chart.js/auto'
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useCategoriesStore } from '../stores/categories'
import { useStatsStore } from '../stores/stats'
import { useCurrency } from '../composables/useCurrency'
import { getCurrentMonth } from '../composables/useDateFormat'

const categoriesStore = useCategoriesStore()
const statsStore = useStatsStore()
const { formatCurrency } = useCurrency()

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
  if (categoriesStore.list.length === 0) {
    await categoriesStore.fetchAll()
  }
  await statsStore.fetchMonthly(month.value)
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

    <div v-if="statsStore.monthly" class="summary-cards">
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

    <div v-if="statsStore.monthly?.byCategory.length" class="chart-container">
      <canvas ref="chartCanvas"></canvas>
    </div>
    <p v-else-if="statsStore.monthly">Brak transakcji w tym miesiącu.</p>
  </div>
</template>

<style scoped>
.filters {
  display: flex;
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
</style>
