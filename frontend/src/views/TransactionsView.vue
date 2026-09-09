<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import EmptyState from '../components/EmptyState.vue'
import ErrorAlert from '../components/ErrorAlert.vue'
import LoadingIndicator from '../components/LoadingIndicator.vue'
import { useApiError } from '../composables/useApiError'
import { useCategoriesStore } from '../stores/categories'
import { useTagsStore } from '../stores/tags'
import { useTransactionsStore, type TransactionPayload } from '../stores/transactions'
import { useCurrency } from '../composables/useCurrency'
import { useDateFormat, getCurrentMonth } from '../composables/useDateFormat'
import type { Transaction } from '../types'
import { isBlank, isPositiveNumber } from '../utils/validators'

const { t } = useI18n()
const categoriesStore = useCategoriesStore()
const tagsStore = useTagsStore()
const transactionsStore = useTransactionsStore()
const { formatCurrency } = useCurrency()
const { formatDate } = useDateFormat()
const { extractErrorMessage, extractViolations } = useApiError()

const isLoading = ref(true)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
const editingId = ref<number | null>(null)

const monthFilter = ref(transactionsStore.currentMonth)
const categoryFilter = ref<number | ''>('')

const form = reactive({
  categoryId: 0,
  amount: '',
  description: '',
  date: getCurrentMonth() + '-01',
  tagIds: [] as number[],
})

function categoryName(categoryId: number): string {
  return categoriesStore.list.find((category) => category.id === categoryId)?.name ?? t('common.none')
}

function tagNames(tagIds: number[]): string {
  if (!tagIds.length) {
    return t('common.none')
  }

  return tagIds
    .map((tagId) => tagsStore.list.find((tag) => tag.id === tagId)?.name)
    .filter((name): name is string => Boolean(name))
    .join(', ')
}

function resetForm(): void {
  editingId.value = null
  fieldErrors.value = {}
  form.categoryId = categoriesStore.list[0]?.id ?? 0
  form.amount = ''
  form.description = ''
  form.date = new Date().toISOString().slice(0, 10)
  form.tagIds = []
}

function startEdit(transaction: Transaction): void {
  editingId.value = transaction.id
  form.categoryId = transaction.categoryId
  form.amount = transaction.amount
  form.description = transaction.description ?? ''
  form.date = transaction.date
  form.tagIds = [...transaction.tagIds]
}

async function loadTransactions(): Promise<void> {
  try {
    await transactionsStore.fetchByMonth(monthFilter.value, categoryFilter.value || undefined)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('transactions.errors.loadFailed'))
  }
}

function validate(): boolean {
  const errors: Record<string, string> = {}

  if (!form.categoryId) {
    errors.categoryId = t('transactions.validation.categoryRequired')
  }
  if (!isPositiveNumber(form.amount)) {
    errors.amount = t('transactions.validation.amountPositive')
  }
  if (isBlank(form.date)) {
    errors.date = t('transactions.validation.dateRequired')
  }

  fieldErrors.value = errors

  return Object.keys(errors).length === 0
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  if (!validate()) {
    return
  }

  const payload: TransactionPayload = {
    categoryId: form.categoryId,
    amount: form.amount,
    description: form.description.trim() ? form.description.trim() : null,
    date: form.date,
    tagIds: form.tagIds,
  }

  try {
    if (editingId.value !== null) {
      await transactionsStore.update(editingId.value, payload)
    } else {
      await transactionsStore.create(payload)
    }
    resetForm()
  } catch (error) {
    const violations = extractViolations(error)
    if (violations) {
      fieldErrors.value = violations
    } else {
      errorMessage.value = extractErrorMessage(error, t('transactions.errors.saveFailed'))
    }
  }
}

