<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import EmptyState from '../components/EmptyState.vue'
import ErrorAlert from '../components/ErrorAlert.vue'
import LoadingIndicator from '../components/LoadingIndicator.vue'
import { useApiError } from '../composables/useApiError'
import { useCategoriesStore } from '../stores/categories'
import {
  useRecurringTransactionsStore,
  type RecurringTransactionPayload,
} from '../stores/recurringTransactions'
import { useCurrency } from '../composables/useCurrency'
import { useDateFormat } from '../composables/useDateFormat'
import type { RecurringTransaction, RecurringTransactionFrequency } from '../types'
import { isBlank, isPositiveNumber } from '../utils/validators'

const categoriesStore = useCategoriesStore()
const recurringStore = useRecurringTransactionsStore()
const { formatCurrency } = useCurrency()
const { formatDate } = useDateFormat()
const { extractErrorMessage, extractViolations } = useApiError()

const isLoading = ref(true)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
const editingId = ref<number | null>(null)

const form = reactive({
  categoryId: 0,
  amount: '',
  description: '',
  frequency: 'monthly' as RecurringTransactionFrequency,
  nextRunDate: new Date().toISOString().slice(0, 10),
  active: true,
})

function categoryName(categoryId: number): string {
  return categoriesStore.list.find((category) => category.id === categoryId)?.name ?? '—'
}

function frequencyLabel(frequency: RecurringTransactionFrequency): string {
  return frequency === 'weekly' ? 'Co tydzień' : 'Co miesiąc'
}

function resetForm(): void {
  editingId.value = null
  fieldErrors.value = {}
  form.categoryId = categoriesStore.list[0]?.id ?? 0
  form.amount = ''
  form.description = ''
  form.frequency = 'monthly'
  form.nextRunDate = new Date().toISOString().slice(0, 10)
  form.active = true
}

function startEdit(recurring: RecurringTransaction): void {
  editingId.value = recurring.id
  form.categoryId = recurring.categoryId
  form.amount = recurring.amount
  form.description = recurring.description ?? ''
  form.frequency = recurring.frequency
  form.nextRunDate = recurring.nextRunDate
  form.active = recurring.active
}

function validate(): boolean {
  const errors: Record<string, string> = {}

  if (!form.categoryId) {
    errors.categoryId = 'Kategoria jest wymagana.'
  }
  if (!isPositiveNumber(form.amount)) {
    errors.amount = 'Kwota musi być liczbą dodatnią.'
  }
  if (isBlank(form.nextRunDate)) {
    errors.nextRunDate = 'Data jest wymagana.'
  }

  fieldErrors.value = errors

  return Object.keys(errors).length === 0
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  if (!validate()) {
    return
  }

  const payload: RecurringTransactionPayload = {
    categoryId: form.categoryId,
    amount: form.amount,
    description: form.description.trim() ? form.description.trim() : null,
    frequency: form.frequency,
    nextRunDate: form.nextRunDate,
    active: form.active,
  }

  try {
    if (editingId.value !== null) {
      await recurringStore.update(editingId.value, payload)
    } else {
      await recurringStore.create(payload)
    }
    resetForm()
  } catch (error) {
    const violations = extractViolations(error)
    if (violations) {
      fieldErrors.value = violations
    } else {
      errorMessage.value = extractErrorMessage(error, 'Nie udało się zapisać transakcji cyklicznej.')
    }
  }
}

async function handleDelete(recurring: RecurringTransaction): Promise<void> {
  if (!confirm('Usunąć tę transakcję cykliczną?')) {
    return
  }

  errorMessage.value = ''

  try {
    await recurringStore.remove(recurring.id)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się usunąć transakcji cyklicznej.')
  }
}

const hasCategories = computed(() => categoriesStore.list.length > 0)

onMounted(async () => {
  try {
    if (categoriesStore.list.length === 0) {
      await categoriesStore.fetchAll()
    }
    resetForm()
    await recurringStore.fetchAll()
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się załadować danych.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="recurring-transactions-view">
    <h1>Transakcje cykliczne</h1>

    <LoadingIndicator v-if="isLoading" />
    <EmptyState
      v-else-if="!recurringStore.list.length"
      message="Brak transakcji cyklicznych — dodaj pierwszą poniżej."
    />
    <div v-else class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Kategoria</th>
            <th>Kwota</th>
            <th>Częstotliwość</th>
            <th>Następne wystąpienie</th>
            <th>Aktywna</th>
            <th>Opis</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="recurring in recurringStore.list" :key="recurring.id">
            <td>{{ categoryName(recurring.categoryId) }}</td>
            <td>{{ formatCurrency(recurring.amount) }}</td>
            <td>{{ frequencyLabel(recurring.frequency) }}</td>
            <td>{{ formatDate(recurring.nextRunDate) }}</td>
            <td>{{ recurring.active ? 'Tak' : 'Nie' }}</td>
            <td>{{ recurring.description ?? '—' }}</td>
            <td class="form-actions">
              <button type="button" class="btn btn-secondary btn-small" @click="startEdit(recurring)">Edytuj</button>
              <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(recurring)">Usuń</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <form v-if="hasCategories" @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <h2>{{ editingId !== null ? 'Edytuj transakcję cykliczną' : 'Nowa transakcja cykliczna' }}</h2>

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
        Kwota
        <input v-model="form.amount" type="text" inputmode="decimal" />
        <span v-if="fieldErrors.amount" class="field-error">{{ fieldErrors.amount }}</span>
      </label>

      <label>
        Opis (opcjonalnie)
        <input v-model="form.description" type="text" maxlength="255" />
      </label>

      <label>
        Częstotliwość
        <select v-model="form.frequency">
          <option value="monthly">Co miesiąc</option>
          <option value="weekly">Co tydzień</option>
        </select>
      </label>

      <label>
        {{ editingId !== null ? 'Następne wystąpienie' : 'Pierwsze wystąpienie' }}
        <input v-model="form.nextRunDate" type="date" />
        <span v-if="fieldErrors.nextRunDate" class="field-error">{{ fieldErrors.nextRunDate }}</span>
      </label>

      <label class="checkbox-label">
        <input v-model="form.active" type="checkbox" />
        Aktywna
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <div class="form-actions">
        <button type="submit" class="btn">{{ editingId !== null ? 'Zapisz zmiany' : 'Dodaj' }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">Anuluj</button>
      </div>
    </form>
    <p v-else>Dodaj najpierw kategorię, żeby móc dodawać transakcje cykliczne.</p>
  </div>
</template>

<style scoped>
.checkbox-label {
  flex-direction: row !important;
  align-items: center;
  gap: 8px !important;
}
</style>
