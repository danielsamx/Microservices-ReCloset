<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../store/auth'
import { useNotifications } from '../store/notifications'
import Icon from './ui/Icon.vue'
import Logo from './ui/Logo.vue'

const auth = useAuth()
const notif = useNotifications()
const router = useRouter()
const route = useRoute()
const unread = computed(() => notif.unread)
const menuOpen = ref(false)
const isActive = (p) => route.path === p || (p !== '/' && route.path.startsWith(p))

const links = [
  { to: '/catalog', label: 'Catálogo', icon: 'compass' },
  { to: '/my-items', label: 'Mi armario', icon: 'bag', auth: true },
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
  <header class="sticky top-0 z-30 bg-white/85 backdrop-blur border-b border-slate-200/70">
    <div class="max-w-6xl mx-auto px-4 sm:px-5 h-16 flex items-center gap-2">
      <router-link to="/" class="flex items-center gap-2 mr-2 shrink-0" aria-label="ReCloset inicio">
        <Logo :size="34" show-text />
      </router-link>

      <nav class="hidden md:flex items-center gap-1 ml-2">
        <template v-for="l in links" :key="l.to">
          <router-link v-if="!l.auth || auth.isAuthed" :to="l.to" class="nav-link" :class="{ 'nav-active': isActive(l.to) }">
            <Icon :name="l.icon" :size="17" /> {{ l.label }}
          </router-link>
        </template>
      </nav>

      <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
        <template v-if="auth.isAuthed">
          <router-link to="/items/new" class="btn btn-primary btn-sm hidden sm:inline-flex">
            <Icon name="plus" :size="17" /> Publicar
          </router-link>
          <button @click="notif.showModal = true" class="relative w-10 h-10 grid place-items-center rounded-xl text-slate-600 hover:bg-slate-100 transition" aria-label="Notificaciones">
            <Icon name="bell" :size="20" />
            <span v-if="unread > 0" class="absolute top-1.5 right-1.5 min-w-[16px] h-4 px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full grid place-items-center">{{ unread > 9 ? '9+' : unread }}</span>
          </button>

          <!-- User dropdown -->
          <div class="relative hidden sm:block" @click.stop>
            <button @click="toggleMenu" class="flex items-center gap-2 rounded-xl hover:bg-slate-100 py-1 pl-1 pr-2 transition"
              :aria-expanded="menuOpen" aria-haspopup="menu" aria-label="Menú de usuario">
              <span class="w-8 h-8 rounded-lg bg-brand-100 text-brand-800 grid place-items-center text-sm font-bold uppercase">{{ auth.user?.name?.[0] || 'U' }}</span>
              <span class="text-sm font-medium text-slate-700 max-w-[100px] truncate">{{ auth.user?.name }}</span>
              <Icon name="chevronRight" :size="15" class="text-slate-400 rotate-90 transition" :class="{ '-rotate-90': menuOpen }" />
            </button>
            <Transition name="modal">
              <div v-if="menuOpen" role="menu" class="absolute right-0 mt-2 w-52 card p-1.5 animate-pop origin-top-right">
                <div class="px-2.5 py-2 border-b border-slate-100 mb-1">
                  <p class="text-sm font-semibold text-slate-800 truncate">{{ auth.user?.name }}</p>
                  <p class="text-xs text-slate-400 truncate">{{ auth.user?.email }}</p>
                </div>
                <router-link to="/profile" class="menu-item" role="menuitem"><Icon name="user" :size="17" /> Mi perfil</router-link>
                <router-link to="/my-items" class="menu-item" role="menuitem"><Icon name="bag" :size="17" /> Mi armario</router-link>
                <button @click="logout" class="menu-item w-full text-rose-600 hover:bg-rose-50" role="menuitem"><Icon name="logout" :size="17" /> Cerrar sesión</button>
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
</template>

<style scoped>
.nav-link { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .8rem; border-radius: .7rem; font-size: .9rem; font-weight: 500; color: #475569; transition: background .15s, color .15s; }
.nav-link:hover { background: #f1f5f9; color: #0f172a; }
.nav-active { background: #f0fdfa; color: #0f766e; font-weight: 600; }
.menu-item { display: flex; align-items: center; gap: .6rem; padding: .55rem .65rem; border-radius: .6rem; font-size: .875rem; color: #334155; transition: background .12s; }
.menu-item:hover { background: #f1f5f9; }
</style>
