<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()
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
  return categoriesStore.list.find((category) => category.id === categoryId)?.name ?? t('common.none')
}

function frequencyLabel(frequency: RecurringTransactionFrequency): string {
  return frequency === 'weekly' ? t('recurring.frequencyWeekly') : t('recurring.frequencyMonthly')
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
    errors.categoryId = t('recurring.validation.categoryRequired')
  }
  if (!isPositiveNumber(form.amount)) {
    errors.amount = t('recurring.validation.amountPositive')
  }
  if (isBlank(form.nextRunDate)) {
    errors.nextRunDate = t('recurring.validation.dateRequired')
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
      errorMessage.value = extractErrorMessage(error, t('recurring.errors.saveFailed'))
    }
  }
}

async function handleDelete(recurring: RecurringTransaction): Promise<void> {
  if (!confirm(t('recurring.confirmDelete'))) {
    return
  }

  errorMessage.value = ''

  try {
    await recurringStore.remove(recurring.id)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('recurring.errors.deleteFailed'))
  }
}

const hasCategories = computed(() => categoriesStore.list.length > 0)

onMounted(async () => {
  try {
    await Promise.all([
      categoriesStore.list.length === 0 ? categoriesStore.fetchAll() : Promise.resolve(),
      recurringStore.fetchAll(),
    ])
    resetForm()
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('recurring.errors.loadFailed'))
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="recurring-transactions-view">
    <h1>{{ t('recurring.title') }}</h1>

    <LoadingIndicator v-if="isLoading" />
    <EmptyState v-else-if="!recurringStore.list.length" :message="t('recurring.empty')" />
    <div v-else class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ t('recurring.colCategory') }}</th>
            <th>{{ t('recurring.colAmount') }}</th>
            <th>{{ t('recurring.colFrequency') }}</th>
            <th>{{ t('recurring.colNextRun') }}</th>
            <th>{{ t('recurring.colActive') }}</th>
            <th>{{ t('recurring.colDescription') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="recurring in recurringStore.list" :key="recurring.id">
            <td>{{ categoryName(recurring.categoryId) }}</td>
            <td>{{ formatCurrency(recurring.amount) }}</td>
            <td>{{ frequencyLabel(recurring.frequency) }}</td>
            <td>{{ formatDate(recurring.nextRunDate) }}</td>
            <td>{{ recurring.active ? t('common.yes') : t('common.no') }}</td>
            <td>{{ recurring.description ?? t('common.none') }}</td>
            <td class="form-actions">
              <button type="button" class="btn btn-secondary btn-small" @click="startEdit(recurring)">{{ t('common.edit') }}</button>
              <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(recurring)">{{ t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <form v-if="hasCategories" @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <h2>{{ editingId !== null ? t('recurring.editTitle') : t('recurring.newTitle') }}</h2>

      <label>
        {{ t('recurring.category') }}
        <select v-model="form.categoryId">
          <option v-for="category in categoriesStore.list" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
        <span v-if="fieldErrors.categoryId" class="field-error">{{ fieldErrors.categoryId }}</span>
      </label>

      <label>
        {{ t('recurring.amount') }}
        <input v-model="form.amount" type="text" inputmode="decimal" />
        <span v-if="fieldErrors.amount" class="field-error">{{ fieldErrors.amount }}</span>
      </label>

      <label>
        {{ t('recurring.description') }}
        <input v-model="form.description" type="text" maxlength="255" />
      </label>

      <label>
        {{ t('recurring.frequency') }}
        <select v-model="form.frequency">
          <option value="monthly">{{ t('recurring.frequencyMonthly') }}</option>
          <option value="weekly">{{ t('recurring.frequencyWeekly') }}</option>
        </select>
      </label>

      <label>
        {{ editingId !== null ? t('recurring.nextRunDate') : t('recurring.firstRunDate') }}
        <input v-model="form.nextRunDate" type="date" />
        <span v-if="fieldErrors.nextRunDate" class="field-error">{{ fieldErrors.nextRunDate }}</span>
      </label>

      <label class="checkbox-label">
        <input v-model="form.active" type="checkbox" />
        {{ t('recurring.active') }}
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <div class="form-actions">
        <button type="submit" class="btn">{{ editingId !== null ? t('common.saveChanges') : t('recurring.add') }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">{{ t('common.cancel') }}</button>
      </div>
    </form>
    <p v-else>{{ t('recurring.needCategoryFirst') }}</p>
  </div>
</template>

<style scoped>
.checkbox-label {
  flex-direction: row !important;
  align-items: center;
  gap: 8px !important;
}
</style>
