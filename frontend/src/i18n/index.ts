import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import pl from './locales/pl.json'

export type Locale = 'pl' | 'en'

const STORAGE_KEY = 'frugal-locale'

function getStoredLocale(): Locale {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored === 'pl' || stored === 'en') {
      return stored
    }
  } catch {
    // localStorage unavailable (private browsing, disabled storage) - fall back to default
  }

  return 'pl'
}

export const i18n = createI18n({
  legacy: false,
  locale: getStoredLocale(),
  fallbackLocale: 'pl',
  messages: { pl, en },
})

export function setLocale(locale: Locale): void {
  i18n.global.locale.value = locale
  try {
    localStorage.setItem(STORAGE_KEY, locale)
  } catch {
    // ignore write failures (private browsing, storage full)
  }
}
