<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '../store/auth'
import { useNotifications } from '../store/notifications'
import Icon from './ui/Icon.vue'
const route = useRoute()
const auth = useAuth()
const notif = useNotifications()
const unread = computed(() => notif.unread)
const active = (p) => route.path === p || (p !== '/' && route.path.startsWith(p))
</script>
<template>
  <nav class="md:hidden fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur border-t border-slate-200 pb-[env(safe-area-inset-bottom)]">
    <div class="grid" :class="auth.isAuthed ? 'grid-cols-5' : 'grid-cols-2'">
      <router-link to="/" class="tab" :class="{ 'tab-on': route.path === '/' }" aria-label="Inicio">
        <Icon name="home" :size="21" /><span class="lbl">Inicio</span>
      </router-link>
      <router-link to="/catalog" class="tab" :class="{ 'tab-on': active('/catalog') }" aria-label="Explorar">
        <Icon name="compass" :size="21" /><span class="lbl">Explorar</span>
      </router-link>
      <template v-if="auth.isAuthed">
        <router-link to="/items/new" class="tab" aria-label="Publicar">
          <span class="w-11 h-11 -mt-5 rounded-2xl bg-brand text-white grid place-items-center shadow-card-hover">
            <Icon name="plus" :size="24" />
          </span>
        </router-link>
        <router-link to="/chat" class="tab" :class="{ 'tab-on': active('/chat') }" aria-label="Mensajes">
          <Icon name="message" :size="21" /><span class="lbl">Mensajes</span>
        </router-link>
        <button @click="notif.showModal = true" class="tab relative" :class="{ 'tab-on': notif.showModal }" aria-label="Notificaciones">
          <Icon name="bell" :size="21" /><span class="lbl">Avisos</span>
          <span v-if="unread > 0" class="absolute top-1 right-1/4 translate-x-3 min-w-[15px] h-[15px] px-1 bg-rose-500 text-white text-[9px] font-bold rounded-full grid place-items-center">{{ unread > 9 ? '9+' : unread }}</span>
        </button>
      </template>
      <template v-else>
        <router-link to="/login" class="tab" :class="{ 'tab-on': active('/login') }" aria-label="Entrar">
          <Icon name="user" :size="21" /><span class="lbl">Entrar</span>
        </router-link>
      </template>
    </div>
  </nav>
</template>
<style scoped>
.tab { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; padding: .5rem 0 .55rem; color: #94a3b8; font-size: .68rem; font-weight: 500; background: none; border: none; width: 100%; cursor: pointer; outline: none; }
.tab-on { color: #0f766e; }
.lbl { line-height: 1; }
</style>
