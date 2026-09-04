import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { apiClient, setAuthToken } from '../api/client'
import type { User } from '../types'

const TOKEN_STORAGE_KEY = 'frugal_token'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_STORAGE_KEY))
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => token.value !== null)

  setAuthToken(token.value)

  function setToken(newToken: string | null): void {
    token.value = newToken
    setAuthToken(newToken)

    if (newToken) {
      localStorage.setItem(TOKEN_STORAGE_KEY, newToken)
    } else {
      localStorage.removeItem(TOKEN_STORAGE_KEY)
    }
  }

  async function fetchCurrentUser(): Promise<void> {
    const { data } = await apiClient.get<User>('/me')
    user.value = data
  }

  async function login(email: string, password: string): Promise<void> {
    const { data } = await apiClient.post<{ token: string }>('/login', { email, password })
    setToken(data.token)
    await fetchCurrentUser()
  }

  async function register(email: string, password: string, name: string): Promise<void> {
    await apiClient.post('/register', { email, password, name })
    await login(email, password)
  }

  function logout(): void {
    setToken(null)
    user.value = null
  }

  async function restoreSession(): Promise<void> {
    if (!token.value) {
      return
    }

    try {
      await fetchCurrentUser()
    } catch {
      logout()
    }
  }

  return { token, user, isAuthenticated, login, register, logout, restoreSession }
})
