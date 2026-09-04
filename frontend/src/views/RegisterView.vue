<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const errorMessage = ref('')
const isSubmitting = ref(false)

async function handleSubmit(): Promise<void> {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await authStore.register(email.value, password.value, name.value)
    await router.push('/')
  } catch (error) {
    errorMessage.value = axios.isAxiosError(error)
      ? (error.response?.data?.error ?? error.response?.data?.message ?? 'Nie udało się zarejestrować.')
      : 'Nie udało się zarejestrować.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="auth-view">
    <h1>Zarejestruj się</h1>

    <form @submit.prevent="handleSubmit">
      <label>
        Imię
        <input v-model="name" type="text" required autocomplete="name" />
      </label>

      <label>
        Email
        <input v-model="email" type="email" required autocomplete="email" />
      </label>

      <label>
        Hasło
        <input v-model="password" type="password" required autocomplete="new-password" />
      </label>

      <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>

      <button type="submit" :disabled="isSubmitting">Zarejestruj się</button>
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
