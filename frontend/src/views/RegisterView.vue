<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import ErrorAlert from '../components/ErrorAlert.vue'
import { useApiError } from '../composables/useApiError'
import { useAuthStore } from '../stores/auth'
import { isBlank, isValidEmail } from '../utils/validators'

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
    errors.name = 'Imię jest wymagane.'
  }
  if (isBlank(email.value)) {
    errors.email = 'Email jest wymagany.'
  } else if (!isValidEmail(email.value)) {
    errors.email = 'Podaj prawidłowy adres email.'
  }
  if (isBlank(password.value)) {
    errors.password = 'Hasło jest wymagane.'
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
      errorMessage.value = extractErrorMessage(error, 'Nie udało się zarejestrować.')
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="auth-view">
    <h1>Zarejestruj się</h1>

    <form @submit.prevent="handleSubmit" class="entity-form" novalidate>
      <label>
        Imię
        <input v-model="name" type="text" autocomplete="name" />
        <span v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</span>
      </label>

      <label>
        Email
        <input v-model="email" type="email" autocomplete="email" />
        <span v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</span>
      </label>

      <label>
        Hasło
        <input v-model="password" type="password" autocomplete="new-password" />
        <span v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</span>
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <button type="submit" class="btn" :disabled="isSubmitting">Zarejestruj się</button>
    </form>

    <p>
      Masz już konto? <RouterLink to="/login">Zaloguj się</RouterLink>
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
