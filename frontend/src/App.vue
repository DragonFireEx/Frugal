<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useThemeStore } from './stores/theme'

const authStore = useAuthStore()
const themeStore = useThemeStore()
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
        <RouterLink to="/">Dashboard</RouterLink>
        <RouterLink to="/transactions">Transakcje</RouterLink>
        <RouterLink to="/categories">Kategorie</RouterLink>
        <RouterLink to="/tags">Tagi</RouterLink>
        <RouterLink to="/budgets">Budżety</RouterLink>
        <RouterLink to="/recurring-transactions">Cykliczne</RouterLink>
      </nav>
      <div class="app-nav-actions">
        <button type="button" class="btn btn-secondary btn-small" @click="themeStore.toggle()">
          {{ themeStore.theme === 'dark' ? 'Jasny motyw' : 'Ciemny motyw' }}
        </button>
        <button type="button" @click="handleLogout">Wyloguj</button>
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
