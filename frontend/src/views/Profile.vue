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
  { to: '/my-items', icon: 'bag', label: 'Mis publicaciones', sub: 'Gestiona tus prendas' },
  { to: '/chat', icon: 'message', label: 'Mensajes', sub: 'Tus conversaciones' },
  { to: '/notifications', icon: 'bell', label: 'Notificaciones', sub: 'Avisos y actividad' },
  { to: '/items/new', icon: 'plus', label: 'Publicar prenda', sub: 'Sube algo nuevo' },
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
  <div class="max-w-5xl mx-auto">
    <!-- Hero de perfil (ancho completo) -->
    <div class="card-glass overflow-hidden animate-fade-up shadow-glow">
      <div class="relative h-24 sm:h-28 bg-gradient-to-br from-brand-700 via-brand-500 to-brand-300 overflow-hidden">
        <div class="absolute -right-6 -top-8 w-40 h-40 rounded-full bg-lime-400/25 blur-xl animate-float"></div>
        <div class="absolute inset-0 opacity-[0.08]" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 30px 30px;"></div>
      </div>
      <div class="relative z-10 px-5 sm:px-7 pb-5">
        <!-- avatar solapando el banner, en su propia línea (z-10 para pintar por encima) -->
        <div class="relative z-10 w-20 h-20 sm:w-24 sm:h-24 -mt-10 sm:-mt-12 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 shadow-card grid place-items-center text-3xl sm:text-4xl font-display font-extrabold text-white uppercase ring-4 ring-white dark:ring-[#121e19]">
          {{ auth.user?.name?.[0] || 'U' }}
        </div>
        <!-- nombre + correo, siempre debajo del banner (sobre blanco) -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mt-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="font-display font-bold text-xl sm:text-2xl leading-tight">{{ auth.user?.name }}</h1>
              <span v-if="emailVerified" class="badge badge-available" title="Correo verificado"><Icon name="success" :size="12" /> Verificado</span>
            </div>
            <p class="text-sm text-faint mt-0.5 truncate">{{ auth.user?.email }}</p>
          </div>
          <button @click="confirmOut = true" class="btn btn-ghost btn-sm shrink-0 self-start">
            <Icon name="logout" :size="16" /> Cerrar sesión
          </button>
        </div>
      </div>
    </div>

    <!-- Dashboard de dos columnas -->
    <div class="grid lg:grid-cols-[1.25fr_1fr] gap-4 mt-4">
      <!-- Accesos rápidos -->
      <div class="card card-pad animate-fade-up" style="animation-delay: 40ms">
        <h2 class="font-display font-bold text-lg mb-1">Accesos rápidos</h2>
        <p class="text-sm text-faint mb-4">Todo lo tuyo, a un toque</p>
        <div class="grid sm:grid-cols-2 gap-2.5">
          <router-link v-for="s in shortcuts" :key="s.to" :to="s.to"
            class="group flex items-start gap-3 p-3.5 rounded-xl border hover:bg-brand-500/8 hover:border-brand-400/40 transition"
            style="border-color: var(--border);">
            <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-300 to-brand-600 text-white grid place-items-center shadow-soft transition-transform group-hover:scale-105 shrink-0"><Icon :name="s.icon" :size="20" /></span>
            <div class="min-w-0">
              <p class="font-semibold text-sm text-body">{{ s.label }}</p>
              <p class="text-xs text-faint mt-0.5">{{ s.sub }}</p>
            </div>
            <Icon name="chevronRight" :size="16" class="ml-auto self-center text-faint transition-transform group-hover:translate-x-0.5 shrink-0" />
          </router-link>
        </div>
      </div>

      <!-- Seguridad -->
      <div class="card card-pad animate-fade-up" style="animation-delay: 80ms">
        <h2 class="font-display font-bold text-lg mb-1">Seguridad</h2>
        <p class="text-sm text-faint mb-3">Protege el acceso a tu cuenta</p>

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
