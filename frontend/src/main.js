import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { useAuthStore } from '@/stores/auth'
import { authToken } from '@/api/authToken'
import '@/assets/styles/app.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(vuetify)

async function bootstrap() {
  const auth = useAuthStore()

  if (authToken.value && !auth.user) {
    await auth.fetchUser()
  }

  await router.isReady()
  app.mount('#app')
}

bootstrap()
