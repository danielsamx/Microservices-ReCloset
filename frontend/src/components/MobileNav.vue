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
  <nav class="md:hidden fixed bottom-0 inset-x-0 z-30 glass-strong border-t"
    style="padding-bottom: env(safe-area-inset-bottom); border-color: var(--border)" aria-label="Navegación principal">
    <div class="grid" :class="auth.isAuthed ? 'grid-cols-5' : 'grid-cols-3'">
      <router-link to="/catalog" class="tab" :class="{ 'tab-on': active('/catalog') }">
        <Icon name="compass" :size="20" /><span class="lbl">Explorar</span>
      </router-link>

      <template v-if="auth.isAuthed">
        <router-link to="/my-items" class="tab" :class="{ 'tab-on': active('/my-items') }">
          <Icon name="bag" :size="20" /><span class="lbl">Publicaciones</span>
        </router-link>

        <router-link to="/items/new" class="tab" aria-label="Publicar prenda">
          <span class="w-12 h-12 -mt-5 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 text-white grid place-items-center shadow-glow transition-transform active:scale-90">
            <Icon name="plus" :size="24" />
          </span>
        </router-link>

        <router-link to="/chat" class="tab relative" :class="{ 'tab-on': active('/chat') }">
          <Icon name="message" :size="20" /><span class="lbl">Mensajes</span>
        </router-link>

        <!-- Perfil: antes no era accesible desde móvil -->
        <router-link to="/profile" class="tab relative" :class="{ 'tab-on': active('/profile') }">
          <span class="relative">
            <Icon name="user" :size="20" />
            <span v-if="unread > 0"
              class="absolute -top-1 -right-1.5 min-w-[14px] h-[14px] px-1 bg-danger-500 text-white text-[9px] font-bold rounded-full grid place-items-center">
              {{ unread > 9 ? '9+' : unread }}
            </span>
          </span>
          <span class="lbl">Perfil</span>
        </router-link>
      </template>

      <template v-else>
        <router-link to="/" class="tab" :class="{ 'tab-on': route.path === '/' }">
          <Icon name="home" :size="20" /><span class="lbl">Inicio</span>
        </router-link>
        <router-link to="/login" class="tab" :class="{ 'tab-on': active('/login') }">
          <Icon name="user" :size="20" /><span class="lbl">Entrar</span>
        </router-link>
      </template>
    </div>
  </nav>
</template>

<style scoped>
.tab { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
  min-height: 52px; padding: .45rem .15rem .5rem; color: var(--text-faint); font-size: .64rem; font-weight: 500;
  transition: color .18s ease; }
.tab-on { color: var(--brand-strong); }
:global(.dark) .tab-on { color: var(--mint); }
.lbl { line-height: 1; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
