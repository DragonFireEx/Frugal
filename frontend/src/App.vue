<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()
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
        <RouterLink to="/budgets">Budżety</RouterLink>
      </nav>
      <button type="button" @click="handleLogout">Wyloguj</button>
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

.app-nav a.router-link-active {
  color: var(--accent);
}

.app-main {
  padding: 20px;
  min-width: 0;
}
</style>
