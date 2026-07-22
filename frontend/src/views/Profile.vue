<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useToasts } from '../store/toasts'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Modal from '../components/ui/Modal.vue'
import Icon from '../components/ui/Icon.vue'
import Spinner from '../components/ui/Spinner.vue'
import OtpInput from '../components/ui/OtpInput.vue'

const auth = useAuth(); const router = useRouter(); const toasts = useToasts()
const confirmOut = ref(false); const loggingOut = ref(false)

const shortcuts = [
  { to: '/my-items', icon: 'bag', label: 'Mis publicaciones' },
  { to: '/chat', icon: 'message', label: 'Mensajes' },
  { to: '/notifications', icon: 'bell', label: 'Notificaciones' },
  { to: '/items/new', icon: 'plus', label: 'Publicar prenda' },
]

const emailVerified = computed(() => auth.user?.email_verified)
const twoFactor = computed(() => auth.user?.two_factor_enabled)

onMounted(() => { auth.refreshMe().catch(() => {}) })

// --- Verificación de correo ---
const resending = ref(false)
async function resendVerification() {
  if (resending.value) return
  resending.value = true
  try { const r = await auth.resendVerification(); toasts.success(r.message || 'Enlace de verificación enviado.') }
  catch (e) { toasts.error('No pudimos enviar el correo. Inténtalo más tarde.') }
  finally { resending.value = false }
}

// --- Activar 2FA ---
const enableModal = ref(false)
const enableChallenge = ref('')
const enableCode = ref('')
const enableError = ref('')
const enableSending = ref(false)   // enviando/reenviando código
const enableConfirming = ref(false)

async function startEnable() {
  enableError.value = ''; enableCode.value = ''; enableSending.value = true
  try {
    const r = await auth.enableTwoFactor()
    enableChallenge.value = r.challenge
    enableModal.value = true
    toasts.info('Te enviamos un código a tu correo.')
  } catch (e) {
    toasts.error(e.response?.data?.message || 'No pudimos iniciar la activación.')
  } finally { enableSending.value = false }
}
async function confirmEnable(codeValue) {
  const c = codeValue || enableCode.value
  if (enableConfirming.value || c.length !== 6) return
  enableError.value = ''; enableConfirming.value = true
  try {
    await auth.confirmTwoFactor({ challenge: enableChallenge.value, code: c })
    enableModal.value = false
    toasts.success('Verificación en dos pasos activada.')
  } catch (e) {
    enableError.value = e.response?.data?.errors?.code?.[0] || 'Código incorrecto.'
    enableCode.value = ''
  } finally { enableConfirming.value = false }
}

// --- Desactivar 2FA ---
const disableModal = ref(false)
const disablePassword = ref('')
const disableError = ref('')
const disabling = ref(false)
async function confirmDisable() {
  if (disabling.value) return
  disableError.value = ''; disabling.value = true
  try {
    await auth.disableTwoFactor(disablePassword.value)
    disableModal.value = false; disablePassword.value = ''
    toasts.success('Verificación en dos pasos desactivada.')
  } catch (e) {
    disableError.value = e.response?.data?.errors?.password?.[0] || 'No pudimos desactivarla.'
  } finally { disabling.value = false }
}

async function logout() { loggingOut.value = true; await auth.logout(); router.push('/login') }
</script>

