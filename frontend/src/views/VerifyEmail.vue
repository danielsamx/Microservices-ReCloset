<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '../store/auth'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'
import Logo from '../components/ui/Logo.vue'

const route = useRoute()
const auth = useAuth()
const state = ref('loading') // loading | success | error
const message = ref('')

onMounted(async () => {
  const token = route.query.token
  const email = route.query.email
  if (!token || !email) { state.value = 'error'; message.value = 'El enlace de verificación está incompleto.'; return }
  try {
    const res = await auth.verifyEmail({ email, token })
    state.value = 'success'
    message.value = res.message || 'Tu correo fue verificado correctamente.'
  } catch (e) {
    state.value = 'error'
    message.value = e.response?.data?.message || 'No pudimos verificar tu correo. El enlace puede haber expirado.'
  }
})
</script>

<template>
  <div class="max-w-md mx-auto mt-4 sm:mt-12">
    <div class="text-center mb-6">
      <Logo :size="52" class="mx-auto mb-2" />
    </div>
    <div class="card-glass card-pad animate-fade-up shadow-glow text-center py-8">
      <template v-if="state === 'loading'">
        <Spinner :size="34" class="mx-auto" />
        <p class="text-muted text-sm mt-4">Verificando tu correo…</p>
      </template>

      <template v-else-if="state === 'success'">
        <div class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-br from-brand-400 to-brand-600 text-white grid place-items-center mb-4 shadow-glow animate-pop">
          <Icon name="success" :size="40" />
        </div>
        <h1 class="font-display font-extrabold text-2xl">¡Correo verificado!</h1>
        <p class="text-muted text-sm mt-2">{{ message }}</p>
        <router-link to="/catalog" class="btn btn-primary btn-block mt-6"><Icon name="compass" :size="16" /> Explorar el catálogo</router-link>
      </template>

      <template v-else>
        <div class="mx-auto w-20 h-20 rounded-3xl bg-danger-500/10 text-danger-600 grid place-items-center mb-4">
          <Icon name="warning" :size="36" />
        </div>
        <h1 class="font-display font-extrabold text-2xl">No se pudo verificar</h1>
        <p class="text-muted text-sm mt-2">{{ message }}</p>
        <router-link to="/profile" class="btn btn-ghost btn-block mt-6">Ir a mi perfil</router-link>
      </template>
    </div>
  </div>
</template>
