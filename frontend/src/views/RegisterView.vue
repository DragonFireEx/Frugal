<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'
import ErrorAlert from '../components/ErrorAlert.vue'
import { useApiError } from '../composables/useApiError'
import { useAuthStore } from '../stores/auth'
import { isBlank, isValidEmail } from '../utils/validators'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { extractErrorMessage, extractViolations } = useApiError()

const name = ref('')
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
const isSubmitting = ref(false)

function validate(): boolean {
  const errors: Record<string, string> = {}

  if (isBlank(name.value)) {
    errors.name = t('auth.validation.nameRequired')
  }
  if (isBlank(email.value)) {
    errors.email = t('auth.validation.emailRequired')
  } else if (!isValidEmail(email.value)) {
    errors.email = t('auth.validation.emailInvalid')
  }
  if (isBlank(password.value)) {
    errors.password = t('auth.validation.passwordRequired')
  }

  fieldErrors.value = errors

  return Object.keys(errors).length === 0
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''

  if (!validate()) {
    return
  }

  isSubmitting.value = true

  try {
    await authStore.register(email.value, password.value, name.value)
    await router.push('/')
  } catch (error) {
    const violations = extractViolations(error)
    if (violations) {
      fieldErrors.value = violations
    } else {
      errorMessage.value = extractErrorMessage(error, t('auth.registerErrorFallback'))
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="auth-view">
    <h1>{{ t('auth.registerTitle') }}</h1>

    <form @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <label>
        {{ t('auth.name') }}
        <input v-model="name" type="text" autocomplete="name" />
        <span v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</span>
      </label>

      <label>
        {{ t('auth.email') }}
        <input v-model="email" type="email" autocomplete="email" />
        <span v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</span>
      </label>

      <label>
        {{ t('auth.password') }}
        <input v-model="password" type="password" autocomplete="new-password" />
        <span v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</span>
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <button type="submit" class="btn" :disabled="isSubmitting">{{ t('auth.registerTitle') }}</button>
    </form>

    <p>
      {{ t('auth.hasAccountPrompt') }} <RouterLink to="/login">{{ t('auth.loginTitle') }}</RouterLink>
    </p>
  </div>
</template>

<style scoped>
.auth-view {
  max-width: 360px;
  margin: 40px auto;
  text-align: left;
}
</style>
