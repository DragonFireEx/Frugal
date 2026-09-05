import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('../api/client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
  setAuthToken: vi.fn(),
}))

import { apiClient } from '../api/client'
import { useAuthStore } from './auth'

const TOKEN_STORAGE_KEY = 'frugal_token'

describe('useAuthStore', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  it('starts unauthenticated when there is no stored token', () => {
    const store = useAuthStore()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('login() sets token and user, and persists the token', async () => {
    vi.mocked(apiClient.post).mockResolvedValueOnce({ data: { token: 'abc123' } })
    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: { id: 1, email: 'a@b.com', name: 'A' } })

    const store = useAuthStore()
    await store.login('a@b.com', 'password')

    expect(store.token).toBe('abc123')
    expect(store.user).toEqual({ id: 1, email: 'a@b.com', name: 'A' })
    expect(store.isAuthenticated).toBe(true)
    expect(localStorage.getItem(TOKEN_STORAGE_KEY)).toBe('abc123')
    expect(apiClient.post).toHaveBeenCalledWith('/login', { email: 'a@b.com', password: 'password' })
  })

  it('logout() clears token, user and localStorage', async () => {
    vi.mocked(apiClient.post).mockResolvedValueOnce({ data: { token: 'abc123' } })
    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: { id: 1, email: 'a@b.com', name: 'A' } })

    const store = useAuthStore()
    await store.login('a@b.com', 'password')

    store.logout()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(localStorage.getItem(TOKEN_STORAGE_KEY)).toBeNull()
  })

  it('register() registers then logs in', async () => {
    vi.mocked(apiClient.post)
      .mockResolvedValueOnce({ data: { id: 1, email: 'a@b.com', name: 'A' } })
      .mockResolvedValueOnce({ data: { token: 'abc123' } })
    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: { id: 1, email: 'a@b.com', name: 'A' } })

    const store = useAuthStore()
    await store.register('a@b.com', 'password', 'A')

    expect(apiClient.post).toHaveBeenNthCalledWith(1, '/register', {
      email: 'a@b.com',
      password: 'password',
      name: 'A',
    })
    expect(apiClient.post).toHaveBeenNthCalledWith(2, '/login', { email: 'a@b.com', password: 'password' })
    expect(store.token).toBe('abc123')
    expect(store.isAuthenticated).toBe(true)
  })

  it('restoreSession() loads the user when a token is already stored', async () => {
    localStorage.setItem(TOKEN_STORAGE_KEY, 'existing-token')
    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: { id: 2, email: 'x@y.com', name: 'X' } })

    const store = useAuthStore()
    await store.restoreSession()

    expect(store.user).toEqual({ id: 2, email: 'x@y.com', name: 'X' })
    expect(store.isAuthenticated).toBe(true)
  })

  it('restoreSession() logs out if the stored token is rejected', async () => {
    localStorage.setItem(TOKEN_STORAGE_KEY, 'stale-token')
    vi.mocked(apiClient.get).mockRejectedValueOnce(new Error('401'))

    const store = useAuthStore()
    await store.restoreSession()

    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(localStorage.getItem(TOKEN_STORAGE_KEY)).toBeNull()
  })

  it('does nothing when restoreSession() is called without a stored token', async () => {
    const store = useAuthStore()
    await store.restoreSession()

    expect(apiClient.get).not.toHaveBeenCalled()
    expect(store.isAuthenticated).toBe(false)
  })
})