async function handleExport(): Promise<void> {
  errorMessage.value = ''

  try {
    const blob = await transactionsStore.exportCsv(monthFilter.value)
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `transakcje-${monthFilter.value}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('transactions.errors.exportFailed'))
  }
}

async function handleDelete(transaction: Transaction): Promise<void> {
  if (!confirm(t('transactions.confirmDelete'))) {
    return
  }

  errorMessage.value = ''

  try {
    await transactionsStore.remove(transaction.id)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('transactions.errors.deleteFailed'))
  }
}

const hasCategories = computed(() => categoriesStore.list.length > 0)

watch([monthFilter, categoryFilter], loadTransactions)

onMounted(async () => {
  try {
    await Promise.all([
      categoriesStore.list.length === 0 ? categoriesStore.fetchAll() : Promise.resolve(),
      tagsStore.list.length === 0 ? tagsStore.fetchAll() : Promise.resolve(),
      loadTransactions(),
    ])
    resetForm()
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('transactions.errors.loadDataFailed'))
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="transactions-view">
    <h1>{{ t('transactions.title') }}</h1>

    <div class="filters">
      <label>
        {{ t('transactions.month') }}
        <input v-model="monthFilter" type="month" />
      </label>

      <label>
        {{ t('transactions.category') }}
        <select v-model="categoryFilter">
          <option value="">{{ t('transactions.allCategories') }}</option>
          <option v-for="category in categoriesStore.list" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </label>

      <button type="button" class="btn btn-secondary" @click="handleExport">{{ t('transactions.exportCsv') }}</button>
    </div>

    <LoadingIndicator v-if="isLoading" />
    <EmptyState v-else-if="!transactionsStore.list.length" :message="t('transactions.empty')" />
    <div v-else class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ t('transactions.colDate') }}</th>
            <th>{{ t('transactions.colCategory') }}</th>
            <th>{{ t('transactions.colAmount') }}</th>
            <th>{{ t('transactions.colDescription') }}</th>
            <th>{{ t('transactions.colTags') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="transaction in transactionsStore.list" :key="transaction.id">
            <td>{{ formatDate(transaction.date) }}</td>
            <td>{{ categoryName(transaction.categoryId) }}</td>
            <td>{{ formatCurrency(transaction.amount) }}</td>
            <td>{{ transaction.description ?? t('common.none') }}</td>
            <td>{{ tagNames(transaction.tagIds) }}</td>
            <td class="form-actions">
              <button type="button" class="btn btn-secondary btn-small" @click="startEdit(transaction)">{{ t('common.edit') }}</button>
              <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(transaction)">{{ t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <form v-if="hasCategories" @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <h2>{{ editingId !== null ? t('transactions.editTitle') : t('transactions.newTitle') }}</h2>

      <label>
        {{ t('transactions.category') }}
        <select v-model="form.categoryId">
          <option v-for="category in categoriesStore.list" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
        <span v-if="fieldErrors.categoryId" class="field-error">{{ fieldErrors.categoryId }}</span>
      </label>

      <label>
        {{ t('transactions.amount') }}
        <input v-model="form.amount" type="text" inputmode="decimal" />
        <span v-if="fieldErrors.amount" class="field-error">{{ fieldErrors.amount }}</span>
      </label>

      <label>
        {{ t('transactions.description') }}
        <input v-model="form.description" type="text" maxlength="255" />
      </label>

      <label>
        {{ t('transactions.date') }}
        <input v-model="form.date" type="date" />
        <span v-if="fieldErrors.date" class="field-error">{{ fieldErrors.date }}</span>
      </label>

      <fieldset v-if="tagsStore.list.length" class="tag-fieldset">
        <legend>{{ t('transactions.tags') }}</legend>
        <label v-for="tag in tagsStore.list" :key="tag.id" class="tag-checkbox">
          <input type="checkbox" :value="tag.id" v-model="form.tagIds" />
          {{ tag.name }}
        </label>
      </fieldset>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <div class="form-actions">
        <button type="submit" class="btn">{{ editingId !== null ? t('common.saveChanges') : t('transactions.addTransaction') }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">{{ t('common.cancel') }}</button>
      </div>
    </form>
    <p v-else>{{ t('transactions.needCategoryFirst') }}</p>
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

.filters input,
.filters select {
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font: inherit;
}

.filters .btn {
  align-self: flex-end;
}

.tag-fieldset {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 8px 12px 12px;
}

.tag-fieldset legend {
  font-size: 14px;
  padding: 0 4px;
}

.tag-checkbox {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}
</style>
