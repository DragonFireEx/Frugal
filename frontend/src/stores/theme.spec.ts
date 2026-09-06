import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useThemeStore } from './theme'

const STORAGE_KEY = 'frugal-theme'

function mockPrefersDark(matches: boolean): void {
  window.matchMedia = vi.fn().mockImplementation((query: string) => ({
    matches,
    media: query,
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
  }))
}

describe('useThemeStore', () => {
  beforeEach(() => {
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
    setActivePinia(createPinia())
  })

  it('falls back to system preference when nothing is stored', () => {
    mockPrefersDark(true)

    const store = useThemeStore()

    expect(store.theme).toBe('dark')
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
  })

  it('defaults to light when the system has no dark preference', () => {
    mockPrefersDark(false)

    const store = useThemeStore()

    expect(store.theme).toBe('light')
    expect(document.documentElement.getAttribute('data-theme')).toBe('light')
  })

  it('uses the stored theme over the system preference', () => {
    mockPrefersDark(true)
    localStorage.setItem(STORAGE_KEY, 'light')

    const store = useThemeStore()

    expect(store.theme).toBe('light')
  })

  it('toggle() flips the theme, applies the attribute, and persists it', () => {
    mockPrefersDark(false)

    const store = useThemeStore()
    store.toggle()

    expect(store.theme).toBe('dark')
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
    expect(localStorage.getItem(STORAGE_KEY)).toBe('dark')

    store.toggle()

    expect(store.theme).toBe('light')
    expect(localStorage.getItem(STORAGE_KEY)).toBe('light')
  })
})
