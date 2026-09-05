<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import EmptyState from '../components/EmptyState.vue'
import ErrorAlert from '../components/ErrorAlert.vue'
import LoadingIndicator from '../components/LoadingIndicator.vue'
import { useApiError } from '../composables/useApiError'
import { useCategoriesStore, type CategoryPayload } from '../stores/categories'
import type { Category } from '../types'
import { isBlank } from '../utils/validators'

const categoriesStore = useCategoriesStore()
const { extractErrorMessage, extractViolations } = useApiError()

const isLoading = ref(true)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
const editingId = ref<number | null>(null)

const form = reactive<CategoryPayload>({
  name: '',
  type: 'expense',
  color: '#6366f1',
  icon: null,
})

function resetForm(): void {
  editingId.value = null
  fieldErrors.value = {}
  form.name = ''
  form.type = 'expense'
  form.color = '#6366f1'
  form.icon = null
}

function startEdit(category: Category): void {
  editingId.value = category.id
  form.name = category.name
  form.type = category.type
  form.color = category.color
  form.icon = category.icon
}

function validate(): boolean {
  const errors: Record<string, string> = {}

  if (isBlank(form.name)) {
    errors.name = 'Nazwa jest wymagana.'
  }

  fieldErrors.value = errors

  return Object.keys(errors).length === 0
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  if (!validate()) {
    return
  }

  const payload: CategoryPayload = {
    name: form.name,
    type: form.type,
    color: form.color,
    icon: form.icon?.trim() ? form.icon.trim() : null,
  }

  try {
    if (editingId.value !== null) {
      await categoriesStore.update(editingId.value, payload)
    } else {
      await categoriesStore.create(payload)
    }
    resetForm()
  } catch (error) {
    const violations = extractViolations(error)
    if (violations) {
      fieldErrors.value = violations
    } else {
      errorMessage.value = extractErrorMessage(error, 'Nie udało się zapisać kategorii.')
    }
  }
}

async function handleDelete(category: Category): Promise<void> {
  if (!confirm(`Usunąć kategorię „${category.name}”?`)) {
    return
  }

  errorMessage.value = ''

  try {
    await categoriesStore.remove(category.id)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się usunąć kategorii.')
  }
}

onMounted(async () => {
  try {
    await categoriesStore.fetchAll()
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się pobrać kategorii.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="categories-view">
    <h1>Kategorie</h1>

    <LoadingIndicator v-if="isLoading" />
    <EmptyState v-else-if="!categoriesStore.list.length" message="Brak kategorii — dodaj pierwszą poniżej." />
    <div v-else class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Kolor</th>
            <th>Nazwa</th>
            <th>Typ</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in categoriesStore.list" :key="category.id">
            <td><span class="color-dot" :style="{ backgroundColor: category.color }"></span></td>
            <td>{{ category.name }}</td>
            <td>{{ category.type === 'income' ? 'Przychód' : 'Wydatek' }}</td>
            <td class="form-actions">
              <button type="button" class="btn btn-secondary btn-small" @click="startEdit(category)">Edytuj</button>
              <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(category)">Usuń</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <form @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <h2>{{ editingId !== null ? 'Edytuj kategorię' : 'Nowa kategoria' }}</h2>

      <label>
        Nazwa
        <input v-model="form.name" type="text" maxlength="100" />
        <span v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</span>
      </label>

      <label>
        Typ
        <select v-model="form.type">
          <option value="expense">Wydatek</option>
          <option value="income">Przychód</option>
        </select>
      </label>

      <label>
        Kolor
        <input v-model="form.color" type="color" />
      </label>

      <label>
        Ikona (opcjonalnie)
        <input v-model="form.icon" type="text" maxlength="50" />
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <div class="form-actions">
        <button type="submit" class="btn">{{ editingId !== null ? 'Zapisz zmiany' : 'Dodaj kategorię' }}</button>
        <button v-if="editingId !== null" type="button" class="btn btn-secondary" @click="resetForm">Anuluj</button>
      </div>
    </form>
  </div>
</template>
