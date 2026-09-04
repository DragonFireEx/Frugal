<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

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
    errorMessage.value = axios.isAxiosError(error)
      ? (error.response?.data?.error ?? error.response?.data?.message ?? 'Nie udało się zalogować.')
      : 'Nie udało się zalogować.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="auth-view">
    <h1>Zaloguj się</h1>

    <form @submit.prevent="handleSubmit">
      <label>
        Email
        <input v-model="email" type="email" required autocomplete="email" />
      </label>

      <label>
        Hasło
        <input v-model="password" type="password" required autocomplete="current-password" />
      </label>

      <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>

      <button type="submit" :disabled="isSubmitting">Zaloguj się</button>
    </form>

    <p>
      Nie masz konta? <RouterLink to="/register">Zarejestruj się</RouterLink>
    </p>
  </div>
</template>

<style scoped>
.auth-view {
  max-width: 360px;
  margin: 40px auto;
  text-align: left;
}

form {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 20px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}

input {
  padding: 8px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font: inherit;
}

button {
  padding: 10px;
  border: none;
  border-radius: 6px;
  background: var(--accent);
  color: white;
  font: inherit;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-error {
  color: #dc2626;
  font-size: 14px;
  margin: 0;
}
</style>
