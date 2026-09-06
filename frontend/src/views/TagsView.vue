<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import EmptyState from '../components/EmptyState.vue'
import ErrorAlert from '../components/ErrorAlert.vue'
import LoadingIndicator from '../components/LoadingIndicator.vue'
import { useApiError } from '../composables/useApiError'
import { useTagsStore, type TagPayload } from '../stores/tags'
import type { Tag } from '../types'
import { isBlank } from '../utils/validators'

const { t } = useI18n()
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
    errors.name = t('tags.validation.nameRequired')
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
      errorMessage.value = extractErrorMessage(error, t('tags.errors.saveFailed'))
    }
  }
}

async function handleDelete(tag: Tag): Promise<void> {
  if (!confirm(t('tags.confirmDelete', { name: tag.name }))) {
    return
  }

  errorMessage.value = ''

  try {
    await tagsStore.remove(tag.id)
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('tags.errors.deleteFailed'))
  }
}

onMounted(async () => {
  try {
    await tagsStore.fetchAll()
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('tags.errors.loadFailed'))
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="tags-view">
    <h1>{{ t('tags.title') }}</h1>

    <LoadingIndicator v-if="isLoading" />
    <EmptyState v-else-if="!tagsStore.list.length" :message="t('tags.empty')" />
    <div v-else class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>{{ t('tags.colName') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="tag in tagsStore.list" :key="tag.id">
            <td>{{ tag.name }}</td>
            <td class="form-actions">
              <button type="button" class="btn btn-secondary btn-small" @click="handleDelete(tag)">{{ t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <form @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <h2>{{ t('tags.newTitle') }}</h2>

      <label>
        {{ t('tags.name') }}
        <input v-model="form.name" type="text" maxlength="50" />
        <span v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</span>
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <div class="form-actions">
        <button type="submit" class="btn">{{ t('tags.addTag') }}</button>
      </div>
    </form>
  </div>
</template>
