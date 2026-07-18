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
    <div class="card overflow-hidden animate-fade-up">
      <div class="h-24 bg-gradient-to-br from-brand-600 to-teal-500"></div>
      <div class="px-5 pb-5 -mt-10">
        <div class="w-20 h-20 rounded-2xl bg-white shadow-card grid place-items-center text-3xl font-display font-extrabold text-brand-700 uppercase border-4 border-white">
          {{ auth.user?.name?.[0] || 'U' }}
        </div>
        <h1 class="font-display font-bold text-xl mt-3">{{ auth.user?.name }}</h1>
        <p class="text-sm text-slate-400">{{ auth.user?.email }}</p>

        <div class="grid gap-2 mt-5">
          <router-link v-for="s in shortcuts" :key="s.to" :to="s.to" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition border border-slate-100">
            <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-700 grid place-items-center"><Icon :name="s.icon" :size="18" /></span>
            <span class="font-medium text-sm">{{ s.label }}</span>
            <Icon name="chevronRight" :size="16" class="ml-auto text-slate-300" />
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
