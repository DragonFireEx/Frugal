import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import router from './router'
import { setUnauthorizedHandler } from './api/client'
import { useAuthStore } from './stores/auth'

const app = createApp(App)

app.use(createPinia())

const authStore = useAuthStore()

setUnauthorizedHandler(() => {
  authStore.logout()
  router.push('/login')
})

await authStore.restoreSession()

app.use(router)

app.mount('#app')
