import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useTheme } from './store/theme'
import './style.css'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

// Sincroniza el store del tema con la clase ya aplicada en <html>
useTheme(pinia).init()

app.use(router).mount('#app')
