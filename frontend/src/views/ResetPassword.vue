<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useToasts } from '../store/toasts'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'
import logotipoUrl from '../public/logotipo - recloset.png'

const route = useRoute(); const router = useRouter()
const auth = useAuth(); const toasts = useToasts()

const token = route.query.token || ''
const email = route.query.email || ''
const password = ref('')
const confirmation = ref('')
const show = ref(false)
const loading = ref(false)
const error = ref('')

const rules = computed(() => ({
  len: password.value.length >= 8,
  case: /[a-z]/.test(password.value) && /[A-Z]/.test(password.value),
  num: /\d/.test(password.value),
}))
const invalidLink = computed(() => !token || !email)

async function submit() {
  if (loading.value) return
  error.value = ''
  if (password.value !== confirmation.value) { error.value = 'Las contraseñas no coinciden.'; return }
  loading.value = true
  try {
    await auth.resetPassword({
      email, token,
      password: password.value,
      password_confirmation: confirmation.value,
    })
    toasts.success('Contraseña actualizada. Ya puedes iniciar sesión.')
    router.push('/login')
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.password?.[0]
      || 'No pudimos restablecer la contraseña. El enlace puede haber expirado.'
  } finally { loading.value = false }
}
</script>

<template>
  <div class="max-w-md mx-auto mt-4 sm:mt-10">
    <div class="text-center mb-6">
      <img :src="logotipoUrl" alt="ReCloset Logotipo" class="h-24 mx-auto mb-2 object-contain" />
      <h1 class="font-display font-extrabold text-2xl sm:text-3xl mt-3">Nueva contraseña</h1>
      <p class="text-sm text-faint mt-1">Crea una contraseña segura para tu cuenta</p>
    </div>

    <div class="card-glass card-pad animate-fade-up shadow-glow">
      <div v-if="invalidLink" class="text-center py-4">
        <div class="mx-auto w-16 h-16 rounded-3xl bg-danger-500/10 text-danger-600 grid place-items-center mb-4">
          <Icon name="warning" :size="28" />
        </div>
        <p class="text-soft text-sm">El enlace no es válido o está incompleto. Solicita uno nuevo.</p>
        <router-link to="/forgot-password" class="btn btn-primary btn-block mt-6">Solicitar nuevo enlace</router-link>
      </div>

      <form v-else @submit.prevent="submit" class="space-y-4" novalidate>
        <div v-if="error" class="flex items-center gap-2 bg-danger-500/10 text-danger-600 dark:text-danger-400 text-sm p-3 rounded-xl animate-pop">
          <Icon name="warning" :size="17" class="shrink-0" />{{ error }}
        </div>
        <p class="text-sm text-muted">Para <strong class="text-body">{{ email }}</strong></p>
        <div>
          <label class="field-label" for="pw">Nueva contraseña</label>
          <div class="relative">
            <input id="pw" v-model="password" :type="show ? 'text' : 'password'" required placeholder="••••••••" class="input pr-11" autocomplete="new-password" />
            <button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 text-faint hover:text-brand-600 w-8 h-8 grid place-items-center" :aria-label="show ? 'Ocultar' : 'Ver'">
              <Icon :name="show ? 'eye-off' : 'eye'" :size="18" />
            </button>
          </div>
          <div v-if="password" class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-[11px]">
            <span class="inline-flex items-center gap-1" :class="rules.len ? 'text-brand-600 dark:text-brand-400' : 'text-faint'"><Icon :name="rules.len ? 'check' : 'info'" :size="12" /> 8+ caracteres</span>
            <span class="inline-flex items-center gap-1" :class="rules.case ? 'text-brand-600 dark:text-brand-400' : 'text-faint'"><Icon :name="rules.case ? 'check' : 'info'" :size="12" /> Mayús y minús</span>
            <span class="inline-flex items-center gap-1" :class="rules.num ? 'text-brand-600 dark:text-brand-400' : 'text-faint'"><Icon :name="rules.num ? 'check' : 'info'" :size="12" /> Un número</span>
          </div>
        </div>
        <div>
          <label class="field-label" for="pw2">Confirmar contraseña</label>
          <input id="pw2" v-model="confirmation" :type="show ? 'text' : 'password'" required placeholder="••••••••" class="input" autocomplete="new-password" />
        </div>
        <button :disabled="loading" class="btn btn-primary btn-block btn-lg">
          <Spinner v-if="loading" :size="18" light /> {{ loading ? 'Guardando…' : 'Restablecer contraseña' }}
        </button>
      </form>
    </div>
  </div>
</template>
