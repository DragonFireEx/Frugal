<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'
import ErrorAlert from '../components/ErrorAlert.vue'
import { useApiError } from '../composables/useApiError'
import { useAuthStore } from '../stores/auth'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { extractErrorMessage } = useApiError()

const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await authStore.login(email.value, password.value)
    await router.push('/')
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, t('auth.loginErrorFallback'))
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="auth-view">
    <h1>{{ t('auth.loginTitle') }}</h1>

    <form @submit.prevent="handleSubmit" class="entity-form">
      <label>
        {{ t('auth.email') }}
        <input v-model="email" type="email" required autocomplete="email" />
      </label>

      <label>
        {{ t('auth.password') }}
        <input v-model="password" type="password" required autocomplete="current-password" />
      </label>

      <ErrorAlert v-if="errorMessage" :message="errorMessage" />

      <button type="submit" class="btn" :disabled="isSubmitting">{{ t('auth.loginTitle') }}</button>
    </form>

    <p>
      {{ t('auth.noAccountPrompt') }} <RouterLink to="/register">{{ t('auth.registerTitle') }}</RouterLink>
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
