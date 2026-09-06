import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { i18n } from '../i18n'
import { useLocaleStore } from './locale'

const STORAGE_KEY = 'frugal-locale'

describe('useLocaleStore', () => {
  beforeEach(() => {
    localStorage.clear()
    i18n.global.locale.value = 'pl'
    setActivePinia(createPinia())
  })

  it('starts from the current i18n locale', () => {
    const store = useLocaleStore()

    expect(store.locale).toBe('pl')
  })

  it('setLocale() updates the i18n instance and persists the choice', () => {
    const store = useLocaleStore()
    store.setLocale('en')

    expect(store.locale).toBe('en')
    expect(i18n.global.locale.value).toBe('en')
    expect(localStorage.getItem(STORAGE_KEY)).toBe('en')
  })

  it('toggle() flips between pl and en', () => {
    const store = useLocaleStore()

    store.toggle()
    expect(store.locale).toBe('en')

    store.toggle()
    expect(store.locale).toBe('pl')
  })
})
