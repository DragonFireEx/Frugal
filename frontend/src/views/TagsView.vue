<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import EmptyState from '../components/EmptyState.vue'
import ErrorAlert from '../components/ErrorAlert.vue'
import LoadingIndicator from '../components/LoadingIndicator.vue'
import { useApiError } from '../composables/useApiError'
import { useTagsStore, type TagPayload } from '../stores/tags'
import type { Tag } from '../types'
import { isBlank } from '../utils/validators'

const tagsStore = useTagsStore()
const { extractErrorMessage, extractViolations } = useApiError()

const isLoading = ref(true)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})

const form = reactive<TagPayload>({
  name: '',
})

function resetForm(): void {
  fieldErrors.value = {}
  form.name = ''
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

  try {
    await tagsStore.create({ name: form.name.trim() })
    resetForm()
  } catch (error) {
    const violations = extractViolations(error)
    if (violations) {
      fieldErrors.value = violations
    } else {
      errorMessage.value = extractErrorMessage(error, 'Nie udało się zapisać tagu.')
    }
  }
}

async function handleDelete(tag: Tag): Promise<void> {
  if (!confirm(`Usunąć tag „${tag.name}”?`)) {
    return
  }

  errorMessage.value = ''

  try {
    await tagsStore.remove(tag.id)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się usunąć tagu.')
  }
}

onMounted(async () => {
  try {
    await tagsStore.fetchAll()
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Nie udało się pobrać tagów.')
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="tags-view">
    <h1>Tagi</h1>

    <LoadingIndicator v-if="isLoading" />
    <EmptyState v-else-if="!tagsStore.list.length" message="Brak tagów — dodaj pierwszy poniżej." />
    <div v-else class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nazwa</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="tag in tagsStore.list" :key="tag.id">
            <td>{{ tag.name }}</td>
            <td class="form-actions">
              <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(tag)">Usuń</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <form @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <h2>Nowy tag</h2>

      <label>
        Nazwa
        <input v-model="form.name" type="text" maxlength="50" />
        <span v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</span>
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <div class="form-actions">
        <button type="submit" class="btn">Dodaj tag</button>
      </div>
    </form>
  </div>
</template>
