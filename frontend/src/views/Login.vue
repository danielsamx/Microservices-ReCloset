<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useToasts } from '../store/toasts'
import Spinner from '../components/ui/Spinner.vue'
import Icon from '../components/ui/Icon.vue'
import logotipoUrl from '../public/logotipo - recloset.png'
import OtpInput from '../components/ui/OtpInput.vue'

const auth = useAuth(); const router = useRouter(); const toasts = useToasts()
const form = ref({ email: '', password: '' })
const error = ref(''); const loading = ref(false); const show = ref(false)

// Estado del paso 2FA
const step = ref('credentials')   // 'credentials' | 'otp'
const challenge = ref('')
const code = ref('')
const otpError = ref('')
const verifying = ref(false)
const resending = ref(false)

async function submit() {
  if (loading.value) return
  error.value = ''; loading.value = true
  try {
    const res = await auth.login(form.value)
    if (res.requires_2fa) {
      challenge.value = res.challenge
      step.value = 'otp'
      code.value = ''
      toasts.info('Te enviamos un código a tu correo.')
    } else {
      toasts.success('¡Bienvenido de vuelta!')
      router.push('/catalog')
    }
  } catch (e) {
    error.value = e.response?.data?.errors?.email?.[0] || (e.response ? 'Correo o contraseña incorrectos.' : 'No pudimos conectar con el servidor. Comprueba tu conexión.')
  } finally { loading.value = false }
}

async function verify(codeValue) {
  const c = codeValue || code.value
  if (verifying.value || c.length !== 6) return
  otpError.value = ''; verifying.value = true
  try {
    await auth.verifyTwoFactor({ challenge: challenge.value, code: c })
    toasts.success('¡Bienvenido de vuelta!')
    router.push('/catalog')
  } catch (e) {
    otpError.value = e.response?.data?.errors?.code?.[0] || 'Código incorrecto. Inténtalo de nuevo.'
    code.value = ''
  } finally { verifying.value = false }
}

async function resend() {
  if (resending.value) return
  resending.value = true; otpError.value = ''
  try {
    const res = await auth.login(form.value)
    if (res.requires_2fa) { challenge.value = res.challenge; code.value = ''; toasts.info('Te enviamos un código nuevo.') }
  } catch (e) {
    otpError.value = 'No pudimos reenviar el código.'
  } finally { resending.value = false }
}

function backToCredentials() { step.value = 'credentials'; code.value = ''; otpError.value = '' }
</script>

<template>
  <div class="max-w-md mx-auto mt-4 sm:mt-10">
    <div class="text-center mb-6">
      <img :src="logotipoUrl" alt="ReCloset Logotipo" class="h-24 mx-auto mb-2 object-contain" />
      <h1 class="font-display font-extrabold text-2xl sm:text-3xl mt-3">
        {{ step === 'otp' ? 'Verificación en dos pasos' : 'Bienvenido de vuelta' }}
      </h1>
      <p class="text-sm text-faint mt-1">
        {{ step === 'otp' ? 'Ingresa el código de 6 dígitos que enviamos a tu correo' : 'Inicia sesión para continuar' }}
      </p>
    </div>

    <div class="card-glass card-pad animate-fade-up shadow-glow">
      <!-- Paso 1: credenciales -->
      <template v-if="step === 'credentials'">
        <div v-if="error" class="flex items-center gap-2 bg-danger-500/10 text-danger-600 dark:text-danger-400 text-sm p-3 rounded-xl mb-4 animate-pop">
          <Icon name="warning" :size="17" class="shrink-0" />{{ error }}
        </div>
        <form @submit.prevent="submit" class="space-y-4" novalidate>
          <div>
            <label class="field-label" for="email">Correo electrónico</label>
            <input id="email" v-model="form.email" type="email" required placeholder="tu@correo.com" class="input" autocomplete="email" />
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="field-label !mb-0" for="pw">Contraseña</label>
              <router-link to="/forgot-password" class="text-xs text-brand-700 dark:text-brand-300 font-semibold hover:underline">¿Olvidaste tu contraseña?</router-link>
            </div>
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
      </template>

      <!-- Paso 2: OTP -->
      <template v-else>
        <div class="text-center mb-4">
          <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400/20 to-lime-400/20 text-brand-600 dark:text-brand-300 grid place-items-center mb-2">
            <Icon name="bell" :size="26" />
          </div>
        </div>
        <div v-if="otpError" class="flex items-center gap-2 bg-danger-500/10 text-danger-600 dark:text-danger-400 text-sm p-3 rounded-xl mb-4 animate-pop">
          <Icon name="warning" :size="17" class="shrink-0" />{{ otpError }}
        </div>
        <OtpInput v-model="code" @complete="verify" class="mb-5" />
        <button :disabled="verifying || code.length !== 6" class="btn btn-primary btn-block btn-lg" @click="verify()">
          <Spinner v-if="verifying" :size="18" light /> {{ verifying ? 'Verificando…' : 'Verificar y entrar' }}
        </button>
        <div class="flex items-center justify-between mt-4 text-sm">
          <button type="button" @click="backToCredentials" class="text-muted hover:text-body inline-flex items-center gap-1"><Icon name="back" :size="15" /> Volver</button>
          <button type="button" @click="resend" :disabled="resending" class="text-brand-700 dark:text-brand-300 font-semibold hover:underline disabled:opacity-50">
            {{ resending ? 'Enviando…' : 'Reenviar código' }}
          </button>
        </div>
      </template>
    </div>
  </div>
</template>
