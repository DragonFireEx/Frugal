import { defineStore } from 'pinia'
import { ref } from 'vue'
import { i18n, setLocale, type Locale } from '../i18n'

export const useLocaleStore = defineStore('locale', () => {
  const locale = ref<Locale>(i18n.global.locale.value as Locale)

  function changeLocale(value: Locale): void {
    locale.value = value
    setLocale(value)
  }

  function toggle(): void {
    changeLocale(locale.value === 'pl' ? 'en' : 'pl')
  }

  return { locale, setLocale: changeLocale, toggle }
})
