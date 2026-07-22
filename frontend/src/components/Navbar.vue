<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useNotifications } from '../store/notifications'
import NotificationsModal from './NotificationsModal.vue'
import Icon from './ui/Icon.vue'
import ThemeToggle from './ui/ThemeToggle.vue'

const auth = useAuth()
const notif = useNotifications()
const router = useRouter()
const route = useRoute()
const unread = computed(() => notif.unread)
const menuOpen = ref(false)
const notifOpen = ref(false)
const isActive = (p) => route.path === p || (p !== '/' && route.path.startsWith(p))

const links = [
  { to: '/catalog', label: 'Catálogo', icon: 'compass' },
  { to: '/my-items', label: 'Mis publicaciones', icon: 'bag', auth: true },
  { to: '/chat', label: 'Mensajes', icon: 'message', auth: true },
]

function toggleMenu() {
  menuOpen.value = !menuOpen.value
  if (menuOpen.value) document.addEventListener('click', close, { once: true })
}
function close() { menuOpen.value = false }
async function logout() { close(); await auth.logout(); router.push('/login') }
onBeforeUnmount(() => document.removeEventListener('click', close))
</script>

<template>
  <header class="sticky top-0 z-30 glass-strong border-0 border-b" style="border-color: var(--border);">
    <div class="max-w-7xl mx-auto px-4 sm:px-5 h-16 flex items-center gap-2">
      <router-link to="/" class="group flex items-center gap-2.5 mr-1 shrink-0" aria-label="ReCloset inicio">
        <span class="relative w-9 h-9 rounded-xl bg-gradient-to-br from-brand-400 to-brand-700 text-white grid place-items-center font-bold shadow-glow font-display transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3">
          R
          <span class="absolute -inset-0.5 rounded-xl bg-brand-400/30 blur-md -z-10 opacity-0 group-hover:opacity-100 transition-opacity"></span>
        </span>
        <span class="font-display font-extrabold text-lg tracking-tight hidden sm:block">Re<span class="text-gradient">Closet</span></span>
      </router-link>

      <nav class="hidden md:flex items-center gap-0.5 ml-1">
        <template v-for="l in links" :key="l.to">
          <router-link v-if="!l.auth || auth.isAuthed" :to="l.to" class="nav-link" :class="{ 'nav-active': isActive(l.to) }">
            <Icon :name="l.icon" :size="17" /> {{ l.label }}
          </router-link>
        </template>
      </nav>

      <div class="ml-auto flex items-center gap-1">
        <router-link v-if="auth.isAuthed" to="/items/new" class="btn btn-primary btn-sm hidden sm:inline-flex mr-1">
          <Icon name="plus" :size="17" /> Publicar
        </router-link>
        <ThemeToggle />
        <template v-if="auth.isAuthed">
          <button @click="notifOpen = true"
            class="relative w-10 h-10 grid place-items-center rounded-xl text-muted hover:bg-brand-500/10 hover:text-brand-700 dark:hover:text-brand-300 transition"
            :aria-label="`Notificaciones${unread ? ', ' + unread + ' sin leer' : ''}`">
            <Icon name="bell" :size="20" />
            <span v-if="unread > 0"
              class="absolute top-1 right-1 min-w-[17px] h-[17px] px-1 bg-gradient-to-br from-danger-400 to-danger-600 text-white text-[10px] font-bold rounded-full grid place-items-center ring-2 ring-white/70 dark:ring-black/40 animate-pop">
              {{ unread > 9 ? '9+' : unread }}
            </span>
          </button>

          <span class="hidden sm:block w-px h-6 mx-1.5 shrink-0" style="background: var(--border);"></span>

          <div class="relative hidden sm:block" @click.stop>
            <button @click="toggleMenu" class="flex items-center gap-2 rounded-xl p-1 sm:pr-2 ring-1 ring-transparent hover:bg-brand-500/10 hover:ring-[var(--border)] transition"
              :aria-expanded="menuOpen" aria-haspopup="menu" aria-label="Menú de usuario">
              <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-300 to-brand-600 text-white grid place-items-center text-sm font-bold uppercase shadow-soft shrink-0">{{ auth.user?.name?.[0] || 'U' }}</span>
              <span class="text-sm font-semibold text-body max-w-[130px] truncate hidden lg:block">{{ auth.user?.name }}</span>
              <Icon name="chevronRight" :size="15" class="text-faint rotate-90 transition hidden sm:block" :class="{ '-rotate-90': menuOpen }" />
            </button>
            <Transition name="modal">
              <div v-if="menuOpen" role="menu" class="absolute right-0 mt-2 w-56 card-glass p-1.5 animate-pop origin-top-right z-40">
                <div class="px-2.5 py-2 border-b mb-1" style="border-color: var(--border-soft);">
                  <p class="text-sm font-semibold text-body truncate">{{ auth.user?.name }}</p>
                  <p class="text-xs text-faint truncate">{{ auth.user?.email }}</p>
                </div>
                <router-link to="/profile" class="menu-item" role="menuitem"><Icon name="user" :size="17" /> Mi perfil</router-link>
                <router-link to="/my-items" class="menu-item" role="menuitem"><Icon name="bag" :size="17" /> Mis publicaciones</router-link>
                <router-link to="/chat" class="menu-item" role="menuitem"><Icon name="message" :size="17" /> Mensajes</router-link>
                <router-link to="/notifications" class="menu-item" role="menuitem"><Icon name="bell" :size="17" /> Notificaciones</router-link>
                <div class="h-px my-1" style="background: var(--border-soft);"></div>
                <button @click="logout" class="menu-item w-full !text-danger-600 hover:!bg-danger-500/10" role="menuitem"><Icon name="logout" :size="17" /> Cerrar sesión</button>
              </div>
            </Transition>
          </div>
        </template>
        <template v-else>
          <router-link to="/login" class="btn btn-ghost btn-sm">Entrar</router-link>
          <router-link to="/register" class="btn btn-primary btn-sm">Crear cuenta</router-link>
        </template>
      </div>
    </div>
  </header>

  <NotificationsModal :open="notifOpen" @close="notifOpen = false" />
</template>

<style scoped>
.nav-link { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .8rem; border-radius: .75rem; font-size: .875rem; font-weight: 500; color: var(--text-muted); transition: background .18s, color .18s; }
.nav-link:hover { background: rgba(16,185,129,.1); color: var(--brand-strong); }
:global(.dark) .nav-link:hover { color: var(--mint); }
.nav-active { background: rgba(16,185,129,.14); color: var(--brand-strong); font-weight: 600; }
:global(.dark) .nav-active { color: var(--mint); }
.menu-item { display: flex; align-items: center; gap: .6rem; padding: .55rem .65rem; border-radius: .65rem; font-size: .875rem; color: var(--text-soft); transition: background .14s, color .14s; }
.menu-item:hover { background: rgba(16,185,129,.1); color: var(--brand-strong); }
:global(.dark) .menu-item:hover { color: var(--mint); }
</style>
