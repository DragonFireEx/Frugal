<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import EmptyState from '../components/EmptyState.vue'
import ErrorAlert from '../components/ErrorAlert.vue'
import LoadingIndicator from '../components/LoadingIndicator.vue'
import { useApiError } from '../composables/useApiError'
import { useBudgetsStore, type BudgetPayload } from '../stores/budgets'
import { useCategoriesStore } from '../stores/categories'
import { useCurrency } from '../composables/useCurrency'
import type { Budget } from '../types'
import { isPositiveNumber } from '../utils/validators'

const budgetsStore = useBudgetsStore()
const categoriesStore = useCategoriesStore()
const { formatCurrency } = useCurrency()
const { extractErrorMessage, extractViolations } = useApiError()

const isLoading = ref(true)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
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
  fieldErrors.value = {}
  form.categoryId = categoriesStore.list[0]?.id ?? 0
  form.monthlyLimit = ''
}

function startEdit(budget: Budget): void {
  editingId.value = budget.id
  form.categoryId = budget.categoryId
  form.monthlyLimit = budget.monthlyLimit
}

function validate(): boolean {
  const errors: Record<string, string> = {}

  if (!form.categoryId) {
    errors.categoryId = 'Kategoria jest wymagana.'
  }
  if (!isPositiveNumber(form.monthlyLimit)) {
    errors.monthlyLimit = 'Limit musi być liczbą dodatnią.'
  }

  fieldErrors.value = errors

  return Object.keys(errors).length === 0
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  if (!validate()) {
    return
  }

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
    const violations = extractViolations(error)
    if (violations) {
      fieldErrors.value = violations
    } else {
      errorMessage.value = extractErrorMessage(error, 'Nie udało się zapisać budżetu.')
    }
  }
}

onMounted(async () => {
  try {
    if (categoriesStore.list.length === 0) {
      await categoriesStore.fetchAll()
    }
    resetForm()
    await budgetsStore.fetchAll()
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się załadować danych.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="budgets-view">
    <h1>Budżety</h1>

    <LoadingIndicator v-if="isLoading" />
    <EmptyState v-else-if="!budgetsStore.list.length" message="Brak budżetów — ustaw pierwszy limit poniżej." />
    <div v-else class="table-scroll">
      <table class="data-table">
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
    </div>

    <form v-if="categoriesStore.list.length" @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <h2>{{ editingId !== null ? 'Edytuj budżet' : 'Nowy budżet' }}</h2>

      <label>
        Kategoria
        <select v-model="form.categoryId">
          <option v-for="category in categoriesStore.list" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
        <span v-if="fieldErrors.categoryId" class="field-error">{{ fieldErrors.categoryId }}</span>
      </label>

      <label>
        Miesięczny limit
        <input v-model="form.monthlyLimit" type="text" inputmode="decimal" />
        <span v-if="fieldErrors.monthlyLimit" class="field-error">{{ fieldErrors.monthlyLimit }}</span>
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <div class="form-actions">
        <button type="submit" class="btn">{{ editingId !== null ? 'Zapisz zmiany' : 'Dodaj budżet' }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">Anuluj</button>
      </div>
    </form>
    <p v-else>Dodaj najpierw kategorię, żeby móc ustawić budżet.</p>
  </div>
</template>
