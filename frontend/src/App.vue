<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useLocaleStore } from './stores/locale'
import { useThemeStore } from './stores/theme'

const { t } = useI18n()
const authStore = useAuthStore()
const themeStore = useThemeStore()
const localeStore = useLocaleStore()
const router = useRouter()

function handleLogout(): void {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div id="app">
    <header v-if="authStore.isAuthenticated" class="app-nav">
      <nav>
        <RouterLink to="/">{{ t('nav.dashboard') }}</RouterLink>
        <RouterLink to="/transactions">{{ t('nav.transactions') }}</RouterLink>
        <RouterLink to="/categories">{{ t('nav.categories') }}</RouterLink>
        <RouterLink to="/tags">{{ t('nav.tags') }}</RouterLink>
        <RouterLink to="/budgets">{{ t('nav.budgets') }}</RouterLink>
        <RouterLink to="/recurring-transactions">{{ t('nav.recurring') }}</RouterLink>
      </nav>
      <div class="app-nav-actions">
        <button type="button" class="btn btn-secondary btn-small" @click="localeStore.toggle()">
          {{ t('nav.language') }}
        </button>
        <button type="button" class="btn btn-secondary btn-small" @click="themeStore.toggle()">
          {{ themeStore.theme === 'dark' ? t('nav.themeToLight') : t('nav.themeToDark') }}
        </button>
        <button type="button" @click="handleLogout">{{ t('nav.logout') }}</button>
      </div>
    </header>

    <main class="app-main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 12px 20px;
  border-bottom: 1px solid var(--border);
}

.app-nav nav {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.app-nav-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.app-nav a.router-link-active {
  color: var(--accent);
}

.app-main {
  padding: 20px;
  min-width: 0;
}
</style>