<template>
  <div class="max-w-md mx-auto space-y-4">
    <div class="card-glass overflow-hidden animate-fade-up shadow-glow">
      <div class="relative h-28 bg-gradient-to-br from-brand-700 via-brand-500 to-brand-300 overflow-hidden">
        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-lime-400/25 blur-xl animate-float"></div>
        <div class="absolute inset-0 opacity-[0.08]" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 30px 30px;"></div>
      </div>
      <div class="px-5 pb-5 -mt-11">
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 shadow-card grid place-items-center text-3xl font-display font-extrabold text-white uppercase ring-4 ring-white dark:ring-[#121e19]">
          {{ auth.user?.name?.[0] || 'U' }}
        </div>
        <div class="flex items-center gap-2 mt-3">
          <h1 class="font-display font-bold text-xl">{{ auth.user?.name }}</h1>
          <span v-if="emailVerified" class="badge badge-available" title="Correo verificado"><Icon name="success" :size="12" /> Verificado</span>
        </div>
        <p class="text-sm text-faint">{{ auth.user?.email }}</p>

        <div class="grid gap-2 mt-5">
          <router-link v-for="s in shortcuts" :key="s.to" :to="s.to" class="group flex items-center gap-3 p-3 rounded-xl hover:bg-brand-500/8 hover:border-brand-400/40 transition border" style="border-color: var(--border);">
            <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-brand-300 to-brand-600 text-white grid place-items-center shadow-soft transition-transform group-hover:scale-105"><Icon :name="s.icon" :size="18" /></span>
            <span class="font-medium text-sm text-soft">{{ s.label }}</span>
            <Icon name="chevronRight" :size="16" class="ml-auto text-faint transition-transform group-hover:translate-x-0.5" />
          </router-link>
        </div>

        <button @click="confirmOut = true" class="btn btn-danger btn-block mt-5"><Icon name="logout" :size="17" /> Cerrar sesión</button>
      </div>
    </div>

    <!-- Seguridad -->
    <div class="card card-pad animate-fade-up" style="animation-delay: 60ms">
      <h2 class="font-display font-bold text-lg mb-1">Seguridad</h2>
      <p class="text-sm text-faint mb-4">Protege el acceso a tu cuenta</p>

      <!-- Verificación de correo -->
      <div class="flex items-center gap-3 py-3 border-b" style="border-color: var(--border-soft);">
        <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0" :class="emailVerified ? 'bg-brand-500/12 text-brand-600 dark:text-brand-300' : 'bg-amber-500/12 text-amber-600 dark:text-amber-400'">
          <Icon :name="emailVerified ? 'success' : 'warning'" :size="20" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="font-semibold text-sm text-body">Correo electrónico</p>
          <p class="text-xs" :class="emailVerified ? 'text-muted' : 'text-amber-600 dark:text-amber-400'">
            {{ emailVerified ? 'Verificado' : 'Pendiente de verificar' }}
          </p>
        </div>
        <button v-if="!emailVerified" @click="resendVerification" :disabled="resending" class="btn btn-soft btn-sm shrink-0">
          <Spinner v-if="resending" :size="14" /> Reenviar
        </button>
      </div>

      <!-- 2FA -->
      <div class="flex items-center gap-3 py-3">
        <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0" :class="twoFactor ? 'bg-brand-500/12 text-brand-600 dark:text-brand-300' : 'bg-brand-500/8 text-faint'">
          <Icon name="user-circle" :size="20" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="font-semibold text-sm text-body">Verificación en dos pasos</p>
          <p class="text-xs text-muted">{{ twoFactor ? 'Activa · código por correo en cada login' : 'Añade un código por correo al iniciar sesión' }}</p>
        </div>
        <button v-if="!twoFactor" @click="startEnable" :disabled="enableSending" class="btn btn-primary btn-sm shrink-0">
          <Spinner v-if="enableSending" :size="14" light /> Activar
        </button>
        <button v-else @click="disableModal = true" class="btn btn-danger btn-sm shrink-0">Desactivar</button>
      </div>
    </div>
  </div>

  <!-- Modal activar 2FA -->
  <Modal :open="enableModal" title="Activar verificación en dos pasos" @close="enableModal = false">
    <p class="text-sm text-muted mb-4">Ingresa el código de 6 dígitos que enviamos a <strong class="text-body">{{ auth.user?.email }}</strong>.</p>
    <div v-if="enableError" class="flex items-center gap-2 bg-danger-500/10 text-danger-600 dark:text-danger-400 text-sm p-3 rounded-xl mb-4 animate-pop">
      <Icon name="warning" :size="17" class="shrink-0" />{{ enableError }}
    </div>
    <OtpInput v-if="enableModal" v-model="enableCode" @complete="confirmEnable" class="mb-5" />
    <button :disabled="enableConfirming || enableCode.length !== 6" class="btn btn-primary btn-block" @click="confirmEnable()">
      <Spinner v-if="enableConfirming" :size="18" light /> {{ enableConfirming ? 'Activando…' : 'Confirmar y activar' }}
    </button>
    <button type="button" @click="startEnable" :disabled="enableSending" class="btn btn-ghost btn-block btn-sm mt-2">
      {{ enableSending ? 'Enviando…' : 'Reenviar código' }}
    </button>
  </Modal>

  <!-- Modal desactivar 2FA -->
  <Modal :open="disableModal" title="Desactivar verificación en dos pasos" @close="disableModal = false">
    <p class="text-sm text-muted mb-4">Confirma tu contraseña para desactivar la verificación en dos pasos.</p>
    <div v-if="disableError" class="flex items-center gap-2 bg-danger-500/10 text-danger-600 dark:text-danger-400 text-sm p-3 rounded-xl mb-4 animate-pop">
      <Icon name="warning" :size="17" class="shrink-0" />{{ disableError }}
    </div>
    <form @submit.prevent="confirmDisable">
      <input v-model="disablePassword" type="password" required placeholder="Tu contraseña" class="input mb-4" autocomplete="current-password" />
      <button :disabled="disabling || !disablePassword" class="btn btn-primary btn-block !bg-danger-600 hover:!bg-danger-700">
        <Spinner v-if="disabling" :size="18" light /> {{ disabling ? 'Desactivando…' : 'Desactivar' }}
      </button>
    </form>
  </Modal>

  <ConfirmDialog :open="confirmOut" :loading="loggingOut"
    title="Cerrar sesión" message="¿Seguro que quieres cerrar tu sesión?"
    confirmText="Cerrar sesión" @confirm="logout" @cancel="confirmOut = false" />
</template>
