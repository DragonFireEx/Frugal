<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useCategoriesStore } from '../stores/categories'
import { useTransactionsStore, type TransactionPayload } from '../stores/transactions'
import { useCurrency } from '../composables/useCurrency'
import { useDateFormat, getCurrentMonth } from '../composables/useDateFormat'
import type { Transaction } from '../types'

const categoriesStore = useCategoriesStore()
const transactionsStore = useTransactionsStore()
const { formatCurrency } = useCurrency()
const { formatDate } = useDateFormat()

const errorMessage = ref('')
const editingId = ref<number | null>(null)

const monthFilter = ref(transactionsStore.currentMonth)
const categoryFilter = ref<number | ''>('')

const form = reactive({
  categoryId: 0,
  amount: '',
  description: '',
  date: getCurrentMonth() + '-01',
})

function categoryName(categoryId: number): string {
  return categoriesStore.list.find((category) => category.id === categoryId)?.name ?? '—'
}

function resetForm(): void {
  editingId.value = null
  form.categoryId = categoriesStore.list[0]?.id ?? 0
  form.amount = ''
  form.description = ''
  form.date = new Date().toISOString().slice(0, 10)
}

function startEdit(transaction: Transaction): void {
  editingId.value = transaction.id
  form.categoryId = transaction.categoryId
  form.amount = transaction.amount
  form.description = transaction.description ?? ''
  form.date = transaction.date
}

async function loadTransactions(): Promise<void> {
  await transactionsStore.fetchByMonth(monthFilter.value, categoryFilter.value || undefined)
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  const payload: TransactionPayload = {
    categoryId: form.categoryId,
    amount: form.amount,
    description: form.description.trim() ? form.description.trim() : null,
    date: form.date,
  }

  try {
    if (editingId.value !== null) {
      await transactionsStore.update(editingId.value, payload)
    } else {
      await transactionsStore.create(payload)
    }
    resetForm()
  } catch (error) {
    errorMessage.value = axios.isAxiosError(error)
      ? (error.response?.data?.errors?.categoryId ?? error.response?.data?.error ?? 'Nie udało się zapisać transakcji.')
      : 'Nie udało się zapisać transakcji.'
  }
}

async function handleDelete(transaction: Transaction): Promise<void> {
  if (!confirm('Usunąć tę transakcję?')) {
    return
  }

  errorMessage.value = ''

  try {
    await transactionsStore.remove(transaction.id)
  } catch (error) {
    errorMessage.value = axios.isAxiosError(error)
      ? (error.response?.data?.error ?? 'Nie udało się usunąć transakcji.')
      : 'Nie udało się usunąć transakcji.'
  }
}

const hasCategories = computed(() => categoriesStore.list.length > 0)

watch([monthFilter, categoryFilter], loadTransactions)

onMounted(async () => {
  if (categoriesStore.list.length === 0) {
    await categoriesStore.fetchAll()
  }
  resetForm()
  await loadTransactions()
})
</script>

<template>
  <div class="transactions-view">
    <h1>Transakcje</h1>

    <div class="filters">
      <label>
        Miesiąc
        <input v-model="monthFilter" type="month" />
      </label>

      <label>
        Kategoria
        <select v-model="categoryFilter">
          <option value="">Wszystkie</option>
          <option v-for="category in categoriesStore.list" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </label>
    </div>

    <table v-if="transactionsStore.list.length" class="data-table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Kategoria</th>
          <th>Kwota</th>
          <th>Opis</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="transaction in transactionsStore.list" :key="transaction.id">
          <td>{{ formatDate(transaction.date) }}</td>
          <td>{{ categoryName(transaction.categoryId) }}</td>
          <td>{{ formatCurrency(transaction.amount) }}</td>
          <td>{{ transaction.description ?? '—' }}</td>
          <td class="form-actions">
            <button type="button" class="btn btn-secondary btn-small" @click="startEdit(transaction)">Edytuj</button>
            <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(transaction)">Usuń</button>
          </td>
        </tr>
      </tbody>
    </table>

    <form v-if="hasCategories" @submit.prevent="handleSubmit" class="entity-form">
      <h2>{{ editingId !== null ? 'Edytuj transakcję' : 'Nowa transakcja' }}</h2>

      <label>
        Kategoria
        <select v-model="form.categoryId">
          <option v-for="category in categoriesStore.list" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </label>

      <label>
        Kwota
        <input v-model="form.amount" type="number" step="0.01" min="0.01" required />
      </label>

      <label>
        Opis (opcjonalnie)
        <input v-model="form.description" type="text" maxlength="255" />
      </label>

      <label>
        Data
        <input v-model="form.date" type="date" required />
      </label>

      <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>

      <div class="form-actions">
        <button type="submit" class="btn">{{ editingId !== null ? 'Zapisz zmiany' : 'Dodaj transakcję' }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">Anuluj</button>
      </div>
    </form>
    <p v-else>Dodaj najpierw kategorię, żeby móc dodawać transakcje.</p>
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

.filters input,
.filters select {
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font: inherit;
}
</style>
