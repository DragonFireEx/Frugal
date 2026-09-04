<script setup lang="ts">
import axios from 'axios'
import { onMounted, reactive, ref } from 'vue'
import { useBudgetsStore, type BudgetPayload } from '../stores/budgets'
import { useCategoriesStore } from '../stores/categories'
import { useCurrency } from '../composables/useCurrency'
import type { Budget } from '../types'

const budgetsStore = useBudgetsStore()
const categoriesStore = useCategoriesStore()
const { formatCurrency } = useCurrency()

const errorMessage = ref('')
const editingId = ref<number | null>(null)

const form = reactive<BudgetPayload>({
  categoryId: 0,
  monthlyLimit: '',
})

function categoryName(categoryId: number): string {
  return categoriesStore.list.find((category) => category.id === categoryId)?.name ?? '—'
}

function resetForm(): void {
  editingId.value = null
  form.categoryId = categoriesStore.list[0]?.id ?? 0
  form.monthlyLimit = ''
}

function startEdit(budget: Budget): void {
  editingId.value = budget.id
  form.categoryId = budget.categoryId
  form.monthlyLimit = budget.monthlyLimit
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  const payload: BudgetPayload = {
    categoryId: form.categoryId,
    monthlyLimit: form.monthlyLimit,
  }

  try {
    if (editingId.value !== null) {
      await budgetsStore.update(editingId.value, payload)
    } else {
      await budgetsStore.create(payload)
    }
    resetForm()
  } catch (error) {
    errorMessage.value = axios.isAxiosError(error)
      ? (error.response?.data?.errors?.categoryId ?? error.response?.data?.error ?? 'Nie udało się zapisać budżetu.')
      : 'Nie udało się zapisać budżetu.'
  }
}

onMounted(async () => {
  if (categoriesStore.list.length === 0) {
    await categoriesStore.fetchAll()
  }
  resetForm()
  await budgetsStore.fetchAll()
})
</script>

<template>
  <div class="budgets-view">
    <h1>Budżety</h1>

    <table v-if="budgetsStore.list.length" class="data-table">
      <thead>
        <tr>
          <th>Kategoria</th>
          <th>Miesięczny limit</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="budget in budgetsStore.list" :key="budget.id">
          <td>{{ categoryName(budget.categoryId) }}</td>
          <td>{{ formatCurrency(budget.monthlyLimit) }}</td>
          <td class="form-actions">
            <button type="button" class="btn btn-secondary btn-small" @click="startEdit(budget)">Edytuj</button>
          </td>
        </tr>
      </tbody>
    </table>

    <form v-if="categoriesStore.list.length" @submit.prevent="handleSubmit" class="entity-form">
      <h2>{{ editingId !== null ? 'Edytuj budżet' : 'Nowy budżet' }}</h2>

      <label>
        Kategoria
        <select v-model="form.categoryId">
          <option v-for="category in categoriesStore.list" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </label>

      <label>
        Miesięczny limit
        <input v-model="form.monthlyLimit" type="number" step="0.01" min="0.01" required />
      </label>

      <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>

      <div class="form-actions">
        <button type="submit" class="btn">{{ editingId !== null ? 'Zapisz zmiany' : 'Dodaj budżet' }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">Anuluj</button>
      </div>
    </form>
    <p v-else>Dodaj najpierw kategorię, żeby móc ustawić budżet.</p>
  </div>
</template>
