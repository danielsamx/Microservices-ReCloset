<script setup>
import { ref } from 'vue'
import { useAuth } from '../store/auth'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'
import logotipoUrl from '../public/logotipo - recloset.png'

const auth = useAuth()
const email = ref('')
const loading = ref(false)
const done = ref(false)
const message = ref('')

async function submit() {
  if (loading.value) return
  loading.value = true
  try {
    const res = await auth.forgotPassword(email.value)
    message.value = res.message
    done.value = true
  } catch (e) {
    message.value = 'No pudimos procesar la solicitud. Inténtalo de nuevo.'
    done.value = true
  } finally { loading.value = false }
}
</script>

<template>
  <div class="max-w-md mx-auto mt-4 sm:mt-10">
    <div class="text-center mb-6">
      <img :src="logotipoUrl" alt="ReCloset Logotipo" class="h-24 mx-auto mb-2 object-contain" />
      <h1 class="font-display font-extrabold text-2xl sm:text-3xl mt-3">Recupera tu cuenta</h1>
      <p class="text-sm text-faint mt-1">Te enviaremos un enlace para restablecer tu contraseña</p>
    </div>

    <div class="card-glass card-pad animate-fade-up shadow-glow">
      <div v-if="done" class="text-center py-4 animate-pop">
        <div class="mx-auto w-16 h-16 rounded-3xl bg-gradient-to-br from-brand-500/15 to-lime-400/15 text-brand-600 dark:text-brand-300 grid place-items-center mb-4 ring-1 ring-brand-500/20">
          <Icon name="send" :size="28" />
        </div>
        <p class="text-soft text-sm leading-relaxed">{{ message }}</p>
        <router-link to="/login" class="btn btn-primary btn-block mt-6"><Icon name="back" :size="16" /> Volver a iniciar sesión</router-link>
      </div>

      <form v-else @submit.prevent="submit" class="space-y-4" novalidate>
        <div>
          <label class="field-label" for="email">Correo electrónico</label>
          <input id="email" v-model="email" type="email" required placeholder="tu@correo.com" class="input" autocomplete="email" />
        </div>
        <button :disabled="loading || !email" class="btn btn-primary btn-block btn-lg">
          <Spinner v-if="loading" :size="18" light /> {{ loading ? 'Enviando…' : 'Enviar enlace' }}
        </button>
        <p class="text-sm text-muted text-center">
          <router-link to="/login" class="text-brand-700 dark:text-brand-300 font-semibold hover:underline">Volver a iniciar sesión</router-link>
        </p>
      </form>
    </div>
  </div>
</template>
