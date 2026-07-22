<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import Icon from '../components/ui/Icon.vue'
const auth = useAuth(); const router = useRouter()
const confirmOut = ref(false); const loggingOut = ref(false)
const shortcuts = [
  { to: '/my-items', icon: 'bag', label: 'Mis publicaciones' },
  { to: '/chat', icon: 'message', label: 'Mensajes' },
  { to: '/notifications', icon: 'bell', label: 'Notificaciones' },
  { to: '/items/new', icon: 'plus', label: 'Publicar prenda' },
]
async function logout() { loggingOut.value = true; await auth.logout(); router.push('/login') }
</script>
<template>
  <div class="max-w-md mx-auto">
    <div class="card-glass overflow-hidden animate-fade-up shadow-glow">
      <div class="relative h-28 bg-gradient-to-br from-brand-700 via-brand-500 to-brand-300 overflow-hidden">
        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-lime-400/25 blur-xl animate-float"></div>
        <div class="absolute inset-0 opacity-[0.08]" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 30px 30px;"></div>
      </div>
      <div class="px-5 pb-5 -mt-11">
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 shadow-card grid place-items-center text-3xl font-display font-extrabold text-white uppercase ring-4 ring-white dark:ring-[#121e19]">
          {{ auth.user?.name?.[0] || 'U' }}
        </div>
        <h1 class="font-display font-bold text-xl mt-3">{{ auth.user?.name }}</h1>
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
  </div>

  <ConfirmDialog :open="confirmOut" :loading="loggingOut"
    title="Cerrar sesión" message="¿Seguro que quieres cerrar tu sesión?"
    confirmText="Cerrar sesión" @confirm="logout" @cancel="confirmOut = false" />
</template>
