<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useToasts } from '../store/toasts'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'
import FormErrors from '../components/ui/FormErrors.vue'
import Logo from '../components/ui/Logo.vue'
const auth = useAuth(); const router = useRouter(); const toasts = useToasts()
const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({}); const loading = ref(false); const generalError = ref('')
const rules = computed(() => ({
  len: form.value.password.length >= 8,
  case: /[a-z]/.test(form.value.password) && /[A-Z]/.test(form.value.password),
  num: /\d/.test(form.value.password),
}))
async function submit() {
  if (loading.value) return
  errors.value = {}; generalError.value = ''; loading.value = true
  try {
    await auth.register(form.value)
    toasts.success('¡Cuenta creada! Bienvenido a ReCloset.')
    toasts.info('Te enviamos un correo para verificar tu cuenta.', 6000)
    router.push('/catalog')
  } catch (e) {
    if (e.response?.data?.errors) errors.value = e.response.data.errors
    else generalError.value = e.response
      ? 'No se pudo completar el registro. Inténtalo de nuevo.'
      : 'No pudimos conectar con el servidor. Comprueba tu conexión.'
  } finally { loading.value = false }
}
</script>
<template>
  <div class="max-w-md mx-auto mt-4 sm:mt-8">
    <div class="text-center mb-6">
      <Logo :size="52" class="mx-auto mb-2" />
      <h1 class="font-display font-extrabold text-2xl sm:text-3xl mt-3">Crea tu cuenta</h1>
      <p class="text-sm text-faint mt-1">Únete a la comunidad de moda circular</p>
    </div>
    <div class="card-glass card-pad animate-fade-up shadow-glow">
      <FormErrors :message="generalError" :errors="errors" />
      <form @submit.prevent="submit" class="space-y-4" novalidate>
        <div>
          <label class="field-label">Nombre <span class="field-req">*</span></label>
          <input v-model="form.name" required placeholder="Tu nombre" class="input" :class="{ 'input-error': errors.name }" autocomplete="name" />
          <p v-if="errors.name" class="field-err"><Icon name="warning" :size="13" /> {{ errors.name[0] }}</p>
        </div>
        <div>
          <label class="field-label">Correo electrónico <span class="field-req">*</span></label>
          <input v-model="form.email" type="email" required placeholder="tu@correo.com" class="input" :class="{ 'input-error': errors.email }" autocomplete="email" />
          <p v-if="errors.email" class="field-err"><Icon name="warning" :size="13" /> {{ errors.email[0] }}</p>
        </div>
        <div>
          <label class="field-label">Contraseña <span class="field-req">*</span></label>
          <input v-model="form.password" type="password" required placeholder="Crea una contraseña" class="input" :class="{ 'input-error': errors.password }" autocomplete="new-password" />
          <div v-if="form.password" class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-[11px]">
            <span class="inline-flex items-center gap-1 transition-colors" :class="rules.len ? 'text-brand-600 dark:text-brand-400' : 'text-faint'"><Icon :name="rules.len ? 'check' : 'info'" :size="12" /> 8+ caracteres</span>
            <span class="inline-flex items-center gap-1 transition-colors" :class="rules.case ? 'text-brand-600 dark:text-brand-400' : 'text-faint'"><Icon :name="rules.case ? 'check' : 'info'" :size="12" /> Mayús y minús</span>
            <span class="inline-flex items-center gap-1 transition-colors" :class="rules.num ? 'text-brand-600 dark:text-brand-400' : 'text-faint'"><Icon :name="rules.num ? 'check' : 'info'" :size="12" /> Un número</span>
          </div>
          <p v-if="errors.password" class="field-err"><Icon name="warning" :size="13" /> {{ errors.password[0] }}</p>
        </div>
        <div>
          <label class="field-label">Confirmar contraseña <span class="field-req">*</span></label>
          <input v-model="form.password_confirmation" type="password" required placeholder="Repite la contraseña" class="input" autocomplete="new-password" />
        </div>
        <button :disabled="loading" class="btn btn-primary btn-block btn-lg">
          <Spinner v-if="loading" :size="18" light /> {{ loading ? 'Creando cuenta…' : 'Crear cuenta' }}
        </button>
      </form>
      <p class="text-sm text-muted mt-5 text-center">¿Ya tienes cuenta?
        <router-link to="/login" class="text-brand-700 dark:text-brand-300 font-semibold hover:underline">Inicia sesión</router-link>
      </p>
    </div>
  </div>
</template>
