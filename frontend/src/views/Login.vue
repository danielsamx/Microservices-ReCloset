<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useToasts } from '../store/toasts'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'
import Logo from '../components/ui/Logo.vue'
const auth = useAuth(); const router = useRouter(); const toasts = useToasts()
const form = ref({ email: '', password: '' }); const error = ref(''); const loading = ref(false); const show = ref(false)
async function submit() {
  if (loading.value) return
  error.value = ''; loading.value = true
  try {
    await auth.login(form.value)
    toasts.success('¡Bienvenido de vuelta!')
    router.push('/catalog')
  } catch (e) {
    error.value = e.response?.data?.errors?.email?.[0] || (e.response ? 'Correo o contraseña incorrectos.' : 'No pudimos conectar con el servidor. Comprueba tu conexión.')
  } finally { loading.value = false }
}
</script>
<template>
  <div class="max-w-md mx-auto mt-4 sm:mt-10">
    <div class="text-center mb-6">
      <Logo :size="52" class="mx-auto mb-2" />
      <h1 class="font-display font-extrabold text-2xl sm:text-3xl mt-3">Bienvenido de vuelta</h1>
      <p class="text-sm text-faint mt-1">Inicia sesión para continuar</p>
    </div>
    <div class="card-glass card-pad animate-fade-up shadow-glow">
      <div v-if="error" class="flex items-center gap-2 bg-danger-500/10 text-danger-600 dark:text-danger-400 text-sm p-3 rounded-xl mb-4 animate-pop">
        <Icon name="warning" :size="17" class="shrink-0" />{{ error }}
      </div>
      <form @submit.prevent="submit" class="space-y-4" novalidate>
        <div>
          <label class="field-label" for="email">Correo electrónico</label>
          <input id="email" v-model="form.email" type="email" required placeholder="tu@correo.com" class="input" autocomplete="email" />
        </div>
        <div>
          <label class="field-label" for="pw">Contraseña</label>
          <div class="relative">
            <input id="pw" v-model="form.password" :type="show ? 'text' : 'password'" required placeholder="••••••••" class="input pr-11" autocomplete="current-password" />
            <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 text-faint hover:text-brand-600 w-8 h-8 grid place-items-center transition-colors" :aria-label="show ? 'Ocultar contraseña' : 'Ver contraseña'">
              <Icon :name="show ? 'eye-off' : 'eye'" :size="18" />
            </button>
          </div>
        </div>
        <button :disabled="loading" class="btn btn-primary btn-block btn-lg">
          <Spinner v-if="loading" :size="18" light /> {{ loading ? 'Entrando…' : 'Iniciar sesión' }}
        </button>
      </form>
      <p class="text-sm text-muted mt-5 text-center">¿No tienes cuenta?
        <router-link to="/register" class="text-brand-700 dark:text-brand-300 font-semibold hover:underline">Regístrate gratis</router-link>
      </p>
    </div>
  </div>
</template>
