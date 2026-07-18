<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useNotifications } from '../store/notifications'

const auth = useAuth()
const notif = useNotifications()
const router = useRouter()
const unread = computed(() => notif.unread)

async function logout() { await auth.logout(); router.push('/login') }
</script>

<template>
  <header class="bg-white border-b sticky top-0 z-20">
    <div class="max-w-6xl mx-auto px-4 h-14 flex items-center gap-4">
      <router-link to="/" class="font-bold text-brand text-lg">Re<span class="text-slate-800">Closet</span></router-link>
      <router-link to="/catalog" class="text-sm hover:text-brand">Catálogo</router-link>
      <template v-if="auth.isAuthed">
        <router-link to="/my-items" class="text-sm hover:text-brand">Mis prendas</router-link>
        <router-link to="/chat" class="text-sm hover:text-brand">Chat</router-link>
      </template>
      <div class="ml-auto flex items-center gap-3">
        <template v-if="auth.isAuthed">
          <router-link to="/items/new" class="bg-brand text-white text-sm px-3 py-1.5 rounded-md hover:bg-brand-dark">+ Publicar</router-link>
          <router-link to="/notifications" class="relative text-slate-600 hover:text-brand" title="Notificaciones">
            <span>🔔</span>
            <span v-if="unread > 0" class="absolute -top-2 -right-2 bg-rose-500 text-white text-[10px] rounded-full px-1.5">{{ unread }}</span>
          </router-link>
          <router-link to="/profile" class="text-sm text-slate-600 hover:text-brand">{{ auth.user?.name }}</router-link>
          <button @click="logout" class="text-sm text-slate-400 hover:text-rose-500">Salir</button>
        </template>
        <template v-else>
          <router-link to="/login" class="text-sm hover:text-brand">Entrar</router-link>
          <router-link to="/register" class="bg-brand text-white text-sm px-3 py-1.5 rounded-md hover:bg-brand-dark">Crear cuenta</router-link>
        </template>
      </div>
    </div>
  </header>
</template>
