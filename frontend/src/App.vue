<script setup>
import { onMounted, watch } from 'vue'
import Navbar from './components/Navbar.vue'
import MobileNav from './components/MobileNav.vue'
import ToastHost from './components/ui/ToastHost.vue'
import { useAuth } from './store/auth'
import { useNotifications } from './store/notifications'

const auth = useAuth()
const notif = useNotifications()

function boot() {
  if (auth.isAuthed) { notif.load().catch(() => {}); notif.subscribe() }
}
onMounted(boot)
watch(() => auth.token, boot)
</script>

<template>
  <Navbar />
  <main class="max-w-7xl mx-auto px-4 sm:px-5 py-4 sm:py-6 pb-24 md:pb-10 min-h-[70vh]">
    <!-- El <div> envolvente da a <transition> un único nodo raíz.
         Sin él, las vistas que devuelven fragmentos (varias secciones)
         no pueden animarse y con mode="out-in" no llegan a montarse. -->
    <router-view v-slot="{ Component }">
      <transition name="route" mode="out-in">
        <div>
          <component :is="Component" />
        </div>
      </transition>
    </router-view>
  </main>
  <MobileNav />
  <ToastHost />
</template>
